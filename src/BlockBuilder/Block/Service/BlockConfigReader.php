<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Exception\ConfigFileMissingException;
use BlockBuilder\Block\Exception\ConfigFileTooLargeException;
use BlockBuilder\Block\Exception\ConfigFileUnreadableException;
use BlockBuilder\Block\Exception\ConfigVersionTooNewException;
use BlockBuilder\Block\Exception\InvalidConfigFieldDataException;
use BlockBuilder\Block\Exception\InvalidConfigJsonException;
use BlockBuilder\Block\Exception\UnsupportedConfigSchemaException;
use BlockBuilder\Block\Factory\BlockConfigDtoFactory;
use BlockBuilder\Block\Validation\BlockHandleFormat;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use JsonException;
use Throwable;

readonly class BlockConfigReader
{
    private const int MAX_CONFIG_FILE_SIZE = 5_242_880;

    public function __construct(
        private BlockConfigDtoFactory $blockConfigDtoFactory,
        private EnvironmentService $environmentService,
        private BlockDirectoryLocator $blockDirectoryLocator,
    ) {
    }

    public function getConfigFromApplicationFolder(string $blockHandle): BlockConfigDto
    {
        $blockDirectory = $this->blockDirectoryLocator->getSafeApplicationBlockDirectory($blockHandle);
        if ($blockDirectory === null) {
            throw $this->createUnsafeApplicationDirectoryException($blockHandle);
        }

        return $this->loadConfig(
            path: $blockDirectory . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JSON,
            sourceHandle: $blockHandle,
        );
    }

    public function getPredefinedConfig(string $handle): BlockConfigDto
    {
        $path = $this->getPredefinedConfigsPath() .
            DIRECTORY_SEPARATOR .
            $handle . '.json';

        return $this->loadConfig($path, $handle);
    }

    public function getConfigsFromApplicationFolder(): array
    {
        $configs = [];

        $blockPaths = glob(DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

        if (!is_array($blockPaths)) {
            return $configs;
        }

        foreach ($blockPaths as $blockPath) {
            $sourceHandle = basename($blockPath);
            $candidateConfigPath = $blockPath . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JSON;
            if (!is_file($candidateConfigPath)) {
                continue;
            }

            $blockDirectory = $this->blockDirectoryLocator->getSafeApplicationBlockDirectory($sourceHandle);
            if ($blockDirectory === null) {
                throw $this->createUnsafeApplicationDirectoryException($sourceHandle);
            }

            $configs[] = $this->loadConfig(
                path: $blockDirectory . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JSON,
                sourceHandle: $sourceHandle,
            );
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
            $predefinedConfigs[] = $this->loadConfig(
                path: $path,
                sourceHandle: pathinfo($path, PATHINFO_FILENAME),
            );
        }

        return $predefinedConfigs;
    }

    private function loadConfig(string $path, string $sourceHandle): BlockConfigDto
    {
        if (!BlockHandleFormat::isValid($sourceHandle)) {
            throw new InvalidConfigFieldDataException(
                message: t(
                    'The configuration file "%s" has an invalid source identifier.',
                    $this->getConfigIdentifier($path),
                ),
            );
        }

        $data = $this->parseJsonFile($path);
        $this->validateSchema($data, $path);
        $this->validateFieldCollections($data, $path);

        try {
            $config = $this->blockConfigDtoFactory->fromArray($data);
        } catch (InvalidConfigFieldDataException $exception) {
            throw new InvalidConfigFieldDataException(
                message: t(
                    'The configuration file "%s" contains invalid field data: %s',
                    $this->getConfigIdentifier($path),
                    $exception->getMessage(),
                ),
                previous: $exception,
            );
        } catch (Throwable $throwable) {
            throw new InvalidConfigFieldDataException(
                message: t(
                    'The configuration file "%s" contains invalid field data.',
                    $this->getConfigIdentifier($path),
                ),
                previous: $throwable,
            );
        }

        if ($config->blockHandle !== $sourceHandle) {
            throw new InvalidConfigFieldDataException(
                message: t(
                    'The configuration file "%s" declares block handle "%s", but its source identifier is "%s".',
                    $this->getConfigIdentifier($path),
                    $config->blockHandle,
                    $sourceHandle,
                ),
            );
        }

        return $config;
    }

    private function parseJsonFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new ConfigFileMissingException(
                message: t(
                    'The configuration file "%s" could not be found.',
                    $this->getConfigIdentifier($path),
                ),
            );
        }

        if (!is_file($path) || is_link($path) || !is_readable($path)) {
            throw new ConfigFileUnreadableException(
                message: t(
                    'The configuration file "%s" exists but could not be read.',
                    $this->getConfigIdentifier($path),
                ),
            );
        }

        $fileSize = filesize($path);
        if ($fileSize === false) {
            throw new ConfigFileUnreadableException(
                message: t(
                    'The configuration file "%s" exists but could not be read.',
                    $this->getConfigIdentifier($path),
                ),
            );
        }
        if ($fileSize > self::MAX_CONFIG_FILE_SIZE) {
            throw $this->createConfigFileTooLargeException($path);
        }

        $content = file_get_contents($path, false, null, 0, self::MAX_CONFIG_FILE_SIZE + 1);
        if ($content === false) {
            throw new ConfigFileUnreadableException(
                message: t(
                    'The configuration file "%s" exists but could not be read.',
                    $this->getConfigIdentifier($path),
                ),
            );
        }
        if (strlen($content) > self::MAX_CONFIG_FILE_SIZE) {
            throw $this->createConfigFileTooLargeException($path);
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidConfigJsonException(
                message: t(
                    'The configuration file "%s" contains invalid JSON: %s',
                    $this->getConfigIdentifier($path),
                    $exception->getMessage(),
                ),
                previous: $exception,
            );
        }

        if (!is_array($data)) {
            throw new UnsupportedConfigSchemaException(
                message: t(
                    'The configuration file "%s" must contain a JSON object.',
                    $this->getConfigIdentifier($path),
                ),
            );
        }

        return $data;
    }

    private function validateSchema(array $data, string $path): void
    {
        if (array_is_list($data)) {
            throw new UnsupportedConfigSchemaException(
                message: t(
                    'The configuration file "%s" must contain a JSON object rather than a JSON list.',
                    $this->getConfigIdentifier($path),
                ),
            );
        }

        // At the beginning there was no version in JSON file,
        // later it was called a "version" and now it's a "blockBuilderVersion"
        $version = $data['blockBuilderVersion'] ?? $data['version'] ?? null;
        // Skip version checks if there is no version available
        if ($version === null) {
            return;
        }

        // Example versions: 3, 3.0, 3.0.1, 3.0.1-beta, or 3.0.1+build.5.
        if (!is_string($version) || preg_match('/^\d+(?:\.\d+){0,2}(?:[-+][0-9A-Za-z.-]+)?$/', $version) !== 1) {
            throw new UnsupportedConfigSchemaException(
                message: t(
                    'The configuration file "%s" contains invalid Block Builder version information.',
                    $this->getConfigIdentifier($path),
                ),
            );
        }

        $currentVersion = $this->environmentService->getEnvironment()->blockBuilderVersion;

        if (version_compare($version, $currentVersion, '>')) {
            throw new ConfigVersionTooNewException(
                message: t(
                    'The configuration file "%s" uses Block Builder version "%s", which is newer than the current environment version "%s".',
                    $this->getConfigIdentifier($path),
                    $version,
                    $currentVersion,
                ),
            );
        }
    }

    private function validateFieldCollections(array $data, string $path): void
    {
        foreach (FieldTypeContextEnum::cases() as $fieldTypeContext) {
            $collectionName = $fieldTypeContext->value;
            if (!isset($data[$collectionName]) || !is_array($data[$collectionName])) {
                throw new InvalidConfigFieldDataException(
                    message: t(
                        'The "%s" fields in configuration file "%s" must be provided as an array.',
                        $collectionName,
                        $this->getConfigIdentifier($path),
                    ),
                );
            }

            foreach ($data[$collectionName] as $fieldData) {
                if (!is_array($fieldData)) {
                    throw new InvalidConfigFieldDataException(
                        message: t(
                            'A field in the "%s" fields of configuration file "%s" must be provided as an array.',
                            $collectionName,
                            $this->getConfigIdentifier($path),
                        ),
                    );
                }
            }
        }
    }

    private function getConfigIdentifier(string $path): string
    {
        $filename = basename($path);
        $parentDirectory = basename(dirname($path));

        return $parentDirectory !== '' && $parentDirectory !== '.'
            ? $parentDirectory . '/' . $filename
            : $filename;
    }

    private function createUnsafeApplicationDirectoryException(string $blockHandle): ConfigFileUnreadableException
    {
        return new ConfigFileUnreadableException(
            message: t(
                'The application block directory for "%s" does not exist or cannot be read safely.',
                $blockHandle,
            ),
        );
    }

    private function createConfigFileTooLargeException(string $path): ConfigFileTooLargeException
    {
        $maximumSizeInMegabytes = intdiv(self::MAX_CONFIG_FILE_SIZE, 1_048_576);

        return new ConfigFileTooLargeException(
            message: t(
                'The configuration file "%s" exceeds the maximum allowed size of %s MB.',
                $this->getConfigIdentifier($path),
                $maximumSizeInMegabytes,
            ),
        );
    }

    private function sortByDateAndHandle(array &$blockTypes): void
    {
        // Sort by creation date descending and then by handle ascending
        usort($blockTypes, function (BlockConfigDto $a, BlockConfigDto $b) {
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
}
