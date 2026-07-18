<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Textarea;

use BlockBuilder\Block\Validation\IntegerValueValidator;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class TextareaFieldType extends AbstractFieldType
{
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
        return [];
    }

    public static function createDtoFromArray(array $data): TextareaFieldTypeDto
    {
        return new TextareaFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            titleSource: !empty($data['titleSource']),
            textareaHeight: !empty($data['textareaHeight']) ? (int) $data['textareaHeight'] : null,
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'textareaHeight|invalid_number' => t('Invalid entry in one of "Textarea/Height" fields, should be a number between %s and %s or empty (%s).', 40, 2000, $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        // Height
        $height = $data['textareaHeight'] ?? '';

        if ($height !== '') {
            $isInvalid = !IntegerValueValidator::isInRange($height, 40, 2000);

            if ($isInvalid) {
                $errors[] = 'textareaHeight|invalid_number';
            }
        }

        return $errors;
    }
}
