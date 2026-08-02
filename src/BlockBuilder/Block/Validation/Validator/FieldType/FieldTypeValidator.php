<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\FieldType;

use BlockBuilder\Block\ReservedWord\ReservedHandleChecker;
use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Block\Validation\ValidationFeedbackBuilder;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\FieldTypeRegistry;
use BlockBuilder\Service\Option\FieldTypeOptionProvider;
use Symfony\Component\HttpFoundation\FileBag;

readonly class FieldTypeValidator implements ValidatorInterface
{
    public function __construct(
        private ReservedHandleChecker $reservedHandleChecker,
        private FieldTypeOptionProvider $fieldTypeOptions,
        private FieldTypeRegistry $fieldTypeRegistry,
    ) {
    }

    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $feedback = new ValidationFeedbackBuilder();

        foreach (FieldTypeContextEnum::cases() as $context) {
            $contextHandle = $context->value;
            $fields = $data[$contextHandle] ?? [];
            // IMPORTANT
            // We need to reset keys here, since after $_POST request,
            // keys generated in templates will start from 0.
            $fields = array_values($fields);

            $this->validateFieldsInContext($feedback, $fields, $context);
        }

        return $feedback->build();
    }

    private function validateFieldsInContext(ValidationFeedbackBuilder $feedback, array $fields, FieldTypeContextEnum $context): void
    {
        $errorKeys = [];
        $fieldsWithErrors = [];
        $tabsWithErrors = [];

        $normalizedHandles = [];
        $sharedErrorMessages = $this->getErrorMessages($context);
        $errorMessagesByFieldIndex = [];
        if ($context === FieldTypeContextEnum::RepeatableFields) {
            $titleSourceFieldIndices = [];
            foreach ($fields as $fieldIndex => $field) {
                if (is_array($field) && !empty($field['titleSource'])) {
                    $titleSourceFieldIndices[] = $fieldIndex;
                }
            }
            if (count($titleSourceFieldIndices) > 1) {
                $sharedErrorMessages['titleSource|multiple'] = t('Only one repeatable field can be used as the entry title source.');
                foreach ($titleSourceFieldIndices as $fieldIndex) {
                    $errorKeys[] = $fieldIndex . '|titleSource|multiple';
                }
            }
        }

        foreach ($fields as $fieldIndex => $field) {
            $errorMessagesByFieldIndex[$fieldIndex] = $sharedErrorMessages;

            // A. Validate fields that are shared across all Field Types

            // Get error keys from the label field
            // and prefix it with the current field key
            $label = isset($field['label']) && is_string($field['label']) ? $field['label'] : '';
            $labelErrors = $this->validateLabel($label);
            $indexedLabelErrorKeys = [];
            foreach ($labelErrors as $labelError) {
                $indexedLabelErrorKeys[] = $fieldIndex . '|' . $labelError;
            }

            // Get error keys from the handle field
            // and prefix it with the current field key
            $handle = isset($field['handle']) && is_string($field['handle']) ? $field['handle'] : '';
            $handleErrors = $this->validateHandle($handle, $normalizedHandles);
            $indexedHandleErrorKeys = [];
            foreach ($handleErrors as $handleError) {
                $indexedHandleErrorKeys[] = $fieldIndex . '|' . $handleError;
            }

            $errorKeys = array_merge($errorKeys, $indexedLabelErrorKeys, $indexedHandleErrorKeys);

            // B. Validate fields specific to the current Field Type
            $fieldTypeHandle = isset($field['fieldType']) && is_string($field['fieldType'])
                ? $field['fieldType']
                : null;
            foreach ($this->validateAllowedOptions($field, $fieldTypeHandle) as $optionError) {
                $errorKeys[] = $fieldIndex . '|' . $optionError;
            }

            if ($fieldTypeHandle === null || $fieldTypeHandle === '') {
                $errorKeys[] = $fieldIndex . '|fieldType|empty';
            } else {
                $fieldType = $this->fieldTypeRegistry->findByHandle($fieldTypeHandle);
                if ($fieldType === null) {
                    $errorKeys[] = $fieldIndex . '|fieldType|invalid';
                    continue;
                }

                // Keep field-specific messages scoped to the field type that produced them.
                $errorMessagesByFieldIndex[$fieldIndex] = array_merge(
                    $sharedErrorMessages,
                    $fieldType::getErrorMessages($context),
                );

                // Get error keys from the specific Field Type implementation
                // and prefix it with the current field key
                $fieldTypeErrorKeys = $fieldType->validate($field);
                $indexedFieldTypeErrorKeys = [];
                foreach ($fieldTypeErrorKeys as $errorKey) {
                    $indexedFieldTypeErrorKeys[] = $fieldIndex . '|' . $errorKey;
                }

                $errorKeys = array_merge(
                    $errorKeys,
                    $indexedFieldTypeErrorKeys,
                );
            }

            // Add an entry to the array that collects unique handles
            if ($handle !== '') {
                $normalizedHandles[] = strtolower($handle);
            }
        }

        // Add tabs and fields with errors based on error keys.
        $errors = [];
        if ($errorKeys !== []) {
            $tabsWithErrors[] = $context->getTabHandle();
            foreach ($errorKeys as $errorKey) {
                $errorKeyParts = explode('|', $errorKey);
                $fieldIndex = $errorKeyParts[0] ?? null;
                $propertyName = $errorKeyParts[1] ?? null;
                $errorCode = $errorKeyParts[2] ?? null;

                // Add fields with errors
                $fieldsWithErrors[] = $context->value . '[' . $fieldIndex . '][' . $propertyName . ']';

                // Add tabs with errors
                $messageKey = $propertyName . '|' . $errorCode;
                $fieldErrorMessages = $errorMessagesByFieldIndex[$fieldIndex] ?? $sharedErrorMessages;
                $errors[] = $fieldErrorMessages[$messageKey] ?? $messageKey;
            }
        }

        $this->addContextErrors(
            feedback: $feedback,
            errors: array_unique($errors),
            fields: array_unique($fieldsWithErrors),
            tabs: array_unique($tabsWithErrors),
        );
    }

    private function validateLabel(string $label): array
    {
        $errors = [];

        if ($label === '') {
            $errors[] = 'label|empty';
        } elseif (mb_strlen($label) < 3) {
            $errors[] = 'label|less_than_3_characters';
        }

        return $errors;
    }

    private function validateHandle(string $handle, array $uniqueHandles): array
    {
        $errors = [];

        if ($handle === '') {
            $errors[] = 'handle|empty';

            return $errors;
        }

        if (mb_strlen($handle) < 3) {
            $errors[] = 'handle|less_than_3_characters';
        }
        if (mb_strlen($handle) > 50) {
            $errors[] = 'handle|more_than_50_characters';
        }
        if (preg_match('/^[a-zA-Z_]+$/', $handle) !== 1) {
            $errors[] = 'handle|invalid_characters';
        }
        if (str_starts_with($handle, '_') || str_ends_with($handle, '_')) {
            $errors[] = 'handle|start_or_end_with_underscore';
        }
        if (preg_match('/_{2,}/', $handle) === 1) {
            $errors[] = 'handle|consecutive_underscores';
        }
        if (!ctype_lower(mb_substr($handle, 0, 1))) {
            $errors[] = 'handle|first_character_not_lowercase';
        }
        if (!$this->reservedHandleChecker->isHandleAllowed($handle)) {
            $errors[] = 'handle|forbidden_word';
        }
        if (in_array(strtolower($handle), $uniqueHandles, true)) {
            $errors[] = 'handle|repeated_handle';
        }

        return $errors;
    }

    private function addContextErrors(ValidationFeedbackBuilder $feedback, array $errors, array $fields, array $tabs): void
    {
        foreach ($errors as $error) {
            $feedback->addError(error: $error, field: null, tab: null);
        }
        foreach ($fields as $field) {
            $feedback->addError(error: null, field: $field, tab: null);
        }
        foreach ($tabs as $tab) {
            $feedback->addError(error: null, field: null, tab: $tab);
        }
    }

    private function validateAllowedOptions(array $field, ?string $fieldTypeHandle): array
    {
        $allowedValuesByProperty = match ($fieldTypeHandle) {
            'text_field' => [
                'additionalValidation' => $this->getAllowedOptionValues($this->fieldTypeOptions->getTextAdditionalValidations()),
            ],
            'select_field' => [
                'displayType' => $this->getAllowedOptionValues($this->fieldTypeOptions->getSingleChoiceTypes()),
                'addEmptyOption' => ['0', '1'],
                'listGenerationMethod' => $this->getAllowedOptionValues($this->fieldTypeOptions->getListGenerationMethods()),
            ],
            'select_multiple_field' => [
                'displayType' => $this->getAllowedOptionValues($this->fieldTypeOptions->getMultipleChoiceTypes()),
                'listGenerationMethod' => $this->getAllowedOptionValues($this->fieldTypeOptions->getListGenerationMethods()),
            ],
            'files_from_folder' => [
                'fileOrder' => $this->getAllowedOptionValues($this->fieldTypeOptions->getFilesFromFolderOrders()),
            ],
            default => [],
        };

        $errors = [];
        foreach ($allowedValuesByProperty as $propertyName => $allowedValues) {
            $value = $field[$propertyName] ?? null;
            if (!is_scalar($value) || !in_array((string) $value, $allowedValues, true)) {
                $errors[] = $propertyName . '|invalid_option';
            }
        }

        return $errors;
    }

    private function getAllowedOptionValues(array $options): array
    {
        return array_map('strval', array_keys($options));
    }

    private function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'fieldType|empty' => t('Some "Field type" fields are empty (%s).', $context->getTabName()),
            'fieldType|invalid' => t('Some "Field type" fields contain an unsupported value (%s).', $context->getTabName()),
            'label|empty' => t('Some "Label" fields are empty (%s).', $context->getTabName()),
            'label|less_than_3_characters' => t('Some "Label" fields contain fewer than %s characters (%s).', 3, $context->getTabName()),

            'handle|empty' => t('Some "Handle" fields are empty (%s).', $context->getTabName()),
            'handle|less_than_3_characters' => t('Some "Handle" fields contain fewer than %s characters (%s).', 3, $context->getTabName()),
            'handle|more_than_50_characters' => t('Some "Handle" fields contain more than %s characters (%s).', 50, $context->getTabName()),
            'handle|invalid_characters' => t('Some "Handle" fields contain characters other than a-zA-Z_ (%s).', $context->getTabName()),
            'handle|start_or_end_with_underscore' => t('Some "Handle" fields start or end with an underscore (%s).', $context->getTabName()),
            'handle|consecutive_underscores' => t('Some "Handle" fields contain two or more consecutive underscores (%s).', $context->getTabName()),
            'handle|first_character_not_lowercase' => t('Some "Handle" fields start with an uppercase character (%s).', $context->getTabName()),
            'handle|forbidden_word' => t('Some "Handle" fields use forbidden words (%s).', $context->getTabName()),
            'handle|repeated_handle' => t('All "Handle" fields must be unique (%s).', $context->getTabName()),
        ];
    }
}
