<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 4);
$publicRoot = $projectRoot . DIRECTORY_SEPARATOR . 'public';

defined('DIR_BASE') or define('DIR_BASE', $publicRoot);
defined('APP_CHARSET') or define('APP_CHARSET', 'UTF-8');

require_once $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once $publicRoot . DIRECTORY_SEPARATOR . 'concrete' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'configure.php';

$config = require $publicRoot . DIRECTORY_SEPARATOR . 'concrete' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
$aliases = array_get($config, 'aliases', []);
$aliasList = new \Concrete\Core\Foundation\ClassAliasList();
$aliasList->registerMultiple($aliases);

$aliasAutoloader = new \Concrete\Core\Foundation\AliasClassLoader($aliasList);
$aliasAutoloader->register();

// Provide Concrete's translation helper with a lightweight, untranslated adapter.
// This initializes no database, packages, or application runtime.
$testApplication = new \Concrete\Core\Application\Application();
$testApplication->instance('app', $testApplication);
\Concrete\Core\Support\Facade\Facade::setFacadeApplication($testApplication);
$localization = new \Concrete\Core\Localization\Localization();
$localization->setTranslatorAdapterRepository(
    new \Concrete\Core\Localization\Translator\TranslatorAdapterRepository(
        new \Concrete\Core\Localization\Translator\Adapter\Plain\TranslatorAdapterFactory(),
    ),
);
$localization->setActiveContext(\Concrete\Core\Localization\Localization::CONTEXT_UI);
$testApplication->instance(\Concrete\Core\Localization\Localization::class, $localization);

spl_autoload_register(static function (string $class): void {
    $testNamespacePrefix = 'BlockBuilder\\Tests\\';
    if (str_starts_with($class, $testNamespacePrefix)) {
        $relativeClass = substr($class, strlen($testNamespacePrefix));
        $testClassPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        if (is_file($testClassPath)) {
            require_once $testClassPath;
        }

        return;
    }

    $namespacePrefix = 'BlockBuilder\\';
    if (!str_starts_with($class, $namespacePrefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($namespacePrefix));
    $sourcePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'BlockBuilder';
    $classPath = $sourcePath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (is_file($classPath)) {
        require_once $classPath;
    }
});
