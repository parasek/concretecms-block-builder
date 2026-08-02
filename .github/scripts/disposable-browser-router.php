<?php

declare(strict_types=1);

$configuredPublicRoot = getenv('BLOCK_BUILDER_BROWSER_SERVER_PUBLIC_ROOT');
$expectedSiteIdentifier = getenv('BLOCK_BUILDER_BROWSER_SERVER_SITE_ID');
$publicRoot = is_string($configuredPublicRoot) ? realpath($configuredPublicRoot) : false;
$packageRoot = realpath(dirname(__DIR__, 2));
if (
    $publicRoot === false
    || !is_dir($publicRoot)
    || $packageRoot === false
    || !is_dir($packageRoot)
    || !is_string($configuredPublicRoot)
    || is_link($configuredPublicRoot)
    || !is_string($expectedSiteIdentifier)
    || preg_match('/\A[a-zA-Z0-9_-]{16,128}\z/D', $expectedSiteIdentifier) !== 1
) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Disposable browser server guard is invalid.';

    return true;
}

try {
    require_once $packageRoot
        . DIRECTORY_SEPARATOR . 'tests'
        . DIRECTORY_SEPARATOR . 'Integration'
        . DIRECTORY_SEPARATOR . 'Support'
        . DIRECTORY_SEPARATOR . 'DisposableEnvironmentGuard.php';
    $integrationEnvironment = BlockBuilder\Tests\Integration\Support\DisposableEnvironmentGuard::fromEnvironment(
        expectedPackageRoot: $packageRoot,
    );
} catch (Throwable) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Disposable browser server integration guard is invalid.';

    return true;
}
if (
    !hash_equals($integrationEnvironment->publicRoot, $publicRoot)
    || !hash_equals($integrationEnvironment->packageRoot, $packageRoot)
    || !hash_equals($integrationEnvironment->siteIdentifier, $expectedSiteIdentifier)
) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Disposable browser server integration identity does not match.';

    return true;
}

$blocksRoot = realpath($publicRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'blocks');
$markerPath = dirname($publicRoot) . DIRECTORY_SEPARATOR . '.block-builder-integration-environment.json';
clearstatcache(true, $markerPath);
$markerMetadata = @lstat($markerPath);
if (
    $blocksRoot === false
    || $markerMetadata === false
    || ($markerMetadata['mode'] & 0170000) !== 0100000
    || ($markerMetadata['mode'] & 0777) !== 0600
    || is_link($markerPath)
    || !is_readable($markerPath)
    || $markerMetadata['size'] < 1
    || $markerMetadata['size'] > 4096
) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Disposable browser server marker is invalid.';

    return true;
}

$markerContents = file_get_contents($markerPath);
try {
    $marker = is_string($markerContents)
        ? json_decode($markerContents, true, 16, JSON_THROW_ON_ERROR)
        : null;
} catch (JsonException) {
    $marker = null;
}
$siteIdentifier = is_array($marker) ? ($marker['siteIdentifier'] ?? null) : null;
$databaseName = is_array($marker) ? ($marker['databaseName'] ?? null) : null;
if (
    !is_array($marker)
    || array_is_list($marker)
    || ($marker['purpose'] ?? null) !== 'block_builder_integration'
    || ($marker['publicRoot'] ?? null) !== $publicRoot
    || ($marker['blocksRoot'] ?? null) !== $blocksRoot
    || !is_string($databaseName)
    || preg_match('/\Ablock_builder_test_[a-z0-9_]+\z/D', $databaseName) !== 1
    || !is_string($siteIdentifier)
    || preg_match('/\A[a-zA-Z0-9_-]{16,128}\z/D', $siteIdentifier) !== 1
    || !hash_equals($expectedSiteIdentifier, $siteIdentifier)
) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Disposable browser server marker does not match the configured site.';

    return true;
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
if ($requestPath === '/__block_builder_test_environment') {
    header('Cache-Control: no-store');
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Block-Builder-Test-Site: ' . $siteIdentifier);
    echo json_encode([
        'purpose' => 'block_builder_browser_test',
        'siteIdentifier' => $siteIdentifier,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    return true;
}

$staticPath = realpath($publicRoot . DIRECTORY_SEPARATOR . ltrim($requestPath, '/'));
if (
    $staticPath !== false
    && is_file($staticPath)
    && str_starts_with($staticPath, $publicRoot . DIRECTORY_SEPARATOR)
) {
    return false;
}

defined('DIR_BASE') or define('DIR_BASE', $publicRoot);
$_SERVER['SCRIPT_FILENAME'] = $publicRoot . DIRECTORY_SEPARATOR . 'index.php';
require $publicRoot . DIRECTORY_SEPARATOR . 'index.php';

return true;
