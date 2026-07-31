<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\LinkFromFileManager;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class LinkFromFileManagerFieldType extends AbstractFieldType
{
    protected const array LEGACY_PROPERTY_ALIASES = [
        'linkFromFileManagerShowEndingField' => 'showEndingField',
        'linkFromFileManagerShowTextField' => 'showTextField',
        'linkFromFileManagerShowTitleField' => 'showTitleField',
        'linkFromFileManagerShowNewWindowField' => 'showNewWindowField',
        'linkFromFileManagerShowNoFollowField' => 'showNoFollowField',
    ];

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::LinkFromFileManager;
    }

    public static function getHandle(): string
    {
        return 'link_from_file_manager';
    }

    public static function getLabel(): string
    {
        return t('Link from File Manager');
    }

    public static function getIcon(): string
    {
        return t('fas fa-folder-open');
    }

    public static function getDefaultValues(): array
    {
        return [
            'showEndingField' => false,
            'showTextField' => false,
            'showTitleField' => false,
            'showNewWindowField' => false,
            'showNoFollowField' => false,
        ];
    }

    public static function createDtoFromArray(array $data): LinkFromFileManagerFieldTypeDto
    {
        return new LinkFromFileManagerFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            showEndingField: !empty($data['showEndingField']),
            showTextField: !empty($data['showTextField']),
            showTitleField: !empty($data['showTitleField']),
            showNewWindowField: !empty($data['showNewWindowField']),
            showNoFollowField: !empty($data['showNoFollowField']),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [];
    }

    public function validate(array $data): array
    {
        return [];
    }
}
