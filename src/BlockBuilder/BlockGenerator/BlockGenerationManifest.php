<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class BlockGenerationManifest
{
    public function __construct(
        public bool $shouldInstallBlock,
        public bool $shouldRebuildBlock,
        public string $blockHandlePascalCase,
        public string $blockHandleKebabCase,
        public string $blockPath,
        public string $blockPublicPath,
        public ?string $blockIconPath,
        public ?string $blockIconPublicPath,
        public ?UploadedFile $customBlockIcon,
        public string $databaseTableName,
        public string $entriesDatabaseTableName,
    ) {
    }
}
