<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Dto;

use BlockBuilder\Block\Enum\PostGenerationBlockStateEnum;

readonly class CreateBlockResultDto
{
    public function __construct(
        public ?string $blockName,
        public ?string $blockHandle,
        public PostGenerationBlockStateEnum $postGenerationBlockState,
    ) {
    }
}
