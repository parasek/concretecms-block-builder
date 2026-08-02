<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Service;

use BlockBuilder\Block\Exception\BlockTypeUninstallException;
use BlockBuilder\Block\Service\BlockHandleLockManager;
use BlockBuilder\Block\Service\BlockLifecycleLogger;
use BlockBuilder\Block\Service\BlockOwnershipChecker;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\Block\Service\BlockTypePermissionChecker;
use BlockBuilder\Block\Service\BlockTypeUninstaller;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

final class BlockTypeUninstallerTest extends TestCase
{
    private const int BLOCK_TYPE_ID = 42;
    private const string BLOCK_HANDLE = 'example_block';
    private const string BLOCK_NAME = 'Example Block';

    private string $lockDirectory;

    protected function setUp(): void
    {
        $this->lockDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-uninstaller-test-' . bin2hex(random_bytes(8));
        (new Filesystem())->mkdir($this->lockDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->lockDirectory);
    }

    public function testPermissionCheckExceptionIsWrappedAndLoggedBeforeLookup(): void
    {
        $permissionFailure = new RuntimeException('permission infrastructure failed');
        $state = new BlockTypeUninstallerTestState();
        $state->permissionFailure = $permissionFailure;

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), self::BLOCK_TYPE_ID);

        self::assertStringContainsString('permission could not be verified', $exception->getMessage());
        self::assertSame($permissionFailure, $exception->getPrevious());
        self::assertSame(['permission', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
    }

    public function testDeniedPermissionIsReturnedAsALoggedUninstallFailure(): void
    {
        $state = new BlockTypeUninstallerTestState();
        $state->permissionError = 'Uninstallation denied by test policy.';

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), self::BLOCK_TYPE_ID);

        self::assertSame('Uninstallation denied by test policy.', $exception->getMessage());
        self::assertNull($exception->getPrevious());
        self::assertSame(['permission', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
    }

    public function testLocatorExceptionIsWrappedWithOriginalCauseAndLogged(): void
    {
        $locatorFailure = new RuntimeException('block type lookup failed');
        $state = new BlockTypeUninstallerTestState();
        $state->locatorFailure = $locatorFailure;

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), self::BLOCK_TYPE_ID);

        self::assertStringContainsString('Unable to locate block type', $exception->getMessage());
        self::assertSame($locatorFailure, $exception->getPrevious());
        self::assertSame(['permission', 'locate_block_type', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
    }

    public function testMissingBlockTypeIsReportedAndLogged(): void
    {
        $state = new BlockTypeUninstallerTestState();

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), 'missing_block');

        self::assertStringContainsString('Unable to find block type "missing_block"', $exception->getMessage());
        self::assertSame(['permission', 'locate_block_type', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception, [], 'missing_block');
    }

    public function testEntityInspectionExceptionIsWrappedWithOriginalCauseAndLogged(): void
    {
        $inspectionFailure = new RuntimeException('entity inspection failed');
        $state = new BlockTypeUninstallerTestState();
        $blockType = $this->createBlockTypeEntity(inspectionFailure: $inspectionFailure);
        $blockType->expects(self::never())->method('delete');
        $state->blockType = $blockType;

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), self::BLOCK_TYPE_ID);

        self::assertStringContainsString('Unable to inspect block type', $exception->getMessage());
        self::assertSame($inspectionFailure, $exception->getPrevious());
        $this->assertSingleFailureLog($state, $exception);
    }

    public function testInternalBlockTypeIsRejectedBeforeLockingOrDeletion(): void
    {
        $state = new BlockTypeUninstallerTestState();
        $blockType = $this->createBlockTypeEntity(isInternal: true);
        $blockType->expects(self::never())->method('delete');
        $state->blockType = $blockType;

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), self::BLOCK_TYPE_ID);

        self::assertStringContainsString('internal block type', $exception->getMessage());
        self::assertFileDoesNotExist($this->getLockPath(self::BLOCK_HANDLE));
        $this->assertSingleFailureLog($state, $exception);
    }

    public function testMissingEntityHandleIsLoggedWithIdentifierAndDoesNotAcquireALock(): void
    {
        $state = new BlockTypeUninstallerTestState();
        $blockType = $this->createBlockTypeEntity(handle: null);
        $blockType->expects(self::never())->method('delete');
        $state->blockType = $blockType;

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), self::BLOCK_TYPE_ID);

        self::assertStringContainsString('handle could not be determined', $exception->getMessage());
        self::assertSame([], glob($this->lockDirectory . DIRECTORY_SEPARATOR . '.block-builder-*.lock'));
        $this->assertSingleFailureLog(
            $state,
            $exception,
            ['blockTypeId' => self::BLOCK_TYPE_ID],
        );
    }

    public function testLockAcquisitionFailureIsWrappedLoggedAndDoesNotDelete(): void
    {
        $state = new BlockTypeUninstallerTestState();
        $blockType = $this->createBlockTypeEntity();
        $blockType->expects(self::never())->method('delete');
        $state->blockType = $blockType;
        $missingLockDirectory = $this->lockDirectory . DIRECTORY_SEPARATOR . 'missing';

        $exception = $this->captureUninstallFailure(
            $this->createUninstaller($state, $missingLockDirectory),
            self::BLOCK_TYPE_ID,
        );

        self::assertStringContainsString('Unable to acquire the operation lock', $exception->getMessage());
        self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        $this->assertSingleFailureLog(
            $state,
            $exception,
            ['blockTypeId' => self::BLOCK_TYPE_ID],
        );
    }

    public function testUnownedBlockTypeIsLoggedDoesNotDeleteAndReleasesTheLock(): void
    {
        $state = new BlockTypeUninstallerTestState();
        $state->owned = false;
        $blockType = $this->createBlockTypeEntity();
        $blockType->expects(self::never())->method('delete');
        $state->blockType = $blockType;

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), self::BLOCK_TYPE_ID);

        self::assertStringContainsString('valid matching Block Builder configuration', $exception->getMessage());
        self::assertSame(['permission', 'locate_block_type', 'check_ownership', 'log_failure'], $state->events);
        $this->assertSingleFailureLog(
            $state,
            $exception,
            ['blockTypeId' => self::BLOCK_TYPE_ID],
        );
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    public function testDeleteExceptionIsWrappedLoggedAndReleasesTheLock(): void
    {
        $deleteFailure = new RuntimeException('entity deletion failed');
        $state = new BlockTypeUninstallerTestState();
        $blockType = $this->createBlockTypeEntity();
        $blockType->expects(self::once())->method('delete')->willThrowException($deleteFailure);
        $state->blockType = $blockType;

        $exception = $this->captureUninstallFailure($this->createUninstaller($state), self::BLOCK_TYPE_ID);

        self::assertStringContainsString('could not be uninstalled', $exception->getMessage());
        self::assertSame($deleteFailure, $exception->getPrevious());
        $this->assertSingleFailureLog(
            $state,
            $exception,
            ['blockTypeId' => self::BLOCK_TYPE_ID],
        );
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    public function testSuccessfulUninstallReturnsNameLogsSuccessAndReleasesTheLock(): void
    {
        $state = new BlockTypeUninstallerTestState();
        $blockType = $this->createBlockTypeEntity();
        $blockType->expects(self::once())->method('delete');
        $state->blockType = $blockType;
        $uninstaller = $this->createUninstaller($state);

        $blockTypeName = $uninstaller->uninstall(self::BLOCK_TYPE_ID);

        self::assertSame(self::BLOCK_NAME, $blockTypeName);
        self::assertSame([], $state->failureLogs);
        self::assertSame([
            [
                'operation' => 'uninstall',
                'target' => self::BLOCK_TYPE_ID,
                'context' => ['blockTypeId' => self::BLOCK_TYPE_ID],
            ],
        ], $state->successLogs);
        self::assertSame(
            ['permission', 'locate_block_type', 'check_ownership', 'log_success'],
            $state->events,
        );
        $this->assertHandleLockIsAvailable(self::BLOCK_HANDLE);
    }

    private function createUninstaller(
        BlockTypeUninstallerTestState $state,
        ?string $lockDirectory = null,
    ): BlockTypeUninstaller {
        return new BlockTypeUninstaller(
            permissionChecker: new BlockTypeUninstallerTestPermissionChecker($state),
            blockTypeLocator: new BlockTypeUninstallerTestLocator($state),
            blockOwnershipChecker: new BlockTypeUninstallerTestOwnershipChecker($state),
            lifecycleLogger: new BlockTypeUninstallerTestLifecycleLogger($state),
            blockHandleLockManager: new BlockHandleLockManager($lockDirectory ?? $this->lockDirectory),
        );
    }

    /** @return BlockTypeEntity&MockObject */
    private function createBlockTypeEntity(
        bool $isInternal = false,
        mixed $handle = self::BLOCK_HANDLE,
        ?Throwable $inspectionFailure = null,
    ): BlockTypeEntity {
        $blockType = $this->getMockBuilder(BlockTypeEntity::class)
            ->onlyMethods([
                'isBlockTypeInternal',
                'getBlockTypeName',
                'getBlockTypeID',
                'getBlockTypeHandle',
                'delete',
            ])
            ->getMock();
        if ($inspectionFailure !== null) {
            $blockType->method('isBlockTypeInternal')->willThrowException($inspectionFailure);
        } else {
            $blockType->method('isBlockTypeInternal')->willReturn($isInternal);
        }
        $blockType->method('getBlockTypeName')->willReturn(self::BLOCK_NAME);
        $blockType->method('getBlockTypeID')->willReturn(self::BLOCK_TYPE_ID);
        $blockType->method('getBlockTypeHandle')->willReturn($handle);

        return $blockType;
    }

    private function captureUninstallFailure(
        BlockTypeUninstaller $uninstaller,
        int|string $identifier,
    ): BlockTypeUninstallException {
        try {
            $uninstaller->uninstall($identifier);
            self::fail('The uninstallation operation was expected to fail.');
        } catch (BlockTypeUninstallException $exception) {
            return $exception;
        }
    }

    private function assertSingleFailureLog(
        BlockTypeUninstallerTestState $state,
        BlockTypeUninstallException $exception,
        array $context = [],
        int|string $target = self::BLOCK_TYPE_ID,
    ): void {
        self::assertCount(1, $state->failureLogs);
        self::assertSame([
            'operation' => 'uninstall',
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

final class BlockTypeUninstallerTestState
{
    /** @var string[] */
    public array $events = [];
    public ?string $permissionError = null;
    public ?Throwable $permissionFailure = null;
    public ?BlockTypeEntity $blockType = null;
    public ?Throwable $locatorFailure = null;
    public bool $owned = true;
    /** @var array<array{operation: string, target: string|int, context: array}> */
    public array $successLogs = [];
    /** @var array<array{operation: string, target: string|int, exception: Throwable, context: array}> */
    public array $failureLogs = [];
}

final readonly class BlockTypeUninstallerTestPermissionChecker extends BlockTypePermissionChecker
{
    public function __construct(private BlockTypeUninstallerTestState $state)
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

final readonly class BlockTypeUninstallerTestLocator extends BlockTypeLocator
{
    public function __construct(private BlockTypeUninstallerTestState $state)
    {
    }

    public function findByIdentifier(null|int|string $blockTypeIdentifier): ?BlockTypeEntity
    {
        $this->state->events[] = 'locate_block_type';
        if ($this->state->locatorFailure !== null) {
            throw $this->state->locatorFailure;
        }

        return $this->state->blockType;
    }
}

final readonly class BlockTypeUninstallerTestOwnershipChecker extends BlockOwnershipChecker
{
    public function __construct(private BlockTypeUninstallerTestState $state)
    {
    }

    public function isOwnedApplicationBlock(string $blockHandle): bool
    {
        $this->state->events[] = 'check_ownership';

        return $this->state->owned;
    }
}

final readonly class BlockTypeUninstallerTestLifecycleLogger extends BlockLifecycleLogger
{
    public function __construct(private BlockTypeUninstallerTestState $state)
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
