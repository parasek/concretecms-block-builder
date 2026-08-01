<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\IconPicker;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class IconPickerFieldType extends AbstractFieldType
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

    public static function getDefaultValues(): array
    {
        return ['defaultValue' => ''];
    }

    public static function createDtoFromArray(array $data): IconPickerFieldTypeDto
    {
        return new IconPickerFieldTypeDto(
            fieldType: self::getEnum(),
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
            'defaultValue|invalid_icon' => t('Some "Icon Picker/Default value" fields contain invalid icon classes (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $defaultValue = $data['defaultValue'] ?? '';
        if (!is_scalar($defaultValue)) {
            return ['defaultValue|invalid_icon'];
        }

        $icon = trim((string) $defaultValue);
        if ($icon === '') {
            return [];
        }

        return strlen($icon) <= 255
            && preg_match('/^[A-Za-z][A-Za-z0-9_-]*(?:\s+[A-Za-z][A-Za-z0-9_-]*)*$/D', $icon) === 1
                ? []
                : ['defaultValue|invalid_icon'];
    }
}
