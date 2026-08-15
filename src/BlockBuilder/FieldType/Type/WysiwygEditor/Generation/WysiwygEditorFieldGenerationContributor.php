<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\WysiwygEditor\Generation;

use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContext;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorInterface;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerProperty;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerUseStatement;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use BlockBuilder\BlockGenerator\Generation\Plan\FormGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\WysiwygEditor\WysiwygEditorFieldTypeDto;
use Concrete\Core\Editor\LinkAbstractor;

readonly class WysiwygEditorFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string INITIALIZERS_VARIABLE = 'blockBuilderWysiwygEditorInitializers';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::WysiwygEditor;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof WysiwygEditorFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf('WYSIWYG editor field generation requires DTO "%s"; "%s" was provided.', WysiwygEditorFieldTypeDto::class, $context->fieldDto::class));
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
        $planBuilder->controller
            ->addUseStatement(new ControllerUseStatement(LinkAbstractor::class))
            ->addExportContentColumn($field->handle)
            ->addSearchableField($context->fieldContext, $field->handle);

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

        $this->contributeEditorInitializer($field, $fragmentKeyPrefix, $context->position, $planBuilder);
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
        WysiwygEditorFieldTypeDto $field,
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
                code: sprintf(
                    '$this->set(%1$s, LinkAbstractor::translateFromEditMode($this->%2$s ?? \'\'));',
                    $handleLiteral,
                    $field->handle,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::View->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$this->set(%1$s, LinkAbstractor::translateFrom($this->%2$s ?? \'\'));',
                    $handleLiteral,
                    $field->handle,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::SaveBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$args[%1$s] = isset($args[%1$s]) && is_scalar($args[%1$s])%2$s    ? LinkAbstractor::translateTo((string) $args[%1$s])%2$s    : \'\';',
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
        WysiwygEditorFieldTypeDto $field,
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
                    '$data[%1$s] = isset($entry[%1$s]) && is_scalar($entry[%1$s])%2$s    ? LinkAbstractor::translateTo((string) $entry[%1$s])%2$s    : \'\';',
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
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::PrepareEntryForEdit->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$entry[%1$s] = LinkAbstractor::translateFromEditMode($entry[%1$s] ?? \'\');',
                    $handleLiteral,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::PrepareEntryForView->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$entry[%1$s] = LinkAbstractor::translateFrom($entry[%1$s] ?? \'\');',
                    $handleLiteral,
                ),
                order: $position,
            ),
        );
    }

    private function contributeEditorInitializer(
        WysiwygEditorFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $planBuilder->form->addFragment(
            FormGenerationPlanBuilder::SECTION_SETUP,
            new CodeFragment(
                key: 'wysiwyg_editor.initializers',
                code: sprintf('<?php $%s = []; ?>', self::INITIALIZERS_VARIABLE),
                order: -1000,
            ),
        );
        $planBuilder->form->addFragment(
            FormGenerationPlanBuilder::SECTION_SETUP,
            new CodeFragment(
                key: $fragmentKeyPrefix . '.wysiwyg_initializer',
                code: $this->renderEditorInitializer($field),
                order: $position,
            ),
        );
        if ($field->minHeight !== null || $field->maxHeight !== null) {
            $planBuilder->form->addFragment(
                FormGenerationPlanBuilder::SECTION_SETUP,
                new CodeFragment(
                    key: $fragmentKeyPrefix . '.wysiwyg_height',
                    code: $this->renderEditorHeightStyle($field),
                    order: $position,
                ),
            );
        }
        $planBuilder->form->addFragment(
            FormGenerationPlanBuilder::SECTION_VALIDATION,
            new CodeFragment(
                key: 'wysiwyg_editor.attach_initializers',
                code: $this->stubRenderer->render(
                    'fragments/wysiwyg_editor/attach-initializers.php.stub',
                    ['{{INITIALIZERS_VARIABLE}}' => self::INITIALIZERS_VARIABLE],
                ),
                order: 1000,
            ),
        );
    }

    private function renderEditorInitializer(WysiwygEditorFieldTypeDto $field): string
    {
        $options = $this->decodeEditorOptions($field);

        // Currently Concrete renders two buttons for source preview
        // (inline and popup). Let's remove one.
        if (!$field->customConfig) {
            $options['removePlugins'] = 'sourcedialog';
        }

        return sprintf(
            '<?php $%1$s[%2$s] = $app->make(\'editor\')->getEditorInitJSFunction(%3$s); ?>',
            self::INITIALIZERS_VARIABLE,
            $this->phpLiteralFormatter->format($field->handle),
            $this->phpLiteralFormatter->format($options),
        );
    }

    private function renderEditorHeightStyle(WysiwygEditorFieldTypeDto $field): string
    {
        $declarations = [];
        if ($field->minHeight !== null) {
            $declarations[] = sprintf('        min-height: %dpx;', $field->minHeight);
        }
        if ($field->maxHeight !== null) {
            $declarations[] = sprintf('        max-height: %dpx;', $field->maxHeight);
        }

        return sprintf(
            '<style>%1$s'
            . '    #form-container-<?= h($formInstanceIdentifier); ?> .bb-wysiwyg-editor-%2$s .cke_contents {%1$s'
            . '%3$s%1$s'
            . '    }%1$s'
            . '</style>',
            PHP_EOL,
            $field->handle,
            implode(PHP_EOL, $declarations),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeEditorOptions(WysiwygEditorFieldTypeDto $field): array
    {
        if ($field->customConfig === null || trim($field->customConfig) === '') {
            return [];
        }

        try {
            $configurationObject = json_decode(
                $field->customConfig,
                false,
                512,
                JSON_THROW_ON_ERROR,
            );
            $options = json_decode($field->customConfig, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidFieldGenerationDtoException(sprintf('WYSIWYG editor field "%s" contains invalid editor configuration JSON.', $field->handle), previous: $exception);
        }
        if (!is_object($configurationObject) || !is_array($options)) {
            throw new InvalidFieldGenerationDtoException(sprintf('WYSIWYG editor field "%s" editor configuration must decode to a JSON object.', $field->handle));
        }

        return $options;
    }

    private function renderBasicValidation(WysiwygEditorFieldTypeDto $field, string $handleLiteral): string
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

    private function renderEntryValidation(WysiwygEditorFieldTypeDto $field, string $handleLiteral): string
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

    private function renderFormFragment(WysiwygEditorFieldTypeDto $field, bool $basicField): string
    {
        $helpText = $field->helpText !== null && $field->helpText !== ''
            ? sprintf(
                '%s    <div class="form-text"><?= t(%s); ?></div>',
                PHP_EOL,
                $this->phpLiteralFormatter->format($field->helpText),
            )
            : '';

        return $this->stubRenderer->render(
            $basicField
                ? 'fragments/wysiwyg_editor/form-basic.php.stub'
                : 'fragments/wysiwyg_editor/form-repeatable.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{HELP_TEXT}}' => $helpText,
            ],
        );
    }

    private function renderViewFragment(WysiwygEditorFieldTypeDto $field, bool $basicField): string
    {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        return $this->stubRenderer->render(
            $basicField
                ? 'fragments/wysiwyg_editor/view-basic.php.stub'
                : 'fragments/wysiwyg_editor/view-repeatable.php.stub',
            $basicField
                ? [
                    '{{HANDLE}}' => $field->handle,
                    '{{DISPLAY_CONDITION}}' => sprintf('!empty($%s)', $field->handle),
                ]
                : [
                    '{{HANDLE_LITERAL}}' => $handleLiteral,
                    '{{DISPLAY_CONDITION}}' => sprintf('!empty($entry[%s])', $handleLiteral),
                ],
        );
    }
}
