<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use BlockBuilder\BlockGenerator\Generation\Plan\Support\UniqueContributionCollection;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;

final class DatabaseGenerationPlanBuilder
{
    private UniqueContributionCollection $mainTableColumns;
    private UniqueContributionCollection $entriesTableColumns;
    private UniqueContributionCollection $mainTableIndexes;
    private UniqueContributionCollection $entriesTableIndexes;

    public function __construct()
    {
        $this->mainTableColumns = new UniqueContributionCollection('main database table columns');
        $this->entriesTableColumns = new UniqueContributionCollection('entries database table columns');
        $this->mainTableIndexes = new UniqueContributionCollection('main database table indexes');
        $this->entriesTableIndexes = new UniqueContributionCollection('entries database table indexes');
    }

    public function addColumn(FieldTypeContextEnum $context, DatabaseColumn $column): self
    {
        $collection = $context === FieldTypeContextEnum::BasicFields
            ? $this->mainTableColumns
            : $this->entriesTableColumns;
        $collection->add(strtolower($column->name), $column);

        return $this;
    }

    public function addIndex(FieldTypeContextEnum $context, DatabaseIndex $index): self
    {
        $collection = $context === FieldTypeContextEnum::BasicFields
            ? $this->mainTableIndexes
            : $this->entriesTableIndexes;
        $collection->add(strtolower($index->name), $index);

        return $this;
    }

    public function build(): DatabaseGenerationPlan
    {
        $mainTableColumns = $this->sortColumns($this->mainTableColumns->values());
        $entriesTableColumns = $this->sortColumns($this->entriesTableColumns->values());
        $mainTableIndexes = $this->mainTableIndexes->values();
        $entriesTableIndexes = $this->entriesTableIndexes->values();
        $this->validateIndexes($mainTableIndexes, $mainTableColumns, 'main');
        $this->validateIndexes($entriesTableIndexes, $entriesTableColumns, 'entries');

        return new DatabaseGenerationPlan(
            mainTableColumns: $mainTableColumns,
            entriesTableColumns: $entriesTableColumns,
            mainTableIndexes: $mainTableIndexes,
            entriesTableIndexes: $entriesTableIndexes,
        );
    }

    /**
     * @param DatabaseColumn[] $columns
     *
     * @return DatabaseColumn[]
     */
    private function sortColumns(array $columns): array
    {
        usort(
            $columns,
            static fn (DatabaseColumn $first, DatabaseColumn $second): int => $first->order <=> $second->order,
        );

        return $columns;
    }

    /**
     * @param DatabaseIndex[] $indexes
     * @param DatabaseColumn[] $columns
     */
    private function validateIndexes(array $indexes, array $columns, string $tableDescription): void
    {
        $columnNames = array_map(
            static fn (DatabaseColumn $column): string => strtolower($column->name),
            $columns,
        );

        foreach ($indexes as $index) {
            foreach ($index->columns as $column) {
                if (!in_array(strtolower($column), $columnNames, true)) {
                    throw new \LogicException(sprintf('Database index "%s" references missing column "%s" in the %s table.', $index->name, $column, $tableDescription));
                }
            }
        }
    }
}
