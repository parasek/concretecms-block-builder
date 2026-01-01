<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Request;

use BlockBuilder\Block\Validation\ValidationResult;
use BlockBuilder\Block\Validation\Validator\Block\BlockHandleValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockHeightValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockNameValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockWidthValidator;
use BlockBuilder\Block\Validation\Validator\Block\CsrfValidator;
use BlockBuilder\Block\Validation\Validator\Block\ExcludedFromRemovalValidator;
use BlockBuilder\Block\Validation\Validator\Block\LabelsValidator;
use BlockBuilder\Block\Validation\Validator\Block\PermissionsValidator;
use BlockBuilder\Block\Validation\Validator\Block\CustomBlockIconValidator;
use BlockBuilder\Block\Validation\Validator\FieldType\FieldTypeValidator;
use Symfony\Component\HttpFoundation\FileBag;

readonly class CreateBlockRequest
{
    public function __construct(
        private array $post,
        private FileBag $files,
        private BlockNameValidator $blockNameValidator,
        private BlockHandleValidator $blockHandleValidator,
        private BlockWidthValidator $blockWidthValidator,
        private BlockHeightValidator $blockHeightValidator,
        private CustomBlockIconValidator $customBlockIconValidator,
        private ExcludedFromRemovalValidator $excludedFromRemovalValidator,
        private LabelsValidator $labelsValidator,
        private PermissionsValidator $permissionsValidator,
        private CsrfValidator $csrfValidator,
        private FieldTypeValidator $fieldTypeValidator,
    ) {
    }

    public function validate(): ValidationResult
    {
        $errors = [];
        $fieldsWithError = [];
        $tabsWithError = [];

        $feedbacks = [
            $this->csrfValidator->validate($this->post),
            $this->permissionsValidator->validate($this->post),
            $this->blockNameValidator->validate($this->post),
            $this->blockHandleValidator->validate($this->post),
            $this->blockWidthValidator->validate($this->post),
            $this->blockHeightValidator->validate($this->post),
            $this->customBlockIconValidator->validate($this->post, $this->files),
            $this->excludedFromRemovalValidator->validate($this->post),
            $this->labelsValidator->validate($this->post),
            $this->fieldTypeValidator->validate($this->post),
        ];

        foreach ($feedbacks as $feedback) {
            $errors = array_merge($errors, $feedback->errors);
            $fieldsWithError = array_unique(array_merge($fieldsWithError, $feedback->fieldsWithError));
            $tabsWithError = array_unique(array_merge($tabsWithError, $feedback->tabsWithError));
        }

        $errors = array_values($errors);
        $fieldsWithError = array_values($fieldsWithError);
        $tabsWithError = array_values($tabsWithError);

        return new ValidationResult(
            isValid: empty($errors),
            data: empty($errors) ? $this->post : null,
            errors: $errors,
            fieldsWithError: $fieldsWithError,
            tabsWithError: $tabsWithError
        );
    }
}
