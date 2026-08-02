<?php

declare(strict_types=1);

const OVERALL_LINE_THRESHOLD = 85.0;
const OVERALL_BRANCH_THRESHOLD = 75.0;
const CRITICAL_LINE_THRESHOLD = 95.0;
const CRITICAL_BRANCH_THRESHOLD = 90.0;
const CHANGED_LINE_THRESHOLD = 90.0;

if (!isset($argv[1]) || trim($argv[1]) === '') {
    fwrite(STDERR, "Usage: php tests/Quality/assert-coverage.php <clover.xml> [base-commit]\n");
    exit(2);
}

$cloverPath = $argv[1];
if (!is_file($cloverPath) || !is_readable($cloverPath)) {
    fwrite(STDERR, sprintf("Coverage report \"%s\" is missing or unreadable.\n", $cloverPath));
    exit(2);
}

$document = new DOMDocument();
$previousUseInternalErrors = libxml_use_internal_errors(true);
$loaded = $document->load($cloverPath, LIBXML_NONET | LIBXML_NOBLANKS);
$xmlErrors = libxml_get_errors();
libxml_clear_errors();
libxml_use_internal_errors($previousUseInternalErrors);
if (!$loaded) {
    $firstError = $xmlErrors[0]->message ?? 'unknown XML error';
    fwrite(STDERR, sprintf("Unable to parse Clover report: %s\n", trim($firstError)));
    exit(2);
}

$xpath = new DOMXPath($document);
$projectMetrics = $xpath->query('/coverage/project/metrics')->item(0);
if (!$projectMetrics instanceof DOMElement) {
    fwrite(STDERR, "The Clover report does not contain project metrics.\n");
    exit(2);
}

$failures = [];
$overallLineCoverage = percentage(
    metric($projectMetrics, 'coveredstatements'),
    metric($projectMetrics, 'statements'),
    'overall executable lines',
);
$overallBranchCoverage = percentage(
    metric($projectMetrics, 'coveredconditionals'),
    metric($projectMetrics, 'conditionals'),
    'overall branches',
);
checkThreshold($failures, 'Overall line coverage', $overallLineCoverage, OVERALL_LINE_THRESHOLD);
checkThreshold($failures, 'Overall branch coverage', $overallBranchCoverage, OVERALL_BRANCH_THRESHOLD);

$criticalPrefixes = [
    '/src/BlockBuilder/Block/Factory/',
    '/src/BlockBuilder/Block/Service/',
    '/src/BlockBuilder/Block/Validation/',
    '/src/BlockBuilder/BlockGenerator/',
    '/src/BlockBuilder/FieldType/Type/SvgIconPicker/',
];
$criticalMetrics = [
    'statements' => 0,
    'coveredstatements' => 0,
    'conditionals' => 0,
    'coveredconditionals' => 0,
];
$fileNodes = $xpath->query('/coverage/project/package/file');
if ($fileNodes === false) {
    fwrite(STDERR, "The Clover report file list could not be read.\n");
    exit(2);
}
foreach ($fileNodes as $fileNode) {
    if (!$fileNode instanceof DOMElement) {
        continue;
    }
    $normalizedName = '/' . ltrim(str_replace('\\', '/', $fileNode->getAttribute('name')), '/');
    if (!array_filter($criticalPrefixes, static fn(string $prefix): bool => str_contains($normalizedName, $prefix))) {
        continue;
    }

    $metrics = $xpath->query('./metrics', $fileNode)->item(0);
    if (!$metrics instanceof DOMElement) {
        continue;
    }
    foreach (array_keys($criticalMetrics) as $name) {
        $criticalMetrics[$name] += metric($metrics, $name);
    }
}

$criticalLineCoverage = percentage(
    $criticalMetrics['coveredstatements'],
    $criticalMetrics['statements'],
    'critical executable lines',
);
$criticalBranchCoverage = percentage(
    $criticalMetrics['coveredconditionals'],
    $criticalMetrics['conditionals'],
    'critical branches',
);
checkThreshold($failures, 'Critical line coverage', $criticalLineCoverage, CRITICAL_LINE_THRESHOLD);
checkThreshold($failures, 'Critical branch coverage', $criticalBranchCoverage, CRITICAL_BRANCH_THRESHOLD);

$changedLineCoverage = null;
$baseCommit = $argv[2] ?? '';
if ($baseCommit !== '') {
    $changedLineCoverage = calculateChangedLineCoverage($baseCommit, $xpath, $fileNodes);
    if ($changedLineCoverage !== null) {
        checkThreshold($failures, 'Changed executable line coverage', $changedLineCoverage, CHANGED_LINE_THRESHOLD);
    }
}

