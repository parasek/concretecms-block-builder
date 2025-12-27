<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Image;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class ImageFieldTypeDto implements FieldTypeDtoInterface
{
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public bool $showAltTextField,
        public bool $createThumbnailImage,
        public ?int $thumbnailWidth,
        public ?int $thumbnailHeight,
        public bool $thumbnailCrop,
        public bool $thumbnailEditable,
        public bool $createFullscreenImage,
        public ?int $fullscreenWidth,
        public ?int $fullscreenHeight,
        public bool $fullscreenCrop,
        public bool $fullscreenEditable,
    ) {
    }
}
