<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Editor\EditorInterface $editor
 * @var BlockBuilder\Block\Dto\BlockConfigDto $config
 * @var array $fieldsWithError
 * @var array $installBlockOptions
 * @var array $entriesAsFirstTabOptions
 * @var array $highlightMultiElementFieldsOptions
 */
?>

<div class="mb-4 <?= h(in_array('installBlock', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('installBlock', t('Install the block after creation')); ?>
    <?= $form->select('installBlock', $installBlockOptions, (int) $config->installBlock); ?>
</div>

<div class="row">
    <div class="col-xl-6 mb-4 <?= h(in_array('entriesAsFirstTab', $fieldsWithError) ? 'bb-has-error' : null); ?>">
        <?= $form->label('entriesAsFirstTab', t('Entries as the first tab')); ?>
        <?= $form->select('entriesAsFirstTab', $entriesAsFirstTabOptions, (int) $config->entriesAsFirstTab); ?>
    </div>
    <div class="col-xl-6 mb-4 <?= h(in_array('maxNumberOfEntries', $fieldsWithError) ? 'bb-has-error' : null); ?>">
        <?= $form->label('maxNumberOfEntries', t('Max. number of entries') . ' ' . t('(0 for unlimited)')); ?>
        <?= $form->number('maxNumberOfEntries', $config->maxNumberOfEntries); ?>
    </div>
</div>

<div class="mb-4 <?= h(in_array('highlightMultiElementFields', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('highlightMultiElementFields', t('Highlight multi-element fields')); ?>
    <?= $form->select('highlightMultiElementFields', $highlightMultiElementFieldsOptions, (int) $config->highlightMultiElementFields); ?>
</div>

<div class="mb-4 <?= h(in_array('messageBasicTab', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('messageBasicTab', t('Message displayed in the "Basic information" tab')); ?>
    <?= $editor->outputStandardEditor(
        'messageBasicTab',
        htmlspecialchars($config->messageBasicTab ?? '', ENT_QUOTES, APP_CHARSET),
    ); ?>
</div>

<div class="mb-4 <?= h(in_array('messageEntriesTab', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('messageEntriesTab', t('Message displayed in the "Repeatable entries" tab')); ?>
    <?= $editor->outputStandardEditor(
        'messageEntriesTab',
        htmlspecialchars($config->messageEntriesTab ?? '', ENT_QUOTES, APP_CHARSET),
    ); ?>
</div>
