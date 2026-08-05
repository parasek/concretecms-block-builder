<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use ArrayIterator;
use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Service\BlockHandleLockManager;
use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\BlockGenerationResult;
use BlockBuilder\BlockGenerator\BlockGenerator;
use BlockBuilder\BlockGenerator\BlockIconGenerator;
use BlockBuilder\BlockGenerator\Directory\BlockDirectoryManager;
use BlockBuilder\BlockGenerator\Directory\BlockDirectoryTransaction;
use BlockBuilder\BlockGenerator\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\BlockGenerator\Exception\BlockGenerationException;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorCollection;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFileCollection;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFileWriter;
use BlockBuilder\BlockGenerator\Generation\BlockGenerationPlanFactory;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlan;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Lifecycle\BlockTypeLifecycleService;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Closure;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use Traversable;

/**
 * Test type: Transactional block-generation orchestration component test.
 *
 * Verifies generation order, locking, rebuild cleanup, and failure handling across rendering,
 * writing, icon generation, lifecycle operations, rollback, and retained recovery backups.
 */
final class BlockGeneratorTransactionTest extends BlockBuilderTestCase
{
    private Filesystem $filesystem;
    private string $temporaryDirectory;
    private string $lockDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-generator-transaction-test-' . bin2hex(random_bytes(8));
        $this->lockDirectory = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'locks';
        $this->filesystem->mkdir($this->lockDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);

