<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Validation;

final class ChoiceOptionListValidator
{
    public static function hasValidShape(mixed $options): bool
    {
        if (!is_string($options)) {
            return false;
        }

        if ($options === '') {
            return true;
        }

        $lines = preg_split('/\r\n|\r|\n/', $options);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = explode('::', $line);
            if (count($parts) === 1) {
                continue;
            }

            if (count($parts) !== 2) {
                return false;
            }

            [$key, $label] = array_map('trim', $parts);
            if ($label === '' || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]*$/', $key) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function __construct()
    {
    }
}
