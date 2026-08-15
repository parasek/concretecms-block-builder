<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\MultipleChoice\Generation;

use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContext;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorInterface;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerProperty;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use BlockBuilder\BlockGenerator\Generation\Plan\FormGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\MultipleChoice\MultipleChoiceFieldTypeDto;

readonly class MultipleChoiceFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string NORMALIZER_METHOD = 'normalizeBlockBuilderMultipleChoiceValues';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::MultipleChoice;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof MultipleChoiceFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Multiple choice field generation requires DTO "%s"; "%s" was provided.',
                MultipleChoiceFieldTypeDto::class,
                $context->fieldDto::class,
            ));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;
        $optionNames = $this->getOptionNames($field, $context->isBasicField());

        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'text',
                order: $context->position,
            ),
        );
        $planBuilder->controller
            ->addSearchableField($context->fieldContext, $field->handle)
            ->addProperty(new ControllerProperty(
                name: $optionNames['property'],
                declaration: sprintf('protected ?array $%s = null;', $optionNames['property']),
                order: $context->position,
            ))
            ->addMethodFragment(
                ControllerMethodSectionEnum::OnStart->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix . '.options',
                    code: sprintf(
                        '$this->set(%s, $this->%s());',
                        $this->phpLiteralFormatter->format($optionNames['view']),
                        $optionNames['method'],
                    ),
                    order: $context->position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix . '.options',
                    code: $this->renderOptionsMethod($field, $optionNames),
                    order: $context->position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'multiple_choice.shared.normalizer',
                    code: $this->renderNormalizerMethod(),
                ),
            );

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode(
                field: $field,
                fragmentKeyPrefix: $fragmentKeyPrefix,
                optionsMethod: $optionNames['method'],
                position: $context->position,
                planBuilder: $planBuilder,
            );
        } else {
            $this->contributeRepeatableControllerCode(
                field: $field,
                fragmentKeyPrefix: $fragmentKeyPrefix,
                optionsMethod: $optionNames['method'],
                position: $context->position,
                planBuilder: $planBuilder,
            );
            $planBuilder->form
                ->addFragment(
                    FormGenerationPlanBuilder::SECTION_SETUP,
                    new CodeFragment(
                        key: $fragmentKeyPrefix . '.options',
                        code: sprintf(
                            '<?php $%1$s = isset($%1$s) && is_array($%1$s) ? $%1$s : []; ?>',
                            $optionNames['view'],
                        ),
                        order: $context->position,
                    ),
                )
                ->addRepeatableCapturedVariable($optionNames['view'])
                ->addRepeatableDefaultValue($field->handle, $field->defaultValue ?? '');
        }

        $planBuilder->view
            ->addFieldVariable(
                $context->fieldContext,
                new ViewVariableDocumentation(
                    name: $field->handle,
                    type: 'string|null',
                    description: sprintf('%s; selected option keys separated by "|"', $field->label),
                    order: $context->position * 10,
                ),
            )
            ->addVariable(new ViewVariableDocumentation(
                name: $optionNames['view'],
                type: 'array<array-key, string>',
                description: sprintf('Option labels for %s', $field->label),
                order: ($context->isBasicField() ? 0 : 100_000) + ($context->position * 10) + 1,
            ));

        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($field, $optionNames['view'], $context->isBasicField()),
                order: $context->position,
            ),
        );
        $planBuilder->view->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderViewFragment($field, $optionNames['view'], $context->isBasicField()),
                order: $context->position,
            ),
        );
    }

    private function contributeBasicControllerCode(
        MultipleChoiceFieldTypeDto $field,
        string $fragmentKeyPrefix,
        string $optionsMethod,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $defaultValueLiteral = $this->phpLiteralFormatter->format($field->defaultValue ?? '');

        $planBuilder->controller->addProperty(new ControllerProperty(
            name: $field->handle,
            declaration: sprintf('protected ?string $%s = null;', $field->handle),
            order: $position,
        ));
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::AddEdit->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$this->set(%1$s, $this->%2$s ?? %3$s);',
                    $handleLiteral,
                    $field->handle,
                    $defaultValueLiteral,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::SaveBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$args[%1$s] = implode(\'|\', $this->%2$s($args[%1$s] ?? [], $this->%3$s()));',
                    $handleLiteral,
                    self::NORMALIZER_METHOD,
                    $optionsMethod,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::ValidateBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderBasicValidation($field, $handleLiteral, $optionsMethod),
                order: $position,
            ),
        );
    }

    private function contributeRepeatableControllerCode(
        MultipleChoiceFieldTypeDto $field,
        string $fragmentKeyPrefix,
        string $optionsMethod,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::SaveEntryFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$data[%1$s] = implode(\'|\', $this->%2$s($entry[%1$s] ?? [], $this->%3$s()));',
                    $handleLiteral,
                    self::NORMALIZER_METHOD,
                    $optionsMethod,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::ValidateEntryFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderEntryValidation($field, $handleLiteral, $optionsMethod),
                order: $position,
            ),
        );
    }

    private function renderBasicValidation(
        MultipleChoiceFieldTypeDto $field,
        string $handleLiteral,
        string $optionsMethod,
    ): string {
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $lines = [
            sprintf('$submittedValues = $args[%s] ?? [];', $handleLiteral),
            sprintf(
                '$normalizedValues = $this->%s($submittedValues, $this->%s());',
                self::NORMALIZER_METHOD,
                $optionsMethod,
            ),
            'if ($submittedValues !== null && $submittedValues !== \'\' && !is_array($submittedValues)) {',
            sprintf('    $errors->add(t(\'The field "%%s" contains an invalid value.\', %s));', $translatedLabel),
            '} elseif (is_array($submittedValues) && count($submittedValues) !== count($normalizedValues)) {',
            sprintf('    $errors->add(t(\'The field "%%s" contains an invalid option.\', %s));', $translatedLabel),
        ];
        if ($field->required) {
            $lines[] = '} elseif ($normalizedValues === []) {';
            $lines[] = sprintf('    $errors->add(t(\'The field "%%s" is required.\', %s));', $translatedLabel);
        }
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }

    private function renderEntryValidation(
        MultipleChoiceFieldTypeDto $field,
        string $handleLiteral,
        string $optionsMethod,
    ): string {
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $lines = [
            sprintf('$submittedValues = $entry[%s] ?? [];', $handleLiteral),
            sprintf(
                '$normalizedValues = $this->%s($submittedValues, $this->%s());',
                self::NORMALIZER_METHOD,
                $optionsMethod,
            ),
            'if ($submittedValues !== null && $submittedValues !== \'\' && !is_array($submittedValues)) {',
            sprintf(
                '    $errors->add(t(\'The field "%%s" contains an invalid value in entry %%s.\', %s, $entryPosition + 1));',
                $translatedLabel,
            ),
            '} elseif (is_array($submittedValues) && count($submittedValues) !== count($normalizedValues)) {',
            sprintf(
                '    $errors->add(t(\'The field "%%s" contains an invalid option in entry %%s.\', %s, $entryPosition + 1));',
                $translatedLabel,
            ),
        ];
        if ($field->required) {
            $lines[] = '} elseif ($normalizedValues === []) {';
            $lines[] = sprintf(
                '    $errors->add(t(\'The field "%%s" is required in entry %%s.\', %s, $entryPosition + 1));',
                $translatedLabel,
            );
        }
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }

    private function renderNormalizerMethod(): string
    {
        return <<<'PHP'
private function normalizeBlockBuilderMultipleChoiceValues(mixed $value, array $allowedOptions): array
{
    if (!is_array($value)) {
        return [];
    }

    $normalizedValues = [];
    foreach ($value as $selectedValue) {
        if (!is_scalar($selectedValue)) {
            continue;
        }

        $selectedValue = trim((string) $selectedValue);
        if (
            $selectedValue !== ''
            && !str_contains($selectedValue, '|')
            && array_key_exists($selectedValue, $allowedOptions)
        ) {
            $normalizedValues[$selectedValue] = $selectedValue;
        }
    }

    return array_values($normalizedValues);
}
PHP;
    }

    /**
     * @param array{property: string, method: string, local: string, view: string} $optionNames
     */
    private function renderOptionsMethod(MultipleChoiceFieldTypeDto $field, array $optionNames): string
    {
        $initialization = $field->listGenerationMethod === 'custom_code'
            ? $this->renderCustomOptionsInitialization($field, $optionNames['local'])
            : $this->renderBasicOptionsInitialization($field, $optionNames['local']);

        return sprintf(
            'private function %1$s(): array%2$s'
            . '{%2$s'
            . '    if ($this->%3$s !== null) {%2$s'
            . '        return $this->%3$s;%2$s'
            . '    }%2$s%2$s'
            . '%4$s%2$s%2$s'
            . '    return $this->%3$s = $%5$s;%2$s'
            . '}',
            $optionNames['method'],
            PHP_EOL,
            $optionNames['property'],
            $this->indentCode($initialization, 1),
            $optionNames['local'],
        );
    }

    private function renderBasicOptionsInitialization(MultipleChoiceFieldTypeDto $field, string $localVariable): string
    {
        $lines = [sprintf('$%s = [', $localVariable)];
        foreach ($this->parseOptions($field->options ?? '') as $key => $label) {
            $lines[] = sprintf(
                '    %s => t(%s),',
                $this->phpLiteralFormatter->format($key),
                $this->phpLiteralFormatter->format($label),
            );
        }
        $lines[] = '];';

        return implode(PHP_EOL, $lines);
    }

    private function renderCustomOptionsInitialization(MultipleChoiceFieldTypeDto $field, string $localVariable): string
    {
        $customCode = $this->prepareCustomCode((string) $field->customCode);

        return sprintf(
            '$%1$s = [];%2$s%3$s',
            $localVariable,
            $customCode === '' ? '' : PHP_EOL,
            $customCode,
        );
    }

    private function prepareCustomCode(string $code): string
    {
        if (trim($code) === '') {
            return '';
        }

        $code = rtrim(str_replace(["\r\n", "\r"], PHP_EOL, $code), "\r\n");

        return implode(
            PHP_EOL,
            array_map(
                static fn(string $line): string => str_starts_with($line, '        ')
                    ? substr($line, 8)
                    : $line,
                explode(PHP_EOL, $code),
            ),
        );
    }

    /**
     * @return array<int|string, string>
     */
    private function parseOptions(string $options): array
    {
        $parsedOptions = [];
        $position = 0;
        foreach (preg_split('/\r\n|\r|\n/', $options) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $position++;
            $parts = array_map('trim', explode('::', $line, 2));
            $key = count($parts) === 2 ? $parts[0] : (string) $position;
            $label = count($parts) === 2 ? $parts[1] : $parts[0];
            $parsedOptions[$key] = $label;
        }

        return $parsedOptions;
    }

    private function renderFormFragment(
        MultipleChoiceFieldTypeDto $field,
        string $optionsVariable,
        bool $basicField,
    ): string {
        $helpText = $field->helpText !== null && $field->helpText !== ''
            ? sprintf(
                '%s    <div class="form-text"><?= t(%s); ?></div>',
                PHP_EOL,
                $this->phpLiteralFormatter->format($field->helpText),
            )
            : '';
        $type = in_array($field->displayType, ['default_multiselect', 'enhanced_multiselect', 'checkbox_list'], true)
            ? $field->displayType
            : 'default_multiselect';

        return $this->stubRenderer->render(
            sprintf(
                'fragments/multiple_choice/form-%s-%s.php.stub',
                $type,
                $basicField ? 'basic' : 'repeatable',
            ),
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{OPTIONS_VARIABLE}}' => $optionsVariable,
                '{{HELP_TEXT}}' => $helpText,
            ],
        );
    }

    private function renderViewFragment(
        MultipleChoiceFieldTypeDto $field,
        string $optionsVariable,
        bool $basicField,
    ): string {
        return $this->stubRenderer->render(
            $basicField
                ? 'fragments/multiple_choice/view-basic.php.stub'
                : 'fragments/multiple_choice/view-repeatable.php.stub',
            $basicField
                ? [
                    '{{HANDLE}}' => $field->handle,
                    '{{OPTIONS_VARIABLE}}' => $optionsVariable,
                ]
                : [
                    '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                    '{{OPTIONS_VARIABLE}}' => $optionsVariable,
                ],
        );
    }

    /**
     * @return array{property: string, method: string, local: string, view: string}
     */
    private function getOptionNames(MultipleChoiceFieldTypeDto $field, bool $basicField): array
    {
        $prefix = $basicField ? '' : 'entry_';
        $localVariable = $prefix . $field->handle . '_options';
        $pascalCaseHandle = str_replace(' ', '', ucwords(str_replace('_', ' ', $field->handle)));
        $property = $basicField
            ? 'blockBuilder' . $pascalCaseHandle . 'Options'
            : 'blockBuilderEntry' . $pascalCaseHandle . 'Options';

        return [
            'property' => $property,
            'method' => 'get' . ucfirst($property),
            'local' => $localVariable,
            'view' => $localVariable,
        ];
    }

    private function indentCode(string $code, int $levels): string
    {
        $indentation = str_repeat('    ', $levels);

        return implode(
            PHP_EOL,
            array_map(
                static fn(string $line): string => $line === '' ? '' : $indentation . $line,
                explode(PHP_EOL, $code),
            ),
        );
    }
}
