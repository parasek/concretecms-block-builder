<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType;

use ReflectionClass;
use ReflectionException;

trait FieldTypeTrait
{
    public static function getProperties(): array
    {
        $properties = [];

        try {
            $dtoClass = static::class . 'Dto';

            $reflection = new ReflectionClass($dtoClass);

            $constructor = $reflection->getConstructor();
            if ($constructor) {
                foreach ($constructor->getParameters() as $param) {
                    $properties[] = $param->getName();
                }
            }
        } catch (ReflectionException) {
            return [];
        }

        return $properties;
    }
}
