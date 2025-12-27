<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Dto;

use BlockBuilder\Block\Exception\ConfigLoadingException;

final readonly class BlockConfigCollectionLoadResult
{
    /**
     * @param list<BlockConfigDto> $configs
     * @param list<ConfigLoadingException> $errors
     */
    public function __construct(
        public array $configs,
        public array $errors,
    ) {
    }
}
