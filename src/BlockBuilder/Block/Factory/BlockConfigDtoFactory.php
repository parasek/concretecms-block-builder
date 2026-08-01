<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Factory;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Exception\InvalidConfigFieldDataException;
use BlockBuilder\Environment\Dto\EnvironmentDto;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Factory\FieldTypeDtoFactory;

readonly class BlockConfigDtoFactory
{
    public function __construct(
        private EnvironmentService $environmentService,
        private FieldTypeDtoFactory $fieldTypeDtoFactory,
    ) {
    }

    public function fromArray(array $data): BlockConfigDto
    {
        return $this->createFromArray($data, false, true);
    }

    public function fromGenerationArray(array $data): BlockConfigDto
    {
        return $this->createFromArray($data, true, true);
    }

    public function fromFormArray(array $data): BlockConfigDto
    {
        return $this->createFromArray($data, false, false);
    }

    private function createFromArray(
        array $data,
        bool $isBlockBeingGenerated,
        bool $validateChoiceOptions,
    ): BlockConfigDto
    {
        foreach (FieldTypeContextEnum::cases() as $fieldTypeContext) {
            $collectionName = $fieldTypeContext->value;
            if (!array_key_exists($collectionName, $data)) {
                $data[$collectionName] = [];
            }
        }
        $this->validateInputStructure($data);

        $environmentDto = $this->environmentService->getEnvironment();

        $excludedFromRemoval = $data['excludedFromRemoval'] ?? [];
        if (is_string($excludedFromRemoval)) {
            $excludedFromRemoval = $this->convertExcludedPathsToArray($excludedFromRemoval);
        }

        $basic = $this->transformFieldsToDto(
            $data[FieldTypeContextEnum::BasicFields->value] ?? [],
            $validateChoiceOptions,
        );
        $entries = $this->transformFieldsToDto(
            $data[FieldTypeContextEnum::RepeatableFields->value] ?? [],
            $validateChoiceOptions,
        );

        return new BlockConfigDto(
            blockBuilderVersion: $this->transformBlockBuilderVersion($data, $isBlockBeingGenerated, $environmentDto),
            concreteVersion: $this->transformConcreteVersion($data, $isBlockBeingGenerated, $environmentDto),
            phpVersion: $this->transformPhpVersion($data, $isBlockBeingGenerated, $environmentDto),
            createdAt: $this->transformCreatedAt($data, $isBlockBeingGenerated),
            blockName: trim($data['blockName'] ?? ''),
            blockHandle: trim($data['blockHandle'] ?? ''),
            blockDescription: trim($data['blockDescription'] ?? ''),
            installBlock: $this->transformZeroOneBoolField($data['installBlock'] ?? null),
            blockWidth: (int) ($data['blockWidth'] ?? 0),
            blockHeight: (int) ($data['blockHeight'] ?? 0),
            blockTypeSet: trim($data['blockTypeSet'] ?? ''),
            cacheBlockRecord: $this->transformTrueFalseBoolField($data['cacheBlockRecord'] ?? null),
            cacheBlockOutput: $this->transformTrueFalseBoolField($data['cacheBlockOutput'] ?? null),
            cacheBlockOutputLifetime: (int) ($data['cacheBlockOutputLifetime'] ?? 0),
            cacheBlockOutputOnPost: $this->transformTrueFalseBoolField($data['cacheBlockOutputOnPost'] ?? null),
            cacheBlockOutputOnEditMode: $this->transformTrueFalseBoolField($data['cacheBlockOutputOnEditMode'] ?? null),
            cacheBlockOutputForRegisteredUsers: $this->transformTrueFalseBoolField($data['cacheBlockOutputForRegisteredUsers'] ?? null),
            supportSavingNullValues: $this->transformTrueFalseBoolField($data['supportSavingNullValues'] ?? null),
            ignorePageThemeGridFrameworkContainer: $this->transformTrueFalseBoolField($data['ignorePageThemeGridFrameworkContainer'] ?? null),
            entriesAsFirstTab: $this->transformZeroOneBoolField($data['entriesAsFirstTab'] ?? null),
            maxNumberOfEntries: (int) ($data['maxNumberOfEntries'] ?? 0),
            highlightMultiElementFields: $this->transformZeroOneBoolField($data['highlightMultiElementFields'] ?? null),
            messageBasicTab: trim($data['messageBasicTab'] ?? ''),
            messageEntriesTab: trim($data['messageEntriesTab'] ?? ''),
            registerViewAssetsCustomCode: (string) ($data['registerViewAssetsCustomCode'] ?? ''),
            viewCustomCode: (string) ($data['viewCustomCode'] ?? ''),
            customControllerMethods: (string) ($data['customControllerMethods'] ?? ''),
            excludedFromRemoval: $excludedFromRemoval,
            basicLabel: trim($data['basicLabel'] ?? ''),
            entriesLabel: trim($data['entriesLabel'] ?? ''),
            settingsLabel: trim($data['settingsLabel'] ?? ''),
            addAtTheTopLabel: trim($data['addAtTheTopLabel'] ?? ''),
            addAtTheBottomLabel: trim($data['addAtTheBottomLabel'] ?? ''),
            copyLastEntryLabel: trim($data['copyLastEntryLabel'] ?? ''),
            collapseAllLabel: trim($data['collapseAllLabel'] ?? ''),
            expandAllLabel: trim($data['expandAllLabel'] ?? ''),
            removeAllLabel: trim($data['removeAllLabel'] ?? ''),
            disableSmoothScrollLabel: trim($data['disableSmoothScrollLabel'] ?? ''),
            keepAddedEntryCollapsedLabel: trim($data['keepAddedEntryCollapsedLabel'] ?? ''),
            noEntriesFoundLabel: trim($data['noEntriesFoundLabel'] ?? ''),
            maxNumberOfEntriesLabel: trim($data['maxNumberOfEntriesLabel'] ?? ''),
            removeEntryLabel: trim($data['removeEntryLabel'] ?? ''),
            duplicateEntryLabel: trim($data['duplicateEntryLabel'] ?? ''),
            duplicateEntryAndAddAtTheEndLabel: trim($data['duplicateEntryAndAddAtTheEndLabel'] ?? ''),
            areYouSureLabel: trim($data['areYouSureLabel'] ?? ''),
            requiredFieldsLabel: trim($data['requiredFieldsLabel'] ?? ''),
            urlEndingLabel: trim($data['urlEndingLabel'] ?? ''),
            urlEndingHelpTextLabel: $data['urlEndingHelpTextLabel'] ?? $data['urlEndingHelpText'] ?? '', // Backwards compatibility
            textLabel: trim($data['textLabel'] ?? ''),
            titleLabel: trim($data['titleLabel'] ?? ''),
            altTextLabel: trim($data['altTextLabel'] ?? ''),
            linkFromSitemapLabel: trim($data['linkFromSitemapLabel'] ?? ''),
            linkFromFileManagerLabel: trim($data['linkFromFileManagerLabel'] ?? ''),
            externalLinkLabel: trim($data['externalLinkLabel'] ?? ''),
            showAdditionalFieldsLabel: trim($data['showAdditionalFieldsLabel'] ?? ''),
            hideAdditionalFieldsLabel: trim($data['hideAdditionalFieldsLabel'] ?? ''),
            newWindowLabel: trim($data['newWindowLabel'] ?? ''),
            noFollowLabel: trim($data['noFollowLabel'] ?? ''),
            yesLabel: trim($data['yesLabel'] ?? ''),
            noLabel: trim($data['noLabel'] ?? ''),
            overrideThumbnailDimensionsLabel: trim($data['overrideThumbnailDimensionsLabel'] ?? ''),
            overrideFullscreenImageDimensionsLabel: trim($data['overrideFullscreenImageDimensionsLabel'] ?? ''),
            widthLabel: trim($data['widthLabel'] ?? ''),
            heightLabel: trim($data['heightLabel'] ?? ''),
            cropLabel: trim($data['cropLabel'] ?? ''),
            pxLabel: trim($data['pxLabel'] ?? ''),
            nothingSelectedLabel: trim($data['nothingSelectedLabel'] ?? ''),
            noResultsMatchedLabel: trim($data['noResultsMatchedLabel'] ?? ''),
            selectAllLabel: trim($data['selectAllLabel'] ?? ''),
            deselectAllLabel: trim($data['deselectAllLabel'] ?? ''),
            basic: $basic,
            entries: $entries,
        );
    }

    private function convertExcludedPathsToArray(string $data): array
    {
        $lines = explode(PHP_EOL, $data);

        return array_values(array_filter(array_map('trim', $lines)));
    }

    private function transformFieldsToDto(array $fields, bool $validateChoiceOptions): array
    {
        return array_values(array_map(
            callback: fn(array $fieldData) => $validateChoiceOptions
                ? $this->fieldTypeDtoFactory->fromArray($fieldData)
                : $this->fieldTypeDtoFactory->fromFormArray($fieldData),
            array: $fields
        ));
    }

    private function validateInputStructure(array $data): void
    {
        foreach (FieldTypeContextEnum::cases() as $fieldTypeContext) {
            $collectionName = $fieldTypeContext->value;
            if (!is_array($data[$collectionName])) {
                throw new InvalidConfigFieldDataException(
                    sprintf('The "%s" field collection must be an array.', $collectionName),
                );
            }

            foreach ($data[$collectionName] as $fieldIndex => $fieldData) {
                if (!is_array($fieldData)) {
                    throw new InvalidConfigFieldDataException(
                        sprintf('Field %s in the "%s" collection must be an array.', $fieldIndex, $collectionName),
                    );
                }
            }
        }

        foreach ($data as $propertyName => $value) {
            if (in_array($propertyName, [
                FieldTypeContextEnum::BasicFields->value,
                FieldTypeContextEnum::RepeatableFields->value,
                'excludedFromRemoval',
            ], true)) {
                continue;
            }

            if (is_array($value) || is_object($value) || is_resource($value)) {
                throw new InvalidConfigFieldDataException(
                    sprintf('Configuration property "%s" must contain a scalar value or null.', $propertyName),
                );
            }
        }

        $excludedFromRemoval = $data['excludedFromRemoval'] ?? [];
        if (!is_string($excludedFromRemoval) && !is_array($excludedFromRemoval)) {
            throw new InvalidConfigFieldDataException('The "excludedFromRemoval" property must be a string or an array of strings.');
        }

        if (is_array($excludedFromRemoval)) {
            foreach ($excludedFromRemoval as $excludedPath) {
                if (!is_string($excludedPath)) {
                    throw new InvalidConfigFieldDataException('Every "excludedFromRemoval" entry must be a string.');
                }
            }
        }
    }

    /**
     * Some fields in old configs had boolean fields as strings ("false", "true").
     * Now, booleans are being used there.
     */
    private function transformTrueFalseBoolField(mixed $value): bool
    {
        if (empty($value) || $value === 'false') {
            return false;
        }

        return true;
    }

    /**
     * Some fields in old configs had boolean fields as strings ("0", "1").
     * Now, booleans are being used there.
     */
    private function transformZeroOneBoolField(mixed $value): bool
    {
        return !empty($value);
    }

    private function transformBlockBuilderVersion(array $data, bool $isBlockBeingGenerated, EnvironmentDto $environmentDto): ?string
    {
        // This is run when building/rebuilding a block (and creating JSON config along with it).
        if ($isBlockBeingGenerated) {
            return $environmentDto->blockBuilderVersion;
        }

        // This is run when reading config created by a newer version of Block Builder.
        if (!empty($data['blockBuilderVersion'])) {
            return $data['blockBuilderVersion'];
        }

        // This is run when reading config created by the older version of Block Builder.
        // Field was called "version" back then.
        if (!empty($data['version'])) {
            return $data['version'];
        }

        // This is run when reading config created by the older version of Block Builder.
        // There was no "version" / "blockBuilderVersion" field back then.
        return null;
    }

    private function transformConcreteVersion(array $data, bool $isBlockBeingGenerated, EnvironmentDto $environmentDto): ?string
    {
        // This is run when building/rebuilding a block (and creating JSON config along with it).
        if ($isBlockBeingGenerated) {
            return $environmentDto->concreteVersion;
        }

        // This is run when reading config created by a newer version of Block Builder.
        if (!empty($data['concreteVersion'])) {
            return $data['concreteVersion'];
        }

        // This is run when reading config created by the older version of Block Builder.
        // There was no "concreteVersion" field back then.
        return null;
    }

    private function transformPhpVersion(array $data, bool $isBlockBeingGenerated, EnvironmentDto $environmentDto): ?string
    {
        // This is run when building/rebuilding a block (and creating JSON config along with it).
        if ($isBlockBeingGenerated) {
            return $environmentDto->phpVersion;
        }

        // This is run when reading config created by a newer version of Block Builder.
        if (!empty($data['phpVersion'])) {
            return $data['phpVersion'];
        }

        // This is run when reading config created by the older version of Block Builder.
        // There was no "phpVersion" field back then.
        return null;
    }

    private function transformCreatedAt(array $data, bool $isBlockBeingGenerated): ?string
    {
        // This is run when building/rebuilding a block (and creating JSON config along with it).
        if ($isBlockBeingGenerated) {
            return date('Y-m-d H:i:s');
        }

        // This is run when reading config
        if (!empty($data['createdAt'])) {
            return $data['createdAt'];
        }

        return null;
    }
}
