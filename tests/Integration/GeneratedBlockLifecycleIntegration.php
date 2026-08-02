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
use JsonException;

final class GeneratedBlockLifecycleIntegration extends ConcreteIntegrationTestCase
{
    private const string BLOCK_HANDLE = 'block_builder_integration_fixture';
    private const string MAIN_TABLE = 'btBlockBuilderIntegrationFixture';
    private const string ENTRIES_TABLE = 'btBlockBuilderIntegrationFixtureEntries';

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
    public function testGenerateInstallPersistRebuildDuplicateDeleteAndUninstallLifecycle(): void
    {
        $blockPath = $this->environmentGuard->blocksRoot . DIRECTORY_SEPARATOR . self::BLOCK_HANDLE;
        self::assertDirectoryDoesNotExist($blockPath, 'A disposable integration run must start from a clean block directory.');
        self::assertNull(BlockType::getByHandle(self::BLOCK_HANDLE), 'A disposable integration run must start without the fixture block type.');
        $this->authorizeGeneratedBlockFixtureCleanup(self::BLOCK_HANDLE);

        $fixtureData = $this->loadFixtureData();
        $config = $this->application->make(BlockConfigDtoFactory::class)->fromGenerationArray($fixtureData);
        $manifestFactory = $this->application->make(BlockGenerationManifestFactory::class);
        $generator = $this->application->make(BlockGenerator::class);

        $creationResult = $generator->generate(
            $config,
            $manifestFactory->create(
                config: $config,
                shouldRebuildBlock: false,
                blockIconPublicPath: null,
                customBlockIcon: null,
            ),
        );

        self::assertSame(PostGenerationBlockStateEnum::CreatedAndInstalled, $creationResult->postGenerationBlockState);
        self::assertFileExists($blockPath . DIRECTORY_SEPARATOR . 'controller.php');
        self::assertFileExists($blockPath . DIRECTORY_SEPARATOR . 'db.xml');
        self::assertFileExists($blockPath . DIRECTORY_SEPARATOR . 'config-bb.json');

        $schemaManager = $this->connection->getSchemaManager();
        self::assertTrue($schemaManager->tablesExist([self::MAIN_TABLE, self::ENTRIES_TABLE]));

        $blockType = BlockType::getByHandle(self::BLOCK_HANDLE);
        self::assertInstanceOf(BlockTypeEntity::class, $blockType);

        $block = $blockType->add($this->initialBlockData());
        self::assertInstanceOf(Block::class, $block);
        $blockIdentifier = $block->getBlockID();
        self::assertGreaterThan(0, $blockIdentifier);
        self::assertSame('show', $this->connection->fetchOne(
            'SELECT basic_single_choice_field_default_first FROM ' . self::MAIN_TABLE . ' WHERE bID = ?',
            [$blockIdentifier],
        ));
        self::assertSame(2, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . self::ENTRIES_TABLE . ' WHERE bID = ?',
            [$blockIdentifier],
        ));

        $controller = $block->getController();
        self::assertInstanceOf(BlockController::class, $controller);
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

