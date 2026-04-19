<?php
// =============================================================================
// cron.php - Fulgurite automatic tasks
// Run every hour via CLI crontab:
// 0 * * * * flock -n /tmp/fulgurite-cron.lock /usr/bin/php /var/www/fulgurite/public/cron.php > /dev/null 2>&1
// =============================================================================

require_once __DIR__ . '/../src/bootstrap.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    http_response_code(410);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Entree HTTP cron desactivee. Utilisez la ligne cron CLI fournie par l'interface d'administration.\n";
    exit;
}

// ── Protection against concurrent executions ────────────────────────────
// We acquire the lock in non-blocking mode. If another cron.php instance
// is already running, we log the incident in cron_log and exit cleanly —
// without leaving any trace is the real problem: missed jobs leave
// no history and are impossible to diagnose.
$_cronLockFile = SchedulerManager::getCronLockFilePath();
$_cronLockFp   = @fopen($_cronLockFile, 'c');

if ($_cronLockFp === false || !flock($_cronLockFp, LOCK_EX | LOCK_NB)) {
    // Read the PID of the instance holding the lock (if available)
    $blockedBy = '';
    if ($_cronLockFp !== false) {
        fclose($_cronLockFp);
    }

    // Try to determine the concurrent process PID via the file lock
    if (is_file($_cronLockFile)) {
        $lockCheck = ProcessRunner::run(['fuser', $_cronLockFile], [
            'validate_binary' => false,
            'error_message' => 'fuser indisponible',
        ]);
        $lockPid = trim((string) ($lockCheck['stdout'] ?? $lockCheck['output'] ?? ''));
        if ($lockPid !== '') {
            $blockedBy = " (PID concurrent : $lockPid)";
        }
    }

    $skipMsg = '[SKIP] Cron ignore : une execution est deja en cours' . $blockedBy
        . '. Les backup jobs prevus a cette heure n\'ont PAS ete declenches.';

    // Insert into cron_log to keep a trace
    try {
        Database::getInstance()->prepare(
            "INSERT INTO cron_log (job_type, status, output) VALUES ('cron_skip', 'skipped', ?)"
        )->execute([$skipMsg]);
    } catch (Throwable $e) {
        // The DB may be temporarily inaccessible — nothing more can be done
    }

    echo $skipMsg . "\n";
    exit(0);
}

// lock acquired — continue normal execution.
// We write the PID to simplify diagnosis, then explicitly clean
// the lock file at the end to avoid visual ambiguity.
ftruncate($_cronLockFp, 0);
fwrite($_cronLockFp, (string) (getmypid() ?: ''));
fflush($_cronLockFp);

register_shutdown_function(static function () use ($_cronLockFp, $_cronLockFile): void {
    if (is_resource($_cronLockFp)) {
        @flock($_cronLockFp, LOCK_UN);
        @fclose($_cronLockFp);
    }

    @unlink($_cronLockFile);
});

$db = Database::getInstance();
$log = [];
$now = new DateTimeImmutable('now', appServerTimezone());
$currentHourBucket = $now->format('Y-m-d H:00:00');
$cronMode = (string) ($_SERVER['FULGURITE_CRON_MODE'] ?? getenv('FULGURITE_CRON_MODE') ?: '');
if (!in_array($cronMode, ['manual', 'diagnostic', 'quick'], true)) {
    $cronMode = (($_SERVER['FULGURITE_CRON_DIAGNOSTIC'] ?? getenv('FULGURITE_CRON_DIAGNOSTIC') ?: '') === '1')
        ? 'diagnostic'
        : 'manual';
}
$diagnosticMode = $cronMode === 'diagnostic';
$quickTestMode = $cronMode === 'quick';
$cronJobType = $quickTestMode ? 'cron_test' : 'cron_run';
$cronStatus = 'success';

function cronLog(array &$log, string $message): void {
    $log[] = $message;
    echo $message . "\n";
    if (function_exists('flush')) {
        @flush();
    }
}

cronLog($log, '[STEP] Initialisation du cycle cron');
if ($diagnosticMode) {
    cronLog($log, '[DIAG] Mode diagnostic actif');
}
if ($quickTestMode) {
    cronLog($log, '[TEST] Mode test rapide actif');
}

