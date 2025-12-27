<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Filesystem;

use BlockBuilder\BlockGenerator\Directory\BlockDirectoryTransaction;
use BlockBuilder\BlockGenerator\Exception\BlockDirectoryPreparationException;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Test type: Block directory rollback and recovery filesystem component test.
 *
 * Verifies transaction rollback, crash recovery, and cleanup while ensuring missing, malformed,
 * or symbolic-link recovery artifacts fail safely without modifying unrelated files.
 */
final class BlockDirectoryTransactionTest extends TestCase
{
    private Filesystem $filesystem;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-transaction-test-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);
    }

    /**
     * Verifies that rolling back a new block removes its partially generated directory.
     */
    public function testRollbackRemovesPartiallyGeneratedNewBlock(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new_block';
        $this->filesystem->mkdir($blockPath . DIRECTORY_SEPARATOR . 'nested');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'partial.php', 'partial');
        $transaction = $this->createRealTransaction($blockPath, null);

        $transaction->rollback();
        $transaction->rollback();

        self::assertFileDoesNotExist($blockPath);
        self::assertFalse($transaction->canRollback());
    }

    /**
     * Verifies that rollback restores every backed-up file with its original contents.
     */
    public function testRollbackRestoresPreparedBackupByteForByte(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $this->filesystem->mkdir($blockPath . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'empty');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', "original\n");
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'binary.dat', "\x00\x01\xff");
        $expectedSnapshot = $this->snapshotDirectory($blockPath);
        $this->filesystem->rename($blockPath, $backupPath);

        $transaction = $this->createRealTransaction($blockPath, $backupPath);
        $transaction->markBackupPrepared();
        $this->filesystem->mkdir($blockPath . DIRECTORY_SEPARATOR . 'generated');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'replacement');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . 'new.php', 'new');

        $transaction->rollback();

        self::assertSame($expectedSnapshot, $this->snapshotDirectory($blockPath));
        self::assertFileDoesNotExist($backupPath);
        self::assertFileDoesNotExist($backupPath . '.state');
    }

    /**
     * Verifies that committed files cannot be rolled back and final cleanup removes recovery artifacts.
     */
    public function testCommittedFilesCannotBeRolledBackAndCleanupRemovesRecoveryArtifacts(): void
    {
        [$blockPath, $backupPath, $transaction] = $this->createPreparedRealTransaction();
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');

        $transaction->commitGeneratedFiles();
        self::assertSame('files_committed', $this->readFile($backupPath . '.state'));
        self::assertFalse($transaction->canRollback());

        $transaction->rollback();
        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertDirectoryExists($backupPath);

        $transaction->markLifecycleCompleted();
        self::assertSame('lifecycle_completed', $this->readFile($backupPath . '.state'));

        $transaction->cleanupBackup();
        $transaction->cleanupBackup();
        self::assertDirectoryDoesNotExist($backupPath);
        self::assertFileDoesNotExist($backupPath . '.state');
    }

    /**
     * Verifies that rollback stops safely when the prepared backup has disappeared.
     */
    public function testRollbackFailsClosedWhenPreparedBackupDisappears(): void
    {
        [$blockPath, $backupPath, $transaction] = $this->createPreparedRealTransaction();
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');
        $this->filesystem->remove($backupPath);

        try {
            $transaction->rollback();
            self::fail('Rollback must fail when its prepared backup has disappeared.');
        } catch (BlockDirectoryPreparationException $exception) {
            self::assertStringContainsString('missing or is not a physical directory', $exception->getMessage());
        }

        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertFileExists($backupPath . '.state');
        self::assertTrue($transaction->canRollback());
    }

    /**
     * Verifies that rollback rejects a symlinked backup without modifying the symlink target.
     */
    public function testRollbackRejectsSymlinkedBackupWithoutTouchingTarget(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $externalPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'external';
        $this->filesystem->mkdir([$blockPath, $externalPath]);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');
        $this->writeFile($externalPath . DIRECTORY_SEPARATOR . 'controller.php', 'original');
        if (!@symlink($externalPath, $backupPath)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }
        $transaction = $this->createRealTransaction($blockPath, $backupPath);
        $transaction->markBackupPrepared();

        try {
            $transaction->rollback();
            self::fail('Rollback must reject a symbolic-link backup.');
        } catch (BlockDirectoryPreparationException) {
        }

        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertSame('original', $this->readFile($externalPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertTrue(is_link($backupPath));
    }

    /**
     * Verifies that recovery restores a valid backup left in the prepared state.
     */
    public function testRecoveryRestoresPreparedBackup(): void
    {
        [$blockPath, $backupPath] = $this->createRecoveryDirectories('prepared');

        BlockDirectoryTransaction::recover(
            'existing_block',
            $blockPath,
            $backupPath,
            $this->filesystem,
            $this->createStub(LoggerInterface::class),
        );

        self::assertSame('original', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertFileDoesNotExist($backupPath);
        self::assertFileDoesNotExist($backupPath . '.state');
    }

    /**
     * Verifies that recovery does not overwrite committed generated files and requests manual action.
     */
    public function testRecoveryRequiresManualActionAfterFilesWereCommitted(): void
    {
        [$blockPath, $backupPath] = $this->createRecoveryDirectories('files_committed');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        try {
            BlockDirectoryTransaction::recover(
                'existing_block',
                $blockPath,
                $backupPath,
                $this->filesystem,
                $logger,
            );
            self::fail('Committed files with an unknown lifecycle result require manual recovery.');
        } catch (BlockDirectoryPreparationException $exception) {
            self::assertStringContainsString('incomplete lifecycle operation', $exception->getMessage());
        }

        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertSame('original', $this->readFile($backupPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertFileExists($backupPath . '.state');
    }

    /**
     * Verifies that recovery removes an obsolete backup after the block lifecycle completed successfully.
     */
    public function testRecoveryCleansBackupAfterLifecycleCompleted(): void
    {
        [$blockPath, $backupPath] = $this->createRecoveryDirectories('lifecycle_completed');

        BlockDirectoryTransaction::recover(
            'existing_block',
            $blockPath,
            $backupPath,
            $this->filesystem,
            $this->createStub(LoggerInterface::class),
        );

        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertDirectoryDoesNotExist($backupPath);
        self::assertFileDoesNotExist($backupPath . '.state');
    }

    /**
     * Verifies that recovery retains the backup when a completed lifecycle has no destination directory.
     */
    public function testRecoveryRetainsBackupWhenLifecycleDestinationIsMissing(): void
    {
        [$blockPath, $backupPath] = $this->createRecoveryDirectories('lifecycle_completed');
        $this->filesystem->remove($blockPath);

        $this->expectException(BlockDirectoryPreparationException::class);
        $this->expectExceptionMessage('generated folder');

        try {
            BlockDirectoryTransaction::recover(
                'existing_block',
                $blockPath,
                $backupPath,
                $this->filesystem,
                $this->createStub(LoggerInterface::class),
            );
        } finally {
            self::assertDirectoryExists($backupPath);
            self::assertFileExists($backupPath . '.state');
        }
    }

    /**
     * Verifies that recovery retains the backup when the completed lifecycle destination is a symlink.
     */
    public function testRecoveryRetainsBackupWhenLifecycleDestinationIsASymbolicLink(): void
    {
        [$blockPath, $backupPath] = $this->createRecoveryDirectories('lifecycle_completed');
        $externalPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'external';
        $this->filesystem->remove($blockPath);
        $this->filesystem->mkdir($externalPath);
        $this->writeFile($externalPath . DIRECTORY_SEPARATOR . 'controller.php', 'external');
        if (!@symlink($externalPath, $blockPath)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        try {
            BlockDirectoryTransaction::recover(
                'existing_block',
                $blockPath,
                $backupPath,
                $this->filesystem,
                $this->createStub(LoggerInterface::class),
            );
            self::fail('Recovery must not discard its backup for a symbolic-link destination.');
        } catch (BlockDirectoryPreparationException $exception) {
            self::assertStringContainsString('missing or unsafe', $exception->getMessage());
        }

        self::assertSame('external', $this->readFile($externalPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertDirectoryExists($backupPath);
        self::assertFileExists($backupPath . '.state');
    }

    /**
     * Verifies that recovery rejects a missing or unknown transaction state rather than guessing an action.
     *
     * @dataProvider unrecognizedRecoveryStateProvider
     */
    public function testRecoveryRejectsMissingOrUnrecognizedState(?string $state): void
    {
        [$blockPath, $backupPath] = $this->createRecoveryDirectories($state);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $this->expectException(BlockDirectoryPreparationException::class);
        $this->expectExceptionMessage('Manual recovery is required for stale backup');

        BlockDirectoryTransaction::recover(
            'existing_block',
            $blockPath,
            $backupPath,
            $this->filesystem,
            $logger,
        );
    }

    public function unrecognizedRecoveryStateProvider(): array
    {
        return [
            'missing state marker' => [null],
            'empty state marker' => [''],
            'unknown state marker' => ['unknown'],
        ];
    }

    /**
     * Verifies that recovery rejects a symlinked backup without changing either destination.
     */
    public function testRecoveryRejectsSymlinkedBackupWithoutTouchingTarget(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $externalPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'external';
        $this->filesystem->mkdir([$blockPath, $externalPath]);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');
        $this->writeFile($externalPath . DIRECTORY_SEPARATOR . 'controller.php', 'external');
        if (!@symlink($externalPath, $backupPath)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }
        $this->writeFile($backupPath . '.state', 'prepared');

        try {
            BlockDirectoryTransaction::recover(
                'existing_block',
                $blockPath,
                $backupPath,
                $this->filesystem,
                $this->createStub(LoggerInterface::class),
            );
            self::fail('Recovery must reject a symbolic-link backup.');
        } catch (BlockDirectoryPreparationException $exception) {
            self::assertStringContainsString('not a physical directory', $exception->getMessage());
        }

        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertSame('external', $this->readFile($externalPath . DIRECTORY_SEPARATOR . 'controller.php'));
    }

    /**
     * Verifies that recovery rejects a symlinked state marker without touching the block or backup.
     */
    public function testRecoveryRejectsSymlinkedStateMarkerWithoutTouchingBackupOrTarget(): void
    {
        [$blockPath, $backupPath] = $this->createRecoveryDirectories(null);
        $externalStatePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'external-state';
        $this->writeFile($externalStatePath, 'lifecycle_completed');
        if (!@symlink($externalStatePath, $backupPath . '.state')) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        try {
            BlockDirectoryTransaction::recover(
                'existing_block',
                $blockPath,
                $backupPath,
                $this->filesystem,
                $this->createStub(LoggerInterface::class),
            );
            self::fail('Recovery must reject a symbolic-link transaction state marker.');
        } catch (BlockDirectoryPreparationException $exception) {
            self::assertStringContainsString('state marker', $exception->getMessage());
        }

        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertSame('original', $this->readFile($backupPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertSame('lifecycle_completed', $this->readFile($externalStatePath));
        self::assertTrue(is_link($backupPath . '.state'));
    }

    /**
     * Verifies that cleanup deletes the backup before removing the state marker that records it.
     */
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

    /**
     * Verifies that cleanup keeps the state marker when backup deletion fails so recovery remains possible.
     */
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

    private function createRealTransaction(string $blockPath, ?string $backupPath): BlockDirectoryTransaction
    {
        return new BlockDirectoryTransaction(
            blockPath: $blockPath,
            backupPath: $backupPath,
            filesystem: $this->filesystem,
            logger: $this->createStub(LoggerInterface::class),
            blockHandle: 'existing_block',
        );
    }

    /**
     * @return array{string, string, BlockDirectoryTransaction}
     */
    private function createPreparedRealTransaction(): array
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $this->filesystem->mkdir($backupPath);
        $this->writeFile($backupPath . DIRECTORY_SEPARATOR . 'controller.php', 'original');
        $this->filesystem->mkdir($blockPath);
        $transaction = $this->createRealTransaction($blockPath, $backupPath);
        $transaction->markBackupPrepared();

        return [$blockPath, $backupPath, $transaction];
    }

    /**
     * @return array{string, string}
     */
    private function createRecoveryDirectories(?string $state): array
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $this->filesystem->mkdir([$blockPath, $backupPath]);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');
        $this->writeFile($backupPath . DIRECTORY_SEPARATOR . 'controller.php', 'original');
        if ($state !== null) {
            $this->writeFile($backupPath . '.state', $state);
        }

        return [$blockPath, $backupPath];
    }

    /**
     * @return array<string, string>
     */
    private function snapshotDirectory(string $directory): array
    {
        $snapshot = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            $relativePath = str_replace('\\', '/', substr($item->getPathname(), strlen($directory) + 1));
            $snapshot[$relativePath] = $item->isDir() ? 'directory' : 'file:' . $this->readFile($item->getPathname());
        }
        ksort($snapshot, SORT_STRING);

        return $snapshot;
    }

    private function writeFile(string $path, string $contents): void
    {
        self::assertNotFalse(file_put_contents($path, $contents));
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
