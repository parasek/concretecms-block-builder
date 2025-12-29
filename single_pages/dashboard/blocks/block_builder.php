<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder $controller
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 * @var BlockBuilder\Block\Dto\ConfigDto $config
 * @var string $formActionPath
 */
?>

<div class="bb-container" id="bbContainer" data-fields-with-errors="<?= h(json_encode($fieldsWithError ?? [])); ?>">

    <div class="ccm-dashboard-header-buttons">
        <a href="<?= h(app('url/manager')->resolve(['dashboard/blocks/block_builder'])); ?>"
           class="btn btn-secondary"
        ><i class="fas fa-plus"></i> <?= t('New block'); ?></a>
        <a href="<?= h(app('url/manager')->resolve(['dashboard/blocks/block_builder/configs'])); ?>"
           class="btn btn-secondary"
        >
            <i class="fas fa-upload"></i> <?= t('Load config'); ?>
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= t('Close'); ?>"></button>
            <?php foreach ($errors as $errorEntry): ?>
                <div><?= $errorEntry; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    $infoTable = ['environment' => $environment];
    if (in_array($controller->getAction(), ['config', 'predefined_config'], true)) {
        $infoTable = array_merge($infoTable, ['config' => $config]);
    }
    View::element('info_table', $infoTable, 'block_builder');
    ?>


    <input type="hidden" id="confirmationMessage" value="<?= t('Are you sure?'); ?>">



    <input type="hidden" id="csrfToken" value="<?= $this->controller->token->generate('csrf_token'); ?>">

    <input type="hidden"
           id="installBlockTypeUrl"
           value="<?= $app->make('url/manager')->resolve(['js/install-block-type']); ?>"
    >
    <input type="hidden"
           id="installBlockTypeSuccessMessagePart1"
           value="<?= t('Block has been installed.'); ?>"
    >
    <input type="hidden"
           id="installBlockTypeSuccessMessagePart2"
           value="<?= t('Click Rebuild and refresh block once again.'); ?>"
    >


    <input type="hidden"
           id="uninstallBlockTypeUrl"
           value="<?= $app->make('url/manager')->resolve(['js/uninstall-block-type']); ?>"
    >
    <input type="hidden"
           id="uninstallBlockTypeSuccessMessagePart1"
           value="<?= t('Block has been uninstalled.'); ?>"
    >
    <input type="hidden"
           id="uninstallBlockTypeSuccessMessagePart2"
           value="<?= t('Click Build your block now! once again.'); ?>"
    >


    <input type="hidden"
           id="deleteBlockTypeFolderUrl"
           value="<?= $app->make('url/manager')->resolve(['js/delete-block-type-folder']); ?>"
    >
    <input type="hidden"
           id="deleteBlockTypeFolderSuccessMessagePart1"
           value="<?= t('Block folder has been deleted.'); ?>"
    >
    <input type="hidden"
           id="deleteBlockTypeFolderSuccessMessagePart2"
           value="<?= t('Click Build your block now! once again.'); ?>"
    >




    <form method="post" action="<?= h($controller->action($formActionPath)); ?>">
        <?= $this->controller->token->output('create_block'); ?>

        <ul class="navigation-tabs mb-4" id="navigation-tabs">
            <li><a href="#"
                   data-tab="block-settings"
                   class="btn btn-secondary <?php in_array('block-settings', $tabsWithError) ? print 'has-error' : false; ?>"
                ><i class="fas fa-wrench"></i> <?= t('Block settings'); ?></a></li>
            <li><a href="#"
                   data-tab="build-options"
                   class="btn btn-secondary <?php in_array('build-options', $tabsWithError) ? print 'has-error' : false; ?>"
                ><i class="fas fa-cogs"></i> <?= t('Build options'); ?></a></li>
            <li><a href="#"
                   data-tab="custom-code"
                   class="btn btn-secondary <?php in_array('custom-code', $tabsWithError) ? print 'has-error' : false; ?>"
                ><i class="fas fa-code"></i> <?= t('Custom code'); ?></a></li>
            <li><a href="#"
                   data-tab="texts"
                   class="btn btn-secondary <?php in_array('texts', $tabsWithError) ? print 'has-error' : false; ?>"
                ><i class="fas fa-book"></i> <?= t('Labels'); ?></a></li>
            <li><a href="#"
                   data-tab="<?= h(\BlockBuilder\FieldType\Enum\FieldTypeContextEnum::BasicFields->getTabHandle()); ?>"
                   class="btn btn-secondary <?php in_array('tab-basic-information', $tabsWithError) ? print 'has-error' : false; ?>"
                ><i class="far fa-file"></i> <?= t('Tab: Basic information'); ?></a></li>
            <li><a href="#"
                   data-tab="<?= h(\BlockBuilder\FieldType\Enum\FieldTypeContextEnum::RepeatableFields->getTabHandle()); ?>"
                   class="btn btn-secondary <?php in_array('tab-repeatable-entries', $tabsWithError) ? print 'has-error' : false; ?>"
                ><i class="far fa-copy"></i> <?= t('Tab: Repeatable entries'); ?></a></li>
        </ul>

        <div class="ccm-tab-content active" id="ccm-tab-content-block-settings" style="display: none;">

            <div class="row g-5">
                <div class="col-lg-6 mb-4">

                    <div class="mb-4 <?php in_array('blockName', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('blockName', t('Block name') . ' *'); ?>
                        <?= $form->text('blockName', $blockName, ['maxlength' => '100']); ?>
                        <div class="form-text"><?= t('Human-readable name e.g. "Example block"'); ?></div>
                    </div>
                    <div class="mb-4 <?php in_array('blockHandle', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('blockHandle', t('Block handle') . ' *'); ?>
                        <?= $form->text('blockHandle', $blockHandle, ['maxlength' => '50']); ?>
                        <div class="form-text"><?= t('Lowercase letters and underscores only e.g. "example_block"'); ?></div>
                    </div>
                    <div class="mb-4 <?php in_array('blockDescription', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('blockDescription', t('Block description')); ?>
                        <?= $form->textarea('blockDescription', $blockDescription, ['maxlength' => '100']); ?>
                    </div>
                    <div class="mb-4 <?php in_array('blockWidth', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('blockWidth', t('Block width') . ' *'); ?>
                        <div class="input-group">
                            <?= $form->text('blockWidth', $blockWidth); ?>
                            <span class="input-group-text">px</span>
                        </div>
                    </div>
                    <div class="mb-4 <?php in_array('blockHeight', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('blockHeight', t('Block height') . ' *'); ?>
                        <div class="input-group">
                            <?= $form->text('blockHeight', $blockHeight); ?>
                            <span class="input-group-text">px</span>
                        </div>
                    </div>
                    <div class="mb-4 <?php in_array('blockTypeSet', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('blockTypeSet', t('Block type set')); ?>
                        <?= $form->select('blockTypeSet', $blockTypeSets, $blockTypeSet); ?>
                    </div>

                </div>
                <div class="col-lg-6 mb-4 ">

                    <div class="mb-4 <?php in_array('cacheBlockRecord', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('cacheBlockRecord', t('Cache block record')); ?>
                        <?= $form->select('cacheBlockRecord', $cacheBlockRecordOptions, (int) $cacheBlockRecord); ?>
                    </div>
                    <div class="mb-4 <?php in_array('cacheBlockOutput', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('cacheBlockOutput', t('Cache block output')); ?>
                        <?= $form->select('cacheBlockOutput', $cacheBlockOutputOptions, (int) $cacheBlockOutput); ?>
                    </div>
                    <div class="mb-4 <?php in_array('cacheBlockOutputLifetime', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('cacheBlockOutputLifetime', t('Cache block output lifetime')); ?>
                        <?= $form->text('cacheBlockOutputLifetime', $cacheBlockOutputLifetime); ?>
                    </div>
                    <div class="mb-4 <?php in_array('cacheBlockOutputOnPost', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('cacheBlockOutputOnPost', t('Cache block output on post')); ?>
                        <?= $form->select('cacheBlockOutputOnPost', $cacheBlockOutputOnPostOptions, (int) $cacheBlockOutputOnPost); ?>
                    </div>
                    <div class="mb-4 <?php in_array('cacheBlockOutputForRegisteredUsers', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('cacheBlockOutputForRegisteredUsers', t('Cache block output for registered users')); ?>
                        <?= $form->select('cacheBlockOutputForRegisteredUsers', $cacheBlockOutputForRegisteredUsersOptions, (int) $cacheBlockOutputForRegisteredUsers); ?>
                    </div>
                    <div class="mb-4 <?php in_array('supportSavingNullValues', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('supportSavingNullValues', t('Support saving null values')); ?>
                        <?= $form->select('supportSavingNullValues', $supportSavingNullValuesOptions, (int) $supportSavingNullValues); ?>
                    </div>
                    <div class="mb-4 <?php in_array('ignorePageThemeGridFrameworkContainer', $fieldsWithError) ? print 'has-error' : false; ?>">
                        <?= $form->label('ignorePageThemeGridFrameworkContainer', t('Ignore page theme grid framework container')); ?>
                        <?= $form->select('ignorePageThemeGridFrameworkContainer', $ignorePageThemeGridFrameworkContainerOptions, (int) $ignorePageThemeGridFrameworkContainer); ?>
                    </div>

                </div>
            </div>

        </div>

        <div class="ccm-tab-content" id="ccm-tab-content-build-options" style="display: none;">

            <div class="mb-4 <?php in_array('installBlock', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('installBlock', t('Install block after creation')); ?>
                <?= $form->select('installBlock', $installBlockOptions, (int) $installBlock); ?>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4 <?php in_array('entriesAsFirstTab', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?= $form->label('entriesAsFirstTab', t('Entries as first tab')); ?>
                    <?= $form->select('entriesAsFirstTab', $entriesAsFirstTabOptions, (int) $entriesAsFirstTab); ?>
                </div>
                <div class="col-lg-6 mb-4 <?php in_array('maxNumberOfEntries', $fieldsWithError) ? print 'has-error' : false; ?>">
                    <?= $form->label('maxNumberOfEntries', t('Max. number of entries') . ' ' . t('(0 for unlimited)')); ?>
                    <?= $form->number('maxNumberOfEntries', $maxNumberOfEntries); ?>
                </div>
            </div>

            <div class="mb-4 <?php in_array('highlightMultiElementFields', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('highlightMultiElementFields', t('Highlight multi-element fields')); ?>
                <?= $form->select('highlightMultiElementFields', $highlightMultiElementFieldsOptions, (int) $highlightMultiElementFields); ?>
            </div>

            <div class="mb-4 <?php in_array('fieldsDivider', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('fieldsDivider', t('Use horizontal line as field\'s divider')); ?>
                <?= $form->select('fieldsDivider', $dividerOptions, $fieldsDivider); ?>
            </div>

            <div class="mb-4 <?php in_array('entryFieldsDivider', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('entryFieldsDivider', t('Use horizontal line as field\'s divider in repeatable entries')); ?>
                <?= $form->select('entryFieldsDivider', $dividerOptions, $entryFieldsDivider); ?>
            </div>

        </div>

        <div class="ccm-tab-content" id="ccm-tab-content-custom-code" style="display: none;">

            <div class="mb-4 <?php in_array('registerViewAssetsCustomCode', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('registerViewAssetsCustomCode', t('Custom code inside registerViewAssets() method')); ?>
                <?= $form->textarea('registerViewAssetsCustomCode', $registerViewAssetsCustomCode, ['style' => 'min-height: 200px;']); ?>
                <div class="form-text">
                    <?= t('You can use this field to include js/css assets.'); ?>
                    <br>
                    <?= t('Be careful when inserting custom code, invalid syntax can lead to errors.'); ?>
                    <br>
                    <?= t('Use %s spaces as indentation.', 8); ?>
                    <br>
                    <strong class="d-block mt-2"><?= t('Example code'); ?>:</strong>
                    <code class="bb-code-block">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;// Load lightbox files
                        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$this->requireAsset('javascript',
                        'feature/imagery/frontend');
                        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$this->requireAsset('css',
                        'feature/imagery/frontend');
                    </code>
                </div>
            </div>

            <div class="mb-4 <?php in_array('viewCustomCode', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('viewCustomCode', t('Custom code inside view() method')); ?>
                <?= $form->textarea('viewCustomCode', $viewCustomCode, ['style' => 'min-height: 200px;']); ?>
                <div class="form-text">
                    <?= t('You can use this field to include custom php code.'); ?>
                    <br>
                    <?= t('Be careful when inserting custom code, invalid syntax can lead to errors.'); ?>
                    <br>
                    <?= t('Use %s spaces as indentation.', 8); ?>
                    <br>
                    <strong class="d-block mt-2"><?= t('Example code'); ?>:</strong>
                    <code class="bb-code-block">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$this->set('someVariable', 'value');
                    </code>
                </div>
            </div>

            <div class="mb-4 <?php in_array('customControllerMethods', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('customControllerMethods', t('Custom controller methods')); ?>
                <?= $form->textarea('customControllerMethods', $customControllerMethods, ['style' => 'min-height: 200px;']); ?>
                <div class="form-text">
                    <?= t('You can use this field to put custom methods at the bottom of controller class.'); ?>
                    <br>
                    <?= t('Be careful when inserting custom code, invalid syntax can lead to errors.'); ?>
                    <br>
                    <?= t('Use %s spaces as indentation.', 4); ?>
                    <br>
                    <strong class="d-block mt-2"><?= t('Example code'); ?>:</strong>
                    <code class="bb-code-block mt4">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;private function exampleMethod($exampleArgument)
                        {
                        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;// Your custom code
                        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}
                    </code>
                </div>
            </div>

            <div class="mb-4 <?php in_array('excludedFromRemoval', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('excludedFromRemoval', t('Excluded files and folders when rebuilding block')); ?>
                <?= $form->textarea('excludedFromRemoval', implode(PHP_EOL, $excludedFromRemoval), ['style' => 'min-height: 200px;']); ?>
                <div class="form-text">
                    <?= t('You can use this field to exclude your own files/folders when rebuilding block (case scenario: you do not want to delete "templates" folder).'); ?>
                    <br>
                    <?= t('You can exclude first level files/folders only.'); ?>
                    <br>
                    <?= t('Do not exclude standard files/folders generated by Block Builder.'); ?>
                    <br>
                    <?= t('Every file/folder should be relative to controller.php (for example: templates) and put in new line.'); ?>
                </div>
            </div>
        </div>

        <div class="ccm-tab-content" id="ccm-tab-content-texts" style="display: none;">

            <div class="mb-4 populate-translation-fields">
                <i class="fas fa-book"></i> <?= t('Populate fields with'); ?>
                <a href="#" class="js-populate-translation-fields" data-type="translated"><?= t('translated'); ?></a>
                or
                <a href="#"
                   class="js-populate-translation-fields"
                   data-type="untranslated"
                ><?= t('untranslated'); ?></a>
                <?= t('default texts'); ?>
            </div>

            <div class="mb-4 <?php in_array('basicLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('basicLabel', t('Basic information')); ?>
                <?= $form->text('basicLabel', $basicLabel, ['data-translated-text' => t('Basic information'), 'data-untranslated-text' => 'Basic information']); ?>
                <div class="form-text"><?= t('Displayed name of "Basic information" tab'); ?></div>
            </div>

            <div class="mb-4 <?php in_array('entriesLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('entriesLabel', t('Entries')); ?>
                <?= $form->text('entriesLabel', $entriesLabel, ['data-translated-text' => t('Entries'), 'data-untranslated-text' => 'Entries']); ?>
                <div class="form-text"><?= t('Displayed name of "Repeatable entries" tab'); ?></div>
            </div>

            <div class="mb-4 <?php in_array('settingsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('settingsLabel', t('Settings')); ?>
                <?= $form->text('settingsLabel', $settingsLabel, ['data-translated-text' => t('Settings'), 'data-untranslated-text' => 'Settings']); ?>
                <div class="form-text"><?= t('Displayed name of "Settings" tab'); ?></div>
            </div>

            <div class="mb-4 <?php in_array('addAtTheTopLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('addAtTheTopLabel', t('Add at the top')); ?>
                <?= $form->text('addAtTheTopLabel', $addAtTheTopLabel, ['data-translated-text' => t('Add at the top'), 'data-untranslated-text' => 'Add at the top']); ?>
            </div>

            <div class="mb-4 <?php in_array('addAtTheBottomLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('addAtTheBottomLabel', t('Add at the bottom')); ?>
                <?= $form->text('addAtTheBottomLabel', $addAtTheBottomLabel, ['data-translated-text' => t('Add at the bottom'), 'data-untranslated-text' => 'Add at the bottom']); ?>
            </div>

            <div class="mb-4 <?php in_array('copyLastEntryLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('copyLastEntryLabel', t('Copy last entry')); ?>
                <?= $form->text('copyLastEntryLabel', $copyLastEntryLabel, ['data-translated-text' => t('Copy last entry'), 'data-untranslated-text' => 'Copy last entry']); ?>
            </div>

            <div class="mb-4 <?php in_array('collapseAllLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('collapseAllLabel', t('Collapse all')); ?>
                <?= $form->text('collapseAllLabel', $collapseAllLabel, ['data-translated-text' => t('Collapse all'), 'data-untranslated-text' => 'Collapse all']); ?>
            </div>

            <div class="mb-4 <?php in_array('expandAllLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('expandAllLabel', t('Expand all')); ?>
                <?= $form->text('expandAllLabel', $expandAllLabel, ['data-translated-text' => t('Expand all'), 'data-untranslated-text' => 'Expand all']); ?>
            </div>

            <div class="mb-4 <?php in_array('removeAllLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('removeAllLabel', t('Remove all')); ?>
                <?= $form->text('removeAllLabel', $removeAllLabel, ['data-translated-text' => t('Remove all'), 'data-untranslated-text' => 'Remove all']); ?>
            </div>

            <div class="mb-4 <?php in_array('disableSmoothScrollLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('disableSmoothScrollLabel', t('Disable smooth scroll')); ?>
                <?= $form->text('disableSmoothScrollLabel', $disableSmoothScrollLabel, ['data-translated-text' => t('Disable smooth scroll'), 'data-untranslated-text' => 'Disable smooth scroll']); ?>
            </div>

            <div class="mb-4 <?php in_array('keepAddedEntryCollapsedLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('keepAddedEntryCollapsedLabel', t('Keep added/copied entry collapsed')); ?>
                <?= $form->text('keepAddedEntryCollapsedLabel', $keepAddedEntryCollapsedLabel, ['data-translated-text' => t('Keep added/copied entry collapsed'), 'data-untranslated-text' => 'Keep added/copied entry collapsed']); ?>
            </div>

            <div class="mb-4 <?php in_array('noEntriesFoundLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('noEntriesFoundLabel', t('No entries found.')); ?>
                <?= $form->text('noEntriesFoundLabel', $noEntriesFoundLabel, ['data-translated-text' => t('No entries found.'), 'data-untranslated-text' => 'No entries found.']); ?>
            </div>

            <div class="mb-4 <?php in_array('maxNumberOfEntriesLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('maxNumberOfEntriesLabel', t('Max. number of entries')); ?>
                <?= $form->text('maxNumberOfEntriesLabel', $maxNumberOfEntriesLabel, ['data-translated-text' => t('Max. number of entries'), 'data-untranslated-text' => 'Max. number of entries']); ?>
            </div>

            <div class="mb-4 <?php in_array('removeEntryLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('removeEntryLabel', t('Remove entry')); ?>
                <?= $form->text('removeEntryLabel', $removeEntryLabel, ['data-translated-text' => t('Remove entry'), 'data-untranslated-text' => 'Remove entry']); ?>
            </div>

            <div class="mb-4 <?php in_array('duplicateEntryLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('duplicateEntryLabel', t('Duplicate entry')); ?>
                <?= $form->text('duplicateEntryLabel', $duplicateEntryLabel, ['data-translated-text' => t('Duplicate entry'), 'data-untranslated-text' => 'Duplicate entry']); ?>
            </div>

            <div class="mb-4 <?php in_array('duplicateEntryAndAddAtTheEndLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('duplicateEntryAndAddAtTheEndLabel', t('Duplicate entry and add at the end')); ?>
                <?= $form->text('duplicateEntryAndAddAtTheEndLabel', $duplicateEntryAndAddAtTheEndLabel, ['data-translated-text' => t('Duplicate entry and add at the end'), 'data-untranslated-text' => 'Duplicate entry and add at the end']); ?>
            </div>

            <div class="mb-4 <?php in_array('areYouSureLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('areYouSureLabel', t('Are you sure?')); ?>
                <?= $form->text('areYouSureLabel', $areYouSureLabel, ['data-translated-text' => t('Are you sure?'), 'data-untranslated-text' => 'Are you sure?']); ?>
            </div>

            <div class="mb-4 <?php in_array('requiredFieldsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('requiredFieldsLabel', t('Required fields')); ?>
                <?= $form->text('requiredFieldsLabel', $requiredFieldsLabel, ['data-translated-text' => t('Required fields'), 'data-untranslated-text' => 'Required fields']); ?>
            </div>

            <div class="mb-4 <?php in_array('urlEndingLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('urlEndingLabel', t('Custom string at the end of URL')); ?>
                <?= $form->text('urlEndingLabel', $urlEndingLabel, ['data-translated-text' => t('Custom string at the end of URL'), 'data-untranslated-text' => 'Custom string at the end of URL']); ?>
            </div>

            <div class="mb-4 <?php in_array('urlEndingHelpText', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('urlEndingHelpText', t('(e.g. #contact-form or ?ccm_paging_p=2)')); ?>
                <?= $form->text('urlEndingHelpText', $urlEndingHelpText, ['data-translated-text' => t('(e.g. #contact-form or ?ccm_paging_p=2)'), 'data-untranslated-text' => '(e.g. #contact-form or ?ccm_paging_p=2)']); ?>
            </div>

            <div class="mb-4 <?php in_array('textLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('textLabel', t('Text')); ?>
                <?= $form->text('textLabel', $textLabel, ['data-translated-text' => t('Text'), 'data-untranslated-text' => 'Text']); ?>
            </div>

            <div class="mb-4 <?php in_array('titleLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('titleLabel', t('Title')); ?>
                <?= $form->text('titleLabel', $titleLabel, ['data-translated-text' => t('Title'), 'data-untranslated-text' => 'Title']); ?>
            </div>

            <div class="mb-4 <?php in_array('altTextLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('altTextLabel', t('Alt text')); ?>
                <?= $form->text('altTextLabel', $altTextLabel, ['data-translated-text' => t('Alt text'), 'data-untranslated-text' => 'Alt text']); ?>
            </div>

            <div class="mb-4 <?php in_array('linkFromSitemapLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('linkFromSitemapLabel', t('Link from Sitemap')); ?>
                <?= $form->text('linkFromSitemapLabel', $linkFromSitemapLabel, ['data-translated-text' => t('Link from Sitemap'), 'data-untranslated-text' => 'Link from Sitemap']); ?>
            </div>

            <div class="mb-4 <?php in_array('linkFromFileManagerLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('linkFromFileManagerLabel', t('Link from File Manager')); ?>
                <?= $form->text('linkFromFileManagerLabel', $linkFromFileManagerLabel, ['data-translated-text' => t('Link from File Manager'), 'data-untranslated-text' => 'Link from File Manager']); ?>
            </div>

            <div class="mb-4 <?php in_array('externalLinkLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('externalLinkLabel', t('External Link')); ?>
                <?= $form->text('externalLinkLabel', $externalLinkLabel, ['data-translated-text' => t('External Link'), 'data-untranslated-text' => 'External Link']); ?>
            </div>

            <div class="mb-4 <?php in_array('showAdditionalFieldsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('showAdditionalFieldsLabel', t('Show additional fields')); ?>
                <?= $form->text('showAdditionalFieldsLabel', $showAdditionalFieldsLabel, ['data-translated-text' => t('Show additional fields'), 'data-untranslated-text' => 'Show additional fields']); ?>
            </div>

            <div class="mb-4 <?php in_array('hideAdditionalFieldsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('hideAdditionalFieldsLabel', t('Hide additional fields')); ?>
                <?= $form->text('hideAdditionalFieldsLabel', $hideAdditionalFieldsLabel, ['data-translated-text' => t('Hide additional fields'), 'data-untranslated-text' => 'Hide additional fields']); ?>
            </div>

            <div class="mb-4 <?php in_array('newWindowLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('newWindowLabel', t('Open in new window')); ?>
                <?= $form->text('newWindowLabel', $newWindowLabel, ['data-translated-text' => t('Open in new window'), 'data-untranslated-text' => 'Open in new window']); ?>
            </div>

            <div class="mb-4 <?php in_array('noFollowLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('noFollowLabel', t('Add nofollow attribute')); ?>
                <?= $form->text('noFollowLabel', $noFollowLabel, ['data-translated-text' => t('Add nofollow attribute'), 'data-untranslated-text' => 'Add nofollow attribute']); ?>
            </div>

            <div class="mb-4 <?php in_array('yesLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('yesLabel', t('Yes')); ?>
                <?= $form->text('yesLabel', $yesLabel, ['data-translated-text' => t('Yes'), 'data-untranslated-text' => 'Yes']); ?>
            </div>

            <div class="mb-4 <?php in_array('noLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('noLabel', t('No')); ?>
                <?= $form->text('noLabel', $noLabel, ['data-translated-text' => t('No'), 'data-untranslated-text' => 'No']); ?>
            </div>

            <div class="mb-4 <?php in_array('overrideThumbnailDimensionsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('overrideThumbnailDimensionsLabel', t('Override Thumbnail dimensions')); ?>
                <?= $form->text('overrideThumbnailDimensionsLabel', $overrideThumbnailDimensionsLabel, ['data-translated-text' => t('Override Thumbnail dimensions'), 'data-untranslated-text' => 'Override Thumbnail dimensions']); ?>
            </div>

            <div class="mb-4 <?php in_array('overrideFullscreenImageDimensionsLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('overrideFullscreenImageDimensionsLabel', t('Override Fullscreen Image dimensions')); ?>
                <?= $form->text('overrideFullscreenImageDimensionsLabel', $overrideFullscreenImageDimensionsLabel, ['data-translated-text' => t('Override Fullscreen Image dimensions'), 'data-untranslated-text' => 'Override Fullscreen Image dimensions']); ?>
            </div>

            <div class="mb-4 <?php in_array('widthLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('widthLabel', t('Width')); ?>
                <?= $form->text('widthLabel', $widthLabel, ['data-translated-text' => t('Width'), 'data-untranslated-text' => 'Width']); ?>
            </div>

            <div class="mb-4 <?php in_array('heightLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('heightLabel', t('Height')); ?>
                <?= $form->text('heightLabel', $heightLabel, ['data-translated-text' => t('Height'), 'data-untranslated-text' => 'Height']); ?>
            </div>

            <div class="mb-4 <?php in_array('cropLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('cropLabel', t('Crop')); ?>
                <?= $form->text('cropLabel', $cropLabel, ['data-translated-text' => t('Crop'), 'data-untranslated-text' => 'Crop']); ?>
            </div>

            <div class="mb-4 <?php in_array('pxLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('pxLabel', t('px')); ?>
                <?= $form->text('pxLabel', $pxLabel, ['data-translated-text' => t('px'), 'data-untranslated-text' => 'px']); ?>
            </div>

            <div class="mb-4 <?php in_array('nothingSelectedLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('nothingSelectedLabel', t('Nothing selected')); ?>
                <?= $form->text('nothingSelectedLabel', $nothingSelectedLabel, ['data-translated-text' => t('Nothing selected'), 'data-untranslated-text' => 'Nothing selected']); ?>
            </div>

            <div class="mb-4 <?php in_array('noResultsMatchedLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('noResultsMatchedLabel', t('No results matched {0}')); ?>
                <?= $form->text('noResultsMatchedLabel', $noResultsMatchedLabel, ['data-translated-text' => t('No results matched {0}'), 'data-untranslated-text' => 'No results matched {0}']); ?>
            </div>

            <div class="mb-4 <?php in_array('selectAllLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('selectAllLabel', t('Select All')); ?>
                <?= $form->text('selectAllLabel', $selectAllLabel, ['data-translated-text' => t('Select All'), 'data-untranslated-text' => 'Select All']); ?>
            </div>

            <div class="mb-4 <?php in_array('deselectAllLabel', $fieldsWithError) ? print 'has-error' : false; ?>">
                <?= $form->label('deselectAllLabel', t('Deselect All')); ?>
                <?= $form->text('deselectAllLabel', $deselectAllLabel, ['data-translated-text' => t('Deselect All'), 'data-untranslated-text' => 'Deselect All']); ?>
            </div>

        </div>

        <div class="ccm-tab-content"
             id="ccm-tab-content-<?= h(\BlockBuilder\FieldType\Enum\FieldTypeContextEnum::BasicFields->getTabHandle()); ?>"
             style="display: none;"
        >

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <select class="js-add-entry form-select" data-group-handle="basic">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                            <option value="<?= h($k); ?>" data-icon="<?= h($v['icon']); ?>"><?= h($v['label']); ?></option>
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
                        <label for="scroll-down-1" class="form-check-label"><?= t('Scroll down'); ?></label>
                    </div>
                    <a href="#"
                       class="entries-action js-expand-all"
                    ><i class="far fa-plus-square"></i> <?= t('Expand all'); ?></a>
                    <a href="#"
                       class="entries-action js-collapse-all"
                    ><i class="far fa-minus-square"></i> <?= t('Collapse all'); ?></a>
                    <a href="#"
                       class="entries-action entries-action-remove-all js-remove-all"
                       data-group-handle="basic"
                       data-confirm-text="<?= t('Are you sure?'); ?>"
                    ><i class="fas fa-times-circle"></i> <?= t('Remove all'); ?></a>
                </div>
            </div>

            <div class="mb-4">
                <div id="field-types-basic" class="js-sortable" data-entries="<?= h(json_encode($basic)); ?>"></div>
            </div>

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <select class="js-add-entry form-select" data-group-handle="basic">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                            <option value="<?= h($k); ?>" data-icon="<?= h($v['icon']); ?>"><?= h($v['label']); ?></option>
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
                        <label for="scroll-down-2" class="form-check-label"><?= t('Scroll down'); ?></label>
                    </div>
                    <a href="#"
                       class="entries-action js-expand-all"
                    ><i class="far fa-plus-square"></i> <?= t('Expand all'); ?></a>
                    <a href="#"
                       class="entries-action js-collapse-all"
                    ><i class="far fa-minus-square"></i> <?= t('Collapse all'); ?></a>
                    <a href="#"
                       class="entries-action entries-action-remove-all js-remove-all"
                       data-group-handle="basic"
                       data-confirm-text="<?= t('Are you sure?'); ?>"
                    ><i class="fas fa-times-circle"></i> <?= t('Remove all'); ?></a>
                </div>
            </div>

        </div>

        <div class="ccm-tab-content"
             id="ccm-tab-content-<?= h(\BlockBuilder\FieldType\Enum\FieldTypeContextEnum::RepeatableFields->getTabHandle()); ?>"
             style="display: none;"
        >

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <select class="js-add-entry form-select" data-group-handle="entries">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                            <option value="<?= h($k); ?>" data-icon="<?= h($v['icon']); ?>"><?= h($v['label']); ?></option>
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
                        <label for="scroll-down-3" class="form-check-label"><?= t('Scroll down'); ?></label>
                    </div>
                    <a href="#"
                       class="entries-action js-expand-all"
                    ><i class="far fa-plus-square"></i> <?= t('Expand all'); ?></a>
                    <a href="#"
                       class="entries-action js-collapse-all"
                    ><i class="far fa-minus-square"></i> <?= t('Collapse all'); ?></a>
                    <a href="#"
                       class="entries-action entries-action-remove-all js-remove-all"
                       data-group-handle="entries"
                       data-confirm-text="<?= t('Are you sure?'); ?>"
                    ><i class="fas fa-times-circle"></i> <?= t('Remove all'); ?></a>
                </div>
            </div>

            <div class="mb-4">
                <div id="field-types-entries" class="js-sortable" data-entries="<?= h(json_encode($entries)); ?>"></div>
            </div>

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <select class="js-add-entry form-select" data-group-handle="entries">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                            <option value="<?= h($k); ?>" data-icon="<?= h($v['icon']); ?>"><?= h($v['label']); ?></option>
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
                        <label for="scroll-down-4" class="form-check-label"><?= t('Scroll down'); ?></label>
                    </div>
                    <a href="#"
                       class="entries-action js-expand-all"
                    ><i class="far fa-plus-square"></i> <?= t('Expand all'); ?></a>
                    <a href="#"
                       class="entries-action js-collapse-all"
                    ><i class="far fa-minus-square"></i> <?= t('Collapse all'); ?></a>
                    <a href="#"
                       class="entries-action entries-action-remove-all js-remove-all"
                       data-group-handle="entries"
                       data-confirm-text="<?= t('Are you sure?'); ?>"
                    ><i class="fas fa-times-circle"></i> <?= t('Remove all'); ?></a>
                </div>
            </div>

        </div>

        <hr>
        <p class="small text-muted required-fields">* <?= t('Required fields'); ?></p>

        <div class="ccm-dashboard-form-actions-wrapper">
            <div class="ccm-dashboard-form-actions">
                <?= $form->hidden('sourceAction', $controller->getAction() ?? null); ?>
                <button type="submit" class="btn btn-primary float-end" value="1" name="buildBlock">
                    <i class="fas fa-hammer me-2"></i> <?= t('Build your block now!'); ?>
                </button>
                <?php if ($controller->getAction() === 'config' || $this->post('sourceAction') === 'config'): ?>
                    <button type="submit" class="btn btn-secondary float-end me-4" value="1" name="rebuildBlock">
                        <i class="fas fa-sync-alt"></i> <?= t('Rebuild and refresh block'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

    </form>

    <script type="text/template" class="js-template-entries">

        <div class="well entry js-entry <% if (error) { %>entry-has-error<% } %>" data-counter="<%=counter%>">

            <div class="entry-header">
                <div class="entry-header-action entry-header-remove-entry js-remove-entry"
                     data-confirm-text="<?= t('Are you sure?'); ?>"
                ><i class="fas fa-times"></i></div>
                <div class="entry-header-action entry-header-move-entry js-move-entry"><i class="fas fa-arrows-alt"></i>
                </div>
                <div class="entry-header-action entry-header-toggle-entry js-toggle-entry" data-action="collapse"><i
                        class="far fa-minus-square"
                    ></i></div>
                <div class="entry-header-title">
                    <span class="js-entry-title">
                        <% if (label) { %>
                            <%=label%>
                        <% } else { %>
                            #<%=counter%>
                        <% } %>
                    </span> <i class="<%=fieldTypeIcon%>"></i><span style="margin-left: 8px; font-weight: normal;"><%=fieldTypeName%></span>
                </div>
            </div>

            <div class="entry-content js-entry-content">

                <input type="hidden"
                       id="<%=groupHandle%>[<%=counter%>][fieldType]"
                       name="<%=groupHandle%>[<%=counter%>][fieldType]"
                       value="<%=fieldType%>"
                >

                <div class="row">
                    <div class="col-lg-6 mb-4 <% if (error['label']!=undefined) { %>has-error<% } %>">
                        <label for="<%=groupHandle%>[<%=counter%>][label]" class="form-label"><?= t('Label'); ?>
                            *</label>
                        <input type="text"
                               id="<%=groupHandle%>[<%=counter%>][label]"
                               name="<%=groupHandle%>[<%=counter%>][label]"
                               class="form-control js-entry-title-source"
                               value="<%=label%>"
                        >
                        <div class="form-text"><?= t('Human-readable name e.g. "Product name"'); ?></div>
                    </div>
                    <div class="col-lg-6 mb-4 <% if (error['handle']!=undefined) { %>has-error<% } %>">
                        <label for="<%=groupHandle%>[<%=counter%>][handle]" class="form-label"><?= t('Handle'); ?>
                            *</label>
                        <input type="text"
                               id="<%=groupHandle%>[<%=counter%>][handle]"
                               name="<%=groupHandle%>[<%=counter%>][handle]"
                               class="form-control"
                               value="<%=handle%>"
                               maxlength="50"
                        >
                        <div class="form-text"><?= t('a-zA-Z_ characters only e.g. "productName" or "product_name"'); ?></div>
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
                            <label for="<%=groupHandle%>[<%=counter%>][required]"
                                   class="form-check-label"
                            ><?= t('Required'); ?></label>
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
                            <label for="<%=groupHandle%>[<%=counter%>][titleSource]"
                                   class="form-check-label"
                            ><?= t('Use this field as title in repeatable entries'); ?></label>
                        </div>
                        <% } %>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <label for="<%=groupHandle%>[<%=counter%>][helpText]"
                               class="form-label"
                        ><?= t('Help text'); ?></label>
                        <input type="text"
                               id="<%=groupHandle%>[<%=counter%>][helpText]"
                               name="<%=groupHandle%>[<%=counter%>][helpText]"
                               class="form-control"
                               value="<%=helpText%>"
                        >
                        <div class="form-text"><?= t('This is example preview of help text.'); ?></p>
                        </div>
                    </div>

                    <% if (fieldType != 'text_field' && fieldType != 'link') { %>

                    <hr class="entry-hr">

                    <div class="mb-0">

                        <% if (fieldType == 'number') { %>
                        <div class="mb-4 <% if (error['numberSize']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][numberSize]"
                                   class="form-label"
                            ><?= t('Size'); ?></label>
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][numberSize]"
                                   name="<%=groupHandle%>[<%=counter%>][numberSize]"
                                   class="form-control"
                                   value="<%=numberSize%>"
                            >
                            <div class="form-text">
                                <?= t('Size od decimal field in mysql table.'); ?>
                                <br>
                                <?= t('Value "10.2" means, that database field can store 8 digits for the integer part and 2 digits for the fractional part.'); ?>
                                <br>
                                <?= t('If you want to store integers in database, write "0" after dot ("8.0 or similar").'); ?>
                                <br>
                                <?= t('If you want to store standard money values in database, write "2" after dot ("10.2 or similar").'); ?>
                            </div>
                        </div>
                        <div class="mb-4 <% if (error['numberStep']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][numberStep]"
                                   class="form-label"
                            ><?= t('Step'); ?></label>
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][numberStep]"
                                   name="<%=groupHandle%>[<%=counter%>][numberStep]"
                                   class="form-control"
                                   value="<%=numberStep%>"
                            >
                            <div class="form-text"><?= t('Value "1" will force integers in html field, use "0.01" when you want to use standard money format.'); ?></div>
                        </div>
                        <div class="mb-4 <% if (error['numberMin']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][numberMin]"
                                   class="form-label"
                            ><?= t('Minimum'); ?></label>
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][numberMin]"
                                   name="<%=groupHandle%>[<%=counter%>][numberMin]"
                                   class="form-control"
                                   value="<%=numberMin%>"
                            >
                        </div>
                        <div class="mb-4 <% if (error['numberMax']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][numberMax]"
                                   class="form-label"
                            ><?= t('Maximum'); ?></label>
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][numberMax]"
                                   name="<%=groupHandle%>[<%=counter%>][numberMax]"
                                   class="form-control"
                                   value="<%=numberMax%>"
                            >
                        </div>
                        <div class="mb-4 <% if (error['numberDisplayedDecimals']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimals]"
                                   class="form-label"
                            ><?= t('Displayed decimals'); ?></label>
                            <input type="number"
                                   id="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimals]"
                                   name="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimals]"
                                   class="form-control"
                                   value="<%=numberDisplayedDecimals%>"
                                   min="0"
                                   step="1"
                            >
                            <div class="form-text">
                                <?= t('How many decimals to display after separator.'); ?>
                                <br><?= t('Argument of php function number_format().'); ?>
                            </div>
                        </div>
                        <div class="mb-4 <% if (error['numberDisplayedDecimalSeparator']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                                   class="form-label"
                            ><?= t('Displayed decimal separator'); ?></label>
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                                   name="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                                   class="form-control"
                                   value="<%=numberDisplayedDecimalSeparator%>"
                            >
                            <div class="form-text">
                                <?= t('Usually "," (coma) or "." (dot).'); ?>
                                <br><?= t('Argument of php function number_format().'); ?>
                            </div>
                        </div>
                        <div class="<% if (error['numberDisplayedThousandsSeparator']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][numberDisplayedThousandsSeparator]"
                                   class="form-label"
                            ><?= t('Displayed thousands separator'); ?></label>
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][numberDisplayedThousandsSeparator]"
                                   name="<%=groupHandle%>[<%=counter%>][numberDisplayedThousandsSeparator]"
                                   class="form-control"
                                   value="<%=numberDisplayedThousandsSeparator%>"
                            >
                            <div class="form-text">
                                <?= t('Usually " " (space is not visible in this field), "." (dot) or "," (coma). You can also keep it empty.'); ?>
                                <br><?= t('Argument of php function number_format().'); ?>
                            </div>
                        </div>
                        <% } %>

                        <% if (fieldType == 'textarea') { %>
                        <div class="<% if (error['textareaHeight']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][textareaHeight]"
                                   class="form-label"
                            ><?= t('Height'); ?></label>
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <input type="text"
                                           id="<%=groupHandle%>[<%=counter%>][textareaHeight]"
                                           name="<%=groupHandle%>[<%=counter%>][textareaHeight]"
                                           class="form-control"
                                           value="<%=textareaHeight%>"
                                    >
                                    <span class="input-group-text">px</span>
                                </div>
                            </div>
                            <div class="form-text"><?= t('Default height: %s.', '66px'); ?></div>
                        </div>
                        <% } %>

                        <% if (fieldType == 'wysiwyg_editor') { %>
                        <div class="<% if (error['wysiwygEditorHeight']!=undefined) { %>has-error<% } %> mb-4">
                            <label for="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]"
                                   class="form-label"
                            ><?= t('Height'); ?></label>
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <input type="text"
                                           id="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]"
                                           name="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]"
                                           class="form-control"
                                           value="<%=wysiwygEditorHeight%>"
                                    >
                                    <span class="input-group-text">px</span>
                                </div>
                            </div>
                            <div class="form-text"><?= t('Default height of editable area: %s.', '40px'); ?>
                                <br><?= t('Editor auto-grow will be enabled if you leave this field empty.'); ?></div>
                        </div>

                        <div class="<% if (error['wysiwygCustomConfig']!=undefined) { %>has-error<% } %>">
                            <label for="<%=groupHandle%>[<%=counter%>][wysiwygCustomConfig]"
                                   class="form-label"
                            ><?= t('Custom editor config'); ?></label>
                            <textarea id="<%=groupHandle%>[<%=counter%>][wysiwygCustomConfig]"
                                      name="<%=groupHandle%>[<%=counter%>][wysiwygCustomConfig]"
                                      class="form-control"
                            ><%=wysiwygCustomConfig%></textarea>
                            <div class="form-text">
                                <?= t('Custom editor config should be inserted as JSON.'); ?>
                                <br>
                                <?= t('Full list of options can be found at %sToolbar Configurator%s.', '<a href="https://ckeditor.com/latest/samples/toolbarconfigurator/#advanced" target="_blank">', '</a>'); ?>
                                <br>
                                <?= t('Example config:'); ?>
                                <code class="bb-code-block">
<pre>
{
  "toolbar": [
    {
      "name": "document",
      "items": ["Source", "-"]
    },
    {
      "name": "basicstyles",
      "items": ["Bold", "Italic", "Underline", "Strike", "Subscript", "Superscript", "-", "RemoveFormat"]
    },
    {
      "name": "styles",
      "items": ["Styles", "Format"]
    }
  ]
}
</pre>
                                </code>
                            </div>
                        </div>
                        <% } %>

                        <% if (fieldType == 'select_field') { %>
                        <div class="mb-4">
                            <label for="<%=groupHandle%>[<%=counter%>][selectType]"
                                   class="form-label"
                            ><?= t('Type'); ?></label>
                            <select name="<%=groupHandle%>[<%=counter%>][selectType]"
                                    id="<%=groupHandle%>[<%=counter%>][selectType]"
                                    class="form-select"
                            >
                                <?php foreach ($selectFieldTypes as $k => $v): ?>
                                    <option value="<?= h($k); ?>"
                                    <% if (selectType === '<?= h($k); ?>') { %>selected<% } %>><?= h($v); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="<%=groupHandle%>[<%=counter%>][selectAddEmptyOption]"
                                   class="form-label"
                            ><?= t('Add empty option'); ?></label>
                            <select name="<%=groupHandle%>[<%=counter%>][selectAddEmptyOption]"
                                    id="<%=groupHandle%>[<%=counter%>][selectAddEmptyOption]"
                                    class="form-select"
                            >
                                <option value="0"
                                <% if (!selectAddEmptyOption) { %>selected<% } %>><?= t('No'); ?></option>
                                <option value="1"
                                <% if (selectAddEmptyOption) { %>selected<% } %>><?= t('Yes'); ?></option>
                            </select>
                            <div class="form-text">
                                <?= t('Works only with default and enhanced select field.'); ?>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="<%=groupHandle%>[<%=counter%>][selectDefaultValue]"
                                   class="form-label"
                            ><?= t('Default value'); ?></label>
                            <input type="text"
                                   id="<%=groupHandle%>[<%=counter%>][selectDefaultValue]"
                                   name="<%=groupHandle%>[<%=counter%>][selectDefaultValue]"
                                   class="form-control"
                                   value="<%=selectDefaultValue%>"
                            >
                        </div>
                        <div class="mb-4">
                            <label for="<%=groupHandle%>[<%=counter%>][selectListGenerationMethod]"
                                   class="form-label"
                            ><?= t('List generation method'); ?></label>
                            <select name="<%=groupHandle%>[<%=counter%>][selectListGenerationMethod]"
                                    id="<%=groupHandle%>[<%=counter%>][selectListGenerationMethod]"
                                    class="form-select js-change-select-list-generation-method"
                            >
                                <?php foreach ($selectFieldListGenerationMethods as $k => $v): ?>
                                    <option value="<?= h($k); ?>"
                                    <% if (selectListGenerationMethod === '<?= h($k); ?>') { %>selected<% } %>><?= h($v); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="<% if (error['selectOptions']!=undefined) { %>has-error<% } %>">
                            <div data-select-list-generation-method="basic_list"
                            <% if (!selectListGenerationMethod || (selectListGenerationMethod === 'basic_list')) { %>
                            style="display: block;"
                            <% } else { %>
                            style="display: none;"
                            <% } %>
                            >
                            <div class="mb-4">
                                <label for="<%=groupHandle%>[<%=counter%>][selectOptions]"
                                       class="form-label"
                                ><?= t('Select options'); ?></label>
                                <p class="small text-muted">
                                    <?= t('Enter every option in new line, e.g.'); ?>
                                    <code class="bb-code-block">
                                        <?= t('Don\'t show'); ?>
                                        <br>
                                        <?= t('Show'); ?>
                                    </code>
                                </p>
                                <p class="small text-muted">
                                    <?= t('You can also use double colon to specify key (value saved in database, only a-zA-Z0-9_ characters are permitted) and value (displayed text), e.g.'); ?>
                                    <code class="bb-code-block">
                                        <?= t('no :: Don\'t show'); ?>
                                        <br>
                                        <?= t('yes :: Show'); ?>
                                    </code>
                                </p>
                                <textarea name="<%=groupHandle%>[<%=counter%>][selectOptions]"
                                          id="<%=groupHandle%>[<%=counter%>][selectOptions]"
                                          class="form-control"
                                          rows="4"
                                ><%=selectOptions%></textarea>
                            </div>
                        </div>
                        <div data-select-list-generation-method="custom_code"
                        <% if (selectListGenerationMethod && (selectListGenerationMethod === 'custom_code')) { %>
                        style="display: block;"
                        <% } else { %>
                        style="display: none;"
                        <% } %>
                        >
                        <label for="<%=groupHandle%>[<%=counter%>][selectCustomCode]"
                               class="form-label"
                        ><?= t('Custom code'); ?></label>
                        <?php View::element('custom_code_in_option_list', [], 'block_builder'); ?>
                        <textarea name="<%=groupHandle%>[<%=counter%>][selectCustomCode]"
                                  id="<%=groupHandle%>[<%=counter%>][selectCustomCode]"
                                  class="form-control"
                                  rows="4"
                        ><%=selectCustomCode%></textarea>
                    </div>
                </div>
                <% } %>

                <% if (fieldType == 'select_multiple_field') { %>
                <div class="mb-4">
                    <label for="<%=groupHandle%>[<%=counter%>][selectMultipleType]"
                           class="form-label"
                    ><?= t('Type'); ?></label>
                    <select name="<%=groupHandle%>[<%=counter%>][selectMultipleType]"
                            id="<%=groupHandle%>[<%=counter%>][selectMultipleType]"
                            class="form-select"
                    >
                        <?php foreach ($selectMultipleFieldTypes as $k => $v): ?>
                            <option value="<?= h($k); ?>"
                            <% if (selectMultipleType === '<?= h($k); ?>') { %>selected<% } %>><?= h($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="<%=groupHandle%>[<%=counter%>][selectMultipleDefaultValue]"
                           class="form-label"
                    ><?= t('Default value'); ?></label>
                    <input type="text"
                           id="<%=groupHandle%>[<%=counter%>][selectMultipleDefaultValue]"
                           name="<%=groupHandle%>[<%=counter%>][selectMultipleDefaultValue]"
                           class="form-control"
                           value="<%=selectMultipleDefaultValue%>"
                    >
                    <div class="form-text">
                        <?= t('Use | (pipe character) to separate default values.'); ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="<%=groupHandle%>[<%=counter%>][selectMultipleListGenerationMethod]"
                           class="form-label"
                    ><?= t('List generation method'); ?></label>
                    <select name="<%=groupHandle%>[<%=counter%>][selectMultipleListGenerationMethod]"
                            id="<%=groupHandle%>[<%=counter%>][selectMultipleListGenerationMethod]"
                            class="form-select js-change-select-list-generation-method"
                    >
                        <?php foreach ($selectFieldListGenerationMethods as $k => $v): ?>
                            <option value="<?= h($k); ?>"
                            <% if (selectMultipleListGenerationMethod === '<?= h($k); ?>') { %>selected<% } %>><?= h($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="<% if (error['selectMultipleOptions']!=undefined) { %>has-error<% } %>">
                    <div data-select-list-generation-method="basic_list"
                    <% if (!selectMultipleListGenerationMethod || (selectMultipleListGenerationMethod === 'basic_list'))
                    { %>
                    style="display: block;"
                    <% } else { %>
                    style="display: none;"
                    <% } %>
                    >
                    <label for="<%=groupHandle%>[<%=counter%>][selectMultipleOptions]"
                           class="form-label"
                    ><?= t('Select options'); ?></label>
                    <p class="small text-muted">
                        <?= t('Enter every option in new line, e.g.'); ?>
                        <code class="bb-code-block">
                            <?= t('Don\'t show'); ?>
                            <br>
                            <?= t('Show'); ?>
                        </code>
                    </p>
                    <p class="small text-muted">
                        <?= t('You can also use double colon to specify key (value saved in database, only a-zA-Z0-9_ characters are permitted) and value (displayed text), e.g.'); ?>
                        <code class="bb-code-block">
                            <?= t('no :: Don\'t show'); ?>
                            <br>
                            <?= t('yes :: Show'); ?>
                        </code>
                    </p>
                    <textarea name="<%=groupHandle%>[<%=counter%>][selectMultipleOptions]"
                              id="<%=groupHandle%>[<%=counter%>][selectMultipleOptions]"
                              class="form-control"
                              rows="4"
                    ><%=selectMultipleOptions%></textarea>
                </div>
                <div data-select-list-generation-method="custom_code"
                <% if (selectMultipleListGenerationMethod && (selectMultipleListGenerationMethod === 'custom_code')) {
                %>
                style="display: block;"
                <% } else { %>
                style="display: none;"
                <% } %>
                >
                <label for="<%=groupHandle%>[<%=counter%>][selectMultipleCustomCode]"
                       class="form-label"
                ><?= t('Custom code'); ?></label>
                <?php View::element('custom_code_in_option_list', [], 'block_builder'); ?>
                <textarea name="<%=groupHandle%>[<%=counter%>][selectMultipleCustomCode]"
                          id="<%=groupHandle%>[<%=counter%>][selectMultipleCustomCode]"
                          class="form-control"
                          rows="4"
                ><%=selectMultipleCustomCode%></textarea>
            </div>
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
            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowEndingField]"
                   class="form-check-label"
            ><?= t('Show "Custom string at the end of URL" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]"
                   id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]"
                   value="1"
            <% if (parseInt(linkFromSitemapShowTextField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]"
                   class="form-check-label"
            ><?= t('Show "Text" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]"
                   id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]"
                   value="1"
            <% if (parseInt(linkFromSitemapShowTitleField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]"
                   class="form-check-label"
            ><?= t('Show "Title" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
                   id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
                   value="1"
            <% if (parseInt(linkFromSitemapShowNewWindowField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
                   class="form-check-label"
            ><?= t('Show "Open in new window" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
                   id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
                   value="1"
            <% if (parseInt(linkFromSitemapShowNoFollowField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
                   class="form-check-label"
            ><?= t('Show "Add nofollow attribute" field'); ?></label>
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
            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowEndingField]"
                   class="form-check-label"
            ><?= t('Show "Custom string at the end of URL" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]"
                   id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]"
                   value="1"
            <% if (parseInt(linkFromFileManagerShowTextField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]"
                   class="form-check-label"
            ><?= t('Show "Text" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]"
                   id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]"
                   value="1"
            <% if (parseInt(linkFromFileManagerShowTitleField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]"
                   class="form-check-label"
            ><?= t('Show "Title" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
                   id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
                   value="1"
            <% if (parseInt(linkFromFileManagerShowNewWindowField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
                   class="form-check-label"
            ><?= t('Show "Open in new window" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
                   id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
                   value="1"
            <% if (parseInt(linkFromFileManagerShowNoFollowField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
                   class="form-check-label"
            ><?= t('Show "Add nofollow attribute" field'); ?></label>
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
            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowEndingField]"
                   class="form-check-label"
            ><?= t('Show "Custom string at the end of URL" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]"
                   id="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]"
                   value="1"
            <% if (parseInt(externalLinkShowTextField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]"
                   class="form-check-label"
            ><?= t('Show "Text" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]"
                   id="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]"
                   value="1"
            <% if (parseInt(externalLinkShowTitleField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]"
                   class="form-check-label"
            ><?= t('Show "Title" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]"
                   id="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]"
                   value="1"
            <% if (parseInt(externalLinkShowNewWindowField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]"
                   class="form-check-label"
            ><?= t('Show "Open in new window" field'); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][externalLinkShowNoFollowField]"
                   id="<%=groupHandle%>[<%=counter%>][externalLinkShowNoFollowField]"
                   value="1"
            <% if (parseInt(externalLinkShowNoFollowField)) { %> checked="checked" <% } %>
            >
            <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowNoFollowField]"
                   class="form-check-label"
            ><?= t('Show "Add nofollow attribute" field'); ?></label>
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
            <label for="<%=groupHandle%>[<%=counter%>][imageShowAltTextField]"
                   class="form-check-label"
            ><?= t('Show "Alt text" field'); ?></label>
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
            <label for="<%=groupHandle%>[<%=counter%>][imageCreateThumbnailImage]"
                   class="form-check-label"
            ><?= t('Generate thumbnail using image helper (if original image is bigger than specified dimensions)'); ?></label>
        </div>
        <div class="row mt-2 js-image-create-thumbnail-image-wrapper <% if (error['imageThumbnailOptions']!=undefined) { %>has-error<% } %>"
             id="<%=groupHandle%>[<%=counter%>][imageThumbnailOptions]"
        <% if (!parseInt(imageCreateThumbnailImage)) { %> style="display: none;" <% } %>>
        <div class="col-lg-4 mb-4 <% if (error['imageThumbnailWidth']!=undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]"
                   class="form-label"
            ><?= t('Width'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]"
                       name="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]"
                       class="form-control"
                       value="<%=imageThumbnailWidth%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>
        <div class="col-lg-4 mb-4 <% if (error['imageThumbnailHeight']!=undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]"
                   class="form-label"
            ><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]"
                       name="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]"
                       class="form-control"
                       value="<%=imageThumbnailHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>
        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Crop'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]"
                       id="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]"
                       value="1"
                <% if (parseInt(imageThumbnailCrop)) { %> checked="checked" <% } %>
                >
                <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>
        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Editable'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]"
                       id="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]"
                       value="1"
                <% if (parseInt(imageThumbnailEditable)) { %> checked="checked" <% } %>
                >
                <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
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
            <label for="<%=groupHandle%>[<%=counter%>][imageCreateFullscreenImage]"
                   class="form-check-label"
            ><?= t('Generate fullscreen image using image helper (if original image is bigger than specified dimensions)'); ?></label>
        </div>
        <div class="row mt-2 js-image-create-fullscreen-image-wrapper <% if (error['imageFullscreenOptions']!=undefined) { %>has-error<% } %>"
             id="<%=groupHandle%>[<%=counter%>][imageFullscreenOptions]"
        <% if (!parseInt(imageCreateFullscreenImage)) { %> style="display: none;" <% } %>>
        <div class="col-lg-4 mb-4 <% if (error['imageFullscreenWidth']!=undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]"
                   class="form-label"
            ><?= t('Width'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]"
                       name="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]"
                       class="form-control"
                       value="<%=imageFullscreenWidth%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>
        <div class="col-lg-4 mb-4 <% if (error['imageFullscreenHeight']!=undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]"
                   class="form-label"
            ><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]"
                       name="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]"
                       class="form-control"
                       value="<%=imageFullscreenHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>
        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Crop'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]"
                       id="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]"
                       value="1"
                <% if (parseInt(imageFullscreenCrop)) { %> checked="checked" <% } %>
                >
                <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>
        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Editable'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]"
                       id="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]"
                       value="1"
                <% if (parseInt(imageFullscreenEditable)) { %> checked="checked" <% } %>
                >
                <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>
        </div>
        <% } %>

        <% if (fieldType == 'express') { %>
        <div class="<% if (error['expressHandle']!=undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][expressHandle]"
                   class="form-label"
            ><?= t('Express object handle'); ?> *</label>

            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][expressHandle]"
                   name="<%=groupHandle%>[<%=counter%>][expressHandle]"
                   class="form-control"
                   value="<%=expressHandle%>"
            >
        </div>
        <% } %>

        <% if (fieldType == 'file_set') { %>
        <div class="<% if (error['fileSetPrefix']!=undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][fileSetPrefix]"
                   class="form-label"
            ><?= t('Restrict File Set selection to those starting with:'); ?></label>

            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][fileSetPrefix]"
                   name="<%=groupHandle%>[<%=counter%>][fileSetPrefix]"
                   class="form-control"
                   value="<%=fileSetPrefix%>"
            >
        </div>
        <% } %>

        <% if (fieldType == 'html_editor') { %>
        <div class="<% if (error['htmlEditorHeight']!=undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]" class="form-label"><?= t('Height'); ?></label>
            <div class="input-group col-lg-3">
                <input type="text"
                       id="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]"
                       name="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]"
                       class="form-control"
                       value="<%=htmlEditorHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text">
                <?= t('Default height: %s.', '250px'); ?><br>
            </div>
        </div>
        <% } %>

        <% if (fieldType == 'date_picker') { %>
        <div class="<% if (error['datePickerPattern']!=undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][datePickerPattern]"
                   class="form-label"
            ><?= t('PHP Date Pattern'); ?></label>
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][datePickerPattern]"
                   name="<%=groupHandle%>[<%=counter%>][datePickerPattern]"
                   class="form-control"
                   value="<%=datePickerPattern%>"
            >
            <div class="form-text">
                <?= t('Check %sphp manual%s for available formats. Examples: <code>d.m.Y</code>, <code>d-m-Y</code>, <code>Y-m-d</code>, <code>m-d-Y</code>, <code>m/d/Y</code>', '<a href="https://www.php.net/manual/en/function.date.php" target="_blank" rel="noopener noreferrer">', '</a>'); ?>
            </div>
        </div>
        <% } %>

        </div>

        <% } %>

        </div>

        </div>

    </script>

    <script type="text/template" class="js-template-no-entries">

        <div class="alert alert-info js-alert"><?= t('You haven\'t added any field types yet.'); ?></div>

    </script>

</div>
