<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Image;

use BlockBuilder\Block\Validation\IntegerValueValidator;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class ImageFieldType extends AbstractFieldType
{
    protected const array LEGACY_PROPERTY_ALIASES = [
        'imageShowAltTextField' => 'showAltTextField',
        'imageCreateThumbnailImage' => 'createThumbnailImage',
        'imageThumbnailWidth' => 'thumbnailWidth',
        'imageThumbnailHeight' => 'thumbnailHeight',
        'imageThumbnailCrop' => 'thumbnailCrop',
        'imageThumbnailEditable' => 'thumbnailEditable',
        'imageCreateFullscreenImage' => 'createFullscreenImage',
        'imageFullscreenWidth' => 'fullscreenWidth',
        'imageFullscreenHeight' => 'fullscreenHeight',
        'imageFullscreenCrop' => 'fullscreenCrop',
        'imageFullscreenEditable' => 'fullscreenEditable',
    ];

    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::Image;
    }

    public static function getLabel(): string
    {
        return t('Image');
    }

    public static function getIcon(): string
    {
        return 'fas fa-image';
    }

    public static function getDefaultValues(): array
    {
        return [
            'showAltTextField' => true,
            'createThumbnailImage' => true,
            'thumbnailWidth' => 480,
            'thumbnailHeight' => 270,
            'thumbnailCrop' => true,
            'thumbnailEditable' => true,
            'createFullscreenImage' => true,
            'fullscreenWidth' => 1920,
            'fullscreenHeight' => 1080,
            'fullscreenCrop' => false,
            'fullscreenEditable' => true,
        ];
    }

    public static function createDtoFromArray(array $data): ImageFieldTypeDto
    {
        return new ImageFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            showAltTextField: !empty($data['showAltTextField']),
            createThumbnailImage: !empty($data['createThumbnailImage']),
            thumbnailWidth: !empty($data['thumbnailWidth']) ? (int) $data['thumbnailWidth'] : null,
            thumbnailHeight: !empty($data['thumbnailHeight']) ? (int) $data['thumbnailHeight'] : null,
            thumbnailCrop: !empty($data['thumbnailCrop']),
            thumbnailEditable: !empty($data['thumbnailEditable']),
            createFullscreenImage: !empty($data['createFullscreenImage']),
            fullscreenWidth: !empty($data['fullscreenWidth']) ? (int) $data['fullscreenWidth'] : null,
            fullscreenHeight: !empty($data['fullscreenHeight']) ? (int) $data['fullscreenHeight'] : null,
            fullscreenCrop: !empty($data['fullscreenCrop']),
            fullscreenEditable: !empty($data['fullscreenEditable']),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'thumbnailOptions|empty_width_and_height' => t('Invalid entry in one of "Image/Generate thumbnail" fields, you should provide width, height or both (%s).', $context->getTabName()),
            'thumbnailOptions|crop_requires_width_and_height' => t('Invalid entry in one of "Image/Generate thumbnail" fields, you should provide width and height if you want to crop image (%s).', $context->getTabName()),
            'thumbnailWidth|invalid_number' => t('Invalid entry in one of "Image/Generate thumbnail/Width" fields, should be a number greater than 0 or empty (%s).', $context->getTabName()),
            'thumbnailHeight|invalid_number' => t('Invalid entry in one of "Image/Generate thumbnail/Height" fields, should be a number greater than 0 or empty (%s).', $context->getTabName()),

            'fullscreenOptions|empty_width_and_height' => t('Invalid entry in one of "Image/Generate fullscreen thumbnail" fields, you should provide width, height or both (%s).', $context->getTabName()),
            'fullscreenOptions|crop_requires_width_and_height' => t('Invalid entry in one of "Image/Generate fullscreen thumbnail" fields, you should provide width and height if you want to crop image (%s).', $context->getTabName()),
            'fullscreenWidth|invalid_number' => t('Invalid entry in one of "Image/Generate fullscreen thumbnail/Width" fields, should be a number greater than 0 or empty (%s).', $context->getTabName()),
            'fullscreenHeight|invalid_number' => t('Invalid entry in one of "Image/Generate fullscreen thumbnail/Height" fields, should be a number greater than 0 or empty (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        return array_merge(
            $this->validateImageDimensions(
                data: $data,
                createImageKey: 'createThumbnailImage',
                widthKey: 'thumbnailWidth',
                heightKey: 'thumbnailHeight',
                cropKey: 'thumbnailCrop',
                optionsKey: 'thumbnailOptions',
            ),
            $this->validateImageDimensions(
                data: $data,
                createImageKey: 'createFullscreenImage',
                widthKey: 'fullscreenWidth',
                heightKey: 'fullscreenHeight',
                cropKey: 'fullscreenCrop',
                optionsKey: 'fullscreenOptions',
            )
        );
    }

    private function validateImageDimensions(
        array $data,
        string $createImageKey,
        string $widthKey,
        string $heightKey,
        string $cropKey,
        string $optionsKey,
    ): array {
        $errors = [];

        $width = $data[$widthKey] ?? '';
        $height = $data[$heightKey] ?? '';
        $crop = !empty($data[$cropKey]);
        $shouldCreateImage = !empty($data[$createImageKey]);

        if ($shouldCreateImage && !$crop && ($width === '' && $height === '')) {
            $errors[] = $optionsKey . '|empty_width_and_height';
        }

        if ($shouldCreateImage && $crop && ($width === '' || $height === '')) {
            $errors[] = $optionsKey . '|crop_requires_width_and_height';
        }

        if (!IntegerValueValidator::isInRange($width, 1, allowEmpty: true)) {
            $errors[] = $widthKey . '|invalid_number';
        }

        if (!IntegerValueValidator::isInRange($height, 1, allowEmpty: true)) {
            $errors[] = $heightKey . '|invalid_number';
        }

        return $errors;
    }
}
