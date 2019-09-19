<?php namespace BlockBuilder\FileGenerator;

use Concrete\Core\File\Service\File as FileService;
use BlockBuilder\Utility as BlockBuilderUtility;

defined('C5_EXECUTE') or die('Access Denied.');

class ControllerPhp
{

    public function generate($postDataSummary, $postData) {

        $filename = 'controller.php';
        $code = '';

        // 1. Top
        $code .= '<?php namespace Application\Block\\'.$postDataSummary['blockNamespace'].';'.PHP_EOL.PHP_EOL;

        if ( ! empty($postData['entries'])) {
            $code .= 'use Concrete\Core\Asset\AssetList;'.PHP_EOL;
        }
        $code .= 'use Concrete\Core\Block\BlockController;'.PHP_EOL;
        if ($postDataSummary['wysiwygEditorUsed'] OR $postDataSummary['wysiwygEditorUsed_entry']) {
            $code .= 'use Concrete\Core\Editor\LinkAbstractor;'.PHP_EOL;
        }

        if ( ! empty($postDataSummary['exportFileColumns'])) {
            $code .= 'use Concrete\Core\File\File;'.PHP_EOL;
        }
        if ( ! empty($postDataSummary['exportPageColumns'])) {
            $code .= 'use Concrete\Core\Page\Page;'.PHP_EOL;
        }

        $code .= PHP_EOL;

        $code .= 'defined(\'C5_EXECUTE\') or die(\'Access Denied.\');'.PHP_EOL.PHP_EOL;

        $code .= 'class Controller extends BlockController'.PHP_EOL;
        $code .= '{'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'protected $btTable = \''.$postDataSummary['blockTableName'].'\';'.PHP_EOL;
        if (empty($postData['entries'])) {
            $code .= BlockBuilderUtility::tab(1).'protected $btExportTables = [\''.$postDataSummary['blockTableName'].'\'];'.PHP_EOL;
        } else {
            $code .= BlockBuilderUtility::tab(1).'protected $btExportTables = [\''.$postDataSummary['blockTableName'].'\', \''.$postDataSummary['blockTableNameEntries'].'\'];'.PHP_EOL;
        }
        if (!empty($postDataSummary['exportPageColumns'])) {
            $code .= BlockBuilderUtility::tab(1).'protected $btExportPageColumns = [\''.implode('\', \'', $postDataSummary['exportPageColumns']).'\'];'.PHP_EOL;
        }
        if (!empty($postDataSummary['exportFileColumns'])) {
            $code .= BlockBuilderUtility::tab(1).'protected $btExportFileColumns = [\''.implode('\', \'', $postDataSummary['exportFileColumns']).'\'];'.PHP_EOL;
        }
        $code .= BlockBuilderUtility::tab(1).'protected $btInterfaceWidth = \''.$postData['blockWidth'].'\';'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'protected $btInterfaceHeight = \''.$postData['blockHeight'].'\';'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'protected $btWrapperClass = \'ccm-ui\';'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'protected $btCacheBlockRecord = true;'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'protected $btCacheBlockOutput = true;'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'protected $btCacheBlockOutputOnPost = true;'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'protected $btCacheBlockOutputForRegisteredUsers = true;'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'protected $btCacheBlockOutputLifetime = 0;'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'protected $btDefaultSet = \''.$postData['blockTypeSet'].'\'; // basic, navigation, form, express, social, multimedia'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'private $uniqueID;'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'public function getBlockTypeName() {'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(2).'return t(\''.addslashes($postData['blockName']).'\');'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'public function getBlockTypeDescription() {'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(2).'return t(\''.addslashes($postData['blockDescription']).'\');'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 2. getSearchableContent()
        if ( ! empty($postDataSummary['searchableFields']) OR ! empty($postDataSummary['searchableEntryFields'])) {

            $code .= BlockBuilderUtility::tab(1).'public function getSearchableContent() {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$content = [];'.PHP_EOL;
            foreach ($postDataSummary['searchableFields'] as $k => $v) {
                $code .= BlockBuilderUtility::tab(2) . '$content[] = $this->'.$v.';'.PHP_EOL;
            }

            if (count($postDataSummary['searchableEntryFields'])) {
                $code .= PHP_EOL;
                $code .= BlockBuilderUtility::tab(2).'$entries = $this->getEntries(\'edit\');'.PHP_EOL;
                $code .= BlockBuilderUtility::tab(2).'foreach ($entries as $entry) {'.PHP_EOL;
                foreach ($postDataSummary['searchableEntryFields'] as $k => $v) {
                    $code .= BlockBuilderUtility::tab(3).'$content[] = $entry[\''.$v.'\'];'.PHP_EOL;
                }
                $code .= BlockBuilderUtility::tab(2).'}'.PHP_EOL;
            }

            $code .= PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'return implode(\' \', $content);'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;

        }


        // 3. on_start
        $code .= BlockBuilderUtility::tab(1).'public function on_start() {'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(2).'// Unique identifier'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(2).'$this->uniqueID = $this->app->make(\'helper/validation/identifier\')->getString(18);'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(2).'$this->set(\'uniqueID\', $this->uniqueID);'.PHP_EOL.PHP_EOL;

        if (!empty($postData['basic'])) {

            foreach ($postData['basic'] as $k => $v) {

                if ($v['fieldType']=='select_field') {

                    $code .= BlockBuilderUtility::tab(2).'// '.$v['label'].' ('.$v['handle'].') options'.PHP_EOL;

                    $maxKeyLength = 0;
                    $tempOptions = [];
                    if (!empty($v['selectOptions'])) {
                        $options = explode('<br />', nl2br($v['selectOptions']));
                        if (is_array($options)) {
                            $i = 0;
                            foreach ($options as $k2 => $v2) {
                                $i++;
                                $option = explode('::', $v2);
                                $optionKey   = !empty($option[1]) ? addslashes(trim($option[0])) : $i;
                                $optionValue = !empty($option[1]) ? addslashes(trim($option[1])) : addslashes(trim($option[0]));

                                $keyLength = mb_strlen($optionKey);
                                $maxKeyLength = $keyLength>$maxKeyLength ? $keyLength : $maxKeyLength;
                                $tempOptions[] = ['key'=>$optionKey, 'value'=>$optionValue, 'keyLength'=>$keyLength];
                            }
                        }
                    }

                    // Generate actual code
                    $code .= BlockBuilderUtility::tab(2).'$'.$v['handle'].'_options '.BlockBuilderUtility::arrayGap($maxKeyLength+4).'= [];'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'$'.$v['handle'].'_options[] '.BlockBuilderUtility::arrayGap($maxKeyLength+2).'= \'----\';'.PHP_EOL;
                    foreach ($tempOptions as $tempOption) {
                        $code .= BlockBuilderUtility::tab(2) . '$' . $v['handle'] . '_options[\''.$tempOption['key'].'\'] ';
                        $code .= BlockBuilderUtility::arrayGap($maxKeyLength, $tempOption['keyLength']);
                        $code .= '= t(\''.$tempOption['value'].'\');'.PHP_EOL;
                    }

                    $code .= PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'$this->set(\''.$v['handle'].'_options\', $'.$v['handle'].'_options);'.PHP_EOL.PHP_EOL;

                }

            }

        }

        if (!empty($postData['entries'])) {

            foreach ($postData['entries'] as $k => $v) {

                if ($v['fieldType']=='select_field') {

                    $code .= BlockBuilderUtility::tab(2).'// Entry / '.$v['label'].' ('.$v['handle'].') options'.PHP_EOL;

                    $maxKeyLength = 0;
                    $tempOptions = [];
                    if (!empty($v['selectOptions'])) {
                        $options = explode('<br />', nl2br($v['selectOptions']));
                        if (is_array($options)) {
                            $i = 0;
                            foreach ($options as $k2 => $v2) {
                                $i++;
                                $option = explode('::', $v2);
                                $optionKey   = !empty($option[1]) ? addslashes(trim($option[0])) : $i;
                                $optionValue = !empty($option[1]) ? addslashes(trim($option[1])) : addslashes(trim($option[0]));

                                $keyLength = mb_strlen($optionKey);
                                $maxKeyLength = $keyLength>$maxKeyLength ? $keyLength : $maxKeyLength;
                                $tempOptions[] = ['key'=>$optionKey, 'value'=>$optionValue, 'keyLength'=>$keyLength];
                            }
                        }
                    }

                    // Generate actual code
                    $code .= BlockBuilderUtility::tab(2).'$entry_'.$v['handle'].'_options '.BlockBuilderUtility::arrayGap($maxKeyLength+4).'= [];'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'$entry_'.$v['handle'].'_options[] '.BlockBuilderUtility::arrayGap($maxKeyLength+2).'= \'----\';'.PHP_EOL;
                    foreach ($tempOptions as $tempOption) {
                        $code .= BlockBuilderUtility::tab(2) . '$entry_' . $v['handle'] . '_options[\''.$tempOption['key'].'\'] ';
                        $code .= BlockBuilderUtility::arrayGap($maxKeyLength, $tempOption['keyLength']);
                        $code .= '= t(\''.$tempOption['value'].'\');'.PHP_EOL;
                    }

                    $code .= PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'$this->set(\'entry_'.$v['handle'].'_options\', $entry_'.$v['handle'].'_options);'.PHP_EOL.PHP_EOL;

                }

            }

        }

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 4. add()
        $code .= BlockBuilderUtility::tab(1).'public function add() {'.PHP_EOL.PHP_EOL;
        $code .= BlockBuilderUtility::tab(2).'$this->addEdit();'.PHP_EOL.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 5. edit()
        $code .= BlockBuilderUtility::tab(1).'public function edit() {'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(2).'$this->addEdit();'.PHP_EOL.PHP_EOL;

        if ($postDataSummary['wysiwygEditorUsed']) {
            $code .= BlockBuilderUtility::tab(2) . '// Wysiwyg editors'.PHP_EOL;
            if ( ! empty($postData['basic'])) {
                foreach ($postData['basic'] as $k => $v) {
                    if ($v['fieldType']=='wysiwyg_editor') {
                        $code .= BlockBuilderUtility::tab(2) . '$this->set(\''.$v['handle'].'\', LinkAbstractor::translateFromEditMode($this->'.$v['handle'].'));'.PHP_EOL;
                    }
                }
            }
            $code .= PHP_EOL;
        }

        if ( ! empty($postData['entries'])) {
            $code .= BlockBuilderUtility::tab(2).'// Get entries'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$entries = $this->getEntries(\'edit\');'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$this->set(\'entries\', $entries);'.PHP_EOL.PHP_EOL;
        }

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 6. addEdit()
        $code .= BlockBuilderUtility::tab(1).'public function addEdit() {'.PHP_EOL.PHP_EOL;

        if ( ! empty($postData['entries'])) {

            if ($postDataSummary['linkFromSitemapUsed_entry'] OR$postDataSummary['linkFromFileManagerUsed_entry'] OR $postDataSummary['imageUsed_entry']) {
                $code .= BlockBuilderUtility::tab(2) . '// Load assets for repeatable entries' . PHP_EOL;
            }
            if ($postDataSummary['linkFromSitemapUsed_entry']) {
                $code .= BlockBuilderUtility::tab(2) . '$this->requireAsset(\'core/sitemap\');' . PHP_EOL;
            }
            if ($postDataSummary['linkFromFileManagerUsed_entry'] OR $postDataSummary['imageUsed_entry']) {
                $code .= BlockBuilderUtility::tab(2) . '$this->requireAsset(\'core/file-manager\');' . PHP_EOL;
            }
            if ($postDataSummary['linkFromSitemapUsed_entry'] OR $postDataSummary['linkFromFileManagerUsed_entry'] OR $postDataSummary['imageUsed_entry']) {
                $code .= PHP_EOL;
            }

            $code .= BlockBuilderUtility::tab(2).'// Get entry column names'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$entryColumnNames = $this->getEntryColumnNames();'.PHP_EOL;

            if ($postDataSummary['datePickerUsed_entry']) {
                $code .= PHP_EOL;
                $code .= BlockBuilderUtility::tab(2).'// Fields that don\'t exist in database, but are required in repeatable entry'.PHP_EOL;
                foreach ($postData['entries'] as $k => $v) {
                    if ($v['fieldType'] == 'date_picker') {
                        $code .= BlockBuilderUtility::tab(2) . '$entryColumnNames[] = \'' . $v['handle'] . 'Displayed\';' . PHP_EOL;
                    }
                }
                $code .= PHP_EOL;
            }

            $code .= BlockBuilderUtility::tab(2).'$this->set(\'entryColumnNames\', $entryColumnNames);'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'// Load form.css'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$al = AssetList::getInstance();'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$al->register(\'css\', \''.$postDataSummary['blockHandleDashed'].'/form\', \'blocks/'.$postDataSummary['blockHandle'].'/css_files/form.css\', [], false);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$this->requireAsset(\'css\', \''.$postDataSummary['blockHandleDashed'].'/form\');'.PHP_EOL.PHP_EOL;

        }

        if ($postDataSummary['externalLinkUsed'] OR $postDataSummary['externalLinkUsed_entry']) {
            $code .= BlockBuilderUtility::tab(2).'// External link protocols' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$externalLinkProtocols = [' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'\'http://\'  => \'http://\',' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'\'https://\' => \'https://\',' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'\'other\'    => \'\'' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'];' . PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$this->set(\'externalLinkProtocols\', $externalLinkProtocols);' . PHP_EOL . PHP_EOL;
        }

        if ($postDataSummary['htmlEditorUsed'] OR $postDataSummary['htmlEditorUsed_entry']) {
            $code .= BlockBuilderUtility::tab(2).'// Load html editor'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$this->requireAsset(\'ace\');'.PHP_EOL.PHP_EOL;
        }

        $code .= BlockBuilderUtility::tab(2).'// Make $app available in view'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(2).'$this->set(\'app\', $this->app);'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 7. view()
        $code .= BlockBuilderUtility::tab(1).'public function view() {'.PHP_EOL.PHP_EOL;

        if ($postDataSummary['wysiwygEditorUsed']) {
            $code .= BlockBuilderUtility::tab(2).'// Wysiwyg editors'.PHP_EOL;
            if ( ! empty($postData['basic'])) {
                foreach ($postData['basic'] as $k => $v) {
                    if ($v['fieldType']=='wysiwyg_editor') {
                        $code .= BlockBuilderUtility::tab(2).'$this->set(\''.$v['handle'].'\', LinkAbstractor::translateFrom($this->'.$v['handle'].'));'.PHP_EOL;
                    }
                }
            }
            $code .= PHP_EOL;
        }

        if ( ! empty($postData['basic'])) {

            if ($postDataSummary['linkFromSitemapUsed'] OR $postDataSummary['linkFromFileManagerUsed'] OR $postDataSummary['externalLinkUsed'] OR $postDataSummary['imageUsed']) {
                $code .= BlockBuilderUtility::tab(2).'// Prepare fields for view'.PHP_EOL;
            }

            foreach ($postData['basic'] as $k => $v) {

                if ($v['fieldType']=='link_from_sitemap') {

                    $ending = 'false';
                    $text   = 'false';
                    $title  = 'false';
                    if ( ! empty($v['linkFromSitemapShowEndingField']) ) {
                        $ending = '$this->'.$v['handle'].'_ending';
                    }
                    if ( ! empty($v['linkFromSitemapShowTextField']) ) {
                        $text = '$this->'.$v['handle'].'_text';
                    }
                    if ( ! empty($v['linkFromSitemapShowTitleField']) ) {
                        $title = '$this->'.$v['handle'].'_title';
                    }

                    $code .= BlockBuilderUtility::tab(2).'$this->prepareForViewLinkFromSitemap(\'view\', ['.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'\'        => $this->'.$v['handle'].','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_ending\' => '.$ending.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_text\'   => '.$text.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_title\'  => '.$title.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).']);'.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='link_from_file_manager') {

                    $text  = 'false';
                    $title = 'false';
                    if ( ! empty($v['linkFromFileManagerShowTextField']) ) {
                        $text = '$this->'.$v['handle'].'_text';
                    }
                    if ( ! empty($v['linkFromFileManagerShowTitleField']) ) {
                        $title = '$this->'.$v['handle'].'_title';
                    }

                    $code .= BlockBuilderUtility::tab(2).'$this->prepareForViewLinkFromFileManager(\'view\', ['.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'\'        => $this->'.$v['handle'].','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_text\'   => '.$text.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_title\'  => '.$title.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).']);'.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='external_link') {

                    $text  = 'false';
                    $title = 'false';
                    if ( ! empty($v['externalLinkShowTextField']) ) {
                        $text = '$this->'.$v['handle'].'_text';
                    }
                    if ( ! empty($v['externalLinkShowTitleField']) ) {
                        $title = '$this->'.$v['handle'].'_title';
                    }

                    $code .= BlockBuilderUtility::tab(2).'$this->prepareForViewExternalLink(\'view\', ['.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'\'          => $this->'.$v['handle'].','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_protocol\' => $this->'.$v['handle'].'_protocol'.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_text\'     => '.$text.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_title\'    => '.$title.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).']);'.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='image') {

                    $alt = 'false';
                    if ( ! empty($v['imageShowAltTextField']) ) {
                        $alt = '$this->'.$v['handle'].'_alt';
                    }

                    $thumbnail       = 'false';
                    $thumbnailWidth  = 'false';
                    $thumbnailHeight = 'false';
                    $thumbnailCrop   = 'false';
                    if ( ! empty($v['imageCreateThumbnailImage']) ) {
                        $thumbnail = 'true';
                        if ( ! empty($v['imageThumbnailWidth']) ) {
                            $thumbnailWidth = $v['imageThumbnailWidth'];
                        }
                        if ( ! empty($v['imageThumbnailHeight']) ) {
                            $thumbnailHeight = $v['imageThumbnailHeight'];
                        }
                        if ( ! empty($v['imageThumbnailCrop']) ) {
                            $thumbnailCrop = 'true';
                        }
                    }

                    $fullscreen       = 'false';
                    $fullscreenWidth  = 'false';
                    $fullscreenHeight = 'false';
                    $fullscreenCrop   = 'false';
                    if ( ! empty($v['imageCreateFullscreenImage']) ) {
                        $fullscreen = 'true';
                        if ( ! empty($v['imageFullscreenWidth']) ) {
                            $fullscreenWidth = $v['imageFullscreenWidth'];
                        }
                        if ( ! empty($v['imageFullscreenHeight']) ) {
                            $fullscreenHeight = $v['imageFullscreenHeight'];
                        }
                        if ( ! empty($v['imageFullscreenCrop']) ) {
                            $fullscreenCrop = 'true';
                        }
                    }

                    $code .= BlockBuilderUtility::tab(2).'$this->prepareForViewImage(\'view\', ['.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'\'     => $this->'.$v['handle'].','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\''.$v['handle'].'_alt\' => '.$alt.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'], ['.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\'thumbnail\'        => '.$thumbnail.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\'thumbnailWidth\'   => '.$thumbnailWidth.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\'thumbnailHeight\'  => '.$thumbnailHeight.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\'thumbnailCrop\'    => '.$thumbnailCrop.','.PHP_EOL.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'\'fullscreen\'       => '.$fullscreen.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\'fullscreenWidth\'  => '.$fullscreenWidth.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\'fullscreenHeight\' => '.$fullscreenHeight.','.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'\'fullscreenCrop\'   => '.$fullscreenCrop.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).']);'.PHP_EOL.PHP_EOL;

                }

            }

        }

        if ( ! empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(2) . '// Get entries'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$entries = $this->getEntries();'.PHP_EOL;

            if (
                $postDataSummary['linkFromSitemapUsed_entry'] OR
                $postDataSummary['linkFromFileManagerUsed_entry'] OR
                $postDataSummary['externalLinkUsed_entry'] OR
                $postDataSummary['imageUsed_entry']
            ) {
                $code .= BlockBuilderUtility::tab(2) . '$entries = $this->prepareEntriesForView($entries);' . PHP_EOL ;
            }

            $code .= BlockBuilderUtility::tab(2) . '$this->set(\'entries\', $entries);' . PHP_EOL . PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 8. save()
        $code .= BlockBuilderUtility::tab(1).'public function save($args) {'.PHP_EOL;

        if ( ! empty($postData['basic'])) {

            $code .= PHP_EOL.BlockBuilderUtility::tab(2).'// Basic fields'.PHP_EOL;

            $maxKeyLength = 0;

            foreach ($postData['basic'] as $k => $v) {

                $keyLength = mb_strlen($v['handle']);
                $additionalSpaces = 0;

                // Lower length words first
                if ($v['fieldType']=='link_from_sitemap') {
                    ! empty($v['linkFromSitemapShowTextField']) ? $additionalSpaces=5 : false;
                    ! empty($v['linkFromSitemapShowTitleField']) ? $additionalSpaces=6 : false;
                    ! empty($v['linkFromSitemapShowEndingField']) ? $additionalSpaces=7 : false;
                } elseif ($v['fieldType']=='link_from_file_manager') {
                    ! empty($v['linkFromFileManagerShowTextField']) ? $additionalSpaces=5 : false;
                    ! empty($v['linkFromFileManagerShowTitleField']) ? $additionalSpaces=6 : false;
                } else if ($v['fieldType']=='external_link') {
                    $additionalSpaces = 9; // longest string '_protocol' is always used
                } elseif ($v['fieldType']=='image') {
                    ! empty($v['imageShowAltTextField']) ? $additionalSpaces=4 : false;
                }

                $keyLength += $additionalSpaces;

                $maxKeyLength = $keyLength>$maxKeyLength ? $keyLength : $maxKeyLength;
            }

            foreach ($postData['basic'] as $k => $v) {

                $keyLength = mb_strlen($v['handle']);

                // Basic fields
                if ($v['fieldType']=='wysiwyg_editor') {
                    $code .= BlockBuilderUtility::tab(2).'$args[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= LinkAbstractor::translateTo($args[\''.$v['handle'].'\']);'.PHP_EOL;
                } else if ($v['fieldType']=='html_editor') {
                    $code .= BlockBuilderUtility::tab(2).'$args[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= $args[\''.$v['handle'].'\'];'.PHP_EOL;
                } else if (in_array($v['fieldType'], ['link_from_sitemap', 'link_from_file_manager', 'image'])) {
                    $code .= BlockBuilderUtility::tab(2).'$args[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= intval($args[\''.$v['handle'].'\']);'.PHP_EOL;
                } else if ($v['fieldType']=='date_picker') {
                    $code .= BlockBuilderUtility::tab(2).'$args[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= !empty($args[\''.$v['handle'].'\']) ? $this->app->make(\'helper/form/date_time\')->translate(\''.$v['handle'].'\') : null;'.PHP_EOL;
                } else {
                    $code .= BlockBuilderUtility::tab(2).'$args[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= trim($args[\''.$v['handle'].'\']);'.PHP_EOL;
                }

                // Additional fields
                if ($v['fieldType']=='link_from_sitemap') {
                    if ( ! empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_ending\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 7) . '= trim($args[\'' . $v['handle'] . '_ending\']);' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= trim($args[\'' . $v['handle'] . '_text\']);' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_title\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 6) . '= trim($args[\'' . $v['handle'] . '_title\']);' . PHP_EOL;
                    }
                }

                if ($v['fieldType']=='link_from_file_manager') {
                    if ( ! empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= trim($args[\'' . $v['handle'] . '_text\']);' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\''.$v['handle'].'_title\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+6).'= trim($args[\''.$v['handle'].'_title\']);'.PHP_EOL;
                    }
                }

                if ($v['fieldType']=='external_link') {
                    $code .= BlockBuilderUtility::tab(2) . '$args[\''.$v['handle'].'_protocol\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+9).'= trim($args[\''.$v['handle'].'_protocol\']);'.PHP_EOL;
                    if ( ! empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\''.$v['handle'].'_text\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+5).'= trim($args[\''.$v['handle'].'_text\']);'.PHP_EOL;
                    }
                    if ( ! empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\''.$v['handle'].'_title\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+6).'= trim($args[\''.$v['handle'].'_title\']);'.PHP_EOL;
                    }
                }

                if ($v['fieldType']=='image') {
                    if ( ! empty($v['imageShowAltTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '$args[\''.$v['handle'].'_alt\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+4).'= trim($args[\''.$v['handle'].'_alt\']);'.PHP_EOL;
                    }
                }
            }
        }
        $code .= PHP_EOL;

        $code .= BlockBuilderUtility::tab(2).'parent::save($args);'.PHP_EOL.PHP_EOL;

        if ( ! empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(2).'$db = $this->app->make(\'database\')->connection();'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'// Delete existing entries of current block\'s version'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$db->delete(\''.$postDataSummary['blockTableNameEntries'].'\', [\'bID\' => $this->bID]);'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'if (count($args[\'entry\'])) {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'$i = 1;'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'foreach ($args[\'entry\'] as $entry) {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(4).'// Prepare data for insert'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).'$data = [];'.PHP_EOL;

            $maxKeyLength = 8;
            foreach ($postData['entries'] as $k => $v) {

                $keyLength = mb_strlen($v['handle']);
                $additionalSpaces = 0;

                // Lower length words first
                if ($v['fieldType']=='link_from_sitemap') {
                    ! empty($v['linkFromSitemapShowTextField']) ? $additionalSpaces=5 : false;
                    ! empty($v['linkFromSitemapShowTitleField']) ? $additionalSpaces=6 : false;
                    ! empty($v['linkFromSitemapShowEndingField']) ? $additionalSpaces=7 : false;
                } elseif ($v['fieldType']=='link_from_file_manager') {
                    ! empty($v['linkFromFileManagerShowTextField']) ? $additionalSpaces=5 : false;
                    ! empty($v['linkFromFileManagerShowTitleField']) ? $additionalSpaces=6 : false;
                } else if ($v['fieldType']=='external_link') {
                    $additionalSpaces = 9; // longest string '_protocol' is always used
                } elseif ($v['fieldType']=='image') {
                    ! empty($v['imageShowAltTextField']) ? $additionalSpaces=4 : false;
                }

                $keyLength += $additionalSpaces;

                $maxKeyLength = $keyLength>$maxKeyLength ? $keyLength : $maxKeyLength;
            }

            $code .= BlockBuilderUtility::tab(4).'$data[\'position\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, 8).'= $i;'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).'$data[\'bID\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, 3).'= $this->bID;'.PHP_EOL;

            foreach ($postData['entries'] as $k => $v) {

                $keyLength = mb_strlen($v['handle']);

                // Basic fields
                if ($v['fieldType']=='wysiwyg_editor') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= LinkAbstractor::translateTo($entry[\''.$v['handle'].'\']);'.PHP_EOL;
                } else if ($v['fieldType']=='html_editor') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= $entry[\''.$v['handle'].'\'];'.PHP_EOL;
                } else if (in_array($v['fieldType'], ['link_from_sitemap', 'link_from_file_manager', 'image'])) {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= intval($entry[\''.$v['handle'].'\']);'.PHP_EOL;
                } else if ($v['fieldType']=='date_picker') {
                    $code .= BlockBuilderUtility::tab(4).'$data[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= !empty($entry[\''.$v['handle'].'\']) ? $this->app->make(\'helper/form/date_time\')->translate(\''.$v['handle'].'\', $entry) : null;'.PHP_EOL;
                } else {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= trim($entry[\''.$v['handle'].'\']);'.PHP_EOL;
                }

                // Additional fields
                if ($v['fieldType']=='link_from_sitemap') {
                    if ( ! empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_ending\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 7) . '= trim($entry[\'' . $v['handle'] . '_ending\']);' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= trim($entry[\'' . $v['handle'] . '_text\']);' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_title\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 6) . '= trim($entry[\'' . $v['handle'] . '_title\']);' . PHP_EOL;
                    }
                }

                if ($v['fieldType']=='link_from_file_manager') {
                    if ( ! empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\'' . $v['handle'] . '_text\'] ' . BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength + 5) . '= trim($entry[\'' . $v['handle'] . '_text\']);' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'_title\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+6).'= trim($entry[\''.$v['handle'].'_title\']);'.PHP_EOL;
                    }
                }

                if ($v['fieldType']=='external_link') {
                    $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'_protocol\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+9).'= trim($entry[\''.$v['handle'].'_protocol\']);'.PHP_EOL;
                    if ( ! empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'_text\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+5).'= trim($entry[\''.$v['handle'].'_text\']);'.PHP_EOL;
                    }
                    if ( ! empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'_title\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+6).'= trim($entry[\''.$v['handle'].'_title\']);'.PHP_EOL;
                    }
                }

                if ($v['fieldType']=='image') {
                    if ( ! empty($v['imageShowAltTextField'])) {
                        $code .= BlockBuilderUtility::tab(4) . '$data[\''.$v['handle'].'_alt\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength+4).'= trim($entry[\''.$v['handle'].'_alt\']);'.PHP_EOL;
                    }
                }
            }

            $code .= PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).'$db->insert(\''.$postDataSummary['blockTableNameEntries'].'\', $data);'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(4).'$i++;'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'}'.PHP_EOL.PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 9. duplicate()
        $code .= BlockBuilderUtility::tab(1).'public function duplicate($newBlockID) {'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(2).'parent::duplicate($newBlockID);'.PHP_EOL.PHP_EOL;

        if ( ! empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(2).'$db = $this->app->make(\'database\')->connection();'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '// Get latest entry...'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$sql = \''.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'SELECT'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '.*'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'FROM'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . ''.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'WHERE'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '' . $postDataSummary['blockTableNameEntries'] . '.bID = :bID'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '\';'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$parameters = [];'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '$parameters[\'bID\'] = $this->bID;'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '$entries = $db->fetchAll($sql, $parameters);'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2) . '// ... and copy it'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . 'if (is_array($entries) AND count($entries)) {'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . 'foreach ($entries as $entry) {'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$data = [];'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . 'foreach ($entry as $columnName => $value) {'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(5) . '$data[$columnName] = $value;'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '}'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . 'unset($data[\'id\']);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$data[\'bID\'] = $newBlockID;'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4) . '$db->insert(\'' . $postDataSummary['blockTableNameEntries'] . '\', $data);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3) . '}'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2) . '}'.PHP_EOL.PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 10. delete()
        $code .= BlockBuilderUtility::tab(1).'public function delete() {'.PHP_EOL.PHP_EOL;
        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 11. validate()
        $code .= BlockBuilderUtility::tab(1).'public function validate($args) {'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(2).'$error = $this->app->make(\'helper/validation/error\');'.PHP_EOL.PHP_EOL;

        if ( ! empty($postDataSummary['requiredFields'])) {

            $code .= BlockBuilderUtility::tab(2).'// Required fields'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$requiredFields = [];'.PHP_EOL;
            $maxKeyLength = 0;
            foreach ($postData['basic'] as $k => $v) {
                if ( ! empty($v['required'])) {
                    $keyLength = mb_strlen($v['handle']);
                    $maxKeyLength = $keyLength>$maxKeyLength ? $keyLength : $maxKeyLength;
                }
            }
            foreach ($postData['basic'] as $k => $v) {
                if ( ! empty($v['required'])) {
                    $keyLength = mb_strlen($v['handle']);
                    $code .= BlockBuilderUtility::tab(2) . '$requiredFields[\'' . $v['handle'] . '\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= t(\'' . addslashes($v['label']) . '\');'.PHP_EOL;
                }
            }
            $code .= PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'foreach ($requiredFields as $requiredFieldHandle => $requiredFieldLabel) {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'if (empty($args[$requiredFieldHandle])) {'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).'$error->add(t(\'Field "%s" is required.\', $requiredFieldLabel));'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'}'.PHP_EOL.PHP_EOL;

        }

        if ( ! empty($postDataSummary['requiredEntryFields'])) {

            $code .= BlockBuilderUtility::tab(2).'// Required fields in repeatable entries'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'if (isset($args[\'entry\']) AND is_array($args[\'entry\'])) {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'$requiredEntryFields = [];'.PHP_EOL;
            $maxKeyLength = 0;
            foreach ($postData['entries'] as $k => $v) {
                if ( ! empty($v['required'])) {
                    $keyLength = mb_strlen($v['handle']);
                    $maxKeyLength = $keyLength>$maxKeyLength ? $keyLength : $maxKeyLength;
                    $code .= BlockBuilderUtility::tab(3).'$requiredEntryFields[\''.$v['handle'].'\'] '.BlockBuilderUtility::arrayGap($maxKeyLength, $keyLength).'= t(\''.addslashes($v['label']).'\');'.PHP_EOL;
                }
            }
            $code .= PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'foreach ($requiredEntryFields as $requiredEntryFieldHandle => $requiredEntryFieldLabel) {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(4).'$emptyEntries = [];'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(4).'foreach ($args[\'entry\'] as $entry) {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(5).'if (empty($entry[$requiredEntryFieldHandle])) {'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(6).'$emptyEntries[] = $requiredEntryFieldHandle;'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(5).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(4).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(4).'if (count($emptyEntries) AND in_array($requiredEntryFieldHandle, $emptyEntries)) {'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(5).'$error->add(t(\'Field "%s" is required in every entry.\', $requiredEntryFieldLabel));'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'}'.PHP_EOL.PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(2).'return $error;'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 12. composer()
        $code .= BlockBuilderUtility::tab(1).'public function composer() {'.PHP_EOL.PHP_EOL;

        if ( ! empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(2).'$al = AssetList::getInstance();'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$al->register(\'javascript\', \''.$postDataSummary['blockHandleDashed'].'/auto-js\', \'blocks/'.$postDataSummary['blockHandle'].'/auto.js\', [], false);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$this->requireAsset(\'javascript\', \''.$postDataSummary['blockHandleDashed'].'/auto-js\');'.PHP_EOL.PHP_EOL;

        }

        $code .= BlockBuilderUtility::tab(2).'$this->edit();'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 13. scrapbook()
        $code .= BlockBuilderUtility::tab(1).'public function scrapbook() {'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(2).'$this->edit();'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;


        // 14. getEntries()
        if ( ! empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(1).'private function getEntries($outputMethod = \'view\') {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$db = $this->app->make(\'database\')->connection();'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$sql = \''.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'SELECT'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).''.$postDataSummary['blockTableNameEntries'].'.*'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'FROM'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).''.$postDataSummary['blockTableNameEntries'].''.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'WHERE'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).''.$postDataSummary['blockTableNameEntries'].'.bID = :bID'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'ORDER BY'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(4).''.$postDataSummary['blockTableNameEntries'].'.position ASC'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'\';'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$parameters = [];'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$parameters[\'bID\'] = $this->bID;'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$entries = $db->fetchAll($sql, $parameters);'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$modifiedEntries = [];'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'foreach ($entries as $entry) {'.PHP_EOL;
            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'wysiwyg_editor') {
                    $code .= BlockBuilderUtility::tab(3).'$entry[\''.$v['handle'].'\'] = ($outputMethod==\'edit\') ? LinkAbstractor::translateFromEditMode($entry[\''.$v['handle'].'\']) : LinkAbstractor::translateFrom($entry[\''.$v['handle'].'\']);'.PHP_EOL;
                }
            }
            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'image' OR $v['fieldType'] == 'link_from_file_manager') {
                    $code .= BlockBuilderUtility::tab(3).'$entry[\''.$v['handle'].'\'] = (is_object(File::getByID($entry[\''.$v['handle'].'\']))) ? $entry[\''.$v['handle'].'\'] : 0;'.PHP_EOL;
                }
            }
            foreach ($postData['entries'] as $k => $v) {
                if ($v['fieldType'] == 'date_picker') {
                    $code .= BlockBuilderUtility::tab(3).'$entry[\''.$v['handle'].'Displayed\'] = (!empty($entry[\''.$v['handle'].'\'])) ? date(\''.$v['datePickerPattern'].'\', strtotime($entry[\''.$v['handle'].'\'])) : null;'.PHP_EOL;
                }
            }
            $code .= BlockBuilderUtility::tab(3).'$modifiedEntries[] = $entry;'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'return $modifiedEntries;'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;

        }


        // 15. getEntryColumnNames()
        if ( ! empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(1).' private function getEntryColumnNames() {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$db = $this->app->make(\'database\')->connection();'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$columns = $db->getSchemaManager()->listTableColumns(\''.$postDataSummary['blockTableNameEntries'].'\');'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$columnNames = [];'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'foreach($columns as $column) {'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'$columnNames[] = $column->getName();'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$key1 = array_search(\'id\', $columnNames);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'unset($columnNames[$key1]);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$key2 = array_search(\'bID\', $columnNames);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'unset($columnNames[$key2]);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'$key3 = array_search(\'position\', $columnNames);'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'unset($columnNames[$key3]);'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'return $columnNames;'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;

        }


        // 16. prepareForViewLinkFromSitemap()
        if ($postDataSummary['linkFromSitemapUsed'] OR $postDataSummary['linkFromSitemapUsed_entry']) {

            $code .= file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . '_functions' . DIRECTORY_SEPARATOR . 'prepare_for_view_link_from_sitemap.txt');

        }


        // 17. prepareForViewLinkFromFileManager()
        if ($postDataSummary['linkFromFileManagerUsed'] OR $postDataSummary['linkFromFileManagerUsed_entry']) {

            $code .= file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . '_functions' . DIRECTORY_SEPARATOR . 'prepare_for_view_link_from_file_manager.txt');

        }


        // 18. prepareForViewExternalLink()
        if ($postDataSummary['externalLinkUsed'] OR $postDataSummary['externalLinkUsed_entry']) {

            $code .= file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . '_functions' . DIRECTORY_SEPARATOR . 'prepare_for_view_external_link.txt');

        }


        // 19. prepareForViewImage()
        if ($postDataSummary['imageUsed'] OR $postDataSummary['imageUsed_entry']) {

            $code .= file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . '_functions' . DIRECTORY_SEPARATOR . 'prepare_for_view_image.txt');

        }


        // 20. prepareEntriesForView()
        if (
            ! empty($postData['entries'])
            AND
            (
                $postDataSummary['linkFromSitemapUsed_entry'] OR
                $postDataSummary['linkFromFileManagerUsed_entry'] OR
                $postDataSummary['externalLinkUsed_entry'] OR
                $postDataSummary['imageUsed_entry']
            )
        ) {


            $code .= BlockBuilderUtility::tab(1).'private function prepareEntriesForView($entries) {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'$entriesForView = [];'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'if (count($entries)) {'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'foreach ($entries as $key => $entry) {'.PHP_EOL.PHP_EOL;

            foreach ($postData['entries'] as $k => $v) {

                if ($v['fieldType'] == 'link_from_sitemap') {

                    $ending = 'false';
                    $text = 'false';
                    $title = 'false';
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $ending = '$entry[\'' . $v['handle'] . '_ending\']';
                    }
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $text = '$entry[\'' . $v['handle'] . '_text\']';
                    }
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $title = '$entry[\'' . $v['handle'] . '_title\']';
                    }

                    $code .= BlockBuilderUtility::tab(4) . '$modifiedEntry = $this->prepareForViewLinkFromSitemap(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '\'        => $entry[\'' . $v['handle'] . '\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_ending\' => ' . $ending . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_text\'   => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_title\'  => ' . $title . '' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entry = array_merge($entry, $modifiedEntry);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'link_from_file_manager') {

                    $text = 'false';
                    $title = 'false';
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $text = '$entry[\'' . $v['handle'] . '_text\']';
                    }
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $title = '$entry[\'' . $v['handle'] . '_title\']';
                    }

                    $code .= BlockBuilderUtility::tab(4) . '$modifiedEntry = $this->prepareForViewLinkFromFileManager(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '\'        => $entry[\'' . $v['handle'] . '\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_text\'   => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_title\'  => ' . $title . '' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . ']);' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(4) . '$entry = array_merge($entry, $modifiedEntry);' . PHP_EOL . PHP_EOL;

                }

                if ($v['fieldType'] == 'external_link') {

                    $text = 'false';
                    $title = 'false';
                    if (!empty($v['externalLinkShowTextField'])) {
                        $text = '$entry[\'' . $v['handle'] . '_text\']';
                    }
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $title = '$entry[\'' . $v['handle'] . '_title\']';
                    }

                    $code .= BlockBuilderUtility::tab(4) . '$modifiedEntry = $this->prepareForViewExternalLink(\'entry\', [' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '\'          => $entry[\'' . $v['handle'] . '\'],' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_protocol\' => $entry[\'' . $v['handle'] . '_protocol\'],'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_text\'     => ' . $text . ',' . PHP_EOL;
                    $code .= BlockBuilderUtility::tab(5) . '\'' . $v['handle'] . '_title\'    => ' . $title . '' . PHP_EOL;
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
                        if (!empty($v['imageThumbnailWidth'])) {
                            $thumbnailWidth = $v['imageThumbnailWidth'];
                        }
                        if (!empty($v['imageThumbnailHeight'])) {
                            $thumbnailHeight = $v['imageThumbnailHeight'];
                        }
                        if (!empty($v['imageThumbnailCrop'])) {
                            $thumbnailCrop = 'true';
                        }
                    }

                    $fullscreen = 'false';
                    $fullscreenWidth = 'false';
                    $fullscreenHeight = 'false';
                    $fullscreenCrop = 'false';
                    if (!empty($v['imageCreateFullscreenImage'])) {
                        $fullscreen = 'true';
                        if (!empty($v['imageFullscreenWidth'])) {
                            $fullscreenWidth = $v['imageFullscreenWidth'];
                        }
                        if (!empty($v['imageFullscreenHeight'])) {
                            $fullscreenHeight = $v['imageFullscreenHeight'];
                        }
                        if (!empty($v['imageFullscreenCrop'])) {
                            $fullscreenCrop = 'true';
                        }
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

            $code .= BlockBuilderUtility::tab(4).'$entriesForView[] = $entry;'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(3).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'}'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'return $entriesForView;'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(1).'}'.PHP_EOL.PHP_EOL;

        }


        // Class end
        $code .= '}';

        $fileService = new FileService();
        $fileService->append($postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename, $code);

    }

}
