<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Lifecycle;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Dto\BlockGenerationManifest;
use BlockBuilder\Block\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\Block\Service\BlockTypeInstaller;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\BlockGenerator\Exception\BlockTypeInstallationException;
use BlockBuilder\BlockGenerator\Exception\BlockTypeRefreshException;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

readonly class BlockTypeLifecycleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BlockTypeInstaller $blockTypeInstaller,
        private BlockTypeLocator $blockTypeLocator,
    ) {
    }

    public function apply(BlockConfigDto $config, BlockGenerationManifest $manifest): PostGenerationBlockStateEnum
    {
        if ($manifest->shouldBlockBeRebuilt) {
            try {
                $blockType = $this->blockTypeLocator->find($config->blockHandle);
                if (!$blockType instanceof BlockTypeEntity) {
                    throw new BlockTypeRefreshException(
                        sprintf('Unable to find block type "%s" before refresh.', $config->blockHandle),
                    );
                }

                $blockType = $this->entityManager->find(BlockTypeEntity::class, $blockType->getBlockTypeID());
                if (!$blockType instanceof BlockTypeEntity) {
                    throw new BlockTypeRefreshException(
                        sprintf('Unable to load block type "%s" before refresh.', $config->blockHandle),
                    );
                }

                $blockType->refresh();
            } catch (BlockTypeRefreshException $exception) {
                throw $exception;
            } catch (Throwable $throwable) {
                throw new BlockTypeRefreshException(
                    message: sprintf('Unable to refresh block type "%s".', $config->blockHandle),
                    previous: $throwable,
                );
            }

            return PostGenerationBlockStateEnum::Rebuilt;
        }

        if ($manifest->shouldBlockBeInstalled) {
            try {
                $this->blockTypeInstaller->install($config->blockHandle);
            } catch (Throwable $throwable) {
                throw new BlockTypeInstallationException(
                    message: sprintf('Unable to install block type "%s".', $config->blockHandle),
                    previous: $throwable,
                );
            }

            return PostGenerationBlockStateEnum::CreatedAndInstalled;
        }

        return PostGenerationBlockStateEnum::Created;
    }
}
