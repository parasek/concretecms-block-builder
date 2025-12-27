<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\MultipleChoice;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class MultipleChoiceFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public ?string $displayType,
        public ?string $defaultValue,
        public ?string $listGenerationMethod,
        public ?string $options,
        public ?string $customCode,
    ) {
    }
}
