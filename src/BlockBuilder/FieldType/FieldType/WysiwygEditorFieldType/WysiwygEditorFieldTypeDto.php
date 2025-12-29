<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\WysiwygEditorFieldType;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class WysiwygEditorFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public ?string $label,
        public ?string $handle,
        public bool $required,
        public ?string $helpText,
        public ?int $wysiwygEditorHeight,
        public ?string $wysiwygCustomConfig,
    ) {
    }
}
