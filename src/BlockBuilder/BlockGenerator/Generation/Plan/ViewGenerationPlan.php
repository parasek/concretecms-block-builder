<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class ViewGenerationPlan
{
    /**
     * @param array<string, CodeFragment[]> $fragmentsBySection
     */
    public function __construct(public array $fragmentsBySection)
    {
    }

    /**
     * @return CodeFragment[]
     */
    public function getFragments(string $section): array
    {
        return $this->fragmentsBySection[$section] ?? [];
    }
}
