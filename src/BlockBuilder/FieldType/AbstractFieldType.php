<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType;

abstract class AbstractFieldType implements FieldTypeInterface
{
    /**
     * @var array<string, string>
     */
    protected const array LEGACY_PROPERTY_ALIASES = [];

    public static function getLegacyPropertyAliases(): array
    {
        $aliases = static::LEGACY_PROPERTY_ALIASES;
        $canonicalProperties = static::getProperties();

        foreach ($aliases as $legacyProperty => $canonicalProperty) {
            if (
                !is_string($legacyProperty)
                || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $legacyProperty) !== 1
            ) {
                throw new \LogicException(sprintf('Field type "%s" declares an invalid legacy property name.', static::class));
            }
            if (
                !is_string($canonicalProperty)
                || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $canonicalProperty) !== 1
            ) {
                throw new \LogicException(sprintf('Field type "%s" declares an invalid canonical property name for legacy property "%s".', static::class, $legacyProperty));
            }
            if ($legacyProperty === $canonicalProperty) {
                throw new \LogicException(sprintf('Field type "%s" maps legacy property "%s" to itself.', static::class, $legacyProperty));
            }
            if (in_array($legacyProperty, $canonicalProperties, true)) {
                throw new \LogicException(sprintf('Legacy property "%s" of field type "%s" is already a canonical DTO property.', $legacyProperty, static::class));
            }
            if (!in_array($canonicalProperty, $canonicalProperties, true)) {
                throw new \LogicException(sprintf('Legacy property "%s" of field type "%s" maps to unknown DTO property "%s".', $legacyProperty, static::class, $canonicalProperty));
            }
        }

        return $aliases;
    }

    public static function getDtoClass(): string
    {
        $dtoClass = static::class . 'Dto';
        if (!is_a($dtoClass, FieldTypeDtoInterface::class, true)) {
            throw new \LogicException(sprintf('Field type "%s" must have a corresponding DTO class named "%s" that implements %s.', static::class, $dtoClass, FieldTypeDtoInterface::class));
        }

        return $dtoClass;
    }

    public static function getProperties(): array
    {
        return array_keys(get_class_vars(static::getDtoClass()));
    }
}
