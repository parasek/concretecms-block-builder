<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\BlockIconGenerator;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Symfony\Component\Filesystem\Filesystem;

final class BlockIconGeneratorTest extends BlockBuilderTestCase
{
    private Filesystem $filesystem;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
            'block-builder-icon-test-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);

        parent::tearDown();
    }

    public function testReplacingSymlinkDoesNotModifyItsTarget(): void
    {
        $source = $this->getPackagePath('generator_files/icon.png');
        $symlinkTarget = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'symlink-target.png';
        $destination = $this->temporaryDirectory . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON;
        $this->filesystem->copy($this->getPackagePath('icon.png'), $symlinkTarget);
        $targetContents = $this->readFile($symlinkTarget);
        self::assertNotSame($targetContents, $this->readFile($source));

        if (!@symlink($symlinkTarget, $destination)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        $this->generateIcon($source);

        self::assertFalse(is_link($destination));
        self::assertSame($targetContents, $this->readFile($symlinkTarget));
        self::assertSame($this->readFile($source), $this->readFile($destination));
    }

    public function testReplacingHardLinkDoesNotModifyItsOtherName(): void
    {
        $source = $this->getPackagePath('generator_files/icon.png');
        $hardLinkTarget = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'hard-link-target.png';
        $destination = $this->temporaryDirectory . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON;
        $this->filesystem->copy($this->getPackagePath('icon.png'), $hardLinkTarget);
        $targetContents = $this->readFile($hardLinkTarget);
        self::assertNotSame($targetContents, $this->readFile($source));

        if (!@link($hardLinkTarget, $destination)) {
            self::markTestSkipped('Hard links are not available in this environment.');
        }

        $this->generateIcon($source);

        self::assertSame($targetContents, $this->readFile($hardLinkTarget));
        self::assertSame($this->readFile($source), $this->readFile($destination));
    }

    private function generateIcon(string $source): void
    {
        $manifest = new BlockGenerationManifest(
            shouldInstallBlock: false,
            shouldRebuildBlock: true,
            blockHandlePascalCase: 'IconTest',
            blockHandleKebabCase: 'icon-test',
            blockPath: $this->temporaryDirectory,
            blockPublicPath: '/application/blocks/icon_test',
            blockIconPath: $source,
            blockIconPublicPath: '/test/icon.png',
            customBlockIcon: null,
            databaseTableName: 'btIconTest',
            entriesDatabaseTableName: 'btIconTestEntries',
        );

        $this->getService(BlockIconGenerator::class)->generate($manifest);
    }

    private function getPackagePath(string $relativePath): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
