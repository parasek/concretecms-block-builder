<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\Scaffold;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;

readonly class ScaffoldFileGenerator implements FileGeneratorInterface
{
    private const array STUBS_BY_DESTINATION = [
        FILENAME_BLOCK_ADD => 'add.php.stub',
        FILENAME_BLOCK_EDIT => 'edit.php.stub',
        FILENAME_BLOCK_COMPOSER => 'composer.php.stub',
        FILENAME_BLOCK_VIEW_SCRAPBOOK => 'scrapbook.php.stub',
    ];

    public function __construct(private StubRenderer $stubRenderer)
    {
    }

    /**
     * @return list<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): array
    {
        $generatedFiles = [];
        foreach (self::STUBS_BY_DESTINATION as $relativePath => $stubPath) {
            $generatedFiles[] = new GeneratedTextFile(
                relativePath: $relativePath,
                contents: $this->stubRenderer->render($stubPath),
                producer: self::class,
            );
        }

        return $generatedFiles;
    }
}
