<?php

declare(strict_types=1);

namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks;

use BlockBuilder\Block\Dto\CreateBlockResultDto;
use BlockBuilder\Block\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\Block\Factory\CreateBlockDtoFactory;
use BlockBuilder\Block\Request\CreateBlockRequest;
use BlockBuilder\Block\Service\BlockManifestService;
use BlockBuilder\BlockGenerator\BlockGenerator;
use BlockBuilder\BlockGenerator\Enum\CreateBlockContextEnum;
use BlockBuilder\Controller\BaseDashboardController;
use BlockBuilder\DataProvider\BlockBuilderViewDataProvider;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Concrete\Core\Asset\AssetList;
use Symfony\Component\HttpFoundation\Response;

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
        $config = $this->factory->fromArray($this->provider->getDefaultValues());
        $this->set('config', $config);

        $response = $this->handlePostRequest();
        if ($response) {
            $response->send();
            exit;
        }

        $this->set('pageTitle', t('Block Builder'));
        $this->set('formActionPath', '');
        $this->setProviderData(context: CreateBlockContextEnum::NewBlock, blockHandle: $config->blockHandle);
    }

    public function config($handle): void
    {
        $config = $this->jsonConfigService->getConfigFromApplicationFolder($handle);
        $this->set('config', $config);

        $response = $this->handlePostRequest();
        if ($response) {
            $response->send();
            exit;
        }

        $this->set('pageTitle', t('Block Builder') . ' - ' . t('Config loaded from block "%s"', $config->blockName));
        $this->set('formActionPath', CreateBlockContextEnum::Config->value . '/' . $handle);
        $this->setProviderData(context: CreateBlockContextEnum::Config, blockHandle: $config->blockHandle);
    }

    public function predefined_config($handle): void
    {
        $config = $this->jsonConfigService->getPredefinedConfig($handle);
        $this->set('config', $config);

        $response = $this->handlePostRequest();
        if ($response) {
            $response->send();
            exit;
        }

        $this->set('pageTitle', t('Block Builder') . ' - ' . t('Config loaded from predefined JSON file "%s"', $config->blockName));
        $this->set('formActionPath', CreateBlockContextEnum::PredefinedConfig->value . '/' . $handle);
        $this->setProviderData(context: CreateBlockContextEnum::PredefinedConfig, blockHandle: $config->blockHandle);
    }

    private function handlePostRequest(): ?Response
    {
        if (!$this->post()) {
            return null;
        }

        $request = $this->app->make(CreateBlockRequest::class, [
            'post' => $this->post(),
            'files' => $this->request->files,
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
                blockIcon: $result->data['blockIcon'],
                customBlockIcon: $this->request->files->get('customBlockIcon'),
            );

            $createBlockResult = $this->blockGenerator->create(
                dto: $dto,
                manifestDto: $manifestDto,
            );

            return $this->handleCreateBlockResponse($createBlockResult);
        }

        // Create DTO from $_POST request for form persistence
        $config = $this->factory->fromArray($this->post());
        $this->set('config', $config);

        return null;
    }

    private function handleCreateBlockResponse(CreateBlockResultDto $result): Response
    {
        $message = match ($result->postGenerationBlockState) {
            PostGenerationBlockStateEnum::Rebuilt => t('Block "%s" has been successfully rebuilt and refreshed.', $result->blockName),
            PostGenerationBlockStateEnum::CreatedAndInstalled => t('Block "%s" has been successfully created and installed.', $result->blockName),
            PostGenerationBlockStateEnum::Created => t('Block "%s" has been successfully created. Go to "Block Types" page to manually install it.', $result->blockName),
        };

        $this->flash('success', $message);

        return $this->buildRedirect('/dashboard/blocks/block_builder/config/' . $result->blockHandle);
    }

    private function setProviderData(CreateBlockContextEnum $context, string $blockHandle): void
    {
        $data = array_merge(
            $this->provider->getOptionLists(context: $context, blockHandle: $blockHandle),
        );

        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    private function loadAssets(): void
    {
        $al = AssetList::getInstance();

        $al->register('javascript', 'block-builder/js', 'assets/js/block-builder.js', [], $this->pkg);
        $this->requireAsset('javascript', 'block-builder/js');

        $al->register('javascript', 'sortable/js', 'vendor/sortablejs/Sortable.min.js', [], $this->pkg);
        $this->requireAsset('javascript', 'sortable/js');
    }
}
