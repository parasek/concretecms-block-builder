<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ViewPhp;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;

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
                    '{{VARIABLE_DOCUMENTATION}}' => $this->renderVariableDocumentation($context),
                    '{{ENTRY_DOCUMENTATION}}' => $this->renderEntryDocumentation(
                        $context->plan->view->entryKeys,
                    ),
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
            '<?php if (!empty($entries)): ?>%1$s    <?php foreach ($entries as $entry): ?>%1$s%2$s%1$s    <?php endforeach; ?>%1$s<?php endif; ?>',
            PHP_EOL,
            $this->indentCode($fields, 2),
        );
    }

    private function renderVariableDocumentation(BlockFileGenerationContext $context): string
    {
        $lines = array_map(
            fn(ViewVariableDocumentation $variable): string => sprintf(
                ' * @var %s $%s %s',
                $variable->type,
                $variable->name,
                $this->normalizeDescription($variable->description),
            ),
            $context->plan->view->variables,
        );

        if ($context->config->entries !== []) {
            $lines[] = ' * @var list<array<string, mixed>> $entries Repeatable entries';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param ViewVariableDocumentation[] $entryKeys
     */
    private function renderEntryDocumentation(array $entryKeys): string
    {
        if ($entryKeys === []) {
            return '';
        }

        $lines = [
            '/**',
            ' * Repeatable entry fields:',
        ];
        foreach ($entryKeys as $entryKey) {
            $lines[] = sprintf(
                ' * - %s: %s',
                $entryKey->name,
                $this->normalizeDescription($entryKey->description),
            );
        }
        $lines[] = ' *';
        $lines[] = ' * @var array{';
        foreach ($entryKeys as $entryKey) {
            $lines[] = sprintf(' *     %s?: %s,', $entryKey->name, $entryKey->type);
        }
        $lines[] = ' * } $entry';
        $lines[] = ' */';

        return PHP_EOL . implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function normalizeDescription(string $description): string
    {
        $description = str_replace('*/', '* /', $description);

        return preg_replace('/\s+/', ' ', trim($description)) ?? '';
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
