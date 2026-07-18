<?php

declare(strict_types=1);

namespace BlockBuilder\DataProvider;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\Block\View\BlockConfigListItem;
use BlockBuilder\Block\Enum\BlockFormContextEnum;
use BlockBuilder\Environment\EnvironmentService;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

readonly class BlockBuilderConfigsViewDataProvider
{
    public function __construct(
        private BlockTypeLocator $blockTypeLocator,
        private EnvironmentService $environmentService,
        private ResolverManagerInterface $urlResolver,
    ) {
    }

    /**
     * @param BlockConfigDto[] $configs
     *
     * @return BlockConfigListItem[]
     */
    public function getApplicationConfigItems(array $configs): array
    {
        return array_map(fn(BlockConfigDto $config): BlockConfigListItem => $this->createApplicationItem($config), $configs);
    }

    /**
     * @param BlockConfigDto[] $configs
     *
     * @return BlockConfigListItem[]
     */
    public function getPredefinedConfigItems(array $configs): array
    {
        return array_map(fn(BlockConfigDto $config): BlockConfigListItem => $this->createPredefinedItem($config), $configs);
    }

    public function getNewBlockUrl(): string
    {
        return $this->resolve('/dashboard/blocks/block_builder');
    }

    private function createApplicationItem(BlockConfigDto $config): BlockConfigListItem
    {
        $blockType = $this->blockTypeLocator->find($config->blockHandle);
        $installed = $blockType instanceof BlockTypeEntity;
        $blockTypeId = $installed ? $blockType->getBlockTypeID() : null;

        return new BlockConfigListItem(
            config: $config,
            installed: $installed,
            iconPath: $this->environmentService->getPublicPathToBlockIcon($config->blockHandle),
            loadUrl: $this->resolve('/dashboard/blocks/block_builder/' . BlockFormContextEnum::Config->value . '/' . $config->blockHandle),
            blockTypeId: $blockTypeId,
            usageCount: $installed ? $blockType->getCount() : null,
            activeUsageCount: $installed ? $blockType->getCount(ignoreUnapprovedVersions: true) : null,
            usageUrl: $installed ? $this->resolve('/dashboard/blocks/block_builder/configs/search/' . $blockTypeId) : null,
            installUrl: $installed ? null : $this->resolve('/dashboard/blocks/block_builder/configs/install/' . $config->blockHandle),
            uninstallUrl: $installed ? $this->resolve('/dashboard/blocks/block_builder/configs/uninstall/' . $blockTypeId) : null,
            deleteDirectoryUrl: $installed ? null : $this->resolve('/dashboard/blocks/block_builder/configs/delete_folder/' . $config->blockHandle),
        );
    }

    private function createPredefinedItem(BlockConfigDto $config): BlockConfigListItem
    {
        return new BlockConfigListItem(
            config: $config,
            installed: false,
            iconPath: $this->environmentService->getPublicPathToDefaultBlockIcon(),
            loadUrl: $this->resolve('/dashboard/blocks/block_builder/' . BlockFormContextEnum::PredefinedConfig->value . '/' . $config->blockHandle),
        );
    }

    private function resolve(string $path): string
    {
        return (string) $this->urlResolver->resolve([$path]);
    }
}
