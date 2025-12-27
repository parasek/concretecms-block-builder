<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Exception\BlockDirectoryRemovalException;
use BlockBuilder\Block\Validation\BlockHandleFormat;
use Concrete\Core\File\Service\File as FileService;

readonly class BlockDirectoryRemover
{
    public function __construct(
        private FileService $fileService,
        private BlockTypePermissionChecker $permissionChecker,
        private BlockDirectoryLocator $directoryLocator,
        private BlockOwnershipChecker $blockOwnershipChecker,
        private BlockTypeLocator $blockTypeLocator,
        private BlockLifecycleLogger $lifecycleLogger,
        private BlockHandleLockManager $blockHandleLockManager,
    ) {
    }

    public function remove(string $handle): void
    {
        try {
            $error = $this->permissionChecker->getRemovalErrorMessage();
        } catch (\Throwable $throwable) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Unable to check permission for block type directory removal.'),
                previous: $throwable,
            );
        }
        if ($error) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: $error,
            );
        }

        if (!BlockHandleFormat::isValid($handle)) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Invalid block type handle "%s" supplied for directory removal.', $handle),
            );
        }

        try {
            $blockHandleLock = $this->blockHandleLockManager->acquire($handle);
        } catch (\Throwable $throwable) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Unable to acquire the directory operation lock for block type "%s".', $handle),
                previous: $throwable,
            );
        }

        try {
            $this->removeWhileLocked($handle);
        } finally {
            $blockHandleLock->release();
        }
    }

    private function removeWhileLocked(string $handle): void
    {
        $path = $this->directoryLocator->getSafeApplicationBlockDirectory($handle);
        if ($path === null) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('The directory for block type "%s" is missing, linked, or outside the application block directory.', $handle),
            );
        }
        if (!$this->blockOwnershipChecker->isOwnedApplicationBlock($handle)) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('The directory for block type "%s" does not contain a valid matching Block Builder configuration.', $handle),
                context: ['path' => $path],
            );
        }
        try {
            $isInstalled = $this->blockTypeLocator->isInstalled($handle);
        } catch (\Throwable $throwable) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Unable to determine whether block type "%s" is installed before directory removal.', $handle),
                context: ['path' => $path],
                previous: $throwable,
            );
        }
        if ($isInstalled) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Attempted to remove the directory of installed block type "%s".', $handle),
                context: ['path' => $path],
            );
        }

        try {
            if (!$this->fileService->removeAll($path, true)) {
                throw new \RuntimeException(sprintf('File service returned false while removing "%s".', $path));
            }
        } catch (\Throwable $throwable) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Unable to remove block type directory "%s".', $path),
                context: ['path' => $path],
                previous: $throwable,
            );
        }

        $this->lifecycleLogger->logSuccess(
            operation: 'remove_directory',
            target: $handle,
            context: ['path' => $path],
        );
    }

    private function throwLoggedFailure(
        string $handle,
        string $message,
        array $context = [],
        ?\Throwable $previous = null,
    ): never {
        $exception = new BlockDirectoryRemovalException(message: $message, previous: $previous);
        $this->lifecycleLogger->logFailure(
            operation: 'remove_directory',
            target: $handle,
            exception: $exception,
            context: $context,
        );

        throw $exception;
    }
}
