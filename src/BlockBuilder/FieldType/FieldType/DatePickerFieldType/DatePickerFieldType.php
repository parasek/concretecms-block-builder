<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\DatePickerFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpDatePickerStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;

class DatePickerFieldType implements FieldTypeInterface
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

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpDatePickerStrategy::class;
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
