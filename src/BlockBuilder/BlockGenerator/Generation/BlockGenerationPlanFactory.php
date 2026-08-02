<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlan;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseIndex;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class BlockGenerationPlanFactory
{
    public function __construct(
        private FieldGenerationContributorRegistry $contributorRegistry,
    ) {
    }

    public function create(BlockConfigDto $config, BlockGenerationManifest $manifest): BlockGenerationPlan
    {
        // Collect all controller, database, form, and view contributions.
        $planBuilder = new BlockGenerationPlanBuilder();

        // Expose form-scope variables to the repeatable-entry rendering closure.
        $planBuilder->form
            ->addRepeatableCapturedVariable('app')
            ->addRepeatableCapturedVariable('formInstanceIdentifier');

        // Add the database columns required by every generated block.
        $this->addBaseDatabaseColumns($planBuilder, $config->entries !== []);

        // Ensure that at most one repeatable field provides the entry title.
        $this->validateRepeatableTitleSources($config->entries);

        // Add generation contributions from basic fields.
        $this->contributeFields(
            fields: $config->basic,
            fieldContext: FieldTypeContextEnum::BasicFields,
            config: $config,
            manifest: $manifest,
            planBuilder: $planBuilder,
        );

        // Add generation contributions from repeatable fields.
        $this->contributeFields(
            fields: $config->entries,
            fieldContext: FieldTypeContextEnum::RepeatableFields,
            config: $config,
            manifest: $manifest,
            planBuilder: $planBuilder,
        );

        // Convert the completed mutable builder into an immutable generation plan.
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
                $titleSourceHandles[] = $fieldDto->handle;
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
                type: 'integer',
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
                type: 'integer',
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
                type: 'integer',
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
                type: 'integer',
                unsigned: true,
                hasDefault: true,
                defaultValue: 0,
                order: -998,
            ),
        );
        $planBuilder->database->addIndex(
            FieldTypeContextEnum::RepeatableFields,
            new DatabaseIndex(
                name: 'bID',
                columns: ['bID', 'position'],
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
            $this->contributorRegistry->contribute(
                new FieldGenerationContext(
                    config: $config,
                    manifest: $manifest,
                    fieldType: $fieldDto->fieldType,
                    fieldDto: $fieldDto,
                    fieldContext: $fieldContext,
                    position: $position,
                ),
                $planBuilder,
            );
        }
    }
}
