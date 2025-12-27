<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Console;

use ArrayObject;
use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Exception\ConfigLoadingException;
use BlockBuilder\Block\Request\CreateBlockInputNormalizer;
use BlockBuilder\Block\Service\BlockConfigReader;
use BlockBuilder\Block\Service\BlockDirectoryLocator;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\Block\Validation\CreateBlockValidatorCollection;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Block\Validation\Validator\FieldType\FieldTypeValidator;
use BlockBuilder\BlockGenerator\BlockGenerationManifest;
use BlockBuilder\BlockGenerator\BlockGenerationManifestFactory;
use BlockBuilder\BlockGenerator\BlockGenerationResult;
use BlockBuilder\BlockGenerator\BlockGenerator;
use BlockBuilder\BlockGenerator\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\Console\RebuildBlockCommand;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\FileBag;

final class RebuildBlockCommandTest extends BlockBuilderTestCase
{
    private string $configPath;
    private ArrayObject $calls;

    protected function setUp(): void
    {
        $directory = sys_get_temp_dir() . '/block-builder-rebuild-' . bin2hex(random_bytes(8));
        mkdir($directory . '/console_example', 0700, true);
        $this->configPath = $directory . '/console_example/config-bb.json';
        $this->calls = new ArrayObject();
        $this->writeConfig();
    }

    protected function tearDown(): void
    {
        unlink($this->configPath);
        rmdir(dirname($this->configPath));
        rmdir(dirname($this->configPath, 2));
    }

    public function testRebuildUsesExistingConfigurationAndPreservesIcon(): void
    {
        $command = $this->createCommand();
        self::assertSame(0, $command->execute(['handle' => 'console_example']));
        self::assertStringContainsString('rebuilt and refreshed', $command->getDisplay());
        self::assertCount(2, $this->calls);
        $generation = $this->calls[1];
        self::assertSame('console_example', $generation['config']->blockHandle);
        self::assertTrue($generation['manifest']->shouldInstallBlock);
        self::assertTrue($generation['manifest']->shouldRebuildBlock);
        self::assertSame('/application/blocks/console_example/icon.png', $generation['manifest']->blockIconPublicPath);
        self::assertSame(DIR_BASE . '/application/blocks/console_example', $generation['manifest']->blockPath);
        self::assertSame('3.0.0', $generation['config']->blockBuilderVersion);
    }

    public function testRebuildStillRefreshesWhenInstallSettingIsDisabled(): void
    {
        $this->writeConfig(['installBlock' => false]);
        $command = $this->createCommand();
        self::assertSame(0, $command->execute(['handle' => 'console_example']));
        self::assertFalse($this->calls[1]['manifest']->shouldInstallBlock);
        self::assertTrue($this->calls[1]['manifest']->shouldRebuildBlock);
        self::assertStringContainsString('rebuilt and refreshed', $command->getDisplay());
    }

    public function testValidationOnlyNeverCallsGenerator(): void
    {
        $command = $this->createCommand();
        self::assertSame(0, $command->execute(['handle' => 'console_example', '--validate-only' => true]));
        self::assertCount(1, $this->calls);
        self::assertStringContainsString('is valid', $command->getDisplay());
        self::assertSame('1', $this->calls[0]['installBlock']);
        self::assertSame('1', $this->calls[0]['rebuildBlock']);
        self::assertSame('console_example', $this->calls[0]['rebuildSourceHandle']);
        self::assertSame('templates', $this->calls[0]['excludedFromRemoval']);
    }

    public function testBusinessValidationFailureNeverCallsGenerator(): void
    {
        $command = $this->createCommand(['The block is not installed.', 'Invalid field handle.']);
        self::assertSame(2, $command->execute(['handle' => 'console_example']));
        self::assertCount(1, $this->calls);
        self::assertStringContainsString('The block is not installed.', $command->getDisplay());
        self::assertStringContainsString('Invalid field handle.', $command->getDisplay());
    }

