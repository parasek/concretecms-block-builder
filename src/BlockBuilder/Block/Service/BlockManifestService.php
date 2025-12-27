<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Dto\CreateBlockManifestDto;
use BlockBuilder\Service\NamingConventionService;

readonly class BlockManifestService
{
    public function __construct(
        private NamingConventionService $namingConvention,
    ) {
    }

    public function getManifest(CreateBlockDto $dto, bool $rebuildBlock): CreateBlockManifestDto
    {
        $blockHandlePascalCase = $this->namingConvention->convertToPascalCase($dto->blockHandle);

        return new CreateBlockManifestDto(
            shouldBlockBeInstalled: $dto->installBlock,
            shouldBlockBeRebuilt: $rebuildBlock,
            blockHandlePascalCase: $blockHandlePascalCase,
            blockHandleKebabCase: $this->namingConvention->convertToKebabCase($dto->blockHandle),
            blockPath: DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $dto->blockHandle,
            databaseTableName: 'bt' . $blockHandlePascalCase,
            entriesDatabaseTableName: 'bt' . $blockHandlePascalCase . 'Entries',
        );
    }
}
