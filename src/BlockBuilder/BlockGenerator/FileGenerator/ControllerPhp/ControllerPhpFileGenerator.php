<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\Exception\GenerationContributionConflictException;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerAsset;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerUseStatement;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;

readonly class ControllerPhpFileGenerator implements FileGeneratorInterface
{
    private const string STUB_FILE = 'controller.php.stub';
    private const array RESERVED_GENERATED_PROPERTY_NAMES = [
        'app',
        'controller',
        'form',
        'view',
        'entries',
        'settings',
        'label',
        'description',
        'forminstanceidentifier',
        'bttable',
        'btexporttables',
        'btinterfacewidth',
        'btinterfaceheight',
        'btwrapperclass',
        'btdefaultset',
        'btexportpagecolumns',
        'btexportfilecolumns',
        'btexportcontentcolumns',
        'btexportfilefoldercolumns',
        'btignorepagethemegridframeworkcontainer',
        'btcacheblockrecord',
        'btcacheblockoutput',
        'btcacheblockoutputonpost',
        'btcacheblockoutputforregisteredusers',
        'btcacheblockoutputlifetime',
        'supportsavingnullvalues',
    ];

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    /**
     * @return list<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): array
    {
        $hasEntries = $context->config->entries !== [];
        $hasValidation = $hasEntries
            || $context->plan->controller->getMethodFragments(
                ControllerMethodSectionEnum::ValidateBasicFields->value,
            ) !== [];

        return [
            new GeneratedTextFile(
                relativePath: FILENAME_BLOCK_CONTROLLER,
                contents: $this->normalizeControllerWhitespace($this->stubRenderer->render(self::STUB_FILE, [
                    '{{BLOCK_HANDLE_PASCAL_CASE}}' => $context->manifest->blockHandlePascalCase,
                    '{{IMPLEMENTED_INTERFACES}}' => $this->renderImplementedInterfaces($context),
                    '{{USE_STATEMENTS}}' => $this->renderUseStatements(
                        $context,
                        $hasEntries,
                        $hasValidation,
                    ),
                    '{{BLOCK_PROPERTIES}}' => $this->renderBlockProperties($context),
                    '{{FIELD_PROPERTIES}}' => $this->renderFieldProperties($context),
                    '{{BLOCK_NAME_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->blockName),
                    '{{BLOCK_DESCRIPTION_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->blockDescription ?? ''),
                    '{{REQUIRED_FEATURES_METHOD}}' => $this->renderRequiredFeaturesMethod($context),
                    '{{SEARCHABLE_CONTENT_METHOD}}' => $this->renderSearchableContentMethod($context),
                    '{{USED_FILES_METHOD}}' => $this->renderUsedFilesMethod($context),
                    '{{ON_START_METHOD}}' => $this->renderOnStartMethod($context),
                    '{{ADD_CONTENT}}' => $hasEntries
                        ? $this->indentCode('$this->set(\'entries\', []);', 2)
                        : '',
                    '{{EDIT_CONTENT}}' => $this->combineCode([
                        $this->renderControllerFragments(
                            $context,
                            ControllerMethodSectionEnum::Edit,
                            indentation: 0,
                        ),
                        $hasEntries ? '$this->set(\'entries\', $this->getEntries(\'edit\'));' : '',
                    ], 2),
                    '{{ADD_EDIT_CONTENT}}' => $this->combineCode([
                        $this->renderRequiredAssets($context),
                        '$this->set(\'app\', $this->app);',
                        $this->renderControllerFragments(
                            $context,
                            ControllerMethodSectionEnum::AddEdit,
                            indentation: 0,
                        ),
                    ], 2),
                    '{{VIEW_METHOD}}' => $this->renderViewMethod($context, $hasEntries),
                    '{{SAVE_METHOD}}' => $this->renderSaveMethod($context, $hasEntries),
                    '{{DUPLICATE_METHOD}}' => $hasEntries ? $this->renderDuplicateMethod($context) : '',
                    '{{DELETE_METHOD}}' => $hasEntries ? $this->renderDeleteMethod($context) : '',
                    '{{VALIDATION_METHODS}}' => $hasValidation
                        ? $this->renderValidationMethods($context, $hasEntries)
                        : '',
                    '{{COMPOSER_ASSETS}}' => $this->renderComposerAssets($context),
                    '{{GET_ENTRIES_METHOD}}' => $hasEntries ? $this->renderGetEntriesMethod($context) : '',
                    '{{REPEATABLE_EXPORT_IMPORT_METHODS}}' => $hasEntries
                        ? $this->renderRepeatableExportImportMethods($context)
                        : '',
                    '{{REGISTER_VIEW_ASSETS_METHOD}}' => $this->renderRegisterViewAssetsMethod($context),
                    '{{CUSTOM_CONTROLLER_METHODS}}' => $this->renderCustomControllerMethods($context),
                ])),
                producer: self::class,
            ),
        ];
    }

    private function renderUseStatements(
        BlockFileGenerationContext $context,
        bool $hasEntries,
        bool $usesErrorList,
    ): string {
        $useStatements = [
            'assetlist' => new ControllerUseStatement('Concrete\\Core\\Asset\\AssetList'),
            'blockcontroller' => new ControllerUseStatement('Concrete\\Core\\Block\\BlockController'),
        ];

        if ($usesErrorList) {
            $useStatements['errorlist'] = new ControllerUseStatement('Concrete\\Core\\Error\\ErrorList\\ErrorList');
        }
        if ($hasEntries) {
            $useStatements['connection'] = new ControllerUseStatement('Concrete\\Core\\Database\\Connection\\Connection');
        }
        if ($this->hasRepeatableFileUsage($context)) {
            $useStatements['aggregatetracker'] = new ControllerUseStatement(
                'Concrete\\Core\\Statistics\\UsageTracker\\AggregateTracker',
            );
        }
        foreach ($context->plan->controller->useStatements as $useStatement) {
            $useStatementKey = strtolower($useStatement->getKey());
            $existingUseStatement = $useStatements[$useStatementKey] ?? null;
            if (
                $existingUseStatement !== null
                && (
                    $existingUseStatement->className !== $useStatement->className
                    || $existingUseStatement->alias !== $useStatement->alias
                )
            ) {
                throw new GenerationContributionConflictException(sprintf(
                    'Controller imports "%s" and "%s" both bind the local symbol "%s".',
                    $existingUseStatement->className,
                    $useStatement->className,
                    $useStatement->getKey(),
                ));
            }

            $useStatements[$useStatementKey] = $useStatement;
        }

        $renderedUseStatements = array_map(
            static fn(ControllerUseStatement $useStatement): string => sprintf(
                'use %s%s;',
                $useStatement->className,
                $useStatement->alias !== null ? ' as ' . $useStatement->alias : '',
            ),
            array_values($useStatements),
        );
        sort($renderedUseStatements, SORT_STRING);

        return implode(PHP_EOL, $renderedUseStatements);
    }

    private function renderBlockProperties(BlockFileGenerationContext $context): string
    {
        $config = $context->config;
        $tables = [$context->manifest->databaseTableName];
        if ($config->entries !== []) {
            $tables[] = $context->manifest->entriesDatabaseTableName;
        }

        $properties = [
            'protected $btTable = ' . $this->phpLiteralFormatter->format($context->manifest->databaseTableName) . ';',
            'protected $btExportTables = ' . $this->phpLiteralFormatter->format($tables) . ';',
            'protected $btInterfaceWidth = ' . $config->blockWidth . ';',
            'protected $btInterfaceHeight = ' . $config->blockHeight . ';',
            'protected $btWrapperClass = \'ccm-ui\';',
            'protected $supportSavingNullValues = ' . $this->phpLiteralFormatter->format($config->supportSavingNullValues) . ';',
            'protected $btIgnorePageThemeGridFrameworkContainer = ' . $this->phpLiteralFormatter->format($config->ignorePageThemeGridFrameworkContainer) . ';',
            'protected $btCacheBlockRecord = ' . $this->phpLiteralFormatter->format($config->cacheBlockRecord) . ';',
            'protected $btCacheBlockOutput = ' . $this->phpLiteralFormatter->format($config->cacheBlockOutput) . ';',
            'protected $btCacheBlockOutputOnPost = ' . $this->phpLiteralFormatter->format($config->cacheBlockOutputOnPost) . ';',
            'protected $btCacheBlockOutputOnEditMode = ' . $this->phpLiteralFormatter->format($config->cacheBlockOutputOnEditMode) . ';',
            'protected $btCacheBlockOutputForRegisteredUsers = ' . $this->phpLiteralFormatter->format($config->cacheBlockOutputForRegisteredUsers) . ';',
            'protected $btCacheBlockOutputLifetime = ' . $config->cacheBlockOutputLifetime . ';',
        ];
        if ($config->blockTypeSet !== null && $config->blockTypeSet !== '') {
            $properties[] = 'protected $btDefaultSet = ' . $this->phpLiteralFormatter->format($config->blockTypeSet) . ';';
        }
        if ($context->plan->controller->exportPageColumns !== []) {
            $properties[] = 'protected $btExportPageColumns = '
                . $this->phpLiteralFormatter->format($context->plan->controller->exportPageColumns) . ';';
        }
        if ($context->plan->controller->exportFileColumns !== []) {
            $properties[] = 'protected $btExportFileColumns = '
                . $this->phpLiteralFormatter->format($context->plan->controller->exportFileColumns) . ';';
        }
        if ($context->plan->controller->exportContentColumns !== []) {
            $properties[] = 'protected $btExportContentColumns = '
                . $this->phpLiteralFormatter->format($context->plan->controller->exportContentColumns) . ';';
        }
        if ($context->plan->controller->exportFileFolderColumns !== []) {
            $properties[] = 'protected $btExportFileFolderColumns = '
                . $this->phpLiteralFormatter->format($context->plan->controller->exportFileFolderColumns) . ';';
        }

        return $this->indentCode(implode(PHP_EOL, $properties), 1) . PHP_EOL;
    }

    private function renderImplementedInterfaces(BlockFileGenerationContext $context): string
    {
        if ($context->plan->controller->implementedInterfaces === []) {
            return '';
        }

        return ' implements ' . implode(', ', $context->plan->controller->implementedInterfaces);
    }

    private function renderRequiredFeaturesMethod(BlockFileGenerationContext $context): string
    {
        if ($context->plan->controller->requiredFeatureConstantNames === []) {
            return '';
        }

        $features = array_map(
            static fn(string $featureConstantName): string => '            Features::' . $featureConstantName . ',',
            $context->plan->controller->requiredFeatureConstantNames,
        );

        return PHP_EOL
            . '    public function getRequiredFeatures(): array' . PHP_EOL
            . '    {' . PHP_EOL
            . '        return [' . PHP_EOL
            . implode(PHP_EOL, $features) . PHP_EOL
            . '        ];' . PHP_EOL
            . '    }' . PHP_EOL;
    }

    private function renderFieldProperties(BlockFileGenerationContext $context): string
    {
        foreach ($context->plan->controller->properties as $property) {
            if (in_array(strtolower($property->name), self::RESERVED_GENERATED_PROPERTY_NAMES, true)) {
                throw new GenerationContributionConflictException(sprintf(
                    'Field property "%s" conflicts with generated controller state.',
                    $property->name,
                ));
            }
        }

        $declarations = array_map(
            static fn($property): string => $property->declaration,
            $context->plan->controller->properties,
        );

        return $declarations === []
            ? ''
            : $this->indentCode(implode(PHP_EOL, $declarations), 1) . PHP_EOL;
    }

    private function renderSearchableContentMethod(BlockFileGenerationContext $context): string
    {
        $code = [];
        foreach ($context->plan->controller->searchableBasicFields as $handle) {
            $code[] = sprintf('$content[] = (string) ($this->%s ?? \'\');', $handle);
        }
        if ($context->plan->controller->searchableRepeatableFields !== []) {
            $code[] = 'foreach ($this->getEntries(\'edit\') as $entry) {';
            foreach ($context->plan->controller->searchableRepeatableFields as $handle) {
                $code[] = sprintf(
                    '    $content[] = (string) ($entry[%s] ?? \'\');',
                    $this->phpLiteralFormatter->format($handle),
                );
            }
            $code[] = '}';
        }

        if ($code === []) {
            return '';
        }

        $content = '$content = [];' . PHP_EOL
            . implode(PHP_EOL, $code) . PHP_EOL . PHP_EOL
            . 'return implode(\' \', array_filter(' . PHP_EOL
            . '    $content,' . PHP_EOL
            . '    static fn(mixed $value): bool => $value !== null && $value !== \'\',' . PHP_EOL
            . '));';

        return $this->renderMethod('public function getSearchableContent(): string', $content);
    }

    private function renderOnStartMethod(BlockFileGenerationContext $context): string
    {
        $content = $this->renderControllerFragments(
            $context,
            ControllerMethodSectionEnum::OnStart,
            indentation: 0,
        );
        if ($content === '') {
            return '';
        }

        return $this->renderMethod(
            'public function on_start(): void',
            'parent::on_start();' . PHP_EOL . PHP_EOL . $content,
        );
    }

    private function renderControllerFragments(
        BlockFileGenerationContext $context,
        ControllerMethodSectionEnum $section,
        int $indentation = 2,
    ): string {
        return $this->formatFragments(
            $context->plan->controller->getMethodFragments($section->value),
            $indentation,
        );
    }

    private function renderUsedFilesMethod(BlockFileGenerationContext $context): string
    {
        $basicFieldCode = $this->renderControllerFragments(
            $context,
            ControllerMethodSectionEnum::CollectUsedFilesFromBasicFields,
            indentation: 0,
        );
        $entryCode = $this->renderControllerFragments(
            $context,
            ControllerMethodSectionEnum::CollectUsedFilesFromEntry,
            indentation: 0,
        );
        if ($basicFieldCode === '' && $entryCode === '') {
            return '';
        }

        $parts = ['$files = [];'];
        if ($basicFieldCode !== '') {
            $parts[] = $basicFieldCode;
        }
        if ($entryCode !== '') {
            $parts[] = 'foreach ($this->getEntries(\'raw\') as $entry) {' . PHP_EOL
                . $this->indentCode($entryCode, 1) . PHP_EOL
                . '}';
        }
        $parts[] = 'return $files;';

        return $this->renderMethod(
            'public function getUsedFiles(): array',
            $this->combineCode($parts, 0),
        );
    }

    private function renderViewMethod(BlockFileGenerationContext $context, bool $hasEntries): string
    {
        $parts = [
            $this->renderControllerFragments(
                $context,
                ControllerMethodSectionEnum::View,
                indentation: 0,
            ),
            $hasEntries ? '$this->set(\'entries\', $this->getEntries());' : '',
            trim((string) $context->config->viewCustomCode),
        ];
        if (array_filter($parts, static fn(string $part): bool => $part !== '') === []) {
            return '';
        }
        array_unshift($parts, '$this->set(\'app\', $this->app);');

        return $this->renderMethod(
            'public function view(): void',
            $this->combineCode($parts, 0),
        );
    }

    private function renderSaveMethod(BlockFileGenerationContext $context, bool $hasEntries): string
    {
        $basicFieldCode = $this->renderControllerFragments(
            $context,
            ControllerMethodSectionEnum::SaveBasicFields,
            indentation: 0,
        );
        if ($basicFieldCode === '' && !$hasEntries) {
            return '';
        }

        $content = '$args = is_array($args) ? $args : [];' . PHP_EOL . PHP_EOL
            . $this->combineCode([
                $basicFieldCode,
                $hasEntries ? $this->renderSaveEntries($context) : 'parent::save($args);',
            ], 0);

        return $this->renderMethod('public function save($args): void', $content);
    }

    private function renderSaveEntries(BlockFileGenerationContext $context): string
    {
        $entryFragments = $this->formatFragments(
            $context->plan->controller->getMethodFragments(ControllerMethodSectionEnum::SaveEntryFields->value),
            0,
        );
        $maximumGuard = $context->config->maxNumberOfEntries > 0
            ? sprintf(
                'if (count($entries) > %1$d) {%2$s    throw new \\InvalidArgumentException(t(\'A maximum of %%s entries is allowed.\', %1$d));%2$s}',
                $context->config->maxNumberOfEntries,
                PHP_EOL,
            )
            : '';
        $code = <<<'PHP'

$database = $this->app->make(Connection::class);
if (array_key_exists('entry', $args) && !is_array($args['entry'])) {
    throw new \InvalidArgumentException(t('The repeatable entry collection must be an array.'));
}
$entries = isset($args['entry']) ? array_values($args['entry']) : [];
{{MAXIMUM_GUARD}}
foreach ($entries as $entry) {
    if (!is_array($entry)) {
        throw new \InvalidArgumentException(t('Every repeatable entry must be an array.'));
    }
}

$database->transactional(function (Connection $database) use ($args, $entries): void {
    parent::save($args);
    $database->executeStatement('DELETE FROM {{TABLE}} WHERE bID = ?', [$this->bID]);

    foreach ($entries as $entryPosition => $entry) {
        $data = [
            'bID' => $this->bID,
            'position' => $entryPosition + 1,
        ];
{{ENTRY_FIELDS}}

        $database->insert('{{TABLE}}', $data);
    }
});
PHP;

        $code = str_replace(
            ['{{TABLE}}', '{{MAXIMUM_GUARD}}', '{{ENTRY_FIELDS}}'],
            [$context->manifest->entriesDatabaseTableName, $maximumGuard, $this->indentCode($entryFragments, 2)],
            trim($code),
        );
        if ($this->hasRepeatableFileUsage($context)) {
            $code .= <<<'PHP'


$aggregateUsageTracker = $this->app->make(AggregateTracker::class);
$aggregateUsageTracker->forget($this);
$aggregateUsageTracker->track($this);
PHP;
        }

        return $code;
    }

    private function renderDuplicateMethod(BlockFileGenerationContext $context): string
    {
        $columns = ['position'];
        foreach ($context->plan->database->entriesTableColumns as $column) {
            if (!in_array($column->name, ['id', 'bID', 'position'], true)) {
                $columns[] = $column->name;
            }
        }
        $columnList = implode(', ', $columns);
        $code = sprintf(
            '$database = $this->app->make(Connection::class);%1$s$database->transactional(function (Connection $database) use ($newBID): void {%1$s    parent::duplicate($newBID);%1$s    $database->executeStatement(%1$s        %2$s,%1$s        [$newBID, $this->bID],%1$s    );%1$s});',
            PHP_EOL,
            $this->phpLiteralFormatter->format(sprintf(
                'INSERT INTO %1$s (bID, %2$s) SELECT ?, %2$s FROM %1$s WHERE bID = ?',
                $context->manifest->entriesDatabaseTableName,
                $columnList,
            )),
        );

        return $this->renderMethod('public function duplicate($newBID): void', $code);
    }

    private function renderDeleteMethod(BlockFileGenerationContext $context): string
    {
        $code = sprintf(
            '$database = $this->app->make(Connection::class);%1$s$database->transactional(function (Connection $database): void {%1$s    $database->executeStatement(%2$s, [$this->bID]);%1$s    parent::delete();%1$s});',
            PHP_EOL,
            $this->phpLiteralFormatter->format(
                'DELETE FROM ' . $context->manifest->entriesDatabaseTableName . ' WHERE bID = ?',
            ),
        );

        return $this->renderMethod('public function delete(): void', $code);
    }

    private function renderValidation(BlockFileGenerationContext $context, bool $hasEntries): string
    {
        $parts = [
            $this->formatFragments(
                $context->plan->controller->getMethodFragments(ControllerMethodSectionEnum::ValidateBasicFields->value),
                0,
            ),
        ];
        if ($hasEntries) {
            $entryValidation = $this->formatFragments(
                $context->plan->controller->getMethodFragments(ControllerMethodSectionEnum::ValidateEntryFields->value),
                0,
            );
            $maximumValidation = $context->config->maxNumberOfEntries > 0
                ? sprintf(
                    'if (count($entries) > %1$d) {%2$s    $errors->add(t(\'A maximum of %%s entries is allowed.\', %1$d));%2$s}',
                    $context->config->maxNumberOfEntries,
                    PHP_EOL,
                )
                : '';
            $entriesCode = <<<'PHP'
if (array_key_exists('entry', $args) && !is_array($args['entry'])) {
    $errors->add(t('The repeatable entry collection must be an array.'));
    $entries = [];
} else {
    $entries = isset($args['entry']) ? array_values($args['entry']) : [];
}
{{MAXIMUM_VALIDATION}}
foreach ($entries as $entryPosition => $entry) {
    if (!is_array($entry)) {
        $errors->add(t('Every repeatable entry must be an array.'));
        continue;
    }
{{ENTRY_VALIDATION}}
}
PHP;
            $parts[] = str_replace(
                ['{{MAXIMUM_VALIDATION}}', '{{ENTRY_VALIDATION}}'],
                [$maximumValidation, $this->indentCode($entryValidation, 1)],
                $entriesCode,
            );
        }

        return $this->combineCode($parts, 0);
    }

    private function renderComposerValidation(
        BlockFileGenerationContext $context,
        bool $hasEntries,
    ): string {
        $basicFieldColumns = array_filter(
            $context->plan->database->mainTableColumns,
            static fn(DatabaseColumn $column): bool => $column->name !== 'bID',
        );
        if ($basicFieldColumns === []) {
            $code = '$args = [];';
        } else {
            $argumentLines = array_map(
                fn(DatabaseColumn $column): string => sprintf(
                    '    %s => $this->%s ?? null,',
                    $this->phpLiteralFormatter->format($column->name),
                    $column->name,
                ),
                $basicFieldColumns,
            );
            $code = '$args = [' . PHP_EOL
                . implode(PHP_EOL, $argumentLines) . PHP_EOL
                . '];';
        }
        if ($hasEntries) {
            $code .= PHP_EOL . '$args[\'entry\'] = $this->getEntries(\'edit\');';
        }
        $code .= PHP_EOL . PHP_EOL . 'return $this->validate($args);';

        return $code;
    }

    private function renderValidationMethods(BlockFileGenerationContext $context, bool $hasEntries): string
    {
        $validateMethod = $this->renderMethod(
            'public function validate($args): ErrorList',
            '$args = is_array($args) ? $args : [];' . PHP_EOL
                . '$errors = $this->app->make(ErrorList::class);' . PHP_EOL
                . $this->renderValidation($context, $hasEntries) . PHP_EOL . PHP_EOL
                . 'return $errors;',
        );
        $composerValidationMethod = $this->renderMethod(
            'public function validate_composer(): ErrorList',
            $this->renderComposerValidation($context, $hasEntries),
        );

        return $validateMethod . $composerValidationMethod;
    }

    private function renderComposerAssets(BlockFileGenerationContext $context): string
    {
        $assetHandle = $context->manifest->blockHandleKebabCase . '/auto-js';
        $code = sprintf(
            '$assetList = AssetList::getInstance();%1$s$assetList->register(\'javascript\', %2$s, %3$s);%1$s$this->requireAsset(\'javascript\', %2$s);',
            PHP_EOL,
            $this->phpLiteralFormatter->format($assetHandle),
            $this->phpLiteralFormatter->format('blocks/' . $context->config->blockHandle . '/auto.js'),
        );

        return $this->indentCode($code, 2) . PHP_EOL;
    }

    private function renderRequiredAssets(BlockFileGenerationContext $context): string
    {
        return implode(
            PHP_EOL,
            array_map(
                fn(ControllerAsset $asset): string => $asset->handle === null
                    ? sprintf('$this->requireAsset(%s);', $this->phpLiteralFormatter->format($asset->type))
                    : sprintf(
                        '$this->requireAsset(%s, %s);',
                        $this->phpLiteralFormatter->format($asset->type),
                        $this->phpLiteralFormatter->format($asset->handle),
                    ),
                $context->plan->controller->assets,
            ),
        );
    }

    private function renderGetEntriesMethod(BlockFileGenerationContext $context): string
    {
        $prepareForEdit = $this->renderControllerFragments(
            $context,
            ControllerMethodSectionEnum::PrepareEntryForEdit,
            indentation: 0,
        );
        $prepareForView = $this->renderControllerFragments(
            $context,
            ControllerMethodSectionEnum::PrepareEntryForView,
            indentation: 0,
        );
        $preparation = '';
        if ($prepareForEdit !== '' || $prepareForView !== '') {
            $preparation = sprintf(
                'foreach ($entries as &$entry) {%1$s    if ($outputMethod === \'edit\') {%1$s%2$s%1$s    } elseif ($outputMethod === \'view\') {%1$s%3$s%1$s    }%1$s}%1$sunset($entry);',
                PHP_EOL,
                $this->indentCode($prepareForEdit, 2),
                $this->indentCode($prepareForView, 2),
            );
        }
        $code = sprintf(
            '$database = $this->app->make(Connection::class);%1$s$entries = $database->fetchAllAssociative(%1$s    %2$s,%1$s    [$this->bID],%1$s);%1$s%3$s%1$sreturn $entries;',
            PHP_EOL,
            $this->phpLiteralFormatter->format(
                'SELECT * FROM ' . $context->manifest->entriesDatabaseTableName . ' WHERE bID = ? ORDER BY position, id',
            ),
            $preparation,
        );

        return $this->renderMethod(
            'private function getEntries(string $outputMethod = \'view\'): array',
            $code,
        );
    }

    private function hasRepeatableFileUsage(BlockFileGenerationContext $context): bool
    {
        return $context->plan->controller->getMethodFragments(
            ControllerMethodSectionEnum::CollectUsedFilesFromEntry->value,
        ) !== [];
    }

    private function renderRepeatableExportImportMethods(BlockFileGenerationContext $context): string
    {
        $autoIncrementColumnXPathExpressions = [];
        foreach ($context->plan->database->entriesTableColumns as $column) {
            if ($column->autoIncrement) {
                $autoIncrementColumnXPathExpressions[] = sprintf(
                    './data[@table="%s"]/record/%s',
                    $context->manifest->entriesDatabaseTableName,
                    $column->name,
                );
            }
        }

        if ($autoIncrementColumnXPathExpressions === []) {
            return '';
        }

        $code = <<<'PHP'

    public function export($blockNode): void
    {
        parent::export($blockNode);
        $this->removeRepeatableAutoIncrementValues($blockNode);
    }

    protected function importAdditionalData($b, $blockNode): void
    {
        $this->removeRepeatableAutoIncrementValues($blockNode);
        parent::importAdditionalData($b, $blockNode);
    }

    private function removeRepeatableAutoIncrementValues(\SimpleXMLElement $blockNode): void
    {
        foreach ({{AUTO_INCREMENT_COLUMN_XPATHS}} as $columnXPathExpression) {
            $columnNodes = $blockNode->xpath($columnXPathExpression);
            if ($columnNodes === false) {
                continue;
            }

            foreach ($columnNodes as $columnNode) {
                unset($columnNode[0]);
            }
        }
    }
PHP;

        $formattedXPathExpressions = count($autoIncrementColumnXPathExpressions) === 1
            ? sprintf(
                '[%s]',
                $this->phpLiteralFormatter->format($autoIncrementColumnXPathExpressions[0]),
            )
            : str_replace(
                PHP_EOL,
                PHP_EOL . '        ',
                $this->phpLiteralFormatter->format($autoIncrementColumnXPathExpressions),
            );

        return str_replace(
            '{{AUTO_INCREMENT_COLUMN_XPATHS}}',
            $formattedXPathExpressions,
            $code,
        );
    }

    private function renderRegisterViewAssetsMethod(BlockFileGenerationContext $context): string
    {
        $methodCode = $this->combineCode([
            $this->renderControllerFragments(
                $context,
                ControllerMethodSectionEnum::RegisterViewAssets,
                indentation: 0,
            ),
            (string) $context->config->registerViewAssetsCustomCode,
        ], 2);
        if ($methodCode === '') {
            return '';
        }

        return PHP_EOL
            . '    public function registerViewAssets($outputContent = \'\'): void' . PHP_EOL
            . '    {' . PHP_EOL
            . $methodCode . PHP_EOL
            . '    }' . PHP_EOL;
    }

    private function renderCustomControllerMethods(BlockFileGenerationContext $context): string
    {
        $parts = [];
        $additionalFragments = $this->formatFragments(
            $context->plan->controller->getMethodFragments(ControllerMethodSectionEnum::AdditionalMethods->value),
            0,
        );
        if ($additionalFragments !== '') {
            $parts[] = $additionalFragments;
        }
        if (trim((string) $context->config->customControllerMethods) !== '') {
            $parts[] = trim((string) $context->config->customControllerMethods);
        }

        return $parts === [] ? '' : PHP_EOL . $this->combineCode($parts, 1) . PHP_EOL;
    }

    /**
     * @param CodeFragment[] $fragments
     */
    private function formatFragments(array $fragments, int $indentation): string
    {
        $code = implode(
            PHP_EOL . PHP_EOL,
            array_map(static fn(CodeFragment $fragment): string => trim($fragment->code), $fragments),
        );

        return $this->indentCode($code, $indentation);
    }

    private function combineCode(array $parts, int $indentation): string
    {
        $code = implode(
            PHP_EOL . PHP_EOL,
            array_values(array_filter(array_map('trim', $parts), static fn(string $part): bool => $part !== '')),
        );

        return $this->indentCode($code, $indentation);
    }

    private function renderMethod(string $declaration, string $content): string
    {
        return PHP_EOL
            . '    ' . $declaration . PHP_EOL
            . '    {' . PHP_EOL
            . $this->indentCode($content, 2) . PHP_EOL
            . '    }' . PHP_EOL;
    }

    private function indentCode(string $code, int $indentation): string
    {
        if (trim($code) === '') {
            return '';
        }

        $indent = str_repeat('    ', $indentation);

        return implode(
            PHP_EOL,
            array_map(
                static fn(string $line): string => $line === '' ? '' : $indent . rtrim($line),
                explode(PHP_EOL, trim($code)),
            ),
        );
    }

    private function normalizeControllerWhitespace(string $code): string
    {
        $tokens = token_get_all($code);
        $normalizedCode = '';

        foreach ($tokens as $tokenIndex => $token) {
            if (!is_array($token) || $token[0] !== T_WHITESPACE) {
                $normalizedCode .= is_array($token) ? $token[1] : $token;
                continue;
            }

            $whitespace = str_replace(["\r\n", "\r"], "\n", $token[1]);
            $whitespace = preg_replace('/\n(?:[ \t]*\n){2,}/', "\n\n", $whitespace) ?? $whitespace;

            if (($tokens[$tokenIndex + 1] ?? null) === '}') {
                $whitespace = preg_replace(
                    '/\n(?:[ \t]*\n)+([ \t]*)$/',
                    "\n$1",
                    $whitespace,
                ) ?? $whitespace;
            }

            $normalizedCode .= $whitespace;
        }

        return $normalizedCode;
    }
}
