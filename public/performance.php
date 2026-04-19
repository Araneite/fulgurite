<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('performance.view');

$title = t('performance.title');
$active = 'performance';
$subtitle = t('performance.subtitle');

function statFileInfo(string $path): array {
    return [
        'path' => $path,
        'exists' => file_exists($path),
        'size' => file_exists($path) ? (int) filesize($path) : 0,
        'modified_at' => file_exists($path) ? formatTimestampForDisplay((int) filemtime($path)) : null,
    ];
}

function statDirInfo(string $path): array {
    $size = 0;
    $files = 0;
    $errors = 0;
    if (is_dir($path)) {
        try {
            $directoryIterator = new RecursiveDirectoryIterator(
                $path,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME
            );
            $filter = new RecursiveCallbackFilterIterator(
                $directoryIterator,
                static function (SplFileInfo $current) use (&$errors): bool {
                    try {
                        if ($current->isDir()) {
                            return $current->isReadable();
                        }
                        return true;
                    } catch (Throwable $e) {
                        $errors++;
                        return false;
                    }
                }
            );
            $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $item) {
                try {
                    if ($item->isFile()) {
                        $size += (int) $item->getSize();
                        $files++;
                    }
                } catch (Throwable $e) {
                    $errors++;
                }
            }
        } catch (Throwable $e) {
            $errors++;
        }
    }

    return [
        'path' => $path,
        'exists' => is_dir($path),
        'size' => $size,
        'files' => $files,
        'errors' => $errors,
    ];
}

function tailFileLines(string $path, int $limit = 20): array {
    if (!is_file($path)) {
        return [];
    }

    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        return [];
    }

    $limit = max(1, $limit);
    $buffer = '';
    $chunkSize = 4096;
    $position = -1;
    $lineCount = 0;

    fseek($handle, 0, SEEK_END);
    $fileSize = ftell($handle);
    if ($fileSize === false || $fileSize === 0) {
        fclose($handle);
        return [];
    }

    while (-$position <= $fileSize) {
        $seek = max(-$fileSize, $position - $chunkSize + 1);
        $readLength = abs($position - $seek) + 1;
        fseek($handle, $seek, SEEK_END);
        $chunk = fread($handle, $readLength);
        if ($chunk === false || $chunk === '') {
            break;
        }

        $buffer = $chunk . $buffer;
        $lineCount = substr_count($buffer, "\n");
        if ($lineCount > $limit) {
            break;
        }

        $position = $seek - 1;
    }

    fclose($handle);

    $lines = preg_split('/\r?\n/', trim($buffer));
    if (!is_array($lines)) {
        return [];
    }

    $lines = array_values(array_filter($lines, static fn(string $line): bool => $line !== ''));
    return array_slice($lines, -$limit);
}

function sqliteTableExists(PDO $db, string $table): bool {
    $stmt = $db->prepare("
        SELECT 1
        FROM sqlite_master
        WHERE type = 'table' AND name = ?
        LIMIT 1
    ");
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function safeTableCount(PDO $db, string $table): int {
    if (!sqliteTableExists($db, $table)) {
        return 0;
    }

    return (int) $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

function commandTrimmed(array $command): string {
    $result = ProcessRunner::run($command, ['validate_binary' => false]);
    return trim((string) ($result['stdout'] ?? $result['output'] ?? ''));
}

function readProcMeminfo(): array {
    $path = '/proc/meminfo';
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (!str_contains($line, ':')) {
            continue;
        }
        [$key, $value] = explode(':', $line, 2);
        if (preg_match('/(\d+)/', $value, $matches)) {
            $values[trim($key)] = (int) $matches[1] * 1024;
        }
    }

    return $values;
}

function readPressureMetric(string $path): ?array {
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $line = trim((string) (($lines[0] ?? '')));
    if ($line === '') {
        return null;
    }

    $metrics = [];
    foreach (preg_split('/\s+/', $line) as $chunk) {
        if (!str_contains($chunk, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $chunk, 2);
        $metrics[$key] = is_numeric($value) ? (float) $value : $value;
    }

    return $metrics;
}

function getPhpFpmMetrics(): array {
    $output = commandTrimmed(['ps', '-C', 'php-fpm', '-o', 'rss=']);
    if ($output === '') {
        return ['count' => 0, 'rss' => 0];
    }

    $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $output))));
    $rssBytes = 0;
    foreach ($lines as $line) {
        if (preg_match('/^\d+$/', $line)) {
            $rssBytes += ((int) $line) * 1024;
        }
    }

    return [
        'count' => count($lines),
        'rss' => $rssBytes,
    ];
}

function getDirectorySizeBytes(string $path): ?int {
    if (!is_dir($path)) {
        return null;
    }

    $output = commandTrimmed(['du', '-sb', $path]);
    if ($output === '' || !preg_match('/^(\d+)/', $output, $matches)) {
        return null;
    }

    return (int) $matches[1];
}

function collectRepoSizeMetrics(array $repos): array {
    $sizes = [];
    $scanLimit = min(count($repos), 8);
    foreach (array_slice($repos, 0, $scanLimit) as $repo) {
        $path = (string) ($repo['path'] ?? '');
        if ($path === '' || !is_dir($path)) {
            continue;
        }

        $size = getDirectorySizeBytes($path);
        if ($size === null) {
            continue;
        }

        $sizes[] = [
            'name' => (string) ($repo['name'] ?? basename($path)),
            'path' => $path,
            'size' => $size,
        ];
    }

    usort($sizes, static fn(array $a, array $b) => $b['size'] <=> $a['size']);
    return array_slice($sizes, 0, 5);
}

function getSystemMetrics(array $repos): array {
    $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
    $cpuCount = (int) commandTrimmed(['getconf', '_NPROCESSORS_ONLN']);
    if ($cpuCount <= 0) {
        $cpuCount = 1;
    }

    $meminfo = readProcMeminfo();
    $memTotal = (int) ($meminfo['MemTotal'] ?? 0);
    $memAvailable = (int) ($meminfo['MemAvailable'] ?? ($meminfo['MemFree'] ?? 0));
    $memUsed = max(0, $memTotal - $memAvailable);

    $reposDisk = [];
    if (is_dir(REPOS_BASE_PATH)) {
        $diskTotal = @disk_total_space(REPOS_BASE_PATH);
        $diskFree = @disk_free_space(REPOS_BASE_PATH);
        if ($diskTotal !== false && $diskFree !== false) {
            $reposDisk = [
                'total' => (int) $diskTotal,
                'free' => (int) $diskFree,
                'used' => (int) ($diskTotal - $diskFree),
                'pct' => $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : 0,
            ];
        }
    }

    return [
        'load' => [
            'avg1' => (float) ($load[0] ?? 0),
            'avg5' => (float) ($load[1] ?? 0),
            'avg15' => (float) ($load[2] ?? 0),
            'cpu_count' => $cpuCount,
            'avg1_pct' => $cpuCount > 0 ? round(((float) ($load[0] ?? 0) / $cpuCount) * 100, 1) : 0,
        ],
        'memory' => [
            'total' => $memTotal,
            'available' => $memAvailable,
            'used' => $memUsed,
            'pct' => $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0,
        ],
        'io_pressure' => readPressureMetric('/proc/pressure/io'),
        'php_fpm' => getPhpFpmMetrics(),
        'repos_disk' => $reposDisk,
        'repo_sizes' => collectRepoSizeMetrics($repos),
    ];
}

