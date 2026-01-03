<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\ExternalLinkFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpExternalLinkStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldTypeTrait;

class ExternalLinkFieldType implements FieldTypeInterface
{
    use FieldTypeTrait;

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::ExternalLink;
    }

    public static function getHandle(): string
    {
        return 'external_link';
    }

    public static function getLabel(): string
    {
        return t('External Link');
    }

    public static function getIcon(): string
    {
        return t('fas fa-external-link-alt');
    }

    public static function getDefaultValues(): array
    {
        return [];
    }

    public static function createDtoFromArray(array $data): ExternalLinkFieldTypeDto
    {
        return new ExternalLinkFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            externalLinkShowEndingField: !empty($data['externalLinkShowEndingField']),
            externalLinkShowTextField: !empty($data['externalLinkShowTextField']),
            externalLinkShowTitleField: !empty($data['externalLinkShowTitleField']),
            externalLinkShowNewWindowField: !empty($data['externalLinkShowNewWindowField']),
            externalLinkShowNoFollowField: !empty($data['externalLinkShowNoFollowField']),
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpExternalLinkStrategy::class;
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
