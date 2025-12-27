<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

class ValidationFeedback
{
    public function __construct(
        public array $errors = [],
        public array $fieldsWithError = [],
        public array $tabsWithError = [],
    ) {
    }
}
