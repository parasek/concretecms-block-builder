<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Exception\BlockDirectoryRemovalException;
use BlockBuilder\Block\Validation\BlockHandleFormat;
use Concrete\Core\File\Service\File as FileService;
use RuntimeException;
use Throwable;

readonly class BlockDirectoryRemover
{
    public function __construct(
        private FileService $fileService,
        private BlockTypePermissionChecker $permissionChecker,
        private BlockDirectoryLocator $directoryLocator,
        private BlockTypeLocator $blockTypeLocator,
        private BlockLifecycleLogger $lifecycleLogger,
    ) {
    }

    public function remove(string $handle): void
    {
        try {
            $error = $this->permissionChecker->getRemovalError();
        } catch (Throwable $throwable) {
            $this->throwFailure(
                handle: $handle,
                message: 'Unable to check permission for block type directory removal.',
                safeDisplayMessage: t('Unable to check permission for block type directory removal.'),
                previous: $throwable,
            );
        }
        if ($error) {
            $this->throwFailure(
                handle: $handle,
                message: 'Permission denied while removing a block type directory.' . PHP_EOL . $error,
                safeDisplayMessage: $error,
            );
        }

        if (!BlockHandleFormat::isValid($handle)) {
            $this->throwFailure(
                handle: $handle,
                message: sprintf('Invalid block type handle "%s" supplied for directory removal.', $handle),
                safeDisplayMessage: t('Invalid block type handle "%s" supplied for directory removal.', $handle),
            );
        }

        $path = $this->directoryLocator->getSafeApplicationBlockDirectory($handle);
        if ($path === null) {
            $this->throwFailure(
                handle: $handle,
                message: sprintf('The directory for block type "%s" is missing, linked, or outside the application block directory.', $handle),
                safeDisplayMessage: t('The directory for block type "%s" is missing, linked, or outside the application block directory.', $handle),
            );
        }
        try {
            $isInstalled = $this->blockTypeLocator->isInstalled($handle);
        } catch (Throwable $throwable) {
            $this->throwFailure(
                handle: $handle,
                message: sprintf('Unable to determine whether block type "%s" is installed before directory removal.', $handle),
                safeDisplayMessage: t('Unable to determine whether block type "%s" is installed before directory removal.', $handle),
                context: ['path' => $path],
                previous: $throwable,
            );
        }
        if ($isInstalled) {
            $this->throwFailure(
                handle: $handle,
                message: sprintf('Attempted to remove the directory of installed block type "%s".', $handle),
                safeDisplayMessage: t('Attempted to remove the directory of installed block type "%s".', $handle),
                context: ['path' => $path],
            );
        }

        try {
            if (!$this->fileService->removeAll($path, true)) {
                throw new RuntimeException(sprintf('File service returned false while removing "%s".', $path));
            }
        } catch (Throwable $throwable) {
            $this->throwFailure(
                handle: $handle,
                message: sprintf('Unable to remove block type directory "%s".', $path),
                safeDisplayMessage: t('Unable to remove block type directory "%s".', $path),
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

    private function throwFailure(
        string $handle,
        string $message,
        string $safeDisplayMessage,
        array $context = [],
        ?Throwable $previous = null,
    ): never {
        $exception = new BlockDirectoryRemovalException($message, $safeDisplayMessage, $previous);
        $this->lifecycleLogger->logFailure(
            operation: 'remove_directory',
            target: $handle,
            exception: $exception,
            context: $context,
        );

        throw $exception;
    }
}
