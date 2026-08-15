<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\DbXml;

use BlockBuilder\BlockGenerator\BlockFileGenerationContext;
use BlockBuilder\BlockGenerator\Exception\GeneratedFileDefinitionException;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseIndex;
use DOMDocument;
use DOMElement;

readonly class DbXmlFileGenerator implements FileGeneratorInterface
{
    private const string SCHEMA_NAMESPACE = 'http://www.concrete5.org/doctrine-xml/0.5';
    private const string SCHEMA_INSTANCE_NAMESPACE = 'http://www.w3.org/2001/XMLSchema-instance';
    private const string SCHEMA_LOCATION = 'https://concretecms.github.io/doctrine-xml/doctrine-xml-0.5.xsd';

    /**
     * @return list<GeneratedTextFile>
     */
    public function generate(BlockFileGenerationContext $context): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $schema = $this->createElement($document, 'schema');
        $schema->setAttributeNS(
            self::SCHEMA_INSTANCE_NAMESPACE,
            'xsi:schemaLocation',
            self::SCHEMA_NAMESPACE . ' ' . self::SCHEMA_LOCATION,
        );
        $document->appendChild($schema);

        $this->writeTable(
            $document,
            $schema,
            $context->manifest->databaseTableName,
            $context->plan->database->mainTableColumns,
            $context->plan->database->mainTableIndexes,
        );
        if ($context->plan->database->entriesTableColumns !== []) {
            $this->writeTable(
                $document,
                $schema,
                $context->manifest->entriesDatabaseTableName,
                $context->plan->database->entriesTableColumns,
                $context->plan->database->entriesTableIndexes,
            );
        }

        $xml = $document->saveXML();
        if ($xml === false) {
            throw new GeneratedFileDefinitionException(sprintf(
                'Unable to serialize the database schema for block "%s".',
                $context->config->blockHandle,
            ));
        }
        $xml = $this->formatGeneratedXml($xml);

        return [
            new GeneratedTextFile(
                relativePath: FILENAME_BLOCK_DB,
                contents: $xml,
                producer: self::class,
            ),
        ];
    }

    /**
     * @param DatabaseColumn[] $columns
     * @param DatabaseIndex[] $indexes
     */
    private function writeTable(
        DOMDocument $document,
        DOMElement $schema,
        string $tableName,
        array $columns,
        array $indexes,
    ): void
    {
        $table = $this->createElement($document, 'table');
        $table->setAttribute('name', $tableName);
        $schema->appendChild($table);

        foreach ($columns as $column) {
            $field = $this->createElement($document, 'field');
            $field->setAttribute('name', $column->name);
            $field->setAttribute('type', $column->type);
            $table->appendChild($field);
            if ($column->size !== null) {
                $field->setAttribute('size', $column->size);
            }
            if ($column->unsigned) {
                $field->appendChild($this->createElement($document, 'unsigned'));
            }
            if ($column->autoIncrement) {
                $field->appendChild($this->createElement($document, 'autoincrement'));
            }
            if ($column->primaryKey) {
                $field->appendChild($this->createElement($document, 'key'));
            }
            if ($column->hasDefault) {
                $default = $this->createElement($document, 'default');
                $default->setAttribute('value', $this->formatDefaultValue($column->defaultValue));
                $field->appendChild($default);
            }
            if ($column->notNull) {
                $field->appendChild($this->createElement($document, 'notnull'));
            }
        }

        foreach ($indexes as $indexDefinition) {
            $indexElement = $this->createElement($document, 'index');
            $indexElement->setAttribute('name', $indexDefinition->name);
            $table->appendChild($indexElement);

            foreach ($indexDefinition->columns as $columnName) {
                $columnElement = $this->createElement($document, 'col');
                $columnElement->appendChild($document->createTextNode($columnName));
                $indexElement->appendChild($columnElement);
            }
        }
    }

    private function createElement(DOMDocument $document, string $name): DOMElement
    {
        return $document->createElementNS(self::SCHEMA_NAMESPACE, $name);
    }

    private function formatGeneratedXml(string $xml): string
    {
        $xml = preg_replace_callback(
            '/^( +)/m',
            static fn(array $matches): string => str_repeat(' ', strlen($matches[1]) * 2),
            $xml,
        );
        if ($xml === null) {
            throw new GeneratedFileDefinitionException('Unable to format the generated database schema indentation.');
        }

        $schemaLocationAttribute = sprintf(
            'xsi:schemaLocation="%s %s">',
            self::SCHEMA_NAMESPACE,
            self::SCHEMA_LOCATION,
        );
        $xml = str_replace(
            ' ' . $schemaLocationAttribute,
            PHP_EOL . '        ' . $schemaLocationAttribute,
            $xml,
            $replacementCount,
        );
        if ($replacementCount !== 1) {
            throw new GeneratedFileDefinitionException('Unable to format the generated database schema declaration.');
        }

        return $xml;
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
