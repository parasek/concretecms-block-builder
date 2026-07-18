<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use InvalidArgumentException;

final readonly class ControllerProperty
{
    public function __construct(
        public string $name,
        public string $declaration,
        public int $order = 0,
    ) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException(sprintf('The controller property name "%s" is invalid.', $name));
        }
        if ($declaration === '') {
            throw new InvalidArgumentException('A controller property declaration cannot be empty.');
        }
    }
}
