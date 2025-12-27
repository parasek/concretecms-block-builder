<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Exception\BlockTypeUninstallException;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;

readonly class BlockTypeUninstaller
{
    public function __construct(
        private BlockTypePermissionChecker $permissionChecker,
        private BlockTypeLocator $blockTypeLocator,
        private BlockOwnershipChecker $blockOwnershipChecker,
        private BlockLifecycleLogger $lifecycleLogger,
        private BlockHandleLockManager $blockHandleLockManager,
    ) {
    }

    public function uninstall(int|string $blockTypeIdentifier): string
    {
        try {
            $error = $this->permissionChecker->getRemovalErrorMessage();
        } catch (\Throwable $throwable) {
            $this->throwLoggedFailure(
                blockTypeIdentifier: $blockTypeIdentifier,
                message: t('The block type removal permission could not be verified.'),
                previous: $throwable,
            );
        }
        if ($error) {
            $this->throwLoggedFailure(
                blockTypeIdentifier: $blockTypeIdentifier,
                message: $error,
            );
        }

        try {
            $blockType = $this->blockTypeLocator->findByIdentifier($blockTypeIdentifier);
        } catch (\Throwable $throwable) {
            $this->throwLoggedFailure(
                blockTypeIdentifier: $blockTypeIdentifier,
                message: t('Unable to locate block type "%s" for uninstallation.', $blockTypeIdentifier),
                previous: $throwable,
            );
        }
        if (!$blockType instanceof BlockTypeEntity) {
            $this->throwLoggedFailure(
                blockTypeIdentifier: $blockTypeIdentifier,
                message: t('Unable to find block type "%s" for uninstallation.', $blockTypeIdentifier),
            );
        }

        try {
            $isInternal = $blockType->isBlockTypeInternal();
            $blockTypeName = $blockType->getBlockTypeName();
            $blockTypeId = $blockType->getBlockTypeID();
            $blockTypeHandle = $blockType->getBlockTypeHandle();
        } catch (\Throwable $throwable) {
            $this->throwLoggedFailure(
                blockTypeIdentifier: $blockTypeIdentifier,
                message: t('Unable to inspect block type "%s" before uninstallation.', $blockTypeIdentifier),
                previous: $throwable,
            );
        }

        if ($isInternal) {
            $this->throwLoggedFailure(
                blockTypeIdentifier: $blockTypeIdentifier,
                message: t('Attempted to uninstall internal block type "%s".', $blockTypeIdentifier),
            );
        }

        if (!is_string($blockTypeHandle)) {
            $this->throwLoggedFailure(
                blockTypeIdentifier: $blockTypeIdentifier,
                message: t('The block type handle could not be determined before uninstallation.'),
                context: ['blockTypeId' => $blockTypeId],
            );
        }

        try {
            $blockHandleLock = $this->blockHandleLockManager->acquire($blockTypeHandle);
        } catch (\Throwable $throwable) {
            $this->throwLoggedFailure(
                blockTypeIdentifier: $blockTypeIdentifier,
                message: t('Unable to acquire the operation lock before uninstalling block type "%s".', $blockTypeHandle),
                context: ['blockTypeId' => $blockTypeId],
                previous: $throwable,
            );
        }

        try {
            if (!$this->blockOwnershipChecker->isOwnedApplicationBlock($blockTypeHandle)) {
                $this->throwLoggedFailure(
                    blockTypeIdentifier: $blockTypeIdentifier,
                    message: t('Only block types with a valid matching Block Builder configuration can be uninstalled here.'),
                    context: ['blockTypeId' => $blockTypeId],
                );
            }

            try {
                $blockType->delete();
            } catch (\Throwable $throwable) {
                $this->throwLoggedFailure(
                    blockTypeIdentifier: $blockTypeIdentifier,
                    message: t('The block type could not be uninstalled. Please check the logs for more information.'),
                    context: ['blockTypeId' => $blockTypeId],
                    previous: $throwable,
                );
            }
        } finally {
            $blockHandleLock->release();
        }

        $this->lifecycleLogger->logSuccess(
            operation: 'uninstall',
            target: $blockTypeIdentifier,
            context: ['blockTypeId' => $blockTypeId],
        );

        return $blockTypeName;
    }

    private function throwLoggedFailure(
        int|string $blockTypeIdentifier,
        string $message,
        array $context = [],
        ?\Throwable $previous = null,
    ): never {
        $exception = new BlockTypeUninstallException(message: $message, previous: $previous);
        $this->lifecycleLogger->logFailure(
            operation: 'uninstall',
            target: $blockTypeIdentifier,
            exception: $exception,
            context: $context,
        );

        throw $exception;
    }
}
