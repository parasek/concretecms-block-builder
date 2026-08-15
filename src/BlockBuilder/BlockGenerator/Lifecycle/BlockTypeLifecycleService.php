<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Lifecycle;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Service\BlockTypeInstaller;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\BlockGenerator\Exception\BlockGenerationInstallationException;
use BlockBuilder\BlockGenerator\Exception\BlockGenerationRefreshException;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Doctrine\ORM\EntityManagerInterface;

readonly class BlockTypeLifecycleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BlockTypeInstaller $blockTypeInstaller,
        private BlockTypeLocator $blockTypeLocator,
    ) {
    }

    public function installOrRefresh(BlockConfigDto $config, BlockGenerationManifest $manifest): PostGenerationBlockStateEnum
    {
        if ($manifest->shouldRebuildBlock) {
            try {
                $blockType = $this->blockTypeLocator->findByIdentifier($config->blockHandle);
                if (!$blockType instanceof BlockTypeEntity) {
                    throw new BlockGenerationRefreshException(sprintf('Unable to find block type "%s" before refresh.', $config->blockHandle));
                }

                $blockType = $this->entityManager->find(BlockTypeEntity::class, $blockType->getBlockTypeID());
                if (!$blockType instanceof BlockTypeEntity) {
                    throw new BlockGenerationRefreshException(sprintf('Unable to load block type "%s" before refresh.', $config->blockHandle));
                }

                $blockType->refresh();
            } catch (BlockGenerationRefreshException $exception) {
                throw $exception;
            } catch (\Throwable $throwable) {
                throw new BlockGenerationRefreshException(message: sprintf('Unable to refresh block type "%s".', $config->blockHandle), previous: $throwable);
            }

            return PostGenerationBlockStateEnum::Rebuilt;
        }

        if ($manifest->shouldInstallBlock) {
            try {
                $this->blockTypeInstaller->install($config->blockHandle);
            } catch (\Throwable $throwable) {
                throw new BlockGenerationInstallationException(message: sprintf('Unable to install block type "%s".', $config->blockHandle), previous: $throwable);
            }

            return PostGenerationBlockStateEnum::CreatedAndInstalled;
        }

        return PostGenerationBlockStateEnum::Created;
    }
}
