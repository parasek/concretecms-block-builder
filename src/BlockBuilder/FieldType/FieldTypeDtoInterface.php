<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;

interface FieldTypeDtoInterface
{
    public FieldTypeEnum $fieldType { get; }

    public string $label { get; }

    public string $handle { get; }
}
