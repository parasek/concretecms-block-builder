<?php

declare(strict_types=1);

namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks;

use BlockBuilder\Block\Enum\BlockFormContextEnum;
use BlockBuilder\Block\Exception\ConfigLoadingException;
use BlockBuilder\Block\Factory\BlockConfigDtoFactory;
use BlockBuilder\Block\Validation\BlockHandleFormat;
use BlockBuilder\Block\Validation\CreateBlockRequestValidator;
use BlockBuilder\BlockGenerator\BlockGenerationManifestFactory;
use BlockBuilder\BlockGenerator\BlockGenerationResult;
use BlockBuilder\BlockGenerator\BlockGenerator;
use BlockBuilder\BlockGenerator\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\BlockGenerator\Exception\BlockGenerationException;
use BlockBuilder\Controller\BaseDashboardController;
use BlockBuilder\DataProvider\BlockBuilderViewDataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

defined('C5_EXECUTE') or exit('Access Denied.');

class BlockBuilder extends BaseDashboardController
{
    private BlockGenerator $blockGenerator;
    private BlockGenerationManifestFactory $blockGenerationManifestFactory;
    private BlockConfigDtoFactory $blockConfigDtoFactory;
    private BlockBuilderViewDataProvider $viewDataProvider;
    private CreateBlockRequestValidator $createBlockRequestValidator;

    public function on_start(): void
    {
        parent::on_start();

        $this->blockGenerator = $this->app->make(BlockGenerator::class);
        $this->blockGenerationManifestFactory = $this->app->make(BlockGenerationManifestFactory::class);
        $this->blockConfigDtoFactory = $this->app->make(BlockConfigDtoFactory::class);
        $this->viewDataProvider = $this->app->make(BlockBuilderViewDataProvider::class);
        $this->createBlockRequestValidator = $this->app->make(CreateBlockRequestValidator::class);

        $this->setViewData($this->viewDataProvider->getCommonViewData());

        $this->set('errors', []);
        $this->set('fieldsWithError', []);
        $this->set('tabsWithError', []);
        $this->set('loadedConfig', null);
    }

    public function view(): ?Response
    {
        $config = $this->blockConfigDtoFactory->fromArray($this->viewDataProvider->getDefaultFormValues());
        $this->set('config', $config);

        $response = $this->handlePostRequest();
        if ($response) {
            return $response;
        }

        $this->set('pageTitle', t('Block Builder'));
        $this->set('formActionPath', '');
        $this->setFormViewData(context: BlockFormContextEnum::NewBlock, blockHandle: $config->blockHandle);

        return null;
    }

    public function config(string $handle): ?Response
    {
        if (!BlockHandleFormat::isValid($handle)) {
            return $this->redirectToConfigsWithError(t('The specified block config handle is invalid.'));
        }

        try {
            $config = $this->blockConfigReader->getConfigFromApplicationFolder($handle);
        } catch (ConfigLoadingException $exception) {
            return $this->redirectToConfigsWithError($this->getConfigLoadingErrorMessage($exception));
        }
        $this->set('config', $config);
        $this->set('loadedConfig', $config);

        $response = $this->handlePostRequest(rebuildSourceHandle: $handle);
        if ($response) {
            return $response;
        }

        $this->set('pageTitle', t('Block Builder') . ' - ' . t('Config loaded from block "%s"', $config->blockName));
        $this->set('formActionPath', BlockFormContextEnum::Config->value . '/' . $handle);
        $this->setFormViewData(context: BlockFormContextEnum::Config, blockHandle: $config->blockHandle);

        return null;
    }