        parent::tearDown();
    }

    /**
     * Verifies that new-block generation runs each phase in order and always releases its handle lock.
     */
    public function testSuccessfulGenerationRunsInOrderAndReleasesLock(): void
    {
        $events = new BlockGeneratorTestEventLog();
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new_block';
        $generator = $this->createGenerator(
            $events,
            function () use ($blockPath): BlockDirectoryTransaction {
                $this->filesystem->mkdir($blockPath);

                return $this->createTransaction($blockPath, null);
            },
        );

        $result = $generator->generate(
            $this->createAllFieldTypesConfig(),
            $this->createManifestForPath($blockPath),
        );

        self::assertInstanceOf(BlockGenerationResult::class, $result);
        self::assertSame(PostGenerationBlockStateEnum::Created, $result->postGenerationBlockState);
        self::assertSame([
            'plan',
            'render',
            'prepare',
            'write',
            'icon',
            'lifecycle',
        ], $events->events);
        self::assertDirectoryExists($blockPath);
        $this->assertLockReleased();
    }

    /**
     * Verifies that a successful rebuild completes its lifecycle and removes the no-longer-needed backup.
     */
    public function testSuccessfulRebuildCleansBackupAfterLifecycleCompletes(): void
    {
        $events = new BlockGeneratorTestEventLog();
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $this->filesystem->mkdir($blockPath);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'original');
        $generator = $this->createGenerator(
            $events,
            function () use ($blockPath, $backupPath): BlockDirectoryTransaction {
                $this->filesystem->rename($blockPath, $backupPath);
                $transaction = $this->createTransaction($blockPath, $backupPath);
                $transaction->markBackupPrepared();
                $this->filesystem->mkdir($blockPath);
                $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');

                return $transaction;
            },
        );

        $result = $generator->generate(
            $this->createAllFieldTypesConfig(),
            $this->createManifestForPath($blockPath, true),
        );

        self::assertSame(PostGenerationBlockStateEnum::Created, $result->postGenerationBlockState);
        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertDirectoryDoesNotExist($backupPath);
        self::assertFileDoesNotExist($backupPath . '.state');
        $this->assertLockReleased();
    }

    /**
     * Verifies that backup-cleanup errors are logged without changing an otherwise successful result.
     */
    public function testBackupCleanupFailureIsLoggedWithoutFailingSuccessfulGeneration(): void
    {
        $events = new BlockGeneratorTestEventLog();
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $this->filesystem->mkdir($blockPath);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'original');
        $realFilesystem = $this->filesystem;
        $transactionFilesystem = $this->getMockBuilder(Filesystem::class)
            ->onlyMethods(['remove'])
            ->getMock();
        $transactionFilesystem->method('remove')->willReturnCallback(
            static function (string|array $paths) use ($backupPath, $realFilesystem): void {
                if ($paths === $backupPath) {
                    throw new RuntimeException('simulated cleanup failure');
                }
                $realFilesystem->remove($paths);
            },
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $generator = $this->createGenerator(
            $events,
            function () use ($blockPath, $backupPath, $transactionFilesystem, $logger): BlockDirectoryTransaction {
                $this->filesystem->rename($blockPath, $backupPath);
                $transaction = new BlockDirectoryTransaction(
                    $blockPath,
                    $backupPath,
                    $transactionFilesystem,
                    $logger,
                    'all_field_types_test',
                );
                $transaction->markBackupPrepared();
                $this->filesystem->mkdir($blockPath);

                return $transaction;
            },
        );

        $result = $generator->generate(
            $this->createAllFieldTypesConfig(),
            $this->createManifestForPath($blockPath, true),
        );

        self::assertSame(PostGenerationBlockStateEnum::Created, $result->postGenerationBlockState);
        self::assertDirectoryExists($blockPath);
        self::assertDirectoryExists($backupPath);
        self::assertSame('lifecycle_completed', $this->readFile($backupPath . '.state'));
        $this->assertLockReleased();
    }

    /**
     * Verifies that planning and rendering errors occur before any block directory is prepared or changed.
     *
     * @dataProvider preDirectoryFailureProvider
     */
    public function testRenderingFailuresOccurBeforeDirectoryPreparation(string $failureEvent): void
    {
        $failure = new RuntimeException('simulated ' . $failureEvent . ' failure');
        $events = new BlockGeneratorTestEventLog($failureEvent, $failure);
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new_block';
        $generator = $this->createGenerator(
            $events,
            function () use ($blockPath): BlockDirectoryTransaction {
                $this->filesystem->mkdir($blockPath);

                return $this->createTransaction($blockPath, null);
            },
        );

        try {
            $generator->generate(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath),
            );
            self::fail('The configured generation failure must be reported.');
        } catch (BlockGenerationException $exception) {
            self::assertSame($failure, $exception->getPrevious());
        }

        $expectedEvents = $failureEvent === 'plan' ? ['plan'] : ['plan', 'render'];
        self::assertSame($expectedEvents, $events->events);
        self::assertDirectoryDoesNotExist($blockPath);
        $this->assertLockReleased();
    }

    public function preDirectoryFailureProvider(): array
    {
        return [
            'plan creation' => ['plan'],
            'file rendering' => ['render'],
        ];
    }

    /**
     * Verifies that a file-writing failure removes the partially generated new block directory.
     */
    public function testWriterFailureRollsBackNewBlockDirectory(): void
    {
        $failure = new RuntimeException('simulated writer failure');
        $events = new BlockGeneratorTestEventLog('write', $failure);
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new_block';
        $generator = $this->createGenerator(
            $events,
            function () use ($blockPath): BlockDirectoryTransaction {
                $this->filesystem->mkdir($blockPath);
                $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'partial.php', 'partial');

                return $this->createTransaction($blockPath, null);
            },
        );

        try {
            $generator->generate(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath),
            );
            self::fail('The writer failure must be reported.');
        } catch (BlockGenerationException $exception) {
            self::assertSame($failure, $exception->getPrevious());
        }

        self::assertSame(['plan', 'render', 'prepare', 'write'], $events->events);
        self::assertDirectoryDoesNotExist($blockPath);
        $this->assertLockReleased();
    }

    /**
     * Verifies that an icon-generation failure restores an existing block from its backup.
     */
    public function testIconFailureRestoresExistingBlockDirectory(): void
    {
        $failure = new RuntimeException('simulated icon failure');
        $events = new BlockGeneratorTestEventLog('icon', $failure);
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $this->filesystem->mkdir($blockPath);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'original');
        $generator = $this->createGenerator(
            $events,
            function () use ($blockPath, $backupPath): BlockDirectoryTransaction {
                $this->filesystem->rename($blockPath, $backupPath);
                $transaction = $this->createTransaction($blockPath, $backupPath);
                $transaction->markBackupPrepared();
                $this->filesystem->mkdir($blockPath);
                $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');

                return $transaction;
            },
        );

        try {
            $generator->generate(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath, true),
            );
            self::fail('The icon failure must be reported.');
        } catch (BlockGenerationException $exception) {
            self::assertSame($failure, $exception->getPrevious());
        }

        self::assertSame('original', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertDirectoryDoesNotExist($backupPath);
        self::assertFileDoesNotExist($backupPath . '.state');
        self::assertSame(['plan', 'render', 'prepare', 'write', 'icon'], $events->events);
        $this->assertLockReleased();
    }

    /**
     * Verifies that a post-commit lifecycle failure keeps both generated files and backup for manual recovery.
     */
    public function testLifecycleFailureAfterCommitRetainsGeneratedFilesAndBackupForRecovery(): void
    {
        $failure = new RuntimeException('simulated lifecycle failure');
        $events = new BlockGeneratorTestEventLog('lifecycle', $failure);
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $this->filesystem->mkdir($blockPath);
        $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'original');
        $generator = $this->createGenerator(
            $events,
            function () use ($blockPath, $backupPath): BlockDirectoryTransaction {
                $this->filesystem->rename($blockPath, $backupPath);
                $transaction = $this->createTransaction($blockPath, $backupPath);
                $transaction->markBackupPrepared();
                $this->filesystem->mkdir($blockPath);
                $this->writeFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php', 'generated');

                return $transaction;
            },
        );

        try {
            $generator->generate(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath, true),
            );
            self::fail('The lifecycle failure must be reported.');
        } catch (BlockGenerationException $exception) {
            self::assertStringContainsString('after its generated files were committed', $exception->getMessage());
            self::assertSame($failure, $exception->getPrevious());
        }

        self::assertSame('generated', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertSame('original', $this->readFile($backupPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertSame('files_committed', $this->readFile($backupPath . '.state'));
        self::assertSame([
            'plan',
            'render',
            'prepare',
            'write',
            'icon',
            'lifecycle',
        ], $events->events);
        $this->assertLockReleased();
    }

    /**
     * Verifies that an unsuccessful rollback reports both the generation error and the recovery error.
     */
    public function testRollbackFailureReportsOriginalAndRecoveryFailure(): void
    {
        $failure = new RuntimeException('simulated writer failure');
        $events = new BlockGeneratorTestEventLog('write', $failure);
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block';
        $backupPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing_block.backup';
        $this->filesystem->mkdir($blockPath);
        $generator = $this->createGenerator(
            $events,
            function () use ($blockPath, $backupPath): BlockDirectoryTransaction {
                $this->filesystem->mkdir($backupPath);
                $transaction = $this->createTransaction($blockPath, $backupPath);
                $transaction->markBackupPrepared();
                $this->filesystem->remove($backupPath);

                return $transaction;
            },
        );

        try {
            $generator->generate(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath, true),
            );
            self::fail('Both the original and rollback failure must be reported.');
        } catch (BlockGenerationException $exception) {
            self::assertStringContainsString('directory rollback failed', $exception->getMessage());
            self::assertStringContainsString('missing or is not a physical directory', $exception->getMessage());
            self::assertSame($failure, $exception->getPrevious());
        }

        self::assertDirectoryExists($blockPath);
        self::assertFileExists($backupPath . '.state');
        $this->assertLockReleased();
    }

    /**
     * Verifies that a lock-acquisition error is wrapped clearly before any generation phase begins.
     */
    public function testLockAcquisitionFailureIsWrappedBeforeGenerationStarts(): void
    {
        $events = new BlockGeneratorTestEventLog();
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new_block';
        $missingLockDirectory = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'missing-locks';
        $generator = $this->createGenerator(
            $events,
            fn(): BlockDirectoryTransaction => $this->createTransaction($blockPath, null),
            $missingLockDirectory,
        );

        try {
            $generator->generate(
                $this->createAllFieldTypesConfig(),
                $this->createManifestForPath($blockPath),
            );
            self::fail('A missing lock directory must fail generation.');
        } catch (BlockGenerationException $exception) {
            self::assertStringContainsString('Unable to acquire the generation lock', $exception->getMessage());
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }

        self::assertSame([], $events->events);
        self::assertDirectoryDoesNotExist($blockPath);
    }

    private function createGenerator(
        BlockGeneratorTestEventLog $events,
        Closure $prepareTransaction,
        ?string $lockDirectory = null,
    ): BlockGenerator {
        return new BlockGenerator(
            new RecordingBlockDirectoryManager($events, $prepareTransaction),
            new RecordingBlockGenerationPlanFactory($events),
            new RecordingGeneratedTextFileWriter($events),
            new RecordingFileGeneratorCollection($events),
            new RecordingBlockIconGenerator($events),
            new RecordingBlockTypeLifecycleService($events),
            new BlockHandleLockManager($lockDirectory ?? $this->lockDirectory),
        );
    }

    private function createTransaction(string $blockPath, ?string $backupPath): BlockDirectoryTransaction
    {
        return new BlockDirectoryTransaction(
            $blockPath,
            $backupPath,
            $this->filesystem,
            $this->createStub(LoggerInterface::class),
            'all_field_types_test',
        );
    }

    private function createManifestForPath(
        string $blockPath,
        bool $shouldRebuildBlock = false,
    ): BlockGenerationManifest {
        return new BlockGenerationManifest(
            shouldInstallBlock: false,
            shouldRebuildBlock: $shouldRebuildBlock,
            blockHandlePascalCase: 'AllFieldTypesTest',
            blockHandleKebabCase: 'all-field-types-test',
            blockPath: $blockPath,
            blockPublicPath: '/application/blocks/all_field_types_test',
            blockIconPath: null,
            blockIconPublicPath: null,
            customBlockIcon: null,
            databaseTableName: 'btAllFieldTypesTest',
            entriesDatabaseTableName: 'btAllFieldTypesTestEntries',
        );
    }

    private function assertLockReleased(): void
    {
        $lockPaths = glob($this->lockDirectory . DIRECTORY_SEPARATOR . '.block-builder-*.lock');
        self::assertIsArray($lockPaths);
        self::assertCount(1, $lockPaths);
        $competingStream = fopen($lockPaths[0], 'c+b');
        self::assertIsResource($competingStream);
        try {
            self::assertTrue(flock($competingStream, LOCK_EX | LOCK_NB), 'The generation lock was not released.');
            flock($competingStream, LOCK_UN);
        } finally {
            fclose($competingStream);
        }
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

final class BlockGeneratorTestEventLog
{
    /** @var string[] */
    public array $events = [];

    public function __construct(
        public ?string $failureEvent = null,
        public ?Throwable $failure = null,
    ) {
    }

    public function record(string $event): void
    {
        $this->events[] = $event;
        if ($event === $this->failureEvent) {
            throw $this->failure ?? new RuntimeException('Simulated generation failure.');
        }
    }
}

readonly class RecordingBlockDirectoryManager extends BlockDirectoryManager
{
    public function __construct(
        private BlockGeneratorTestEventLog $eventLog,
        private Closure $prepareTransaction,
    ) {
    }

    public function prepare(BlockConfigDto $config, BlockGenerationManifest $manifest): BlockDirectoryTransaction
    {
        $this->eventLog->record('prepare');

        return ($this->prepareTransaction)();
    }
}

readonly class RecordingBlockGenerationPlanFactory extends BlockGenerationPlanFactory
{
    public function __construct(private BlockGeneratorTestEventLog $eventLog)
    {
    }

    public function create(BlockConfigDto $config, BlockGenerationManifest $manifest): BlockGenerationPlan
    {
        $this->eventLog->record('plan');

        return (new BlockGenerationPlanBuilder())->build();
    }
}

readonly class RecordingGeneratedTextFileWriter extends GeneratedTextFileWriter
{
    public function __construct(private BlockGeneratorTestEventLog $eventLog)
    {
    }

    public function write(GeneratedTextFileCollection $generatedFiles, BlockFileGenerationContext $context): void
    {
        $this->eventLog->record('write');
    }
}

readonly class RecordingFileGeneratorCollection extends FileGeneratorCollection
{
    public function __construct(private BlockGeneratorTestEventLog $eventLog)
    {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator([new RecordingFileGenerator($this->eventLog)]);
    }
}

readonly class RecordingFileGenerator implements FileGeneratorInterface
{
    public function __construct(private BlockGeneratorTestEventLog $eventLog)
    {
    }

    public function generate(BlockFileGenerationContext $context): array
    {
        $this->eventLog->record('render');

        return [new GeneratedTextFile('controller.php', '<?php', self::class)];
    }
}

readonly class RecordingBlockIconGenerator extends BlockIconGenerator
{
    public function __construct(private BlockGeneratorTestEventLog $eventLog)
    {
    }

    public function generate(BlockGenerationManifest $manifest): void
    {
        $this->eventLog->record('icon');
    }
}

readonly class RecordingBlockTypeLifecycleService extends BlockTypeLifecycleService
{
    public function __construct(private BlockGeneratorTestEventLog $eventLog)
    {
    }

    public function installOrRefresh(
        BlockConfigDto $config,
        BlockGenerationManifest $manifest,
    ): PostGenerationBlockStateEnum {
        $this->eventLog->record('lifecycle');

        return PostGenerationBlockStateEnum::Created;
    }
}