function buildBrokerHaSetupGuide(): array {
    $appRoot = str_replace('\\', '/', dirname(__DIR__));
    $phpBinary = str_replace('\\', '/', PHP_BINARY ?: '/usr/bin/php');
    $webGroup = getenv('FULGURITE_WEB_GROUP') ?: (getenv('FULGURITE_WEB_USER') ?: 'www-data');
    $appEndpoints = trim(SecretStore::env('FULGURITE_SECRET_BROKER_ENDPOINTS', ''));
    if ($appEndpoints === '') {
        $appEndpoints = 'tcp://broker-1.example.net:9876,tcp://broker-2.example.net:9876,tcp://broker-3.example.net:9876';
    }

    return [
        'intro' => t('performance.broker_ha.modal_intro', [
            'app_root' => $appRoot,
            'php_bin' => $phpBinary,
            'web_group' => $webGroup,
        ]),
        'current_endpoints' => $appEndpoints,
        'steps' => [
            [
                'title' => t('performance.broker_ha.step1_title'),
                'body' => t('performance.broker_ha.step1_body'),
                'code' => implode("\n", [
                    'sudo groupadd --system fulgurite-secrets 2>/dev/null || true',
                    'sudo useradd --system --home /var/lib/fulgurite-secrets --shell /usr/sbin/nologin --gid fulgurite-secrets fulgurite-secrets 2>/dev/null || true',
                    'sudo install -d -m 0700 -o fulgurite-secrets -g fulgurite-secrets /var/lib/fulgurite-secrets',
                    'sudo install -d -m 0750 -o root -g fulgurite-secrets /etc/fulgurite',
                    'sudo ' . $phpBinary . ' ' . $appRoot . '/bin/fulgurite-secret-key | sudo tee /etc/fulgurite/secret-agent.key >/dev/null',
                    'sudo chown root:fulgurite-secrets /etc/fulgurite/secret-agent.key',
                    'sudo chmod 0640 /etc/fulgurite/secret-agent.key',
                    'sudo scp /etc/fulgurite/secret-agent.key root@broker-2.example.net:/etc/fulgurite/secret-agent.key',
                    'sudo scp /etc/fulgurite/secret-agent.key root@broker-3.example.net:/etc/fulgurite/secret-agent.key',
                ]),
            ],
            [
                'title' => t('performance.broker_ha.step2_title'),
                'body' => t('performance.broker_ha.step2_body'),
                'code' => implode("\n", [
                    "sudo tee /etc/fulgurite/secret-agent.env >/dev/null <<'EOF'",
                    'FULGURITE_SECRET_AGENT_BIND=tcp://0.0.0.0:9876',
                    'FULGURITE_SECRET_AGENT_PUBLIC_ENDPOINT=tcp://broker-1.example.net:9876',
                    'FULGURITE_SECRET_AGENT_NODE_ID=broker-1',
                    'FULGURITE_SECRET_AGENT_NODE_LABEL=broker-1',
                    'FULGURITE_SECRET_AGENT_CLUSTER_NAME=fulgurite-secret-broker',
                    'FULGURITE_SECRET_AGENT_CLUSTER_PEERS=tcp://broker-2.example.net:9876,tcp://broker-3.example.net:9876',
                    'FULGURITE_SECRET_AGENT_DB_DSN=pgsql:host=postgres-broker.example.net;port=5432;dbname=fulgurite_secrets',
                    'FULGURITE_SECRET_AGENT_DB_USER=fulgurite_secret',
                    'FULGURITE_SECRET_AGENT_DB_PASS=change-me',
                    'FULGURITE_SECRET_AGENT_KEY_FILE=/etc/fulgurite/secret-agent.key',
                    'FULGURITE_SECRET_AGENT_AUDIT=/var/lib/fulgurite-secrets/audit.log',
                    'EOF',
                ]),
            ],
            [
                'title' => t('performance.broker_ha.step3_title'),
                'body' => t('performance.broker_ha.step3_body'),
                'code' => implode("\n", [
                    "sudo tee /etc/systemd/system/fulgurite-secret-agent.service >/dev/null <<'UNIT_EOF'",
                    '[Unit]',
                    'Description=Fulgurite HA secret broker',
                    'After=network.target',
                    '',
                    '[Service]',
                    'Type=simple',
                    'User=fulgurite-secrets',
                    'Group=' . $webGroup,
                    'SupplementaryGroups=fulgurite-secrets',
                    'RuntimeDirectory=fulgurite',
                    'RuntimeDirectoryMode=0770',
                    'UMask=0007',
                    'EnvironmentFile=-/etc/fulgurite/secret-agent.env',
                    'ExecStart=' . $phpBinary . ' ' . $appRoot . '/bin/fulgurite-secret-agent',
                    'Restart=on-failure',
                    'RestartSec=2',
                    'NoNewPrivileges=true',
                    'PrivateTmp=true',
                    'ProtectSystem=full',
                    'ProtectHome=true',
                    'ReadWritePaths=/run/fulgurite /var/lib/fulgurite-secrets',
                    '',
                    '[Install]',
                    'WantedBy=multi-user.target',
                    'UNIT_EOF',
                    '',
                    'sudo systemctl daemon-reload',
                    'sudo systemctl enable --now fulgurite-secret-agent',
                    'sudo systemctl status fulgurite-secret-agent --no-pager',
                ]),
            ],
            [
                'title' => t('performance.broker_ha.step4_title'),
                'body' => t('performance.broker_ha.step4_body'),
                'code' => implode("\n", [
                    '# broker-2',
                    'FULGURITE_SECRET_AGENT_PUBLIC_ENDPOINT=tcp://broker-2.example.net:9876',
                    'FULGURITE_SECRET_AGENT_NODE_ID=broker-2',
                    'FULGURITE_SECRET_AGENT_NODE_LABEL=broker-2',
                    'FULGURITE_SECRET_AGENT_CLUSTER_PEERS=tcp://broker-1.example.net:9876,tcp://broker-3.example.net:9876',
                    '',
                    '# broker-3',
                    'FULGURITE_SECRET_AGENT_PUBLIC_ENDPOINT=tcp://broker-3.example.net:9876',
                    'FULGURITE_SECRET_AGENT_NODE_ID=broker-3',
                    'FULGURITE_SECRET_AGENT_NODE_LABEL=broker-3',
                    'FULGURITE_SECRET_AGENT_CLUSTER_PEERS=tcp://broker-1.example.net:9876,tcp://broker-2.example.net:9876',
                ]),
            ],
            [
                'title' => t('performance.broker_ha.step5_title'),
                'body' => t('performance.broker_ha.step5_body'),
                'code' => implode("\n", [
                    "sudo tee -a /etc/fulgurite/.env >/dev/null <<'EOF'",
                    'FULGURITE_SECRET_PROVIDER=agent',
                    'FULGURITE_SECRET_BROKER_ENDPOINTS=' . $appEndpoints,
                    'EOF',
                    'sudo systemctl restart php8.2-fpm || sudo systemctl restart php-fpm',
                    'sudo systemctl restart nginx || sudo systemctl restart apache2',
                ]),
            ],
            [
                'title' => t('performance.broker_ha.step6_title'),
                'body' => t('performance.broker_ha.step6_body'),
                'code' => implode("\n", [
                    $phpBinary . ' -r \'$s=stream_socket_client("tcp://broker-1.example.net:9876", $e, $m, 3); if(!$s){fwrite(STDERR, "ERR $e $m\n"); exit(1);} fwrite($s, "{\"action\":\"cluster_health\",\"purpose\":\"health\"}\n"); echo fgets($s);\'',
                ]),
            ],
        ],
    ];
}

$jobSummary = JobQueue::getSummary();
$recentJobs = JobQueue::getRecentJobs(AppConfig::performanceRecentJobsLimit());
$workerName = AppConfig::workerDefaultName();
$workerHeartbeat = JobQueue::getWorkerHeartbeat($workerName);
$workerStatus = WorkerManager::getStatus($workerName);
$configuredWebUser = getenv('FULGURITE_WEB_USER') ?: 'www-data';

$appDb = Database::getInstance();
$repos = RepoManager::getAll();
$coreCounts = [
    'users' => safeTableCount($appDb, 'users'),
    'repos' => safeTableCount($appDb, 'repos'),
    'hosts' => safeTableCount($appDb, 'hosts'),
    'backup_jobs' => safeTableCount($appDb, 'backup_jobs'),
    'copy_jobs' => safeTableCount($appDb, 'copy_jobs'),
    'ssh_keys' => safeTableCount($appDb, 'ssh_keys'),
];

$mainDb = statFileInfo(DB_PATH);
$searchDb = statFileInfo(defined('SEARCH_DB_PATH') ? SEARCH_DB_PATH : DB_PATH);
$mainWal = statFileInfo(DB_PATH . '-wal');
$searchWal = statFileInfo((defined('SEARCH_DB_PATH') ? SEARCH_DB_PATH : DB_PATH) . '-wal');

