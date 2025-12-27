<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy;

interface ControllerPhpFieldTypeStrategyInterface
{
    public function getUseStatements(): array;
    public function getPropertyDeclarations(array $field): array;
    public function getExportPageColumns(array $field): array;
    public function getExportFileColumns(array $field): array;
    public function isSearchable(): bool;
}
