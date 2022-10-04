<?php namespace Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks;

use Concrete\Core\Asset\AssetList;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Permission\Checker as Permissions;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\Support\Facade\Package;
use BlockBuilder\Generator as BlockBuilderGenerator;
use BlockBuilder\OptionList as BlockBuilderOptionList;

defined('C5_EXECUTE') or die('Access Denied.');

class BlockBuilder extends DashboardPageController
{

    public function on_start()
    {

        parent::on_start();

        // Make $app available in view
        $this->set('app', $this->app);

    }

    public function view()
    {

        // Generate CSRF token
        $token = new Token();
        $ajaxCsrfToken = $token->generate('ajax_csrf_token');
        $this->set('ajaxCsrfToken', $ajaxCsrfToken);

        $this->set('formAction', $this->action(''));

        $blockBuilderValidation = $this->app->build('BlockBuilder\Validation');
        $blockBuilderGenerator = new BlockBuilderGenerator();

        $errors = false;
        $fieldsWithError = [];
        $tabsWithError = [];
        $basic = false;
        $entries = false;

        // Validate
        if ($this->post()) {

            if (!$this->token->validate('csrfToken')) {
                $this->redirect('dashboard/blocks/block_builder');
            }

            // Validate fields
            $validation = $blockBuilderValidation->validateBlockData($this->post());

            $errors = $validation['errors'];
            $fieldsWithError = $validation['fieldsWithError'];
            $tabsWithError = $validation['tabsWithError'];
            $basic = $validation['basic'];
            $entries = $validation['entries'];

            // Create block if there are no errors
            if (!$errors) {

                // Get rid of spaces from labels
                $postData = $this->post();
                $postData['blockName'] = trim($postData['blockName']);
                $postData['blockDescription'] = trim($postData['blockDescription']);

                if (!empty($postData['basic'])) {
                    foreach ($postData['basic'] as $k => $v) {
                        $postData['basic'][$k]['label'] = trim($v['label']);
                        $postData['basic'][$k]['helpText'] = trim($v['helpText']);
                    }
                }

                if (!empty($postData['entries'])) {
                    foreach ($postData['entries'] as $k => $v) {
                        $postData['entries'][$k]['label'] = trim($v['label']);
                        $postData['entries'][$k]['helpText'] = trim($v['helpText']);
                    }
                }

                // Add current package version
                $pkg = Package::getByHandle('block_builder');
                $postData = ['version' => $pkg->getPackageVersion()] + $postData;

                // Refresh block
                if (!empty($this->post('refresh_block'))) {
                    // Check permissions
                    $p = new Permissions();
                    if ($p->canInstallPackages()) {
                        // Remove folder, and later it will be refreshed in generateBlock()
                        $this->removeDirectory(DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $postData['blockHandle']);
                    }
                }

                // Generate block
                $createBlockResult = $blockBuilderGenerator->generateBlock($postData);

                if (is_array($createBlockResult) and $createBlockResult['handle']) {

                    if ($createBlockResult['blockRefreshed']) {
                        $this->redirect('dashboard/blocks/block_builder/block_refreshed?blockNameRefreshed=' . $createBlockResult['handle']);
                    } elseif ($createBlockResult['blockInstalled']) {
                        $this->redirect('dashboard/blocks/block_builder/block_created_and_installed?blockNameCreated=' . $createBlockResult['handle']);
                    } else {
                        $this->redirect('dashboard/blocks/block_builder/block_created?blockNameCreated=' . $createBlockResult['handle']);
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

        $entriesAsFirstTabOptions = $blockBuilderOptionList->getEntriesAsFirstTabOptions();
        $this->set('entriesAsFirstTabOptions', $entriesAsFirstTabOptions);

        $dividerOptions = $blockBuilderOptionList->getDividerOptions();
        $this->set('dividerOptions', $dividerOptions);

        $installBlockOptions = $blockBuilderOptionList->getInstallBlockOptions();
        $this->set('installBlockOptions', $installBlockOptions);

        $selectFieldTypes = $blockBuilderOptionList->getSelectFieldTypes();
        $this->set('selectFieldTypes', $selectFieldTypes);

        $selectMultipleFieldTypes = $blockBuilderOptionList->getSelectMultipleFieldTypes();
        $this->set('selectMultipleFieldTypes', $selectMultipleFieldTypes);

        // Default values
        $this->set('blockName', '');
        $this->set('blockHandle', '');
        $this->set('blockDescription', '');
        $this->set('blockTypeSet', '');
        $this->set('entriesAsFirstTab', '');
        $this->set('fieldsDivider', '');
        $this->set('entryFieldsDivider', '');
        $this->set('basic', '');
        $this->set('entries', '');

        $this->set('blockWidth', 1000);
        $this->set('blockHeight', 650);
        $this->set('installBlock', 1);
        $this->set('maxNumberOfEntries', 0);

        $this->set('basicLabel', t('Basic information'));
        $this->set('entriesLabel', t('Entries'));
        $this->set('settingsLabel', t('Settings'));
        $this->set('addAtTheTopLabel', t('Add at the top'));
        $this->set('addAtTheBottomLabel', t('Add at the bottom'));
        $this->set('copyLastEntryLabel', t('Copy last entry'));
        $this->set('collapseAllLabel', t('Collapse all'));
        $this->set('expandAllLabel', t('Expand all'));
        $this->set('removeAllLabel', t('Remove all'));
        $this->set('disableSmoothScrollLabel', t('Disable smooth scroll'));
        $this->set('keepAddedEntryCollapsedLabel', t('Keep added/copied entry collapsed'));
        $this->set('noEntriesFoundLabel', t('No entries found.'));
        $this->set('maxNumberOfEntriesLabel', t('Max. number of entries'));
        $this->set('removeEntryLabel', t('Remove entry'));
        $this->set('duplicateEntryLabel', t('Duplicate entry'));
        $this->set('duplicateEntryAndAddAtTheEndLabel', t('Duplicate entry and add at the end'));
        $this->set('areYouSureLabel', t('Are you sure?'));
        $this->set('requiredFieldsLabel', t('Required fields'));
        $this->set('urlEndingLabel', t('Custom string at the end of URL'));
        $this->set('urlEndingHelpText', t('(e.g. #contact-form or ?ccm_paging_p=2)'));
        $this->set('textLabel', t('Text'));
        $this->set('titleLabel', t('Title'));
        $this->set('altTextLabel', t('Alt text'));
        $this->set('linkFromSitemapLabel', t('Link from Sitemap'));
        $this->set('linkFromFileManagerLabel', t('Link from File Manager'));
        $this->set('externalLinkLabel', t('External Link'));
        $this->set('showAdditionalFieldsLabel', t('Show additional fields'));
        $this->set('hideAdditionalFieldsLabel', t('Hide additional fields'));
        $this->set('newWindowLabel', t('Open in new window'));
        $this->set('yesLabel', t('Yes'));
        $this->set('noLabel', t('No'));
        $this->set('overrideThumbnailDimensionsLabel', t('Override Thumbnail dimensions'));
        $this->set('overrideFullscreenImageDimensionsLabel', t('Override Fullscreen Image dimensions'));
        $this->set('widthLabel', t('Width'));
        $this->set('heightLabel', t('Height'));
        $this->set('cropLabel', t('Crop'));
        $this->set('pxLabel', t('px'));
        $this->set('nothingSelectedLabel', t('Nothing selected'));
        $this->set('noResultsMatchedLabel', t('No results matched {0}'));
        $this->set('selectAllLabel', t('Select All'));
        $this->set('deselectAllLabel', t('Deselect All'));

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

    public function config($type, $configBlockHandle)
    {

        $this->view();

        $this->set('type', $type);

        // Replace some variables when loading config
        $this->set('formAction', $this->action('config', $type, $configBlockHandle));

        // Load values from config (only for non-post)
        if (!$this->post()) {

            if ($type === 'predefined') {
                $pkg = Package::getByHandle('block_builder');
                $configPath = DIR_PACKAGES . DIRECTORY_SEPARATOR . $pkg->getPackageHandle() . DIRECTORY_SEPARATOR . 'predefined_configs' . DIRECTORY_SEPARATOR . $configBlockHandle . '.json';
            } else {
                $configPath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $configBlockHandle . DIRECTORY_SEPARATOR . 'config-bb.json';
            }

            if (file_exists($configPath)) {

                $configJson = file_get_contents($configPath);
                $config = json_decode($configJson, true);

                if (is_array($config)) {

                    foreach ($config as $k => $v) {
                        if ($k == 'basic') {
                            $this->set('basic', array_values($v));
                        } elseif ($k == 'entries') {
                            $this->set('entries', array_values($v));
                        } else {
                            $this->set($k, $v);
                        }
                    }

                    $pkg = Package::getByHandle('block_builder');
                    $pkgVersion = $pkg->getPackageVersion();
                    $message = t('You have successfully loaded configuration from:') . ' ' . $config['blockName'];
                    if ($config['version']) {
                        $message .= ' (' . $config['version'] . ')';
                    }
                    $message .= '.';
                    if (!$config['version']) {
                        $message .= PHP_EOL . t('Loaded configuration was generated by unknown version of package.');
                    } else if ($config['version'] and $config['version'] != $pkgVersion) {
                        $message .= PHP_EOL . t('Loaded configuration (%s) was generated by version of package different than currently installed (%s).', $config['version'], $pkgVersion);
                    }
                    $this->set('message', $message);
                }

            }

        }

    }

    public function block_created()
    {

        $this->set('success', t('Block "%s" has been successfully created. Go to "Block Types" page to manually install it.', $this->get('blockNameCreated')));

        $this->view();

    }

    public function block_created_and_installed()
    {

        $this->set('success', t('Block "%s" has been successfully created and installed.', $this->get('blockNameCreated')));

        $this->view();

    }

    public function block_refreshed()
    {

        $this->set('success', t('Block "%s" has been successfully rebuild and refreshed.', $this->get('blockNameRefreshed')));

        $this->view();

    }

    public function configs()
    {

        $pkg = Package::getByHandle('block_builder');
        $pkgVersion = $pkg->getPackageVersion();
        $this->set('pkgVersion', $pkgVersion);

        $al = AssetList::getInstance();
        $al->register('css', 'bb/styles', 'css_files/styles.css', [], $pkg);
        $this->requireAsset('css', 'bb/styles');

        // Configs from block types
        $blockTypePaths = glob(DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

        $blockTypes = [];
        $i = 0;

        if (is_array($blockTypePaths)) {

            foreach ($blockTypePaths as $blockTypePath) {

                $blockTypeHandle = basename($blockTypePath);
                $configPath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $blockTypeHandle . DIRECTORY_SEPARATOR . 'config-bb.json';

                if (file_exists($configPath)) {

                    $configJson = file_get_contents($configPath);
                    $config = json_decode($configJson, true);

                    $blockTypes[$i]['handle'] = $blockTypeHandle;
                    $blockTypes[$i]['name'] = $config['blockName'];
                    $blockTypes[$i]['description'] = $config['blockDescription'];
                    $blockTypes[$i]['version'] = $config['version'];

                    $i++;
                }

            }

        }

        $this->set('blockTypes', $blockTypes);

        // Predefined configs
        $predefinedConfigPaths = glob(DIR_PACKAGES . DIRECTORY_SEPARATOR . $pkg->getPackageHandle() . DIRECTORY_SEPARATOR . 'predefined_configs' . DIRECTORY_SEPARATOR .  '*');

        $predefinedConfigs = [];
        $i = 0;

        if (is_array($predefinedConfigPaths)) {
            foreach ($predefinedConfigPaths as $predefinedConfigPath) {
                $configJson = file_get_contents($predefinedConfigPath);
                $config = json_decode($configJson, true);

                $predefinedConfigs[$i]['handle'] = $config['blockHandle'];
                $predefinedConfigs[$i]['name'] = $config['blockName'];
                $predefinedConfigs[$i]['description'] = $config['blockDescription'];
                $predefinedConfigs[$i]['version'] = $config['version'];
                $i++;
            }
        }
        $this->set('predefinedConfigs', $predefinedConfigs);

        $this->render('dashboard/blocks/block_builder/configs');

    }

    public function refresh_warning()
    {
        $this->render('dashboard/blocks/block_builder/refresh_warning');
    }

    private function removeDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->removeDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

}
