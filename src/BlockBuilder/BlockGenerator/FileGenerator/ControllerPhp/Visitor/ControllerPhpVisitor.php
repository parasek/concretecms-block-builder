<?php

namespace BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Visitor;

use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpFieldTypeStrategyInterface;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;

class ControllerPhpVisitor
{
    private array $useStatements = [
        'Concrete\Core\Asset\AssetList',
        'Concrete\Core\Block\BlockController',
    ];

    private array $searchableBasicFields = [];
    private array $searchableEntryFields = [];

    public function visit(ControllerPhpFieldTypeStrategyInterface $strategy, FieldTypeDtoInterface $fieldTypeDto, FieldTypeContextEnum $context): void
    {
        $this->collectUseStatements($strategy);
        $this->collectSearchableFields($strategy, $fieldTypeDto, $context);
    }

    private function collectUseStatements(ControllerPhpFieldTypeStrategyInterface $strategy): void
    {
        foreach ($strategy->getUseStatements() as $useStatement) {
            $this->useStatements[$useStatement] = $useStatement; // Key will make sure we don't add duplicates
        }
    }

    private function collectSearchableFields(ControllerPhpFieldTypeStrategyInterface $strategy, FieldTypeDtoInterface $fieldTypeDto, FieldTypeContextEnum $context): void
    {
        if ($strategy->isSearchable()) {
            if ($context === FieldTypeContextEnum::BasicFields) {
                $this->searchableBasicFields[] = $fieldTypeDto->handle;
            } elseif ($context === FieldTypeContextEnum::RepeatableFields) {
                $this->searchableEntryFields[] = $fieldTypeDto->handle;
            }
        }
    }

    public function getUseStatements(): array
    {
        return array_values($this->useStatements); // Removes keys and returns a clean indexed array
    }

    public function getSearchableBasicFields(): array
    {
        return $this->searchableBasicFields;
    }

    public function getSearchableEntryFields(): array
    {
        return $this->searchableEntryFields;
    }
}
