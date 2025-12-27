<?php

declare(strict_types=1);

namespace BlockBuilder\Service;

readonly class NamingConventionService
{
    public function convertToPascalCase($handle): string
    {
        $handleParts = explode('_', $handle);

        if (is_array($handleParts)) {
            $namespace = '';

            foreach ($handleParts as $handlePart) {
                $namespace .= ucfirst($handlePart);
            }
        } else {
            $namespace = ucfirst($handle);
        }

        return $namespace;
    }

    public function convertToKebabCase($handle): array|string
    {
        return str_replace('_', '-', $handle);
    }
}
