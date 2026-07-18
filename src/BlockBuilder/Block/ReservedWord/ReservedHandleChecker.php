<?php

declare(strict_types=1);

namespace BlockBuilder\Block\ReservedWord;

readonly class ReservedHandleChecker
{
    public function __construct(private HandleNormalizer $handleNormalizer)
    {
    }

    public function isHandleAllowed(string $handle): bool
    {
        return $this->isAllowed($handle, ReservedWordCatalog::getFieldHandleWords());
    }

    public function isBlockHandleAllowed(string $handle): bool
    {
        return $this->isAllowed($handle, ReservedWordCatalog::getBlockHandleWords());
    }

    private function isAllowed(string $handle, array $reservedWords): bool
    {
        $normalizedHandle = $this->handleNormalizer->normalize($handle);
        foreach ($reservedWords as $reservedWord) {
            if ($normalizedHandle === $this->handleNormalizer->normalize($reservedWord)) {
                return false;
            }
        }

        return true;
    }
}
