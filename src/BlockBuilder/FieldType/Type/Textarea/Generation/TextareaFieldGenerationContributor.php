<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Textarea\Generation;

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
use BlockBuilder\FieldType\Type\Textarea\TextareaFieldTypeDto;

readonly class TextareaFieldGenerationContributor implements FieldGenerationContributorInterface
{
    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::Textarea;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof TextareaFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Textarea field generation requires DTO "%s"; "%s" was provided.',
                TextareaFieldTypeDto::class,
                $context->fieldDto::class,
            ));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;

        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'text',
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
                type: 'string|null',
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
        TextareaFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $planBuilder->controller->addProperty(new ControllerProperty(
            name: $field->handle,
            declaration: sprintf('protected ?string $%s = null;', $field->handle),
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
                code: $this->renderBasicValidation($field, $handleLiteral),
                order: $position,
            ),
        );
    }

    private function contributeRepeatableControllerCode(
        TextareaFieldTypeDto $field,
        string $fragmentKeyPrefix,
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
                code: $this->renderEntryValidation($field, $handleLiteral),
                order: $position,
            ),
        );
    }

    private function renderBasicValidation(TextareaFieldTypeDto $field, string $handleLiteral): string
    {
        return $this->renderLengthValidation($field, '$args', $handleLiteral, false);
    }

    private function renderEntryValidation(TextareaFieldTypeDto $field, string $handleLiteral): string
    {
        return $this->renderLengthValidation($field, '$entry', $handleLiteral, true);
    }

    private function renderLengthValidation(
        TextareaFieldTypeDto $field,
        string $sourceVariable,
        string $handleLiteral,
        bool $repeatable,
    ): string {
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $entryArguments = $repeatable ? ', $entryPosition + 1' : '';
        $entryText = $repeatable ? ' in entry %s' : '';
        $lines = [
            sprintf('$textareaValue = %s[%s] ?? null;', $sourceVariable, $handleLiteral),
            'if ($textareaValue !== null && !is_scalar($textareaValue)) {',
            sprintf('    $errors->add(t(%s, %s%s));', $this->phpLiteralFormatter->format('The field "%s" contains an invalid value' . $entryText . '.'), $label, $entryArguments),
            '} else {',
            '    $textareaValue = trim((string) ($textareaValue ?? \'\'));',
            '    $textareaLength = mb_strlen($textareaValue);',
        ];
        if ($field->required) {
            $lines[] = '    if ($textareaValue === \'\') {';
            $lines[] = sprintf('        $errors->add(t(%s, %s%s));', $this->phpLiteralFormatter->format('The field "%s" is required' . $entryText . '.'), $label, $entryArguments);
            $lines[] = '    }';
        }
        if ($field->minimumLength !== null && $field->minimumLength > 0) {
            $lines[] = sprintf('    %sif ($textareaValue !== \'\' && $textareaLength < %d) {', $field->required ? 'else' : '', $field->minimumLength);
            $lines[] = sprintf('        $errors->add(t(%s, %s%s, %d));', $this->phpLiteralFormatter->format('The field "%s"' . $entryText . ' must contain at least %s characters.'), $label, $entryArguments, $field->minimumLength);
            $lines[] = '    }';
        }
        if ($field->maximumLength !== null) {
            $hasPreviousCondition = $field->required || ($field->minimumLength !== null && $field->minimumLength > 0);
            $lines[] = sprintf('    %sif ($textareaLength > %d) {', $hasPreviousCondition ? 'else' : '', $field->maximumLength);
            $lines[] = sprintf('        $errors->add(t(%s, %s%s, %d));', $this->phpLiteralFormatter->format('The field "%s"' . $entryText . ' must contain at most %s characters.'), $label, $entryArguments, $field->maximumLength);
            $lines[] = '    }';
        }
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }

    private function renderFormFragment(TextareaFieldTypeDto $field, bool $basicField): string
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
            '{{COUNTER_MAXIMUM}}' => $field->maximumLength === null
                ? ''
                : '/<span>' . $field->maximumLength . '</span>',
        ];

        $heightStyles = [];
        if ($field->minHeight !== null) {
            $heightStyles[] = sprintf('min-block-size: %dpx;', $field->minHeight);
        }
        if ($field->maxHeight !== null) {
            $heightStyles[] = sprintf('max-block-size: %dpx;', $field->maxHeight);
        }

        if ($basicField) {
            $formAttributes = [
                '            \'data-block-builder-autosize\' => true,',
                '            \'data-block-builder-character-count\' => true,',
                '            \'data-character-count-maximum\' => ' . $this->phpLiteralFormatter->format($field->maximumLength === null ? '' : (string) $field->maximumLength) . ',',
            ];
            if ($field->placeholder !== '') {
                $formAttributes[] = '            \'placeholder\' => t(' . $this->phpLiteralFormatter->format($field->placeholder) . '),';
            }
            if ($field->maximumLength !== null) {
                $formAttributes[] = sprintf('            \'maxlength\' => %d,', $field->maximumLength);
            }
            if ($heightStyles !== []) {
                $formAttributes[] = sprintf(
                    '            \'style\' => %s,',
                    $this->phpLiteralFormatter->format(implode(' ', $heightStyles)),
                );
            }
            $replacements['{{FORM_OPTIONS_ARGUMENT}}'] = $formAttributes === []
                ? ''
                : sprintf(
                    ',%1$s        [%1$s%2$s%1$s        ],',
                    PHP_EOL,
                    implode(PHP_EOL, $formAttributes),
                );
        } else {
            $replacements['{{PLACEHOLDER_ATTRIBUTE}}'] = $field->placeholder === ''
                ? ''
                : PHP_EOL . '        placeholder="<?= t(' . $this->phpLiteralFormatter->format($field->placeholder) . '); ?>"';
            $replacements['{{MAXIMUM_LENGTH_ATTRIBUTE}}'] = $field->maximumLength === null
                ? ''
                : PHP_EOL . '        maxlength="' . $field->maximumLength . '"';
            $replacements['{{MAXIMUM_LENGTH_VALUE}}'] = $field->maximumLength === null
                ? ''
                : (string) $field->maximumLength;
            $replacements['{{HEIGHT_STYLE_ATTRIBUTE}}'] = $heightStyles !== []
                ? sprintf('%s        style="%s"', PHP_EOL, implode(' ', $heightStyles))
                : '';
            $replacements['{{TITLE_SOURCE_ATTRIBUTE}}'] = $field->titleSource
                ? PHP_EOL . '        data-entry-title-source'
                : '';
        }

        return $this->stubRenderer->render(
            $basicField ? 'fragments/textarea/form-basic.php.stub' : 'fragments/textarea/form-repeatable.php.stub',
            $replacements,
        );
    }

    private function renderViewFragment(TextareaFieldTypeDto $field, bool $basicField): string
    {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        if ($basicField) {
            $displayCondition = $field->displayZeroValue
                ? sprintf('isset($%1$s) && $%1$s !== \'\'', $field->handle)
                : sprintf('!empty($%s)', $field->handle);
        } else {
            $displayCondition = $field->displayZeroValue
                ? sprintf('isset($entry[%1$s]) && $entry[%1$s] !== \'\'', $handleLiteral)
                : sprintf('!empty($entry[%s])', $handleLiteral);
        }

        return $this->stubRenderer->render(
            $basicField ? 'fragments/textarea/view-basic.php.stub' : 'fragments/textarea/view-repeatable.php.stub',
            $basicField
                ? [
                    '{{HANDLE}}' => $field->handle,
                    '{{DISPLAY_CONDITION}}' => $displayCondition,
                ]
                : [
                    '{{HANDLE_LITERAL}}' => $handleLiteral,
                    '{{DISPLAY_CONDITION}}' => $displayCondition,
                ],
        );
    }
}
