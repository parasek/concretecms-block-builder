<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Filesystem;

use BlockBuilder\BlockGenerator\Directory\BlockDirectoryTransaction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class BlockDirectoryTransactionTest extends TestCase
{
    public function testCleanupRemovesBackupBeforeStateMarker(): void
    {
        $removedPaths = [];
        $filesystem = $this->createMockFilesystem($removedPaths);
        $transaction = $this->createPreparedTransaction($filesystem);

        $transaction->cleanupBackup();

        self::assertSame([
            '/temporary/example-block-backup',
            '/temporary/example-block-backup.state',
        ], $removedPaths);
    }

    public function testCleanupRetainsStateMarkerWhenBackupRemovalFails(): void
    {
        $removedPaths = [];
        $filesystem = $this->createMockFilesystem(
            removedPaths: $removedPaths,
            failingPath: '/temporary/example-block-backup',
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $transaction = $this->createPreparedTransaction($filesystem, $logger);

        $transaction->cleanupBackup();

        self::assertSame(['/temporary/example-block-backup'], $removedPaths);
    }

    private function createPreparedTransaction(
        Filesystem $filesystem,
        ?LoggerInterface $logger = null,
    ): BlockDirectoryTransaction {
        $transaction = new BlockDirectoryTransaction(
            blockPath: '/temporary/example-block',
            backupPath: '/temporary/example-block-backup',
            filesystem: $filesystem,
            logger: $logger ?? $this->createStub(LoggerInterface::class),
            blockHandle: 'example_block',
        );
        $transaction->markBackupPrepared();
        $transaction->markLifecycleCompleted();

        return $transaction;
    }

    private function createMockFilesystem(array &$removedPaths, ?string $failingPath = null): Filesystem
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->onlyMethods(['dumpFile', 'remove'])
            ->getMock();
        $filesystem->method('remove')->willReturnCallback(
            static function (string $path) use (&$removedPaths, $failingPath): void {
                $removedPaths[] = $path;
                if ($path === $failingPath) {
                    throw new RuntimeException('Simulated removal failure.');
                }
            },
        );

        return $filesystem;
    }
}
