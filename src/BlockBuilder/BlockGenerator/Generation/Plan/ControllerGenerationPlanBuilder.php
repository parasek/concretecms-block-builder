<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use BlockBuilder\BlockGenerator\Generation\Plan\Support\SectionedCodeFragmentBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\Support\UniqueContributionCollection;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use InvalidArgumentException;

final class ControllerGenerationPlanBuilder
{
    private UniqueContributionCollection $useStatements;
    private UniqueContributionCollection $properties;
    private UniqueContributionCollection $exportPageColumns;
    private UniqueContributionCollection $exportFileColumns;
    private UniqueContributionCollection $searchableBasicFields;
    private UniqueContributionCollection $searchableRepeatableFields;
    private UniqueContributionCollection $assets;
    private SectionedCodeFragmentBuilder $methodFragments;

    public function __construct()
    {
        $this->useStatements = new UniqueContributionCollection('controller use statements');
        $this->properties = new UniqueContributionCollection('controller properties');
        $this->exportPageColumns = new UniqueContributionCollection('controller page export columns');
        $this->exportFileColumns = new UniqueContributionCollection('controller file export columns');
        $this->searchableBasicFields = new UniqueContributionCollection('controller searchable basic fields');
        $this->searchableRepeatableFields = new UniqueContributionCollection('controller searchable repeatable fields');
        $this->assets = new UniqueContributionCollection('controller assets');
        $this->methodFragments = new SectionedCodeFragmentBuilder('controller method fragments');
    }

    public function addUseStatement(ControllerUseStatement $useStatement): self
    {
        $this->useStatements->add(strtolower($useStatement->getKey()), $useStatement);

        return $this;
    }

    public function addProperty(ControllerProperty $property): self
    {
        $this->properties->add($property->name, $property);

        return $this;
    }

    public function addExportPageColumn(string $column): self
    {
        $this->exportPageColumns->add($column, $column);

        return $this;
    }

    public function addExportFileColumn(string $column): self
    {
        $this->exportFileColumns->add($column, $column);

        return $this;
    }

    public function addSearchableField(FieldTypeContextEnum $context, string $fieldHandle): self
    {
        $collection = $context === FieldTypeContextEnum::BasicFields
            ? $this->searchableBasicFields
            : $this->searchableRepeatableFields;
        $collection->add($fieldHandle, $fieldHandle);

        return $this;
    }

    public function addAsset(ControllerAsset $asset): self
    {
        $this->assets->add($asset->getKey(), $asset);

        return $this;
    }

    public function addMethodFragment(string $section, CodeFragment $fragment): self
    {
        if (ControllerMethodSectionEnum::tryFrom($section) === null) {
            throw new InvalidArgumentException(sprintf('Unknown controller generation section "%s".', $section));
        }

        $this->methodFragments->add($section, $fragment);

        return $this;
    }

    public function build(): ControllerGenerationPlan
    {
        $properties = $this->properties->values();
        usort(
            $properties,
            static fn(ControllerProperty $first, ControllerProperty $second): int => $first->order <=> $second->order,
        );

        return new ControllerGenerationPlan(
            useStatements: $this->useStatements->values(),
            properties: $properties,
            exportPageColumns: $this->exportPageColumns->values(),
            exportFileColumns: $this->exportFileColumns->values(),
            searchableBasicFields: $this->searchableBasicFields->values(),
            searchableRepeatableFields: $this->searchableRepeatableFields->values(),
            assets: $this->assets->values(),
            methodFragmentsBySection: $this->methodFragments->build(),
        );
    }
}
