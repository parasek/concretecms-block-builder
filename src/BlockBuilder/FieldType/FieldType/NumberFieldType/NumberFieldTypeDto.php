<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\NumberFieldType;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class NumberFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public ?string $label,
        public ?string $handle,
        public bool $required,
        public ?string $helpText,
        public ?string $numberSize,
        public ?string $numberStep,
        public ?string $numberMin,
        public ?string $numberMax,
        public int $numberDisplayedDecimals,
        public ?string $numberDisplayedDecimalSeparator,
        public ?string $numberDisplayedThousandsSeparator,
    ) {
    }
}
