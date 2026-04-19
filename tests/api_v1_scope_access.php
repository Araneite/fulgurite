<?php

declare(strict_types=1);

function fail(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fail(
            $message
            . "\nExpected: " . var_export($expected, true)
            . "\nActual: " . var_export($actual, true)
        );
    }
}

function assertTrueValue(bool $condition, string $message): void {
    if (!$condition) {
        fail($message);
    }
}

function headerValue(array $headers, string $name): ?string {
    $needle = strtolower($name) . ':';
    foreach ($headers as $header) {
        if (str_starts_with(strtolower($header), $needle)) {
            return trim(substr($header, strlen($needle)));
        }
    }

    return null;
}

function removeDirectory(string $path): void {
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            retryFilesystemOperation(static fn(): bool => @rmdir($item->getPathname()));
            continue;
        }

        retryFilesystemOperation(static fn(): bool => @unlink($item->getPathname()));
    }

    retryFilesystemOperation(static fn(): bool => @rmdir($path));
}

function retryFilesystemOperation(callable $operation, int $attempts = 10, int $delayMicros = 100000): void {
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        if ($operation()) {
            return;
        }
        usleep($delayMicros);
    }
}

function resetDatabaseConnections(): void {
    $reflection = new ReflectionClass(Database::class);
    foreach (['instance', 'indexInstance'] as $propertyName) {
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}

function writePrependFile(string $path): void {
    file_put_contents($path, "<?php\ndefine('FULGURITE_CLI', true);\n");
}

function parseCgiResponse(string $stdout): array {
    $parts = preg_split("/\r\n\r\n|\n\n/", $stdout, 2);
    if (!is_array($parts) || count($parts) !== 2) {
        fail("Unable to parse CGI response.\nSTDOUT:\n" . trim($stdout));
    }

    $headerLines = preg_split("/\r\n|\n|\r/", trim($parts[0])) ?: [];
    $statusHeader = headerValue($headerLines, 'Status');
    $status = 200;
    if ($statusHeader !== null) {
        if (preg_match('/^(\d{3})\b/', $statusHeader, $matches) !== 1) {
            fail("CGI response is missing a valid Status header.\nHeaders:\n" . implode("\n", $headerLines));
        }
        $status = (int) $matches[1];
    }

    return [
        'status' => $status,
        'headers' => $headerLines,
        'body' => $parts[1],
    ];
}

function executeCgiRequest(string $root, string $tmp, string $method, string $uri, ?string $token = null, ?array $body = null, array $extraEnv = []): array {
    $prependPath = $tmp . DIRECTORY_SEPARATOR . 'prepend.php';
    writePrependFile($prependPath);

    $phpCgi = getenv('PHP_CGI_BINARY') ?: (dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'php-cgi.exe');
    if (!is_file($phpCgi)) {
        fail('php-cgi binary not found.');
    }
    $command = '"'
        . str_replace('"', '\"', $phpCgi)
        . '" -d auto_prepend_file="'
        . str_replace('"', '\"', $prependPath)
        . '"';

    $payload = $body !== null ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
    if ($payload === false) {
        fail('Unable to encode JSON body.');
    }

    $env = array_merge($_ENV, [
        'REDIRECT_STATUS' => '1',
        'GATEWAY_INTERFACE' => 'CGI/1.1',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'REQUEST_METHOD' => $method,
        'REQUEST_URI' => $uri,
        'SCRIPT_NAME' => '/api/v1/index.php',
        'SCRIPT_FILENAME' => $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'v1' . DIRECTORY_SEPARATOR . 'index.php',
        'DOCUMENT_ROOT' => $root . DIRECTORY_SEPARATOR . 'public',
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_HOST' => 'localhost',
        'CONTENT_LENGTH' => (string) strlen($payload),
        'CONTENT_TYPE' => $payload !== '' ? 'application/json' : '',
        'DB_DRIVER' => 'sqlite',
        'DB_PATH' => $tmp . DIRECTORY_SEPARATOR . 'fulgurite.db',
        'SEARCH_DB_PATH' => $tmp . DIRECTORY_SEPARATOR . 'fulgurite-search.db',
    ], $extraEnv);

    if ($token !== null) {
        $env['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, $root, $env);
    if (!is_resource($process)) {
        fail('Unable to start php-cgi.');
    }

    fwrite($pipes[0], $payload);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        fail("php-cgi request failed.\nSTDERR:\n" . trim((string) $stderr));
    }

    return parseCgiResponse((string) $stdout);
}

function decodeJsonBody(array $response): array {
    $decoded = json_decode((string) $response['body'], true);
    if (!is_array($decoded)) {
        fail("Unable to decode JSON response.\nBody:\n" . (string) $response['body']);
    }

    return $decoded;
}

function requestJson(string $root, string $tmp, string $method, string $uri, string $token, ?array $body = null, array $extraEnv = []): array {
    $response = executeCgiRequest($root, $tmp, $method, $uri, $token, $body, $extraEnv);
    return [
        'status' => $response['status'],
        'headers' => $response['headers'],
        'json' => decodeJsonBody($response),
    ];
}

function seedRestoreHistory(int $repoId, string $repoName, string $snapshotId): int {
    $db = Database::getInstance();
    $db->prepare("
        INSERT INTO restore_history (repo_id, repo_name, snapshot_id, mode, target, status, started_by)
        VALUES (?, ?, ?, 'local', ?, 'success', 'test')
    ")->execute([$repoId, $repoName, $snapshotId, 'C:\\restore-target']);

    return (int) $db->lastInsertId();
}

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fulgurite-api-scope-test-' . bin2hex(random_bytes(4));
if (!mkdir($tmp, 0700, true) && !is_dir($tmp)) {
    fail('Unable to create test temp directory.');
}

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . DIRECTORY_SEPARATOR . 'fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . DIRECTORY_SEPARATOR . 'fulgurite-search.db');
putenv('FULGURITE_SECRET_PROVIDER=local');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . DIRECTORY_SEPARATOR . 'fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . DIRECTORY_SEPARATOR . 'fulgurite-search.db';
$_ENV['FULGURITE_SECRET_PROVIDER'] = 'local';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . DIRECTORY_SEPARATOR . 'fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . DIRECTORY_SEPARATOR . 'fulgurite-search.db';
$_SERVER['FULGURITE_SECRET_PROVIDER'] = 'local';

require_once $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

try {
    Database::setSetting('api_enabled', '1');

    $userId = UserManager::createUser([
        'username' => 'scope-user-' . bin2hex(random_bytes(4)),
        'password_hash' => password_hash('test-password', PASSWORD_DEFAULT),
        'role' => ROLE_RESTORE_OPERATOR,
        'repo_scope_mode' => 'selected',
        'repo_scope_json' => [],
        'host_scope_mode' => 'selected',
        'host_scope_json' => [],
    ]);
    $scopedManagerId = UserManager::createUser([
        'username' => 'scoped-manager-' . bin2hex(random_bytes(4)),
        'password_hash' => password_hash('test-password', PASSWORD_DEFAULT),
        'role' => ROLE_RESTORE_OPERATOR,
        'permissions_json' => [
            'repos.view' => true,
            'repos.manage' => true,
            'hosts.manage' => true,
            'stats.view' => true,
        ],
        'repo_scope_mode' => 'selected',
        'repo_scope_json' => [],
        'host_scope_mode' => 'selected',
        'host_scope_json' => [],
    ]);

    $allowedRepoId = RepoManager::add('Allowed Repo', $tmp . DIRECTORY_SEPARATOR . 'repo-allowed', 'pw');
    $deniedRepoId = RepoManager::add('Denied Repo', $tmp . DIRECTORY_SEPARATOR . 'repo-denied', 'pw');
    $allowedHostId = HostManager::add('Allowed Host', 'allowed.example.test');
    $deniedHostId = HostManager::add('Denied Host', 'denied.example.test');

    UserManager::updateAccessPolicy($userId, [
        'repo_scope_mode' => 'selected',
        'repo_scope_json' => [$allowedRepoId],
        'host_scope_mode' => 'selected',
        'host_scope_json' => [$allowedHostId],
    ]);
    UserManager::updateAccessPolicy($scopedManagerId, [
        'permissions_json' => [
            'repos.view' => true,
            'repos.manage' => true,
            'hosts.manage' => true,
            'stats.view' => true,
        ],
        'repo_scope_mode' => 'selected',
        'repo_scope_json' => [$allowedRepoId],
        'host_scope_mode' => 'selected',
        'host_scope_json' => [$allowedHostId],
    ]);

    $createdToken = ApiTokenManager::create($userId, [
        'name' => 'scope-token',
        'scopes' => [
            'backup_jobs:read',
            'backup_jobs:write',
            'backup_jobs:run',
            'copy_jobs:read',
            'copy_jobs:write',
            'copy_jobs:run',
            'restores:read',
            'restores:write',
        ],
    ], $userId);
    $token = (string) $createdToken['secret'];
    $scopedManagerToken = (string) ApiTokenManager::create($scopedManagerId, [
        'name' => 'scoped-manager-token',
        'scopes' => [
            'repos:read',
            'repos:write',
            'repos:check',
            'hosts:read',
            'hosts:write',
            'stats:read',
        ],
    ], $scopedManagerId)['secret'];

    $allowedBackupId = BackupJobManager::add('Allowed backup', $allowedRepoId, ['C:\\data'], [], [], '', 0, 2, '1', 0, $allowedHostId);
    $localBackupId = BackupJobManager::add('Local backup', $allowedRepoId, ['C:\\local'], [], [], '', 0, 2, '1', 0, null);
    $deniedRepoBackupId = BackupJobManager::add('Denied repo backup', $deniedRepoId, ['C:\\repo-denied'], [], [], '', 0, 2, '1', 0, $allowedHostId);
    $deniedHostBackupId = BackupJobManager::add('Denied host backup', $allowedRepoId, ['C:\\host-denied'], [], [], '', 0, 2, '1', 0, $deniedHostId);

    $allowedCopyId = CopyJobManager::add('Allowed copy', $allowedRepoId, 'C:\\copy-allowed', 'pw');
    $deniedCopyId = CopyJobManager::add('Denied copy', $deniedRepoId, 'C:\\copy-denied', 'pw');

    $allowedRestoreId = seedRestoreHistory($allowedRepoId, 'Allowed Repo', 'snap-allowed');
    $deniedRestoreId = seedRestoreHistory($deniedRepoId, 'Denied Repo', 'snap-denied');
    Database::getInstance()->prepare("
        INSERT INTO repo_runtime_status (repo_id, repo_name, snapshot_count, last_snapshot_time, hours_ago, status, updated_at)
        VALUES (?, ?, ?, datetime('now'), ?, ?, datetime('now'))
    ")->execute([$allowedRepoId, 'Allowed Repo', 4, 1.25, 'ok']);
    Database::getInstance()->prepare("
        INSERT INTO repo_runtime_status (repo_id, repo_name, snapshot_count, last_snapshot_time, hours_ago, status, updated_at)
        VALUES (?, ?, ?, datetime('now'), ?, ?, datetime('now'))
    ")->execute([$deniedRepoId, 'Denied Repo', 8, 48.0, 'error']);

    $backupIndex = requestJson($root, $tmp, 'GET', '/api/v1/backup-jobs', $token);
    assertSameValue(200, $backupIndex['status'], 'Backup jobs index should succeed.');
    assertSameValue(2, count($backupIndex['json']['data']), 'Backup jobs index should only include allowed repo and host scope results.');
    assertSameValue([$allowedBackupId, $localBackupId], array_column($backupIndex['json']['data'], 'id'), 'Backup jobs index should not leak out-of-scope jobs.');

    $copyIndex = requestJson($root, $tmp, 'GET', '/api/v1/copy-jobs', $token);
    assertSameValue(200, $copyIndex['status'], 'Copy jobs index should succeed.');
    assertSameValue([$allowedCopyId], array_column($copyIndex['json']['data'], 'id'), 'Copy jobs index should only include authorized source repos.');

    $restoreIndex = requestJson($root, $tmp, 'GET', '/api/v1/restores?page=1&per_page=10', $token);
    assertSameValue(200, $restoreIndex['status'], 'Restore history index should succeed.');
    assertSameValue(1, $restoreIndex['json']['meta']['total'], 'Restore history total should exclude out-of-scope rows.');
    assertSameValue([$allowedRestoreId], array_column($restoreIndex['json']['data'], 'id'), 'Restore history should only list authorized rows.');

    $deniedBackupShow = requestJson($root, $tmp, 'GET', '/api/v1/backup-jobs/' . $deniedRepoBackupId, $token);
    assertSameValue(403, $deniedBackupShow['status'], 'Backup show must reject out-of-scope repo access.');

    $deniedHostBackupShow = requestJson($root, $tmp, 'GET', '/api/v1/backup-jobs/' . $deniedHostBackupId, $token);
    assertSameValue(403, $deniedHostBackupShow['status'], 'Backup show must reject out-of-scope host access.');

    $deniedCopyShow = requestJson($root, $tmp, 'GET', '/api/v1/copy-jobs/' . $deniedCopyId, $token);
    assertSameValue(403, $deniedCopyShow['status'], 'Copy show must reject out-of-scope repo access.');

    $deniedRestoreShow = requestJson($root, $tmp, 'GET', '/api/v1/restores/' . $deniedRestoreId, $token);
    assertSameValue(403, $deniedRestoreShow['status'], 'Restore show must reject out-of-scope repo access.');

    $backupCountBeforeCreate = count(BackupJobManager::getAll());
    $unauthorizedBackupCreate = requestJson($root, $tmp, 'POST', '/api/v1/backup-jobs', $token, [
        'name' => 'Blocked backup',
        'repo_id' => $allowedRepoId,
        'host_id' => $deniedHostId,
    ], ['HTTP_X_DRY_RUN' => '1']);
    assertSameValue(403, $unauthorizedBackupCreate['status'], 'Backup create must reject unauthorized host references.');
    assertSameValue($backupCountBeforeCreate, count(BackupJobManager::getAll()), 'Unauthorized backup create must not persist a new job.');

    $copyCountBeforeCreate = count(CopyJobManager::getAll());
    $unauthorizedCopyCreate = requestJson($root, $tmp, 'POST', '/api/v1/copy-jobs', $token, [
        'name' => 'Blocked copy',
        'source_repo_id' => $deniedRepoId,
        'dest_path' => 'C:\\blocked-copy',
        'dest_password' => 'pw',
    ], ['HTTP_X_DRY_RUN' => '1']);
    assertSameValue(403, $unauthorizedCopyCreate['status'], 'Copy create must reject unauthorized repo references.');
    assertSameValue($copyCountBeforeCreate, count(CopyJobManager::getAll()), 'Unauthorized copy create must not persist a new job.');

    $unauthorizedRestoreCreate = requestJson($root, $tmp, 'POST', '/api/v1/restores', $token, [
        'repo_id' => $deniedRepoId,
        'snapshot_id' => 'snap-denied',
    ], ['HTTP_X_DRY_RUN' => '1']);
    assertSameValue(403, $unauthorizedRestoreCreate['status'], 'Restore create must reject unauthorized repos.');

    $unauthorizedBackupUpdate = requestJson($root, $tmp, 'PATCH', '/api/v1/backup-jobs/' . $deniedRepoBackupId, $token, [
        'name' => 'Tampered backup',
    ]);
    assertSameValue(403, $unauthorizedBackupUpdate['status'], 'Backup update must reject out-of-scope existing resources.');
    assertSameValue('Denied repo backup', BackupJobManager::getById($deniedRepoBackupId)['name'] ?? null, 'Unauthorized backup update must not change the job.');

    $reassignBackupRepo = requestJson($root, $tmp, 'PATCH', '/api/v1/backup-jobs/' . $allowedBackupId, $token, [
        'repo_id' => $deniedRepoId,
    ]);
    assertSameValue(403, $reassignBackupRepo['status'], 'Backup update must reject reassignment to an unauthorized repo.');
    assertSameValue($allowedRepoId, (int) (BackupJobManager::getById($allowedBackupId)['repo_id'] ?? 0), 'Unauthorized backup repo reassignment must not persist.');

    $reassignBackupHost = requestJson($root, $tmp, 'PATCH', '/api/v1/backup-jobs/' . $allowedBackupId, $token, [
        'host_id' => $deniedHostId,
    ]);
    assertSameValue(403, $reassignBackupHost['status'], 'Backup update must reject reassignment to an unauthorized host.');
    assertSameValue($allowedHostId, (int) (BackupJobManager::getById($allowedBackupId)['host_id'] ?? 0), 'Unauthorized backup host reassignment must not persist.');

    $reassignCopyRepo = requestJson($root, $tmp, 'PATCH', '/api/v1/copy-jobs/' . $allowedCopyId, $token, [
        'source_repo_id' => $deniedRepoId,
    ]);
    assertSameValue(403, $reassignCopyRepo['status'], 'Copy update must reject reassignment to an unauthorized repo.');
    assertSameValue($allowedRepoId, (int) (CopyJobManager::getById($allowedCopyId)['source_repo_id'] ?? 0), 'Unauthorized copy repo reassignment must not persist.');

    $unauthorizedCopyDelete = requestJson($root, $tmp, 'DELETE', '/api/v1/copy-jobs/' . $deniedCopyId, $token);
    assertSameValue(403, $unauthorizedCopyDelete['status'], 'Copy delete must reject out-of-scope resources.');
    assertTrueValue(CopyJobManager::getById($deniedCopyId) !== null, 'Unauthorized copy delete must not remove the job.');

    $unauthorizedBackupDelete = requestJson($root, $tmp, 'DELETE', '/api/v1/backup-jobs/' . $deniedRepoBackupId, $token);
    assertSameValue(403, $unauthorizedBackupDelete['status'], 'Backup delete must reject out-of-scope resources.');
    assertTrueValue(BackupJobManager::getById($deniedRepoBackupId) !== null, 'Unauthorized backup delete must not remove the job.');

    $unauthorizedBackupRun = requestJson($root, $tmp, 'POST', '/api/v1/backup-jobs/' . $deniedRepoBackupId . '/run', $token, null, [
        'HTTP_X_DRY_RUN' => '1',
    ]);
    assertSameValue(403, $unauthorizedBackupRun['status'], 'Backup run must reject out-of-scope resources.');

    $unauthorizedCopyRun = requestJson($root, $tmp, 'POST', '/api/v1/copy-jobs/' . $deniedCopyId . '/run', $token, null, [
        'HTTP_X_DRY_RUN' => '1',
    ]);
    assertSameValue(403, $unauthorizedCopyRun['status'], 'Copy run must reject out-of-scope resources.');

    $repoIndex = requestJson($root, $tmp, 'GET', '/api/v1/repos', $scopedManagerToken);
    assertSameValue(200, $repoIndex['status'], 'Repo index should succeed for a scoped manager token.');
    assertSameValue([$allowedRepoId], array_column($repoIndex['json']['data'], 'id'), 'Repo index must not leak out-of-scope repos.');

    $deniedRepoShow = requestJson($root, $tmp, 'GET', '/api/v1/repos/' . $deniedRepoId, $scopedManagerToken);
    assertSameValue(403, $deniedRepoShow['status'], 'Repo show must reject out-of-scope repos.');

    $repoCountBeforeCreate = count(RepoManager::getAll());
    $unauthorizedRepoCreate = requestJson($root, $tmp, 'POST', '/api/v1/repos', $scopedManagerToken, [
        'name' => 'Blocked repo',
        'path' => $tmp . DIRECTORY_SEPARATOR . 'repo-blocked',
        'password' => 'pw',
    ], ['HTTP_X_DRY_RUN' => '1']);
    assertSameValue(403, $unauthorizedRepoCreate['status'], 'Repo create must reject scoped tokens because the new repo would be outside scope.');
    assertSameValue($repoCountBeforeCreate, count(RepoManager::getAll()), 'Unauthorized repo create must not persist a new repo.');

    $unauthorizedRepoUpdate = requestJson($root, $tmp, 'PATCH', '/api/v1/repos/' . $deniedRepoId, $scopedManagerToken, [
        'name' => 'Tampered repo',
    ]);
    assertSameValue(403, $unauthorizedRepoUpdate['status'], 'Repo update must reject out-of-scope repos.');
    assertSameValue('Denied Repo', RepoManager::getById($deniedRepoId)['name'] ?? null, 'Unauthorized repo update must not change the repo.');

    $unauthorizedRepoDelete = requestJson($root, $tmp, 'DELETE', '/api/v1/repos/' . $deniedRepoId, $scopedManagerToken);
    assertSameValue(403, $unauthorizedRepoDelete['status'], 'Repo delete must reject out-of-scope repos.');
    assertTrueValue(RepoManager::getById($deniedRepoId) !== null, 'Unauthorized repo delete must not remove the repo.');

    $unauthorizedRepoCheck = requestJson($root, $tmp, 'POST', '/api/v1/repos/' . $deniedRepoId . '/check', $scopedManagerToken);
    assertSameValue(403, $unauthorizedRepoCheck['status'], 'Repo check must reject out-of-scope repos.');

    $unauthorizedRepoStats = requestJson($root, $tmp, 'GET', '/api/v1/repos/' . $deniedRepoId . '/stats', $scopedManagerToken);
    assertSameValue(403, $unauthorizedRepoStats['status'], 'Per-repo stats must reject out-of-scope repos.');

    $hostIndex = requestJson($root, $tmp, 'GET', '/api/v1/hosts', $scopedManagerToken);
    assertSameValue(200, $hostIndex['status'], 'Host index should succeed for a scoped manager token.');
    assertSameValue([$allowedHostId], array_column($hostIndex['json']['data'], 'id'), 'Host index must not leak out-of-scope hosts.');

    $deniedHostShow = requestJson($root, $tmp, 'GET', '/api/v1/hosts/' . $deniedHostId, $scopedManagerToken);
    assertSameValue(403, $deniedHostShow['status'], 'Host show must reject out-of-scope hosts.');

    $hostCountBeforeCreate = count(HostManager::getAll());
    $unauthorizedHostCreate = requestJson($root, $tmp, 'POST', '/api/v1/hosts', $scopedManagerToken, [
        'name' => 'Blocked host',
        'hostname' => 'blocked.example.test',
    ], ['HTTP_X_DRY_RUN' => '1']);
    assertSameValue(403, $unauthorizedHostCreate['status'], 'Host create must reject scoped tokens because the new host would be outside scope.');
    assertSameValue($hostCountBeforeCreate, count(HostManager::getAll()), 'Unauthorized host create must not persist a new host.');

    $unauthorizedHostUpdate = requestJson($root, $tmp, 'PATCH', '/api/v1/hosts/' . $deniedHostId, $scopedManagerToken, [
        'name' => 'Tampered host',
    ]);
    assertSameValue(403, $unauthorizedHostUpdate['status'], 'Host update must reject out-of-scope hosts.');
    assertSameValue('Denied Host', HostManager::getById($deniedHostId)['name'] ?? null, 'Unauthorized host update must not change the host.');

    $unauthorizedHostDelete = requestJson($root, $tmp, 'DELETE', '/api/v1/hosts/' . $deniedHostId, $scopedManagerToken);
    assertSameValue(403, $unauthorizedHostDelete['status'], 'Host delete must reject out-of-scope hosts.');
    assertTrueValue(HostManager::getById($deniedHostId) !== null, 'Unauthorized host delete must not remove the host.');

    $unauthorizedHostTest = requestJson($root, $tmp, 'POST', '/api/v1/hosts/' . $deniedHostId . '/test', $scopedManagerToken);
    assertSameValue(403, $unauthorizedHostTest['status'], 'Host test must reject out-of-scope hosts.');

    $statsSummary = requestJson($root, $tmp, 'GET', '/api/v1/stats/summary', $scopedManagerToken);
    assertSameValue(200, $statsSummary['status'], 'Scoped stats summary should succeed.');
    assertSameValue(1, $statsSummary['json']['data']['repos'] ?? null, 'Stats summary must only count authorized repos.');
    assertSameValue(1, $statsSummary['json']['data']['hosts'] ?? null, 'Stats summary must only count authorized hosts.');
    assertSameValue(2, $statsSummary['json']['data']['backup_jobs'] ?? null, 'Stats summary must only count authorized backup jobs.');
    assertSameValue(1, $statsSummary['json']['data']['copy_jobs'] ?? null, 'Stats summary must only count authorized copy jobs.');
    assertSameValue(1, $statsSummary['json']['data']['restores'] ?? null, 'Stats summary must only count authorized restores.');
    assertSameValue(null, $statsSummary['json']['data']['users'] ?? null, 'Scoped stats summary must not expose global user counts.');
    assertSameValue(['ok' => 1], $statsSummary['json']['data']['repo_status_breakdown'] ?? null, 'Stats summary must only include authorized repo status aggregates.');

    $repoRuntime = requestJson($root, $tmp, 'GET', '/api/v1/stats/repo-runtime', $scopedManagerToken);
    assertSameValue(200, $repoRuntime['status'], 'Repo runtime stats should succeed.');
    assertSameValue([$allowedRepoId], array_column($repoRuntime['json']['data'], 'repo_id'), 'Repo runtime stats must not leak out-of-scope repos.');
} finally {
    resetDatabaseConnections();
    removeDirectory($tmp);
}

echo "API v1 scope access tests OK.\n";
