<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Filesystem;

use BlockBuilder\Block\Service\BlockHandleLockManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Test type: Block handle locking filesystem component test.
 *
 * Verifies that generation locks are exclusive, can be acquired after release, and require an
 * existing lock directory instead of silently creating one in an unexpected location.
 */
final class BlockHandleLockManagerTest extends TestCase
{
    private string $lockDirectory;

    protected function setUp(): void
    {
        $this->lockDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-lock-test-' . bin2hex(random_bytes(8));
        (new Filesystem())->mkdir($this->lockDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->lockDirectory);
    }

    /**
     * Verifies that only one process can hold a block-handle lock until the owner releases it.
     */
    public function testLockIsExclusiveUntilReleased(): void
    {
        $manager = new BlockHandleLockManager($this->lockDirectory);
        $lock = $manager->acquire('example_block');
        $lockPaths = glob($this->lockDirectory . DIRECTORY_SEPARATOR . '.block-builder-*.lock');

        self::assertIsArray($lockPaths);
        self::assertCount(1, $lockPaths);

        $competingStream = fopen($lockPaths[0], 'c+b');
        self::assertIsResource($competingStream);
        self::assertFalse(flock($competingStream, LOCK_EX | LOCK_NB));

        $lock->release();

        self::assertTrue(flock($competingStream, LOCK_EX | LOCK_NB));
        flock($competingStream, LOCK_UN);
        fclose($competingStream);
    }

    /**
     * Verifies that lock acquisition fails clearly when the configured lock directory does not exist.
     */
    public function testMissingLockDirectoryIsRejected(): void
    {
        $manager = new BlockHandleLockManager($this->lockDirectory . DIRECTORY_SEPARATOR . 'missing');

        $this->expectException(RuntimeException::class);
        $manager->acquire('example_block');
    }
}
