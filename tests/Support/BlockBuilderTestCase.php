<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Support;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Factory\BlockConfigDtoFactory;
use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorCollection;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFileCollection;
use BlockBuilder\BlockGenerator\Generation\BlockGenerationPlanFactory;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\FieldType\Factory\FieldTypeDtoFactory;
use BlockBuilder\FieldType\FieldTypeRegistry;
use Concrete\Core\Config\Repository\Repository as ConfigRepository;
use Concrete\Core\Package\PackageService;
use Concrete\Core\System\Info as SystemInfo;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

abstract class BlockBuilderTestCase extends TestCase
{
    /**
     * @var array<class-string, object>
     */
    private array $services = [];

    protected function createBlockConfigDtoFactory(): BlockConfigDtoFactory
    {
        return new BlockConfigDtoFactory(
            environmentService: $this->getEnvironmentService(),
            fieldTypeDtoFactory: new FieldTypeDtoFactory($this->getService(FieldTypeRegistry::class)),
        );
    }

    protected function loadJsonFixture(string $fixtureName): array
    {
        $fixturePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . $fixtureName;
        $contents = file_get_contents($fixturePath);
        self::assertNotFalse($contents, sprintf('Fixture "%s" must be readable.', $fixtureName));

        return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }

    protected function createAllFieldTypesConfig(): BlockConfigDto
    {
        $registry = $this->getService(FieldTypeRegistry::class);
        $basicFields = [];
        $repeatableFields = [];
        foreach ($registry->all() as $position => $fieldType) {
            $fieldTypeHandle = $fieldType::getFieldType()->value;
            $handleSuffix = str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldTypeHandle)));
            $fieldData = [
                'fieldType' => $fieldTypeHandle,
                'label' => $fieldType::getFieldType()->name,
                'handle' => 'basic' . $handleSuffix,
                'required' => false,
                'helpText' => '',
                ...$fieldType::getDefaultValues(),
            ];
            $basicFields[(string) ($position + 1)] = $fieldData;
            $fieldData['handle'] = 'repeatable' . $handleSuffix;
            $repeatableFields[(string) ($position + 1)] = $fieldData;
        }

        $config = [
            'blockName' => 'All Field Types Test',
            'blockHandle' => 'all_field_types_test',
            'blockDescription' => 'Generated in memory by the Block Builder tests.',
            'installBlock' => false,
            'blockWidth' => 1000,
            'blockHeight' => 650,
            'cacheBlockRecord' => false,
            'cacheBlockOutput' => false,
            'cacheBlockOutputLifetime' => 0,
            'cacheBlockOutputOnPost' => false,
            'cacheBlockOutputOnEditMode' => true,
            'cacheBlockOutputForRegisteredUsers' => false,
            'supportSavingNullValues' => false,
            'ignorePageThemeGridFrameworkContainer' => false,
            'entriesAsFirstTab' => false,
            'maxNumberOfEntries' => 0,
            'highlightMultiElementFields' => true,
            'excludedFromRemoval' => [],
            'basic' => $basicFields,
            'entries' => $repeatableFields,
        ];

        return $this->createBlockConfigDtoFactory()->fromGenerationArray($config);
    }

    protected function createManifest(BlockConfigDto $config): BlockGenerationManifest
    {
        return new BlockGenerationManifest(
            shouldInstallBlock: false,
            shouldRebuildBlock: false,
            blockHandlePascalCase: 'AllFieldTypesTest',
            blockHandleKebabCase: 'all-field-types-test',
            blockPath: '/not-written/all_field_types_test',
            blockPublicPath: '/application/blocks/all_field_types_test',
            blockIconPath: null,
            blockIconPublicPath: null,
            customBlockIcon: null,
            databaseTableName: 'btAllFieldTypesTest',
            entriesDatabaseTableName: 'btAllFieldTypesTestEntries',
        );
    }

    protected function createGenerationContext(BlockConfigDto $config): BlockFileGenerationContext
    {
        $manifest = $this->createManifest($config);
        $plan = $this->getService(BlockGenerationPlanFactory::class)->create($config, $manifest);

        return new BlockFileGenerationContext($config, $manifest, $plan);
    }

    protected function generateTextFiles(BlockFileGenerationContext $context): GeneratedTextFileCollection
    {
        $generatedFiles = new GeneratedTextFileCollection();
        foreach ($this->getService(FileGeneratorCollection::class) as $fileGenerator) {
            $generatedFiles->addAll($fileGenerator->generate($context));
        }

        return $generatedFiles;
    }

    /**
     * @template TObject of object
     * @param class-string<TObject> $class
     * @return TObject
     */
    protected function getService(string $class): object
    {
        if (isset($this->services[$class])) {
            return $this->services[$class];
        }
        if ($class === EnvironmentService::class) {
            return $this->services[$class] = $this->createEnvironmentService();
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $this->services[$class] = $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && $type->isBuiltin() && $parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new RuntimeException(sprintf(
                    'Test service "%s" has an unsupported constructor parameter "$%s".',
                    $class,
                    $parameter->getName(),
                ));
            }
            $arguments[] = $this->getService($type->getName());
        }

        return $this->services[$class] = $reflection->newInstanceArgs($arguments);
    }

    private function getEnvironmentService(): EnvironmentService
    {
        return $this->getService(EnvironmentService::class);
    }

    private function createEnvironmentService(): EnvironmentService
    {
        $systemInfo = $this->getMockBuilder(SystemInfo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPhpVersion'])
            ->getMock();
        $systemInfo->method('getPhpVersion')->willReturn('8.4.0');

        $config = $this->getMockBuilder(ConfigRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $config->method('get')->with('concrete.version')->willReturn('9.5.2');

        $package = new class {
            public function getPackageVersion(): string
            {
                return '3.0.0';
            }

            public function getPackageHandle(): string
            {
                return EnvironmentService::PACKAGE_HANDLE;
            }
        };
        $packageService = $this->getMockBuilder(PackageService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByHandle'])
            ->getMock();
        $packageService->method('getByHandle')
            ->with(EnvironmentService::PACKAGE_HANDLE)
            ->willReturn($package);

        return new EnvironmentService($systemInfo, $config, $packageService);
    }
}
