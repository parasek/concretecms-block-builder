<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use InvalidArgumentException;

final readonly class ControllerAsset
{
    public function __construct(
        public string $type,
        public ?string $handle = null,
    ) {
        if (trim($type) === '') {
            throw new InvalidArgumentException('A controller asset type cannot be empty.');
        }
        if ($handle !== null && trim($handle) === '') {
            throw new InvalidArgumentException('A controller asset handle cannot be empty when provided.');
        }
    }

    public function getKey(): string
    {
        return $this->type . ':' . ($this->handle ?? '');
    }
}
