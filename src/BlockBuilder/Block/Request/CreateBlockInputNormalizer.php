<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Request;

use BlockBuilder\Block\Validation\ValidationFeedbackBuilder;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\FieldTypeRegistry;

final class CreateBlockInputNormalizer
{
    private const int MAX_FIELDS_PER_COLLECTION = 100;
    private const int MAX_OPTIONS_PER_FIELD = 1_000;
    private const int MAX_DEFAULT_STRING_LENGTH = 10_000;
    private const int MAX_LONG_TEXT_LENGTH = 100_000;
    private const int MAX_CUSTOM_CODE_LENGTH = 500_000;

    private const array BOOLEAN_FIELDS = [
        'cacheBlockRecord',
        'cacheBlockOutput',
        'cacheBlockOutputOnPost',
        'cacheBlockOutputOnEditMode',
        'cacheBlockOutputForRegisteredUsers',
        'supportSavingNullValues',
        'ignorePageThemeGridFrameworkContainer',
        'installBlock',
        'entriesAsFirstTab',
        'highlightMultiElementFields',
        'rebuildBlock',
    ];

    private const array INTEGER_FIELDS = [
        'blockWidth',
        'blockHeight',
        'cacheBlockOutputLifetime',
        'maxNumberOfEntries',
    ];

    private const array STRING_FIELDS = [
        'blockName',
        'blockHandle',
        'blockDescription',
        'blockTypeSet',
        'blockIcon',
        'messageBasicTab',
        'messageEntriesTab',
        'registerViewAssetsCustomCode',
        'viewCustomCode',
        'customControllerMethods',
        'excludedFromRemoval',
        'basicLabel',
        'entriesLabel',
        'settingsLabel',
        'addAtTheTopLabel',
        'addAtTheBottomLabel',
        'copyLastEntryLabel',
        'collapseAllLabel',
        'expandAllLabel',
        'removeAllLabel',
        'disableSmoothScrollLabel',
        'keepAddedEntryCollapsedLabel',
        'noEntriesFoundLabel',
        'maxNumberOfEntriesLabel',
        'removeEntryLabel',
        'duplicateEntryLabel',
        'duplicateEntryAndAddAtTheEndLabel',
        'areYouSureLabel',
        'requiredFieldsLabel',
        'urlEndingLabel',
        'urlEndingHelpTextLabel',
        'textLabel',
        'titleLabel',
        'altTextLabel',
        'linkFromSitemapLabel',
        'linkFromFileManagerLabel',
        'externalLinkLabel',
        'showAdditionalFieldsLabel',
        'hideAdditionalFieldsLabel',
        'newWindowLabel',
        'noFollowLabel',
        'yesLabel',
        'noLabel',
        'overrideThumbnailDimensionsLabel',
        'overrideFullscreenImageDimensionsLabel',
        'widthLabel',
        'heightLabel',
        'cropLabel',
        'pxLabel',
        'nothingSelectedLabel',
        'noResultsMatchedLabel',
        'selectAllLabel',
        'deselectAllLabel',
    ];

    private const array FIELD_INTEGER_PROPERTIES = [
        'numberDisplayedDecimals',
        'minHeight',
        'maxHeight',
        'htmlEditorHeight',
        'imageThumbnailWidth',
        'imageThumbnailHeight',
        'imageFullscreenWidth',
        'imageFullscreenHeight',
    ];

    private const array IGNORED_CONTROL_FIELDS = [
        'ccm_token',
        'sourceAction',
        'buildBlock',
        'addEntry',
        'scroll',
    ];

    private const array FIELD_BOOLEAN_PROPERTIES = [
        'required',
        'titleSource',
        'displayZeroValue',
        'selectAddEmptyOption',
        'imageShowAltTextField',
        'imageCreateThumbnailImage',
        'imageThumbnailCrop',
        'imageThumbnailEditable',
        'imageCreateFullscreenImage',
        'imageFullscreenCrop',
        'imageFullscreenEditable',
        'linkFromSitemapShowEndingField',
        'linkFromSitemapShowTextField',
        'linkFromSitemapShowTitleField',
        'linkFromSitemapShowNewWindowField',
        'linkFromSitemapShowNoFollowField',
        'linkFromFileManagerShowEndingField',
        'linkFromFileManagerShowTextField',
        'linkFromFileManagerShowTitleField',
        'linkFromFileManagerShowNewWindowField',
        'linkFromFileManagerShowNoFollowField',
        'externalLinkShowEndingField',
        'externalLinkShowTextField',
        'externalLinkShowTitleField',
        'externalLinkShowNewWindowField',
        'externalLinkShowNoFollowField',
    ];

