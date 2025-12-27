<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Service;

use BlockBuilder\Block\Exception\BlockDirectoryRemovalException;
use BlockBuilder\Block\Service\BlockDirectoryLocator;
use BlockBuilder\Block\Service\BlockDirectoryRemover;
use BlockBuilder\Block\Service\BlockHandleLockManager;
use BlockBuilder\Block\Service\BlockLifecycleLogger;
use BlockBuilder\Block\Service\BlockOwnershipChecker;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\Block\Service\BlockTypePermissionChecker;
use Concrete\Core\File\Service\File as FileService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * Test type: Block directory removal service unit test.
 *
 * Verifies permission, handle, path, ownership, installation-state, locking, deletion, and
 * lifecycle-logging behavior for safe generated block directory removal.
 */
final class BlockDirectoryRemoverTest extends TestCase
{
    private const string BLOCK_HANDLE = 'example_block';
    private const string BLOCK_PATH = '/application/blocks/example_block';

    private string $lockDirectory;

    protected function setUp(): void
    {
        $this->lockDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-directory-remover-test-' . bin2hex(random_bytes(8));
        (new Filesystem())->mkdir($this->lockDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->lockDirectory);
    }

    /**
     * Confirms that an exception during permission checking is wrapped, logged, and stops all
     * filesystem and locking work.
     */
    public function testPermissionCheckExceptionIsWrappedAndLoggedBeforeAnyOtherWork(): void
    {
        $permissionFailure = new RuntimeException('permission infrastructure failed');
        $state = new BlockDirectoryRemoverTestState();
        $state->permissionFailure = $permissionFailure;

        $exception = $this->captureRemovalFailure($this->createRemover($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('Unable to check permission', $exception->getMessage());
        self::assertSame($permissionFailure, $exception->getPrevious());
        self::assertSame(['permission', 'log_failure'], $state->events);
        self::assertSame([], $state->removedPaths);
        $this->assertSingleFailureLog($state, $exception);
    }

    /**
     * Confirms that denied permissions produce a logged removal failure without touching the
     * block directory.
     */
    public function testDeniedPermissionIsReturnedAsALoggedRemovalFailure(): void
    {
        $state = new BlockDirectoryRemoverTestState();
        $state->permissionError = 'Removal denied by test policy.';

        $exception = $this->captureRemovalFailure($this->createRemover($state), self::BLOCK_HANDLE);

        self::assertSame('Removal denied by test policy.', $exception->getMessage());
        self::assertNull($exception->getPrevious());
        self::assertSame(['permission', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
    }

    /**
     * Confirms that an invalid block handle is rejected before constructing a path or acquiring a
     * lifecycle lock.
     */
    public function testInvalidHandleIsRejectedBeforeAPathOrLockIsUsed(): void
    {
        $state = new BlockDirectoryRemoverTestState();

        $exception = $this->captureRemovalFailure($this->createRemover($state), 'Invalid Handle');

        self::assertStringContainsString('Invalid block type handle', $exception->getMessage());
        self::assertSame(['permission', 'log_failure'], $state->events);
        self::assertFileDoesNotExist($this->getLockPath('Invalid Handle'));
        $this->assertSingleFailureLog($state, $exception, [], 'Invalid Handle');
    }

    /**
     * Confirms that failure to acquire the handle lock is wrapped and logged without attempting
     * directory removal.
     */
    public function testLockAcquisitionFailureIsWrappedAndLogged(): void
    {
        $state = new BlockDirectoryRemoverTestState();
        $missingLockDirectory = $this->lockDirectory . DIRECTORY_SEPARATOR . 'missing';

        $exception = $this->captureRemovalFailure(
            $this->createRemover($state, $missingLockDirectory),
            self::BLOCK_HANDLE,
        );

        self::assertStringContainsString('Unable to acquire the directory operation lock', $exception->getMessage());
        self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        self::assertSame(['permission', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
    }

    /**
     * Confirms that a missing or unsafe block directory is reported and that its handle lock is
     * always released.
     */
    public function testMissingOrUnsafeDirectoryIsLoggedAndReleasesTheHandleLock(): void
    {
        $state = new BlockDirectoryRemoverTestState();
        $state->blockPath = null;

        $exception = $this->captureRemovalFailure($this->createRemover($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('missing, linked, or outside', $exception->getMessage());
        self::assertSame(['permission', 'locate_directory', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    /**
     * Confirms that a directory not owned by Block Builder is preserved, logged with its safe
     * path, and followed by lock release.
     */
    public function testUnownedDirectoryIsLoggedWithItsPathAndReleasesTheHandleLock(): void
    {
        $state = new BlockDirectoryRemoverTestState();
        $state->owned = false;

        $exception = $this->captureRemovalFailure($this->createRemover($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('valid matching Block Builder configuration', $exception->getMessage());
        self::assertSame(
            ['permission', 'locate_directory', 'check_ownership', 'log_failure'],
            $state->events,
        );
        $this->assertSingleFailureLog($state, $exception, ['path' => self::BLOCK_PATH]);
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    /**
     * Confirms that an installed-block lookup exception is wrapped and logged while still
     * releasing the acquired handle lock.
     */
    public function testInstalledLookupExceptionIsWrappedLoggedAndReleasesTheHandleLock(): void
    {
        $lookupFailure = new RuntimeException('installed lookup failed');
        $state = new BlockDirectoryRemoverTestState();
        $state->installedLookupFailure = $lookupFailure;

        $exception = $this->captureRemovalFailure($this->createRemover($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('Unable to determine whether block type', $exception->getMessage());
        self::assertSame($lookupFailure, $exception->getPrevious());
        self::assertSame(
            ['permission', 'locate_directory', 'check_ownership', 'check_installed', 'log_failure'],
            $state->events,
        );
        $this->assertSingleFailureLog($state, $exception, ['path' => self::BLOCK_PATH]);
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    /**
     * Confirms that the directory of an installed block type is never removed and its lock is
     * released.
     */
    public function testInstalledBlockDirectoryIsNotRemovedAndReleasesTheHandleLock(): void
    {
        $state = new BlockDirectoryRemoverTestState();
        $state->installed = true;

        $exception = $this->captureRemovalFailure($this->createRemover($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('installed block type', $exception->getMessage());
        self::assertSame([], $state->removedPaths);
        $this->assertSingleFailureLog($state, $exception, ['path' => self::BLOCK_PATH]);
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    /**
     * Confirms that a false recursive-delete result becomes a logged failure and does not leak the
     * handle lock.
     */
    public function testFalseFileServiceResultIsWrappedLoggedAndReleasesTheHandleLock(): void
    {
        $state = new BlockDirectoryRemoverTestState();
        $state->removeResult = false;

        $exception = $this->captureRemovalFailure($this->createRemover($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('Unable to remove block type directory', $exception->getMessage());
        self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        self::assertStringContainsString('File service returned false', $exception->getPrevious()->getMessage());
        self::assertSame([[self::BLOCK_PATH, true]], $state->removedPaths);
        $this->assertSingleFailureLog($state, $exception, ['path' => self::BLOCK_PATH]);
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    /**
     * Confirms that a filesystem exception is retained as the original cause of the removal
     * failure and that the handle lock is released.
     */
    public function testFileServiceExceptionIsWrappedWithOriginalCauseAndReleasesTheHandleLock(): void
    {
        $removeFailure = new RuntimeException('filesystem mutation failed');
        $state = new BlockDirectoryRemoverTestState();
        $state->removeFailure = $removeFailure;

        $exception = $this->captureRemovalFailure($this->createRemover($state), self::BLOCK_HANDLE);

        self::assertSame($removeFailure, $exception->getPrevious());
        self::assertSame([[self::BLOCK_PATH, true]], $state->removedPaths);
        $this->assertSingleFailureLog($state, $exception, ['path' => self::BLOCK_PATH]);
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    /**
     * Confirms that a valid uninstalled owned block is recursively removed, logged as successful,
     * and followed by lock release.
     */
    public function testSuccessfulRemovalUsesRecursiveDirectoryDeletionLogsSuccessAndReleasesTheLock(): void
    {
        $state = new BlockDirectoryRemoverTestState();
        $remover = $this->createRemover($state);

        $remover->remove(self::BLOCK_HANDLE);

        self::assertSame([[self::BLOCK_PATH, true]], $state->removedPaths);
        self::assertSame([], $state->failureLogs);
        self::assertSame([
            [
                'operation' => 'remove_directory',
                'target' => self::BLOCK_HANDLE,
                'context' => ['path' => self::BLOCK_PATH],
            ],
        ], $state->successLogs);
        self::assertSame(
            [
                'permission',
                'locate_directory',
                'check_ownership',
                'check_installed',
                'remove_directory',
                'log_success',
            ],
            $state->events,
        );
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    private function createRemover(
        BlockDirectoryRemoverTestState $state,
        ?string $lockDirectory = null,
    ): BlockDirectoryRemover {
        return new BlockDirectoryRemover(
            fileService: new BlockDirectoryRemoverTestFileService($state),
            permissionChecker: new BlockDirectoryRemoverTestPermissionChecker($state),
            directoryLocator: new BlockDirectoryRemoverTestDirectoryLocator($state),
            blockOwnershipChecker: new BlockDirectoryRemoverTestOwnershipChecker($state),
            blockTypeLocator: new BlockDirectoryRemoverTestBlockTypeLocator($state),
            lifecycleLogger: new BlockDirectoryRemoverTestLifecycleLogger($state),
            blockHandleLockManager: new BlockHandleLockManager($lockDirectory ?? $this->lockDirectory),
        );
    }

    private function captureRemovalFailure(
        BlockDirectoryRemover $remover,
        string $handle,
    ): BlockDirectoryRemovalException {
        try {
            $remover->remove($handle);
            self::fail('The removal operation was expected to fail.');
        } catch (BlockDirectoryRemovalException $exception) {
            return $exception;
        }
    }

    private function assertSingleFailureLog(
        BlockDirectoryRemoverTestState $state,
        BlockDirectoryRemovalException $exception,
        array $context = [],
        string $target = self::BLOCK_HANDLE,
    ): void {
        self::assertCount(1, $state->failureLogs);
        self::assertSame([
            'operation' => 'remove_directory',
            'target' => $target,
            'exception' => $exception,
            'context' => $context,
        ], $state->failureLogs[0]);
        self::assertSame([], $state->successLogs);
    }

    private function assertHandleLockIsAvailable(string $handle): void
    {
        $lockPath = $this->getLockPath($handle);
        self::assertFileExists($lockPath);

        $stream = fopen($lockPath, 'c+b');
        self::assertIsResource($stream);
        $lockAcquired = flock($stream, LOCK_EX | LOCK_NB);
        if ($lockAcquired) {
            flock($stream, LOCK_UN);
        }
        fclose($stream);

        self::assertTrue($lockAcquired, 'The per-handle operation lock must be released.');
    }

    private function getLockPath(string $handle): string
    {
        return $this->lockDirectory
            . DIRECTORY_SEPARATOR
            . '.block-builder-' . hash('sha256', $handle) . '.lock';
    }
}

final class BlockDirectoryRemoverTestState
{
    /** @var string[] */
    public array $events = [];
    public ?string $permissionError = null;
    public ?Throwable $permissionFailure = null;
    public ?string $blockPath = '/application/blocks/example_block';
    public bool $owned = true;
    public bool $installed = false;
    public ?Throwable $installedLookupFailure = null;
    public bool $removeResult = true;
    public ?Throwable $removeFailure = null;
    /** @var array<array{0: string, 1: bool}> */
    public array $removedPaths = [];
    /** @var array<array{operation: string, target: string|int, context: array}> */
    public array $successLogs = [];
    /** @var array<array{operation: string, target: string|int, exception: Throwable, context: array}> */
    public array $failureLogs = [];
}

final class BlockDirectoryRemoverTestFileService extends FileService
{
    public function __construct(private readonly BlockDirectoryRemoverTestState $state)
    {
    }

    public function removeAll($source, $includeSource = false)
    {
        $this->state->events[] = 'remove_directory';
        $this->state->removedPaths[] = [(string) $source, (bool) $includeSource];
        if ($this->state->removeFailure !== null) {
            throw $this->state->removeFailure;
        }

        return $this->state->removeResult;
    }
}

final readonly class BlockDirectoryRemoverTestPermissionChecker extends BlockTypePermissionChecker
{
    public function __construct(private BlockDirectoryRemoverTestState $state)
    {
    }

    public function getRemovalErrorMessage(): ?string
    {
        $this->state->events[] = 'permission';
        if ($this->state->permissionFailure !== null) {
            throw $this->state->permissionFailure;
        }

        return $this->state->permissionError;
    }
}

final readonly class BlockDirectoryRemoverTestDirectoryLocator extends BlockDirectoryLocator
{
    public function __construct(private BlockDirectoryRemoverTestState $state)
    {
    }

    public function getSafeApplicationBlockDirectory(string $handle): ?string
    {
        $this->state->events[] = 'locate_directory';

        return $this->state->blockPath;
    }
}

final readonly class BlockDirectoryRemoverTestOwnershipChecker extends BlockOwnershipChecker
{
    public function __construct(private BlockDirectoryRemoverTestState $state)
    {
    }

    public function isOwnedApplicationBlock(string $blockHandle): bool
    {
        $this->state->events[] = 'check_ownership';

        return $this->state->owned;
    }
}

final readonly class BlockDirectoryRemoverTestBlockTypeLocator extends BlockTypeLocator
{
    public function __construct(private BlockDirectoryRemoverTestState $state)
    {
    }

    public function isInstalled(null|\Concrete\Core\Entity\Block\BlockType\BlockType|int|string $blockType): bool
    {
        $this->state->events[] = 'check_installed';
        if ($this->state->installedLookupFailure !== null) {
            throw $this->state->installedLookupFailure;
        }

        return $this->state->installed;
    }
}

final readonly class BlockDirectoryRemoverTestLifecycleLogger extends BlockLifecycleLogger
{
    public function __construct(private BlockDirectoryRemoverTestState $state)
    {
    }

    public function logSuccess(string $operation, string|int $target, array $context = []): void
    {
        $this->state->events[] = 'log_success';
        $this->state->successLogs[] = compact('operation', 'target', 'context');
    }

    public function logFailure(
        string $operation,
        string|int $target,
        Throwable $exception,
        array $context = [],
    ): void {
        $this->state->events[] = 'log_failure';
        $this->state->failureLogs[] = compact('operation', 'target', 'exception', 'context');
    }
}
