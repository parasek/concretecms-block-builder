<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\WysiwygEditor;

final class WysiwygEditorPresets
{
    /** @return array<string, array{label: string, allowedTags: string, customConfig: string}> */
    public static function getAll(): array
    {
        $document = ['name' => 'document', 'items' => ['Source', '-']];
        $basicStyles = [
            'name' => 'basicstyles',
            'items' => ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'],
        ];
        $links = ['name' => 'links', 'items' => ['Unlink']];

        return [
            'default' => [
                'label' => t('Default editor - No custom configuration, all tags allowed'),
                'allowedTags' => '',
                'customConfig' => '',
            ],
            'basic_editor' => [
                'label' => t('Basic editor'),
                'allowedTags' => '<span><b><strong><i><em><u><sub><sup><br>',
                'customConfig' => json_encode(['toolbar' => [$document, $basicStyles, $links]], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            ],
            'basic_editor_with_styles' => [
                'label' => t('Basic editor with styles'),
                'allowedTags' => '<div><p><blockquote><span><b><strong><i><em><u><sub><sup><br><h1><h2><h3><h4><h5><h6><ul><ol><li>',
                'customConfig' => json_encode(['toolbar' => [
                    $document,
                    $basicStyles,
                    ['name' => 'paragraph', 'items' => ['NumberedList', 'BulletedList']],
                    ['name' => 'styles', 'items' => ['Styles', 'Format']],
                    $links,
                ]], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            ],
        ];
    }
}
