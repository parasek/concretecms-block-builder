<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

final class ValidationFeedbackBuilder
{
    private array $errors = [];
    private array $fieldsWithError = [];
    private array $tabsWithError = [];

    public function addError(?string $error, ?string $field, ?string $tab): void
    {
        if ($error !== null && $error !== '') {
            $this->errors[] = $error;
        }
        if ($field !== null && $field !== '') {
            $this->fieldsWithError[] = $field;
        }
        if ($tab !== null && $tab !== '') {
            $this->tabsWithError[] = $tab;
        }
    }

    public function build(): ValidationFeedback
    {
        return new ValidationFeedback(
            errors: array_values(array_unique($this->errors)),
            fieldsWithError: array_values(array_unique($this->fieldsWithError)),
            tabsWithError: array_values(array_unique($this->tabsWithError)),
        );
    }

    public function addFeedback(ValidationFeedback $feedback): void
    {
        foreach ($feedback->errors as $error) {
            $this->addError($error, null, null);
        }
        foreach ($feedback->fieldsWithError as $field) {
            $this->addError(null, $field, null);
        }
        foreach ($feedback->tabsWithError as $tab) {
            $this->addError(null, null, $tab);
        }
    }
}
