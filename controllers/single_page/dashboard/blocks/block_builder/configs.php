<?php

declare(strict_types=1);

namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder;

use BlockBuilder\Block\Exception\ConfigLoadingException;
use BlockBuilder\Block\Exception\BlockLifecycleException;
use BlockBuilder\Block\Service\BlockDirectoryRemover;
use BlockBuilder\Block\Service\BlockTypeInstaller;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\Block\Service\BlockTypeUninstaller;
use BlockBuilder\Controller\BaseDashboardController;
use BlockBuilder\DataProvider\BlockBuilderConfigsViewDataProvider;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Search\Field\Field\ContainsBlockTypeField;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyResponse;

defined('C5_EXECUTE') or exit('Access Denied.');

class Configs extends BaseDashboardController
{
    private BlockDirectoryRemover $blockDirectoryRemover;
    private BlockTypeInstaller $blockTypeInstaller;
    private BlockTypeLocator $blockTypeLocator;
    private BlockTypeUninstaller $blockTypeUninstaller;
    private BlockBuilderConfigsViewDataProvider $viewDataProvider;

    public function on_start(): void
    {
        parent::on_start();

        $this->blockDirectoryRemover = $this->app->make(BlockDirectoryRemover::class);
        $this->blockTypeInstaller = $this->app->make(BlockTypeInstaller::class);
        $this->blockTypeLocator = $this->app->make(BlockTypeLocator::class);
        $this->blockTypeUninstaller = $this->app->make(BlockTypeUninstaller::class);
        $this->viewDataProvider = $this->app->make(BlockBuilderConfigsViewDataProvider::class);
    }

    public function view(): void
    {
        $configLoadingErrors = [];

        try {
            $configs = $this->blockConfigReader->getConfigsFromApplicationFolder();
        } catch (ConfigLoadingException $exception) {
            $configs = [];
            $configLoadingErrors[] = $this->getConfigLoadingErrorMessage($exception);
        }
        $this->set('configItems', $this->viewDataProvider->getApplicationConfigItems($configs));

        try {
            $predefinedConfigs = $this->blockConfigReader->getPredefinedConfigs();
        } catch (ConfigLoadingException $exception) {
            $predefinedConfigs = [];
            $configLoadingErrors[] = $this->getConfigLoadingErrorMessage($exception);
        }
        $this->set('predefinedConfigItems', $this->viewDataProvider->getPredefinedConfigItems($predefinedConfigs));
        $this->set('configLoadingErrors', array_unique($configLoadingErrors));
        $this->set('newBlockUrl', $this->viewDataProvider->getNewBlockUrl());

        $this->set('pageTitle', t('Block Builder') . ' - ' . t('Browse existing configs'));
    }

    public function install(string $blockTypeHandle): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST requests are allowed.'));
        } elseif (!$this->token->validate('install_block')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            try {
                $bt = $this->blockTypeInstaller->install($blockTypeHandle);
                $this->flash('success', t('The block type "%s" has been successfully installed.', $bt->getBlockTypeName()));
            } catch (BlockLifecycleException $exception) {
                $this->flash('error', $exception->getMessage());
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs');
    }

    public function uninstall($blockTypeId = 0): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST requests are allowed.'));
        } elseif (!$this->token->validate('uninstall_block')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            try {
                $blockTypeName = $this->blockTypeUninstaller->uninstall((int) $blockTypeId);
                $this->flash('success', t('The block type "%s" has been successfully uninstalled.', $blockTypeName));
            } catch (BlockLifecycleException $exception) {
                $this->flash('error', $exception->getMessage());
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs');
    }

    public function delete_folder(?string $handle = null): SymfonyResponse
    {
        if (!$this->request->isMethod('post')) {
            $this->flash('error', t('Only POST requests are allowed.'));
        } elseif (!$this->token->validate('delete_folder')) {
            $this->flash('error', $this->token->getErrorMessage());
        } else {
            try {
                $this->blockDirectoryRemover->remove((string) $handle);
                $this->flash('success', t('The block type folder "%s" has been successfully deleted.', $handle));
            } catch (BlockLifecycleException $exception) {
                $this->flash('error', $exception->getMessage());
            }
        }

        return $this->buildRedirect('/dashboard/blocks/block_builder/configs');
    }

    public function search($blockTypeId = 0)
    {
        $bt = $blockTypeId > 0 ? $this->blockTypeLocator->find((int) $blockTypeId) : null;
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
