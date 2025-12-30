<?php

declare(strict_types=1);

namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Dto\CreateBlockResultDto;
use BlockBuilder\Block\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\Block\Factory\CreateBlockDtoFactory;
use BlockBuilder\Block\Request\CreateBlockRequest;
use BlockBuilder\Block\Service\BlockManifestService;
use BlockBuilder\BlockGenerator\BlockGenerator;
use BlockBuilder\Controller\BaseDashboardController;
use BlockBuilder\DataProvider\BlockBuilderViewDataProvider;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Concrete\Core\Asset\AssetList;
use JetBrains\PhpStorm\NoReturn;

defined('C5_EXECUTE') or exit('Access Denied.');

class BlockBuilder extends BaseDashboardController
{
    private array $errors = [];
    private array $fieldsWithError = [];
    private array $tabsWithError = [];

    private BlockGenerator $blockGenerator;
    private BlockManifestService $blockManifestService;
    private CreateBlockDtoFactory $factory;
    private BlockBuilderViewDataProvider $provider;

    public function on_start(): void
    {
        parent::on_start();

        $this->blockGenerator = $this->app->make(BlockGenerator::class);
        $this->blockManifestService = $this->app->make(BlockManifestService::class);
        $this->factory = $this->app->make(CreateBlockDtoFactory::class);
        $this->provider = $this->app->make(BlockBuilderViewDataProvider::class);

        $this->loadAssets();

        $this->set('errors', $this->errors);
        $this->set('fieldsWithError', $this->fieldsWithError);
        $this->set('tabsWithError', $this->tabsWithError);

        $this->set('navigationTabEnums', NavigationTabEnum::cases());
    }

    public function view(): void
    {
        // TODO: Fix Dto (maybe we should not use DTO but raw data)
        $this->handlePostRequest();

        $this->set('pageTitle', t('Block Builder'));
        $this->set('formActionPath', '');
        $this->set('config', $this->app->make(CreateBlockDtoFactory::class)->fromArray($this->provider->getDefaultValues()));

        $this->setProviderData();
        $this->overrideFieldsWithDataFromConfig($this->app->make(CreateBlockDtoFactory::class)->fromArray($this->provider->getDefaultValues()));
    }

    public function config($handle): void
    {
        $this->handlePostRequest();

        $config = $this->jsonConfigService->getConfigFromApplicationFolder($handle);

        $this->set('config', $config);
        $this->set('pageTitle', t('Block Builder') . ' - ' . t('Config loaded from block "%s"', $config->blockName));
        $this->set('formActionPath', 'config/' . $handle);
        $this->setProviderData();

        $this->overrideFieldsWithDataFromConfig($config);
    }

    public function predefined_config($handle): void
    {
        $this->handlePostRequest();

        $config = $this->jsonConfigService->getPredefinedConfig($handle);

        $this->set('config', $config);
        $this->set('pageTitle', t('Block Builder') . ' - ' . t('Config loaded from predefined JSON file "%s"', $config->blockName));
        $this->set('formActionPath', 'predefined_config/' . $handle);

        $this->setProviderData();
        $this->overrideFieldsWithDataFromConfig($config);
    }

    private function handlePostRequest(): void
    {
        if ($this->post()) {
            $request = $this->app->make(CreateBlockRequest::class, [
                'post' => $this->post(),
            ]);
            $result = $request->validate();
            $this->set('errors', $result->errors);
            $this->set('fieldsWithError', $result->fieldsWithError);
            $this->set('tabsWithError', $result->tabsWithError);

            if (!$result->hasErrors()) {
                $dto = $this->factory->fromArray($result->data);
                $manifestDto = $this->blockManifestService->getManifest(
                    dto: $dto,
                    rebuildBlock: !empty($result->data['rebuildBlock']),
                );

                $createBlockResult = $this->blockGenerator->create(
                    dto: $dto,
                    manifestDto: $manifestDto,
                );

                $this->handleCreateBlockResponse($createBlockResult);
            }

            $this->overrideJavaScriptFieldsWithDataFromPost();
        }
    }

    #[NoReturn]
    private function handleCreateBlockResponse(CreateBlockResultDto $result): void
    {
        $message = '';

        if ($result->postGenerationBlockState === PostGenerationBlockStateEnum::Rebuilt) {
            $message = t('Block "%s" has been successfully rebuilt and refreshed.', $result->blockName);
        } elseif ($result->postGenerationBlockState === PostGenerationBlockStateEnum::CreatedAndInstalled) {
            $message = t('Block "%s" has been successfully created and installed.', $result->blockName);
        } elseif ($result->postGenerationBlockState === PostGenerationBlockStateEnum::Created) {
            $message = t('Block "%s" has been successfully created. Go to "Block Types" page to manually install it.', $result->blockName);
        }

        // This is the only working solution for redirecting to the proper url and displaying a flash message. Why???
        $this->flash('success', $message);
        $this->buildRedirect('/dashboard/blocks/block_builder/config/' . $result->blockHandle)->send();
        exit;
    }

    private function setProviderData(): void
    {
        $data = array_merge(
            $this->provider->getOptionLists(),
            $this->provider->getLabels(),
            // TODO: THIS ONE SHOULD BE DELETED CAUSE WE ARE MAKING DTO?
            $this->provider->getDefaultValues(),
        );

        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Overrides basic/entry fields with values from $_POST (since JavaScript generates those fields).
     * Rest of fields is created using Concrete form helper, so $_POST values will be handled automatically.
     */
    private function overrideJavaScriptFieldsWithDataFromPost(): void
    {
        if (is_array($this->post('basic'))) {
            $this->set('basic', array_values($this->post('basic')));
        }
        if (is_array($this->post('entries'))) {
            $this->set('entries', array_values($this->post('entries')));
        }
    }

    private function overrideFieldsWithDataFromConfig(CreateBlockDto $config): void
    {
        // Do not trigger on $_POST request
        if (!$this->post()) {
            foreach ($config as $k => $v) {
                $this->set($k, $v);
            }
        }
    }

    private function loadAssets(): void
    {
        $al = AssetList::getInstance();

        $al->register('javascript', 'bb/scripts', 'js_files/block-builder.js', [], $this->pkg);
        $this->requireAsset('javascript', 'bb/scripts');
    }
}
