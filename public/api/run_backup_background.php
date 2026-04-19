<?php
// =============================================================================
// run_backup_background.php — executed in the background by run_backup_job.php
// Arguments : $argv[1] = job_id, $argv[2] = log_file, $argv[3] = run_id
// =============================================================================

set_time_limit(0);
ini_set('max_execution_time', 0);

$jobId   = (int) ($argv[1] ?? 0);
$logFile = $argv[2] ?? (rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'fulgurite_backup_bg.log');
$rawRunId = (string) ($argv[3] ?? '');

if (!$jobId) exit(1);

$_SESSION = [];
define('FULGURITE_CLI', true);

require_once __DIR__ . '/../../src/bootstrap.php';
ProcessRunner::daemonizeFromEnvironment();
$runId = RunLogManager::sanitizeRunId($rawRunId);

$runtimeCacheDir = Restic::getRuntimeCacheRootForCurrentProcess();

function bgLog(string $msg, string $logFile): void {
    $line = '[' . formatCurrentDisplayDate('H:i:s') . '] ' . $msg . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}

function bgAudit(string $msg, string $logFile, int $jobId, bool $forcePersist = false): void {
    static $lastPersistAt = 0;
    bgLog('[AUDIT] ' . $msg, $logFile);
    $now = time();
    if (!$forcePersist && ($now - $lastPersistAt) < 15) {
        return;
    }

    $snapshot = @file_get_contents($logFile);
    if ($snapshot === false || $snapshot === '') {
        return;
    }

    Database::getInstance()->prepare("
        UPDATE backup_jobs
        SET last_output = ?
        WHERE id = ? AND last_status = 'running'
    ")->execute([$snapshot, $jobId]);
    $lastPersistAt = $now;
}

// executes a command, captures stdout+stderr, logs each line, returns the exit code
function bgRun(array $cmd, array $env, string $logFile, int $jobId, ?string $cwd = null, array $options = []): int {
    $auditLabel = trim((string) ($options['audit_label'] ?? 'command'));
    if ($auditLabel === '') {
        $auditLabel = 'command';
    }
    $auditInterval = max(15, (int) ($options['audit_interval_seconds'] ?? 30));
    $lastHeartbeat = -$auditInterval;
    bgAudit("Start {$auditLabel}", $logFile, $jobId, true);

    $logged = 0;
    $result = ProcessRunner::run($cmd, [
        'cwd' => $cwd,
        'env' => $env,
        'umask' => $options['umask'] ?? null,
        'progress_callback' => static function (array $progress) use ($logFile, $jobId, $auditLabel, $auditInterval, &$lastHeartbeat): void {
            $elapsed = max(0, (int) ($progress['elapsed_seconds'] ?? 0));
            if ($elapsed - $lastHeartbeat < $auditInterval) {
                return;
            }
            $lastHeartbeat = $elapsed;
            bgAudit("Still running: {$auditLabel} (+" . $elapsed . "s)", $logFile, $jobId);
        },
        'stdout_callback' => static function (string $line) use ($logFile, &$logged): void {
            if (trim($line) === '') {
                return;
            }
            bgLog(trim($line), $logFile);
            $logged++;
        },
        'stderr_callback' => static function (string $line) use ($logFile, &$logged): void {
            if (trim($line) === '') {
                return;
            }
            bgLog(trim($line), $logFile);
            $logged++;
        },
    ]);
    $combined = (string) ($result['output'] ?? '');
    $rc = (int) ($result['code'] ?? 1);

    if (stripos($combined, 'permission denied') !== false) {
        bgLog('PERMISSION ERROR: check the rights of the restic repository and the application runtime cache.', $logFile);
    }

    if ($logged === 0) {
        bgLog("⚠ No output received (exit code: $rc)", $logFile);
    }

    bgAudit("End {$auditLabel} (code {$rc})", $logFile, $jobId, true);
    return $rc;
}

function bgRunDetailed(array $cmd, array $env, string $logFile, int $jobId, ?string $cwd = null, array $options = []): array {
    $auditLabel = trim((string) ($options['audit_label'] ?? 'command'));
    if ($auditLabel === '') {
        $auditLabel = 'command';
    }
    $auditInterval = max(15, (int) ($options['audit_interval_seconds'] ?? 30));
    $lastHeartbeat = -$auditInterval;
    bgAudit("Start {$auditLabel}", $logFile, $jobId, true);

    $logged = 0;
    $result = ProcessRunner::run($cmd, [
        'cwd' => $cwd,
        'env' => $env,
        'umask' => $options['umask'] ?? null,
        'progress_callback' => static function (array $progress) use ($logFile, $jobId, $auditLabel, $auditInterval, &$lastHeartbeat): void {
            $elapsed = max(0, (int) ($progress['elapsed_seconds'] ?? 0));
            if ($elapsed - $lastHeartbeat < $auditInterval) {
                return;
            }
            $lastHeartbeat = $elapsed;
            bgAudit("Still running: {$auditLabel} (+" . $elapsed . "s)", $logFile, $jobId);
        },
        'stdout_callback' => static function (string $line) use ($logFile, &$logged): void {
            if (trim($line) === '') {
                return;
            }
            bgLog(trim($line), $logFile);
            $logged++;
        },
        'stderr_callback' => static function (string $line) use ($logFile, &$logged): void {
            if (trim($line) === '') {
                return;
            }
            bgLog(trim($line), $logFile);
            $logged++;
        },
    ]);
    $combined = trim((string) ($result['output'] ?? ''));
    $rc = (int) ($result['code'] ?? 1);
    if (stripos($combined, 'permission denied') !== false) {
        bgLog('PERMISSION ERROR: check the rights of the restic repository and the application runtime cache.', $logFile);
    }

    if ($logged === 0) {
        bgLog("Aucune sortie recue (code retour: $rc)", $logFile);
    }

    bgAudit("End {$auditLabel} (code {$rc})", $logFile, $jobId, true);
    return ['code' => $rc, 'output' => $combined];
}

file_put_contents($logFile, '');
bgLog("Démarrage du job #$jobId", $logFile);
if ($runId !== '') {
    bgLog("Run ID : $runId", $logFile);
}
if (($pid = getmypid()) !== false) {
    bgLog("PID : $pid", $logFile);
}

$job = BackupJobManager::getById($jobId);
if (!$job) {
    bgLog("ERREUR: Job #$jobId introuvable", $logFile);
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}

// Mark immediately as running — leaves a trace if process crashes
// and prevents getDueJobs() from triggering the same job in parallel via cron
BackupJobManager::markRunning($jobId);
bgAudit('Phase: initialisation', $logFile, $jobId, true);

bgLog("Job : {$job['name']}", $logFile);
bgLog("Dépôt : {$job['repo_name']} ({$job['repo_path']})", $logFile);

$sourcePaths = json_decode($job['source_paths'], true) ?? [];
$tags        = json_decode($job['tags'],         true) ?? [];
$excludes    = json_decode($job['excludes'],     true) ?? [];

$preHook           = '';
$postHook          = '';
$retentionEnabled  = (int)($job['retention_enabled'] ?? 0);

bgLog("Chemins : " . implode(', ', $sourcePaths), $logFile);
if (!empty($tags))     bgLog("Tags : "       . implode(', ', $tags),     $logFile);
if (!empty($excludes)) bgLog("Exclusions : " . implode(', ', $excludes), $logFile);

$repo = RepoManager::getById($job['repo_id']);
if (!$repo) {
    bgLog("ERREUR: Dépôt introuvable", $logFile);
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}

$preflight = DiskSpaceMonitor::preflightBackupJob($job, $repo);
if (empty($preflight['allowed'])) {
    bgLog('[PRECHECK] ' . (string) ($preflight['message'] ?? 'Espace disque insuffisant'), $logFile);
    $output = file_get_contents($logFile);
    Database::getInstance()->prepare("UPDATE backup_jobs SET last_run=datetime('now'),last_status='failed',last_output=? WHERE id=?")->execute([$output, $jobId]);
    Database::getInstance()->prepare("INSERT INTO cron_log (job_type,job_id,status,output) VALUES ('backup',?,?,?)")->execute([$jobId,'failed',$output]);
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}
if (!empty($preflight['supported'])) {
    bgLog('[PRECHECK] ' . (string) ($preflight['message'] ?? 'Verification disque OK'), $logFile);
}


// ── Determine mode: local or remote ─────────────────────────────────────
$isRemote = !empty($job['host_id']) && !empty($job['ssh_key_id']);
$resolvedRetryPolicy = JobRetryPolicy::resolvePolicy(JobRetryPolicy::getEntityPolicy($job));

if (!empty($job['pre_hook_script_id'])) {
    bgLog('[HOOK] Execution du pre-hook approuve', $logFile);
    $hookResult = HookScriptRunner::runJobHook($job, 'pre');
    if (!empty($hookResult['output'])) {
        foreach (preg_split('/\r?\n/', (string) $hookResult['output']) as $line) {
            $line = trim($line);
            if ($line !== '') {
                bgLog($line, $logFile);
            }
        }
    }
    if (empty($hookResult['success'])) {
        if (!empty($job['post_hook_script_id'])) {
            bgLog('[HOOK] Execution du post-hook de nettoyage', $logFile);
            $cleanupResult = HookScriptRunner::runJobHook($job, 'post');
            if (!empty($cleanupResult['output'])) {
                foreach (preg_split('/\r?\n/', (string) $cleanupResult['output']) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        bgLog($line, $logFile);
                    }
                }
            }
        }
        $output = file_get_contents($logFile);
        $db = Database::getInstance();
        $db->prepare("UPDATE backup_jobs SET last_run=datetime('now'),last_status='failed',last_output=? WHERE id=?")->execute([$output, $jobId]);
        $db->prepare("INSERT INTO cron_log (job_type,job_id,status,output) VALUES ('backup',?,?,?)")->execute([$jobId,'failed',$output]);
        file_put_contents($logFile . '.done', 'error');
        exit(1);
    }
}

