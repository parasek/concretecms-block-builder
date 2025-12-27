<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType;

use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;

interface FieldTypeInterface
{
    public static function getFieldType(): FieldTypeEnum;

    public static function getLabel(): string;

    public static function getIcon(): string;

    public static function getDefaultValues(): array;

    /**
     * Maps legacy configuration property names to their canonical DTO property names.
     *
     * @return array<string, string>
     */
    public static function getLegacyPropertyAliases(): array;

    /**
     * @return class-string<FieldTypeDtoInterface>
     */
    public static function getDtoClass(): string;

    /**
     * @return string[]
     */
    public static function getProperties(): array;

    public static function createDtoFromArray(array $data): FieldTypeDtoInterface;

    public static function getErrorMessages(FieldTypeContextEnum $context): array;

    public function validate(array $data): array;
}
