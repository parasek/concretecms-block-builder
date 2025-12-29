<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\FileSetFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpFileSetStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;

class FileSetFieldType implements FieldTypeInterface
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::FileSet;
    }

    public static function getHandle(): string
    {
        return 'file_set';
    }

    public static function getLabel(): string
    {
        return t('File Set');
    }

    public static function getIcon(): string
    {
        return t('fas fa-clone');
    }

    public static function createDtoFromArray(array $data): FileSetFieldTypeDto
    {
        return new FileSetFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            fileSetPrefix: trim($data['fileSetPrefix'] ?? ''),
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpFileSetStrategy::class;
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
