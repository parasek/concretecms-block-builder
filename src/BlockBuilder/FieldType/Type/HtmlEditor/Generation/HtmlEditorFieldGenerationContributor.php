<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\HtmlEditor\Generation;

use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContext;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorInterface;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerAsset;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerProperty;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerUseStatement;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\HtmlEditor\HtmlEditorFieldTypeDto;
use Concrete\Core\Editor\LinkAbstractor;

readonly class HtmlEditorFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const int DEFAULT_EDITOR_HEIGHT = 250;

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::HtmlEditor;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof HtmlEditorFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'HTML editor field generation requires DTO "%s"; "%s" was provided.',
                HtmlEditorFieldTypeDto::class,
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
        $planBuilder->controller
            ->addUseStatement(new ControllerUseStatement(LinkAbstractor::class))
            ->addExportContentColumn($field->handle)
            ->addSearchableField($context->fieldContext, $field->handle)
            ->addAsset(new ControllerAsset('ace'));

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
        } else {
            $this->contributeRepeatableControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
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
        HtmlEditorFieldTypeDto $field,
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
        HtmlEditorFieldTypeDto $field,
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

    private function renderBasicValidation(HtmlEditorFieldTypeDto $field, string $handleLiteral): string
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

    private function renderEntryValidation(HtmlEditorFieldTypeDto $field, string $handleLiteral): string
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

    private function renderFormFragment(HtmlEditorFieldTypeDto $field, bool $basicField): string
    {
        $helpText = $field->helpText !== null && $field->helpText !== ''
            ? sprintf(
                '%s    <div class="form-text"><?= t(%s); ?></div>',
                PHP_EOL,
                $this->phpLiteralFormatter->format($field->helpText),
            )
            : '';

        $height = $field->height ?? self::DEFAULT_EDITOR_HEIGHT;

        return $this->stubRenderer->render(
            $basicField
                ? 'fragments/html_editor/form-basic.php.stub'
                : 'fragments/html_editor/form-repeatable.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{HEIGHT}}' => (string) $height,
                '{{HELP_TEXT}}' => $helpText,
            ],
        );
    }

    private function renderViewFragment(HtmlEditorFieldTypeDto $field, bool $basicField): string
    {
        return $this->stubRenderer->render(
            $basicField
                ? 'fragments/html_editor/view-basic.php.stub'
                : 'fragments/html_editor/view-repeatable.php.stub',
            $basicField
                ? ['{{HANDLE}}' => $field->handle]
                : ['{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle)],
        );
    }
}