$returnCode = 0;
$passFile   = null;
$tmpHome    = null;

// ── Helper: run a script via SSH (used for hooks + remote retention) ──
function bgRunSSH(array $sshBase, string $script, array $env, string $logFile, int $jobId, array $options = []): int {
    $cmd = array_merge($sshBase, [$script]);
    return bgRun($cmd, $env, $logFile, $jobId, null, $options);
}

function bgIsLocalFilesystemPath(string $path): bool {
    return $path !== ''
        && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)
        && !preg_match('#^[a-z0-9+.-]+:#i', $path);
}

function bgLogPermissionReport(array $report, string $path, string $logFile): void {
    $changed = (int) ($report['changed'] ?? 0);
    $errors  = is_array($report['errors'] ?? null) ? $report['errors'] : [];
    $sudo    = is_array($report['sudo'] ?? null) ? $report['sudo'] : [];

    if ($changed > 0) {
        bgLog("✓ Permissions corrigées sur {$path} ({$changed} éléments ajustés)", $logFile);
    }

    if (!empty($sudo['attempted'])) {
        if (!empty($sudo['success'])) {
            bgLog("✓ Réparation permissions via sudo réussie sur {$path}", $logFile);
        } else {
            $code = (int) ($sudo['code'] ?? 1);
            bgLog("⚠ Réparation permissions via sudo échouée sur {$path} (code {$code})", $logFile);
            $sudoOutput = trim((string) ($sudo['output'] ?? ''));
            if ($sudoOutput !== '') {
                foreach (preg_split('/\r?\n/', $sudoOutput) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        bgLog("  > {$line}", $logFile);
                    }
                }
            }
        }
    }

    if ($errors === []) {
        return;
    }

    bgLog("⚠ Correction des permissions incomplète sur {$path} (" . count($errors) . " erreur(s))", $logFile);
    foreach (array_slice($errors, 0, 10) as $error) {
        $mode    = (string) ($error['mode'] ?? '????');
        $target  = (string) ($error['path'] ?? '?');
        $message = trim((string) ($error['message'] ?? 'erreur inconnue'));
        bgLog("  - chmod {$mode} {$target} :: {$message}", $logFile);
    }
    if (count($errors) > 10) {
        bgLog("  - ... " . (count($errors) - 10) . " erreur(s) supplémentaire(s)", $logFile);
    }
}

