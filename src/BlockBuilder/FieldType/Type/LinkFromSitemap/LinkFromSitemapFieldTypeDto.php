<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\LinkFromSitemap;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class LinkFromSitemapFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public bool $showEndingField,
        public bool $showTextField,
        public bool $showTitleField,
        public bool $showNewWindowField,
        public bool $showNoFollowField,
    ) {
    }
}
