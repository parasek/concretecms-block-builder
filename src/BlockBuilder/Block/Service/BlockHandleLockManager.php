<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use RuntimeException;

final readonly class BlockHandleLockManager
{
    public function __construct(private ?string $lockDirectory = null)
    {
    }

    public function acquire(string $blockHandle): BlockHandleLock
    {
        $lockDirectory = $this->lockDirectory ?? DIR_FILES_BLOCK_TYPES;
        if (!is_dir($lockDirectory)) {
            throw new RuntimeException(sprintf(
                'Unable to lock block "%s" because the application block directory does not exist.',
                $blockHandle,
            ));
        }

        $lockPath = $lockDirectory
            . DIRECTORY_SEPARATOR
            . '.block-builder-' . hash('sha256', $blockHandle) . '.lock';
        $stream = @fopen($lockPath, 'c+b');
        if ($stream === false) {
            throw new RuntimeException(sprintf('Unable to open the operation lock for block "%s".', $blockHandle));
        }

        if (!flock($stream, LOCK_EX)) {
            fclose($stream);

            throw new RuntimeException(sprintf('Unable to acquire the operation lock for block "%s".', $blockHandle));
        }

        return new BlockHandleLock($stream);
    }
}
