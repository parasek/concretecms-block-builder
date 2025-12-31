<?php

declare(strict_types=1);

namespace BlockBuilder\Service;

class FormatterService
{
    public function tab(int $numberOfTabs): string
    {
        $spaces = '';
        for ($i = 1; $i <= $numberOfTabs; ++$i) {
            $spaces .= '    ';
        }

        return $spaces;
    }

    public function phpIndexedArray(
        string $variableName,
        array $items,
        int $indentation,
        bool $skipFirstIndentation,
    ): string {
        $firstIndentation = $skipFirstIndentation ? 0 : $indentation;

        $content = $this->tab($firstIndentation) . '$' . $variableName . ' = [';
        if (!empty($items)) {
            $content .= PHP_EOL;
            foreach ($items as $item) {
                $content .= $this->tab($indentation + 1) . $item . ',' . PHP_EOL;
            }
            $content .= $this->tab($indentation);
        }
        $content .= '];';

        return $content;
    }
}
