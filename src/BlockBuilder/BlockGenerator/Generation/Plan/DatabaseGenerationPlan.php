<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class DatabaseGenerationPlan
{
    /**
     * @param DatabaseColumn[] $mainTableColumns
     * @param DatabaseColumn[] $entriesTableColumns
     */
    public function __construct(
        public array $mainTableColumns,
        public array $entriesTableColumns,
    ) {
    }
}