    private const array LONG_TEXT_FIELDS = [
        'blockDescription',
        'excludedFromRemoval',
        'messageBasicTab',
        'messageEntriesTab',
    ];

    private const array CUSTOM_CODE_FIELDS = [
        'registerViewAssetsCustomCode',
        'viewCustomCode',
        'customControllerMethods',
    ];

    private const array FIELD_LONG_TEXT_PROPERTIES = [
        'selectOptions',
        'selectMultipleOptions',
        'customConfig',
    ];

    private const array FIELD_OPTION_LIST_PROPERTIES = [
        'selectOptions',
        'selectMultipleOptions',
    ];

    private const array FIELD_CUSTOM_CODE_PROPERTIES = [
        'selectCustomCode',
        'selectMultipleCustomCode',
    ];

    public function __construct(private readonly FieldTypeRegistry $fieldTypeRegistry)
    {
    }

    public function normalize(array $data): CreateBlockInputNormalizationResult
    {
        $feedback = new ValidationFeedbackBuilder();
        $normalizedData = [];

        // Check if request contains an unsupported fields
        $allowedTopLevelFields = array_merge(
            self::STRING_FIELDS,
            self::BOOLEAN_FIELDS,
            self::INTEGER_FIELDS,
            self::IGNORED_CONTROL_FIELDS,
            array_map(
                static fn(FieldTypeContextEnum $context): string => $context->value,
                FieldTypeContextEnum::cases(),
            ),
        );

        foreach (array_keys($data) as $propertyName) {
            if (!is_string($propertyName) || !in_array($propertyName, $allowedTopLevelFields, true)) {
                $feedback->addError(
                    error: t('The request contains an unsupported field.'),
                    field: null,
                    tab: null,
                );
            }
        }

        // Normalize top level fields
        foreach (self::STRING_FIELDS as $propertyName) {
            $normalizedData[$propertyName] = $this->normalizeString(
                value: $data[$propertyName] ?? '',
                propertyName: $propertyName,
                maximumLength: $this->getTopLevelMaximumLength($propertyName),
                feedback: $feedback,
            );
        }

        foreach (self::BOOLEAN_FIELDS as $propertyName) {
            $normalizedData[$propertyName] = $this->normalizeBoolean(
                value: $data[$propertyName] ?? null,
                propertyName: $propertyName,
                feedback: $feedback,
                missingValue: $propertyName === 'rebuildBlock' ? '0' : '',
            );
        }

        foreach (self::INTEGER_FIELDS as $propertyName) {
            $normalizedData[$propertyName] = $this->normalizeIntegerString(
                value: $data[$propertyName] ?? '',
                propertyName: $propertyName,
                feedback: $feedback,
            );
        }

        // Normalize "basic"/"entries" fields
        foreach (FieldTypeContextEnum::cases() as $context) {
            $normalizedData[$context->value] = $this->normalizeFieldCollection(
                value: $data[$context->value] ?? [],
                context: $context,
                feedback: $feedback,
            );
        }

        return new CreateBlockInputNormalizationResult(
            data: $normalizedData,
            feedback: $feedback->build(),
        );
    }

