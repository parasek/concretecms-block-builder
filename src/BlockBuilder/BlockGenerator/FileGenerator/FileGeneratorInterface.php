<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;

interface FileGeneratorInterface
{
    /**
     * @return iterable<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): iterable;
}
