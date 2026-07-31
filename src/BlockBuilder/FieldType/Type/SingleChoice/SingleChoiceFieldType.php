<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\SingleChoice;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Validation\ChoiceOptionListValidator;

class SingleChoiceFieldType extends AbstractFieldType
{
    protected const array LEGACY_PROPERTY_ALIASES = [
        'selectType' => 'displayType',
        'selectAddEmptyOption' => 'addEmptyOption',
        'selectDefaultValue' => 'defaultValue',
        'selectListGenerationMethod' => 'listGenerationMethod',
        'selectOptions' => 'options',
        'selectCustomCode' => 'customCode',
    ];

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
        return [
            'displayType' => 'default_select',
            'addEmptyOption' => 0,
            'defaultValue' => '',
            'listGenerationMethod' => 'basic_list',
            'options' => '',
            'customCode' => '',
        ];
    }

    public static function createDtoFromArray(array $data): SingleChoiceFieldTypeDto
    {
        return new SingleChoiceFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            displayType: trim($data['displayType'] ?? ''),
            addEmptyOption: in_array($data['addEmptyOption'] ?? null, [true, 1, '1', 'yes'], true),
            defaultValue: trim($data['defaultValue'] ?? ''),
            listGenerationMethod: trim($data['listGenerationMethod'] ?? ''),
            options: $data['options'] ?? '',
            customCode: $data['customCode'] ?? '',
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'displayType|invalid_option' => t('Some "Single Choice Field/Type" fields contain an invalid option (%s).', $context->getTabName()),
            'addEmptyOption|invalid_option' => t('Some "Single Choice Field/Add an empty option" fields contain an invalid option (%s).', $context->getTabName()),
            'listGenerationMethod|invalid_option' => t('Some "Single Choice Field/List generation method" fields contain an invalid option (%s).', $context->getTabName()),
            'options|empty' => t('There are some empty "Single Choice Field/Select options" fields (%s).', $context->getTabName()),
            'options|invalid_data' => t('Invalid entry in one of "Single Choice Field/Select options" fields (%s).', $context->getTabName()),
            'defaultValue|invalid_option' => t('The default value of a Single Choice Field does not match any configured option (%s).', $context->getTabName()),
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

        if ($method === 'custom_code') {
            return $errors;
        }

        // Options
        if (trim($optionsString) === '') {
            $errors[] = 'options|empty';

            return $errors;
        }

        $optionKeys = $this->getOptionKeys($optionsString);
        if (
            count($optionKeys) !== count(array_unique($optionKeys))
            || array_any($optionKeys, static fn(string $key): bool => mb_strlen($key) > 255)
        ) {
            $errors[] = 'options|invalid_data';

            return $errors;
        }

        $defaultValue = trim((string) ($data['defaultValue'] ?? ''));
        if ($defaultValue !== '' && !in_array($defaultValue, $optionKeys, true)) {
            $errors[] = 'defaultValue|invalid_option';
        }

        return $errors;
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
