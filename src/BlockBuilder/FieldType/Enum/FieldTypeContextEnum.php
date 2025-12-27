<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Enum;

use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;

/**
 * Represents the context of field types within a block creation process.
 *
 * Field types can be added as basic (non-repeatable) or repeatable fields,
 * so in many cases we need to know the exact context.
 */
enum FieldTypeContextEnum: string
{
    case BasicFields = 'basic';
    case RepeatableFields = 'entries';

    public function getTabName(): string
    {
        return match ($this) {
            self::BasicFields => NavigationTabEnum::TabBasicInformation->getName(),
            self::RepeatableFields => NavigationTabEnum::TabRepeatableEntries->getName(),
        };
    }

    public function getTabHandle(): string
    {
        return match ($this) {
            self::BasicFields => NavigationTabEnum::TabBasicInformation->getHandle(),
            self::RepeatableFields => NavigationTabEnum::TabRepeatableEntries->getHandle(),
        };
    }
}
