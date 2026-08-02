<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Integration\Support;

use BlockBuilder\Block\Service\BlockDirectoryRemover;
use BlockBuilder\Block\Service\BlockTypeUninstaller;
use Concrete\Core\Application\Application;
use Concrete\Core\Block\Block;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Concrete\Core\User\User;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

abstract class ConcreteIntegrationTestCase extends TestCase
{
    /** @var array<string, true> */
    private array $authorizedCleanupHandles = [];

    protected Application $application;
    protected Connection $connection;
    protected DisposableEnvironmentGuard $environmentGuard;

    protected function setUp(): void
    {
        parent::setUp();

        $application = $GLOBALS['blockBuilderIntegrationApplication'] ?? null;
        $environmentGuard = $GLOBALS['blockBuilderIntegrationEnvironmentGuard'] ?? null;
        if (!$application instanceof Application || !$environmentGuard instanceof DisposableEnvironmentGuard) {
            throw new RuntimeException('The guarded Concrete integration bootstrap did not complete.');
        }

        $this->application = $application;
        $this->environmentGuard = $environmentGuard;
        $this->connection = $application->make(Connection::class);
        $environmentGuard->assertActiveDatabase($this->connection);

        $superUser = User::loginByUserID(USER_SUPER_ID);
        if (!$superUser instanceof User || !$superUser->isSuperUser()) {
            throw new RuntimeException('The integration suite could not establish a disposable super-user session.');
        }
        $application->instance(User::class, $superUser);
    }

    protected function cleanGeneratedBlockFixture(string $blockHandle): void
    {
        $this->environmentGuard->assertActiveDatabase($this->connection);
        if (preg_match('/\Ablock_builder_integration_[a-z0-9_]+\z/D', $blockHandle) !== 1) {
            throw new RuntimeException('Refusing to clean a block outside the integration fixture namespace.');
        }
        if (!isset($this->authorizedCleanupHandles[$blockHandle])) {
            return;
        }

        $blockType = BlockType::getByHandle($blockHandle);
        if ($blockType instanceof BlockTypeEntity) {
            $blockIdentifiers = $this->connection->fetchFirstColumn(
                'SELECT bID FROM Blocks WHERE btID = ?',
                [$blockType->getBlockTypeID()],
            );
            foreach ($blockIdentifiers as $blockIdentifier) {
                $block = Block::getByID((int) $blockIdentifier);
                if ($block instanceof Block) {
                    $block->deleteBlock(true);
                }
            }

            $this->application->make(BlockTypeUninstaller::class)->uninstall($blockType->getBlockTypeID());
        }

        $blockPath = $this->environmentGuard->blocksRoot . DIRECTORY_SEPARATOR . $blockHandle;
        if (!file_exists($blockPath) && !is_link($blockPath)) {
            return;
        }

        if (is_dir($blockPath) && !is_link($blockPath)) {
            try {
                $this->application->make(BlockDirectoryRemover::class)->remove($blockHandle);

                return;
            } catch (Throwable) {
                // A failed generation may not have produced a valid ownership config yet.
                // The exact fixture path is removed below after repeating all guards.
            }
        }

        $this->removeExactGeneratedBlockFixturePath($blockHandle, $blockPath);
    }

    protected function authorizeGeneratedBlockFixtureCleanup(string $blockHandle): void
    {
        $this->environmentGuard->assertActiveDatabase($this->connection);
        if (preg_match('/\Ablock_builder_integration_[a-z0-9_]+\z/D', $blockHandle) !== 1) {
            throw new RuntimeException('Refusing to authorize cleanup outside the integration fixture namespace.');
        }
        if (BlockType::getByHandle($blockHandle) instanceof BlockTypeEntity) {
            throw new RuntimeException(sprintf(
                'Refusing to claim cleanup ownership of pre-existing block type "%s".',
                $blockHandle,
            ));
        }

        $blockPath = $this->environmentGuard->blocksRoot . DIRECTORY_SEPARATOR . $blockHandle;
        if (file_exists($blockPath) || is_link($blockPath)) {
            throw new RuntimeException(sprintf(
                'Refusing to claim cleanup ownership of pre-existing block path "%s".',
                $blockPath,
            ));
        }

        $this->authorizedCleanupHandles[$blockHandle] = true;
    }

    private function removeExactGeneratedBlockFixturePath(string $blockHandle, string $blockPath): void
    {
        $this->environmentGuard->assertActiveDatabase($this->connection);
        $expectedPath = $this->environmentGuard->blocksRoot . DIRECTORY_SEPARATOR . $blockHandle;
        if (!hash_equals($expectedPath, $blockPath) || basename($blockPath) !== $blockHandle) {
            throw new RuntimeException('Refusing to clean an unexpected integration fixture path.');
        }
        if (is_link($blockPath)) {
            throw new RuntimeException('Refusing to follow or remove a linked integration fixture path.');
        }
        if (!is_dir($blockPath)) {
            throw new RuntimeException('The integration fixture path exists but is not a directory.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($blockPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            if ($item->isLink()) {
                if (!unlink($itemPath)) {
                    throw new RuntimeException(sprintf('Unable to remove fixture link "%s".', $itemPath));
                }
            } elseif ($item->isDir()) {
                if (!rmdir($itemPath)) {
                    throw new RuntimeException(sprintf('Unable to remove fixture directory "%s".', $itemPath));
                }
            } elseif (!unlink($itemPath)) {
                throw new RuntimeException(sprintf('Unable to remove fixture file "%s".', $itemPath));
            }
        }
        if (!rmdir($blockPath)) {
            throw new RuntimeException(sprintf('Unable to remove fixture directory "%s".', $blockPath));
        }
    }
}
