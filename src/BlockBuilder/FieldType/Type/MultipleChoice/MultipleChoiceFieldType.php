<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\MultipleChoice;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Validation\ChoiceOptionListValidator;

class MultipleChoiceFieldType extends AbstractFieldType
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::MultipleChoice;
    }

    public static function getHandle(): string
    {
        return 'select_multiple_field';
    }

    public static function getLabel(): string
    {
        return t('Multiple Choice Field');
    }

    public static function getIcon(): string
    {
        return t('fas fa-check-square');
    }

    public static function getDefaultValues(): array
    {
        return [];
    }

    public static function createDtoFromArray(array $data): MultipleChoiceFieldTypeDto
    {
        return new MultipleChoiceFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            selectMultipleType: trim($data['selectMultipleType'] ?? ''),
            selectMultipleDefaultValue: trim($data['selectMultipleDefaultValue'] ?? ''),
            selectMultipleListGenerationMethod: trim($data['selectMultipleListGenerationMethod'] ?? ''),
            selectMultipleOptions: $data['selectMultipleOptions'] ?? '',
            selectMultipleCustomCode: $data['selectMultipleCustomCode'] ?? '',
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'selectMultipleOptions|empty' => t('There are some empty "Multiple Choice Field/Select options" fields (%s).', $context->getTabName()),
            'selectMultipleOptions|invalid_data' => t('Invalid entry in one of "Multiple Choice Field/Select options" fields (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];
        $method = $data['selectMultipleListGenerationMethod'] ?? '';
        $optionsString = $data['selectMultipleOptions'] ?? '';

        if (!ChoiceOptionListValidator::hasValidShape($optionsString)) {
            return ['selectMultipleOptions|invalid_data'];
        }

        if ($method === 'custom_code') {
            return $errors;
        }

        // Options
        if (empty($optionsString)) {
            $errors[] = 'selectMultipleOptions|empty';

            return $errors;
        }

        return $errors;
    }
}
