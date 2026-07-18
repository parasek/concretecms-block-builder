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
        'uniqueid',
        'bttable',
        'btexporttables',
        'btinterfacewidth',
        'btinterfaceheight',
        'btwrapperclass',
        'btdefaultset',
        'btexportpagecolumns',
        'btexportfilecolumns',
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
     * @return iterable<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): iterable
    {
        $hasEntries = $context->config->entries !== [];
        $hasJavaScript = $context->plan->javaScript->capabilities !== []
            || $context->plan->javaScript->fragmentsBySection !== [];
        $hasFormCss = $context->plan->css->capabilities !== []
            || $context->plan->css->fragmentsBySection !== [];

        yield new GeneratedTextFile(
            relativePath: FILENAME_BLOCK_CONTROLLER,
            contents: $this->stubRenderer->render(self::STUB_FILE, [
                '{{BLOCK_HANDLE_PASCAL_CASE}}' => $context->manifest->blockHandlePascalCase,
                '{{USE_STATEMENTS}}' => $this->renderUseStatements(
                    $context,
                    $hasEntries,
                    $hasJavaScript || $hasFormCss,
                ),
                '{{BLOCK_PROPERTIES}}' => $this->renderBlockProperties($context),
                '{{FIELD_PROPERTIES}}' => $this->renderFieldProperties($context),
                '{{BLOCK_NAME_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->blockName),
                '{{BLOCK_DESCRIPTION_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->blockDescription ?? ''),
                '{{SEARCHABLE_CONTENT}}' => $this->renderSearchableContent($context),
                '{{ON_START_CONTENT}}' => $this->renderControllerFragments($context, ControllerMethodSectionEnum::OnStart),
                '{{EDIT_CONTENT}}' => $this->combineCode([
                    $this->renderControllerFragments(
                        $context,
                        ControllerMethodSectionEnum::Edit,
                        indentation: 0,
                    ),
                    $hasEntries ? '$this->set(\'entries\', $this->getEntries(\'edit\'));' : '',
                ], 2),
                '{{ADD_EDIT_CONTENT}}' => $this->combineCode([
                    $hasFormCss ? $this->renderFormCssAsset($context) : '',
                    $this->renderRequiredAssets($context),
                    '$this->set(\'app\', $this->app);',
                    $this->renderControllerFragments(
                        $context,
                        ControllerMethodSectionEnum::AddEdit,
                        indentation: 0,
                    ),
                ], 2),
                '{{VIEW_CONTENT}}' => $this->combineCode([
                    $this->renderControllerFragments($context, ControllerMethodSectionEnum::View, indentation: 0),
                    $hasEntries ? '$this->set(\'entries\', $this->getEntries());' : '',
                    $context->config->viewCustomCode ?? '',
                ], 2),
                '{{SAVE_CONTENT}}' => $this->renderSaveContent($context, $hasEntries),
                '{{DUPLICATE_CONTENT}}' => $this->renderDuplicateContent($context, $hasEntries),
                '{{DELETE_CONTENT}}' => $this->renderDeleteContent($context, $hasEntries),
                '{{VALIDATE_CONTENT}}' => $this->renderValidation($context, $hasEntries),
                '{{COMPOSER_ASSETS}}' => $hasJavaScript ? $this->renderComposerAssets($context) : '',
                '{{GET_ENTRIES_CONTENT}}' => $hasEntries ? $this->renderGetEntries($context) : $this->indentCode('return [];', 2),
                '{{REGISTER_VIEW_ASSETS_METHOD}}' => $this->renderRegisterViewAssetsMethod($context),
                '{{CUSTOM_CONTROLLER_METHODS}}' => $this->renderCustomControllerMethods($context),
            ]),
            producer: self::class,
        );
    }

    private function renderUseStatements(
        BlockFileGenerationContext $context,
        bool $hasEntries,
        bool $usesAssetList,
    ): string {
        $useStatements = [
            'blockcontroller' => new ControllerUseStatement('Concrete\\Core\\Block\\BlockController'),
            'errorlist' => new ControllerUseStatement('Concrete\\Core\\Error\\ErrorList\\ErrorList'),
        ];

        if ($hasEntries) {
            $useStatements['connection'] = new ControllerUseStatement('Concrete\\Core\\Database\\Connection\\Connection');
        }
        if ($usesAssetList) {
            $useStatements['assetlist'] = new ControllerUseStatement('Concrete\\Core\\Asset\\AssetList');
        }
        foreach ($context->plan->controller->useStatements as $useStatement) {
            $useStatementKey = strtolower($useStatement->getKey());
            if (isset($useStatements[$useStatementKey]) && $useStatements[$useStatementKey] != $useStatement) {
                throw new GenerationContributionConflictException(sprintf(
                    'Controller imports "%s" and "%s" both bind the local symbol "%s".',
                    $useStatements[$useStatementKey]->className,
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

        return $this->indentCode(implode(PHP_EOL, $properties), 1) . PHP_EOL;
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

    private function renderSearchableContent(BlockFileGenerationContext $context): string
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

        return $this->indentCode(implode(PHP_EOL, $code), 2);
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

    private function renderSaveContent(BlockFileGenerationContext $context, bool $hasEntries): string
    {
        return $this->combineCode([
            $this->renderControllerFragments(
                $context,
                ControllerMethodSectionEnum::SaveBasicFields,
                indentation: 0,
            ),
            $hasEntries ? $this->renderSaveEntries($context) : 'parent::save($args);',
        ], 2);
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

        return $code;
    }

    private function renderDuplicateContent(BlockFileGenerationContext $context, bool $hasEntries): string
    {
        if (!$hasEntries) {
            return $this->indentCode('parent::duplicate($newBlockId);', 2);
        }

        $columns = ['position'];
        foreach ($context->plan->database->entriesTableColumns as $column) {
            if (!in_array($column->name, ['id', 'bID', 'position'], true)) {
                $columns[] = $column->name;
            }
        }
        $columnList = implode(', ', $columns);
        $code = sprintf(
            '$database = $this->app->make(Connection::class);%1$s$database->transactional(function (Connection $database) use ($newBlockId): void {%1$s    parent::duplicate($newBlockId);%1$s    $database->executeStatement(%1$s        %2$s,%1$s        [$newBlockId, $this->bID],%1$s    );%1$s});',
            PHP_EOL,
            $this->phpLiteralFormatter->format(sprintf(
                'INSERT INTO %1$s (bID, %2$s) SELECT ?, %2$s FROM %1$s WHERE bID = ?',
                $context->manifest->entriesDatabaseTableName,
                $columnList,
            )),
        );

        return $this->indentCode($code, 2);
    }

    private function renderDeleteContent(BlockFileGenerationContext $context, bool $hasEntries): string
    {
        if (!$hasEntries) {
            return $this->indentCode('parent::delete();', 2);
        }

        $code = sprintf(
            '$database = $this->app->make(Connection::class);%1$s$database->transactional(function (Connection $database): void {%1$s    $database->executeStatement(%2$s, [$this->bID]);%1$s    parent::delete();%1$s});',
            PHP_EOL,
            $this->phpLiteralFormatter->format(
                'DELETE FROM ' . $context->manifest->entriesDatabaseTableName . ' WHERE bID = ?',
            ),
        );

        return $this->indentCode($code, 2);
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

        return $this->combineCode($parts, 2);
    }

    private function renderComposerAssets(BlockFileGenerationContext $context): string
    {
        $assetHandle = $context->manifest->blockHandleKebabCase . '/auto-js';
        $code = sprintf(
            '$assetList = AssetList::getInstance();%1$s$assetList->register(\'javascript\', %2$s, %3$s, [], false);%1$s$this->requireAsset(\'javascript\', %2$s);',
            PHP_EOL,
            $this->phpLiteralFormatter->format($assetHandle),
            $this->phpLiteralFormatter->format('blocks/' . $context->config->blockHandle . '/auto.js'),
        );

        return $this->indentCode($code, 2) . PHP_EOL;
    }

    private function renderFormCssAsset(BlockFileGenerationContext $context): string
    {
        $assetHandle = $context->manifest->blockHandleKebabCase . '/form';

        return sprintf(
            '$assetList = AssetList::getInstance();%1$s$assetList->register(\'css\', %2$s, %3$s, [], false);%1$s$this->requireAsset(\'css\', %2$s);',
            PHP_EOL,
            $this->phpLiteralFormatter->format($assetHandle),
            $this->phpLiteralFormatter->format('blocks/' . $context->config->blockHandle . '/css_files/form.css'),
        );
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

    private function renderGetEntries(BlockFileGenerationContext $context): string
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
                'foreach ($entries as &$entry) {%1$s    if ($outputMethod === \'edit\') {%1$s%2$s%1$s    } else {%1$s%3$s%1$s    }%1$s}%1$sunset($entry);',
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

        return $this->indentCode($code, 2);
    }

    private function renderRegisterViewAssetsMethod(BlockFileGenerationContext $context): string
    {
        $customCode = trim((string) $context->config->registerViewAssetsCustomCode);
        if ($customCode === '') {
            return '';
        }

        return PHP_EOL
            . '    public function registerViewAssets($outputContent = \'\'): void' . PHP_EOL
            . '    {' . PHP_EOL
            . $this->indentCode($customCode, 2) . PHP_EOL
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
}
