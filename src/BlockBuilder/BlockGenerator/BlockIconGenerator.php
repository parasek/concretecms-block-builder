<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use BlockBuilder\Block\Dto\BlockGenerationManifest;
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

        try {
            if ($manifest->customBlockIcon !== null) {
                $manifest->customBlockIcon->move(dirname($destination), basename($destination));
            } elseif ($manifest->blockIconPath !== null && is_file($manifest->blockIconPath)) {
                $this->copyUnlessSourceIsDestination($manifest->blockIconPath, $destination);
            }

            if (!is_file($destination)) {
                $this->copyUnlessSourceIsDestination(
                    DIR_BASE . $this->environmentService->getPublicPathToDefaultBlockIcon(),
                    $destination,
                );
            }

            $this->assertValidPng($destination);
        } catch (Throwable $throwable) {
            if ($throwable instanceof BlockIconGenerationException) {
                throw $throwable;
            }

            throw new BlockIconGenerationException(
                message: sprintf('Unable to generate block icon at "%s".', $destination),
                previous: $throwable,
            );
        }
    }

    private function copyUnlessSourceIsDestination(string $source, string $destination): void
    {
        $resolvedSource = realpath($source);
        $resolvedDestination = realpath($destination);

        if ($resolvedSource !== false && $resolvedSource === $resolvedDestination) {
            return;
        }

        $this->filesystem->copy($source, $destination, true);
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
