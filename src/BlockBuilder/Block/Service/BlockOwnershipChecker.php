<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Exception\ConfigLoadingException;

readonly class BlockOwnershipChecker
{
    public function __construct(private BlockConfigReader $blockConfigReader)
    {
    }

    public function isOwnedApplicationBlock(string $blockHandle): bool
    {
        try {
            $this->blockConfigReader->getConfigFromApplicationFolder($blockHandle);
        } catch (ConfigLoadingException) {
            return false;
        }

        return true;
    }
}
