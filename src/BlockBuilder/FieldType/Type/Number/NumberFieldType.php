<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Number;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class NumberFieldType extends AbstractFieldType
{
    public const int MAXIMUM_DISPLAYED_DECIMALS = 20;

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
            'displayZeroValue' => 0,
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
            displayZeroValue: !empty($data['displayZeroValue']),
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
            'numberStep|not_positive' => t('Some "Number/Step" fields are not greater than zero (%s).', $context->getTabName()),
            'numberMin|greater_than_maximum' => t('Some "Number/Minimum" fields are greater than their maximum (%s).', $context->getTabName()),
            'numberDisplayedDecimals|invalid_number' => t(
                'Invalid entry in one of "Displayed decimals" fields, should be an integer between 0 and %s (%s).',
                self::MAXIMUM_DISPLAYED_DECIMALS,
                $context->getTabName(),
            ),
            'numberDisplayedDecimalSeparator|invalid_value' => t('Some "Displayed decimal separator" fields are empty (%s).', $context->getTabName()),
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
        // Validates formats like "10.2" or "8.0", ensuring it doesn't start with "0".
        if (!is_scalar($size) || preg_match('/^[1-9]\d*\.\d+$/', (string) $size) !== 1) {
            $errors[] = 'numberSize|invalid_format';
        }

        // Step
        if (!is_scalar($step) || !is_numeric($step) || !is_finite((float) $step)) {
            $errors[] = 'numberStep|invalid_format';
        } elseif ((float) $step <= 0) {
            $errors[] = 'numberStep|not_positive';
        }

        // Minimum
        if (!is_scalar($min) || !is_numeric($min) || !is_finite((float) $min)) {
            $errors[] = 'numberMin|invalid_format';
        }

        // Maximum
        if (!is_scalar($max) || !is_numeric($max) || !is_finite((float) $max)) {
            $errors[] = 'numberMax|invalid_format';
        }
        if (
            is_scalar($min)
            && is_scalar($max)
            && is_numeric($min)
            && is_numeric($max)
            && is_finite((float) $min)
            && is_finite((float) $max)
            && (float) $min > (float) $max
        ) {
            $errors[] = 'numberMin|greater_than_maximum';
        }

        // Displayed decimals
        if (
            !is_scalar($decimals)
            || !ctype_digit((string) $decimals)
            || (int) $decimals > self::MAXIMUM_DISPLAYED_DECIMALS
        ) {
            $errors[] = 'numberDisplayedDecimals|invalid_number';
        }

        // Displayed decimal separator
        if (!is_scalar($separator) || $separator === '') {
            $errors[] = 'numberDisplayedDecimalSeparator|invalid_value';
        }

        return $errors;
    }
}
