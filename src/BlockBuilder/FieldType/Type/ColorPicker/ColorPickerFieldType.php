<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\ColorPicker;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class ColorPickerFieldType extends AbstractFieldType
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::ColorPicker;
    }

    public static function getHandle(): string
    {
        return 'color_picker';
    }

    public static function getLabel(): string
    {
        return t('Color Picker');
    }

    public static function getIcon(): string
    {
        return t('fas fa-palette');
    }

    public static function getDefaultValues(): array
    {
        return [];
    }

    public static function createDtoFromArray(array $data): ColorPickerFieldTypeDto
    {
        return new ColorPickerFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [];
    }

    public function validate(array $data): array
    {
        return [];
    }
}