    private function normalizeFieldCollection(
        mixed $value,
        FieldTypeContextEnum $context,
        ValidationFeedbackBuilder $feedback,
    ): array {
        if (!is_array($value)) {
            $feedback->addError(
                error: t('The field collection "%s" has an invalid structure.', $context->value),
                field: null,
                tab: $context->getTabHandle(),
            );

            return [];
        }

        foreach (array_keys($value) as $fieldIndex) {
            if (!is_int($fieldIndex) && (!is_string($fieldIndex) || !ctype_digit($fieldIndex))) {
                $feedback->addError(
                    error: t('The field collection "%s" must use numeric field indexes.', $context->value),
                    field: null,
                    tab: $context->getTabHandle(),
                );
                break;
            }
        }

        if (count($value) > self::MAX_FIELDS_PER_COLLECTION) {
            $feedback->addError(
                error: t('The field collection "%s" may contain at most %s fields.', $context->value, self::MAX_FIELDS_PER_COLLECTION),
                field: null,
                tab: $context->getTabHandle(),
            );
            $value = array_slice($value, 0, self::MAX_FIELDS_PER_COLLECTION);
        }

        $normalizedFields = [];
        foreach (array_values($value) as $fieldIndex => $fieldData) {
            if (!is_array($fieldData)) {
                $feedback->addError(
                    error: t('A field in the "%s" collection has an invalid structure.', $context->value),
                    field: null,
                    tab: $context->getTabHandle(),
                );
                continue;
            }

            $fieldTypeValue = $fieldData['fieldType'] ?? null;
            if (!is_string($fieldTypeValue)) {
                $this->addInvalidFieldPropertyFeedback($feedback, $context, $fieldIndex, 'fieldType');
                continue;
            }

            $fieldType = $this->fieldTypeRegistry->findByHandle($fieldTypeValue);
            if ($fieldType === null) {
                $feedback->addError(
                    error: t('A field in the "%s" collection uses an unsupported field type.', $context->value),
                    field: sprintf('%s[%s][fieldType]', $context->value, $fieldIndex),
                    tab: $context->getTabHandle(),
                );
                continue;
            }

            $allowedProperties = $fieldType::getProperties();
            foreach (array_keys($fieldData) as $propertyName) {
                if (!is_string($propertyName) || !in_array($propertyName, $allowedProperties, true)) {
                    $feedback->addError(
                        error: t('A field in the "%s" collection contains an unsupported property.', $context->value),
                        field: null,
                        tab: $context->getTabHandle(),
                    );
                }
            }

            $normalizedField = ['fieldType' => $fieldType::getHandle()];
            foreach ($allowedProperties as $propertyName) {
                if ($propertyName === 'fieldType') {
                    continue;
                }

                $propertyValue = $fieldData[$propertyName] ?? null;
                if (in_array($propertyName, self::FIELD_BOOLEAN_PROPERTIES, true)) {
                    $normalizedField[$propertyName] = $this->normalizeBoolean(
                        value: $propertyValue,
                        propertyName: $propertyName,
                        feedback: $feedback,
                        fieldPath: sprintf('%s[%s][%s]', $context->value, $fieldIndex, $propertyName),
                        tab: $context->getTabHandle(),
                    );
                    continue;
                }

                if (in_array($propertyName, self::FIELD_INTEGER_PROPERTIES, true)) {
                    $normalizedField[$propertyName] = $this->normalizeIntegerString(
                        value: $propertyValue ?? '',
                        propertyName: $propertyName,
                        feedback: $feedback,
                        fieldPath: sprintf('%s[%s][%s]', $context->value, $fieldIndex, $propertyName),
                        tab: $context->getTabHandle(),
                    );
                    continue;
                }

                $normalizedField[$propertyName] = $this->normalizeFieldString(
                    value: $propertyValue ?? '',
                    propertyName: $propertyName,
                    maximumLength: $this->getFieldPropertyMaximumLength($propertyName),
                    feedback: $feedback,
                    fieldPath: sprintf('%s[%s][%s]', $context->value, $fieldIndex, $propertyName),
                    tab: $context->getTabHandle(),
                );
            }

            $normalizedFields[] = $normalizedField;
        }

        return $normalizedFields;
    }

    private function normalizeString(
        mixed $value,
        string $propertyName,
        int $maximumLength,
        ValidationFeedbackBuilder $feedback,
        ?string $fieldPath = null,
        ?string $tab = null,
    ): string {
        if (!is_string($value)) {
            $this->addInvalidScalarFeedback($feedback, $propertyName, $fieldPath, $tab);

            return '';
        }

        if (mb_strlen($value) <= $maximumLength) {
            return $value;
        }

        $feedback->addError(
            error: t('The field "%s" exceeds the maximum allowed length of %s characters.', $propertyName, $maximumLength),
            field: $fieldPath ?? $propertyName,
            tab: $tab,
        );

        return mb_substr($value, 0, $maximumLength);
    }

