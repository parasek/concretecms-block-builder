<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\Service;

readonly class PhpLiteralFormatter
{
    public function format(mixed $value): string
    {
        return var_export($value, true);
    }
}
