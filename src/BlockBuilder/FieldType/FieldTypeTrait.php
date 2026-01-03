<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType;

trait FieldTypeTrait
{
    public static function getProperties(): array
    {
        $properties = [];

        $dtoClass = static::class . 'Dto';
        $reflection = new \ReflectionClass($dtoClass);

        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $properties[] = $param->getName();
            }
        }

        return $properties;
    }
}
