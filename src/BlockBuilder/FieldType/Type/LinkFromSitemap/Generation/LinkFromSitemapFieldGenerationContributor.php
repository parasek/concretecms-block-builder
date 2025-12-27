<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\LinkFromSitemap\Generation;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Generation\AbstractIndividualLinkFieldGenerationContributor;

final readonly class LinkFromSitemapFieldGenerationContributor extends AbstractIndividualLinkFieldGenerationContributor
{
    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::LinkFromSitemap;
    }
}
