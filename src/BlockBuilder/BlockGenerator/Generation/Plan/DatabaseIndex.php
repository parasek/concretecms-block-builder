<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class DatabaseIndex
{
    /**
     * @param non-empty-list<string> $columns
     */
    public function __construct(
        public string $name,
        public array $columns,
    ) {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('The database index name "%s" is invalid.', $name));
        }
        if ($columns === []) {
            throw new \InvalidArgumentException(sprintf('The database index "%s" must contain at least one column.', $name));
        }

        $normalizedColumns = [];
        foreach ($columns as $column) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column) !== 1) {
                throw new \InvalidArgumentException(sprintf('The database index "%s" contains an invalid column name "%s".', $name, $column));
            }

            $normalizedColumn = strtolower($column);
            if (isset($normalizedColumns[$normalizedColumn])) {
                throw new \InvalidArgumentException(sprintf('The database index "%s" contains the column "%s" more than once.', $name, $column));
            }
            $normalizedColumns[$normalizedColumn] = true;
        }
    }
}
