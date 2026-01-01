<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class CreateBlockManifestDto
{
    public function __construct(
        public bool $shouldBlockBeInstalled,
        public bool $shouldBlockBeRebuilt,
        public ?string $blockHandlePascalCase,
        public ?string $blockHandleKebabCase,
        public ?string $blockPath,
        public ?string $blockPublicPath,
        public ?string $blockIconPath,
        public ?string $blockIconPublicPath,
        public ?UploadedFile $customBlockIcon,
        public ?string $databaseTableName,
        public ?string $entriesDatabaseTableName,
    ) {
    }
}
