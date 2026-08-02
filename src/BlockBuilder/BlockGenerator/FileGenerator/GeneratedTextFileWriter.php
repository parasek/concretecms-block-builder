<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\Exception\GeneratedFileWriteException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

readonly class GeneratedTextFileWriter
{
    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    public function write(GeneratedTextFileCollection $generatedFiles, BlockFileGenerationContext $context): void
    {
        foreach ($generatedFiles as $generatedFile) {
            $this->writeFile($generatedFile, $context);
        }
    }

    private function writeFile(GeneratedTextFile $generatedFile, BlockFileGenerationContext $context): void
    {
        $relativeSystemPath = str_replace('/', DIRECTORY_SEPARATOR, $generatedFile->relativePath);
        $path = $context->manifest->blockPath . DIRECTORY_SEPARATOR . $relativeSystemPath;

        try {
            $this->assertSafeDestination($generatedFile, $context);
            $this->filesystem->dumpFile($path, $generatedFile->contents);
        } catch (GeneratedFileWriteException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new GeneratedFileWriteException(
                message: sprintf(
                    'Unable to generate file "%s" for block "%s" using "%s".',
                    $path,
                    $context->config->blockHandle,
                    $generatedFile->producer,
                ),
                previous: $throwable,
            );
        }
    }

    private function assertSafeDestination(
        GeneratedTextFile $generatedFile,
        BlockFileGenerationContext $context,
    ): void {
        $resolvedBlockPath = realpath($context->manifest->blockPath);
        if ($resolvedBlockPath === false || !is_dir($resolvedBlockPath)) {
            throw new GeneratedFileWriteException(sprintf(
                'The destination directory for block "%s" does not exist.',
                $context->config->blockHandle,
            ));
        }

        $pathSegments = explode('/', $generatedFile->relativePath);
        $currentPath = $resolvedBlockPath;
        foreach ($pathSegments as $pathPosition => $pathSegment) {
            $currentPath .= DIRECTORY_SEPARATOR . $pathSegment;
            if (is_link($currentPath)) {
                throw new GeneratedFileWriteException(sprintf(
                    'Refusing to write generated file "%s" for block "%s" through a symbolic link.',
                    $generatedFile->relativePath,
                    $context->config->blockHandle,
                ));
            }

            $isDestination = $pathPosition === array_key_last($pathSegments);
            if ($isDestination || !file_exists($currentPath)) {
                continue;
            }

            $resolvedCurrentPath = realpath($currentPath);
            if (
                $resolvedCurrentPath === false
                || !is_dir($resolvedCurrentPath)
                || !$this->isPathInsideDirectory($resolvedCurrentPath, $resolvedBlockPath)
            ) {
                throw new GeneratedFileWriteException(sprintf(
                    'Generated destination "%s" for block "%s" is not a directory inside the block folder.',
                    $generatedFile->relativePath,
                    $context->config->blockHandle,
                ));
            }

            $currentPath = $resolvedCurrentPath;
        }
    }

    private function isPathInsideDirectory(string $path, string $directory): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
            $directory = strtolower($directory);
        }

        return $path === $directory || str_starts_with($path, $directory . DIRECTORY_SEPARATOR);
    }
}
