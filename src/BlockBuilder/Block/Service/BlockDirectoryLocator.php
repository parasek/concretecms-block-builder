<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\ReservedWord\HandleNormalizer;
use Concrete\Core\File\Service\File as FileService;

readonly class BlockDirectoryLocator
{
    public function __construct(
        private FileService $fileService,
        private HandleNormalizer $handleNormalizer,
    ) {
    }

    public function getApplicationBlockPath(string $handle): string
    {
        return DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $handle;
    }

    public function getSafeApplicationBlockDirectory(string $handle): ?string
    {
        $rootPath = realpath(DIR_FILES_BLOCK_TYPES);
        $candidatePath = $this->getApplicationBlockPath($handle);

        if ($rootPath === false || !is_dir($candidatePath) || is_link($candidatePath)) {
            return null;
        }

        $resolvedPath = realpath($candidatePath);
        if ($resolvedPath === false || !str_starts_with($resolvedPath, $rootPath . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $resolvedPath;
    }

    public function hasApplicationBlockCollision(string $handle): bool
    {
        return $this->hasCollisionInDirectory($handle, DIR_FILES_BLOCK_TYPES);
    }

    public function hasCoreBlockCollision(string $handle): bool
    {
        return $this->hasCollisionInDirectory($handle, DIR_FILES_BLOCK_TYPES_CORE);
    }

    private function hasCollisionInDirectory(string $handle, string $basePath): bool
    {
        if (is_dir($basePath . DIRECTORY_SEPARATOR . $handle)) {
            return true;
        }

        foreach ($this->fileService->getDirectoryContents($basePath) as $folder) {
            if ($this->handleNormalizer->normalize($handle) === $this->handleNormalizer->normalize($folder)) {
                return true;
            }
        }

        return false;
    }
}
