<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/src/DatabaseConfigWriter.php';

function failTest(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        failTest(
            $message
            . "\nExpected: " . var_export($expected, true)
            . "\nActual: " . var_export($actual, true)
        );
    }
}

function runGeneratorScript(string $scriptPath, string $outputPath, array $env): void
{
    $command = escapeshellarg(PHP_BINARY)
        . ' '
        . escapeshellarg($scriptPath)
        . ' '
        . escapeshellarg($outputPath);

    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__), $env);
    if (!is_resource($process)) {
        failTest('Unable to start generator PHP process.');
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        failTest(
            "Generator process failed.\nSTDOUT:\n"
            . trim((string) $stdout)
            . "\nSTDERR:\n"
            . trim((string) $stderr)
        );
    }
}

function readConfigValueInChild(string $scriptPath, string $configPath): array
{
    $command = escapeshellarg(PHP_BINARY)
        . ' '
        . escapeshellarg($scriptPath)
        . ' '
        . escapeshellarg($configPath);

    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        failTest('Unable to start child PHP process.');
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        failTest("Child process failed.\nSTDERR:\n" . trim((string) $stderr));
    }

    try {
        $decoded = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        failTest("Child process returned invalid JSON.\nSTDOUT:\n" . trim((string) $stdout));
    }

    if (!is_array($decoded)) {
        failTest('Child process returned an unexpected payload.');
    }

    return $decoded;
}

$dbPass = "pa'ss\\with\\slashes\nsecond line";
$tmpDir = sys_get_temp_dir() . '/fulgurite-db-config-' . bin2hex(random_bytes(6));
$configPath = $tmpDir . '/database.php';
$scriptPath = $root . '/scripts/generate-database-config.php';
$readerScript = __DIR__ . '/fixtures/read_database_config.php';

if (!mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
    failTest('Unable to create temporary test directory.');
}

try {
    $content = DatabaseConfigWriter::buildConfigPhp([
        'DB_DRIVER' => 'mysql',
        'DB_PATH' => $tmpDir . '/fulgurite.db',
        'SEARCH_DB_PATH' => $tmpDir . '/fulgurite-search.db',
        'DB_HOST' => 'db.internal',
        'DB_PORT' => '3306',
        'DB_NAME' => 'fulgurite',
        'DB_USER' => 'fulgurite',
        'DB_PASS' => $dbPass,
        'DB_CHARSET' => 'utf8mb4',
    ]);

    assertSameValue(
        "define('DB_PASS', " . var_export($dbPass, true) . ');',
        str_contains($content, "define('DB_PASS', " . var_export($dbPass, true) . ');')
            ? "define('DB_PASS', " . var_export($dbPass, true) . ');'
            : null,
        'Generated config must write DB_PASS using var_export().'
    );

    $env = [];
    foreach (['PATH', 'SystemRoot', 'ComSpec', 'PATHEXT', 'TEMP', 'TMP', 'OS'] as $key) {
        $value = getenv($key);
        if ($value !== false) {
            $env[$key] = (string) $value;
        }
    }
    $env['DB_DRIVER'] = 'mysql';
    $env['DB_PATH'] = $tmpDir . '/fulgurite.db';
    $env['SEARCH_DB_PATH'] = $tmpDir . '/fulgurite-search.db';
    $env['DB_HOST'] = 'db.internal';
    $env['DB_PORT'] = '3306';
    $env['DB_NAME'] = 'fulgurite';
    $env['DB_USER'] = 'fulgurite';
    $env['DB_PASS'] = $dbPass;
    $env['DB_CHARSET'] = 'utf8mb4';

    runGeneratorScript($scriptPath, $configPath, $env);

    $generatedContent = (string) file_get_contents($configPath);
    assertSameValue($content, $generatedContent, 'CLI generator output should match the shared writer output.');

    $loaded = readConfigValueInChild($readerScript, $configPath);
    assertSameValue($dbPass, $loaded['DB_PASS'] ?? null, 'Loaded DB_PASS should preserve apostrophe, backslash, and newline.');

    echo "Database config writer tests OK.\n";
} finally {
    if (is_file($configPath)) {
        @unlink($configPath);
    }
    if (is_dir($tmpDir)) {
        @rmdir($tmpDir);
    }
}
