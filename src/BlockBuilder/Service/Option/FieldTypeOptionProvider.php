<?php

declare(strict_types=1);

namespace BlockBuilder\Service\Option;

readonly class FieldTypeOptionProvider
{
    public function getSingleChoiceTypes(bool $includeEmptyOption = false): array
    {
        return $this->withOptionalEmpty([
            'default_select' => t('Default select field'),
            'enhanced_select' => t('Enhanced select field'),
            'radio_list' => t('Radio list'),
        ], $includeEmptyOption);
    }

    public function getMultipleChoiceTypes(bool $includeEmptyOption = false): array
    {
        return $this->withOptionalEmpty([
            'default_multiselect' => t('Default multiselect field'),
            'enhanced_multiselect' => t('Enhanced multiselect field'),
            'checkbox_list' => t('Checkbox list'),
        ], $includeEmptyOption);
    }

    public function getListGenerationMethods(bool $includeEmptyOption = false): array
    {
        return $this->withOptionalEmpty([
            'basic_list' => t('Basic list'),
            'custom_code' => t('Custom code'),
        ], $includeEmptyOption);
    }

    private function withOptionalEmpty(array $options, bool $includeEmptyOption): array
    {
        return $includeEmptyOption ? ['' => '---'] + $options : $options;
    }
}
