<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-run-log-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';

require_once $root . '/src/bootstrap.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue(bool $value, string $message): void {
    if (!$value) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

try {
    $db = Database::getInstance();
    $db->prepare("
        INSERT INTO users (username, password, role, permissions_json)
        VALUES (?, ?, ?, ?)
    ")->execute([
        'run-log-user',
        password_hash('test', PASSWORD_DEFAULT),
        'viewer',
        json_encode(['backup_jobs.manage' => true], JSON_THROW_ON_ERROR),
    ]);

    $_SESSION['user_id'] = (int) $db->lastInsertId();
    $_SESSION['username'] = 'run-log-user';
    $_SESSION['role'] = 'viewer';
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SERVER['REQUEST_URI'] = '/api/poll_backup_log.php';

    $run = RunLogManager::createRun('quick_backup');
    $runId = (string) $run['run_id'];

    assertTrueValue(preg_match('/^[a-f0-9]{32}$/', $runId) === 1, 'Run id should be a 128-bit random hex token.');
    assertTrueValue(str_contains((string) $run['log_file'], 'fulgurite_quick_backup_' . $runId), 'Run log file should use the typed prefix and run id.');
    assertTrueValue(str_contains((string) ($run['result_file'] ?? ''), 'fulgurite_quick_backup_' . $runId), 'Run result file should use the typed prefix and run id.');

    $stmt = $db->prepare("SELECT type, user_id, permission_required, expires_at FROM job_log_runs WHERE run_id = ?");
    $stmt->execute([$runId]);
    $metadata = $stmt->fetch();

    assertTrueValue(is_array($metadata), 'Run metadata was not stored.');
    assertSameValue('quick_backup', $metadata['type'], 'Run type was not stored.');
    assertSameValue((int) $_SESSION['user_id'], (int) $metadata['user_id'], 'Run owner was not stored.');
    assertSameValue('backup_jobs.manage', $metadata['permission_required'], 'Run permission was not stored.');
    assertTrueValue(strtotime((string) $metadata['expires_at']) > time(), 'Run expiry should be in the future.');

    $authorized = RunLogManager::requireAccessibleRun('quick_backup', $runId);
    assertSameValue($runId, $authorized['run_id'], 'Creator should be authorized to read its run metadata.');

    RunLogManager::deleteRunMetadata($runId);
    $stmt = $db->prepare("SELECT COUNT(*) FROM job_log_runs WHERE run_id = ?");
    $stmt->execute([$runId]);
    assertSameValue(0, (int) $stmt->fetchColumn(), 'Run metadata should be deleted.');

    echo "RunLogManager tests OK.\n";
} finally {
    Database::getInstance()->exec('PRAGMA optimize');
}
