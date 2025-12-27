<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class ViewVariableDocumentation
{
    public function __construct(
        public string $name,
        public string $type,
        public string $description,
        public int $order = 0,
    ) {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid documented view variable name "%s".', $name));
        }
        if (trim($type) === '') {
            throw new \InvalidArgumentException('A documented view variable type cannot be empty.');
        }
        if (trim($description) === '') {
            throw new \InvalidArgumentException('A documented view variable description cannot be empty.');
        }
    }
}
