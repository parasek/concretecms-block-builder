<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\DbXml;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\Exception\GeneratedFileDefinitionException;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use DOMDocument;
use DOMElement;

readonly class DbXmlFileGenerator implements FileGeneratorInterface
{
    /**
     * @return iterable<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): iterable
    {
        $document = new DOMDocument('1.0');
        $document->formatOutput = true;
        $schema = $document->createElement('schema');
        $schema->setAttribute('version', '0.3');
        $document->appendChild($schema);

        $this->writeTable(
            $document,
            $schema,
            $context->manifest->databaseTableName,
            $context->plan->database->mainTableColumns,
        );
        if ($context->plan->database->entriesTableColumns !== []) {
            $this->writeTable(
                $document,
                $schema,
                $context->manifest->entriesDatabaseTableName,
                $context->plan->database->entriesTableColumns,
            );
        }

        $xml = $document->saveXML();
        if ($xml === false) {
            throw new GeneratedFileDefinitionException(sprintf(
                'Unable to serialize the database schema for block "%s".',
                $context->config->blockHandle,
            ));
        }

        yield new GeneratedTextFile(
            relativePath: FILENAME_BLOCK_DB,
            contents: $xml,
            producer: self::class,
        );
    }

    /**
     * @param DatabaseColumn[] $columns
     */
    private function writeTable(
        DOMDocument $document,
        DOMElement $schema,
        string $tableName,
        array $columns,
    ): void
    {
        $table = $document->createElement('table');
        $table->setAttribute('name', $tableName);
        $schema->appendChild($table);

        foreach ($columns as $column) {
            $field = $document->createElement('field');
            $field->setAttribute('name', $column->name);
            $field->setAttribute('type', $column->type);
            $table->appendChild($field);
            if ($column->size !== null) {
                $field->setAttribute('size', $column->size);
            }
            if ($column->primaryKey) {
                $field->appendChild($document->createElement('key'));
            }
            if ($column->unsigned) {
                $field->appendChild($document->createElement('unsigned'));
            }
            if ($column->autoIncrement) {
                $field->appendChild($document->createElement('autoincrement'));
            }
            if ($column->notNull) {
                $field->appendChild($document->createElement('notnull'));
            }
            if ($column->hasDefault) {
                $default = $document->createElement('default');
                $default->setAttribute('value', $this->formatDefaultValue($column->defaultValue));
                $field->appendChild($default);
            }
        }
    }

    private function formatDefaultValue(string|int|float|bool|null $defaultValue): string
    {
        return match (true) {
            $defaultValue === null => '',
            is_bool($defaultValue) => $defaultValue ? '1' : '0',
            default => (string) $defaultValue,
        };
    }
}
