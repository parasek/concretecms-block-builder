<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator;

readonly class GeneratedTextFile
{
    public function __construct(
        public string $relativePath,
        public string $contents,
        public string $producer,
    ) {
    }
}
