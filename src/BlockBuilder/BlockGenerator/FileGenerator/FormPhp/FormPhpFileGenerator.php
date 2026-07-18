<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\FormPhp;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\FormGenerationPlanBuilder;

readonly class FormPhpFileGenerator implements FileGeneratorInterface
{
    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    /**
     * @return iterable<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): iterable
    {
        yield new GeneratedTextFile(
            relativePath: FILENAME_FORM,
            contents: $this->stubRenderer->render('form.php.stub', [
                '{{SETUP}}' => $this->renderFragments(
                    $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_SETUP),
                ),
                '{{BASIC_FIELDS}}' => $this->renderFragments(
                    $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_BASIC_FIELDS),
                ),
                '{{SETTINGS}}' => $this->renderFragments(
                    $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_SETTINGS),
                ),
                '{{REPEATABLE_SECTION}}' => $context->config->entries !== []
                    ? $this->renderRepeatableSection($context)
                    : '',
                '{{VALIDATION}}' => $this->renderFragments(
                    $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_VALIDATION),
                ),
            ]),
            producer: self::class,
        );
    }

    private function renderRepeatableSection(BlockFileGenerationContext $context): string
    {
        $fieldFragments = $this->renderFragments(
            $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_REPEATABLE_FIELDS),
        );
        $config = $context->config;
        $entriesLabel = $config->entriesLabel ?: 'Entries';
        $addLabel = $config->addAtTheBottomLabel ?: 'Add entry';
        $removeLabel = $config->removeEntryLabel ?: 'Remove entry';
        $duplicateLabel = $config->duplicateEntryLabel ?: 'Duplicate entry';
        $maximumLabel = $config->maxNumberOfEntriesLabel ?: 'Maximum number of entries';

        $template = <<<'PHP'
<?php
$blockBuilderEntries = is_array($entries ?? null) ? array_values($entries) : [];
$renderBlockBuilderEntry = static function (int|string $entryIndex, array $entry) use ({{REPEATABLE_CAPTURE_LIST}}): void {
?>
<article
    class="card mb-3"
    data-entry
    data-entry-index="<?= h((string) $entryIndex); ?>"
    data-empty-entry-title="<?= h(t({{ENTRIES_LABEL}})); ?>"
>
    <div class="card-header d-flex align-items-center justify-content-between gap-3">
        <strong>
            <?= h(t({{ENTRIES_LABEL}})); ?> <span data-entry-position></span>
            <span data-entry-title></span>
        </strong>
        <div class="btn-group btn-group-sm" role="group">
            <button class="btn btn-secondary" type="button" data-entry-action="move-up" aria-label="<?= h(t('Move up')); ?>">
                <i class="fas fa-arrow-up" aria-hidden="true"></i>
            </button>
            <button class="btn btn-secondary" type="button" data-entry-action="move-down" aria-label="<?= h(t('Move down')); ?>">
                <i class="fas fa-arrow-down" aria-hidden="true"></i>
            </button>
            <button class="btn btn-secondary" type="button" data-entry-action="duplicate">
                <?= h(t({{DUPLICATE_LABEL}})); ?>
            </button>
            <button class="btn btn-danger" type="button" data-entry-action="remove">
                <?= h(t({{REMOVE_LABEL}})); ?>
            </button>
        </div>
    </div>
    <div class="card-body">
{{REPEATABLE_FIELDS}}
    </div>
</article>
<?php
};
?>
<section class="mt-4" data-block-builder-repeatable>
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <h3 class="h5 mb-0"><?= h(t({{ENTRIES_LABEL}})); ?></h3>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">
                <span data-entry-count>0</span>{{MAXIMUM_COUNTER}}
            </span>
            <button class="btn btn-secondary btn-sm" type="button" data-entry-action="add">
                <?= h(t({{ADD_LABEL}})); ?>
            </button>
        </div>
    </div>

    <script type="application/json" data-entry-data><?= json_encode(
        [
            'entries' => [],
            'defaultValues' => {{REPEATABLE_DEFAULT_VALUES}},
            'maxEntries' => {{MAXIMUM_ENTRIES}},
        ],
        JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ); ?></script>
    <div data-block-builder-entries data-max-entries="{{MAXIMUM_ENTRIES}}">
        <?php foreach ($blockBuilderEntries as $entryIndex => $entry): ?>
            <?php if (is_array($entry)): ?>
                <?php $renderBlockBuilderEntry($entryIndex, $entry); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <template data-entry-template>
        <?php $renderBlockBuilderEntry('__INDEX__', []); ?>
    </template>
</section>
PHP;

        $maximumCounter = $config->maxNumberOfEntries > 0
            ? sprintf(
                ' / <span title="<?= h(t(%s)); ?>">%d</span>',
                $this->phpLiteralFormatter->format($maximumLabel),
                $config->maxNumberOfEntries,
            )
            : '';

        return strtr($template, [
            '{{ENTRIES_LABEL}}' => $this->phpLiteralFormatter->format($entriesLabel),
            '{{ADD_LABEL}}' => $this->phpLiteralFormatter->format($addLabel),
            '{{REMOVE_LABEL}}' => $this->phpLiteralFormatter->format($removeLabel),
            '{{DUPLICATE_LABEL}}' => $this->phpLiteralFormatter->format($duplicateLabel),
            '{{MAXIMUM_COUNTER}}' => $maximumCounter,
            '{{MAXIMUM_ENTRIES}}' => (string) $config->maxNumberOfEntries,
            '{{REPEATABLE_CAPTURE_LIST}}' => $this->renderRepeatableCaptureList($context),
            '{{REPEATABLE_DEFAULT_VALUES}}' => $this->phpLiteralFormatter->format(
                $context->plan->form->repeatableDefaultValues,
            ),
            '{{REPEATABLE_FIELDS}}' => $this->indentCode($fieldFragments, 4),
        ]);
    }

    private function renderRepeatableCaptureList(BlockFileGenerationContext $context): string
    {
        $variableNames = array_values(array_unique([
            'form',
            'view',
            ...$context->plan->form->repeatableCapturedVariableNames,
        ]));
        sort($variableNames, SORT_STRING);

        return implode(
            ', ',
            array_map(static fn(string $variableName): string => '$' . $variableName, $variableNames),
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
