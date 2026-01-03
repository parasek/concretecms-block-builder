<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\TextFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpTextStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldTypeTrait;

class TextFieldType implements FieldTypeInterface
{
    use FieldTypeTrait;

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
        return [];
    }

    public static function createDtoFromArray(array $data): TextFieldTypeDto
    {
        return new TextFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            titleSource: !empty($data['titleSource']),
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpTextStrategy::class;
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [];
    }

    public function validate(array $data): array
    {
        return [];
    }
}
