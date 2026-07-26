<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\Service;

readonly class PhpLiteralFormatter
{
    public function format(mixed $value): string
    {
        if (is_array($value)) {
            return $this->formatArray($value);
        }

        return var_export($value, true);
    }

    private function formatArray(array $values, int $depth = 0): string
    {
        if ($values === []) {
            return '[]';
        }

        $isList = array_is_list($values);
        $entryIndentation = str_repeat('    ', $depth + 1);
        $closingIndentation = str_repeat('    ', $depth);
        $lines = ['['];

        foreach ($values as $key => $value) {
            $keyLiteral = $isList ? '' : $this->format($key) . ' => ';
            $valueLiteral = is_array($value)
                ? $this->formatArray($value, $depth + 1)
                : $this->format($value);

            $lines[] = $entryIndentation . $keyLiteral . $valueLiteral . ',';
        }

        $lines[] = $closingIndentation . ']';

        return implode(PHP_EOL, $lines);
    }
}
