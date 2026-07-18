<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

final class IntegerValueValidator
{
    public static function isInRange(mixed $value, int $minimum, ?int $maximum = null, bool $allowEmpty = false): bool
    {
        if ($allowEmpty && ($value === '' || $value === null)) {
            return true;
        }

        if (is_int($value)) {
            $integerValue = $value;
        } elseif (is_string($value) && $value !== '' && ctype_digit($value)) {
            $normalizedValue = ltrim($value, '0');
            $normalizedValue = $normalizedValue === '' ? '0' : $normalizedValue;
            $maximumInteger = (string) PHP_INT_MAX;

            if (strlen($normalizedValue) > strlen($maximumInteger)
                || (strlen($normalizedValue) === strlen($maximumInteger) && strcmp($normalizedValue, $maximumInteger) > 0)
            ) {
                return false;
            }

            $integerValue = (int) $normalizedValue;
        } else {
            return false;
        }

        return $integerValue >= $minimum && ($maximum === null || $integerValue <= $maximum);
    }

    private function __construct()
    {
    }
}
