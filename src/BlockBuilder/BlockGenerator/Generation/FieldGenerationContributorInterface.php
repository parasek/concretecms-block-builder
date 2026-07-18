<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;

/**
 * Adds one field type's structured requirements without rendering or writing complete files.
 */
interface FieldGenerationContributorInterface
{
    public function getFieldType(): FieldTypeEnum;

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void;
}
