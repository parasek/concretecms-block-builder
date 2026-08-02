<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\HtmlEditor;

use BlockBuilder\Block\Validation\IntegerValueValidator;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class HtmlEditorFieldType extends AbstractFieldType
{
    private const int MINIMUM_EDITOR_HEIGHT = 40;
    private const int MAXIMUM_EDITOR_HEIGHT = 2000;

    protected const array LEGACY_PROPERTY_ALIASES = [
        'htmlEditorHeight' => 'height',
    ];

    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::HtmlEditor;
    }

    public static function getLabel(): string
    {
        return t('HTML Editor');
    }

    public static function getIcon(): string
    {
        return 'fas fa-code';
    }

    public static function getDefaultValues(): array
    {
        return [
            'defaultValue' => '',
            'height' => '',
        ];
    }

    public static function createDtoFromArray(array $data): HtmlEditorFieldTypeDto
    {
        return new HtmlEditorFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            defaultValue: (string) ($data['defaultValue'] ?? ''),
            height: !empty($data['height']) ? (int) $data['height'] : null,
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'height|invalid_number' => t(
                'Invalid entry in one of "HTML Editor/Height" fields, should be a number between %s and %s or empty (%s).',
                self::MINIMUM_EDITOR_HEIGHT,
                self::MAXIMUM_EDITOR_HEIGHT,
                $context->getTabName(),
            ),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        $height = $data['height'] ?? '';
        $heightIsValid = $height === ''
            || IntegerValueValidator::isInRange(
                $height,
                self::MINIMUM_EDITOR_HEIGHT,
                self::MAXIMUM_EDITOR_HEIGHT,
            );
        if (!$heightIsValid) {
            $errors[] = 'height|invalid_number';
        }

        return $errors;
    }
}
