<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlan;

readonly class BlockFileGenerationContext
{
    public function __construct(
        public BlockConfigDto $config,
        public BlockGenerationManifest $manifest,
        public BlockGenerationPlan $plan,
    ) {
    }
}
