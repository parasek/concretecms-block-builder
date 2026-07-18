<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan\Support;

use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use InvalidArgumentException;

final class SectionedCodeFragmentBuilder
{
    /**
     * @var array<string, UniqueContributionCollection>
     */
    private array $sections = [];

    public function __construct(private readonly string $scope)
    {
    }

    public function add(string $section, CodeFragment $fragment): void
    {
        $section = trim($section);
        if ($section === '') {
            throw new InvalidArgumentException('A code fragment section cannot be empty.');
        }

        $this->sections[$section] ??= new UniqueContributionCollection(sprintf(
            '%s section "%s"',
            $this->scope,
            $section,
        ));
        $this->sections[$section]->add($fragment->key, $fragment);
    }

    /**
     * @return array<string, CodeFragment[]>
     */
    public function build(): array
    {
        $sections = [];
        foreach ($this->sections as $section => $collection) {
            $fragments = $collection->values();
            usort(
                $fragments,
                static fn(CodeFragment $first, CodeFragment $second): int => $first->order <=> $second->order,
            );
            $sections[$section] = $fragments;
        }

        return $sections;
    }
}
