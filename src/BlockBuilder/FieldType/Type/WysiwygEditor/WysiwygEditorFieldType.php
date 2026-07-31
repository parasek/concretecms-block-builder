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
            'wysiwygEditorHeight' => '',
            'minHeight' => '',
            'wysiwygCustomConfig' => '',
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
            wysiwygEditorHeight: !empty($data['wysiwygEditorHeight']) ? (int) $data['wysiwygEditorHeight'] : null,
            minHeight: !empty($data['minHeight']) ? (int) $data['minHeight'] : null,
            wysiwygCustomConfig: $data['wysiwygCustomConfig'] ?? '',
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'wysiwygEditorHeight|invalid_number' => t('Invalid entry in one of "WYSIWYG Editor/Maximum height" fields, should be a number between %s and %s or empty (%s).', self::MINIMUM_EDITOR_HEIGHT, self::MAXIMUM_EDITOR_HEIGHT, $context->getTabName()),
            'minHeight|invalid_number' => t('Invalid entry in one of "WYSIWYG Editor/Minimum height" fields, should be a number between %s and %s or empty (%s).', self::MINIMUM_EDITOR_HEIGHT, self::MAXIMUM_EDITOR_HEIGHT, $context->getTabName()),
            'minHeight|greater_than_maximum' => t('The minimum height of a WYSIWYG Editor cannot be greater than its maximum height (%s).', $context->getTabName()),
            'wysiwygCustomConfig|invalid_json' => t('Invalid entry in one of "WYSIWYG Editor/Custom editor configuration" fields, should be a valid JSON object (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        $maximumHeight = $data['wysiwygEditorHeight'] ?? '';
        $minimumHeight = $data['minHeight'] ?? '';
        $customConfig = $data['wysiwygCustomConfig'] ?? '';

        $maximumHeightIsValid = $maximumHeight === ''
            || IntegerValueValidator::isInRange(
                $maximumHeight,
                self::MINIMUM_EDITOR_HEIGHT,
                self::MAXIMUM_EDITOR_HEIGHT,
            );
        if (!$maximumHeightIsValid) {
            $errors[] = 'wysiwygEditorHeight|invalid_number';
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
                $errors[] = 'wysiwygCustomConfig|invalid_json';
            } else {
                try {
                    $decodedConfig = json_decode($customConfig, false, 512, JSON_THROW_ON_ERROR);
                    if (!is_object($decodedConfig)) {
                        $errors[] = 'wysiwygCustomConfig|invalid_json';
                    }
                } catch (JsonException) {
                    $errors[] = 'wysiwygCustomConfig|invalid_json';
                }
            }
        }

        return $errors;
    }
}
