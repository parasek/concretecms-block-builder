<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\LinkFromSitemapFieldType;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class LinkFromSitemapFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public ?string $label,
        public ?string $handle,
        public bool $required,
        public ?string $helpText,
        public bool $linkFromSitemapShowEndingField,
        public bool $linkFromSitemapShowTextField,
        public bool $linkFromSitemapShowTitleField,
        public bool$linkFromSitemapShowNewWindowField,
        public bool $linkFromSitemapShowNoFollowField,
    ) {
    }
}
