<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Integration\Support;

use JsonException;
use RuntimeException;
use Throwable;

final readonly class DisposableEnvironmentGuard
{
    public const string MARKER_FILENAME = '.block-builder-integration-environment.json';

    private const string PURPOSE = 'block_builder_integration';

    private const int MAX_DATABASE_CONFIGURATION_BYTES = 65536;

    private function __construct(
        public string $publicRoot,
        public string $blocksRoot,
        public string $databaseName,
        public string $siteIdentifier,
        public string $packageRoot,
    ) {
    }

    /**
     * @param array<string, string>|null $environment
     */
    public static function fromEnvironment(?array $environment = null, ?string $expectedPackageRoot = null): self
    {
        $environment ??= self::readProcessEnvironment();
        if (($environment['BLOCK_BUILDER_INTEGRATION'] ?? '') !== '1') {
            throw new RuntimeException(
                'Integration tests are disabled. Set BLOCK_BUILDER_INTEGRATION=1 only for a disposable CMS installation.',
            );
        }

        $publicRoot = self::requireRealDirectory(
            $environment,
            'BLOCK_BUILDER_INTEGRATION_PUBLIC_ROOT',
        );
        if (!is_file($publicRoot . DIRECTORY_SEPARATOR . 'concrete' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'configure.php')) {
            throw new RuntimeException('The disposable public root does not contain a Concrete CMS core.');
        }
        if (!is_file(dirname($publicRoot) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
            throw new RuntimeException('The disposable Concrete project does not contain a Composer autoloader.');
        }
        $blocksRoot = self::requireRealDirectory(
            $environment,
            'BLOCK_BUILDER_INTEGRATION_BLOCKS_ROOT',
        );
        $expectedBlocksRoot = realpath(
            $publicRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'blocks',
        );
        if ($expectedBlocksRoot === false || !hash_equals($expectedBlocksRoot, $blocksRoot)) {
            throw new RuntimeException('The configured block directory is not the disposable site application/blocks directory.');
        }

        $databaseName = self::requireEnvironmentValue($environment, 'BLOCK_BUILDER_INTEGRATION_DATABASE_NAME');
        if (preg_match('/\Ablock_builder_test_[a-z0-9_]+\z/D', $databaseName) !== 1) {
            throw new RuntimeException('The disposable database name must begin with "block_builder_test_".');
        }

        $siteIdentifier = self::requireEnvironmentValue($environment, 'BLOCK_BUILDER_INTEGRATION_SITE_ID');
        if (preg_match('/\A[a-zA-Z0-9_-]{16,128}\z/D', $siteIdentifier) !== 1) {
            throw new RuntimeException('The disposable site identifier must contain 16-128 safe characters.');
        }

        $packageRoot = realpath($expectedPackageRoot ?? dirname(__DIR__, 3));
        if ($packageRoot === false || !is_dir($packageRoot)) {
            throw new RuntimeException('The Block Builder package root could not be resolved.');
        }
        $installedPackageRoot = realpath(
            $publicRoot . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'block_builder',
        );
        if ($installedPackageRoot === false || !hash_equals($installedPackageRoot, $packageRoot)) {
            throw new RuntimeException('The integration suite must run from the Block Builder package installed in the disposable site.');
        }

        self::assertMarkerMatches(
            publicRoot: $publicRoot,
            blocksRoot: $blocksRoot,
            databaseName: $databaseName,
            siteIdentifier: $siteIdentifier,
        );
        self::assertConfiguredDatabaseMatches($publicRoot, $databaseName);

        return new self(
            publicRoot: $publicRoot,
            blocksRoot: $blocksRoot,
            databaseName: $databaseName,
            siteIdentifier: $siteIdentifier,
            packageRoot: $packageRoot,
        );
    }

    public function assertActiveDatabase(object $connection): void
    {
        if (!method_exists($connection, 'getDatabase')) {
            throw new RuntimeException('The active database connection cannot report its database name.');
        }

        $activeDatabaseName = (string) $connection->getDatabase();
        if (!hash_equals($this->databaseName, $activeDatabaseName)) {
            throw new RuntimeException(sprintf(
                'Refusing integration writes: active database "%s" does not match disposable database "%s".',
                $activeDatabaseName,
                $this->databaseName,
            ));
        }
    }

    /**
     * @return array<string, string>
     */
    private static function readProcessEnvironment(): array
    {
        $environment = [];
        foreach ([
            'BLOCK_BUILDER_INTEGRATION',
            'BLOCK_BUILDER_INTEGRATION_PUBLIC_ROOT',
            'BLOCK_BUILDER_INTEGRATION_BLOCKS_ROOT',
            'BLOCK_BUILDER_INTEGRATION_DATABASE_NAME',
            'BLOCK_BUILDER_INTEGRATION_SITE_ID',
        ] as $name) {
            $value = getenv($name);
            if (is_string($value)) {
                $environment[$name] = $value;
            }
        }

        return $environment;
    }

    /**
     * @param array<string, string> $environment
     */
    private static function requireRealDirectory(array $environment, string $name): string
    {
        $configuredPath = self::requireEnvironmentValue($environment, $name);
        if (!self::isAbsolutePath($configuredPath)) {
            throw new RuntimeException(sprintf('%s must be an absolute path.', $name));
        }
        self::assertPathContainsNoSymbolicLinks($configuredPath, $name);

        $realPath = realpath($configuredPath);
        if ($realPath === false || !is_dir($realPath)) {
            throw new RuntimeException(sprintf('%s must resolve to an existing directory.', $name));
        }

        return $realPath;
    }

    private static function assertPathContainsNoSymbolicLinks(string $path, string $name): void
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (preg_match('/\A[A-Za-z]:[\\\\\/]/D', $path) === 1) {
            $prefix = substr($normalizedPath, 0, 3);
            $normalizedPath = substr($normalizedPath, 3);
        } else {
            $prefix = str_starts_with($normalizedPath, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';
            $normalizedPath = ltrim($normalizedPath, DIRECTORY_SEPARATOR);
        }

        $candidate = $prefix;
        foreach (array_filter(explode(DIRECTORY_SEPARATOR, $normalizedPath), 'strlen') as $component) {
            $candidate = $candidate === '' || str_ends_with($candidate, DIRECTORY_SEPARATOR)
                ? $candidate . $component
                : $candidate . DIRECTORY_SEPARATOR . $component;
            if (is_link($candidate)) {
                throw new RuntimeException(sprintf('%s must not contain symbolic-link path components.', $name));
            }
        }
    }

    /**
     * @param array<string, string> $environment
     */
    private static function requireEnvironmentValue(array $environment, string $name): string
    {
        $value = trim($environment[$name] ?? '');
        if ($value === '') {
            throw new RuntimeException(sprintf('The required environment variable %s is missing.', $name));
        }

        return $value;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/\A[A-Za-z]:[\\\\\/]/D', $path) === 1;
    }

    private static function assertMarkerMatches(
        string $publicRoot,
        string $blocksRoot,
        string $databaseName,
        string $siteIdentifier,
    ): void {
        $markerPath = dirname($publicRoot) . DIRECTORY_SEPARATOR . self::MARKER_FILENAME;
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
            throw new RuntimeException(sprintf(
                'Disposable environment marker "%s" is missing or unsafe.',
                $markerPath,
            ));
        }

        $markerContents = file_get_contents($markerPath);
        if ($markerContents === false || strlen($markerContents) > 4096) {
            throw new RuntimeException('The disposable environment marker could not be read safely.');
        }

        try {
            $marker = json_decode($markerContents, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The disposable environment marker contains invalid JSON.', previous: $exception);
        }
        if (!is_array($marker) || array_is_list($marker)) {
            throw new RuntimeException('The disposable environment marker must contain a JSON object.');
        }

        $expectedMarker = [
            'purpose' => self::PURPOSE,
            'siteIdentifier' => $siteIdentifier,
            'databaseName' => $databaseName,
            'publicRoot' => $publicRoot,
            'blocksRoot' => $blocksRoot,
        ];
        foreach ($expectedMarker as $key => $expectedValue) {
            $actualValue = $marker[$key] ?? null;
            if (!is_string($actualValue) || !hash_equals($expectedValue, $actualValue)) {
                throw new RuntimeException(sprintf(
                    'Disposable environment marker property "%s" does not match the active test environment.',
                    $key,
                ));
            }
        }
    }

    private static function assertConfiguredDatabaseMatches(string $publicRoot, string $databaseName): void
    {
        $databaseConfigurationPath = $publicRoot
            . DIRECTORY_SEPARATOR . 'application'
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'database.php';
        self::assertPathContainsNoSymbolicLinks(
            $databaseConfigurationPath,
            'The disposable database configuration path',
        );
        clearstatcache(true, $databaseConfigurationPath);
        $metadata = @lstat($databaseConfigurationPath);
        if (
            $metadata === false
            || ($metadata['mode'] & 0170000) !== 0100000
            || is_link($databaseConfigurationPath)
            || !is_file($databaseConfigurationPath)
            || !is_readable($databaseConfigurationPath)
            || $metadata['size'] < 1
            || $metadata['size'] > self::MAX_DATABASE_CONFIGURATION_BYTES
        ) {
            throw new RuntimeException(
                'The disposable Concrete database configuration must be a readable, regular, non-linked file no larger than 65536 bytes.',
            );
        }
        self::assertNoDatabaseConfigurationOverrides(dirname($databaseConfigurationPath));

        try {
            $configuration = (static function (string $configurationPath): mixed {
                return require $configurationPath;
            })($databaseConfigurationPath);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The disposable Concrete database configuration could not be loaded safely.',
                previous: $exception,
            );
        }
        if (!is_array($configuration) || array_is_list($configuration)) {
            throw new RuntimeException('The disposable Concrete database configuration must return an associative array.');
        }

        $defaultConnectionName = $configuration['default-connection'] ?? null;
        if (!is_string($defaultConnectionName) || trim($defaultConnectionName) === '') {
            throw new RuntimeException('The disposable Concrete database configuration must identify a default connection.');
        }
        $connections = $configuration['connections'] ?? null;
        $defaultConnection = is_array($connections) ? ($connections[$defaultConnectionName] ?? null) : null;
        if (!is_array($defaultConnection) || array_is_list($defaultConnection)) {
            throw new RuntimeException('The disposable Concrete database configuration must define its default connection.');
        }

        $configuredDatabaseName = $defaultConnection['database'] ?? null;
        if (!is_string($configuredDatabaseName) || trim($configuredDatabaseName) === '') {
            throw new RuntimeException('The disposable Concrete default connection must define a database name.');
        }
        if (!hash_equals($databaseName, $configuredDatabaseName)) {
            throw new RuntimeException(sprintf(
                'Refusing to boot Concrete: configured database "%s" does not match disposable database "%s".',
                $configuredDatabaseName,
                $databaseName,
            ));
        }
    }

    private static function assertNoDatabaseConfigurationOverrides(string $configurationDirectoryPath): void
    {
        $entries = scandir($configurationDirectoryPath);
        if ($entries === false) {
            throw new RuntimeException('The disposable Concrete configuration directory could not be inspected.');
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $configurationDirectoryPath . DIRECTORY_SEPARATOR . $entry;
            if (is_link($entryPath)) {
                throw new RuntimeException('The disposable Concrete configuration directory must not contain symbolic links.');
            }
            if ($entry !== 'database.php' && str_ends_with($entry, '.database.php')) {
                throw new RuntimeException(sprintf(
                    'Environment-specific database configuration overrides are not allowed in disposable integration sites: %s',
                    $entry,
                ));
            }
            if (is_dir($entryPath)) {
                $environmentDatabasePath = $entryPath . DIRECTORY_SEPARATOR . 'database.php';
                if (file_exists($environmentDatabasePath) || is_link($environmentDatabasePath)) {
                    throw new RuntimeException(sprintf(
                        'Environment-specific database configuration overrides are not allowed in disposable integration sites: %s',
                        $entry . DIRECTORY_SEPARATOR . 'database.php',
                    ));
                }
            }
        }
    }
}
