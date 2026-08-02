<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Config;

use BlockBuilder\Block\Exception\ConfigFileMissingException;
use BlockBuilder\Block\Exception\ConfigFileTooLargeException;
use BlockBuilder\Block\Exception\ConfigFileUnreadableException;
use BlockBuilder\Block\Exception\ConfigVersionTooNewException;
use BlockBuilder\Block\Exception\InvalidConfigFieldDataException;
use BlockBuilder\Block\Exception\InvalidConfigJsonException;
use BlockBuilder\Block\Exception\UnsupportedConfigSchemaException;
use BlockBuilder\Block\Service\BlockConfigReader;
use BlockBuilder\Block\Service\BlockDirectoryLocator;
use BlockBuilder\Block\Validation\BlockConfigLimits;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Closure;

final class BlockConfigReaderBoundaryTest extends BlockBuilderTestCase
{
    private const int MAXIMUM_CONFIG_FILE_SIZE = 5_242_880;
    private const string SOURCE_HANDLE = 'reader_boundary_test';

    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-config-reader-'
            . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->temporaryDirectory, 0700));
    }

    protected function tearDown(): void
    {
        $paths = scandir($this->temporaryDirectory);
        if (is_array($paths)) {
            foreach ($paths as $path) {
                if ($path === '.' || $path === '..') {
                    continue;
                }

                $absolutePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . $path;
                if (is_link($absolutePath) || is_file($absolutePath)) {
                    unlink($absolutePath);
                } elseif (is_dir($absolutePath)) {
                    rmdir($absolutePath);
                }
            }
        }
        rmdir($this->temporaryDirectory);

        parent::tearDown();
    }

    public function testMissingConfigIsReportedThroughThePublicReader(): void
    {
        $this->expectException(ConfigFileMissingException::class);
        $this->expectExceptionMessage(basename($this->temporaryDirectory) . '/config-bb.json');

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    public function testDirectoryAtConfigPathIsRejected(): void
    {
        self::assertTrue(mkdir($this->getConfigPath()));

        $this->expectException(ConfigFileUnreadableException::class);
        $this->expectExceptionMessage('exists but could not be read');

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    public function testSymlinkedConfigIsRejected(): void
    {
        $targetPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'target.json';
        self::assertNotFalse(file_put_contents($targetPath, '{}'));
        if (!@symlink($targetPath, $this->getConfigPath())) {
            self::markTestSkipped('The current filesystem does not support symbolic links.');
        }

        $this->expectException(ConfigFileUnreadableException::class);
        $this->expectExceptionMessage('exists but could not be read');

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    /**
     * @dataProvider invalidDocumentProvider
     */
    public function testInvalidDocumentsAreRejectedThroughThePublicReader(
        string $contents,
        string $expectedException,
        string $expectedMessage,
    ): void {
        $this->writeRawConfig($contents);

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    public static function invalidDocumentProvider(): array
    {
        return [
            'truncated JSON' => [
                '{"basic":',
                InvalidConfigJsonException::class,
                'contains invalid JSON',
            ],
            'scalar JSON root' => [
                '"not an object"',
                UnsupportedConfigSchemaException::class,
                'must contain a JSON object',
            ],
            'list JSON root' => [
                '[]',
                UnsupportedConfigSchemaException::class,
                'JSON object rather than a JSON list',
            ],
        ];
    }

    /**
     * @dataProvider invalidVersionProvider
     */
    public function testInvalidVersionValuesAreRejected(mixed $version): void
    {
        $data = $this->createValidConfigData();
        $data['blockBuilderVersion'] = $version;
        $this->writeConfig($data);

        $this->expectException(UnsupportedConfigSchemaException::class);
        $this->expectExceptionMessage('invalid Block Builder version information');

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    public static function invalidVersionProvider(): array
    {
        return [
            'integer' => [3],
            'empty string' => [''],
            'prefixed version' => ['v3.0.0'],
            'four numeric components' => ['3.0.0.0'],
            'empty numeric component' => ['3..0'],
            'empty prerelease' => ['3.0.0-'],
        ];
    }

    /**
     * @dataProvider supportedVersionProvider
     */
    public function testSupportedSemanticVersionFormatsAreAccepted(string $version): void
    {
        $data = $this->createValidConfigData();
        $data['blockBuilderVersion'] = $version;
        $this->writeConfig($data);

        $config = $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);

        self::assertSame($version, $config->blockBuilderVersion);
    }

    public static function supportedVersionProvider(): array
    {
        return [
            'major' => ['3'],
            'major and minor' => ['3.0'],
            'prerelease' => ['3.0.0-beta'],
            'build metadata' => ['3.0.0+build.5'],
        ];
    }

    public function testFutureVersionIsRejected(): void
    {
        $data = $this->createValidConfigData();
        $data['blockBuilderVersion'] = '3.0.1';
        $this->writeConfig($data);

        $this->expectException(ConfigVersionTooNewException::class);
        $this->expectExceptionMessage('newer than the current environment version "3.0.0"');

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    public function testInvalidSourceHandleIsRejectedBeforeReadingTheFile(): void
    {
        $this->expectException(InvalidConfigFieldDataException::class);
        $this->expectExceptionMessage('invalid source identifier');

        $this->createReader()->getConfigFromApplicationFolder('../unsafe');
    }

    public function testDeclaredHandleMustMatchTheSourceHandle(): void
    {
        $data = $this->createValidConfigData();
        $data['blockHandle'] = 'different_handle';
        $this->writeConfig($data);

        $this->expectException(InvalidConfigFieldDataException::class);
        $this->expectExceptionMessage('declares block handle "different_handle"');
        $this->expectExceptionMessage('source identifier is "' . self::SOURCE_HANDLE . '"');

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    /**
     * @dataProvider schemaLimitViolationProvider
     */
    public function testSchemaAndResourceLimitViolationsAreRejected(
        Closure $mutateConfig,
        string $expectedException,
        string $expectedMessage,
    ): void {
        $data = $this->createValidConfigData();
        $mutateConfig($data);
        $this->writeConfig($data);

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    public static function schemaLimitViolationProvider(): array
    {
        $textField = static fn(array $overrides = []): array => [
            'fieldType' => 'text_field',
            'label' => 'Example field',
            'handle' => 'exampleField',
            ...$overrides,
        ];
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><path d="M0 0h1v1z"></path></svg>';

        return [
            'missing basic collection' => [
                static function (array &$data): void {
                    unset($data['basic']);
                },
                InvalidConfigFieldDataException::class,
                '"basic" fields',
            ],
            'non-array entries collection' => [
                static function (array &$data): void {
                    $data['entries'] = 'invalid';
                },
                InvalidConfigFieldDataException::class,
                'must be provided as an array',
            ],
            'non-array field' => [
                static function (array &$data): void {
                    $data['basic'] = ['invalid'];
                },
                InvalidConfigFieldDataException::class,
                'must be provided as an array',
            ],
            'too many fields' => [
                static function (array &$data) use ($textField): void {
                    $data['basic'] = array_fill(0, BlockConfigLimits::MAX_FIELDS_PER_COLLECTION + 1, $textField());
                },
                InvalidConfigFieldDataException::class,
                'may contain at most 100 fields',
            ],
            'top-level long text' => [
                static function (array &$data): void {
                    $data['blockDescription'] = str_repeat('x', 100_001);
                },
                UnsupportedConfigSchemaException::class,
                'property "blockDescription"',
            ],
            'field placeholder' => [
                static function (array &$data) use ($textField): void {
                    $data['basic'] = [$textField(['placeholder' => str_repeat('x', 256)])];
                },
                InvalidConfigFieldDataException::class,
                'Property "placeholder"',
            ],
            'too many choice options' => [
                static function (array &$data): void {
                    $data['basic'] = [[
                        'fieldType' => 'select_field',
                        'label' => 'Example choice',
                        'handle' => 'exampleChoice',
                        'options' => implode("\n", array_fill(0, BlockConfigLimits::MAX_OPTIONS_PER_FIELD + 1, 'option')),
                    ]];
                },
                InvalidConfigFieldDataException::class,
                'may contain at most 1000 options',
            ],
            'too many SVG icons' => [
                static function (array &$data) use ($svg): void {
                    $data['basic'] = [[
                        'fieldType' => 'svg_icon_picker',
                        'label' => 'Example icons',
                        'handle' => 'exampleIcons',
                        'icons' => array_fill(0, BlockConfigLimits::MAX_SVG_ICONS_PER_FIELD + 1, [
                            'name' => 'Icon',
                            'handle' => 'icon',
                            'svg' => $svg,
                        ]),
                    ]];
                },
                InvalidConfigFieldDataException::class,
                'may contain at most 100 SVG icons',
            ],
            'oversized SVG property' => [
                static function (array &$data) use ($svg): void {
                    $data['basic'] = [[
                        'fieldType' => 'svg_icon_picker',
                        'label' => 'Example icons',
                        'handle' => 'exampleIcons',
                        'icons' => [[
                            'name' => str_repeat('x', BlockConfigLimits::MAX_SVG_ICON_NAME_LENGTH + 1),
                            'handle' => 'icon',
                            'svg' => $svg,
                        ]],
                    ]];
                },
                InvalidConfigFieldDataException::class,
                'SVG icon property "name"',
            ],
        ];
    }

    public function testExactSchemaAndCollectionLimitsAreAccepted(): void
    {
        $data = $this->createValidConfigData();
        $data['blockDescription'] = str_repeat('x', 100_000);
        $data['basic'] = array_map(
            static fn(int $fieldNumber): array => [
                'fieldType' => 'text_field',
                'label' => sprintf('Example field %d', $fieldNumber),
                'handle' => sprintf('exampleField%d', $fieldNumber),
                'placeholder' => str_repeat('x', 255),
            ],
            range(1, BlockConfigLimits::MAX_FIELDS_PER_COLLECTION),
        );
        $data['entries'] = [[
            'fieldType' => 'select_field',
            'label' => 'Example choice',
            'handle' => 'exampleChoice',
            'options' => implode("\n", array_map(
                static fn(int $optionNumber): string => sprintf('option%d :: Option %d', $optionNumber, $optionNumber),
                range(1, BlockConfigLimits::MAX_OPTIONS_PER_FIELD),
            )),
        ]];
        $this->writeConfig($data);

        $config = $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);

        self::assertSame(self::SOURCE_HANDLE, $config->blockHandle);
        self::assertCount(BlockConfigLimits::MAX_FIELDS_PER_COLLECTION, $config->basic);
        self::assertCount(1, $config->entries);
    }

    public function testMaximumConfigFileSizeIsAccepted(): void
    {
        $json = json_encode($this->createValidConfigData(), JSON_THROW_ON_ERROR);
        self::assertLessThan(self::MAXIMUM_CONFIG_FILE_SIZE, strlen($json));
        $this->writeRawConfig(
            $json . str_repeat(' ', self::MAXIMUM_CONFIG_FILE_SIZE - strlen($json)),
        );

        $config = $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);

        self::assertSame(self::SOURCE_HANDLE, $config->blockHandle);
        self::assertSame(self::MAXIMUM_CONFIG_FILE_SIZE, filesize($this->getConfigPath()));
    }

    public function testConfigOneByteOverMaximumSizeIsRejected(): void
    {
        $json = json_encode($this->createValidConfigData(), JSON_THROW_ON_ERROR);
        $this->writeRawConfig(
            $json . str_repeat(' ', self::MAXIMUM_CONFIG_FILE_SIZE + 1 - strlen($json)),
        );

        $this->expectException(ConfigFileTooLargeException::class);
        $this->expectExceptionMessage('maximum allowed size of 5 MB');

        $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
    }

    public function testFieldFactoryFailuresRetainTheirPreviousException(): void
    {
        $data = $this->createValidConfigData();
        $data['basic'] = [[
            'fieldType' => 'unknown_field_type',
            'label' => 'Unknown field',
            'handle' => 'unknownField',
        ]];
        $this->writeConfig($data);

        try {
            $this->createReader()->getConfigFromApplicationFolder(self::SOURCE_HANDLE);
            self::fail('An invalid field type should not be loaded.');
        } catch (InvalidConfigFieldDataException $exception) {
            self::assertStringContainsString('contains invalid field data', $exception->getMessage());
            self::assertNotNull($exception->getPrevious());
            self::assertStringContainsString('Unknown field type', $exception->getPrevious()->getMessage());
        }
    }

    private function createReader(): BlockConfigReader
    {
        $directory = $this->temporaryDirectory;
        $blockDirectoryLocator = new readonly class($directory) extends BlockDirectoryLocator {
            public function __construct(private string $directory)
            {
            }

            public function getSafeApplicationBlockDirectory(string $handle): ?string
            {
                return $this->directory;
            }
        };

        return new BlockConfigReader(
            blockConfigDtoFactory: $this->createBlockConfigDtoFactory(),
            environmentService: $this->getService(EnvironmentService::class),
            blockDirectoryLocator: $blockDirectoryLocator,
        );
    }

    private function createValidConfigData(): array
    {
        $path = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'predefined_configs'
            . DIRECTORY_SEPARATOR
            . 'all_fields.json';
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);
        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        $data['blockHandle'] = self::SOURCE_HANDLE;

        return $data;
    }

    private function writeConfig(array $data): void
    {
        $this->writeRawConfig(json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function writeRawConfig(string $contents): void
    {
        self::assertSame(strlen($contents), file_put_contents($this->getConfigPath(), $contents));
    }

    private function getConfigPath(): string
    {
        return $this->temporaryDirectory . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JSON;
    }
}
