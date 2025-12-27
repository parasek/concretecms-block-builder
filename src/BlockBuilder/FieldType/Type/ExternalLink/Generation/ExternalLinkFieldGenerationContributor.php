<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\ExternalLink\Generation;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Generation\AbstractIndividualLinkFieldGenerationContributor;

final readonly class ExternalLinkFieldGenerationContributor extends AbstractIndividualLinkFieldGenerationContributor
{
    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::ExternalLink;
    }
}
