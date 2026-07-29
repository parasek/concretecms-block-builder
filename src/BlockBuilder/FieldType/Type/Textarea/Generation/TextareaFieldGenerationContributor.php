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
        }

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
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        if ($field->required) {
            return sprintf(
                'if (!isset($args[%1$s]) || !is_scalar($args[%1$s]) || trim((string) $args[%1$s]) === \'\') {%2$s    $errors->add(t(\'The field "%%s" is required.\', %3$s));%2$s}',
                $handleLiteral,
                PHP_EOL,
                $translatedLabel,
            );
        }

        return sprintf(
            'if (array_key_exists(%1$s, $args) && $args[%1$s] !== null && !is_scalar($args[%1$s])) {%2$s    $errors->add(t(\'The field "%%s" contains an invalid value.\', %3$s));%2$s}',
            $handleLiteral,
            PHP_EOL,
            $translatedLabel,
        );
    }

    private function renderEntryValidation(TextareaFieldTypeDto $field, string $handleLiteral): string
    {
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        if ($field->required) {
            return sprintf(
                'if (!isset($entry[%1$s]) || !is_scalar($entry[%1$s]) || trim((string) $entry[%1$s]) === \'\') {%2$s    $errors->add(t(\'The field "%%s" is required in entry %%s.\', %3$s, $entryPosition + 1));%2$s}',
                $handleLiteral,
                PHP_EOL,
                $translatedLabel,
            );
        }

        return sprintf(
            'if (array_key_exists(%1$s, $entry) && $entry[%1$s] !== null && !is_scalar($entry[%1$s])) {%2$s    $errors->add(t(\'The field "%%s" contains an invalid value in entry %%s.\', %3$s, $entryPosition + 1));%2$s}',
            $handleLiteral,
            PHP_EOL,
            $translatedLabel,
        );
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
        ];

        if ($basicField) {
            $formAttributes = [
                '            \'data-block-builder-autosize\' => true,',
            ];
            if ($field->maxHeight !== null) {
                $formAttributes[] = sprintf(
                    '            \'style\' => \'max-block-size: %dpx;\',',
                    $field->maxHeight,
                );
            }
            if ($field->required) {
                $formAttributes[] = '            \'required\' => true,';
            }
            $replacements['{{FORM_OPTIONS_ARGUMENT}}'] = $formAttributes === []
                ? ''
                : sprintf(
                    ',%1$s        [%1$s%2$s%1$s        ],',
                    PHP_EOL,
                    implode(PHP_EOL, $formAttributes),
                );
        } else {
            $replacements['{{MAX_HEIGHT_ATTRIBUTE}}'] = $field->maxHeight !== null
                ? sprintf('%s        style="max-block-size: %dpx;"', PHP_EOL, $field->maxHeight)
                : '';
            $replacements['{{REQUIRED_HTML_ATTRIBUTE}}'] = $field->required
                ? PHP_EOL . '        required'
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
