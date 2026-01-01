<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Dto\CreateBlockManifestDto;
use BlockBuilder\Service\NamingConventionService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class BlockManifestService
{
    public function __construct(
        private NamingConventionService $namingConvention,
    ) {
    }

    public function getManifest(
        CreateBlockDto $dto,
        bool $rebuildBlock,
        ?string $blockIcon,
        ?UploadedFile $customBlockIcon,
    ): CreateBlockManifestDto
    {
        $blockHandlePascalCase = $this->namingConvention->convertToPascalCase($dto->blockHandle);

        $blockPublicPath = DIRECTORY_SEPARATOR . DIRNAME_APPLICATION . DIRECTORY_SEPARATOR. DIRNAME_BLOCKS . DIRECTORY_SEPARATOR . $dto->blockHandle;
        $blockPath = DIR_BASE . $blockPublicPath;

        $blockIconPublicPath = $blockIcon;
        $blockIconPath = DIR_BASE . $blockIcon;

        return new CreateBlockManifestDto(
            shouldBlockBeInstalled: $dto->installBlock,
            shouldBlockBeRebuilt: $rebuildBlock,
            blockHandlePascalCase: $blockHandlePascalCase,
            blockHandleKebabCase: $this->namingConvention->convertToKebabCase($dto->blockHandle),
            blockPath: $blockPath,
            blockPublicPath: $blockPublicPath,
            blockIconPath: $blockIconPath,
            blockIconPublicPath: $blockIconPublicPath,
            customBlockIcon: $customBlockIcon,
            databaseTableName: 'bt' . $blockHandlePascalCase,
            entriesDatabaseTableName: 'bt' . $blockHandlePascalCase . 'Entries',
        );
    }
}
