<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Dto\BlockGenerationManifest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class BlockGenerationManifestFactory
{
    public function create(
        BlockConfigDto $config,
        bool $rebuildBlock,
        ?string $blockIcon,
        ?UploadedFile $customBlockIcon,
    ): BlockGenerationManifest
    {
        $blockHandlePascalCase = $this->convertHandleToPascalCase($config->blockHandle);
        $blockPublicPath = DIRECTORY_SEPARATOR . DIRNAME_APPLICATION . DIRECTORY_SEPARATOR . DIRNAME_BLOCKS . DIRECTORY_SEPARATOR . $config->blockHandle;
        $blockPath = DIR_BASE . $blockPublicPath;

        return new BlockGenerationManifest(
            shouldBlockBeInstalled: $config->installBlock,
            shouldBlockBeRebuilt: $rebuildBlock,
            blockHandlePascalCase: $blockHandlePascalCase,
            blockHandleKebabCase: $this->convertHandleToKebabCase($config->blockHandle),
            blockPath: $blockPath,
            blockPublicPath: $blockPublicPath,
            blockIconPath: $blockIcon ? DIR_BASE . $blockIcon : null,
            blockIconPublicPath: $blockIcon,
            customBlockIcon: $customBlockIcon,
            databaseTableName: 'bt' . $blockHandlePascalCase,
            entriesDatabaseTableName: 'bt' . $blockHandlePascalCase . 'Entries',
        );
    }

    private function convertHandleToPascalCase(string $handle): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $handle)));
    }

    private function convertHandleToKebabCase(string $handle): string
    {
        return str_replace('_', '-', $handle);
    }
}
