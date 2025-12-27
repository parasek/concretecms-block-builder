<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Service;

use BlockBuilder\Service\FormatterService;

readonly class ControllerPhpFormatterService
{
    public function __construct(
        private FormatterService $formatter,
    ) {
    }

    public function formatUseStatements(array $useStatements): string
    {
        if (empty($useStatements)) {
            return '';
        }

        $useStatements = array_unique($useStatements);
        sort($useStatements);

        return implode(PHP_EOL, array_map(fn($useStatement) => "use $useStatement;", $useStatements));
    }

    public function formatSearchableFields(array $searchableBasicFields, array $searchableEntryFields): string
    {
        $phpArrayItems = [];
        foreach ($searchableBasicFields as $handle) {
            $phpArrayItems[] = '$this->' . $handle;
        }
        $content = $this->formatter->phpIndexedArray(
            variableName: 'content',
            items: $phpArrayItems,
            indentation: 2,
            skipFirstIndentation: true,
        );

        if (!empty($searchableEntryFields)) {
            $content .= PHP_EOL . PHP_EOL;
            $content .= $this->formatter->tab(2) . '$entries = $this->getEntries(\'edit\');' . PHP_EOL;
            $content .= $this->formatter->tab(2) . 'foreach ($entries as $entry) {' . PHP_EOL;
            foreach ($searchableEntryFields as $handle) {
                $content .= $this->formatter->tab(3) . '$content[] = $entry[\'' . $handle . '\'];' . PHP_EOL;
            }
            $content .= $this->formatter->tab(2) . '}';
        }

        return $content;
    }
}
