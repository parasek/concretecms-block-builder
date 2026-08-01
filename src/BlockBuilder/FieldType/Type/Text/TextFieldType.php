<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Text;

use BlockBuilder\Block\Validation\IntegerValueValidator;
use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;

class TextFieldType extends AbstractFieldType
{
    public const int MAXIMUM_AFFIX_LENGTH = 100;
    public const int MAXIMUM_LENGTH = 255;

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::Text;
    }

    public static function getHandle(): string
    {
        return 'text_field';
    }

    public static function getLabel(): string
    {
        return t('Text');
    }

    public static function getIcon(): string
    {
        return t('fas fa-font');
    }

    public static function getDefaultValues(): array
    {
        return [
            'displayZeroValue' => 0,
            'defaultValue' => '',
            'placeholder' => '',
            'additionalValidation' => 'none',
            'minimumLength' => '',
            'maximumLength' => (string) self::MAXIMUM_LENGTH,
            'prefix' => '',
            'suffix' => '',
        ];
    }

    public static function createDtoFromArray(array $data): TextFieldTypeDto
    {
        return new TextFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            displayZeroValue: !empty($data['displayZeroValue']),
            defaultValue: (string) ($data['defaultValue'] ?? ''),
            placeholder: trim((string) ($data['placeholder'] ?? '')),
            additionalValidation: trim((string) ($data['additionalValidation'] ?? 'none')),
            minimumLength: ($data['minimumLength'] ?? '') !== '' ? (int) $data['minimumLength'] : null,
            maximumLength: ($data['maximumLength'] ?? '') !== '' ? (int) $data['maximumLength'] : self::MAXIMUM_LENGTH,
            prefix: trim($data['prefix'] ?? ''),
            suffix: trim($data['suffix'] ?? ''),
            titleSource: !empty($data['titleSource']),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'prefix|too_long' => t('Some "Field prefix" values contain more than %s characters (%s).', self::MAXIMUM_AFFIX_LENGTH, $context->getTabName()),
            'suffix|too_long' => t('Some "Field suffix" values contain more than %s characters (%s).', self::MAXIMUM_AFFIX_LENGTH, $context->getTabName()),
            'additionalValidation|invalid_option' => t('Some "Text/Additional validation" fields contain an invalid option (%s).', $context->getTabName()),
            'minimumLength|invalid_number' => t('Some "Text/Minimum length" fields must contain a number between 0 and %s or be empty (%s).', self::MAXIMUM_LENGTH, $context->getTabName()),
            'maximumLength|invalid_number' => t('Some "Text/Maximum length" fields must contain a number between 1 and %s (%s).', self::MAXIMUM_LENGTH, $context->getTabName()),
            'minimumLength|greater_than_maximum' => t('The minimum length of a Text field cannot be greater than its maximum length (%s).', $context->getTabName()),
            'defaultValue|outside_length' => t('Some "Text/Default value" fields do not satisfy their configured minimum or maximum length (%s).', $context->getTabName()),
            'defaultValue|invalid_additional_validation' => t('Some "Text/Default value" fields do not satisfy the selected additional validation (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];
        foreach (['prefix', 'suffix'] as $propertyName) {
            $value = $data[$propertyName] ?? '';
            if (!is_scalar($value) || mb_strlen(trim((string) $value)) > self::MAXIMUM_AFFIX_LENGTH) {
                $errors[] = $propertyName . '|too_long';
            }
        }

        $minimumLength = $data['minimumLength'] ?? '';
        $maximumLength = $data['maximumLength'] ?? '';
        $minimumIsValid = $minimumLength === ''
            || IntegerValueValidator::isInRange($minimumLength, 0, self::MAXIMUM_LENGTH);
        $maximumIsValid = IntegerValueValidator::isInRange($maximumLength, 1, self::MAXIMUM_LENGTH);
        if (!$minimumIsValid) {
            $errors[] = 'minimumLength|invalid_number';
        }
        if (!$maximumIsValid) {
            $errors[] = 'maximumLength|invalid_number';
        }
        if (
            $minimumLength !== ''
            && $minimumIsValid
            && $maximumIsValid
            && (int) $minimumLength > (int) $maximumLength
        ) {
            $errors[] = 'minimumLength|greater_than_maximum';
        }

        $defaultValue = $data['defaultValue'] ?? '';
        if (is_scalar($defaultValue) && (string) $defaultValue !== '' && $maximumIsValid) {
            $defaultValue = trim((string) $defaultValue);
            $defaultLength = mb_strlen($defaultValue);
            if (
                ($minimumIsValid && $minimumLength !== '' && $defaultLength < (int) $minimumLength)
                || $defaultLength > (int) $maximumLength
            ) {
                $errors[] = 'defaultValue|outside_length';
            }
            if (!$this->passesAdditionalValidation($defaultValue, $data['additionalValidation'] ?? 'none')) {
                $errors[] = 'defaultValue|invalid_additional_validation';
            }
        }

        return $errors;
    }

    private function passesAdditionalValidation(string $value, mixed $additionalValidation): bool
    {
        return match ($additionalValidation) {
            'none' => true,
            'phone' => preg_match('/^(?=.*\d)[0-9+().\s-]+$/D', $value) === 1,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            default => true,
        };
    }
}
