<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\LinkFromFileManagerFieldType;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class LinkFromFileManagerFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public ?string $label,
        public ?string $handle,
        public bool $required,
        public ?string $helpText,
        public bool $linkFromFileManagerShowEndingField,
        public bool $linkFromFileManagerShowTextField,
        public bool $linkFromFileManagerShowTitleField,
        public bool $linkFromFileManagerShowNewWindowField,
        public bool $linkFromFileManagerShowNoFollowField,
    ) {
    }
}
