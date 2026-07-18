<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Number;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class NumberFieldType extends AbstractFieldType
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::Number;
    }

    public static function getHandle(): string
    {
        return 'number';
    }

    public static function getLabel(): string
    {
        return t('Number');
    }

    public static function getIcon(): string
    {
        return t('fas fa-hashtag');
    }

    public static function getDefaultValues(): array
    {
        return [
            'numberSize' => '10.2',
            'numberMin' => '0',
            'numberMax' => '99999999.99',
            'numberStep' => '0.01',
            'numberDisplayedDecimals' => '2',
            'numberDisplayedDecimalSeparator' => ',',
            'numberDisplayedThousandsSeparator' => ' ',
        ];
    }

    public static function createDtoFromArray(array $data): NumberFieldTypeDto
    {
        return new NumberFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            numberSize: trim($data['numberSize'] ?? ''),
            numberStep: trim($data['numberStep'] ?? ''),
            numberMin: trim($data['numberMin'] ?? ''),
            numberMax: trim($data['numberMax'] ?? ''),
            numberDisplayedDecimals: (int) ($data['numberDisplayedDecimals'] ?? 0),
            numberDisplayedDecimalSeparator: trim($data['numberDisplayedDecimalSeparator'] ?? ''),
            numberDisplayedThousandsSeparator: trim($data['numberDisplayedThousandsSeparator'] ?? ''),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'numberSize|invalid_format' => t('Invalid entry in one of "Number/Size" fields, should be a dot-separated decimal format like 10.2 or 8.0 (%s).', $context->getTabName()),
            'numberStep|invalid_format' => t('Invalid entry in one of "Number/%s" fields, should be a numeric value like 1 or 0.01 (%s).', t('Step'), $context->getTabName()),
            'numberMin|invalid_format' => t('Invalid entry in one of "Number/%s" fields, should be a numeric value like 1 or 0.01 (%s).', t('Minimum'), $context->getTabName()),
            'numberMax|invalid_format' => t('Invalid entry in one of "Number/%s" fields, should be a numeric value like 1 or 0.01 (%s).', t('Maximum'), $context->getTabName()),
            'numberDisplayedDecimals|invalid_number' => t('Invalid entry in one of "Displayed decimals" fields, should be a non-negative integer (%s).', 40, 2000, $context->getTabName()),
            'numberDisplayedDecimalSeparator|invalid_value' => t('Invalid entry in one of "Displayed decimal separator" fields (%s).', 40, 2000, $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        $size = $data['numberSize'] ?? '';
        $step = $data['numberStep'] ?? '';
        $min = $data['numberMin'] ?? '';
        $max = $data['numberMax'] ?? '';
        $decimals = $data['numberDisplayedDecimals'] ?? '';
        $separator = $data['numberDisplayedDecimalSeparator'] ?? '';

        // Size
        if ($size !== '') {
            // Validates formats like "10.2" or "8.0",
            // ensuring it doesn't start with "0"
            if (!preg_match('/^[1-9]\d*\.\d+$/', (string) $size)) {
                $errors[] = 'numberSize|invalid_format';
            }
        }

        // Step
        if (!is_numeric($step)) {
            $errors[] = 'numberStep|invalid_format';
        }

        // Minimum
        if (!is_numeric($min)) {
            $errors[] = 'numberMin|invalid_format';
        }

        // Maximum
        if (!is_numeric($max)) {
            $errors[] = 'numberMax|invalid_format';
        }

        // Displayed decimals
        if ($decimals !== '') {
            if (!ctype_digit((string) $decimals)) {
                $errors[] = 'numberDisplayedDecimals|invalid_number';
            }
        }

        // Displayed decimal separator
        if ($separator === '') {
            $errors[] = 'numberDisplayedDecimalSeparator|invalid_value';
        }

        return $errors;
    }
}
