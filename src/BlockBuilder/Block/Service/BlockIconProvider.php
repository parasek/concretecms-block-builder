<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\File\Service\File as FileService;

readonly class BlockIconProvider
{
    public function __construct(private FileService $fileService)
    {
    }

    public function getPublicPaths(): array
    {
        $paths = [];
        $folders = $this->fileService->getDirectoryContents(DIR_FILES_BLOCK_TYPES_CORE);
        sort($folders);

        foreach ($folders as $folder) {
            $path = $this->getPublicPath($folder);
            if (file_exists(DIR_BASE . $path)) {
                $paths[] = ['path' => $path, 'label' => $folder];
            }
        }

        return $paths;
    }

    public function isExistingApplicationBlockIcon(string $publicPath): bool
    {
        $applicationBlocksPath = realpath(DIR_FILES_BLOCK_TYPES);
        $iconPath = realpath(DIR_BASE . $publicPath);
        if ($applicationBlocksPath === false || $iconPath === false || !is_file($iconPath)) {
            return false;
        }

        if (basename($iconPath) !== FILENAME_BLOCK_ICON) {
            return false;
        }

        $iconBlocksPath = dirname($iconPath, 2);
        if (DIRECTORY_SEPARATOR === '\\') {
            $applicationBlocksPath = strtolower($applicationBlocksPath);
            $iconBlocksPath = strtolower($iconBlocksPath);
        }

        return $iconBlocksPath === $applicationBlocksPath;
    }

    private function getPublicPath(string $folder): string
    {
        return DIRECTORY_SEPARATOR .
            DIRNAME_CORE . DIRECTORY_SEPARATOR .
            DIRNAME_BLOCKS . DIRECTORY_SEPARATOR .
            $folder . DIRECTORY_SEPARATOR .
            FILENAME_BLOCK_ICON;
    }
}
