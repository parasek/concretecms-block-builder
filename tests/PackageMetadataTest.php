<?php

declare(strict_types=1);

namespace BlockBuilder\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test type: Package release metadata contract test.
 *
 * Verifies that the controller and Composer PHP/Concrete requirements agree and that the package
 * version remains aligned with every current predefined configuration shipped for new blocks.
 */
final class PackageMetadataTest extends TestCase
{
    /**
     * Confirms that the declared release version and platform requirements match Composer and
     * that every current predefined configuration carries the same package version.
     */
    public function testReleaseMetadataAndCurrentPresetsStayAligned(): void
    {
        $packageRoot = dirname(__DIR__);
        $composerContents = file_get_contents($packageRoot . DIRECTORY_SEPARATOR . 'composer.json');
        self::assertNotFalse($composerContents);
        $controllerContents = file_get_contents($packageRoot . DIRECTORY_SEPARATOR . 'controller.php');
        self::assertNotFalse($controllerContents);

        $composerData = json_decode($composerContents, true, flags: JSON_THROW_ON_ERROR);
        $packageVersion = $this->readStringPropertyDefault($controllerContents, 'pkgVersion');
        $phpVersionRequired = $this->readStringPropertyDefault($controllerContents, 'phpVersionRequired');
        $appVersionRequired = $this->readStringPropertyDefault($controllerContents, 'appVersionRequired');

        self::assertSame('3.0.0', $packageVersion);
        self::assertSame('>=8.4', $composerData['require']['php']);
        self::assertSame('8.4', $phpVersionRequired);
        self::assertSame('^9.5.2', $composerData['require']['concrete5/core']);
        self::assertSame('9.5.2', $appVersionRequired);

        foreach (['all_fields.json', 'all_fields_double.json'] as $presetFileName) {
            $presetContents = file_get_contents(
                $packageRoot . DIRECTORY_SEPARATOR . 'predefined_configs' . DIRECTORY_SEPARATOR . $presetFileName,
            );
            self::assertNotFalse($presetContents);
            $presetData = json_decode($presetContents, true, flags: JSON_THROW_ON_ERROR);

            self::assertSame($packageVersion, $presetData['blockBuilderVersion']);
        }
    }

    private function readStringPropertyDefault(string $controllerContents, string $propertyName): string
    {
        $matched = preg_match(
            sprintf('/protected(?:\\s+string)?\\s+\\$%s\\s*=\\s*[\'\"]([^\'\"]+)[\'\"]\\s*;/', preg_quote($propertyName, '/')),
            $controllerContents,
            $matches,
        );
        self::assertSame(1, $matched, sprintf('Controller property "$%s" must have a literal string default.', $propertyName));

        return $matches[1];
    }
}