$latestStatsByRepo = [];
foreach ($db->query("
    SELECT repo_id, MAX(recorded_at) AS recorded_at
    FROM repo_stats_history
    GROUP BY repo_id
")->fetchAll() as $row) {
    $latestStatsByRepo[(int) $row['repo_id']] = $row['recorded_at'];
}

if ($quickTestMode) {
    $configErrors = [];

    cronLog($log, '[TEST] Verification du stockage temporaire');
    $tempDir = rtrim(sys_get_temp_dir(), '\\/');
    if ($tempDir === '' || !is_dir($tempDir) || !is_writable($tempDir)) {
        $configErrors[] = 'Repertoire temporaire indisponible ou non inscriptible';
        cronLog($log, '[TEST] ERREUR: repertoire temporaire indisponible ou non inscriptible');
    } else {
        cronLog($log, '[TEST] Temp directory OK: ' . $tempDir);
    }

    cronLog($log, '[TEST] Verification de la base et de la file interne');
    $db->query('SELECT 1')->fetchColumn();
    $queuedJobs = (int) $db->query("
        SELECT COUNT(*)
        FROM job_queue
        WHERE status = 'queued'
          AND available_at <= datetime('now')
    ")->fetchColumn();
    cronLog($log, '[TEST] Jobs internes dus: ' . $queuedJobs);

    cronLog($log, '[TEST] Verification des jobs planifies');
    $dueCopyJobs = CopyJobManager::getDueJobs();
    $dueBackupJobs = BackupJobManager::getDueJobs();
    cronLog($log, '[TEST] Copy jobs dus detectes: ' . count($dueCopyJobs));
    cronLog($log, '[TEST] Backup jobs dus detectes: ' . count($dueBackupJobs));

    cronLog($log, '[TEST] Verification des taches globales');
    $enabledGlobalTasks = array_values(array_filter(
        SchedulerManager::getGlobalTasks(),
        fn(array $task): bool => !empty($task['enabled'])
    ));
    cronLog($log, '[TEST] Taches globales actives: ' . count($enabledGlobalTasks));
    foreach ($enabledGlobalTasks as $task) {
        $nextRun = !empty($task['next_run']) ? $task['next_run'] : 'prochain run non calcule';
        cronLog($log, '[TEST] - ' . $task['title'] . ' (' . $nextRun . ')');
    }

    cronLog($log, '[TEST] Verification de la configuration des depots');
    $repos = RepoManager::getAll();
    cronLog($log, '[TEST] Depots configures: ' . count($repos));
    foreach ($repos as $repo) {
        $repoErrors = [];
        $passwordSource = (string) ($repo['password_source'] ?? 'file');
        $repoPath = trim((string) ($repo['path'] ?? ''));

        if ($repoPath === '') {
            $repoErrors[] = 'chemin vide';
        }

        if ($passwordSource === 'infisical') {
            if (trim((string) ($repo['infisical_secret_name'] ?? '')) === '') {
                $repoErrors[] = 'secret Infisical manquant';
            }
        } else {
            $passwordRef = trim((string) ($repo['password_ref'] ?? ''));
            $passwordFile = trim((string) ($repo['password_file'] ?? ''));
            if (SecretStore::isSecretRef($passwordRef)) {
                try {
                    if (!SecretStore::exists($passwordRef)) {
                        $repoErrors[] = 'reference de secret introuvable';
                    }
                } catch (Throwable $e) {
                    $repoErrors[] = 'provider de secret indisponible';
                }
            } elseif ($passwordFile === '' || !is_file($passwordFile) || !is_readable($passwordFile)) {
                $repoErrors[] = 'secret manquant';
            }
        }

        if (empty($repoErrors)) {
            cronLog($log, '[TEST] Depot ' . $repo['name'] . ': configuration OK');
            continue;
        }

        $message = 'Depot ' . $repo['name'] . ': ' . implode(', ', $repoErrors);
        $configErrors[] = $message;
        cronLog($log, '[TEST] ERREUR: ' . $message);
    }

    if (empty($configErrors)) {
        cronLog($log, '[TEST] Verification rapide terminee avec succes');
    } else {
        $cronStatus = 'error';
        cronLog($log, '[TEST] Verification rapide terminee avec erreur(s)');
    }

    $output = implode("\n", $log);
    $db->prepare("
        INSERT INTO cron_log (job_type, status, output)
        VALUES (?, ?, ?)
    ")->execute([$cronJobType, $cronStatus, $output]);

    exit($cronStatus === 'success' ? 0 : 1);
}

// all notifications (jobs + repository alerts) are collected during execution
// and sent in a single pass at the very end — after all information is known.
$pendingNotifications = [];

// 1. Scheduled copy jobs
cronLog($log, '[STEP] Verification des copy jobs planifies');
$dueCopyJobs = CopyJobManager::getDueJobs();
if (empty($dueCopyJobs)) {
    cronLog($log, '[COPY] Aucun job de copie a lancer');
}
foreach ($dueCopyJobs as $job) {
    cronLog($log, "[COPY] Demarrage job #{$job['id']} : {$job['name']}");
    $result = CopyJobManager::run((int) $job['id'], null, false);
    cronLog($log, "[COPY] #{$job['id']} : " . ($result['success'] ? 'OK' : 'ECHEC'));
    if (!empty($result['job'])) {
        $pendingNotifications[] = ['type' => 'copy', 'job' => $result['job'], 'success' => (bool) $result['success'], 'output' => (string) ($result['output'] ?? '')];
    }
}

// 1b. Scheduled backup jobs
cronLog($log, '[STEP] Verification des backup jobs planifies');
$dueBackupJobs = BackupJobManager::getDueJobs();
if (empty($dueBackupJobs)) {
    cronLog($log, '[BACKUP] Aucun backup job a lancer');
}
// Repos with a successsful backup in THIS pass — used to:
// (1) invalidate snapshot cache before monitoring,
// (2) remove incorrect no_snap/stale alerts caused by stale cache.
$backedUpRepoIds = [];
foreach ($dueBackupJobs as $job) {
    cronLog($log, "[BACKUP] Demarrage job #{$job['id']} : {$job['name']}");
    $result = BackupJobManager::run((int) $job['id'], false);
    cronLog($log, "[BACKUP] #{$job['id']} : " . ($result['success'] ? 'OK' : 'ECHEC'));
    if (!empty($result['success'])) {
        $backedUpRepoIds[(int) $job['repo_id']] = true;
    }
    if (!empty($result['job'])) {
        $pendingNotifications[] = ['type' => 'backup', 'job' => $result['job'], 'success' => (bool) $result['success'], 'output' => (string) ($result['output'] ?? '')];
    }
}

// 2. Internal queue + configurable global tasks
// IMPORTANT: repo_snapshot_refresh is excluded here — this type must always run
// AFTER monitoring (Step 3) to never read a pre-backup state.
// snapshot_full_index jobs (content indexing) can run at any time.
cronLog($log, '[STEP] Traitement de la file interne');
foreach (JobQueue::processDueJobs(4, ['snapshot_full_index']) as $queueResult) {
    cronLog($log, "[QUEUE] #{$queueResult['id']} {$queueResult['type']} : {$queueResult['status']} - {$queueResult['message']}");
}

$weeklyReportRun = SchedulerManager::runWeeklyReportTask(false, $now);
foreach ($weeklyReportRun['logs'] ?? [] as $line) {
    cronLog($log, $line);
}
if (empty($weeklyReportRun['logs'])) {
    cronLog($log, '[REPORT] Aucun rapport hebdomadaire a envoyer');
}

$integrityRun = SchedulerManager::runIntegrityCheckTask(false, $now);
foreach ($integrityRun['logs'] ?? [] as $line) {
    cronLog($log, $line);
}
if (empty($integrityRun['logs'])) {
    cronLog($log, '[CHECK] Aucune verification d integrite a lancer');
}

// 3. Repository monitoring
cronLog($log, '[STEP] Surveillance des depots');
$repos = RepoManager::getAll();
$runtimeStatuses = [];

foreach ($repos as $repo) {
    cronLog($log, "[REPO] Analyse {$repo['name']}");
    $repoJustBacked = isset($backedUpRepoIds[(int) $repo['id']]);
    $restic = RepoManager::getRestic($repo);
    if ($repoJustBacked) {
        // A backup just finished for this repository in this same cron pass.
        // We invalidate the snapshot cache to guarantee fresh reads — without this,
        // a pre-backup cache would trigger a false "no snapshot" alert.
        $restic->clearCache('snapshots');
        if ($diagnosticMode) {
            cronLog($log, "[DIAG] Cache snapshots invalide pour {$repo['name']} (backup vient de tourner)");
        }
    }
    if ($diagnosticMode) {
        cronLog($log, "[DIAG] snapshots() pour {$repo['name']}");
    }
    $snaps = $restic->snapshots();
    $ok = is_array($snaps) && !isset($snaps['error']);
    $count = $ok ? count($snaps) : 0;
    $alertHours = (int) ($repo['alert_hours'] ?? 25);
    $lastHour = $latestStatsByRepo[(int) $repo['id']] ?? null;
    $lastSnap = ($ok && $count > 0) ? end($snaps) : null;
    $hoursAgo = null;
    $status = 'ok';

    if (substr((string) $lastHour, 0, 13) !== substr($currentHourBucket, 0, 13)) {
        if ($diagnosticMode) {
            cronLog($log, "[DIAG] stats() pour {$repo['name']}");
        }
        $stats = $ok ? $restic->stats() : [];
        $db->prepare("
            INSERT INTO repo_stats_history (repo_id, snapshot_count, total_size, total_file_count, recorded_at)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $repo['id'],
            $count,
            (int) ($stats['total_size'] ?? 0),
            (int) ($stats['total_file_count'] ?? 0),
            $currentHourBucket,
        ]);
        $latestStatsByRepo[(int) $repo['id']] = $currentHourBucket;
    }

    if (!$ok) {
        $status = 'error';
    } elseif ($count === 0) {
        $status = 'no_snap';
    } elseif ($lastSnap) {
        try {
            $diff = $now->getTimestamp() - (new DateTimeImmutable((string) $lastSnap['time']))->getTimestamp();
            $hoursAgo = round($diff / 3600, 1);
            if ($hoursAgo > $alertHours) {
                $status = 'warning';
            }
        } catch (Throwable $e) {
            $hoursAgo = null;
        }
    }

    $runtimeStatuses[] = [
        'id' => (int) $repo['id'],
        'name' => $repo['name'],
        'count' => $count,
        'last_time' => $lastSnap['time'] ?? null,
        'hours_ago' => $hoursAgo,
        'status' => $status,
        'repo' => $repo,
    ];

    if ($ok && !empty($repo['snapshot_refresh_enabled'])) {
        if ($diagnosticMode) {
            cronLog($log, "[DIAG] sync catalogue pour {$repo['name']}");
        }
        RepoSnapshotCatalog::sync((int) $repo['id'], $snaps);
        if ($count > 0 && !SnapshotSearchIndex::isIndexed((int) $repo['id'], (string) ($lastSnap['short_id'] ?? ''))) {
            if ($diagnosticMode) {
                cronLog($log, "[DIAG] enqueue refresh index pour {$repo['name']}");
            }
            JobQueue::enqueueRepoSnapshotRefresh((int) $repo['id'], 'cron_catchup', 140);
        }
    }

    if (!$ok) {
        $pendingNotifications[] = ['type' => 'repo_alert', 'repo' => $repo, 'hours_ago' => 0, 'event' => 'error'];
    } elseif ($ok && $count === 0 && !$repoJustBacked) {
        $pendingNotifications[] = ['type' => 'repo_alert', 'repo' => $repo, 'hours_ago' => 0, 'event' => 'no_snap'];
    } elseif ($ok && $count > 0 && $hoursAgo !== null && $hoursAgo > $alertHours && !$repoJustBacked) {
        $pendingNotifications[] = ['type' => 'repo_alert', 'repo' => $repo, 'hours_ago' => $hoursAgo, 'event' => 'stale'];
    }
}
cronLog($log, '[REPO] Surveillance des depots terminee');

RepoStatusService::upsertStatuses($runtimeStatuses);

cronLog($log, '[STEP] Surveillance espace disque');
$diskChecks = DiskSpaceMonitor::performCronChecks();
foreach ($diskChecks['checks'] as $diskCheck) {
    $label = (string) ($diskCheck['location_label'] ?? $diskCheck['path'] ?? 'stockage');
    $host = !empty($diskCheck['host_name']) ? ' @ ' . $diskCheck['host_name'] : '';
    $free = formatBytes((int) ($diskCheck['free_bytes'] ?? 0));
    $total = formatBytes((int) ($diskCheck['total_bytes'] ?? 0));
    $severity = strtoupper((string) ($diskCheck['severity'] ?? 'unknown'));
    cronLog($log, "[DISK] {$severity} {$label}{$host} - {$free} libres / {$total}");
}
if (!empty($diskChecks['notifications'])) {
    $pendingNotifications[] = [
        'type' => 'disk_space_batch',
        'notifications' => (array) $diskChecks['notifications'],
    ];
}

// repo_snapshot_refresh runs HERE, after monitoring, never before.
// The limit is raised to 4 to cover multiple repositories in one pass.
cronLog($log, '[STEP] Rafraichissement index snapshots');
foreach (JobQueue::processDueJobs(4, ['repo_snapshot_refresh']) as $queueResult) {
    cronLog($log, "[QUEUE] #{$queueResult['id']} {$queueResult['type']} : {$queueResult['status']} - {$queueResult['message']}");
}

// 4. Maintenance courante
cronLog($log, '[STEP] Maintenance courante');
Auth::cleanExpiredSessions();

$cacheDirs = [
    dirname(DB_PATH) . '/cache/restic' => max(
        AppConfig::resticSnapshotsCacheTtl(),
        AppConfig::resticLsCacheTtl(),
        AppConfig::resticStatsCacheTtl(),
        AppConfig::resticSearchCacheTtl(),
        AppConfig::resticTreeCacheTtl()
    ),
    dirname(DB_PATH) . '/cache/restic-runtime' => 86400,
    dirname(DB_PATH) . '/cache/explore' => max(60, AppConfig::exploreViewCacheTtl()),
];

foreach ($cacheDirs as $cacheDir => $maxAgeSeconds) {
    if (!is_dir($cacheDir)) {
        continue;
    }

    $deleted = 0;
    foreach (glob($cacheDir . '/*.json') ?: [] as $cacheFile) {
        $modifiedAt = @filemtime($cacheFile);
        if ($modifiedAt !== false && (time() - $modifiedAt) > $maxAgeSeconds && @unlink($cacheFile)) {
            $deleted++;
        }
    }

    if ($deleted > 0) {
        cronLog($log, "[MAINT] Cache nettoye ($cacheDir): $deleted fichier(s)");
    }
}

$retentionPolicies = [
    ['table' => 'api_rate_limits', 'column' => 'hit_at', 'days' => AppConfig::auditRateLimitRetentionDays(), 'label' => 'Rate limits archives'],
    ['table' => 'login_attempts', 'column' => 'attempted_at', 'days' => AppConfig::auditLoginAttemptRetentionDays(), 'label' => 'Tentatives de connexion archivees'],
    ['table' => 'cron_log', 'column' => 'ran_at', 'days' => AppConfig::auditCronRetentionDays(), 'label' => 'Historique cron archive'],
    ['table' => 'activity_logs', 'column' => 'created_at', 'days' => AppConfig::auditActivityRetentionDays(), 'label' => 'Journal d activite archive'],
    ['table' => 'restore_history', 'column' => 'started_at', 'days' => AppConfig::auditRestoreRetentionDays(), 'label' => 'Historique des restaurations archive'],
    ['table' => 'disk_space_checks', 'column' => 'checked_at', 'days' => AppConfig::diskMonitorHistoryRetentionDays(), 'label' => 'Historique disque archive'],
];

foreach ($retentionPolicies as $policy) {
    $deleted = DbArchive::archiveAndPurgeByAge($db, $policy['table'], $policy['column'], $policy['days']);
    if ($deleted > 0) {
        cronLog($log, "[MAINT] {$policy['label']}: $deleted ligne(s)");
    }
}

$downsampledStats = DbArchive::downsampleRepoStatsHistory(
    AppConfig::auditRepoStatsHourlyRetentionDays(),
    AppConfig::auditRepoStatsDownsampleBatch()
);
if ($downsampledStats > 0) {
    cronLog($log, "[MAINT] Historique stats reduit: $downsampledStats ligne(s)");
}

$deletedJobs = DbArchive::archiveAndPurgeJobQueue($db, AppConfig::auditJobQueueRetentionDays());
if ($deletedJobs > 0) {
    cronLog($log, "[MAINT] File de jobs archivee: $deletedJobs job(s)");
}

$deletedArchives = DbArchive::purgeArchiveFiles(AppConfig::auditArchiveRetentionDays());
if ($deletedArchives > 0) {
    cronLog($log, "[MAINT] Archives gzip purgees: $deletedArchives fichier(s)");
}

$dailyOptimize = $db->prepare("
    SELECT 1
    FROM cron_log
    WHERE job_type = 'db_optimize'
      AND ran_at >= datetime('now', 'start of day')
    LIMIT 1
");
$dailyOptimize->execute();

if (!$dailyOptimize->fetchColumn()) {
    $db->exec('PRAGMA optimize');
    Database::getIndexInstance()->exec('PRAGMA optimize');
    $db->prepare("
        INSERT INTO cron_log (job_type, status, output)
        VALUES ('db_optimize', 'success', ?)
    ")->execute(['PRAGMA optimize execute sur les bases principale et index']);
    cronLog($log, '[MAINT] PRAGMA optimize execute');
}

$vacuumRun = SchedulerManager::runDbVacuumTask(false, $now);
foreach ($vacuumRun['logs'] ?? [] as $line) {
    cronLog($log, $line);
}
if (empty($vacuumRun['logs'])) {
    cronLog($log, '[MAINT] Aucun VACUUM planifie');
}

// 5. Batch dispatch of all notifications — executed last,
//    after all backups and monitoring are processed.
if (!empty($pendingNotifications)) {
    cronLog($log, '[STEP] Envoi des notifications (' . count($pendingNotifications) . ')');
    foreach ($pendingNotifications as $notif) {
        try {
            if ($notif['type'] === 'backup') {
                BackupJobManager::notifyResult($notif['job'], $notif['success'], $notif['output']);
            } elseif ($notif['type'] === 'copy') {
                CopyJobManager::notifyResult($notif['job'], $notif['success'], $notif['output']);
            } elseif ($notif['type'] === 'repo_alert') {
                Notifier::sendBackupAlert($notif['repo'], (float) $notif['hours_ago'], $notif['event']);
            } elseif ($notif['type'] === 'disk_space_batch') {
                DiskSpaceMonitor::dispatchGroupedNotifications((array) ($notif['notifications'] ?? []));
            }
        } catch (Throwable $e) {
            cronLog($log, '[NOTIF] Erreur envoi : ' . $e->getMessage());
        }
    }
}

// ── HA Broker cluster health check ───────────────────────────────────────────
cronLog($log, '[STEP] Verification sante cluster broker HA');
try {
    require_once __DIR__ . '/../src/BrokerClusterMonitor.php';
    $brokerResult = BrokerClusterMonitor::checkAndNotify();
    $brokerState  = (string) ($brokerResult['status']['state'] ?? 'unknown');
    $brokerEvents = $brokerResult['events'] ?? [];
    cronLog($log, '[BROKER] Etat cluster: ' . $brokerState
        . ' (' . (int) ($brokerResult['status']['healthy'] ?? 0)
        . '/' . (int) ($brokerResult['status']['total'] ?? 0) . ' noeuds)');
    if (!empty($brokerEvents)) {
        cronLog($log, '[BROKER] Evenements detectes: ' . implode(', ', $brokerEvents));
    }
} catch (Throwable $e) {
    cronLog($log, '[BROKER] Verification impossible: ' . SecretRedaction::safeThrowableMessage($e));
}

$output = implode("\n", $log);
if ($output === '') {
    $output = 'Aucune tache a executer';
    echo $output . "\n";
}

$db->prepare("
    INSERT INTO cron_log (job_type, status, output)
    VALUES (?, ?, ?)
")->execute([$cronJobType, $cronStatus, $output]);
