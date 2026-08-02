<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\FileSet;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class FileSetFieldType extends AbstractFieldType
{
    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::FileSet;
    }

    public static function getLabel(): string
    {
        return t('File Set');
    }

    public static function getIcon(): string
    {
        return 'fas fa-clone';
    }

    public static function getDefaultValues(): array
    {
        return [
            'fileSetPrefix' => '',
        ];
    }

    public static function createDtoFromArray(array $data): FileSetFieldTypeDto
    {
        return new FileSetFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            fileSetPrefix: $data['fileSetPrefix'] ?? '',
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
