<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Service\BlockTypeInstaller;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\BlockGenerator\Exception\BlockGenerationInstallationException;
use BlockBuilder\BlockGenerator\Exception\BlockGenerationRefreshException;
use BlockBuilder\BlockGenerator\Lifecycle\BlockTypeLifecycleService;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;

/**
 * Test type: Generated block lifecycle service unit test.
 *
 * Verifies when generated block types are left alone, installed, or refreshed, including entity
 * lookup behavior and the domain exceptions produced when each lifecycle dependency fails.
 */
final class BlockTypeLifecycleServiceTest extends BlockBuilderTestCase
{
    /**
     * Verifies that generating files alone does not install or refresh a Concrete block type.
     */
    public function testCreatedStatePerformsNoConcreteLifecycleOperation(): void
    {
        $log = new BlockTypeLifecycleTestLog();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('find');
        $service = $this->createService($log, $entityManager);

        $state = $service->installOrRefresh(
            $this->createAllFieldTypesConfig(),
            $this->createLifecycleManifest(false, false),
        );

        self::assertSame(PostGenerationBlockStateEnum::Created, $state);
        self::assertSame([], $log->events);
    }

    /**
     * Verifies that an install request passes the generated block handle to the block-type installer.
     */
    public function testInstallBranchPassesHandleToInstaller(): void
    {
        $log = new BlockTypeLifecycleTestLog();
        $log->installerResult = $this->createStub(BlockTypeEntity::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('find');
        $service = $this->createService($log, $entityManager);

        $state = $service->installOrRefresh(
            $this->createAllFieldTypesConfig(),
            $this->createLifecycleManifest(true, false),
        );

        self::assertSame(PostGenerationBlockStateEnum::CreatedAndInstalled, $state);
        self::assertSame(['install:all_field_types_test'], $log->events);
    }

    /**
     * Verifies that an installer failure becomes a generation exception while retaining its original cause.
     */
    public function testInstallFailureIsWrappedWithOriginalCause(): void
    {
        $failure = new RuntimeException('simulated install failure');
        $log = new BlockTypeLifecycleTestLog();
        $log->installerFailure = $failure;
        $service = $this->createService($log, $this->createStub(EntityManagerInterface::class));

        try {
            $service->installOrRefresh(
                $this->createAllFieldTypesConfig(),
                $this->createLifecycleManifest(true, false),
            );
            self::fail('Installer failures must be converted to a generation installation exception.');
        } catch (BlockGenerationInstallationException $exception) {
            self::assertStringContainsString('all_field_types_test', $exception->getMessage());
            self::assertSame($failure, $exception->getPrevious());
        }

        self::assertSame(['install:all_field_types_test'], $log->events);
    }

    /**
     * Verifies that rebuild takes priority over install and refreshes the existing managed block entity.
     */
    public function testRebuildTakesPrecedenceOverInstallAndRefreshesManagedEntity(): void
    {
        $log = new BlockTypeLifecycleTestLog();
        $locatedBlockType = $this->createBlockTypeWithIdentifier(42);
        $managedBlockType = $this->getMockBuilder(BlockTypeEntity::class)
            ->onlyMethods(['refresh'])
            ->getMock();
        $managedBlockType->expects(self::once())->method('refresh');
        $log->locatorResult = $locatedBlockType;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('find')
            ->with(BlockTypeEntity::class, 42)
            ->willReturn($managedBlockType);
        $service = $this->createService($log, $entityManager);

        $state = $service->installOrRefresh(
            $this->createAllFieldTypesConfig(),
            $this->createLifecycleManifest(true, true),
        );

        self::assertSame(PostGenerationBlockStateEnum::Rebuilt, $state);
        self::assertSame(['locate:all_field_types_test'], $log->events);
    }

    /**
     * Verifies that rebuilding fails clearly when Concrete cannot locate the existing block type.
     */
    public function testRebuildFailsWhenBlockTypeCannotBeLocated(): void
    {
        $log = new BlockTypeLifecycleTestLog();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('find');
        $service = $this->createService($log, $entityManager);

        $this->expectException(BlockGenerationRefreshException::class);
        $this->expectExceptionMessage('Unable to find block type "all_field_types_test"');

        $service->installOrRefresh(
            $this->createAllFieldTypesConfig(),
            $this->createLifecycleManifest(false, true),
        );
    }

    /**
     * Verifies that rebuilding fails when the located block type cannot be loaded as a managed entity.
     */
    public function testRebuildFailsWhenManagedEntityCannotBeLoaded(): void
    {
        $log = new BlockTypeLifecycleTestLog();
        $log->locatorResult = $this->createBlockTypeWithIdentifier(42);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('find')
            ->with(BlockTypeEntity::class, 42)
            ->willReturn(null);
        $service = $this->createService($log, $entityManager);

        $this->expectException(BlockGenerationRefreshException::class);
        $this->expectExceptionMessage('Unable to load block type "all_field_types_test"');

        $service->installOrRefresh(
            $this->createAllFieldTypesConfig(),
            $this->createLifecycleManifest(false, true),
        );
    }

    /**
     * Verifies that a block-type lookup failure is wrapped while preserving the original exception.
     */
    public function testLocatorFailureIsWrappedWithOriginalCause(): void
    {
        $failure = new RuntimeException('simulated locator failure');
        $log = new BlockTypeLifecycleTestLog();
        $log->locatorFailure = $failure;
        $service = $this->createService($log, $this->createStub(EntityManagerInterface::class));

        $this->assertRefreshFailureHasPrevious($service, $failure);
    }

    /**
     * Verifies that an entity-manager lookup failure is wrapped while preserving the original exception.
     */
    public function testEntityManagerFailureIsWrappedWithOriginalCause(): void
    {
        $failure = new RuntimeException('simulated entity manager failure');
        $log = new BlockTypeLifecycleTestLog();
        $log->locatorResult = $this->createBlockTypeWithIdentifier(42);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willThrowException($failure);
        $service = $this->createService($log, $entityManager);

        $this->assertRefreshFailureHasPrevious($service, $failure);
    }

    /**
     * Verifies that an entity refresh failure is wrapped while preserving the original exception.
     */
    public function testEntityRefreshFailureIsWrappedWithOriginalCause(): void
    {
        $failure = new RuntimeException('simulated refresh failure');
        $log = new BlockTypeLifecycleTestLog();
        $log->locatorResult = $this->createBlockTypeWithIdentifier(42);
        $managedBlockType = $this->getMockBuilder(BlockTypeEntity::class)
            ->onlyMethods(['refresh'])
            ->getMock();
        $managedBlockType->method('refresh')->willThrowException($failure);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturn($managedBlockType);
        $service = $this->createService($log, $entityManager);

        $this->assertRefreshFailureHasPrevious($service, $failure);
    }

    /**
     * Verifies that a domain-specific refresh exception is rethrown unchanged instead of being wrapped again.
     */
    public function testExistingRefreshExceptionIsPreserved(): void
    {
        $failure = new BlockGenerationRefreshException('specific refresh failure');
        $log = new BlockTypeLifecycleTestLog();
        $log->locatorResult = $this->createBlockTypeWithIdentifier(42);
        $managedBlockType = $this->getMockBuilder(BlockTypeEntity::class)
            ->onlyMethods(['refresh'])
            ->getMock();
        $managedBlockType->method('refresh')->willThrowException($failure);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturn($managedBlockType);
        $service = $this->createService($log, $entityManager);

        try {
            $service->installOrRefresh(
                $this->createAllFieldTypesConfig(),
                $this->createLifecycleManifest(false, true),
            );
            self::fail('An existing refresh exception must be preserved.');
        } catch (BlockGenerationRefreshException $exception) {
            self::assertSame($failure, $exception);
        }
    }

    private function createService(
        BlockTypeLifecycleTestLog $log,
        EntityManagerInterface $entityManager,
    ): BlockTypeLifecycleService {
        return new BlockTypeLifecycleService(
            $entityManager,
            new LifecycleTestBlockTypeInstaller($log),
            new LifecycleTestBlockTypeLocator($log),
        );
    }

    private function createLifecycleManifest(bool $shouldInstallBlock, bool $shouldRebuildBlock): BlockGenerationManifest
    {
        return new BlockGenerationManifest(
            shouldInstallBlock: $shouldInstallBlock,
            shouldRebuildBlock: $shouldRebuildBlock,
            blockHandlePascalCase: 'AllFieldTypesTest',
            blockHandleKebabCase: 'all-field-types-test',
            blockPath: '/not-used/all_field_types_test',
            blockPublicPath: '/application/blocks/all_field_types_test',
            blockIconPath: null,
            blockIconPublicPath: null,
            customBlockIcon: null,
            databaseTableName: 'btAllFieldTypesTest',
            entriesDatabaseTableName: 'btAllFieldTypesTestEntries',
        );
    }

    private function createBlockTypeWithIdentifier(int $identifier): BlockTypeEntity
    {
        $blockType = $this->getMockBuilder(BlockTypeEntity::class)
            ->onlyMethods(['getBlockTypeID'])
            ->getMock();
        $blockType->method('getBlockTypeID')->willReturn($identifier);

        return $blockType;
    }

    private function assertRefreshFailureHasPrevious(
        BlockTypeLifecycleService $service,
        Throwable $failure,
    ): void {
        try {
            $service->installOrRefresh(
                $this->createAllFieldTypesConfig(),
                $this->createLifecycleManifest(false, true),
            );
            self::fail('Refresh failures must be converted to a generation refresh exception.');
        } catch (BlockGenerationRefreshException $exception) {
            self::assertStringContainsString('all_field_types_test', $exception->getMessage());
            self::assertSame($failure, $exception->getPrevious());
        }
    }
}

final class BlockTypeLifecycleTestLog
{
    /** @var string[] */
    public array $events = [];
    public ?BlockTypeEntity $installerResult = null;
    public ?BlockTypeEntity $locatorResult = null;
    public ?Throwable $installerFailure = null;
    public ?Throwable $locatorFailure = null;
}

readonly class LifecycleTestBlockTypeInstaller extends BlockTypeInstaller
{
    public function __construct(private BlockTypeLifecycleTestLog $log)
    {
    }

    public function install(string $handle): BlockTypeEntity
    {
        $this->log->events[] = 'install:' . $handle;
        if ($this->log->installerFailure !== null) {
            throw $this->log->installerFailure;
        }

        return $this->log->installerResult ?? throw new RuntimeException('No installer result was configured.');
    }
}

readonly class LifecycleTestBlockTypeLocator extends BlockTypeLocator
{
    public function __construct(private BlockTypeLifecycleTestLog $log)
    {
    }

    public function findByIdentifier(null|int|string $blockTypeIdentifier): ?BlockTypeEntity
    {
        $this->log->events[] = 'locate:' . (string) $blockTypeIdentifier;
        if ($this->log->locatorFailure !== null) {
            throw $this->log->locatorFailure;
        }

        return $this->log->locatorResult;
    }
}
