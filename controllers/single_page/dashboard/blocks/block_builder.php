<?php namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks;

use Concrete\Core\Asset\AssetList;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\Support\Facade\Package;
use BlockBuilder\Generator as BlockBuilderGenerator;
use BlockBuilder\OptionList as BlockBuilderOptionList;

defined('C5_EXECUTE') or die('Access Denied.');

class BlockBuilder extends DashboardPageController
{

    public function on_start() {

        parent::on_start();

        // Make $app available in view
        $this->set('app', $this->app);

    }

    public function view() {

        // Generate CSRF token
        $token = new Token();
        $ajaxCsrfToken = $token->generate('ajax_csrf_token');
        $this->set('ajaxCsrfToken', $ajaxCsrfToken);

        $this->set('formAction', $this->action(''));

        $blockBuilderValidation = $this->app->build('BlockBuilder\Validation');
        $blockBuilderGenerator  = new BlockBuilderGenerator();

        $errors          = false;
        $fieldsWithError = [];
        $tabsWithError   = [];
        $basic           = false;
        $entries         = false;

        // Validate
        if ($this->post()) {

            if ( ! $this->token->validate('csrfToken')) {
                $this->redirect('dashboard/blocks/block_builder');
            }

            // Validate fields
            $validation      = $blockBuilderValidation->validateBlockData($this->post());

            $errors          = $validation['errors'];
            $fieldsWithError = $validation['fieldsWithError'];
            $tabsWithError   = $validation['tabsWithError'];
            $basic           = $validation['basic'];
            $entries         = $validation['entries'];

            // Create block if there are no errors
            if ( ! $errors) {

                // Get rid of spaces from labels
                $postData = $this->post();
                $postData['blockName']        = trim($postData['blockName']);
                $postData['blockDescription'] = trim($postData['blockDescription']);

                if ( ! empty($postData['basic'])) {
                    foreach ($postData['basic'] as $k => $v) {
                        $postData['basic'][$k]['label']    = trim($v['label']);
                        $postData['basic'][$k]['helpText'] = trim($v['helpText']);
                    }
                }

                if ( ! empty($postData['entries'])) {
                    foreach ($postData['entries'] as $k => $v) {
                        $postData['entries'][$k]['label']    = trim($v['label']);
                        $postData['entries'][$k]['helpText'] = trim($v['helpText']);
                    }
                }

                // Generate block
                $createBlockResult = $blockBuilderGenerator->generateBlock($postData);

                if (is_array($createBlockResult) AND $createBlockResult['handle']) {

                    if ($createBlockResult['blockInstalled']) {
                        $this->redirect('dashboard/blocks/block_builder/block_created_and_installed?blockNameCreated='.$createBlockResult['handle']);
                    } else {
                        $this->redirect('dashboard/blocks/block_builder/block_created?blockNameCreated='.$createBlockResult['handle']);
                    }

                } else {

                    $this->error->add($createBlockResult);
                    
                }
                
            }

        }

        $this->set('errors', $errors);
        $this->set('fieldsWithError', $fieldsWithError);
        $this->set('tabsWithError', $tabsWithError);

        // Get options for select fields
        $blockBuilderOptionList = new BlockBuilderOptionList;

        $fieldTypes = $blockBuilderOptionList->getFieldTypes();
        $this->set('fieldTypes', $fieldTypes);

        $blockTypeSets = $blockBuilderOptionList->getBlockTypeSets();
        $this->set('blockTypeSets', $blockTypeSets);

        $dividerOptions = $blockBuilderOptionList->getDividerOptions();
        $this->set('dividerOptions', $dividerOptions);

        $installBlockOptions = $blockBuilderOptionList->getInstallBlockOptions();
        $this->set('installBlockOptions', $installBlockOptions);

        // Default values
        $this->set('blockWidth', 800);
        $this->set('blockHeight', 650);
        $this->set('installBlock', 1);

        $this->set('basicLabel', t('Basic information'));
        $this->set('entriesLabel', t('Entries'));
        $this->set('addAtTheTopLabel', t('Add at the top'));
        $this->set('addAtTheBottomLabel', t('Add at the bottom'));
        $this->set('collapseAllLabel', t('Collapse all'));
        $this->set('expandAllLabel', t('Expand all'));
        $this->set('noEntriesFoundLabel', t('No entries found.'));
        $this->set('requiredFieldsLabel', t('Required fields'));
        $this->set('urlEndingLabel', t('Custom string at the end of URL'));
        $this->set('urlEndingHelpText', t('(e.g. #contact-form or ?ccm_paging_p=2)'));
        $this->set('textLabel', t('Text'));
        $this->set('titleLabel', t('Title'));
        $this->set('altTextLabel', t('Alt text'));
        $this->set('areYouSureLabel', t('Are you sure?'));

        // Get rid of keys in repeatable entries (for json manipulation in js)
        if (is_array($basic)) {
            $this->set('basic', array_values($basic));
        }

        if (is_array($entries)) {
            $this->set('entries', array_values($entries));
        }

        // Load assets
        $pkg = Package::getByHandle('block_builder');

        $al = AssetList::getInstance();
        $al->register('css', 'bb/styles', 'css_files/styles.css', [], $pkg);
        $al->register('javascript', 'bb/scripts', 'js_files/block-builder.js', [], $pkg);
        $this->requireAsset('css', 'bb/styles');
        $this->requireAsset('javascript', 'bb/scripts');

    }

