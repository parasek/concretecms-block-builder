<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Dto\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlan;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class BlockGenerationPlanFactory
{
    public function __construct(
        private FieldGenerationContributorRegistry $contributorRegistry,
    ) {
    }

    public function create(BlockConfigDto $config, BlockGenerationManifest $manifest): BlockGenerationPlan
    {
        $planBuilder = new BlockGenerationPlanBuilder();
        $planBuilder->form
            ->addRepeatableCapturedVariable('app')
            ->addRepeatableCapturedVariable('uniqueId');
        $this->addBaseDatabaseColumns($planBuilder, $config->entries !== []);
        $this->validateRepeatableTitleSources($config->entries);

        $this->contributeFields(
            fields: $config->basic,
            fieldContext: FieldTypeContextEnum::BasicFields,
            config: $config,
            manifest: $manifest,
            planBuilder: $planBuilder,
        );
        $this->contributeFields(
            fields: $config->entries,
            fieldContext: FieldTypeContextEnum::RepeatableFields,
            config: $config,
            manifest: $manifest,
            planBuilder: $planBuilder,
        );

        if ($config->entries !== []) {
            $planBuilder->javaScript->requireCapability('repeatable_entries');
            $planBuilder->css->requireCapability('repeatable_entries');
        }

        return $planBuilder->build();
    }

    /**
     * @param FieldTypeDtoInterface[] $fields
     */
    private function validateRepeatableTitleSources(array $fields): void
    {
        $titleSourceHandles = [];
        foreach ($fields as $fieldDto) {
            if (property_exists($fieldDto, 'titleSource') && $fieldDto->titleSource === true) {
                $titleSourceHandles[] = property_exists($fieldDto, 'handle')
                    ? (string) $fieldDto->handle
                    : $fieldDto::class;
            }
        }

        if (count($titleSourceHandles) > 1) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Only one repeatable field may be used as the entry title source; received: %s.',
                implode(', ', $titleSourceHandles),
            ));
        }
    }

    private function addBaseDatabaseColumns(BlockGenerationPlanBuilder $planBuilder, bool $hasEntries): void
    {
        $planBuilder->database->addColumn(
            FieldTypeContextEnum::BasicFields,
            new DatabaseColumn(
                name: 'bID',
                type: 'I',
                primaryKey: true,
                unsigned: true,
                order: -1000,
            ),
        );

        if (!$hasEntries) {
            return;
        }

        $planBuilder->database->addColumn(
            FieldTypeContextEnum::RepeatableFields,
            new DatabaseColumn(
                name: 'id',
                type: 'I',
                primaryKey: true,
                unsigned: true,
                autoIncrement: true,
                order: -1000,
            ),
        );
        $planBuilder->database->addColumn(
            FieldTypeContextEnum::RepeatableFields,
            new DatabaseColumn(
                name: 'bID',
                type: 'I',
                unsigned: true,
                hasDefault: true,
                defaultValue: 0,
                order: -999,
            ),
        );
        $planBuilder->database->addColumn(
            FieldTypeContextEnum::RepeatableFields,
            new DatabaseColumn(
                name: 'position',
                type: 'I',
                unsigned: true,
                hasDefault: true,
                defaultValue: 0,
                order: -998,
            ),
        );
    }

    /**
     * @param FieldTypeDtoInterface[] $fields
     */
    private function contributeFields(
        array $fields,
        FieldTypeContextEnum $fieldContext,
        BlockConfigDto $config,
        BlockGenerationManifest $manifest,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        foreach ($fields as $position => $fieldDto) {
            $fieldType = $this->getFieldType($fieldDto);
            $this->contributorRegistry->contribute(
                new FieldGenerationContext(
                    config: $config,
                    manifest: $manifest,
                    fieldType: $fieldType,
                    fieldDto: $fieldDto,
                    fieldContext: $fieldContext,
                    position: $position,
                ),
                $planBuilder,
            );
        }
    }

    private function getFieldType(FieldTypeDtoInterface $fieldDto): FieldTypeEnum
    {
        if (!property_exists($fieldDto, 'fieldType') || !$fieldDto->fieldType instanceof FieldTypeEnum) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Field DTO "%s" does not expose a valid field type.',
                $fieldDto::class,
            ));
        }

        return $fieldDto->fieldType;
    }
}
