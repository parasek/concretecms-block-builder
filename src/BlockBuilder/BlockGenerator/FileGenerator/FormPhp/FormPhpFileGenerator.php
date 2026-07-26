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
     * @return list<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): array
    {
        $basicFields = $this->renderFragments(
            $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_BASIC_FIELDS),
        );
        $settings = $this->renderFragments(
            $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_SETTINGS),
        );
        $repeatableSection = $context->config->entries !== []
            ? $this->renderRepeatableSection($context)
            : '';

        return [
            new GeneratedTextFile(
                relativePath: FILENAME_FORM,
                contents: $this->stubRenderer->render('form.php.stub', [
                    '{{SETUP}}' => $this->renderFragments(
                        $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_SETUP),
                    ),
                    '{{FORM_CONTENT}}' => $this->renderFormContent(
                        $context,
                        $basicFields,
                        $repeatableSection,
                        $settings,
                    ),
                    '{{VALIDATION}}' => $this->renderFragments(
                        $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_VALIDATION),
                    ),
                    '{{BLOCK_EVENT_NAME}}' => $context->manifest->blockHandleKebabCase,
                ]),
                producer: self::class,
            ),
        ];
    }

    private function renderFormContent(
        BlockFileGenerationContext $context,
        string $basicFields,
        string $repeatableSection,
        string $settings,
    ): string {
        $tabs = [
            'basic-information' => [
                'label' => $context->config->basicLabel ?: 'Basic information',
                'content' => $basicFields,
            ],
        ];
        if ($repeatableSection !== '') {
            $tabs['entries'] = [
                'label' => $context->config->entriesLabel ?: 'Entries',
                'content' => $repeatableSection,
            ];
        }
        $tabs['settings'] = [
            'label' => $context->config->settingsLabel ?: 'Settings',
            'content' => $settings,
        ];
        if ($context->config->entriesAsFirstTab && isset($tabs['entries'])) {
            $entriesTab = $tabs['entries'];
            unset($tabs['entries']);
            $tabs = ['entries' => $entriesTab, ...$tabs];
        }

        $tabDefinitions = [];
        $tabPanes = [];
        foreach ($tabs as $tabIndex => $tab) {
            $active = $tabDefinitions === [];
            $tabIdExpression = $this->phpLiteralFormatter->format($tabIndex . '-tab-')
                . ' . $formInstanceIdentifier';
            $tabDefinitions[] = sprintf(
                '    [%s, t(%s), %s],',
                $tabIdExpression,
                $this->phpLiteralFormatter->format($tab['label']),
                $active ? 'true' : 'false',
            );
            $tabPanes[] = sprintf(
                '<div class="tab-pane fade%s" id="<?= h(%s); ?>" role="tabpanel">%s%s%s</div>',
                $active ? ' show active' : '',
                $tabIdExpression,
                PHP_EOL,
                $this->indentCode($tab['content'], 1),
                PHP_EOL,
            );
        }

        return sprintf(
            '<div id="form-container-<?= h($formInstanceIdentifier); ?>" data-block-builder-form>%1$s<?php%1$s'
            . 'echo $userInterface->tabs([%1$s%2$s%1$s]);%1$s?>%1$s'
            . '<div class="tab-content mt-4">%1$s%3$s%1$s</div>%1$s</div>',
            PHP_EOL,
            implode(PHP_EOL, $tabDefinitions),
            $this->indentCode(implode(PHP_EOL, $tabPanes), 1),
        );
    }

    private function renderRepeatableSection(BlockFileGenerationContext $context): string
    {
        $fieldFragments = $this->renderFragments(
            $context->plan->form->getFragments(FormGenerationPlanBuilder::SECTION_REPEATABLE_FIELDS),
        );
        $config = $context->config;
        $labels = [
            'addAtTop' => $config->addAtTheTopLabel ?: 'Add at the top',
            'addAtBottom' => $config->addAtTheBottomLabel ?: 'Add at the bottom',
            'copyLast' => $config->copyLastEntryLabel ?: 'Copy last entry',
            'expandAll' => $config->expandAllLabel ?: 'Expand all',
            'collapseAll' => $config->collapseAllLabel ?: 'Collapse all',
            'removeAll' => $config->removeAllLabel ?: 'Delete all',
            'disableSmoothScroll' => $config->disableSmoothScrollLabel ?: 'Disable smooth scroll',
            'keepAddedCollapsed' => $config->keepAddedEntryCollapsedLabel ?: 'Keep added/copied entry collapsed',
            'noEntries' => $config->noEntriesFoundLabel ?: 'No entries found.',
            'maximum' => $config->maxNumberOfEntriesLabel ?: 'Maximum number of entries',
            'remove' => $config->removeEntryLabel ?: 'Delete entry',
            'duplicate' => $config->duplicateEntryLabel ?: 'Duplicate entry',
            'duplicateAtEnd' => $config->duplicateEntryAndAddAtTheEndLabel ?: 'Duplicate entry and add at the end',
            'confirm' => $config->areYouSureLabel ?: 'Are you sure?',
        ];

        $template = <<<'PHP'
<?php
$blockBuilderEntries = is_array($entries ?? null) ? array_values($entries) : [];
$translatedAddAtTopLabel = t({{ADD_AT_TOP_LABEL}});
$translatedAddAtBottomLabel = t({{ADD_AT_BOTTOM_LABEL}});
$translatedCopyLastLabel = t({{COPY_LAST_LABEL}});
$translatedExpandAllLabel = t({{EXPAND_ALL_LABEL}});
$translatedCollapseAllLabel = t({{COLLAPSE_ALL_LABEL}});
$translatedRemoveAllLabel = t({{REMOVE_ALL_LABEL}});
$translatedDisableSmoothScrollLabel = t({{DISABLE_SMOOTH_SCROLL_LABEL}});
$translatedKeepAddedCollapsedLabel = t({{KEEP_ADDED_COLLAPSED_LABEL}});
$translatedNoEntriesLabel = t({{NO_ENTRIES_LABEL}});
$translatedMaximumLabel = t({{MAXIMUM_LABEL}});
$translatedRemoveLabel = t({{REMOVE_LABEL}});
$translatedDuplicateLabel = t({{DUPLICATE_LABEL}});
$translatedDuplicateAtEndLabel = t({{DUPLICATE_AT_END_LABEL}});
$translatedConfirmLabel = t({{CONFIRM_LABEL}});
$translatedMoveLabel = t('Move entry');
$renderBlockBuilderEntry = static function (int|string $entryIndex, array $entry) use ({{REPEATABLE_CAPTURE_LIST}}): void {
?>
<article
    class="bb-entry"
    data-entry
    data-entry-index="<?= h((string) $entryIndex); ?>"
>
    <div class="bb-entry-header">
        <div class="bb-entry-header-start">
            <button
                class="bb-entry-icon-button bb-entry-drag-handle"
                type="button"
                data-entry-drag-handle
                aria-label="<?= h($translatedMoveLabel); ?>"
                title="<?= h($translatedMoveLabel); ?>"
            >
                <i class="fas fa-arrows-alt" aria-hidden="true"></i>
            </button>
            <button
                class="bb-entry-icon-button"
                type="button"
                data-entry-action="toggle"
                data-expand-label="<?= h($translatedExpandAllLabel); ?>"
                data-collapse-label="<?= h($translatedCollapseAllLabel); ?>"
                aria-expanded="true"
                aria-label="<?= h($translatedCollapseAllLabel); ?>"
                title="<?= h($translatedCollapseAllLabel); ?>"
            >
                <i class="far fa-minus-square" aria-hidden="true"></i>
            </button>
        </div>
        <strong class="bb-entry-title" data-entry-title></strong>
        <div class="bb-entry-header-end">
            <button
                class="bb-entry-icon-button"
                type="button"
                data-entry-action="duplicate"
                aria-label="<?= h($translatedDuplicateLabel); ?>"
                title="<?= h($translatedDuplicateLabel); ?>"
            >
                <i class="far fa-clone" aria-hidden="true"></i>
            </button>
            <button
                class="bb-entry-icon-button"
                type="button"
                data-entry-action="duplicate-at-end"
                aria-label="<?= h($translatedDuplicateAtEndLabel); ?>"
                title="<?= h($translatedDuplicateAtEndLabel); ?>"
            >
                <i class="fas fa-clone" aria-hidden="true"></i>
            </button>
            <button
                class="bb-entry-icon-button bb-entry-remove-button"
                type="button"
                data-entry-action="remove"
                data-confirm="<?= h($translatedConfirmLabel); ?>"
                aria-label="<?= h($translatedRemoveLabel); ?>"
                title="<?= h($translatedRemoveLabel); ?>"
            >
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="bb-entry-content" data-entry-content>
{{REPEATABLE_FIELDS}}
    </div>
</article>
<?php
};
?>
<section data-block-builder-repeatable>
    <div class="bb-entry-toolbar">
        <div class="bb-entry-toolbar-buttons">
            <button class="btn btn-primary" type="button" data-entry-action="add-top">
                <?= $translatedAddAtTopLabel; ?>
            </button>
            <button class="btn btn-primary" type="button" data-entry-action="add-bottom">
                <?= $translatedAddAtBottomLabel; ?>
            </button>
            <button class="btn btn-primary" type="button" data-entry-action="copy-last">
                <?= $translatedCopyLastLabel; ?>
            </button>
        </div>
        <div class="bb-entry-toolbar-links">
            <button class="btn btn-link" type="button" data-entry-action="expand-all">
                <i class="far fa-plus-square" aria-hidden="true"></i>
                <?= $translatedExpandAllLabel; ?>
            </button>
            <button class="btn btn-link" type="button" data-entry-action="collapse-all">
                <i class="far fa-minus-square" aria-hidden="true"></i>
                <?= $translatedCollapseAllLabel; ?>
            </button>
            <button
                class="btn btn-link bb-entry-remove-all-button"
                type="button"
                data-entry-action="remove-all"
                data-confirm="<?= h($translatedConfirmLabel); ?>"
                aria-label="<?= h($translatedRemoveAllLabel); ?>"
                title="<?= h($translatedRemoveAllLabel); ?>"
            >
                <i class="fas fa-times-circle" aria-hidden="true"></i>
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
    <div class="alert alert-info" data-no-entries-message hidden>
        <?= $translatedNoEntriesLabel; ?>
    </div>
    <div class="bb-entry-count text-muted">
        <span data-entry-count>0</span>{{MAXIMUM_COUNTER}}
    </div>

    <template data-entry-template>
        <?php $renderBlockBuilderEntry('__INDEX__', []); ?>
    </template>

    <div class="bb-entry-toolbar bb-entry-toolbar-bottom">
        <div class="bb-entry-toolbar-buttons">
            <button class="btn btn-primary" type="button" data-entry-action="add-top">
                <?= $translatedAddAtTopLabel; ?>
            </button>
            <button class="btn btn-primary" type="button" data-entry-action="add-bottom">
                <?= $translatedAddAtBottomLabel; ?>
            </button>
            <button class="btn btn-primary" type="button" data-entry-action="copy-last">
                <?= $translatedCopyLastLabel; ?>
            </button>
        </div>
        <div class="bb-entry-toolbar-links">
            <button class="btn btn-link" type="button" data-entry-action="expand-all">
                <i class="far fa-plus-square" aria-hidden="true"></i>
                <?= $translatedExpandAllLabel; ?>
            </button>
            <button class="btn btn-link" type="button" data-entry-action="collapse-all">
                <i class="far fa-minus-square" aria-hidden="true"></i>
                <?= $translatedCollapseAllLabel; ?>
            </button>
            <button
                class="btn btn-link bb-entry-remove-all-button"
                type="button"
                data-entry-action="remove-all"
                data-confirm="<?= h($translatedConfirmLabel); ?>"
                aria-label="<?= h($translatedRemoveAllLabel); ?>"
                title="<?= h($translatedRemoveAllLabel); ?>"
            >
                <i class="fas fa-times-circle" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="bb-entry-options">
        <div class="form-check">
            <input
                class="form-check-input"
                id="disable-smooth-scroll-<?= h($formInstanceIdentifier); ?>"
                type="checkbox"
                data-disable-smooth-scroll
            >
            <label class="form-check-label" for="disable-smooth-scroll-<?= h($formInstanceIdentifier); ?>">
                <?= $translatedDisableSmoothScrollLabel; ?>
            </label>
        </div>
        <div class="form-check">
            <input
                class="form-check-input"
                id="keep-added-collapsed-<?= h($formInstanceIdentifier); ?>"
                type="checkbox"
                data-keep-added-collapsed
            >
            <label class="form-check-label" for="keep-added-collapsed-<?= h($formInstanceIdentifier); ?>">
                <?= $translatedKeepAddedCollapsedLabel; ?>
            </label>
        </div>
    </div>
</section>
PHP;

        $maximumCounter = $config->maxNumberOfEntries > 0
            ? sprintf(' / <span title="<?= h($translatedMaximumLabel); ?>">%d</span>', $config->maxNumberOfEntries)
            : '';

        return strtr($template, [
            '{{ADD_AT_TOP_LABEL}}' => $this->phpLiteralFormatter->format($labels['addAtTop']),
            '{{ADD_AT_BOTTOM_LABEL}}' => $this->phpLiteralFormatter->format($labels['addAtBottom']),
            '{{COPY_LAST_LABEL}}' => $this->phpLiteralFormatter->format($labels['copyLast']),
            '{{EXPAND_ALL_LABEL}}' => $this->phpLiteralFormatter->format($labels['expandAll']),
            '{{COLLAPSE_ALL_LABEL}}' => $this->phpLiteralFormatter->format($labels['collapseAll']),
            '{{REMOVE_ALL_LABEL}}' => $this->phpLiteralFormatter->format($labels['removeAll']),
            '{{DISABLE_SMOOTH_SCROLL_LABEL}}' => $this->phpLiteralFormatter->format($labels['disableSmoothScroll']),
            '{{KEEP_ADDED_COLLAPSED_LABEL}}' => $this->phpLiteralFormatter->format($labels['keepAddedCollapsed']),
            '{{NO_ENTRIES_LABEL}}' => $this->phpLiteralFormatter->format($labels['noEntries']),
            '{{MAXIMUM_LABEL}}' => $this->phpLiteralFormatter->format($labels['maximum']),
            '{{REMOVE_LABEL}}' => $this->phpLiteralFormatter->format($labels['remove']),
            '{{DUPLICATE_LABEL}}' => $this->phpLiteralFormatter->format($labels['duplicate']),
            '{{DUPLICATE_AT_END_LABEL}}' => $this->phpLiteralFormatter->format($labels['duplicateAtEnd']),
            '{{CONFIRM_LABEL}}' => $this->phpLiteralFormatter->format($labels['confirm']),
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
            'translatedCollapseAllLabel',
            'translatedConfirmLabel',
            'translatedDuplicateLabel',
            'translatedDuplicateAtEndLabel',
            'translatedExpandAllLabel',
            'translatedMoveLabel',
            'translatedRemoveLabel',
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
