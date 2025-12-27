<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Dto;

readonly class CreateBlockManifestDto
{
    public function __construct(
        public bool $shouldBlockBeInstalled,
        public bool $shouldBlockBeRebuilt,
        public ?string $blockHandlePascalCase,
        public ?string $blockHandleKebabCase,
        public ?string $blockPath,
        public ?string $databaseTableName,
        public ?string $entriesDatabaseTableName,
    ) {
    }
}
