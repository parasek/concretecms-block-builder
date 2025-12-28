<?php

namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder;

use BlockBuilder\Controller\BaseDashboardController;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Concrete\Core\File\Service\File as FileService;
use Concrete\Core\User\User;
use Concrete\Core\Permission\Key\Key as Permissions;
use Exception;
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

    public function uninstall($btID = 0): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST request is allowed.'));
        } elseif (!$this->token->validate('uninstall_block')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            $u = $this->app->make(User::class);
            $bt = $btID > 0 ? $this->entityManager->find(BlockTypeEntity::class, $btID) : null;
            if ($bt === null) {
                $this->flash('error', t('Unable to find the block type specified.'));
            } elseif (!$u->isSuperUser()) {
                $this->flash('error', t('Only the super user may remove block types.'));
            } elseif (!$bt->canUnInstall()) {
                $this->flash('error', t('This block type is internal. It cannot be uninstalled.'));
            } else {
                $blockTypeName = $bt->getBlockTypeName();
                $bt->delete();
                $this->flash('success', t('Block "%s" has been successfully uninstalled.', $blockTypeName));
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs');
    }

    public function delete_folder($handle = null): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST request is allowed.'));
        } elseif (!$this->token->validate('delete_folder')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            $u = $this->app->make(User::class);
            $key = Permissions::getByHandle('uninstall_packages');
            $blockTypePath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $handle;

            if (!$u->isSuperUser()) {
                $this->flash('error', t('Only the super user may remove block types.'));
            } elseif (!$key->validate()) {
                $this->flash('error', t('You do not have permission to uninstall packages.'));
            } elseif (!is_dir($blockTypePath)) {
                $this->flash('error', t('Folder "%s" does not exist.', $blockTypePath));
            } else {
                try {
                    $fileService = new FileService();
                    $fileService->removeAll($blockTypePath, true);
                    $this->flash('success', t('Block type folder "%s" has been successfully deleted.', $handle));
                } catch (Exception) {
                    $this->flash('error', t('Failed to remove block type folder "%s". Please check file permissions.', $handle));
                }
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs');
    }
}
