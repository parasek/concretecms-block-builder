<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

use BlockBuilder\Block\Request\CreateBlockInputNormalizer;
use BlockBuilder\Block\Validation\Validator\Block\CsrfValidator;
use BlockBuilder\Block\Validation\Validator\Block\PermissionsValidator;
use Symfony\Component\HttpFoundation\FileBag;

readonly class CreateBlockRequestValidator
{
    public function __construct(
        private CsrfValidator $csrfValidator,
        private PermissionsValidator $permissionsValidator,
        private CreateBlockInputNormalizer $inputNormalizer,
        private CreateBlockBusinessValidatorCollection $businessValidators,
    ) {
    }

    public function validate(array $data, FileBag $files): ValidationResult
    {
        $feedback = $this->csrfValidator->validate($data, $files);
        if ($feedback->errors !== []) {
            return $this->createResult([], $feedback);
        }

        $feedback = $this->permissionsValidator->validate($data, $files);
        if ($feedback->errors !== []) {
            return $this->createResult([], $feedback);
        }

        $normalizationResult = $this->inputNormalizer->normalize($data);
        if ($normalizationResult->feedback->errors !== []) {
            return $this->createResult($normalizationResult->data, $normalizationResult->feedback);
        }

        return $this->createResult(
            data: $normalizationResult->data,
            feedback: $this->businessValidators->validate($normalizationResult->data, $files),
        );
    }

    private function createResult(array $data, ValidationFeedback $feedback): ValidationResult
    {
        return new ValidationResult(
            data: $data,
            errors: $feedback->errors,
            fieldsWithError: $feedback->fieldsWithError,
            tabsWithError: $feedback->tabsWithError,
        );
    }
}
