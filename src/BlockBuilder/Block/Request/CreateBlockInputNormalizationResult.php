<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Request;

use BlockBuilder\Block\Validation\ValidationFeedback;

readonly class CreateBlockInputNormalizationResult
{
    public function __construct(
        public array $data,
        public ValidationFeedback $feedback,
    ) {
    }
}
