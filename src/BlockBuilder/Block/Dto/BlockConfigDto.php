<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Dto;

use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class BlockConfigDto
{
    public function __construct(
        public ?string $blockBuilderVersion,
        public ?string $concreteVersion,
        public ?string $phpVersion,
        public ?string $createdAt,
        public string $blockName,
        public string $blockHandle,
        public ?string $blockDescription,
        public bool $installBlock,
        public int $blockWidth,
        public int $blockHeight,
        public ?string $blockTypeSet,
        public bool $cacheBlockRecord,
        public bool $cacheBlockOutput,
        public int $cacheBlockOutputLifetime,
        public bool $cacheBlockOutputOnPost,
        public bool $cacheBlockOutputOnEditMode,
        public bool $cacheBlockOutputForRegisteredUsers,
        public bool $supportSavingNullValues,
        public bool $ignorePageThemeGridFrameworkContainer,
        public bool $entriesAsFirstTab,
        public int $maxNumberOfEntries,
        public bool $highlightMultiElementFields,
        public ?string $messageBasicTab,
        public ?string $messageEntriesTab,
        public ?string $registerViewAssetsCustomCode,
        public ?string $viewCustomCode,
        public ?string $customControllerMethods,
        public array $excludedFromRemoval,
        public ?string $basicLabel,
        public ?string $entriesLabel,
        public ?string $settingsLabel,
        public ?string $addAtTheTopLabel,
        public ?string $addAtTheBottomLabel,
        public ?string $copyLastEntryLabel,
        public ?string $collapseAllLabel,
        public ?string $expandAllLabel,
        public ?string $removeAllLabel,
        public ?string $disableSmoothScrollLabel,
        public ?string $keepAddedEntryCollapsedLabel,
        public ?string $noEntriesFoundLabel,
        public ?string $maxNumberOfEntriesLabel,
        public ?string $removeEntryLabel,
        public ?string $duplicateEntryLabel,
        public ?string $duplicateEntryAndAddAtTheEndLabel,
        public ?string $areYouSureLabel,
        public ?string $requiredFieldsLabel,
        public ?string $urlEndingLabel,
        public ?string $urlEndingHelpTextLabel,
        public ?string $textLabel,
        public ?string $titleLabel,
        public ?string $altTextLabel,
        public ?string $linkFromSitemapLabel,
        public ?string $linkFromFileManagerLabel,
        public ?string $externalLinkLabel,
        public ?string $showAdditionalFieldsLabel,
        public ?string $hideAdditionalFieldsLabel,
        public ?string $newWindowLabel,
        public ?string $noFollowLabel,
        public ?string $yesLabel,
        public ?string $noLabel,
        public ?string $overrideThumbnailDimensionsLabel,
        public ?string $overrideFullscreenImageDimensionsLabel,
        public ?string $widthLabel,
        public ?string $heightLabel,
        public ?string $cropLabel,
        public ?string $pxLabel,
        public ?string $nothingSelectedLabel,
        public ?string $noResultsMatchedLabel,
        public ?string $selectAllLabel,
        public ?string $deselectAllLabel,
        /**
         * @var FieldTypeDtoInterface[]
         */
        public array $basic,
        /**
         * @var FieldTypeDtoInterface[]
         */
        public array $entries,
    ) {
    }

    public function hasFields(): bool
    {
        return $this->basic !== [] || $this->entries !== [];
    }
}
