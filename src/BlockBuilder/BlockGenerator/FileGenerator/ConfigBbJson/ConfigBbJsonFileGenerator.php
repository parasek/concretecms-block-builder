<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ConfigBbJson;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Dto\CreateBlockManifestDto;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;

readonly class ConfigBbJsonFileGenerator implements FileGeneratorInterface
{
    public function getOutput(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): string
    {
        return json_encode($dto, JSON_PRETTY_PRINT);
    }
}
