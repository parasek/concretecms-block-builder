<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Filesystem;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\Directory\BlockDirectoryManager;
use BlockBuilder\BlockGenerator\Exception\BlockDirectoryPreparationException;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Concrete\Core\File\Service\File as FileService;
use FilesystemIterator;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Test type: Block directory preparation filesystem component test.
 *
 * Verifies that new and rebuilt block directories are prepared safely, preserve custom files,
 * create recoverable backups, reject symbolic links, and stop when stale recovery data is found.
 */
final class BlockDirectoryManagerTest extends BlockBuilderTestCase
{
    private Filesystem $filesystem;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-directory-manager-test-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);

        parent::tearDown();
    }

    /**
     * Verifies that preparing a new block creates its directory and a transaction that can roll it back.
     */
    public function testPreparingNewBlockCreatesRollbackCapableDirectory(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new_block';
        $transaction = $this->createManager()->prepare(
            $this->createAllFieldTypesConfig(),
            $this->createManifestForPath($blockPath, false),
        );

        self::assertDirectoryExists($blockPath);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'partial.php', 'partial');

        $transaction->rollback();

        self::assertDirectoryDoesNotExist($blockPath);
    }

    /**
     * Verifies that a new block cannot overwrite an existing file or directory at its destination.
     *
     * @dataProvider existingNewBlockDestinationProvider
     */
    public function testPreparingNewBlockRejectsExistingDestination(string $destinationType): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new_block';
        if ($destinationType === 'directory') {
            $this->filesystem->mkdir($blockPath);
        } else {
            $this->writeFile($blockPath, 'existing file');
        }

        $this->expectException(BlockDirectoryPreparationException::class);
        $this->expectExceptionMessage('already exists');

        $this->createManager()->prepare(
            $this->createAllFieldTypesConfig(),
            $this->createManifestForPath($blockPath, false),
        );
    }

    public function existingNewBlockDestinationProvider(): array
    {
        return [
            'existing directory' => ['directory'],
            'existing file' => ['file'],
        ];
    }

    /**
     * Verifies that a symlink destination is rejected without changing the directory it points to.
     */
    public function testPreparingNewBlockRejectsSymlinkDestinationWithoutTouchingTarget(): void
    {
        $targetPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'target';
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new_block';
        $this->filesystem->mkdir($targetPath);
        $this->writeFile($targetPath . DIRECTORY_SEPARATOR . 'keep.txt', 'keep');
        if (!@symlink($targetPath, $blockPath)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        try {
            $this->createManager()->prepare(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath, false),
            );
            self::fail('A new block must not replace a symbolic-link destination.');
        } catch (BlockDirectoryPreparationException $exception) {
            self::assertStringContainsString('already exists', $exception->getMessage());
        }

        self::assertSame('keep', $this->readFile($targetPath . DIRECTORY_SEPARATOR . 'keep.txt'));
        self::assertTrue(is_link($blockPath));
    }

    /**
     * Verifies that rebuild preparation backs up the original block and removes only generated files.
     */
    public function testPreparingRebuildMirrorsOriginalAndRemovesOnlyGeneratedContents(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $this->filesystem->mkdir($blockPath . DIRECTORY_SEPARATOR . 'templates');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'original controller');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'view.php', 'original view');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON, 'original icon');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'custom.php', 'custom');
        $originalSnapshot = $this->snapshotDirectory($blockPath);
        $config = $this->withExcludedFromRemoval($this->createAllFieldTypesConfig(), ['templates']);
        $manifest = $this->createManifestForPath(
            blockPath: $blockPath,
            shouldRebuildBlock: true,
            blockIconPublicPath: '/application/blocks/existing_block/icon.png',
        );

        $transaction = $this->createManager()->prepare($config, $manifest);

        self::assertFileDoesNotExist($blockPath . DIRECTORY_SEPARATOR . 'controller.php');
        self::assertFileDoesNotExist($blockPath . DIRECTORY_SEPARATOR . 'view.php');
        self::assertSame('original icon', $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON));
        self::assertSame('custom', $this->readFile(
            $blockPath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'custom.php',
        ));
        self::assertCount(1, $this->findBackupDirectories($blockPath));

        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated controller');
        $transaction->rollback();

        self::assertSame($originalSnapshot, $this->snapshotDirectory($blockPath));
        self::assertSame([], $this->findBackupDirectories($blockPath));
    }

    /**
     * Verifies that rebuild preparation removes an icon that is not marked as application-owned.
     */
    public function testPreparingRebuildRemovesIconWhenItIsNotAnApplicationBlockIcon(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $this->filesystem->mkdir($blockPath);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON, 'old icon');

        $transaction = $this->createManager()->prepare(
            $this->createAllFieldTypesConfig(),
            $this->createManifestForPath(
                blockPath: $blockPath,
                shouldRebuildBlock: true,
                blockIconPublicPath: '/packages/block_builder/generator_files/icon.png',
            ),
        );

        self::assertFileDoesNotExist($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON);
        $transaction->rollback();
        self::assertSame('old icon', $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON));
    }

    /**
     * Verifies that a failed rebuild preparation restores the original block directory unchanged.
     */
    public function testPreparationFailureRestoresOriginalDirectory(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $this->filesystem->mkdir($blockPath . DIRECTORY_SEPARATOR . 'nested');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'original controller');
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'custom.txt', 'original custom');
        $originalSnapshot = $this->snapshotDirectory($blockPath);
        $realFilesystem = $this->filesystem;
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->onlyMethods(['remove'])
            ->getMock();
        $filesystem->method('remove')->willReturnCallback(
            static function (string|array $paths) use ($blockPath, $realFilesystem): void {
                if ($paths === $blockPath . DIRECTORY_SEPARATOR . 'controller.php') {
                    throw new RuntimeException('simulated removal failure');
                }
                $realFilesystem->remove($paths);
            },
        );

        try {
            $this->createManager($filesystem)->prepare(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath, true),
            );
            self::fail('A preparation failure must be reported.');
        } catch (BlockDirectoryPreparationException $exception) {
            self::assertStringContainsString('Unable to prepare directory', $exception->getMessage());
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }

        self::assertSame($originalSnapshot, $this->snapshotDirectory($blockPath));
        self::assertSame([], $this->findBackupDirectories($blockPath));
    }

    /**
     * Verifies that rebuilding through a symlink is rejected without changing its target.
     */
    public function testPreparingRebuildRejectsSymlinkDestinationWithoutTouchingTarget(): void
    {
        $targetPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'target';
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $this->filesystem->mkdir($targetPath);
        $this->writeFile($targetPath . DIRECTORY_SEPARATOR . 'controller.php', 'target');
        if (!@symlink($targetPath, $blockPath)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        $this->expectException(BlockDirectoryPreparationException::class);
        $this->expectExceptionMessage('must be an existing physical directory');

        try {
            $this->createManager()->prepare(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath, true),
            );
        } finally {
            self::assertSame('target', $this->readFile($targetPath . DIRECTORY_SEPARATOR . 'controller.php'));
            self::assertTrue(is_link($blockPath));
        }
    }

    /**
     * Verifies that multiple stale backups stop automatic preparation and require manual recovery.
     */
    public function testMultipleStaleBackupsRequireManualRecovery(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $firstBackupPath = $blockPath . '.block-builder-backup-1111111111111111';
        $secondBackupPath = $blockPath . '.block-builder-backup-2222222222222222';
        $this->filesystem->mkdir([$blockPath, $firstBackupPath, $secondBackupPath]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $this->expectException(BlockDirectoryPreparationException::class);
        $this->expectExceptionMessage('multiple stale backups');

        try {
            $this->createManager(logger: $logger)->prepare(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath, true),
            );
        } finally {
            self::assertDirectoryExists($blockPath);
            self::assertDirectoryExists($firstBackupPath);
            self::assertDirectoryExists($secondBackupPath);
        }
    }

    /**
     * Verifies that an orphaned recovery-state marker prevents generation until it is handled manually.
     */
    public function testOrphanedStateMarkerRequiresManualRecovery(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $blockPath . '.block-builder-backup-1111111111111111';
        $statePath = $backupPath . '.state';
        $this->filesystem->mkdir($blockPath);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');
        $this->writeFile($statePath, 'prepared');

        try {
            $this->createManager()->prepare(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath, true),
            );
            self::fail('An orphaned transaction state must stop generation for manual recovery.');
        } catch (BlockDirectoryPreparationException $exception) {
            self::assertStringContainsString('missing or is not a physical directory', $exception->getMessage());
        }

        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertFileExists($statePath);
    }

    private function createManager(
        ?Filesystem $filesystem = null,
        ?LoggerInterface $logger = null,
    ): BlockDirectoryManager {
        return new BlockDirectoryManager(
            new FileService(),
            $filesystem ?? $this->filesystem,
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }

    private function createManifestForPath(
        string $blockPath,
        bool $shouldRebuildBlock,
        ?string $blockIconPublicPath = null,
    ): BlockGenerationManifest {
        return new BlockGenerationManifest(
            shouldInstallBlock: false,
            shouldRebuildBlock: $shouldRebuildBlock,
            blockHandlePascalCase: 'ExistingBlock',
            blockHandleKebabCase: 'existing-block',
            blockPath: $blockPath,
            blockPublicPath: '/application/blocks/existing_block',
            blockIconPath: null,
            blockIconPublicPath: $blockIconPublicPath,
            customBlockIcon: null,
            databaseTableName: 'btExistingBlock',
            entriesDatabaseTableName: 'btExistingBlockEntries',
        );
    }

    private function withExcludedFromRemoval(BlockConfigDto $config, array $excludedFromRemoval): BlockConfigDto
    {
        $properties = get_object_vars($config);
        $properties['excludedFromRemoval'] = $excludedFromRemoval;

        return new BlockConfigDto(...$properties);
    }

    /**
     * @return string[]
     */
    private function findBackupDirectories(string $blockPath): array
    {
        $backupPaths = glob($blockPath . '.block-builder-backup-*', GLOB_ONLYDIR);
        self::assertIsArray($backupPaths);
        sort($backupPaths, SORT_STRING);

        return $backupPaths;
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
