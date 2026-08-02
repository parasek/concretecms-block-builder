<?php

declare(strict_types=1);

namespace Concrete\Package\BlockBuilder;

use Concrete\Core\Entity\Package as PackageEntity;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\Single as SinglePage;

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
        // Added in version 3.0.0
        $page = SinglePage::add('/dashboard/blocks/block_builder/configs', $package);
        $page->updateCollectionName(t('Configs'));
        $page->setAttribute('exclude_nav', true);
    }
}
