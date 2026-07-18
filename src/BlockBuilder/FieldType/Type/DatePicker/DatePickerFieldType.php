<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\DatePicker;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class DatePickerFieldType extends AbstractFieldType
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::DatePicker;
    }

    public static function getHandle(): string
    {
        return 'date_picker';
    }

    public static function getLabel(): string
    {
        return t('Date Picker');
    }

    public static function getIcon(): string
    {
        return t('fas fa-calendar-alt');
    }

    public static function getDefaultValues(): array
    {
        return [
            'datePickerPattern' => 'd.m.Y',
        ];
    }

    public static function createDtoFromArray(array $data): DatePickerFieldTypeDto
    {
        return new DatePickerFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            datePickerPattern: trim($data['datePickerPattern'] ?? ''),
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
