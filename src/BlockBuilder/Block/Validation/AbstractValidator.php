<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

abstract class AbstractValidator
{
    /**
     * Messages displayed at the top of the form in a red alert box.
     */
    protected array $errors = [];

    /**
     * Fields that will be colored/outlined in red.
     */
    protected array $fieldsWithError = [];

    /**
     * Tabs that will be colored in red.
     */
    protected array $tabsWithError = [];

    /**
     * 'Errors', 'Fields with error' and 'Tabs with error' are being collected independent of each other.
     * That means you can add only one $error/$field/$tab or any combination of them.
     * All will be collected and sorted out at the end of the validation process.
     */
    protected function addError(?string $error, ?string $field, ?string $tab): void
    {
        if (!empty($error)) {
            $this->errors[] = $error;
        }
        if (!empty($field)) {
            $this->fieldsWithError[] = $field;
        }
        if (!empty($tab)) {
            $this->tabsWithError[] = $tab;
        }
    }

    protected function getValidationFeedback(): ValidationFeedback
    {
        return new ValidationFeedback(
            errors: $this->errors,
            fieldsWithError: $this->fieldsWithError,
            tabsWithError: $this->tabsWithError
        );
    }

    abstract public function validate(array $data): ValidationFeedback;
}
