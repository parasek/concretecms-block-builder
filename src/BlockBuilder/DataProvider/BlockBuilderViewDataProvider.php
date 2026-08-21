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
            'basicLabel' => 'Basic information',
            'entriesLabel' => 'Entries',
            'settingsLabel' => 'Settings',
            'addAtTheTopLabel' => 'Add at the top',
            'addAtTheBottomLabel' => 'Add at the bottom',
            'copyLastEntryLabel' => 'Copy last entry',
            'collapseAllLabel' => 'Collapse all',
            'expandAllLabel' => 'Expand all',
            'removeAllLabel' => 'Remove all',
            'disableSmoothScrollLabel' => 'Disable smooth scroll',
            'keepAddedEntryCollapsedLabel' => 'Keep added or copied entries collapsed',
            'noEntriesFoundLabel' => 'No entries found.',
            'maxNumberOfEntriesLabel' => 'Maximum number of entries',
            'removeEntryLabel' => 'Remove entry',
            'duplicateEntryLabel' => 'Duplicate entry',
            'duplicateEntryAndAddAtTheEndLabel' => 'Duplicate entry at the end',
            'areYouSureLabel' => 'Are you sure?',
            'requiredFieldsLabel' => 'Required fields',
            'urlEndingLabel' => 'Custom string at the end of the URL',
            'urlEndingHelpTextLabel' => '(e.g., #contact-form or ?ccm_paging_p=2)',
            'textLabel' => 'Text',
            'titleLabel' => 'Title',
            'altTextLabel' => 'Alt text',
            'linkFromSitemapLabel' => 'Link from Sitemap',
            'linkFromFileManagerLabel' => 'Link from File Manager',
            'externalLinkLabel' => 'External Link',
            'showAdditionalFieldsLabel' => 'Show additional fields',
            'hideAdditionalFieldsLabel' => 'Hide additional fields',
            'newWindowLabel' => 'Open in new window',
            'noFollowLabel' => 'Add the nofollow attribute',
            'yesLabel' => 'Yes',
            'noLabel' => 'No',
            'overrideThumbnailDimensionsLabel' => 'Override thumbnail dimensions',
            'overrideFullscreenImageDimensionsLabel' => 'Override fullscreen image dimensions',
            'widthLabel' => 'Width',
            'heightLabel' => 'Height',
            'cropLabel' => 'Crop',
            'pxLabel' => 'px',
            'nothingSelectedLabel' => 'Nothing selected',
            'noResultsMatchedLabel' => 'No results matched {0}',
            'selectAllLabel' => 'Select all',
            'deselectAllLabel' => 'Deselect all',
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
