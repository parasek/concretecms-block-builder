<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class FrontendAssetGenerationPlan
{
    /**
     * @param string[] $capabilities
     * @param array<string, CodeFragment[]> $fragmentsBySection
     */
    public function __construct(
        public array $capabilities,
        public array $fragmentsBySection,
    ) {
    }

    public function requires(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    /**
     * @return CodeFragment[]
     */
    public function getFragments(string $section): array
    {
        return $this->fragmentsBySection[$section] ?? [];
    }
}
