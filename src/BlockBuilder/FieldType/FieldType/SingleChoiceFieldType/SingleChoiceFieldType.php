<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\SingleChoiceFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpSingleChoiceStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldTypeTrait;

class SingleChoiceFieldType implements FieldTypeInterface
{
    use FieldTypeTrait;

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
        if (!empty($data['selectAddEmptyOption']) && $data['selectAddEmptyOption'] === 'yes') {
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

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpSingleChoiceStrategy::class;
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

        if ($method === 'custom_code') {
            return $errors;
        }

        // Options
        if (empty($optionsString)) {
            $errors[] = 'selectOptions|empty';

            return $errors;
        }

        // Convert newlines to a consistent format and split into lines
        $lines = preg_split('/\r\n|\r|\n/', $optionsString);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = explode('::', $line);

            // If the current line format is NOT "key::value",
            // it means only label is provided,
            // so we skip key validation in the next step.
            if (count($parts) !== 2) {
                continue;
            }

            // Validate the key: alphanumeric/underscores, and cannot start with underscore
            $key = trim($parts[0]);
            $isValidKey = preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]*$/', $key);

            if (!$isValidKey) {
                $errors[] = 'selectOptions|invalid_data';
                break; // One error is enough to invalidate the whole field
            }
        }

        return $errors;
    }
}
