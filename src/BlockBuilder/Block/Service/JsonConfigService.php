<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Factory\CreateBlockDtoFactory;
use BlockBuilder\Environment\EnvironmentService;
use Concrete\Core\Application\Application;

readonly class JsonConfigService
{
    public function __construct(
        private Application $app,
        private EnvironmentService $environmentService,
    ) {
    }

    public function getConfigFromApplicationFolder(string $blockHandle): CreateBlockDto
    {
        $path = DIR_FILES_BLOCK_TYPES .
            DIRECTORY_SEPARATOR .
            $blockHandle .
            DIRECTORY_SEPARATOR .
            EnvironmentService::CONFIG_BB_JS;

        $data = $this->parseJsonFile($path);

        return $this->getFactory()->fromArray($data);
    }

    public function getPredefinedConfig(string $handle): CreateBlockDto
    {
        $path = $this->getPredefinedConfigsPath() .
            DIRECTORY_SEPARATOR .
            $handle . '.json';

        $data = $this->parseJsonFile($path);

        return $this->getFactory()->fromArray($data);
    }

    public function getConfigsFromApplicationFolder(): array
    {
        $configs = [];

        $blockPaths = glob(DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

        if (!is_array($blockPaths)) {
            return $configs;
        }

        foreach ($blockPaths as $blockPath) {
            $path = $blockPath . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JS;
            $config = $this->parseJsonFile($path);
            if (!empty($config)) {
                $configs[] = $this->getFactory()->fromArray($config);
            }
        }

        $this->sortByDateAndHandle($configs);

        return $configs;
    }

    public function getPredefinedConfigs(): array
    {
        $predefinedConfigs = [];

        $paths = glob($this->getPredefinedConfigsPath() . DIRECTORY_SEPARATOR . '*.json');

        if (!is_array($paths)) {
            return $predefinedConfigs;
        }

        foreach ($paths as $path) {
            $data = $this->parseJsonFile($path);
            if (!empty($data)) {
                $predefinedConfigs[] = $this->getFactory()->fromArray($data);
            }
        }

        return $predefinedConfigs;
    }

    private function parseJsonFile(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    private function sortByDateAndHandle(array &$blockTypes): void
    {
        // Sort by creation date descending and then by handle ascending
        usort($blockTypes, function (CreateBlockDto $a, CreateBlockDto $b) {
            $dateA = $a->createdAt ?? '0000-00-00'; // Sort null dates as earliest (will be put at the end)
            $dateB = $b->createdAt ?? '0000-00-00';

            if ($dateA === $dateB) {
                return strcmp($a->blockHandle, $b->blockHandle); // Sort by handle ascending if dates are equal
            }

            return $dateB <=> $dateA; // Sort by createdAt descending
        });
    }

    private function getPredefinedConfigsPath(): string
    {
        return DIR_PACKAGES .
            DIRECTORY_SEPARATOR .
            $this->environmentService->getEnvironment()->packageHandle .
            DIRECTORY_SEPARATOR .
            EnvironmentService::PREDEFINED_CONFIGS_FOLDER;
    }

    private function getFactory(): CreateBlockDtoFactory
    {
        return $this->app->make(CreateBlockDtoFactory::class);
    }
}
