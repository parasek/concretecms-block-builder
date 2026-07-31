<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\WysiwygEditor;

use BlockBuilder\Block\Validation\IntegerValueValidator;
use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use JsonException;

class WysiwygEditorFieldType extends AbstractFieldType
{
    private const int MINIMUM_EDITOR_HEIGHT = 40;
    private const int MAXIMUM_EDITOR_HEIGHT = 2000;

    protected const array LEGACY_PROPERTY_ALIASES = [
        'wysiwygEditorHeight' => 'maxHeight',
        'wysiwygCustomConfig' => 'customConfig',
    ];

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::WysiwygEditor;
    }

    public static function getHandle(): string
    {
        return 'wysiwyg_editor';
    }

    public static function getLabel(): string
    {
        return t('WYSIWYG Editor');
    }

    public static function getIcon(): string
    {
        return t('far fa-window-maximize');
    }

    public static function getDefaultValues(): array
    {
        return [
            'maxHeight' => '',
            'minHeight' => '',
            'customConfig' => '',
        ];
    }

    public static function createDtoFromArray(array $data): WysiwygEditorFieldTypeDto
    {
        return new WysiwygEditorFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            maxHeight: !empty($data['maxHeight']) ? (int) $data['maxHeight'] : null,
            minHeight: !empty($data['minHeight']) ? (int) $data['minHeight'] : null,
            customConfig: $data['customConfig'] ?? '',
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'maxHeight|invalid_number' => t('Invalid entry in one of "WYSIWYG Editor/Maximum height" fields, should be a number between %s and %s or empty (%s).', self::MINIMUM_EDITOR_HEIGHT, self::MAXIMUM_EDITOR_HEIGHT, $context->getTabName()),
            'minHeight|invalid_number' => t('Invalid entry in one of "WYSIWYG Editor/Minimum height" fields, should be a number between %s and %s or empty (%s).', self::MINIMUM_EDITOR_HEIGHT, self::MAXIMUM_EDITOR_HEIGHT, $context->getTabName()),
            'minHeight|greater_than_maximum' => t('The minimum height of a WYSIWYG Editor cannot be greater than its maximum height (%s).', $context->getTabName()),
            'customConfig|invalid_json' => t('Invalid entry in one of "WYSIWYG Editor/Custom editor configuration" fields, should be a valid JSON object (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        $maximumHeight = $data['maxHeight'] ?? '';
        $minimumHeight = $data['minHeight'] ?? '';
        $customConfig = $data['customConfig'] ?? '';

        $maximumHeightIsValid = $maximumHeight === ''
            || IntegerValueValidator::isInRange(
                $maximumHeight,
                self::MINIMUM_EDITOR_HEIGHT,
                self::MAXIMUM_EDITOR_HEIGHT,
            );
        if (!$maximumHeightIsValid) {
            $errors[] = 'maxHeight|invalid_number';
        }

        $minimumHeightIsValid = $minimumHeight === ''
            || IntegerValueValidator::isInRange(
                $minimumHeight,
                self::MINIMUM_EDITOR_HEIGHT,
                self::MAXIMUM_EDITOR_HEIGHT,
            );
        if (!$minimumHeightIsValid) {
            $errors[] = 'minHeight|invalid_number';
        }

        if (
            $minimumHeight !== ''
            && $maximumHeight !== ''
            && $minimumHeightIsValid
            && $maximumHeightIsValid
            && (int) $minimumHeight > (int) $maximumHeight
        ) {
            $errors[] = 'minHeight|greater_than_maximum';
        }

        if ($customConfig !== '') {
            if (!is_string($customConfig)) {
                $errors[] = 'customConfig|invalid_json';
            } else {
                try {
                    $decodedConfig = json_decode($customConfig, false, 512, JSON_THROW_ON_ERROR);
                    if (!is_object($decodedConfig)) {
                        $errors[] = 'customConfig|invalid_json';
                    }
                } catch (JsonException) {
                    $errors[] = 'customConfig|invalid_json';
                }
            }
        }

        return $errors;
    }
}
