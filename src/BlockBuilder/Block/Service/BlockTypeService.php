<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\File\Service\File as FileService;

readonly class BlockTypeService
{
    public function __construct(
        private FileService $fileService,
        private ReservedWordsService $reservedWordsService,
    ) {
    }

    public function isBlockTypeFolderAlreadyCreated(string $handle, ?string $searchedFolder = null): bool
    {
        // Check the "application/blocks" path
        if (!$searchedFolder || $searchedFolder === 'application') {
            $applicationPath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $handle;
            if (is_dir($applicationPath)) {
                return true;
            }
        }

        // Check the "concrete/blocks" path
        if (!$searchedFolder || $searchedFolder === 'concrete') {
            $concretePath = DIR_FILES_BLOCK_TYPES_CORE . DIRECTORY_SEPARATOR . $handle;
            if (is_dir($concretePath)) {
                return true;
            }
        }

        // Handles like "google_map" and "googlemap" will generate the similar database table name:
        // google_map => btGoogleMap
        // googlemap => btGooglemap
        // In the end, it will mess up the database. Let's prevent from that happening.
        $applicationFolders = [];
        if (!$searchedFolder || $searchedFolder === 'application') {
            $applicationFolders = $this->fileService->getDirectoryContents(DIR_FILES_BLOCK_TYPES);
        }

        $concreteFolders = [];
        if (!$searchedFolder || $searchedFolder === 'concrete') {
            $concreteFolders = $this->fileService->getDirectoryContents(DIR_FILES_BLOCK_TYPES_CORE);
        }

        $folders = array_merge($applicationFolders, $concreteFolders);

        foreach ($folders as $folder) {
            if ($this->reservedWordsService->normalize($handle) === $this->reservedWordsService->normalize($folder)) {
                return true;
            }
        }

        return false;
    }

    public function isBlockTypeInstalled(string $handle): bool
    {
        return is_object(BlockType::getByHandle($handle));
    }

    public function getBlockTypeId(?string $handle): ?int
    {
        $blockType = BlockType::getByHandle($handle);

        if (is_object($blockType)) {
            return $blockType->getBlockTypeID();
        }

        return null;
    }
}
