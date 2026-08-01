<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\ColorPicker\Generation;

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
use BlockBuilder\FieldType\Type\ColorPicker\ColorPickerFieldTypeDto;

final readonly class ColorPickerFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string NORMALIZE_METHOD = 'normalizeBlockBuilderColor';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::ColorPicker;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof ColorPickerFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Color Picker field generation requires DTO "%s"; "%s" was provided.',
                ColorPickerFieldTypeDto::class,
                $context->fieldDto::class,
            ));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;

        $this->contributeSharedCode($planBuilder);
        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'string',
                size: '255',
                order: $context->position,
            ),
        );
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

    private function contributeSharedCode(BlockGenerationPlanBuilder $planBuilder): void
    {
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::AdditionalMethods->value,
            new CodeFragment(
                key: 'color_picker.normalize',
                code: <<<'PHP'
private function normalizeBlockBuilderColor(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $color = trim($value);
    if ($color === '') {
        return '';
    }
    if (strlen($color) > 255) {
        return null;
    }

    // Accept three-, four-, six-, or eight-digit hexadecimal colors.
    if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/iD', $color) === 1) {
        return $color;
    }

    // Accept RGB colors whose integer channels range from 0 to 255.
    if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/iD', $color, $matches) === 1) {
        return (int) $matches[1] <= 255 && (int) $matches[2] <= 255 && (int) $matches[3] <= 255
            ? $color
            : null;
    }

    // Accept RGBA colors with integer channels and alpha ranging from 0 to 1.
    if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*((?:0|1)(?:\.\d+)?|\.\d+)\s*\)$/iD', $color, $matches) === 1) {
        return (int) $matches[1] <= 255
            && (int) $matches[2] <= 255
            && (int) $matches[3] <= 255
            && (float) $matches[4] >= 0.0
            && (float) $matches[4] <= 1.0
                ? $color
                : null;
    }

    return null;
}
PHP,
            ),
        );
    }

    private function contributeBasicControllerCode(
        ColorPickerFieldTypeDto $field,
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
                code: sprintf('$this->set(%1$s, $this->%2$s ?? \'\');', $handleLiteral, $field->handle),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::SaveBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$args[%1$s] = $this->%2$s($args[%1$s] ?? \'\') ?? \'\';',
                    $handleLiteral,
                    self::NORMALIZE_METHOD,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::ValidateBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderValidationCode($field, '$args', false),
                order: $position,
            ),
        );
    }

    private function contributeRepeatableControllerCode(
        ColorPickerFieldTypeDto $field,
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
                    '$data[%1$s] = $this->%2$s($entry[%1$s] ?? \'\') ?? \'\';',
                    $handleLiteral,
                    self::NORMALIZE_METHOD,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::ValidateEntryFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderValidationCode($field, '$entry', true),
                order: $position,
            ),
        );
    }

    private function renderValidationCode(
        ColorPickerFieldTypeDto $field,
        string $sourceVariable,
        bool $repeatable,
    ): string {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $invalidError = $repeatable
            ? sprintf('$errors->add(t(\'The field "%%s" contains an invalid color in entry %%s.\', %s, $entryPosition + 1));', $translatedLabel)
            : sprintf('$errors->add(t(\'The field "%%s" contains an invalid color.\', %s));', $translatedLabel);
        $requiredError = $repeatable
            ? sprintf('$errors->add(t(\'The field "%%s" is required in entry %%s.\', %s, $entryPosition + 1));', $translatedLabel)
            : sprintf('$errors->add(t(\'The field "%%s" is required.\', %s));', $translatedLabel);

        $lines = [
            sprintf('$normalizedColor = $this->%s(%s[%s] ?? \'\');', self::NORMALIZE_METHOD, $sourceVariable, $handleLiteral),
            'if ($normalizedColor === null) {',
            '    ' . $invalidError,
        ];
        if ($field->required) {
            $lines[] = '} elseif ($normalizedColor === \'\') {';
            $lines[] = '    ' . $requiredError;
        }
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }

    private function renderFormFragment(ColorPickerFieldTypeDto $field, bool $basicField): string
    {
        $replacements = [
            '{{HANDLE}}' => $field->handle,
            '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
            '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
            '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
            '{{HELP_TEXT}}' => $field->helpText === null || $field->helpText === ''
                ? ''
                : PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>',
        ];

        return $this->stubRenderer->render(
            'fragments/color_picker/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $replacements,
        );
    }

    private function renderViewFragment(ColorPickerFieldTypeDto $field, bool $basicField): string
    {
        return $this->stubRenderer->render(
            'fragments/color_picker/view-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $basicField
                ? ['{{HANDLE}}' => $field->handle]
                : ['{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle)],
        );
    }
}
