<?php

declare(strict_types=1);

namespace BlockBuilder\NavigationTab\Enum;

enum NavigationTabEnum
{
    case BlockSettings;
    case BuildOptions;
    case CustomCode;
    case Labels;
    case TabBasicInformation;
    case TabRepeatableEntries;

    public function getName(): string
    {
        return match ($this) {
            self::BlockSettings => t('Block settings'),
            self::BuildOptions => t('Build options'),
            self::CustomCode => t('Custom code'),
            self::Labels => t('Labels'),
            self::TabBasicInformation => t('Tab: Basic information'),
            self::TabRepeatableEntries => t('Tab: Repeatable entries'),
        };
    }

    public function getHandle(): string
    {
        return match ($this) {
            self::BlockSettings => 'block-settings',
            self::BuildOptions => 'build-options',
            self::CustomCode => 'custom-code',
            self::Labels => 'labels',
            self::TabBasicInformation => 'tab-basic-information',
            self::TabRepeatableEntries => 'tab-repeatable-entries',
        };
    }

    public function getTabContentElementName(): string
    {
        return 'navigation_tab_content/' . str_replace('-', '_', $this->getHandle());
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::BlockSettings => 'fas fa-wrench',
            self::BuildOptions => 'fas fa-cogs',
            self::CustomCode => 'fas fa-code',
            self::Labels => 'fas fa-book',
            self::TabBasicInformation => 'far fa-file',
            self::TabRepeatableEntries => 'far fa-copy',
        };
    }
}
