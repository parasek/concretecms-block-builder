<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

final class BlockHandleFormat
{
    public const int MIN_LENGTH = 3;
    public const int MAX_LENGTH = 50;

    private const string ALLOWED_CHARACTERS_PATTERN = '/^[a-z_]+$/';

    public static function isValid(string $handle): bool
    {
        return self::isLengthValid($handle)
            && self::containsOnlyAllowedCharacters($handle)
            && self::hasValidBoundaryCharacters($handle)
            && !self::containsConsecutiveUnderscores($handle);
    }

    public static function isLengthValid(string $handle): bool
    {
        $length = mb_strlen($handle);

        return $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH;
    }

    public static function containsOnlyAllowedCharacters(string $handle): bool
    {
        return preg_match(self::ALLOWED_CHARACTERS_PATTERN, $handle) === 1;
    }

    public static function hasValidBoundaryCharacters(string $handle): bool
    {
        return !str_starts_with($handle, '_') && !str_ends_with($handle, '_');
    }

    public static function containsConsecutiveUnderscores(string $handle): bool
    {
        return str_contains($handle, '__');
    }
}
