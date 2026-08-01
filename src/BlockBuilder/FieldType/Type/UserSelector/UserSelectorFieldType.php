<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\UserSelector;

use BlockBuilder\FieldType\AbstractFieldType;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;

class UserSelectorFieldType extends AbstractFieldType
{
    public static function getEnum(): FieldTypeEnum
    {
        return FieldTypeEnum::UserSelector;
    }

    public static function getHandle(): string
    {
        return 'user_selector';
    }

    public static function getLabel(): string
    {
        return t('User Selector');
    }

    public static function getIcon(): string
    {
        return 'fas fa-user';
    }

    public static function getDefaultValues(): array
    {
        return [];
    }

    public static function createDtoFromArray(array $data): UserSelectorFieldTypeDto
    {
        return new UserSelectorFieldTypeDto(
            fieldType: self::getEnum(),
            label: trim($data['label'] ?? ''),
            handle: trim($data['handle'] ?? ''),
            required: !empty($data['required']),
            helpText: trim($data['helpText'] ?? ''),
        );
    }

    public static function getErrorMessages(FieldTypeContextEnum $context): array
    {
        return [];
    }

    public function validate(array $data): array
    {
        return [];
    }
}
