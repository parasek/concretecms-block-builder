<?php namespace BlockBuilder\FileGenerator;

use Concrete\Core\File\Service\File as FileService;
use BlockBuilder\Utility as BlockBuilderUtility;

defined('C5_EXECUTE') or die('Access Denied.');

class FormPhp
{

    public function generate($postDataSummary, $postData)
    {

        $filename = 'form.php';

        $code = '';
        $code .= '<?php defined(\'C5_EXECUTE\') or die(\'Access Denied.\'); ?>' . PHP_EOL . PHP_EOL;

        $code .= '<div id="form-container-<?php echo $uniqueID; ?>">' . PHP_EOL . PHP_EOL;

        if ((!empty($postData['basic']) and !empty($postData['entries'])) or !empty($postDataSummary['settingsTab'])) {
            $code .= BlockBuilderUtility::tab(1) . '<?php' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(1) . 'echo $app->make(\'helper/concrete/ui\')->tabs([' . PHP_EOL;
            if (!empty($postData['entriesAsFirstTab'])) {
                if (!empty($postData['entries'])) {
                    $code .= BlockBuilderUtility::tab(2) . '[\'entries-tab-\'.$uniqueID, t(\'' . addslashes($postData['entriesLabel']) . '\'), true],' . PHP_EOL;
                }
                if (!empty($postData['basic'])) {
                    $activeTab = empty($postData['entries']) ? ', true' : '';
                    $code .= BlockBuilderUtility::tab(2) . '[\'basic-information-tab-\'.$uniqueID, t(\'' . addslashes($postData['basicLabel']) . '\')' . $activeTab . '],' . PHP_EOL;
                }
            } else {
                if (!empty($postData['basic'])) {
                    $code .= BlockBuilderUtility::tab(2) . '[\'basic-information-tab-\'.$uniqueID, t(\'' . addslashes($postData['basicLabel']) . '\'), true],' . PHP_EOL;
                }
                if (!empty($postData['entries'])) {
                    $activeTab = empty($postData['basic']) ? ', true' : '';
                    $code .= BlockBuilderUtility::tab(2) . '[\'entries-tab-\'.$uniqueID, t(\'' . addslashes($postData['entriesLabel']) . '\')' . $activeTab . '],' . PHP_EOL;
                }
            }
            if (!empty($postDataSummary['settingsTab'])) {
                $code .= BlockBuilderUtility::tab(2) . '[\'settings-tab-\'.$uniqueID, t(\'' . addslashes($postData['settingsLabel']) . '\')],' . PHP_EOL;
            }
            $code .= BlockBuilderUtility::tab(1) . ']);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(1) . '?>' . PHP_EOL . PHP_EOL;
        }

        $code .= BlockBuilderUtility::tab(1) . '<div class="tab-content mt-4">' . PHP_EOL . PHP_EOL;

        if (!empty($postData['basic'])) {
            $code .= BlockBuilderUtility::tab(2) . '<div class="js-tab-pane';
            if (!empty($postData['entries'])) {
                $code .= ' tab-pane';
                if (empty($postData['entriesAsFirstTab'])) {
                    $code .= ' show active';
                }
                $code .= '" id="basic-information-tab-<?php echo $uniqueID; ?>';
            }
            $code .= '">' . PHP_EOL . PHP_EOL;
        }

        if (!empty($postData['basic'])) {

            $previousFieldTypeHadMultipleFields = false;
            $i = 0;

            foreach ($postData['basic'] as $k => $v) {

                $i++;

                $required = !empty($v['required']) ? '.\' *\'' : '';

                // Horizontal line (always)
                if ($postData['fieldsDivider'] == 'always' and $i != 1) {
                    $code .= BlockBuilderUtility::tab(3) . '<hr/>' . PHP_EOL . PHP_EOL;
                }

                // Horizontal line (smart)
                if ($postData['fieldsDivider'] == 'smart') {
                    if (
                        !empty($v['linkFromSitemapShowEndingField']) or
                        !empty($v['linkFromSitemapShowTextField']) or
                        !empty($v['linkFromSitemapShowTitleField']) or
                        !empty($v['linkFromSitemapShowNewWindowField']) or
                        !empty($v['linkFromSitemapShowNoFollowField']) or
                        !empty($v['linkFromFileManagerShowEndingField']) or
                        !empty($v['linkFromFileManagerShowTextField']) or
                        !empty($v['linkFromFileManagerShowTitleField']) or
                        !empty($v['linkFromFileManagerShowNewWindowField']) or
                        !empty($v['linkFromFileManagerShowNoFollowField']) or
                        !empty($v['externalLinkShowEndingField']) or
                        !empty($v['externalLinkShowTextField']) or
                        !empty($v['externalLinkShowTitleField']) or
                        !empty($v['externalLinkShowNewWindowField']) or
                        !empty($v['externalLinkShowNoFollowField']) or
                        ($v['fieldType'] == 'link') or
                        ($v['fieldType'] == 'image')
                    ) {
                        if ($i != 1) {
                            $code .= BlockBuilderUtility::tab(3) . '<hr/>' . PHP_EOL . PHP_EOL;
                        }
                        $previousFieldTypeHadMultipleFields = true;
                    } else {
                        if ($previousFieldTypeHadMultipleFields) {
                            $code .= BlockBuilderUtility::tab(3) . '<hr/>' . PHP_EOL . PHP_EOL;
                            $previousFieldTypeHadMultipleFields = false;
                        }
                    }
                }

                // Field types
                if ($v['fieldType'] == 'text_field') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . ', [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'number') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->number($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . ', [\'min\'=>\'' . $v['numberMin'] . '\', \'max\'=>\'' . $v['numberMax'] . '\', \'step\'=>\'' . $v['numberStep'] . '\']); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'textarea') {

                    $height = !empty($v['textareaHeight']) ? ', [\'style\'=>\'height: ' . $v['textareaHeight'] . 'px\']' : false;

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->textarea($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . $height . '); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'wysiwyg_editor') {

                    $height = !empty($v['wysiwygEditorHeight']) ? $v['wysiwygEditorHeight'] : false;
                    $customConfig = !empty($v['wysiwygCustomConfig']) ? $v['wysiwygCustomConfig'] : '{}';

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4 js-custom-editor-height-<?php echo $view->field(\'' . $v['handle'] . '\'); ?>-<?php echo $uniqueID; ?>">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $app->make(\'editor\')->outputEditorWithOptions($view->field(\'' . $v['handle'] . '\'), json_decode(\'' . $customConfig . '\', true), $' . $v['handle'] . '); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    if ($height) {
                        $code .= BlockBuilderUtility::tab(4) . '<style>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '.js-custom-editor-height-<?php echo str_replace([\'[\', \']\'], [\'\[\', \'\]\'], $view->field(\'' . $v['handle'] . '\')); ?>-<?php echo $uniqueID; ?> .cke_contents {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . 'height: ' . $height . 'px !important;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '.js-custom-editor-height-<?php echo str_replace([\'[\', \']\'], [\'\[\', \'\]\'], $view->field(\'' . $v['handle'] . '\')); ?>-<?php echo $uniqueID; ?> .cke_editable {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . 'min-height: ' . $height . 'px !important;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</style>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'select_field') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;

                    if (empty($v['selectType']) or $v['selectType'] === 'default_select') {
                        $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . '_options, $' . $v['handle'] . '); ?>' . PHP_EOL;
                    } elseif ($v['selectType'] === 'enhanced_select') {
                        $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . '_options, $' . $v['handle'] . ', [\'class\' => \'form-control\', \'data-enhanced-select\' => hash(\'md5\', $view->field(\'' . $v['handle'] . '\'))]); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '<script type="text/javascript">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$(function() {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . 'Concrete.Vue.activateContext(\'cms\', function(Vue, config) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . 'new Vue({' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . 'el: \'[data-enhanced-select="<?php echo h(hash(\'md5\', $view->field(\'' . $v['handle'] . '\'))); ?>"]\',' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . 'components: config.components,' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . 'mounted(){' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . 'new TomSelect($(\'[data-enhanced-select="<?php echo h(hash(\'md5\', $view->field(\'' . $v['handle'] . '\'))); ?>"]\').get(0), {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(10) . 'create: false' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '},' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</script>' . PHP_EOL;
                    } elseif ($v['selectType'] === 'radio_list') {
                        $code .= BlockBuilderUtility::tab(4) . '<?php $radioIndex = 0; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '<?php foreach ($' . $v['handle'] . '_options as $' . $v['handle'] . '_key => $' . $v['handle'] . '_value): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php $radioIndex++; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<div class="form-check">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->radio($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . '_key, $' . $v['handle'] . ' == $' . $v['handle'] . '_key, [\'id\' => $view->field(\'' . $v['handle'] . '\' . $radioIndex)]); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\' . $radioIndex), t($' . $v['handle'] . '_value), [\'class\' => \'form-check-label\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '<?php endforeach; ?>' . PHP_EOL;
                    }

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'select_multiple_field') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;

                    if (empty($v['selectMultipleType']) or $v['selectMultipleType'] === 'default_multiselect') {
                        $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->selectMultiple($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . '_options, explode(\'|\', $' . $v['handle'] . ')); ?>' . PHP_EOL;
                    } elseif ($v['selectMultipleType'] === 'enhanced_multiselect') {
                        $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->selectMultiple($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . '_options, explode(\'|\', $' . $v['handle'] . '), [\'class\' => \'form-control\', \'data-enhanced-select\' => hash(\'md5\', $view->field(\'' . $v['handle'] . '\'))]); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '<script type="text/javascript">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$(function() {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . 'Concrete.Vue.activateContext(\'cms\', function(Vue, config) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . 'new Vue({' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . 'el: \'[data-enhanced-select="<?php echo h(hash(\'md5\', $view->field(\'' . $v['handle'] . '\'))); ?>"]\',' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . 'components: config.components,' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . 'mounted(){' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . 'new TomSelect($(\'[data-enhanced-select="<?php echo h(hash(\'md5\', $view->field(\'' . $v['handle'] . '\'))); ?>"]\').get(0), {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(10) . 'create: false' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '},' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</script>' . PHP_EOL;
                    } elseif ($v['selectMultipleType'] === 'checkbox_list') {
                        $code .= BlockBuilderUtility::tab(4) . '<?php $checkboxIndex = 0; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '<?php foreach ($' . $v['handle'] . '_options as $' . $v['handle'] . '_key => $' . $v['handle'] . '_value): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php $checkboxIndex++; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php $' . $v['handle'] . '_selected = explode(\'|\', $' . $v['handle'] . '); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<div class="form-check">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->checkbox($view->field(\'' . $v['handle'] . '[]\'), $' . $v['handle'] . '_key, in_array($' . $v['handle'] . '_key, $' . $v['handle'] . '_selected), [\'id\' => $view->field(\'' . $v['handle'] . '\' . $checkboxIndex)]); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\' . $checkboxIndex), t($' . $v['handle'] . '_value), [\'class\' => \'form-check-label\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '<?php endforeach; ?>' . PHP_EOL;
                    }

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link') {

                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields']) {
                        $fieldGroupHighlight = ' field-group-highlight';
                    }

                    $code .= BlockBuilderUtility::tab(3) . '<div class="field-group' . $fieldGroupHighlight . ' mb-4 js-link-wrapper">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="row margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_link_type\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 col-lg-6 margin-bottom-on-mobile">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_link_type\'), $linkTypes, $' . $v['handle'] . '[\'link_type\'] ?? null, [\'class\' => \'form-select js-link-type-' . $v['handle'] . '-\'.$uniqueID]); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(6) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 col-lg-6">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<span class="toggle-additional-fields <?php if (!empty($' . $v['handle'] . '[\'show_additional_fields\']) AND $' . $v['handle'] . '[\'show_additional_fields\']): ?>toggle-additional-fields-active<?php endif; ?> btn btn-secondary js-toggle-additional-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'data-show-text="<?php echo t(\'' . addslashes($postData['showAdditionalFieldsLabel']) . '\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'data-hide-text="<?php echo t(\'' . addslashes($postData['hideAdditionalFieldsLabel']) . '\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<?php if (empty($' . $v['handle'] . '[\'link_type\'])): ?>style="display: none;"<?php endif; ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '><i class="fas fa-caret-right"></i> <span class="js-toggle-additional-fields-text-' . $v['handle'] . '-<?php echo $uniqueID; ?>"><?php if (!empty($' . $v['handle'] . '[\'show_additional_fields\'])): ?><?php echo t(\'' . addslashes($postData['hideAdditionalFieldsLabel']) . '\'); ?><?php else: ?><?php echo t(\'' . addslashes($postData['showAdditionalFieldsLabel']) . '\'); ?><?php endif; ?></span></span>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->hidden($view->field(\'' . $v['handle'] . '_show_additional_fields\'), $' . $v['handle'] . '[\'show_additional_fields\'] ?? null, [\'class\'=>\'js-toggle-additional-fields-value-' . $v['handle'] . '-\'.$uniqueID, \'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="row margin-bottom js-link-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?> js-link-wrapper-link_from_sitemap-' . $v['handle'] . '-<?php echo $uniqueID; ?>" style="display: none;">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $app->make(\'helper/form/page_selector\')->selectPage($view->field(\'' . $v['handle'] . '_link_from_sitemap\'), (!empty($' . $v['handle'] . '[\'link_from_sitemap\']) AND !Page::getByID($' . $v['handle'] . '[\'link_from_sitemap\'])->isError() AND !Page::getByID($' . $v['handle'] . '[\'link_from_sitemap\'])->isInTrash()) ? $' . $v['handle'] . '[\'link_from_sitemap\'] : null); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="row margin-bottom  js-link-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?> js-link-wrapper-link_from_file_manager-' . $v['handle'] . '-<?php echo $uniqueID; ?>" style="display: none;">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $app->make(\'helper/concrete/asset_library\')->file(\'' . $v['handle'] . '_link_from_file_manager-\'.$uniqueID, $view->field(\'' . $v['handle'] . '_link_from_file_manager\'), t(\'Choose File\'), !empty($' . $v['handle'] . '[\'link_from_file_manager\']) ? File::getByID($' . $v['handle'] . '[\'link_from_file_manager\']) : null); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="row margin-bottom js-link-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?> js-link-wrapper-external_link-' . $v['handle'] . '-<?php echo $uniqueID; ?>" style="display: none;">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 col-lg-3 margin-bottom-on-mobile">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_protocol\'), $externalLinkProtocols, $' . $v['handle'] . '[\'protocol\'] ?? \'http://\', [\'class\'=>\'form-select js-external-link-protocol-' . $v['handle'] . '-\'.$uniqueID]); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 col-lg-9">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_external_link\'), $' . $v['handle'] . '[\'external_link\'] ?? null, [\'maxlength\'=>\'255\', \'class\'=>\'form-control js-external-link-url-' . $v['handle'] . '-\'.$uniqueID]); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<script>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '$(function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '$(\'.js-external-link-url-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').on(\'keyup change\', function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . 'var url = $(this).val();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . 'if (url.indexOf(\'https://\') == 0) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(this).val(url.substring(8));' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(\'.js-external-link-protocol-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val(url.substring(0, 8));' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '} else if (url.indexOf(\'http://\') == 0) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(this).val(url.substring(7));' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(\'.js-external-link-protocol-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val(url.substring(0, 7));' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '}' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</script>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="row js-additional-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>" style="display: none;">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_ending\'), t(\'' . addslashes($postData['urlEndingLabel']) . '\')); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_ending\'), $' . $v['handle'] . '[\'ending\'] ?? null, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<div class="form-text"><?php echo t(\'' . addslashes($postData['urlEndingHelpText']) . '\'); ?></div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_text\'), t(\'' . addslashes($postData['textLabel']) . '\')); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->textarea($view->field(\'' . $v['handle'] . '_text\'), $' . $v['handle'] . '[\'text\'] ?? null, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_title\'), t(\'' . addslashes($postData['titleLabel']) . '\')); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_title\'), $' . $v['handle'] . '[\'title\'] ?? null, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_new_window\'), t(\'' . addslashes($postData['newWindowLabel']) . '\')); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_new_window\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '[\'new_window\'] ?? null); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_no_follow\'), t(\'' . addslashes($postData['noFollowLabel']) . '\')); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_no_follow\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '[\'no_follow\'] ?? null); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<script>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '$(function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . 'var linkType = $(\'.js-link-type-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . 'var toggleAdditionalFieldsValue = $(\'.js-toggle-additional-fields-value-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . 'if (linkType!=0) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '$(\'.js-link-wrapper-\'+linkType+\'-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'if (toggleAdditionalFieldsValue!=0) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-additional-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '}' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '}' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '$(\'.js-link-type-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').on(\'change\', function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'var linkType = $(\'.js-link-type-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'var toggleAdditionalFieldsValue = parseInt($(\'.js-toggle-additional-fields-value-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val());' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '$(\'.js-link-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').hide();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '$(\'.js-additional-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').hide();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '$(\'.js-toggle-additional-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').hide();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'if (linkType!=0) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-link-wrapper-\'+linkType+\'-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . 'if (toggleAdditionalFieldsValue==1) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(\'.js-additional-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '}' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '}' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '$(\'.js-toggle-additional-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').on(\'click\', function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'var toggleAdditionalFieldsValue = parseInt($(\'.js-toggle-additional-fields-value-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val());' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'var showText = $(\'.js-toggle-additional-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').attr(\'data-show-text\');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'var hideText = $(\'.js-toggle-additional-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').attr(\'data-hide-text\');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'if (toggleAdditionalFieldsValue) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-additional-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').hide();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').removeClass(\'toggle-additional-fields-active\');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-fields-value-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val(0);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-fields-text-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').text(showText);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '} else {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-additional-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').addClass(\'toggle-additional-fields-active\');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-fields-value-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val(1);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-fields-text-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').text(hideText);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '}' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</script>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3) . '</div><?php // .js-link-wrapper ?>' . PHP_EOL . PHP_EOL;


                }

                if ($v['fieldType'] == 'link_from_sitemap') {

                    $hasMultipleElements =
                        !empty($v['linkFromSitemapShowEndingField']) ||
                        !empty($v['linkFromSitemapShowTextField']) ||
                        !empty($v['linkFromSitemapShowTitleField']) ||
                        !empty($v['linkFromSitemapShowNewWindowField']) ||
                        !empty($v['linkFromSitemapShowNoFollowField']);
                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields'] && $hasMultipleElements) {
                        $fieldGroupHighlight = ' field-group-highlight mb-4';
                    }

                    $code .= BlockBuilderUtility::tab(3) . '<div class="field-group' . $fieldGroupHighlight . '">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<?php echo $app->make(\'helper/form/page_selector\')->selectPage($view->field(\'' . $v['handle'] . '\'), (!Page::getByID($' . $v['handle'] . ')->isError() AND !Page::getByID($' . $v['handle'] . ')->isInTrash()) ? $' . $v['handle'] . ' : null); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(5) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_ending\'), t(\'' . addslashes($postData['urlEndingLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_ending\'), $' . $v['handle'] . '_ending, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<div class="form-text"><?php echo t(\'' . addslashes($postData['urlEndingHelpText']) . '\'); ?></div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_text\'), t(\'' . addslashes($postData['textLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->textarea($view->field(\'' . $v['handle'] . '_text\'), $' . $v['handle'] . '_text, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_title\'), t(\'' . addslashes($postData['titleLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_title\'), $' . $v['handle'] . '_title, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromSitemapShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_new_window\'), t(\'' . addslashes($postData['newWindowLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_new_window\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '_new_window); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromSitemapShowNoFollowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_no_follow\'), t(\'' . addslashes($postData['noFollowLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_no_follow\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '_no_follow); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link_from_file_manager') {

                    $hasMultipleElements =
                        !empty($v['linkFromFileManagerShowEndingField']) ||
                        !empty($v['linkFromFileManagerShowTextField']) ||
                        !empty($v['linkFromFileManagerShowTitleField']) ||
                        !empty($v['linkFromFileManagerShowNewWindowField']) ||
                        !empty($v['linkFromFileManagerShowNoFollowField']);
                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields'] && $hasMultipleElements) {
                        $fieldGroupHighlight = ' field-group-highlight mb-4';
                    }

                    $code .= BlockBuilderUtility::tab(3) . '<div class="field-group' . $fieldGroupHighlight . '">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<?php echo $app->make(\'helper/concrete/asset_library\')->file(\'' . $v['handle'] . '-\'.$uniqueID, $view->field(\'' . $v['handle'] . '\'), t(\'Choose File\'), !empty($' . $v['handle'] . ') ? File::getByID($' . $v['handle'] . ') : null); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(5) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    if (!empty($v['linkFromFileManagerShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_ending\'), t(\'' . addslashes($postData['urlEndingLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_ending\'), $' . $v['handle'] . '_ending, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<div class="form-text"><?php echo t(\'' . addslashes($postData['urlEndingHelpText']) . '\'); ?></div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_text\'), t(\'' . addslashes($postData['textLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->textarea($view->field(\'' . $v['handle'] . '_text\'), $' . $v['handle'] . '_text, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_title\'), t(\'' . addslashes($postData['titleLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_title\'), $' . $v['handle'] . '_title, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_new_window\'), t(\'' . addslashes($postData['newWindowLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_new_window\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '_new_window); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromFileManagerShowNoFollowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_no_follow\'), t(\'' . addslashes($postData['noFollowLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_no_follow\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '_no_follow); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'external_link') {

                    $hasMultipleElements =
                        !empty($v['externalLinkShowEndingField']) ||
                        !empty($v['externalLinkShowTextField']) ||
                        !empty($v['externalLinkShowTitleField']) ||
                        !empty($v['externalLinkShowNewWindowField']) ||
                        !empty($v['externalLinkShowNoFollowField']);
                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields'] && $hasMultipleElements) {
                        $fieldGroupHighlight = ' field-group-highlight mb-4';
                    }

                    $code .= BlockBuilderUtility::tab(3) . '<div class="field-group' . $fieldGroupHighlight . '">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(5) . '<div class="row">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<div class="col-12 col-lg-3 margin-bottom-on-mobile">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_protocol\'), $externalLinkProtocols, $' . $v['handle'] . '_protocol ? $' . $v['handle'] . '_protocol : \'http://\', [\'class\'=>\'form-select js-external-link-protocol-' . $v['handle'] . '-\'.$uniqueID]); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<div class="col-12 col-lg-9">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . ', [\'maxlength\'=>\'255\', \'class\'=>\'form-control js-external-link-url-' . $v['handle'] . '-\'.$uniqueID]); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<script>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '$(function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '$(\'.js-external-link-url-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').on(\'keyup change\', function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . 'var url = $(this).val();' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . 'if (url.indexOf(\'https://\') == 0) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(this).val(url.substring(8));' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(\'.js-external-link-protocol-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val(url.substring(0, 8));' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '} else if (url.indexOf(\'http://\') == 0) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(this).val(url.substring(7));' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '$(\'.js-external-link-protocol-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val(url.substring(0, 7));' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '}' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</script>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    if (!empty($v['externalLinkShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_ending\'), t(\'' . addslashes($postData['urlEndingLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_ending\'), $' . $v['handle'] . '_ending, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<div class="form-text"><?php echo t(\'' . addslashes($postData['urlEndingHelpText']) . '\'); ?></div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_text\'), t(\'' . addslashes($postData['textLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->textarea($view->field(\'' . $v['handle'] . '_text\'), $' . $v['handle'] . '_text, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_title\'), t(\'' . addslashes($postData['titleLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_title\'), $' . $v['handle'] . '_title, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['externalLinkShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_new_window\'), t(\'' . addslashes($postData['newWindowLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_new_window\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '_new_window); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['externalLinkShowNoFollowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_no_follow\'), t(\'' . addslashes($postData['noFollowLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_no_follow\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '_no_follow); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'image') {

                    $hasMultipleElements =
                        !empty($v['imageShowAltTextField']) ||
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) ||
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']));
                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields'] && $hasMultipleElements) {
                        $fieldGroupHighlight = ' field-group-highlight';
                    }

                    $code .= BlockBuilderUtility::tab(3) . '<div class="field-group' . $fieldGroupHighlight . ' mb-4 js-image-wrapper">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div class="row margin-bottom">' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 col-lg-' . ((!empty($v['imageShowAltTextField']) or (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) or (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))) ? 6 : 12) . ' margin-bottom-on-mobile">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '<?php echo $app->make(\'helper/concrete/asset_library\')->image(\'' . $v['handle'] . '-\'.$uniqueID, $view->field(\'' . $v['handle'] . '\'), t(\'Choose Image\'), !empty($' . $v['handle'] . ') ? File::getByID($' . $v['handle'] . ') : null); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(6) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;

                    if (!empty($v['imageShowAltTextField']) or (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) or (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))) {
                        $code .= BlockBuilderUtility::tab(5) . '<div class="col-12 col-lg-6">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<span class="toggle-additional-image-fields <?php if (!empty($' . $v['handle'] . '_data[\'show_additional_fields\'])): ?>toggle-additional-image-fields-active<?php endif; ?> btn btn-secondary js-toggle-additional-image-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . 'data-show-text="<?php echo t(\'' . addslashes($postData['showAdditionalFieldsLabel']) . '\'); ?>"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . 'data-hide-text="<?php echo t(\'' . addslashes($postData['hideAdditionalFieldsLabel']) . '\'); ?>"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '><i class="fas fa-caret-right"></i> <span class="js-toggle-additional-image-fields-text-' . $v['handle'] . '-<?php echo $uniqueID; ?>"><?php if (!empty($' . $v['handle'] . '_data[\'show_additional_fields\'])): ?><?php echo t(\'' . addslashes($postData['hideAdditionalFieldsLabel']) . '\'); ?><?php else: ?><?php echo t(\'' . addslashes($postData['showAdditionalFieldsLabel']) . '\'); ?><?php endif; ?></span></span>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->hidden($view->field(\'' . $v['handle'] . '_show_additional_fields\'), $' . $v['handle'] . '_data[\'show_additional_fields\'] ?? null, [\'class\'=>\'js-toggle-additional-image-fields-value-' . $v['handle'] . '-\'.$uniqueID, \'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                    if (!empty($v['imageShowAltTextField']) or (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) or (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))) {

                        $code .= BlockBuilderUtility::tab(4) . '<div class="js-additional-image-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>" <?php if (empty($' . $v['handle'] . '_data[\'show_additional_fields\'])): ?>style="display: none;"<?php endif; ?>>' . PHP_EOL . PHP_EOL;

                        if (!empty($v['imageShowAltTextField'])) {
                            $code .= BlockBuilderUtility::tab(5) . '<div class="mb-4">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_alt\'), t(\'' . addslashes($postData['altTextLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->text($view->field(\'' . $v['handle'] . '_alt\'), $' . $v['handle'] . '_alt, [\'maxlength\'=>\'255\']); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL . PHP_EOL;
                        }

                        if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {
                            $code .= BlockBuilderUtility::tab(5) . '<div class="row margin-bottom">' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(6) . '<div class="col-12">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->label(\'\', t(\'' . addslashes($postData['overrideThumbnailDimensionsLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '<div class="form-check">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->checkbox($view->field(\'' . $v['handle'] . '_override_dimensions\'), \'1\', $' . $v['handle'] . '_data[\'override_dimensions\'] ?? null, [\'class\' => \'form-check-input js-toggle-override-dimensions-\'.$uniqueID]); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'' . $v['handle'] . '_override_dimensions\'); ?>" class="form-check-label"><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(6) . '<div class="row mt-3 js-override-dimensions-wrapper-<?php echo $uniqueID; ?>" <?php if (empty($' . $v['handle'] . '_data[\'override_dimensions\'])): ?>style="display: none;"<?php endif; ?>>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(7) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_custom_width\'), t(\'' . addslashes($postData['widthLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<div class="input-group">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<?php echo $form->number($view->field(\'' . $v['handle'] . '_custom_width\'), !empty($' . $v['handle'] . '_data[\'custom_width\']) ? $' . $v['handle'] . '_data[\'custom_width\'] : \'\'); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(7) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_custom_height\'), t(\'' . addslashes($postData['heightLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<div class="input-group">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<?php echo $form->number($view->field(\'' . $v['handle'] . '_custom_height\'), !empty($' . $v['handle'] . '_data[\'custom_height\']) ? $' . $v['handle'] . '_data[\'custom_height\'] : \'\'); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(7) . '<div class="col-12 col-lg-4">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_custom_crop\'), t(\'' . addslashes($postData['cropLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_custom_crop\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '_data[\'custom_crop\'] ?? null); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL . PHP_EOL;
                        }

                        if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {
                            $code .= BlockBuilderUtility::tab(5) . '<div class="row margin-bottom">' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(6) . '<div class="col-12">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->label(\'\', t(\'' . addslashes($postData['overrideFullscreenImageDimensionsLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '<div class="form-check">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->checkbox($view->field(\'' . $v['handle'] . '_override_fullscreen_dimensions\'), \'1\', $' . $v['handle'] . '_data[\'override_fullscreen_dimensions\'] ?? null, [\'class\' => \'form-check-input js-toggle-override-fullscreen-dimensions-\'.$uniqueID]); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'' . $v['handle'] . '_override_fullscreen_dimensions\'); ?>" class="form-check-label"><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(6) . '<div class="row mt-3 js-override-fullscreen-dimensions-wrapper-<?php echo $uniqueID; ?>" <?php if (empty($' . $v['handle'] . '_data[\'override_fullscreen_dimensions\'])): ?>style="display: none;"<?php endif; ?>>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(7) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_custom_fullscreen_width\'), t(\'' . addslashes($postData['widthLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<div class="input-group">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<?php echo $form->number($view->field(\'' . $v['handle'] . '_custom_fullscreen_width\'), !empty($' . $v['handle'] . '_data[\'custom_fullscreen_width\']) ? $' . $v['handle'] . '_data[\'custom_fullscreen_width\'] : \'\'); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(7) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_custom_fullscreen_height\'), t(\'' . addslashes($postData['heightLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<div class="input-group">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<?php echo $form->number($view->field(\'' . $v['handle'] . '_custom_fullscreen_height\'), !empty($' . $v['handle'] . '_data[\'custom_fullscreen_height\']) ? $' . $v['handle'] . '_data[\'custom_fullscreen_height\'] : \'\'); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(7) . '<div class="col-12 col-lg-4">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '_custom_fullscreen_crop\'), t(\'' . addslashes($postData['cropLabel']) . '\')); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '_custom_fullscreen_crop\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $' . $v['handle'] . '_data[\'custom_fullscreen_crop\'] ?? null); ?>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL . PHP_EOL;
                        }

                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(4) . '<script>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$(function() {' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(6) . '$(\'.js-toggle-additional-image-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').on(\'click\', function() {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . 'var toggleAdditionalFieldsValue = parseInt($(\'.js-toggle-additional-image-fields-value-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val());' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . 'var showText = $(\'.js-toggle-additional-image-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').attr(\'data-show-text\');' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . 'var hideText = $(\'.js-toggle-additional-image-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').attr(\'data-hide-text\');' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . 'if (toggleAdditionalFieldsValue) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '$(\'.js-additional-image-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').hide();' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-image-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').removeClass(\'toggle-additional-image-fields-active\');' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-image-fields-value-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val(0);' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-image-fields-text-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').text(showText);' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '} else {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '$(\'.js-additional-image-fields-wrapper-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-image-fields-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').addClass(\'toggle-additional-image-fields-active\');' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-image-fields-value-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').val(1);' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '$(\'.js-toggle-additional-image-fields-text-' . $v['handle'] . '-<?php echo $uniqueID; ?>\').text(hideText);' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;

                        if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {
                            $code .= BlockBuilderUtility::tab(6) . '$(\'.js-toggle-override-dimensions-<?php echo $uniqueID; ?>\').on(\'change\', function() {' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . 'if ($(this).is(\':checked\')) {' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '$(\'.js-override-dimensions-wrapper-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '} else {' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '$(\'.js-override-dimensions-wrapper-<?php echo $uniqueID; ?>\').hide();' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '}' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                        }

                        if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {
                            $code .= BlockBuilderUtility::tab(6) . '$(\'.js-toggle-override-fullscreen-dimensions-<?php echo $uniqueID; ?>\').on(\'change\', function() {' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . 'if ($(this).is(\':checked\')) {' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '$(\'.js-override-fullscreen-dimensions-wrapper-<?php echo $uniqueID; ?>\').show();' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '} else {' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '$(\'.js-override-fullscreen-dimensions-wrapper-<?php echo $uniqueID; ?>\').hide();' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(7) . '}' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                        }

                        $code .= BlockBuilderUtility::tab(5) . '});' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</script>' . PHP_EOL . PHP_EOL;

                    }

                    $code .= BlockBuilderUtility::tab(3) . '</div><?php // .js-image-wrapper ?>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'express') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entity = \Express::getObjectByHandle(\'' . $v['expressHandle'] . '\');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entry = \Express::getEntry($' . $v['handle'] . ');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php if (isset($entity) && $entity instanceof Concrete\Core\Entity\Express\Entity): ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<?php echo $app->make(\'form/express/entry_selector\')->selectEntry($entity, \'' . $v['handle'] . '\', $entry); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php endif; ?>' . PHP_EOL;

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'file_set') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL ;

                    if (!empty($v['fileSetPrefix'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="input-group">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<span class="input-group-text">' . $v['fileSetPrefix'] . '</span>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . '_fileSets, $' . $v['handle'] . '); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL;
                    } else {
                        $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->select($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . '_fileSets, $' . $v['handle'] . '); ?>' . PHP_EOL;
                    }

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'html_editor') {

                    $height = !empty($v['htmlEditorHeight']) ? $v['htmlEditorHeight'] : 250;

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div id="' . $v['handle'] . '-html-editor-<?php echo $uniqueID; ?>" style="height: ' . $height . 'px; border: 1px solid #dedede;"><?php echo h($' . $v['handle'] . '); ?></div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->textarea($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . ', [\'style\'=>\'display: none;\']); ?>' . PHP_EOL . PHP_EOL;

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(4) . '<script>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '$(function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . 'var htmlEditor = ace.edit(\'' . $v['handle'] . '-html-editor-<?php echo $uniqueID; ?>\');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . 'htmlEditor.setTheme(\'ace/theme/eclipse\');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . 'htmlEditor.getSession().setMode(\'ace/mode/html\');' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . 'htmlEditor.getSession().on(\'change\', function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '$("#<?php echo str_replace([\'[\', \']\'], [\'\\\\\\\\[\', \'\\\\\\\\]\'], $view->field(\'' . $v['handle'] . '\')); ?>").val(htmlEditor.getValue());' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</script>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'date_picker') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $app->make(\'helper/form/date_time\')->date($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . '); ?>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'color_picker') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<?php echo $app->make(\'helper/form/color\')->output($view->field(\'' . $v['handle'] . '\'), $' . $v['handle'] . ', [\'showAlpha\' => true, \'showPalette\' => false, \'preferredFormat\' => \'hex\']); ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL;
                    if (!empty($v['helpText'])) { // echo
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'icon_picker') {

                    $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<?php echo $form->label($view->field(\'' . $v['handle'] . '\'), t(\'' . addslashes($v['label']) . '\')' . $required . '); ?>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(4) . '<div id="ccm-icon-selector-' . $v['handle'] . '-<?php echo h($uniqueID); ?>">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '<icon-selector name="' . $v['handle'] . '"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '               selected="<?php echo h($' . $v['handle'] . '); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '               title="<?php echo t(\'Choose Icon\') ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '               empty-option-label="<?php echo tc(\'Icon\', \'** None Selected\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '></icon-selector>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '<script type="text/javascript">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '$(function() {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . 'Concrete.Vue.activateContext(\'cms\', function(Vue, config) {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . 'new Vue({' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . 'el: \'#ccm-icon-selector-' . $v['handle'] . '-<?php echo h($uniqueID); ?>\',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . 'components: config.components,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '});' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '</script>' . PHP_EOL;

                    if (!empty($v['helpText'])) { // echo
                        $code .= BlockBuilderUtility::tab(4) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

                }

            }

        }

        if (!empty($postData['basic'])) {
            $code .= BlockBuilderUtility::tab(2) . '</div>' . PHP_EOL . PHP_EOL;
        }

        ///////////////////////////////////////////////////////////////////////////
        // Entries
        ///////////////////////////////////////////////////////////////////////////

        if (!empty($postData['entries'])) {
            $code .= BlockBuilderUtility::tab(2) . '<div class="js-tab-pane';
            if (!empty($postData['basic']) or !empty($postDataSummary['settingsTab'])) {
                $code .= ' tab-pane';
                if (!empty($postData['entriesAsFirstTab']) or (empty($postData['entriesAsFirstTab']) and empty($postData['basic']))) {
                    $code .= ' show active';
                }
                $code .= '" id="entries-tab-<?php echo $uniqueID; ?>';
            }
            $code .= '">' . PHP_EOL . PHP_EOL;
        }

        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(3) . '<div class="mb-3 entries-actions">' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '<button type="button" class="btn btn-primary entries-action-button js-add-entry" data-action="prepend"><?php echo t(\'' . addslashes($postData['addAtTheTopLabel']) . '\'); ?></button>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '<button type="button" class="btn btn-primary entries-action-button js-add-entry" data-action="append"><?php echo t(\'' . addslashes($postData['addAtTheBottomLabel']) . '\'); ?></button>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '<button type="button" class="btn btn-primary entries-action-button js-copy-last-entry" data-action="append"><?php echo t(\'' . addslashes($postData['copyLastEntryLabel']) . '\'); ?></button>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '<span class="entries-actions-links">' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '<a href="#" class="entries-action-link js-expand-all"><i class="far fa-plus-square"></i> <?php echo t(\'' . addslashes($postData['expandAllLabel']) . '\'); ?></a>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '<a href="#" class="entries-action-link js-collapse-all"><i class="far fa-minus-square"></i> <?php echo t(\'' . addslashes($postData['collapseAllLabel']) . '\'); ?></a>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '</span>' . PHP_EOL;
            if (!empty($postData['maxNumberOfEntries'])) {
                $code .= BlockBuilderUtility::tab(4) . '<span class="entries-actions-counter"><i class="fas fa-question-circle" title="<?php echo t(\'' . addslashes($postData['maxNumberOfEntriesLabel']) . '\'); ?>"></i> <span class="js-number-of-entries">0</span>/<span class="js-max-number-of-entries">' . $postData['maxNumberOfEntries'] . '</span></span>' . PHP_EOL;
            }
            $code .= BlockBuilderUtility::tab(4) . '<a href="#" class="entries-action-link entries-action-link-remove-all js-remove-all" data-confirm-text="<?php echo t(\'' . addslashes($postData['areYouSureLabel']) . '\'); ?>"  title="<?php echo t(\'' . addslashes($postData['removeAllLabel']) . '\'); ?>"><i class="fas fa-times-circle"></i></a>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '<div class="entries" id="entries-<?php echo $uniqueID; ?>" data-entries="<?php echo htmlspecialchars(json_encode($entries)); ?>" data-default-values="<?php echo htmlspecialchars(json_encode($defaultValues)); ?>" data-column-names="<?php echo h(json_encode($entryColumnNames)); ?>"></div>' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '<div class="mb-3 entries-actions">' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '<button type="button" class="btn btn-primary entries-action-button js-add-entry" data-action="prepend"><?php echo t(\'' . addslashes($postData['addAtTheTopLabel']) . '\'); ?></button>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '<button type="button" class="btn btn-primary entries-action-button js-add-entry" data-action="append"><?php echo t(\'' . addslashes($postData['addAtTheBottomLabel']) . '\'); ?></button>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '<button type="button" class="btn btn-primary entries-action-button js-copy-last-entry" data-action="append"><?php echo t(\'' . addslashes($postData['copyLastEntryLabel']) . '\'); ?></button>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '<span class="entries-actions-links">' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '<a href="#" class="entries-action-link js-expand-all"><i class="far fa-plus-square"></i> <?php echo t(\'' . addslashes($postData['expandAllLabel']) . '\'); ?></a>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '<a href="#" class="entries-action-link js-collapse-all"><i class="far fa-minus-square"></i> <?php echo t(\'' . addslashes($postData['collapseAllLabel']) . '\'); ?></a>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '</span>' . PHP_EOL;
            if (!empty($postData['maxNumberOfEntries'])) {
                $code .= BlockBuilderUtility::tab(4) . '<span class="entries-actions-counter"><i class="fas fa-question-circle" title="<?php echo t(\'' . addslashes($postData['maxNumberOfEntriesLabel']) . '\'); ?>"></i> <span class="js-number-of-entries">0</span>/<span>' . $postData['maxNumberOfEntries'] . '</span></span>' . PHP_EOL;
            }
            $code .= BlockBuilderUtility::tab(4) . '<a href="#" class="entries-action-link entries-action-link-remove-all js-remove-all" data-confirm-text="<?php echo t(\'' . addslashes($postData['areYouSureLabel']) . '\'); ?>"  title="<?php echo t(\'' . addslashes($postData['removeAllLabel']) . '\'); ?>"><i class="fas fa-times-circle"></i></a>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '<div class="mb-3">' . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '<div class="form-check-inline">' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '<input type="checkbox"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . 'name="<?php echo $view->field(\'disableSmoothScroll\'); ?>"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . 'class="form-check-input js-disable-smooth-scroll"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . 'value="1"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . 'id="<?php echo $view->field(\'disableSmoothScroll\'); ?>"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '/>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '<label for="<?php echo $view->field(\'disableSmoothScroll\'); ?>" class="form-check-label"><?php echo t(\'' . addslashes($postData['disableSmoothScrollLabel']) . '\'); ?></label>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL; // .form-check-inline

            $code .= BlockBuilderUtility::tab(4) . '<div class="form-check-inline">' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '<input type="checkbox"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . 'name="<?php echo $view->field(\'keepAddedEntryCollapsed\'); ?>"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . 'class="form-check-input js-keep-added-entry-collapsed"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . 'value="1"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . 'id="<?php echo $view->field(\'keepAddedEntryCollapsed\'); ?>"' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '/>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '<label for="<?php echo $view->field(\'keepAddedEntryCollapsed\'); ?>" class="form-check-label"><?php echo t(\'' . addslashes($postData['keepAddedEntryCollapsedLabel']) . '\'); ?></label>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL; // .form-check-inline

            $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL; // .mb-3
        }

        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(3) . '<script type="text/template" class="js-entry-template">' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '<div class="well entry js-entry" data-position="<%=_.escape(position)%>">' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(5) . '<div class="entry-header">' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '<button type="button" class="entry-header-action entry-header-duplicate-entry-and-add-at-the-end js-duplicate-entry-and-add-at-the-end" title="<?php echo t(\'' . addslashes($postData['duplicateEntryAndAddAtTheEndLabel']) . '\'); ?>"><i class="far fa-clone"></i></button>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '<button type="button" class="entry-header-action entry-header-duplicate-entry js-duplicate-entry" title="<?php echo t(\'' . addslashes($postData['duplicateEntryLabel']) . '\'); ?>"><i class="fas fa-clone"></i></button>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '<div class="entry-header-action entry-header-remove-entry js-remove-entry" data-confirm-text="<?php echo t(\'' . addslashes($postData['areYouSureLabel']) . '\'); ?>"  title="<?php echo t(\'' . addslashes($postData['removeEntryLabel']) . '\'); ?>"><i class="fas fa-times"></i></div>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '<div class="entry-header-action entry-header-move-entry js-move-entry"><i class="fas fa-arrows-alt"></i></div>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '<div class="entry-header-action entry-header-toggle-entry js-toggle-entry" data-action="collapse"><i class="far fa-minus-square"></i></div>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '<div class="entry-header-title">' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(7) . '<span class="js-entry-title">' . PHP_EOL;
            if ($postDataSummary['entryTitleSource']) {
                $code .= BlockBuilderUtility::tab(8) . '<% if (' . $postDataSummary['entryTitleSource'] . ') { %>' . PHP_EOL;
                $code .= BlockBuilderUtility::tab(9) . '<%=_.escape(' . $postDataSummary['entryTitleSource'] . ')%>' . PHP_EOL;
                $code .= BlockBuilderUtility::tab(8) . '<% } else { %>' . PHP_EOL;
                $code .= BlockBuilderUtility::tab(9) . '#<%=_.escape(position)%>' . PHP_EOL;
                $code .= BlockBuilderUtility::tab(8) . '<% } %>' . PHP_EOL;
            } else {
                $code .= BlockBuilderUtility::tab(8) . '#<%=_.escape(position)%>' . PHP_EOL;
            }
            $code .= BlockBuilderUtility::tab(7) . '</span>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(5) . '<div class="entry-content js-entry-content" <% if (keepAddedEntryCollapsed) { %>style="display: none;"<% } %>>' . PHP_EOL . PHP_EOL;


            $previousFieldTypeHadMultipleFields = false;
            $i = 0;

            foreach ($postData['entries'] as $k => $v) {

                $i++;

                $required = !empty($v['required']) ? ' *' : '';

                // Horizontal line (always)
                if ($postData['entryFieldsDivider'] == 'always' and $i != 1) {
                    $code .= BlockBuilderUtility::tab(6) . '<hr/>' . PHP_EOL . PHP_EOL;
                }

                // Horizontal line (smart)
                if ($postData['entryFieldsDivider'] == 'smart') {
                    if (
                        !empty($v['linkFromSitemapShowEndingField']) or
                        !empty($v['linkFromSitemapShowTextField']) or
                        !empty($v['linkFromSitemapShowTitleField']) or
                        !empty($v['linkFromSitemapShowNewWindowField']) or
                        !empty($v['linkFromSitemapShowNoFollowField']) or
                        !empty($v['linkFromFileManagerShowEndingField']) or
                        !empty($v['linkFromFileManagerShowTextField']) or
                        !empty($v['linkFromFileManagerShowTitleField']) or
                        !empty($v['linkFromFileManagerShowNewWindowField']) or
                        !empty($v['linkFromFileManagerShowNoFollowField']) or
                        !empty($v['externalLinkShowEndingField']) or
                        !empty($v['externalLinkShowTextField']) or
                        !empty($v['externalLinkShowTitleField']) or
                        !empty($v['externalLinkShowNewWindowField']) or
                        !empty($v['externalLinkShowNoFollowField']) or
                        ($v['fieldType'] == 'link') or
                        ($v['fieldType'] == 'image')
                    ) {
                        if ($i != 1) {
                            $code .= BlockBuilderUtility::tab(6) . '<hr/>' . PHP_EOL . PHP_EOL;
                        }
                        $previousFieldTypeHadMultipleFields = true;
                    } else {
                        if ($previousFieldTypeHadMultipleFields) {
                            $code .= BlockBuilderUtility::tab(6) . '<hr/>' . PHP_EOL . PHP_EOL;
                            $previousFieldTypeHadMultipleFields = false;
                        }
                    }
                }

                // Field types
                if ($v['fieldType'] == 'text_field') {

                    $jsEntryTitleSource = ($postDataSummary['entryTitleSource'] == $v['handle']) ? ' js-entry-title-source' : false;

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="<%=_.escape(' . $v['handle'] . ')%>" class="form-control' . $jsEntryTitleSource . '" maxlength="255" />' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'number') {

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<input type="number" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="<%=_.escape(' . $v['handle'] . ')%>" class="form-control" min="' . $v['numberMin'] . '"  max="' . $v['numberMax'] . '"  step="' . $v['numberStep'] . '" />' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'textarea') {

                    $jsEntryTitleSource = ($postDataSummary['entryTitleSource'] == $v['handle']) ? ' js-entry-title-source' : false;

                    $height = !empty($v['textareaHeight']) ? ' style="height: ' . $v['textareaHeight'] . 'px;"' : false;

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<textarea id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-control' . $jsEntryTitleSource . '"' . $height . '><%=_.escape(' . $v['handle'] . ')%></textarea>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'wysiwyg_editor') {

                    $height = !empty($v['wysiwygEditorHeight']) ? $v['wysiwygEditorHeight'] : false;

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4 js-custom-editor-height-<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']-<?php echo $uniqueID; ?>">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<textarea data-activation-script="' . $v['handle'] . '" style="display: none;" class="js-editor-content" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"><%=_.escape(' . $v['handle'] . ')%></textarea>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    if ($height) {
                        $code .= BlockBuilderUtility::tab(7) . '<style>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '.js-custom-editor-height-<?php echo str_replace([\'[\', \']\'], [\'\[\', \'\]\'], $view->field(\'entry\').\'[<%=_.escape(position)%>][' . $v['handle'] . ']\'); ?>-<?php echo $uniqueID; ?> .cke_contents {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . 'height: ' . $height . 'px !important;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '.js-custom-editor-height-<?php echo str_replace([\'[\', \']\'], [\'\[\', \'\]\'], $view->field(\'entry\').\'[<%=_.escape(position)%>][' . $v['handle'] . ']\'); ?>-<?php echo $uniqueID; ?> .cke_editable {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . 'min-height: ' . $height . 'px !important;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</style>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'select_field') {

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;

                    if (empty($v['selectType']) or $v['selectType'] === 'default_select') {
                        $code .= BlockBuilderUtility::tab(7) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php foreach ($entry_' . $v['handle'] . '_options as $k => $v): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . '==\'<?php echo $k; ?>\') { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php endforeach; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</select>' . PHP_EOL;
                    } elseif ($v['selectType'] === 'enhanced_select') {
                        $code .= BlockBuilderUtility::tab(7) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '        name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '        class="form-select form-control js-enhanced-select"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php foreach ($entry_' . $v['handle'] . '_options as $k => $v): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . '==\'<?php echo $k; ?>\') { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php endforeach; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</select>' . PHP_EOL;
                    } elseif ($v['selectType'] === 'radio_list') {
                        $code .= BlockBuilderUtility::tab(7) . '<?php $radioIndex = 0; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<input type="hidden" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php foreach ($entry_' . $v['handle'] . '_options as $k => $v): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php $radioIndex++; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<div class="form-check">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\' . $radioIndex); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-check-label"><?php echo h($v); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<input type="radio" id="<?php echo $view->field(\'entry\' . $radioIndex); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="<?php echo $k; ?>" class="form-check-input" <% if (' . $v['handle'] . '==\'<?php echo $k; ?>\') { %>checked<% } %>>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php endforeach; ?>' . PHP_EOL;
                    }
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'select_multiple_field') {

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;

                    if (empty($v['selectMultipleType']) or $v['selectMultipleType'] === 'default_multiselect') {
                        $code .= BlockBuilderUtility::tab(7) . '<% var ' . $v['handle'] . '_exploded_items = ' . $v['handle'] . ' ? ' . $v['handle'] . '.split(\'|\') : []; %>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<input type="hidden" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<select multiple id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '][]" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php foreach ($entry_' . $v['handle'] . '_options as $k => $v): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . ' != null && ' . $v['handle'] . '.includes(\'<?php echo $k; ?>\')) { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php endforeach; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</select>' . PHP_EOL;
                    } elseif ($v['selectMultipleType'] === 'enhanced_multiselect') {
                        $code .= BlockBuilderUtility::tab(7) . '<input type="hidden" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '        name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '][]"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '        class="form-select form-control js-enhanced-select"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '        multiple' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php foreach ($entry_' . $v['handle'] . '_options as $k => $v): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . ' != null && ' . $v['handle'] . '.includes(\'<?php echo $k; ?>\')) { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php endforeach; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</select>' . PHP_EOL;
                    } elseif ($v['selectMultipleType'] === 'checkbox_list') {
                        $code .= BlockBuilderUtility::tab(7) . '<?php $checkboxIndex = 0; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<input type="hidden" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php foreach ($entry_' . $v['handle'] . '_options as $k => $v): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php $checkboxIndex++; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<div class="form-check">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\' . $checkboxIndex); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-check-label"><?php echo h($v); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<input type="checkbox" id="<?php echo $view->field(\'entry\' . $checkboxIndex); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '][]" value="<?php echo $k; ?>" class="form-check-input" <% if (' . $v['handle'] . ' != null && ' . $v['handle'] . '.split(\'|\').includes(\'<?php echo $k; ?>\')) { %>checked<% } %>>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php endforeach; ?>' . PHP_EOL;
                    }
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link') {

                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields']) {
                        $fieldGroupHighlight = ' field-group-highlight';
                    }

                    $code .= BlockBuilderUtility::tab(6) . '<div class="field-group' . $fieldGroupHighlight . ' mb-4 js-link-wrapper">' . PHP_EOL . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<div class="row margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_link_type]" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 col-lg-6 margin-bottom-on-mobile">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_link_type]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_link_type]" class="form-select js-link-type">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<?php foreach ($linkTypes as $k => $v): ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . '_link_type==\'<?php echo $k; ?>\') { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<?php endforeach; ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '</select>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(9) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 col-lg-6">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<span class="toggle-additional-fields <% if (parseInt(' . $v['handle'] . '_show_additional_fields)) { %>toggle-additional-fields-active<% } %> btn btn-secondary js-toggle-additional-fields"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'data-show-text="<?php echo t(\'' . addslashes($postData['showAdditionalFieldsLabel']) . '\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'data-hide-text="<?php echo t(\'' . addslashes($postData['hideAdditionalFieldsLabel']) . '\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<% if (!' . $v['handle'] . '_link_type) { %>style="display: none;"<% } %>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '><i class="fas fa-caret-right"></i> <span class="js-toggle-additional-fields-text"><% if (parseInt(' . $v['handle'] . '_show_additional_fields)) { %><?php echo t(\'' . addslashes($postData['hideAdditionalFieldsLabel']) . '\'); ?><% } else { %><?php echo t(\'' . addslashes($postData['showAdditionalFieldsLabel']) . '\'); ?><% } %></span></span>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<input type="hidden"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'class="js-toggle-additional-fields-value"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_show_additional_fields]"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'value="<%=_.escape(' . $v['handle'] . '_show_additional_fields)%>">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="row margin-bottom js-link-type-wrapper js-link-type-wrapper-link_from_sitemap" <% if (' . $v['handle'] . '_link_type!=\'link_from_sitemap\') { %>style="display: none;"<% } %>>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<div data-concrete-page-input="js-page-selector">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<concrete-page-input :page-id="<%= ' . $v['handle'] . '_link_from_sitemap ? _.escape(' . $v['handle'] . '_link_from_sitemap) : false %>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . 'input-name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_link_from_sitemap]"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . 'choose-text="<?php echo t(\'Choose Page\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . ':include-system-pages="false"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . ':ask-include-system-pages="false"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '></concrete-page-input>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="row margin-bottom js-link-type-wrapper js-link-type-wrapper-link_from_file_manager" <% if (' . $v['handle'] . '_link_type!=\'link_from_file_manager\') { %>style="display: none;"<% } %>>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<div data-concrete-file-input="js-file-selector">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<concrete-file-input :file-id="<%= ' . $v['handle'] . '_link_from_file_manager ? _.escape(' . $v['handle'] . '_link_from_file_manager) : \'0\' %>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . 'choose-text="<?php echo t(\'Choose File\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . 'input-name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_link_from_file_manager]"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '></concrete-file-input>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="row margin-bottom js-link-type-wrapper js-link-type-wrapper-external_link" <% if (' . $v['handle'] . '_link_type!=\'external_link\') { %>style="display: none;"<% } %>>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 col-lg-3 margin-bottom-on-mobile">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_protocol]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_protocol]" class="form-select js-external-link-protocol">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<?php foreach ($externalLinkProtocols as $k => $v): ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . '_protocol==\'<?php echo $k; ?>\') { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<?php endforeach; ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '</select>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 col-lg-9">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_external_link]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_external_link]" value="<%=_.escape(' . $v['handle'] . '_external_link)%>" class="form-control js-external-link-url" maxlength="255" />' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="row js-additional-fields-wrapper" <% if (!' . $v['handle'] . '_link_type || !parseInt(' . $v['handle'] . '_show_additional_fields)) { %>style="display: none;"<% } %>>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" class="form-label"><?php echo t(\'' . addslashes($postData['urlEndingLabel']) . '\'); ?></label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" value="<%=_.escape(' . $v['handle'] . '_ending)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<div class="form-text"><?php echo t(\'' . addslashes($postData['urlEndingHelpText']) . '\'); ?></div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" class="form-label"><?php echo t(\'' . addslashes($postData['textLabel']) . '\'); ?></label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<textarea id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" class="form-control" maxlength="255"><%=_.escape(' . $v['handle'] . '_text)%></textarea>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" class="form-label"><?php echo t(\'' . addslashes($postData['titleLabel']) . '\'); ?></label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" value="<%=_.escape(' . $v['handle'] . '_title)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" class="form-label"><?php echo t(\'' . addslashes($postData['newWindowLabel']) . '\'); ?></label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" class="form-select">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<option value="0" <% if (!' . $v['handle'] . '_new_window) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<option value="1" <% if (' . $v['handle'] . '_new_window==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '</select>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 margin-bottom">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" class="form-label"><?php echo t(\'' . addslashes($postData['noFollowLabel']) . '\'); ?></label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" class="form-select">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<option value="0" <% if (!' . $v['handle'] . '_no_follow) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<option value="1" <% if (' . $v['handle'] . '_no_follow==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '</select>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '</div><?php // .js-link-wrapper ?>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link_from_sitemap') {

                    $hasMultipleElements =
                        !empty($v['linkFromSitemapShowEndingField']) ||
                        !empty($v['linkFromSitemapShowTextField']) ||
                        !empty($v['linkFromSitemapShowTitleField']) ||
                        !empty($v['linkFromSitemapShowNoFollowField']) ||
                        !empty($v['linkFromSitemapShowNewWindowField']);
                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields'] && $hasMultipleElements) {
                        $fieldGroupHighlight = ' field-group-highlight mb-4';
                    }

                    $code .= BlockBuilderUtility::tab(6) . '<div class="field-group' . $fieldGroupHighlight . '">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<div data-concrete-page-input="js-page-selector">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<concrete-page-input :page-id="<%= ' . $v['handle'] . ' ? _.escape(' . $v['handle'] . ') : false %>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'input-name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'choose-text="<?php echo t(\'Choose Page\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . ':include-system-pages="false"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . ':ask-include-system-pages="false"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '></concrete-page-input>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(8) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;

                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" class="form-label"><?php echo t(\'' . addslashes($postData['urlEndingLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" value="<%=_.escape(' . $v['handle'] . '_ending)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<div class="form-text"><?php echo t(\'' . addslashes($postData['urlEndingHelpText']) . '\'); ?></div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" class="form-label"><?php echo t(\'' . addslashes($postData['textLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<textarea id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" class="form-control" maxlength="255"><%=_.escape(' . $v['handle'] . '_text)%></textarea>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" class="form-label"><?php echo t(\'' . addslashes($postData['titleLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" value="<%=_.escape(' . $v['handle'] . '_title)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromSitemapShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" class="form-label"><?php echo t(\'' . addslashes($postData['newWindowLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="0" <% if (!' . $v['handle'] . '_new_window) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="1" <% if (' . $v['handle'] . '_new_window==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</select>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromSitemapShowNoFollowField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" class="form-label"><?php echo t(\'' . addslashes($postData['noFollowLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="0" <% if (!' . $v['handle'] . '_no_follow) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="1" <% if (' . $v['handle'] . '_no_follow==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</select>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link_from_file_manager') {

                    $hasMultipleElements =
                        !empty($v['linkFromFileManagerShowEndingField']) ||
                        !empty($v['linkFromFileManagerShowTextField']) ||
                        !empty($v['linkFromFileManagerShowTitleField']) ||
                        !empty($v['linkFromFileManagerShowNoFollowField']) ||
                        !empty($v['linkFromFileManagerShowNewWindowField']);
                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields'] && $hasMultipleElements) {
                        $fieldGroupHighlight = ' field-group-highlight mb-4';
                    }

                    $code .= BlockBuilderUtility::tab(6) . '<div class="field-group' . $fieldGroupHighlight . '">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(8) . '<div data-concrete-file-input="js-file-selector">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<concrete-file-input :file-id="<%= ' . $v['handle'] . ' ? _.escape(' . $v['handle'] . ') : \'0\' %>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'choose-text="<?php echo t(\'Choose File\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . 'input-name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '></concrete-file-input>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(8) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;

                    if (!empty($v['linkFromFileManagerShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" class="form-label"><?php echo t(\'' . addslashes($postData['urlEndingLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" value="<%=_.escape(' . $v['handle'] . '_ending)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<div class="form-text"><?php echo t(\'' . addslashes($postData['urlEndingHelpText']) . '\'); ?></div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" class="form-label"><?php echo t(\'' . addslashes($postData['textLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<textarea id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" class="form-control" maxlength="255"><%=_.escape(' . $v['handle'] . '_text)%></textarea>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" class="form-label"><?php echo t(\'' . addslashes($postData['titleLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" value="<%=_.escape(' . $v['handle'] . '_title)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" class="form-label"><?php echo t(\'' . addslashes($postData['newWindowLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="0" <% if (!' . $v['handle'] . '_new_window) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="1" <% if (' . $v['handle'] . '_new_window==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</select>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['linkFromFileManagerShowNoFollowField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" class="form-label"><?php echo t(\'' . addslashes($postData['noFollowLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="0" <% if (!' . $v['handle'] . '_no_follow) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="1" <% if (' . $v['handle'] . '_no_follow==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</select>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'express') {

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div data-concrete-express-entry-input="js-express-entry-selector">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<concrete-express-entry-input' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . 'entity-id="<?php echo h($expressEntity[\'' . $v['handle'] . '\'][\'entity_id\']); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . 'entry-id="<%= ' . $v['handle'] . ' ? _.escape(' . $v['handle'] . ') : \'0\' %>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . 'input-name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . 'choose-text="<?php echo t(\'Choose Entry\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '></concrete-express-entry-input>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'file_set') {

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;

                    if (!empty($v['fileSetPrefix'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="input-group">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<span class="input-group-text">' . $v['fileSetPrefix'] . '</span>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<?php foreach ($entry_' . $v['handle'] . '_fileSets as $k => $v): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(10) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . '==\'<?php echo $k; ?>\') { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<?php endforeach; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</select>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                    } else {
                        $code .= BlockBuilderUtility::tab(7) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php foreach ($entry_' . $v['handle'] . '_fileSets as $k => $v): ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . '==\'<?php echo $k; ?>\') { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php endforeach; ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</select>' . PHP_EOL;
                    }

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'external_link') {

                    $hasMultipleElements =
                        !empty($v['externalLinkShowEndingField']) ||
                        !empty($v['externalLinkShowTextField']) ||
                        !empty($v['externalLinkShowTitleField']) ||
                        !empty($v['externalLinkShowNoFollowField']) ||
                        !empty($v['externalLinkShowNewWindowField']);
                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields'] && $hasMultipleElements) {
                        $fieldGroupHighlight = ' field-group-highlight mb-4';
                    }

                    $code .= BlockBuilderUtility::tab(6) . '<div class="field-group' . $fieldGroupHighlight . '">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(8) . '<div class="row">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<div class="col-12 col-lg-3 margin-bottom-on-mobile">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_protocol]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_protocol]" class="form-select js-external-link-protocol">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . '<?php foreach ($externalLinkProtocols as $k => $v): ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(12) . '<option value="<?php echo $k; ?>" <% if (' . $v['handle'] . '_protocol==\'<?php echo $k; ?>\') { %>selected="selected"<% } %> ><?php echo h($v); ?></option>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . '<?php endforeach; ?>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '</select>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<div class="col-12 col-lg-9">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="<%=_.escape(' . $v['handle'] . ')%>" class="form-control js-external-link-url" maxlength="255" />' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(10) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(9) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;

                    if (!empty($v['externalLinkShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" class="form-label"><?php echo t(\'' . addslashes($postData['urlEndingLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_ending]" value="<%=_.escape(' . $v['handle'] . '_ending)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<div class="form-text"><?php echo t(\'' . addslashes($postData['urlEndingHelpText']) . '\'); ?></div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" class="form-label"><?php echo t(\'' . addslashes($postData['textLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<textarea id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_text]" class="form-control" maxlength="255"><%=_.escape(' . $v['handle'] . '_text)%></textarea>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" class="form-label"><?php echo t(\'' . addslashes($postData['titleLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_title]" value="<%=_.escape(' . $v['handle'] . '_title)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['externalLinkShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" class="form-label"><?php echo t(\'' . addslashes($postData['newWindowLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_new_window]" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="0" <% if (!' . $v['handle'] . '_new_window) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="1" <% if (' . $v['handle'] . '_new_window==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</select>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    if (!empty($v['externalLinkShowNoFollowField'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" class="form-label"><?php echo t(\'' . addslashes($postData['noFollowLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_no_follow]" class="form-select">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="0" <% if (!' . $v['handle'] . '_no_follow) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<option value="1" <% if (' . $v['handle'] . '_no_follow==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</select>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'image') {

                    $hasMultipleElements =
                        !empty($v['imageShowAltTextField']) ||
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) ||
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']));
                    $fieldGroupHighlight = '';
                    if ($postData['highlightMultiElementFields'] && $hasMultipleElements) {
                        $fieldGroupHighlight = ' field-group-highlight';
                    }

                    $code .= BlockBuilderUtility::tab(6) . '<div class="field-group' . $fieldGroupHighlight . ' mb-4 js-image-wrapper">' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="row margin-bottom">' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 col-lg-' . ((!empty($v['imageShowAltTextField']) or (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) or (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))) ? 6 : 12) . ' margin-bottom-on-mobile">' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(9) . '<div data-concrete-file-input="js-file-selector">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '<concrete-file-input :file-id="<%= ' . $v['handle'] . ' ? _.escape(' . $v['handle'] . ') : \'0\' %>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . 'choose-text="<?php echo t(\'Choose File\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(11) . 'input-name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(10) . '></concrete-file-input>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(9) . '</div>' . PHP_EOL;

                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(9) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;

                    if (!empty($v['imageShowAltTextField']) or (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) or (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))) {
                        $code .= BlockBuilderUtility::tab(8) . '<div class="col-12 col-lg-6">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<span class="toggle-additional-image-fields <% if (parseInt(' . $v['handle'] . '_show_additional_fields)) { %>toggle-additional-image-fields-active<% } %> btn btn-secondary js-toggle-additional-image-fields"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(10) . 'data-show-text="<?php echo t(\'' . addslashes($postData['showAdditionalFieldsLabel']) . '\'); ?>"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(10) . 'data-hide-text="<?php echo t(\'' . addslashes($postData['hideAdditionalFieldsLabel']) . '\'); ?>"' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '><i class="fas fa-caret-right"></i> <span class="js-toggle-additional-image-fields-text"><% if (parseInt(' . $v['handle'] . '_show_additional_fields)) { %><?php echo t(\'' . addslashes($postData['hideAdditionalFieldsLabel']) . '\'); ?><% } else { %><?php echo t(\'' . addslashes($postData['showAdditionalFieldsLabel']) . '\'); ?><% } %></span></span>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(9) . '<input type="hidden" class="js-toggle-additional-image-fields-value" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_show_additional_fields]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_show_additional_fields]" value="<%=_.escape(' . $v['handle'] . '_show_additional_fields)%>" maxlength="255" />' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL;
                    }

                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL; // row margin-bottom

                    if (!empty($v['imageShowAltTextField']) or (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) or (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))) {

                        $code .= BlockBuilderUtility::tab(7) . '<div class="js-additional-image-fields-wrapper" <% if (!parseInt(' . $v['handle'] . '_show_additional_fields)) { %>style="display: none;"<% } %>>' . PHP_EOL . PHP_EOL;

                        if (!empty($v['imageShowAltTextField'])) {
                            $code .= BlockBuilderUtility::tab(8) . '<div class="mb-4">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_alt]" class="form-label"><?php echo t(\'' . addslashes($postData['altTextLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_alt]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_alt]" value="<%=_.escape(' . $v['handle'] . '_alt)%>" class="form-control" maxlength="255" />' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL . PHP_EOL;
                        }

                        if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {
                            $code .= BlockBuilderUtility::tab(8) . '<div class="row margin-bottom">' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(9) . '<div class="col-12">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '<label for="" class="form-label"><?php echo t(\'' . addslashes($postData['overrideThumbnailDimensionsLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '<div class="form-check">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<input type="checkbox" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_override_dimensions]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_override_dimensions]" class="js-toggle-override-image-dimensions form-check-input" value="<%=parseInt(' . $v['handle'] . '_override_dimensions)%>" <% if (parseInt(' . $v['handle'] . '_override_dimensions)) { %>checked<% } %> />' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_override_dimensions]" class="form-check-label"><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(9) . '<div class="row mt-3 js-override-image-dimensions-wrapper" <% if (!parseInt(' . $v['handle'] . '_override_dimensions)) { %>style="display: none;"<% } %>>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(10) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_width]" class="form-label"><?php echo t(\'' . addslashes($postData['widthLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<div class="input-group">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<input type="number" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_width]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_width]" value="<% if (parseInt(' . $v['handle'] . '_custom_width)) { %><%=_.escape(' . $v['handle'] . '_custom_width)%><% } else { %><% } %>" class="form-control ccm-input-number" />' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(10) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_height]" class="form-label"><?php echo t(\'' . addslashes($postData['heightLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<div class="input-group">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<input type="number" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_height]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_height]" value="<% if (parseInt(' . $v['handle'] . '_custom_height)) { %><%=_.escape(' . $v['handle'] . '_custom_height)%><% } else { %><% } %>" class="form-control ccm-input-number" />' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(10) . '<div class="col-12 col-lg-4">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_crop]" class="form-label"><?php echo t(\'' . addslashes($postData['cropLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_crop]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_crop]" class="form-select">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<option value="0" <% if (!parseInt(' . $v['handle'] . '_custom_crop)) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<option value="1" <% if (parseInt(' . $v['handle'] . '_custom_crop)==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '</select>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(9) . '</div>' . PHP_EOL; // .js-override-image-dimensions-wrapper

                            $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL . PHP_EOL; // row margin-bottom
                        }

                        if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {
                            $code .= BlockBuilderUtility::tab(8) . '<div class="row margin-bottom">' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(9) . '<div class="col-12">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '<label for="" class="form-label"><?php echo t(\'' . addslashes($postData['overrideFullscreenImageDimensionsLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '<div class="form-check">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<input type="checkbox" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_override_fullscreen_dimensions]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_override_fullscreen_dimensions]" class="form-check-input js-toggle-override-fullscreen-image-dimensions" value="<%=parseInt(' . $v['handle'] . '_override_fullscreen_dimensions)%>" <% if (parseInt(' . $v['handle'] . '_override_fullscreen_dimensions)) { %>checked<% } %> />' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_override_fullscreen_dimensions]" class="form-check-label"><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(9) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(9) . '<div class="row mt-3 js-override-fullscreen-image-dimensions-wrapper" <% if (!parseInt(' . $v['handle'] . '_override_fullscreen_dimensions)) { %>style="display: none;"<% } %>>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(10) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_width]" class="form-label"><?php echo t(\'' . addslashes($postData['widthLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<div class="input-group">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<input type="number" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_width]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_width]" value="<% if (parseInt(' . $v['handle'] . '_custom_fullscreen_width)) { %><%=_.escape(' . $v['handle'] . '_custom_fullscreen_width)%><% } else { %><% } %>" class="form-control ccm-input-number" />' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(10) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_height]" class="form-label"><?php echo t(\'' . addslashes($postData['heightLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<div class="input-group">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<input type="number" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_height]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_height]" value="<% if (parseInt(' . $v['handle'] . '_custom_fullscreen_height)) { %><%=_.escape(' . $v['handle'] . '_custom_fullscreen_height)%><% } else { %><% } %>" class="form-control ccm-input-number" />' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '</div>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(10) . '<div class="col-12 col-lg-4">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_crop]" class="form-label"><?php echo t(\'' . addslashes($postData['cropLabel']) . '\'); ?></label>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '<select id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_crop]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_custom_fullscreen_crop]" class="form-select">' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<option value="0" <% if (!parseInt(' . $v['handle'] . '_custom_fullscreen_crop)) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['noLabel']) . '\'); ?></option>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(12) . '<option value="1" <% if (parseInt(' . $v['handle'] . '_custom_fullscreen_crop)==1) { %>selected="selected"<% } %>><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></option>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(11) . '</select>' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(10) . '</div>' . PHP_EOL;

                            $code .= BlockBuilderUtility::tab(9) . '</div>' . PHP_EOL; // .js-override-fullscreen-image-dimensions-wrapper

                            $code .= BlockBuilderUtility::tab(8) . '</div>' . PHP_EOL . PHP_EOL; // row margin-bottom
                        }

                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL . PHP_EOL; // .js-additional-image-fields-wrapper

                    }

                    $code .= BlockBuilderUtility::tab(6) . '</div><?php // .js-image-wrapper ?>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'html_editor') {

                    $height = !empty($v['htmlEditorHeight']) ? $v['htmlEditorHeight'] : 250;

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<div id="entry-<%=_.escape(position)%>-' . $v['handle'] . '-html-editor-<?php echo $uniqueID; ?>" data-textarea-id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="js-html-editor" style="height: ' . $height . 'px; border: 1px solid #dedede;"><%=_.escape(' . $v['handle'] . ')%></div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<textarea id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-control" style="display: none;"><%=_.escape(' . $v['handle'] . ')%></textarea>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'date_picker') {

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(7) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" value="<%=_.escape(' . $v['handle'] . ')%>" class="form-control js-entry-' . $v['handle'] . '-<%=_.escape(position)%>" style="display: none;"/>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<input type="text" id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_displayed]" name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . '_displayed]" value="<%=_.escape(' . $v['handle'] . '_displayed)%>" class="form-control js-entry-date-displayed" data-date-format="<?php echo $app->make(\'helper/date\')->getJQueryUIDatePickerFormat(\'' . addslashes($v['datePickerPattern']) . '\'); ?>" data-target-field="' . $v['handle'] . '" data-position="<%=_.escape(position)%>" />' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(8) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'color_picker') {

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<div>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<input type="text"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       id="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       value="<%=_.escape(' . $v['handle'] . ')%>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       class="js-color-picker"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       data-cancel-text="<?php echo t(\'Cancel\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       data-choose-text="<?php echo t(\'Choose\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       data-toggle-palette-more-text="<?php echo t(\'more\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       data-toggle-palette-less-text="<?php echo t(\'less\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       data-no-color-selected-text="<?php echo t(\'No Color Selected\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '       data-clear-text="<?php echo t(\'Clear Color Selection\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '/>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'icon_picker') {

                    $code .= BlockBuilderUtility::tab(6) . '<div class="mb-4">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']" class="form-label"><?php echo t(\'' . addslashes($v['label']) . '\'); ?>' . $required . '</label>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '<div class="js-icon-picker">' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '<icon-selector name="<?php echo $view->field(\'entry\'); ?>[<%=_.escape(position)%>][' . $v['handle'] . ']"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '               selected="<%=_.escape(' . $v['handle'] . ')%>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '               title="<?php echo t(\'Choose Icon\') ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '               empty-option-label="<?php echo tc(\'Icon\', \'** None Selected\'); ?>"' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(8) . '></icon-selector>' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                    if (!empty($v['helpText'])) {
                        $code .= BlockBuilderUtility::tab(7) . '<div class="form-text"><?php echo t(\'' . addslashes($v['helpText']) . '\'); ?></div>' . PHP_EOL;
                    }
                    $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL . PHP_EOL;

                }

            }

            $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '</script>' . PHP_EOL . PHP_EOL;

        }

        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(3) . '<script type="text/template" class="js-template-no-entries">' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '<div class="alert alert-info js-alert"><?php echo t(\'' . addslashes($postData['noEntriesFoundLabel']) . '\'); ?></div>' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '</script>' . PHP_EOL . PHP_EOL;

        }

        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(3) . '<script>' . PHP_EOL . PHP_EOL;

            if ($postDataSummary['wysiwygEditorUsed_entry']) {
                $code .= BlockBuilderUtility::tab(4) . 'var CCM_EDITOR_SECURITY_TOKEN = \'<?php echo $app->make(\'helper/validation/token\')->generate(\'editor\'); ?>\';' . PHP_EOL;
                $code .= BlockBuilderUtility::tab(4) . 'var activateEditor = {' . PHP_EOL;
                foreach ($postData['entries'] as $k => $v) {
                    $customConfig = !empty($v['wysiwygCustomConfig']) ? $v['wysiwygCustomConfig'] : '{}';
                    $code .= BlockBuilderUtility::tab(5) . $v['handle'] . ': <?php echo $app->make(\'editor\')->getEditorInitJSFunction(json_decode(\'' . $customConfig . '\', true)); ?>,' . PHP_EOL;
                }
                $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL . PHP_EOL;
            }

            $code .= BlockBuilderUtility::tab(4) . '$(function () {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(5) . 'Concrete.event.publish(\'open.block.' . $postDataSummary['blockHandleDashed'] . '\', {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '\'uniqueID\' : \'<?php echo $uniqueID; ?>\'' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '});' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '});' . PHP_EOL . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '</script>' . PHP_EOL . PHP_EOL;

        }

        if (!empty($postData['entries'])) {
            $code .= BlockBuilderUtility::tab(2) . '</div>' . PHP_EOL . PHP_EOL;
        }

        ///////////////////////////////////////////////////////////////////////////
        /// Settings tab
        ///////////////////////////////////////////////////////////////////////////

        if (!empty($postDataSummary['settingsTab'])) {

            $code .= BlockBuilderUtility::tab(2) . '<div class="js-tab-pane tab-pane" id="settings-tab-<?php echo $uniqueID; ?>">' . PHP_EOL . PHP_EOL;

            foreach ($postData['entries'] as $k => $v) {

                if ($v['fieldType'] == 'image') {

                    if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {

                        $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4 js-image-settings-wrapper">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '<div class="row margin-bottom">' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(5) . '<div class="col-12">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label(\'\', \'' . $v['label'] . '\' . \' - \' . t(\'' . addslashes($postData['overrideThumbnailDimensionsLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<div class="form-check">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->checkbox($view->field(\'settings[' . $v['handle'] . '_override_dimensions]\'), \'1\', $settings[\'' . $v['handle'] . '_override_dimensions\'] ?? null, [\'class\' => \'form-check-input js-toggle-override-all-dimensions\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'settings[' . $v['handle'] . '_override_dimensions]\'); ?>" class="form-check-label"><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(5) . '<div class="row mt-3 js-override-all-dimensions-wrapper" <?php if (empty($settings[\'' . $v['handle'] . '_override_dimensions\'])): ?>style="display: none;"<?php endif; ?>>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(6) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->label($view->field(\'settings[' . $v['handle'] . '_custom_width]\'), t(\'' . addslashes($postData['widthLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<div class="input-group">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->number($view->field(\'settings[' . $v['handle'] . '_custom_width]\'), !empty($settings[\'' . $v['handle'] . '_custom_width\']) ? $settings[\'' . $v['handle'] . '_custom_width\'] : \'\'); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(6) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->label($view->field(\'settings[' . $v['handle'] . '_custom_height]\'), t(\'' . addslashes($postData['heightLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<div class="input-group">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->number($view->field(\'settings[' . $v['handle'] . '_custom_height]\'), !empty($settings[\'' . $v['handle'] . '_custom_height\']) ? $settings[\'' . $v['handle'] . '_custom_height\'] : \'\'); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(6) . '<div class="col-12 col-lg-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->label($view->field(\'settings[' . $v['handle'] . '_custom_crop]\'), t(\'' . addslashes($postData['cropLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->select($view->field(\'settings[' . $v['handle'] . '_custom_crop]\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $settings[\'' . $v['handle'] . '_custom_crop\'] ?? null); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL; // .row margin-bottom
                        $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL; // .js-image-settings-wrapper
                    }

                    if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {

                        $code .= BlockBuilderUtility::tab(3) . '<div class="mb-4 js-fullscreen-image-settings-wrapper">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '<div class="row margin-bottom">' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(5) . '<div class="col-12">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<?php echo $form->label(\'\', \'' . $v['label'] . '\' . \' - \' . t(\'' . addslashes($postData['overrideFullscreenImageDimensionsLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '<div class="form-check">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->checkbox($view->field(\'settings[' . $v['handle'] . '_override_fullscreen_dimensions]\'), \'1\', $settings[\'' . $v['handle'] . '_override_fullscreen_dimensions\'] ?? null, [\'class\' => \'form-check-input js-toggle-override-all-fullscreen-dimensions\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<label for="<?php echo $view->field(\'settings[' . $v['handle'] . '_override_fullscreen_dimensions]\'); ?>" class="form-check-label"><?php echo t(\'' . addslashes($postData['yesLabel']) . '\'); ?></label>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(5) . '<div class="row mt-3 js-override-all-fullscreen-dimensions-wrapper" <?php if (empty($settings[\'' . $v['handle'] . '_override_fullscreen_dimensions\'])): ?>style="display: none;"<?php endif; ?>>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(6) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->label($view->field(\'settings[' . $v['handle'] . '_custom_fullscreen_width]\'), t(\'' . addslashes($postData['widthLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<div class="input-group">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->number($view->field(\'settings[' . $v['handle'] . '_custom_fullscreen_width]\'), !empty($settings[\'' . $v['handle'] . '_custom_fullscreen_width\']) ? $settings[\'' . $v['handle'] . '_custom_fullscreen_width\'] : \'\'); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(6) . '<div class="col-12 col-lg-4 margin-bottom-on-mobile">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->label($view->field(\'settings[' . $v['handle'] . '_custom_fullscreen_height]\'), t(\'' . addslashes($postData['heightLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<div class="input-group">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<?php echo $form->number($view->field(\'settings[' . $v['handle'] . '_custom_fullscreen_height]\'), !empty($settings[\'' . $v['handle'] . '_custom_fullscreen_height\']) ? $settings[\'' . $v['handle'] . '_custom_fullscreen_height\'] : \'\'); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(8) . '<span class="input-group-text"><?php echo t(\'' . addslashes($postData['pxLabel']) . '\'); ?></span>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '</div>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(6) . '<div class="col-12 col-lg-4">' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->label($view->field(\'settings[' . $v['handle'] . '_custom_fullscreen_crop]\'), t(\'' . addslashes($postData['cropLabel']) . '\')); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(7) . '<?php echo $form->select($view->field(\'settings[' . $v['handle'] . '_custom_fullscreen_crop]\'), [\'0\'=>t(\'' . addslashes($postData['noLabel']) . '\'), \'1\'=>t(\'' . addslashes($postData['yesLabel']) . '\')], $settings[\'' . $v['handle'] . '_custom_fullscreen_crop\'] ?? null); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(6) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(5) . '</div>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(4) . '</div>' . PHP_EOL; // .row margin-bottom
                        $code .= BlockBuilderUtility::tab(3) . '</div>' . PHP_EOL . PHP_EOL; // .js-fullscreen-image-settings-wrapper
                    }

                }

            }

            $code .= BlockBuilderUtility::tab(2) . '</div>' . PHP_EOL . PHP_EOL; // .js-tab-pane

        }

        ///////////////////////////////////////////////////////////////////////////
        /// Footer
        ///////////////////////////////////////////////////////////////////////////

        if (!empty($postData['requiredFieldsLabel']) and (!empty($postDataSummary['requiredFields']) or !empty($postDataSummary['requiredEntryFields']))) {
            $code .= BlockBuilderUtility::tab(2) . '<hr/>' . PHP_EOL . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '<div class="form-text">* <?php echo t(\'' . addslashes($postData['requiredFieldsLabel']) . '\'); ?></div>' . PHP_EOL . PHP_EOL;
        }


        $code .= BlockBuilderUtility::tab(1) . '</div>' . PHP_EOL . PHP_EOL; // .tab-content
        $code .= '</div>'; // #form-container-x


        $fileService = new FileService();
        $fileService->append($postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename, $code);

    }

}
