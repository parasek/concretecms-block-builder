<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\ControllerPhpFileGenerator;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\DatePicker\DatePickerFieldType;
use BlockBuilder\FieldType\Type\MultipleChoice\MultipleChoiceFieldType;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use PhpParser\ParserFactory;

/**
 * Test type: Generated Composer-validation output contract test.
 *
 * Verifies that database values are reconstructed into their submitted form shapes before the
 * generated controller validates an existing block through Composer.
 */
final class ComposerValidationGenerationTest extends BlockBuilderTestCase
{
    public function testComposerValidationReconstructsPersistedFieldValues(): void
    {
        $config = $this->createBlockConfigDtoFactory()->fromGenerationArray([
            'blockName' => 'Composer Validation Test',
            'blockHandle' => 'composer_validation_test',
            'basic' => [
                $this->createMultipleChoiceFieldConfig('Basic choices', 'basicChoices'),
                $this->createDatePickerFieldConfig('Basic date', 'basicDate', false),
                $this->createDatePickerFieldConfig('Basic date and time', 'basicDateTime', true),
            ],
            'entries' => [
                $this->createMultipleChoiceFieldConfig('Repeatable choices', 'repeatableChoices'),
                $this->createDatePickerFieldConfig('Repeatable date', 'repeatableDate', false),
                $this->createDatePickerFieldConfig('Repeatable date and time', 'repeatableDateTime', true),
            ],
        ]);
        [$generatedController] = $this->getService(ControllerPhpFileGenerator::class)->generate(
            $this->createGenerationContext($config),
        );
        $controllerCode = preg_replace('/^[ \t]+/m', '', $generatedController->contents);
        self::assertIsString($controllerCode);

        self::assertStringContainsString(
            <<<'PHP'
if (is_string($args['basicChoices'] ?? null)) {
$args['basicChoices'] = $args['basicChoices'] === '' ? [] : explode('|', $args['basicChoices']);
}
PHP,
            $controllerCode,
        );
        self::assertStringContainsString(
            <<<'PHP'
$dateTimeParts = $this->getBlockBuilderDateTimeFormParts($args['basicDate'] ?? null, false);
if ($dateTimeParts['date'] !== '') {
$args['basicDate'] = $dateTimeParts['date'];
}
PHP,
            $controllerCode,
        );
        self::assertStringContainsString(
            <<<'PHP'
$dateTimeParts = $this->getBlockBuilderDateTimeFormParts($args['basicDateTime'] ?? null, true);
if ($dateTimeParts['date'] !== '') {
$args['basicDateTime'] = $dateTimeParts['date'];
$args['basicDateTime_hour'] = $dateTimeParts['hour'];
$args['basicDateTime_minute'] = $dateTimeParts['minute'];
}
PHP,
            $controllerCode,
        );
        self::assertStringContainsString(
            '$args[\'entry\'] = $this->getEntries(\'composer\');',
            $controllerCode,
        );

        $composerBranchPosition = strpos($controllerCode, '} elseif ($outputMethod === \'composer\') {');
        self::assertNotFalse($composerBranchPosition);
        $composerBranchEndPosition = strpos($controllerCode, 'unset($entry);', $composerBranchPosition);
        self::assertNotFalse($composerBranchEndPosition);
        $composerBranch = substr(
            $controllerCode,
            $composerBranchPosition,
            $composerBranchEndPosition - $composerBranchPosition,
        );
        self::assertStringContainsString(
            <<<'PHP'
if (is_string($entry['repeatableChoices'] ?? null)) {
$entry['repeatableChoices'] = $entry['repeatableChoices'] === '' ? [] : explode('|', $entry['repeatableChoices']);
}
PHP,
            $composerBranch,
        );
        self::assertStringContainsString(
            <<<'PHP'
$dateTimeParts = $this->getBlockBuilderDateTimeFormParts($entry['repeatableDate'] ?? null, false);
$entry['repeatableDate'] = $dateTimeParts['date'];
PHP,
            $composerBranch,
        );
        self::assertStringContainsString(
            <<<'PHP'
$dateTimeParts = $this->getBlockBuilderDateTimeFormParts($entry['repeatableDateTime'] ?? null, true);
$entry['repeatableDateTime'] = $dateTimeParts['date'];
$entry['repeatableDateTime_hour'] = $dateTimeParts['hour'];
$entry['repeatableDateTime_minute'] = $dateTimeParts['minute'];
PHP,
            $composerBranch,
        );

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        self::assertNotNull($parser->parse($generatedController->contents));
    }

    private function createMultipleChoiceFieldConfig(string $label, string $handle): array
    {
        return [
            'fieldType' => FieldTypeEnum::MultipleChoice->value,
            'label' => $label,
            'handle' => $handle,
            'required' => true,
            'helpText' => '',
            ...MultipleChoiceFieldType::getDefaultValues(),
            'options' => "alpha :: Alpha\nbeta :: Beta",
        ];
    }

    private function createDatePickerFieldConfig(string $label, string $handle, bool $attachTimeSelector): array
    {
        return [
            'fieldType' => FieldTypeEnum::DatePicker->value,
            'label' => $label,
            'handle' => $handle,
            'required' => true,
            'helpText' => '',
            ...DatePickerFieldType::getDefaultValues(),
            'attachTimeSelector' => $attachTimeSelector,
            'minuteInterval' => 15,
        ];
    }
}
