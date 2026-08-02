<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Exception\BlockTypeInstallException;
use BlockBuilder\Block\Validation\BlockHandleFormat;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Throwable;

readonly class BlockTypeInstaller
{
    public function __construct(
        private BlockTypePermissionChecker $permissionChecker,
        private BlockDirectoryLocator $directoryLocator,
        private BlockOwnershipChecker $blockOwnershipChecker,
        private BlockTypeLocator $blockTypeLocator,
        private BlockLifecycleLogger $lifecycleLogger,
    ) {
    }

    public function install(string $handle): BlockTypeEntity
    {
        try {
            $error = $this->permissionChecker->getInstallationErrorMessage();
        } catch (Throwable $throwable) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('The block type installation permission could not be verified.'),
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
                message: t('Invalid block type handle "%s" supplied for installation.', $handle),
            );
        }

        $blockTypePath = $this->directoryLocator->getSafeApplicationBlockDirectory($handle);
        if ($blockTypePath === null) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('The block type directory for "%s" is missing, linked, or outside the application block directory.', $handle),
            );
        }
        if (!$this->blockOwnershipChecker->isOwnedApplicationBlock($handle)) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Only block types with a valid matching Block Builder configuration can be installed here.'),
                context: ['path' => $blockTypePath],
            );
        }

        try {
            $isInstalled = $this->blockTypeLocator->isInstalled($handle);
        } catch (Throwable $throwable) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Unable to determine whether block type "%s" is installed.', $handle),
                context: ['path' => $blockTypePath],
                previous: $throwable,
            );
        }
        if ($isInstalled) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Block type "%s" is already installed.', $handle),
                context: ['path' => $blockTypePath],
            );
        }

        try {
            $blockType = BlockType::installBlockType($handle);
        } catch (Throwable $throwable) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Concrete CMS failed to install block type "%s".', $handle),
                context: ['path' => $blockTypePath],
                previous: $throwable,
            );
        }

        if (!$blockType instanceof BlockTypeEntity) {
            $this->throwLoggedFailure(
                handle: $handle,
                message: t('Concrete CMS did not return a block type entity after installing "%s".', $handle),
                context: ['path' => $blockTypePath],
            );
        }

        $this->lifecycleLogger->logSuccess(
            operation: 'install',
            target: $handle,
            context: ['path' => $blockTypePath],
        );

        return $blockType;
    }

    private function throwLoggedFailure(
        string $handle,
        string $message,
        array $context = [],
        ?Throwable $previous = null,
    ): never {
        $exception = new BlockTypeInstallException(message: $message, previous: $previous);
        $this->lifecycleLogger->logFailure(
            operation: 'install',
            target: $handle,
            exception: $exception,
            context: $context,
        );

        throw $exception;
    }
}
