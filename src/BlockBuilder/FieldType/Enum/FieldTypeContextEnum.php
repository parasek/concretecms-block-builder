<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Enum;

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

    public function getName(): string
    {
        return match ($this) {
            self::BasicFields => t('Basic information'),
            self::RepeatableFields => t('Repeatable entries'),
        };
    }

    public function getTabName(): string
    {
        return match ($this) {
            self::BasicFields => t('Tab: Basic information'),
            self::RepeatableFields => t('Tab: Repeatable entries'),
        };
    }

    public function getTabHandle(): string
    {
        return match ($this) {
            self::BasicFields => 'tab-basic-information',
            self::RepeatableFields => 'tab-repeatable-entries',
        };
    }
}
