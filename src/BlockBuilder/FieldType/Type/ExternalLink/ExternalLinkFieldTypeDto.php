<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\ExternalLink;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class ExternalLinkFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public bool $externalLinkShowEndingField,
        public bool $externalLinkShowTextField,
        public bool $externalLinkShowTitleField,
        public bool $externalLinkShowNewWindowField,
        public bool $externalLinkShowNoFollowField,
    ) {
    }
}
