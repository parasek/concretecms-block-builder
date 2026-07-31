<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\LinkFromFileManager\Generation;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Generation\AbstractIndividualLinkFieldGenerationContributor;

final readonly class LinkFromFileManagerFieldGenerationContributor extends AbstractIndividualLinkFieldGenerationContributor
{
    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::LinkFromFileManager;
    }
}
