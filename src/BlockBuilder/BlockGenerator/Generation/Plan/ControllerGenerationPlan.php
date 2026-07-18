<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class ControllerGenerationPlan
{
    /**
     * @param ControllerUseStatement[] $useStatements
     * @param ControllerProperty[] $properties
     * @param string[] $exportPageColumns
     * @param string[] $exportFileColumns
     * @param string[] $searchableBasicFields
     * @param string[] $searchableRepeatableFields
     * @param ControllerAsset[] $assets
     * @param array<string, CodeFragment[]> $methodFragmentsBySection
     */
    public function __construct(
        public array $useStatements,
        public array $properties,
        public array $exportPageColumns,
        public array $exportFileColumns,
        public array $searchableBasicFields,
        public array $searchableRepeatableFields,
        public array $assets,
        public array $methodFragmentsBySection,
    ) {
    }

    /**
     * @return CodeFragment[]
     */
    public function getMethodFragments(string $section): array
    {
        return $this->methodFragmentsBySection[$section] ?? [];
    }
}
