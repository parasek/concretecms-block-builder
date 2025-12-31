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

    public function __construct(
        private SystemInfo $systemInfo,
        private Config $config,
        private PackageService $packageService,
    ) {
    }

    public function getEnvironment(): EnvironmentDto
    {
        $pkg = $this->packageService->getByHandle(self::PACKAGE_HANDLE);

        return new EnvironmentDto(
            blockBuilderVersion: $pkg->getPackageVersion(),
            concreteVersion: $this->config->get('concrete.version'),
            phpVersion: $this->systemInfo->getPhpVersion(),
            packageHandle: $pkg->getPackageHandle(),
        );
    }

    public function getGeneratorFilesPath(): string
    {
        return DIR_PACKAGES . DIRECTORY_SEPARATOR .
            self::PACKAGE_HANDLE . DIRECTORY_SEPARATOR .
            'generator_files';
    }

    public function getGeneratorSkeletonsPath(): string
    {
        return $this->getGeneratorFilesPath() . DIRECTORY_SEPARATOR . 'skeletons';
    }
}
