<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\BlockIconGenerator;
use BlockBuilder\BlockGenerator\Exception\BlockIconGenerationException;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Test type: Block icon source-selection filesystem component test.
 *
 * Verifies icon-source precedence and fallbacks, rejects invalid sources and destinations, wraps
 * copy failures, and always cleans temporary uploaded files when generation cannot complete.
 */
final class BlockIconGeneratorSourceTest extends BlockBuilderTestCase
{
    private Filesystem $filesystem;
    private string $temporaryDirectory;
    private string $selectedIconPath;
    private string $packageIconPath;
    private string $smallIconPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-icon-source-test-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->temporaryDirectory);
        $packagePath = dirname(__DIR__, 2);
        $this->selectedIconPath = $packagePath . DIRECTORY_SEPARATOR . 'generator_files' . DIRECTORY_SEPARATOR . 'icon.png';
        $this->packageIconPath = $packagePath . DIRECTORY_SEPARATOR . 'icon.png';
        $this->smallIconPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'small.png';
        $smallPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        self::assertNotFalse($smallPng);
        $this->writeFile($this->smallIconPath, $smallPng);
        self::assertNotFalse(getimagesize($this->smallIconPath));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);

        parent::tearDown();
    }

    /**
     * Verifies that an uploaded icon is chosen before a selected preset or existing block icon.
     */
    public function testUploadedIconTakesPrecedenceOverSelectedAndExistingIcons(): void
    {
        $blockPath = $this->createBlockDirectoryWithIcon($this->packageIconPath);
        $uploadedIcon = new UploadedFile($this->smallIconPath, 'uploaded.png', 'image/png', null, true);

        $this->generate($this->createIconManifest($blockPath, $this->selectedIconPath, $uploadedIcon));

        self::assertSame(
            $this->readFile($this->smallIconPath),
            $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON),
        );
        $this->assertNoTemporaryIconsRemain($blockPath);
    }

    /**
     * Verifies that a selected preset icon replaces an existing block icon when no upload is supplied.
     */
    public function testSelectedIconTakesPrecedenceOverExistingIcon(): void
    {
        $blockPath = $this->createBlockDirectoryWithIcon($this->packageIconPath);

        $this->generate($this->createIconManifest($blockPath, $this->selectedIconPath));

        self::assertSame(
            $this->readFile($this->selectedIconPath),
            $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON),
        );
        $this->assertNoTemporaryIconsRemain($blockPath);
    }

    /**
     * Verifies that an existing block icon is kept when no uploaded or selected icon is provided.
     */
    public function testExistingIconIsRetainedWhenNoNewSourceIsProvided(): void
    {
        $blockPath = $this->createBlockDirectoryWithIcon($this->packageIconPath);
        $expectedContents = $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON);

        $this->generate($this->createIconManifest($blockPath));

        self::assertSame($expectedContents, $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON));
        $this->assertNoTemporaryIconsRemain($blockPath);
    }

    /**
     * Verifies that the package default icon is copied when a block has no other icon source.
     */
    public function testDefaultPackageIconIsUsedWhenDestinationHasNoIcon(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'example_block';
        $this->filesystem->mkdir($blockPath);

        $this->generate($this->createIconManifest($blockPath));

        self::assertSame(
            $this->readFile($this->selectedIconPath),
            $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON),
        );
        $this->assertNoTemporaryIconsRemain($blockPath);
    }

    /**
     * Verifies that a missing selected icon leaves the existing block icon in place.
     */
    public function testMissingSelectedIconFallsBackToExistingIcon(): void
    {
        $blockPath = $this->createBlockDirectoryWithIcon($this->packageIconPath);
        $expectedContents = $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON);

        $this->generate($this->createIconManifest(
            $blockPath,
            $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'missing.png',
        ));

        self::assertSame($expectedContents, $this->readFile($blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON));
        $this->assertNoTemporaryIconsRemain($blockPath);
    }

    /**
     * Verifies that an invalid upload preserves the existing icon and removes its temporary file.
     */
    public function testInvalidUploadedIconDoesNotReplaceExistingIconAndCleansTemporaryFile(): void
    {
        $blockPath = $this->createBlockDirectoryWithIcon($this->packageIconPath);
        $destination = $blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON;
        $expectedContents = $this->readFile($destination);
        $invalidPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'invalid.png';
        $this->writeFile($invalidPath, 'not a png');
        $uploadedIcon = new UploadedFile($invalidPath, 'invalid.png', 'image/png', null, true);

        try {
            $this->generate($this->createIconManifest($blockPath, $this->selectedIconPath, $uploadedIcon));
            self::fail('An invalid uploaded icon must fail generation.');
        } catch (BlockIconGenerationException $exception) {
            self::assertStringContainsString('not a valid PNG image', $exception->getMessage());
        }

        self::assertSame($expectedContents, $this->readFile($destination));
        $this->assertNoTemporaryIconsRemain($blockPath);
    }

    /**
     * Verifies that choosing an invalid preset fails explicitly instead of silently using another icon.
     */
    public function testInvalidSelectedIconDoesNotFallBackAfterSelection(): void
    {
        $blockPath = $this->createBlockDirectoryWithIcon($this->packageIconPath);
        $destination = $blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON;
        $expectedContents = $this->readFile($destination);
        $invalidPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'invalid-selected.png';
        $this->writeFile($invalidPath, 'not a png');

        $this->expectException(BlockIconGenerationException::class);
        $this->expectExceptionMessage('not a valid PNG image');

        try {
            $this->generate($this->createIconManifest($blockPath, $invalidPath));
        } finally {
            self::assertSame($expectedContents, $this->readFile($destination));
            $this->assertNoTemporaryIconsRemain($blockPath);
        }
    }

    /**
     * Verifies that an icon destination occupied by a directory is rejected and temporary files are removed.
     */
    public function testDirectoryAtIconDestinationIsRejectedAndTemporaryFileIsCleaned(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'example_block';
        $destination = $blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON;
        $this->filesystem->mkdir($destination);

        $this->expectException(BlockIconGenerationException::class);
        $this->expectExceptionMessage('is not a file');

        try {
            $this->generate($this->createIconManifest($blockPath, $this->selectedIconPath));
        } finally {
            self::assertDirectoryExists($destination);
            $this->assertNoTemporaryIconsRemain($blockPath);
        }
    }

    /**
     * Verifies that a copy failure is reported as an icon error and leaves no temporary icon behind.
     */
    public function testFilesystemCopyFailureIsWrappedAndTemporaryFileIsCleaned(): void
    {
        $blockPath = $this->createBlockDirectoryWithIcon($this->packageIconPath);
        $destination = $blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON;
        $expectedContents = $this->readFile($destination);
        $failure = new RuntimeException('simulated copy failure');
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->onlyMethods(['copy'])
            ->getMock();
        $filesystem->method('copy')->willThrowException($failure);

        try {
            (new BlockIconGenerator($this->getService(\BlockBuilder\Environment\EnvironmentService::class), $filesystem))
                ->generate($this->createIconManifest($blockPath, $this->selectedIconPath));
            self::fail('Filesystem copy failures must be wrapped.');
        } catch (BlockIconGenerationException $exception) {
            self::assertSame($failure, $exception->getPrevious());
            self::assertStringContainsString($destination, $exception->getMessage());
        }

        self::assertSame($expectedContents, $this->readFile($destination));
        $this->assertNoTemporaryIconsRemain($blockPath);
    }

    private function generate(BlockGenerationManifest $manifest): void
    {
        $this->getService(BlockIconGenerator::class)->generate($manifest);
    }

    private function createBlockDirectoryWithIcon(string $sourcePath): string
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'example_block';
        $this->filesystem->mkdir($blockPath);
        $this->filesystem->copy($sourcePath, $blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON);

        return $blockPath;
    }

    private function createIconManifest(
        string $blockPath,
        ?string $selectedIconPath = null,
        ?UploadedFile $customBlockIcon = null,
    ): BlockGenerationManifest {
        return new BlockGenerationManifest(
            shouldInstallBlock: false,
            shouldRebuildBlock: false,
            blockHandlePascalCase: 'ExampleBlock',
            blockHandleKebabCase: 'example-block',
            blockPath: $blockPath,
            blockPublicPath: '/application/blocks/example_block',
            blockIconPath: $selectedIconPath,
            blockIconPublicPath: $selectedIconPath === null ? null : '/selected/icon.png',
            customBlockIcon: $customBlockIcon,
            databaseTableName: 'btExampleBlock',
            entriesDatabaseTableName: 'btExampleBlockEntries',
        );
    }

    private function assertNoTemporaryIconsRemain(string $blockPath): void
    {
        $temporaryPaths = glob($blockPath . DIRECTORY_SEPARATOR . '.block-builder-icon-*');
        self::assertIsArray($temporaryPaths);
        self::assertSame([], $temporaryPaths);
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
