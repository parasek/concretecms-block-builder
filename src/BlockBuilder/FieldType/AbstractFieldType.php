<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType;

use LogicException;

abstract class AbstractFieldType implements FieldTypeInterface
{
    public static function getDtoClass(): string
    {
        $dtoClass = static::class . 'Dto';
        if (!is_a($dtoClass, FieldTypeDtoInterface::class, true)) {
            throw new LogicException(sprintf(
                'Field type "%s" must have a corresponding DTO class named "%s" that implements %s.',
                static::class,
                $dtoClass,
                FieldTypeDtoInterface::class,
            ));
        }

        return $dtoClass;
    }

    public static function getProperties(): array
    {
        return array_keys(get_class_vars(static::getDtoClass()));
    }
}
