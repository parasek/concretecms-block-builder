<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Textarea;

use BlockBuilder\Block\Validation\IntegerValueValidator;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class TextareaFieldType extends AbstractFieldType
{
    private const int MINIMUM_HEIGHT = 66;
    private const int MAXIMUM_HEIGHT = 2000;
    private const int MAXIMUM_LENGTH = 65535;

    protected const array LEGACY_PROPERTY_ALIASES = [
        'textareaHeight' => 'maxHeight',
    ];

    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::Textarea;
    }

    public static function getLabel(): string
    {
        return t('Textarea');
    }

    public static function getIcon(): string
    {
        return 'fas fa-align-justify';
    }

    public static function getDefaultValues(): array
    {
        return [
            'displayZeroValue' => 0,
            'defaultValue' => '',
            'placeholder' => '',
            'minimumLength' => '',
            'maximumLength' => '',
            'minHeight' => '',
            'maxHeight' => '',
        ];
    }

    public static function createDtoFromArray(array $data): TextareaFieldTypeDto
    {
        return new TextareaFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            displayZeroValue: !empty($data['displayZeroValue']),
            defaultValue: (string) ($data['defaultValue'] ?? ''),
            placeholder: trim((string) ($data['placeholder'] ?? '')),
            minimumLength: ($data['minimumLength'] ?? '') !== '' ? (int) $data['minimumLength'] : null,
            maximumLength: ($data['maximumLength'] ?? '') !== '' ? (int) $data['maximumLength'] : null,
            titleSource: !empty($data['titleSource']),
            minHeight: !empty($data['minHeight']) ? (int) $data['minHeight'] : null,
            maxHeight: !empty($data['maxHeight']) ? (int) $data['maxHeight'] : null,
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'minHeight|invalid_number' => t(
                'Some "Textarea/Minimum height" fields must contain a number between %s and %s or be empty (%s).',
                self::MINIMUM_HEIGHT,
                self::MAXIMUM_HEIGHT,
                $context->getTabName(),
            ),
            'maxHeight|invalid_number' => t(
                'Some "Textarea/Maximum height" fields must contain a number between %s and %s or be empty (%s).',
                self::MINIMUM_HEIGHT,
                self::MAXIMUM_HEIGHT,
                $context->getTabName(),
            ),
            'minHeight|greater_than_maximum' => t(
                'The minimum height of a Textarea cannot be greater than its maximum height (%s).',
                $context->getTabName(),
            ),
            'minimumLength|invalid_number' => t('Some "Textarea/Minimum length" fields must contain a number between 0 and %s or be empty (%s).', self::MAXIMUM_LENGTH, $context->getTabName()),
            'maximumLength|invalid_number' => t('Some "Textarea/Maximum length" fields must contain a number between 1 and %s or be empty (%s).', self::MAXIMUM_LENGTH, $context->getTabName()),
            'minimumLength|greater_than_maximum' => t('The minimum length of a Textarea cannot be greater than its maximum length (%s).', $context->getTabName()),
            'defaultValue|outside_length' => t('Some "Textarea/Default value" fields do not satisfy their configured minimum or maximum length (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        $minimumHeight = $data['minHeight'] ?? '';
        $maximumHeight = $data['maxHeight'] ?? '';
        $minimumLength = $data['minimumLength'] ?? '';
        $maximumLength = $data['maximumLength'] ?? '';

        $minimumHeightIsValid = $minimumHeight === ''
            || IntegerValueValidator::isInRange(
                $minimumHeight,
                self::MINIMUM_HEIGHT,
                self::MAXIMUM_HEIGHT,
            );
        if (!$minimumHeightIsValid) {
            $errors[] = 'minHeight|invalid_number';
        }

        $maximumHeightIsValid = $maximumHeight === ''
            || IntegerValueValidator::isInRange(
                $maximumHeight,
                self::MINIMUM_HEIGHT,
                self::MAXIMUM_HEIGHT,
            );
        if (!$maximumHeightIsValid) {
            $errors[] = 'maxHeight|invalid_number';
        }

        if (
            $minimumHeight !== ''
            && $maximumHeight !== ''
            && $minimumHeightIsValid
            && $maximumHeightIsValid
            && (int) $minimumHeight > (int) $maximumHeight
        ) {
            $errors[] = 'minHeight|greater_than_maximum';
        }

        $minimumLengthIsValid = $minimumLength === ''
            || IntegerValueValidator::isInRange($minimumLength, 0, self::MAXIMUM_LENGTH);
        $maximumLengthIsValid = $maximumLength === ''
            || IntegerValueValidator::isInRange($maximumLength, 1, self::MAXIMUM_LENGTH);
        if (!$minimumLengthIsValid) {
            $errors[] = 'minimumLength|invalid_number';
        }
        if (!$maximumLengthIsValid) {
            $errors[] = 'maximumLength|invalid_number';
        }
        if (
            $minimumLength !== ''
            && $maximumLength !== ''
            && $minimumLengthIsValid
            && $maximumLengthIsValid
            && (int) $minimumLength > (int) $maximumLength
        ) {
            $errors[] = 'minimumLength|greater_than_maximum';
        }

        $defaultValue = $data['defaultValue'] ?? '';
        if (is_scalar($defaultValue) && (string) $defaultValue !== '') {
            $defaultLength = mb_strlen(trim((string) $defaultValue));
            if (
                ($minimumLengthIsValid && $minimumLength !== '' && $defaultLength < (int) $minimumLength)
                || ($maximumLengthIsValid && $maximumLength !== '' && $defaultLength > (int) $maximumLength)
            ) {
                $errors[] = 'defaultValue|outside_length';
            }
        }

        return $errors;
    }
}
