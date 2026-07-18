<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use BlockBuilder\BlockGenerator\Generation\Plan\Support\SectionedCodeFragmentBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\Support\UniqueContributionCollection;
use InvalidArgumentException;

final class FrontendAssetGenerationPlanBuilder
{
    public const string SECTION_SETUP = 'setup';
    public const string SECTION_BASE = 'base';
    public const string SECTION_FIELDS = 'fields';
    public const string SECTION_REPEATABLE_ENTRIES = 'repeatable_entries';

    private UniqueContributionCollection $capabilities;
    private SectionedCodeFragmentBuilder $fragments;

    /**
     * @param string[] $allowedSections
     */
    private function __construct(
        private readonly string $assetName,
        private readonly array $allowedSections,
    ) {
        $this->capabilities = new UniqueContributionCollection($assetName . ' capabilities');
        $this->fragments = new SectionedCodeFragmentBuilder($assetName . ' fragments');
    }

    public static function createForJavaScript(): self
    {
        return new self(
            assetName: 'JavaScript',
            allowedSections: [
                self::SECTION_SETUP,
                self::SECTION_FIELDS,
                self::SECTION_REPEATABLE_ENTRIES,
            ],
        );
    }

    public static function createForStylesheet(): self
    {
        return new self(
            assetName: 'CSS',
            allowedSections: [
                self::SECTION_BASE,
                self::SECTION_FIELDS,
                self::SECTION_REPEATABLE_ENTRIES,
            ],
        );
    }

    public function requireCapability(string $capability): self
    {
        $capability = trim($capability);
        if ($capability === '') {
            throw new InvalidArgumentException(sprintf(
                'A %s capability cannot be empty.',
                $this->assetName,
            ));
        }
        $this->capabilities->add($capability, $capability);

        return $this;
    }

    public function addFragment(string $section, CodeFragment $fragment): self
    {
        if (!in_array($section, $this->allowedSections, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown %s generation section "%s".',
                $this->assetName,
                $section,
            ));
        }

        $this->fragments->add($section, $fragment);

        return $this;
    }

    public function build(): FrontendAssetGenerationPlan
    {
        return new FrontendAssetGenerationPlan(
            capabilities: $this->capabilities->values(),
            fragmentsBySection: $this->fragments->build(),
        );
    }
}
