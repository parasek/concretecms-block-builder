<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class CodeFragment
{
    public function __construct(
        public string $key,
        public string $code,
        public int $order = 0,
    ) {
        if (trim($key) === '') {
            throw new \InvalidArgumentException('A code fragment key cannot be empty.');
        }
        if ($code === '') {
            throw new \InvalidArgumentException('A code fragment cannot be empty.');
        }
    }
}