        $duplicateIdentifier = $this->createEmptyCoreBlockRecord($blockType);
        $controller->duplicate($duplicateIdentifier);
        self::assertSame('dont_show', $this->connection->fetchOne(
            'SELECT basic_single_choice_field_default_first FROM ' . self::MAIN_TABLE . ' WHERE bID = ?',
            [$duplicateIdentifier],
        ));
        self::assertSame(2, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . self::ENTRIES_TABLE . ' WHERE bID = ?',
            [$duplicateIdentifier],
        ));

        $retainedDirectory = $blockPath . DIRECTORY_SEPARATOR . 'templates';
        self::assertTrue(mkdir($retainedDirectory, 0777, true));
        $retainedPath = $retainedDirectory . DIRECTORY_SEPARATOR . 'retained.txt';
        self::assertNotFalse(file_put_contents($retainedPath, 'retained through rebuild'));

        $rebuildResult = $generator->generate(
            $config,
            $manifestFactory->create(
                config: $config,
                shouldRebuildBlock: true,
                blockIconPublicPath: null,
                customBlockIcon: null,
            ),
        );
        self::assertSame(PostGenerationBlockStateEnum::Rebuilt, $rebuildResult->postGenerationBlockState);
        self::assertSame('retained through rebuild', file_get_contents($retainedPath));
        self::assertSame(2, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . self::ENTRIES_TABLE . ' WHERE bID = ?',
            [$blockIdentifier],
        ), 'Refreshing the generated schema must retain existing repeatable data.');

        $duplicateBlock = Block::getByID($duplicateIdentifier);
        self::assertInstanceOf(Block::class, $duplicateBlock);
        $duplicateBlock->deleteBlock(true);
        $block->deleteBlock(true);
        self::assertSame(0, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . self::MAIN_TABLE . ' WHERE bID IN (?, ?)',
            [$blockIdentifier, $duplicateIdentifier],
        ));
        self::assertSame(0, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . self::ENTRIES_TABLE . ' WHERE bID IN (?, ?)',
            [$blockIdentifier, $duplicateIdentifier],
        ));

        $uninstalledName = $this->application->make(BlockTypeUninstaller::class)->uninstall($blockType->getBlockTypeID());
        self::assertSame('Block Builder integration fixture', $uninstalledName);
        self::assertNull(BlockType::getByHandle(self::BLOCK_HANDLE));

        $this->application->make(BlockDirectoryRemover::class)->remove(self::BLOCK_HANDLE);
        self::assertDirectoryDoesNotExist($blockPath);
    }

    /**
     * @return array<string, mixed>
     * @throws JsonException
     */
    private function loadFixtureData(): array
    {
        $fixturePath = $this->environmentGuard->packageRoot
            . DIRECTORY_SEPARATOR . 'predefined_configs'
            . DIRECTORY_SEPARATOR . 'single_multiple_choice_field.json';
        $contents = file_get_contents($fixturePath);
        self::assertNotFalse($contents);

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $data['blockName'] = 'Block Builder integration fixture';
        $data['blockHandle'] = self::BLOCK_HANDLE;
        $data['blockDescription'] = 'Disposable lifecycle fixture.';
        $data['installBlock'] = true;
        $data['excludedFromRemoval'] = ['templates'];

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function initialBlockData(): array
    {
        return [
            'basic_single_choice_field_default_first' => 'show',
            'basic_single_choice_field_enhanced_first' => 'Show',
            'basic_single_choice_field_radio_list_first' => 'example',
            'basic_multiple_choice_field_default_first' => ['Show'],
            'basic_multiple_choice_field_enhanced_first' => ['2'],
            'basic_multiple_choice_field_checkbox_list_first' => ['some_data'],
            'entry' => [
                [
                    'repeatable_single_choice_field_default_first' => 'aaa',
                    'repeatable_single_choice_field_enhanced_first' => 'example2',
                ],
                [
                    'repeatable_single_choice_field_default_first' => 'bbb',
                    'repeatable_single_choice_field_enhanced_first' => 'some_data2',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function updatedBlockData(): array
    {
        $data = $this->initialBlockData();
        $data['basic_single_choice_field_default_first'] = 'dont_show';
        $data['entry'] = array_reverse($data['entry']);

        return $data;
    }

    private function createEmptyCoreBlockRecord(BlockTypeEntity $blockType): int
    {
        $now = date('Y-m-d H:i:s');
        $this->connection->insert('Blocks', [
            'bName' => 'Integration duplicate',
            'bDateAdded' => $now,
            'bDateModified' => $now,
            'bIsActive' => 1,
            'btID' => $blockType->getBlockTypeID(),
            'uID' => USER_SUPER_ID,
        ]);

        return (int) $this->connection->lastInsertId();
    }

}
