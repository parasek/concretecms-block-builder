<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

final class BlockHandleFormat
{
    private const string PATTERN = '/^[a-z][a-z_]*[a-z]$/';

    public static function isValid(string $handle): bool
    {
        return preg_match(self::PATTERN, $handle) === 1;
    }
}
