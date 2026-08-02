<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\DatePicker;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class DatePickerFieldType extends AbstractFieldType
{
    protected const array LEGACY_PROPERTY_ALIASES = [
        'datePickerPattern' => 'datePattern',
    ];

    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::DatePicker;
    }

    public static function getLabel(): string
    {
        return t('Date Picker');
    }

    public static function getIcon(): string
    {
        return 'fas fa-calendar-alt';
    }

    public static function getDefaultValues(): array
    {
        return [
            'datePattern' => '',
            'minDate' => '',
            'maxDate' => '',
            'attachTimeSelector' => 0,
            'minuteInterval' => 1,
        ];
    }

    public static function createDtoFromArray(array $data): DatePickerFieldTypeDto
    {
        return new DatePickerFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            datePattern: trim((string) ($data['datePattern'] ?? '')),
            minDate: trim((string) ($data['minDate'] ?? '')),
            maxDate: trim((string) ($data['maxDate'] ?? '')),
            attachTimeSelector: !empty($data['attachTimeSelector']),
            minuteInterval: (int) ($data['minuteInterval'] ?? 1),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'minDate|invalid' => t('Some "Date Picker/Minimum date" fields must contain a valid date (%s).', $context->getTabName()),
            'maxDate|invalid' => t('Some "Date Picker/Maximum date" fields must contain a valid date (%s).', $context->getTabName()),
            'dateRange|invalid' => t('Some "Date Picker/Minimum date" fields must not be later than their maximum date (%s).', $context->getTabName()),
            'minuteInterval|invalid' => t('Some "Date Picker/Minute interval" fields must contain a positive integer that divides 60 without a remainder (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];
        $minDate = $data['minDate'] ?? '';
        $maxDate = $data['maxDate'] ?? '';
        $minDateIsValid = $this->isValidOptionalDate($minDate);
        $maxDateIsValid = $this->isValidOptionalDate($maxDate);

        if (!$minDateIsValid) {
            $errors[] = 'minDate|invalid';
        }
        if (!$maxDateIsValid) {
            $errors[] = 'maxDate|invalid';
        }
        if (
            $minDateIsValid
            && $maxDateIsValid
            && $minDate !== ''
            && $maxDate !== ''
            && (string) $minDate > (string) $maxDate
        ) {
            $errors[] = 'dateRange|invalid';
        }

        $minuteInterval = $data['minuteInterval'] ?? 1;
        if (
            !is_scalar($minuteInterval)
            || preg_match('/^[1-9]\d*$/', (string) $minuteInterval) !== 1
            || (int) $minuteInterval > 60
            || 60 % (int) $minuteInterval !== 0
        ) {
            $errors[] = 'minuteInterval|invalid';
        }

        return $errors;
    }

    private function isValidOptionalDate(mixed $date): bool
    {
        if (!is_scalar($date)) {
            return false;
        }

        $date = (string) $date;
        if ($date === '') {
            return true;
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsedDate !== false && $parsedDate->format('Y-m-d') === $date;
    }
}
