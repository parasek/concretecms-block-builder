<?php

namespace Concrete\Package\BlockBuilder;

use Concrete\Core\Package\Package;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Core\Routing\RouterInterface;

defined('C5_EXECUTE') or exit('Access Denied.');

class Controller extends Package
{
    protected string $pkgHandle = 'block_builder';
    protected $appVersionRequired = '9.4.3';
    protected string $pkgVersion = '2.8.2'; // TODO: Update to 3.0.0 before release

    protected $pkgAutoloaderRegistries = [
        'src/BlockBuilder' => 'BlockBuilder',
    ];

    public function getPackageName(): string
    {
        return t('Block Builder');
    }

    public function getPackageDescription(): string
    {
        return t('Build custom Concrete CMS blocks (with optional set of repeatable entries).');
    }

    public function on_start(): void
    {
        $this->app->make(RouterInterface::class)->post(
            path: 'ajax/delete-block-type-folder',
            action: 'Concrete\Package\BlockBuilder\Controller\Ajax::deleteBlockTypeFolder',
        );
    }

    public function install(): void
    {
        $pkg = parent::install();

        $page = SinglePage::add('/dashboard/blocks/block_builder', $pkg);
        $page->updateCollectionName(t('Block Builder'));

        $this->installOrUpgrade($pkg);
    }

    public function upgrade(): void
    {
        parent::upgrade();

        $this->installOrUpgrade($this->getPackageEntity());
    }

    private function installOrUpgrade($pkg): void
    {
        // Added in version 3.0.0
        $page = SinglePage::add('/dashboard/blocks/block_builder/configs', $pkg);
        $page->updateCollectionName(t('Configs'));
        $page->setAttribute('exclude_nav', true);
    }
}
