<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\BlockGenerator\BlockGenerationManifestFactory;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Test type: Generation manifest factory unit test.
 *
 * Verifies that configuration values become the expected file paths, class and table names,
 * lifecycle flags, and icon metadata, and that unsafe block handles are rejected.
 */
final class BlockGenerationManifestFactoryTest extends BlockBuilderTestCase
{
    /**
     * Verifies that a block configuration produces the expected names, paths, tables, flags, and icon data.
     */
    public function testManifestDerivesNamesPathsTablesAndFlagsFromConfiguration(): void
    {
        $config = $this->withInstallBlock($this->createAllFieldTypesConfig(), true);
        $iconPublicPath = DIRECTORY_SEPARATOR
            . DIRNAME_PACKAGES
            . DIRECTORY_SEPARATOR
            . 'block_builder'
            . DIRECTORY_SEPARATOR
            . 'generator_files'
            . DIRECTORY_SEPARATOR
            . FILENAME_BLOCK_ICON;
        $uploadedIcon = new UploadedFile(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'icon.png',
            FILENAME_BLOCK_ICON,
            'image/png',
            null,
            true,
        );

        $manifest = (new BlockGenerationManifestFactory())->create(
            $config,
            true,
            $iconPublicPath,
            $uploadedIcon,
        );

        $expectedPublicPath = DIRECTORY_SEPARATOR
            . DIRNAME_APPLICATION
            . DIRECTORY_SEPARATOR
            . DIRNAME_BLOCKS
            . DIRECTORY_SEPARATOR
            . 'all_field_types_test';
        self::assertTrue($manifest->shouldInstallBlock);
        self::assertTrue($manifest->shouldRebuildBlock);
        self::assertSame('AllFieldTypesTest', $manifest->blockHandlePascalCase);
        self::assertSame('all-field-types-test', $manifest->blockHandleKebabCase);
        self::assertSame($expectedPublicPath, $manifest->blockPublicPath);
        self::assertSame(DIR_BASE . $expectedPublicPath, $manifest->blockPath);
        self::assertSame($iconPublicPath, $manifest->blockIconPublicPath);
        self::assertSame(DIR_BASE . $iconPublicPath, $manifest->blockIconPath);
        self::assertSame($uploadedIcon, $manifest->customBlockIcon);
        self::assertSame('btAllFieldTypesTest', $manifest->databaseTableName);
        self::assertSame('btAllFieldTypesTestEntries', $manifest->entriesDatabaseTableName);
    }

    /**
     * Verifies that disabled lifecycle flags stay false and an absent icon remains absent in the manifest.
     */
    public function testManifestPreservesFalseFlagsAndMissingIcons(): void
    {
        $config = $this->withInstallBlock($this->createAllFieldTypesConfig(), false);

        $manifest = (new BlockGenerationManifestFactory())->create($config, false, null, null);

        self::assertFalse($manifest->shouldInstallBlock);
        self::assertFalse($manifest->shouldRebuildBlock);
        self::assertNull($manifest->blockIconPath);
        self::assertNull($manifest->blockIconPublicPath);
        self::assertNull($manifest->customBlockIcon);
    }

    /**
     * Verifies that unsafe block handles are rejected before any manifest values are derived.
     *
     * @dataProvider invalidHandleProvider
     */
    public function testInvalidHandleIsRejectedBeforeManifestDerivation(string $blockHandle): void
    {
        $config = $this->withBlockHandle($this->createAllFieldTypesConfig(), $blockHandle);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('invalid block handle "%s"', $blockHandle));

        (new BlockGenerationManifestFactory())->create($config, false, null, null);
    }

    public function invalidHandleProvider(): array
    {
        return [
            'too short' => ['ab'],
            'too long' => [str_repeat('a', 51)],
            'uppercase' => ['Invalid_handle'],
            'number' => ['invalid1'],
            'hyphen' => ['invalid-handle'],
            'leading underscore' => ['_invalid'],
            'trailing underscore' => ['invalid_'],
            'consecutive underscores' => ['invalid__handle'],
        ];
    }

    private function withInstallBlock(BlockConfigDto $config, bool $installBlock): BlockConfigDto
    {
        $properties = get_object_vars($config);
        $properties['installBlock'] = $installBlock;

        return new BlockConfigDto(...$properties);
    }

    private function withBlockHandle(BlockConfigDto $config, string $blockHandle): BlockConfigDto
    {
        $properties = get_object_vars($config);
        $properties['blockHandle'] = $blockHandle;

        return new BlockConfigDto(...$properties);
    }
}
