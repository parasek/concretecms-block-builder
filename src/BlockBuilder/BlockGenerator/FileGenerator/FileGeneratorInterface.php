<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Dto\CreateBlockManifestDto;

interface FileGeneratorInterface
{
    public function getOutput(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): string;
}
