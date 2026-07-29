<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Textarea;

use BlockBuilder\Block\Validation\IntegerValueValidator;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class TextareaFieldType extends AbstractFieldType
{
    private const int MINIMUM_MAX_HEIGHT = 66;
    private const int MAXIMUM_MAX_HEIGHT = 2000;

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
            maxHeight: !empty($data['maxHeight']) ? (int) $data['maxHeight'] : null,
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'maxHeight|invalid_number' => t(
                'Invalid entry in one of "Textarea/Maximum height" fields, should be a number between %s and %s or empty (%s).',
                self::MINIMUM_MAX_HEIGHT,
                self::MAXIMUM_MAX_HEIGHT,
                $context->getTabName(),
            ),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        $maxHeight = $data['maxHeight'] ?? '';

        if ($maxHeight !== '') {
            $isInvalid = !IntegerValueValidator::isInRange(
                $maxHeight,
                self::MINIMUM_MAX_HEIGHT,
                self::MAXIMUM_MAX_HEIGHT,
            );

            if ($isInvalid) {
                $errors[] = 'maxHeight|invalid_number';
            }
        }

        return $errors;
    }
}
