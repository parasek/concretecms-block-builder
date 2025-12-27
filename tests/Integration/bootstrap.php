<?php

declare(strict_types=1);

use BlockBuilder\Tests\Integration\Support\DisposableEnvironmentGuard;
use Concrete\Core\Foundation\ClassAutoloader;
use Concrete\Core\Package\PackageService;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'DisposableEnvironmentGuard.php';

$guard = DisposableEnvironmentGuard::fromEnvironment();
$publicRoot = $guard->publicRoot;

spl_autoload_register(static function (string $class): void {
    $namespacePrefix = 'BlockBuilder\\Tests\\Integration\\';
    if (!str_starts_with($class, $namespacePrefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($namespacePrefix));
    $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

defined('DIR_BASE') or define('DIR_BASE', $publicRoot);
$_SERVER['SCRIPT_FILENAME'] ??= $publicRoot . DIRECTORY_SEPARATOR . 'index.php';
$_SERVER['PHP_SELF'] ??= '/index.php';

require_once $publicRoot . DIRECTORY_SEPARATOR . 'concrete' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'configure.php';
require_once $publicRoot . DIRECTORY_SEPARATOR . 'concrete' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'autoload.php';

// Concrete's bootstrap/start.php boots the runtime before it returns the application.
// Do not call getRuntime()->boot() a second time here.
/** @var Concrete\Core\Application\Application $application */
$application = require $publicRoot . DIRECTORY_SEPARATOR . 'concrete' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'start.php';
$application->instance('app', $application);
$application->instance(Concrete\Core\Application\Application::class, $application);
$application->instance(Illuminate\Container\Container::class, $application);
$application->instance(ClassAutoloader::class, ClassAutoloader::getInstance());

$connection = $application->make(Concrete\Core\Database\Connection\Connection::class);
$guard->assertActiveDatabase($connection);

$packageController = $application->make(PackageService::class)->getClass('block_builder');
if (!$packageController instanceof Concrete\Package\BlockBuilder\Controller) {
    throw new RuntimeException('The installed Block Builder package controller could not be loaded.');
}
if (!class_exists(BlockBuilder\Block\Factory\BlockConfigDtoFactory::class)) {
    throw new RuntimeException('The installed Block Builder package autoloader could not load package classes.');
}

$GLOBALS['blockBuilderIntegrationApplication'] = $application;
$GLOBALS['blockBuilderIntegrationEnvironmentGuard'] = $guard;
