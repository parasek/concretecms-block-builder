<?php

declare(strict_types=1);

namespace BlockBuilder\DataProvider;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\Service\OptionListService;

readonly class BlockBuilderViewDataProvider
{
    public function __construct(
        private OptionListService $optionListService,
    ) {
    }

    public function getOptionLists(): array
    {
        return [
            'fieldTypes' => FieldTypeEnum::cases(),
            'blockTypeSets' => $this->optionListService->getBlockTypeSets(includeEmptyOption: true),
            'cacheBlockRecordOptions' => $this->optionListService->getCacheBlockRecordOptions(),
            'cacheBlockOutputOptions' => $this->optionListService->getCacheBlockOutputOptions(),
            'cacheBlockOutputOnPostOptions' => $this->optionListService->getCacheBlockOutputOnPostOptions(),
            'cacheBlockOutputForRegisteredUsersOptions' => $this->optionListService->getCacheBlockOutputForRegisteredUsersOptions(),
            'supportSavingNullValuesOptions' => $this->optionListService->getSupportSavingNullValuesOptions(),
            'ignorePageThemeGridFrameworkContainerOptions' => $this->optionListService->getIgnorePageThemeGridFrameworkContainerOptions(),
            'entriesAsFirstTabOptions' => $this->optionListService->getEntriesAsFirstTabOptions(),
            'highlightMultiElementFieldsOptions' => $this->optionListService->getHighlightMultiElementFieldsOptions(),
            'dividerOptions' => $this->optionListService->getDividerOptions(),
            'installBlockOptions' => $this->optionListService->getInstallBlockOptions(),
            'selectFieldTypes' => $this->optionListService->getSelectFieldTypes(),
            'selectMultipleFieldTypes' => $this->optionListService->getSelectMultipleFieldTypes(),
            'selectFieldListGenerationMethods' => $this->optionListService->getSelectFieldListGenerationMethods(),
        ];
    }

    public function getDefaultValues(): array
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
            'cacheBlockOutputForRegisteredUsers' => true,
            'supportSavingNullValues' => false,
            'ignorePageThemeGridFrameworkContainer' => false,
            'entriesAsFirstTab' => '',
            'highlightMultiElementFields' => true,
            'fieldsDivider' => 'never',
            'entryFieldsDivider' => 'never',
            'registerViewAssetsCustomCode' => '',
            'viewCustomCode' => '',
            'customControllerMethods' => '',
            'excludedFromRemoval' => ['templates'],
            'basic' => [],
            'entries' => [],
            'blockWidth' => 1000,
            'blockHeight' => 650,
            'installBlock' => true,
            'maxNumberOfEntries' => 0,
            // Labels
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
            'keepAddedEntryCollapsedLabel' => t('Keep added/copied entry collapsed'),
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
            'externalLinkLabel' => t('External Link'),
            'showAdditionalFieldsLabel' => t('Show additional fields'),
            'hideAdditionalFieldsLabel' => t('Hide additional fields'),
            'newWindowLabel' => t('Open in new window'),
            'noFollowLabel' => t('Add nofollow attribute'),
            'yesLabel' => t('Yes'),
            'noLabel' => t('No'),
            'overrideThumbnailDimensionsLabel' => t('Override Thumbnail dimensions'),
            'overrideFullscreenImageDimensionsLabel' => t('Override Fullscreen Image dimensions'),
            'widthLabel' => t('Width'),
            'heightLabel' => t('Height'),
            'cropLabel' => t('Crop'),
            'pxLabel' => t('px'),
            'nothingSelectedLabel' => t('Nothing selected'),
            'noResultsMatchedLabel' => t('No results matched {0}'),
            'selectAllLabel' => t('Select All'),
            'deselectAllLabel' => t('Deselect All'),
        ];
    }
}
