<?php namespace BlockBuilder\FileGenerator;

use Concrete\Core\File\Service\File as FileService;
use BlockBuilder\Utility as BlockBuilderUtility;

defined('C5_EXECUTE') or die('Access Denied.');

class ControllerPhp
{

    public function generate($postDataSummary, $postData)
    {

        $filename = 'controller.php';
        $code = '';

        // 1. Top
        $code .= '<?php namespace Application\Block\\' . $postDataSummary['blockNamespace'] . ';' . PHP_EOL . PHP_EOL;

        $code .= 'use Concrete\Core\Asset\AssetList;' . PHP_EOL;
        $code .= 'use Concrete\Core\Block\BlockController;' . PHP_EOL;
        if ($postDataSummary['wysiwygEditorUsed'] or $postDataSummary['wysiwygEditorUsed_entry']) {
            $code .= 'use Concrete\Core\Editor\LinkAbstractor;' . PHP_EOL;
        }

        if (!empty($postDataSummary['exportFileColumns']) or $postDataSummary['linkUsed'] or $postDataSummary['linkUsed_entry']) {
            $code .= 'use Concrete\Core\File\File;' . PHP_EOL;
        }
        if (!empty($postDataSummary['exportPageColumns']) or $postDataSummary['linkUsed'] or $postDataSummary['linkUsed_entry']) {
            $code .= 'use Concrete\Core\Page\Page;' . PHP_EOL;
        }

        $code .= PHP_EOL;

        $code .= 'defined(\'C5_EXECUTE\') or die(\'Access Denied.\');' . PHP_EOL . PHP_EOL;

        $code .= 'class Controller extends BlockController' . PHP_EOL;
        $code .= '{' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(1) . 'protected $btTable = \'' . $postDataSummary['blockTableName'] . '\';' . PHP_EOL;
        if (empty($postData['entries'])) {
            $code .= BlockBuilderUtility::tab(1) . 'protected $btExportTables = [\'' . $postDataSummary['blockTableName'] . '\'];' . PHP_EOL;
        } else {
            $code .= BlockBuilderUtility::tab(1) . 'protected $btExportTables = [\'' . $postDataSummary['blockTableName'] . '\', \'' . $postDataSummary['blockTableNameEntries'] . '\'];' . PHP_EOL;
        }
        if (!empty($postDataSummary['exportPageColumns'])) {
            $code .= BlockBuilderUtility::tab(1) . 'protected $btExportPageColumns = [\'' . implode('\', \'', $postDataSummary['exportPageColumns']) . '\'];' . PHP_EOL;
        }
        if (!empty($postDataSummary['exportFileColumns'])) {
            $code .= BlockBuilderUtility::tab(1) . 'protected $btExportFileColumns = [\'' . implode('\', \'', $postDataSummary['exportFileColumns']) . '\'];' . PHP_EOL;
        }
        $code .= BlockBuilderUtility::tab(1) . 'protected $btInterfaceWidth = \'' . $postData['blockWidth'] . '\';' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . 'protected $btInterfaceHeight = \'' . $postData['blockHeight'] . '\';' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . 'protected $btWrapperClass = \'ccm-ui\';' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . 'protected $btCacheBlockRecord = true;' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . 'protected $btCacheBlockOutput = true;' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . 'protected $btCacheBlockOutputOnPost = true;' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . 'protected $btCacheBlockOutputForRegisteredUsers = true;' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . 'protected $btCacheBlockOutputLifetime = 0;' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(1) . 'protected $btDefaultSet = \'' . $postData['blockTypeSet'] . '\'; // basic, navigation, form, express, social, multimedia' . PHP_EOL . PHP_EOL;

        if (!empty($postDataSummary['settingsTab'])) {
            $code .= BlockBuilderUtility::tab(1) . 'protected $settings;'. PHP_EOL . PHP_EOL;
        }

        if (!empty($postData['basic'])) {
            foreach ($postData['basic'] as $k => $v) {
                $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . ';' . PHP_EOL;

                if ($v['fieldType'] == 'link_from_sitemap') {
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_ending;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_text;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_title;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_new_window;' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'link_from_file_manager') {
                    if (!empty($v['linkFromFileManagerShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_ending;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_text;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_title;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_new_window;' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'external_link') {
                    $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_protocol;' . PHP_EOL;
                    if (!empty($v['externalLinkShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_ending;' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_text;' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_title;' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_new_window;' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'image') {
                    if (!empty($v['imageShowAltTextField'])) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_alt;' . PHP_EOL;
                    }
                    if (
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']))
                        or
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))
                        or
                        (!empty($v['imageShowAltTextField']))
                    ) {
                        $code .= BlockBuilderUtility::tab(1) . 'protected $' . $v['handle'] . '_data;' . PHP_EOL;
                    }
                }
            }
            $code .= PHP_EOL;
        }

        $code .= BlockBuilderUtility::tab(1) . 'private $uniqueID;' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(1) . 'public function getBlockTypeName() {' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . 'return t(\'' . addslashes($postData['blockName']) . '\');' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(1) . 'public function getBlockTypeDescription() {' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . 'return t(\'' . addslashes($postData['blockDescription']) . '\');' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 2. getSearchableContent()
        if (!empty($postDataSummary['searchableFields']) or !empty($postDataSummary['searchableEntryFields'])) {

            $code .= BlockBuilderUtility::tab(1) . 'public function getSearchableContent() {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$content = [];' . PHP_EOL;
            foreach ($postDataSummary['searchableFields'] as $k => $v) {
                $code .= BlockBuilderUtility::tab(2) . '$content[] = $this->' . $v . ';' . PHP_EOL;
            }

            if (is_array($postDataSummary['searchableEntryFields']) and count($postDataSummary['searchableEntryFields'])) {
                $code .= PHP_EOL;
                $code .= BlockBuilderUtility::tab(2) . '$entries = $this->getEntries(\'edit\');' . PHP_EOL;
                $code .= BlockBuilderUtility::tab(2) . 'foreach ($entries as $entry) {' . PHP_EOL;
                foreach ($postDataSummary['searchableEntryFields'] as $k => $v) {
                    $code .= BlockBuilderUtility::tab(3) . '$content[] = $entry[\'' . $v . '\'];' . PHP_EOL;
                }
                $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL;
            }

            $code .= PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . 'return implode(\' \', $content);' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;

        }


        // 3. on_start
        $code .= BlockBuilderUtility::tab(1) . 'public function on_start() {' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(2) . '// Unique identifier' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$this->uniqueID = $this->app->make(\'helper/validation/identifier\')->getString(18);' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'uniqueID\', $this->uniqueID);' . PHP_EOL . PHP_EOL;

        if ($postDataSummary['settingsTab']) {
            $code .= BlockBuilderUtility::tab(2) . '// Settings tab' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$this->settings = is_array($this->settings) ? $this->settings : json_decode($this->settings, true);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$this->set(\'settings\', $this->settings);' . PHP_EOL . PHP_EOL;
        }

        if (!empty($postData['basic'])) {

            foreach ($postData['basic'] as $k => $v) {

                if ($v['fieldType'] == 'select_field') {

                    $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') options' . PHP_EOL;

                    $maxKeyLength = 0;
                    $tempOptions = [];
                    if (!empty($v['selectOptions'])) {
                        $options = explode('<br />', nl2br($v['selectOptions']));
                        if (is_array($options)) {
                            $i = 0;
                            foreach ($options as $k2 => $v2) {
                                $i++;
                                $option = explode('::', $v2);
                                $optionKey = !empty($option[1]) ? addslashes(trim($option[0])) : $i;
                                $optionValue = !empty($option[1]) ? addslashes(trim($option[1])) : addslashes(trim($option[0]));

                                $keyLength = mb_strlen($optionKey);
                                $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
                                $tempOptions[] = ['key' => $optionKey, 'value' => $optionValue, 'keyLength' => $keyLength];
                            }
                        }
                    }

                    // Generate actual code
                    $code .= BlockBuilderUtility::tab(2) . '$' . $v['handle'] . '_options ' . BlockBuilderUtility::arrayGap($maxKeyLength + 4) . '= [];' . PHP_EOL;
                    if (empty($v['selectType']) or $v['selectType'] === 'default_select') {
                        $code .= BlockBuilderUtility::tab(2) . '$' . $v['handle'] . '_options[] ' . BlockBuilderUtility::arrayGap($maxKeyLength + 2) . '= \'----\';' . PHP_EOL;
                    }
                    foreach ($tempOptions as $tempOption) {
                        $code .= BlockBuilderUtility::tab(2) . '$' . $v['handle'] . '_options[\'' . $tempOption['key'] . '\'] ';
                        $code .= BlockBuilderUtility::arrayGap($maxKeyLength, $tempOption['keyLength']);
                        $code .= '= t(\'' . $tempOption['value'] . '\');' . PHP_EOL;
                    }

                    $code .= PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_options\', $' . $v['handle'] . '_options);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'select_multiple_field') {

                    $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') options' . PHP_EOL;

                    $maxKeyLength = 0;
                    $tempOptions = [];
                    if (!empty($v['selectMultipleOptions'])) {
                        $options = explode('<br />', nl2br($v['selectMultipleOptions']));
                        if (is_array($options)) {
                            $i = 0;
                            foreach ($options as $k2 => $v2) {
                                $i++;
                                $option = explode('::', $v2);
                                $optionKey = !empty($option[1]) ? addslashes(trim($option[0])) : $i;
                                $optionValue = !empty($option[1]) ? addslashes(trim($option[1])) : addslashes(trim($option[0]));

                                $keyLength = mb_strlen($optionKey);
                                $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
                                $tempOptions[] = ['key' => $optionKey, 'value' => $optionValue, 'keyLength' => $keyLength];
                            }
                        }
                    }

                    // Generate actual code
                    $code .= BlockBuilderUtility::tab(2) . '$' . $v['handle'] . '_options ' . BlockBuilderUtility::arrayGap($maxKeyLength + 4) . '= [];' . PHP_EOL;
                    foreach ($tempOptions as $tempOption) {
                        $code .= BlockBuilderUtility::tab(2) . '$' . $v['handle'] . '_options[\'' . $tempOption['key'] . '\'] ';
                        $code .= BlockBuilderUtility::arrayGap($maxKeyLength, $tempOption['keyLength']);
                        $code .= '= t(\'' . $tempOption['value'] . '\');' . PHP_EOL;
                    }

                    $code .= PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_options\', $' . $v['handle'] . '_options);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'image') {
                    if (
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']))
                        or
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))
                        or
                        (!empty($v['imageShowAltTextField']))
                    ) {
                        $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . '_data) - Additional fields for Image' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$this->' . $v['handle'] . '_data = is_array($this->' . $v['handle'] . '_data) ? $this->' . $v['handle'] . '_data : json_decode($this->' . $v['handle'] . '_data, true);' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_data\', $this->' . $v['handle'] . '_data);' . PHP_EOL . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'link') {
                    $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Link' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '$this->' . $v['handle'] . ' = is_array($this->' . $v['handle'] . ') ? $this->' . $v['handle'] . ' : json_decode($this->' . $v['handle'] . ', true);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '\', $this->' . $v['handle'] . ');' . PHP_EOL . PHP_EOL;
                }

            }

        }

        if (!empty($postData['entries'])) {

            foreach ($postData['entries'] as $k => $v) {

                if ($v['fieldType'] == 'select_field') {

                    $code .= BlockBuilderUtility::tab(2) . '// Entry / ' . addslashes($v['label']) . ' (' . $v['handle'] . ') options' . PHP_EOL;

                    $maxKeyLength = 0;
                    $tempOptions = [];
                    if (!empty($v['selectOptions'])) {
                        $options = explode('<br />', nl2br($v['selectOptions']));
                        if (is_array($options)) {
                            $i = 0;
                            foreach ($options as $k2 => $v2) {
                                $i++;
                                $option = explode('::', $v2);
                                $optionKey = !empty($option[1]) ? addslashes(trim($option[0])) : $i;
                                $optionValue = !empty($option[1]) ? addslashes(trim($option[1])) : addslashes(trim($option[0]));

                                $keyLength = mb_strlen($optionKey);
                                $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
                                $tempOptions[] = ['key' => $optionKey, 'value' => $optionValue, 'keyLength' => $keyLength];
                            }
                        }
                    }

                    // Generate actual code
                    $code .= BlockBuilderUtility::tab(2) . '$entry_' . $v['handle'] . '_options ' . BlockBuilderUtility::arrayGap($maxKeyLength + 4) . '= [];' . PHP_EOL;
                    if (empty($v['selectType']) or $v['selectType'] === 'default_select') {
                        $code .= BlockBuilderUtility::tab(2) . '$entry_' . $v['handle'] . '_options[] ' . BlockBuilderUtility::arrayGap($maxKeyLength + 2) . '= \'----\';' . PHP_EOL;
                    }
                    foreach ($tempOptions as $tempOption) {
                        $code .= BlockBuilderUtility::tab(2) . '$entry_' . $v['handle'] . '_options[\'' . $tempOption['key'] . '\'] ';
                        $code .= BlockBuilderUtility::arrayGap($maxKeyLength, $tempOption['keyLength']);
                        $code .= '= t(\'' . $tempOption['value'] . '\');' . PHP_EOL;
                    }

                    $code .= PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '$this->set(\'entry_' . $v['handle'] . '_options\', $entry_' . $v['handle'] . '_options);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'select_multiple_field') {

                    $code .= BlockBuilderUtility::tab(2) . '// Entry / ' . addslashes($v['label']) . ' (' . $v['handle'] . ') options' . PHP_EOL;

                    $maxKeyLength = 0;
                    $tempOptions = [];
                    if (!empty($v['selectMultipleOptions'])) {
                        $options = explode('<br />', nl2br($v['selectMultipleOptions']));
                        if (is_array($options)) {
                            $i = 0;
                            foreach ($options as $k2 => $v2) {
                                $i++;
                                $option = explode('::', $v2);
                                $optionKey = !empty($option[1]) ? addslashes(trim($option[0])) : $i;
                                $optionValue = !empty($option[1]) ? addslashes(trim($option[1])) : addslashes(trim($option[0]));

                                $keyLength = mb_strlen($optionKey);
                                $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
                                $tempOptions[] = ['key' => $optionKey, 'value' => $optionValue, 'keyLength' => $keyLength];
                            }
                        }
                    }

                    // Generate actual code
                    $code .= BlockBuilderUtility::tab(2) . '$entry_' . $v['handle'] . '_options ' . BlockBuilderUtility::arrayGap($maxKeyLength + 4) . '= [];' . PHP_EOL;
                    foreach ($tempOptions as $tempOption) {
                        $code .= BlockBuilderUtility::tab(2) . '$entry_' . $v['handle'] . '_options[\'' . $tempOption['key'] . '\'] ';
                        $code .= BlockBuilderUtility::arrayGap($maxKeyLength, $tempOption['keyLength']);
                        $code .= '= t(\'' . $tempOption['value'] . '\');' . PHP_EOL;
                    }

                    $code .= PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '$this->set(\'entry_' . $v['handle'] . '_options\', $entry_' . $v['handle'] . '_options);' . PHP_EOL . PHP_EOL;

                }

            }

        }

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 4. add()
        $code .= BlockBuilderUtility::tab(1) . 'public function add() {' . PHP_EOL . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$this->addEdit();' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'entries\', []);' . PHP_EOL . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 5. edit()
        $code .= BlockBuilderUtility::tab(1) . 'public function edit() {' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(2) . '$this->addEdit();' . PHP_EOL . PHP_EOL;

        if ($postDataSummary['wysiwygEditorUsed']) {
            $code .= BlockBuilderUtility::tab(2) . '// Wysiwyg editors' . PHP_EOL;
            if (!empty($postData['basic'])) {
                foreach ($postData['basic'] as $k => $v) {
                    if ($v['fieldType'] == 'wysiwyg_editor') {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '\', LinkAbstractor::translateFromEditMode($this->' . $v['handle'] . '));' . PHP_EOL;
                    }
                }
            }
            $code .= PHP_EOL;
        }

        if (!empty($postData['entries'])) {
            $code .= BlockBuilderUtility::tab(2) . '// Get entries' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$entries = $this->getEntries(\'edit\');' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$this->set(\'entries\', $entries);' . PHP_EOL . PHP_EOL;
        }

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 6. addEdit()
        $code .= BlockBuilderUtility::tab(1) . 'public function addEdit() {' . PHP_EOL . PHP_EOL;

        if (!empty($postData['basic'])) {
            foreach ($postData['basic'] as $k => $v) {
                $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '\', $this->' . $v['handle'] . ');' . PHP_EOL;

                if ($v['fieldType'] == 'link_from_sitemap') {
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_ending\', $this->' . $v['handle'] . '_ending);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_text\', $this->' . $v['handle'] . '_text);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_title\', $this->' . $v['handle'] . '_title);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_new_window\', $this->' . $v['handle'] . '_new_window);' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'link_from_file_manager') {
                    if (!empty($v['linkFromFileManagerShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_ending\', $this->' . $v['handle'] . '_ending);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_text\', $this->' . $v['handle'] . '_text);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_title\', $this->' . $v['handle'] . '_title);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_new_window\', $this->' . $v['handle'] . '_new_window);' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'external_link') {
                    $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_protocol\', $this->' . $v['handle'] . '_protocol);' . PHP_EOL;
                    if (!empty($v['externalLinkShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_ending\', $this->' . $v['handle'] . '_ending);' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_text\', $this->' . $v['handle'] . '_text);' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_title\', $this->' . $v['handle'] . '_title);' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_new_window\', $this->' . $v['handle'] . '_new_window);' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'image') {
                    if (!empty($v['imageShowAltTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_alt\', $this->' . $v['handle'] . '_alt);' . PHP_EOL;
                    }
                    if (
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']))
                        or
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))
                        or
                        (!empty($v['imageShowAltTextField']))
                    ) {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '_data\', $this->' . $v['handle'] . '_data);' . PHP_EOL;
                    }
                }
            }
            $code .= PHP_EOL;
        }

        if (!empty($postData['entries'])) {

            if ($postDataSummary['linkUsed_entry'] or $postDataSummary['linkFromSitemapUsed_entry'] or $postDataSummary['linkFromFileManagerUsed_entry'] or $postDataSummary['imageUsed_entry']) {
                $code .= BlockBuilderUtility::tab(2) . '// Load assets for repeatable entries' . PHP_EOL;
            }
            if ($postDataSummary['linkUsed_entry'] or $postDataSummary['linkFromSitemapUsed_entry']) {
                $code .= BlockBuilderUtility::tab(2) . '$this->requireAsset(\'core/sitemap\');' . PHP_EOL;
            }
            if ($postDataSummary['linkUsed_entry'] or $postDataSummary['linkFromFileManagerUsed_entry'] or $postDataSummary['imageUsed_entry']) {
                $code .= BlockBuilderUtility::tab(2) . '$this->requireAsset(\'core/file-manager\');' . PHP_EOL;
            }
            if ($postDataSummary['linkUsed_entry'] or $postDataSummary['linkFromSitemapUsed_entry'] or $postDataSummary['linkFromFileManagerUsed_entry'] or $postDataSummary['imageUsed_entry']) {
                $code .= PHP_EOL;
            }

            $code .= BlockBuilderUtility::tab(2) . '// Get entry column names' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames = $this->getEntryColumnNames();' . PHP_EOL;

            if ($postDataSummary['datePickerUsed_entry']) {
                $code .= PHP_EOL;
                $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Fields that don\'t exist in database, but are required in repeatable entry' . PHP_EOL;
                foreach ($postData['entries'] as $k => $v) {
                    if ($v['fieldType'] == 'date_picker') {
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_displayed\';' . PHP_EOL;
                    }
                }
            }

            if ($postDataSummary['linkUsed_entry']) {
                foreach ($postData['entries'] as $k => $v) {
                    if ($v['fieldType'] == 'link') {
                        $code .= PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Fields that don\'t exist in database, but are required in repeatable entry (link)' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_link_type\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_show_additional_fields\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_link_from_sitemap\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_link_from_file_manager\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_protocol\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_external_link\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_ending\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_text\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_title\';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_new_window\';' . PHP_EOL;
                    }
                }
            }

            if ($postDataSummary['imageUsed_entry']) {
                foreach ($postData['entries'] as $k => $v) {
                    if ($v['fieldType'] == 'image') {
                        if (
                            (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']))
                            or
                            (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))
                            or
                            (!empty($v['imageShowAltTextField']))
                        ) {
                            $code .= PHP_EOL;
                            $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Fields that don\'t exist in database, but are required in repeatable entry (image)' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_show_additional_fields\';' . PHP_EOL;
                            if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {
                                $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_override_dimensions\';' . PHP_EOL;
                                $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_custom_width\';' . PHP_EOL;
                                $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_custom_height\';' . PHP_EOL;
                                $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_custom_crop\';' . PHP_EOL;
                            }
                            if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {
                                $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_override_fullscreen_dimensions\';' . PHP_EOL;
                                $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_custom_fullscreen_width\';' . PHP_EOL;
                                $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_custom_fullscreen_height\';' . PHP_EOL;
                                $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . '_custom_fullscreen_crop\';' . PHP_EOL;
                            }
                        }
                    }
                }
            }

            $code .= PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$this->set(\'entryColumnNames\', $entryColumnNames);' . PHP_EOL . PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(2) . '// Load form.css' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$al = AssetList::getInstance();' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$al->register(\'css\', \'' . $postDataSummary['blockHandleDashed'] . '/form\', \'blocks/' . $postDataSummary['blockHandle'] . '/css_files/form.css\', [], false);' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$this->requireAsset(\'css\', \'' . $postDataSummary['blockHandleDashed'] . '/form\');' . PHP_EOL . PHP_EOL;

        if ($postDataSummary['linkUsed'] or $postDataSummary['linkUsed_entry'] or $postDataSummary['externalLinkUsed'] or $postDataSummary['externalLinkUsed_entry']) {
            $code .= BlockBuilderUtility::tab(2) . '// External link protocols' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$externalLinkProtocols = [' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'http://\'  => \'http://\',' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'https://\' => \'https://\',' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'BASE_URL\' => \'BASE_URL\',' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'CURRENT_PAGE\' => \'CURRENT_PAGE\',' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'other\'    => \'----\'' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '];' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$this->set(\'externalLinkProtocols\', $externalLinkProtocols);' . PHP_EOL . PHP_EOL;
        }

        if ($postDataSummary['linkUsed'] or $postDataSummary['linkUsed_entry']) {
            $code .= BlockBuilderUtility::tab(2) . '// Link types' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$linkTypes = [' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'\'                       => \'----\',' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'link_from_sitemap\'      => t(\'' . addslashes($postData['linkFromSitemapLabel']) . '\'),' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'link_from_file_manager\' => t(\'' . addslashes($postData['linkFromFileManagerLabel']) . '\'),' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '\'external_link\'          => t(\'' . addslashes($postData['externalLinkLabel']) . '\')' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '];' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$this->set(\'linkTypes\', $linkTypes);' . PHP_EOL . PHP_EOL;
        }

        if ($postDataSummary['htmlEditorUsed'] or $postDataSummary['htmlEditorUsed_entry']) {
            $code .= BlockBuilderUtility::tab(2) . '// Load html editor' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$this->requireAsset(\'ace\');' . PHP_EOL . PHP_EOL;
        }

        $code .= BlockBuilderUtility::tab(2) . '// Make $app available in view' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'app\', $this->app);' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 7. view()
        $code .= BlockBuilderUtility::tab(1) . 'public function view() {' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(2) . '// Make $app available in view' . PHP_EOL;
        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'app\', $this->app);' . PHP_EOL . PHP_EOL;

        if ($postDataSummary['wysiwygEditorUsed']) {
            $code .= BlockBuilderUtility::tab(2) . '// Wysiwyg editors' . PHP_EOL;
            if (!empty($postData['basic'])) {
                foreach ($postData['basic'] as $k => $v) {
                    if ($v['fieldType'] == 'wysiwyg_editor') {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\'' . $v['handle'] . '\', LinkAbstractor::translateFrom($this->' . $v['handle'] . '));' . PHP_EOL;
                    }
                }
            }
            $code .= PHP_EOL;
        }

        if (!empty($postData['basic'])) {

            if ($postDataSummary['linkUsed'] or $postDataSummary['linkFromSitemapUsed'] or $postDataSummary['linkFromFileManagerUsed'] or $postDataSummary['externalLinkUsed'] or $postDataSummary['imageUsed']) {
                $code .= BlockBuilderUtility::tab(2) . '// Prepare fields for view' . PHP_EOL;
            }

            foreach ($postData['basic'] as $k => $v) {

                if ($v['fieldType'] == 'link') {

                    $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Link' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2) . 'if (!empty($this->' . $v['handle'] . ') and $this->' . $v['handle'] . '[\'link_type\'] == \'link_from_sitemap\') {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$this->prepareForViewLinkFromSitemap(\'view\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '\'            => $this->' . $v['handle'] . '[\'link_from_sitemap\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_ending\'     => $this->' . $v['handle'] . '[\'ending\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_text\'       => $this->' . $v['handle'] . '[\'text\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_title\'      => $this->' . $v['handle'] . '[\'title\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_new_window\' => $this->' . $v['handle'] . '[\'new_window\']' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . ']);' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2) . '} elseif (!empty($this->' . $v['handle'] . ') and $this->' . $v['handle'] . '[\'link_type\'] == \'link_from_file_manager\') {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$this->prepareForViewLinkFromFileManager(\'view\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '\'            => $this->' . $v['handle'] . '[\'link_from_file_manager\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_ending\'     => $this->' . $v['handle'] . '[\'ending\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_text\'       => $this->' . $v['handle'] . '[\'text\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_title\'      => $this->' . $v['handle'] . '[\'title\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_new_window\' => $this->' . $v['handle'] . '[\'new_window\']' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . ']);' . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2) . '} elseif (!empty($this->' . $v['handle'] . ') and $this->' . $v['handle'] . '[\'link_type\'] == \'external_link\') {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$this->prepareForViewExternalLink(\'view\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '\'            => $this->' . $v['handle'] . '[\'external_link\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_protocol\'   => $this->' . $v['handle'] . '[\'protocol\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_ending\'     => $this->' . $v['handle'] . '[\'ending\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_text\'       => $this->' . $v['handle'] . '[\'text\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_title\'      => $this->' . $v['handle'] . '[\'title\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '\'' . $v['handle'] . '_new_window\' => $this->' . $v['handle'] . '[\'new_window\']' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link_from_sitemap') {

                    $ending = 'false';
                    $text = 'false';
                    $title = 'false';
                    $newWindow = 'false';
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $ending = '$this->' . $v['handle'] . '_ending';
                    }
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $text = '$this->' . $v['handle'] . '_text';
                    }
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $title = '$this->' . $v['handle'] . '_title';
                    }
                    if (!empty($v['linkFromSitemapShowNewWindowField'])) {
                        $newWindow = '$this->' . $v['handle'] . '_new_window';
                    }

                    $code .= BlockBuilderUtility::tab(2) . '$this->prepareForViewLinkFromSitemap(\'view\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '\'            => $this->' . $v['handle'] . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_ending\'     => ' . $ending . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_text\'       => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_title\'      => ' . $title . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_new_window\' => ' . $newWindow . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . ']);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link_from_file_manager') {

                    $ending = 'false';
                    $text = 'false';
                    $title = 'false';
                    $newWindow = 'false';
                    if (!empty($v['linkFromFileManagerShowEndingField'])) {
                        $ending = '$this->' . $v['handle'] . '_ending';
                    }
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $text = '$this->' . $v['handle'] . '_text';
                    }
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $title = '$this->' . $v['handle'] . '_title';
                    }
                    if (!empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $newWindow = '$this->' . $v['handle'] . '_new_window';
                    }

                    $code .= BlockBuilderUtility::tab(2) . '$this->prepareForViewLinkFromFileManager(\'view\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '\'            => $this->' . $v['handle'] . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_ending\'     => ' . $ending . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_text\'       => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_title\'      => ' . $title . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_new_window\' => ' . $newWindow . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . ']);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'external_link') {

                    $ending = 'false';
                    $text = 'false';
                    $title = 'false';
                    $newWindow = 'false';
                    if (!empty($v['externalLinkShowEndingField'])) {
                        $ending = '$this->' . $v['handle'] . '_ending';
                    }
                    if (!empty($v['externalLinkShowTextField'])) {
                        $text = '$this->' . $v['handle'] . '_text';
                    }
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $title = '$this->' . $v['handle'] . '_title';
                    }
                    if (!empty($v['externalLinkShowNewWindowField'])) {
                        $newWindow = '$this->' . $v['handle'] . '_new_window';
                    }

                    $code .= BlockBuilderUtility::tab(2) . '$this->prepareForViewExternalLink(\'view\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '\'            => $this->' . $v['handle'] . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_protocol\'   => $this->' . $v['handle'] . '_protocol' . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_ending\'     => ' . $ending . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_text\'       => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_title\'      => ' . $title . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_new_window\' => ' . $newWindow . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . ']);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'image') {

                    $alt = 'false';
                    if (!empty($v['imageShowAltTextField'])) {
                        $alt = '$this->' . $v['handle'] . '_alt';
                    }

                    $thumbnail = 'false';
                    $thumbnailWidth = 'false';
                    $thumbnailHeight = 'false';
                    $thumbnailCrop = 'false';
                    if (!empty($v['imageCreateThumbnailImage'])) {
                        $thumbnail = 'true';
                        if (!empty($v['imageThumbnailEditable'])) {
                            $imageThumbnailWidthDefault = !empty($v['imageThumbnailWidth']) ? $v['imageThumbnailWidth'] : 'false';
                            $thumbnailWidth = '!empty($this->' . $v['handle'] . '_data[\'override_dimensions\']) ? (!empty($this->' . $v['handle'] . '_data[\'custom_width\']) ? $this->' . $v['handle'] . '_data[\'custom_width\'] : false) : ' . $imageThumbnailWidthDefault;
                            $imageThumbnailHeightDefault = !empty($v['imageThumbnailHeight']) ? $v['imageThumbnailHeight'] : 'false';
                            $thumbnailHeight = '!empty($this->' . $v['handle'] . '_data[\'override_dimensions\']) ? (!empty($this->' . $v['handle'] . '_data[\'custom_height\']) ? $this->' . $v['handle'] . '_data[\'custom_height\'] : false) : ' . $imageThumbnailHeightDefault;
                            $imageThumbnailCropDefault = !empty($v['imageThumbnailCrop']) ? $v['imageThumbnailCrop'] : 'false';
                            $thumbnailCrop = '!empty($this->' . $v['handle'] . '_data[\'override_dimensions\']) ? (!empty($this->' . $v['handle'] . '_data[\'custom_crop\']) ? true : false) : ' . $imageThumbnailCropDefault;
                        } else {
                            if (!empty($v['imageThumbnailWidth'])) $thumbnailWidth = $v['imageThumbnailWidth'];
                            if (!empty($v['imageThumbnailHeight'])) $thumbnailHeight = $v['imageThumbnailHeight'];
                            if (!empty($v['imageThumbnailCrop'])) $thumbnailCrop = 'true';
                        }
                    }

                    $fullscreen = 'false';
                    $fullscreenWidth = 'false';
                    $fullscreenHeight = 'false';
                    $fullscreenCrop = 'false';
                    if (!empty($v['imageCreateFullscreenImage'])) {
                        $fullscreen = 'true';
                        if (!empty($v['imageFullscreenEditable'])) {
                            $imageFullscreenWidthDefault = !empty($v['imageFullscreenWidth']) ? $v['imageFullscreenWidth'] : 'false';
                            $fullscreenWidth = '!empty($this->' . $v['handle'] . '_data[\'override_fullscreen_dimensions\']) ? (!empty($this->' . $v['handle'] . '_data[\'custom_fullscreen_width\']) ? $this->' . $v['handle'] . '_data[\'custom_fullscreen_width\'] : false) : ' . $imageFullscreenWidthDefault;
                            $imageFullscreenHeightDefault = !empty($v['imageFullscreenHeight']) ? $v['imageFullscreenHeight'] : 'false';
                            $fullscreenHeight = '!empty($this->' . $v['handle'] . '_data[\'override_fullscreen_dimensions\']) ? (!empty($this->' . $v['handle'] . '_data[\'custom_fullscreen_height\']) ? $this->' . $v['handle'] . '_data[\'custom_fullscreen_height\'] : false) : ' . $imageFullscreenHeightDefault;
                            $imageFullscreenCropDefault = !empty($v['imageFullscreenCrop']) ? $v['imageFullscreenCrop'] : 'false';
                            $fullscreenCrop = '!empty($this->' . $v['handle'] . '_data[\'override_fullscreen_dimensions\']) ? (!empty($this->' . $v['handle'] . '_data[\'custom_fullscreen_crop\']) ? true : false) : ' . $imageFullscreenCropDefault;
                        } else {
                            if (!empty($v['imageFullscreenWidth'])) $fullscreenWidth = $v['imageFullscreenWidth'];
                            if (!empty($v['imageFullscreenHeight'])) $fullscreenHeight = $v['imageFullscreenHeight'];
                            if (!empty($v['imageFullscreenCrop'])) $fullscreenCrop = 'true';
                        }
                    }

                    $code .= BlockBuilderUtility::tab(2) . '$this->prepareForViewImage(\'view\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '\'     => $this->' . $v['handle'] . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'' . $v['handle'] . '_alt\' => ' . $alt . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '], [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'thumbnail\'        => ' . $thumbnail . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'thumbnailWidth\'   => ' . $thumbnailWidth . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'thumbnailHeight\'  => ' . $thumbnailHeight . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'thumbnailCrop\'    => ' . $thumbnailCrop . ',' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3) . '\'fullscreen\'       => ' . $fullscreen . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'fullscreenWidth\'  => ' . $fullscreenWidth . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'fullscreenHeight\' => ' . $fullscreenHeight . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'fullscreenCrop\'   => ' . $fullscreenCrop . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . ']);' . PHP_EOL . PHP_EOL;

                }

            }

        }

        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(2) . '// Get entries' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$entries = $this->getEntries();' . PHP_EOL;

            if (
                $postDataSummary['linkUsed_entry'] or
                $postDataSummary['linkFromSitemapUsed_entry'] or
                $postDataSummary['linkFromFileManagerUsed_entry'] or
                $postDataSummary['externalLinkUsed_entry'] or
                $postDataSummary['imageUsed_entry']
            ) {
                $code .= BlockBuilderUtility::tab(2) . '$entries = $this->prepareEntriesForView($entries);' . PHP_EOL;
            }

            $code .= BlockBuilderUtility::tab(2) . '$this->set(\'entries\', $entries);' . PHP_EOL . PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 8. save()
        $code .= BlockBuilderUtility::tab(1) . 'public function save($args) {' . PHP_EOL;

        if ($postDataSummary['settingsTab']) {
            $code .= PHP_EOL . BlockBuilderUtility::tab(2) . '// Settings' . PHP_EOL;

            if (!empty($postData['entries'])) {

                foreach ($postData['entries'] as $k => $v) {

                    if ($v['fieldType'] == 'image') {

                        if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {
                            $code .= BlockBuilderUtility::tab(2) . 'if (isset($args[\'settings\']) and $args[\'settings\'][\'' . $v['handle'] . '_custom_crop\']===\'1\' and (empty($args[\'settings\'][\'' . $v['handle'] . '_custom_width\']) or empty($args[\'settings\'][\'' . $v['handle'] . '_custom_height\']))) {' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '$args[\'settings\'][\'' . $v['handle'] . '_custom_crop\'] = 0; // Crop should be disabled if width or height is missing' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL;
                        }

                        if (!empty($v['imageFullscreenEditable']) and !empty($v['imageCreateFullscreenImage'])) {
                            $code .= BlockBuilderUtility::tab(2) . 'if (isset($args[\'settings\']) and $args[\'settings\'][\'' . $v['handle'] . '_custom_fullscreen_crop\']===\'1\' and (empty($args[\'settings\'][\'' . $v['handle'] . '_custom_fullscreen_width\']) or empty($args[\'settings\'][\'' . $v['handle'] . '_custom_fullscreen_height\']))) {' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '$args[\'settings\'][\'' . $v['handle'] . '_custom_fullscreen_crop\'] = 0; // Crop should be disabled if width or height is missing' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL;
                        }

                    }

                }

            }

            $code .= BlockBuilderUtility::tab(2) . '$args[\'settings\'] = isset($args[\'settings\']) ? json_encode($args[\'settings\']) : null;' . PHP_EOL;
        }

        if (!empty($postData['basic'])) {

            $code .= PHP_EOL . BlockBuilderUtility::tab(2) . '// Basic fields' . PHP_EOL;

            $maxKeyLength = 0;

            foreach ($postData['basic'] as $k => $v) {

                $keyLength = mb_strlen($v['handle']);
                $additionalSpaces = 0;

                // Lower length words first
                if ($v['fieldType'] == 'link_from_sitemap') {
                    !empty($v['linkFromSitemapShowTextField']) ? $additionalSpaces = 5 : false;
                    !empty($v['linkFromSitemapShowTitleField']) ? $additionalSpaces = 6 : false;
                    !empty($v['linkFromSitemapShowEndingField']) ? $additionalSpaces = 7 : false;
                    !empty($v['linkFromSitemapShowNewWindowField']) ? $additionalSpaces = 11 : false;
                } elseif ($v['fieldType'] == 'link_from_file_manager') {
                    !empty($v['linkFromFileManagerShowTextField']) ? $additionalSpaces = 5 : false;
                    !empty($v['linkFromFileManagerShowTitleField']) ? $additionalSpaces = 6 : false;
                    !empty($v['linkFromFileManagerShowEndingField']) ? $additionalSpaces = 7 : false;
                    !empty($v['linkFromFileManagerShowNewWindowField']) ? $additionalSpaces = 11 : false;
                } else if ($v['fieldType'] == 'external_link') {
                    $additionalSpaces = 9; // string '_protocol' is always used
                    !empty($v['externalLinkShowNewWindowField']) ? $additionalSpaces = 11 : false;
                } elseif ($v['fieldType'] == 'image') {
                    !empty($v['imageShowAltTextField']) ? $additionalSpaces = 4 : false;
                }

                $keyLength += $additionalSpaces;

                $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
            }

            foreach ($postData['basic'] as $k => $v) {

                $keyLength = mb_strlen($v['handle']);

                // Basic fields
                if ($v['fieldType'] == 'wysiwyg_editor') {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($args[\'' . $v['handle'] . '\']) ? LinkAbstractor::translateTo($args[\'' . $v['handle'] . '\']) : null;' . PHP_EOL;
                } else if ($v['fieldType'] == 'html_editor') {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($args[\'' . $v['handle'] . '\']) ? $args[\'' . $v['handle'] . '\'] : null;' . PHP_EOL;
                } else if (in_array($v['fieldType'], ['link_from_sitemap', 'link_from_file_manager', 'image'])) {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($args[\'' . $v['handle'] . '\']) ? intval($args[\'' . $v['handle'] . '\']) : 0;' . PHP_EOL;
                } else if ($v['fieldType'] == 'date_picker') {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($args[\'' . $v['handle'] . '\']) ? $args[\'' . $v['handle'] . '\'] : null;' . PHP_EOL;
                } else if ($v['fieldType'] == 'link') {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($args[\'' . $v['handle'] . '\']) ? trim($args[\'' . $v['handle'] . '\']) : null;' . PHP_EOL;
                } else if ($v['fieldType'] == 'select_field') {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($args[\'' . $v['handle'] . '\']) ? trim($args[\'' . $v['handle'] . '\']) : \'\';' . PHP_EOL;
                } else if ($v['fieldType'] == 'select_multiple_field') {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($args[\'' . $v['handle'] . '\']) ? trim(implode(\'|\', $args[\'' . $v['handle'] . '\'])) : \'\';' . PHP_EOL;
                } else {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($args[\'' . $v['handle'] . '\']) ? trim($args[\'' . $v['handle'] . '\']) : null;' . PHP_EOL;
                }

                // Additional fields
                if ($v['fieldType'] == 'link_from_sitemap') {
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_ending\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 7) . '= !empty($args[\'' . $v['handle'] . '_ending\']) ? trim($args[\'' . $v['handle'] . '_ending\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= !empty($args[\'' . $v['handle'] . '_text\']) ? trim($args[\'' . $v['handle'] . '_text\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_title\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 6) . '= !empty($args[\'' . $v['handle'] . '_title\']) ? trim($args[\'' . $v['handle'] . '_title\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_new_window\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 11) . '= !empty($args[\'' . $v['handle'] . '_new_window\']) ? intval($args[\'' . $v['handle'] . '_new_window\']) : 0;' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'link_from_file_manager') {
                    if (!empty($v['linkFromFileManagerShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_ending\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 7) . '= !empty($args[\'' . $v['handle'] . '_ending\']) ? trim($args[\'' . $v['handle'] . '_ending\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= !empty($args[\'' . $v['handle'] . '_text\']) ? trim($args[\'' . $v['handle'] . '_text\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_title\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 6) . '= !empty($args[\'' . $v['handle'] . '_title\']) ? trim($args[\'' . $v['handle'] . '_title\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_new_window\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 11) . '= !empty($args[\'' . $v['handle'] . '_new_window\']) ? intval($args[\'' . $v['handle'] . '_new_window\']) : 0;' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'external_link') {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_protocol\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 9) . '= !empty($args[\'' . $v['handle'] . '_protocol\']) ? trim($args[\'' . $v['handle'] . '_protocol\']) : null;' . PHP_EOL;
                    if (!empty($v['externalLinkShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_ending\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 7) . '= !empty($args[\'' . $v['handle'] . '_ending\']) ? trim($args[\'' . $v['handle'] . '_ending\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= !empty($args[\'' . $v['handle'] . '_text\']) ? trim($args[\'' . $v['handle'] . '_text\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_title\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 6) . '= !empty($args[\'' . $v['handle'] . '_title\']) ? trim($args[\'' . $v['handle'] . '_title\']) : null;' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_new_window\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 11) . '= !empty($args[\'' . $v['handle'] . '_new_window\']) ? intval($args[\'' . $v['handle'] . '_new_window\']) : 0;' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'image') {
                    if (!empty($v['imageShowAltTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_alt\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 4) . '= !empty($args[\'' . $v['handle'] . '_alt\']) ? trim($args[\'' . $v['handle'] . '_alt\']) : null;' . PHP_EOL;
                    }
                }
            }

            foreach ($postData['basic'] as $k => $v) {
                if ($v['fieldType'] == 'image') {
                    if (
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']))
                        or
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))
                        or
                        (!empty($v['imageShowAltTextField']))
                    ) {
                        $code .= PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Additional fields for Image' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_data\'] = json_encode([' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3) . '\'show_additional_fields\'         => !empty($args[\'' . $v['handle'] . '_show_additional_fields\']) ? intval($args[\'' . $v['handle'] . '_show_additional_fields\']) : 0,' . PHP_EOL;
                        if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {
                            $code .= BlockBuilderUtility::tab(3) . '\'override_dimensions\'            => !empty($args[\'' . $v['handle'] . '_override_dimensions\']) ? intval($args[\'' . $v['handle'] . '_override_dimensions\']) : 0,' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '\'custom_width\'                   => !empty($args[\'' . $v['handle'] . '_custom_width\']) ? intval($args[\'' . $v['handle'] . '_custom_width\']) : 0,' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '\'custom_height\'                  => !empty($args[\'' . $v['handle'] . '_custom_height\']) ? intval($args[\'' . $v['handle'] . '_custom_height\']) : 0,' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '\'custom_crop\'                    => (!empty($args[\'' . $v['handle'] . '_custom_crop\']) and $args[\'' . $v['handle'] . '_custom_crop\']===\'1\' and (!(bool)$args[\'' . $v['handle'] . '_custom_width\'] or !(bool)$args[\'' . $v['handle'] . '_custom_height\'])) ? false : (isset($args[\'' . $v['handle'] . '_custom_crop\']) ? intval($args[\'' . $v['handle'] . '_custom_crop\']) : 0), // do not crop without width and height filled' . PHP_EOL;
                        }
                        if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {
                            $code .= BlockBuilderUtility::tab(3) . '\'override_fullscreen_dimensions\' => !empty($args[\'' . $v['handle'] . '_override_fullscreen_dimensions\']) ? intval($args[\'' . $v['handle'] . '_override_fullscreen_dimensions\']) : 0,' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '\'custom_fullscreen_width\'        => !empty($args[\'' . $v['handle'] . '_custom_fullscreen_width\']) ? intval($args[\'' . $v['handle'] . '_custom_fullscreen_width\']) : 0,' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '\'custom_fullscreen_height\'       => !empty($args[\'' . $v['handle'] . '_custom_fullscreen_height\']) ? intval($args[\'' . $v['handle'] . '_custom_fullscreen_height\']) : 0,' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '\'custom_fullscreen_crop\'         => (!empty($args[\'' . $v['handle'] . '_custom_fullscreen_crop\']) and $args[\'' . $v['handle'] . '_custom_fullscreen_crop\']===\'1\' and (!(bool)$args[\'' . $v['handle'] . '_custom_fullscreen_width\'] or !(bool)$args[\'' . $v['handle'] . '_custom_fullscreen_height\'])) ? false : (isset($args[\'' . $v['handle'] . '_custom_fullscreen_crop\']) ? intval($args[\'' . $v['handle'] . '_custom_fullscreen_crop\']) : 0), // do not crop without width and height filled' . PHP_EOL;
                        }
                        $code .= BlockBuilderUtility::tab(2) . ']);' . PHP_EOL;
                    }
                }
                if ($v['fieldType'] == 'link') {
                    $code .= PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Link' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '\'] = json_encode([' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'link_type\'              => !empty($args[\'' . $v['handle'] . '_link_type\']) ? trim($args[\'' . $v['handle'] . '_link_type\']) : null,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'show_additional_fields\' => !empty($args[\'' . $v['handle'] . '_show_additional_fields\']) ? intval($args[\'' . $v['handle'] . '_show_additional_fields\']) : 0,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'link_from_sitemap\'      => !empty($args[\'' . $v['handle'] . '_link_from_sitemap\']) ? intval($args[\'' . $v['handle'] . '_link_from_sitemap\']) : 0,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'link_from_file_manager\' => !empty($args[\'' . $v['handle'] . '_link_from_file_manager\']) ? intval($args[\'' . $v['handle'] . '_link_from_file_manager\']) : 0,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'protocol\'               => !empty($args[\'' . $v['handle'] . '_protocol\']) ? trim($args[\'' . $v['handle'] . '_protocol\']) : null,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'external_link\'          => !empty($args[\'' . $v['handle'] . '_external_link\']) ? trim($args[\'' . $v['handle'] . '_external_link\']) : null,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'ending\'                 => !empty($args[\'' . $v['handle'] . '_ending\']) ? trim($args[\'' . $v['handle'] . '_ending\']) : null,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'text\'                   => !empty($args[\'' . $v['handle'] . '_text\']) ? trim($args[\'' . $v['handle'] . '_text\']) : null,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'title\'                  => !empty($args[\'' . $v['handle'] . '_title\']) ? trim($args[\'' . $v['handle'] . '_title\']) : null,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '\'new_window\'             => !empty($args[\'' . $v['handle'] . '_new_window\']) ? intval($args[\'' . $v['handle'] . '_new_window\']) : 0' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2) . ']);' . PHP_EOL;
                }
            }

        }
        $code .= PHP_EOL;

        $code .= BlockBuilderUtility::tab(2) . 'parent::save($args);' . PHP_EOL . PHP_EOL;

        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(2) . '$db = $this->app->make(\'database\')->connection();' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '// Delete existing entries of current block\'s version' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$db->delete(\'' . $postDataSummary['blockTableNameEntries'] . '\', [\'bID\' => $this->bID]);' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'if (isset($args[\'entry\']) AND is_array($args[\'entry\']) AND count($args[\'entry\'])) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '$i = 1;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . 'foreach ($args[\'entry\'] as $entry) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '// Prepare data for insert' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$data = [];' . PHP_EOL;

            $maxKeyLength = 8;
            foreach ($postData['entries'] as $k => $v) {

                $keyLength = mb_strlen($v['handle']);
                $additionalSpaces = 0;

                // Lower length words first
                if ($v['fieldType'] == 'link_from_sitemap') {
                    !empty($v['linkFromSitemapShowTextField']) ? $additionalSpaces = 5 : false;
                    !empty($v['linkFromSitemapShowTitleField']) ? $additionalSpaces = 6 : false;
                    !empty($v['linkFromSitemapShowEndingField']) ? $additionalSpaces = 7 : false;
                    !empty($v['linkFromSitemapShowNewWindowField']) ? $additionalSpaces = 11 : false;
                } elseif ($v['fieldType'] == 'link_from_file_manager') {
                    !empty($v['linkFromFileManagerShowTextField']) ? $additionalSpaces = 5 : false;
                    !empty($v['linkFromFileManagerShowTitleField']) ? $additionalSpaces = 6 : false;
                    !empty($v['linkFromFileManagerShowEndingField']) ? $additionalSpaces = 7 : false;
                    !empty($v['linkFromFileManagerShowNewWindowField']) ? $additionalSpaces = 11 : false;
                } else if ($v['fieldType'] == 'external_link') {
                    $additionalSpaces = 9; // string '_protocol' is always used
                    !empty($v['externalLinkShowNewWindowField']) ? $additionalSpaces = 11 : false;
                } elseif ($v['fieldType'] == 'image') {
                    !empty($v['imageShowAltTextField']) ? $additionalSpaces = 4 : false;
                }

                $keyLength += $additionalSpaces;

                $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
            }

            $code .= BlockBuilderUtility::tab(4) . '$data[\'position\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, 8) . '= $i;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$data[\'bID\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, 3) . '= $this->bID;' . PHP_EOL;

            foreach ($postData['entries'] as $k => $v) {

                $keyLength = mb_strlen($v['handle']);

                // Basic fields
                if ($v['fieldType'] == 'wysiwyg_editor') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= LinkAbstractor::translateTo($entry[\'' . $v['handle'] . '\']);' . PHP_EOL;
                } else if ($v['fieldType'] == 'html_editor') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= $entry[\'' . $v['handle'] . '\'];' . PHP_EOL;
                } else if (in_array($v['fieldType'], ['link_from_sitemap', 'link_from_file_manager', 'image'])) {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= intval($entry[\'' . $v['handle'] . '\']);' . PHP_EOL;
                } else if ($v['fieldType'] == 'date_picker') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($entry[\'' . $v['handle'] . '\']) ? $this->app->make(\'helper/form/date_time\')->translate(\'' . $v['handle'] . '\', $entry) : null;' . PHP_EOL;
                } else if ($v['fieldType'] == 'link') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($entry[\'' . $v['handle'] . '\']) ? trim($entry[\'' . $v['handle'] . '\']) : null;' . PHP_EOL;
                } else if ($v['fieldType'] == 'select_field') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($entry[\'' . $v['handle'] . '\']) ? trim($entry[\'' . $v['handle'] . '\']) : \'\';' . PHP_EOL;
                } else if ($v['fieldType'] == 'select_multiple_field') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= !empty($entry[\'' . $v['handle'] . '\']) ? implode(\'|\', $entry[\'' . $v['handle'] . '\']) : \'\';' . PHP_EOL;
                } else {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= trim($entry[\'' . $v['handle'] . '\']);' . PHP_EOL;
                }

                // Additional fields
                if ($v['fieldType'] == 'link_from_sitemap') {
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_ending\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 7) . '= trim($entry[\'' . $v['handle'] . '_ending\']);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= trim($entry[\'' . $v['handle'] . '_text\']);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_title\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 6) . '= trim($entry[\'' . $v['handle'] . '_title\']);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromSitemapShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_new_window\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 11) . '= intval($entry[\'' . $v['handle'] . '_new_window\']);' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'link_from_file_manager') {
                    if (!empty($v['linkFromFileManagerShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_ending\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 7) . '= trim($entry[\'' . $v['handle'] . '_ending\']);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= trim($entry[\'' . $v['handle'] . '_text\']);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_title\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 6) . '= trim($entry[\'' . $v['handle'] . '_title\']);' . PHP_EOL;
                    }
                    if (!empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_new_window\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 11) . '= intval($entry[\'' . $v['handle'] . '_new_window\']);' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'external_link') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_protocol\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 9) . '= trim($entry[\'' . $v['handle'] . '_protocol\']);' . PHP_EOL;
                    if (!empty($v['externalLinkShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_ending\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 7) . '= trim($entry[\'' . $v['handle'] . '_ending\']);' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= trim($entry[\'' . $v['handle'] . '_text\']);' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_title\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 6) . '= trim($entry[\'' . $v['handle'] . '_title\']);' . PHP_EOL;
                    }
                    if (!empty($v['externalLinkShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_new_window\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 11) . '= intval($entry[\'' . $v['handle'] . '_new_window\']);' . PHP_EOL;
                    }
                }

                if ($v['fieldType'] == 'image') {
                    if (!empty($v['imageShowAltTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_alt\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 4) . '= trim($entry[\'' . $v['handle'] . '_alt\']);' . PHP_EOL;
                    }
                }

            }

            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'link') {
                    $code .= PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Link' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '\'] = json_encode([' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'link_type\'              => trim($entry[\'' . $v['handle'] . '_link_type\']),' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'show_additional_fields\' => intval($entry[\'' . $v['handle'] . '_show_additional_fields\']),' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'link_from_sitemap\'      => intval($entry[\'' . $v['handle'] . '_link_from_sitemap\']),' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'link_from_file_manager\' => !empty($entry[\'' . $v['handle'] . '_link_from_file_manager\']) ? intval($entry[\'' . $v['handle'] . '_link_from_file_manager\']) : 0,' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'protocol\'               => trim($entry[\'' . $v['handle'] . '_protocol\']),' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'external_link\'          => trim($entry[\'' . $v['handle'] . '_external_link\']),' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'ending\'                 => trim($entry[\'' . $v['handle'] . '_ending\']),' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'text\'                   => trim($entry[\'' . $v['handle'] . '_text\']),' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'title\'                  => trim($entry[\'' . $v['handle'] . '_title\']),' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'new_window\'             => intval($entry[\'' . $v['handle'] . '_new_window\'])' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . ']);' . PHP_EOL;
                }
            }

            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'image') {
                    if (
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']))
                        or
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))
                        or
                        (!empty($v['imageShowAltTextField']))
                    ) {
                        $code .= PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Image' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_data\'] = json_encode([' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '\'show_additional_fields\'         => intval($entry[\'' . $v['handle'] . '_show_additional_fields\']),' . PHP_EOL;
                        if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {
                            $code .= BlockBuilderUtility::tab(5) . '\'override_dimensions\'            => !empty($entry[\'' . $v['handle'] . '_override_dimensions\']) ? intval($entry[\'' . $v['handle'] . '_override_dimensions\']) : 0,' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(5) . '\'custom_width\'                   => intval($entry[\'' . $v['handle'] . '_custom_width\']),' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(5) . '\'custom_height\'                  => intval($entry[\'' . $v['handle'] . '_custom_height\']),' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(5) . '\'custom_crop\'                    => ($entry[\'' . $v['handle'] . '_custom_crop\']===\'1\' and (!(bool)$entry[\'' . $v['handle'] . '_custom_width\'] or !(bool)$entry[\'' . $v['handle'] . '_custom_height\'])) ? false : intval($entry[\'' . $v['handle'] . '_custom_crop\']), // do not crop without width and height filled' . PHP_EOL;
                        }
                        if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {
                            $code .= BlockBuilderUtility::tab(5) . '\'override_fullscreen_dimensions\' => !empty($entry[\'' . $v['handle'] . '_override_fullscreen_dimensions\']) ? intval($entry[\'' . $v['handle'] . '_override_fullscreen_dimensions\']) : 0,' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(5) . '\'custom_fullscreen_width\'        => intval($entry[\'' . $v['handle'] . '_custom_fullscreen_width\']),' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(5) . '\'custom_fullscreen_height\'       => intval($entry[\'' . $v['handle'] . '_custom_fullscreen_height\']),' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(5) . '\'custom_fullscreen_crop\'         => ($entry[\'' . $v['handle'] . '_custom_fullscreen_crop\']===\'1\' and (!(bool)$entry[\'' . $v['handle'] . '_custom_fullscreen_width\'] or !(bool)$entry[\'' . $v['handle'] . '_custom_fullscreen_height\'])) ? false : intval($entry[\'' . $v['handle'] . '_custom_fullscreen_crop\']), // do not crop without width and height filled' . PHP_EOL;
                        }
                        $code .= BlockBuilderUtility::tab(4) . ']);' . PHP_EOL;
                    }
                }
            }

            $code .= PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$db->insert(\'' . $postDataSummary['blockTableNameEntries'] . '\', $data);' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '$i++;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 9. duplicate()
        $code .= BlockBuilderUtility::tab(1) . 'public function duplicate($newBlockID) {' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(2) . 'parent::duplicate($newBlockID);' . PHP_EOL . PHP_EOL;

        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(2) . '$db = $this->app->make(\'database\')->connection();' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '// Get latest entry...' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$sql = \'' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'SELECT' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '.*' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'FROM' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'WHERE' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '.bID = :bID' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '\';' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$parameters = [];' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$parameters[\'bID\'] = $this->bID;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$entries = $db->fetchAll($sql, $parameters);' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '// ... and copy it' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . 'if (is_array($entries) AND count($entries)) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'foreach ($entries as $entry) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$data = [];' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . 'foreach ($entry as $columnName => $value) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '$data[$columnName] = $value;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . 'unset($data[\'id\']);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$data[\'bID\'] = $newBlockID;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$db->insert(\'' . $postDataSummary['blockTableNameEntries'] . '\', $data);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '}' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 10. delete()
        $code .= BlockBuilderUtility::tab(1) . 'public function delete() {' . PHP_EOL . PHP_EOL;
        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 11. validate()
        $code .= BlockBuilderUtility::tab(1) . 'public function validate($args) {' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(2) . '$error = $this->app->make(\'helper/validation/error\');' . PHP_EOL . PHP_EOL;

        if (!empty($postDataSummary['requiredFields'])) {

            // Required fields
            $code .= BlockBuilderUtility::tab(2) . '// Required fields' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$requiredFields = [];' . PHP_EOL;
            $maxKeyLength = 0;
            foreach ($postData['basic'] as $k => $v) {
                if (!empty($v['required']) and $v['fieldType'] != 'link') {
                    $keyLength = mb_strlen($v['handle']);
                    $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
                }
            }
            foreach ($postData['basic'] as $k => $v) {
                if (!empty($v['required']) and $v['fieldType'] != 'link') {
                    $keyLength = mb_strlen($v['handle']);
                    $code .= BlockBuilderUtility::tab(2) . '$requiredFields[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= t(\'' . addslashes($v['label']) . '\');' . PHP_EOL;
                }
            }
            $code .= PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'foreach ($requiredFields as $requiredFieldHandle => $requiredFieldLabel) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . 'if (empty($args[$requiredFieldHandle])) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$error->add(t(\'Field "%s" is required.\', $requiredFieldLabel));' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

            // Required fields - Links
            $code .= BlockBuilderUtility::tab(2) . '// Required fields - Links' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$requiredLinkFields = [];' . PHP_EOL;

            $maxKeyLength = 0;
            foreach ($postData['basic'] as $k => $v) {
                if (!empty($v['required']) and $v['fieldType'] == 'link') {
                    $keyLength = mb_strlen($v['handle']);
                    $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
                }
            }
            foreach ($postData['basic'] as $k => $v) {
                if (!empty($v['required']) and $v['fieldType'] == 'link') {
                    $keyLength = mb_strlen($v['handle']);
                    $code .= BlockBuilderUtility::tab(2) . '$requiredLinkFields[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= t(\'' . addslashes($v['label']) . '\');' . PHP_EOL;
                }
            }
            $code .= PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'foreach ($requiredLinkFields as $requiredLinkFieldHandle => $requiredLinkFieldLabel) {' . PHP_EOL . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$errorCounter = 0;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$errorCounter += empty($args[$requiredLinkFieldHandle.\'_link_type\']) ? 1 : 0;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$errorCounter += ($args[$requiredLinkFieldHandle.\'_link_type\']==\'link_from_sitemap\' AND empty($args[$requiredLinkFieldHandle.\'_link_from_sitemap\'])) ? 1 : 0;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$errorCounter += ($args[$requiredLinkFieldHandle.\'_link_type\']==\'link_from_file_manager\' AND empty($args[$requiredLinkFieldHandle.\'_link_from_file_manager\'])) ? 1 : 0;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$errorCounter += ($args[$requiredLinkFieldHandle.\'_link_type\']==\'external_link\' AND empty($args[$requiredLinkFieldHandle.\'_external_link\'])) ? 1 : 0;' . PHP_EOL . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'if ($errorCounter > 0) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$error->add(t(\'Field "%s" is required.\', $requiredLinkFieldLabel));' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

        }

        if (!empty($postDataSummary['requiredEntryFields'])) {

            $code .= BlockBuilderUtility::tab(2) . '// Repeatable entries' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . 'if (isset($args[\'entry\']) AND is_array($args[\'entry\'])) {' . PHP_EOL . PHP_EOL;

            // Required fields in repeatable entries
            $code .= BlockBuilderUtility::tab(3) . '// Required fields in repeatable entries' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$requiredEntryFields = [];' . PHP_EOL;
            $maxKeyLength = 0;
            foreach ($postData['entries'] as $k => $v) {
                if (!empty($v['required']) and $v['fieldType'] != 'link') {
                    $keyLength = mb_strlen($v['handle']);
                    $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
                }
            }
            foreach ($postData['entries'] as $k => $v) {
                if (!empty($v['required']) and $v['fieldType'] != 'link') {
                    $keyLength = mb_strlen($v['handle']);
                    $code .= BlockBuilderUtility::tab(3) . '$requiredEntryFields[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= t(\'' . addslashes($v['label']) . '\');' . PHP_EOL;
                }
            }
            $code .= PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . 'foreach ($requiredEntryFields as $requiredEntryFieldHandle => $requiredEntryFieldLabel) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '$emptyEntries = [];' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . 'foreach ($args[\'entry\'] as $entry) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(5) . 'if (empty($entry[$requiredEntryFieldHandle])) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '$emptyEntries[] = $requiredEntryFieldHandle;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . 'if (is_array($emptyEntries) AND count($emptyEntries) AND in_array($requiredEntryFieldHandle, $emptyEntries)) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '$error->add(t(\'Field "%s" is required in every entry.\', $requiredEntryFieldLabel));' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '}' . PHP_EOL . PHP_EOL;

            // Required fields in repeatable entries - Links
            $code .= BlockBuilderUtility::tab(3) . '// Required fields in repeatable entries - Links' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$requiredEntryLinkFields = [];' . PHP_EOL;
            $maxKeyLength = 0;
            foreach ($postData['entries'] as $k => $v) {
                if (!empty($v['required']) and $v['fieldType'] == 'link') {
                    $keyLength = mb_strlen($v['handle']);
                    $maxKeyLength = $keyLength > $maxKeyLength ? $keyLength : $maxKeyLength;
                }
            }
            foreach ($postData['entries'] as $k => $v) {
                if (!empty($v['required']) and $v['fieldType'] == 'link') {
                    $keyLength = mb_strlen($v['handle']);
                    $code .= BlockBuilderUtility::tab(3) . '$requiredEntryLinkFields[\'' . $v['handle'] . '\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength) . '= t(\'' . addslashes($v['label']) . '\');' . PHP_EOL;
                }
            }
            $code .= PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . 'foreach ($requiredEntryLinkFields as $requiredEntryLinkFieldHandle => $requiredEntryLinkFieldLabel) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '$emptyEntries = [];' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . 'foreach ($args[\'entry\'] as $entry) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(5) . '$errorCounter = 0;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '$errorCounter += empty($entry[$requiredEntryLinkFieldHandle.\'_link_type\']) ? 1 : 0;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '$errorCounter += ($entry[$requiredEntryLinkFieldHandle.\'_link_type\']==\'link_from_sitemap\' AND empty($entry[$requiredEntryLinkFieldHandle.\'_link_from_sitemap\'])) ? 1 : 0;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '$errorCounter += ($entry[$requiredEntryLinkFieldHandle.\'_link_type\']==\'link_from_file_manager\' AND empty($entry[$requiredEntryLinkFieldHandle.\'_link_from_file_manager\'])) ? 1 : 0;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '$errorCounter += ($entry[$requiredEntryLinkFieldHandle.\'_link_type\']==\'external_link\' AND empty($entry[$requiredEntryLinkFieldHandle.\'_external_link\'])) ? 1 : 0;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(5) . 'if ($errorCounter > 0) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(6) . '$emptyEntries[] = $requiredEntryLinkFieldHandle;' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(4) . 'if (is_array($emptyEntries) AND count($emptyEntries) AND in_array($requiredEntryLinkFieldHandle, $emptyEntries)) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '$error->add(t(\'Field "%s" is required in every entry.\', $requiredEntryLinkFieldLabel));' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(2) . 'return $error;' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 12. composer()
        $code .= BlockBuilderUtility::tab(1) . 'public function composer() {' . PHP_EOL . PHP_EOL;

        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(2) . '$al = AssetList::getInstance();' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$al->register(\'javascript\', \'' . $postDataSummary['blockHandleDashed'] . '/auto-js\', \'blocks/' . $postDataSummary['blockHandle'] . '/auto.js\', [], false);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$this->requireAsset(\'javascript\', \'' . $postDataSummary['blockHandleDashed'] . '/auto-js\');' . PHP_EOL . PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(2) . '$this->edit();' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 13. scrapbook()
        $code .= BlockBuilderUtility::tab(1) . 'public function scrapbook() {' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(2) . '$this->edit();' . PHP_EOL . PHP_EOL;

        $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;


        // 14. getEntries()
        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(1) . 'private function getEntries($outputMethod = \'view\') {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$db = $this->app->make(\'database\')->connection();' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$sql = \'' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'SELECT' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '.*' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'FROM' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'WHERE' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '.bID = :bID' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'ORDER BY' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '.position ASC' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '\';' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$parameters = [];' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$parameters[\'bID\'] = $this->bID;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$entries = $db->fetchAll($sql, $parameters);' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$modifiedEntries = [];' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'foreach ($entries as $entry) {' . PHP_EOL . PHP_EOL;
            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'wysiwyg_editor') {
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '\'] = ($outputMethod==\'edit\') ? LinkAbstractor::translateFromEditMode($entry[\'' . $v['handle'] . '\']) : LinkAbstractor::translateFrom($entry[\'' . $v['handle'] . '\']);' . PHP_EOL;
                }
            }
            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'image' or $v['fieldType'] == 'link_from_file_manager') {
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '\'] = (is_object(File::getByID($entry[\'' . $v['handle'] . '\']))) ? $entry[\'' . $v['handle'] . '\'] : 0;' . PHP_EOL;
                }
                if ($v['fieldType'] == 'image') {
                    if (
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']))
                        or
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))
                        or
                        (!empty($v['imageShowAltTextField']))
                    ) {
                        $code .= BlockBuilderUtility::tab(3) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Image' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3) . '$' . $v['handle'] . 'Array = json_decode($entry[\'' . $v['handle'] . '_data\'], true);' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_show_additional_fields\']         = $' . $v['handle'] . 'Array[\'show_additional_fields\'] ?? \'\';' . PHP_EOL;
                        if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {
                            $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_override_dimensions\']            = $' . $v['handle'] . 'Array[\'override_dimensions\'] ?? \'\';' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_custom_width\']                   = $' . $v['handle'] . 'Array[\'custom_width\'] ?? \'\';' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_custom_height\']                  = $' . $v['handle'] . 'Array[\'custom_height\'] ?? \'\';' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_custom_crop\']                    = $' . $v['handle'] . 'Array[\'custom_crop\'] ?? \'\';' . PHP_EOL;
                        }
                        if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {
                            $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_override_fullscreen_dimensions\'] = $' . $v['handle'] . 'Array[\'override_fullscreen_dimensions\'] ?? \'\';' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_custom_fullscreen_width\']        = $' . $v['handle'] . 'Array[\'custom_fullscreen_width\'] ?? \'\';' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_custom_fullscreen_height\']       = $' . $v['handle'] . 'Array[\'custom_fullscreen_height\'] ?? \'\';' . PHP_EOL;
                            $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_custom_fullscreen_crop\']         = $' . $v['handle'] . 'Array[\'custom_fullscreen_crop\'] ?? \'\';' . PHP_EOL;
                        }
                    }
                }
            }
            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'date_picker') {
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_displayed\'] = (!empty($entry[\'' . $v['handle'] . '\'])) ? date(\'' . addslashes($v['datePickerPattern']) . '\', strtotime($entry[\'' . $v['handle'] . '\'])) : null;' . PHP_EOL;
                }
            }
            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'link') {
                    $code .= BlockBuilderUtility::tab(3) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Link' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$' . $v['handle'] . 'Array = json_decode($entry[\'' . $v['handle'] . '\'], true);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_link_type\']              = $' . $v['handle'] . 'Array[\'link_type\'] ?? \'\';' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_show_additional_fields\'] = $' . $v['handle'] . 'Array[\'show_additional_fields\'] ?? \'\';' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_link_from_sitemap\']      = $' . $v['handle'] . 'Array[\'link_from_sitemap\'] ?? 0;' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_link_from_file_manager\'] = (!empty($' . $v['handle'] . 'Array[\'link_from_file_manager\']) and is_object(File::getByID($' . $v['handle'] . 'Array[\'link_from_file_manager\']))) ? $' . $v['handle'] . 'Array[\'link_from_file_manager\'] : 0;' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_protocol\']               = $' . $v['handle'] . 'Array[\'protocol\'] ?? \'\';' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_external_link\']          = $' . $v['handle'] . 'Array[\'external_link\'] ?? \'\';' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_ending\']                 = $' . $v['handle'] . 'Array[\'ending\'] ?? \'\';' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_text\']                   = $' . $v['handle'] . 'Array[\'text\'] ?? \'\';' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_title\']                  = $' . $v['handle'] . 'Array[\'title\'] ?? \'\';' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3) . '$entry[\'' . $v['handle'] . '_new_window\']             = $' . $v['handle'] . 'Array[\'new_window\'] ?? 0;' . PHP_EOL;
                }
            }
            $code .= BlockBuilderUtility::tab(3) . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$modifiedEntries[] = $entry;' . PHP_EOL . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'return $modifiedEntries;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;

        }


        // 15. getEntryColumnNames()
        if (!empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(1) . ' private function getEntryColumnNames() {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$db = $this->app->make(\'database\')->connection();' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$columns = $db->getSchemaManager()->listTableColumns(\'' . $postDataSummary['blockTableNameEntries'] . '\');' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$columnNames = [];' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'foreach($columns as $column) {' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '$columnNames[] = $column->getName();' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$key1 = array_search(\'id\', $columnNames);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . 'unset($columnNames[$key1]);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$key2 = array_search(\'bID\', $columnNames);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . 'unset($columnNames[$key2]);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$key3 = array_search(\'position\', $columnNames);' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . 'unset($columnNames[$key3]);' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'return $columnNames;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;

        }


        // 16. prepareForViewLinkFromSitemap()
        if ($postDataSummary['linkUsed'] or $postDataSummary['linkUsed_entry'] or $postDataSummary['linkFromSitemapUsed'] or $postDataSummary['linkFromSitemapUsed_entry']) {

            $code .= file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . '_functions' . DIRECTORY_SEPARATOR . 'prepare_for_view_link_from_sitemap.txt');

        }


        // 17. prepareForViewLinkFromFileManager()
        if ($postDataSummary['linkUsed'] or $postDataSummary['linkUsed_entry'] or $postDataSummary['linkFromFileManagerUsed'] or $postDataSummary['linkFromFileManagerUsed_entry']) {

            $code .= file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . '_functions' . DIRECTORY_SEPARATOR . 'prepare_for_view_link_from_file_manager.txt');

        }


        // 18. prepareForViewExternalLink()
        if ($postDataSummary['linkUsed'] or $postDataSummary['linkUsed_entry'] or $postDataSummary['externalLinkUsed'] or $postDataSummary['externalLinkUsed_entry']) {

            $code .= file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . '_functions' . DIRECTORY_SEPARATOR . 'prepare_for_view_external_link.txt');

        }


        // 19. prepareForViewImage()
        if ($postDataSummary['imageUsed'] or $postDataSummary['imageUsed_entry']) {

            $code .= file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . '_functions' . DIRECTORY_SEPARATOR . 'prepare_for_view_image.txt');

        }


        // 20. prepareEntriesForView()
        if (
            !empty($postData['entries'])
            and
            (
                $postDataSummary['linkUsed_entry'] or
                $postDataSummary['linkFromSitemapUsed_entry'] or
                $postDataSummary['linkFromFileManagerUsed_entry'] or
                $postDataSummary['externalLinkUsed_entry'] or
                $postDataSummary['imageUsed_entry']
            )
        ) {


            $code .= BlockBuilderUtility::tab(1) . 'private function prepareEntriesForView($entries) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$entriesForView = [];' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'if (is_array($entries) AND count($entries)) {' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . 'foreach ($entries as $key => $entry) {' . PHP_EOL . PHP_EOL;

            foreach ($postData['entries'] as $k => $v) {

                if ($v['fieldType'] == 'link') {

                    $code .= BlockBuilderUtility::tab(4) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Link' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$modifiedEntry = [];' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . 'if ($entry[\'' . $v['handle'] . '_link_type\'] == \'link_from_sitemap\') {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '$modifiedEntry = $this->prepareForViewLinkFromSitemap(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '\'            => $entry[\'' . $v['handle'] . '_link_from_sitemap\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_ending\'     => $entry[\'' . $v['handle'] . '_ending\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_text\'       => $entry[\'' . $v['handle'] . '_text\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_title\'      => $entry[\'' . $v['handle'] . '_title\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_new_window\' => $entry[\'' . $v['handle'] . '_new_window\']' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '} elseif ($entry[\'' . $v['handle'] . '_link_type\'] == \'link_from_file_manager\') {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '$modifiedEntry = $this->prepareForViewLinkFromFileManager(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '\'            => $entry[\'' . $v['handle'] . '_link_from_file_manager\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_ending\'     => $entry[\'' . $v['handle'] . '_ending\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_text\'       => $entry[\'' . $v['handle'] . '_text\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_title\'      => $entry[\'' . $v['handle'] . '_title\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_new_window\' => $entry[\'' . $v['handle'] . '_new_window\']' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '} elseif ($entry[\'' . $v['handle'] . '_link_type\'] == \'external_link\') {' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '$modifiedEntry = $this->prepareForViewExternalLink(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '\'            => $entry[\'' . $v['handle'] . '_external_link\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_protocol\'   => $entry[\'' . $v['handle'] . '_protocol\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_ending\'     => $entry[\'' . $v['handle'] . '_ending\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_text\'       => $entry[\'' . $v['handle'] . '_text\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_title\'      => $entry[\'' . $v['handle'] . '_title\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(6) . '\'' . $v['handle'] . '_new_window\' => $entry[\'' . $v['handle'] . '_new_window\']' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entry = array_merge($entry, $modifiedEntry);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link_from_sitemap') {

                    $ending = 'false';
                    $text = 'false';
                    $title = 'false';
                    $newWindow = 'false';
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $ending = '$entry[\'' . $v['handle'] . '_ending\']';
                    }
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $text = '$entry[\'' . $v['handle'] . '_text\']';
                    }
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $title = '$entry[\'' . $v['handle'] . '_title\']';
                    }
                    if (!empty($v['linkFromSitemapShowNewWindowField'])) {
                        $newWindow = '$entry[\'' . $v['handle'] . '_new_window\']';
                    }

                    $code .= BlockBuilderUtility::tab(4) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Link from Sitemap' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$modifiedEntry = $this->prepareForViewLinkFromSitemap(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '\'            => $entry[\'' . $v['handle'] . '\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_ending\'     => ' . $ending . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_text\'       => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_title\'      => ' . $title . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_new_window\' => ' . $newWindow . '' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entry = array_merge($entry, $modifiedEntry);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link_from_file_manager') {

                    $ending = 'false';
                    $text = 'false';
                    $title = 'false';
                    $newWindow = 'false';
                    if (!empty($v['linkFromFileManagerShowEndingField'])) {
                        $ending = '$entry[\'' . $v['handle'] . '_ending\']';
                    }
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $text = '$entry[\'' . $v['handle'] . '_text\']';
                    }
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $title = '$entry[\'' . $v['handle'] . '_title\']';
                    }
                    if (!empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $newWindow = '$entry[\'' . $v['handle'] . '_new_window\']';
                    }

                    $code .= BlockBuilderUtility::tab(4) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Link from File Manager' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$modifiedEntry = $this->prepareForViewLinkFromFileManager(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '\'            => $entry[\'' . $v['handle'] . '\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_ending\'     => ' . $ending . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_text\'       => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_title\'      => ' . $title . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_new_window\' => ' . $newWindow . '' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entry = array_merge($entry, $modifiedEntry);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'external_link') {

                    $ending = 'false';
                    $text = 'false';
                    $title = 'false';
                    $newWindow = 'false';
                    if (!empty($v['externalLinkShowEndingField'])) {
                        $ending = '$entry[\'' . $v['handle'] . '_ending\']';
                    }
                    if (!empty($v['externalLinkShowTextField'])) {
                        $text = '$entry[\'' . $v['handle'] . '_text\']';
                    }
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $title = '$entry[\'' . $v['handle'] . '_title\']';
                    }
                    if (!empty($v['externalLinkShowNewWindowField'])) {
                        $newWindow = '$entry[\'' . $v['handle'] . '_new_window\']';
                    }

                    $code .= BlockBuilderUtility::tab(4) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - External Link' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$modifiedEntry = $this->prepareForViewExternalLink(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '\'            => $entry[\'' . $v['handle'] . '\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_protocol\'   => $entry[\'' . $v['handle'] . '_protocol\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_ending\'     => ' . $ending . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_text\'       => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_title\'      => ' . $title . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_new_window\' => ' . $newWindow . '' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entry = array_merge($entry, $modifiedEntry);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'image') {

                    $alt = 'false';
                    if (!empty($v['imageShowAltTextField'])) {
                        $alt = '$entry[\'' . $v['handle'] . '_alt\']';
                    }

                    $thumbnail = 'false';
                    $thumbnailWidth = 'false';
                    $thumbnailHeight = 'false';
                    $thumbnailCrop = 'false';
                    if (!empty($v['imageCreateThumbnailImage'])) {
                        $thumbnail = 'true';
                        if (!empty($v['imageThumbnailWidth'])) $thumbnailWidth = $v['imageThumbnailWidth'];
                        if (!empty($v['imageThumbnailHeight'])) $thumbnailHeight = $v['imageThumbnailHeight'];
                        if (!empty($v['imageThumbnailCrop'])) $thumbnailCrop = 'true';
                    }

                    $fullscreen = 'false';
                    $fullscreenWidth = 'false';
                    $fullscreenHeight = 'false';
                    $fullscreenCrop = 'false';
                    if (!empty($v['imageCreateFullscreenImage'])) {
                        $fullscreen = 'true';
                        if (!empty($v['imageFullscreenWidth'])) $fullscreenWidth = $v['imageFullscreenWidth'];
                        if (!empty($v['imageFullscreenHeight'])) $fullscreenHeight = $v['imageFullscreenHeight'];
                        if (!empty($v['imageFullscreenCrop'])) $fullscreenCrop = 'true';
                    }

                    $code .= BlockBuilderUtility::tab(4) . '// ' . addslashes($v['label']) . ' (' . $v['handle'] . ') - Image' . PHP_EOL;

                    // If repeatable image field is editable, then we need to add more code and made it more complicated
                    if (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable'])) {

                        $thumbnail = 'true';
                        $thumbnailWidth = '$thumbnailWidth';
                        $thumbnailHeight = '$thumbnailHeight';
                        $thumbnailCrop = '$thumbnailCrop';

                        $code .= BlockBuilderUtility::tab(4) . '$thumbnailWidth = ' . (!empty($v['imageThumbnailWidth']) ? $v['imageThumbnailWidth'] : 'false') . ';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($this->settings[\'' . $v['handle'] . '_override_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$thumbnailWidth = !empty($this->settings[\'' . $v['handle'] . '_custom_width\']) ? $this->settings[\'' . $v['handle'] . '_custom_width\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($entry[\'' . $v['handle'] . '_override_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$thumbnailWidth = !empty($entry[\'' . $v['handle'] . '_custom_width\']) ? $entry[\'' . $v['handle'] . '_custom_width\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(4) . '$thumbnailHeight = ' . (!empty($v['imageThumbnailHeight']) ? $v['imageThumbnailHeight'] : 'false') . ';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($this->settings[\'' . $v['handle'] . '_override_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$thumbnailHeight = !empty($this->settings[\'' . $v['handle'] . '_custom_height\']) ? $this->settings[\'' . $v['handle'] . '_custom_height\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($entry[\'' . $v['handle'] . '_override_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$thumbnailHeight = !empty($entry[\'' . $v['handle'] . '_custom_height\']) ? $entry[\'' . $v['handle'] . '_custom_height\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(4) . '$thumbnailCrop = ' . (!empty($v['imageThumbnailCrop']) ? 'true' : 'false') . ';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($this->settings[\'' . $v['handle'] . '_override_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$thumbnailCrop = !empty($this->settings[\'' . $v['handle'] . '_custom_crop\']) ? $this->settings[\'' . $v['handle'] . '_custom_crop\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($entry[\'' . $v['handle'] . '_override_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$thumbnailCrop = !empty($entry[\'' . $v['handle'] . '_custom_crop\']) ? $entry[\'' . $v['handle'] . '_custom_crop\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;

                    }

                    // If repeatable image field is editable, then we need to add more code and made it more complicated
                    if (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable'])) {

                        $fullscreen = 'true';
                        $fullscreenWidth = '$fullscreenWidth';
                        $fullscreenHeight = '$fullscreenHeight';
                        $fullscreenCrop = '$fullscreenCrop';

                        $code .= BlockBuilderUtility::tab(4) . '$fullscreenWidth = ' . (!empty($v['imageFullscreenWidth']) ? $v['imageFullscreenWidth'] : 'false') . ';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($this->settings[\'' . $v['handle'] . '_override_fullscreen_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$fullscreenWidth = !empty($this->settings[\'' . $v['handle'] . '_custom_fullscreen_width\']) ? $this->settings[\'' . $v['handle'] . '_custom_fullscreen_width\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($entry[\'' . $v['handle'] . '_override_fullscreen_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$fullscreenWidth = !empty($entry[\'' . $v['handle'] . '_custom_fullscreen_width\']) ? $entry[\'' . $v['handle'] . '_custom_fullscreen_width\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(4) . '$fullscreenHeight = ' . (!empty($v['imageFullscreenHeight']) ? $v['imageFullscreenHeight'] : 'false') . ';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($this->settings[\'' . $v['handle'] . '_override_fullscreen_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$fullscreenHeight = !empty($this->settings[\'' . $v['handle'] . '_custom_fullscreen_height\']) ? $this->settings[\'' . $v['handle'] . '_custom_fullscreen_height\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($entry[\'' . $v['handle'] . '_override_fullscreen_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$fullscreenHeight = !empty($entry[\'' . $v['handle'] . '_custom_fullscreen_height\']) ? $entry[\'' . $v['handle'] . '_custom_fullscreen_height\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(4) . '$fullscreenCrop = ' . (!empty($v['imageFullscreenCrop']) ? 'true' : 'false') . ';' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($this->settings[\'' . $v['handle'] . '_override_fullscreen_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$fullscreenCrop = !empty($this->settings[\'' . $v['handle'] . '_custom_fullscreen_crop\']) ? $this->settings[\'' . $v['handle'] . '_custom_fullscreen_crop\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . 'if (!empty($entry[\'' . $v['handle'] . '_override_fullscreen_dimensions\'])) {' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(5) . '$fullscreenCrop = !empty($entry[\'' . $v['handle'] . '_custom_fullscreen_crop\']) ? $entry[\'' . $v['handle'] . '_custom_fullscreen_crop\'] : false;' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(4) . '}' . PHP_EOL;

                    }

                    $code .= BlockBuilderUtility::tab(4) . '$modifiedEntry = $this->prepareForViewImage(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '\'     => $entry[\'' . $v['handle'] . '\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_alt\' => ' . $alt . '' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '], [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'thumbnail\'       => ' . $thumbnail . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'thumbnailWidth\'  => ' . $thumbnailWidth . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'thumbnailHeight\' => ' . $thumbnailHeight . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'thumbnailCrop\'   => ' . $thumbnailCrop . ',' . PHP_EOL . PHP_EOL;

                    $code .= BlockBuilderUtility::tab(5) . '\'fullscreen\'        => ' . $fullscreen . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'fullscreenWidth\'   => ' . $fullscreenWidth . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'fullscreenHeight\'  => ' . $fullscreenHeight . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'fullscreenCrop\'    => ' . $fullscreenCrop . '' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entry = array_merge($entry, $modifiedEntry);' . PHP_EOL . PHP_EOL;
                }

            }

            $code .= BlockBuilderUtility::tab(4) . '$entriesForView[] = $entry;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(3) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '}' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . 'return $entriesForView;' . PHP_EOL . PHP_EOL;

            $code .= BlockBuilderUtility::tab(1) . '}' . PHP_EOL . PHP_EOL;

        }


        // Class end
        $code .= '}';

        $fileService = new FileService();
        $fileService->append($postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename, $code);

    }

}
