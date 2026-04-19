<?php
// =============================================================================
// BackupJobManager.php — management of jobs of backup restic backup
// =============================================================================

class BackupJobManager {

    // ── Lire ──────────────────────────────────────────────────────────────────

    public static function getAll(?array $repoIds = null, ?array $hostIds = null): array {
        $db = Database::getInstance();
        $sql = "
            SELECT b.*, r.name AS repo_name, r.path AS repo_path,
                   h.name AS host_name, h.hostname AS host_hostname,
                   h.user AS host_user, h.port AS host_port,
                   h.sudo_password_file, h.sudo_password_ref, h.ssh_key_id,
                   k.private_key_file,
                   pre_script.name AS pre_hook_script_name,
                   post_script.name AS post_hook_script_name
            FROM backup_jobs b
            LEFT JOIN repos r ON r.id = b.repo_id
            LEFT JOIN hosts h ON h.id = b.host_id
            LEFT JOIN ssh_keys k ON k.id = h.ssh_key_id
            LEFT JOIN hook_scripts pre_script ON pre_script.id = b.pre_hook_script_id
            LEFT JOIN hook_scripts post_script ON post_script.id = b.post_hook_script_id
        ";

        $where = [];
        $params = [];
        if ($repoIds !== null) {
            $where[] = self::inClause('b.repo_id', $repoIds, $params);
        }
        if ($hostIds !== null) {
            if ($hostIds === []) {
                $where[] = 'b.host_id IS NULL';
            } else {
                $where[] = '(b.host_id IS NULL OR ' . self::inClause('b.host_id', $hostIds, $params) . ')';
            }
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY b.name';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT b.*, r.name AS repo_name, r.path AS repo_path,
                   h.name AS host_name, h.hostname AS host_hostname,
                   h.user AS host_user, h.port AS host_port,
                   h.sudo_password_file, h.sudo_password_ref, h.ssh_key_id,
                   k.private_key_file,
                   pre_script.name AS pre_hook_script_name,
                   post_script.name AS post_hook_script_name
            FROM backup_jobs b
            LEFT JOIN repos r ON r.id = b.repo_id
            LEFT JOIN hosts h ON h.id = b.host_id
            LEFT JOIN ssh_keys k ON k.id = h.ssh_key_id
            LEFT JOIN hook_scripts pre_script ON pre_script.id = b.pre_hook_script_id
            LEFT JOIN hook_scripts post_script ON post_script.id = b.post_hook_script_id
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ── Write helpers ─────────────────────────────────────────────────────────

    /**
     * Validates all source paths before persisting or executing a backup job.
     *
     * For local jobs ($isRemote = false) paths must be absolute, free of traversal
     * and control characters, and must not point to virtual filesystems (/proc, /sys, /dev).
     * For remote jobs ($isRemote = true) the same format rules apply but virtual FS
     * roots are not rejected (they live on the remote host, outside local scope).
     *
     * @throws InvalidArgumentException|RuntimeException on the first invalid path
     */
    public static function validateSourcePaths(array $paths, bool $isRemote = false): void
    {
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            FilesystemScopeGuard::assertValidBackupSourcePath($path, !$isRemote);
        }
    }

    public static function add(
        string  $name,
        int     $repoId,
        array   $sourcePaths,
        array   $tags             = [],
        array   $excludes         = [],
        string  $description      = '',
        int     $scheduleEnabled  = 0,
        int     $scheduleHour     = 2,
        string  $scheduleDays     = '1',
        int     $notifyOnFailure  = 1,
        ?int    $hostId           = null,
        ?string $remoteRepoPath   = null,
        ?string $hostnameOverride = null,
        int     $retentionEnabled = 0,
        int     $retKeepLast      = 0,
        int     $retKeepDaily     = 0,
        int     $retKeepWeekly    = 0,
        int     $retKeepMonthly   = 0,
        int     $retKeepYearly    = 0,
        int     $retPrune         = 1,
        ?int    $preHookScriptId  = null,
        ?int    $postHookScriptId = null,
        ?string $notificationPolicy = null,
        ?string $retryPolicy = null
    ): int {
        self::validateSourcePaths($sourcePaths, $hostId !== null);
        $db = Database::getInstance();
        $db->prepare("
            INSERT INTO backup_jobs
                (name, repo_id, source_paths, tags, excludes, description,
                 schedule_enabled, schedule_hour, schedule_days, notify_on_failure,
                 host_id, remote_repo_path, hostname_override,
                 retention_enabled, retention_keep_last, retention_keep_daily,
                 retention_keep_weekly, retention_keep_monthly, retention_keep_yearly,
                 retention_prune, pre_hook_script_id, post_hook_script_id, notification_policy, retry_policy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $name,
            $repoId,
            json_encode(array_values($sourcePaths)),
            json_encode(array_values($tags)),
            json_encode(array_values($excludes)),
            $description,
            $scheduleEnabled,
            $scheduleHour,
            $scheduleDays,
            $notifyOnFailure,
            $hostId,
            $remoteRepoPath   ?: null,
            $hostnameOverride ?: null,
            $retentionEnabled,
            $retKeepLast,
            $retKeepDaily,
            $retKeepWeekly,
            $retKeepMonthly,
            $retKeepYearly,
            $retPrune,
            $preHookScriptId ?: null,
            $postHookScriptId ?: null,
            $notificationPolicy,
            $retryPolicy,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $db  = Database::getInstance();
        $job = self::getById($id);
        if (!$job) return;

        $fields = [];
        $values = [];

        foreach (['name', 'repo_id', 'description', 'schedule_enabled', 'schedule_hour',
                  'schedule_days', 'notify_on_failure', 'host_id', 'remote_repo_path',
                  'hostname_override', 'retention_enabled', 'retention_keep_last',
                  'retention_keep_daily', 'retention_keep_weekly', 'retention_keep_monthly',
                  'retention_keep_yearly', 'retention_prune', 'pre_hook_script_id', 'post_hook_script_id',
                  'notification_policy', 'retry_policy'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }

        foreach (['source_paths', 'tags', 'excludes'] as $f) {
            if (array_key_exists($f, $data)) {
                if ($f === 'source_paths') {
                    $effectiveHostId = array_key_exists('host_id', $data)
                        ? ($data['host_id'] !== null && $data['host_id'] !== '' ? (int) $data['host_id'] : null)
                        : ($job['host_id'] !== null && $job['host_id'] !== '' ? (int) $job['host_id'] : null);
                    self::validateSourcePaths((array) $data[$f], $effectiveHostId !== null);
                }
                $fields[] = "$f = ?";
                $values[] = json_encode(array_values((array) $data[$f]));
            }
        }

        if (empty($fields)) return;
        $values[] = $id;
        $db->prepare("UPDATE backup_jobs SET " . implode(', ', $fields) . " WHERE id = ?")
           ->execute($values);
    }

    public static function delete(int $id): void {
        Database::getInstance()
            ->prepare("DELETE FROM backup_jobs WHERE id = ?")
            ->execute([$id]);
    }

    private static function inClause(string $column, array $ids, array &$params): string {
        if ($ids === []) {
            return '1 = 0';
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        foreach ($ids as $id) {
            $params[] = (int) $id;
        }

        return $column . ' IN (' . $placeholders . ')';
    }

    // ── Marquer en cours before execution ─────────────────────────────────────
    // Appele before the backup for : (1) fournir a trace en cas of crash of
    // process, (2) empecher a double-declenchement in getDueJobs() if the
    // same job is declenche en parallele (cron + UI by exemple).
    public static function markRunning(int $jobId): void {
        Database::getInstance()->prepare(
            "UPDATE backup_jobs SET last_run = datetime('now'), last_status = 'running', last_output = '' WHERE id = ?"
        )->execute([$jobId]);
    }

    // ── Executer (cron synchrone) ─────────────────────────────────────────────

    public static function run(int $jobId, bool $notifyNow = true): array {
        $job = self::getById($jobId);
        if (!$job) return ['success' => false, 'output' => 'Job introuvable', 'job' => null];

        $repo = RepoManager::getById($job['repo_id']);
        if (!$repo) return ['success' => false, 'output' => 'Dépôt introuvable', 'job' => $job];

        // Marquer immediatement comme "running" — protege contre the doubles
        // declenchements and laisse a trace if the process crash before _saveResult()
        self::markRunning($jobId);

        $sourcePaths = json_decode($job['source_paths'], true) ?? [];
        $tags        = json_decode($job['tags'],         true) ?? [];
        $excludes    = json_decode($job['excludes'],     true) ?? [];

        $log      = [];
        $log[]    = "→ Sauvegarde « {$job['name']} » vers {$job['repo_name']}";
        $isRemote = !empty($job['host_id']) && !empty($job['private_key_file']);
        $persistRunningLog = static function () use (&$log, $jobId): void {
            Database::getInstance()->prepare("
                UPDATE backup_jobs
                SET last_output = ?
                WHERE id = ? AND last_status = 'running'
            ")->execute([implode("\n", $log), $jobId]);
        };
        $audit = static function (string $message, bool $persist = true) use (&$log, $persistRunningLog): void {
            $log[] = '[AUDIT] ' . $message;
            if ($persist) {
                $persistRunningLog();
            }
        };
        $audit('Initialisation du run (mode ' . ($isRemote ? 'distant' : 'local') . ')');

        if (!empty($job['pre_hook_script_id'])) {
            $log[] = "Pre-hook approuve";
            $audit('Execution du pre-hook approuve');
            $hookResult = HookScriptRunner::runJobHook($job, 'pre');
            if (!empty($hookResult['output'])) {
                $log[] = $hookResult['output'];
            }
            if (empty($hookResult['success'])) {
                if (!empty($job['post_hook_script_id'])) {
                    $log[] = "Post-hook de nettoyage";
                    $cleanupResult = HookScriptRunner::runJobHook($job, 'post');
                    if (!empty($cleanupResult['output'])) {
                        $log[] = $cleanupResult['output'];
                    }
                }
                $output = implode("\n", $log);
                self::_saveResult($jobId, false, $output);
                if ($notifyNow) self::notifyResult($job, false, $output);
                return ['success' => false, 'output' => $output, 'job' => $job];
            }
        }

        // the hooks shell libres historiques are desactives.
        $preHook  = '';
        $postHook = '';

        // ── Helpers of execution ───────────────────────────────────────────────

        $execLocal = function(string $script) use (&$log): int {
            $result = ProcessRunner::run(['bash', '-c', $script]);
            $out = (string) ($result['stdout'] ?? '');
            $err = (string) ($result['stderr'] ?? '');
            $rc = (int) ($result['code'] ?? 1);
            foreach (explode("\n", $out . "\n" . $err) as $l) {
                if (trim($l)) $log[] = "  " . trim($l);
            }
            return $rc;
        };

        $execRemote = function(string $script) use ($job, &$log): int {
            $tmpHome = '/tmp/fulgurite-hook-' . uniqid();
            mkdir($tmpHome . '/.ssh', 0700, true);
            $sshKeyFile = SshKeyManager::getTemporaryKeyFile((int)$job['ssh_key_id']);
            $sshBase = array_merge([
                SSH_BIN,
                '-i', $sshKeyFile,
                '-p', (string) $job['host_port'],
            ], SshKnownHosts::sshOptions((string) $job['host_hostname'], (int) $job['host_port'], 10), [
                $job['host_user'] . '@' . $job['host_hostname'],
                $script,
            ]);
            $env     = ['HOME' => $tmpHome, 'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'];
            $result = ProcessRunner::run($sshBase, ['env' => $env]);
            $out = (string) ($result['stdout'] ?? '');
            $err = (string) ($result['stderr'] ?? '');
            $rc = (int) ($result['code'] ?? 1);
            Restic::deleteTempSecretFile($sshKeyFile);
            FileSystem::removeDirectory($tmpHome);
            foreach (explode("\n", $out . "\n" . $err) as $l) {
                if (trim($l)) $log[] = "  " . trim($l);
            }
            return $rc;
        };

        // ── Pre-hook ──────────────────────────────────────────────────────────

        if ($preHook) {
            $log[] = "── Pré-hook";
            $hookRc = $isRemote ? $execRemote($preHook) : $execLocal($preHook);
            if ($hookRc !== 0) {
                $log[] = "✗ Pré-hook échoué (code $hookRc) — sauvegarde annulée";
                if ($postHook) {
                    $log[] = "── Post-hook (nettoyage)";
                    $isRemote ? $execRemote($postHook) : $execLocal($postHook);
                }
                $output = implode("\n", $log);
                self::_saveResult($jobId, false, $output);
                if ($notifyNow) self::notifyResult($job, false, $output);
                return ['success' => false, 'output' => $output, 'job' => $job];
            }
        }

        // ── backup ────────────────────────────────────────────────────────

        $sudoPassword = HostManager::getSudoPassword($job);
        $remoteRepoPath   = !empty($job['remote_repo_path']) ? $job['remote_repo_path'] : $job['repo_path'];
        $hostnameOverride = $job['hostname_override'] ?? '';
        $resolvedRetryPolicy = JobRetryPolicy::resolvePolicy(JobRetryPolicy::getEntityPolicy($job));
        $retryCount = 0;
        $attempt = 1;
        $result = ['success' => false, 'output' => '', 'code' => 1];
        $restic = RepoManager::getRestic($repo);
        $audit('Debut prechecks');

        $preflight = DiskSpaceMonitor::preflightBackupJob($job, $repo);
        if (empty($preflight['allowed'])) {
            $log[] = '[PRECHECK] ' . (string) ($preflight['message'] ?? 'Espace disque insufficient');
            $output = implode("\n", $log);
            self::_saveResult($jobId, false, $output);
            if ($notifyNow) self::notifyResult($job, false, $output);
            return ['success' => false, 'output' => $output, 'job' => $job];
        }
        if (!empty($preflight['supported'])) {
            $log[] = '[PRECHECK] ' . (string) ($preflight['message'] ?? 'Verification disque OK');
        }

        if (self::isLocalFilesystemPath((string) ($repo['path'] ?? ''))) {
            $log[] = "-- Permissions depot avant backup";
            $permissionReport = RepoManager::fixGroupPermissions((string) $repo['path']);
            $log[] = self::formatPermissionReport((string) $repo['path'], $permissionReport);
        }

        if ($isRemote && !empty($job['remote_repo_path']) && self::looksLikeLocalFilesystemPath($remoteRepoPath)) {
            $log[] = "-- Permissions depot distant avant backup";
            $permissionCommand = self::buildRemotePermissionCommand($remoteRepoPath);
            $permissionRc = $execRemote('bash -lc ' . escapeshellarg($permissionCommand));
            $log[] = $permissionRc === 0
                ? "Permissions corrigees sur le depot distant {$remoteRepoPath}"
                : "Correction des permissions distante echouee sur {$remoteRepoPath} (code {$permissionRc})";
        }

        do {
            $log[] = "-> Tentative #{$attempt}";
            $audit('Tentative #' . $attempt . ' en cours');

        if ($isRemote) {
            $log[] = "→ Hôte distant : {$job['host_user']}@{$job['host_hostname']}:{$job['host_port']}";
            $log[] = "→ Chemins : " . implode(', ', $sourcePaths);
            $audit('Lancement restic distant (tentative #' . $attempt . ')');
            $restic = RepoManager::getRestic($repo);
            $sshKeyFile = SshKeyManager::getTemporaryKeyFile((int)$job['ssh_key_id']);
            $result = $restic->backupRemote(
                $job['host_user'], $job['host_hostname'], (int) $job['host_port'],
                $sshKeyFile, $remoteRepoPath,
                $sourcePaths, $tags, $excludes, $sudoPassword, $hostnameOverride
            );
            Restic::deleteTempSecretFile($sshKeyFile);
        } else {
            $log[] = "→ Chemins : " . implode(', ', $sourcePaths);
            $audit('Lancement restic local (tentative #' . $attempt . ')');
            $restic = RepoManager::getRestic($repo);
            $result = $restic->backup($sourcePaths, $tags, $excludes, $hostnameOverride);
        }

        $log[] = $result['output'];
        $audit('Fin tentative #' . $attempt . ' (code ' . (int) ($result['code'] ?? 1) . ')');
        if (!empty($result['success'])) {
            break;
        }

        $decision = JobRetryPolicy::shouldRetry(
            $resolvedRetryPolicy,
            (string) ($result['output'] ?? ''),
            (int) ($result['code'] ?? 1),
            $retryCount
        );
        $classification = $decision['classification'] ?? ['label' => 'Erreur non classee'];
        $log[] = "!! Classification: " . ($classification['label'] ?? 'Erreur non classee');

        if (empty($decision['retry'])) {
            $log[] = "-> Pas de retry: " . ($decision['reason'] ?? 'politique non applicable');
            break;
        }

        $retryCount++;
        $attempt++;
        $delaySeconds = max(1, (int) ($decision['delay_seconds'] ?? 1));
        $log[] = "-> Retry #{$retryCount} dans {$delaySeconds}s";
        $audit('Retry programme dans ' . $delaySeconds . 's');
        sleep($delaySeconds);
        } while (true);

        // ── Post-hook ─────────────────────────────────────────────────────────

        if ($postHook) {
            $log[] = "── Post-hook";
            $isRemote ? $execRemote($postHook) : $execLocal($postHook);
        }

        // ── Retention ─────────────────────────────────────────────────────────

        if (!empty($job['post_hook_script_id'])) {
            $log[] = "Post-hook approuve";
            $hookResult = HookScriptRunner::runJobHook($job, 'post');
            if (!empty($hookResult['output'])) {
                $log[] = $hookResult['output'];
            }
            if (empty($hookResult['success'])) {
                $log[] = "Post-hook en erreur (code " . (int) ($hookResult['code'] ?? 1) . ')';
            }
        }

        $retentionApplied = false;
        if ($result['success'] && (int)($job['retention_enabled'] ?? 0)) {
            $log[] = "── Rétention";
            $audit('Debut retention');
            $retResult = self::applyRetention($job, $repo, $isRemote, $remoteRepoPath, $execRemote);
            $log[]     = $retResult['output'];
            if (!$retResult['success']) {
                $log[] = "⚠ Rétention échouée (sauvegarde conservée)";
            } else {
                $retentionApplied = true;
            }
            $persistRunningLog();
        }

        // ── Resultat ──────────────────────────────────────────────────────────

        if ($result['success'] && (!empty($repo['snapshot_refresh_enabled']) || $retentionApplied)) {
            JobQueue::enqueueRepoSnapshotRefresh((int) $repo['id'], 'backup_success', 220);
            $log[] = "-> Indexation du depot mise en file";
        }

        if (self::isLocalFilesystemPath((string) ($repo['path'] ?? ''))) {
            $log[] = "-- Permissions depot apres backup";
            $permissionReport = RepoManager::fixGroupPermissions((string) $repo['path']);
            $log[] = self::formatPermissionReport((string) $repo['path'], $permissionReport);
        }

        if ($isRemote && !empty($job['remote_repo_path']) && self::looksLikeLocalFilesystemPath($remoteRepoPath)) {
            $log[] = "-- Permissions depot distant apres backup";
            $permissionCommand = self::buildRemotePermissionCommand($remoteRepoPath);
            $permissionRc = $execRemote('bash -lc ' . escapeshellarg($permissionCommand));
            $log[] = $permissionRc === 0
                ? "Permissions corrigees sur le depot distant {$remoteRepoPath}"
                : "Correction des permissions distante echouee sur {$remoteRepoPath} (code {$permissionRc})";
        }

        $output = implode("\n", $log);
        self::_saveResult($jobId, $result['success'], $output);
        if ($notifyNow) self::notifyResult($job, (bool) $result['success'], $output);
        return ['success' => $result['success'], 'output' => $output, 'job' => $job];
    }

    private static function isLocalFilesystemPath(string $path): bool {
        return self::looksLikeLocalFilesystemPath($path)
            && is_dir($path);
    }

    private static function looksLikeLocalFilesystemPath(string $path): bool {
        return $path !== ''
            && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)
            && !preg_match('#^[a-z0-9+.-]+:#i', $path);
    }

    private static function buildRemotePermissionCommand(string $path): string {
        $escaped = escapeshellarg($path);
        return 'find ' . $escaped . ' -type d -exec chmod 2770 {} +'
            . ' && find ' . $escaped . ' -type f -exec chmod 0660 {} +';
    }

    private static function formatPermissionReport(string $path, array $report): string {
        $lines = [];
        $changed = (int) ($report['changed'] ?? 0);
        $errors = is_array($report['errors'] ?? null) ? $report['errors'] : [];
        $sudo = is_array($report['sudo'] ?? null) ? $report['sudo'] : [];

        if ($changed > 0) {
            $lines[] = "Permissions corrigees sur {$path} ({$changed} element(s) ajustes)";
        }

        if (!empty($sudo['attempted'])) {
            if (!empty($sudo['success'])) {
                $lines[] = "Reparation permissions via sudo reussie sur {$path}";
            } else {
                $lines[] = "Reparation permissions via sudo echouee sur {$path} (code " . (int) ($sudo['code'] ?? 1) . ")";
                $sudoOutput = trim((string) ($sudo['output'] ?? ''));
                if ($sudoOutput !== '') {
                    foreach (preg_split('/\r?\n/', $sudoOutput) as $line) {
                        $line = trim($line);
                        if ($line !== '') {
                            $lines[] = "  > {$line}";
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            $lines[] = "Correction des permissions incomplete sur {$path} (" . count($errors) . " erreur(s))";
            foreach (array_slice($errors, 0, 10) as $error) {
                $mode = (string) ($error['mode'] ?? '????');
                $target = (string) ($error['path'] ?? '?');
                $message = trim((string) ($error['message'] ?? 'erreur inconnue'));
                $lines[] = "  - chmod {$mode} {$target} :: {$message}";
            }
            if (count($errors) > 10) {
                $lines[] = "  - ... " . (count($errors) - 10) . " erreur(s) supplementaire(s)";
            }
        }

        if (empty($lines)) {
            $lines[] = "Permissions deja conformes sur {$path}";
        }

        return implode("\n", $lines);
    }

    // ── Apply retention (restic forget) ────────────────────────────────

    public static function applyRetention(array $job, array $repo, bool $isRemote, string $repoPath, ?callable $execRemote = null): array {
        $keepLast    = (int) ($job['retention_keep_last']    ?? 0);
        $keepDaily   = (int) ($job['retention_keep_daily']   ?? 0);
        $keepWeekly  = (int) ($job['retention_keep_weekly']  ?? 0);
        $keepMonthly = (int) ($job['retention_keep_monthly'] ?? 0);
        $keepYearly  = (int) ($job['retention_keep_yearly']  ?? 0);
        $prune       = (bool)($job['retention_prune']        ?? true);

        // if no rule is defined, do nothing
        if (!$keepLast && !$keepDaily && !$keepWeekly && !$keepMonthly && !$keepYearly) {
            return ['success' => true, 'output' => '(aucune règle de rétention configurée)'];
        }

        if ($isRemote) {
            // Build remote forget command
            $forgetCmd = 'restic -r ' . escapeshellarg($repoPath) . ' --password-file "$_RPASS" --cache-dir /tmp/restic-cache forget';
            if ($keepLast)    $forgetCmd .= ' --keep-last '    . $keepLast;
            if ($keepDaily)   $forgetCmd .= ' --keep-daily '   . $keepDaily;
            if ($keepWeekly)  $forgetCmd .= ' --keep-weekly '  . $keepWeekly;
            if ($keepMonthly) $forgetCmd .= ' --keep-monthly ' . $keepMonthly;
            if ($keepYearly)  $forgetCmd .= ' --keep-yearly '  . $keepYearly;
            if ($prune)       $forgetCmd .= ' --prune';

            $repoPassword  = RepoManager::getPassword($repo);
            $remoteCmd     = '_RPASS=$(mktemp)'
                           . ' && printf %s ' . escapeshellarg(trim($repoPassword)) . ' > "$_RPASS"'
                           . ' && (' . $forgetCmd . ')'
                           . '; _RC=$?; rm -f "$_RPASS"; exit $_RC';

            if ($execRemote) {
                $rc = $execRemote($remoteCmd);
                return ['success' => $rc === 0, 'output' => ''];
            }
            return ['success' => false, 'output' => 'execRemote non disponible'];
        } else {
            // Local retention
            $passFile = Restic::writeTempSecretFile(RepoManager::getPassword($repo), 'rui_ret_pass_');

            $cmd = [RESTIC_BIN, '-r', $repo['path'], '--password-file', $passFile, 'forget'];
            if ($keepLast)    { $cmd[] = '--keep-last';    $cmd[] = (string) $keepLast; }
            if ($keepDaily)   { $cmd[] = '--keep-daily';   $cmd[] = (string) $keepDaily; }
            if ($keepWeekly)  { $cmd[] = '--keep-weekly';  $cmd[] = (string) $keepWeekly; }
            if ($keepMonthly) { $cmd[] = '--keep-monthly'; $cmd[] = (string) $keepMonthly; }
            if ($keepYearly)  { $cmd[] = '--keep-yearly';  $cmd[] = (string) $keepYearly; }
            if ($prune) $cmd[] = '--prune';

            $env     = ['RESTIC_CACHE_DIR' => Restic::getRuntimeCacheRootForCurrentProcess(), 'HOME' => '/var/www',
                        'RCLONE_CONFIG' => '/var/www/.config/rclone/rclone.conf',
                        'XDG_CACHE_HOME' => '/tmp',
                        'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'];
            $processResult = ProcessRunner::run($cmd, ['env' => $env]);
            Restic::deleteTempSecretFile($passFile);
            return [
                'success' => (int) ($processResult['code'] ?? 1) === 0,
                'output' => (string) ($processResult['output'] ?? ''),
            ];
        }
    }

    // ── Helpers prives ────────────────────────────────────────────────────────

    public static function notifyResult(array $job, bool $success, string $output): void {
        $policy = Notifier::getEntityPolicy('backup_job', $job);
        $event = $success ? 'success' : 'failure';
        $title = ($success ? 'Backup reussi - ' : 'Backup echoue - ') . ($job['name'] ?? 'job');

        // Feature 2: Create a sober body and pass full log content separately
        $jobName = (string) ($job['name'] ?? 'backup_job');
        $jobId = isset($job['id']) ? (int) $job['id'] : 0;
        $body = "**Statut** : " . ($success ? 'Succes' : 'Echec') . "\n"
               . "**Job** : $jobName\n"
               . "**Date** : " . formatCurrentDisplayDate() . "\n"
               . "[Voir les details dans l interface](". routePath('/backup_jobs.php', ['id' => $jobId]) . ")";

        Notifier::dispatchPolicy('backup_job', $policy, $event, $title, $body, [
            'context_type' => 'backup_job',
            'context_id' => $jobId ?: null,
            'context_name' => $jobName,
            'ntfy_priority' => $success ? 'default' : 'high',
            'log_content' => $output,
        ]);
    }

    private static function _saveResult(int $jobId, bool $success, string $output): void {
        $db = Database::getInstance();
        $db->prepare("
            UPDATE backup_jobs
            SET last_run = datetime('now'), last_status = ?, last_output = ?
            WHERE id = ?
        ")->execute([$success ? 'success' : 'failed', $output, $jobId]);

        $db->prepare("
            INSERT INTO cron_log (job_type, job_id, status, output) VALUES ('backup', ?, ?, ?)
        ")->execute([$jobId, $success ? 'success' : 'failed', $output]);
    }

    // ── Planification ─────────────────────────────────────────────────────────

    public static function getDueJobs(): array {
        $db  = Database::getInstance();
        $runtimeNow = new DateTimeImmutable('now', appServerTimezone());
        $scheduleNow = $runtimeNow->setTimezone(new DateTimeZone(SchedulerManager::getScheduleTimezoneName()));
        $day = (int) $scheduleNow->format('N');
        $h   = (int) $scheduleNow->format('G');

        $jobs = $db->query("SELECT * FROM backup_jobs WHERE schedule_enabled = 1")->fetchAll();
        $due  = [];

        foreach ($jobs as $job) {
            $days = explode(',', $job['schedule_days'] ?? '1');
            if (!in_array((string) $day, $days)) continue;
            if ((int) $job['schedule_hour'] !== $h) continue;

            if ($job['last_run']) {
                $lastRun = parseAppDate((string) $job['last_run']);
                if ($lastRun) {
                    $lastRunSchedule = $lastRun->setTimezone(new DateTimeZone(SchedulerManager::getScheduleTimezoneName()));

                    // Same hourly bucket -> already executed (or running) this hour
                    if ($lastRunSchedule->format('Y-m-d H') === $scheduleNow->format('Y-m-d H')) {
                        // Exception: if status is 'running' and the job is stuck
                        // for more than 4h, consider it crashed and allow
                        // a new trigger (otherwise it stays blocked until next day)
                        $isStaleRunning = ($job['last_status'] ?? '') === 'running'
                            && (time() - $lastRun->getTimestamp()) > 4 * 3600;

                        if (!$isStaleRunning) {
                            continue;
                        }
                    }
                }
            }

            $due[] = $job;
        }

        return $due;
    }
}
