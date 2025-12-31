<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Dto\CreateBlockManifestDto;
use BlockBuilder\Environment\EnvironmentService;

readonly abstract class AbstractFileGenerator
{
    public function __construct(
        protected EnvironmentService $environmentService,
    ) {
    }

    protected function loadStub(string $fileName): string
    {
        $stubPath = $this->environmentService->getGeneratorSkeletonsPath() . DIRECTORY_SEPARATOR . $fileName;

        return file_get_contents($stubPath);
    }

    protected function getCommonReplacements(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): array
    {
        return [
            '{{BLOCK_NAME}}' => $dto->blockName,
            '{{BLOCK_DESCRIPTION}}' => $dto->blockDescription,
            '{{BLOCK_HANDLE}}' => $dto->blockHandle,
            '{{BLOCK_HANDLE_PASCAL_CASE}}' => $manifestDto->blockHandlePascalCase,
            '{{BLOCK_HANDLE_KEBAB_CASE}}' => $manifestDto->blockHandleKebabCase,
        ];
    }

    protected function generateOutput(string $stubFileName, array $specificReplacements, CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): string
    {
        $template = $this->loadStub($stubFileName);
        $replacements = array_merge($this->getCommonReplacements($dto, $manifestDto), $specificReplacements);

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
