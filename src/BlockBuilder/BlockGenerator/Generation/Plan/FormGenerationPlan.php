<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class FormGenerationPlan
{
    /**
     * @param array<string, CodeFragment[]> $fragmentsBySection
     * @param array<string, mixed> $repeatableDefaultValues
     * @param string[] $repeatableCapturedVariableNames
     */
    public function __construct(
        public array $fragmentsBySection,
        public array $repeatableDefaultValues,
        public array $repeatableCapturedVariableNames,
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
