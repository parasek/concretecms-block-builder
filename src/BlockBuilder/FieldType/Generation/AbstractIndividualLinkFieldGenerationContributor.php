<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Generation;

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
use BlockBuilder\FieldType\Type\ExternalLink\ExternalLinkFieldTypeDto;
use BlockBuilder\FieldType\Type\LinkFromFileManager\LinkFromFileManagerFieldTypeDto;
use BlockBuilder\FieldType\Type\LinkFromSitemap\LinkFromSitemapFieldTypeDto;

abstract readonly class AbstractIndividualLinkFieldGenerationContributor implements FieldGenerationContributorInterface
{
    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
        private LinkFieldGenerationSupport $linkFieldGenerationSupport,
    ) {
    }

    final public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $expectedDtoClass = match ($this->getFieldType()) {
            FieldTypeEnum::LinkFromSitemap => LinkFromSitemapFieldTypeDto::class,
            FieldTypeEnum::LinkFromFileManager => LinkFromFileManagerFieldTypeDto::class,
            FieldTypeEnum::ExternalLink => ExternalLinkFieldTypeDto::class,
            default => throw new InvalidFieldGenerationDtoException(sprintf('Field type "%s" is not an individual link field.', $this->getFieldType()->value)),
        };
        if (!$context->fieldDto instanceof $expectedDtoClass) {
            throw new InvalidFieldGenerationDtoException(sprintf('Field type "%s" generation requires DTO "%s"; "%s" was provided.', $this->getFieldType()->value, $expectedDtoClass, $context->fieldDto::class));
        }

        /** @var ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field */
        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;
        $storedFields = $this->getStoredFields($field);

        $this->linkFieldGenerationSupport->contributeControllerCode($planBuilder);
        $this->contributeDatabaseColumns($context, $planBuilder, $storedFields);
        $this->contributeControllerMetadata($context, $planBuilder, $field);

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($context, $planBuilder, $field, $storedFields, $fragmentKeyPrefix);
        } else {
            $this->contributeRepeatableControllerCode($context, $planBuilder, $field, $storedFields, $fragmentKeyPrefix);
            foreach ($storedFields as $storedField) {
                $planBuilder->form->addRepeatableDefaultValue(
                    $storedField['name'],
                    $this->getDefaultValue($storedField['kind']),
                );
            }
        }

        $this->contributeViewDocumentation($context, $planBuilder, $field, $storedFields);
        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($context, $field),
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

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     *
     * @return list<array{name: string, kind: string}>
     */
    private function getStoredFields(object $field): array
    {
        $fields = [[
            'name' => $field->handle,
            'kind' => $this->getFieldType() === FieldTypeEnum::ExternalLink ? 'string' : 'identifier',
        ]];
        if ($this->getFieldType() === FieldTypeEnum::ExternalLink) {
            $fields[] = ['name' => $field->handle . '_protocol', 'kind' => 'protocol'];
        }
        foreach ([
            'showEndingField' => ['_ending', 'string'],
            'showTextField' => ['_text', 'string'],
            'showTitleField' => ['_title', 'string'],
            'showNewWindowField' => ['_new_window', 'boolean'],
            'showNoFollowField' => ['_no_follow', 'boolean'],
        ] as $option => [$suffix, $kind]) {
            if ($field->{$option}) {
                $fields[] = ['name' => $field->handle . $suffix, 'kind' => $kind];
            }
        }

        return $fields;
    }

    /**
     * @param list<array{name: string, kind: string}> $storedFields
     */
    private function contributeDatabaseColumns(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
        array $storedFields,
    ): void {
        foreach ($storedFields as $storedField) {
            $kind = $storedField['kind'];
            $planBuilder->database->addColumn(
                $context->fieldContext,
                new DatabaseColumn(
                    name: $storedField['name'],
                    type: in_array($kind, ['identifier', 'boolean'], true) ? 'integer' : 'string',
                    size: in_array($kind, ['identifier', 'boolean'], true)
                        ? null
                        : ($kind === 'protocol' ? '20' : '255'),
                    unsigned: in_array($kind, ['identifier', 'boolean'], true),
                    hasDefault: in_array($kind, ['identifier', 'boolean', 'protocol'], true),
                    defaultValue: match ($kind) {
                        'identifier', 'boolean' => 0,
                        'protocol' => 'https://',
                        default => null,
                    },
                    order: $context->position,
                ),
            );
        }
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     */
    private function contributeControllerMetadata(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
        object $field,
    ): void {
        if ($this->getFieldType() === FieldTypeEnum::LinkFromSitemap) {
            $planBuilder->controller
                ->addExportPageColumn($field->handle);
        } elseif ($this->getFieldType() === FieldTypeEnum::LinkFromFileManager) {
            $planBuilder->controller
                ->addExportFileColumn($field->handle)
                ->addFileUsageFragment(
                    $context->fieldContext,
                    new CodeFragment(
                        key: $context->fieldContext->value . '.' . $field->handle,
                        code: $context->isBasicField()
                            ? sprintf(
                                'if ((int) ($this->%1$s ?? 0) > 0) {%2$s    $files[] = (int) $this->%1$s;%2$s}',
                                $field->handle,
                                PHP_EOL,
                            )
                            : sprintf(
                                'if ((int) ($entry[%1$s] ?? 0) > 0) {%2$s    $files[] = (int) $entry[%1$s];%2$s}',
                                $this->phpLiteralFormatter->format($field->handle),
                                PHP_EOL,
                            ),
                        order: $context->position,
                    ),
                );
        }
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     * @param list<array{name: string, kind: string}> $storedFields
     */
    private function contributeBasicControllerCode(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
        object $field,
        array $storedFields,
        string $fragmentKeyPrefix,
    ): void {
        $addEditLines = [];
        foreach ($storedFields as $storedField) {
            $planBuilder->controller->addProperty(new ControllerProperty(
                name: $storedField['name'],
                declaration: sprintf(
                    'protected %s $%s = null;',
                    in_array($storedField['kind'], ['identifier', 'boolean'], true)
                        ? 'int|string|null'
                        : '?string',
                    $storedField['name'],
                ),
                order: $context->position,
            ));
            $addEditLines[] = sprintf(
                '$this->set(%s, $this->%s);',
                $this->phpLiteralFormatter->format($storedField['name']),
                $storedField['name'],
            );
        }

        $planBuilder->controller
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: implode(PHP_EOL, $addEditLines),
                    order: $context->position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::View->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderBasicViewPreparation($field, $storedFields),
                    order: $context->position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode('$args', '$args', $storedFields),
                    order: $context->position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, 'args'),
                    order: $context->position,
                ),
            );
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     * @param list<array{name: string, kind: string}> $storedFields
     */
    private function contributeRepeatableControllerCode(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
        object $field,
        array $storedFields,
        string $fragmentKeyPrefix,
    ): void {
        $planBuilder->controller
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode('$data', '$entry', $storedFields),
                    order: $context->position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, 'entry', true),
                    order: $context->position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForView->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderRepeatableViewPreparation($field, $storedFields),
                    order: $context->position,
                ),
            );
    }

    /**
     * @param list<array{name: string, kind: string}> $storedFields
     */
    private function renderSaveCode(string $target, string $source, array $storedFields): string
    {
        $lines = [];
        foreach ($storedFields as $storedField) {
            $key = $this->phpLiteralFormatter->format($storedField['name']);
            $sourceExpression = sprintf('%s[%s]', $source, $key);
            $lines[] = match ($storedField['kind']) {
                'identifier' => sprintf(
                    '%1$s[%2$s] = isset(%3$s) && is_scalar(%3$s) ? max(0, (int) %3$s) : 0;',
                    $target,
                    $key,
                    $sourceExpression,
                ),
                'boolean' => sprintf('%s[%s] = !empty(%s) ? 1 : 0;', $target, $key, $sourceExpression),
                'protocol' => sprintf(
                    '$protocol = isset(%1$s) && is_scalar(%1$s) ? trim((string) %1$s) : \'https://\';%2$s'
                    . '%3$s[%4$s] = in_array($protocol, [\'http://\', \'https://\', \'BASE_URL\', \'CURRENT_PAGE\', \'other\'], true)%2$s'
                    . '    ? $protocol%2$s'
                    . '    : \'other\';',
                    $sourceExpression,
                    PHP_EOL,
                    $target,
                    $key,
                ),
                default => sprintf(
                    '%1$s[%2$s] = isset(%3$s) && is_scalar(%3$s) ? trim((string) %3$s) : \'\';',
                    $target,
                    $key,
                    $sourceExpression,
                ),
            };
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     */
    private function renderValidationCode(object $field, string $source, bool $repeatable = false): string
    {
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $position = $repeatable ? ', $entryPosition + 1' : '';
        $suffix = $repeatable ? ' in entry %s' : '';

        return sprintf(
            '$linkValidationError = $this->%1$s(%2$s, %3$s);%4$s'
            . 'if ($linkValidationError === \'required\') {%4$s'
            . '    $errors->add(t(\'The field "%%s" is required%5$s.\', %6$s%7$s));%4$s'
            . '} elseif ($linkValidationError === \'missing_destination\') {%4$s'
            . '    $errors->add(t(\'The field "%%s" requires a valid link destination%5$s.\', %6$s%7$s));%4$s'
            . '} elseif ($linkValidationError === \'too_long\') {%4$s'
            . '    $errors->add(t(\'The field "%%s" contains a value longer than 255 characters%5$s.\', %6$s%7$s));%4$s'
            . '} elseif ($linkValidationError !== null) {%4$s'
            . '    $errors->add(t(\'The field "%%s" contains an invalid link%5$s.\', %6$s%7$s));%4$s'
            . '}',
            LinkFieldGenerationSupport::VALIDATE_METHOD,
            $this->renderLinkDataArray($field, $source),
            $field->required ? 'true' : 'false',
            PHP_EOL,
            $suffix,
            $label,
            $position,
        );
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     */
    private function renderLinkDataArray(object $field, string $source): string
    {
        $value = fn (string $name): string => match ($source) {
            'properties' => sprintf('$this->%s ?? null', $name),
            'args' => sprintf('$args[%s] ?? null', $this->phpLiteralFormatter->format($name)),
            'entry' => sprintf('$entry[%s] ?? null', $this->phpLiteralFormatter->format($name)),
        };
        $handle = $field->handle;
        $destinationProperty = match ($this->getFieldType()) {
            FieldTypeEnum::LinkFromSitemap => 'link_from_sitemap',
            FieldTypeEnum::LinkFromFileManager => 'link_from_file_manager',
            FieldTypeEnum::ExternalLink => 'external_link',
            default => '',
        };
        $destinationValue = $value($handle);
        $linkType = $this->phpLiteralFormatter->format($this->getFieldType()->value);
        $values = [
            'link_type' => sprintf('!empty(%s) ? %s : \'\',', $destinationValue, $linkType),
            'show_additional_fields' => '1,',
            'link_from_sitemap' => ($destinationProperty === 'link_from_sitemap' ? $destinationValue : '0') . ',',
            'link_from_file_manager' => ($destinationProperty === 'link_from_file_manager' ? $destinationValue : '0') . ',',
            'protocol' => ($this->getFieldType() === FieldTypeEnum::ExternalLink
                ? $value($handle . '_protocol')
                : "'other'") . ',',
            'external_link' => ($destinationProperty === 'external_link' ? $destinationValue : "''") . ',',
            'ending' => ($field->showEndingField ? $value($handle . '_ending') : "''") . ',',
            'text' => ($field->showTextField ? $value($handle . '_text') : "''") . ',',
            'title' => ($field->showTitleField ? $value($handle . '_title') : "''") . ',',
            'new_window' => ($field->showNewWindowField ? $value($handle . '_new_window') : '0') . ',',
            'no_follow' => ($field->showNoFollowField ? $value($handle . '_no_follow') : '0') . ',',
        ];
        $lines = ['['];
        foreach ($values as $property => $expression) {
            $lines[] = sprintf('    %s => %s', $this->phpLiteralFormatter->format($property), $expression);
        }
        $lines[] = ']';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     * @param list<array{name: string, kind: string}> $storedFields
     */
    private function renderBasicViewPreparation(object $field, array $storedFields): string
    {
        $lines = [
            sprintf(
                '$linkData = $this->%s(%s);',
                LinkFieldGenerationSupport::RESOLVE_METHOD,
                $this->renderLinkDataArray($field, 'properties'),
            ),
        ];
        foreach ($storedFields as $storedField) {
            $property = $this->getLinkDataProperty($field, $storedField['name']);
            $value = $storedField['kind'] === 'boolean'
                ? sprintf(
                    '$linkData[%s] ? %s : \'\'',
                    $this->phpLiteralFormatter->format($property),
                    $this->phpLiteralFormatter->format(
                        str_ends_with($storedField['name'], '_new_window') ? 'target="_blank"' : 'rel="nofollow"',
                    ),
                )
                : sprintf('$linkData[%s]', $this->phpLiteralFormatter->format($property));
            $lines[] = sprintf(
                '$this->set(%s, %s);',
                $this->phpLiteralFormatter->format($storedField['name']),
                $value,
            );
        }
        $lines = [...$lines, ...$this->renderBasicDerivedViewValues($field)];

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     * @param list<array{name: string, kind: string}> $storedFields
     */
    private function renderRepeatableViewPreparation(object $field, array $storedFields): string
    {
        $lines = [
            sprintf(
                '$linkData = $this->%s(%s);',
                LinkFieldGenerationSupport::RESOLVE_METHOD,
                $this->renderLinkDataArray($field, 'entry'),
            ),
        ];
        foreach ($storedFields as $storedField) {
            $property = $this->getLinkDataProperty($field, $storedField['name']);
            $value = $storedField['kind'] === 'boolean'
                ? sprintf(
                    '$linkData[%s] ? %s : \'\'',
                    $this->phpLiteralFormatter->format($property),
                    $this->phpLiteralFormatter->format(
                        str_ends_with($storedField['name'], '_new_window') ? 'target="_blank"' : 'rel="nofollow"',
                    ),
                )
                : sprintf('$linkData[%s]', $this->phpLiteralFormatter->format($property));
            $lines[] = sprintf(
                '$entry[%s] = %s;',
                $this->phpLiteralFormatter->format($storedField['name']),
                $value,
            );
        }
        $handle = $field->handle;
        if ($this->getFieldType() === FieldTypeEnum::LinkFromSitemap) {
            $lines[] = sprintf('$entry[%s] = $linkData[\'name\'];', $this->phpLiteralFormatter->format($handle . '_name'));
        } elseif ($this->getFieldType() === FieldTypeEnum::LinkFromFileManager) {
            $lines[] = sprintf('$entry[%s] = $linkData[\'filename\'];', $this->phpLiteralFormatter->format($handle . '_filename'));
        }
        $lines[] = sprintf('$entry[%s] = $linkData[\'url\'];', $this->phpLiteralFormatter->format($handle . '_link'));
        $lines[] = sprintf(
            '$entry[%s] = %s;',
            $this->phpLiteralFormatter->format($handle . '_link_type'),
            $this->phpLiteralFormatter->format($this->getFieldType()->value),
        );

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     *
     * @return string[]
     */
    private function renderBasicDerivedViewValues(object $field): array
    {
        $handle = $field->handle;
        $lines = [];
        if ($this->getFieldType() === FieldTypeEnum::LinkFromSitemap) {
            $lines[] = sprintf('$this->set(%s, $linkData[\'object\']);', $this->phpLiteralFormatter->format($handle . '_object'));
            $lines[] = sprintf('$this->set(%s, $linkData[\'name\']);', $this->phpLiteralFormatter->format($handle . '_name'));
        } elseif ($this->getFieldType() === FieldTypeEnum::LinkFromFileManager) {
            $lines[] = sprintf('$this->set(%s, $linkData[\'object\']);', $this->phpLiteralFormatter->format($handle . '_object'));
            $lines[] = sprintf('$this->set(%s, $linkData[\'filename\']);', $this->phpLiteralFormatter->format($handle . '_filename'));
        }
        $lines[] = sprintf('$this->set(%s, $linkData[\'url\']);', $this->phpLiteralFormatter->format($handle . '_link'));
        $lines[] = sprintf(
            '$this->set(%s, %s);',
            $this->phpLiteralFormatter->format($handle . '_link_type'),
            $this->phpLiteralFormatter->format($this->getFieldType()->value),
        );

        return $lines;
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     */
    private function getLinkDataProperty(object $field, string $storedFieldName): string
    {
        if ($storedFieldName === $field->handle) {
            return match ($this->getFieldType()) {
                FieldTypeEnum::LinkFromSitemap => 'link_from_sitemap',
                FieldTypeEnum::LinkFromFileManager => 'link_from_file_manager',
                FieldTypeEnum::ExternalLink => 'external_link',
                default => '',
            };
        }

        return substr($storedFieldName, strlen($field->handle) + 1);
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     * @param list<array{name: string, kind: string}> $storedFields
     */
    private function contributeViewDocumentation(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
        object $field,
        array $storedFields,
    ): void {
        $documentation = [];
        foreach ($storedFields as $storedField) {
            $documentation[] = new ViewVariableDocumentation(
                name: $storedField['name'],
                type: $storedField['kind'] === 'identifier' ? 'int' : 'string',
                description: $this->getStoredFieldDescription($field, $storedField['name']),
            );
        }
        if ($context->isBasicField() && $this->getFieldType() === FieldTypeEnum::LinkFromSitemap) {
            $documentation[] = new ViewVariableDocumentation(
                name: $field->handle . '_object',
                type: '\\Concrete\\Core\\Page\\Page|false',
                description: sprintf('Resolved page object for %s', $field->label),
            );
        }
        if ($context->isBasicField() && $this->getFieldType() === FieldTypeEnum::LinkFromFileManager) {
            $documentation[] = new ViewVariableDocumentation(
                name: $field->handle . '_object',
                type: '\\Concrete\\Core\\Entity\\File\\File|false',
                description: sprintf('Resolved file object for %s', $field->label),
            );
        }
        if ($this->getFieldType() === FieldTypeEnum::LinkFromSitemap) {
            $documentation[] = new ViewVariableDocumentation(
                name: $field->handle . '_name',
                type: 'string',
                description: sprintf('Resolved page name for %s', $field->label),
            );
        } elseif ($this->getFieldType() === FieldTypeEnum::LinkFromFileManager) {
            $documentation[] = new ViewVariableDocumentation(
                name: $field->handle . '_filename',
                type: 'string',
                description: sprintf('Resolved filename for %s', $field->label),
            );
        }
        $documentation[] = new ViewVariableDocumentation(
            name: $field->handle . '_link',
            type: 'string',
            description: sprintf('Resolved URL for %s', $field->label),
        );
        $documentation[] = new ViewVariableDocumentation(
            name: $field->handle . '_link_type',
            type: 'string',
            description: sprintf('Link type for %s', $field->label),
        );

        foreach ($documentation as $offset => $variable) {
            $planBuilder->view->addFieldVariable(
                $context->fieldContext,
                new ViewVariableDocumentation(
                    name: $variable->name,
                    type: $variable->type,
                    description: $variable->description,
                    order: ($context->position * 10) + min($offset, 9),
                ),
            );
        }
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     */
    private function getStoredFieldDescription(object $field, string $name): string
    {
        return match (true) {
            $name === $field->handle => sprintf('Stored link destination for %s', $field->label),
            str_ends_with($name, '_protocol') => sprintf('Selected URL protocol for %s', $field->label),
            str_ends_with($name, '_ending') => sprintf('URL suffix for %s', $field->label),
            str_ends_with($name, '_text') => sprintf('Link text for %s', $field->label),
            str_ends_with($name, '_title') => sprintf('Title attribute for %s', $field->label),
            str_ends_with($name, '_new_window') => sprintf('Generated target attribute for %s', $field->label),
            default => sprintf('Generated rel attribute for %s', $field->label),
        };
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     */
    private function renderFormFragment(FieldGenerationContext $context, object $field): string
    {
        $basic = $context->isBasicField();
        $destination = $this->indentCode($this->renderDestinationField($field, $basic));
        $highlightField = $context->config->highlightMultiElementFields && (
            $this->getFieldType() === FieldTypeEnum::ExternalLink
            || $field->showEndingField
            || $field->showTextField
            || $field->showTitleField
            || $field->showNewWindowField
            || $field->showNoFollowField
        );
        $replacements = [
            '{{HIGHLIGHT_CLASS}}' => $highlightField ? ' field-group-highlight' : '',
            '{{DESTINATION_FIELD}}' => $destination,
            '{{ENDING_FIELD}}' => $field->showEndingField
                ? PHP_EOL . $this->indentCode(
                    $this->renderStringFormField($field->handle . '_ending', $context, false, true),
                )
                : '',
            '{{TEXT_FIELD}}' => $field->showTextField
                ? PHP_EOL . $this->indentCode($this->renderStringFormField($field->handle . '_text', $context, true))
                : '',
            '{{TITLE_FIELD}}' => $field->showTitleField
                ? PHP_EOL . $this->indentCode($this->renderStringFormField($field->handle . '_title', $context))
                : '',
            '{{NEW_WINDOW_FIELD}}' => $field->showNewWindowField
                ? PHP_EOL . $this->indentCode(
                    $this->renderBooleanFormField($field->handle . '_new_window', $context, true),
                )
                : '',
            '{{NO_FOLLOW_FIELD}}' => $field->showNoFollowField
                ? PHP_EOL . $this->indentCode(
                    $this->renderBooleanFormField($field->handle . '_no_follow', $context, false),
                )
                : '',
        ];

        return $this->stubRenderer->render(
            $basic
                ? 'fragments/individual_link/form-basic.php.stub'
                : 'fragments/individual_link/form-repeatable.php.stub',
            $replacements,
        );
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     */
    private function renderDestinationField(object $field, bool $basic): string
    {
        $common = [
            '{{HANDLE}}' => $field->handle,
            '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
            '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
            '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? " . ' *'" : '',
            '{{HELP_TEXT}}' => $this->renderHelpText($field->helpText),
        ];
        if ($this->getFieldType() === FieldTypeEnum::ExternalLink) {
            return $this->stubRenderer->render(
                $basic
                    ? 'fragments/individual_link/external-basic.php.stub'
                    : 'fragments/individual_link/external-repeatable.php.stub',
                [
                    ...$common,
                    '{{PROTOCOL_HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_protocol'),
                ],
            );
        }

        $pageSelector = $this->getFieldType() === FieldTypeEnum::LinkFromSitemap;

        return $this->stubRenderer->render(
            $basic
                ? 'fragments/individual_link/selector-basic.php.stub'
                : 'fragments/individual_link/selector-repeatable.php.stub',
            [
                ...$common,
                '{{SELECTOR_TYPE}}' => $pageSelector ? 'page' : 'file',
                '{{COMPONENT_NAME}}' => $pageSelector ? 'concrete-page-input' : 'concrete-file-input',
                '{{IDENTIFIER_ATTRIBUTE}}' => $pageSelector ? 'page-id' : 'file-id',
                '{{CHOOSE_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format(
                    $pageSelector ? 'Choose page' : 'Choose file',
                ),
            ],
        );
    }

    private function renderStringFormField(
        string $handle,
        FieldGenerationContext $context,
        bool $textarea = false,
        bool $ending = false,
    ): string {
        $label = $ending
            ? ($context->config->urlEndingLabel ?: 'Custom string at the end of the URL')
            : (str_ends_with($handle, '_text')
                ? ($context->config->textLabel ?: 'Text')
                : ($context->config->titleLabel ?: 'Title'));
        $handleLiteral = $this->phpLiteralFormatter->format($handle);
        $labelLiteral = $this->phpLiteralFormatter->format($label);
        if ($context->isBasicField()) {
            $control = $textarea
                ? sprintf(
                    '<textarea class="form-control" id="<?= h($view->field(%1$s)); ?>" name="<?= h($view->field(%1$s)); ?>" maxlength="255"><?= h($%2$s ?? \'\'); ?></textarea>',
                    $handleLiteral,
                    $handle,
                )
                : sprintf(
                    '<input class="form-control" id="<?= h($view->field(%1$s)); ?>" name="<?= h($view->field(%1$s)); ?>" type="text" value="<?= h($%2$s ?? \'\'); ?>" maxlength="255">',
                    $handleLiteral,
                    $handle,
                );
            $id = sprintf('<?= h($view->field(%s)); ?>', $handleLiteral);
        } else {
            $control = $textarea
                ? sprintf(
                    '<textarea class="form-control" id="<?= h($view->field(\'entry\')); ?>-<?= h((string) $entryIndex); ?>-%1$s" name="<?= h($view->field(\'entry\')); ?>[<?= h((string) $entryIndex); ?>][%1$s]" maxlength="255" data-entry-field="%1$s"><?= h($entry[%2$s] ?? \'\'); ?></textarea>',
                    $handle,
                    $handleLiteral,
                )
                : sprintf(
                    '<input class="form-control" id="<?= h($view->field(\'entry\')); ?>-<?= h((string) $entryIndex); ?>-%1$s" name="<?= h($view->field(\'entry\')); ?>[<?= h((string) $entryIndex); ?>][%1$s]" type="text" value="<?= h($entry[%2$s] ?? \'\'); ?>" maxlength="255" data-entry-field="%1$s">',
                    $handle,
                    $handleLiteral,
                );
            $id = sprintf('<?= h($view->field(\'entry\')); ?>-<?= h((string) $entryIndex); ?>-%s', $handle);
        }
        $help = $ending
            ? PHP_EOL . sprintf(
                '    <div class="form-text"><?= t(%s); ?></div>',
                $this->phpLiteralFormatter->format(
                    $context->config->urlEndingHelpTextLabel ?: '(e.g., #contact-form or ?ccm_paging_p=2)',
                ),
            )
            : '';

        return sprintf(
            '<div class="mb-4">%1$s    <label class="form-label" for="%2$s"><?= t(%3$s); ?></label>%1$s    %4$s%5$s%1$s</div>',
            PHP_EOL,
            $id,
            $labelLiteral,
            $control,
            $help,
        );
    }

    private function renderBooleanFormField(
        string $handle,
        FieldGenerationContext $context,
        bool $newWindow,
    ): string {
        $label = $newWindow
            ? ($context->config->newWindowLabel ?: 'Open in new window')
            : ($context->config->noFollowLabel ?: 'Add the nofollow attribute');
        $yes = $context->config->yesLabel ?: 'Yes';
        $no = $context->config->noLabel ?: 'No';
        $handleLiteral = $this->phpLiteralFormatter->format($handle);
        if ($context->isBasicField()) {
            $id = sprintf('<?= h($view->field(%s)); ?>', $handleLiteral);
            $name = $id;
            $value = sprintf('$%s', $handle);
            $entryAttribute = '';
        } else {
            $id = sprintf('<?= h($view->field(\'entry\')); ?>-<?= h((string) $entryIndex); ?>-%s', $handle);
            $name = sprintf('<?= h($view->field(\'entry\')); ?>[<?= h((string) $entryIndex); ?>][%s]', $handle);
            $value = sprintf('$entry[%s]', $handleLiteral);
            $entryAttribute = sprintf(' data-entry-field="%s"', $handle);
        }

        return sprintf(
            '<div class="mb-4">%1$s'
            . '    <label class="form-label" for="%2$s"><?= t(%3$s); ?></label>%1$s'
            . '    <select class="form-select" id="%2$s" name="%4$s"%5$s>%1$s'
            . '        <option value="0" <?= empty(%6$s) ? \'selected\' : \'\'; ?>><?= t(%7$s); ?></option>%1$s'
            . '        <option value="1" <?= !empty(%6$s) ? \'selected\' : \'\'; ?>><?= t(%8$s); ?></option>%1$s'
            . '    </select>%1$s'
            . '</div>',
            PHP_EOL,
            $id,
            $this->phpLiteralFormatter->format($label),
            $name,
            $entryAttribute,
            $value,
            $this->phpLiteralFormatter->format($no),
            $this->phpLiteralFormatter->format($yes),
        );
    }

    /**
     * @param ExternalLinkFieldTypeDto|LinkFromFileManagerFieldTypeDto|LinkFromSitemapFieldTypeDto $field
     */
    private function renderViewFragment(object $field, bool $basic): string
    {
        $handle = $field->handle;
        if ($basic) {
            $replacements = [
                '{{HANDLE}}' => $handle,
                '{{ENDING_EXPRESSION}}' => $field->showEndingField ? sprintf(' . ($%s_ending ?? \'\')', $handle) : '',
                '{{TITLE_ATTRIBUTE}}' => $field->showTitleField
                    ? sprintf('title="<?= h($%s_title ?? \'\'); ?>"', $handle)
                    : '',
                '{{NEW_WINDOW_ATTRIBUTE}}' => $field->showNewWindowField
                    ? sprintf(' <?= $%s_new_window ?? \'\'; ?>', $handle)
                    : '',
                '{{NO_FOLLOW_ATTRIBUTE}}' => $field->showNoFollowField
                    ? sprintf(' <?= $%s_no_follow ?? \'\'; ?>', $handle)
                    : '',
                '{{TEXT_CONTENT}}' => $field->showTextField
                    ? sprintf('%1$s        <?= nl2br(h($%2$s_text ?? \'\'), false); ?>%1$s    ', PHP_EOL, $handle)
                    : '',
            ];
        } else {
            $key = fn (string $suffix): string => $this->phpLiteralFormatter->format($handle . $suffix);
            $replacements = [
                '{{LINK_KEY_LITERAL}}' => $key('_link'),
                '{{ENDING_EXPRESSION}}' => $field->showEndingField
                    ? sprintf(' . ($entry[%s] ?? \'\')', $key('_ending'))
                    : '',
                '{{TITLE_ATTRIBUTE}}' => $field->showTitleField
                    ? sprintf('title="<?= h($entry[%s] ?? \'\'); ?>"', $key('_title'))
                    : '',
                '{{NEW_WINDOW_ATTRIBUTE}}' => $field->showNewWindowField
                    ? sprintf(' <?= $entry[%s] ?? \'\'; ?>', $key('_new_window'))
                    : '',
                '{{NO_FOLLOW_ATTRIBUTE}}' => $field->showNoFollowField
                    ? sprintf(' <?= $entry[%s] ?? \'\'; ?>', $key('_no_follow'))
                    : '',
                '{{TEXT_CONTENT}}' => $field->showTextField
                    ? sprintf('%1$s        <?= nl2br(h($entry[%2$s] ?? \'\'), false); ?>%1$s    ', PHP_EOL, $key('_text'))
                    : '',
            ];
        }

        return $this->stubRenderer->render(
            $basic
                ? 'fragments/individual_link/view-basic.php.stub'
                : 'fragments/individual_link/view-repeatable.php.stub',
            $replacements,
        );
    }

    private function renderHelpText(?string $helpText): string
    {
        if ($helpText === null || trim($helpText) === '') {
            return '';
        }

        return PHP_EOL . sprintf(
            '    <div class="form-text"><?= t(%s); ?></div>',
            $this->phpLiteralFormatter->format($helpText),
        );
    }

    private function getDefaultValue(string $kind): string|int
    {
        return match ($kind) {
            'identifier', 'boolean' => 0,
            'protocol' => 'https://',
            default => '',
        };
    }

    private function indentCode(string $code): string
    {
        return implode(PHP_EOL, array_map(
            static fn (string $line): string => $line === '' ? '' : '    ' . $line,
            explode(PHP_EOL, $code),
        ));
    }
}
