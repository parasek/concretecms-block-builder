<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\SvgIconPicker\Generation;

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
use BlockBuilder\FieldType\Type\SvgIconPicker\SvgIconPickerFieldTypeDto;

final readonly class SvgIconPickerFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string NORMALIZE_METHOD = 'normalizeBlockBuilderSvgIconHandle';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::SvgIconPicker;
    }

    public function contribute(FieldGenerationContext $context, BlockGenerationPlanBuilder $planBuilder): void
    {
        if (!$context->fieldDto instanceof SvgIconPickerFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'SVG Icon Picker field generation requires DTO "%s"; "%s" was provided.',
                SvgIconPickerFieldTypeDto::class,
                $context->fieldDto::class,
            ));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;
        $allowedHandles = array_column($field->icons, 'handle');

        $this->contributeSharedControllerCode($planBuilder);
        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'string',
                size: '50',
                order: $context->position,
            ),
        );

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($field, $allowedHandles, $fragmentKeyPrefix, $context->position, $planBuilder);
        } else {
            $this->contributeRepeatableControllerCode($field, $allowedHandles, $fragmentKeyPrefix, $context->position, $planBuilder);
            $planBuilder->form->addRepeatableDefaultValue($field->handle, '');
        }

        $planBuilder->view->addFieldVariable(
            $context->fieldContext,
            new ViewVariableDocumentation(
                name: $field->handle,
                type: 'string|null',
                description: sprintf('%s selected SVG icon handle', $field->label),
                order: $context->position,
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

    private function contributeSharedControllerCode(BlockGenerationPlanBuilder $planBuilder): void
    {
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::AdditionalMethods->value,
            new CodeFragment(
                key: 'svg_icon_picker.normalize',
                code: <<<'PHP'
private function normalizeBlockBuilderSvgIconHandle(mixed $value, array $allowedHandles): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $handle = trim($value);
    if ($handle === '') {
        return '';
    }

    return in_array($handle, $allowedHandles, true) ? $handle : null;
}
PHP,
            ),
        );
    }

    /**
     * @param string[] $allowedHandles
     */
    private function contributeBasicControllerCode(
        SvgIconPickerFieldTypeDto $field,
        array $allowedHandles,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $allowedHandlesLiteral = $this->phpLiteralFormatter->format($allowedHandles);

        $planBuilder->controller
            ->addProperty(new ControllerProperty(
                name: $field->handle,
                declaration: sprintf('protected ?string $%s = null;', $field->handle),
                order: $position,
            ))
            ->addMethodFragment(
                ControllerMethodSectionEnum::Add->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$this->set(%s, %s);',
                        $handleLiteral,
                        $this->phpLiteralFormatter->format(''),
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$this->set(%1$s, $this->%2$s ?? %3$s);',
                        $handleLiteral,
                        $field->handle,
                        $this->phpLiteralFormatter->format(''),
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$args[%1$s] = $this->%2$s($args[%1$s] ?? %3$s, %4$s) ?? %3$s;',
                        $handleLiteral,
                        self::NORMALIZE_METHOD,
                        $this->phpLiteralFormatter->format(''),
                        $allowedHandlesLiteral,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, $allowedHandlesLiteral, '$args', false),
                    order: $position,
                ),
            );
    }

    /**
     * @param string[] $allowedHandles
     */
    private function contributeRepeatableControllerCode(
        SvgIconPickerFieldTypeDto $field,
        array $allowedHandles,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $allowedHandlesLiteral = $this->phpLiteralFormatter->format($allowedHandles);

        $planBuilder->controller
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$data[%1$s] = $this->%2$s($entry[%1$s] ?? %3$s, %4$s) ?? %3$s;',
                        $handleLiteral,
                        self::NORMALIZE_METHOD,
                        $this->phpLiteralFormatter->format(''),
                        $allowedHandlesLiteral,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, $allowedHandlesLiteral, '$entry', true),
                    order: $position,
                ),
            );
    }

    private function renderValidationCode(
        SvgIconPickerFieldTypeDto $field,
        string $allowedHandlesLiteral,
        string $sourceVariable,
        bool $repeatable,
    ): string {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $invalidError = $repeatable
            ? sprintf('$errors->add(t(\'The field "%%s" contains an invalid SVG icon in entry %%s.\', %s, $entryPosition + 1));', $translatedLabel)
            : sprintf('$errors->add(t(\'The field "%%s" contains an invalid SVG icon.\', %s));', $translatedLabel);
        $requiredError = $repeatable
            ? sprintf('$errors->add(t(\'The field "%%s" is required in entry %%s.\', %s, $entryPosition + 1));', $translatedLabel)
            : sprintf('$errors->add(t(\'The field "%%s" is required.\', %s));', $translatedLabel);

        $lines = [
            sprintf(
                '$normalizedSvgIconHandle = $this->%s(%s[%s] ?? \'\', %s);',
                self::NORMALIZE_METHOD,
                $sourceVariable,
                $handleLiteral,
                $allowedHandlesLiteral,
            ),
            'if ($normalizedSvgIconHandle === null) {',
            '    ' . $invalidError,
        ];
        if ($field->required) {
            $lines[] = '} elseif ($normalizedSvgIconHandle === \'\') {';
            $lines[] = '    ' . $requiredError;
        }
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }

    private function renderFormFragment(SvgIconPickerFieldTypeDto $field, bool $basicField): string
    {
        $replacements = [
            '{{HANDLE}}' => $field->handle,
            '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
            '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
            '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
            '{{OPTIONS}}' => $this->renderOptions($field, $basicField),
            '{{SVG_VARIABLE}}' => '_blockBuilderSvgIconPreview_' . $field->handle,
            '{{PREVIEW_MAP}}' => $this->renderPreviewMap($field),
            '{{HELP_TEXT}}' => $field->helpText === null || $field->helpText === ''
                ? ''
                : PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>',
        ];
        return $this->stubRenderer->render(
            'fragments/svg_icon_picker/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $replacements,
        );
    }

    private function renderOptions(SvgIconPickerFieldTypeDto $field, bool $basicField): string
    {
        $selectedExpression = $basicField
            ? sprintf('($%s ?? \'\')', $field->handle)
            : sprintf('($entry[%s] ?? \'\')', $this->phpLiteralFormatter->format($field->handle));
        $options = [];
        foreach ($field->icons as $icon) {
            $handleLiteral = $this->phpLiteralFormatter->format($icon['handle']);
            $previewSource = $this->getPreviewSource($icon['svg']);
            $options[] = sprintf(
                '            <option%5$s'
                . '                value="<?= h(%1$s); ?>"%5$s'
                . '                data-svg-icon-preview="<?= h(%2$s); ?>"%5$s'
                . '                <?= %3$s === %1$s ? \'selected\' : \'\'; ?>%5$s'
                . '            ><?= t(%4$s); ?></option>',
                $handleLiteral,
                $this->phpLiteralFormatter->format($previewSource),
                $selectedExpression,
                $this->phpLiteralFormatter->format($icon['name']),
                PHP_EOL,
            );
        }

        return implode(PHP_EOL, $options);
    }

    private function renderViewFragment(SvgIconPickerFieldTypeDto $field, bool $basicField): string
    {
        $map = [];
        foreach ($field->icons as $icon) {
            $previewSource = $this->getPreviewSource($icon['svg']);
            $map[] = sprintf(
                '    %s => [\'source\' => %s, \'name\' => t(%s)],',
                $this->phpLiteralFormatter->format($icon['handle']),
                $this->phpLiteralFormatter->format($previewSource),
                $this->phpLiteralFormatter->format($icon['name']),
            );
        }

        $replacements = [
            '{{SVG_VARIABLE}}' => '_blockBuilderSvgIcon_' . $field->handle,
            '{{SVG_MAP}}' => implode(PHP_EOL, $map),
        ];
        $replacements[$basicField ? '{{HANDLE}}' : '{{HANDLE_LITERAL}}'] = $basicField
            ? $field->handle
            : $this->phpLiteralFormatter->format($field->handle);

        return $this->stubRenderer->render(
            'fragments/svg_icon_picker/view-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $replacements,
        );
    }

    private function renderPreviewMap(SvgIconPickerFieldTypeDto $field): string
    {
        $map = [];
        foreach ($field->icons as $icon) {
            $map[] = sprintf(
                '    %s => %s,',
                $this->phpLiteralFormatter->format($icon['handle']),
                $this->phpLiteralFormatter->format($this->getPreviewSource($icon['svg'])),
            );
        }

        return implode(PHP_EOL, $map);
    }

    private function getPreviewSource(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
