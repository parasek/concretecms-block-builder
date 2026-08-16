<?php

declare(strict_types=1);

namespace BlockBuilder\DataProvider;

use BlockBuilder\Block\Enum\BlockFormContextEnum;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\FieldType\FieldTypeRegistry;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use BlockBuilder\Service\Option\BlockIconOptionProvider;
use BlockBuilder\Service\Option\BlockSettingsOptionProvider;
use BlockBuilder\Service\Option\FieldTypeOptionProvider;
use Concrete\Core\Editor\EditorInterface;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

readonly class BlockBuilderViewDataProvider
{
    public function __construct(
        private BlockSettingsOptionProvider $blockSettingsOptions,
        private FieldTypeOptionProvider $fieldTypeOptions,
        private BlockIconOptionProvider $blockIconOptions,
        private FieldTypeRegistry $fieldTypeRegistry,
        private EnvironmentService $environmentService,
        private ResolverManagerInterface $urlResolver,
        private EditorInterface $editor,
    ) {
    }

    public function getCommonViewData(): array
    {
        return [
            'newBlockUrl' => $this->resolve('/dashboard/blocks/block_builder'),
            'configsUrl' => $this->resolve('/dashboard/blocks/block_builder/configs'),
            'navigationTabEnums' => NavigationTabEnum::cases(),
        ];
    }

    public function getDefaultFormValues(): array
    {
        return [
            'blockName' => '',
            'blockHandle' => '',
            'blockDescription' => '',
            'blockTypeSet' => '',
            'cacheBlockRecord' => true,
            'cacheBlockOutput' => true,
            'cacheBlockOutputLifetime' => 0,
            'cacheBlockOutputOnPost' => true,
            'cacheBlockOutputOnEditMode' => false,
            'cacheBlockOutputForRegisteredUsers' => true,
            'supportSavingNullValues' => false,
            'ignorePageThemeGridFrameworkContainer' => false,
            'entriesAsFirstTab' => false,
            'highlightMultiElementFields' => true,
            'messageBasicTab' => '',
            'messageEntriesTab' => '',
            'registerViewAssetsCustomCode' => '',
            'viewCustomCode' => '',
            'customControllerMethods' => '',
            'excludedFromRemoval' => ['templates'],
            'basic' => [],
            'entries' => [],
            'blockWidth' => 1200,
            'blockHeight' => 650,
            'installBlock' => true,
            'maxNumberOfEntries' => 0,
            'basicLabel' => t('Basic information'),
            'entriesLabel' => t('Entries'),
            'settingsLabel' => t('Settings'),
            'addAtTheTopLabel' => t('Add at the top'),
            'addAtTheBottomLabel' => t('Add at the bottom'),
            'copyLastEntryLabel' => t('Copy last entry'),
            'collapseAllLabel' => t('Collapse all'),
            'expandAllLabel' => t('Expand all'),
            'removeAllLabel' => t('Remove all'),
            'disableSmoothScrollLabel' => t('Disable smooth scroll'),
            'keepAddedEntryCollapsedLabel' => t('Keep added or copied entries collapsed'),
            'noEntriesFoundLabel' => t('No entries found.'),
            'maxNumberOfEntriesLabel' => t('Max. number of entries'),
            'removeEntryLabel' => t('Remove entry'),
            'duplicateEntryLabel' => t('Duplicate entry'),
            'duplicateEntryAndAddAtTheEndLabel' => t('Duplicate entry and add at the end'),
            'areYouSureLabel' => t('Are you sure?'),
            'requiredFieldsLabel' => t('Required fields'),
            'urlEndingLabel' => t('Custom string at the end of URL'),
            'urlEndingHelpTextLabel' => t('(e.g. #contact-form or ?ccm_paging_p=2)'),
            'textLabel' => t('Text'),
            'titleLabel' => t('Title'),
            'altTextLabel' => t('Alt text'),
            'linkFromSitemapLabel' => t('Link from Sitemap'),
            'linkFromFileManagerLabel' => t('Link from File Manager'),
            'externalLinkLabel' => t('External link'),
            'showAdditionalFieldsLabel' => t('Show additional fields'),
            'hideAdditionalFieldsLabel' => t('Hide additional fields'),
            'newWindowLabel' => t('Open in new window'),
            'noFollowLabel' => t('Add nofollow attribute'),
            'yesLabel' => t('Yes'),
            'noLabel' => t('No'),
            'overrideThumbnailDimensionsLabel' => t('Override thumbnail dimensions'),
            'overrideFullscreenImageDimensionsLabel' => t('Override fullscreen image dimensions'),
            'widthLabel' => t('Width'),
            'heightLabel' => t('Height'),
            'cropLabel' => t('Crop'),
            'pxLabel' => t('px'),
            'nothingSelectedLabel' => t('Nothing selected'),
            'noResultsMatchedLabel' => t('No results matched {0}'),
            'selectAllLabel' => t('Select all'),
            'deselectAllLabel' => t('Deselect all'),
        ];
    }

    public function getFormViewData(BlockFormContextEnum $context, string $blockHandle): array
    {
        return [
            'blockIconPreviewPath' => $blockHandle !== ''
                ? $this->environmentService->getPublicPathToBlockIcon($blockHandle)
                : $this->environmentService->getPublicPathToDefaultBlockIcon(),
            'fieldTypes' => $this->fieldTypeRegistry->all(),
            'blockTypeSets' => $this->blockSettingsOptions->getBlockTypeSets(includeEmptyOption: true),
            'blockIcons' => $this->blockIconOptions->getOptions(context: $context, blockHandle: $blockHandle),
            'cacheBlockRecordOptions' => $this->blockSettingsOptions->getBooleanOptions(),
            'cacheBlockOutputOptions' => $this->blockSettingsOptions->getBooleanOptions(),
            'cacheBlockOutputOnPostOptions' => $this->blockSettingsOptions->getBooleanOptions(),
            'cacheBlockOutputOnEditModeOptions' => $this->blockSettingsOptions->getBooleanOptions(),
            'cacheBlockOutputForRegisteredUsersOptions' => $this->blockSettingsOptions->getBooleanOptions(),
            'supportSavingNullValuesOptions' => $this->blockSettingsOptions->getBooleanOptions(),
            'ignorePageThemeGridFrameworkContainerOptions' => $this->blockSettingsOptions->getBooleanOptions(),
            'entriesAsFirstTabOptions' => $this->blockSettingsOptions->getYesNoOptions(),
            'highlightMultiElementFieldsOptions' => $this->blockSettingsOptions->getYesNoOptions(),
            'installBlockOptions' => $this->blockSettingsOptions->getYesNoOptions(),
            'editor' => $this->editor,
            'textAdditionalValidations' => $this->fieldTypeOptions->getTextAdditionalValidations(),
            'selectFieldTypes' => $this->fieldTypeOptions->getSingleChoiceTypes(),
            'selectMultipleFieldTypes' => $this->fieldTypeOptions->getMultipleChoiceTypes(),
            'selectFieldListGenerationMethods' => $this->fieldTypeOptions->getListGenerationMethods(),
            'filesFromFolderOrders' => $this->fieldTypeOptions->getFilesFromFolderOrders(),
        ];
    }

    private function resolve(string $path): string
    {
        return (string) $this->urlResolver->resolve([$path]);
    }
}
