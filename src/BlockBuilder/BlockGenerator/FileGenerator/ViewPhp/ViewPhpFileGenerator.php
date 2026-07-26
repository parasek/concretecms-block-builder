<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ViewPhp;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewGenerationPlanBuilder;

readonly class ViewPhpFileGenerator implements FileGeneratorInterface
{
    public function __construct(private StubRenderer $stubRenderer)
    {
    }

    /**
     * @return list<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): array
    {
        return [
            new GeneratedTextFile(
                relativePath: FILENAME_BLOCK_VIEW,
                contents: $this->stubRenderer->render('view.php.stub', [
                    '{{SETUP}}' => $this->renderFragments(
                        $context->plan->view->getFragments(ViewGenerationPlanBuilder::SECTION_SETUP),
                    ),
                    '{{BASIC_FIELDS}}' => $this->renderFragments(
                        $context->plan->view->getFragments(ViewGenerationPlanBuilder::SECTION_BASIC_FIELDS),
                    ),
                    '{{REPEATABLE_SECTION}}' => $context->config->entries !== []
                        ? $this->renderRepeatableSection($context)
                        : '',
                ]),
                producer: self::class,
            ),
        ];
    }

    private function renderRepeatableSection(BlockFileGenerationContext $context): string
    {
        $fields = $this->renderFragments(
            $context->plan->view->getFragments(ViewGenerationPlanBuilder::SECTION_REPEATABLE_FIELDS),
        );

        return sprintf(
            '<?php if (isset($entries) && is_array($entries) && $entries !== []): ?>%1$s    <?php foreach ($entries as $entry): ?>%1$s%2$s%1$s    <?php endforeach; ?>%1$s<?php endif; ?>',
            PHP_EOL,
            $this->indentCode($fields, 2),
        );
    }

    /**
     * @param CodeFragment[] $fragments
     */
    private function renderFragments(array $fragments): string
    {
        return implode(
            PHP_EOL . PHP_EOL,
            array_map(static fn(CodeFragment $fragment): string => trim($fragment->code), $fragments),
        );
    }

    private function indentCode(string $code, int $indentation): string
    {
        $indent = str_repeat('    ', $indentation);

        return implode(
            PHP_EOL,
            array_map(
                static fn(string $line): string => $line === '' ? '' : $indent . rtrim($line),
                explode(PHP_EOL, trim($code)),
            ),
        );
    }
}
