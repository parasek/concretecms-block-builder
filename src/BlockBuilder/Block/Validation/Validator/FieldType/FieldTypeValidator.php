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

class FieldTypeValidator implements ValidatorInterface
{
    public function __construct(
        private readonly ReservedHandleChecker $reservedHandleChecker,
        private readonly FieldTypeOptionProvider $fieldTypeOptions,
        private readonly FieldTypeRegistry $fieldTypeRegistry,
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

            $this->processContextFields($feedback, $fields, $context);
        }

        return $feedback->build();
    }

    private function processContextFields(ValidationFeedbackBuilder $feedback, array $fields, FieldTypeContextEnum $context): void
    {
        $errorHandles = [];
        $fieldsWithErrors = [];
        $tabsWithError = [];

        $uniqueHandles = [];
        $errorMessages = $this->getErrorMessages($context);
        if ($context === FieldTypeContextEnum::RepeatableFields) {
            $titleSourceKeys = [];
            foreach ($fields as $fieldKey => $field) {
                if (is_array($field) && !empty($field['titleSource'])) {
                    $titleSourceKeys[] = $fieldKey;
                }
            }
            if (count($titleSourceKeys) > 1) {
                $errorMessages['titleSource|multiple'] = t('Only one repeatable field can be used as the entry title source.');
                foreach ($titleSourceKeys as $titleSourceKey) {
                    $errorHandles[] = $titleSourceKey . '|titleSource|multiple';
                }
            }
        }

        foreach ($fields as $key => $field) {
            // A. Validate fields that are shared across all Field Types

            // Get error handles from the label field
            // and prefix it with the current field key
            $label = isset($field['label']) && is_string($field['label']) ? $field['label'] : '';
            $labelErrors = $this->validateLabel($label);
            $keyedLabelErrors = [];
            foreach ($labelErrors as $labelError) {
                $keyedLabelErrors[] = $key . '|' . $labelError;
            }

            // Get error handles from the handle field
            // and prefix it with the current field key
            $handle = isset($field['handle']) && is_string($field['handle']) ? $field['handle'] : '';
            $handleErrors = $this->validateHandle($handle, $uniqueHandles);
            $keyedHandleErrors = [];
            foreach ($handleErrors as $handleError) {
                $keyedHandleErrors[] = $key . '|' . $handleError;
            }

            $errorHandles = array_merge($errorHandles, $keyedLabelErrors, $keyedHandleErrors);

            // B. Validate fields specific to the current Field Type
            $fieldTypeValue = isset($field['fieldType']) && is_string($field['fieldType'])
                ? $field['fieldType']
                : null;
            foreach ($this->validateFieldTypeOptions($field, $fieldTypeValue) as $optionError) {
                $errorHandles[] = $key . '|' . $optionError;
            }

            if (!$fieldTypeValue) {
                $errorHandles[] = $key . '|fieldType|empty';
            } else {
                $fieldType = $this->fieldTypeRegistry->findByHandle($fieldTypeValue);
                if ($fieldType === null) {
                    $errorHandles[] = $key . '|fieldType|invalid';
                    continue;
                }

                // Collect all human-readable error messages provided by the current Field Type
                $errorMessages = array_merge($errorMessages, $fieldType::getErrorMessages($context));

                // Get error handles from the specific Field Type implementation
                // and prefix it with the current field key
                $fieldTypeErrorHandles = $fieldType->validate($field);
                $keyedFieldTypeErrorHandles = [];
                foreach ($fieldTypeErrorHandles as $errorItem) {
                    $keyedFieldTypeErrorHandles[] = $key . '|' . $errorItem;
                }

                $errorHandles = array_merge(
                    $errorHandles,
                    $keyedFieldTypeErrorHandles,
                );
            }

            // Add an entry to the array that collects unique handles
            if ($handle !== '') {
                $uniqueHandles[] = strtolower($handle);
            }
        }

        // Add tabs and fields with errors (based on error handles)
        $errors = [];
        if (!empty($errorHandles)) {
            $tabsWithError[] = $context->getTabHandle();
            foreach ($errorHandles as $errorHandle) {
                $errorHandleData = explode('|', $errorHandle);
                $extractedKey = $errorHandleData[0] ?? null;
                $extractedHandle = $errorHandleData[1] ?? null;
                $extractedErrorHandle = $errorHandleData[2] ?? null;

                // Add fields with errors
                $fieldsWithErrors[] = $context->value . '[' . $extractedKey . '][' . $extractedHandle . ']';

                // Add tabs with errors
                $transformedKey = $extractedHandle . '|' .$extractedErrorHandle;
                $errors[] = $errorMessages[$transformedKey] ?? $transformedKey;
            }
        }

        $this->addContextErrors(
            feedback: $feedback,
            errors: array_unique($errors),
            fields: array_unique($fieldsWithErrors),
            tabs: array_unique($tabsWithError),
        );
    }

    private function validateLabel(string $label): array
    {
        $errors = [];

        if (!$label) {
            $errors[] = 'label|empty';
        } elseif (mb_strlen($label) < 3) {
            $errors[] = 'label|less_than_3_characters';
        }

        return $errors;
    }

    private function validateHandle(string $handle, array $uniqueHandles): array
    {
        $errors = [];

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
        if (preg_match('/_{2,}/', $handle)) {
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

    private function validateFieldTypeOptions(array $field, ?string $fieldType): array
    {
        $optionMap = match ($fieldType) {
            'select_field' => [
                'selectType' => $this->getStringKeys($this->fieldTypeOptions->getSingleChoiceTypes()),
                'selectAddEmptyOption' => ['0', '1'],
                'selectListGenerationMethod' => $this->getStringKeys($this->fieldTypeOptions->getListGenerationMethods()),
            ],
            'select_multiple_field' => [
                'selectMultipleType' => $this->getStringKeys($this->fieldTypeOptions->getMultipleChoiceTypes()),
                'selectMultipleListGenerationMethod' => $this->getStringKeys($this->fieldTypeOptions->getListGenerationMethods()),
            ],
            default => [],
        };

        $errors = [];
        foreach ($optionMap as $handle => $allowedValues) {
            $value = $field[$handle] ?? null;
            if (!is_scalar($value) || !in_array((string) $value, $allowedValues, true)) {
                $errors[] = $handle . '|invalid_option';
            }
        }

        return $errors;
    }

    private function getStringKeys(array $options): array
    {
        return array_map('strval', array_keys($options));
    }

    private function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'selectType|invalid_option' => t('Some "Single Choice Field/Type" fields contain an invalid option (%s).', $context->getTabName()),
            'selectAddEmptyOption|invalid_option' => t('Some "Single Choice Field/Add an empty option" fields contain an invalid option (%s).', $context->getTabName()),
            'selectListGenerationMethod|invalid_option' => t('Some "Single Choice Field/List generation method" fields contain an invalid option (%s).', $context->getTabName()),
            'selectMultipleType|invalid_option' => t('Some "Multiple Choice Field/Type" fields contain an invalid option (%s).', $context->getTabName()),
            'selectMultipleListGenerationMethod|invalid_option' => t('Some "Multiple Choice Field/List generation method" fields contain an invalid option (%s).', $context->getTabName()),
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
