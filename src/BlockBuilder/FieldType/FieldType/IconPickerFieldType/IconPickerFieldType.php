<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\IconPickerFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpIconPickerStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;

class IconPickerFieldType implements FieldTypeInterface
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::IconPicker;
    }

    public static function getHandle(): string
    {
        return 'icon_picker';
    }

    public static function getLabel(): string
    {
        return t('Icon Picker');
    }

    public static function getIcon(): string
    {
        return t('fab fa-redhat');
    }

    public static function createDtoFromArray(array $data): IconPickerFieldTypeDto
    {
        return new IconPickerFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpIconPickerStrategy::class;
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
