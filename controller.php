<?php

declare(strict_types=1);

namespace Concrete\Package\BlockBuilder;

use BlockBuilder\Environment\RuntimeDirectory;
use Concrete\Core\Entity\Package as PackageEntity;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\Single as SinglePage;
use Symfony\Component\Filesystem\Filesystem;

defined('C5_EXECUTE') or exit('Access Denied.');

class Controller extends Package
{
    protected string $pkgHandle = 'block_builder';
    protected $appVersionRequired = '9.5.2';
    protected $phpVersionRequired = '8.4';
    protected string $pkgVersion = '3.0.0';

    protected $pkgAutoloaderRegistries = [
        'src/BlockBuilder' => 'BlockBuilder',
    ];

    public function getPackageName(): string
    {
        return t('Block Builder');
    }

    public function getPackageDescription(): string
    {
        return t('Design, configure and build custom Concrete CMS blocks with a user‑friendly interface.');
    }

    public function install(): void
    {
        $package = parent::install();

        $page = SinglePage::add('/dashboard/blocks/block_builder', $package);
        $page->updateCollectionName(t('Block Builder'));

        $this->installOrUpgrade($package);
    }

    public function upgrade(): void
    {
        parent::upgrade();

        $this->installOrUpgrade($this->getPackageEntity());
    }

    private function installOrUpgrade(PackageEntity $package): void
    {
        $this->initializeRuntimeDirectories();

        // Added in version 3.0.0
        $page = SinglePage::add('/dashboard/blocks/block_builder/configs', $package);
        $page->updateCollectionName(t('Configurations'));
        $page->setAttribute('exclude_nav', true);
    }

    private function initializeRuntimeDirectories(): void
    {
        $directories = [
            RuntimeDirectory::getPath(),
            RuntimeDirectory::getLocksPath(),
            RuntimeDirectory::getBackupsPath(),
        ];

        foreach ($directories as $directory) {
            if (is_link($directory) || (file_exists($directory) && !is_dir($directory))) {
                throw new \RuntimeException(sprintf('Unable to initialize the unsafe Block Builder runtime directory "%s".', $directory));
            }
        }

        $permissions = (int) $this->app->make('config')->get('concrete.filesystem.permissions.directory');
        $this->app->make(Filesystem::class)->mkdir($directories, $permissions);

        foreach ($directories as $directory) {
            if (!is_dir($directory) || is_link($directory)) {
                throw new \RuntimeException(sprintf('Unable to initialize the Block Builder runtime directory "%s".', $directory));
            }
        }
    }
}
