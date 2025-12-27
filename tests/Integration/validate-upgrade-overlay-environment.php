<?php

declare(strict_types=1);

use BlockBuilder\Tests\Integration\Support\DisposableEnvironmentGuard;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'DisposableEnvironmentGuard.php';

if (count($argv) !== 5) {
    fwrite(STDERR, "Usage: php validate-upgrade-overlay-environment.php <site-root> <package-root> <database-name> <legacy-version>\n");
    exit(2);
}

[$scriptPath, $configuredSiteRoot, $configuredPackageRoot, $databaseName, $legacyVersion] = $argv;
unset($scriptPath);

if (preg_match('/\Ablock_builder_test_[a-z0-9_]+\z/D', $databaseName) !== 1) {
    throw new RuntimeException('Upgrade overlays require a disposable block_builder_test_* database.');
}
if (preg_match('/\A\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?\z/D', $legacyVersion) !== 1) {
    throw new RuntimeException('The legacy source version is not a supported semantic version.');
}

$siteRoot = realpath($configuredSiteRoot);
$packageRoot = realpath($configuredPackageRoot);
if ($siteRoot === false || !is_dir($siteRoot) || is_link($configuredSiteRoot)) {
    throw new RuntimeException('The upgrade site root must be an existing, non-linked directory.');
}
$publicRoot = realpath($siteRoot . DIRECTORY_SEPARATOR . 'public');
$blocksRoot = realpath($siteRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'blocks');
$expectedPackageRoot = $siteRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'block_builder';
if (
    $publicRoot === false
    || $blocksRoot === false
    || $packageRoot === false
    || !hash_equals($expectedPackageRoot, $packageRoot)
    || is_link($configuredPackageRoot)
) {
    throw new RuntimeException('The upgrade package must be the non-linked Block Builder directory in the guarded site.');
}

$markerPath = $siteRoot . DIRECTORY_SEPARATOR . DisposableEnvironmentGuard::MARKER_FILENAME;
clearstatcache(true, $markerPath);
$markerMetadata = @lstat($markerPath);
if (
    $markerMetadata === false
    || ($markerMetadata['mode'] & 0170000) !== 0100000
    || ($markerMetadata['mode'] & 0777) !== 0600
    || is_link($markerPath)
    || !is_readable($markerPath)
    || $markerMetadata['size'] < 1
    || $markerMetadata['size'] > 4096
) {
    throw new RuntimeException('The private disposable environment marker is missing or unsafe.');
}
$markerContents = file_get_contents($markerPath);
if ($markerContents === false) {
    throw new RuntimeException('The private disposable environment marker could not be read.');
}
$marker = json_decode($markerContents, true, 16, JSON_THROW_ON_ERROR);
if (!is_array($marker) || array_is_list($marker)) {
    throw new RuntimeException('The private disposable environment marker must contain a JSON object.');
}
$siteIdentifier = $marker['siteIdentifier'] ?? null;
if (!is_string($siteIdentifier) || preg_match('/\A[a-zA-Z0-9_-]{16,128}\z/D', $siteIdentifier) !== 1) {
    throw new RuntimeException('The private disposable environment marker has an invalid site identifier.');
}
$expectedMarker = [
    'purpose' => 'block_builder_integration',
    'databaseName' => $databaseName,
    'publicRoot' => $publicRoot,
    'blocksRoot' => $blocksRoot,
];
foreach ($expectedMarker as $key => $expectedValue) {
    $actualValue = $marker[$key] ?? null;
    if (!is_string($actualValue) || !hash_equals($expectedValue, $actualValue)) {
        throw new RuntimeException(sprintf('The private disposable environment marker property "%s" does not match.', $key));
    }
}

$controllerPath = $packageRoot . DIRECTORY_SEPARATOR . 'controller.php';
clearstatcache(true, $controllerPath);
$controllerMetadata = @lstat($controllerPath);
if (
    $controllerMetadata === false
    || ($controllerMetadata['mode'] & 0170000) !== 0100000
    || is_link($controllerPath)
    || !is_readable($controllerPath)
    || $controllerMetadata['size'] < 1
    || $controllerMetadata['size'] > 1048576
) {
    throw new RuntimeException('The installed legacy package controller is missing or unsafe.');
}
$controllerContents = file_get_contents($controllerPath);
if ($controllerContents === false) {
    throw new RuntimeException('The installed legacy package controller could not be read.');
}
$quotedVersion = preg_quote($legacyVersion, '/');
if (preg_match('/\bprotected(?:\s+string)?\s+\$pkgVersion\s*=\s*([\'\"])' . $quotedVersion . '\1\s*;/', $controllerContents) !== 1) {
    throw new RuntimeException(sprintf(
        'The installed package source does not declare the expected legacy version "%s".',
        $legacyVersion,
    ));
}

printf("Validated disposable upgrade source version: %s\n", $legacyVersion);
