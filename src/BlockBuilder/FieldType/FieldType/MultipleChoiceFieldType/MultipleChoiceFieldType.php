<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\MultipleChoiceFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpMultipleChoiceStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;

class MultipleChoiceFieldType implements FieldTypeInterface
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

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpMultipleChoiceStrategy::class;
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

        if ($method === 'custom_code') {
            return $errors;
        }

        // Options
        if (empty($optionsString)) {
            $errors[] = 'selectMultipleOptions|empty';

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
                $errors[] = 'selectMultipleOptions|invalid_data';
                break; // One error is enough to invalidate the whole field
            }
        }

        return $errors;
    }
}
