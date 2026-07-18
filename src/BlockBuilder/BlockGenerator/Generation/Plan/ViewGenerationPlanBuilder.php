<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use BlockBuilder\BlockGenerator\Generation\Plan\Support\SectionedCodeFragmentBuilder;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use InvalidArgumentException;

final class ViewGenerationPlanBuilder
{
    public const string SECTION_SETUP = 'setup';
    public const string SECTION_BASIC_FIELDS = 'basic_fields';
    public const string SECTION_REPEATABLE_FIELDS = 'repeatable_fields';

    private SectionedCodeFragmentBuilder $fragments;

    public function __construct()
    {
        $this->fragments = new SectionedCodeFragmentBuilder('view fragments');
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
            throw new InvalidArgumentException(sprintf('Unknown view generation section "%s".', $section));
        }

        $this->fragments->add($section, $fragment);

        return $this;
    }

    public function build(): ViewGenerationPlan
    {
        return new ViewGenerationPlan($this->fragments->build());
    }
}
