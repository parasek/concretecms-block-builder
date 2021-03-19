<?php namespace BlockBuilder\FileGenerator;

use Concrete\Core\File\Service\File as FileService;
use BlockBuilder\Utility as BlockBuilderUtility;

defined('C5_EXECUTE') or die('Access Denied.');

class DbXml
{

    public function generate($postDataSummary, $postData) {

        $filename = 'db.xml';

        $code = '';
        $code .= '<?xml version="1.0"?>'.PHP_EOL;
        $code .= '<schema version="0.3">'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(1).'<table name="'.$postDataSummary['blockTableName'].'">'.PHP_EOL.PHP_EOL;

        $code .= BlockBuilderUtility::tab(2).'<field name="bID" type="I">'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(3).'<key/>'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
        $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL.PHP_EOL;

        if ($postDataSummary['settingsTab']) {
             $code .= BlockBuilderUtility::tab(2).'<field name="settings" type="X2"></field>'.PHP_EOL.PHP_EOL;
        }

        if ( ! empty($postData['basic'])) {

            foreach ($postData['basic'] as $k => $v) {

                if ($v['fieldType']=='text_field') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="C" size="255"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='textarea') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="X"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='wysiwyg_editor') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="X2"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='select_field') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="C" size="255"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='link') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="X2"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='link_from_sitemap') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="I">'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    if ( ! empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_ending" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_text" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_title" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_new_window" type="I">'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    }
                    $code .= PHP_EOL;
                }

                if ($v['fieldType']=='link_from_file_manager') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="I">'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    if ( ! empty($v['linkFromFileManagerShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_ending" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_text" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_title" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_new_window" type="I">'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    }
                    $code .= PHP_EOL;
                }

                if ($v['fieldType']=='external_link') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="C" size="255"></field>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_protocol" type="C" size="10"></field>'.PHP_EOL;
                    if ( ! empty($v['externalLinkShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_ending" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_text" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_title" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['externalLinkShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_new_window" type="I">'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    }
                    $code .= PHP_EOL;
                }

                if ($v['fieldType']=='image') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="I">'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    if ( ! empty($v['imageShowAltTextField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_alt" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if (
                        ( !empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']) )
                        or
                        ( !empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']) )
                        or
                        ( !empty($v['imageShowAltTextField']) )
                    ) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_data" type="X2"></field>'.PHP_EOL;
                    }
                    $code .= PHP_EOL;
                }

                if ($v['fieldType']=='html_editor') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="X2"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='date_picker') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="T"></field>'.PHP_EOL.PHP_EOL;
                }

            }

        }

        $code .= BlockBuilderUtility::tab(1).'</table>'.PHP_EOL.PHP_EOL;

        if ( ! empty($postData['entries'])) {

            $code .= BlockBuilderUtility::tab(1).'<table name="'.$postDataSummary['blockTableNameEntries'].'">'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'<field name="id" type="I">'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'<autoincrement/>'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'<key/>'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'<field name="bID" type="I">'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(2).'<field name="position" type="I">'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
            $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL.PHP_EOL;

            foreach ($postData['entries'] as $k => $v) {

                if ($v['fieldType']=='text_field') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="C" size="255"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='textarea') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="X"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='wysiwyg_editor') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="X2"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='select_field') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="C" size="255"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='link') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="X2"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='link_from_sitemap') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="I">'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    if ( ! empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '<field name="' . $v['handle'] . '_ending" type="C" size="255"></field>' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '<field name="' . $v['handle'] . '_text" type="C" size="255"></field>' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '<field name="' . $v['handle'] . '_title" type="C" size="255"></field>' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromSitemapShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_new_window" type="I">'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    }
                    $code .= PHP_EOL;
                }

                if ($v['fieldType']=='link_from_file_manager') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="I">'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    if ( ! empty($v['linkFromFileManagerShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '<field name="' . $v['handle'] . '_ending" type="C" size="255"></field>' . PHP_EOL;
                    }
                    if ( ! empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_text" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_title" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['linkFromFileManagerShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_new_window" type="I">'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    }
                    $code .= PHP_EOL;
                }

                if ($v['fieldType']=='external_link') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="C" size="255"></field>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_protocol" type="C" size="10"></field>'.PHP_EOL;
                    if ( ! empty($v['externalLinkShowEndingField'])) {
                        $code .= BlockBuilderUtility::tab(2) . '<field name="' . $v['handle'] . '_ending" type="C" size="255"></field>' . PHP_EOL;
                    }
                    if ( ! empty($v['externalLinkShowTextField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_text" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['externalLinkShowTitleField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_title" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if ( ! empty($v['externalLinkShowNewWindowField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_new_window" type="I">'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    }
                    $code .= PHP_EOL;
                }

                if ($v['fieldType']=='image') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="I">'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<default value="0"/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<unsigned/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(2).'</field>'.PHP_EOL;
                    if ( ! empty($v['imageShowAltTextField'])) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_alt" type="C" size="255"></field>'.PHP_EOL;
                    }
                    if (
                        ( !empty($v['imageCreateThumbnailImage']) and !empty($v['imageThumbnailEditable']) )
                        or
                        ( !empty($v['imageCreateFullscreenImage']) and !empty($v['imageFullscreenEditable']) )
                        or
                        ( !empty($v['imageShowAltTextField']) )
                    ) {
                        $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'_data" type="X2"></field>'.PHP_EOL;
                    }
                    $code .= PHP_EOL;
                }

                if ($v['fieldType']=='html_editor') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="X2"></field>'.PHP_EOL.PHP_EOL;
                }

                if ($v['fieldType']=='date_picker') {
                    $code .= BlockBuilderUtility::tab(2).'<field name="'.$v['handle'].'" type="T"></field>'.PHP_EOL.PHP_EOL;
                }

            }

            $code .= BlockBuilderUtility::tab(1).'</table>'.PHP_EOL.PHP_EOL;

        }

        $code .= '</schema>';

        $fileService = new FileService();
        $fileService->append($postDataSummary['blockPath']. DIRECTORY_SEPARATOR . $filename, $code);

    }

}
