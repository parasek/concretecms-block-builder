<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Integration;

use BlockBuilder\Environment\RuntimeDirectory;
use BlockBuilder\Tests\Integration\Support\ConcreteIntegrationTestCase;
use Concrete\Core\Entity\Package as PackageEntity;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;

/**
 * Test type: Package installation and environment integration test.
 *
 * Verifies the installed package version and dashboard pages, then confirms that Concrete's
 * active paths and database all belong to the guarded disposable installation.
 */
final class PackageInstallationIntegration extends ConcreteIntegrationTestCase
{
    /**
     * Confirms that Block Builder was installed correctly in the disposable Concrete site.
     *
     * The test loads the installed package and its two dashboard pages. It expects package version
     * 3.0.0, verifies that both pages belong to Block Builder, and confirms that the Configs page
     * remains hidden from normal dashboard navigation.
     */
    public function testDisposableSiteContainsInstalledPackageAndDashboardPages(): void
    {
        self::assertTrue($this->application->isInstalled());

        $package = $this->application->make(PackageService::class)->getByHandle('block_builder');
        self::assertInstanceOf(PackageEntity::class, $package);
        self::assertTrue($package->isPackageInstalled());
        self::assertSame('3.0.0', $package->getPackageVersion());

        $builderPage = Page::getByPath('/dashboard/blocks/block_builder');
        self::assertFalse($builderPage->isError());
        self::assertSame($package->getPackageID(), $builderPage->getPackageID());

        $configsPage = Page::getByPath('/dashboard/blocks/block_builder/configs');
        self::assertFalse($configsPage->isError());
        self::assertSame($package->getPackageID(), $configsPage->getPackageID());
        self::assertTrue((bool) $configsPage->getAttribute('exclude_nav'));
    }

    /**
     * Confirms that the test is running only inside the approved disposable environment.
     *
     * The test compares Concrete's active public, generated-block, package, and database locations
     * with the locations approved by the environment guard. Every location must belong to the
     * disposable installation so the suite cannot accidentally use a development or production site.
     */
    public function testRuntimePathsRemainInsideDisposableInstallation(): void
    {
        self::assertSame($this->environmentGuard->publicRoot, realpath(DIR_BASE));
        self::assertSame($this->environmentGuard->blocksRoot, realpath(DIR_FILES_BLOCK_TYPES));
        self::assertSame(
            $this->environmentGuard->packageRoot,
            realpath(DIR_PACKAGES . DIRECTORY_SEPARATOR . 'block_builder'),
        );
        self::assertSame(RuntimeDirectory::getPath(), realpath(RuntimeDirectory::getPath()));
        self::assertSame(RuntimeDirectory::getLocksPath(), realpath(RuntimeDirectory::getLocksPath()));
        self::assertSame(RuntimeDirectory::getBackupsPath(), realpath(RuntimeDirectory::getBackupsPath()));

        $this->environmentGuard->assertActiveDatabase($this->connection);
    }
}
