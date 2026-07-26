<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class ControllerGenerationPlan
{
    /**
     * @param ControllerUseStatement[] $useStatements
     * @param string[] $implementedInterfaces
     * @param ControllerProperty[] $properties
     * @param string[] $exportPageColumns
     * @param string[] $exportFileColumns
     * @param string[] $exportContentColumns
     * @param string[] $exportFileFolderColumns
     * @param string[] $requiredFeatureConstantNames
     * @param string[] $searchableBasicFields
     * @param string[] $searchableRepeatableFields
     * @param ControllerAsset[] $assets
     * @param array<string, CodeFragment[]> $methodFragmentsBySection
     */
    public function __construct(
        public array $useStatements,
        public array $implementedInterfaces,
        public array $properties,
        public array $exportPageColumns,
        public array $exportFileColumns,
        public array $exportContentColumns,
        public array $exportFileFolderColumns,
        public array $requiredFeatureConstantNames,
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
