<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\SingleChoice\Generation;

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
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\SingleChoice\SingleChoiceFieldTypeDto;

readonly class SingleChoiceFieldGenerationContributor implements FieldGenerationContributorInterface
{
    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::SingleChoice;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof SingleChoiceFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Single choice field generation requires DTO "%s"; "%s" was provided.',
                SingleChoiceFieldTypeDto::class,
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
                type: 'string',
                size: '255',
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
            );

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode(
                $field,
                $fragmentKeyPrefix,
                $optionNames['method'],
                $context->position,
                $planBuilder,
            );
        } else {
            $this->contributeRepeatableControllerCode(
                $field,
                $fragmentKeyPrefix,
                $optionNames['method'],
                $context->position,
                $planBuilder,
            );
            $planBuilder->form
                ->addRepeatableCapturedVariable($optionNames['view'])
                ->addRepeatableDefaultValue($field->handle, $field->defaultValue ?? '');
        }

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
        SingleChoiceFieldTypeDto $field,
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
                    '$args[%1$s] = isset($args[%1$s]) && is_scalar($args[%1$s])%2$s    ? trim((string) $args[%1$s])%2$s    : \'\';',
                    $handleLiteral,
                    PHP_EOL,
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
        SingleChoiceFieldTypeDto $field,
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
                    '$data[%1$s] = isset($entry[%1$s]) && is_scalar($entry[%1$s])%2$s    ? trim((string) $entry[%1$s])%2$s    : \'\';',
                    $handleLiteral,
                    PHP_EOL,
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
        SingleChoiceFieldTypeDto $field,
        string $handleLiteral,
        string $optionsMethod,
    ): string {
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $invalidTypeCondition = sprintf(
            'array_key_exists(%1$s, $args) && $args[%1$s] !== null && !is_scalar($args[%1$s])',
            $handleLiteral,
        );
        $emptyCondition = sprintf(
            '!isset($args[%1$s]) || !is_scalar($args[%1$s]) || trim((string) $args[%1$s]) === \'\'',
            $handleLiteral,
        );
        $invalidOptionCondition = sprintf(
            'isset($args[%1$s]) && is_scalar($args[%1$s]) && trim((string) $args[%1$s]) !== \'\''
            . ' && !array_key_exists(trim((string) $args[%1$s]), $this->%2$s())',
            $handleLiteral,
            $optionsMethod,
        );

        if ($field->required) {
            return sprintf(
                'if (%1$s) {%4$s    $errors->add(t(\'The field "%%s" is required.\', %3$s));%4$s}'
                . ' elseif (%2$s) {%4$s    $errors->add(t(\'The field "%%s" contains an invalid option.\', %3$s));%4$s}',
                $emptyCondition,
                $invalidOptionCondition,
                $translatedLabel,
                PHP_EOL,
            );
        }

        return sprintf(
            'if (%1$s) {%4$s    $errors->add(t(\'The field "%%s" contains an invalid value.\', %3$s));%4$s}'
            . ' elseif (%2$s) {%4$s    $errors->add(t(\'The field "%%s" contains an invalid option.\', %3$s));%4$s}',
            $invalidTypeCondition,
            $invalidOptionCondition,
            $translatedLabel,
            PHP_EOL,
        );
    }

    private function renderEntryValidation(
        SingleChoiceFieldTypeDto $field,
        string $handleLiteral,
        string $optionsMethod,
    ): string {
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $invalidTypeCondition = sprintf(
            'array_key_exists(%1$s, $entry) && $entry[%1$s] !== null && !is_scalar($entry[%1$s])',
            $handleLiteral,
        );
        $emptyCondition = sprintf(
            '!isset($entry[%1$s]) || !is_scalar($entry[%1$s]) || trim((string) $entry[%1$s]) === \'\'',
            $handleLiteral,
        );
        $invalidOptionCondition = sprintf(
            'isset($entry[%1$s]) && is_scalar($entry[%1$s]) && trim((string) $entry[%1$s]) !== \'\''
            . ' && !array_key_exists(trim((string) $entry[%1$s]), $this->%2$s())',
            $handleLiteral,
            $optionsMethod,
        );

        if ($field->required) {
            return sprintf(
                'if (%1$s) {%4$s    $errors->add(t(\'The field "%%s" is required in entry %%s.\', %3$s, $entryPosition + 1));%4$s}'
                . ' elseif (%2$s) {%4$s    $errors->add(t(\'The field "%%s" contains an invalid option in entry %%s.\', %3$s, $entryPosition + 1));%4$s}',
                $emptyCondition,
                $invalidOptionCondition,
                $translatedLabel,
                PHP_EOL,
            );
        }

        return sprintf(
            'if (%1$s) {%4$s    $errors->add(t(\'The field "%%s" contains an invalid value in entry %%s.\', %3$s, $entryPosition + 1));%4$s}'
            . ' elseif (%2$s) {%4$s    $errors->add(t(\'The field "%%s" contains an invalid option in entry %%s.\', %3$s, $entryPosition + 1));%4$s}',
            $invalidTypeCondition,
            $invalidOptionCondition,
            $translatedLabel,
            PHP_EOL,
        );
    }

    /**
     * @param array{property: string, method: string, local: string, view: string} $optionNames
     */
    private function renderOptionsMethod(SingleChoiceFieldTypeDto $field, array $optionNames): string
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

    private function renderBasicOptionsInitialization(SingleChoiceFieldTypeDto $field, string $localVariable): string
    {
        $lines = [sprintf('$%s = [', $localVariable)];
        if ($field->addEmptyOption && $field->displayType !== 'radio_list') {
            $lines[] = '    \'\' => \'----\',';
        }
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

    private function renderCustomOptionsInitialization(SingleChoiceFieldTypeDto $field, string $localVariable): string
    {
        $customCode = trim((string) $field->customCode);

        return sprintf(
            '$%1$s = [];%2$s%3$s',
            $localVariable,
            $customCode === '' ? '' : PHP_EOL,
            $customCode,
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
        SingleChoiceFieldTypeDto $field,
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
        $type = in_array($field->displayType, ['default_select', 'enhanced_select', 'radio_list'], true)
            ? $field->displayType
            : 'default_select';

        return $this->stubRenderer->render(
            sprintf(
                'fragments/single_choice/form-%s-%s.php.stub',
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
        SingleChoiceFieldTypeDto $field,
        string $optionsVariable,
        bool $basicField,
    ): string {
        return $this->stubRenderer->render(
            $basicField
                ? 'fragments/single_choice/view-basic.php.stub'
                : 'fragments/single_choice/view-repeatable.php.stub',
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
    private function getOptionNames(SingleChoiceFieldTypeDto $field, bool $basicField): array
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
