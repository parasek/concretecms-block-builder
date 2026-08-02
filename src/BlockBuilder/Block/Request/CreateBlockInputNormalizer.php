<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Request;

use BlockBuilder\Block\Validation\BlockConfigLimits;
use BlockBuilder\Block\Validation\ValidationFeedbackBuilder;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\FieldTypeRegistry;

final class CreateBlockInputNormalizer
{
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
        'displayedDecimals',
        'height',
        'minHeight',
        'maxHeight',
        'minuteInterval',
        'minimumLength',
        'maximumLength',
        'thumbnailWidth',
        'thumbnailHeight',
        'fullscreenWidth',
        'fullscreenHeight',
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
        'attachTimeSelector',
        'addEmptyOption',
        'showAltTextField',
        'createThumbnailImage',
        'thumbnailCrop',
        'thumbnailEditable',
        'createFullscreenImage',
        'fullscreenCrop',
        'fullscreenEditable',
        'showEndingField',
        'showTextField',
        'showTitleField',
        'showNewWindowField',
        'showNoFollowField',
    ];

    private const array FIELD_OPTION_LIST_PROPERTIES = [
        'options',
    ];

    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly BlockFormFieldLabelProvider $fieldLabelProvider,
    ) {
    }

    public function normalize(array $data): CreateBlockInputNormalizationResult
    {
        $feedback = new ValidationFeedbackBuilder();
        $normalizedData = [];

        // Check whether the request contains unsupported fields.
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
                maximumLength: BlockConfigLimits::getTopLevelStringMaximum($propertyName),
                feedback: $feedback,
            );
        }

        foreach (self::BOOLEAN_FIELDS as $propertyName) {
            $normalizedData[$propertyName] = $this->normalizeBooleanFormValue(
                value: $data[$propertyName] ?? null,
                propertyName: $propertyName,
                feedback: $feedback,
                valueWhenMissing: $propertyName === 'rebuildBlock' ? '0' : '',
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

        if (count($value) > BlockConfigLimits::MAX_FIELDS_PER_COLLECTION) {
            $feedback->addError(
                error: t('The field collection "%s" may contain at most %s fields.', $context->value, BlockConfigLimits::MAX_FIELDS_PER_COLLECTION),
                field: null,
                tab: $context->getTabHandle(),
            );
            $value = array_slice($value, 0, BlockConfigLimits::MAX_FIELDS_PER_COLLECTION);
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

            $normalizedField = ['fieldType' => $fieldType::getFieldType()->value];
            foreach ($allowedProperties as $propertyName) {
                if ($propertyName === 'fieldType') {
                    continue;
                }

                $propertyValue = $fieldData[$propertyName] ?? null;
                if ($propertyName === 'icons') {
                    $normalizedField[$propertyName] = $this->normalizeSvgIcons(
                        value: $propertyValue ?? [],
                        context: $context,
                        fieldIndex: $fieldIndex,
                        feedback: $feedback,
                    );
                    continue;
                }

                if (in_array($propertyName, self::FIELD_BOOLEAN_PROPERTIES, true)) {
                    $normalizedField[$propertyName] = $this->normalizeBooleanFormValue(
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
                    maximumLength: BlockConfigLimits::getFieldStringMaximum($propertyName),
                    feedback: $feedback,
                    fieldPath: sprintf('%s[%s][%s]', $context->value, $fieldIndex, $propertyName),
                    tab: $context->getTabHandle(),
                );
            }

            $normalizedFields[] = $normalizedField;
        }

        return $normalizedFields;
    }

    /**
     * @return array<int, array{name: string, handle: string, svg: string}>
     */
    private function normalizeSvgIcons(
        mixed $value,
        FieldTypeContextEnum $context,
        int $fieldIndex,
        ValidationFeedbackBuilder $feedback,
    ): array {
        $fieldPath = sprintf('%s[%s][icons]', $context->value, $fieldIndex);
        if (!is_array($value)) {
            $this->addInvalidScalarFeedback($feedback, 'icons', $fieldPath, $context->getTabHandle());

            return [];
        }

        foreach (array_keys($value) as $iconIndex) {
            if (!is_int($iconIndex) && (!is_string($iconIndex) || !ctype_digit($iconIndex))) {
                $feedback->addError(
                    error: t('The field "%s" must use numeric icon indexes.', $this->fieldLabelProvider->getLabel('icons')),
                    field: $fieldPath,
                    tab: $context->getTabHandle(),
                );
                break;
            }
        }

        if (count($value) > BlockConfigLimits::MAX_SVG_ICONS_PER_FIELD) {
            $feedback->addError(
                error: t(
                    'The field "%s" may contain at most %s icons.',
                    $this->fieldLabelProvider->getLabel('icons'),
                    BlockConfigLimits::MAX_SVG_ICONS_PER_FIELD,
                ),
                field: $fieldPath,
                tab: $context->getTabHandle(),
            );
            $value = array_slice($value, 0, BlockConfigLimits::MAX_SVG_ICONS_PER_FIELD);
        }

        $normalizedIcons = [];
        foreach (array_values($value) as $iconIndex => $icon) {
            if (!is_array($icon)) {
                $this->addInvalidScalarFeedback($feedback, 'icons', $fieldPath, $context->getTabHandle());
                continue;
            }

            foreach (array_keys($icon) as $propertyName) {
                if (!is_string($propertyName) || !in_array($propertyName, ['name', 'handle', 'svg'], true)) {
                    $feedback->addError(
                        error: t('The field "%s" contains an unsupported icon property.', $this->fieldLabelProvider->getLabel('icons')),
                        field: $fieldPath,
                        tab: $context->getTabHandle(),
                    );
                }
            }

            $normalizedIcons[] = [
                'name' => $this->normalizeString(
                    value: $icon['name'] ?? '',
                    propertyName: 'svgIconName',
                    maximumLength: BlockConfigLimits::MAX_SVG_ICON_NAME_LENGTH,
                    feedback: $feedback,
                    fieldPath: sprintf('%s[%s][name]', $fieldPath, $iconIndex),
                    tab: $context->getTabHandle(),
                ),
                'handle' => $this->normalizeString(
                    value: $icon['handle'] ?? '',
                    propertyName: 'svgIconHandle',
                    maximumLength: BlockConfigLimits::MAX_SVG_ICON_HANDLE_LENGTH,
                    feedback: $feedback,
                    fieldPath: sprintf('%s[%s][handle]', $fieldPath, $iconIndex),
                    tab: $context->getTabHandle(),
                ),
                'svg' => $this->normalizeString(
                    value: $icon['svg'] ?? '',
                    propertyName: 'svgContent',
                    maximumLength: BlockConfigLimits::MAX_SVG_CONTENT_LENGTH,
                    feedback: $feedback,
                    fieldPath: sprintf('%s[%s][svg]', $fieldPath, $iconIndex),
                    tab: $context->getTabHandle(),
                ),
            ];
        }

        return $normalizedIcons;
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
            error: t(
                'The field "%s" exceeds the maximum allowed length of %s characters.',
                $this->fieldLabelProvider->getLabel($propertyName),
                $maximumLength,
            ),
            field: $fieldPath ?? $propertyName,
            tab: $tab,
        );

        return mb_substr($value, 0, $maximumLength);
    }

    private function normalizeBooleanFormValue(
        mixed $value,
        string $propertyName,
        ValidationFeedbackBuilder $feedback,
        ?string $fieldPath = null,
        ?string $tab = null,
        string $valueWhenMissing = '0',
    ): string {
        if ($value === null || $value === '') {
            return $valueWhenMissing;
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
        if (!is_array($lines) || count($lines) <= BlockConfigLimits::MAX_OPTIONS_PER_FIELD) {
            return $normalizedValue;
        }

        $feedback->addError(
            error: t(
                'The field "%s" may contain at most %s options.',
                $this->fieldLabelProvider->getLabel($propertyName),
                BlockConfigLimits::MAX_OPTIONS_PER_FIELD,
            ),
            field: $fieldPath,
            tab: $tab,
        );

        return implode(PHP_EOL, array_slice($lines, 0, BlockConfigLimits::MAX_OPTIONS_PER_FIELD));
    }

    private function addInvalidScalarFeedback(
        ValidationFeedbackBuilder $feedback,
        string $propertyName,
        ?string $fieldPath,
        ?string $tab,
    ): void {
        $feedback->addError(
            error: t(
                'The field "%s" has an invalid value type.',
                $this->fieldLabelProvider->getLabel($propertyName),
            ),
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
            error: t(
                'A field in the "%s" collection has an invalid "%s" value.',
                $context->value,
                $this->fieldLabelProvider->getLabel($propertyName),
            ),
            field: sprintf('%s[%s][%s]', $context->value, $fieldIndex, $propertyName),
            tab: $context->getTabHandle(),
        );
    }
}
