<?php

declare(strict_types=1);

namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder;

use BlockBuilder\Controller\BaseDashboardController;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Search\Field\Field\ContainsBlockTypeField;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
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

    public function install(string $blockTypeHandle): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST requests are allowed.'));
        } elseif (!$this->token->validate('install_block')) {
            $this->flash('error', $this->token->getErrorMessage());
        }

        $bt = BlockType::installBlockType($blockTypeHandle);

        $this->flash('success', t('The block type "%s" has been successfully installed.', $bt->getBlockTypeName()));

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs')->send();
    }

    public function uninstall($blockTypeId = 0): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST requests are allowed.'));
        } elseif (!$this->token->validate('uninstall_block')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            $error = $this->blockTypeService->validateBlockTypeBeforeUninstall((int) $blockTypeId);
            if ($error) {
                $this->flash('error', $error);
            } else {
                $blockTypeName = $this->blockTypeService->uninstallBlockType((int) $blockTypeId);
                $this->flash('success', t('The block type "%s" has been successfully uninstalled.', $blockTypeName));
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs')->send();
    }

    public function delete_folder(?string $handle = null): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST requests are allowed.'));
        } elseif (!$this->token->validate('delete_folder')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            $error = $this->blockTypeService->validateBlockTypeFolderBeforeDeletion((string) $handle);
            if ($error) {
                $this->flash('error', $error);
            } else {
                $result = $this->blockTypeService->deleteBlockTypeFolder($handle);
                if ($result !== true) {
                    $this->flash('error', $result);
                } else {
                    $this->flash('success', t('The block type folder "%s" has been successfully deleted.', $handle));
                }
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs')->send();
    }

    public function search($blockTypeId = 0)
    {
        $bt = $blockTypeId > 0 ? $this->entityManager->find(BlockTypeEntity::class, $blockTypeId) : null;
        if ($bt === null) {
            $this->flash('error', t('Unable to find the block type specified.'));

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/blocks/types']),
                302,
            );
        }
        $field = new ContainsBlockTypeField();
        $qs = [
            'field' => [$field->getKey()],
            'btID' => $bt->getBlockTypeID(),
        ];
        $url = $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/sitemap/search/advanced_search']);
        $url = $url->setQuery($qs);

        return $this->app->make(ResponseFactoryInterface::class)->redirect(
            $url,
            302,
        );
    }
}
