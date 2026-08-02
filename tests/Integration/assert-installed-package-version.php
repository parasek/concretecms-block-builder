<?php

declare(strict_types=1);

if (count($argv) !== 6) {
    fwrite(STDERR, "Usage: php assert-installed-package-version.php <host> <database> <username> <password> <expected-version>\n");
    exit(2);
}

[$scriptPath, $host, $databaseName, $username, $password, $expectedVersion] = $argv;
unset($scriptPath);

if (preg_match('/\A(?:127\.0\.0\.1|localhost)(?::\d+)?\z/D', $host) !== 1) {
    throw new RuntimeException('Package-version checks may connect only to a loopback database host.');
}
if (preg_match('/\Ablock_builder_test_[a-z0-9_]+\z/D', $databaseName) !== 1) {
    throw new RuntimeException('Package-version checks require a disposable block_builder_test_* database.');
}
if (preg_match('/\A\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?\z/D', $expectedVersion) !== 1) {
    throw new RuntimeException('The expected package version is not a supported semantic version.');
}

$hostParts = explode(':', $host, 2);
$databaseHost = $hostParts[0];
$databasePort = $hostParts[1] ?? null;
if ($databasePort !== null && ((int) $databasePort < 1 || (int) $databasePort > 65535)) {
    throw new RuntimeException('The loopback database port must be between 1 and 65535.');
}
$dataSourceName = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $databaseHost, $databaseName);
if ($databasePort !== null) {
    $dataSourceName .= ';port=' . $databasePort;
}

$connection = new PDO(
    $dataSourceName,
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$statement = $connection->prepare('SELECT pkgVersion FROM Packages WHERE pkgHandle = :handle AND pkgIsInstalled = 1');
$statement->execute(['handle' => 'block_builder']);
$installedVersion = $statement->fetchColumn();
if (!is_string($installedVersion) || !hash_equals($expectedVersion, $installedVersion)) {
    throw new RuntimeException(sprintf(
        'Expected installed Block Builder version "%s", found "%s".',
        $expectedVersion,
        is_scalar($installedVersion) ? (string) $installedVersion : 'none',
    ));
}

printf("Installed Block Builder version: %s\n", $installedVersion);
