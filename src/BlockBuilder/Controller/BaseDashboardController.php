<?php

declare(strict_types=1);

namespace BlockBuilder\Controller;

use BlockBuilder\Block\Service\BlockTypeService;
use BlockBuilder\Block\Service\JsonConfigService;
use BlockBuilder\Environment\EnvironmentService;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Entity\Package as PackageEntity;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Controller\DashboardPageController;

class BaseDashboardController extends DashboardPageController
{
    protected PackageEntity $pkg;
    protected EnvironmentService $environmentService;
    protected BlockTypeService $blockTypeService;
    protected JsonConfigService $jsonConfigService;

    public function on_start(): void
    {
        parent::on_start();

        $this->environmentService = $this->app->make(EnvironmentService::class);
        $this->blockTypeService = $this->app->make(BlockTypeService::class);
        $this->jsonConfigService = $this->app->make(JsonConfigService::class);

        $environment = $this->environmentService->getEnvironment();
        $this->pkg = $this->app->make(PackageService::class)->getByHandle($environment->packageHandle);

        $this->loadAssets();

        $this->set('app', $this->app);
        $this->set('environment', $environment);
    }

    private function loadAssets(): void
    {
        $al = AssetList::getInstance();

        $al->register('css', 'choices/css', 'vendor/choices.js/choices.min.css', [], $this->pkg);
        $this->requireAsset('css', 'choices/css');

        $al->register('javascript', 'choices/js', 'vendor/choices.js/choices.min.js', [], $this->pkg);
        $this->requireAsset('javascript', 'choices/js');

        $al->register('javascript', 'sortable/js', 'vendor/sortablejs/Sortable.min.js', [], $this->pkg);
        $this->requireAsset('javascript', 'sortable/js');

        $al->register('css', 'block-builder/styles', 'assets/css/styles.css', [], $this->pkg);
        $this->requireAsset('css', 'block-builder/styles');

        $al->register('javascript', 'block-builder/js', 'assets/js/block-builder.js', [], $this->pkg);
        $this->requireAsset('javascript', 'block-builder/js');
    }
}
