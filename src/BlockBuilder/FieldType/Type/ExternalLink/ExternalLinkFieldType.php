<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\ExternalLink;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\AbstractFieldType;

class ExternalLinkFieldType extends AbstractFieldType
{
    protected const array LEGACY_PROPERTY_ALIASES = [
        'externalLinkShowEndingField' => 'showEndingField',
        'externalLinkShowTextField' => 'showTextField',
        'externalLinkShowTitleField' => 'showTitleField',
        'externalLinkShowNewWindowField' => 'showNewWindowField',
        'externalLinkShowNoFollowField' => 'showNoFollowField',
    ];

    public static function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::ExternalLink;
    }

    public static function getLabel(): string
    {
        return t('External Link');
    }

    public static function getIcon(): string
    {
        return 'fas fa-external-link-alt';
    }

    public static function getDefaultValues(): array
    {
        return [
            'showEndingField' => false,
            'showTextField' => false,
            'showTitleField' => false,
            'showNewWindowField' => false,
            'showNoFollowField' => false,
        ];
    }

    public static function createDtoFromArray(array $data): ExternalLinkFieldTypeDto
    {
        return new ExternalLinkFieldTypeDto(
            fieldType: self::getFieldType(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            showEndingField: !empty($data['showEndingField']),
            showTextField: !empty($data['showTextField']),
            showTitleField: !empty($data['showTitleField']),
            showNewWindowField: !empty($data['showNewWindowField']),
            showNoFollowField: !empty($data['showNoFollowField']),
        );
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
