<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\Block\BlockType\Set as BlockTypeSet;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;

class BlockTypeSetSynchronizer
{
    public function synchronize(BlockTypeEntity $blockType, ?string $targetSetHandle): void
    {
        $targetSetHandle = trim((string) $targetSetHandle);
        $targetSet = $targetSetHandle !== '' ? $this->findByHandle($targetSetHandle) : null;
        if ($targetSetHandle !== '' && !$targetSet instanceof BlockTypeSet) {
            throw new \RuntimeException(sprintf('Unable to find block type set "%s".', $targetSetHandle));
        }

        $targetSetIdentifier = $targetSet instanceof BlockTypeSet
            ? (int) $targetSet->getBlockTypeSetID()
            : null;
        $alreadyInTargetSet = false;
        foreach ($blockType->getBlockTypeSets() as $currentSet) {
            if (!$currentSet instanceof BlockTypeSet) {
                continue;
            }
            if ($targetSetIdentifier !== null && (int) $currentSet->getBlockTypeSetID() === $targetSetIdentifier) {
                $alreadyInTargetSet = true;
                continue;
            }

            $currentSet->deleteKey($blockType);
        }

        if ($targetSet instanceof BlockTypeSet && !$alreadyInTargetSet) {
            $targetSet->addBlockType($blockType);
        }
    }

    protected function findByHandle(string $handle): ?BlockTypeSet
    {
        return BlockTypeSet::getByHandle($handle);
    }
}
