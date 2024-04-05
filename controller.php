<?php namespace Concrete\Package\BlockBuilder;

use Concrete\Core\Package\Package;
use Concrete\Core\Page\Single as SinglePage;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{
    protected $pkgHandle = 'block_builder';
    protected $appVersionRequired = '9.2.0';
    protected $pkgVersion = '2.6.1';

    protected $pkgAutoloaderRegistries = [
        'src/BlockBuilder' => 'BlockBuilder'
    ];

    public function getPackageName()
    {
        return t('Block Builder');
    }

    public function getPackageDescription()
    {
        return t('Build your custom Concrete CMS blocks (with optional set of repeatable entries).');
    }

    public function on_start()
    {

        $this->app->make('Concrete\Core\Routing\RouterInterface')->register('ajax/delete-block-type-folder', 'Concrete\Package\BlockBuilder\Controller\Ajax::deleteBlockTypeFolder');

    }

    public function install()
    {

        $pkg = parent::install();

        // Install single pages
        $page = SinglePage::add('/dashboard/blocks/block_builder', $pkg);
        $page->updateCollectionName(t('Block Builder'));

    }

}
