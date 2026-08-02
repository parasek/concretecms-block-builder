<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\ColorPicker;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class ColorPickerFieldType extends AbstractFieldType
{
    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::ColorPicker;
    }

    public static function getLabel(): string
    {
        return t('Color Picker');
    }

    public static function getIcon(): string
    {
        return 'fas fa-palette';
    }

    public static function getDefaultValues(): array
    {
        return ['defaultValue' => ''];
    }

    public static function createDtoFromArray(array $data): ColorPickerFieldTypeDto
    {
        return new ColorPickerFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            defaultValue: trim((string) ($data['defaultValue'] ?? '')),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'defaultValue|invalid_color' => t('Some "Color Picker/Default value" fields contain an invalid color (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $defaultValue = $data['defaultValue'] ?? '';
        if (!is_scalar($defaultValue)) {
            return ['defaultValue|invalid_color'];
        }

        $color = trim((string) $defaultValue);
        if ($color === '') {
            return [];
        }
        if (strlen($color) > 255) {
            return ['defaultValue|invalid_color'];
        }
        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/iD', $color) === 1) {
            return [];
        }
        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/iD', $color, $matches) === 1) {
            return (int) $matches[1] <= 255 && (int) $matches[2] <= 255 && (int) $matches[3] <= 255
                ? []
                : ['defaultValue|invalid_color'];
        }
        if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*((?:0|1)(?:\.\d+)?|\.\d+)\s*\)$/iD', $color, $matches) === 1) {
            return (int) $matches[1] <= 255
                && (int) $matches[2] <= 255
                && (int) $matches[3] <= 255
                && (float) $matches[4] >= 0.0
                && (float) $matches[4] <= 1.0
                    ? []
                    : ['defaultValue|invalid_color'];
        }

        return ['defaultValue|invalid_color'];
    }
}
