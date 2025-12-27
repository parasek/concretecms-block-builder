<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Express;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class ExpressFieldType extends AbstractFieldType
{
    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::Express;
    }

    public static function getLabel(): string
    {
        return t('Express');
    }

    public static function getIcon(): string
    {
        return 'fas fa-database';
    }

    public static function getDefaultValues(): array
    {
        return [
            'expressHandle' => '',
        ];
    }

    public static function createDtoFromArray(array $data): ExpressFieldTypeDto
    {
        return new ExpressFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            expressHandle: trim((string) ($data['expressHandle'] ?? '')),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'expressHandle|empty' => t('Some "Express object handle" fields are empty (%s).', $context->getTabName()),
            'expressHandle|invalid' => t('Some "Express object handle" fields are invalid (%s). Each value must start with a letter and contain only lowercase letters, numbers, and underscores.', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        // Handle
        $expressHandle = is_scalar($data['expressHandle'] ?? null)
            ? trim((string) $data['expressHandle'])
            : '';
        if ($expressHandle === '') {
            $errors[] = 'expressHandle|empty';
        } elseif (preg_match('/^[a-z][a-z0-9_]*$/', $expressHandle) !== 1) {
            $errors[] = 'expressHandle|invalid';
        }

        return $errors;
    }
}
