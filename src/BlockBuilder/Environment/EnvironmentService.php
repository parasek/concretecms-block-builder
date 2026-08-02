<?php

declare(strict_types=1);

namespace BlockBuilder\Environment;

use BlockBuilder\Environment\Dto\EnvironmentDto;
use Concrete\Core\Config\Repository\Repository as Config;
use Concrete\Core\Package\PackageService;
use Concrete\Core\System\Info as SystemInfo;

readonly class EnvironmentService
{
    const string PACKAGE_HANDLE = 'block_builder';

    const string PREDEFINED_CONFIGS_FOLDER = 'predefined_configs';
    const string GENERATOR_FILES_FOLDER = 'generator_files';
    const string SKELETONS_FOLDER = 'skeletons';

    const string CONFIG_BB_JSON = 'config-bb.json';
    const string SOURCE_BLOCK_ICON = 'icon.png';

    public function __construct(
        private SystemInfo $systemInfo,
        private Config $config,
        private PackageService $packageService,
    ) {
    }

    public function getEnvironment(): EnvironmentDto
    {
        $package = $this->packageService->getByHandle(self::PACKAGE_HANDLE);

        return new EnvironmentDto(
            blockBuilderVersion: $package->getPackageVersion(),
            concreteVersion: $this->config->get('concrete.version'),
            phpVersion: $this->systemInfo->getPhpVersion(),
            packageHandle: $package->getPackageHandle(),
        );
    }

    public function getGeneratorFilesPath(): string
    {
        return DIR_PACKAGES . DIRECTORY_SEPARATOR .
            self::PACKAGE_HANDLE . DIRECTORY_SEPARATOR .
            self::GENERATOR_FILES_FOLDER;
    }

    public function getGeneratorSkeletonsPath(): string
    {
        return $this->getGeneratorFilesPath() . DIRECTORY_SEPARATOR . self::SKELETONS_FOLDER;
    }

    public function getPublicPathToBlockIcon(string $blockHandle): string
    {
        return DIRECTORY_SEPARATOR .
            DIRNAME_APPLICATION . DIRECTORY_SEPARATOR .
            DIRNAME_BLOCKS . DIRECTORY_SEPARATOR .
            $blockHandle . DIRECTORY_SEPARATOR .
            FILENAME_BLOCK_ICON;
    }

    public function getPublicPathToDefaultBlockIcon(): string
    {
        return DIRECTORY_SEPARATOR .
            DIRNAME_PACKAGES . DIRECTORY_SEPARATOR .
            self::PACKAGE_HANDLE . DIRECTORY_SEPARATOR .
            self::GENERATOR_FILES_FOLDER . DIRECTORY_SEPARATOR .
            self::SOURCE_BLOCK_ICON;
    }
}
