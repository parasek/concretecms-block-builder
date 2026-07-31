<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class ViewGenerationPlan
{
    /**
     * @param array<string, CodeFragment[]> $fragmentsBySection
     * @param ViewVariableDocumentation[] $variables
     * @param ViewVariableDocumentation[] $entryKeys
     */
    public function __construct(
        public array $fragmentsBySection,
        public array $variables,
        public array $entryKeys,
    ) {
    }

    /**
     * @return CodeFragment[]
     */
    public function getFragments(string $section): array
    {
        return $this->fragmentsBySection[$section] ?? [];
    }
}
