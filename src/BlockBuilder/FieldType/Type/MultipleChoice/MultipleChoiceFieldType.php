<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\MultipleChoice;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Validation\ChoiceOptionListValidator;

class MultipleChoiceFieldType extends AbstractFieldType
{
    protected const array LEGACY_PROPERTY_ALIASES = [
        'selectMultipleType' => 'displayType',
        'selectMultipleDefaultValue' => 'defaultValue',
        'selectMultipleListGenerationMethod' => 'listGenerationMethod',
        'selectMultipleOptions' => 'options',
        'selectMultipleCustomCode' => 'customCode',
    ];

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
        return [
            'displayType' => 'default_multiselect',
            'defaultValue' => '',
            'listGenerationMethod' => 'basic_list',
            'options' => '',
            'customCode' => '',
        ];
    }

    public static function createDtoFromArray(array $data): MultipleChoiceFieldTypeDto
    {
        return new MultipleChoiceFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            displayType: trim($data['displayType'] ?? ''),
            defaultValue: trim($data['defaultValue'] ?? ''),
            listGenerationMethod: trim($data['listGenerationMethod'] ?? ''),
            options: $data['options'] ?? '',
            customCode: $data['customCode'] ?? '',
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'displayType|invalid_option' => t('Some "Multiple Choice Field/Type" fields contain an invalid option (%s).', $context->getTabName()),
            'listGenerationMethod|invalid_option' => t('Some "Multiple Choice Field/List generation method" fields contain an invalid option (%s).', $context->getTabName()),
            'options|empty' => t('There are some empty "Multiple Choice Field/Select options" fields (%s).', $context->getTabName()),
            'options|invalid_data' => t('Some "Multiple Choice Field/Select options" fields contain invalid options. Use one option per line. Explicit keys must use "key :: label", start with a letter or number, and contain only letters, numbers, underscores, and hyphens (%s).', $context->getTabName()),
            'defaultValue|invalid_option' => t('The default value of a Multiple Choice Field contains an option that is not configured (%s).', $context->getTabName()),
            'defaultValue|invalid_data' => t('The default value of a Multiple Choice Field must contain unique option keys separated by pipes (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];
        $method = $data['listGenerationMethod'] ?? '';
        $optionsString = $data['options'] ?? '';

        if (!ChoiceOptionListValidator::hasValidShape($optionsString)) {
            return ['options|invalid_data'];
        }

        $defaultValues = $this->getDefaultValuesFromString((string) ($data['defaultValue'] ?? ''));
        if ($defaultValues === null) {
            return ['defaultValue|invalid_data'];
        }

        if ($method === 'custom_code') {
            return $errors;
        }

        if (trim($optionsString) === '') {
            $errors[] = 'options|empty';

            return $errors;
        }

        $optionKeys = $this->getOptionKeys($optionsString);
        if (count($optionKeys) !== count(array_unique($optionKeys))) {
            return ['options|invalid_data'];
        }

        foreach ($defaultValues as $defaultValue) {
            if (!in_array($defaultValue, $optionKeys, true)) {
                $errors[] = 'defaultValue|invalid_option';
                break;
            }
        }

        return $errors;
    }

    /**
     * @return string[]|null
     */
    private function getDefaultValuesFromString(string $defaultValue): ?array
    {
        if ($defaultValue === '') {
            return [];
        }

        $values = array_map('trim', explode('|', $defaultValue));
        if (
            array_any($values, static fn(string $value): bool => $value === '')
            || count($values) !== count(array_unique($values))
        ) {
            return null;
        }

        return $values;
    }

    /**
     * @return string[]
     */
    private function getOptionKeys(string $options): array
    {
        $keys = [];
        $position = 0;
        foreach (preg_split('/\r\n|\r|\n/', $options) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $position++;
            $parts = array_map('trim', explode('::', $line, 2));
            $keys[] = count($parts) === 2 ? $parts[0] : (string) $position;
        }

        return $keys;
    }
}
