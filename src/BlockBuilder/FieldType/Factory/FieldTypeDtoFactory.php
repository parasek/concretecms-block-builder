<?php

namespace BlockBuilder\FieldType\Factory;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

class FieldTypeDtoFactory
{
    public function fromArray(array $data): FieldTypeDtoInterface
    {
        $type = FieldTypeEnum::fromHandle($data['fieldType']);

        return $type->createDtoFromArray($data);
    }
}
