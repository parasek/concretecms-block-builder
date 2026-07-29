<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\AutoCss;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;

readonly class AutoCssFileGenerator implements FileGeneratorInterface
{
    public function __construct(private StubRenderer $stubRenderer)
    {
    }

    /**
     * @return list<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): array
    {
        return [
            new GeneratedTextFile(
                relativePath: 'auto.css',
                contents: rtrim($this->stubRenderer->render('auto.css.stub')) . PHP_EOL,
                producer: self::class,
            ),
        ];
    }
}