function bgBuildRemotePermissionCommand(string $path): string {
    $escaped = escapeshellarg($path);
    return 'find ' . $escaped . ' -type d -exec chmod 2770 {} +'
         . ' && find ' . $escaped . ' -type f -exec chmod 0660 {} +';
}

if ($isRemote) {
    // ── Mode SSH remote ──────────────────────────────────────────────────────
    // restic runs on the remote host; repository must be accessible from
    // that host (job field "remote repository path", e.g. sftp:user@server:/backups/repo)
    bgLog("Mode : distant (SSH) — {$job['host_user']}@{$job['host_hostname']}:{$job['host_port']}", $logFile);
    bgLog("---", $logFile);

    $repoPassword = RepoManager::getPassword($repo);
    $tmpKey = SshKeyManager::getTemporaryKeyFile((int) ($job['ssh_key_id'] ?? 0));
    $sudoPassword = HostManager::getSudoPassword($job);

    // Repository path as seen from remote host
    $remoteRepoPath = !empty($job['remote_repo_path']) ? $job['remote_repo_path'] : $job['repo_path'];
    bgLog("Dépôt distant : $remoteRepoPath", $logFile);

    // Strategy: write password to a temp file on remote host
    // then use --password-file (compatible with all restic versions, no pipe)
    $resticCmd = 'umask 0007; restic -r ' . escapeshellarg($remoteRepoPath)
               . ' --password-file "$_RPASS"'
               . ' --cache-dir /tmp/restic-cache backup';
    if (!empty($job['hostname_override'])) {
        $resticCmd .= ' --host ' . escapeshellarg($job['hostname_override']);
    }
    foreach ($tags        as $tag) { $resticCmd .= ' --tag '     . escapeshellarg($tag); }
    foreach ($excludes    as $pat) { $resticCmd .= ' --exclude ' . escapeshellarg($pat); }
    foreach ($sourcePaths as $p)   { $resticCmd .= ' '           . escapeshellarg($p); }

    if ($sudoPassword) {
        // env HOME= preserves elkarbackup SSH keys even when restic runs as root
        $userHome = '/home/' . $job['host_user'];
        $runRestic = 'echo ' . escapeshellarg($sudoPassword) . ' | sudo -S env HOME=' . escapeshellarg($userHome) . ' ' . $resticCmd;
    } else {
        $runRestic = $resticCmd;
    }

    // Full command: createate temp file, run restic, delete file
    $remoteCmd = '_RPASS=$(mktemp)'
               . ' && printf %s ' . escapeshellarg(trim($repoPassword)) . ' > "$_RPASS"'
               . ' && (' . $runRestic . ')'
               . '; _RC=$?; rm -f "$_RPASS"; exit $_RC';

    $tmpHome = '/tmp/fulgurite-bkp-' . $jobId . '-' . uniqid();
    mkdir($tmpHome . '/.ssh', 0700, true);

    bgLog("SSH : " . SSH_BIN . " -i $tmpKey -p {$job['host_port']} {$job['host_user']}@{$job['host_hostname']}", $logFile);
    bgLog("Cmd : restic -r " . escapeshellarg($remoteRepoPath) . " [...] backup " . implode(' ', array_map('escapeshellarg', $sourcePaths)), $logFile);

    $env = [
        'HOME' => $tmpHome,
        'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
    ];

    $sshBase = array_merge([
        SSH_BIN,
        '-i', $tmpKey,
        '-p', (string) $job['host_port'],
    ], SshKnownHosts::sshOptions((string) $job['host_hostname'], (int) $job['host_port'], 10), [
        $job['host_user'] . '@' . $job['host_hostname'],
    ]);

    $cmd = array_merge($sshBase, [$remoteCmd]);
    $retryCount = 0;
    $attempt = 1;
    while (true) {
        bgLog("Tentative #{$attempt}", $logFile);
        $attemptResult = bgRunDetailed($cmd, $env, $logFile, $jobId, null, [
            'audit_label' => "backup distant tentative #{$attempt}",
        ]);
        $attemptResult = SshKnownHosts::finalizeSshResult($attemptResult, (string) $job['host_hostname'], (int) $job['host_port'], 'backup_remote');
        if (!empty($attemptResult['host_key'])) {
            bgLog('BLOCAGE HOST KEY: ' . (string) ($attemptResult['output'] ?? 'Verification stricte SSH refusee'), $logFile);
        }
        $returnCode = (int) ($attemptResult['code'] ?? 1);
        if ($returnCode === 0) {
            break;
        }

        $decision = JobRetryPolicy::shouldRetry(
            $resolvedRetryPolicy,
            (string) ($attemptResult['output'] ?? ''),
            $returnCode,
            $retryCount
        );
        $classification = $decision['classification'] ?? ['label' => 'Erreur non classee'];
        bgLog("Classification: " . ($classification['label'] ?? 'Erreur non classee'), $logFile);

        if (empty($decision['retry'])) {
            bgLog("Pas de retry: " . ($decision['reason'] ?? 'politique non applicable'), $logFile);
            break;
        }

        $retryCount++;
        $attempt++;
        $delaySeconds = max(1, (int) ($decision['delay_seconds'] ?? 1));
        bgLog("Retry #{$retryCount} dans {$delaySeconds}s", $logFile);
        sleep($delaySeconds);
    }

    if ($returnCode === 0 && bgIsLocalFilesystemPath($remoteRepoPath)) {
        $permissionCmd = bgBuildRemotePermissionCommand($remoteRepoPath);
        if ($sudoPassword) {
            $permissionCmd = 'echo ' . escapeshellarg($sudoPassword) . ' | sudo -S bash -lc ' . escapeshellarg($permissionCmd);
        } else {
            $permissionCmd = 'bash -lc ' . escapeshellarg($permissionCmd);
        }
        bgLog("Réparation distante des permissions sur {$remoteRepoPath}", $logFile);
        $permRc = bgRunSSH($sshBase, $permissionCmd, $env, $logFile, $jobId, [
            'audit_label' => 'correction permissions distante',
        ]);
        if ($permRc !== 0) {
            bgLog("⚠ Correction distante des permissions échouée (code {$permRc})", $logFile);
        }
    }

@unlink($tmpKey);
} else {
    // ── Mode local ────────────────────────────────────────────────────────────
    bgLog("Mode : local", $logFile);
    bgLog("---", $logFile);

    $passFile = Restic::writeTempSecretFile(RepoManager::getPassword($repo), 'rui_bkp_pass_');

    $env = [
        'RESTIC_CACHE_DIR' => $runtimeCacheDir,
        'HOME'             => '/var/www',
        'RCLONE_CONFIG'    => '/var/www/.config/rclone/rclone.conf',
        'XDG_CACHE_HOME'   => '/tmp',
        'PATH'             => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
    ];

    $cmd = [RESTIC_BIN, '-r', $repo['path'], '--password-file', $passFile, 'backup'];
    if (!empty($job['hostname_override'])) { $cmd[] = '--host'; $cmd[] = $job['hostname_override']; }
    foreach ($tags        as $tag) { $cmd[] = '--tag';     $cmd[] = $tag; }
    foreach ($excludes    as $pat) { $cmd[] = '--exclude'; $cmd[] = $pat; }
    foreach ($sourcePaths as $p)   { $cmd[] = $p; }

    $retryCount = 0;
    $attempt = 1;
    while (true) {
        bgLog("Tentative #{$attempt}", $logFile);
        $attemptResult = bgRunDetailed($cmd, $env, $logFile, $jobId, null, [
            'umask' => 0007,
            'audit_label' => "backup local tentative #{$attempt}",
        ]);
        $returnCode = (int) ($attemptResult['code'] ?? 1);
        if ($returnCode === 0) {
            break;
        }

        $decision = JobRetryPolicy::shouldRetry(
            $resolvedRetryPolicy,
            (string) ($attemptResult['output'] ?? ''),
            $returnCode,
            $retryCount
        );
        $classification = $decision['classification'] ?? ['label' => 'Erreur non classee'];
        bgLog("Classification: " . ($classification['label'] ?? 'Erreur non classee'), $logFile);

        if (empty($decision['retry'])) {
            bgLog("Pas de retry: " . ($decision['reason'] ?? 'politique non applicable'), $logFile);
            break;
        }

        $retryCount++;
        $attempt++;
        $delaySeconds = max(1, (int) ($decision['delay_seconds'] ?? 1));
        bgLog("Retry #{$retryCount} dans {$delaySeconds}s", $logFile);
        sleep($delaySeconds);
    }
}

