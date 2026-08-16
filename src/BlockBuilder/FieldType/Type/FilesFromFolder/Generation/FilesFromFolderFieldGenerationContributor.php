<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\FilesFromFolder\Generation;

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
use BlockBuilder\FieldType\Type\FilesFromFolder\FilesFromFolderFieldTypeDto;
use Concrete\Core\Entity\File\File as ConcreteFile;
use Concrete\Core\Entity\File\Version as FileVersion;
use Concrete\Core\File\Filesystem;
use Concrete\Core\File\FolderItemList;
use Concrete\Core\Permission\Response\FileFolderResponse;
use Concrete\Core\Permission\Response\FileResponse;
use Concrete\Core\Permission\Response\Response as PermissionResponse;
use Concrete\Core\Tree\Node\Type\File as FileTreeNode;
use Concrete\Core\Tree\Node\Type\FileFolder;

final readonly class FilesFromFolderFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string GET_FOLDERS_METHOD = 'getBlockBuilderFileFolders';
    private const string GET_FILES_METHOD = 'getBlockBuilderFilesFromFolder';
    private const string VALIDATE_FOLDER_METHOD = 'isValidBlockBuilderFileFolder';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::FilesFromFolder;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof FilesFromFolderFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf('Files from a Folder field generation requires DTO "%s"; "%s" was provided.', FilesFromFolderFieldTypeDto::class, $context->fieldDto::class));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;
        $folderOptionVariable = $context->isBasicField()
            ? $field->handle . '_folders'
            : 'entry_' . $field->handle . '_folders';

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
            ->addExportFileFolderColumn($field->handle)
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix . '.folders',
                    code: sprintf(
                        '$this->set(%s, $this->%s());',
                        $this->phpLiteralFormatter->format($folderOptionVariable),
                        self::GET_FOLDERS_METHOD,
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
                        key: $fragmentKeyPrefix . '.folders',
                        code: sprintf(
                            '<?php $%1$s = isset($%1$s) && is_array($%1$s) ? $%1$s : []; ?>',
                            $folderOptionVariable,
                        ),
                        order: $context->position,
                    ),
                )
                ->addRepeatableCapturedVariable($folderOptionVariable)
                ->addRepeatableDefaultValue($field->handle, 0);
        }

        $this->contributeViewDocumentation($context, $field, $planBuilder);
        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($field, $folderOptionVariable, $context->isBasicField()),
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
            ->addUseStatement(new ControllerUseStatement(ConcreteFile::class, 'ConcreteFile'))
            ->addUseStatement(new ControllerUseStatement(FileVersion::class, 'FileVersion'))
            ->addUseStatement(new ControllerUseStatement(Filesystem::class))
            ->addUseStatement(new ControllerUseStatement(FolderItemList::class))
            ->addUseStatement(new ControllerUseStatement(FileFolderResponse::class))
            ->addUseStatement(new ControllerUseStatement(FileResponse::class))
            ->addUseStatement(new ControllerUseStatement(PermissionResponse::class, 'PermissionResponse'))
            ->addUseStatement(new ControllerUseStatement(FileTreeNode::class, 'FileTreeNode'))
            ->addUseStatement(new ControllerUseStatement(FileFolder::class))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'files_from_folder.get_folders',
                    code: <<<'PHP'
