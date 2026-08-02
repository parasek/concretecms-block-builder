<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

final class BlockConfigLimits
{
    public const int MAX_FIELDS_PER_COLLECTION = 100;
    public const int MAX_OPTIONS_PER_FIELD = 1_000;
    public const int MAX_SVG_ICONS_PER_FIELD = 100;
    public const int MAX_SVG_ICON_NAME_LENGTH = 100;
    public const int MAX_SVG_ICON_HANDLE_LENGTH = 50;
    public const int MAX_SVG_CONTENT_LENGTH = 100_000;

    private const int MAX_DEFAULT_STRING_LENGTH = 10_000;
    private const int MAX_PLACEHOLDER_LENGTH = 255;
    private const int MAX_LONG_TEXT_LENGTH = 100_000;
    private const int MAX_CUSTOM_CODE_LENGTH = 500_000;

    private const array TOP_LEVEL_LONG_TEXT_PROPERTIES = [
        'blockDescription',
        'excludedFromRemoval',
        'messageBasicTab',
        'messageEntriesTab',
    ];

    private const array TOP_LEVEL_CUSTOM_CODE_PROPERTIES = [
        'registerViewAssetsCustomCode',
        'viewCustomCode',
        'customControllerMethods',
    ];

    private const array FIELD_LONG_TEXT_PROPERTIES = [
        'options',
        'customConfig',
        'defaultValue',
    ];

    public static function getTopLevelStringMaximum(string $propertyName): int
    {
        if (in_array($propertyName, self::TOP_LEVEL_CUSTOM_CODE_PROPERTIES, true)) {
            return self::MAX_CUSTOM_CODE_LENGTH;
        }

        if (in_array($propertyName, self::TOP_LEVEL_LONG_TEXT_PROPERTIES, true)) {
            return self::MAX_LONG_TEXT_LENGTH;
        }

        return self::MAX_DEFAULT_STRING_LENGTH;
    }

    public static function getFieldStringMaximum(string $propertyName): int
    {
        if ($propertyName === 'placeholder') {
            return self::MAX_PLACEHOLDER_LENGTH;
        }

        if ($propertyName === 'customCode') {
            return self::MAX_CUSTOM_CODE_LENGTH;
        }

        if (in_array($propertyName, self::FIELD_LONG_TEXT_PROPERTIES, true)) {
            return self::MAX_LONG_TEXT_LENGTH;
        }

        return self::MAX_DEFAULT_STRING_LENGTH;
    }
}
