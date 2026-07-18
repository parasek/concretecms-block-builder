<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use InvalidArgumentException;

final readonly class DatabaseColumn
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $size = null,
        public bool $primaryKey = false,
        public bool $unsigned = false,
        public bool $autoIncrement = false,
        public bool $notNull = false,
        public bool $hasDefault = false,
        public string|int|float|bool|null $defaultValue = null,
        public int $order = 0,
    ) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException(sprintf('The database column name "%s" is invalid.', $name));
        }
        if (!preg_match('/^[A-Z][A-Z0-9]*$/', $type)) {
            throw new InvalidArgumentException(sprintf('The database column type "%s" is invalid.', $type));
        }
        if (!$hasDefault && $defaultValue !== null) {
            throw new InvalidArgumentException('A database default value requires hasDefault to be true.');
        }
    }
}
