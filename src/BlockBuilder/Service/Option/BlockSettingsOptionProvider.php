<?php

declare(strict_types=1);

namespace BlockBuilder\Service\Option;

use Concrete\Core\Block\BlockType\Set as BlockTypeSet;

readonly class BlockSettingsOptionProvider
{
    public function getBlockTypeSets(bool $includeEmptyOption = false): array
    {
        t('Basic');
        t('Navigation');
        t('Forms');
        t('Express');
        t('Social Networking');
        t('Calendar & Events');
        t('Multimedia');

        $options = [];
        foreach (BlockTypeSet::getList() as $blockTypeSet) {
            $options[$blockTypeSet->getBlockTypeSetHandle()] = t($blockTypeSet->getBlockTypeSetName());
        }

        return $this->withOptionalEmpty($options, $includeEmptyOption);
    }

    public function getBooleanOptions(bool $includeEmptyOption = false): array
    {
        return $this->withOptionalEmpty([1 => 'true', 0 => 'false'], $includeEmptyOption);
    }

    public function getYesNoOptions(bool $includeEmptyOption = false): array
    {
        return $this->withOptionalEmpty([0 => t('No'), 1 => t('Yes')], $includeEmptyOption);
    }

    public function getDividerOptions(bool $includeEmptyOption = false): array
    {
        return $this->withOptionalEmpty([
            'smart' => t('Only if the field type consists of more than 1 element (default)'),
            'always' => t('Always'),
            'never' => t('Never'),
        ], $includeEmptyOption);
    }

    private function withOptionalEmpty(array $options, bool $includeEmptyOption): array
    {
        return $includeEmptyOption ? ['' => '---'] + $options : $options;
    }
}
