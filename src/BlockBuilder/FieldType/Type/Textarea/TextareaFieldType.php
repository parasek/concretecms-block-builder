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

    protected const array LEGACY_PROPERTY_ALIASES = [
        'textareaHeight' => 'maxHeight',
    ];

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::Textarea;
    }

    public static function getHandle(): string
    {
        return 'textarea';
    }

    public static function getLabel(): string
    {
        return t('Textarea');
    }

    public static function getIcon(): string
    {
        return t('fas fa-align-justify');
    }

    public static function getDefaultValues(): array
    {
        return [
            'displayZeroValue' => 0,
            'minHeight' => '',
            'maxHeight' => '',
        ];
    }

    public static function createDtoFromArray(array $data): TextareaFieldTypeDto
    {
        return new TextareaFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            displayZeroValue: !empty($data['displayZeroValue']),
            titleSource: !empty($data['titleSource']),
            minHeight: !empty($data['minHeight']) ? (int) $data['minHeight'] : null,
            maxHeight: !empty($data['maxHeight']) ? (int) $data['maxHeight'] : null,
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'minHeight|invalid_number' => t(
                'Invalid entry in one of "Textarea/Minimum height" fields, should be a number between %s and %s or empty (%s).',
                self::MINIMUM_HEIGHT,
                self::MAXIMUM_HEIGHT,
                $context->getTabName(),
            ),
            'maxHeight|invalid_number' => t(
                'Invalid entry in one of "Textarea/Maximum height" fields, should be a number between %s and %s or empty (%s).',
                self::MINIMUM_HEIGHT,
                self::MAXIMUM_HEIGHT,
                $context->getTabName(),
            ),
            'minHeight|greater_than_maximum' => t(
                'The minimum height of a Textarea cannot be greater than its maximum height (%s).',
                $context->getTabName(),
            ),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        $minimumHeight = $data['minHeight'] ?? '';
        $maximumHeight = $data['maxHeight'] ?? '';

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

        return $errors;
    }
}
