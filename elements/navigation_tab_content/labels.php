<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var BlockBuilder\Block\Dto\BlockConfigDto $config
 * @var array $fieldsWithError
 */
?>

<div class="mb-4 populate-translation-fields">
    <i class="fas fa-book"></i> <?= t('Populate fields with'); ?>
    <a href="#"
       data-populate-translation-fields
       data-type="translated"
    ><?= t('translated'); ?></a>
    /
    <a href="#"
       data-populate-translation-fields
       data-type="untranslated"
    ><?= t('untranslated'); ?></a>
    <?= t('default labels'); ?>
</div>

<div class="mb-4 <?= h(in_array('basicLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('basicLabel', t('Basic information')); ?>
    <?= $form->text('basicLabel', $config->basicLabel, ['data-translated-text' => t('Basic information'), 'data-untranslated-text' => 'Basic information']); ?>
    <div class="form-text"><?= t('Displayed name of the "Basic information" tab'); ?></div>
</div>

<div class="mb-4 <?= h(in_array('entriesLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('entriesLabel', t('Entries')); ?>
    <?= $form->text('entriesLabel', $config->entriesLabel, ['data-translated-text' => t('Entries'), 'data-untranslated-text' => 'Entries']); ?>
    <div class="form-text"><?= t('Displayed name of the "Repeatable entries" tab'); ?></div>
</div>

<div class="mb-4 <?= h(in_array('settingsLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('settingsLabel', t('Settings')); ?>
    <?= $form->text('settingsLabel', $config->settingsLabel, ['data-translated-text' => t('Settings'), 'data-untranslated-text' => 'Settings']); ?>
    <div class="form-text"><?= t('Displayed name of the "Settings" tab'); ?></div>
</div>

<div class="mb-4 <?= h(in_array('addAtTheTopLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('addAtTheTopLabel', t('Add at the top')); ?>
    <?= $form->text('addAtTheTopLabel', $config->addAtTheTopLabel, ['data-translated-text' => t('Add at the top'), 'data-untranslated-text' => 'Add at the top']); ?>
</div>

<div class="mb-4 <?= h(in_array('addAtTheBottomLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('addAtTheBottomLabel', t('Add at the bottom')); ?>
    <?= $form->text('addAtTheBottomLabel', $config->addAtTheBottomLabel, ['data-translated-text' => t('Add at the bottom'), 'data-untranslated-text' => 'Add at the bottom']); ?>
</div>

<div class="mb-4 <?= h(in_array('copyLastEntryLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('copyLastEntryLabel', t('Copy last entry')); ?>
    <?= $form->text('copyLastEntryLabel', $config->copyLastEntryLabel, ['data-translated-text' => t('Copy last entry'), 'data-untranslated-text' => 'Copy last entry']); ?>
</div>

<div class="mb-4 <?= h(in_array('collapseAllLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('collapseAllLabel', t('Collapse all')); ?>
    <?= $form->text('collapseAllLabel', $config->collapseAllLabel, ['data-translated-text' => t('Collapse all'), 'data-untranslated-text' => 'Collapse all']); ?>
</div>

<div class="mb-4 <?= h(in_array('expandAllLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('expandAllLabel', t('Expand all')); ?>
    <?= $form->text('expandAllLabel', $config->expandAllLabel, ['data-translated-text' => t('Expand all'), 'data-untranslated-text' => 'Expand all']); ?>
</div>

<div class="mb-4 <?= h(in_array('removeAllLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('removeAllLabel', t('Remove all')); ?>
    <?= $form->text('removeAllLabel', $config->removeAllLabel, ['data-translated-text' => t('Remove all'), 'data-untranslated-text' => 'Remove all']); ?>
</div>

<div class="mb-4 <?= h(in_array('disableSmoothScrollLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('disableSmoothScrollLabel', t('Disable smooth scroll')); ?>
    <?= $form->text('disableSmoothScrollLabel', $config->disableSmoothScrollLabel, ['data-translated-text' => t('Disable smooth scroll'), 'data-untranslated-text' => 'Disable smooth scroll']); ?>
</div>

<div class="mb-4 <?= h(in_array('keepAddedEntryCollapsedLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('keepAddedEntryCollapsedLabel', t('Keep added or copied entries collapsed')); ?>
    <?= $form->text('keepAddedEntryCollapsedLabel', $config->keepAddedEntryCollapsedLabel, ['data-translated-text' => t('Keep added or copied entries collapsed'), 'data-untranslated-text' => 'Keep added or copied entries collapsed']); ?>
</div>

<div class="mb-4 <?= h(in_array('noEntriesFoundLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('noEntriesFoundLabel', t('No entries found.')); ?>
    <?= $form->text('noEntriesFoundLabel', $config->noEntriesFoundLabel, ['data-translated-text' => t('No entries found.'), 'data-untranslated-text' => 'No entries found.']); ?>
</div>

