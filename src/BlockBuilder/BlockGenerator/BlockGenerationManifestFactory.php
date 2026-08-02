<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Validation\BlockHandleFormat;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class BlockGenerationManifestFactory
{
    public function create(
        BlockConfigDto $config,
        bool $shouldRebuildBlock,
        ?string $blockIconPublicPath,
        ?UploadedFile $customBlockIcon,
    ): BlockGenerationManifest
    {
        if (!BlockHandleFormat::isValid($config->blockHandle)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to create a generation manifest for invalid block handle "%s".',
                $config->blockHandle,
            ));
        }

        $blockHandlePascalCase = $this->convertHandleToPascalCase($config->blockHandle);
        $blockPublicPath = DIRECTORY_SEPARATOR . DIRNAME_APPLICATION . DIRECTORY_SEPARATOR . DIRNAME_BLOCKS . DIRECTORY_SEPARATOR . $config->blockHandle;
        $blockPath = DIR_BASE . $blockPublicPath;

        return new BlockGenerationManifest(
            shouldInstallBlock: $config->installBlock,
            shouldRebuildBlock: $shouldRebuildBlock,
            blockHandlePascalCase: $blockHandlePascalCase,
            blockHandleKebabCase: $this->convertHandleToKebabCase($config->blockHandle),
            blockPath: $blockPath,
            blockPublicPath: $blockPublicPath,
            blockIconPath: $blockIconPublicPath ? DIR_BASE . $blockIconPublicPath : null,
            blockIconPublicPath: $blockIconPublicPath,
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