printf("Overall lines: %.2f%% (required %.2f%%)\n", $overallLineCoverage, OVERALL_LINE_THRESHOLD);
printf("Overall branches: %.2f%% (required %.2f%%)\n", $overallBranchCoverage, OVERALL_BRANCH_THRESHOLD);
printf("Critical lines: %.2f%% (required %.2f%%)\n", $criticalLineCoverage, CRITICAL_LINE_THRESHOLD);
printf("Critical branches: %.2f%% (required %.2f%%)\n", $criticalBranchCoverage, CRITICAL_BRANCH_THRESHOLD);
if ($baseCommit !== '') {
    if ($changedLineCoverage === null) {
        printf("Changed executable lines: none between %s and HEAD\n", $baseCommit);
    } else {
        printf("Changed executable lines: %.2f%% (required %.2f%%)\n", $changedLineCoverage, CHANGED_LINE_THRESHOLD);
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    if (getenv('BLOCK_BUILDER_COVERAGE_REPORT_ONLY') !== '1') {
        exit(1);
    }

    fwrite(
        STDOUT,
        "Coverage targets are currently report-only until the first CI baseline is measured and ratcheted.\n",
    );
}

/**
 * @param list<string> $failures
 */
function checkThreshold(array &$failures, string $label, float $actual, float $required): void
{
    if ($actual + 0.00001 < $required) {
        $failures[] = sprintf('%s is %.2f%%, below the required %.2f%%.', $label, $actual, $required);
    }
}

function metric(DOMElement $metrics, string $name): int
{
    $value = $metrics->getAttribute($name);
    if ($value === '' || preg_match('/\A\d+\z/D', $value) !== 1) {
        throw new RuntimeException(sprintf('Coverage metric "%s" is missing or invalid.', $name));
    }

    return (int) $value;
}

function percentage(int $covered, int $total, string $label): float
{
    if ($total === 0) {
        throw new RuntimeException(sprintf(
            'Coverage report contains no %s. Run PHPUnit with Xdebug and path coverage enabled.',
            $label,
        ));
    }

    return ($covered / $total) * 100;
}

/**
 * @param DOMNodeList<DOMElement> $fileNodes
 */
function calculateChangedLineCoverage(string $baseCommit, DOMXPath $xpath, DOMNodeList $fileNodes): ?float
{
    if (preg_match('/\A[0-9a-f]{7,40}\z/Di', $baseCommit) !== 1) {
        throw new RuntimeException('The changed-line base commit must be a Git hexadecimal object identifier.');
    }

    $configuredGitRoot = getenv('BLOCK_BUILDER_COVERAGE_GIT_ROOT');
    $gitRoot = realpath(is_string($configuredGitRoot) && $configuredGitRoot !== ''
        ? $configuredGitRoot
        : dirname(__DIR__, 2));
    if ($gitRoot === false || !is_dir($gitRoot . DIRECTORY_SEPARATOR . '.git')) {
        throw new RuntimeException(
            'Changed-line coverage requires BLOCK_BUILDER_COVERAGE_GIT_ROOT to identify the package Git checkout.',
        );
    }

    $process = proc_open(
        ['git', 'diff', '--unified=0', $baseCommit . '...HEAD', '--', 'src'],
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $gitRoot,
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start Git for changed-line coverage.');
    }
    $diff = stream_get_contents($pipes[1]);
    $errorOutput = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0 || $diff === false) {
        throw new RuntimeException(sprintf('Unable to read changed lines from Git: %s', trim((string) $errorOutput)));
    }

    $changedLines = parseAddedLines($diff);
    if ($changedLines === []) {
        return null;
    }

    $covered = 0;
    $executable = 0;
    foreach ($fileNodes as $fileNode) {
        if (!$fileNode instanceof DOMElement) {
            continue;
        }
        $relativePath = relativeSourcePath($fileNode->getAttribute('name'));
        if ($relativePath === null || !isset($changedLines[$relativePath])) {
            continue;
        }

        $lineNodes = $xpath->query('./line[@type="stmt"]', $fileNode);
        if ($lineNodes === false) {
            continue;
        }
        foreach ($lineNodes as $lineNode) {
            if (!$lineNode instanceof DOMElement) {
                continue;
            }
            $lineNumber = (int) $lineNode->getAttribute('num');
            if (!isset($changedLines[$relativePath][$lineNumber])) {
                continue;
            }
            $executable++;
            if ((int) $lineNode->getAttribute('count') > 0) {
                $covered++;
            }
        }
    }

    return $executable === 0 ? null : ($covered / $executable) * 100;
}

/**
 * @return array<string, array<int, true>>
 */
function parseAddedLines(string $diff): array
{
    $changedLines = [];
    $currentPath = null;
    foreach (preg_split('/\R/', $diff) ?: [] as $line) {
        if (str_starts_with($line, '+++ b/')) {
            $currentPath = substr($line, 6);
            continue;
        }
        if ($currentPath === null || preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $matches) !== 1) {
            continue;
        }

        $start = (int) $matches[1];
        $count = isset($matches[2]) ? (int) $matches[2] : 1;
        for ($lineNumber = $start; $lineNumber < $start + $count; $lineNumber++) {
            $changedLines[$currentPath][$lineNumber] = true;
        }
    }

    return $changedLines;
}

function relativeSourcePath(string $path): ?string
{
    $normalizedPath = str_replace('\\', '/', $path);
    $sourcePosition = strpos($normalizedPath, '/src/');
    if ($sourcePosition === false) {
        return null;
    }

    return substr($normalizedPath, $sourcePosition + 1);
}