    public function config($configBlockHandle) {

        $this->view();

        // Replace some variables when loading config
        $this->set('formAction', $this->action('config', $configBlockHandle));

        // Load values from config (only for non-post)
        if ( ! $this->post() ) {

            $configPath = DIR_FILES_BLOCK_TYPES.DIRECTORY_SEPARATOR.$configBlockHandle.DIRECTORY_SEPARATOR.'config-bb.json';

            if (file_exists($configPath)) {

                $configJson = file_get_contents($configPath);
                $config     = json_decode($configJson, true);

                if (is_array($config)) {

                    foreach ($config as $k => $v) {
                        if ($k=='basic') {
                            $this->set('basic', array_values($v));
                        } elseif ($k=='entries') {
                            $this->set('entries', array_values($v));
                        } else {
                            $this->set($k, $v);
                        }
                    }

                    $this->set('message', t('You have successfully loaded configuration from:') . ' ' . $config['blockName']);

                }

            }

        }

    }

    public function block_created() {

        $this->set('success', t('Block "%s" has been successfully created. Go to "Block Types" page to manually install it.', $this->get('blockNameCreated')));

        $this->view();

    }

    public function block_created_and_installed() {

        $this->set('success', t('Block "%s" has been successfully created and installed.', $this->get('blockNameCreated')));

        $this->view();

    }

    public function configs() {

        $pkg = Package::getByHandle('block_builder');

        $al = AssetList::getInstance();
        $al->register('css', 'bb/styles', 'css_files/styles.css', [], $pkg);
        $this->requireAsset('css', 'bb/styles');

        $blockTypeHandles = scandir(DIR_FILES_BLOCK_TYPES);

        $blockTypes = [];
        $i=0;

        if (is_array($blockTypeHandles)) {

            unset($blockTypeHandles[0]); // .
            unset($blockTypeHandles[1]); // ..

            foreach ($blockTypeHandles as $blockTypeHandle) {

                $blockTypePath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $blockTypeHandle . DIRECTORY_SEPARATOR . 'config-bb.json';

                if (file_exists($blockTypePath)) {

                    $configJson = file_get_contents($blockTypePath);
                    $config = json_decode($configJson, true);

                    $blockTypes[$i]['handle'] = $blockTypeHandle;
                    $blockTypes[$i]['name']   = $config['blockName'];

                    $i++;
                }

            }

        }

        $this->set('blockTypes', $blockTypes);

        $this->render('dashboard/blocks/block_builder/configs');

    }

}