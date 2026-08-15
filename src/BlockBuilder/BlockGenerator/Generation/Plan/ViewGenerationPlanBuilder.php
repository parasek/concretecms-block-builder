<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use BlockBuilder\BlockGenerator\Generation\Plan\Support\SectionedCodeFragmentBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\Support\UniqueContributionCollection;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;

final class ViewGenerationPlanBuilder
{
    public const string SECTION_SETUP = 'setup';
    public const string SECTION_BASIC_FIELDS = 'basic_fields';
    public const string SECTION_REPEATABLE_FIELDS = 'repeatable_fields';

    private SectionedCodeFragmentBuilder $fragments;
    private UniqueContributionCollection $variables;
    private UniqueContributionCollection $entryKeys;

    public function __construct()
    {
        $this->fragments = new SectionedCodeFragmentBuilder('view fragments');
        $this->variables = new UniqueContributionCollection('documented view variables');
        $this->entryKeys = new UniqueContributionCollection('documented repeatable-entry keys');
    }

    public function addVariable(ViewVariableDocumentation $variable): self
    {
        $this->variables->add($variable->name, $variable);

        return $this;
    }

    public function addEntryKey(ViewVariableDocumentation $entryKey): self
    {
        $this->entryKeys->add($entryKey->name, $entryKey);

        return $this;
    }

    public function addFieldVariable(
        FieldTypeContextEnum $context,
        ViewVariableDocumentation $variable,
    ): self {
        return $context === FieldTypeContextEnum::BasicFields
            ? $this->addVariable($variable)
            : $this->addEntryKey($variable);
    }

    public function addFieldFragment(FieldTypeContextEnum $context, CodeFragment $fragment): self
    {
        return $this->addFragment(
            $context === FieldTypeContextEnum::BasicFields
                ? self::SECTION_BASIC_FIELDS
                : self::SECTION_REPEATABLE_FIELDS,
            $fragment,
        );
    }

    public function addFragment(string $section, CodeFragment $fragment): self
    {
        if (!in_array($section, [
            self::SECTION_SETUP,
            self::SECTION_BASIC_FIELDS,
            self::SECTION_REPEATABLE_FIELDS,
        ], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown view generation section "%s".', $section));
        }

        $this->fragments->add($section, $fragment);

        return $this;
    }

    public function build(): ViewGenerationPlan
    {
        return new ViewGenerationPlan(
            fragmentsBySection: $this->fragments->build(),
            variables: $this->sortDocumentation($this->variables->values()),
            entryKeys: $this->sortDocumentation($this->entryKeys->values()),
        );
    }

    /**
     * @param ViewVariableDocumentation[] $documentation
     *
     * @return ViewVariableDocumentation[]
     */
    private function sortDocumentation(array $documentation): array
    {
        usort(
            $documentation,
            static fn (ViewVariableDocumentation $first, ViewVariableDocumentation $second): int => [$first->order, $first->name] <=> [$second->order, $second->name],
        );

        return $documentation;
    }
}
