<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\AutoJs;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;

readonly class AutoJsFileGenerator implements FileGeneratorInterface
{
    public function __construct(private StubRenderer $stubRenderer)
    {
    }

    /**
     * @return list<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): array
    {
        if (!$context->config->hasFields()) {
            return [];
        }

        return [
            new GeneratedTextFile(
                relativePath: 'auto.js',
                contents: $this->stubRenderer->render('auto.js.stub', [
                    '{{BLOCK_EVENT_NAME}}' => (string) $context->manifest->blockHandleKebabCase,
                ]),
                producer: self::class,
            ),
        ];
    }
}
