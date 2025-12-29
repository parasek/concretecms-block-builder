<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\FieldType\LinkFromSitemapFieldType;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpLinkFromSitemapStrategy;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;

class LinkFromSitemapFieldType implements FieldTypeInterface
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::LinkFromSitemap;
    }

    public static function getHandle(): string
    {
        return 'link_from_sitemap';
    }

    public static function getLabel(): string
    {
        return t('Link from Sitemap');
    }

    public static function getIcon(): string
    {
        return t('fas fa-sitemap');
    }

    public static function createDtoFromArray(array $data): LinkFromSitemapFieldTypeDto
    {
        return new LinkFromSitemapFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            linkFromSitemapShowEndingField: !empty($data['linkFromSitemapShowEndingField']),
            linkFromSitemapShowTextField: !empty($data['linkFromSitemapShowTextField']),
            linkFromSitemapShowTitleField: !empty($data['linkFromSitemapShowTitleField']),
            linkFromSitemapShowNewWindowField: !empty($data['linkFromSitemapShowNewWindowField']),
            linkFromSitemapShowNoFollowField: !empty($data['linkFromSitemapShowNoFollowField']),
        );
    }

    public static function getControllerPhpStrategyClass(): string
    {
        return ControllerPhpLinkFromSitemapStrategy::class;
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
