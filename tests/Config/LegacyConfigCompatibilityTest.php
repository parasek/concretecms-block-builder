<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Config;

use BlockBuilder\Block\Service\BlockConfigReader;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Exception\MalformedFieldDataException;
use BlockBuilder\FieldType\Type\DatePicker\DatePickerFieldTypeDto;
use BlockBuilder\FieldType\Type\Image\ImageFieldTypeDto;
use BlockBuilder\FieldType\Type\LinkFromSitemap\LinkFromSitemapFieldTypeDto;
use BlockBuilder\FieldType\Type\Number\NumberFieldTypeDto;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use ReflectionMethod;

final class LegacyConfigCompatibilityTest extends BlockBuilderTestCase
{
    public function testLegacyAllFieldsConfigLoadsAndNormalizes(): void
    {
        $legacyData = $this->loadJsonFixture('all-fields-2.8.1.json');
        self::assertSame('1', $legacyData['scroll']);
        $configPath = 'all_fields/config-bb.json';
        (new ReflectionMethod(BlockConfigReader::class, 'validateSchema'))->invoke(
            $this->getService(BlockConfigReader::class),
            $legacyData,
            $configPath,
        );
        (new ReflectionMethod(BlockConfigReader::class, 'validateFieldCollections'))->invoke(
            $this->getService(BlockConfigReader::class),
            $legacyData,
            $configPath,
        );
        $config = $this->createBlockConfigDtoFactory()->fromArray($legacyData);

        self::assertSame('2.8.1', $config->blockBuilderVersion);
        self::assertNull($config->concreteVersion);
        self::assertNull($config->phpVersion);
        self::assertTrue($config->installBlock);
        self::assertTrue($config->cacheBlockRecord);
        self::assertFalse($config->supportSavingNullValues);
        self::assertSame(['templates'], $config->excludedFromRemoval);
        self::assertCount(21, $config->basic);
        self::assertCount(21, $config->entries);

        $expectedLegacyTypes = [
            FieldTypeEnum::Text,
            FieldTypeEnum::Textarea,
            FieldTypeEnum::Number,
            FieldTypeEnum::WysiwygEditor,
            FieldTypeEnum::SingleChoice,
            FieldTypeEnum::MultipleChoice,
            FieldTypeEnum::FlexLink,
            FieldTypeEnum::LinkFromSitemap,
            FieldTypeEnum::LinkFromFileManager,
            FieldTypeEnum::ExternalLink,
            FieldTypeEnum::Image,
            FieldTypeEnum::Express,
            FieldTypeEnum::FileSet,
            FieldTypeEnum::HtmlEditor,
            FieldTypeEnum::DatePicker,
            FieldTypeEnum::ColorPicker,
            FieldTypeEnum::IconPicker,
        ];
        $actualTypes = array_values(array_unique(array_map(
            static fn(object $field): FieldTypeEnum => $field->fieldType,
            [...$config->basic, ...$config->entries],
        ), SORT_REGULAR));
        usort($expectedLegacyTypes, static fn(FieldTypeEnum $first, FieldTypeEnum $second): int => $first->value <=> $second->value);
        usort($actualTypes, static fn(FieldTypeEnum $first, FieldTypeEnum $second): int => $first->value <=> $second->value);
        self::assertSame($expectedLegacyTypes, $actualTypes);

        $number = $this->findField($config->basic, 'basicNumberFirst');
        self::assertInstanceOf(NumberFieldTypeDto::class, $number);
        self::assertSame('10.2', $number->size);
        self::assertSame('0.01', $number->step);
        self::assertSame('0', $number->minimum);
        self::assertSame('99999999.99', $number->maximum);

        $link = $this->findField($config->basic, 'basicLinkFromSitemapFirst');
        self::assertInstanceOf(LinkFromSitemapFieldTypeDto::class, $link);
        self::assertTrue($link->showEndingField);
        self::assertTrue($link->showTextField);
        self::assertTrue($link->showTitleField);
        self::assertTrue($link->showNewWindowField);
        self::assertTrue($link->showNoFollowField);

        $image = $this->findField($config->basic, 'basicImageFirst');
        self::assertInstanceOf(ImageFieldTypeDto::class, $image);
        self::assertSame(480, $image->thumbnailWidth);
        self::assertSame(1080, $image->fullscreenHeight);

        $date = $this->findField($config->basic, 'basicDatePickerFirst');
        self::assertInstanceOf(DatePickerFieldTypeDto::class, $date);
        self::assertSame('d.m.Y', $date->datePattern);
    }

    public function testCanonicalJsonSurvivesARoundTrip(): void
    {
        $blockConfigDtoFactory = $this->createBlockConfigDtoFactory();
        $config = $blockConfigDtoFactory->fromArray($this->loadJsonFixture('all-fields-2.8.1.json'));
        $canonicalJson = json_encode($config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $canonicalData = json_decode($canonicalJson, true, flags: JSON_THROW_ON_ERROR);
        $roundTrippedConfig = $blockConfigDtoFactory->fromArray($canonicalData);

        self::assertSame(
            $canonicalJson,
            json_encode($roundTrippedConfig, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
        self::assertArrayHasKey('blockBuilderVersion', $canonicalData);
        self::assertArrayNotHasKey('version', $canonicalData);
        self::assertArrayNotHasKey('fieldsDivider', $canonicalData);
        self::assertArrayNotHasKey('entryFieldsDivider', $canonicalData);
        self::assertArrayNotHasKey('scroll', $canonicalData);

        $canonicalNumber = $canonicalData['basic'][1];
        self::assertArrayHasKey('size', $canonicalNumber);
        self::assertArrayNotHasKey('numberSize', $canonicalNumber);
    }

    public function testUnknownFieldPropertiesAreRejectedAfterLegacyAliasesAreNormalized(): void
    {
        $legacyData = $this->loadJsonFixture('all-fields-2.8.1.json');
        $firstFieldIndex = array_key_first($legacyData['basic']);
        self::assertNotNull($firstFieldIndex);
        $legacyData['basic'][$firstFieldIndex]['require'] = true;

        $this->expectException(MalformedFieldDataException::class);
        $this->expectExceptionMessage('unsupported property "require"');

        $this->createBlockConfigDtoFactory()->fromArray($legacyData);
    }

    private function findField(array $fields, string $handle): object
    {
        foreach ($fields as $field) {
            if ($field->handle === $handle) {
                return $field;
            }
        }

        self::fail(sprintf('Field "%s" was not found.', $handle));
    }
}
