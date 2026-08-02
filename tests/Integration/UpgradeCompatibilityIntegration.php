<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Integration;

use BlockBuilder\Tests\Integration\Support\ConcreteIntegrationTestCase;
use Concrete\Core\Block\Block;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;

final class UpgradeCompatibilityIntegration extends ConcreteIntegrationTestCase
{
    private const string BLOCK_HANDLE = 'block_builder_integration_upgrade_fixture';
    private const string MAIN_TABLE = 'btBlockBuilderIntegrationUpgradeFixture';
    private const string ENTRIES_TABLE = 'btBlockBuilderIntegrationUpgradeFixtureEntries';

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('BLOCK_BUILDER_INTEGRATION_EXPECT_UPGRADE_FIXTURE') !== '1') {
            self::markTestSkipped('The legacy upgrade fixture is created only by the guarded upgrade job.');
        }
    }

    public function testLegacyGeneratedBlockAndDataSurvivePackageUpgrade(): void
    {
        $this->environmentGuard->assertActiveDatabase($this->connection);

        $configPath = $this->environmentGuard->blocksRoot
            . DIRECTORY_SEPARATOR . self::BLOCK_HANDLE
            . DIRECTORY_SEPARATOR . 'config-bb.json';
        self::assertFileExists($configPath);
        self::assertFalse(is_link($configPath));
        $configContents = file_get_contents($configPath);
        self::assertNotFalse($configContents);
        $config = json_decode($configContents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($config);
        self::assertSame('2.8.1', $config['version']);
        self::assertSame(self::BLOCK_HANDLE, $config['blockHandle']);

        $blockType = BlockType::getByHandle(self::BLOCK_HANDLE);
        self::assertInstanceOf(BlockTypeEntity::class, $blockType);
        $mainRows = $this->connection->fetchAllAssociative(
            'SELECT bID, basic_single_choice_field_default_first FROM ' . self::MAIN_TABLE,
        );
        self::assertCount(1, $mainRows);
        self::assertSame('show', $mainRows[0]['basic_single_choice_field_default_first']);
        $blockIdentifier = (int) $mainRows[0]['bID'];
        self::assertGreaterThan(0, $blockIdentifier);
        self::assertSame(['example2', 'some_data2'], $this->connection->fetchFirstColumn(
            'SELECT repeatable_single_choice_field_enhanced_first FROM ' . self::ENTRIES_TABLE
            . ' WHERE bID = ? ORDER BY position, id',
            [$blockIdentifier],
        ));

        $block = Block::getByID($blockIdentifier);
        self::assertInstanceOf(Block::class, $block);
        $controller = $block->getController();
        self::assertInstanceOf(BlockController::class, $controller);
        $controller->view();
        $viewData = $controller->getSets();
        self::assertSame('show', $viewData['basic_single_choice_field_default_first']);
        self::assertCount(2, $viewData['entries']);
        self::assertSame('example2', $viewData['entries'][0]['repeatable_single_choice_field_enhanced_first']);
        self::assertSame('some_data2', $viewData['entries'][1]['repeatable_single_choice_field_enhanced_first']);

        $controller->save($this->updatedBlockData());
        self::assertSame('dont_show', $this->connection->fetchOne(
            'SELECT basic_single_choice_field_default_first FROM ' . self::MAIN_TABLE . ' WHERE bID = ?',
            [$blockIdentifier],
        ));
        self::assertSame(['some_data2', 'example2'], $this->connection->fetchFirstColumn(
            'SELECT repeatable_single_choice_field_enhanced_first FROM ' . self::ENTRIES_TABLE
            . ' WHERE bID = ? ORDER BY position, id',
            [$blockIdentifier],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function updatedBlockData(): array
    {
        return [
            'basic_single_choice_field_default_first' => 'dont_show',
            'basic_single_choice_field_enhanced_first' => 'Show',
            'basic_single_choice_field_radio_list_first' => 'example',
            'basic_multiple_choice_field_default_first' => ['Show'],
            'basic_multiple_choice_field_enhanced_first' => ['2'],
            'basic_multiple_choice_field_checkbox_list_first' => ['some_data'],
            'entry' => [
                [
                    'repeatable_single_choice_field_default_first' => 'bbb',
                    'repeatable_single_choice_field_enhanced_first' => 'some_data2',
                ],
                [
                    'repeatable_single_choice_field_default_first' => 'aaa',
                    'repeatable_single_choice_field_enhanced_first' => 'example2',
                ],
            ],
        ];
    }
}
