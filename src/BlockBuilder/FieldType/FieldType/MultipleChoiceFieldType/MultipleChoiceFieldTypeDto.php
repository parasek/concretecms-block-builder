<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\MultipleChoiceFieldType;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class MultipleChoiceFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public ?string $label,
        public ?string $handle,
        public bool $required,
        public ?string $helpText,
        public ?string $selectMultipleType,
        public ?string $selectMultipleDefaultValue,
        public ?string $selectMultipleListGenerationMethod,
        public ?string $selectMultipleOptions,
        public ?string $selectMultipleCustomCode,
    ) {
    }
}
