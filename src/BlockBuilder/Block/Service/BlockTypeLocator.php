<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;

readonly class BlockTypeLocator
{
    public function find(null|int|string $handleOrId): ?BlockTypeEntity
    {
        $blockType = match (true) {
            is_int($handleOrId), is_string($handleOrId) && ctype_digit($handleOrId) => BlockType::getByID((int) $handleOrId),
            is_string($handleOrId) => BlockType::getByHandle($handleOrId),
            default => null,
        };

        return $blockType instanceof BlockTypeEntity ? $blockType : null;
    }

    public function isInstalled(null|BlockTypeEntity|int|string $blockType): bool
    {
        return $blockType instanceof BlockTypeEntity || $this->find($blockType) !== null;
    }
}
