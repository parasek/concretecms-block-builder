<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Number;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class NumberFieldType extends AbstractFieldType
{
    public const int MAXIMUM_DISPLAYED_DECIMALS = 20;

    protected const array LEGACY_PROPERTY_ALIASES = [
        'numberSize' => 'size',
        'numberStep' => 'step',
        'numberMin' => 'minimum',
        'numberMax' => 'maximum',
        'numberDisplayedDecimals' => 'displayedDecimals',
        'numberDisplayedDecimalSeparator' => 'displayedDecimalSeparator',
        'numberDisplayedThousandsSeparator' => 'displayedThousandsSeparator',
    ];

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
            'size' => '10.2',
            'minimum' => '0',
            'maximum' => '99999999.99',
            'step' => '0.01',
            'displayedDecimals' => '2',
            'displayedDecimalSeparator' => ',',
            'displayedThousandsSeparator' => ' ',
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
            size: trim($data['size'] ?? ''),
            step: trim($data['step'] ?? ''),
            minimum: trim($data['minimum'] ?? ''),
            maximum: trim($data['maximum'] ?? ''),
            displayedDecimals: (int) ($data['displayedDecimals'] ?? 0),
            displayedDecimalSeparator: trim($data['displayedDecimalSeparator'] ?? ''),
            displayedThousandsSeparator: trim($data['displayedThousandsSeparator'] ?? ''),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'size|invalid_format' => t('Invalid entry in one of "Number/Size" fields, should be a dot-separated decimal format like 10.2 or 8.0 (%s).', $context->getTabName()),
            'step|invalid_format' => t('Invalid entry in one of "Number/%s" fields, should be a numeric value like 1 or 0.01 (%s).', t('Step'), $context->getTabName()),
            'minimum|invalid_format' => t('Invalid entry in one of "Number/%s" fields, should be a numeric value like 1 or 0.01 (%s).', t('Minimum'), $context->getTabName()),
            'maximum|invalid_format' => t('Invalid entry in one of "Number/%s" fields, should be a numeric value like 1 or 0.01 (%s).', t('Maximum'), $context->getTabName()),
            'step|not_positive' => t('Some "Number/Step" fields are not greater than zero (%s).', $context->getTabName()),
            'minimum|greater_than_maximum' => t('Some "Number/Minimum" fields are greater than their maximum (%s).', $context->getTabName()),
            'displayedDecimals|invalid_number' => t(
                'Invalid entry in one of "Displayed decimals" fields, should be an integer between 0 and %s (%s).',
                self::MAXIMUM_DISPLAYED_DECIMALS,
                $context->getTabName(),
            ),
            'displayedDecimalSeparator|invalid_value' => t('Some "Displayed decimal separator" fields are empty (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        $size = $data['size'] ?? '';
        $step = $data['step'] ?? '';
        $minimum = $data['minimum'] ?? '';
        $maximum = $data['maximum'] ?? '';
        $decimals = $data['displayedDecimals'] ?? '';
        $separator = $data['displayedDecimalSeparator'] ?? '';

        // Size
        // Validates formats like "10.2" or "8.0", ensuring it doesn't start with "0".
        if (!is_scalar($size) || preg_match('/^[1-9]\d*\.\d+$/', (string) $size) !== 1) {
            $errors[] = 'size|invalid_format';
        }

        // Step
        if (!is_scalar($step) || !is_numeric($step) || !is_finite((float) $step)) {
            $errors[] = 'step|invalid_format';
        } elseif ((float) $step <= 0) {
            $errors[] = 'step|not_positive';
        }

        // Minimum
        if (!is_scalar($minimum) || !is_numeric($minimum) || !is_finite((float) $minimum)) {
            $errors[] = 'minimum|invalid_format';
        }

        // Maximum
        if (!is_scalar($maximum) || !is_numeric($maximum) || !is_finite((float) $maximum)) {
            $errors[] = 'maximum|invalid_format';
        }
        if (
            is_scalar($minimum)
            && is_scalar($maximum)
            && is_numeric($minimum)
            && is_numeric($maximum)
            && is_finite((float) $minimum)
            && is_finite((float) $maximum)
            && (float) $minimum > (float) $maximum
        ) {
            $errors[] = 'minimum|greater_than_maximum';
        }

        // Displayed decimals
        if (
            !is_scalar($decimals)
            || !ctype_digit((string) $decimals)
            || (int) $decimals > self::MAXIMUM_DISPLAYED_DECIMALS
        ) {
            $errors[] = 'displayedDecimals|invalid_number';
        }

        // Displayed decimal separator
        if (!is_scalar($separator) || $separator === '') {
            $errors[] = 'displayedDecimalSeparator|invalid_value';
        }

        return $errors;
    }
}
