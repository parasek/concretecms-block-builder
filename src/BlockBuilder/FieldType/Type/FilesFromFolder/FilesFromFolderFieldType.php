<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\FilesFromFolder;

use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;

final class FilesFromFolderFieldType extends AbstractFieldType
{
    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::FilesFromFolder;
    }

    public static function getLabel(): string
    {
        return t('Files from a Folder');
    }

    public static function getIcon(): string
    {
        return 'fas fa-folder-open';
    }

    public static function getDefaultValues(): array
    {
        return [
            'fileOrder' => 'file_manager',
        ];
    }

    public static function createDtoFromArray(array $data): FilesFromFolderFieldTypeDto
    {
        return new FilesFromFolderFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            fileOrder: trim($data['fileOrder'] ?? ''),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'fileOrder|invalid_option' => t('Some "File order" fields contain an invalid option (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        return [];
    }
}
