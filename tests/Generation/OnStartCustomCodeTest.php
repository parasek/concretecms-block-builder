<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\Block\Request\CreateBlockInputNormalizer;
use BlockBuilder\Block\Validation\BlockConfigLimits;
use BlockBuilder\BlockGenerator\FileGenerator\ConfigBbJson\ConfigBbJsonFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\ControllerPhpFileGenerator;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use ReflectionMethod;

final class OnStartCustomCodeTest extends BlockBuilderTestCase
{
    public function testCustomInitializationRunsAfterParentWithoutFields(): void
    {
        $code = '        $this->calls[] = "custom";';
        $method = $this->renderOnStart(['onStartCustomCode' => $code]);
        $controller = eval('return new class extends \\BlockBuilder\\Tests\\Generation\\OnStartTestController {' . $method . '};');
        $controller->on_start();

        self::assertSame(['parent', 'custom'], $controller->calls);
    }

    public function testCustomCodeFollowsGeneratedChoiceInitialization(): void
    {
        $config = $this->createBlockConfigDtoFactory()->fromGenerationArray([
            'blockHandle' => 'startup_example',
            'basic' => [[
                'fieldType' => 'select_field',
                'handle' => 'choice',
                'label' => 'Choice',
                'options' => 'one :: One',
            ]],
            'onStartCustomCode' => '        $this->set("customMarker", true);',
        ]);
        $context = $this->createGenerationContext($config);
        $files = $this->getService(ControllerPhpFileGenerator::class)->generate($context);
        token_get_all($files[0]->contents, TOKEN_PARSE);
        $method = (new ReflectionMethod(ControllerPhpFileGenerator::class, 'renderOnStartMethod'))
            ->invoke($this->getService(ControllerPhpFileGenerator::class), $context);

        self::assertSame(1, substr_count($method, 'parent::on_start();'));
        self::assertLessThan(strpos($method, 'customMarker'), strpos($method, '$this->set('));
    }

    public function testBlankCodeDoesNotCreateUnnecessaryMethod(): void
    {
        self::assertSame('', $this->renderOnStart([]));
        self::assertSame('', $this->renderOnStart(['onStartCustomCode' => " \r\n "]));
    }

    public function testConfigurationRoundTripPreservesCustomCode(): void
    {
        $code = '        $this->set("startup", true);';
        $normalized = $this->getService(CreateBlockInputNormalizer::class)->normalize([
            'onStartCustomCode' => $code,
        ]);
        self::assertSame($code, $normalized->data['onStartCustomCode']);
        self::assertSame(500_000, BlockConfigLimits::getTopLevelStringMaximum('onStartCustomCode'));
        $config = $this->createBlockConfigDtoFactory()->fromGenerationArray([
            'blockHandle' => 'startup_example',
            'onStartCustomCode' => $normalized->data['onStartCustomCode'],
        ]);
        $files = $this->getService(ConfigBbJsonFileGenerator::class)->generate($this->createGenerationContext($config));
        $saved = json_decode($files[0]->contents, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($code, $saved['onStartCustomCode']);
        self::assertSame($code, $this->createBlockConfigDtoFactory()->fromArray($saved)->onStartCustomCode);
    }

    /** @dataProvider invalidCustomCodeProvider */
    public function testInvalidCustomCodeIsRejected(mixed $code): void
    {
        $normalized = $this->getService(CreateBlockInputNormalizer::class)->normalize([
            'onStartCustomCode' => $code,
        ]);

        self::assertContains('onStartCustomCode', $normalized->feedback->fieldsWithError);
    }

    public static function invalidCustomCodeProvider(): array
    {
        return [
            'non-string value' => [['unexpected']],
            'oversized snippet' => [str_repeat('x', 500_001)],
        ];
    }

    private function renderOnStart(array $data): string
    {
        $config = $this->createBlockConfigDtoFactory()->fromGenerationArray([
            'blockHandle' => 'startup_example',
            ...$data,
        ]);

        return (new ReflectionMethod(ControllerPhpFileGenerator::class, 'renderOnStartMethod'))
            ->invoke($this->getService(ControllerPhpFileGenerator::class), $this->createGenerationContext($config));
    }
}

class OnStartTestController
{
    public array $calls = [];

    public function on_start(): void
    {
        $this->calls[] = 'parent';
    }
}
