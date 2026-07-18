<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan\Support;

use BlockBuilder\BlockGenerator\Exception\GenerationContributionConflictException;

final class UniqueContributionCollection
{
    private array $valuesByKey = [];

    public function __construct(private readonly string $scope)
    {
    }

    public function add(string $key, mixed $value): void
    {
        if (!array_key_exists($key, $this->valuesByKey)) {
            $this->valuesByKey[$key] = $value;

            return;
        }

        if ($this->valuesByKey[$key] == $value) {
            return;
        }

        throw new GenerationContributionConflictException(sprintf(
            'Conflicting contributions use key "%s" in %s.',
            $key,
            $this->scope,
        ));
    }

    public function values(): array
    {
        return array_values($this->valuesByKey);
    }
}
