<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Directory;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Dto\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\Exception\BlockDirectoryPreparationException;
use Concrete\Core\File\Service\File as FileService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

readonly class BlockDirectoryManager
{
    public function __construct(
        private FileService $fileService,
        private Filesystem $filesystem,
        private LoggerInterface $logger,
    ) {
    }

    public function prepare(BlockConfigDto $config, BlockGenerationManifest $manifest): BlockDirectoryTransaction
    {
        $transaction = null;

        try {
            $this->recoverStaleBackups($config->blockHandle, $manifest->blockPath);

            $backupPath = $manifest->shouldBlockBeRebuilt
                ? $manifest->blockPath . '.block-builder-backup-' . bin2hex(random_bytes(8))
                : null;

            if ($backupPath === null && (file_exists($manifest->blockPath) || is_link($manifest->blockPath))) {
                throw new BlockDirectoryPreparationException(sprintf(
                    'The destination for new block "%s" already exists.',
                    $config->blockHandle,
                ));
            }
            if ($backupPath !== null && (!is_dir($manifest->blockPath) || is_link($manifest->blockPath))) {
                throw new BlockDirectoryPreparationException(sprintf(
                    'The rebuild destination for block "%s" must be an existing physical directory.',
                    $config->blockHandle,
                ));
            }

            $transaction = new BlockDirectoryTransaction(
                blockPath: $manifest->blockPath,
                backupPath: $backupPath,
                filesystem: $this->filesystem,
                logger: $this->logger,
                blockHandle: $config->blockHandle,
            );

            if ($backupPath === null) {
                $this->filesystem->mkdir($manifest->blockPath);

                return $transaction;
            }

            $this->filesystem->rename($manifest->blockPath, $backupPath);
            $transaction->markBackupPrepared();
            $this->filesystem->mirror($backupPath, $manifest->blockPath);
            $this->removeGeneratedContents($config, $manifest);

            return $transaction;
        } catch (Throwable $throwable) {
            if ($transaction !== null) {
                try {
                    $transaction->rollback();
                } catch (Throwable $rollbackThrowable) {
                    throw new BlockDirectoryPreparationException(
                        message: sprintf(
                            'Unable to prepare or restore directory "%s" for block "%s". Rollback error: %s',
                            $manifest->blockPath,
                            $config->blockHandle,
                            $rollbackThrowable->getMessage(),
                        ),
                        previous: $throwable,
                    );
                }
            }

            if ($throwable instanceof BlockDirectoryPreparationException) {
                throw $throwable;
            }

            throw new BlockDirectoryPreparationException(
                message: sprintf(
                    'Unable to prepare directory "%s" for block "%s".',
                    $manifest->blockPath,
                    $config->blockHandle,
                ),
                previous: $throwable,
            );
        }
    }

    private function recoverStaleBackups(string $blockHandle, string $blockPath): void
    {
        $backupPaths = glob($blockPath . '.block-builder-backup-*', GLOB_ONLYDIR);
        if ($backupPaths === false || $backupPaths === []) {
            return;
        }

        sort($backupPaths, SORT_STRING);
        if (count($backupPaths) > 1) {
            $this->logger->error(
                'Block Builder found multiple stale backups for block "{blockHandle}".',
                ['blockHandle' => $blockHandle, 'backupPaths' => $backupPaths],
            );

            throw new BlockDirectoryPreparationException(
                sprintf('Manual recovery is required because block "%s" has multiple stale backups.', $blockHandle),
            );
        }

        foreach ($backupPaths as $backupPath) {
            BlockDirectoryTransaction::recover(
                blockHandle: $blockHandle,
                blockPath: $blockPath,
                backupPath: $backupPath,
                filesystem: $this->filesystem,
                logger: $this->logger,
            );
        }
    }

    private function removeGeneratedContents(BlockConfigDto $config, BlockGenerationManifest $manifest): void
    {
        $excludedFromRemoval = $config->excludedFromRemoval;
        $applicationBlocksPath = DIRECTORY_SEPARATOR . DIRNAME_APPLICATION . DIRECTORY_SEPARATOR . DIRNAME_BLOCKS;

        if ($manifest->blockIconPublicPath !== null && str_starts_with($manifest->blockIconPublicPath, $applicationBlocksPath)) {
            $excludedFromRemoval[] = FILENAME_BLOCK_ICON;
        }

        $items = $this->fileService->getDirectoryContents($manifest->blockPath, array_unique($excludedFromRemoval));
        foreach ($items as $item) {
            $this->filesystem->remove($manifest->blockPath . DIRECTORY_SEPARATOR . $item);
        }
    }
}
