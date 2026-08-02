<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\FieldType;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Exception\ConflictingFieldPropertyAliasException;
use BlockBuilder\FieldType\Factory\FieldTypeDtoFactory;
use BlockBuilder\FieldType\FieldTypeRegistry;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

final class LegacyFieldPropertyAliasTest extends BlockBuilderTestCase
{
    /**
     * @dataProvider legacyFieldPropertyAliasProvider
     */
    public function testLegacyPropertyMapsToExpectedCanonicalPropertyAndValue(
        FieldTypeEnum $fieldType,
        string $legacyProperty,
        string $canonicalProperty,
        mixed $legacyValue,
        mixed $expectedCanonicalValue,
    ): void {
        $fieldTypeRegistry = $this->getService(FieldTypeRegistry::class);
        $registeredFieldType = $fieldTypeRegistry->get($fieldType);
        self::assertSame(
            $canonicalProperty,
            $registeredFieldType::getLegacyPropertyAliases()[$legacyProperty] ?? null,
        );

        $fieldData = $this->createFieldData($fieldType);
        unset($fieldData[$canonicalProperty]);
        $fieldData[$legacyProperty] = $legacyValue;

        $field = (new FieldTypeDtoFactory($fieldTypeRegistry))->fromArray($fieldData);
        self::assertTrue(property_exists($field, $canonicalProperty));
        self::assertSame($expectedCanonicalValue, $field->{$canonicalProperty});

        $canonicalData = json_decode(
            json_encode($field, JSON_THROW_ON_ERROR),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertArrayHasKey($canonicalProperty, $canonicalData);
        self::assertArrayNotHasKey($legacyProperty, $canonicalData);
        self::assertSame($expectedCanonicalValue, $canonicalData[$canonicalProperty]);
    }

    public function testProviderCoversEveryRegisteredLegacyPropertyAlias(): void
    {
        $providedAliases = [];
        foreach (self::legacyFieldPropertyAliasProvider() as $testCase) {
            [$fieldType, $legacyProperty, $canonicalProperty] = $testCase;
            $providedAliases[$fieldType->value][$legacyProperty] = $canonicalProperty;
        }
        ksort($providedAliases);
        foreach (array_keys($providedAliases) as $fieldTypeHandle) {
            ksort($providedAliases[$fieldTypeHandle]);
        }

        $registeredAliases = [];
        foreach ($this->getService(FieldTypeRegistry::class)->all() as $registeredFieldType) {
            $aliases = $registeredFieldType::getLegacyPropertyAliases();
            if ($aliases === []) {
                continue;
            }

            ksort($aliases);
            $registeredAliases[$registeredFieldType::getFieldType()->value] = $aliases;
        }
        ksort($registeredAliases);

        self::assertCount(49, self::legacyFieldPropertyAliasProvider());
        self::assertSame($registeredAliases, $providedAliases);
    }

    public function testMatchingLegacyAndCanonicalPropertiesAreAccepted(): void
    {
        $fieldData = $this->createFieldData(FieldTypeEnum::Number);
        $fieldData['numberSize'] = '12.4';
        $fieldData['size'] = '12.4';

        $field = (new FieldTypeDtoFactory($this->getService(FieldTypeRegistry::class)))->fromArray($fieldData);
        self::assertSame('12.4', $field->size);

        $canonicalData = json_decode(
            json_encode($field, JSON_THROW_ON_ERROR),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertSame('12.4', $canonicalData['size']);
        self::assertArrayNotHasKey('numberSize', $canonicalData);
    }

    public function testConflictingLegacyAndCanonicalPropertiesAreRejected(): void
    {
        $fieldData = $this->createFieldData(FieldTypeEnum::Number);
        $fieldData['numberSize'] = '12.4';
        $fieldData['size'] = '99.9';

        $this->expectException(ConflictingFieldPropertyAliasException::class);
        $this->expectExceptionMessage(
            'Field type "number" contains conflicting values for legacy property "numberSize" and canonical property "size".',
        );

        (new FieldTypeDtoFactory($this->getService(FieldTypeRegistry::class)))->fromArray($fieldData);
    }

    /**
     * @return array<string, array{FieldTypeEnum, string, string, mixed, mixed}>
     */
    public static function legacyFieldPropertyAliasProvider(): array
    {
        return [
            'number size' => [FieldTypeEnum::Number, 'numberSize', 'size', '12.4', '12.4'],
            'number step' => [FieldTypeEnum::Number, 'numberStep', 'step', '0.125', '0.125'],
            'number minimum' => [FieldTypeEnum::Number, 'numberMin', 'minimum', '-321.5', '-321.5'],
            'number maximum' => [FieldTypeEnum::Number, 'numberMax', 'maximum', '654.75', '654.75'],
            'number displayed decimals' => [FieldTypeEnum::Number, 'numberDisplayedDecimals', 'displayedDecimals', '7', 7],
            'number decimal separator' => [FieldTypeEnum::Number, 'numberDisplayedDecimalSeparator', 'displayedDecimalSeparator', '.', '.'],
            'number thousands separator' => [FieldTypeEnum::Number, 'numberDisplayedThousandsSeparator', 'displayedThousandsSeparator', '_', '_'],
            'textarea height' => [FieldTypeEnum::Textarea, 'textareaHeight', 'maxHeight', '321', 321],
            'wysiwyg height' => [FieldTypeEnum::WysiwygEditor, 'wysiwygEditorHeight', 'maxHeight', '654', 654],
            'wysiwyg custom config' => [FieldTypeEnum::WysiwygEditor, 'wysiwygCustomConfig', 'customConfig', '{"toolbar":["bold"]}', '{"toolbar":["bold"]}'],
            'single choice display type' => [FieldTypeEnum::SingleChoice, 'selectType', 'displayType', 'radio_list', 'radio_list'],
            'single choice empty option' => [FieldTypeEnum::SingleChoice, 'selectAddEmptyOption', 'addEmptyOption', 'yes', true],
            'single choice default value' => [FieldTypeEnum::SingleChoice, 'selectDefaultValue', 'defaultValue', 'second', 'second'],
            'single choice generation method' => [FieldTypeEnum::SingleChoice, 'selectListGenerationMethod', 'listGenerationMethod', 'custom_code', 'custom_code'],
            'single choice options' => [FieldTypeEnum::SingleChoice, 'selectOptions', 'options', "first :: First\nsecond :: Second", "first :: First\nsecond :: Second"],
            'single choice custom code' => [FieldTypeEnum::SingleChoice, 'selectCustomCode', 'customCode', 'return [];', 'return [];'],
            'multiple choice display type' => [FieldTypeEnum::MultipleChoice, 'selectMultipleType', 'displayType', 'checkbox_list', 'checkbox_list'],
            'multiple choice default value' => [FieldTypeEnum::MultipleChoice, 'selectMultipleDefaultValue', 'defaultValue', 'first|second', 'first|second'],
            'multiple choice generation method' => [FieldTypeEnum::MultipleChoice, 'selectMultipleListGenerationMethod', 'listGenerationMethod', 'custom_code', 'custom_code'],
            'multiple choice options' => [FieldTypeEnum::MultipleChoice, 'selectMultipleOptions', 'options', "first :: First\nsecond :: Second", "first :: First\nsecond :: Second"],
            'multiple choice custom code' => [FieldTypeEnum::MultipleChoice, 'selectMultipleCustomCode', 'customCode', 'return [];', 'return [];'],
            'sitemap ending field' => [FieldTypeEnum::LinkFromSitemap, 'linkFromSitemapShowEndingField', 'showEndingField', '1', true],
            'sitemap text field' => [FieldTypeEnum::LinkFromSitemap, 'linkFromSitemapShowTextField', 'showTextField', '1', true],
            'sitemap title field' => [FieldTypeEnum::LinkFromSitemap, 'linkFromSitemapShowTitleField', 'showTitleField', '1', true],
            'sitemap new-window field' => [FieldTypeEnum::LinkFromSitemap, 'linkFromSitemapShowNewWindowField', 'showNewWindowField', '1', true],
            'sitemap nofollow field' => [FieldTypeEnum::LinkFromSitemap, 'linkFromSitemapShowNoFollowField', 'showNoFollowField', '1', true],
            'file link ending field' => [FieldTypeEnum::LinkFromFileManager, 'linkFromFileManagerShowEndingField', 'showEndingField', '1', true],
            'file link text field' => [FieldTypeEnum::LinkFromFileManager, 'linkFromFileManagerShowTextField', 'showTextField', '1', true],
            'file link title field' => [FieldTypeEnum::LinkFromFileManager, 'linkFromFileManagerShowTitleField', 'showTitleField', '1', true],
            'file link new-window field' => [FieldTypeEnum::LinkFromFileManager, 'linkFromFileManagerShowNewWindowField', 'showNewWindowField', '1', true],
            'file link nofollow field' => [FieldTypeEnum::LinkFromFileManager, 'linkFromFileManagerShowNoFollowField', 'showNoFollowField', '1', true],
            'external link ending field' => [FieldTypeEnum::ExternalLink, 'externalLinkShowEndingField', 'showEndingField', '1', true],
            'external link text field' => [FieldTypeEnum::ExternalLink, 'externalLinkShowTextField', 'showTextField', '1', true],
            'external link title field' => [FieldTypeEnum::ExternalLink, 'externalLinkShowTitleField', 'showTitleField', '1', true],
            'external link new-window field' => [FieldTypeEnum::ExternalLink, 'externalLinkShowNewWindowField', 'showNewWindowField', '1', true],
            'external link nofollow field' => [FieldTypeEnum::ExternalLink, 'externalLinkShowNoFollowField', 'showNoFollowField', '1', true],
            'image alt-text field' => [FieldTypeEnum::Image, 'imageShowAltTextField', 'showAltTextField', '1', true],
            'image thumbnail creation' => [FieldTypeEnum::Image, 'imageCreateThumbnailImage', 'createThumbnailImage', '1', true],
            'image thumbnail width' => [FieldTypeEnum::Image, 'imageThumbnailWidth', 'thumbnailWidth', '481', 481],
            'image thumbnail height' => [FieldTypeEnum::Image, 'imageThumbnailHeight', 'thumbnailHeight', '271', 271],
            'image thumbnail crop' => [FieldTypeEnum::Image, 'imageThumbnailCrop', 'thumbnailCrop', '1', true],
            'image thumbnail editable' => [FieldTypeEnum::Image, 'imageThumbnailEditable', 'thumbnailEditable', '1', true],
            'image fullscreen creation' => [FieldTypeEnum::Image, 'imageCreateFullscreenImage', 'createFullscreenImage', '1', true],
            'image fullscreen width' => [FieldTypeEnum::Image, 'imageFullscreenWidth', 'fullscreenWidth', '1921', 1921],
            'image fullscreen height' => [FieldTypeEnum::Image, 'imageFullscreenHeight', 'fullscreenHeight', '1081', 1081],
            'image fullscreen crop' => [FieldTypeEnum::Image, 'imageFullscreenCrop', 'fullscreenCrop', '1', true],
            'image fullscreen editable' => [FieldTypeEnum::Image, 'imageFullscreenEditable', 'fullscreenEditable', '1', true],
            'html editor height' => [FieldTypeEnum::HtmlEditor, 'htmlEditorHeight', 'height', '512', 512],
            'date-picker pattern' => [FieldTypeEnum::DatePicker, 'datePickerPattern', 'datePattern', 'Y-m-d', 'Y-m-d'],
        ];
    }

    private function createFieldData(FieldTypeEnum $fieldType): array
    {
        $registeredFieldType = $this->getService(FieldTypeRegistry::class)->get($fieldType);

        return [
            'fieldType' => $fieldType->value,
            'label' => 'Legacy alias test field',
            'handle' => 'legacyAliasTestField',
            'required' => false,
            'helpText' => '',
            ...$registeredFieldType::getDefaultValues(),
        ];
    }
}
