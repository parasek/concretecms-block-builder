<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?php foreach ($errors as $errorEntry): ?>
            <div><?php echo $errorEntry; ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div id="bb-container" class="bb-container">

    <div class="mb-4 small text-muted">
        <?php echo t('Discard current changes and <a href="%s">create new block</a> from scratch or <a href="%s">load configuration</a> from existing blocks.', $this->action(''), $this->action('configs')); ?>
    </div>

    <input type="hidden" id="ajaxCsrfToken" value="<?php echo $ajaxCsrfToken; ?>"/>
    <input type="hidden" id="deleteBlockTypeFolderUrl" value="<?php echo $app->make('url/manager')->resolve(['ajax/delete-block-type-folder']); ?>"/>
    <input type="hidden" id="deleteBlockTypeFolderSuccessMessagePart1" value="<?php echo t('Block type folder has been deleted.'); ?>"/>
    <input type="hidden" id="deleteBlockTypeFolderSuccessMessagePart2" value="<?php echo t('Press button Build your block now! once again.'); ?>"/>
    <input type="hidden" id="deleteBlockTypeFolderConfirmationMessage" value="<?php echo t('Are you sure?'); ?>"/>

    <form method="post" action="<?php echo $formAction; ?>">

        <?php echo $this->controller->token->output('csrfToken') ?>

        <ul class="navigation-tabs mb-4" id="navigation-tabs">
            <li><a href="#" data-tab="block-settings" class="btn btn-secondary <?php in_array('block-settings', $tabsWithError) ? print 'has-error' : false; ?>"><?php echo t('Block settings'); ?></a></li>
            <li><a href="#" data-tab="texts" class="btn btn-secondary <?php in_array('texts', $tabsWithError) ? print 'has-error' : false; ?>"><?php echo t('Texts for translation'); ?></a></li>
            <li><a href="#" data-tab="tab-basic-information" class="btn btn-secondary <?php in_array('tab-basic-information', $tabsWithError) ? print 'has-error' : false; ?>"><?php echo t('Tab: Basic information'); ?></a></li>
            <li><a href="#" data-tab="tab-repeatable-entries" class="btn btn-secondary <?php in_array('tab-repeatable-entries', $tabsWithError) ? print 'has-error' : false; ?>"><?php echo t('Tab: Repeatable entries'); ?></a></li>
        </ul>

        <div class="ccm-tab-content active" id="ccm-tab-content-block-settings" style="display: none;">

            <div class="row">
                <div class="col-lg-6 mb-4 <?php in_array('blockName', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('blockName', t('Block name') . ' *'); ?>
                    <?php echo $form->text('blockName', $blockName, ['maxlength' => '100']); ?>
                    <div class="form-text"><?php echo t('Human-readable name e.g. "Example block"'); ?></div>
                </div>
                <div class="col-lg-6 mb-4 <?php in_array('blockHandle', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('blockHandle', t('Block handle') . ' *'); ?>
                    <?php echo $form->text('blockHandle', $blockHandle, ['maxlength' => '50']); ?>
                    <div class="form-text"><?php echo t('Lowercase letters and underscores only e.g. "example_block"'); ?></div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4 <?php in_array('blockDescription', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('blockDescription', t('Block description')); ?>
                    <?php echo $form->textarea('blockDescription', $blockDescription, ['maxlength' => '100']); ?>
                </div>
                <div class="col-lg-6 mb-4 <?php in_array('installBlock', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('installBlock', t('Install block after creation')); ?>
                    <?php echo $form->select('installBlock', $installBlockOptions, $installBlock); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4 <?php in_array('blockWidth', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('blockWidth', t('Block width') . ' *'); ?>
                    <div class="input-group">
                        <?php echo $form->text('blockWidth', $blockWidth); ?>
                        <span class="input-group-text">px</span>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 <?php in_array('blockHeight', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('blockHeight', t('Block height') . ' *'); ?>
                    <div class="input-group">
                        <?php echo $form->text('blockHeight', $blockHeight); ?>
                        <span class="input-group-text">px</span>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 <?php in_array('blockTypeSet', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('blockTypeSet', t('Block type set')); ?>
                    <?php echo $form->select('blockTypeSet', $blockTypeSets, $blockTypeSet); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4 <?php in_array('entriesAsFirstTab', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('entriesAsFirstTab', t('Entries as first tab')); ?>
                    <?php echo $form->select('entriesAsFirstTab', $entriesAsFirstTabOptions, $entriesAsFirstTab); ?>
                </div>
                <div class="col-lg-6 mb-4 <?php in_array('maxNumberOfEntries', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?php echo $form->label('maxNumberOfEntries', t('Max. number of entries') . ' ' . t('(0 for unlimited)')); ?>
                    <?php echo $form->number('maxNumberOfEntries', $maxNumberOfEntries); ?>
                </div>
            </div>

            <div class="mb-4 <?php in_array('fieldsDivider', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('fieldsDivider', t('Use horizontal line as field\'s divider')); ?>
                <?php echo $form->select('fieldsDivider', $dividerOptions, $fieldsDivider); ?>
            </div>

            <div class="mb-4 <?php in_array('entryFieldsDivider', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('entryFieldsDivider', t('Use horizontal line as field\'s divider in repeatable entries')); ?>
                <?php echo $form->select('entryFieldsDivider', $dividerOptions, $entryFieldsDivider); ?>
            </div>

        </div>

        <div class="ccm-tab-content" id="ccm-tab-content-texts" style="display: none;">

            <div class="mb-4 populate-translation-fields">
                <i class="fas fa-book"></i> <?php echo t('Populate fields with'); ?>
                <a href="#" class="js-populate-translation-fields" data-type="translated"><?php echo t('translated'); ?></a>
                or
                <a href="#" class="js-populate-translation-fields" data-type="untranslated"><?php echo t('untranslated'); ?></a>
                <?php echo t('default texts'); ?>
            </div>

            <div class="mb-4 <?php in_array('basicLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('basicLabel', t('Basic information')); ?>
                <?php echo $form->text('basicLabel', $basicLabel, ['data-translated-text' => t('Basic information'), 'data-untranslated-text' => 'Basic information']); ?>
                <div class="form-text"><?php echo t('Displayed name of "Basic information" tab'); ?></div>
            </div>

            <div class="mb-4 <?php in_array('entriesLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('entriesLabel', t('Entries')); ?>
                <?php echo $form->text('entriesLabel', $entriesLabel, ['data-translated-text' => t('Entries'), 'data-untranslated-text' => 'Entries']); ?>
                <div class="form-text"><?php echo t('Displayed name of "Repeatable entries" tab'); ?></div>
            </div>

            <div class="mb-4 <?php in_array('settingsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('settingsLabel', t('Settings')); ?>
                <?php echo $form->text('settingsLabel', $settingsLabel, ['data-translated-text' => t('Settings'), 'data-untranslated-text' => 'Settings']); ?>
                <div class="form-text"><?php echo t('Displayed name of "Settings" tab'); ?></div>
            </div>

            <div class="mb-4 <?php in_array('addAtTheTopLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('addAtTheTopLabel', t('Add at the top')); ?>
                <?php echo $form->text('addAtTheTopLabel', $addAtTheTopLabel, ['data-translated-text' => t('Add at the top'), 'data-untranslated-text' => 'Add at the top']); ?>
            </div>

            <div class="mb-4 <?php in_array('addAtTheBottomLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('addAtTheBottomLabel', t('Add at the bottom')); ?>
                <?php echo $form->text('addAtTheBottomLabel', $addAtTheBottomLabel, ['data-translated-text' => t('Add at the bottom'), 'data-untranslated-text' => 'Add at the bottom']); ?>
            </div>

            <div class="mb-4 <?php in_array('copyLastEntryLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('copyLastEntryLabel', t('Copy last entry')); ?>
                <?php echo $form->text('copyLastEntryLabel', $copyLastEntryLabel, ['data-translated-text' => t('Copy last entry'), 'data-untranslated-text' => 'Copy last entry']); ?>
            </div>

            <div class="mb-4 <?php in_array('collapseAllLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('collapseAllLabel', t('Collapse all')); ?>
                <?php echo $form->text('collapseAllLabel', $collapseAllLabel, ['data-translated-text' => t('Collapse all'), 'data-untranslated-text' => 'Collapse all']); ?>
            </div>

            <div class="mb-4 <?php in_array('expandAllLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('expandAllLabel', t('Expand all')); ?>
                <?php echo $form->text('expandAllLabel', $expandAllLabel, ['data-translated-text' => t('Expand all'), 'data-untranslated-text' => 'Expand all']); ?>
            </div>

            <div class="mb-4 <?php in_array('removeAllLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('removeAllLabel', t('Remove all')); ?>
                <?php echo $form->text('removeAllLabel', $removeAllLabel, ['data-translated-text' => t('Remove all'), 'data-untranslated-text' => 'Remove all']); ?>
            </div>

            <div class="mb-4 <?php in_array('disableSmoothScrollLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('disableSmoothScrollLabel', t('Disable smooth scroll')); ?>
                <?php echo $form->text('disableSmoothScrollLabel', $disableSmoothScrollLabel, ['data-translated-text' => t('Disable smooth scroll'), 'data-untranslated-text' => 'Disable smooth scroll']); ?>
            </div>

            <div class="mb-4 <?php in_array('keepAddedEntryCollapsedLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('keepAddedEntryCollapsedLabel', t('Keep added/copied entry collapsed')); ?>
                <?php echo $form->text('keepAddedEntryCollapsedLabel', $keepAddedEntryCollapsedLabel, ['data-translated-text' => t('Keep added/copied entry collapsed'), 'data-untranslated-text' => 'Keep added/copied entry collapsed']); ?>
            </div>

            <div class="mb-4 <?php in_array('noEntriesFoundLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('noEntriesFoundLabel', t('No entries found.')); ?>
                <?php echo $form->text('noEntriesFoundLabel', $noEntriesFoundLabel, ['data-translated-text' => t('No entries found.'), 'data-untranslated-text' => 'No entries found.']); ?>
            </div>

            <div class="mb-4 <?php in_array('maxNumberOfEntriesLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('maxNumberOfEntriesLabel', t('Max. number of entries')); ?>
                <?php echo $form->text('maxNumberOfEntriesLabel', $maxNumberOfEntriesLabel, ['data-translated-text' => t('Max. number of entries'), 'data-untranslated-text' => 'Max. number of entries']); ?>
            </div>

            <div class="mb-4 <?php in_array('removeEntryLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('removeEntryLabel', t('Remove entry')); ?>
                <?php echo $form->text('removeEntryLabel', $removeEntryLabel, ['data-translated-text' => t('Remove entry'), 'data-untranslated-text' => 'Remove entry']); ?>
            </div>

            <div class="mb-4 <?php in_array('duplicateEntryLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('duplicateEntryLabel', t('Duplicate entry')); ?>
                <?php echo $form->text('duplicateEntryLabel', $duplicateEntryLabel, ['data-translated-text' => t('Duplicate entry'), 'data-untranslated-text' => 'Duplicate entry']); ?>
            </div>

            <div class="mb-4 <?php in_array('duplicateEntryAndAddAtTheEndLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('duplicateEntryAndAddAtTheEndLabel', t('Duplicate entry and add at the end')); ?>
                <?php echo $form->text('duplicateEntryAndAddAtTheEndLabel', $duplicateEntryAndAddAtTheEndLabel, ['data-translated-text' => t('Duplicate entry and add at the end'), 'data-untranslated-text' => 'Duplicate entry and add at the end']); ?>
            </div>

            <div class="mb-4 <?php in_array('areYouSureLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('areYouSureLabel', t('Are you sure?')); ?>
                <?php echo $form->text('areYouSureLabel', $areYouSureLabel, ['data-translated-text' => t('Are you sure?'), 'data-untranslated-text' => 'Are you sure?']); ?>
            </div>

            <div class="mb-4 <?php in_array('requiredFieldsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('requiredFieldsLabel', t('Required fields')); ?>
                <?php echo $form->text('requiredFieldsLabel', $requiredFieldsLabel, ['data-translated-text' => t('Required fields'), 'data-untranslated-text' => 'Required fields']); ?>
            </div>

            <div class="mb-4 <?php in_array('urlEndingLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('urlEndingLabel', t('Custom string at the end of URL')); ?>
                <?php echo $form->text('urlEndingLabel', $urlEndingLabel, ['data-translated-text' => t('Custom string at the end of URL'), 'data-untranslated-text' => 'Custom string at the end of URL']); ?>
            </div>

            <div class="mb-4 <?php in_array('urlEndingHelpText', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('urlEndingHelpText', t('(e.g. #contact-form or ?ccm_paging_p=2)')); ?>
                <?php echo $form->text('urlEndingHelpText', $urlEndingHelpText, ['data-translated-text' => t('(e.g. #contact-form or ?ccm_paging_p=2)'), 'data-untranslated-text' => '(e.g. #contact-form or ?ccm_paging_p=2)']); ?>
            </div>

            <div class="mb-4 <?php in_array('textLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('textLabel', t('Text')); ?>
                <?php echo $form->text('textLabel', $textLabel, ['data-translated-text' => t('Text'), 'data-untranslated-text' => 'Text']); ?>
            </div>

            <div class="mb-4 <?php in_array('titleLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('titleLabel', t('Title')); ?>
                <?php echo $form->text('titleLabel', $titleLabel, ['data-translated-text' => t('Title'), 'data-untranslated-text' => 'Title']); ?>
            </div>

            <div class="mb-4 <?php in_array('altTextLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('altTextLabel', t('Alt text')); ?>
                <?php echo $form->text('altTextLabel', $altTextLabel, ['data-translated-text' => t('Alt text'), 'data-untranslated-text' => 'Alt text']); ?>
            </div>

            <div class="mb-4 <?php in_array('linkFromSitemapLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('linkFromSitemapLabel', t('Link from Sitemap')); ?>
                <?php echo $form->text('linkFromSitemapLabel', $linkFromSitemapLabel, ['data-translated-text' => t('Link from Sitemap'), 'data-untranslated-text' => 'Link from Sitemap']); ?>
            </div>

            <div class="mb-4 <?php in_array('linkFromFileManagerLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('linkFromFileManagerLabel', t('Link from File Manager')); ?>
                <?php echo $form->text('linkFromFileManagerLabel', $linkFromFileManagerLabel, ['data-translated-text' => t('Link from File Manager'), 'data-untranslated-text' => 'Link from File Manager']); ?>
            </div>

            <div class="mb-4 <?php in_array('externalLinkLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('externalLinkLabel', t('External Link')); ?>
                <?php echo $form->text('externalLinkLabel', $externalLinkLabel, ['data-translated-text' => t('External Link'), 'data-untranslated-text' => 'External Link']); ?>
            </div>

            <div class="mb-4 <?php in_array('showAdditionalFieldsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('showAdditionalFieldsLabel', t('Show additional fields')); ?>
                <?php echo $form->text('showAdditionalFieldsLabel', $showAdditionalFieldsLabel, ['data-translated-text' => t('Show additional fields'), 'data-untranslated-text' => 'Show additional fields']); ?>
            </div>

            <div class="mb-4 <?php in_array('hideAdditionalFieldsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('hideAdditionalFieldsLabel', t('Hide additional fields')); ?>
                <?php echo $form->text('hideAdditionalFieldsLabel', $hideAdditionalFieldsLabel, ['data-translated-text' => t('Hide additional fields'), 'data-untranslated-text' => 'Hide additional fields']); ?>
            </div>

            <div class="mb-4 <?php in_array('newWindowLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('newWindowLabel', t('Open in new window')); ?>
                <?php echo $form->text('newWindowLabel', $newWindowLabel, ['data-translated-text' => t('Open in new window'), 'data-untranslated-text' => 'Open in new window']); ?>
            </div>

            <div class="mb-4 <?php in_array('yesLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('yesLabel', t('Yes')); ?>
                <?php echo $form->text('yesLabel', $yesLabel, ['data-translated-text' => t('Yes'), 'data-untranslated-text' => 'Yes']); ?>
            </div>

            <div class="mb-4 <?php in_array('noLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('noLabel', t('No')); ?>
                <?php echo $form->text('noLabel', $noLabel, ['data-translated-text' => t('No'), 'data-untranslated-text' => 'No']); ?>
            </div>

            <div class="mb-4 <?php in_array('overrideThumbnailDimensionsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('overrideThumbnailDimensionsLabel', t('Override Thumbnail dimensions')); ?>
                <?php echo $form->text('overrideThumbnailDimensionsLabel', $overrideThumbnailDimensionsLabel, ['data-translated-text' => t('Override Thumbnail dimensions'), 'data-untranslated-text' => 'Override Thumbnail dimensions']); ?>
            </div>

            <div class="mb-4 <?php in_array('overrideFullscreenImageDimensionsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('overrideFullscreenImageDimensionsLabel', t('Override Fullscreen Image dimensions')); ?>
                <?php echo $form->text('overrideFullscreenImageDimensionsLabel', $overrideFullscreenImageDimensionsLabel, ['data-translated-text' => t('Override Fullscreen Image dimensions'), 'data-untranslated-text' => 'Override Fullscreen Image dimensions']); ?>
            </div>

            <div class="mb-4 <?php in_array('widthLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('widthLabel', t('Width')); ?>
                <?php echo $form->text('widthLabel', $widthLabel, ['data-translated-text' => t('Width'), 'data-untranslated-text' => 'Width']); ?>
            </div>

            <div class="mb-4 <?php in_array('heightLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('heightLabel', t('Height')); ?>
                <?php echo $form->text('heightLabel', $heightLabel, ['data-translated-text' => t('Height'), 'data-untranslated-text' => 'Height']); ?>
            </div>

            <div class="mb-4 <?php in_array('cropLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('cropLabel', t('Crop')); ?>
                <?php echo $form->text('cropLabel', $cropLabel, ['data-translated-text' => t('Crop'), 'data-untranslated-text' => 'Crop']); ?>
            </div>

            <div class="mb-4 <?php in_array('pxLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?php echo $form->label('pxLabel', t('px')); ?>
                <?php echo $form->text('pxLabel', $pxLabel, ['data-translated-text' => t('px'), 'data-untranslated-text' => 'px']); ?>
            </div>

        </div>

        <div class="ccm-tab-content" id="ccm-tab-content-tab-basic-information" style="display: none;">

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <select class="js-add-entry form-select" data-group-handle="basic">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                            <option value="<?php echo h($k); ?>"><?php echo h($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-9 mb-4 entries-actions d-flex flex-column flex-md-row align-items-md-center">
                    <div class="entries-action entries-action-scroll form-check-inline">
                        <input type="checkbox"
                               name="scroll"
                               class="js-toggle-scroll form-check-input"
                               value="1"
                               id="scroll-down-1"
                               <?php if (empty($app->make('cookie')->get('scrollDisabled'))): ?>checked="checked"<?php endif; ?>
                        >
                        <label for="scroll-down-1" class="form-check-label"><?php echo t('Scroll down'); ?></label>
                    </div>
                    <a href="#" class="entries-action js-expand-all"><i class="far fa-plus-square"></i> <?php echo t('Expand all'); ?></a>
                    <a href="#" class="entries-action js-collapse-all"><i class="far fa-minus-square"></i> <?php echo t('Collapse all'); ?></a>
                    <a href="#" class="entries-action entries-action-remove-all js-remove-all" data-group-handle="basic" data-confirm-text="<?php echo t('Are you sure?'); ?>"><i class="fas fa-times-circle"></i> <?php echo t('Remove all'); ?></a>
                </div>
            </div>

            <div class="mb-4">
                <div id="field-types-basic" class="js-sortable" data-entries="<?php echo h(json_encode($basic)); ?>"></div>
            </div>

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <select class="js-add-entry form-select" data-group-handle="basic">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                            <option value="<?php echo h($k); ?>"><?php echo h($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-9 mb-4 entries-actions d-flex flex-column flex-md-row align-items-md-center">
                    <div class="entries-action entries-action-scroll form-check-inline">
                        <input type="checkbox"
                               name="scroll"
                               class="js-toggle-scroll form-check-input"
                               value="1"
                               id="scroll-down-2"
                               <?php if (empty($app->make('cookie')->get('scrollDisabled'))): ?>checked="checked"<?php endif; ?>
                        >
                        <label for="scroll-down-2" class="form-check-label"><?php echo t('Scroll down'); ?></label>
                    </div>
                    <a href="#" class="entries-action js-expand-all"><i class="far fa-plus-square"></i> <?php echo t('Expand all'); ?></a>
                    <a href="#" class="entries-action js-collapse-all"><i class="far fa-minus-square"></i> <?php echo t('Collapse all'); ?></a>
                    <a href="#" class="entries-action entries-action-remove-all js-remove-all" data-group-handle="basic" data-confirm-text="<?php echo t('Are you sure?'); ?>"><i class="fas fa-times-circle"></i> <?php echo t('Remove all'); ?></a>
                </div>
            </div>

        </div>

        <div class="ccm-tab-content" id="ccm-tab-content-tab-repeatable-entries" style="display: none;">

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <select class="js-add-entry form-select" data-group-handle="entries">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                            <option value="<?php echo h($k); ?>"><?php echo h($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-9 mb-4 entries-actions d-flex flex-column flex-md-row align-items-md-center">
                    <div class="entries-action entries-action-scroll form-check-inline">
                        <input type="checkbox"
                               name="scroll"
                               class="js-toggle-scroll form-check-input"
                               value="1"
                               id="scroll-down-3"
                               <?php if (empty($app->make('cookie')->get('scrollDisabled'))): ?>checked="checked"<?php endif; ?>
                        >
                        <label for="scroll-down-3" class="form-check-label"><?php echo t('Scroll down'); ?></label>
                    </div>
                    <a href="#" class="entries-action js-expand-all"><i class="far fa-plus-square"></i> <?php echo t('Expand all'); ?></a>
                    <a href="#" class="entries-action js-collapse-all"><i class="far fa-minus-square"></i> <?php echo t('Collapse all'); ?></a>
                    <a href="#" class="entries-action entries-action-remove-all js-remove-all" data-group-handle="entries" data-confirm-text="<?php echo t('Are you sure?'); ?>"><i class="fas fa-times-circle"></i> <?php echo t('Remove all'); ?></a>
                </div>
            </div>

            <div class="mb-4">
                <div id="field-types-entries" class="js-sortable" data-entries="<?php echo h(json_encode($entries)); ?>"></div>
            </div>

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <select class="js-add-entry form-select" data-group-handle="entries">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                            <option value="<?php echo h($k); ?>"><?php echo h($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-9 mb-4 entries-actions d-flex flex-column flex-md-row align-items-md-center">
                    <div class="entries-action entries-action-scroll form-check-inline">
                        <input type="checkbox"
                               name="scroll"
                               class="js-toggle-scroll form-check-input"
                               value="1"
                               id="scroll-down-4"
                               <?php if (empty($app->make('cookie')->get('scrollDisabled'))): ?>checked="checked"<?php endif; ?>
                        >
                        <label for="scroll-down-4" class="form-check-label"><?php echo t('Scroll down'); ?></label>
                    </div>
                    <a href="#" class="entries-action js-expand-all"><i class="far fa-plus-square"></i> <?php echo t('Expand all'); ?></a>
                    <a href="#" class="entries-action js-collapse-all"><i class="far fa-minus-square"></i> <?php echo t('Collapse all'); ?></a>
                    <a href="#" class="entries-action entries-action-remove-all js-remove-all" data-group-handle="entries" data-confirm-text="<?php echo t('Are you sure?'); ?>"><i class="fas fa-times-circle"></i> <?php echo t('Remove all'); ?></a>
                </div>
            </div>

        </div>

        <hr/>
        <p class="small text-muted required-fields">* <?php echo t('Required fields'); ?></p>

        <div class="ccm-dashboard-form-actions-wrapper">
            <div class="ccm-dashboard-form-actions">
                <input type="submit" class="btn btn-primary float-end" value="<?php echo t('Build your block now!'); ?>" id="ccm-submit-url-form" name="ccm-submit-url-form">
            </div>
        </div>

    </form>

    <script type="text/template" class="js-template-entries">

        <div class="well entry js-entry <% if (error) { %>entry-has-error<% } %>" data-counter="<%=counter%>">

            <div class="entry-header">
                <div class="entry-header-action entry-header-remove-entry js-remove-entry" data-confirm-text="<?php echo t('Are you sure?'); ?>"><i class="fas fa-times"></i></div>
                <div class="entry-header-action entry-header-move-entry js-move-entry"><i class="fas fa-arrows-alt"></i></div>
                <div class="entry-header-action entry-header-toggle-entry js-toggle-entry" data-action="collapse"><i class="far fa-minus-square"></i></div>
                <div class="entry-header-title">
                    <span class="js-entry-title">
                        <% if (label) { %>
                            <%=label%>
                        <% } else { %>
                            #<%=counter%>
                        <% } %>
                    </span> (<%=fieldTypeName%>)
                </div>
            </div>

            <div class="entry-content js-entry-content">

                <input type="hidden" id="<%=groupHandle%>[<%=counter%>][fieldType]" name="<%=groupHandle%>[<%=counter%>][fieldType]" value="<%=fieldType%>"/>

                <div class="row">
                    <div class="col-lg-6 mb-4 <% if (error['label']!=undefined) { %>has-error<% } %>">
                        <label for="<%=groupHandle%>[<%=counter%>][label]" class="form-label"><?php echo t('Label'); ?> *</label>
                        <input type="text"
                               id="<%=groupHandle%>[<%=counter%>][label]"
                               name="<%=groupHandle%>[<%=counter%>][label]"
                               class="form-control js-entry-title-source"
                               value="<%=label%>"
                        />
                        <div class="form-text"><?php echo t('Human-readable name e.g. "Product name"'); ?></div>
                    </div>
                    <div class="col-lg-6 mb-4 <% if (error['handle']!=undefined) { %>has-error<% } %>">
                        <label for="<%=groupHandle%>[<%=counter%>][handle]" class="form-label"><?php echo t('Handle'); ?> *</label>
                        <input type="text"
                               id="<%=groupHandle%>[<%=counter%>][handle]"
                               name="<%=groupHandle%>[<%=counter%>][handle]"
                               class="form-control"
                               value="<%=handle%>"
                               maxlength="50"
                        />
                        <div class="form-text"><?php echo t('a-zA-Z_ characters only e.g. "productName" or "product_name"'); ?></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][required]"
                                   id="<%=groupHandle%>[<%=counter%>][required]"
                                   value="1"
                            <% if (parseInt(required)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][required]" class="form-check-label"><?php echo t('Required'); ?></label>
                        </div>
                        <% if (groupHandle=='entries' && (fieldType == 'text_field' || fieldType == 'textarea')) { %>
                        <div class="form-check">
                            <input type="checkbox"
                                   name="<%=groupHandle%>[<%=counter%>][titleSource]"
                                   id="<%=groupHandle%>[<%=counter%>][titleSource]"
                                   class="form-check-input js-use-field-as-title-in-repeatable-entries"
                                   value="1"
                            <% if (parseInt(titleSource)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][titleSource]" class="form-check-label"><?php echo t('Use this field as title in repeatable entries'); ?></label>
                        </div>
                        <% } %>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <label for="<%=groupHandle%>[<%=counter%>][helpText]" class="form-label"><?php echo t('Help text'); ?></label>
                        <input type="text"
                               id="<%=groupHandle%>[<%=counter%>][helpText]"
                               name="<%=groupHandle%>[<%=counter%>][helpText]"
                               class="form-control"
                               value="<%=helpText%>"
                        />
                        <div class="form-text"><?php echo t('It will look exactly like this text'); ?></p>
                        </div>
                    </div>

                    <% if (fieldType != 'text_field' && fieldType != 'link') { %>

                    <hr class="entry-hr"/>

                    <div class="mb-0">

                        <% if (fieldType == 'textarea') { %>
                        <div class="<% if (error['textareaHeight']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][textareaHeight]" class="form-label"><?php echo t('Height'); ?></label>
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <input type="text"
                                           id="<%=groupHandle%>[<%=counter%>][textareaHeight]"
                                           name="<%=groupHandle%>[<%=counter%>][textareaHeight]"
                                           class="form-control"
                                           value="<%=textareaHeight%>"
                                    />
                                    <span class="input-group-text">px</span>
                                </div>
                            </div>
                            <div class="form-text"><?php echo t('Default height: %s.', '66px'); ?></div>
                        </div>
                        <% } %>

                        <% if (fieldType == 'wysiwyg_editor') { %>
                        <div class="<% if (error['wysiwygEditorHeight']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]" class="form-label"><?php echo t('Height'); ?></label>
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <input type="text"
                                           id="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]"
                                           name="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]"
                                           class="form-control"
                                           value="<%=wysiwygEditorHeight%>"
                                    />
                                    <span class="input-group-text">px</span>
                                </div>
                            </div>
                            <div class="form-text"><?php echo t('Default height of editable area: %s.', '40px'); ?><br/><?php echo t('Editor auto-grow will be enabled if you leave this field empty.'); ?></div>
                        </div>
                        <% } %>

                        <% if (fieldType == 'select_field') { %>
                        <div class="<% if (error['selectOptions']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][selectOptions]" class="form-label"><?php echo t('Select options'); ?></label>
                            <p class="small text-muted">
                                <?php echo t('Enter every option in new line, e.g.'); ?>
                                <br/><code><?php echo t('Don\'t show'); ?></code>
                                <br/><code><?php echo t('Show'); ?></code>
                            </p>
                            <p class="small text-muted">
                                <?php echo t('You can also use double colon to specify key (value saved in database, only a-zA-Z0-9_ characters are permitted) and value (displayed text), e.g.'); ?>
                                <br/><code><?php echo t('no :: Don\'t show'); ?></code>
                                <br/><code><?php echo t('yes :: Show'); ?></code>
                            </p>
                            <textarea name="<%=groupHandle%>[<%=counter%>][selectOptions]"
                                      id="<%=groupHandle%>[<%=counter%>][selectOptions]"
                                      class="form-control"
                                      rows="4"
                            ><%=selectOptions%></textarea>
                        </div>
                        <% } %>

                        <% if (fieldType == 'link_from_sitemap') { %>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowEndingField]"
                                   id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowEndingField]"
                                   value="1"
                            <% if (parseInt(linkFromSitemapShowEndingField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowEndingField]" class="form-check-label"><?php echo t('Show "Custom string at the end of URL" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]"
                                   id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]"
                                   value="1"
                            <% if (parseInt(linkFromSitemapShowTextField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]" class="form-check-label"><?php echo t('Show "Text" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]"
                                   id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]"
                                   value="1"
                            <% if (parseInt(linkFromSitemapShowTitleField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]" class="form-check-label"><?php echo t('Show "Title" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
                                   id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
                                   value="1"
                            <% if (parseInt(linkFromSitemapShowNewWindowField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]" class="form-check-label"><?php echo t('Show "Open in new window" field'); ?></label>
                        </div>
                        <% } %>

                        <% if (fieldType == 'link_from_file_manager') { %>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowEndingField]"
                                   id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowEndingField]"
                                   value="1"
                            <% if (parseInt(linkFromFileManagerShowEndingField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowEndingField]" class="form-check-label"><?php echo t('Show "Custom string at the end of URL" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]"
                                   id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]"
                                   value="1"
                            <% if (parseInt(linkFromFileManagerShowTextField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]" class="form-check-label"><?php echo t('Show "Text" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]"
                                   id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]"
                                   value="1"
                            <% if (parseInt(linkFromFileManagerShowTitleField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]" class="form-check-label"><?php echo t('Show "Title" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
                                   id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
                                   value="1"
                            <% if (parseInt(linkFromFileManagerShowNewWindowField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]" class="form-check-label"><?php echo t('Show "Open in new window" field'); ?></label>
                        </div>
                        <% } %>

                        <% if (fieldType == 'external_link') { %>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][externalLinkShowEndingField]"
                                   id="<%=groupHandle%>[<%=counter%>][externalLinkShowEndingField]"
                                   value="1"
                            <% if (parseInt(externalLinkShowEndingField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowEndingField]" class="form-check-label"><?php echo t('Show "Custom string at the end of URL" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]"
                                   id="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]"
                                   value="1"
                            <% if (parseInt(externalLinkShowTextField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]" class="form-check-label"><?php echo t('Show "Text" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]"
                                   id="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]"
                                   value="1"
                            <% if (parseInt(externalLinkShowTitleField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]" class="form-check-label"><?php echo t('Show "Title" field'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]"
                                   id="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]"
                                   value="1"
                            <% if (parseInt(externalLinkShowNewWindowField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]" class="form-check-label"><?php echo t('Show "Open in new window" field'); ?></label>
                        </div>
                        <% } %>

                        <% if (fieldType == 'image') { %>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][imageShowAltTextField]"
                                   id="<%=groupHandle%>[<%=counter%>][imageShowAltTextField]"
                                   value="1"
                            <% if (parseInt(imageShowAltTextField)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][imageShowAltTextField]" class="form-check-label"><?php echo t('Show "Alt text" field'); ?></label>
                        </div>
                        <div class="row" style="display: none;"></div>
                        <div class="form-check">
                            <input type="checkbox"
                                   name="<%=groupHandle%>[<%=counter%>][imageCreateThumbnailImage]"
                                   id="<%=groupHandle%>[<%=counter%>][imageCreateThumbnailImage]"
                                   value="1"
                                   class="form-check-input js-image-create-thumbnail-image"
                            <% if (parseInt(imageCreateThumbnailImage)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][imageCreateThumbnailImage]" class="form-check-label"><?php echo t('Generate thumbnail using image helper (if original image is bigger than specified dimensions)'); ?></label>
                        </div>
                        <div class="row mt-2 js-image-create-thumbnail-image-wrapper <% if (error['imageThumbnailOptions']!=undefined) { %>has-error<% } %>"
                        <% if (!parseInt(imageCreateThumbnailImage)) { %> style="display: none;" <% } %>>
                        <div class="col-lg-4 mb-4 <% if (error['imageThumbnailWidth']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]" class="form-label"><?php echo t('Width'); ?></label>
                            <div class="input-group">
                                <input type="text"
                                       id="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]"
                                       name="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]"
                                       class="form-control"
                                       value="<%=imageThumbnailWidth%>"
                                />
                                <span class="input-group-text">px</span>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-4 <% if (error['imageThumbnailHeight']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]" class="form-label"><?php echo t('Height'); ?></label>
                            <div class="input-group">
                                <input type="text"
                                       id="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]"
                                       name="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]"
                                       class="form-control"
                                       value="<%=imageThumbnailHeight%>"
                                />
                                <span class="input-group-text">px</span>
                            </div>
                        </div>
                        <div class="col-lg-2 mb-4">
                            <label class="form-label"><?php echo t('Crop'); ?></label>
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       name="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]"
                                       id="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]"
                                       value="1"
                                <% if (parseInt(imageThumbnailCrop)) { %> checked="checked" <% } %>
                                >
                                <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]" class="form-check-label"><?php echo t('Yes'); ?></label>
                            </div>
                        </div>
                        <div class="col-lg-2 mb-4">
                            <label class="form-label"><?php echo t('Editable'); ?></label>
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       name="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]"
                                       id="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]"
                                       value="1"
                                <% if (parseInt(imageThumbnailEditable)) { %> checked="checked" <% } %>
                                >
                                <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]" class="form-check-label"><?php echo t('Yes'); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox"
                               name="<%=groupHandle%>[<%=counter%>][imageCreateFullscreenImage]"
                               id="<%=groupHandle%>[<%=counter%>][imageCreateFullscreenImage]"
                               value="1"
                               class="form-check-input js-image-create-fullscreen-image"
                        <% if (parseInt(imageCreateFullscreenImage)) { %> checked="checked" <% } %>
                        >
                        <label for="<%=groupHandle%>[<%=counter%>][imageCreateFullscreenImage]" class="form-check-label"><?php echo t('Generate fullscreen image using image helper (if original image is bigger than specified dimensions)'); ?></label>
                    </div>
                    <div class="row mt-2 js-image-create-fullscreen-image-wrapper <% if (error['imageFullscreenOptions']!=undefined) { %>has-error<% } %>"
                    <% if (!parseInt(imageCreateFullscreenImage)) { %> style="display: none;" <% } %>>
                    <div class="col-lg-4 mb-4 <% if (error['imageFullscreenWidth']!=undefined) { %>has-error<% } %>">
                        <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]" class="form-label"><?php echo t('Width'); ?></label>
                        <div class="input-group">
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]"
                                   name="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]"
                                   class="form-control"
                                   value="<%=imageFullscreenWidth%>"
                            />
                            <span class="input-group-text">px</span>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4 <% if (error['imageFullscreenHeight']!=undefined) { %>has-error<% } %>">
                        <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]" class="form-label"><?php echo t('Height'); ?></label>
                        <div class="input-group">
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]"
                                   name="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]"
                                   class="form-control"
                                   value="<%=imageFullscreenHeight%>"
                            />
                            <span class="input-group-text">px</span>
                        </div>
                    </div>
                    <div class="col-lg-2 mb-4">
                        <label class="form-label"><?php echo t('Crop'); ?></label>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]"
                                   id="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]"
                                   value="1"
                            <% if (parseInt(imageFullscreenCrop)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]" class="form-check-label"><?php echo t('Yes'); ?></label>
                        </div>
                    </div>
                    <div class="col-lg-2 mb-4">
                        <label class="form-label"><?php echo t('Editable'); ?></label>
                        <div class="form-check">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]"
                                   id="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]"
                                   value="1"
                            <% if (parseInt(imageFullscreenEditable)) { %> checked="checked" <% } %>
                            >
                            <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]" class="form-check-label"><?php echo t('Yes'); ?></label>
                        </div>
                    </div>
                </div>
                <% } %>

                <% if (fieldType == 'html_editor') { %>
                <div class="<% if (error['htmlEditorHeight']!=undefined) { %>has-error<% } %>">
                    <label for="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]" class="form-label"><?php echo t('Height'); ?></label>
                    <div class="input-group col-lg-3">
                        <input type="text"
                               id="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]"
                               name="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]"
                               class="form-control"
                               value="<%=htmlEditorHeight%>"
                        />
                        <span class="input-group-text">px</span>
                    </div>
                    <div class="form-text">
                        <?php echo t('Default height: %s.', '250px'); ?><br/>
                    </div>
                </div>
                <% } %>

                <% if (fieldType == 'date_picker') { %>
                <div class="<% if (error['datePickerPattern']!=undefined) { %>has-error<% } %>">
                    <label for="<%=groupHandle%>[<%=counter%>][datePickerPattern]" class="form-label"><?php echo t('PHP Date Pattern'); ?></label>
                    <input type="text"
                           id="<%=groupHandle%>[<%=counter%>][datePickerPattern]"
                           name="<%=groupHandle%>[<%=counter%>][datePickerPattern]"
                           class="form-control"
                           value="<%=datePickerPattern%>"
                    />
                    <div class="form-text">
                        <?php echo t('Check %sphp manual%s for available formats. Examples: <code>d.m.Y</code>, <code>d-m-Y</code>, <code>Y-m-d</code>, <code>m-d-Y</code>, <code>m/d/Y</code>', '<a href="https://www.php.net/manual/en/function.date.php" target="_blank" rel="noopener noreferrer">', '</a>'); ?>
                    </div>
                </div>
                <% } %>

            </div>

            <% } %>

        </div>

        </div>

    </script>

    <script type="text/template" class="js-template-no-entries">

        <div class="alert alert-info js-alert"><?php echo t('You haven\'t added any field types yet.'); ?></div>

    </script>

</div>
