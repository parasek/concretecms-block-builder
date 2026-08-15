<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\FileSet\Generation;

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
use BlockBuilder\FieldType\Type\FileSet\FileSetFieldTypeDto;
use Concrete\Core\File\FileList;
use Concrete\Core\File\Set\Set;
use Concrete\Core\File\Set\SetList;

final readonly class FileSetFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string GET_FILE_SETS_METHOD = 'getBlockBuilderFileSets';
    private const string GET_FILES_METHOD = 'getBlockBuilderFilesByFileSetID';
    private const string VALIDATE_FILE_SET_METHOD = 'isValidBlockBuilderFileSet';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::FileSet;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof FileSetFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf('File Set field generation requires DTO "%s"; "%s" was provided.', FileSetFieldTypeDto::class, $context->fieldDto::class));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;
        $optionVariable = $context->isBasicField()
            ? $field->handle . '_fileSets'
            : 'entry_' . $field->handle . '_fileSets';

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
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix . '.options',
                    code: sprintf(
                        '$this->set(%s, $this->%s(%s));',
                        $this->phpLiteralFormatter->format($optionVariable),
                        self::GET_FILE_SETS_METHOD,
                        $this->phpLiteralFormatter->format($field->fileSetPrefix),
                    ),
                    order: $context->position,
                ),
            );

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
        } else {
            $this->contributeRepeatableControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
            $planBuilder->form
                ->addFragment(
                    FormGenerationPlanBuilder::SECTION_SETUP,
                    new CodeFragment(
                        key: $fragmentKeyPrefix . '.options',
                        code: sprintf(
                            '<?php $%1$s = isset($%1$s) && is_array($%1$s) ? $%1$s : []; ?>',
                            $optionVariable,
                        ),
                        order: $context->position,
                    ),
                )
                ->addRepeatableCapturedVariable($optionVariable)
                ->addRepeatableDefaultValue($field->handle, 0);
        }

        $this->contributeViewDocumentation($context, $field, $planBuilder);
        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($field, $optionVariable, $context->isBasicField()),
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
        $planBuilder->controller
            ->addUseStatement(new ControllerUseStatement(FileList::class))
            ->addUseStatement(new ControllerUseStatement(Set::class, 'FileSet'))
            ->addUseStatement(new ControllerUseStatement(SetList::class, 'FileSetList'))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'file_set.get_file_sets',
                    code: <<<'PHP'
