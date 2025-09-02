<?php

namespace Concrete\Package\BlockBuilder;

use Concrete\Core\Package\Package;
use Concrete\Core\Page\Single as SinglePage;
use Illuminate\Contracts\Container\BindingResolutionException;

defined('C5_EXECUTE') or exit('Access Denied.');

class Controller extends Package
{
    protected string $pkgHandle = 'block_builder';
    protected $appVersionRequired = '9.4.3';
    protected string $pkgVersion = '2.8.0';

    protected $pkgAutoloaderRegistries = [
        'src/BlockBuilder' => 'BlockBuilder',
    ];

    public function getPackageName(): string
    {
        return t('Block Builder');
    }

    public function getPackageDescription(): string
    {
        return t('Build your custom Concrete CMS blocks (with optional set of repeatable entries).');
    }

    /**
     * @throws BindingResolutionException
     */
    public function on_start(): void
    {
        $this->app->make('Concrete\Core\Routing\RouterInterface')->register('ajax/delete-block-type-folder', 'Concrete\Package\BlockBuilder\Controller\Ajax::deleteBlockTypeFolder');
    }

    public function install(): void
    {
        $pkg = parent::install();

        $this->installSinglePages($pkg);
    }

    private function installSinglePages($pkg): void
    {
        $page = SinglePage::add('/dashboard/blocks/block_builder', $pkg);
        $page->updateCollectionName(t('Block Builder'));
    }
}
