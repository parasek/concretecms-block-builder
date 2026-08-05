<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Security;

use BlockBuilder\Tests\Integration\Support\DisposableEnvironmentGuard;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

require_once dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'Integration'
    . DIRECTORY_SEPARATOR
    . 'Support'
    . DIRECTORY_SEPARATOR
    . 'DisposableEnvironmentGuard.php';

/**
 * Test type: Disposable-environment security guard unit test.
 *
 * Verifies that destructive integration tests accept only an explicitly opted-in, privately
 * marked, non-symlinked disposable site whose paths and database identity match exactly.
 */
final class DisposableEnvironmentGuardTest extends BlockBuilderTestCase
{
    private const string DATABASE_NAME = 'block_builder_test_guard';
    private const string SITE_IDENTIFIER = 'guard-test-site-123456';

    private Filesystem $filesystem;
    private string $temporaryRoot;
    private string $projectRoot;
    private string $publicRoot;
    private string $blocksRoot;
    private string $packageRoot;
    private string $databaseConfigPath;
    private string $markerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $temporaryBase = realpath(sys_get_temp_dir());
        self::assertNotFalse($temporaryBase);
        $this->temporaryRoot = $temporaryBase
            . DIRECTORY_SEPARATOR
            . 'block-builder-guard-'
            . bin2hex(random_bytes(8));
        $this->projectRoot = $this->temporaryRoot . DIRECTORY_SEPARATOR . 'project';
        $this->publicRoot = $this->projectRoot . DIRECTORY_SEPARATOR . 'public';
        $this->blocksRoot = $this->publicRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'blocks';
        $this->packageRoot = $this->publicRoot . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'block_builder';
        $this->databaseConfigPath = $this->publicRoot
            . DIRECTORY_SEPARATOR
            . 'application'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'database.php';
        $this->markerPath = $this->projectRoot
            . DIRECTORY_SEPARATOR
            . DisposableEnvironmentGuard::MARKER_FILENAME;

        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir([
            $this->blocksRoot,
            $this->packageRoot,
            dirname($this->databaseConfigPath),
            $this->publicRoot . DIRECTORY_SEPARATOR . 'concrete' . DIRECTORY_SEPARATOR . 'bootstrap',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'vendor',
        ], 0700);
        $this->writeFile(
            $this->publicRoot . DIRECTORY_SEPARATOR . 'concrete' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'configure.php',
            "<?php\n",
        );
        $this->writeFile(
            $this->projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
            "<?php\n",
        );
        $this->writeDatabaseConfig(self::DATABASE_NAME);
        $this->writeMarker();
    }

    protected function tearDown(): void
    {
        if (isset($this->markerPath) && is_file($this->markerPath)) {
            @chmod($this->markerPath, 0600);
        }
        if (isset($this->databaseConfigPath) && is_file($this->databaseConfigPath)) {
            @chmod($this->databaseConfigPath, 0600);
        }
        if (isset($this->filesystem, $this->temporaryRoot)) {
            $this->filesystem->remove($this->temporaryRoot);
        }

        parent::tearDown();
    }

    /**
     * Confirms that integration mode must be explicitly enabled before any configured filesystem
     * path is inspected.
     */
    public function testExplicitOptInIsRequiredBeforePathsAreInspected(): void
    {
        $environment = $this->createEnvironment();
        unset($environment['BLOCK_BUILDER_INTEGRATION']);
        $environment['BLOCK_BUILDER_INTEGRATION_PUBLIC_ROOT'] = '/path-that-must-not-be-inspected';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Integration tests are disabled');

        DisposableEnvironmentGuard::fromEnvironment($environment, $this->packageRoot);
    }

    /**
     * Confirms that a fully matching marked site, package, blocks directory, database, and site
     * identity are accepted as disposable.
     */
    public function testExactDisposableEnvironmentIsAccepted(): void
    {
        $guard = DisposableEnvironmentGuard::fromEnvironment(
            $this->createEnvironment(),
            $this->packageRoot,
        );

        self::assertSame(realpath($this->publicRoot), $guard->publicRoot);
        self::assertSame(realpath($this->blocksRoot), $guard->blocksRoot);
        self::assertSame(self::DATABASE_NAME, $guard->databaseName);
        self::assertSame(self::SITE_IDENTIFIER, $guard->siteIdentifier);
        self::assertSame(realpath($this->packageRoot), $guard->packageRoot);
    }

    /**
     * Confirms that relative public or blocks roots cannot be used for destructive integration
     * operations.
     */
    public function testConfiguredRootsMustBeAbsolute(): void
    {
        $environment = $this->createEnvironment();
        $environment['BLOCK_BUILDER_INTEGRATION_PUBLIC_ROOT'] = 'relative/public';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('BLOCK_BUILDER_INTEGRATION_PUBLIC_ROOT must be an absolute path');

        DisposableEnvironmentGuard::fromEnvironment($environment, $this->packageRoot);
    }

    /**
     * Confirms that the writable blocks root must resolve to the selected site's exact
     * application/blocks directory.
     */
    public function testBlocksRootMustBeTheSitesApplicationBlocksDirectory(): void
    {
        $otherDirectory = $this->publicRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'other';
        $this->filesystem->mkdir($otherDirectory);
        $environment = $this->createEnvironment();
        $environment['BLOCK_BUILDER_INTEGRATION_BLOCKS_ROOT'] = $otherDirectory;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('configured block directory is not the disposable site application/blocks directory');

        DisposableEnvironmentGuard::fromEnvironment($environment, $this->packageRoot);
    }

    /**
     * Confirms that a public-root path containing a symbolic-link component is rejected.
     */
    public function testPublicRootMustNotContainSymlinkComponents(): void
    {
        $linkedProjectRoot = $this->temporaryRoot . DIRECTORY_SEPARATOR . 'linked-project';
        if (!@symlink($this->projectRoot, $linkedProjectRoot)) {
            self::markTestSkipped('The current filesystem does not support symbolic links.');
        }
        $environment = $this->createEnvironment();
        $environment['BLOCK_BUILDER_INTEGRATION_PUBLIC_ROOT'] = $linkedProjectRoot . DIRECTORY_SEPARATOR . 'public';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not contain symbolic-link path components');

        DisposableEnvironmentGuard::fromEnvironment($environment, $this->packageRoot);
    }

    /**
     * Confirms that destructive integration tests refuse a project without the disposable-site
     * marker.
     */
    public function testMarkerMustExist(): void
    {
        self::assertTrue(unlink($this->markerPath));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('environment marker');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that the disposable-site marker must be a physical file rather than a symbolic
     * link.
     */
    public function testMarkerMustNotBeLinked(): void
    {
        $targetPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'marker-target.json';
        self::assertTrue(rename($this->markerPath, $targetPath));
        if (!@symlink($targetPath, $this->markerPath)) {
            self::markTestSkipped('The current filesystem does not support symbolic links.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('environment marker');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that an unreadable disposable-site marker cannot authorize integration writes.
     */
    public function testMarkerMustBeReadable(): void
    {
        self::assertTrue(chmod($this->markerPath, 0000));
        if (is_readable($this->markerPath)) {
            self::markTestSkipped('The current filesystem cannot make the marker unreadable.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('environment marker');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that the disposable-site marker must use owner-only permissions.
     */
    public function testMarkerMustUsePrivatePermissions(): void
    {
        self::assertTrue(chmod($this->markerPath, 0644));
        clearstatcache(true, $this->markerPath);
        if ((fileperms($this->markerPath) & 0777) !== 0644) {
            self::markTestSkipped('The current filesystem does not expose POSIX permission modes.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('environment marker');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that every identity recorded in the marker exactly matches the requested site and
     * rejects non-string identity values.
     *
     * @dataProvider markerMismatchProvider
     */
    public function testEveryMarkerIdentityPropertyMustMatch(string $property, mixed $value): void
    {
        $this->writeMarker([$property => $value]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('marker property "%s"', $property));

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    public static function markerMismatchProvider(): array
    {
        return [
            'purpose' => ['purpose', 'another_purpose'],
            'site identifier' => ['siteIdentifier', 'different-site-123456'],
            'database name' => ['databaseName', 'block_builder_test_different'],
            'public root' => ['publicRoot', '/different/public'],
            'blocks root' => ['blocksRoot', '/different/blocks'],
            'non-string value' => ['purpose', ['block_builder_integration']],
        ];
    }

    /**
     * Confirms that invalid JSON in the disposable-site marker fails closed.
     */
    public function testMalformedMarkerJsonIsRejected(): void
    {
        $this->writeFile($this->markerPath, '{"purpose":');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('marker contains invalid JSON');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that the site's database configuration must be a physical file rather than a
     * symbolic link.
     */
    public function testDatabaseConfigMustNotBeLinked(): void
    {
        $targetPath = dirname($this->databaseConfigPath) . DIRECTORY_SEPARATOR . 'database-target.php';
        self::assertTrue(rename($this->databaseConfigPath, $targetPath));
        if (!@symlink($targetPath, $this->databaseConfigPath)) {
            self::markTestSkipped('The current filesystem does not support symbolic links.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('database configuration');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that symbolic links anywhere in the database configuration directory are rejected
     * before booting Concrete.
     */
    public function testDatabaseConfigurationDirectoryMustNotContainSymlinks(): void
    {
        $targetPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'unrelated-config-target.php';
        $this->writeFile($targetPath, "<?php\n");
        $linkedPath = dirname($this->databaseConfigPath) . DIRECTORY_SEPARATOR . 'linked-config.php';
        if (!@symlink($targetPath, $linkedPath)) {
            self::markTestSkipped('The current filesystem does not support symbolic links.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('configuration directory must not contain symbolic links');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that environment-specific database files cannot override the validated default
     * database configuration.
     *
     * @dataProvider databaseOverrideProvider
     */
    public function testEnvironmentSpecificDatabaseOverridesAreRejected(string $relativePath): void
    {
        $path = dirname($this->databaseConfigPath) . DIRECTORY_SEPARATOR . $relativePath;
        $this->filesystem->mkdir(dirname($path));
        $this->writeFile($path, "<?php\nreturn [];\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment-specific database configuration overrides are not allowed');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    public static function databaseOverrideProvider(): array
    {
        return [
            'top-level override' => ['production.database.php'],
            'environment directory override' => ['production' . DIRECTORY_SEPARATOR . 'database.php'],
        ];
    }

    /**
     * Confirms that an unreadable database configuration cannot authorize integration writes.
     */
    public function testDatabaseConfigMustBeReadable(): void
    {
        self::assertTrue(chmod($this->databaseConfigPath, 0000));
        if (is_readable($this->databaseConfigPath)) {
            self::markTestSkipped('The current filesystem cannot make database.php unreadable.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('database configuration');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that syntactically invalid or structurally incomplete database configurations are
     * rejected instead of being interpreted permissively.
     *
     * @dataProvider malformedDatabaseConfigProvider
     */
    public function testMalformedDatabaseConfigsAreRejected(string $contents): void
    {
        $this->writeFile($this->databaseConfigPath, $contents);

        try {
            DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
            self::fail('A malformed database configuration must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('database', strtolower($exception->getMessage()));
        }
    }

    public static function malformedDatabaseConfigProvider(): array
    {
        return [
            'invalid PHP' => ["<?php\nreturn [;\n"],
            'non-array result' => ["<?php\nreturn 'invalid';\n"],
            'list result' => ["<?php\nreturn [];\n"],
            'missing default connection' => ["<?php\nreturn ['connections' => []];\n"],
            'missing selected connection' => ["<?php\nreturn ['default-connection' => 'concrete', 'connections' => []];\n"],
            'missing database name' => ["<?php\nreturn [\n    'default-connection' => 'concrete',\n    'connections' => ['concrete' => []],\n];\n"],
            'non-string database name' => ["<?php\nreturn [\n    'default-connection' => 'concrete',\n    'connections' => ['concrete' => ['database' => ['unsafe']]],\n];\n"],
        ];
    }

    /**
     * Confirms that the database named in database.php must match the disposable database before
     * Concrete is booted.
     */
    public function testConfiguredDatabaseMustMatchBeforeConcreteBoots(): void
    {
        $this->writeDatabaseConfig('block_builder_test_another_site');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not match disposable database');

        DisposableEnvironmentGuard::fromEnvironment($this->createEnvironment(), $this->packageRoot);
    }

    /**
     * Confirms that the live Concrete connection is checked again and writes are refused if it
     * points to another database after boot.
     */
    public function testActiveDatabaseMustStillMatchAfterBoot(): void
    {
        $guard = DisposableEnvironmentGuard::fromEnvironment(
            $this->createEnvironment(),
            $this->packageRoot,
        );
        $connection = new class {
            public function getDatabase(): string
            {
                return 'block_builder_test_wrong_runtime';
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing integration writes');

        $guard->assertActiveDatabase($connection);
    }

    /**
     * @return array<string, string>
     */
    private function createEnvironment(): array
    {
        return [
            'BLOCK_BUILDER_INTEGRATION' => '1',
            'BLOCK_BUILDER_INTEGRATION_PUBLIC_ROOT' => $this->publicRoot,
            'BLOCK_BUILDER_INTEGRATION_BLOCKS_ROOT' => $this->blocksRoot,
            'BLOCK_BUILDER_INTEGRATION_DATABASE_NAME' => self::DATABASE_NAME,
            'BLOCK_BUILDER_INTEGRATION_SITE_ID' => self::SITE_IDENTIFIER,
        ];
    }

    private function writeDatabaseConfig(string $databaseName): void
    {
        $config = [
            'default-connection' => 'concrete',
            'connections' => [
                'concrete' => [
                    'driver' => 'c5_pdo_mysql',
                    'server' => 'database',
                    'database' => $databaseName,
                    'username' => 'test',
                    'password' => 'test',
                ],
            ],
        ];
        $this->writeFile(
            $this->databaseConfigPath,
            "<?php\n\nreturn " . var_export($config, true) . ";\n",
        );
    }

    private function writeMarker(array $overrides = []): void
    {
        $marker = [
            'purpose' => 'block_builder_integration',
            'siteIdentifier' => self::SITE_IDENTIFIER,
            'databaseName' => self::DATABASE_NAME,
            'publicRoot' => (string) realpath($this->publicRoot),
            'blocksRoot' => (string) realpath($this->blocksRoot),
            ...$overrides,
        ];
        $this->writeFile(
            $this->markerPath,
            json_encode($marker, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        );
        self::assertTrue(chmod($this->markerPath, 0600));
    }

    private function writeFile(string $path, string $contents): void
    {
        self::assertSame(strlen($contents), file_put_contents($path, $contents));
    }
}
