<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Service;

use BlockBuilder\Block\Exception\BlockTypeInstallException;
use BlockBuilder\Block\Service\BlockDirectoryLocator;
use BlockBuilder\Block\Service\BlockLifecycleLogger;
use BlockBuilder\Block\Service\BlockOwnershipChecker;
use BlockBuilder\Block\Service\BlockTypeInstaller;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\Block\Service\BlockTypePermissionChecker;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class BlockTypeInstallerTest extends TestCase
{
    private const string BLOCK_HANDLE = 'example_block';
    private const string BLOCK_PATH = '/application/blocks/example_block';

    public function testPermissionCheckExceptionIsWrappedAndLoggedBeforeFilesystemLookup(): void
    {
        $permissionFailure = new RuntimeException('permission infrastructure failed');
        $state = new BlockTypeInstallerTestState();
        $state->permissionFailure = $permissionFailure;

        $exception = $this->captureInstallationFailure($this->createInstaller($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('permission could not be verified', $exception->getMessage());
        self::assertSame($permissionFailure, $exception->getPrevious());
        self::assertSame(['permission', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
    }

    public function testDeniedPermissionIsReturnedAsALoggedInstallationFailure(): void
    {
        $state = new BlockTypeInstallerTestState();
        $state->permissionError = 'Installation denied by test policy.';

        $exception = $this->captureInstallationFailure($this->createInstaller($state), self::BLOCK_HANDLE);

        self::assertSame('Installation denied by test policy.', $exception->getMessage());
        self::assertNull($exception->getPrevious());
        self::assertSame(['permission', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
    }

    public function testInvalidHandleIsRejectedBeforeFilesystemLookup(): void
    {
        $state = new BlockTypeInstallerTestState();

        $exception = $this->captureInstallationFailure($this->createInstaller($state), 'Invalid Handle');

        self::assertStringContainsString('Invalid block type handle', $exception->getMessage());
        self::assertSame(['permission', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception, [], 'Invalid Handle');
    }

    public function testMissingOrUnsafeDirectoryIsRejectedAndLogged(): void
    {
        $state = new BlockTypeInstallerTestState();
        $state->blockPath = null;

        $exception = $this->captureInstallationFailure($this->createInstaller($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('missing, linked, or outside', $exception->getMessage());
        self::assertSame(['permission', 'locate_directory', 'log_failure'], $state->events);
        $this->assertSingleFailureLog($state, $exception);
    }

    public function testUnownedDirectoryIsRejectedAndLoggedWithItsSafePath(): void
    {
        $state = new BlockTypeInstallerTestState();
        $state->owned = false;

        $exception = $this->captureInstallationFailure($this->createInstaller($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('valid matching Block Builder configuration', $exception->getMessage());
        self::assertSame(
            ['permission', 'locate_directory', 'check_ownership', 'log_failure'],
            $state->events,
        );
        $this->assertSingleFailureLog($state, $exception, ['path' => self::BLOCK_PATH]);
    }

    public function testInstalledLookupExceptionIsWrappedWithOriginalCauseAndLogged(): void
    {
        $lookupFailure = new RuntimeException('installed lookup failed');
        $state = new BlockTypeInstallerTestState();
        $state->installedLookupFailure = $lookupFailure;

        $exception = $this->captureInstallationFailure($this->createInstaller($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('Unable to determine whether block type', $exception->getMessage());
        self::assertSame($lookupFailure, $exception->getPrevious());
        self::assertSame(
            ['permission', 'locate_directory', 'check_ownership', 'check_installed', 'log_failure'],
            $state->events,
        );
        $this->assertSingleFailureLog($state, $exception, ['path' => self::BLOCK_PATH]);
    }

    public function testAlreadyInstalledBlockIsRejectedAndLoggedWithoutCallingStaticInstaller(): void
    {
        $state = new BlockTypeInstallerTestState();
        $state->installed = true;

        $exception = $this->captureInstallationFailure($this->createInstaller($state), self::BLOCK_HANDLE);

        self::assertStringContainsString('already installed', $exception->getMessage());
        self::assertSame(
            ['permission', 'locate_directory', 'check_ownership', 'check_installed', 'log_failure'],
            $state->events,
        );
        $this->assertSingleFailureLog($state, $exception, ['path' => self::BLOCK_PATH]);
    }

    private function createInstaller(BlockTypeInstallerTestState $state): BlockTypeInstaller
    {
        return new BlockTypeInstaller(
            permissionChecker: new BlockTypeInstallerTestPermissionChecker($state),
            directoryLocator: new BlockTypeInstallerTestDirectoryLocator($state),
            blockOwnershipChecker: new BlockTypeInstallerTestOwnershipChecker($state),
            blockTypeLocator: new BlockTypeInstallerTestLocator($state),
            lifecycleLogger: new BlockTypeInstallerTestLifecycleLogger($state),
        );
    }

    private function captureInstallationFailure(
        BlockTypeInstaller $installer,
        string $handle,
    ): BlockTypeInstallException {
        try {
            $installer->install($handle);
            self::fail('The installation operation was expected to fail before static CMS installation.');
        } catch (BlockTypeInstallException $exception) {
            return $exception;
        }
    }

    private function assertSingleFailureLog(
        BlockTypeInstallerTestState $state,
        BlockTypeInstallException $exception,
        array $context = [],
        string $target = self::BLOCK_HANDLE,
    ): void {
        self::assertCount(1, $state->failureLogs);
        self::assertSame([
            'operation' => 'install',
            'target' => $target,
            'exception' => $exception,
            'context' => $context,
        ], $state->failureLogs[0]);
        self::assertSame([], $state->successLogs);
    }
}

final class BlockTypeInstallerTestState
{
    /** @var string[] */
    public array $events = [];
    public ?string $permissionError = null;
    public ?Throwable $permissionFailure = null;
    public ?string $blockPath = '/application/blocks/example_block';
    public bool $owned = true;
    public bool $installed = false;
    public ?Throwable $installedLookupFailure = null;
    /** @var array<array{operation: string, target: string|int, context: array}> */
    public array $successLogs = [];
    /** @var array<array{operation: string, target: string|int, exception: Throwable, context: array}> */
    public array $failureLogs = [];
}

final readonly class BlockTypeInstallerTestPermissionChecker extends BlockTypePermissionChecker
{
    public function __construct(private BlockTypeInstallerTestState $state)
    {
    }

    public function getInstallationErrorMessage(): ?string
    {
        $this->state->events[] = 'permission';
        if ($this->state->permissionFailure !== null) {
            throw $this->state->permissionFailure;
        }

        return $this->state->permissionError;
    }
}

final readonly class BlockTypeInstallerTestDirectoryLocator extends BlockDirectoryLocator
{
    public function __construct(private BlockTypeInstallerTestState $state)
    {
    }

    public function getSafeApplicationBlockDirectory(string $handle): ?string
    {
        $this->state->events[] = 'locate_directory';

        return $this->state->blockPath;
    }
}

final readonly class BlockTypeInstallerTestOwnershipChecker extends BlockOwnershipChecker
{
    public function __construct(private BlockTypeInstallerTestState $state)
    {
    }

    public function isOwnedApplicationBlock(string $blockHandle): bool
    {
        $this->state->events[] = 'check_ownership';

        return $this->state->owned;
    }
}

final readonly class BlockTypeInstallerTestLocator extends BlockTypeLocator
{
    public function __construct(private BlockTypeInstallerTestState $state)
    {
    }

    public function isInstalled(null|BlockTypeEntity|int|string $blockType): bool
    {
        $this->state->events[] = 'check_installed';
        if ($this->state->installedLookupFailure !== null) {
            throw $this->state->installedLookupFailure;
        }

        return $this->state->installed;
    }
}

final readonly class BlockTypeInstallerTestLifecycleLogger extends BlockLifecycleLogger
{
    public function __construct(private BlockTypeInstallerTestState $state)
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
