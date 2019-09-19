<?php namespace BlockBuilder;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\File\Service\File as FileService;
use Concrete\Core\Url\Resolver\Manager\ResolverManager;

defined('C5_EXECUTE') or die('Access Denied.');

class Validation
{

    protected $resolverManager;

    public function __construct(ResolverManager $resolverManager) {
        $this->resolverManager = $resolverManager;
    }

    public function getForbiddenBlockHandles() {

        // Few blocks, which have folder name different from db table name
        $forbiddenWords = [
            'navigation',
            'content_local',
            'content_file',
            'content_image'
        ];

        return $forbiddenWords;

    }

    public function getForbiddenHandles() {

        $forbiddenWords = ['id', 'bID', 'position'];

        return $forbiddenWords;

    }

    public function validateBlockData($postData) {

        $errors = [];
        $tabsWithError = [];
        $fieldsWithError = [];

        // 1. Block settings

        // Block name
        if (!$postData['blockName']) {

            $errors[] = t('Field "%s" is required (%s).', t('Block name'), t('Block settings'));
            $fieldsWithError[] = 'blockName';
            $tabsWithError[] = 'block-settings';

        } else {

            if (mb_strlen($postData['blockName']) < 3 OR mb_strlen($postData['blockName']) > 100) {
                $errors[] = t('Field "%s" should be between %s and %s characters long (%s).', t('Block name'), 3, 100, t('Block settings'));
                $fieldsWithError[] = 'blockName';
                $tabsWithError[] = 'block-settings';
            }

        }

        // Block handle
        if (!$postData['blockHandle']) {

            $errors[] = t('Field "%s" is required (%s).', t('Block handle'), t('Block settings'));
            $fieldsWithError[] = 'blockHandle';
            $tabsWithError[] = 'block-settings';

        } else {

            if (mb_strlen($postData['blockHandle']) < 3 OR mb_strlen($postData['blockHandle']) > 50) {
                $errors[] = t('Field "%s" should be between %s and %s characters long (%s).', t('Block handle'), 3, 50, t('Block settings'));
                $fieldsWithError[] = 'blockHandle';
                $tabsWithError[] = 'block-settings';
            }

            if (!preg_match('/^[a-z_]+$/', $postData['blockHandle'])) {
                $errors[] = t('Field "%s" should only consist of lowercase letters and underscores (%s).', t('Block handle'), t('Block settings'));
                $fieldsWithError[] = 'blockHandle';
                $tabsWithError[] = 'block-settings';
            }

            if (mb_substr($postData['blockHandle'], 0, 1, 'utf-8') == '_' OR mb_substr($postData['blockHandle'], -1, 1, 'utf-8') == '_') {
                $errors[] = t('Field "%s" should not start and end with underscore (%s).', t('Block handle'), t('Block settings'));
                $fieldsWithError[] = 'blockHandle';
                $tabsWithError[] = 'block-settings';
            }

            if (preg_match('/[_]{2,}/', $postData['blockHandle'])) {
                $errors[] = t('Field "%s" should not consist of two or more consecutive underscores (%s).', t('Block handle'), t('Block settings'));
                $fieldsWithError[] = 'blockHandle';
                $tabsWithError[] = 'block-settings';
            }

            if ($this->blockTypeFolderExists($postData['blockHandle'], true)) {

                $errors[] = t('Concrete5 already uses same name as your handle for one of its blocks. Use different handle. (%s).', t('Block settings'));
                $fieldsWithError[] = 'blockHandle';
                $tabsWithError[] = 'block-settings';

            } else {

                if ($this->isBlockInstalled($postData['blockHandle'])) {

                    $blockType = BlockType::getByHandle($postData['blockHandle']);

                    $urlEnding = '';
                    if (is_object($blockType)) {
                        $urlEnding = '/inspect/'.$blockType->getBlockTypeID();
                    }

                    $errors[] = t('Block with that handle is already installed. <a href="%s" target="_blank" rel="noopener">Uninstall it</a> first and then build block again or use another handle (%s).',  $this->resolverManager->resolve(['dashboard/blocks/types'.$urlEnding]), t('Block settings'));
                    $fieldsWithError[] = 'blockHandle';
                    $tabsWithError[] = 'block-settings';

                } else {

                    if ($this->blockTypeFolderExists($postData['blockHandle'])) {

                        $errors[] = t('Block folder named "%s" already exists. <a href="#" class="js-delete-block-type-folder">Permanently delete that folder</a> or use another handle. (%s).', $postData['blockHandle'], t('Block settings'));
                        $fieldsWithError[] = 'blockHandle';
                        $tabsWithError[] = 'block-settings';

                    }

                }
            }


            if ( ! $this->validateForbiddenBlockHandles($postData['blockHandle'])) {
                $errors[] = t('Your "%s" is forbidden word, use different phrase (%s).', t('Block handle'), t('Block settings'));
                $fieldsWithError[] = 'blockHandle';
                $tabsWithError[] = 'block-settings';
            }

        }

        // Other
        if (!$postData['blockWidth']) {

            $errors[] = t('Field "%s" is required (%s).', t('Block width'), t('Block settings'));
            $fieldsWithError[] = 'blockWidth';
            $tabsWithError[] = 'block-settings';

        } else {

            if (!ctype_digit($postData['blockWidth']) OR $postData['blockWidth'] < 300 OR $postData['blockWidth'] > 2000) {
                $errors[] = t('Field "%s" should be a number between %s and %s (%s).', t('Block width'), 300, 2000, t('Block settings'));
                $fieldsWithError[] = 'blockWidth';
                $tabsWithError[] = 'block-settings';
            }

        }

        if (!$postData['blockHeight']) {

            $errors[] = t('Field "%s" is required (%s).', t('Block height'), t('Block settings'));
            $fieldsWithError[] = 'blockHeight';
            $tabsWithError[] = 'block-settings';

        } else {

            if (!ctype_digit($postData['blockHeight']) OR $postData['blockHeight'] < 300 OR $postData['blockHeight'] > 2000) {
                $errors[] = t('Field "%s" should be a number between %s and %s (%s).', t('Block width'), 300, 2000, t('Block settings'));
                $fieldsWithError[] = 'blockHeight';
                $tabsWithError[] = 'block-settings';
            }

        }

        // 2. Texts
        if (!$postData['addAtTheTopLabel'] AND !$postData['addAtTheBottomLabel']) {
            $errors[] = t('At least one label for buttons ("Add at the top" or "Add at the bottom") is required (%s).', t('Texts for translation'));
            $fieldsWithError[] = 'addAtTheTopLabel';
            $tabsWithError[] = 'texts';
        }

        if ((is_array($postData['basic']) AND count($postData['basic'])) AND (is_array($postData['entries']) AND count($postData['entries'])) AND !$postData['basicLabel']) {
            $errors[] = t('Label for "%s" is required (%s).', t('Basic information'), t('Texts for translation'));
            $fieldsWithError[] = 'basicLabel';
            $tabsWithError[] = 'texts';
        }

        if ((is_array($postData['entries']) AND count($postData['entries'])) AND (is_array($postData['basic']) AND count($postData['basic'])) AND !$postData['entriesLabel']) {
            $errors[] = t('Label for "%s" is required (%s).', t('Entries'), t('Texts for translation'));
            $fieldsWithError[] = 'entriesLabel';
            $tabsWithError[] = 'texts';
        }


        // 3. Basic information + Repeatable entries
        $basicData   = $this->validateRepeatableEntries($postData['basic'], 'basic-information', t('Tab: Basic information'));
        $entriesData = $this->validateRepeatableEntries($postData['entries'], 'repeatable-entries', t('Tab: Repeatable entries'));

        // Combine everything
        $validation = [];

        $validation['errors']          = array_merge($errors, $basicData['errors'], $entriesData['errors']);
        $validation['tabsWithError']   = array_merge($tabsWithError, $basicData['tabsWithError'], $entriesData['tabsWithError']);
        $validation['fieldsWithError'] = array_merge($fieldsWithError, $basicData['fieldsWithError'], $entriesData['fieldsWithError']);

        $validation['basic']   = $basicData['data'];
        $validation['entries'] = $entriesData['data'];

        return $validation;

    }

