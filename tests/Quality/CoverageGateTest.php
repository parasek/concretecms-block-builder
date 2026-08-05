<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Quality;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Test type: Coverage policy quality test.
 *
 * Verifies that Clover reports enforce every configured coverage target, preserve useful
 * diagnostics in report-only mode, and fail closed when coverage data is malformed.
 */
final class CoverageGateTest extends TestCase
{
    private Filesystem $filesystem;
    private string $temporaryDirectory;
    private string $coverageGatePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-coverage-gate-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->temporaryDirectory, 0700);
        $this->coverageGatePath = __DIR__ . DIRECTORY_SEPARATOR . 'assert-coverage.php';
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);

        parent::tearDown();
    }

    /**
     * Confirms that a Clover report meeting the exact overall and critical thresholds exits
     * successfully and reports the measured percentages.
     */
    public function testPassingReportSatisfiesEveryTarget(): void
    {
        $cloverPath = $this->writeCloverReport(
            overallStatements: 100,
            overallCoveredStatements: 85,
            overallConditionals: 100,
            overallCoveredConditionals: 75,
            criticalStatements: 100,
            criticalCoveredStatements: 95,
            criticalConditionals: 100,
            criticalCoveredConditionals: 90,
        );

        $result = $this->runCoverageGate($cloverPath);

        self::assertSame(0, $result['exitCode'], $result['errorOutput']);
        self::assertStringContainsString('Overall lines: 85.00%', $result['output']);
        self::assertStringContainsString('Critical branches: 90.00%', $result['output']);
        self::assertSame('', $result['errorOutput']);
    }

    /**
     * Confirms that strict mode fails and lists every overall and critical line or branch target
     * that the report misses.
     */
    public function testFailingReportReturnsFailureAndListsEveryMissedTarget(): void
    {
        $cloverPath = $this->writeCloverReport(
            overallStatements: 100,
            overallCoveredStatements: 50,
            overallConditionals: 100,
            overallCoveredConditionals: 25,
            criticalStatements: 100,
            criticalCoveredStatements: 40,
            criticalConditionals: 100,
            criticalCoveredConditionals: 20,
        );

        $result = $this->runCoverageGate($cloverPath);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('Overall line coverage is 50.00%', $result['errorOutput']);
        self::assertStringContainsString('Overall branch coverage is 25.00%', $result['errorOutput']);
        self::assertStringContainsString('Critical line coverage is 40.00%', $result['errorOutput']);
        self::assertStringContainsString('Critical branch coverage is 20.00%', $result['errorOutput']);
    }

    /**
     * Confirms that report-only mode exposes all below-threshold diagnostics while leaving the
     * provisional baseline job successful.
     */
    public function testReportOnlyModePreservesDiagnosticsWithoutFailingTheBaselineJob(): void
    {
        $cloverPath = $this->writeCloverReport(
            overallStatements: 10,
            overallCoveredStatements: 1,
            overallConditionals: 10,
            overallCoveredConditionals: 1,
            criticalStatements: 10,
            criticalCoveredStatements: 1,
            criticalConditionals: 10,
            criticalCoveredConditionals: 1,
        );

        $result = $this->runCoverageGate($cloverPath, true);

        self::assertSame(0, $result['exitCode'], $result['errorOutput']);
        self::assertStringContainsString('below the required', $result['errorOutput']);
        self::assertStringContainsString('currently report-only', $result['output']);
    }

    /**
     * Confirms that malformed coverage XML fails even in report-only mode rather than silently
     * passing without trustworthy measurements.
     */
    public function testMalformedCloverReportFailsClosed(): void
    {
        $cloverPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'malformed.xml';
        self::assertSame(19, file_put_contents($cloverPath, '<coverage><project>'));

        $result = $this->runCoverageGate($cloverPath, true);

        self::assertSame(2, $result['exitCode']);
        self::assertStringContainsString('Unable to parse Clover report', $result['errorOutput']);
    }

    /**
     * @return array{exitCode: int, output: string, errorOutput: string}
     */
    private function runCoverageGate(string $cloverPath, bool $reportOnly = false): array
    {
        $environment = $reportOnly ? ['BLOCK_BUILDER_COVERAGE_REPORT_ONLY' => '1'] : null;
        $process = proc_open(
            [PHP_BINARY, $this->coverageGatePath, $cloverPath],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            dirname(__DIR__, 2),
            $environment,
        );
        self::assertIsResource($process);

        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertIsString($output);
        self::assertIsString($errorOutput);

        return [
            'exitCode' => $exitCode,
            'output' => $output,
            'errorOutput' => $errorOutput,
        ];
    }

    private function writeCloverReport(
        int $overallStatements,
        int $overallCoveredStatements,
        int $overallConditionals,
        int $overallCoveredConditionals,
        int $criticalStatements,
        int $criticalCoveredStatements,
        int $criticalConditionals,
        int $criticalCoveredConditionals,
    ): string {
        $cloverPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'clover.xml';
        $contents = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<coverage><project>'
            . '<metrics statements="%d" coveredstatements="%d" conditionals="%d" coveredconditionals="%d"/>'
            . '<package name="critical"><file name="/workspace/src/BlockBuilder/Block/Service/Example.php">'
            . '<metrics statements="%d" coveredstatements="%d" conditionals="%d" coveredconditionals="%d"/>'
            . '</file></package>'
            . '</project></coverage>',
            $overallStatements,
            $overallCoveredStatements,
            $overallConditionals,
            $overallCoveredConditionals,
            $criticalStatements,
            $criticalCoveredStatements,
            $criticalConditionals,
            $criticalCoveredConditionals,
        );
        self::assertSame(strlen($contents), file_put_contents($cloverPath, $contents));

        return $cloverPath;
    }
}
