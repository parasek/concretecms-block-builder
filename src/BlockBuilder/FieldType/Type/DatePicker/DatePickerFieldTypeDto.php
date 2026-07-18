<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\DatePicker;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class DatePickerFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public ?string $datePickerPattern,
    ) {
    }
}
