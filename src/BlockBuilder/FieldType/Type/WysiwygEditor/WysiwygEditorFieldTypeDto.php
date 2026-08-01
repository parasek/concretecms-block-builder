<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\WysiwygEditor;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class WysiwygEditorFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public string $defaultValue,
        public ?int $minHeight,
        public ?int $maxHeight,
        public ?string $customConfig,
    ) {
    }
}
