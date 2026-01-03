<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;

interface FieldTypeInterface
{
    public static function getEnum(): FieldTypeEnum;
    public static function getHandle(): string;
    public static function getLabel(): string;
    public static function getIcon(): string;
    public static function getProperties(): array;
    public static function getDefaultValues(): array;
    public static function createDtoFromArray(array $data): FieldTypeDtoInterface;
    public static function getControllerPhpStrategyClass(): string;
    public static function getErrorMessages(FieldTypeContextEnum $context): array;
    public function validate(array $data): array;
}
