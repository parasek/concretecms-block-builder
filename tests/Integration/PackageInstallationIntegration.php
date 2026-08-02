<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Integration;

use BlockBuilder\Tests\Integration\Support\ConcreteIntegrationTestCase;
use Concrete\Core\Entity\Package as PackageEntity;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;

final class PackageInstallationIntegration extends ConcreteIntegrationTestCase
{
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

    public function testRuntimePathsRemainInsideDisposableInstallation(): void
    {
        self::assertSame($this->environmentGuard->publicRoot, realpath(DIR_BASE));
        self::assertSame($this->environmentGuard->blocksRoot, realpath(DIR_FILES_BLOCK_TYPES));
        self::assertSame(
            $this->environmentGuard->packageRoot,
            realpath(DIR_PACKAGES . DIRECTORY_SEPARATOR . 'block_builder'),
        );

        $this->environmentGuard->assertActiveDatabase($this->connection);
    }
}
