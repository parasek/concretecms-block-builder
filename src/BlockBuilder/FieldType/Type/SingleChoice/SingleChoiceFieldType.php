<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\SingleChoice;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Validation\ChoiceOptionListValidator;

class SingleChoiceFieldType extends AbstractFieldType
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::SingleChoice;
    }

    public static function getHandle(): string
    {
        return 'select_field';
    }

    public static function getLabel(): string
    {
        return t('Single Choice Field');
    }

    public static function getIcon(): string
    {
        return t('fas fa-check-circle');
    }

    public static function getDefaultValues(): array
    {
        return [];
    }

    public static function createDtoFromArray(array $data): SingleChoiceFieldTypeDto
    {
        $selectAddEmptyOption = false;
        if (in_array($data['selectAddEmptyOption'] ?? null, ['1', 1, 'yes'], true)) {
            $selectAddEmptyOption = true;
        }

        return new SingleChoiceFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            selectType: trim($data['selectType'] ?? ''),
            selectAddEmptyOption: $selectAddEmptyOption,
            selectDefaultValue: trim($data['selectDefaultValue'] ?? ''),
            selectListGenerationMethod: trim($data['selectListGenerationMethod'] ?? ''),
            selectOptions: $data['selectOptions'] ?? '',
            selectCustomCode: $data['selectCustomCode'] ?? '',
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'selectOptions|empty' => t('There are some empty "Single Choice Field/Select options" fields (%s).', $context->getTabName()),
            'selectOptions|invalid_data' => t('Invalid entry in one of "Single Choice Field/Select options" fields (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];
        $method = $data['selectListGenerationMethod'] ?? '';
        $optionsString = $data['selectOptions'] ?? '';

        if (!ChoiceOptionListValidator::hasValidShape($optionsString)) {
            return ['selectOptions|invalid_data'];
        }

        if ($method === 'custom_code') {
            return $errors;
        }

        // Options
        if (empty($optionsString)) {
            $errors[] = 'selectOptions|empty';

            return $errors;
        }

        return $errors;
    }
}