    private function normalizeBoolean(
        mixed $value,
        string $propertyName,
        ValidationFeedbackBuilder $feedback,
        ?string $fieldPath = null,
        ?string $tab = null,
        string $missingValue = '0',
    ): string {
        if ($value === null || $value === '') {
            return $missingValue;
        }

        if (in_array($value, ['1', 1, true], true)) {
            return '1';
        }

        if (in_array($value, ['0', 0, false], true)) {
            return '0';
        }

        $this->addInvalidScalarFeedback($feedback, $propertyName, $fieldPath, $tab);

        return '0';
    }

    private function normalizeIntegerString(
        mixed $value,
        string $propertyName,
        ValidationFeedbackBuilder $feedback,
        ?string $fieldPath = null,
        ?string $tab = null,
    ): string {
        if (is_int($value) && $value >= 0) {
            return (string) $value;
        }

        if (is_string($value) && ($value === '' || ctype_digit($value)) && strlen($value) <= 20) {
            return $value;
        }

        $this->addInvalidScalarFeedback($feedback, $propertyName, $fieldPath, $tab);

        return '';
    }

    private function normalizeFieldString(
        mixed $value,
        string $propertyName,
        int $maximumLength,
        ValidationFeedbackBuilder $feedback,
        string $fieldPath,
        string $tab,
    ): string {
        $normalizedValue = $this->normalizeString(
            value: $value,
            propertyName: $propertyName,
            maximumLength: $maximumLength,
            feedback: $feedback,
            fieldPath: $fieldPath,
            tab: $tab,
        );

        if (!in_array($propertyName, self::FIELD_OPTION_LIST_PROPERTIES, true)) {
            return $normalizedValue;
        }

        $lines = preg_split('/\R/u', $normalizedValue);
        if (!is_array($lines) || count($lines) <= self::MAX_OPTIONS_PER_FIELD) {
            return $normalizedValue;
        }

        $feedback->addError(
            error: t('The field "%s" may contain at most %s options.', $propertyName, self::MAX_OPTIONS_PER_FIELD),
            field: $fieldPath,
            tab: $tab,
        );

        return implode(PHP_EOL, array_slice($lines, 0, self::MAX_OPTIONS_PER_FIELD));
    }

    private function addInvalidScalarFeedback(
        ValidationFeedbackBuilder $feedback,
        string $propertyName,
        ?string $fieldPath,
        ?string $tab,
    ): void {
        $feedback->addError(
            error: t('The field "%s" has an invalid value type.', $propertyName),
            field: $fieldPath ?? $propertyName,
            tab: $tab,
        );
    }

    private function addInvalidFieldPropertyFeedback(
        ValidationFeedbackBuilder $feedback,
        FieldTypeContextEnum $context,
        int $fieldIndex,
        string $propertyName,
    ): void {
        $feedback->addError(
            error: t('A field in the "%s" collection has an invalid "%s" value.', $context->value, $propertyName),
            field: sprintf('%s[%s][%s]', $context->value, $fieldIndex, $propertyName),
            tab: $context->getTabHandle(),
        );
    }

    private function getTopLevelMaximumLength(string $propertyName): int
    {
        if (in_array($propertyName, self::CUSTOM_CODE_FIELDS, true)) {
            return self::MAX_CUSTOM_CODE_LENGTH;
        }

        if (in_array($propertyName, self::LONG_TEXT_FIELDS, true)) {
            return self::MAX_LONG_TEXT_LENGTH;
        }

        return self::MAX_DEFAULT_STRING_LENGTH;
    }

    private function getFieldPropertyMaximumLength(string $propertyName): int
    {
        if (in_array($propertyName, self::FIELD_CUSTOM_CODE_PROPERTIES, true)) {
            return self::MAX_CUSTOM_CODE_LENGTH;
        }

        if (in_array($propertyName, self::FIELD_LONG_TEXT_PROPERTIES, true)) {
            return self::MAX_LONG_TEXT_LENGTH;
        }

        return self::MAX_DEFAULT_STRING_LENGTH;
    }
}
