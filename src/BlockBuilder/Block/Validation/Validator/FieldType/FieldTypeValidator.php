<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\FieldType;

use BlockBuilder\Block\Service\ReservedWordsService;
use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeInterface;
use Symfony\Component\HttpFoundation\FileBag;

class FieldTypeValidator extends AbstractValidator
{
    public function __construct(
        private readonly ReservedWordsService $reservedWordsService,
    ) {
    }

    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        foreach (FieldTypeContextEnum::cases() as $context) {
            $contextHandle = $context->value;
            $fields = $data[$contextHandle] ?? [];
            // IMPORTANT
            // We need to reset keys here, since after $_POST request,
            // keys generated in templates will start from 0.
            $fields = array_values($fields);

            $this->processContextFields($fields, $context);
        }

        return $this->getValidationFeedback();
    }

    private function processContextFields(array $fields, FieldTypeContextEnum $context): void
    {
        $errorHandles = [];
        $fieldsWithErrors = [];
        $tabsWithError = [];

        $uniqueHandles = [];
        $errorMessages = $this->getErrorMessages($context);

        foreach ($fields as $key => $field) {
            // Validate fields that are shared across all Field Types
            $labelErrors = $this->validateLabel($field);
            $handleErrors = $this->validateHandle($field, $uniqueHandles);

            $errorHandles = array_merge($errorHandles, $labelErrors, $handleErrors);

            // Validate fields specific to the current Field Type
            $fieldTypeValue = $field['fieldType'] ?? null;
            if ($fieldTypeValue) {
                // Retrieve Field Type class
                $enum = FieldTypeEnum::fromHandle($fieldTypeValue);
                $class = $enum->getDefinitionClass();
                /** @var FieldTypeInterface $class */
                $fieldType = new $class();

                // Collect all error messages provided by the current Field Type
                $errorMessages = array_merge($errorMessages, $class::getErrorMessages($context));

                // Collect validation errors from the specific Field Type implementation
                $errorHandles = array_merge(
                    $errorHandles,
                    $fieldType->validate($field),
                );
            }

            // Add tabs and fields with errors (based on error handles)
            if (!empty($errorHandles)) {
                $tabsWithError[] = $context->getTabHandle();
                foreach ($errorHandles as $errorHandle) {
                    $extractedHandle = explode('|', $errorHandle)[0];
                    $fieldsWithErrors[] = $context->value . '[' . $key . '][' . $extractedHandle . ']';
                }
            }

            // Add an entry to the array that collects unique handles
            if (!empty($field['handle'])) {
                $uniqueHandles[] = $field['handle'];
            }
        }

        // Transform handles to human-readable messages
        $errors = [];
        foreach ($errorHandles as $errorHandle) {
            $errors[] = $errorMessages[$errorHandle] ?? $errorHandle;
        }

        $this->addContextErrors(
            errors: array_unique($errors),
            fields: array_unique($fieldsWithErrors),
            tabs: array_unique($tabsWithError),
        );
    }

    private function validateLabel(array $field): array
    {
        $errors = [];
        $label = $field['label'] ?? '';

        if (!$label) {
            $errors[] = 'label|empty';
        } elseif (mb_strlen($label) < 3) {
            $errors[] = 'label|less_than_3_characters';
        }

        return $errors;
    }

    private function validateHandle(array $field, array $uniqueHandles): array
    {
        $errors = [];
        $handle = $field['handle'] ?? '';

        if (!$handle) {
            $errors[] = 'handle|empty';

            return $errors;
        }

        if (mb_strlen($handle) < 3) {
            $errors[] = 'handle|less_than_3_characters';
        }
        if (mb_strlen($handle) > 50) {
            $errors[] = 'handle|more_than_50_characters';
        }
        if (!preg_match('/^[a-zA-Z_]+$/', $handle)) {
            $errors[] = 'handle|invalid_characters';
        }
        if (str_starts_with($handle, '_') || str_ends_with($handle, '_')) {
            $errors[] = 'handle|start_or_end_with_underscore';
        }
        if (preg_match('/[_]{2,}/', $handle)) {
            $errors[] = 'handle|consecutive_underscores';
        }
        if (!ctype_lower(mb_substr($handle, 0, 1))) {
            $errors[] = 'handle|first_character_not_lowercase';
        }
        if (!$this->reservedWordsService->isHandleAllowed($handle)) {
            $errors[] = 'handle|forbidden_word';
        }
        if (in_array($handle, $uniqueHandles)) {
            $errors[] = 'handle|repeated_handle';
        }

        return $errors;
    }

    private function addContextErrors(array $errors, array $fields, array $tabs): void
    {
        foreach ($errors as $error) {
            $this->addError(error: $error, field: null, tab: null);
        }
        foreach ($fields as $field) {
            $this->addError(error: null, field: $field, tab: null);
        }
        foreach ($tabs as $tab) {
            $this->addError(error: null, field: null, tab: $tab);
        }
    }

    private function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'label|empty' => t('There are some empty "Label" fields (%s).', $context->getTabName()),
            'label|less_than_3_characters' => t('There are some "Label" fields which consist of less than %s characters (%s).', 3, $context->getTabName()),

            'handle|empty' => t('There are some empty "Handle" fields (%s).', $context->getTabName()),
            'handle|less_than_3_characters' => t('There are some "Handle" fields which consist of less than %s characters (%s).', 3, $context->getTabName()),
            'handle|more_than_50_characters' => t('There are some "Handle" fields which consist of more than %s characters (%s).', 50, $context->getTabName()),
            'handle|invalid_characters' => t('There are some "Handle" fields which consist of characters other than a-zA-Z_ (%s).', $context->getTabName()),
            'handle|start_or_end_with_underscore' => t('There are some "Handle" fields which start or end with underscore (%s).', $context->getTabName()),
            'handle|consecutive_underscores' => t('There are some "Handle" fields which consist of two or more consecutive underscores (%s).', $context->getTabName()),
            'handle|first_character_not_lowercase' => t('There are some "Handle" fields which start with uppercase character (%s).', $context->getTabName()),
            'handle|forbidden_word' => t('There are some "Handle" fields which are forbidden words (%s).', $context->getTabName()),
            'handle|repeated_handle' => t('All "Handle" fields should be unique (%s).', $context->getTabName()),
        ];
    }
}
