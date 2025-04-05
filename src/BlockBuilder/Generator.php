<?php namespace BlockBuilder;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\File\Service\File as FileService;
use Concrete\Core\Foundation\Environment;
use Concrete\Core\Permission\Checker as Permissions;
use BlockBuilder\FileGenerator\ControllerPhp as FileGeneratorControllerPhp;
use BlockBuilder\FileGenerator\ViewPhp as FileGeneratorViewPhp;
use BlockBuilder\FileGenerator\DbXml as FileGeneratorDbXml;
use BlockBuilder\FileGenerator\FormPhp as FileGeneratorFormPhp;
use BlockBuilder\Utility as BlockBuilderUtility;
use Doctrine\ORM\EntityManagerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

class Generator
{

    public function generateBlock($postData)
    {

        unset($postData['ccm_token']);
        unset($postData['ccm-submit-url-form']);

        $postDataSummary = [];

        $postDataSummary['blockHandle'] = $postData['blockHandle'];
        $postDataSummary['blockHandleDashed'] = BlockBuilderUtility::convertHandleToDashed($postDataSummary['blockHandle']);
        $postDataSummary['blockNamespace'] = BlockBuilderUtility::convertHandleToNamespace($postDataSummary['blockHandle']);
        $postDataSummary['blockTableName'] = 'bt' . BlockBuilderUtility::convertHandleToNamespace($postDataSummary['blockHandle']);
        $postDataSummary['blockTableNameEntries'] = 'bt' . BlockBuilderUtility::convertHandleToNamespace($postDataSummary['blockHandle']) . 'Entries';

        $postDataSummary['blockPath'] = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $postDataSummary['blockHandle'];
        $postDataSummary['templatePath'] = DIR_BASE . DIRECTORY_SEPARATOR . DIRNAME_PACKAGES . DIRECTORY_SEPARATOR . 'block_builder' . DIRECTORY_SEPARATOR . 'generator_templates';

        // Crawl through settings/entries
        $postDataSummary['exportPageColumns'] = [];
        $postDataSummary['exportFileColumns'] = [];

        $postDataSummary['searchableFields'] = [];
        $postDataSummary['searchableEntryFields'] = [];

        $postDataSummary['requiredFields'] = [];
        $postDataSummary['requiredEntryFields'] = [];

        $postDataSummary['settingsTab'] = false;

        $postDataSummary['wysiwygEditorUsed'] = false;
        $postDataSummary['htmlEditorUsed'] = false;
        $postDataSummary['linkUsed'] = false;
        $postDataSummary['linkFromSitemapUsed'] = false;
        $postDataSummary['linkFromFileManagerUsed'] = false;
        $postDataSummary['externalLinkUsed'] = false;
        $postDataSummary['imageUsed'] = false;
        $postDataSummary['fileSetUsed'] = false;
        $postDataSummary['iconPickerUsed'] = false;
        $postDataSummary['numberUsed'] = false;

        $postDataSummary['wysiwygEditorUsed_entry'] = false;
        $postDataSummary['htmlEditorUsed_entry'] = false;
        $postDataSummary['linkUsed_entry'] = false;
        $postDataSummary['linkFromSitemapUsed_entry'] = false;
        $postDataSummary['linkFromFileManagerUsed_entry'] = false;
        $postDataSummary['externalLinkUsed_entry'] = false;
        $postDataSummary['imageUsed_entry'] = false;
        $postDataSummary['expressUsed_entry'] = false;
        $postDataSummary['fileSetUsed_entry'] = false;
        $postDataSummary['datePickerUsed_entry'] = false;
        $postDataSummary['iconPickerUsed_entry'] = false;
        $postDataSummary['numberUsed_entry'] = false;

        $postDataSummary['entryTitleSource'] = false;

        if (!empty($postData['basic'])) {

            foreach ($postData['basic'] as $k => $v) {

                // Export fields
                if ($v['fieldType'] == 'link_from_sitemap') {
                    if (!in_array($v['handle'], $postDataSummary['exportPageColumns'])) {
                        $postDataSummary['exportPageColumns'][] = $v['handle'];
                    }
                }

                if ($v['fieldType'] == 'link_from_file_manager' or $v['fieldType'] == 'image') {
                    if (!in_array($v['handle'], $postDataSummary['exportFileColumns'])) {
                        $postDataSummary['exportFileColumns'][] = $v['handle'];
                    }
                }

                // Searchable fields
                if (in_array($v['fieldType'], ['text_field', 'textarea', 'wysiwyg_editor', 'html_editor'])) {
                    if (!in_array($v['handle'], $postDataSummary['searchableFields'])) {
                        $postDataSummary['searchableFields'][] = $v['handle'];
                    }
                }

                // Required fields
                if (!empty($v['required'])) {
                    if (!in_array($v['handle'], $postDataSummary['requiredFields'])) {
                        $postDataSummary['requiredFields'][] = $v['handle'];
                    }
                }

                // Check if given field is used
                if ($v['fieldType'] == 'wysiwyg_editor') {
                    $postDataSummary['wysiwygEditorUsed'] = true;
                }

                if ($v['fieldType'] == 'html_editor') {
                    $postDataSummary['htmlEditorUsed'] = true;
                }

                if ($v['fieldType'] == 'link') {
                    $postDataSummary['linkUsed'] = true;
                }

                if ($v['fieldType'] == 'link_from_sitemap') {
                    $postDataSummary['linkFromSitemapUsed'] = true;
                }

                if ($v['fieldType'] == 'link_from_file_manager') {
                    $postDataSummary['linkFromFileManagerUsed'] = true;
                }

                if ($v['fieldType'] == 'external_link') {
                    $postDataSummary['externalLinkUsed'] = true;
                }

                if ($v['fieldType'] == 'image') {
                    $postDataSummary['imageUsed'] = true;
                }

                if ($v['fieldType'] == 'file_set') {
                    $postDataSummary['fileSetUsed'] = true;
                }

                if ($v['fieldType'] == 'icon_picker') {
                    $postDataSummary['iconPickerUsed'] = true;
                }

                if ($v['fieldType'] == 'number') {
                    $postDataSummary['numberUsed'] = true;
                }

            }

        }

        if (!empty($postData['entries'])) {

            foreach ($postData['entries'] as $k => $v) {

                // Export fields
                if ($v['fieldType'] == 'link_from_sitemap') {
                    if (!in_array($v['handle'], $postDataSummary['exportPageColumns'])) {
                        $postDataSummary['exportPageColumns'][] = $v['handle'];
                    }
                }

                if ($v['fieldType'] == 'link_from_file_manager' or $v['fieldType'] == 'image') {
                    if (!in_array($v['handle'], $postDataSummary['exportFileColumns'])) {
                        $postDataSummary['exportFileColumns'][] = $v['handle'];
                    }
                }

                // Searchable fields
                if (in_array($v['fieldType'], ['text_field', 'textarea', 'wysiwyg_editor', 'html_editor'])) {
                    if (!in_array($v['handle'], $postDataSummary['searchableEntryFields'])) {
                        $postDataSummary['searchableEntryFields'][] = $v['handle'];
                    }
                }

                // Required fields
                if (!empty($v['required'])) {
                    if (!in_array($v['handle'], $postDataSummary['requiredEntryFields'])) {
                        $postDataSummary['requiredEntryFields'][] = $v['handle'];
                    }
                }

                // Check if Settings tab should be created
                if ($v['fieldType'] == 'image') {
                    if (
                        (!empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']))
                        or
                        (!empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']))
                    ) {
                        $postDataSummary['settingsTab'] = true;
                    }
                }

                // Check if given field is used
                if ($v['fieldType'] == 'wysiwyg_editor') {
                    $postDataSummary['wysiwygEditorUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'html_editor') {
                    $postDataSummary['htmlEditorUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'link') {
                    $postDataSummary['linkUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'link_from_sitemap') {
                    $postDataSummary['linkFromSitemapUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'link_from_file_manager') {
                    $postDataSummary['linkFromFileManagerUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'external_link') {
                    $postDataSummary['externalLinkUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'image') {
                    $postDataSummary['imageUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'express') {
                    $postDataSummary['expressUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'file_set') {
                    $postDataSummary['fileSetUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'date_picker') {
                    $postDataSummary['datePickerUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'icon_picker') {
                    $postDataSummary['iconPickerUsed_entry'] = true;
                }

                if ($v['fieldType'] == 'number') {
                    $postDataSummary['numberUsed_entry'] = true;
                }

                // entryTitleSource
                if (!empty($v['titleSource'])) {
                    $postDataSummary['entryTitleSource'] = $v['handle'];
                }

            }

        }

        // 1. Check permissions
        $p = new Permissions();

        if ($p->canInstallPackages()) {

            // 2. Create folder (this happens only when building new block)
            if (!file_exists($postDataSummary['blockPath'])) {
                mkdir($postDataSummary['blockPath']);
            }

            // 3. Generate files
            $this->generateConfigBbJson($postData, $postDataSummary);

            $this->generateIconPng(false, $postDataSummary);
            $this->generateScrapbookPhp(false, $postDataSummary);
            $this->generateComposerPhp(false, $postDataSummary);
            $this->generateControllerPhp($postData, $postDataSummary);
            $this->generateViewPhp($postData, $postDataSummary);
            $this->generateDbXml($postData, $postDataSummary);
            $this->generateFormCss(false, $postDataSummary);

            if (!empty($postData['basic']) or !empty($postData['entries'])) {
                $this->generateAddPhp(false, $postDataSummary);
                $this->generateEditPhp(false, $postDataSummary);
                $this->generateFormPhp($postData, $postDataSummary);
            }

            if (!empty($postData['entries'])) {
                $this->generateAutoJs(false, $postDataSummary);
            }

            // 4. Refresh (if clicked) or Install block (if selected)
            if (!empty($postData['refresh_block'])) {
                $blockType = BlockType::getByHandle($postData['blockHandle']);
                $app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
                $em = $app->make(EntityManagerInterface::class);
                $bt = $em->find(BlockTypeEntity::class, $blockType->getBlockTypeID());
                try {
                    $bt->refresh();
                    return ['handle' => $postData['blockName'], 'blockInstalled' => false, 'blockRefreshed' => true];
                } catch (UserMessageException $e) {
                    $this->flash('error', $e->getMessage());
                }

            } elseif ($postData['installBlock']) {

                try {

                    $env = Environment::get();
                    $env->clearOverrideCache();

                    BlockType::installBlockType($postDataSummary['blockHandle']);

                    return ['handle' => $postData['blockName'], 'blockInstalled' => true, 'blockRefreshed' => false];

                } catch (\Exception $e) {
                    return t($e->getMessage());
                }

            } else {

                return ['handle' => $postData['blockName'], 'blockInstalled' => false, 'blockRefreshed' => false];

            }

        } else {

            return t('You do not have permission to install custom block types or add-ons.');

        }

        return t('Oops! Something went wrong...');

    }

    private function generateIconPng($postData, $postDataSummary)
    {

        $filename = 'icon.png';

        copy($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . $filename, $postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename);

    }

    private function generateAddPhp($postData, $postDataSummary)
    {

        $filename = 'add.php';

        copy($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . $filename, $postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename);

    }

    private function generateComposerPhp($postData, $postDataSummary)
    {

        $filename = 'composer.php';

        copy($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . $filename, $postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename);

    }

    private function generateEditPhp($postData, $postDataSummary)
    {

        $filename = 'edit.php';

        copy($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . $filename, $postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename);

    }

    private function generateScrapbookPhp($postData, $postDataSummary)
    {

        $filename = 'scrapbook.php';

        copy($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . $filename, $postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename);

    }

    private function generateFormCss($postData, $postDataSummary)
    {

        $filename = 'form.css';

        mkdir($postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . 'css_files');

        copy($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . 'css_files' . DIRECTORY_SEPARATOR . $filename, $postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . 'css_files' . DIRECTORY_SEPARATOR . $filename);

    }

    private function generateConfigBbJson($postData, $postDataSummary)
    {

        $filename = 'config-bb.json';

        unset($postData['refresh_block']);

        $fileService = new FileService();
        $fileService->append($postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename, json_encode($postData, JSON_PRETTY_PRINT));

    }

    private function generateAutoJs($postData, $postDataSummary)
    {

        $filename = 'auto.js';

        $code = file_get_contents($postDataSummary['templatePath'] . DIRECTORY_SEPARATOR . $filename);
        $code = str_replace('[[[BLOCK_HANDLE_DASHED]]]', $postDataSummary['blockHandleDashed'], $code);

        $fileService = new FileService();
        $fileService->append($postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename, $code);

    }

    private function generateControllerPhp($postData, $postDataSummary)
    {

        $fileGeneratorControllerPhp = new FileGeneratorControllerPhp();
        $fileGeneratorControllerPhp->generate($postDataSummary, $postData);

    }

    private function generateViewPhp($postData, $postDataSummary)
    {

        $fileGeneratorViewPhp = new FileGeneratorViewPhp();
        $fileGeneratorViewPhp->generate($postDataSummary, $postData);

    }

    private function generateDbXml($postData, $postDataSummary)
    {

        $fileGeneratorDbXml = new FileGeneratorDbXml();
        $fileGeneratorDbXml->generate($postDataSummary, $postData);

    }

    private function generateFormPhp($postData, $postDataSummary)
    {

        $fileGeneratorFormPhp = new FileGeneratorFormPhp();
        $fileGeneratorFormPhp->generate($postDataSummary, $postData);

    }

}
