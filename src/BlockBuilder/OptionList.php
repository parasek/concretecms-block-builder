<?php namespace BlockBuilder;

use Concrete\Core\Block\BlockType\Set as BlockTypeSet;

defined('C5_EXECUTE') or die('Access Denied.');

class OptionList
{

    public function getBlockTypeSets()
    {

        $options = [];
        $options[''] = t('None');

        $blockTypeSets = BlockTypeSet::getList();

        // Those should be listed here manually, so translations will work
        $translations = [
            t('Basic'),
            t('Navigation'),
            t('Forms'),
            t('Express'),
            t('Social Networking'),
            t('Multimedia'),
        ];

        foreach ($blockTypeSets as $blockTypeSet) {
            $options[$blockTypeSet->btsHandle] = t($blockTypeSet->btsName);
        }

        return $options;

    }

    public function getEntriesAsFirstTabOptions()
    {

        $options = [];
        $options[0] = t('No');
        $options[1] = t('Yes');

        return $options;

    }

    public function getFieldTypes()
    {

        $options = [];
        $options[''] = t('+ Add new field type');
        $options['text_field'] = t('Text field');
        $options['textarea'] = t('Textarea');
        $options['wysiwyg_editor'] = t('WYSIWYG Editor');
        $options['select_field'] = t('Select field');
        $options['link'] = t('Link with Type Selection');
        $options['link_from_sitemap'] = t('Link from Sitemap');
        $options['link_from_file_manager'] = t('Link from File Manager');
        $options['external_link'] = t('External Link');
        $options['image'] = t('Image');
        $options['html_editor'] = t('HTML Editor');
        $options['date_picker'] = t('Date Picker');

        return $options;

    }

    public function getDividerOptions()
    {

        $options = [];
        $options['smart'] = t('Only if the field type consists of more than 1 element (default)');
        $options['always'] = t('Always');
        $options['never'] = t('Never');

        return $options;

    }

    public function getInstallBlockOptions()
    {

        $options = [];
        $options[0] = t('No');
        $options[1] = t('Yes');

        return $options;

    }

    public function getSelectFieldTypes()
    {

        $options = [
            [
                'label' => t('Single choice'),
                'options' => [
                    'default_select' => t('Default Select Field'),
                    'enhanced_select' => t('Enhanced Select Field'),
                    'radio_list' => t('Radio List'),
                ],
            ],
            [
                'label' => t('Multiple choice'),
                'options' => [
                    'default_multiselect' => t('Default Multiselect Field'),
                    'enhanced_multiselect' => t('Enhanced Multiselect Field'),
                    'checkbox_list' => t('Checkbox List'),
                ],
            ],
        ];

        return $options;

    }

}
