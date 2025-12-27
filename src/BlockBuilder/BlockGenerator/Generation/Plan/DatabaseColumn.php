<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class DatabaseColumn
{
    private const array ALLOWED_TYPES = [
        'array',
        'bigint',
        'binary',
        'blob',
        'boolean',
        'date',
        'datetime',
        'datetimetz',
        'decimal',
        'float',
        'guid',
        'integer',
        'json_array',
        'object',
        'smallint',
        'string',
        'text',
        'time',
        'timestamp',
    ];

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
            throw new \InvalidArgumentException(sprintf('The database column name "%s" is invalid.', $name));
        }
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('The database column type "%s" is invalid.', $type));
        }
        if ($size !== null && preg_match('/^[1-9][0-9]*(?:\.[0-9]+)?$/', $size) !== 1) {
            throw new \InvalidArgumentException(sprintf('The database column size "%s" is invalid.', $size));
        }
        if (!$hasDefault && $defaultValue !== null) {
            throw new \InvalidArgumentException('A database default value requires hasDefault to be true.');
        }
    }
}
