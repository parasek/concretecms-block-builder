<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

readonly class ValidationResult
{
    public function __construct(
        public array $data = [],
        public array $errors = [],
        public array $fieldsWithError = [],
        public array $tabsWithError = [],
    ) {
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
