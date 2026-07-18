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

    public function getApplicationPath(string $handle): string
    {
        return DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $handle;
    }

    public function getSafeApplicationBlockDirectory(string $handle): ?string
    {
        $rootPath = realpath(DIR_FILES_BLOCK_TYPES);
        $candidatePath = $this->getApplicationPath($handle);

        if ($rootPath === false || !is_dir($candidatePath) || is_link($candidatePath)) {
            return null;
        }

        $resolvedPath = realpath($candidatePath);
        if ($resolvedPath === false || !str_starts_with($resolvedPath, $rootPath . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $resolvedPath;
    }

    public function hasCollision(string $handle, ?string $searchedFolder = null): bool
    {
        $paths = [];
        if ($searchedFolder === null || $searchedFolder === 'application') {
            $paths[DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $handle] = DIR_FILES_BLOCK_TYPES;
        }
        if ($searchedFolder === null || $searchedFolder === 'concrete') {
            $paths[DIR_FILES_BLOCK_TYPES_CORE . DIRECTORY_SEPARATOR . $handle] = DIR_FILES_BLOCK_TYPES_CORE;
        }

        foreach (array_keys($paths) as $path) {
            if (is_dir($path)) {
                return true;
            }
        }

        foreach (array_unique(array_values($paths)) as $basePath) {
            foreach ($this->fileService->getDirectoryContents($basePath) as $folder) {
                if ($this->handleNormalizer->normalize($handle) === $this->handleNormalizer->normalize($folder)) {
                    return true;
                }
            }
        }

        return false;
    }
}
