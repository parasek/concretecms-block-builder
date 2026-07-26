<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Directory;

use BlockBuilder\BlockGenerator\Exception\BlockDirectoryPreparationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

final class BlockDirectoryTransaction
{
    private const string STATE_PREPARED = 'prepared';
    private const string STATE_FILES_COMMITTED = 'files_committed';
    private const string STATE_LIFECYCLE_COMPLETED = 'lifecycle_applied';

    private bool $finished = false;
    private bool $backupPrepared = false;
    private bool $generatedFilesCommitted = false;

    public function __construct(
        private readonly string $blockPath,
        private readonly ?string $backupPath,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
        private readonly string $blockHandle,
    ) {
    }

    public function commitGeneratedFiles(): void
    {
        if ($this->finished || $this->generatedFilesCommitted) {
            return;
        }

        $this->writeState(self::STATE_FILES_COMMITTED);
        $this->generatedFilesCommitted = true;
    }

    public function markLifecycleCompleted(): void
    {
        if ($this->finished) {
            return;
        }

        try {
            $this->writeState(self::STATE_LIFECYCLE_COMPLETED);
        } catch (Throwable $throwable) {
            $this->logger->warning(
                'Block Builder could not update the post-lifecycle transaction state for block "{blockHandle}".'
                    . PHP_EOL
                    . '{errorMessage}',
                [
                    'blockHandle' => $this->blockHandle,
                    'backupPath' => $this->backupPath,
                    'errorMessage' => $throwable->getMessage(),
                    'exception' => $throwable,
                ],
            );
        }
    }

    public function cleanupBackup(): void
    {
        if ($this->finished) {
            return;
        }

        try {
            if ($this->backupPrepared && $this->backupPath !== null) {
                $this->filesystem->remove([$this->backupPath, $this->getStatePath()]);
            }
        } catch (Throwable $throwable) {
            $this->logger->warning(
                'Block Builder could not remove the post-commit backup for block "{blockHandle}" at "{backupPath}".'
                    . PHP_EOL
                    . '{errorMessage}',
                [
                    'blockHandle' => $this->blockHandle,
                    'backupPath' => $this->backupPath,
                    'errorMessage' => $throwable->getMessage(),
                    'exception' => $throwable,
                ],
            );
        }

        $this->finished = true;
    }

    public function rollback(): void
    {
        if (!$this->canRollback()) {
            return;
        }

        if ($this->backupPath === null) {
            $this->filesystem->remove($this->blockPath);
        } elseif ($this->backupPrepared && file_exists($this->backupPath)) {
            $this->filesystem->remove($this->blockPath);
            $this->filesystem->rename($this->backupPath, $this->blockPath);
            $this->filesystem->remove($this->getStatePath());
        }

        $this->finished = true;
    }

    public function canRollback(): bool
    {
        return !$this->finished && !$this->generatedFilesCommitted;
    }

    public function markBackupPrepared(): void
    {
        $this->backupPrepared = true;
        $this->writeState(self::STATE_PREPARED);
    }

    public static function recover(
        string $blockHandle,
        string $blockPath,
        string $backupPath,
        Filesystem $filesystem,
        LoggerInterface $logger,
    ): void {
        $statePath = self::getStatePathForBackup($backupPath);
        $state = is_file($statePath) ? trim((string) file_get_contents($statePath)) : null;

        try {
            if ($state === self::STATE_PREPARED) {
                $filesystem->remove($blockPath);
                $filesystem->rename($backupPath, $blockPath);
                $filesystem->remove($statePath);

                return;
            }

            if ($state === self::STATE_FILES_COMMITTED) {
                $logger->error(
                    'Block Builder found a block whose files were committed without a confirmed lifecycle result for block "{blockHandle}".',
                    ['blockHandle' => $blockHandle, 'backupPath' => $backupPath],
                );

                throw new BlockDirectoryPreparationException(sprintf(
                    'Manual recovery is required for the incomplete lifecycle operation of block "%s".',
                    $blockHandle,
                ));
            }

            if ($state === self::STATE_LIFECYCLE_COMPLETED) {
                if (!is_dir($blockPath)) {
                    throw new BlockDirectoryPreparationException(sprintf(
                        'Manual recovery is required because the generated folder for block "%s" is missing after its lifecycle completed.',
                        $blockHandle,
                    ));
                }

                $filesystem->remove($backupPath);
                $filesystem->remove($statePath);

                return;
            }
        } catch (BlockDirectoryPreparationException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new BlockDirectoryPreparationException(
                message: sprintf('Unable to recover stale directory transaction for block "%s" from "%s".', $blockHandle, $backupPath),
                previous: $throwable,
            );
        }

        $logger->error(
            'Block Builder found an unrecognized stale backup for block "{blockHandle}" at "{backupPath}".',
            ['blockHandle' => $blockHandle, 'backupPath' => $backupPath],
        );

        throw new BlockDirectoryPreparationException(
            sprintf('Manual recovery is required for stale backup "%s" of block "%s".', $backupPath, $blockHandle),
        );
    }

    private function writeState(string $state): void
    {
        if ($this->backupPath !== null && $this->backupPrepared) {
            $this->filesystem->dumpFile($this->getStatePath(), $state);
        }
    }

    private function getStatePath(): string
    {
        return self::getStatePathForBackup((string) $this->backupPath);
    }

    private static function getStatePathForBackup(string $backupPath): string
    {
        return $backupPath . '.state';
    }
}