    public function testDuplicateFieldHandlesStopGeneration(): void
    {
        $field = ['fieldType' => 'text_field', 'label' => 'Title', 'handle' => 'title'];
        $this->writeConfig(['basic' => [$field, $field], 'entries' => []]);
        $command = $this->createCommand();

        self::assertSame(2, $command->execute(['handle' => 'console_example']));
        self::assertCount(1, $this->calls);
    }

    /** @dataProvider invalidConfigProvider */
    public function testMalformedConfigurationsNeverReachValidation(string $contents): void
    {
        file_put_contents($this->configPath, $contents);
        $command = $this->createCommand();
        try {
            $command->execute(['handle' => 'console_example']);
            self::fail('Invalid configuration was accepted.');
        } catch (ConfigLoadingException) {
            self::assertCount(0, $this->calls);
        }
    }

    public static function invalidConfigProvider(): array
    {
        return [
            'malformed JSON' => ['{'],
            'list' => ['[]'],
            'null collection' => ['{"blockHandle":"example","basic":null}'],
            'unsupported rebuild flag' => ['{"basic":[],"entries":[],"rebuildBlock":true}'],
            'unknown field type' => ['{"basic":[{"fieldType":"unknown"}],"entries":[]}'],
        ];
    }

    /** @dataProvider invalidHandleProvider */
    public function testInvalidSourceNeverReachesGeneration(string $handle): void
    {
        $command = $this->createCommand();
        $this->expectException(ConfigLoadingException::class);
        try {
            $command->execute(['handle' => $handle]);
        } finally {
            self::assertCount(0, $this->calls);
        }
    }

    public static function invalidHandleProvider(): array
    {
        return [['missing_block'], ['../console_example'], ['Invalid']];
    }

    public function testMismatchedConfigHandleNeverReachesGeneration(): void
    {
        $this->writeConfig(['blockHandle' => 'another_block']);
        $command = $this->createCommand();
        $this->expectException(ConfigLoadingException::class);
        try {
            $command->execute(['handle' => 'console_example']);
        } finally {
            self::assertCount(0, $this->calls);
        }
    }

    private function writeConfig(array $overrides = []): void
    {
        $data = json_decode(file_get_contents(dirname(__DIR__, 2) . '/predefined_configs/all_fields.json'), true, flags: JSON_THROW_ON_ERROR);
        $data = array_replace($data, [
            'blockHandle' => 'console_example',
            'installBlock' => true,
            'excludedFromRemoval' => ['templates'],
        ], $overrides);
        file_put_contents($this->configPath, json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function createCommand(array $errors = []): CommandTester
    {
        $validators = new readonly class($this->calls, $errors, $this->getService(FieldTypeValidator::class)) extends CreateBlockValidatorCollection {
            public function __construct(private ArrayObject $calls, private array $errors, private FieldTypeValidator $fieldValidator)
            {
            }

            public function validate(array $data, FileBag $files): ValidationFeedback
            {
                $this->calls[] = $data;

                return new ValidationFeedback(errors: [...$this->errors, ...$this->fieldValidator->validate($data)->errors]);
            }
        };
        $generator = new readonly class($this->calls) extends BlockGenerator {
            public function __construct(private ArrayObject $calls)
            {
            }

            public function generate(BlockConfigDto $config, BlockGenerationManifest $manifest): BlockGenerationResult
            {
                $this->calls[] = ['config' => $config, 'manifest' => $manifest];

                return new BlockGenerationResult(
                    $config->blockName,
                    $config->blockHandle,
                    PostGenerationBlockStateEnum::Rebuilt,
                );
            }
        };

        $directoryLocator = new readonly class(dirname($this->configPath, 2)) extends BlockDirectoryLocator {
            public function __construct(private string $directory)
            {
            }

            public function getApplicationBlocksPath(): string
            {
                return $this->directory;
            }
        };

        return new CommandTester(new RebuildBlockCommand(
            new BlockConfigReader($this->createBlockConfigDtoFactory(), $this->getService(EnvironmentService::class), $directoryLocator),
            $this->getService(CreateBlockInputNormalizer::class),
            $validators,
            $this->createBlockConfigDtoFactory(),
            new BlockGenerationManifestFactory(),
            $generator,
        ));
    }
}
