<?php

declare(strict_types=1);

use BlockBuilder\Tests\Integration\Support\DisposableEnvironmentGuard;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'DisposableEnvironmentGuard.php';

if (count($argv) !== 5) {
    fwrite(STDERR, "Usage: php create-environment-marker.php <public-root> <blocks-root> <database-name> <site-identifier>\n");
    exit(2);
}

[$scriptPath, $configuredPublicRoot, $configuredBlocksRoot, $databaseName, $siteIdentifier] = $argv;
unset($scriptPath);

$assertSafeAbsolutePath = static function (string $path, string $label): void {
    if (
        (!str_starts_with($path, DIRECTORY_SEPARATOR) && preg_match('/\A[A-Za-z]:[\\\\\/]/D', $path) !== 1)
        || preg_match('~(?:\A|[\\\\/])\.\.?(?:[\\\\/]|\z)~D', $path) === 1
    ) {
        throw new RuntimeException(sprintf('%s must be an absolute path without . or .. components.', $label));
    }

    $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    if (preg_match('/\A[A-Za-z]:[\\\\\/]/D', $path) === 1) {
        $candidate = substr($normalizedPath, 0, 3);
        $normalizedPath = substr($normalizedPath, 3);
    } else {
        $candidate = DIRECTORY_SEPARATOR;
        $normalizedPath = ltrim($normalizedPath, DIRECTORY_SEPARATOR);
    }
    foreach (array_filter(explode(DIRECTORY_SEPARATOR, $normalizedPath), 'strlen') as $component) {
        $candidate = str_ends_with($candidate, DIRECTORY_SEPARATOR)
            ? $candidate . $component
            : $candidate . DIRECTORY_SEPARATOR . $component;
        if (is_link($candidate)) {
            throw new RuntimeException(sprintf('%s must not contain symbolic-link path components.', $label));
        }
    }
};
$assertSafeAbsolutePath($configuredPublicRoot, 'The marker public root');
$assertSafeAbsolutePath($configuredBlocksRoot, 'The marker blocks root');

$publicRoot = realpath($configuredPublicRoot);
$blocksRoot = realpath($configuredBlocksRoot);
if ($publicRoot === false || !is_dir($publicRoot)) {
    throw new RuntimeException('The marker public root must resolve to an existing directory.');
}
if ($blocksRoot === false || !is_dir($blocksRoot)) {
    throw new RuntimeException('The marker blocks root must resolve to an existing directory.');
}
$expectedBlocksRoot = realpath($publicRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'blocks');
if ($expectedBlocksRoot === false || !hash_equals($expectedBlocksRoot, $blocksRoot)) {
    throw new RuntimeException('The marker blocks root must be the public root application/blocks directory.');
}
if (preg_match('/\Ablock_builder_test_[a-z0-9_]+\z/D', $databaseName) !== 1) {
    throw new RuntimeException('The marker database name must begin with "block_builder_test_".');
}
if (preg_match('/\A[a-zA-Z0-9_-]{16,128}\z/D', $siteIdentifier) !== 1) {
    throw new RuntimeException('The marker site identifier must contain 16-128 safe characters.');
}

$marker = [
    'purpose' => 'block_builder_integration',
    'siteIdentifier' => $siteIdentifier,
    'databaseName' => $databaseName,
    'publicRoot' => $publicRoot,
    'blocksRoot' => $blocksRoot,
];
$markerPath = dirname($publicRoot) . DIRECTORY_SEPARATOR . DisposableEnvironmentGuard::MARKER_FILENAME;
$encodedMarker = json_encode($marker, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
if (file_exists($markerPath) || is_link($markerPath)) {
    throw new RuntimeException(sprintf('Refusing to replace existing disposable environment marker "%s".', $markerPath));
}

$temporaryMarkerPath = null;
$temporaryMarkerHandle = null;
try {
    for ($attempt = 0; $attempt < 10; ++$attempt) {
        $candidatePath = dirname($markerPath)
            . DIRECTORY_SEPARATOR
            . '.' . DisposableEnvironmentGuard::MARKER_FILENAME
            . '.' . bin2hex(random_bytes(16))
            . '.tmp';
        $candidateHandle = @fopen($candidatePath, 'x+b');
        if (is_resource($candidateHandle)) {
            $temporaryMarkerPath = $candidatePath;
            $temporaryMarkerHandle = $candidateHandle;
            break;
        }
    }
    if (!is_resource($temporaryMarkerHandle) || !is_string($temporaryMarkerPath)) {
        throw new RuntimeException('Unable to create a unique temporary disposable environment marker.');
    }
    if (!chmod($temporaryMarkerPath, 0600)) {
        throw new RuntimeException('Unable to restrict permissions on the temporary disposable environment marker.');
    }

    $remainingContents = $encodedMarker;
    while ($remainingContents !== '') {
        $writtenBytes = fwrite($temporaryMarkerHandle, $remainingContents);
        if ($writtenBytes === false || $writtenBytes === 0) {
            throw new RuntimeException('Unable to write the temporary disposable environment marker.');
        }
        $remainingContents = substr($remainingContents, $writtenBytes);
    }
    if (!fflush($temporaryMarkerHandle)) {
        throw new RuntimeException('Unable to flush the temporary disposable environment marker.');
    }
    if (function_exists('fsync') && !fsync($temporaryMarkerHandle)) {
        throw new RuntimeException('Unable to synchronize the temporary disposable environment marker.');
    }
    fclose($temporaryMarkerHandle);
    $temporaryMarkerHandle = null;

    clearstatcache(true, $markerPath);
    if (file_exists($markerPath) || is_link($markerPath)) {
        throw new RuntimeException(sprintf('Refusing to replace existing disposable environment marker "%s".', $markerPath));
    }
    if (!link($temporaryMarkerPath, $markerPath)) {
        throw new RuntimeException(sprintf('Unable to publish disposable environment marker "%s".', $markerPath));
    }
    if (!unlink($temporaryMarkerPath)) {
        throw new RuntimeException('Unable to remove the temporary disposable environment marker after publication.');
    }
    $temporaryMarkerPath = null;
    clearstatcache(true, $markerPath);
    if ((fileperms($markerPath) & 0777) !== 0600) {
        throw new RuntimeException('The disposable environment marker does not have mode 0600.');
    }
} finally {
    if (is_resource($temporaryMarkerHandle)) {
        fclose($temporaryMarkerHandle);
    }
    if (is_string($temporaryMarkerPath) && (file_exists($temporaryMarkerPath) || is_link($temporaryMarkerPath))) {
        unlink($temporaryMarkerPath);
    }
}
