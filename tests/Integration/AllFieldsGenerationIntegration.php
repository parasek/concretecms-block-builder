<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Integration;

use BlockBuilder\Block\Factory\BlockConfigDtoFactory;
use BlockBuilder\Block\Service\BlockDirectoryRemover;
use BlockBuilder\Block\Service\BlockTypeUninstaller;
use BlockBuilder\BlockGenerator\BlockGenerationManifestFactory;
use BlockBuilder\BlockGenerator\BlockGenerator;
use BlockBuilder\BlockGenerator\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\Tests\Integration\Support\ConcreteIntegrationTestCase;
use Concrete\Core\Block\Block;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use JsonException;

final class AllFieldsGenerationIntegration extends ConcreteIntegrationTestCase
{
    private const string BLOCK_HANDLE = 'block_builder_integration_all_fields';
    private const string MAIN_TABLE = 'btBlockBuilderIntegrationAllFields';
    private const string ENTRIES_TABLE = 'btBlockBuilderIntegrationAllFieldsEntries';

    protected function tearDown(): void
    {
        try {
            $this->cleanGeneratedBlockFixture(self::BLOCK_HANDLE);
        } finally {
            parent::tearDown();
        }
    }

    /**
     * @throws JsonException
     */
    public function testCanonicalAllFieldsPresetInstallsRepresentativeSchema(): void
    {
        $blockPath = $this->environmentGuard->blocksRoot . DIRECTORY_SEPARATOR . self::BLOCK_HANDLE;
        self::assertDirectoryDoesNotExist($blockPath);
        self::assertNull(BlockType::getByHandle(self::BLOCK_HANDLE));
        $this->authorizeGeneratedBlockFixtureCleanup(self::BLOCK_HANDLE);

        $fixturePath = $this->environmentGuard->packageRoot
            . DIRECTORY_SEPARATOR . 'predefined_configs'
            . DIRECTORY_SEPARATOR . 'all_fields.json';
        $contents = file_get_contents($fixturePath);
        self::assertNotFalse($contents);
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $data['blockName'] = 'Block Builder integration all fields';
        $data['blockHandle'] = self::BLOCK_HANDLE;
        $data['blockDescription'] = 'Disposable all-fields schema fixture.';
        $data['installBlock'] = true;
        $data['excludedFromRemoval'] = [];

        $config = $this->application->make(BlockConfigDtoFactory::class)->fromGenerationArray($data);
        $manifestFactory = $this->application->make(BlockGenerationManifestFactory::class);
        $result = $this->application->make(BlockGenerator::class)->generate(
            $config,
            $manifestFactory->create(
                config: $config,
                shouldRebuildBlock: false,
                blockIconPublicPath: null,
                customBlockIcon: null,
            ),
        );

        self::assertSame(PostGenerationBlockStateEnum::CreatedAndInstalled, $result->postGenerationBlockState);
        $blockType = BlockType::getByHandle(self::BLOCK_HANDLE);
        self::assertInstanceOf(BlockTypeEntity::class, $blockType);

        $schemaManager = $this->connection->getSchemaManager();
        self::assertTrue($schemaManager->tablesExist([self::MAIN_TABLE, self::ENTRIES_TABLE]));

        $mainColumns = array_map(
            static fn(Column $column): string => $column->getName(),
            $schemaManager->listTableColumns(self::MAIN_TABLE),
        );
        foreach ([
            'bID',
            'basicTextFirst',
            'basicNumberFirst',
            'basicTextareaFirst',
            'basicFlexLinkFirst',
            'basicImageFirst',
            'basicImageFirst_data',
            'basicDatePickerFirst',
            'basicSvgIconPickerFirst',
        ] as $columnName) {
            self::assertContains($columnName, $mainColumns);
        }

        $entryColumns = array_map(
            static fn(Column $column): string => $column->getName(),
            $schemaManager->listTableColumns(self::ENTRIES_TABLE),
        );
        foreach ([
            'id',
            'bID',
            'position',
            'repeatableTextFirst',
            'repeatableFlexLinkFirst',
            'repeatableImageFirst',
            'repeatableSvgIconPickerFirst',
        ] as $columnName) {
            self::assertContains($columnName, $entryColumns);
        }

        $entryIndexes = $schemaManager->listTableIndexes(self::ENTRIES_TABLE);
        $positionIndex = array_values(array_filter(
            $entryIndexes,
            static fn(Index $index): bool => strtolower($index->getName()) === 'bid',
        ));
        self::assertCount(1, $positionIndex);
        self::assertSame(['bid', 'position'], array_map('strtolower', $positionIndex[0]->getColumns()));

        $block = $blockType->add([
            'basicTextFirst' => 'Integration text',
            'basicNumberFirst' => '42.5',
            'basicTextareaFirst' => "Integration\ntextarea",
            'basicWysiwygEditorFirst' => '<p>Integration rich text</p>',
            'basicSingleChoiceFieldDefaultFirst' => 'show',
            'basicSingleChoiceFieldEnhancedFirst' => 'Show',
            'basicSingleChoiceFieldRadioListFirst' => 'example',
            'basicHtmlEditorFirst' => '<strong>Integration HTML</strong>',
            'basicColorPickerFirst' => '#123456',
            'basicIconPickerFirst' => 'fas fa-star',
            'basicSvgIconPickerFirst' => 'square',
            'entry' => [[
                'repeatableTextFirst' => 'Repeatable integration text',
                'repeatableNumberFirst' => '7.25',
                'repeatableTextareaFirst' => "Repeatable\ntextarea",
                'repeatableWysiwygEditorFirst' => '<p>Repeatable rich text</p>',
                'repeatableSingleChoiceFieldDefaultFirst' => 'bbb',
                'repeatableSingleChoiceFieldEnhancedFirst' => 'some_data2',
                'repeatableSingleChoiceFieldRadioListFirst' => 'Show',
                'repeatableHtmlEditorFirst' => '<em>Repeatable HTML</em>',
                'repeatableColorPickerFirst' => '#654321',
                'repeatableIconPickerFirst' => 'fas fa-circle',
                'repeatableSvgIconPickerFirst' => 'square',
            ]],
        ]);
        self::assertInstanceOf(Block::class, $block);
        $blockIdentifier = $block->getBlockID();
        self::assertGreaterThan(0, $blockIdentifier);

        $mainRow = $this->connection->fetchAssociative(
            'SELECT basicTextFirst, basicNumberFirst, basicTextareaFirst, basicWysiwygEditorFirst,'
            . ' basicSingleChoiceFieldDefaultFirst, basicSingleChoiceFieldEnhancedFirst,'
            . ' basicSingleChoiceFieldRadioListFirst, basicHtmlEditorFirst, basicColorPickerFirst,'
            . ' basicIconPickerFirst, basicSvgIconPickerFirst'
            . ' FROM ' . self::MAIN_TABLE . ' WHERE bID = ?',
            [$blockIdentifier],
        );
        self::assertIsArray($mainRow);
        self::assertSame('Integration text', $mainRow['basicTextFirst']);
        self::assertSame(42.5, (float) $mainRow['basicNumberFirst']);
        self::assertSame("Integration\ntextarea", $mainRow['basicTextareaFirst']);
        self::assertSame('<p>Integration rich text</p>', $mainRow['basicWysiwygEditorFirst']);
        self::assertSame('show', $mainRow['basicSingleChoiceFieldDefaultFirst']);
        self::assertSame('Show', $mainRow['basicSingleChoiceFieldEnhancedFirst']);
        self::assertSame('example', $mainRow['basicSingleChoiceFieldRadioListFirst']);
        self::assertSame('<strong>Integration HTML</strong>', $mainRow['basicHtmlEditorFirst']);
        self::assertSame('#123456', $mainRow['basicColorPickerFirst']);
        self::assertSame('fas fa-star', $mainRow['basicIconPickerFirst']);
        self::assertSame('square', $mainRow['basicSvgIconPickerFirst']);

        $entryRow = $this->connection->fetchAssociative(
            'SELECT position, repeatableTextFirst, repeatableNumberFirst, repeatableTextareaFirst,'
            . ' repeatableWysiwygEditorFirst, repeatableSingleChoiceFieldDefaultFirst,'
            . ' repeatableSingleChoiceFieldEnhancedFirst, repeatableSingleChoiceFieldRadioListFirst,'
            . ' repeatableHtmlEditorFirst, repeatableColorPickerFirst, repeatableIconPickerFirst,'
            . ' repeatableSvgIconPickerFirst'
            . ' FROM ' . self::ENTRIES_TABLE . ' WHERE bID = ?',
            [$blockIdentifier],
        );
        self::assertIsArray($entryRow);
        self::assertSame(1, (int) $entryRow['position']);
        self::assertSame('Repeatable integration text', $entryRow['repeatableTextFirst']);
        self::assertSame(7.25, (float) $entryRow['repeatableNumberFirst']);
        self::assertSame("Repeatable\ntextarea", $entryRow['repeatableTextareaFirst']);
        self::assertSame('<p>Repeatable rich text</p>', $entryRow['repeatableWysiwygEditorFirst']);
        self::assertSame('bbb', $entryRow['repeatableSingleChoiceFieldDefaultFirst']);
        self::assertSame('some_data2', $entryRow['repeatableSingleChoiceFieldEnhancedFirst']);
        self::assertSame('Show', $entryRow['repeatableSingleChoiceFieldRadioListFirst']);
        self::assertSame('<em>Repeatable HTML</em>', $entryRow['repeatableHtmlEditorFirst']);
        self::assertSame('#654321', $entryRow['repeatableColorPickerFirst']);
        self::assertSame('fas fa-circle', $entryRow['repeatableIconPickerFirst']);
        self::assertSame('square', $entryRow['repeatableSvgIconPickerFirst']);

        $controller = $block->getController();
        self::assertInstanceOf(BlockController::class, $controller);
        $controller->view();
        $viewData = $controller->getSets();
        self::assertSame('Integration text', $viewData['basicTextFirst']);
        self::assertSame("Integration\ntextarea", $viewData['basicTextareaFirst']);
        self::assertSame('<strong>Integration HTML</strong>', $viewData['basicHtmlEditorFirst']);
        self::assertSame('#123456', $viewData['basicColorPickerFirst']);
        self::assertSame('square', $viewData['basicSvgIconPickerFirst']);
        self::assertIsArray($viewData['entries']);
        self::assertCount(1, $viewData['entries']);
        self::assertSame('Repeatable integration text', $viewData['entries'][0]['repeatableTextFirst']);
        self::assertSame('#654321', $viewData['entries'][0]['repeatableColorPickerFirst']);
        self::assertSame('square', $viewData['entries'][0]['repeatableSvgIconPickerFirst']);

        $block->deleteBlock(true);
        self::assertSame(0, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . self::MAIN_TABLE . ' WHERE bID = ?',
            [$blockIdentifier],
        ));
        self::assertSame(0, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . self::ENTRIES_TABLE . ' WHERE bID = ?',
            [$blockIdentifier],
        ));

        $uninstalledName = $this->application->make(BlockTypeUninstaller::class)->uninstall($blockType->getBlockTypeID());
        self::assertSame('Block Builder integration all fields', $uninstalledName);
        self::assertNull(BlockType::getByHandle(self::BLOCK_HANDLE));

        $this->application->make(BlockDirectoryRemover::class)->remove(self::BLOCK_HANDLE);
        self::assertDirectoryDoesNotExist($blockPath);
    }
}
