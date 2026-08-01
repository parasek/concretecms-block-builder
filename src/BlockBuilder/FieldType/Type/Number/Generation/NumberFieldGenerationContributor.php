<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Number\Generation;

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
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\Number\NumberFieldType;
use BlockBuilder\FieldType\Type\Number\NumberFieldTypeDto;

readonly class NumberFieldGenerationContributor implements FieldGenerationContributorInterface
{
    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::Number;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof NumberFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Number field generation requires DTO "%s"; "%s" was provided.',
                NumberFieldTypeDto::class,
                $context->fieldDto::class,
            ));
        }

        $field = $context->fieldDto;
        $this->validateGenerationOptions($field);
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;

        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'decimal',
                size: $field->size,
                order: $context->position,
            ),
        );
        $planBuilder->controller->addSearchableField($context->fieldContext, $field->handle);

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
        } else {
            $this->contributeRepeatableControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
            $planBuilder->form->addRepeatableDefaultValue($field->handle, $field->defaultValue);
        }

        $planBuilder->view->addFieldVariable(
            $context->fieldContext,
            new ViewVariableDocumentation(
                name: $field->handle,
                type: 'int|float|string|null',
                description: $field->label,
                order: $context->position * 10,
            ),
        );

        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($field, $context->isBasicField()),
                order: $context->position,
            ),
        );
        $planBuilder->view->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderViewFragment($field, $context->isBasicField()),
                order: $context->position,
            ),
        );
    }

    private function contributeBasicControllerCode(
        NumberFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $planBuilder->controller->addProperty(new ControllerProperty(
            name: $field->handle,
            declaration: sprintf('protected int|float|string|null $%s = null;', $field->handle),
            order: $position,
        ));
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::Add->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf('$this->set(%s, %s);', $handleLiteral, $this->phpLiteralFormatter->format($field->defaultValue)),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::AddEdit->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf('$this->set(%s, $this->%s);', $handleLiteral, $field->handle),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::SaveBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderSaveCode('$args', '$args', $handleLiteral),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::ValidateBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderValidationCode(
                    field: $field,
                    sourceExpression: sprintf('$args[%s] ?? null', $handleLiteral),
                    entryField: false,
                ),
                order: $position,
            ),
        );
    }

    private function contributeRepeatableControllerCode(
        NumberFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::SaveEntryFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderSaveCode('$data', '$entry', $handleLiteral),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::ValidateEntryFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderValidationCode(
                    field: $field,
                    sourceExpression: sprintf('$entry[%s] ?? null', $handleLiteral),
                    entryField: true,
                ),
                order: $position,
            ),
        );
    }

    private function renderSaveCode(
        string $targetVariable,
        string $sourceVariable,
        string $handleLiteral,
    ): string {
        return sprintf(
            '%1$s[%3$s] = isset(%2$s[%3$s]) && is_scalar(%2$s[%3$s]) && is_numeric(trim((string) %2$s[%3$s]))%4$s    ? trim((string) %2$s[%3$s])%4$s    : null;',
            $targetVariable,
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
        );
    }

    private function renderValidationCode(
        NumberFieldTypeDto $field,
        string $sourceExpression,
        bool $entryField,
    ): string {
        $labelExpression = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $minimumLiteral = $this->phpLiteralFormatter->format($field->minimum);
        $maximumLiteral = $this->phpLiteralFormatter->format($field->maximum);
        $stepLiteral = $this->phpLiteralFormatter->format($field->step);

        $invalidNumberError = $this->renderValidationError(
            $entryField,
            'The field "%s" must contain a number.',
            'The field "%s" in entry %s must contain a number.',
            $labelExpression,
        );
        $minimumError = $this->renderValidationError(
            $entryField,
            'The field "%s" must be at least %s.',
            'The field "%s" in entry %s must be at least %s.',
            $labelExpression,
            $minimumLiteral,
        );
        $maximumError = $this->renderValidationError(
            $entryField,
            'The field "%s" must be at most %s.',
            'The field "%s" in entry %s must be at most %s.',
            $labelExpression,
            $maximumLiteral,
        );
        $stepError = $this->renderValidationError(
            $entryField,
            'The field "%s" must use increments of %s.',
            'The field "%s" in entry %s must use increments of %s.',
            $labelExpression,
            $stepLiteral,
        );

        $valueValidation = strtr(
            <<<'PHP'
if (!is_string($numberValue) || preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/D', $numberValue) !== 1) {
    {{INVALID_NUMBER_ERROR}}
} else {
    $numericValue = (float) $numberValue;
    if (!is_finite($numericValue)) {
        {{INVALID_NUMBER_ERROR}}
    } elseif ($numericValue < (float) {{MINIMUM}}) {
        {{MINIMUM_ERROR}}
    } elseif ($numericValue > (float) {{MAXIMUM}}) {
        {{MAXIMUM_ERROR}}
    } else {
        $numberStepPosition = ($numericValue - (float) {{MINIMUM}}) / (float) {{STEP}};
        if (
            !is_finite($numberStepPosition)
            || abs($numberStepPosition - round($numberStepPosition)) > 1.0E-9
        ) {
            {{STEP_ERROR}}
        }
    }
}
PHP,
            [
                '{{INVALID_NUMBER_ERROR}}' => $invalidNumberError,
                '{{MINIMUM}}' => $minimumLiteral,
                '{{MINIMUM_ERROR}}' => $minimumError,
                '{{MAXIMUM}}' => $maximumLiteral,
                '{{MAXIMUM_ERROR}}' => $maximumError,
                '{{STEP}}' => $stepLiteral,
                '{{STEP_ERROR}}' => $stepError,
            ],
        );

        $lines = [
            sprintf('$numberValue = %s;', $sourceExpression),
            'if (is_scalar($numberValue)) {',
            '    $numberValue = trim((string) $numberValue);',
            '}',
        ];
        if ($field->required) {
            $requiredError = $this->renderValidationError(
                $entryField,
                'The field "%s" is required.',
                'The field "%s" is required in entry %s.',
                $labelExpression,
            );
            $lines[] = 'if ($numberValue === null || $numberValue === \'\') {';
            $lines[] = '    ' . $requiredError;
            $lines[] = '} else {';
            $lines[] = $this->indentCode($valueValidation);
            $lines[] = '}';
        } else {
            $lines[] = 'if ($numberValue !== null && $numberValue !== \'\') {';
            $lines[] = $this->indentCode($valueValidation);
            $lines[] = '}';
        }

        return implode(PHP_EOL, $lines);
    }

    private function renderValidationError(
        bool $entryField,
        string $basicMessage,
        string $entryMessage,
        string $labelExpression,
        ?string $limitLiteral = null,
    ): string {
        if ($entryField) {
            $arguments = [$labelExpression, '$entryPosition + 1'];
            if ($limitLiteral !== null) {
                $arguments[] = $limitLiteral;
            }

            return sprintf(
                '$errors->add(t(%s, %s));',
                $this->phpLiteralFormatter->format($entryMessage),
                implode(', ', $arguments),
            );
        }

        $arguments = [$labelExpression];
        if ($limitLiteral !== null) {
            $arguments[] = $limitLiteral;
        }

        return sprintf(
            '$errors->add(t(%s, %s));',
            $this->phpLiteralFormatter->format($basicMessage),
            implode(', ', $arguments),
        );
    }

    private function renderFormFragment(NumberFieldTypeDto $field, bool $basicField): string
    {
        $helpText = $field->helpText !== null && $field->helpText !== ''
            ? sprintf(
                '%s    <div class="form-text"><?= t(%s); ?></div>',
                PHP_EOL,
                $this->phpLiteralFormatter->format($field->helpText),
            )
            : '';

        $replacements = [
            '{{HANDLE}}' => $field->handle,
            '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
            '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
            '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
            '{{HELP_TEXT}}' => $helpText,
            '{{PREFIX}}' => $field->prefix === ''
                ? ''
                : '        <span class="input-group-text"><?= h(' . $this->phpLiteralFormatter->format($field->prefix) . '); ?></span>' . PHP_EOL,
            '{{SUFFIX}}' => $field->suffix === ''
                ? ''
                : PHP_EOL . '        <span class="input-group-text"><?= h(' . $this->phpLiteralFormatter->format($field->suffix) . '); ?></span>',
        ];
        if ($basicField) {
            $replacements['{{MINIMUM_LITERAL}}'] = $this->phpLiteralFormatter->format($field->minimum);
            $replacements['{{MAXIMUM_LITERAL}}'] = $this->phpLiteralFormatter->format($field->maximum);
            $replacements['{{STEP_LITERAL}}'] = $this->phpLiteralFormatter->format($field->step);
        } else {
            $replacements['{{MINIMUM_VALUE}}'] = (string) $field->minimum;
            $replacements['{{MAXIMUM_VALUE}}'] = (string) $field->maximum;
            $replacements['{{STEP_VALUE}}'] = (string) $field->step;
        }

        return $this->stubRenderer->render(
            $basicField ? 'fragments/number/form-basic.php.stub' : 'fragments/number/form-repeatable.php.stub',
            $replacements,
        );
    }

    private function renderViewFragment(NumberFieldTypeDto $field, bool $basicField): string
    {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        if ($basicField) {
            $displayCondition = $field->displayZeroValue
                ? sprintf('isset($%1$s) && $%1$s !== \'\'', $field->handle)
                : sprintf('isset($%1$s) && ((float) $%1$s !== 0.0)', $field->handle);
        } else {
            $displayCondition = $field->displayZeroValue
                ? sprintf('isset($entry[%1$s]) && $entry[%1$s] !== \'\'', $handleLiteral)
                : sprintf(
                    'isset($entry[%1$s]) && ((float) $entry[%1$s] !== 0.0)',
                    $handleLiteral,
                );
        }

        return $this->stubRenderer->render(
            $basicField ? 'fragments/number/view-basic.php.stub' : 'fragments/number/view-repeatable.php.stub',
            array_merge(
                $basicField
                    ? ['{{HANDLE}}' => $field->handle]
                    : ['{{HANDLE_LITERAL}}' => $handleLiteral],
                [
                    '{{DISPLAY_CONDITION}}' => $displayCondition,
                    '{{DISPLAYED_DECIMALS}}' => (string) $field->displayedDecimals,
                    '{{DECIMAL_SEPARATOR_LITERAL}}' => $this->phpLiteralFormatter->format(
                        $field->displayedDecimalSeparator,
                    ),
                    '{{THOUSANDS_SEPARATOR_LITERAL}}' => $this->phpLiteralFormatter->format(
                        $field->displayedThousandsSeparator,
                    ),
                ],
            ),
        );
    }

    private function validateGenerationOptions(NumberFieldTypeDto $field): void
    {
        if (
            $field->size === null
            || preg_match('/^[1-9]\d*\.\d+$/', $field->size) !== 1
        ) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Number field "%s" requires a decimal database size such as "10.2".',
                $field->handle,
            ));
        }
        if (
            $field->step === null
            || !is_numeric($field->step)
            || (float) $field->step <= 0
        ) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Number field "%s" requires a step greater than zero.',
                $field->handle,
            ));
        }
        if (
            $field->minimum === null
            || $field->maximum === null
            || !is_numeric($field->minimum)
            || !is_numeric($field->maximum)
            || (float) $field->minimum > (float) $field->maximum
        ) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Number field "%s" requires a valid minimum that is not greater than its maximum.',
                $field->handle,
            ));
        }
        if (
            $field->displayedDecimals < 0
            || $field->displayedDecimals > NumberFieldType::MAXIMUM_DISPLAYED_DECIMALS
        ) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Number field "%s" requires between 0 and %d displayed decimals.',
                $field->handle,
                NumberFieldType::MAXIMUM_DISPLAYED_DECIMALS,
            ));
        }
        if (
            $field->displayedDecimalSeparator === null
            || $field->displayedDecimalSeparator === ''
            || $field->displayedThousandsSeparator === null
        ) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Number field "%s" requires valid display separators.',
                $field->handle,
            ));
        }
    }

    private function indentCode(string $code): string
    {
        return implode(
            PHP_EOL,
            array_map(
                static fn(string $line): string => $line === '' ? '' : '    ' . $line,
                explode(PHP_EOL, $code),
            ),
        );
    }
}
