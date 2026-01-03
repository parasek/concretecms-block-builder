<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\HtmlEditorFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpHtmlEditorStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldTypeTrait;

class HtmlEditorFieldType implements FieldTypeInterface
{
    use FieldTypeTrait;

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::HtmlEditor;
    }

    public static function getHandle(): string
    {
        return 'html_editor';
    }

    public static function getLabel(): string
    {
        return t('HTML Editor');
    }

    public static function getIcon(): string
    {
        return t('fas fa-code');
    }

    public static function getDefaultValues(): array
    {
        return [];
    }

    public static function createDtoFromArray(array $data): HtmlEditorFieldTypeDto
    {
        return new HtmlEditorFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            htmlEditorHeight: !empty($data['htmlEditorHeight']) ? (int) $data['htmlEditorHeight'] : null
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpHtmlEditorStrategy::class;
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'htmlEditorHeight|invalid_number' => t('Invalid entry in one of "HTML Editor/Height" fields, should be a number between %s and %s or empty (%s).', 40, 2000, $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        // Height
        $height = $data['htmlEditorHeight'] ?? '';

        if ($height !== '') {
            $isInvalid = !ctype_digit((string) $height) || $height < 40 || $height > 2000;

            if ($isInvalid) {
                $errors[] = 'htmlEditorHeight|invalid_number';
            }
        }

        return $errors;
    }
}
