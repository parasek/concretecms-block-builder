<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Express\Generation;

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
use BlockBuilder\BlockGenerator\Generation\Plan\ViewGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\Express\ExpressFieldTypeDto;
use Concrete\Core\Entity\Express\Entity as ExpressEntity;
use Concrete\Core\Entity\Express\Entry as ExpressEntry;
use Concrete\Core\Support\Facade\Express;

final readonly class ExpressFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string GET_ENTITY_METHOD = 'getBlockBuilderExpressEntity';
    private const string GET_ENTRY_METHOD = 'getBlockBuilderExpressEntry';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::Express;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof ExpressFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Express field generation requires DTO "%s"; "%s" was provided.',
                ExpressFieldTypeDto::class,
                $context->fieldDto::class,
            ));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;
        $entityIdentifierVariable = $context->fieldContext->value . '_' . $field->handle . '_expressEntityIdentifier';

        $this->contributeSharedControllerCode($planBuilder);
        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'integer',
                unsigned: true,
                hasDefault: true,
                defaultValue: 0,
                order: $context->position,
            ),
        );
        $planBuilder->controller
            ->addRequiredFeature('EXPRESS')
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix . '.entity',
                    code: sprintf(
                        '$expressEntity = $this->%1$s(%2$s);%3$s'
                        . '$this->set(%4$s, $expressEntity instanceof ExpressEntity ? (int) $expressEntity->getID() : 0);',
                        self::GET_ENTITY_METHOD,
                        $this->phpLiteralFormatter->format($field->expressHandle),
                        PHP_EOL,
                        $this->phpLiteralFormatter->format($entityIdentifierVariable),
                    ),
                    order: $context->position,
                ),
            );

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
        } else {
            $this->contributeRepeatableControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
            $planBuilder->form
                ->addRepeatableCapturedVariable($entityIdentifierVariable)
                ->addRepeatableDefaultValue($field->handle, 0);
        }

        $planBuilder->form->addFragment(
            FormGenerationPlanBuilder::SECTION_SETUP,
            new CodeFragment(
                key: $fragmentKeyPrefix . '.entity',
                code: sprintf(
                    '<?php $%1$s = isset($%1$s) ? (int) $%1$s : 0; ?>',
                    $entityIdentifierVariable,
                ),
                order: $context->position,
            ),
        );
        $this->contributeViewDocumentation($context, $field, $planBuilder);
        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($field, $entityIdentifierVariable, $context->isBasicField()),
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
        $planBuilder->view->addFragment(
            ViewGenerationPlanBuilder::SECTION_SETUP,
            new CodeFragment(
                key: 'express.application',
                code: <<<'PHP'
<?php
$application = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
?>
PHP,
            ),
        );

        $planBuilder->controller
            ->addUseStatement(new ControllerUseStatement(ExpressEntity::class, 'ExpressEntity'))
            ->addUseStatement(new ControllerUseStatement(ExpressEntry::class, 'ExpressEntry'))
            ->addUseStatement(new ControllerUseStatement(Express::class))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'express.get_entity',
                    code: <<<'PHP'
