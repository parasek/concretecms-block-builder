<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder $controller
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 * @var BlockBuilder\Block\Dto\CreateBlockDto $config
 * @var BlockBuilder\NavigationTab\Enum\NavigationTabEnum[] $navigationTabEnums
 * @var Concrete\Core\Form\Service\Form $form
 * @var string $formActionPath
 * @var array $tabsWithError
 * @var array $blockTypeSets
 * @var array $cacheBlockRecordOptions
 * @var array $cacheBlockOutputOptions
 * @var array $cacheBlockOutputOnPostOptions
 * @var array $cacheBlockOutputForRegisteredUsersOptions
 * @var array $supportSavingNullValuesOptions
 * @var array $ignorePageThemeGridFrameworkContainerOptions
 * @var array $installBlockOptions
 * @var array $entriesAsFirstTabOptions
 * @var array $highlightMultiElementFieldsOptions
 * @var array $dividerOptions
 * @var array $fieldTypes
 * @var BlockBuilder\FieldType\FieldTypeDtoInterface[] $basic
 * @var BlockBuilder\FieldType\FieldTypeDtoInterface[] $entries
 */
?>

<div class="bb-container"
     id="bbContainer"
     data-csrf-token="<?= h($this->controller->token->generate('csrf_token')); ?>"
     data-fields-with-errors="<?= h(json_encode($fieldsWithError ?? [])); ?>"
     data-install-block-type-url="<?= app('url/manager')->resolve(['js/install-block-type']); ?>"
     data-uninstall-block-type-url="<?= h(app('url/manager')->resolve(['js/uninstall-block-type'])); ?>"
     data-delete-block-type-folder-url="<?= h(app('url/manager')->resolve(['js/delete-block-type-folder'])); ?>"
     data-confirmation-message="<?= t('Are you sure?'); ?>"
     data-install-block-type-success-message-1="<?= t('Block has been installed.'); ?>"
     data-install-block-type-success-message-2="<?= t('Click Rebuild and refresh block once again.'); ?>"
     data-uninstall-block-type-success-message-1="<?= t('Block has been uninstalled.'); ?>"
     data-uninstall-block-type-success-message-2="<?= t('Click Build your block now! once again.'); ?>"
     data-delete-block-type-folder-success-message-1="<?= t('Block type folder has been deleted.'); ?>"
     data-delete-block-type-folder-success-message-2="<?= t('Click Build your block now! once again.'); ?>"
>
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

    <form method="post" action="<?= h($controller->action($formActionPath)); ?>">
        <?= $this->controller->token->output('create_block'); ?>

        <?php // TODO: data-tab, name, icon? should go t seperate enum class + find usage accros file ?>
        <?php // TODO: zmienic texts na labals i i moze inne rzeczy? ?>
        <?php if (!empty($navigationTabEnums)): ?>

            <ul class="navigation-tabs mb-4" id="navigation-tabs">
                <?php foreach ($navigationTabEnums as $navigationTabEnum): ?>
                    <li>
                        <a href="#"
                           class="navigation-tab-link <?= h(in_array($navigationTabEnum->getHandle(), $tabsWithError) ? 'has-error' : null); ?>"
                           data-tab="<?= h($navigationTabEnum->getHandle()); ?>"
                        ><i class="<?= h($navigationTabEnum->getIcon()); ?>"></i> <?= h($navigationTabEnum->getName()); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php foreach ($navigationTabEnums as $k => $navigationTabEnum): ?>
                <div class="ccm-tab-content <?php if (!$k): ?>active<?php endif; ?>"
                     id="ccm-tab-content-<?= h($navigationTabEnum->getHandle()); ?>"
                     style="display: none;"
                >
                    <?= View::element(
                        _file: $navigationTabEnum->getTabContentElementName(),
                        args: [
                            'fieldsWithError' => $fieldsWithError,
                            'form' => $form,
                            'config' => $config ?? null,
                            // Block settings
                            'blockTypeSets' => $blockTypeSets,
                            'cacheBlockRecordOptions' => $cacheBlockRecordOptions,
                            'cacheBlockOutputOptions' => $cacheBlockOutputOptions,
                            'cacheBlockOutputOnPostOptions' => $cacheBlockOutputOnPostOptions,
                            'cacheBlockOutputForRegisteredUsersOptions' => $cacheBlockOutputForRegisteredUsersOptions,
                            'supportSavingNullValuesOptions' => $supportSavingNullValuesOptions,
                            'ignorePageThemeGridFrameworkContainerOptions' => $ignorePageThemeGridFrameworkContainerOptions,
                            // Build options
                            'installBlockOptions' => $installBlockOptions,
                            'entriesAsFirstTabOptions' => $entriesAsFirstTabOptions,
                            'highlightMultiElementFieldsOptions' => $highlightMultiElementFieldsOptions,
                            'dividerOptions' => $dividerOptions,
                            // Tab: Basic information / Tab: Repeatable entries
                            'fieldTypes' => $fieldTypes,
                            // Tab: Basic information
                            'basic' => $basic ,
                            // Tab: Repeatable entries
                            'entries' => $entries ,
                        ],
                        _pkgHandle: 'block_builder',
                    ); ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

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
