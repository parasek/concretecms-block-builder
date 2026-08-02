<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\Exception\GeneratedFileWriteException;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFileCollection;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFileWriter;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class GeneratedTextFileWriterTest extends BlockBuilderTestCase
{
    private Filesystem $filesystem;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-writer-test-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);

        parent::tearDown();
    }

    public function testWritesFilesAndCreatesNestedDirectories(): void
    {
        $blockPath = $this->createBlockDirectory();
        $generatedFiles = new GeneratedTextFileCollection([
            new GeneratedTextFile('controller.php', 'controller', 'controller generator'),
            new GeneratedTextFile('nested/form.php', 'form', 'form generator'),
        ]);

        (new GeneratedTextFileWriter($this->filesystem))->write(
            $generatedFiles,
            $this->createContextForPath($blockPath),
        );

        self::assertSame('controller', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'controller.php'));
        self::assertSame('form', $this->readFile($blockPath . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'form.php'));
    }

    public function testMissingBlockDirectoryIsRejectedWithoutCreatingIt(): void
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'missing';

        try {
            $this->writeOneFile($blockPath, 'controller.php');
            self::fail('The writer must not create a missing block root implicitly.');
        } catch (GeneratedFileWriteException $exception) {
            self::assertStringContainsString('does not exist', $exception->getMessage());
        }

        self::assertDirectoryDoesNotExist($blockPath);
    }

    public function testSymbolicLinkBlockRootIsRejectedWithoutWritingToTarget(): void
    {
        $externalPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'external';
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'block_link';
        $this->filesystem->mkdir($externalPath);
        if (!@symlink($externalPath, $blockPath)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        try {
            $this->writeOneFile($blockPath, 'controller.php');
            self::fail('A symbolic-link block root must be rejected.');
        } catch (GeneratedFileWriteException $exception) {
            self::assertStringContainsString('destination directory is a symbolic link', $exception->getMessage());
        }

        self::assertFileDoesNotExist($externalPath . DIRECTORY_SEPARATOR . 'controller.php');
        self::assertTrue(is_link($blockPath));
    }

    public function testSymbolicLinkAncestorIsRejectedWithoutWritingToTarget(): void
    {
        $blockPath = $this->createBlockDirectory();
        $externalPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'external';
        $linkedPath = $blockPath . DIRECTORY_SEPARATOR . 'nested';
        $this->filesystem->mkdir($externalPath);
        if (!@symlink($externalPath, $linkedPath)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        $this->expectUnsafeLinkFailure($blockPath, 'nested/controller.php');

        self::assertFileDoesNotExist($externalPath . DIRECTORY_SEPARATOR . 'controller.php');
        self::assertTrue(is_link($linkedPath));
    }

    public function testSymbolicLinkDestinationIsRejectedWithoutModifyingTarget(): void
    {
        $blockPath = $this->createBlockDirectory();
        $externalFile = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'external.php';
        $destination = $blockPath . DIRECTORY_SEPARATOR . 'controller.php';
        $this->writeFile($externalFile, 'external');
        if (!@symlink($externalFile, $destination)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        $this->expectUnsafeLinkFailure($blockPath, 'controller.php');

        self::assertSame('external', $this->readFile($externalFile));
        self::assertTrue(is_link($destination));
    }

    public function testRegularFileInIntermediatePathIsRejected(): void
    {
        $blockPath = $this->createBlockDirectory();
        $intermediatePath = $blockPath . DIRECTORY_SEPARATOR . 'nested';
        $this->writeFile($intermediatePath, 'not a directory');

        try {
            $this->writeOneFile($blockPath, 'nested/controller.php');
            self::fail('A regular file cannot be traversed as a generated directory.');
        } catch (GeneratedFileWriteException $exception) {
            self::assertStringContainsString('is not a directory inside the block folder', $exception->getMessage());
        }

        self::assertSame('not a directory', $this->readFile($intermediatePath));
    }

    public function testReplacingHardLinkDoesNotModifyItsOtherName(): void
    {
        $blockPath = $this->createBlockDirectory();
        $externalFile = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'external.php';
        $destination = $blockPath . DIRECTORY_SEPARATOR . 'controller.php';
        $this->writeFile($externalFile, 'external');
        if (!@link($externalFile, $destination)) {
            self::markTestSkipped('Hard links are not available in this environment.');
        }

        $this->writeOneFile($blockPath, 'controller.php', 'generated');

        self::assertSame('external', $this->readFile($externalFile));
        self::assertSame('generated', $this->readFile($destination));
    }

    public function testFilesystemFailureIsWrappedWithPathProducerAndPreviousException(): void
    {
        $blockPath = $this->createBlockDirectory();
        $failure = new RuntimeException('simulated write failure');
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->onlyMethods(['dumpFile'])
            ->getMock();
        $filesystem->method('dumpFile')->willThrowException($failure);
        $generatedFiles = new GeneratedTextFileCollection([
            new GeneratedTextFile('controller.php', 'generated', 'controller generator'),
        ]);

        try {
            (new GeneratedTextFileWriter($filesystem))->write(
                $generatedFiles,
                $this->createContextForPath($blockPath),
            );
            self::fail('Filesystem failures must be converted to a generated-file write exception.');
        } catch (GeneratedFileWriteException $exception) {
            self::assertStringContainsString('controller.php', $exception->getMessage());
            self::assertStringContainsString('controller generator', $exception->getMessage());
            self::assertSame($failure, $exception->getPrevious());
        }
    }

    private function createBlockDirectory(): string
    {
        $blockPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'example_block';
        $this->filesystem->mkdir($blockPath);

        return $blockPath;
    }

    private function writeOneFile(string $blockPath, string $relativePath, string $contents = 'generated'): void
    {
        (new GeneratedTextFileWriter($this->filesystem))->write(
            new GeneratedTextFileCollection([
                new GeneratedTextFile($relativePath, $contents, 'test generator'),
            ]),
            $this->createContextForPath($blockPath),
        );
    }

    private function expectUnsafeLinkFailure(string $blockPath, string $relativePath): void
    {
        try {
            $this->writeOneFile($blockPath, $relativePath);
            self::fail('Writing through a symbolic link must be rejected.');
        } catch (GeneratedFileWriteException $exception) {
            self::assertStringContainsString('through a symbolic link', $exception->getMessage());
        }
    }

    private function createContextForPath(string $blockPath): BlockFileGenerationContext
    {
        $config = $this->createAllFieldTypesConfig();
        $baseContext = $this->createGenerationContext($config);
        $manifest = new BlockGenerationManifest(
            shouldInstallBlock: false,
            shouldRebuildBlock: false,
            blockHandlePascalCase: 'ExampleBlock',
            blockHandleKebabCase: 'example-block',
            blockPath: $blockPath,
            blockPublicPath: '/application/blocks/example_block',
            blockIconPath: null,
            blockIconPublicPath: null,
            customBlockIcon: null,
            databaseTableName: 'btExampleBlock',
            entriesDatabaseTableName: 'btExampleBlockEntries',
        );

        return new BlockFileGenerationContext($config, $manifest, $baseContext->plan);
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
