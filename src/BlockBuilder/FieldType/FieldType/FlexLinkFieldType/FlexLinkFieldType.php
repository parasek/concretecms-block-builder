<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\FlexLinkFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpFlexLinkStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;

class FlexLinkFieldType implements FieldTypeInterface
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::FlexLink;
    }

    public static function getHandle(): string
    {
        return 'link';
    }

    public static function getLabel(): string
    {
        return t('Flex Link');
    }

    public static function getIcon(): string
    {
        return t('fas fa-link');
    }

    public static function createDtoFromArray(array $data): FlexLinkFieldTypeDto
    {
        return new FlexLinkFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpFlexLinkStrategy::class;
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
