<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\ImageFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpImageStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldTypeTrait;

class ImageFieldType implements FieldTypeInterface
{
    use FieldTypeTrait;

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::Image;
    }

    public static function getHandle(): string
    {
        return 'image';
    }

    public static function getLabel(): string
    {
        return t('Image');
    }

    public static function getIcon(): string
    {
        return t('fas fa-image');
    }

    public static function getDefaultValues(): array
    {
        return [
            'imageShowAltTextField' => 1,
            'imageCreateFullscreenImage' => 1,
            'imageCreateThumbnailImage' => 1,

            'imageThumbnailWidth' => 480,
            'imageThumbnailHeight' => 270,
            'imageThumbnailCrop' => 1,
            'imageThumbnailEditable' => 1,

            'imageFullscreenWidth' => 1920,
            'imageFullscreenHeight' => 1080,
            'imageFullscreenCrop' => 0,
            'imageFullscreenEditable' => 1,
        ];
    }

    public static function createDtoFromArray(array $data): ImageFieldTypeDto
    {
        return new ImageFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            imageShowAltTextField: !empty($data['imageShowAltTextField']),
            imageCreateThumbnailImage: !empty($data['imageCreateThumbnailImage']),
            imageThumbnailWidth: !empty($data['imageThumbnailWidth']) ? (int) $data['imageThumbnailWidth'] : null,
            imageThumbnailHeight: !empty($data['imageThumbnailHeight']) ? (int) $data['imageThumbnailHeight'] : null,
            imageThumbnailCrop: !empty($data['imageThumbnailCrop']),
            imageThumbnailEditable: !empty($data['imageThumbnailEditable']),
            imageCreateFullscreenImage: !empty($data['imageCreateFullscreenImage']),
            imageFullscreenWidth: !empty($data['imageFullscreenWidth']) ? (int) $data['imageFullscreenWidth'] : null,
            imageFullscreenHeight: !empty($data['imageFullscreenHeight']) ? (int) $data['imageFullscreenHeight'] : null,
            imageFullscreenCrop: !empty($data['imageFullscreenCrop']),
            imageFullscreenEditable: !empty($data['imageFullscreenEditable']),
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpImageStrategy::class;
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'imageThumbnailOptions|empty_width_and_height' => t('Invalid entry in one of "Image/Generate thumbnail" fields, you should provide width, height or both (%s).', $context->getTabName()),
            'imageThumbnailOptions|crop_requires_width_and_height' => t('Invalid entry in one of "Image/Generate thumbnail" fields, you should provide width and height if you want to crop image (%s).', $context->getTabName()),
            'imageThumbnailWidth|invalid_number' => t('Invalid entry in one of "Image/Generate thumbnail/Width" fields, should be a number greater than 0 or empty (%s).', $context->getTabName()),
            'imageThumbnailHeight|invalid_number' => t('Invalid entry in one of "Image/Generate thumbnail/Height" fields, should be a number greater than 0 or empty (%s).', $context->getTabName()),

            'imageFullscreenOptions|empty_width_and_height' => t('Invalid entry in one of "Image/Generate fullscreen thumbnail" fields, you should provide width, height or both (%s).', $context->getTabName()),
            'imageFullscreenOptions|crop_requires_width_and_height' => t('Invalid entry in one of "Image/Generate fullscreen thumbnail" fields, you should provide width and height if you want to crop image (%s).', $context->getTabName()),
            'imageFullscreenWidth|invalid_number' => t('Invalid entry in one of "Image/Generate fullscreen thumbnail/Width" fields, should be a number greater than 0 or empty (%s).', $context->getTabName()),
            'imageFullscreenHeight|invalid_number' => t('Invalid entry in one of "Image/Generate fullscreen thumbnail/Height" fields, should be a number greater than 0 or empty (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        return array_merge(
            $this->validateImageDimensions(
                data: $data,
                createImageKey: 'imageCreateThumbnailImage',
                widthKey: 'imageThumbnailWidth',
                heightKey: 'imageThumbnailHeight',
                cropKey: 'imageThumbnailCrop',
                optionsKey: 'imageThumbnailOptions',
            ),
            $this->validateImageDimensions(
                data: $data,
                createImageKey: 'imageCreateFullscreenImage',
                widthKey: 'imageFullscreenWidth',
                heightKey: 'imageFullscreenHeight',
                cropKey: 'imageFullscreenCrop',
                optionsKey: 'imageFullscreenOptions',
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

        if (empty($data[$createImageKey])) {
            return $errors;
        }

        $width = $data[$widthKey] ?? '';
        $height = $data[$heightKey] ?? '';
        $crop = !empty($data[$cropKey]);

        if (!$crop && ($width === '' && $height === '')) {
            $errors[] = $optionsKey . '|empty_width_and_height';
        }

        if ($crop && ($width === '' || $height === '')) {
            $errors[] = $optionsKey . '|crop_requires_width_and_height';
        }

        if ($width !== '' && (!ctype_digit((string) $width) || $width < 1)) {
            $errors[] = $widthKey . '|invalid_number';
        }

        if ($height !== '' && (!ctype_digit((string) $height) || $height < 1)) {
            $errors[] = $heightKey . '|invalid_number';
        }

        return $errors;
    }
}