<div class="mb-4 <?= h(in_array('maxNumberOfEntriesLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('maxNumberOfEntriesLabel', t('Max. number of entries')); ?>
    <?= $form->text('maxNumberOfEntriesLabel', $config->maxNumberOfEntriesLabel, ['data-translated-text' => t('Max. number of entries'), 'data-untranslated-text' => 'Max. number of entries']); ?>
</div>

<div class="mb-4 <?= h(in_array('removeEntryLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('removeEntryLabel', t('Remove entry')); ?>
    <?= $form->text('removeEntryLabel', $config->removeEntryLabel, ['data-translated-text' => t('Remove entry'), 'data-untranslated-text' => 'Remove entry']); ?>
</div>

<div class="mb-4 <?= h(in_array('duplicateEntryLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('duplicateEntryLabel', t('Duplicate entry')); ?>
    <?= $form->text('duplicateEntryLabel', $config->duplicateEntryLabel, ['data-translated-text' => t('Duplicate entry'), 'data-untranslated-text' => 'Duplicate entry']); ?>
</div>

<div class="mb-4 <?= h(in_array('duplicateEntryAndAddAtTheEndLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('duplicateEntryAndAddAtTheEndLabel', t('Duplicate entry and add at the end')); ?>
    <?= $form->text('duplicateEntryAndAddAtTheEndLabel', $config->duplicateEntryAndAddAtTheEndLabel, ['data-translated-text' => t('Duplicate entry and add at the end'), 'data-untranslated-text' => 'Duplicate entry and add at the end']); ?>
</div>

<div class="mb-4 <?= h(in_array('areYouSureLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('areYouSureLabel', t('Are you sure?')); ?>
    <?= $form->text('areYouSureLabel', $config->areYouSureLabel, ['data-translated-text' => t('Are you sure?'), 'data-untranslated-text' => 'Are you sure?']); ?>
</div>

<div class="mb-4 <?= h(in_array('requiredFieldsLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('requiredFieldsLabel', t('Required fields')); ?>
    <?= $form->text('requiredFieldsLabel', $config->requiredFieldsLabel, ['data-translated-text' => t('Required fields'), 'data-untranslated-text' => 'Required fields']); ?>
</div>

<div class="mb-4 <?= h(in_array('urlEndingLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('urlEndingLabel', t('Custom string at the end of URL')); ?>
    <?= $form->text('urlEndingLabel', $config->urlEndingLabel, ['data-translated-text' => t('Custom string at the end of URL'), 'data-untranslated-text' => 'Custom string at the end of URL']); ?>
</div>

<div class="mb-4 <?= h(in_array('urlEndingHelpTextLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('urlEndingHelpTextLabel', t('(e.g. #contact-form or ?ccm_paging_p=2)')); ?>
    <?= $form->text('urlEndingHelpTextLabel', $config->urlEndingHelpTextLabel, ['data-translated-text' => t('(e.g. #contact-form or ?ccm_paging_p=2)'), 'data-untranslated-text' => '(e.g. #contact-form or ?ccm_paging_p=2)']); ?>
</div>

<div class="mb-4 <?= h(in_array('textLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('textLabel', t('Text')); ?>
    <?= $form->text('textLabel', $config->textLabel, ['data-translated-text' => t('Text'), 'data-untranslated-text' => 'Text']); ?>
</div>

<div class="mb-4 <?= h(in_array('titleLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('titleLabel', t('Title')); ?>
    <?= $form->text('titleLabel', $config->titleLabel, ['data-translated-text' => t('Title'), 'data-untranslated-text' => 'Title']); ?>
</div>

<div class="mb-4 <?= h(in_array('altTextLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('altTextLabel', t('Alt text')); ?>
    <?= $form->text('altTextLabel', $config->altTextLabel, ['data-translated-text' => t('Alt text'), 'data-untranslated-text' => 'Alt text']); ?>
</div>

<div class="mb-4 <?= h(in_array('linkFromSitemapLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('linkFromSitemapLabel', t('Link from Sitemap')); ?>
    <?= $form->text('linkFromSitemapLabel', $config->linkFromSitemapLabel, ['data-translated-text' => t('Link from Sitemap'), 'data-untranslated-text' => 'Link from Sitemap']); ?>
</div>

<div class="mb-4 <?= h(in_array('linkFromFileManagerLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('linkFromFileManagerLabel', t('Link from File Manager')); ?>
    <?= $form->text('linkFromFileManagerLabel', $config->linkFromFileManagerLabel, ['data-translated-text' => t('Link from File Manager'), 'data-untranslated-text' => 'Link from File Manager']); ?>
</div>

<div class="mb-4 <?= h(in_array('externalLinkLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('externalLinkLabel', t('External link')); ?>
    <?= $form->text('externalLinkLabel', $config->externalLinkLabel, ['data-translated-text' => t('External link'), 'data-untranslated-text' => 'External link']); ?>
</div>

<div class="mb-4 <?= h(in_array('showAdditionalFieldsLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('showAdditionalFieldsLabel', t('Show additional fields')); ?>
    <?= $form->text('showAdditionalFieldsLabel', $config->showAdditionalFieldsLabel, ['data-translated-text' => t('Show additional fields'), 'data-untranslated-text' => 'Show additional fields']); ?>