// ── Post-hook ─────────────────────────────────────────────────────────────────
if ($postHook) {
    bgLog("── Post-hook ─────────────────────────────────────────────────────", $logFile);
    if ($isRemote && isset($sshBase)) {
        bgRunSSH($sshBase, $postHookWithEnv ?? $postHook, $env, $logFile, $jobId, [
            'audit_label' => 'post-hook distant',
        ]);
    } else {
        bgRun(['bash', '-c', $postHook], $localHookEnv ?? $env, $logFile, $jobId, null, [
            'audit_label' => 'post-hook local',
        ]);
    }
}

if (!empty($job['post_hook_script_id'])) {
    bgLog('[HOOK] Execution du post-hook approuve', $logFile);
    $hookResult = HookScriptRunner::runJobHook($job, 'post');
    if (!empty($hookResult['output'])) {
        foreach (preg_split('/\r?\n/', (string) $hookResult['output']) as $line) {
            $line = trim($line);
            if ($line !== '') {
                bgLog($line, $logFile);
            }
        }
    }
}

$success = $returnCode === 0;

// ── retention ─────────────────────────────────────────────────────────────────
if ($success && $retentionEnabled) {
    bgLog("── Rétention (restic forget) ─────────────────────────────────────", $logFile);
    $keepLast    = (int)($job['retention_keep_last']    ?? 0);
    $keepDaily   = (int)($job['retention_keep_daily']   ?? 0);
    $keepWeekly  = (int)($job['retention_keep_weekly']  ?? 0);
    $keepMonthly = (int)($job['retention_keep_monthly'] ?? 0);
    $keepYearly  = (int)($job['retention_keep_yearly']  ?? 0);
    $retPrune    = (bool)($job['retention_prune']       ?? true);

    if ($keepLast || $keepDaily || $keepWeekly || $keepMonthly || $keepYearly) {
        if ($isRemote && isset($sshBase)) {
            $repoPassword  = RepoManager::getPassword($repo);
            $remoteRepoPath2 = !empty($job['remote_repo_path']) ? $job['remote_repo_path'] : $job['repo_path'];
            $forgetCmd  = 'restic -r ' . escapeshellarg($remoteRepoPath2) . ' --password-file "$_RPASS" --cache-dir /tmp/restic-cache forget';
            if ($keepLast)    $forgetCmd .= ' --keep-last '    . $keepLast;
            if ($keepDaily)   $forgetCmd .= ' --keep-daily '   . $keepDaily;
            if ($keepWeekly)  $forgetCmd .= ' --keep-weekly '  . $keepWeekly;
            if ($keepMonthly) $forgetCmd .= ' --keep-monthly ' . $keepMonthly;
            if ($keepYearly)  $forgetCmd .= ' --keep-yearly '  . $keepYearly;
            if ($retPrune)    $forgetCmd .= ' --prune';
            $retRemoteCmd = '_RPASS=$(mktemp)'
                          . ' && printf %s ' . escapeshellarg(trim($repoPassword)) . ' > "$_RPASS"'
                          . ' && (' . $forgetCmd . ')'
                          . '; _RC=$?; rm -f "$_RPASS"; exit $_RC';
            $retRc = bgRunSSH($sshBase, $retRemoteCmd, $env, $logFile, $jobId, [
                'audit_label' => 'retention distante',
            ]);
        } else {
            $retCmd = [RESTIC_BIN, '-r', $repo['path'], '--password-file', $passFile, 'forget'];
            if ($keepLast)    { $retCmd[] = '--keep-last';    $retCmd[] = (string)$keepLast; }
            if ($keepDaily)   { $retCmd[] = '--keep-daily';   $retCmd[] = (string)$keepDaily; }
            if ($keepWeekly)  { $retCmd[] = '--keep-weekly';  $retCmd[] = (string)$keepWeekly; }
            if ($keepMonthly) { $retCmd[] = '--keep-monthly'; $retCmd[] = (string)$keepMonthly; }
            if ($keepYearly)  { $retCmd[] = '--keep-yearly';  $retCmd[] = (string)$keepYearly; }
            if ($retPrune)    { $retCmd[] = '--prune'; }
            $retRc = bgRun($retCmd, $env, $logFile, $jobId, null, [
                'audit_label' => 'retention locale',
            ]);
        }
        if (isset($retRc) && $retRc !== 0) {
            bgLog("⚠ Rétention échouée (code $retRc)", $logFile);
        }
    }
}

