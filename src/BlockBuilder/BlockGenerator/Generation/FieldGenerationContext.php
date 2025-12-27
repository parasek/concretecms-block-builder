<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

final readonly class FieldGenerationContext
{
    public function __construct(
        public BlockConfigDto $config,
        public BlockGenerationManifest $manifest,
        public FieldTypeEnum $fieldType,
        public FieldTypeDtoInterface $fieldDto,
        public FieldTypeContextEnum $fieldContext,
        public int $position,
    ) {
        if ($position < 0) {
            throw new \InvalidArgumentException('The field position must be zero or greater.');
        }
    }

    public function isBasicField(): bool
    {
        return $this->fieldContext === FieldTypeContextEnum::BasicFields;
    }

    public function isRepeatableField(): bool
    {
        return $this->fieldContext === FieldTypeContextEnum::RepeatableFields;
    }
}
