<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\SvgIconPicker;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

readonly class SvgIconPickerFieldTypeDto implements FieldTypeDtoInterface
{
    /**
     * @param array<int, array{name: string, handle: string, svg: string}> $icons
     */
    public function __construct(
        public FieldTypeEnum $fieldType,
        public string $label,
        public string $handle,
        public bool $required,
        public ?string $helpText,
        public array $icons,
    ) {
    }
}