// ── Final permissions fix (after all restic writes) ────────────────────────
if ($success && isset($repo['path']) && bgIsLocalFilesystemPath($repo['path']) && is_dir($repo['path'])) {
    $permissionReport = RepoManager::fixGroupPermissions($repo['path']);
    bgLogPermissionReport($permissionReport, $repo['path'], $logFile);
}

// ── Refresh catalog/index on final repository state ──────────────────────
if ($success) {
    // Background script bypasses Restic helpers that normally purge
    // repository cache after write.
    RepoManager::getRestic($repo)->clearCache();
    if (!empty($repo['snapshot_refresh_enabled'])) {
        $refreshJobId = JobQueue::enqueueRepoSnapshotRefresh((int) $repo['id'], 'backup_success', 220);
        bgLog("[INDEX] Refresh enfile (job #{$refreshJobId})", $logFile);
    }
}

// ── Cleanup ───────────────────────────────────────────────────────────────────
Restic::deleteTempSecretFile($passFile);
if ($tmpHome) {
    FileSystem::removeDirectory($tmpHome);
}

// ── Final result ────────────────────────────────────────────────────────────
bgLog("---", $logFile);
bgLog($success ? "✓ Sauvegarde terminée avec succès" : "✗ Erreur (code: $returnCode)", $logFile);

$output = file_get_contents($logFile);
$db = Database::getInstance();
$db->prepare("
    UPDATE backup_jobs SET last_run = datetime('now'), last_status = ?, last_output = ? WHERE id = ?
")->execute([$success ? 'success' : 'failed', $output, $jobId]);

$db->prepare("
    INSERT INTO cron_log (job_type, job_id, status, output) VALUES ('backup', ?, ?, ?)
")->execute([$jobId, $success ? 'success' : 'failed', $output]);

BackupJobManager::notifyResult($job, $success, $output);
bgAudit('Phase: terminee (' . ($success ? 'success' : 'error') . ')', $logFile, $jobId, true);

file_put_contents($logFile . '.done', $success ? 'success' : 'error');
