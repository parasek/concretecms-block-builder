<?php

namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder;

use BlockBuilder\Controller\BaseDashboardController;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Concrete\Core\User\User;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyResponse;

defined('C5_EXECUTE') or exit('Access Denied.');

class Configs extends BaseDashboardController
{
    public function view(): void
    {
        $configs = $this->jsonConfigService->getConfigsFromApplicationFolder();
        $this->set('configs', $configs);

        $predefinedConfigs = $this->jsonConfigService->getPredefinedConfigs();
        $this->set('predefinedConfigs', $predefinedConfigs);

        $this->set('pageTitle', t('Block Builder') . ' - ' . t('Browse existing configs'));
    }

    public function uninstall($blockTypeId = 0): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST request is allowed.'));
        } elseif (!$this->token->validate('uninstall_block')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            $error = $this->blockTypeService->validateBlockTypeBeforeUninstall($blockTypeId);
            if ($error) {
                $this->flash('error', $error);
            } else {
                $blockTypeName = $this->blockTypeService->uninstallBlockType($blockTypeId);
                $this->flash('success', t('Block "%s" has been successfully uninstalled.', $blockTypeName));
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs')->send();
    }

    public function delete_folder(?string $handle = null): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST request is allowed.'));
        } elseif (!$this->token->validate('delete_folder')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            $error = $this->blockTypeService->validateBlockTypeFolderBeforeDeletion((string)$handle);
            if ($error) {
                $this->flash('error', $error);
            } else {
                $result = $this->blockTypeService->deleteBlockTypeFolder($handle);
                if ($result !== true) {
                    $this->flash('error', $result);
                } else {
                    $this->flash('success', t('Block type folder "%s" has been successfully deleted.', $handle));
                }
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs')->send();
    }
}