private function getBlockBuilderFileFolders(): array
{
    $filesystem = new Filesystem();
    $rootFolder = $filesystem->getRootFolder();
    $options = [0 => t('----')];
    if (!$rootFolder instanceof FileFolder) {
        return $options;
    }

    $folders = $rootFolder->getHierarchicalNodesOfType(treeNodeTypeHandle: 'file_folder', returnNodeObjects: true);

    foreach ($folders as $folderData) {
        $folder = $folderData['treeNodeObject'] ?? null;
        if (!$folder instanceof FileFolder) {
            continue;
        }

        /** @var FileFolderResponse $folderPermissions */
        $folderPermissions = PermissionResponse::getResponse($folder);
        if (!$folderPermissions->canViewTreeNode()) {
            continue;
        }

        $level = max(1, (int) ($folderData['level'] ?? 1));
        $folderName = (string) $folder->getTreeNodeDisplayName('text');
        $options[(int) $folder->getTreeNodeID()] = str_repeat('— ', $level - 1) . $folderName;
    }

    return $options;
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'files_from_folder.validate_folder',
                    code: <<<'PHP'
private function isValidBlockBuilderFileFolder(mixed $folderID, bool $checkPermissions = false): bool
{
    if (
        (!is_int($folderID) && !is_string($folderID))
        || (is_string($folderID) && !ctype_digit($folderID))
        || (int) $folderID < 1
    ) {
        return false;
    }

    $folder = FileFolder::getByID((int) $folderID);
    if (!$folder instanceof FileFolder) {
        return false;
    }

    if (!$checkPermissions) {
        return true;
    }

    /** @var FileFolderResponse $folderPermissions */
    $folderPermissions = PermissionResponse::getResponse($folder);

    return $folderPermissions->canViewTreeNode();
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'files_from_folder.get_files',
                    code: <<<'PHP'
private function getBlockBuilderFilesFromFolder(mixed $folderID, string $fileOrder): array
{
    if (!$this->isValidBlockBuilderFileFolder($folderID)) {
        return [];
    }

    $folder = FileFolder::getByID((int) $folderID);
    if (!$folder instanceof FileFolder) {
        return [];
    }

    $fileList = new FolderItemList();
    // Keep the query non-recursive and exclude child folders.
    $fileList->filterByParentFolder($folder);
    $fileList->getQueryObject()
        ->andWhere('nt.treeNodeTypeHandle = :blockBuilderFileNodeType')
        ->setParameter('blockBuilderFileNodeType', 'file');
    // File Manager order follows the folder's stored tree-node order.
    $fileList->sortBy('n.treeNodeDisplayOrder');

    $files = [];
    foreach ($fileList->getResults() as $fileNode) {
        if (!$fileNode instanceof FileTreeNode) {
            continue;
        }

        $file = $fileNode->getTreeNodeFileObject();
        if (!$file instanceof ConcreteFile) {
            continue;
        }

        /** @var FileResponse $filePermissions */
        $filePermissions = PermissionResponse::getResponse($file);
        if ($filePermissions->canRead()) {
            $files[] = $file;
        }
    }

    if ($fileOrder === 'random') {
        shuffle($files);
    } elseif ($fileOrder === 'ascending' || $fileOrder === 'descending') {
        $direction = $fileOrder === 'descending' ? -1 : 1;
        usort(
            $files,
            static function (ConcreteFile $firstFile, ConcreteFile $secondFile) use ($direction): int {
                $firstVersion = $firstFile->getVersion();
                $secondVersion = $secondFile->getVersion();
                $firstName = $firstVersion instanceof FileVersion
                    ? (string) ($firstVersion->getTitle() ?: $firstVersion->getFileName())
                    : '';
                $secondName = $secondVersion instanceof FileVersion
                    ? (string) ($secondVersion->getTitle() ?: $secondVersion->getFileName())
                    : '';
                $comparison = strnatcasecmp($firstName, $secondName);

                return $direction * ($comparison !== 0
                        ? $comparison
                        : $firstFile->getFileID() <=> $secondFile->getFileID());
            },
        );
    }

    return $files;
}
PHP,
                ),
            );
    }

    private function contributeBasicControllerCode(
        FilesFromFolderFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $fileOrderLiteral = $this->phpLiteralFormatter->format($field->fileOrder);

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
                        '$this->set(%1$s, $this->%2$s($this->%3$s, true) ? (int) $this->%3$s : 0);',
                        $handleLiteral,
                        self::VALIDATE_FOLDER_METHOD,
                        $field->handle,
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
                        '$folderID = $this->%1$s($this->%2$s) ? (int) $this->%2$s : null;%3$s'
                        . '$this->set(%4$s, $folderID);%3$s'
                        . '$this->set(%5$s, $this->%6$s($folderID, %7$s));',
                        self::VALIDATE_FOLDER_METHOD,
                        $field->handle,
                        PHP_EOL,
                        $handleLiteral,
                        $this->phpLiteralFormatter->format($field->handle . '_files'),
                        self::GET_FILES_METHOD,
                        $fileOrderLiteral,
                    ),
                    order: $position,
                ),
            );
    }

    private function contributeRepeatableControllerCode(
        FilesFromFolderFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $filesHandleLiteral = $this->phpLiteralFormatter->format($field->handle . '_files');
        $fileOrderLiteral = $this->phpLiteralFormatter->format($field->fileOrder);

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
                        '$entry[%1$s] = $this->%2$s($entry[%1$s] ?? null, true) ? (int) $entry[%1$s] : 0;',
                        $handleLiteral,
                        self::VALIDATE_FOLDER_METHOD,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForView->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$folderID = $this->%1$s($entry[%2$s] ?? null) ? (int) $entry[%2$s] : null;%3$s'
                        . '$entry[%2$s] = $folderID;%3$s'
                        . '$entry[%4$s] = $this->%5$s($folderID, %6$s);',
                        self::VALIDATE_FOLDER_METHOD,
                        $handleLiteral,
                        PHP_EOL,
                        $filesHandleLiteral,
                        self::GET_FILES_METHOD,
                        $fileOrderLiteral,
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
            '$folderID = %1$s[%2$s] ?? null;%3$s'
            . '%4$s[%2$s] = (is_int($folderID) || (is_string($folderID) && ctype_digit($folderID)))%3$s'
            . '    ? max(0, (int) $folderID)%3$s'
            . '    : 0;',
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
            $targetVariable,
        );
    }

    private function renderValidationCode(
        FilesFromFolderFieldTypeDto $field,
        string $sourceVariable,
        string $handleLiteral,
        bool $repeatable,
    ): string {
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $entryMessage = $repeatable ? ' in entry %s' : '';
        $entryArgument = $repeatable ? ', $entryPosition + 1' : '';
        $requiredValidation = $field->required
            ? sprintf(
                'if (!$hasFolderSelection) {%1$s    $errors->add(t(\'The field "%%s" is required%2$s.\', %3$s%4$s));%1$s}'
                . ' elseif (!$this->%5$s($folderValue, true)) {%1$s    $errors->add(t(\'The field "%%s" contains an invalid folder%2$s.\', %3$s%4$s));%1$s}',
                PHP_EOL,
                $entryMessage,
                $label,
                $entryArgument,
                self::VALIDATE_FOLDER_METHOD,
            )
            : sprintf(
                'if ($hasFolderSelection && !$this->%1$s($folderValue, true)) {%2$s'
                . '    $errors->add(t(\'The field "%%s" contains an invalid folder%3$s.\', %4$s%5$s));%2$s}',
                self::VALIDATE_FOLDER_METHOD,
                PHP_EOL,
                $entryMessage,
                $label,
                $entryArgument,
            );

        return sprintf(
            '$folderValue = %1$s[%2$s] ?? null;%3$s'
            . '$hasFolderSelection = $folderValue !== null && $folderValue !== \'\' && $folderValue !== 0 && $folderValue !== \'0\';%3$s'
            . '%4$s',
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
            $requiredValidation,
        );
    }

    private function contributeViewDocumentation(
        FieldGenerationContext $context,
        FilesFromFolderFieldTypeDto $field,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $order = $context->position * 10;
        $planBuilder->view
            ->addFieldVariable(
                $context->fieldContext,
                new ViewVariableDocumentation(
                    name: $field->handle,
                    type: 'int|null',
                    description: sprintf('%s folder ID', $field->label),
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
        FilesFromFolderFieldTypeDto $field,
        string $folderOptionVariable,
        bool $basicField,
    ): string {
        return $this->stubRenderer->render(
            'fragments/files_from_folder/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{OPTION_VARIABLE}}' => $folderOptionVariable,
                '{{HELP_TEXT}}' => $field->helpText === null || $field->helpText === ''
                    ? ''
                    : PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>',
            ],
        );
    }

    private function renderViewFragment(FilesFromFolderFieldTypeDto $field, bool $basicField): string
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
            'fragments/files_from_folder/view-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $replacements,
        );
    }
}
