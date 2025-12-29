<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Dto;

readonly class ConfigDto
{
    public function __construct(
        public ?string $blockBuilderVersion,
        public ?string $concreteVersion,
        public ?string $phpVersion,
        public ?string $createdAt,
    ) {
    }
}
