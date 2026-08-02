<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\FieldType;

use BlockBuilder\Block\Request\CreateBlockInputNormalizer;
use BlockBuilder\Block\Validation\Validator\FieldType\FieldTypeValidator;
use BlockBuilder\FieldType\FieldTypeRegistry;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

final class FieldTypeInputValidationTest extends BlockBuilderTestCase
{
    public function testNormalizerRejectsNestedScalarValuesUsingFormLabels(): void
    {
        $field = $this->createField('text_field', 'exampleText', [
            'placeholder' => ['nested value'],
        ]);

        $result = $this->getService(CreateBlockInputNormalizer::class)->normalize([
            'cacheBlockRecord' => ['1'],
            'basic' => [$field],
            'entries' => [],
        ]);

        self::assertSame([
            'The field "Cache block record" has an invalid value type.',
            'The field "Placeholder" has an invalid value type.',
        ], $result->feedback->errors);
        self::assertSame([
            'cacheBlockRecord',
            'basic[0][placeholder]',
        ], $result->feedback->fieldsWithError);
        self::assertSame(['tab-basic-information'], $result->feedback->tabsWithError);
        self::assertSame('0', $result->data['cacheBlockRecord']);
        self::assertSame('', $result->data['basic'][0]['placeholder']);
    }

    public function testNormalizerReportsMalformedAndUnknownFieldTypesWithoutThrowing(): void
    {
        $result = $this->getService(CreateBlockInputNormalizer::class)->normalize([
            'basic' => [
                ['fieldType' => ['text_field']],
                ['fieldType' => 'unknown_field_type'],
            ],
            'entries' => [],
        ]);

        self::assertSame([
            'A field in the "basic" collection has an invalid "Field type" value.',
            'A field in the "basic" collection uses an unsupported field type.',
        ], $result->feedback->errors);
        self::assertSame([
            'basic[0][fieldType]',
            'basic[1][fieldType]',
        ], $result->feedback->fieldsWithError);
        self::assertSame(['tab-basic-information'], $result->feedback->tabsWithError);
        self::assertSame([], $result->data['basic']);
    }

    public function testValidatorRejectsEveryFieldTypeOptionOutsideItsAllowList(): void
    {
        $feedback = $this->getService(FieldTypeValidator::class)->validate([
            'basic' => [
                $this->createField('text_field', 'textOption', [
                    'additionalValidation' => 'postal_code',
                ]),
                $this->createField('select_field', 'singleChoice', [
                    'displayType' => 'dropdown',
                    'addEmptyOption' => 'yes',
                    'listGenerationMethod' => 'runtime',
                    'options' => 'first :: First',
                    'defaultValue' => 'first',
                ]),
                $this->createField('select_multiple_field', 'multipleChoice', [
                    'displayType' => 'multi_dropdown',
                    'listGenerationMethod' => 'runtime',
                    'options' => 'first :: First',
                    'defaultValue' => 'first',
                ]),
                $this->createField('files_from_folder', 'folderFiles', [
                    'fileOrder' => 'newest',
                ]),
            ],
            'entries' => [],
        ]);

        self::assertSame([
            'Some "Text/Additional validation" fields contain an invalid option (Tab: Basic information).',
            'Some "Single Choice Field/Type" fields contain an invalid option (Tab: Basic information).',
            'Some "Single Choice Field/Add an empty option" fields contain an invalid option (Tab: Basic information).',
            'Some "Single Choice Field/List generation method" fields contain an invalid option (Tab: Basic information).',
            'Some "Multiple Choice Field/Type" fields contain an invalid option (Tab: Basic information).',
            'Some "Multiple Choice Field/List generation method" fields contain an invalid option (Tab: Basic information).',
            'Some "File order" fields contain an invalid option (Tab: Basic information).',
        ], $feedback->errors);
        self::assertSame([
            'basic[0][additionalValidation]',
            'basic[1][displayType]',
            'basic[1][addEmptyOption]',
            'basic[1][listGenerationMethod]',
            'basic[2][displayType]',
            'basic[2][listGenerationMethod]',
            'basic[3][fileOrder]',
        ], $feedback->fieldsWithError);
        self::assertSame(['tab-basic-information'], $feedback->tabsWithError);
    }

    public function testDuplicateHandlesAndMultipleTitleSourcesAreScopedToRepeatableFields(): void
    {
        $feedback = $this->getService(FieldTypeValidator::class)->validate([
            'basic' => [
                $this->createField('text_field', 'sharedHandle'),
            ],
            'entries' => [
                $this->createField('text_field', 'sharedHandle', ['titleSource' => '1']),
                $this->createField('text_field', 'sharedHandle', ['titleSource' => '1']),
            ],
        ]);

        self::assertSame([
            'Only one repeatable field can be used as the entry title source.',
            'All "Handle" fields must be unique (Tab: Repeatable entries).',
        ], $feedback->errors);
        self::assertSame([
            'entries[0][titleSource]',
            'entries[1][titleSource]',
            'entries[1][handle]',
        ], $feedback->fieldsWithError);
        self::assertSame(['tab-repeatable-entries'], $feedback->tabsWithError);
        self::assertNotContains('basic[0][handle]', $feedback->fieldsWithError);
    }

    private function createField(string $fieldTypeHandle, string $handle, array $overrides = []): array
    {
        $fieldType = $this->getService(FieldTypeRegistry::class)->findByHandle($fieldTypeHandle);
        self::assertNotNull($fieldType, sprintf('Field type "%s" must be registered.', $fieldTypeHandle));

        return [
            'fieldType' => $fieldTypeHandle,
            'label' => 'Example field',
            'handle' => $handle,
            'required' => '0',
            'helpText' => '',
            ...$fieldType::getDefaultValues(),
            ...$overrides,
        ];
    }
}
