<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Factory;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpFieldTypeStrategyInterface;
use BlockBuilder\FieldType\FieldTypeInterface;

class ControllerPhpFieldTypeStrategyFactory
{
    public function create(FieldTypeInterface $fieldType): ControllerPhpFieldTypeStrategyInterface
    {
        $strategyClass = $fieldType->getControllerPhpStrategyClass();

        return new $strategyClass();
    }
}
