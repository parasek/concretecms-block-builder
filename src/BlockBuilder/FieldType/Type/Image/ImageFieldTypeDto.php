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
        public bool $imageShowAltTextField,
        public bool $imageCreateThumbnailImage,
        public ?int $imageThumbnailWidth,
        public ?int $imageThumbnailHeight,
        public bool $imageThumbnailCrop,
        public bool $imageThumbnailEditable,
        public bool $imageCreateFullscreenImage,
        public ?int $imageFullscreenWidth,
        public ?int $imageFullscreenHeight,
        public bool $imageFullscreenCrop,
        public bool $imageFullscreenEditable,
    ) {
    }
}
