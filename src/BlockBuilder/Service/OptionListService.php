<?php

declare(strict_types=1);

namespace BlockBuilder\Service;

use BlockBuilder\Block\Service\BlockTypeService;
use BlockBuilder\BlockGenerator\Enum\CreateBlockContextEnum;
use BlockBuilder\Environment\EnvironmentService;
use Concrete\Core\Block\BlockType\Set as BlockTypeSet;

readonly class OptionListService
{
    public function __construct(
        private BlockTypeService $blockTypeService,
        private EnvironmentService $environmentService,
    ) {
    }

    public function getBlockTypeSets(bool $includeEmptyOption = false): array
    {
        // These calls ensure Concrete's translation extractor picks up these strings
        t('Basic');
        t('Navigation');
        t('Forms');
        t('Express');
        t('Social Networking');
        t('Calendar & Events');
        t('Multimedia');

        $options = $includeEmptyOption ? $this->getEmptyOption() : [];

        foreach (BlockTypeSet::getList() as $blockTypeSet) {
            $options[$blockTypeSet->getBlockTypeSetHandle()] = t($blockTypeSet->getBlockTypeSetName());
        }

        return $options;
    }

    public function getBlockIcons(CreateBlockContextEnum $context, string $blockHandle): array
    {
        $icons = [];

        if ($context === CreateBlockContextEnum::Config) {
            // Skip if the block type folder/icon was already deleted
            // (for example, using js call in an alert message)
            $publicPath = $this->environmentService->getPublicPathToBlockIcon($blockHandle);
            if (file_exists(DIR_BASE . $publicPath)) {
                $icons[] = [
                    'path' => $publicPath,
                    'label' => t('Keep current icon'),
                ];
            }

        }

        $icons[] = [
            'path' => $this->environmentService->getPublicPathToDefaultBlockIcon(),
            'label' => t('Default Block Builder icon'),
        ];

        $concreteIcons = $this->blockTypeService->getBlockTypeIconPublicPaths();
        $allIcons = array_merge($icons, $concreteIcons);

        $options = [];
        foreach ($allIcons as $icon) {
            $options[$icon['path']] = $icon['label'];
        }

        return $options;
    }

    public function getCacheBlockRecordOptions(bool $includeEmptyOption = false): array
    {
        return $this->getStringBooleanOptions($includeEmptyOption);
    }

    public function getCacheBlockOutputOptions(bool $includeEmptyOption = false): array
    {
        return $this->getStringBooleanOptions($includeEmptyOption);
    }

    public function getCacheBlockOutputOnPostOptions(bool $includeEmptyOption = false): array
    {
        return $this->getStringBooleanOptions($includeEmptyOption);
    }

    public function getCacheBlockOutputForRegisteredUsersOptions(bool $includeEmptyOption = false): array
    {
        return $this->getStringBooleanOptions($includeEmptyOption);
    }

    public function getSupportSavingNullValuesOptions(bool $includeEmptyOption = false): array
    {
        return $this->getStringBooleanOptions($includeEmptyOption);
    }

    public function getIgnorePageThemeGridFrameworkContainerOptions(bool $includeEmptyOption = false): array
    {
        return $this->getStringBooleanOptions($includeEmptyOption);
    }

    public function getEntriesAsFirstTabOptions(bool $includeEmptyOption = false): array
    {
        return $this->getYesNoOptions($includeEmptyOption);
    }

    public function getHighlightMultiElementFieldsOptions(bool $includeEmptyOption = false): array
    {
        return $this->getYesNoOptions($includeEmptyOption);
    }

    public function getDividerOptions(bool $includeEmptyOption = false): array
    {
        $options = [
            'smart' => t('Only if the field type consists of more than 1 element (default)'),
            'always' => t('Always'),
            'never' => t('Never'),
        ];

        if ($includeEmptyOption) {
            $options = $this->getEmptyOption() + $options;
        }

        return $options;
    }

    public function getInstallBlockOptions(bool $includeEmptyOption = false): array
    {
        return $this->getYesNoOptions($includeEmptyOption);
    }

    public function getSelectFieldTypes(bool $includeEmptyOption = false): array
    {
        $options = [
            'default_select' => t('Default Select Field'),
            'enhanced_select' => t('Enhanced Select Field'),
            'radio_list' => t('Radio List'),
        ];

        if ($includeEmptyOption) {
            $options = $this->getEmptyOption() + $options;
        }

        return $options;
    }

    public function getSelectMultipleFieldTypes(bool $includeEmptyOption = false): array
    {
        $options = [
            'default_multiselect' => t('Default Multiselect Field'),
            'enhanced_multiselect' => t('Enhanced Multiselect Field'),
            'checkbox_list' => t('Checkbox List'),
        ];

        if ($includeEmptyOption) {
            $options = $this->getEmptyOption() + $options;
        }

        return $options;
    }

    public function getSelectFieldListGenerationMethods(bool $includeEmptyOption = false): array
    {
        $options = [
            'basic_list' => t('Basic list'),
            'custom_code' => t('Custom code'),
        ];

        if ($includeEmptyOption) {
            $options = $this->getEmptyOption() + $options;
        }

        return $options;
    }

    private function getStringBooleanOptions(bool $includeEmptyOption = false): array
    {
        $options = [
            1 => 'true',
            0 => 'false',
        ];

        if ($includeEmptyOption) {
            $options = $this->getEmptyOption() + $options;
        }

        return $options;
    }

    private function getYesNoOptions(bool $includeEmptyOption = false): array
    {
        $options = [
            0 => t('No'),
            1 => t('Yes'),
        ];

        if ($includeEmptyOption) {
            $options = $this->getEmptyOption() + $options;
        }

        return $options;
    }

    private function getEmptyOption(?string $label = null): array
    {
        return ['' => ($label ?? '---')];
    }
}
