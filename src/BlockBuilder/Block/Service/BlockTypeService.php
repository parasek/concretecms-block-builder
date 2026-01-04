<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Concrete\Core\File\Service\File as FileService;
use Concrete\Core\Permission\Key\Key as Permissions;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class BlockTypeService
{
    public function __construct(
        private FileService $fileService,
        private ReservedWordsService $reservedWordsService,
        private User $u,
        private EntityManagerInterface $entityManager,
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

    public function isBlockTypeInstalled(null|BlockTypeEntity|int|string $bt): bool
    {
        if (is_numeric($bt)) {
            $bt = BlockType::getByID($bt);
        } elseif (is_string($bt)) {
            $bt = BlockType::getByHandle($bt);
        }

        if ($bt instanceof BlockTypeEntity) {
            return true;
        }

        return false;
    }

    public function getBlockTypeObject(null|int|string $handleOrId): ?BlockTypeEntity
    {
        if (is_numeric($handleOrId)) {
            $bt = BlockType::getByID($handleOrId);
        } elseif (is_string($handleOrId)) {
            $bt = BlockType::getByHandle($handleOrId);
        } else {
            $bt = null;
        }

        if ($bt instanceof BlockTypeEntity) {
            return $bt;
        }

        return null;
    }

    /**
     * Validates the block type folder before deletion.
     *
     * @param string $handle
     *
     * @return bool|string Returns false if there are no errors, error message otherwise.
     */
    public function validateBlockTypeFolderBeforeDeletion(string $handle): bool|string
    {
        $key = Permissions::getByHandle('uninstall_packages');
        $blockTypePath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $handle;

        if (!$this->u->isSuperUser()) {
            return t('Only the super user may remove block types.');
        }

        if (!$key->validate()) {
            return t('You do not have permission to uninstall packages.');
        }

        if (!is_dir($blockTypePath)) {
            return t('Folder "%s" does not exist.', $blockTypePath);
        }

        if ($this->isBlockTypeInstalled($handle)) {
            return t('Uninstall block type before deleting folder.');
        }

        return false;
    }

    /**
     * Deletes the block type folder.
     *
     * @param string $handle
     *
     * @return bool|string Returns true if there are no errors, error message otherwise.
     */
    public function deleteBlockTypeFolder(string $handle): bool|string
    {
        $blockTypePath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $handle;

        try {
            $result = $this->fileService->removeAll(source: $blockTypePath, inc: true);
            if (!$result) {
                return t('Failed to remove block type folder "%s"', $handle);
            }
        } catch (Exception) {
            return t('Failed to remove block type folder "%s". Please check file permissions.', $handle);
        }

        return true;
    }

    /**
     * Validates the block type before uninstallation.
     *
     * @param int $blockTypeId
     *
     * @return bool|string Returns false if there are no errors, error message otherwise.
     */
    public function validateBlockTypeBeforeUninstall(int $blockTypeId): bool|string
    {
        $key = Permissions::getByHandle('uninstall_packages');

        if (!$this->u->isSuperUser()) {
            return t('Only the super user may remove block types.');
        }

        if (!$key->validate()) {
            return t('You do not have permission to uninstall packages.');
        }

        $bt = $blockTypeId > 0 ? $this->entityManager->find(BlockTypeEntity::class, $blockTypeId) : null;

        if ($bt === null) {
            return t('Unable to find the block type specified.');
        }

        if ($bt->isBlockTypeInternal()) {
            return t('This block type is internal. It cannot be uninstalled.');
        }

        $handle = $bt->getBlockTypeHandle();

        if (!$this->isBlockTypeInstalled($handle)) {
            return t('Specified block type is not installed.');
        }

        return false;
    }

    /**
     * Uninstalls the block type.
     */
    public function uninstallBlockType(int $blockTypeId): string
    {
        $bt = $blockTypeId > 0 ? $this->entityManager->find(BlockTypeEntity::class, $blockTypeId) : null;

        $blockTypeName = '';

        if ($bt) {
            $blockTypeName = $bt->getBlockTypeName();
            $bt->delete();
        }

        return $blockTypeName;
    }

    public function getBlockTypeIconPublicPaths(): array
    {
        $paths = [];

        $concreteBlockFolders = $this->fileService->getDirectoryContents(DIR_FILES_BLOCK_TYPES_CORE);
        sort($concreteBlockFolders);

        foreach ($concreteBlockFolders as $concreteBlockFolder) {
            $path = $this->getBlockTypeIconPublicPath($concreteBlockFolder);

            if (file_exists(DIR_BASE . $path)) {
                $paths[] = [
                    'path' => $path,
                    'label' => $concreteBlockFolder,
                ];
            }

        }

        return $paths;
    }

    public function getBlockTypeIconPublicPath(string $concreteBlockFolder): string
    {
        return DIRECTORY_SEPARATOR . DIRNAME_CORE . DIRECTORY_SEPARATOR .
            DIRNAME_BLOCKS . DIRECTORY_SEPARATOR .
            $concreteBlockFolder . DIRECTORY_SEPARATOR .
            FILENAME_BLOCK_ICON;
    }
}