</div>

<div class="mb-4 <?= h(in_array('hideAdditionalFieldsLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('hideAdditionalFieldsLabel', t('Hide additional fields')); ?>
    <?= $form->text('hideAdditionalFieldsLabel', $config->hideAdditionalFieldsLabel, ['data-translated-text' => t('Hide additional fields'), 'data-untranslated-text' => 'Hide additional fields']); ?>
</div>

<div class="mb-4 <?= h(in_array('newWindowLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('newWindowLabel', t('Open in new window')); ?>
    <?= $form->text('newWindowLabel', $config->newWindowLabel, ['data-translated-text' => t('Open in new window'), 'data-untranslated-text' => 'Open in new window']); ?>
</div>

<div class="mb-4 <?= h(in_array('noFollowLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('noFollowLabel', t('Add nofollow attribute')); ?>
    <?= $form->text('noFollowLabel', $config->noFollowLabel, ['data-translated-text' => t('Add nofollow attribute'), 'data-untranslated-text' => 'Add nofollow attribute']); ?>
</div>

<div class="mb-4 <?= h(in_array('yesLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('yesLabel', t('Yes')); ?>
    <?= $form->text('yesLabel', $config->yesLabel, ['data-translated-text' => t('Yes'), 'data-untranslated-text' => 'Yes']); ?>
</div>

<div class="mb-4 <?= h(in_array('noLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('noLabel', t('No')); ?>
    <?= $form->text('noLabel', $config->noLabel, ['data-translated-text' => t('No'), 'data-untranslated-text' => 'No']); ?>
</div>

<div class="mb-4 <?= h(in_array('overrideThumbnailDimensionsLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('overrideThumbnailDimensionsLabel', t('Override thumbnail dimensions')); ?>
    <?= $form->text('overrideThumbnailDimensionsLabel', $config->overrideThumbnailDimensionsLabel, ['data-translated-text' => t('Override thumbnail dimensions'), 'data-untranslated-text' => 'Override thumbnail dimensions']); ?>
</div>

<div class="mb-4 <?= h(in_array('overrideFullscreenImageDimensionsLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('overrideFullscreenImageDimensionsLabel', t('Override fullscreen image dimensions')); ?>
    <?= $form->text('overrideFullscreenImageDimensionsLabel', $config->overrideFullscreenImageDimensionsLabel, ['data-translated-text' => t('Override fullscreen image dimensions'), 'data-untranslated-text' => 'Override fullscreen image dimensions']); ?>
</div>

<div class="mb-4 <?= h(in_array('widthLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('widthLabel', t('Width')); ?>
    <?= $form->text('widthLabel', $config->widthLabel, ['data-translated-text' => t('Width'), 'data-untranslated-text' => 'Width']); ?>
</div>

<div class="mb-4 <?= h(in_array('heightLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('heightLabel', t('Height')); ?>
    <?= $form->text('heightLabel', $config->heightLabel, ['data-translated-text' => t('Height'), 'data-untranslated-text' => 'Height']); ?>
</div>

<div class="mb-4 <?= h(in_array('cropLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('cropLabel', t('Crop')); ?>
    <?= $form->text('cropLabel', $config->cropLabel, ['data-translated-text' => t('Crop'), 'data-untranslated-text' => 'Crop']); ?>
</div>

<div class="mb-4 <?= h(in_array('pxLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('pxLabel', t('px')); ?>
    <?= $form->text('pxLabel', $config->pxLabel, ['data-translated-text' => t('px'), 'data-untranslated-text' => 'px']); ?>
</div>

<div class="mb-4 <?= h(in_array('nothingSelectedLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('nothingSelectedLabel', t('Nothing selected')); ?>
    <?= $form->text('nothingSelectedLabel', $config->nothingSelectedLabel, ['data-translated-text' => t('Nothing selected'), 'data-untranslated-text' => 'Nothing selected']); ?>
</div>

<div class="mb-4 <?= h(in_array('noResultsMatchedLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('noResultsMatchedLabel', t('No results matched {0}')); ?>
    <?= $form->text('noResultsMatchedLabel', $config->noResultsMatchedLabel, ['data-translated-text' => t('No results matched {0}'), 'data-untranslated-text' => 'No results matched {0}']); ?>
</div>

<div class="mb-4 <?= h(in_array('selectAllLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('selectAllLabel', t('Select all')); ?>
    <?= $form->text('selectAllLabel', $config->selectAllLabel, ['data-translated-text' => t('Select all'), 'data-untranslated-text' => 'Select all']); ?>
</div>

<div class="mb-4 <?= h(in_array('deselectAllLabel', $fieldsWithError) ? 'bb-has-error' : null); ?>">
    <?= $form->label('deselectAllLabel', t('Deselect all')); ?>
    <?= $form->text('deselectAllLabel', $config->deselectAllLabel, ['data-translated-text' => t('Deselect all'), 'data-untranslated-text' => 'Deselect all']); ?>
</div>
