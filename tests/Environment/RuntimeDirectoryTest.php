<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Environment;

use BlockBuilder\Environment\RuntimeDirectory;
use PHPUnit\Framework\TestCase;

/**
 * Test type: Block Builder runtime path contract test.
 *
 * Verifies that transient operation artifacts are kept outside the application block directory.
 */
final class RuntimeDirectoryTest extends TestCase
{
    public function testRuntimeArtifactsUseDedicatedApplicationFilesDirectories(): void
    {
        $runtimePath = DIR_FILES_UPLOADED_STANDARD
            . DIRECTORY_SEPARATOR
            . 'block_builder';

        self::assertSame($runtimePath, RuntimeDirectory::getPath());
        self::assertSame(
            $runtimePath . DIRECTORY_SEPARATOR . 'locks',
            RuntimeDirectory::getLocksPath(),
        );
        self::assertSame(
            $runtimePath . DIRECTORY_SEPARATOR . 'backups',
            RuntimeDirectory::getBackupsPath(),
        );
        self::assertStringNotContainsString(
            rtrim(DIR_FILES_BLOCK_TYPES, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            RuntimeDirectory::getPath() . DIRECTORY_SEPARATOR,
        );
    }
}
