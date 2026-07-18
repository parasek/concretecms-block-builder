<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\FormCss;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\Plan\FrontendAssetGenerationPlanBuilder;

readonly class FormCssFileGenerator implements FileGeneratorInterface
{
    public function __construct(private StubRenderer $stubRenderer)
    {
    }

    /**
     * @return iterable<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): iterable
    {
        $parts = [rtrim($this->stubRenderer->render('css_files/form.css.stub'))];
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

        yield new GeneratedTextFile(
            relativePath: 'css_files/form.css',
            contents: implode(PHP_EOL . PHP_EOL, array_filter($parts)) . PHP_EOL,
            producer: self::class,
        );
    }
}
