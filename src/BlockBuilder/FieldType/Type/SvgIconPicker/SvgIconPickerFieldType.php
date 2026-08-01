<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\SvgIconPicker;

use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;

class SvgIconPickerFieldType extends AbstractFieldType
{
    private const int MAX_ICONS = 100;
    private const int MAX_NAME_LENGTH = 100;
    private const int MAX_HANDLE_LENGTH = 50;

    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::SvgIconPicker;
    }

    public static function getHandle(): string
    {
        return 'svg_icon_picker';
    }

    public static function getLabel(): string
    {
        return t('SVG Icon Picker');
    }

    public static function getIcon(): string
    {
        return 'fas fa-icons';
    }

    public static function getDefaultValues(): array
    {
        return ['icons' => []];
    }

    public static function createDtoFromArray(array $data): SvgIconPickerFieldTypeDto
    {
        $icons = [];
        foreach (is_array($data['icons'] ?? null) ? $data['icons'] : [] as $icon) {
            if (!is_array($icon)) {
                continue;
            }

            $icons[] = [
                'name' => trim((string) ($icon['name'] ?? '')),
                'handle' => trim((string) ($icon['handle'] ?? '')),
                'svg' => trim((string) ($icon['svg'] ?? '')),
            ];
        }

        return new SvgIconPickerFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
            icons: $icons,
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [
            'icons|empty' => t('Some "SVG Icon Picker/Icons" fields do not contain any icons (%s).', $context->getTabName()),
            'icons|too_many' => t('Some "SVG Icon Picker/Icons" fields contain more than %s icons (%s).', self::MAX_ICONS, $context->getTabName()),
            'icons|invalid_definition' => t('Some "SVG Icon Picker/Icons" fields contain incomplete or invalid icon definitions (%s).', $context->getTabName()),
            'icons|invalid_handle' => t('Some "SVG Icon Picker/Icons" fields contain invalid icon handles (%s).', $context->getTabName()),
            'icons|duplicate_handle' => t('Icon handles in each "SVG Icon Picker/Icons" field must be unique (%s).', $context->getTabName()),
            'icons|invalid_svg' => t('Some "SVG Icon Picker/Icons" fields contain invalid SVG content (%s).', $context->getTabName()),
        ];
    }

    public function validate(array $data): array
    {
        $icons = $data['icons'] ?? null;
        if (!is_array($icons) || $icons === []) {
            return ['icons|empty'];
        }
        if (count($icons) > self::MAX_ICONS) {
            return ['icons|too_many'];
        }

        $errors = [];
        $handles = [];
        foreach ($icons as $icon) {
            if (!is_array($icon)) {
                $errors[] = 'icons|invalid_definition';
                continue;
            }

            $name = $icon['name'] ?? null;
            $handle = $icon['handle'] ?? null;
            $svg = $icon['svg'] ?? null;
            if (!is_string($name) || !is_string($handle) || !is_string($svg)) {
                $errors[] = 'icons|invalid_definition';
                continue;
            }

            $name = trim($name);
            $handle = trim($handle);
            if ($name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH) {
                $errors[] = 'icons|invalid_definition';
            }
            if (
                strlen($handle) > self::MAX_HANDLE_LENGTH
                || preg_match('/^[a-z][a-z0-9]*(?:[-_][a-z0-9]+)*$/D', $handle) !== 1
            ) {
                $errors[] = 'icons|invalid_handle';
            } elseif (isset($handles[$handle])) {
                $errors[] = 'icons|duplicate_handle';
            } else {
                $handles[$handle] = true;
            }
            if (SvgIconSanitizer::sanitize($svg) === null) {
                $errors[] = 'icons|invalid_svg';
            }
        }

        return array_values(array_unique($errors));
    }
}
