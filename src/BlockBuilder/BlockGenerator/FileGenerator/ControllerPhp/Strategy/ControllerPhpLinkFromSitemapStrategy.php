<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy;

class ControllerPhpLinkFromSitemapStrategy implements ControllerPhpFieldTypeStrategyInterface
{
    public function getUseStatements(): array
    {
        return [];
    }

    public function getPropertyDeclarations(array $field): array
    {
        return [];
    }

    public function getExportPageColumns(array $field): array
    {
        return [];
    }

    public function getExportFileColumns(array $field): array
    {
        return [];
    }

    public function isSearchable(): bool
    {
        return true;
    }
}
