<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\Service;

use BlockBuilder\BlockGenerator\Exception\StubRenderingException;
use BlockBuilder\Environment\EnvironmentService;

readonly class StubRenderer
{
    public function __construct(
        private EnvironmentService $environmentService,
    ) {
    }

    /**
     * @param array<string, string> $replacements
     */
    public function render(string $relativeStubPath, array $replacements = []): string
    {
        $stubPath = $this->resolveStubPath($relativeStubPath);
        $template = @file_get_contents($stubPath);
        if ($template === false) {
            throw new StubRenderingException(
                sprintf('Unable to read generator stub "%s".', $relativeStubPath),
            );
        }

        foreach ($replacements as $placeholder => $replacement) {
            if (!is_string($placeholder) || !is_string($replacement)) {
                throw new StubRenderingException(
                    sprintf('Every replacement for generator stub "%s" must have a string key and value.', $relativeStubPath),
                );
            }

            if (preg_match('/^\{\{[A-Z][A-Z0-9_]*\}\}$/', $placeholder) !== 1) {
                throw new StubRenderingException(
                    sprintf('Replacement key "%s" is not a valid placeholder in generator stub "%s".', $placeholder, $relativeStubPath),
                );
            }
        }

        preg_match_all('/\{\{[A-Z][A-Z0-9_]*\}\}/', $template, $placeholderMatches);
        $requiredPlaceholders = array_values(array_unique($placeholderMatches[0]));
        $missingPlaceholders = array_values(array_diff($requiredPlaceholders, array_keys($replacements)));
        if ($missingPlaceholders !== []) {
            throw new StubRenderingException(
                sprintf(
                    'Generator stub "%s" contains unresolved placeholders: %s.',
                    $relativeStubPath,
                    implode(', ', $missingPlaceholders),
                ),
            );
        }

        $unknownPlaceholders = array_values(array_diff(array_keys($replacements), $requiredPlaceholders));
        if ($unknownPlaceholders !== []) {
            throw new StubRenderingException(
                sprintf(
                    'Generator stub "%s" does not contain replacement placeholders: %s.',
                    $relativeStubPath,
                    implode(', ', $unknownPlaceholders),
                ),
            );
        }

        return strtr($template, $replacements);
    }

    private function resolveStubPath(string $relativeStubPath): string
    {
        $this->validateRelativeStubPath($relativeStubPath);

        $skeletonsPath = realpath($this->environmentService->getGeneratorSkeletonsPath());
        if ($skeletonsPath === false || !is_dir($skeletonsPath) || !is_readable($skeletonsPath)) {
            throw new StubRenderingException('The generator skeleton directory is missing or unreadable.');
        }

        $candidatePath = $skeletonsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeStubPath);
        $resolvedStubPath = realpath($candidatePath);
        if ($resolvedStubPath === false || !is_file($resolvedStubPath)) {
            throw new StubRenderingException(
                sprintf('Generator stub "%s" does not exist.', $relativeStubPath),
            );
        }

        if (!str_starts_with($resolvedStubPath, $skeletonsPath . DIRECTORY_SEPARATOR)) {
            throw new StubRenderingException(
                sprintf('Generator stub "%s" resolves outside the generator skeleton directory.', $relativeStubPath),
            );
        }

        if (!is_readable($resolvedStubPath)) {
            throw new StubRenderingException(
                sprintf('Generator stub "%s" is unreadable.', $relativeStubPath),
            );
        }

        return $resolvedStubPath;
    }

    private function validateRelativeStubPath(string $relativeStubPath): void
    {
        if ($relativeStubPath === '') {
            throw new StubRenderingException('The generator stub path cannot be empty.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $relativeStubPath) === 1) {
            throw new StubRenderingException('The generator stub path cannot contain control characters.');
        }

        if (str_contains($relativeStubPath, '\\')) {
            throw new StubRenderingException('Generator stub paths must use forward slashes as directory separators.');
        }

        if (str_starts_with($relativeStubPath, '/') || preg_match('/^[A-Za-z]:/', $relativeStubPath) === 1) {
            throw new StubRenderingException('The generator stub path must be relative to the generator skeleton directory.');
        }

        foreach (explode('/', $relativeStubPath) as $pathSegment) {
            if ($pathSegment === '' || $pathSegment === '.' || $pathSegment === '..') {
                throw new StubRenderingException(
                    'Generator stub paths cannot contain empty, current-directory, or parent-directory segments.',
                );
            }
        }
    }
}
