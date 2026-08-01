<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Text;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class TextFieldTypeDto implements FieldTypeDtoInterface
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
        public ?int $minimumLength,
        public int $maximumLength,
        public string $prefix,
        public string $suffix,
        public bool $titleSource,
    ) {
    }
}
