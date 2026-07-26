<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\AutoCss;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\Plan\FrontendAssetGenerationPlanBuilder;

readonly class AutoCssFileGenerator implements FileGeneratorInterface
{
    public function __construct(private StubRenderer $stubRenderer)
    {
    }

    /**
     * @return list<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): array
    {
        $parts = [rtrim($this->stubRenderer->render('auto.css.stub'))];
        foreach ([
            FrontendAssetGenerationPlanBuilder::SECTION_BASE,
            FrontendAssetGenerationPlanBuilder::SECTION_FIELDS,
            FrontendAssetGenerationPlanBuilder::SECTION_REPEATABLE_ENTRIES,
        ] as $section) {
            $fragments = $context->plan->css->getFragments($section);
            foreach ($fragments as $fragment) {
                $parts[] = trim($fragment->code);
            }
        }

        return [
            new GeneratedTextFile(
                relativePath: 'auto.css',
                contents: implode(PHP_EOL . PHP_EOL, array_filter($parts)) . PHP_EOL,
                producer: self::class,
            ),
        ];
    }
}
