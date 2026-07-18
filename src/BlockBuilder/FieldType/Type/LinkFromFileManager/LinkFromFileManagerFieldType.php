<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\LinkFromFileManager;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class LinkFromFileManagerFieldType extends AbstractFieldType
{
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
        return [];
    }

    public static function createDtoFromArray(array $data): LinkFromFileManagerFieldTypeDto
    {
        return new LinkFromFileManagerFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            linkFromFileManagerShowEndingField: !empty($data['linkFromFileManagerShowEndingField']),
            linkFromFileManagerShowTextField: !empty($data['linkFromFileManagerShowTextField']),
            linkFromFileManagerShowTitleField: !empty($data['linkFromFileManagerShowTitleField']),
            linkFromFileManagerShowNewWindowField: !empty($data['linkFromFileManagerShowNewWindowField']),
            linkFromFileManagerShowNoFollowField: !empty($data['linkFromFileManagerShowNoFollowField']),
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
