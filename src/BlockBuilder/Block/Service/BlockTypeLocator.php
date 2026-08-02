<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;

readonly class BlockTypeLocator
{
    public function findByIdentifier(null|int|string $blockTypeIdentifier): ?BlockTypeEntity
    {
        $blockType = match (true) {
            is_int($blockTypeIdentifier), is_string($blockTypeIdentifier) && ctype_digit($blockTypeIdentifier) => BlockType::getByID((int) $blockTypeIdentifier),
            is_string($blockTypeIdentifier) => BlockType::getByHandle($blockTypeIdentifier),
            default => null,
        };

        return $blockType instanceof BlockTypeEntity ? $blockType : null;
    }

    public function isInstalled(null|BlockTypeEntity|int|string $blockType): bool
    {
        return $blockType instanceof BlockTypeEntity || $this->findByIdentifier($blockType) !== null;
    }
}