$resticCache = statDirInfo(dirname(DB_PATH) . '/cache/restic');
$exploreCache = statDirInfo(dirname(DB_PATH) . '/cache/explore');
$runtimeCache = statDirInfo(rtrim(sys_get_temp_dir(), '/\\') . '/fulgurite-runtime');
$archiveDir = statDirInfo(dirname(DB_PATH) . '/archive/db');

$slowLogPath = dirname(DB_PATH) . '/logs/restic-slow.log';
$profilerLogPath = dirname(DB_PATH) . '/logs/request-profiler.log';
$workerLogPath = dirname(DB_PATH) . '/logs/job-worker.log';

$slowLogLines = tailFileLines($slowLogPath, AppConfig::performanceLogTailLines());
$profilerLines = tailFileLines($profilerLogPath, AppConfig::performanceLogTailLines());
$workerLogLines = tailFileLines($workerLogPath, AppConfig::performanceLogTailLines());

$indexDb = Database::getIndexInstance();
$catalogCount = safeTableCount($indexDb, 'repo_snapshot_catalog');
$navIndexCount = safeTableCount($indexDb, 'snapshot_navigation_index');
$searchIndexCount = safeTableCount($indexDb, 'snapshot_file_index');
$statusCount = safeTableCount($indexDb, 'snapshot_search_index_status');
$systemMetrics = PerformanceMetrics::collectLightweight();
$systemMetrics['repo_sizes'] = PerformanceMetrics::collectRepoSizeMetrics(
    $repos,
    AppConfig::performanceRepoSizeScanLimit(),
    AppConfig::performanceRepoSizeTopLimit()
);

// ── HA Broker cluster health ─────────────────────────────────────────────────
require_once __DIR__ . '/../src/BrokerClusterMonitor.php';
$brokerHealth = BrokerClusterMonitor::liveHealth();
$brokerCluster = $brokerHealth['cluster'] ?? [];
$brokerLastStatus = BrokerClusterMonitor::getLastStatus();
$brokerHaGuide = buildBrokerHaSetupGuide();

include 'layout_top.php';
?>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-value"><?= (int) ($jobSummary['counts']['queued'] ?? 0) ?></div>
        <div class="stat-label"><?= t('performance.stat.jobs_queued') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int) ($jobSummary['counts']['running'] ?? 0) ?></div>
        <div class="stat-label"><?= t('performance.stat.jobs_running') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int) ($jobSummary['delayed_count'] ?? 0) ?></div>
        <div class="stat-label"><?= t('performance.stat.jobs_delayed') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--red)"><?= (int) ($jobSummary['counts']['dead_letter'] ?? 0) ?></div>
        <div class="stat-label">Dead-letter</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= formatBytes($mainDb['size'] + $searchDb['size']) ?></div>
        <div class="stat-label"><?= t('performance.stat.sqlite_size') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $workerHeartbeat && !empty($workerHeartbeat['at']) ? h(formatDate((string) $workerHeartbeat['at'])) : t('common.never') ?></div>
        <div class="stat-label"><?= t('performance.stat.worker_heartbeat') ?></div>
    </div>
</div>

<?php
$primaryDataLooksSparse =
    $coreCounts['repos'] === 0 &&
    $coreCounts['hosts'] === 0 &&
    $coreCounts['backup_jobs'] === 0 &&
    $coreCounts['copy_jobs'] === 0;
?>

