<?php

declare(strict_types=1);

// Xdebug applies coverage filters when files are compiled, before PHPUnit's bootstrap runs.
// Prepend this file so that PHPUnit and Concrete dependencies are never instrumented.
// https://xdebug.org/docs/code_coverage#filter
xdebug_set_filter(
    XDEBUG_FILTER_CODE_COVERAGE,
    XDEBUG_PATH_INCLUDE,
    [dirname(__DIR__) . '/src/BlockBuilder/'],
);
