<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use BlockBuilder\BlockGenerator\Generation\Plan\Support\UniqueContributionCollection;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;

final class DatabaseGenerationPlanBuilder
{
    private UniqueContributionCollection $mainTableColumns;
    private UniqueContributionCollection $entriesTableColumns;

    public function __construct()
    {
        $this->mainTableColumns = new UniqueContributionCollection('main database table columns');
        $this->entriesTableColumns = new UniqueContributionCollection('entries database table columns');
    }

    public function addColumn(FieldTypeContextEnum $context, DatabaseColumn $column): self
    {
        $collection = $context === FieldTypeContextEnum::BasicFields
            ? $this->mainTableColumns
            : $this->entriesTableColumns;
        $collection->add(strtolower($column->name), $column);

        return $this;
    }

    public function build(): DatabaseGenerationPlan
    {
        return new DatabaseGenerationPlan(
            mainTableColumns: $this->sortColumns($this->mainTableColumns->values()),
            entriesTableColumns: $this->sortColumns($this->entriesTableColumns->values()),
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
            static fn(DatabaseColumn $first, DatabaseColumn $second): int => $first->order <=> $second->order,
        );

        return $columns;
    }
}
