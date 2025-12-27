<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Number;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class NumberFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public bool $displayZeroValue,
        public string $defaultValue,
        public string $placeholder,
        public string $prefix,
        public string $suffix,
        public ?string $size,
        public ?string $step,
        public ?string $minimum,
        public ?string $maximum,
        public int $displayedDecimals,
        public ?string $displayedDecimalSeparator,
        public ?string $displayedThousandsSeparator,
    ) {
    }
}
