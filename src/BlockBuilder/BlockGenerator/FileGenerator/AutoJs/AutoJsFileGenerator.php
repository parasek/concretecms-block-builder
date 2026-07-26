<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\AutoJs;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\FrontendAssetGenerationPlanBuilder;

readonly class AutoJsFileGenerator implements FileGeneratorInterface
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
                relativePath: 'auto.js',
                contents: $this->stubRenderer->render('auto.js.stub', [
                    '{{BLOCK_EVENT_NAME}}' => (string) $context->manifest->blockHandleKebabCase,
                    '{{SETUP_CODE}}' => $this->renderFragments(
                        $context->plan->javaScript->getFragments(FrontendAssetGenerationPlanBuilder::SECTION_SETUP),
                        indentation: 1,
                        emptyComment: '// No shared JavaScript setup is required.',
                    ),
                    '{{BASIC_FIELD_INITIALIZERS}}' => $this->renderFragments(
                        $context->plan->javaScript->getFragments(FrontendAssetGenerationPlanBuilder::SECTION_FIELDS),
                        indentation: 2,
                        emptyComment: '// No basic-field JavaScript initialization is required.',
                    ),
                    '{{REPEATABLE_FIELD_INITIALIZERS}}' => $this->renderFragments(
                        $context->plan->javaScript->getFragments(FrontendAssetGenerationPlanBuilder::SECTION_REPEATABLE_ENTRIES),
                        indentation: 2,
                        emptyComment: '// No repeatable-field JavaScript initialization is required.',
                    ),
                ]),
                producer: self::class,
            ),
        ];
    }

    /**
     * @param CodeFragment[] $fragments
     */
    private function renderFragments(array $fragments, int $indentation, string $emptyComment): string
    {
        if ($fragments === []) {
            $code = $emptyComment;
        } else {
            $code = implode(
                PHP_EOL . PHP_EOL,
                array_map(static fn(CodeFragment $fragment): string => trim($fragment->code), $fragments),
            );
        }

        $indent = str_repeat('    ', $indentation);

        return implode(
            PHP_EOL,
            array_map(
                static fn(string $line): string => $line === '' ? '' : $indent . rtrim($line),
                explode(PHP_EOL, $code),
            ),
        );
    }
}
