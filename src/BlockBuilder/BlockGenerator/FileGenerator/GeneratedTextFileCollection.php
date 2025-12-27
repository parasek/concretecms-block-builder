<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator;

use BlockBuilder\BlockGenerator\Exception\GeneratedFileDefinitionException;

/**
 * @implements \IteratorAggregate<int, GeneratedTextFile>
 */
final class GeneratedTextFileCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, GeneratedTextFile>
     */
    private array $generatedFilesByDestination = [];

    /**
     * @param iterable<GeneratedTextFile> $generatedFiles
     */
    public function __construct(iterable $generatedFiles = [])
    {
        $this->addAll($generatedFiles);
    }

    public function add(GeneratedTextFile $generatedFile): void
    {
        $this->validate($generatedFile);

        $destinationKey = strtolower($generatedFile->relativePath);
        if (isset($this->generatedFilesByDestination[$destinationKey])) {
            $existingGeneratedFile = $this->generatedFilesByDestination[$destinationKey];

            throw new GeneratedFileDefinitionException(sprintf('Generators "%s" and "%s" both declared the destination "%s".', $existingGeneratedFile->producer, $generatedFile->producer, $generatedFile->relativePath));
        }

        $this->generatedFilesByDestination[$destinationKey] = $generatedFile;
    }

    /**
     * @param iterable<GeneratedTextFile> $generatedFiles
     */
    public function addAll(iterable $generatedFiles): void
    {
        foreach ($generatedFiles as $generatedFile) {
            $this->add($generatedFile);
        }
    }

    public function count(): int
    {
        return count($this->generatedFilesByDestination);
    }

    /**
     * @return \Traversable<int, GeneratedTextFile>
     */
    public function getIterator(): \Traversable
    {
        $generatedFiles = array_values($this->generatedFilesByDestination);
        usort(
            $generatedFiles,
            static fn (GeneratedTextFile $first, GeneratedTextFile $second): int => strcmp($first->relativePath, $second->relativePath),
        );

        return new \ArrayIterator($generatedFiles);
    }

    private function validate(GeneratedTextFile $generatedFile): void
    {
        if (trim($generatedFile->producer) === '') {
            throw new GeneratedFileDefinitionException('A generated text file must identify its producer.');
        }

        $relativePath = $generatedFile->relativePath;
        if ($relativePath === '') {
            throw $this->invalidPath($generatedFile, 'The destination cannot be empty.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $relativePath) === 1) {
            throw $this->invalidPath($generatedFile, 'Control characters are not allowed.');
        }

        if (str_contains($relativePath, '\\')) {
            throw $this->invalidPath($generatedFile, 'Use forward slashes as directory separators.');
        }

        if (str_starts_with($relativePath, '/') || preg_match('/^[A-Za-z]:/', $relativePath) === 1) {
            throw $this->invalidPath($generatedFile, 'The destination must be relative to the block directory.');
        }

        foreach (explode('/', $relativePath) as $pathSegment) {
            if ($pathSegment === '' || $pathSegment === '.' || $pathSegment === '..') {
                throw $this->invalidPath($generatedFile, 'Empty, current-directory, and parent-directory path segments are not allowed.');
            }
        }
    }

    private function invalidPath(GeneratedTextFile $generatedFile, string $reason): GeneratedFileDefinitionException
    {
        return new GeneratedFileDefinitionException(
            sprintf(
                'Generator "%s" declared invalid destination "%s": %s',
                $generatedFile->producer,
                $generatedFile->relativePath,
                $reason,
            ),
        );
    }
}
