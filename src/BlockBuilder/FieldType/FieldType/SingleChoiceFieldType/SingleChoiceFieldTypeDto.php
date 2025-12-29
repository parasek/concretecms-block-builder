<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\SingleChoiceFieldType;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class SingleChoiceFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public ?string $label,
        public ?string $handle,
        public bool $required,
        public ?string $helpText,
        public ?string $selectType,
        public bool $selectAddEmptyOption,
        public ?string $selectDefaultValue,
        public ?string $selectListGenerationMethod,
        public ?string $selectOptions,
        public ?string $selectCustomCode,
    ) {
    }
}
