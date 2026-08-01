<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\IconPicker\Generation;

use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContext;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorInterface;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerAsset;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerProperty;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\IconPicker\IconPickerFieldTypeDto;

final readonly class IconPickerFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string NORMALIZE_METHOD = 'normalizeBlockBuilderIcon';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::IconPicker;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof IconPickerFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Icon Picker field generation requires DTO "%s"; "%s" was provided.',
                IconPickerFieldTypeDto::class,
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
            $planBuilder->form->addRepeatableDefaultValue($field->handle, '');
        }

        $planBuilder->view->addFieldVariable(
            $context->fieldContext,
            new ViewVariableDocumentation(
                name: $field->handle,
                type: 'string|null',
                description: sprintf('%s icon classes', $field->label),
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
        $planBuilder->controller
            ->addAsset(new ControllerAsset('css', 'font-awesome'))
            ->addMethodFragment(
                ControllerMethodSectionEnum::RegisterViewAssets->value,
                new CodeFragment(
                    key: 'icon_picker.font_awesome',
                    code: '$this->requireAsset(\'css\', \'font-awesome\');',
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'icon_picker.normalize',
                    code: <<<'PHP'
private function normalizeBlockBuilderIcon(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $icon = trim($value);
    if ($icon === '') {
        return '';
    }
    if (
        strlen($icon) > 255
        || preg_match('/^[A-Za-z][A-Za-z0-9_-]*(?:\s+[A-Za-z][A-Za-z0-9_-]*)*$/D', $icon) !== 1
    ) {
        return null;
    }

    return preg_replace('/\s+/', ' ', $icon);
}
PHP,
                ),
            );
    }

    private function contributeBasicControllerCode(
        IconPickerFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $planBuilder->controller
            ->addProperty(new ControllerProperty(
                name: $field->handle,
                declaration: sprintf('protected ?string $%s = null;', $field->handle),
                order: $position,
            ))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf('$this->set(%1$s, $this->%2$s ?? \'\');', $handleLiteral, $field->handle),
                    order: $position,
                ),
            )
            ->addMethodFragment(
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
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, '$args', false),
                    order: $position,
                ),
            );
    }

    private function contributeRepeatableControllerCode(
        IconPickerFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $planBuilder->controller
            ->addMethodFragment(
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
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, '$entry', true),
                    order: $position,
                ),
            );
    }

    private function renderValidationCode(
        IconPickerFieldTypeDto $field,
        string $sourceVariable,
        bool $repeatable,
    ): string {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $translatedLabel = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $invalidError = $repeatable
            ? sprintf('$errors->add(t(\'The field "%%s" contains an invalid icon in entry %%s.\', %s, $entryPosition + 1));', $translatedLabel)
            : sprintf('$errors->add(t(\'The field "%%s" contains an invalid icon.\', %s));', $translatedLabel);
        $requiredError = $repeatable
            ? sprintf('$errors->add(t(\'The field "%%s" is required in entry %%s.\', %s, $entryPosition + 1));', $translatedLabel)
            : sprintf('$errors->add(t(\'The field "%%s" is required.\', %s));', $translatedLabel);

        $lines = [
            sprintf('$normalizedIcon = $this->%s(%s[%s] ?? \'\');', self::NORMALIZE_METHOD, $sourceVariable, $handleLiteral),
            'if ($normalizedIcon === null) {',
            '    ' . $invalidError,
        ];
        if ($field->required) {
            $lines[] = '} elseif ($normalizedIcon === \'\') {';
            $lines[] = '    ' . $requiredError;
        }
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }

    private function renderFormFragment(IconPickerFieldTypeDto $field, bool $basicField): string
    {
        return $this->stubRenderer->render(
            'fragments/icon_picker/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{HELP_TEXT}}' => $field->helpText === null || $field->helpText === ''
                    ? ''
                    : PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>',
            ],
        );
    }

    private function renderViewFragment(IconPickerFieldTypeDto $field, bool $basicField): string
    {
        return $this->stubRenderer->render(
            'fragments/icon_picker/view-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $basicField
                ? ['{{HANDLE}}' => $field->handle]
                : ['{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle)],
        );
    }
}