private function getBlockBuilderExpressEntity(string $entityHandle): ?ExpressEntity
{
    $entity = Express::getObjectByHandle($entityHandle);

    return $entity instanceof ExpressEntity ? $entity : null;
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'express.get_entry',
                    code: <<<'PHP'
private function getBlockBuilderExpressEntry(mixed $entryIdentifier, string $entityHandle): ?ExpressEntry
{
    if (
        (!is_int($entryIdentifier) && !is_string($entryIdentifier))
        || (is_string($entryIdentifier) && !ctype_digit($entryIdentifier))
        || (int) $entryIdentifier < 1
    ) {
        return null;
    }

    $entry = Express::getEntry((int) $entryIdentifier);
    if (!$entry instanceof ExpressEntry || $entry->getEntity()->getHandle() !== $entityHandle) {
        return null;
    }

    return $entry;
}
PHP,
                ),
            );
    }

    private function contributeBasicControllerCode(
        ExpressFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $expressHandleLiteral = $this->phpLiteralFormatter->format($field->expressHandle);

        $planBuilder->controller
            ->addProperty(new ControllerProperty(
                name: $field->handle,
                declaration: sprintf('protected int|string|null $%s = null;', $field->handle),
                order: $position,
            ))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$this->set(%1$s, $this->%2$s($this->%3$s, %4$s) instanceof ExpressEntry ? (int) $this->%3$s : 0);',
                        $handleLiteral,
                        self::GET_ENTRY_METHOD,
                        $field->handle,
                        $expressHandleLiteral,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::View->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$expressEntry = $this->%1$s($this->%2$s, %3$s);%4$s$this->set(%5$s, $expressEntry instanceof ExpressEntry ? (int) $expressEntry->getID() : null);',
                        self::GET_ENTRY_METHOD,
                        $field->handle,
                        $expressHandleLiteral,
                        PHP_EOL,
                        $handleLiteral,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode('$args', '$args', $handleLiteral),
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
        ExpressFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $expressHandleLiteral = $this->phpLiteralFormatter->format($field->expressHandle);

        $planBuilder->controller
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode('$data', '$entry', $handleLiteral),
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
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$expressEntry = $this->%1$s($entry[%2$s] ?? null, %3$s);%4$s$entry[%2$s] = $expressEntry instanceof ExpressEntry ? (int) $expressEntry->getID() : 0;',
                        self::GET_ENTRY_METHOD,
                        $handleLiteral,
                        $expressHandleLiteral,
                        PHP_EOL,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForView->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$expressEntry = $this->%1$s($entry[%2$s] ?? null, %3$s);%4$s$entry[%2$s] = $expressEntry instanceof ExpressEntry ? (int) $expressEntry->getID() : null;',
                        self::GET_ENTRY_METHOD,
                        $handleLiteral,
                        $expressHandleLiteral,
                        PHP_EOL,
                    ),
                    order: $position,
                ),
            );
    }

    private function renderSaveCode(string $targetVariable, string $sourceVariable, string $handleLiteral): string
    {
        return sprintf(
            '$expressEntryIdentifier = %1$s[%2$s] ?? null;%3$s'
            . '%4$s[%2$s] = (is_int($expressEntryIdentifier) || (is_string($expressEntryIdentifier) && ctype_digit($expressEntryIdentifier)))%3$s'
            . '    ? max(0, (int) $expressEntryIdentifier)%3$s'
            . '    : 0;',
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
            $targetVariable,
        );
    }

    private function renderValidationCode(
        ExpressFieldTypeDto $field,
        string $sourceVariable,
        bool $repeatable,
    ): string {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $expressHandleLiteral = $this->phpLiteralFormatter->format($field->expressHandle);
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $entryMessage = $repeatable ? ' in entry %s' : '';
        $entryArgument = $repeatable ? ', $entryPosition + 1' : '';
        $validation = $field->required
            ? sprintf(
                'if (!$hasExpressEntry) {%1$s    $errors->add(t(\'The field "%%s" is required%2$s.\', %3$s%4$s));%1$s}'
                . ' elseif (!$expressEntry instanceof ExpressEntry) {%1$s    $errors->add(t(\'The field "%%s" contains an invalid Express entry%2$s.\', %3$s%4$s));%1$s}',
                PHP_EOL,
                $entryMessage,
                $label,
                $entryArgument,
            )
            : sprintf(
                'if ($hasExpressEntry && !$expressEntry instanceof ExpressEntry) {%1$s'
                . '    $errors->add(t(\'The field "%%s" contains an invalid Express entry%2$s.\', %3$s%4$s));%1$s}',
                PHP_EOL,
                $entryMessage,
                $label,
                $entryArgument,
            );

        return sprintf(
            '$expressEntryValue = %1$s[%2$s] ?? null;%3$s'
            . '$hasExpressEntry = $expressEntryValue !== null && $expressEntryValue !== \'\' && $expressEntryValue !== 0 && $expressEntryValue !== \'0\';%3$s'
            . '$expressEntry = $hasExpressEntry ? $this->%4$s($expressEntryValue, %5$s) : null;%3$s'
            . '%6$s',
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
            self::GET_ENTRY_METHOD,
            $expressHandleLiteral,
            $validation,
        );
    }

    private function contributeViewDocumentation(
        FieldGenerationContext $context,
        ExpressFieldTypeDto $field,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $planBuilder->view->addFieldVariable(
            $context->fieldContext,
            new ViewVariableDocumentation(
                name: $field->handle,
                type: 'int|null',
                description: sprintf('%s Express entry identifier', $field->label),
                order: $context->position,
            ),
        );
    }

    private function renderFormFragment(
        ExpressFieldTypeDto $field,
        string $entityIdentifierVariable,
        bool $basicField,
    ): string {
        return $this->stubRenderer->render(
            'fragments/express/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{EXPRESS_HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->expressHandle),
                '{{ENTITY_IDENTIFIER_VARIABLE}}' => $entityIdentifierVariable,
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{HELP_TEXT}}' => $field->helpText === null || $field->helpText === ''
                    ? ''
                    : PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>',
            ],
        );
    }

    private function renderViewFragment(ExpressFieldTypeDto $field, bool $basicField): string
    {
        return $this->stubRenderer->render(
            'fragments/express/view.php.stub',
            [
                '{{ENTRY_IDENTIFIER_EXPRESSION}}' => $basicField
                    ? '$' . $field->handle
                    : '$entry[' . $this->phpLiteralFormatter->format($field->handle) . ']',
                '{{ENTRY_VARIABLE}}' => $field->expressHandle . '_entry',
            ],
        );
    }
}
