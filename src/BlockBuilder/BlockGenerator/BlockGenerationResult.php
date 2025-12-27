<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use BlockBuilder\BlockGenerator\Enum\PostGenerationBlockStateEnum;

readonly class BlockGenerationResult
{
    public function __construct(
        public string $blockName,
        public string $blockHandle,
        public PostGenerationBlockStateEnum $postGenerationBlockState,
    ) {
    }
}
