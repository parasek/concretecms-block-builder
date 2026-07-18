<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Textarea;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class TextareaFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public bool $titleSource,
        public ?int $textareaHeight,
    ) {
    }
}
