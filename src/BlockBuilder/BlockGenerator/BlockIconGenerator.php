<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use BlockBuilder\BlockGenerator\Exception\BlockIconGenerationException;
use BlockBuilder\Environment\EnvironmentService;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

readonly class BlockIconGenerator
{
    public function __construct(
        private EnvironmentService $environmentService,
        private Filesystem $filesystem,
    ) {
    }

    public function generate(BlockGenerationManifest $manifest): void
    {
        $destination = $manifest->blockPath . DIRECTORY_SEPARATOR . FILENAME_BLOCK_ICON;
        $temporaryPath = null;

        try {
            if ($manifest->customBlockIcon !== null) {
                $source = $manifest->customBlockIcon->getPathname();
            } elseif ($manifest->blockIconPath !== null && is_file($manifest->blockIconPath)) {
                $source = $manifest->blockIconPath;
            } elseif (is_file($destination)) {
                $source = $destination;
            } else {
                $source = DIR_BASE . $this->environmentService->getPublicPathToDefaultBlockIcon();
            }

            $temporaryPath = $this->filesystem->tempnam(
                dirname($destination),
                '.block-builder-icon-',
                '.png',
            );
            $this->filesystem->copy($source, $temporaryPath, true);
            $this->assertValidPng($temporaryPath);
            $this->replaceDestination($temporaryPath, $destination);
            $temporaryPath = null;
            $this->assertValidPng($destination);
        } catch (Throwable $throwable) {
            if ($throwable instanceof BlockIconGenerationException) {
                throw $throwable;
            }

            throw new BlockIconGenerationException(
                message: sprintf('Unable to generate block icon at "%s".', $destination),
                previous: $throwable,
            );
        } finally {
            if ($temporaryPath !== null && (file_exists($temporaryPath) || is_link($temporaryPath))) {
                try {
                    $this->filesystem->remove($temporaryPath);
                } catch (Throwable) {
                    // Keep the original generation failure as the reported error.
                }
            }
        }
    }

    private function replaceDestination(string $source, string $destination): void
    {
        if (file_exists($destination) || is_link($destination)) {
            if (!is_file($destination) && !is_link($destination)) {
                throw new BlockIconGenerationException(
                    sprintf('The block icon destination "%s" is not a file.', $destination),
                );
            }
            $this->filesystem->remove($destination);
        }

        $this->filesystem->rename($source, $destination);
    }

    private function assertValidPng(string $path): void
    {
        $fileSize = is_file($path) ? filesize($path) : false;
        if ($fileSize === false || $fileSize === 0 || !is_readable($path)) {
            throw new BlockIconGenerationException(
                sprintf('The generated block icon at "%s" is missing, unreadable, or empty.', $path),
            );
        }

        $imageInformation = getimagesize($path);
        if ($imageInformation === false || ($imageInformation[2] ?? null) !== IMAGETYPE_PNG) {
            throw new BlockIconGenerationException(
                sprintf('The generated block icon at "%s" is not a valid PNG image.', $path),
            );
        }
    }
}
