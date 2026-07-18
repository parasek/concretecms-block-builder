<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ConfigBbJson;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\Environment\EnvironmentService;

readonly class ConfigBbJsonFileGenerator implements FileGeneratorInterface
{
    /**
     * @return iterable<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): iterable
    {
        yield new GeneratedTextFile(
            relativePath: EnvironmentService::CONFIG_BB_JSON,
            contents: json_encode($context->config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            producer: self::class,
        );
    }
}
