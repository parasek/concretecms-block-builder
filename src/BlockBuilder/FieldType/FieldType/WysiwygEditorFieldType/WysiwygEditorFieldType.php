<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\WysiwygEditorFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpWysiwygEditorStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldTypeTrait;

class WysiwygEditorFieldType implements FieldTypeInterface
{
    use FieldTypeTrait;

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
        return [];
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
            wysiwygCustomConfig: $data['wysiwygCustomConfig'] ?? '',
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpWysiwygEditorStrategy::class;
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'wysiwygEditorHeight|invalid_number' => t('Invalid entry in one of "WYSIWYG Editor/Height" fields, should be a number between %s and %s or empty (%s).', 40, 2000, $context->getTabName()),
            'wysiwygCustomConfig|invalid_json' => t('Invalid JSON in one of "WYSIWYG Editor/Custom editor config" fields, should be a valid JSON (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        // Height
        $height = $data['wysiwygEditorHeight'] ?? '';
        $customConfig = $data['wysiwygCustomConfig'] ?? '';

        if ($height !== '') {
            $isInvalid = !ctype_digit((string) $height) || $height < 40 || $height > 2000;

            if ($isInvalid) {
                $errors[] = 'wysiwygEditorHeight|invalid_number';
            }
        }

        // Custom config
        if (!empty($customConfig) && json_decode($customConfig) === null && json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'wysiwygCustomConfig|invalid_json';
        }

        return $errors;
    }
}