    public function predefined_config(string $handle): ?Response
    {
        if (!BlockHandleFormat::isValid($handle)) {
            return $this->redirectToConfigsWithError(t('The specified predefined config handle is invalid.'));
        }

        try {
            $config = $this->blockConfigReader->getPredefinedConfig($handle);
        } catch (ConfigLoadingException $exception) {
            return $this->redirectToConfigsWithError($this->getConfigLoadingErrorMessage($exception));
        }
        $this->set('config', $config);
        $this->set('loadedConfig', $config);

        $response = $this->handlePostRequest();
        if ($response) {
            return $response;
        }

        $this->set('pageTitle', t('Block Builder') . ' - ' . t('Config loaded from predefined JSON file "%s"', $config->blockName));
        $this->set('formActionPath', BlockFormContextEnum::PredefinedConfig->value . '/' . $handle);
        $this->setFormViewData(context: BlockFormContextEnum::PredefinedConfig, blockHandle: $config->blockHandle);

        return null;
    }

    private function handlePostRequest(?string $rebuildSourceHandle = null): ?Response
    {
        if (!$this->request->isMethod('POST')) {
            return null;
        }

        $validationResult = $this->createBlockRequestValidator->validate(
            data: $this->post(),
            files: $this->request->files,
            rebuildSourceHandle: $rebuildSourceHandle,
        );

        $this->set('errors', $validationResult->errors);
        $this->set('fieldsWithError', $validationResult->fieldsWithError);
        $this->set('tabsWithError', $validationResult->tabsWithError);

        if (!$validationResult->hasErrors()) {
            $blockConfig = $this->blockConfigDtoFactory->fromGenerationArray($validationResult->data);
            $customBlockIcon = $this->request->files->get('customBlockIcon');
            $manifest = $this->blockGenerationManifestFactory->create(
                config: $blockConfig,
                shouldRebuildBlock: !empty($validationResult->data['rebuildBlock']),
                blockIconPublicPath: $validationResult->data['blockIcon'] ?? null,
                customBlockIcon: $customBlockIcon instanceof UploadedFile ? $customBlockIcon : null,
            );

            try {
                $generationResult = $this->blockGenerator->generate(
                    config: $blockConfig,
                    manifest: $manifest,
                );
            } catch (BlockGenerationException $exception) {
                $this->app->make(LoggerInterface::class)->error(
                    'Block Builder failed to generate block "{blockHandle}".'
                        . PHP_EOL
                        . '{errorMessage}',
                    [
                        'blockHandle' => $blockConfig->blockHandle,
                        'errorMessage' => $exception->getMessage(),
                        'exception' => $exception,
                    ],
                );

                $errors = $validationResult->errors;
                $errors[] = $exception->getMessage();
                $this->set('errors', $errors);
                $this->set('config', $this->blockConfigDtoFactory->fromArray($validationResult->data));

                return null;
            }

            return $this->handleGenerationResponse($generationResult);
        }

        $config = $this->blockConfigDtoFactory->fromFormArray($validationResult->data);
        $this->set('config', $config);

        return null;
    }

    private function handleGenerationResponse(BlockGenerationResult $generationResult): Response
    {
        $message = match ($generationResult->postGenerationBlockState) {
            PostGenerationBlockStateEnum::Rebuilt => t('The block "%s" has been successfully rebuilt and refreshed.', $generationResult->blockName),
            PostGenerationBlockStateEnum::CreatedAndInstalled => t('The block type "%s" has been successfully created and installed.', $generationResult->blockName),
            PostGenerationBlockStateEnum::Created => t('The block type "%s" has been successfully created. Please install it manually now.', $generationResult->blockName),
        };

        $this->flash('success', $message);

        return $this->buildRedirect('/dashboard/blocks/block_builder/config/' . $generationResult->blockHandle);
    }

    private function setFormViewData(BlockFormContextEnum $context, string $blockHandle): void
    {
        $this->setViewData(
            $this->viewDataProvider->getFormViewData(
                context: $context,
                blockHandle: $blockHandle,
            ),
        );
    }

    private function setViewData(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    private function redirectToConfigsWithError(string $message): Response
    {
        $this->flash('error', $message);

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs');
    }
}
