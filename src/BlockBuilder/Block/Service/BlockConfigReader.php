<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use BlockBuilder\Block\Dto\BlockConfigCollectionLoadResult;
use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Exception\ConfigFileMissingException;
use BlockBuilder\Block\Exception\ConfigFileTooLargeException;
use BlockBuilder\Block\Exception\ConfigFileUnreadableException;
use BlockBuilder\Block\Exception\ConfigLoadingException;
use BlockBuilder\Block\Exception\ConfigVersionTooNewException;
use BlockBuilder\Block\Exception\InvalidConfigFieldDataException;
use BlockBuilder\Block\Exception\InvalidConfigJsonException;
use BlockBuilder\Block\Exception\UnsupportedConfigSchemaException;
use BlockBuilder\Block\Factory\BlockConfigDtoFactory;
use BlockBuilder\Block\Validation\BlockHandleFormat;
use BlockBuilder\Block\Validation\BlockConfigLimits;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;

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

    public function getConfigsFromApplicationFolder(): BlockConfigCollectionLoadResult
    {
        $configs = [];
        $errors = [];

        $blockPaths = glob(
            $this->blockDirectoryLocator->getApplicationBlocksPath() . DIRECTORY_SEPARATOR . '*',
            GLOB_ONLYDIR,
        );

        if (!is_array($blockPaths)) {
            return new BlockConfigCollectionLoadResult(configs: [], errors: []);
        }

        foreach ($blockPaths as $blockPath) {
            $sourceHandle = basename($blockPath);
            $candidateConfigPath = $blockPath . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JSON;
            if (!is_file($candidateConfigPath)) {
                continue;
            }

            try {
                $blockDirectory = $this->blockDirectoryLocator->getSafeApplicationBlockDirectory($sourceHandle);
                if ($blockDirectory === null) {
                    throw $this->createUnsafeApplicationDirectoryException($sourceHandle);
                }

                $configs[] = $this->loadConfig(
                    path: $blockDirectory . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JSON,
                    sourceHandle: $sourceHandle,
                );
            } catch (ConfigLoadingException $exception) {
                $errors[] = $exception;
            }
        }

        return new BlockConfigCollectionLoadResult(
            configs: $this->sortByDateAndHandle($configs),
            errors: $errors,
        );
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
            throw new InvalidConfigFieldDataException(message: t('The configuration file "%s" has an invalid source identifier.', $this->getConfigIdentifier($path)));
        }

        $data = $this->parseJsonFile($path);
        $this->validateSchema($data, $path);
        $this->validateFieldCollections($data, $path);

        try {
            $config = $this->blockConfigDtoFactory->fromArray($data);
        } catch (InvalidConfigFieldDataException $exception) {
            throw new InvalidConfigFieldDataException(message: t('The configuration file "%s" contains invalid field data: %s', $this->getConfigIdentifier($path), $exception->getMessage()), previous: $exception);
        } catch (\Throwable $throwable) {
            throw new InvalidConfigFieldDataException(message: t('The configuration file "%s" contains invalid field data.', $this->getConfigIdentifier($path)), previous: $throwable);
        }

        if ($config->blockHandle !== $sourceHandle) {
            throw new InvalidConfigFieldDataException(message: t('The configuration file "%s" declares block handle "%s", but its source identifier is "%s".', $this->getConfigIdentifier($path), $config->blockHandle, $sourceHandle));
        }

        return $config;
    }

    private function parseJsonFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new ConfigFileMissingException(message: t('The configuration file "%s" could not be found.', $this->getConfigIdentifier($path)));
        }

        if (!is_file($path) || is_link($path) || !is_readable($path)) {
            throw new ConfigFileUnreadableException(message: t('The configuration file "%s" exists but could not be read.', $this->getConfigIdentifier($path)));
        }

        $fileSize = filesize($path);
        if ($fileSize === false) {
            throw new ConfigFileUnreadableException(message: t('The configuration file "%s" exists but could not be read.', $this->getConfigIdentifier($path)));
        }
        if ($fileSize > self::MAX_CONFIG_FILE_SIZE) {
            throw $this->createConfigFileTooLargeException($path);
        }

        $content = file_get_contents($path, false, null, 0, self::MAX_CONFIG_FILE_SIZE + 1);
        if ($content === false) {
            throw new ConfigFileUnreadableException(message: t('The configuration file "%s" exists but could not be read.', $this->getConfigIdentifier($path)));
        }
        if (strlen($content) > self::MAX_CONFIG_FILE_SIZE) {
            throw $this->createConfigFileTooLargeException($path);
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidConfigJsonException(message: t('The configuration file "%s" contains invalid JSON: %s', $this->getConfigIdentifier($path), $exception->getMessage()), previous: $exception);
        }

        if (!is_array($data)) {
            throw new UnsupportedConfigSchemaException(message: t('The configuration file "%s" must contain a JSON object.', $this->getConfigIdentifier($path)));
        }

        return $data;
    }

    private function validateSchema(array $data, string $path): void
    {
        if (array_is_list($data)) {
            throw new UnsupportedConfigSchemaException(message: t('The configuration file "%s" must contain a JSON object rather than a JSON list.', $this->getConfigIdentifier($path)));
        }

        $allowedProperties = [
            ...array_keys(get_class_vars(BlockConfigDto::class)),
            // Supported names used by 2.8.1 and earlier configuration files.
            'version',
            'urlEndingHelpText',
            'fieldsDivider',
            'entryFieldsDivider',
            'scroll',
        ];
        $unsupportedProperties = array_diff(array_keys($data), $allowedProperties);
        if ($unsupportedProperties !== []) {
            throw new UnsupportedConfigSchemaException(message: t('The configuration file "%s" contains an unsupported property "%s".', $this->getConfigIdentifier($path), (string) reset($unsupportedProperties)));
        }

        foreach ($data as $propertyName => $value) {
            if (
                is_string($propertyName)
                && is_string($value)
                && mb_strlen($value) > BlockConfigLimits::getTopLevelStringMaximum($propertyName)
            ) {
                throw new UnsupportedConfigSchemaException(message: t('The property "%s" in the configuration file "%s" exceeds the maximum allowed length.', $propertyName, $this->getConfigIdentifier($path)));
            }
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
            throw new UnsupportedConfigSchemaException(message: t('The configuration file "%s" contains invalid Block Builder version information.', $this->getConfigIdentifier($path)));
        }

        $currentVersion = $this->environmentService->getEnvironment()->blockBuilderVersion;

        if (version_compare($version, $currentVersion, '>')) {
            throw new ConfigVersionTooNewException(message: t('The configuration file "%s" uses Block Builder version "%s", which is newer than the current environment version "%s".', $this->getConfigIdentifier($path), $version, $currentVersion));
        }
    }

    private function validateFieldCollections(array $data, string $path): void
    {
        foreach (FieldTypeContextEnum::cases() as $fieldTypeContext) {
            $collectionName = $fieldTypeContext->value;
            if (!isset($data[$collectionName]) || !is_array($data[$collectionName])) {
                throw new InvalidConfigFieldDataException(message: t('The "%s" field collection in the configuration file "%s" must be an array.', $collectionName, $this->getConfigIdentifier($path)));
            }

            if (count($data[$collectionName]) > BlockConfigLimits::MAX_FIELDS_PER_COLLECTION) {
                throw new InvalidConfigFieldDataException(message: t('The "%s" field collection in the configuration file "%s" may contain at most %s fields.', $collectionName, $this->getConfigIdentifier($path), BlockConfigLimits::MAX_FIELDS_PER_COLLECTION));
            }

            foreach ($data[$collectionName] as $fieldIndex => $fieldData) {
                if (!is_array($fieldData)) {
                    throw new InvalidConfigFieldDataException(message: t('Each field in the "%s" collection of the configuration file "%s" must be an array.', $collectionName, $this->getConfigIdentifier($path)));
                }

                $this->validateFieldDataLimits($fieldData, $fieldIndex, $collectionName, $path);
            }
        }
    }

    private function validateFieldDataLimits(
        array $fieldData,
        int|string $fieldIndex,
        string $collectionName,
        string $path,
    ): void {
        foreach ($fieldData as $propertyName => $value) {
            if (!is_string($propertyName)) {
                continue;
            }

            if (
                is_string($value)
                && mb_strlen($value) > BlockConfigLimits::getFieldStringMaximum($propertyName)
            ) {
                throw new InvalidConfigFieldDataException(message: t('Property "%s" of field %s in the "%s" collection of the configuration file "%s" exceeds the maximum allowed length.', $propertyName, $fieldIndex, $collectionName, $this->getConfigIdentifier($path)));
            }

            if ($propertyName === 'options' && is_string($value)) {
                $options = preg_split('/\R/u', $value);
                if (is_array($options) && count($options) > BlockConfigLimits::MAX_OPTIONS_PER_FIELD) {
                    throw new InvalidConfigFieldDataException(message: t('Field %s in the "%s" collection of the configuration file "%s" may contain at most %s options.', $fieldIndex, $collectionName, $this->getConfigIdentifier($path), BlockConfigLimits::MAX_OPTIONS_PER_FIELD));
                }
            }
        }

        $icons = $fieldData['icons'] ?? null;
        if (!is_array($icons)) {
            return;
        }
        if (count($icons) > BlockConfigLimits::MAX_SVG_ICONS_PER_FIELD) {
            throw new InvalidConfigFieldDataException(message: t('Field %s in the "%s" collection of the configuration file "%s" may contain at most %s SVG icons.', $fieldIndex, $collectionName, $this->getConfigIdentifier($path), BlockConfigLimits::MAX_SVG_ICONS_PER_FIELD));
        }

        $maximumLengths = [
            'name' => BlockConfigLimits::MAX_SVG_ICON_NAME_LENGTH,
            'handle' => BlockConfigLimits::MAX_SVG_ICON_HANDLE_LENGTH,
            'svg' => BlockConfigLimits::MAX_SVG_CONTENT_LENGTH,
        ];
        foreach ($icons as $icon) {
            if (!is_array($icon)) {
                continue;
            }
            foreach ($maximumLengths as $propertyName => $maximumLength) {
                $value = $icon[$propertyName] ?? null;
                if (is_string($value) && mb_strlen($value) > $maximumLength) {
                    throw new InvalidConfigFieldDataException(message: t('An SVG icon property "%s" in field %s of the configuration file "%s" exceeds the maximum allowed length.', $propertyName, $fieldIndex, $this->getConfigIdentifier($path)));
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

    private function sortByDateAndHandle(array $configs): array
    {
        // Sort by creation date descending and then by handle ascending.
        usort($configs, static function (BlockConfigDto $firstConfig, BlockConfigDto $secondConfig): int {
            // Treat missing dates as the earliest so they appear last.
            $firstDate = $firstConfig->createdAt ?? '0000-00-00';
            $secondDate = $secondConfig->createdAt ?? '0000-00-00';

            if ($firstDate === $secondDate) {
                return strcmp($firstConfig->blockHandle, $secondConfig->blockHandle);
            }

            return $secondDate <=> $firstDate;
        });

        return $configs;
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
