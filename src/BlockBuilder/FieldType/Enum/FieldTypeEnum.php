<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Enum;

use JsonSerializable;

enum FieldTypeEnum: string implements JsonSerializable
{
    case Text = 'text_field';
    case Textarea = 'textarea';
    case Number = 'number';
    case WysiwygEditor = 'wysiwyg_editor';
    case SingleChoice = 'select_field';
    case MultipleChoice = 'select_multiple_field';
    case FlexLink = 'link';
    case LinkFromSitemap = 'link_from_sitemap';
    case LinkFromFileManager = 'link_from_file_manager';
    case ExternalLink = 'external_link';
    case Image = 'image';
    case Express = 'express';
    case FileSet = 'file_set';
    case HtmlEditor = 'html_editor';
    case DatePicker = 'date_picker';
    case ColorPicker = 'color_picker';
    case IconPicker = 'icon_picker';
    case UserSelector = 'user_selector';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