<?php if ($primaryDataLooksSparse): ?>
<div class="alert alert-warning mb-4">
    <?= t('performance.warning_sparse_db') ?>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><?= t('performance.core_data_title') ?></div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $coreCounts['repos'] ?></div>
                <div class="stat-label"><?= t('performance.stat.repos') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $coreCounts['hosts'] ?></div>
                <div class="stat-label"><?= t('performance.stat.hosts') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $coreCounts['backup_jobs'] ?></div>
                <div class="stat-label">Backup jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $coreCounts['copy_jobs'] ?></div>
                <div class="stat-label">Copy jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $coreCounts['ssh_keys'] ?></div>
                <div class="stat-label"><?= t('performance.stat.ssh_keys') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $coreCounts['users'] ?></div>
                <div class="stat-label"><?= t('performance.stat.users') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <span><?= t('performance.system_title') ?></span>
        <span id="system-metrics-updated" style="font-size:12px;color:var(--text2)"><?= t('performance.system_updated_prefix') ?> <?= h(formatDate((string) ($systemMetrics['refreshed_at'] ?? date(DATE_ATOM)))) ?></span>
    </div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" id="sys-load-avg1"><?= number_format((float) ($systemMetrics['load']['avg1'] ?? 0), 2) ?></div>
                <div class="stat-label" id="sys-load-label">Load 1m (<?= (int) ($systemMetrics['load']['cpu_count'] ?? 1) ?> CPU)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="sys-load-pct"><?= (float) ($systemMetrics['load']['avg1_pct'] ?? 0) ?>%</div>
                <div class="stat-label"><?= t('performance.stat.cpu_load') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="sys-memory-used"><?= !empty($systemMetrics['memory']['total']) ? formatBytes((int) ($systemMetrics['memory']['used'] ?? 0)) : 'N/A' ?></div>
                <div class="stat-label"><?= t('performance.stat.memory_used') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="sys-memory-pct"><?= (float) ($systemMetrics['memory']['pct'] ?? 0) ?>%</div>
                <div class="stat-label"><?= t('performance.stat.memory_system') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="sys-phpfpm-count"><?= (int) ($systemMetrics['php_fpm']['count'] ?? 0) ?></div>
                <div class="stat-label"><?= t('performance.stat.phpfpm_workers') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="sys-phpfpm-rss"><?= !empty($systemMetrics['php_fpm']['rss']) ? formatBytes((int) $systemMetrics['php_fpm']['rss']) : 'N/A' ?></div>
                <div class="stat-label">RSS PHP-FPM</div>
            </div>
        </div>
        <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
            <div>
                <div style="font-size:12px;color:var(--text2);margin-bottom:8px"><?= t('performance.system_io_storage') ?></div>
                <div class="table-wrap"><table class="table">
                    <tbody>
                        <tr>
                            <td><?= t('performance.stat.load_5_15') ?></td>
                            <td id="sys-load-avg5-15"><?= number_format((float) ($systemMetrics['load']['avg5'] ?? 0), 2) ?> / <?= number_format((float) ($systemMetrics['load']['avg15'] ?? 0), 2) ?></td>
                        </tr>
                        <tr>
                            <td><?= t('performance.stat.repos_disk') ?></td>
                            <td id="sys-repos-disk"><?= !empty($systemMetrics['repos_disk']) ? formatBytes((int) $systemMetrics['repos_disk']['used']) . ' / ' . formatBytes((int) $systemMetrics['repos_disk']['total']) . ' (' . $systemMetrics['repos_disk']['pct'] . '%)' : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td>IO pressure avg10</td>
                            <td id="sys-io-avg10"><?= isset($systemMetrics['io_pressure']['avg10']) ? number_format((float) $systemMetrics['io_pressure']['avg10'], 2) . '%' : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td>IO pressure avg60</td>
                            <td id="sys-io-avg60"><?= isset($systemMetrics['io_pressure']['avg60']) ? number_format((float) $systemMetrics['io_pressure']['avg60'], 2) . '%' : 'N/A' ?></td>
                        </tr>
                    </tbody>
                </table></div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--text2);margin-bottom:8px"><?= t('performance.system_repo_sizes') ?></div>
                <?php if (!empty($systemMetrics['repo_sizes'])): ?>
                <div class="table-wrap"><table class="table">
                    <tbody>
                        <?php foreach ($systemMetrics['repo_sizes'] as $repoSize): ?>
                        <tr>
                            <td><?= h($repoSize['name']) ?></td>
                            <td><?= formatBytes((int) $repoSize['size']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
                <?php else: ?>
                <div style="font-size:12px;color:var(--text2)"><?= t('performance.system_repo_sizes_na') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('performance.worker_title') ?></div>
    <div class="card-body">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
            <div style="font-size:13px">
                <?php
                $workerBadgeClass = 'badge-gray';
                if (!empty($workerStatus['systemd_active']) || !empty($workerStatus['running'])) {
                    $workerBadgeClass = 'badge-green';
                } elseif (!empty($workerStatus['systemd_configured'])) {
                    $workerBadgeClass = 'badge-blue';
                } elseif (!empty($workerStatus['scheduled'])) {
                    $workerBadgeClass = 'badge-blue';
                }
                ?>
                <span id="worker-status-badge" class="badge <?= $workerBadgeClass ?>">
                    <?= h((string) ($workerStatus['control_label'] ?? t('performance.worker_status.stopped'))) ?>
                </span>
                <span id="worker-status-text" style="margin-left:8px;color:var(--text2)">
                    <?php if (!empty($workerStatus['systemd_active'])): ?>
                    <?= t('performance.worker_service_name', [':service' => h((string) ($workerStatus['systemd_service'] ?? '')), ':pid' => (int) ($workerStatus['pid'] ?? 0)]) ?>
                    <?php elseif (!empty($workerStatus['systemd_configured'])): ?>
                    <?= t('performance.worker_service_configured', [':service' => h((string) ($workerStatus['systemd_service'] ?? ''))]) ?>
                    <?php elseif (!empty($workerStatus['running']) && !empty($workerStatus['pid'])): ?>
                    PID <?= (int) $workerStatus['pid'] ?>
                    <?php elseif (!empty($workerStatus['scheduled'])): ?>
                    <?= t('performance.worker_text.scheduled') ?>
                    <?php else: ?>
                    <?= t('performance.worker_text.stopped') ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="flex gap-2" style="flex-wrap:wrap">
                <button class="btn btn-sm btn-success" id="btn-worker-start" onclick="manageWorker('start')"><?= t('performance.btn.start') ?></button>
                <button class="btn btn-sm btn-warning" id="btn-worker-restart" onclick="manageWorker('restart')"><?= t('performance.btn.restart') ?></button>
                <button class="btn btn-sm btn-danger" id="btn-worker-stop" onclick="manageWorker('stop')"><?= t('performance.btn.stop') ?></button>
                <button class="btn btn-sm" id="btn-worker-run-once" onclick="manageWorker('run_once')"><?= t('performance.btn.run_once') ?></button>
            </div>
        </div>
        <div style="font-size:13px;color:var(--text2);margin-bottom:12px"><?= t('performance.worker_cmd_label') ?></div>
        <div class="code-viewer">php public/worker.php --name=<?= h($workerName) ?> --sleep=<?= AppConfig::workerSleepSeconds() ?> --limit=<?= AppConfig::workerLimit() ?></div>
        <div style="margin-top:12px;font-size:13px" id="worker-heartbeat-text">
            <?php if ($workerHeartbeat): ?>
            <?= t('performance.worker_heartbeat_prefix') ?> <strong><?= h(formatDate((string) ($workerHeartbeat['at'] ?? ''))) ?></strong>
            <?php if (!empty($workerHeartbeat['pid'])): ?>
            , PID <?= (int) $workerHeartbeat['pid'] ?>
            <?php endif; ?>
            <?php else: ?>
            <?= t('performance.worker_no_heartbeat') ?>
            <?php endif; ?>
        </div>
        <div style="margin-top:8px;font-size:12px;color:var(--text2)" id="worker-meta">
            PID file : <code><?= h((string) ($workerStatus['pid_file'] ?? '')) ?></code><br>
            Log file : <code><?= h((string) ($workerStatus['log_file'] ?? '')) ?></code><br>
            PHP CLI : <code><?= h((string) ($workerStatus['php_bin'] ?? '')) ?></code>
            <?php if (!empty($workerStatus['systemd_service'])): ?>
            <br>Systemd : <code><?= h((string) $workerStatus['systemd_service']) ?></code>
            <?php endif; ?>
            <?php if (!empty($workerStatus['systemd']['error'])): ?>
            <br><?= t('performance.worker_systemd_diag') ?> <code><?= h((string) $workerStatus['systemd']['error']) ?></code>
            <?php endif; ?>
        </div>
        <?php if (!empty($workerLogLines)): ?>
        <div style="margin-top:12px;font-size:12px;color:var(--text2)"><?= t('performance.worker_last_lines') ?></div>
        <div class="code-viewer" id="worker-log-preview" style="max-height:220px"><?= h(implode("\n", $workerLogLines)) ?></div>
        <?php endif; ?>

        <hr style="margin:16px 0;border:none;border-top:1px solid var(--border)">
        <div style="font-size:13px;font-weight:600;margin-bottom:12px"><?= t('performance.worker_install_title') ?></div>

        <?php /* Phase 1 — Cron */ ?>
        <div style="margin-bottom:16px">
            <div style="font-size:12px;font-weight:500;margin-bottom:4px;color:var(--text)"><?= t('performance.worker_cron_title') ?> <span style="font-weight:400;color:var(--text2)"><?= t('performance.worker_cron_no_root') ?></span></div>
            <div style="font-size:12px;color:var(--text2);margin-bottom:8px"><?= t('performance.worker_cron_desc', [':user' => h((string) $configuredWebUser)]) ?></div>
            <div class="flex gap-2" style="flex-wrap:wrap;margin-bottom:6px">
                <button class="btn btn-sm btn-success" id="btn-install-cron" onclick="workerInstallCron()"><?= t('performance.btn.install_cron') ?></button>
                <button class="btn btn-sm btn-danger" id="btn-uninstall-cron" onclick="workerUninstallCron()"><?= t('performance.btn.uninstall_cron') ?></button>
            </div>
            <div id="cron-install-output" style="font-size:11px;color:var(--text2)">
                <?php if (!empty($workerStatus['scheduled'])): ?>
                <span style="color:var(--green)"><?= t('performance.worker_cron_installed') ?></span> — <code><?= h((string) ($workerStatus['cron_line'] ?? '')) ?></code>
                <?php else: ?>
                <?= t('performance.worker_cron_not_installed') ?>
                <?php endif; ?>
            </div>
        </div>

        <?php /* Phase 2 & 3 — Systemd */ ?>
        <div>
            <div style="font-size:12px;font-weight:500;margin-bottom:4px;color:var(--text)"><?= t('performance.worker_systemd_title') ?> <span style="font-weight:400;color:var(--text2)"><?= t('performance.worker_systemd_recommended') ?></span></div>
            <div style="font-size:12px;color:var(--text2);margin-bottom:8px"><?= t('performance.worker_systemd_desc') ?></div>
            <div class="flex gap-2" style="flex-wrap:wrap;margin-bottom:6px">
                <button class="btn btn-sm" onclick="workerGenerateSystemdUnit()"><?= t('performance.btn.view_systemd') ?></button>
                <button class="btn btn-sm btn-success" id="btn-auto-install-systemd" onclick="workerAutoInstallSystemd()" style="display:none"><?= t('performance.btn.auto_install_systemd') ?></button>
                <button class="btn btn-sm btn-danger" id="btn-auto-uninstall-systemd" onclick="workerAutoUninstallSystemd()" style="display:none"><?= t('performance.btn.auto_uninstall_systemd') ?></button>
            </div>
            <div id="worker-auto-install-hint" style="font-size:11px;color:var(--text2)"><?= t('performance.worker_systemd_checking') ?></div>
        </div>
    </div>
</div>

<?php /* Modal Phase 2 : Generation file systemd */ ?>
<div id="modal-systemd-unit" class="modal-overlay">
    <div class="modal" style="max-width:720px">
        <div class="modal-title"><?= t('performance.modal_systemd_title') ?></div>

        <div style="margin-bottom:16px">
            <div style="font-size:13px;font-weight:500;margin-bottom:6px"><?= t('performance.modal_systemd_file_label') ?> <code id="systemd-service-name"></code></div>
            <div style="font-size:12px;color:var(--text2);margin-bottom:6px"><?= t('performance.modal_systemd_copy_hint') ?> <code id="systemd-service-file"></code></div>
            <div class="code-viewer" id="systemd-unit-content" style="max-height:260px;user-select:text;white-space:pre"></div>
        </div>

        <div style="margin-bottom:16px">
            <div style="font-size:13px;font-weight:500;margin-bottom:6px"><?= t('performance.modal_systemd_install_label') ?></div>
            <div class="code-viewer" id="systemd-instructions" style="max-height:200px;user-select:text;white-space:pre"></div>
        </div>

        <div style="margin-bottom:16px">
            <div style="font-size:13px;font-weight:500;margin-bottom:6px"><?= t('performance.modal_systemd_sudoers_label') ?> <span style="font-size:12px;font-weight:400;color:var(--text2)"><?= t('performance.modal_systemd_sudoers_note') ?></span></div>
            <div style="font-size:12px;color:var(--text2);margin-bottom:4px"><?= t('performance.modal_systemd_sudoers_hint') ?></div>
            <div class="code-viewer" id="systemd-sudoers-hint" style="user-select:text;white-space:pre-wrap"></div>
        </div>

        <div style="margin-bottom:16px" id="systemd-auto-install-section">
            <div style="font-size:13px;font-weight:500;margin-bottom:6px"><?= t('performance.modal_systemd_auto_title') ?></div>
            <div style="font-size:12px;color:var(--text2);margin-bottom:8px" id="systemd-auto-install-status"><?= t('performance.modal_systemd_auto_checking') ?></div>
            <div id="systemd-auto-install-guide" style="display:none">
                <div style="font-size:12px;color:var(--text2);margin-bottom:8px">
                    <?= t('performance.modal_systemd_auto_guide_hint') ?>
                </div>
                <div class="code-viewer" id="systemd-auto-install-cmds" style="user-select:text;white-space:pre"></div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end">
            <button class="btn btn-sm" onclick="window.closeModal('modal-systemd-unit')"><?= t('common.close') ?></button>
        </div>
    </div>
</div>

<div id="modal-broker-ha-guide" class="modal-overlay">
    <div class="modal" style="max-width:920px">
        <div class="modal-title"><?= h(t('performance.broker_ha.modal_title')) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-bottom:12px"><?= h($brokerHaGuide['intro']) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-bottom:16px">
            <?= h(t('performance.broker_ha.current_endpoints', ['endpoints' => (string) $brokerHaGuide['current_endpoints']])) ?>
        </div>

        <?php foreach (($brokerHaGuide['steps'] ?? []) as $index => $step): ?>
        <div style="margin-bottom:18px">
            <div style="font-size:13px;font-weight:600;margin-bottom:6px">
                <?= h(($index + 1) . '. ' . (string) ($step['title'] ?? '')) ?>
            </div>
            <div style="font-size:12px;color:var(--text2);margin-bottom:8px">
                <?= h((string) ($step['body'] ?? '')) ?>
            </div>
            <div class="code-viewer" style="max-height:240px;user-select:text;white-space:pre-wrap"><?= h((string) ($step['code'] ?? '')) ?></div>
        </div>
        <?php endforeach; ?>

        <div style="display:flex;justify-content:flex-end">
            <button class="btn btn-sm" onclick="window.closeModal('modal-broker-ha-guide')"><?= h(t('common.close')) ?></button>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('performance.queue_health_title') ?></div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= (int) ($jobSummary['counts']['completed'] ?? 0) ?></div>
                <div class="stat-label"><?= t('performance.stat.completed') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= (int) ($jobSummary['counts']['failed'] ?? 0) ?></div>
                <div class="stat-label"><?= t('performance.stat.failed_legacy') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= (int) ($jobSummary['highest_priority_queued'] ?? 0) ?></div>
                <div class="stat-label"><?= t('performance.stat.max_priority') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= !empty($jobSummary['next_available_at']) ? h(formatDate((string) $jobSummary['next_available_at'])) : t('performance.stat.none') ?></div>
                <div class="stat-label"><?= t('performance.stat.next_job') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('performance.snapshot_index_title') ?></div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $catalogCount ?></div>
                <div class="stat-label"><?= t('performance.stat.catalogued') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $navIndexCount ?></div>
                <div class="stat-label"><?= t('performance.stat.nav_entries') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $searchIndexCount ?></div>
                <div class="stat-label"><?= t('performance.stat.search_entries') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statusCount ?></div>
                <div class="stat-label"><?= t('performance.stat.index_statuses') ?></div>
            </div>
        </div>
        <div style="font-size:12px;color:var(--text2);margin-top:12px">
            <?= t('performance.snapshot_index_note') ?>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('performance.storage_title') ?></div>
    <div class="card-body">
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('performance.storage_col_target') ?></th>
                    <th><?= t('performance.storage_col_size') ?></th>
                    <th><?= t('performance.storage_col_updated') ?></th>
                    <th><?= t('performance.storage_col_path') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ([
                    t('performance.storage_label_main_db') => $mainDb,
                    t('performance.storage_label_index_db') => $searchDb,
                    t('performance.storage_label_main_wal') => $mainWal,
                    t('performance.storage_label_index_wal') => $searchWal,
                ] as $label => $info): ?>
                <tr>
                    <td><?= h($label) ?></td>
                    <td><?= $info['exists'] ? formatBytes((int) $info['size']) : t('performance.storage_absent') ?></td>
                    <td><?= !empty($info['modified_at']) ? h($info['modified_at']) : '—' ?></td>
                    <td style="font-family:var(--font-mono);font-size:12px"><?= h($info['path']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php foreach ([
                    t('performance.storage_label_restic_cache') => $resticCache,
                    t('performance.storage_label_explore_cache') => $exploreCache,
                    t('performance.storage_label_runtime_cache') => $runtimeCache,
                    t('performance.storage_label_archive') => $archiveDir,
                ] as $label => $info): ?>
                <tr>
                    <td><?= h($label) ?></td>
                    <td>
                        <?php if ($info['exists']): ?>
                        <?= formatBytes((int) $info['size']) . ' / ' . (int) $info['files'] . ' ' . t('performance.storage_files_suffix') ?>
                        <?php if (!empty($info['errors'])): ?>
                        <span style="color:var(--text2);font-size:11px">(<?= (int) $info['errors'] ?> <?= t('performance.storage_access_denied') ?>)</span>
                        <?php endif; ?>
                        <?php else: ?>
                        <?= t('performance.storage_absent') ?>
                        <?php endif; ?>
                    </td>
                    <td>—</td>
                    <td style="font-family:var(--font-mono);font-size:12px"><?= h($info['path']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('performance.jobs_recent_title') ?></div>
    <div class="card-body">
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('performance.jobs_col_id') ?></th>
                    <th><?= t('performance.jobs_col_type') ?></th>
                    <th><?= t('performance.jobs_col_status') ?></th>
                    <th><?= t('performance.jobs_col_priority') ?></th>
                    <th><?= t('performance.jobs_col_attempts') ?></th>
                    <th><?= t('performance.jobs_col_availability') ?></th>
                    <th><?= t('performance.jobs_col_error') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentJobs as $job): ?>
                <?php
                $jobStatus = (string) ($job['status'] ?? '');
                $jobBadgeClass = match ($jobStatus) {
                    'completed' => 'badge-green',
                    'running' => 'badge-blue',
                    'dead_letter' => 'badge-red',
                    'failed' => 'badge-red',
                    'queued' => 'badge-gray',
                    default => 'badge-gray',
                };
                ?>
                <tr>
                    <td><?= (int) $job['id'] ?></td>
                    <td style="font-family:var(--font-mono);font-size:12px"><?= h($job['type']) ?></td>
                    <td><span class="badge <?= $jobBadgeClass ?>"><?= h($jobStatus) ?></span></td>
                    <td><?= (int) $job['priority'] ?></td>
                    <td><?= (int) $job['attempts'] ?></td>
                    <td><?= !empty($job['available_at']) ? h(formatDate((string) $job['available_at'])) : '—' ?></td>
                    <td style="max-width:360px;white-space:normal;font-size:12px;color:var(--text2)"><?= h((string) ($job['last_error'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div class="card">
        <div class="card-header"><?= t('performance.slow_log_title') ?></div>
        <div class="card-body">
            <div class="code-viewer" style="max-height:280px"><?= h(implode("\n", $slowLogLines ?: [t('performance.log_empty')])) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><?= t('performance.profiler_title') ?></div>
        <div class="card-body">
            <div class="code-viewer" style="max-height:280px"><?= h(implode("\n", $profilerLines ?: [t('performance.log_empty')])) ?></div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════ HA Broker Cluster ═══════════════════════════════════════ -->
<div class="card mb-4" id="broker-cluster">
    <div class="card-header" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <span><?= t('performance.broker_title') ?></span>
        <?php
        $brokerTotalNodes   = (int) ($brokerCluster['total']   ?? 0);
        $brokerHealthyNodes = (int) ($brokerCluster['healthy'] ?? 0);
        $brokerDegraded     = !empty($brokerCluster['degraded']);
        $brokerOk           = !empty($brokerHealth['ok']);
        if ($brokerTotalNodes === 0):
        ?>
        <span class="badge badge-gray"><?= t('performance.broker_not_configured') ?></span>
        <?php elseif (!$brokerOk): ?>
        <span class="badge badge-red"><?= t('performance.broker_offline') ?></span>
        <?php elseif ($brokerDegraded): ?>
        <span class="badge badge-yellow" style="background:color-mix(in srgb,var(--yellow,#d69e2e) 14%,var(--bg2));color:var(--yellow,#d69e2e);border:1px solid color-mix(in srgb,var(--yellow,#d69e2e) 35%,var(--border))"><?= t('performance.broker_degraded') ?></span>
        <?php else: ?>
        <span class="badge badge-green"><?= t('performance.broker_operational') ?></span>
        <?php endif; ?>
        <span style="font-size:12px;color:var(--text2)"><?= t('performance.broker_nodes_active', [':healthy' => $brokerHealthyNodes, ':total' => $brokerTotalNodes]) ?></span>
        <button class="btn btn-sm" type="button" onclick="analyzeBrokerHealth()"><?= h(t('performance.broker_analyze_btn')) ?></button>
        <button class="btn btn-sm" type="button" onclick="window.openModal('modal-broker-ha-guide')"><?= h(t('performance.broker_ha.helper_btn')) ?></button>
    </div>
    <div class="card-body">
        <?php if ($brokerTotalNodes === 0): ?>
        <div class="alert alert-info" style="margin-bottom:0">
            <?= t('performance.broker_no_config') ?>
        </div>
        <?php else: ?>
        <div class="stats-grid" style="margin-bottom:16px">
            <div class="stat-card">
                <div class="stat-value" style="color:<?= $brokerOk ? 'var(--green)' : 'var(--red)' ?>"><?= $brokerHealthyNodes ?></div>
                <div class="stat-label"><?= t('performance.broker_healthy_nodes') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="<?= ($brokerTotalNodes - $brokerHealthyNodes) > 0 ? 'color:var(--red)' : '' ?>"><?= $brokerTotalNodes - $brokerHealthyNodes ?></div>
                <div class="stat-label"><?= t('performance.broker_failed_nodes') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="font-size:11px;word-break:break-all"><?= h((string) ($brokerCluster['selected_endpoint'] ?? $brokerCluster['active_endpoint'] ?? '—')) ?></div>
                <div class="stat-label"><?= t('performance.broker_active_endpoint') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="font-size:14px"><?= h((string) ($brokerLastStatus['state'] ?? 'inconnu')) ?></div>
                <div class="stat-label"><?= t('performance.broker_last_state') ?></div>
            </div>
            <?php if (!empty($brokerLastStatus['checked_at'])): ?>
            <div class="stat-card">
                <div class="stat-value" style="font-size:12px"><?= h(formatDateForDisplay((string) $brokerLastStatus['checked_at'])) ?></div>
                <div class="stat-label"><?= t('performance.broker_last_check') ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php $brokerNodes = $brokerCluster['nodes'] ?? []; ?>
        <?php if (!empty($brokerNodes)): ?>
        <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em"><?= t('performance.broker_nodes_label') ?></div>
        <div class="table-wrap">
        <table class="table" style="margin-bottom:0">
            <thead>
                <tr>
                    <th><?= t('performance.broker_col_endpoint') ?></th>
                    <th><?= t('performance.broker_col_type') ?></th>
                    <th><?= t('performance.broker_col_node') ?></th>
                    <th><?= t('performance.broker_col_status') ?></th>
                    <th><?= t('performance.broker_col_info') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($brokerNodes as $node): ?>
                <tr>
                    <td class="mono" style="font-size:12px;word-break:break-all"><?= h((string) ($node['endpoint'] ?? $node['uri'] ?? '')) ?></td>
                    <td><span class="badge badge-gray"><?= h((string) ($node['backend'] ?? $node['type'] ?? 'unknown')) ?></span></td>
                    <td style="font-size:12px;color:var(--text2)"><?= h((string) ($node['node_label'] ?? '')) ?></td>
                    <td>
                        <?php if (!empty($node['ok'])): ?>
                        <span class="badge badge-green">OK</span>
                        <?php else: ?>
                        <span class="badge badge-red"><?= t('common.error') ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--text2)">
                        <?php if (!empty($node['ok'])): ?>
                        <?= h((string) ($node['status'] ?? $node['node_id'] ?? 'ready')) ?>
                        <?php else: ?>
                        <?= h((string) ($node['error'] ?? '')) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top:14px;font-size:12px;color:var(--text2)">
            <?= t('performance.broker_config_note') ?>
        </div>
    </div>
</div>

<script<?= cspNonceAttr() ?>>
function renderWorkerStatus(status) {
    const badge = document.getElementById('worker-status-badge');
    const text = document.getElementById('worker-status-text');
    const heartbeat = document.getElementById('worker-heartbeat-text');
    const meta = document.getElementById('worker-meta');
    const startBtn = document.getElementById('btn-worker-start');
    const stopBtn = document.getElementById('btn-worker-stop');
    const restartBtn = document.getElementById('btn-worker-restart');

    if (!status || !badge || !text || !meta) {
        return;
    }

    if (status.systemd_active) {
        badge.className = 'badge badge-green';
        badge.textContent = 'Systemd';
        text.textContent = status.systemd_service
            ? `<?= h(t('performance.js.service_prefix')) ?> ${status.systemd_service}${status.pid ? `, PID ${status.pid}` : ''}`
            : '<?= h(t('performance.js.worker_active_systemd')) ?>';
        if (startBtn) startBtn.disabled = true;
    } else if (status.systemd_configured) {
        badge.className = 'badge badge-blue';
        badge.textContent = '<?= h(t('performance.js.systemd_configured')) ?>';
        text.textContent = status.systemd_service
            ? `<?= h(t('performance.js.service_prefix')) ?> ${status.systemd_service} <?= h(t('performance.js.service_inactive')) ?>`
            : '<?= h(t('performance.js.service_inactive_generic')) ?>';
        if (startBtn) startBtn.disabled = false;
    } else if (status.scheduled) {
        badge.className = 'badge badge-blue';
        badge.textContent = '<?= h(t('performance.js.scheduled')) ?>';
        text.textContent = '<?= h(t('performance.js.restart_via_cron')) ?>';
        if (startBtn) startBtn.disabled = true;
    } else {
        badge.className = 'badge badge-gray';
        badge.textContent = '<?= h(t('performance.js.stopped')) ?>';
        text.textContent = '<?= h(t('performance.js.no_process')) ?>';
        if (startBtn) startBtn.disabled = false;
    }

    if (stopBtn) stopBtn.disabled = false;
    if (restartBtn) restartBtn.disabled = false;

    const heartbeatAt = status.heartbeat && status.heartbeat.at
        ? (status.heartbeat.at_formatted || (typeof window.formatAppDateTime === 'function' ? window.formatAppDateTime(status.heartbeat.at) : status.heartbeat.at))
        : '';
    const heartbeatText = status.heartbeat && status.heartbeat.at
        ? `<?= h(t('performance.js.heartbeat_prefix')) ?> ${heartbeatAt}${status.heartbeat.pid ? `, PID ${status.heartbeat.pid}` : ''}`
        : '<?= h(t('performance.js.no_heartbeat')) ?>';
    if (heartbeat) {
        heartbeat.textContent = heartbeatText;
    }

    meta.innerHTML =
        `PID file : <code>${status.pid_file || ''}</code><br>` +
        `Log file : <code>${status.log_file || ''}</code><br>` +
        `PHP CLI : <code>${status.php_bin || ''}</code>` +
        `${status.systemd_service ? `<br>Systemd : <code>${status.systemd_service}</code>` : ''}` +
        `${status.systemd && status.systemd.error ? `<br><?= h(t('performance.js.systemd_diag')) ?> : <code>${status.systemd.error}</code>` : ''}` +
        `${status.cron_line ? `<br>Cron : <code>${status.cron_line}</code>` : ''}`;
}

async function refreshWorkerStatus() {
    try {
        const res = await apiPost('/api/manage_worker.php', { action: 'status' });
        if (res && res.status) {
            renderWorkerStatus(res.status);
        }
    } catch (error) {
        // ignore refresh errors on passive polling
    }
}

async function analyzeBrokerHealth() {
    try {
        toast('<?= h(t('performance.js.broker_analyzing')) ?>');
        const res = await apiPost('/api/manage_broker.php', { action: 'analyze_health' });
        toast(res.message || '<?= h(t('performance.js.broker_analyze_ok')) ?>', res.success ? 'success' : 'error');
        if (res.success) {
            window.location.reload();
        }
    } catch (error) {
        toast(error.message || '<?= h(t('performance.js.broker_analyze_error')) ?>', 'error');
    }
}

async function manageWorker(action) {
    const labels = {
        start: '<?= h(t('performance.js.worker_starting')) ?>',
        stop: '<?= h(t('performance.js.worker_stopping')) ?>',
        restart: '<?= h(t('performance.js.worker_restarting')) ?>',
        run_once: '<?= h(t('performance.js.worker_run_once')) ?>',
    };

    try {
        toast(labels[action] || '<?= h(t('performance.js.operation_running')) ?>');
        const res = await apiPost('/api/manage_worker.php', {
            action,
            name: <?= json_encode($workerName) ?>,
            sleep: <?= AppConfig::workerSleepSeconds() ?>,
            limit: <?= AppConfig::workerLimit() ?>,
            stale_minutes: <?= AppConfig::workerStaleMinutes() ?>,
        });

        if (res.status) {
            renderWorkerStatus(res.status);
        }

        if (res.output) {
            const preview = document.getElementById('worker-log-preview');
            if (preview) {
                preview.textContent = res.output;
            }
        }

        if (!res.success && res.diagnostic) {
            const preview = document.getElementById('worker-log-preview');
            if (preview) {
                preview.textContent = res.diagnostic;
            }
        }

        toast(res.message || (res.success ? '<?= h(t('performance.js.operation_done')) ?>' : '<?= h(t('performance.js.operation_error')) ?>'), res.success ? 'success' : 'error');
        await refreshWorkerStatus();
    } catch (error) {
        toast(error.message || '<?= h(t('performance.js.worker_manage_error')) ?>', 'error');
    }
}

// ── Worker : installation cron (Phase 1) ─────────────────────────────────────

async function workerInstallCron() {
    try {
        toast('<?= h(t('performance.js.cron_installing')) ?>');
        const res = await apiPost('/api/manage_worker.php', {
            action: 'install_cron',
            name: <?= json_encode($workerName) ?>,
            limit: <?= AppConfig::workerLimit() ?>,
            stale_minutes: <?= AppConfig::workerStaleMinutes() ?>,
        });
        if (res.status) renderWorkerStatus(res.status);
        renderCronInstallOutput(res);
        toast(res.message || (res.success ? '<?= h(t('performance.js.cron_installed')) ?>' : '<?= h(t('performance.js.failed')) ?>'), res.success ? 'success' : 'error');
    } catch (e) {
        toast(e.message || '<?= h(t('performance.js.cron_install_error')) ?>', 'error');
    }
}

async function workerUninstallCron() {
    try {
        toast('<?= h(t('performance.js.cron_uninstalling')) ?>');
        const res = await apiPost('/api/manage_worker.php', {
            action: 'uninstall_cron',
            name: <?= json_encode($workerName) ?>,
        });
        if (res.status) renderWorkerStatus(res.status);
        renderCronInstallOutput(res);
        toast(res.message || (res.success ? '<?= h(t('performance.js.cron_uninstalled')) ?>' : '<?= h(t('performance.js.failed')) ?>'), res.success ? 'success' : 'error');
    } catch (e) {
        toast(e.message || '<?= h(t('performance.js.cron_uninstall_error')) ?>', 'error');
    }
}

function renderCronInstallOutput(res) {
    const el = document.getElementById('cron-install-output');
    if (!el) return;
    if (res.status && res.status.scheduled) {
        const line = res.cron_line || (res.status && res.status.cron_line) || '';
        el.innerHTML = `<span style="color:var(--green)">✔ <?= h(t('performance.js.cron_installed')) ?></span>${line ? ` — <code>${line}</code>` : ''}`;
    } else {
        el.textContent = '<?= h(t('performance.js.cron_not_installed')) ?>';
    }
}

// ── Worker: guided systemd generation (Phase 2) ──────────────────────────────

async function workerGenerateSystemdUnit() {
    try {
        const res = await apiPost('/api/manage_worker.php', {
            action: 'generate_systemd_unit',
            name: <?= json_encode($workerName) ?>,
        });
        if (!res.success) { toast(res.message || '<?= h(t('performance.js.generate_error')) ?>', 'error'); return; }

        document.getElementById('systemd-service-name').textContent = res.service_name || '';
        document.getElementById('systemd-service-file').textContent = res.service_file || '';
        document.getElementById('systemd-unit-content').textContent = res.unit_content || '';
        document.getElementById('systemd-instructions').textContent = res.instructions || '';
        document.getElementById('systemd-sudoers-hint').textContent = res.sudoers_hint || '';

        // Check whether auto-install is possible (Phase 3)
        populateSystemdAutoInstallSection();

        window.openModal('modal-systemd-unit');
    } catch (e) {
        toast(e.message || '<?= h(t('performance.js.systemd_generate_error')) ?>', 'error');
    }
}

// ── Worker : auto-install systemd (Phase 3) ──────────────────────────────
let workerAutoInstallChecked = false;
let workerAutoInstallCan = false;

async function checkWorkerAutoInstall() {
    try {
        const res = await apiPost('/api/manage_worker.php', {
            action: 'check_auto_install',
            name: <?= json_encode($workerName) ?>,
        });
        workerAutoInstallChecked = true;
        workerAutoInstallCan = !!(res && res.can_auto_install);
        renderAutoInstallButtons(workerAutoInstallCan, res);
        return res;
    } catch (e) {
        workerAutoInstallChecked = true;
        workerAutoInstallCan = false;
        renderAutoInstallButtons(false, null);
        return null;
    }
}

function renderAutoInstallButtons(can, res) {
    const btnInstall = document.getElementById('btn-auto-install-systemd');
    const btnUninstall = document.getElementById('btn-auto-uninstall-systemd');
    const hint = document.getElementById('worker-auto-install-hint');

    if (btnInstall) btnInstall.style.display = can ? '' : 'none';
    if (btnUninstall) btnUninstall.style.display = can ? '' : 'none';

    if (hint) {
        if (can) {
            hint.innerHTML = '<span style="color:var(--green)">✔ <?= h(t('performance.js.auto_install_available')) ?></span>';
        } else {
            const reason = (res && res.reason) ? res.reason : '<?= h(t('performance.js.helper_missing')) ?>';
            const helper = (res && res.helper) ? res.helper : '';
            hint.textContent = reason;
            if (helper) {
                hint.innerHTML += `<br><span style="color:var(--text2)"><?= h(t('performance.js.see_systemd_config')) ?></span>`;
            }
        }
    }
}

async function populateSystemdAutoInstallSection() {
    const statusEl = document.getElementById('systemd-auto-install-status');
    const guideEl = document.getElementById('systemd-auto-install-guide');
    const cmdsEl = document.getElementById('systemd-auto-install-cmds');

    if (statusEl) statusEl.textContent = '<?= h(t('performance.js.checking')) ?>';
    if (guideEl) guideEl.style.display = 'none';

    const res = await checkWorkerAutoInstall();
    if (!statusEl) return;

    if (workerAutoInstallCan) {
        statusEl.innerHTML = '<span style="color:var(--green)">✔ <?= h(t('performance.js.helper_available')) ?></span>';
    } else {
        const reason = (res && res.reason) ? res.reason : '<?= h(t('performance.js.helper_unavailable')) ?>';
        const helper = (res && res.helper) ? res.helper : '';
        statusEl.textContent = reason;
        if (helper && guideEl && cmdsEl) {
            const appRoot = <?= json_encode(dirname(__DIR__)) ?>;
            const webUser = <?= json_encode((string) $configuredWebUser) ?>;
            const cmds = [
                `# Rendre le script exécutable et l'attribuer à root`,
                `sudo chown root:root ${helper}`,
                `sudo chmod 750 ${helper}`,
                ``,
                `# Créer la règle sudoers`,
                `echo "${webUser} ALL=(root) NOPASSWD: ${helper}" | sudo tee /etc/sudoers.d/fulgurite-worker`,
                `sudo chmod 440 /etc/sudoers.d/fulgurite-worker`,
                ``,
                `# Tester (doit afficher "ok")`,
                `sudo -u ${webUser} sudo -n ${helper} --check`,
            ].join('\n');
            cmdsEl.textContent = cmds;
            guideEl.style.display = '';
        }
    }
}

async function workerAutoInstallSystemd() {
    if (!workerAutoInstallCan) { toast('<?= h(t('performance.js.auto_install_unavailable')) ?>', 'error'); return; }
    try {
        toast('<?= h(t('performance.js.systemd_installing')) ?>');
        const res = await apiPost('/api/manage_worker.php', {
            action: 'install_systemd',
            name: <?= json_encode($workerName) ?>,
        });
        if (res.status) renderWorkerStatus(res.status);
        if (res.output) {
            const preview = document.getElementById('worker-log-preview');
            if (preview) preview.textContent = res.output;
        }
        toast(res.message || (res.success ? '<?= h(t('performance.js.systemd_installed')) ?>' : '<?= h(t('performance.js.failed')) ?>'), res.success ? 'success' : 'error');
    } catch (e) {
        toast(e.message || '<?= h(t('performance.js.systemd_install_error')) ?>', 'error');
    }
}

async function workerAutoUninstallSystemd() {
    if (!workerAutoInstallCan) { toast('<?= h(t('performance.js.auto_uninstall_unavailable')) ?>', 'error'); return; }
    try {
        toast('<?= h(t('performance.js.systemd_uninstalling')) ?>');
        const res = await apiPost('/api/manage_worker.php', {
            action: 'uninstall_systemd',
            name: <?= json_encode($workerName) ?>,
        });
        if (res.status) renderWorkerStatus(res.status);
        toast(res.message || (res.success ? '<?= h(t('performance.js.systemd_uninstalled')) ?>' : '<?= h(t('performance.js.failed')) ?>'), res.success ? 'success' : 'error');
    } catch (e) {
        toast(e.message || '<?= h(t('performance.js.systemd_uninstall_error')) ?>', 'error');
    }
}

const initialWorkerStatus = <?= json_encode($workerStatus, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const performanceRefreshMs = <?= AppConfig::performanceMetricsRefreshIntervalSeconds() * 1000 ?>;

function perfFormatBytes(bytes) {
    const value = Number(bytes || 0);
    if (!Number.isFinite(value) || value <= 0) {
        return '0 o';
    }
    if (value >= 1073741824) return (value / 1073741824).toFixed(2) + ' Go';
    if (value >= 1048576) return (value / 1048576).toFixed(2) + ' Mo';
    if (value >= 1024) return (value / 1024).toFixed(2) + ' Ko';
    return value + ' o';
}

function perfFormatDate(value) {
    if (!value) {
        return 'N/A';
    }

    if (typeof window.formatAppDateTime === 'function') {
        return window.formatAppDateTime(value);
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString('fr-FR');
}

function updateSystemMetrics(metrics) {
    if (!metrics) {
        return;
    }

    const load = metrics.load || {};
    const memory = metrics.memory || {};
    const phpFpm = metrics.php_fpm || {};
    const reposDisk = metrics.repos_disk || {};
    const ioPressure = metrics.io_pressure || {};

    const assignText = (id, text) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = text;
        }
    };

    assignText('sys-load-avg1', Number(load.avg1 || 0).toFixed(2));
    assignText('sys-load-label', `Load 1m (${parseInt(load.cpu_count || 1, 10)} CPU)`);
    assignText('sys-load-pct', `${Number(load.avg1_pct || 0).toFixed(1)}%`);
    assignText('sys-memory-used', memory.total ? perfFormatBytes(memory.used || 0) : 'N/A');
    assignText('sys-memory-pct', `${Number(memory.pct || 0).toFixed(1)}%`);
    assignText('sys-phpfpm-count', String(parseInt(phpFpm.count || 0, 10)));
    assignText('sys-phpfpm-rss', phpFpm.rss ? perfFormatBytes(phpFpm.rss) : 'N/A');
    assignText('sys-load-avg5-15', `${Number(load.avg5 || 0).toFixed(2)} / ${Number(load.avg15 || 0).toFixed(2)}`);
    assignText(
        'sys-repos-disk',
        reposDisk.total ? `${perfFormatBytes(reposDisk.used || 0)} / ${perfFormatBytes(reposDisk.total || 0)} (${Number(reposDisk.pct || 0).toFixed(1)}%)` : 'N/A'
    );
    assignText('sys-io-avg10', ioPressure.avg10 !== undefined ? `${Number(ioPressure.avg10).toFixed(2)}%` : 'N/A');
    assignText('sys-io-avg60', ioPressure.avg60 !== undefined ? `${Number(ioPressure.avg60).toFixed(2)}%` : 'N/A');

    if (metrics.refreshed_at) {
        const updated = document.getElementById('system-metrics-updated');
        if (updated) {
            updated.textContent = `<?= h(t('performance.js.updated_prefix')) ?> ${perfFormatDate(metrics.refreshed_at)}`;
        }
    }
}

async function refreshSystemMetrics() {
    try {
        const response = await window.fetchJsonSafe('/api/performance_metrics.php', {
            timeoutMs: 4000,
            cache: 'no-store',
        });

        if (response && response.success && response.metrics) {
            updateSystemMetrics(response.metrics);
        }
    } catch (error) {
        // keep the last visible metrics silently
    }
}

const initialSystemMetrics = <?= json_encode($systemMetrics, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

window.addEventListener('load', () => {
    renderWorkerStatus(initialWorkerStatus);
    renderCronInstallOutput({ status: initialWorkerStatus, cron_line: initialWorkerStatus.cron_line });
    checkWorkerAutoInstall();
    updateSystemMetrics(initialSystemMetrics);

    if (typeof window.registerVisibilityAwareInterval === 'function') {
        window.registerVisibilityAwareInterval(refreshWorkerStatus, performanceRefreshMs, { runImmediately: false, skipWhenHidden: true });
        window.registerVisibilityAwareInterval(refreshSystemMetrics, performanceRefreshMs, { runImmediately: true, skipWhenHidden: true });
        return;
    }

    refreshSystemMetrics();
    window.setInterval(() => {
        if (!document.hidden) {
            refreshWorkerStatus();
            refreshSystemMetrics();
        }
    }, performanceRefreshMs);
});

window.analyzeBrokerHealth = analyzeBrokerHealth;
window.manageWorker = manageWorker;
window.workerInstallCron = workerInstallCron;
window.workerUninstallCron = workerUninstallCron;
window.workerAutoInstallSystemd = workerAutoInstallSystemd;
window.workerAutoUninstallSystemd = workerAutoUninstallSystemd;
window.workerGenerateSystemdUnit = workerGenerateSystemdUnit;
</script>

<?php include 'layout_bottom.php'; ?>
