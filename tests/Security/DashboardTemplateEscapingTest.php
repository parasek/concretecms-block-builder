<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Security;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Test type: Dashboard template escaping security contract test.
 *
 * Scans dashboard templates to ensure raw Underscore interpolation is reserved for trusted,
 * pre-rendered partial markup rather than user-controlled values.
 */
final class DashboardTemplateEscapingTest extends TestCase
{
    /**
     * Scans field templates and confirms that raw interpolation is used only for the trusted
     * pre-rendered partial-content value.
     */
    public function testOnlyPreRenderedPartialMarkupUsesRawUnderscoreInterpolation(): void
    {
        $templateRoot = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'elements'
            . DIRECTORY_SEPARATOR
            . 'field_type_template';
        $rawInterpolations = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templateRoot));
        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertNotFalse($contents);
            if (preg_match_all('/<%=\s*([^%]+?)\s*%>/', $contents, $matches) > 0) {
                foreach ($matches[1] as $expression) {
                    $rawInterpolations[] = trim($expression);
                }
            }
        }

        self::assertSame(['partialContent'], $rawInterpolations);
    }
}
