<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\ExpressFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpExpressStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldTypeTrait;

class ExpressFieldType implements FieldTypeInterface
{
    use FieldTypeTrait;

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::Express;
    }

    public static function getHandle(): string
    {
        return 'express';
    }

    public static function getLabel(): string
    {
        return t('Express');
    }

    public static function getIcon(): string
    {
        return t('fas fa-database');
    }

    public static function getDefaultValues(): array
    {
        return [];
    }

    public static function createDtoFromArray(array $data): ExpressFieldTypeDto
    {
        return new ExpressFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            expressHandle: trim($data['expressHandle'] ?? ''),
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpExpressStrategy::class;
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'expressHandle|empty' => t('There are some empty "Express object handle" fields (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        // Handle
        if (empty($data['expressHandle'])) {
            $errors[] = 'expressHandle|empty';
        }

        return $errors;
    }
}
