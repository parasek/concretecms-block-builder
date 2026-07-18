<?php

declare(strict_types=1);

namespace BlockBuilder\Block\ReservedWord;

readonly class HandleNormalizer
{
    public function normalize(string $handle): string
    {
        return strtolower(str_replace('_', '', $handle));
    }
}
