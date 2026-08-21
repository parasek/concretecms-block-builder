<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Block;

use BlockBuilder\Block\Service\BlockTypeSetSynchronizer;
use Concrete\Core\Block\BlockType\Set as BlockTypeSet;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use PHPUnit\Framework\TestCase;

final class BlockTypeSetSynchronizerTest extends TestCase
{
    public function testChangedSetRemovesOldMembershipAndAddsNewMembership(): void
    {
        $blockType = $this->createBlockType();
        $oldSet = $this->createBlockTypeSet(1);
        $oldSet->expects(self::once())->method('deleteKey')->with($blockType);
        $targetSet = $this->createBlockTypeSet(2);
        $targetSet->expects(self::once())->method('addBlockType')->with($blockType);
        $blockType->method('getBlockTypeSets')->willReturn([$oldSet]);
        $synchronizer = $this->createSynchronizer('multimedia', $targetSet);

        $synchronizer->synchronize($blockType, 'multimedia');
    }

    public function testUnchangedSetPreservesExistingMembershipAndDisplayOrder(): void
    {
        $blockType = $this->createBlockType();
        $currentSet = $this->createBlockTypeSet(2);
        $currentSet->expects(self::never())->method('deleteKey');
        $targetSet = $this->createBlockTypeSet(2);
        $targetSet->expects(self::never())->method('addBlockType');
        $blockType->method('getBlockTypeSets')->willReturn([$currentSet]);
        $synchronizer = $this->createSynchronizer('multimedia', $targetSet);

        $synchronizer->synchronize($blockType, 'multimedia');
    }

    public function testEmptyTargetRemovesEveryExistingMembership(): void
    {
        $blockType = $this->createBlockType();
        $firstSet = $this->createBlockTypeSet(1);
        $secondSet = $this->createBlockTypeSet(2);
        $firstSet->expects(self::once())->method('deleteKey')->with($blockType);
        $secondSet->expects(self::once())->method('deleteKey')->with($blockType);
        $blockType->method('getBlockTypeSets')->willReturn([$firstSet, $secondSet]);
        $synchronizer = $this->getMockBuilder(BlockTypeSetSynchronizer::class)
            ->onlyMethods(['findByHandle'])
            ->getMock();
        $synchronizer->expects(self::never())->method('findByHandle');

        $synchronizer->synchronize($blockType, '');
    }

    public function testUnknownTargetFailsBeforeExistingMembershipIsChanged(): void
    {
        $blockType = $this->createBlockType();
        $currentSet = $this->createBlockTypeSet(1);
        $currentSet->expects(self::never())->method('deleteKey');
        $blockType->method('getBlockTypeSets')->willReturn([$currentSet]);
        $synchronizer = $this->createSynchronizer('missing', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to find block type set "missing".');

        $synchronizer->synchronize($blockType, 'missing');
    }

    private function createBlockType(): BlockTypeEntity
    {
        return $this->getMockBuilder(BlockTypeEntity::class)
            ->onlyMethods(['getBlockTypeSets'])
            ->getMock();
    }

    private function createBlockTypeSet(int $identifier): BlockTypeSet
    {
        $set = $this->getMockBuilder(BlockTypeSet::class)
            ->onlyMethods(['getBlockTypeSetID', 'addBlockType', 'deleteKey'])
            ->getMock();
        $set->method('getBlockTypeSetID')->willReturn($identifier);

        return $set;
    }

    private function createSynchronizer(string $handle, ?BlockTypeSet $set): BlockTypeSetSynchronizer
    {
        $synchronizer = $this->getMockBuilder(BlockTypeSetSynchronizer::class)
            ->onlyMethods(['findByHandle'])
            ->getMock();
        $synchronizer->expects(self::once())
            ->method('findByHandle')
            ->with($handle)
            ->willReturn($set);

        return $synchronizer;
    }
}