    private function validateRepeatableEntries($postData, $handle, $label) {

        $errors          = [];
        $fieldsWithError = [];
        $tabsWithError   = [];

        if ( is_array($postData) AND count($postData) ) {

            $uniqueHandles = [];

            foreach ($postData as $counter => $entry) {

                // Label
                if ( ! $entry['label']) {

                    $postData[$counter]['error']['label'] = 1;
                    $fieldsWithError[] = $handle.'|label|empty';
                    $tabsWithError[]   = 'tab-'.$handle;

                } else {

                    if (mb_strlen($entry['label'])<3) {
                        $postData[$counter]['error']['label'] = 1;
                        $fieldsWithError[] = $handle.'|label|less_than_3_characters';
                        $tabsWithError[]   = 'tab-'.$handle;
                    }

                }

                // Handle
                if ( ! $entry['handle']) {

                    $postData[$counter]['error']['handle'] = 1;
                    $fieldsWithError[] = $handle.'|handle|empty';
                    $tabsWithError[]   = 'tab-'.$handle;

                } else {

                    if (mb_strlen($entry['handle'])<3) {
                        $postData[$counter]['error']['handle'] = 1;
                        $fieldsWithError[] = $handle.'|handle|less_than_3_characters';
                        $tabsWithError[]   = 'tab-'.$handle;
                    }

                    if (mb_strlen($entry['handle'])>50) {
                        $postData[$counter]['error']['handle'] = 1;
                        $fieldsWithError[] = $handle.'|handle|more_than_50_characters';
                        $tabsWithError[]   = 'tab-'.$handle;
                    }

                    if ( ! preg_match('/^[a-zA-Z_]+$/', $entry['handle'])) {
                        $postData[$counter]['error']['handle'] = 1;
                        $fieldsWithError[] = $handle.'|handle|invalid_characters';
                        $tabsWithError[]   = 'tab-'.$handle;
                    }

                    if (mb_substr($entry['handle'], 0, 1, 'utf-8') == '_' OR mb_substr($entry['handle'], -1, 1, 'utf-8') == '_') {
                        $postData[$counter]['error']['handle'] = 1;
                        $fieldsWithError[] = $handle.'|handle|start_or_end_with_underscore';
                        $tabsWithError[]   = 'tab-'.$handle;
                    }

                    if (preg_match('/[_]{2,}/', $entry['handle'])) {
                        $postData[$counter]['error']['handle'] = 1;
                        $fieldsWithError[] = $handle.'|handle|consecutive_underscores';
                        $tabsWithError[]   = 'tab-'.$handle;
                    }

                    if (!ctype_lower(mb_substr($entry['handle'], 0, 1))) {
                        $postData[$counter]['error']['handle'] = 1;
                        $fieldsWithError[] = $handle.'|handle|first_character_not_lowercase';
                        $tabsWithError[]   = 'tab-'.$handle;
                    }

                    if ( ! $this->validateForbiddenHandles($entry['handle'])) {
                        $postData[$counter]['error']['handle'] = 1;
                        $fieldsWithError[] = $handle.'|handle|forbidden_word';
                        $tabsWithError[]   = 'tab-'.$handle;
                    }

                    // Check if there aren't repeated handles
                    if (in_array($entry['handle'], $uniqueHandles)) {
                        $postData[$counter]['error']['handle'] = 1;
                        $fieldsWithError[] = $handle.'|handle|repeated_handle';
                        $tabsWithError[]   = 'tab-'.$handle;
                    } else {
                        $uniqueHandles[] = $entry['handle'];
                    }

                }


                // textarea
                if ($entry['textareaHeight']!='' AND (!ctype_digit($entry['textareaHeight']) OR $entry['textareaHeight'] < 40 OR $entry['textareaHeight'] > 2000) ) {

                    $postData[$counter]['error']['textareaHeight'] = 1;
                    $fieldsWithError[] = $handle.'|textareaHeight|invalid_number';
                    $tabsWithError[]   = 'tab-'.$handle;

                }

                // wysiwyg_editor
                if ($entry['wysiwygEditorHeight']!='' AND (!ctype_digit($entry['wysiwygEditorHeight']) OR $entry['wysiwygEditorHeight'] < 40 OR $entry['wysiwygEditorHeight'] > 2000) ) {

                    $postData[$counter]['error']['wysiwygEditorHeight'] = 1;
                    $fieldsWithError[] = $handle.'|wysiwygEditorHeight|invalid_number';
                    $tabsWithError[]   = 'tab-'.$handle;

                }

                // select_field
                if ( isset($entry['selectOptions']) AND ! $entry['selectOptions']) {

                    $postData[$counter]['error']['selectOptions'] = 1;
                    $fieldsWithError[] = $handle.'|selectOptions|empty';
                    $tabsWithError[]   = 'tab-'.$handle;

                } else {

                    $options = explode('<br />', nl2br($entry['selectOptions']));

                    $invalidKey = 0;

                    if (is_array($options)) {

                        foreach ($options as $option) {

                            $explodedOption = explode('::', $option);

                            if (is_array($explodedOption) AND count($explodedOption)==2) {

                                $key = $explodedOption[0];

                                if ( ! preg_match('/^[a-zA-Z0-9_]+$/', trim($key))) {
                                    $invalidKey++;
                                }

                                if (mb_substr(trim($key), 0, 1, 'utf-8') == '_') {
                                    $invalidKey++;
                                }

                            }

                        }

                    }

                    if ($invalidKey>0) {

                        $postData[$counter]['error']['selectOptions'] = 1;
                        $fieldsWithError[] = $handle.'|selectOptions|invalid_data';
                        $tabsWithError[]   = 'tab-'.$handle;

                    }

                }

                // image
                if ( ! empty($entry['imageCreateThumbnailImage'])) {

                    if ($entry['imageThumbnailWidth'] == '' AND $entry['imageThumbnailHeight'] == '') {

                        $postData[$counter]['error']['imageThumbnailOptions'] = 1;
                        $fieldsWithError[] = $handle . '|imageThumbnailOptions|empty_width_and_height';
                        $tabsWithError[] = 'tab-' . $handle;

                    }

                    if ( ! empty($entry['imageThumbnailCrop']) AND ($entry['imageThumbnailWidth'] == '' OR $entry['imageThumbnailHeight'] == '')) {

                        $postData[$counter]['error']['imageThumbnailOptions'] = 1;
                        $fieldsWithError[] = $handle . '|imageThumbnailOptions|crop_requires_width_and_height';
                        $tabsWithError[] = 'tab-' . $handle;

                    }

                    if ($entry['imageThumbnailWidth']!='' AND (!ctype_digit($entry['imageThumbnailWidth']) OR $entry['imageThumbnailWidth'] < 1) ) {

                        $postData[$counter]['error']['imageThumbnailWidth'] = 1;
                        $fieldsWithError[] = $handle.'|imageThumbnailWidth|invalid_number';
                        $tabsWithError[]   = 'tab-'.$handle;

                    }

                    if ($entry['imageThumbnailHeight']!='' AND (!ctype_digit($entry['imageThumbnailHeight']) OR $entry['imageThumbnailHeight'] < 1) ) {

                        $postData[$counter]['error']['imageThumbnailHeight'] = 1;
                        $fieldsWithError[] = $handle.'|imageThumbnailHeight|invalid_number';
                        $tabsWithError[]   = 'tab-'.$handle;

                    }

                }

                if ( ! empty($entry['imageCreateFullscreenImage'])) {

                    if ($entry['imageFullscreenWidth'] == '' AND $entry['imageFullscreenHeight'] == '') {

                        $postData[$counter]['error']['imageFullscreenOptions'] = 1;
                        $fieldsWithError[] = $handle . '|imageFullscreenOptions|empty_width_and_height';
                        $tabsWithError[] = 'tab-' . $handle;

                    }

                    if ( ! empty($entry['imageFullscreenCrop']) AND ($entry['imageFullscreenWidth'] == '' OR $entry['imageFullscreenHeight'] == '')) {

                        $postData[$counter]['error']['imageFullscreenOptions'] = 1;
                        $fieldsWithError[] = $handle . '|imageFullscreenOptions|crop_requires_width_and_height';
                        $tabsWithError[] = 'tab-' . $handle;

                    }

                    if ($entry['imageFullscreenWidth']!='' AND (!ctype_digit($entry['imageFullscreenWidth']) OR $entry['imageFullscreenWidth'] < 1) ) {

                        $postData[$counter]['error']['imageFullscreenWidth'] = 1;
                        $fieldsWithError[] = $handle.'|imageFullscreenWidth|invalid_number';
                        $tabsWithError[]   = 'tab-'.$handle;

                    }

                    if ($entry['imageFullscreenHeight']!='' AND (!ctype_digit($entry['imageFullscreenHeight']) OR $entry['imageFullscreenHeight'] < 1) ) {

                        $postData[$counter]['error']['imageFullscreenHeight'] = 1;
                        $fieldsWithError[] = $handle.'|imageFullscreenHeight|invalid_number';
                        $tabsWithError[]   = 'tab-'.$handle;

                    }

                }

                // html_editor
                if ($entry['htmlEditorHeight']!='' AND (!ctype_digit($entry['htmlEditorHeight']) OR $entry['htmlEditorHeight'] < 40 OR $entry['htmlEditorHeight'] > 2000) ) {

                    $postData[$counter]['error']['htmlEditorHeight'] = 1;
                    $fieldsWithError[] = $handle.'|htmlEditorHeight|invalid_number';
                    $tabsWithError[]   = 'tab-'.$handle;

                }

            }

        }

        // Label
        if (in_array($handle.'|label|empty', $fieldsWithError)) {
            $errors[] = t('There are some empty "Label" fields (%s).', $label);
        }

        if (in_array($handle.'|label|less_than_3_characters', $fieldsWithError)) {
            $errors[] = t('There are some "Label" fields which consist of less than %s characters (%s).', 3, $label);
        }

        // Handle
        if (in_array($handle.'|handle|empty', $fieldsWithError)) {
            $errors[] = t('There are some empty "Handle" fields (%s).', $label);
        }

        if (in_array($handle.'|handle|less_than_3_characters', $fieldsWithError)) {
            $errors[] = t('There are some "Handle" fields which consist of less than %s characters (%s).', 3, $label);
        }

        if (in_array($handle.'|handle|more_than_50_characters', $fieldsWithError)) {
            $errors[] = t('There are some "Handle" fields which consist of more than %s characters (%s).', 50, $label);
        }

        if (in_array($handle.'|handle|invalid_characters', $fieldsWithError)) {
            $errors[] = t('There are some "Handle" fields which consist of characters other than a-zA-Z_ (%s).', $label);
        }

        if (in_array($handle.'|handle|start_or_end_with_underscore', $fieldsWithError)) {
            $errors[] = t('There are some "Handle" fields which start or end with underscore (%s).', $label);
        }

        if (in_array($handle.'|handle|consecutive_underscores', $fieldsWithError)) {
            $errors[] = t('There are some "Handle" fields which consist of two or more consecutive underscores (%s).', $label);
        }

        if (in_array($handle.'|handle|first_character_not_lowercase', $fieldsWithError)) {
            $errors[] = t('There are some "Handle" fields which start with uppercase character (%s).', $label);
        }

        if (in_array($handle.'|handle|forbidden_word', $fieldsWithError)) {
            $errors[] = t('There are some "Handle" fields which are forbidden words (%s).', $label);
        }

        if (in_array($handle.'|handle|repeated_handle', $fieldsWithError)) {
            $errors[] = t('All "Handle" fields should be unique (%s).', $label);
        }

        // textarea
        if (in_array($handle.'|textareaHeight|invalid_number', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Textarea/Height" fields, should be a number between %s and %s or empty (%s).', 40, 2000, $label);
        }

        // wysiwyg_editor
        if (in_array($handle.'|wysiwygEditorHeight|invalid_number', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "WYSIWYG Editor/Height" fields, should be a number between %s and %s or empty (%s).', 40, 2000, $label);
        }

        // select_field
        if (in_array($handle.'|selectOptions|empty', $fieldsWithError)) {
            $errors[] = t('There are some empty "Select field/Select options" fields (%s).', $label);
        }
        if (in_array($handle.'|selectOptions|invalid_data', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Select field/Select options" fields (%s).', $label);
        }

        // image
        if (in_array($handle.'|imageThumbnailOptions|empty_width_and_height', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Image/Generate thumbnail" fields, you should provide width, height or both (%s).', $label);
        }
        if (in_array($handle.'|imageThumbnailOptions|crop_requires_width_and_height', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Image/Generate thumbnail" fields, you should provide width and height if you want to crop image (%s).', $label);
        }
        if (in_array($handle.'|imageThumbnailWidth|invalid_number', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Image/Generate thumbnail/Width" fields, should be a number greater than 0 or empty (%s).', $label);
        }
        if (in_array($handle.'|imageThumbnailHeight|invalid_number', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Image/Generate thumbnail/Height" fields, should be a number greater than 0 or empty (%s).', $label);
        }

        if (in_array($handle.'|imageFullscreenOptions|empty_width_and_height', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Image/Generate fullscreen thumbnail" fields, you should provide width, height or both (%s).', $label);
        }
        if (in_array($handle.'|imageFullscreenOptions|crop_requires_width_and_height', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Image/Generate fullscreen thumbnail" fields, you should provide width and height if you want to crop image (%s).', $label);
        }
        if (in_array($handle.'|imageFullscreenWidth|invalid_number', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Image/Generate fullscreen thumbnail/Width" fields, should be a number greater than 0 or empty (%s).', $label);
        }
        if (in_array($handle.'|imageFullscreenHeight|invalid_number', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "Image/Generate fullscreen thumbnail/Height" fields, should be a number greater than 0 or empty (%s).', $label);
        }

        // html_editor
        if (in_array($handle.'|htmlEditorHeight|invalid_number', $fieldsWithError)) {
            $errors[] = t('Invalid entry in one of "HTML Editor/Height" fields, should be a number between %s and %s or empty (%s).', 40, 2000, $label);
        }


        // Return data
        $validation = [];

        $validation['errors'] = [];
        if (count($errors)) {
            $validation['errors'] = $errors;
        }

        $validation['tabsWithError'] = [];
        if (count($tabsWithError)) {
            $validation['tabsWithError'] = $tabsWithError;
        }

        $validation['fieldsWithError'] = [];
        if (count($fieldsWithError)) {
            $validation['fieldsWithError'] = $fieldsWithError;
        }

        $validation['data'] = $postData;

        return $validation;
    }

    public function blockTypeFolderExists($handle, $pathToCore = false) {

        if ($pathToCore) {
            $blockTypePath = DIR_FILES_BLOCK_TYPES_CORE . DIRECTORY_SEPARATOR . $handle;
        } else {
            $blockTypePath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $handle;
        }

        if (is_dir($blockTypePath)) {
            return true;
        }

        // We do additional check in core blocks because
        // we don't want to override existing core tables
        // google_map => btGoogleMap
        // googlemap => btGooglemap
        $fileService = new FileService();
        $coreBlockFolders = $fileService->getDirectoryContents(DIR_FILES_BLOCK_TYPES_CORE);

        foreach ($coreBlockFolders as $k => $v) {
            if (strtolower(str_replace('_', '', $handle)) == strtolower(str_replace('_', '', $v))) {
                return true;
            }
        }

        return false;

    }

    public function isBlockInstalled($handle) {

        $blockType = BlockType::getByHandle($handle);

        if (is_object($blockType)) {
            return true;
        } else {
            return false;
        }

    }

    private function validateForbiddenBlockHandles($handle) {

        // We do this instead of simple in_array() because
        // we don't want to override existing core tables
        // content_image => btContentImage
        // contentimage => btContentimage
        foreach ($this->getForbiddenBlockHandles() as $k => $v) {

            if (strtolower(str_replace('_', '', $handle)) == strtolower(str_replace('_', '', $v))) {

                return false;

            }

        }

        return true;

    }

    private function validateForbiddenHandles($handle) {

        // Case-insensitive check
        foreach ($this->getForbiddenHandles() as $k => $v) {

            if (strtolower($handle) == strtolower($v)) {

                return false;

            }

        }

        return true;

    }

}