private function getBlockBuilderFileSets(string $fileSetPrefix = ''): array
{
    $options = [0 => t('----')];
    $fileSetList = new FileSetList();

    foreach ($fileSetList->get() as $fileSet) {
        if (!$fileSet instanceof FileSet) {
            continue;
        }

        $fileSetName = (string) $fileSet->getFileSetName();
        if ($fileSetPrefix !== '' && !str_starts_with($fileSetName, $fileSetPrefix)) {
            continue;
        }
        if ($fileSetPrefix !== '') {
            $fileSetName = substr($fileSetName, strlen($fileSetPrefix));
        }
        $options[(int) $fileSet->getFileSetID()] = $fileSetName;
    }

    return $options;
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'file_set.validate_file_set',
                    code: <<<'PHP'
private function isValidBlockBuilderFileSet(mixed $fileSetID, string $fileSetPrefix = ''): bool
{
    if (
        (!is_int($fileSetID) && !is_string($fileSetID))
        || (is_string($fileSetID) && !ctype_digit($fileSetID))
        || (int) $fileSetID < 1
    ) {
        return false;
    }

    $fileSet = FileSet::getByID((int) $fileSetID);
    if (!$fileSet instanceof FileSet) {
        return false;
    }

    return $fileSetPrefix === ''
        || str_starts_with((string) $fileSet->getFileSetName(), $fileSetPrefix);
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'file_set.get_files',
                    code: <<<'PHP'
private function getBlockBuilderFilesByFileSetID(mixed $fileSetID): array
{
    if (!$this->isValidBlockBuilderFileSet($fileSetID)) {
        return [];
    }

    $fileSet = FileSet::getByID((int) $fileSetID);
    $fileList = new FileList();
    $fileList->filterBySet($fileSet);
    $fileList->sortByFileSetDisplayOrder();

    return $fileList->getResults();
}
PHP,
                ),
            );
    }

    private function contributeBasicControllerCode(
        FileSetFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

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
                        '$this->set(%1$s, $this->%2$s($this->%3$s, %4$s) ? (int) $this->%3$s : 0);',
                        $handleLiteral,
                        self::VALIDATE_FILE_SET_METHOD,
                        $field->handle,
                        $this->phpLiteralFormatter->format($field->fileSetPrefix),
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode('$args', $handleLiteral),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, '$args', $handleLiteral, false),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::View->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$fileSetID = $this->%1$s(%2$s, %3$s) ? (int) %2$s : null;%4$s'
                        . '$this->set(%5$s, $fileSetID);%4$s'
                        . '$this->set(%6$s, $this->%7$s($fileSetID));',
                        self::VALIDATE_FILE_SET_METHOD,
                        '$this->' . $field->handle,
                        $this->phpLiteralFormatter->format($field->fileSetPrefix),
                        PHP_EOL,
                        $handleLiteral,
                        $this->phpLiteralFormatter->format($field->handle . '_files'),
                        self::GET_FILES_METHOD,
                    ),
                    order: $position,
                ),
            );
    }

    private function contributeRepeatableControllerCode(
        FileSetFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $filesHandleLiteral = $this->phpLiteralFormatter->format($field->handle . '_files');
        $prefixLiteral = $this->phpLiteralFormatter->format($field->fileSetPrefix);

        $planBuilder->controller
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode('$data', $handleLiteral, '$entry'),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, '$entry', $handleLiteral, true),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$entry[%1$s] = $this->%2$s($entry[%1$s] ?? null, %3$s) ? (int) $entry[%1$s] : 0;',
                        $handleLiteral,
                        self::VALIDATE_FILE_SET_METHOD,
                        $prefixLiteral,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForView->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$fileSetID = $this->%1$s($entry[%2$s] ?? null, %3$s) ? (int) $entry[%2$s] : null;%4$s'
                        . '$entry[%2$s] = $fileSetID;%4$s'
                        . '$entry[%5$s] = $this->%6$s($fileSetID);',
                        self::VALIDATE_FILE_SET_METHOD,
                        $handleLiteral,
                        $prefixLiteral,
                        PHP_EOL,
                        $filesHandleLiteral,
                        self::GET_FILES_METHOD,
                    ),
                    order: $position,
                ),
            );
    }

    private function renderSaveCode(
        string $targetVariable,
        string $handleLiteral,
        string $sourceVariable = '$args',
    ): string {
        return sprintf(
            '$fileSetID = %1$s[%2$s] ?? null;%3$s'
            . '%4$s[%2$s] = (is_int($fileSetID) || (is_string($fileSetID) && ctype_digit($fileSetID)))%3$s'
            . '    ? max(0, (int) $fileSetID)%3$s'
            . '    : 0;',
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
            $targetVariable,
        );
    }

    private function renderValidationCode(
        FileSetFieldTypeDto $field,
        string $sourceVariable,
        string $handleLiteral,
        bool $repeatable,
    ): string {
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $prefix = $this->phpLiteralFormatter->format($field->fileSetPrefix);
        $entryMessage = $repeatable ? ' in entry %s' : '';
        $entryArgument = $repeatable ? ', $entryPosition + 1' : '';
        $requiredValidation = $field->required
            ? sprintf(
                'if (!$hasFileSetSelection) {%1$s    $errors->add(t(\'The field "%%s" is required%2$s.\', %3$s%4$s));%1$s}'
                . ' elseif (!$this->%5$s($fileSetValue, %6$s)) {%1$s    $errors->add(t(\'The field "%%s" contains an invalid file set%2$s.\', %3$s%4$s));%1$s}',
                PHP_EOL,
                $entryMessage,
                $label,
                $entryArgument,
                self::VALIDATE_FILE_SET_METHOD,
                $prefix,
            )
            : sprintf(
                'if ($hasFileSetSelection && !$this->%1$s($fileSetValue, %2$s)) {%3$s'
                . '    $errors->add(t(\'The field "%%s" contains an invalid file set%4$s.\', %5$s%6$s));%3$s}',
                self::VALIDATE_FILE_SET_METHOD,
                $prefix,
                PHP_EOL,
                $entryMessage,
                $label,
                $entryArgument,
            );

        return sprintf(
            '$fileSetValue = %1$s[%2$s] ?? null;%3$s'
            . '$hasFileSetSelection = $fileSetValue !== null && $fileSetValue !== \'\' && $fileSetValue !== 0 && $fileSetValue !== \'0\';%3$s'
            . '%4$s',
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
            $requiredValidation,
        );
    }

    private function contributeViewDocumentation(
        FieldGenerationContext $context,
        FileSetFieldTypeDto $field,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $order = $context->position * 10;
        $planBuilder->view
            ->addFieldVariable(
                $context->fieldContext,
                new ViewVariableDocumentation(
                    name: $field->handle,
                    type: 'int|null',
                    description: sprintf('%s File Set ID', $field->label),
                    order: $order,
                ),
            )
            ->addFieldVariable(
                $context->fieldContext,
                new ViewVariableDocumentation(
                    name: $field->handle . '_files',
                    type: 'list<\\Concrete\\Core\\Entity\\File\\File|\\Concrete\\Core\\Entity\\File\\Version>',
                    description: sprintf('Files in %s', $field->label),
                    order: $order + 1,
                ),
            );
    }

    private function renderFormFragment(
        FileSetFieldTypeDto $field,
        string $optionVariable,
        bool $basicField,
    ): string {
        return $this->stubRenderer->render(
            'fragments/file_set/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{OPTION_VARIABLE}}' => $optionVariable,
                '{{PREFIX}}' => $field->fileSetPrefix === ''
                    ? ''
                    : '        <span class="input-group-text"><?= h(' . $this->phpLiteralFormatter->format($field->fileSetPrefix) . '); ?></span>' . PHP_EOL,
                '{{HELP_TEXT}}' => $field->helpText === null || $field->helpText === ''
                    ? ''
                    : PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>',
            ],
        );
    }

    private function renderViewFragment(FileSetFieldTypeDto $field, bool $basicField): string
    {
        $replacements = [
            '{{FILE_VARIABLE}}' => ($basicField ? '' : 'entry_') . $field->handle . '_file',
        ];
        if ($basicField) {
            $replacements['{{HANDLE}}'] = $field->handle;
        } else {
            $replacements += [
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{FILES_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_files'),
            ];
        }

        return $this->stubRenderer->render(
            'fragments/file_set/view-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $replacements,
        );
    }
}
