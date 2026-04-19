<?php
// =============================================================================
// CopyJobManager.php — management of jobs of copie restic
// =============================================================================

class CopyJobManager {

    public static function getAll(?array $repoIds = null): array {
        $db = Database::getInstance();
        $sql = "
            SELECT c.*, r.name as source_name, r.path as source_path
            FROM copy_jobs c
            LEFT JOIN repos r ON r.id = c.source_repo_id
        ";

        $params = [];
        if ($repoIds !== null) {
            $sql .= ' WHERE ' . self::inClause('c.source_repo_id', $repoIds, $params);
        }

        $sql .= ' ORDER BY c.name';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, r.name as source_name, r.path as source_path
            FROM copy_jobs c
            LEFT JOIN repos r ON r.id = c.source_repo_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function add(
        string $name,
        int    $sourceRepoId,
        string $destPath,
        string $destPassword,
        string $description     = '',
        int    $scheduleEnabled = 0,
        int    $scheduleHour    = 2,
        string $scheduleDays    = '1',
        string $destPasswordSource = '',
        string $destInfisicalSecretName = '',
        ?string $notificationPolicy = null,
        ?string $retryPolicy = null
    ): int {
        $destPasswordSource = self::normalizePasswordSource($destPasswordSource);
        $db = Database::getInstance();
        $startedTransaction = !$db->inTransaction();
        if ($startedTransaction) {
            $db->beginTransaction();
        }
        try {
            $db->prepare("
                INSERT INTO copy_jobs
                (name, source_repo_id, dest_path, dest_password_file, dest_password_ref, dest_password_source, dest_infisical_secret_name, description, schedule_enabled, schedule_hour, schedule_days, notification_policy, retry_policy)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $name,
                $sourceRepoId,
                $destPath,
                null,
                null,
                $destPasswordSource,
                $destInfisicalSecretName ?: null,
                $description,
                $scheduleEnabled,
                $scheduleHour,
                $scheduleDays,
                $notificationPolicy,
                $retryPolicy,
            ]);
            $id = (int) $db->lastInsertId();
            if (in_array($destPasswordSource, ['agent', 'local'], true)) {
                $ref = SecretStore::writableRef('copy-job', $id, 'dest-password', $destPasswordSource);
                SecretStore::put($ref, $destPassword, ['entity' => 'copy_job', 'id' => $id, 'name' => $name]);
                $db->prepare("UPDATE copy_jobs SET dest_password_ref = ? WHERE id = ?")->execute([$ref, $id]);
            } elseif ($destPasswordSource === 'infisical' && $destInfisicalSecretName !== '') {
                $db->prepare("UPDATE copy_jobs SET dest_password_ref = ? WHERE id = ?")->execute([SecretStore::infisicalRef($destInfisicalSecretName), $id]);
            }
            if ($startedTransaction) {
                $db->commit();
            }
            return $id;
        } catch (Throwable $e) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function update(int $id, array $data): void {
        $db  = Database::getInstance();
        $job = self::getById($id);
        if (!$job) return;

        $fields = [];
        $values = [];
        if (isset($data['dest_password_source'])) {
            $data['dest_password_source'] = self::normalizePasswordSource((string) $data['dest_password_source']);
        }

        foreach (['name', 'source_repo_id', 'dest_path', 'description', 'schedule_enabled', 'schedule_hour', 'schedule_days', 'notification_policy', 'retry_policy', 'dest_password_source', 'dest_infisical_secret_name'] as $f) {
            if (isset($data[$f])) { $fields[] = "$f = ?"; $values[] = $data[$f]; }
        }

        $nextPasswordSource = self::normalizePasswordSource((string) ($data['dest_password_source'] ?? $job['dest_password_source'] ?? 'agent'));

        if ($nextPasswordSource === 'infisical') {
            if (!empty($job['dest_password_ref']) && SecretStore::isSecretRef($job['dest_password_ref'])) {
                SecretStore::delete($job['dest_password_ref']);
            }
            if (!empty($job['dest_password_file']) && file_exists($job['dest_password_file'])) {
                FileSystem::deleteFile((string) $job['dest_password_file']);
            }
            $fields[] = "dest_password_file = ?";
            $values[] = null;
            $fields[] = "dest_password_ref = ?";
            $values[] = !empty($data['dest_infisical_secret_name']) ? SecretStore::infisicalRef((string) $data['dest_infisical_secret_name']) : null;
        }

        if (in_array($nextPasswordSource, ['agent', 'local'], true) && !empty($data['dest_password'])) {
            $currentProvider = !empty($job['dest_password_ref']) && SecretStore::isSecretRef($job['dest_password_ref'])
                ? SecretStore::providerNameForRef((string) $job['dest_password_ref'])
                : '';
            $ref = $currentProvider === $nextPasswordSource
                ? (string) $job['dest_password_ref']
                : SecretStore::writableRef('copy-job', $id, 'dest-password', $nextPasswordSource);
            SecretStore::put($ref, (string) $data['dest_password'], ['entity' => 'copy_job', 'id' => $id, 'name' => $data['name'] ?? $job['name']]);
            if (!empty($job['dest_password_file']) && file_exists($job['dest_password_file'])) {
                FileSystem::deleteFile((string) $job['dest_password_file']);
            }
            $fields[] = "dest_password_file = ?";
            $values[] = null;
            $fields[] = "dest_password_ref = ?";
            $values[] = $ref;
        }

        if (empty($fields)) return;
        $values[] = $id;
        $db->prepare("UPDATE copy_jobs SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
    }

    public static function delete(int $id): void {
        $db  = Database::getInstance();
        $job = self::getById($id);
        if ($job) {
            if (!empty($job['dest_password_ref']) && SecretStore::isSecretRef($job['dest_password_ref'])) {
                SecretStore::delete($job['dest_password_ref']);
            }
            if (!empty($job['dest_password_file']) && file_exists($job['dest_password_file'])) {
                FileSystem::deleteFile((string) $job['dest_password_file']);
            }
        }
        $db->prepare("DELETE FROM copy_jobs WHERE id = ?")->execute([$id]);
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

    public static function getDestPassword(array $job): string {
        $source = self::normalizePasswordSource((string) ($job['dest_password_source'] ?? 'agent'));
        if ($source === 'infisical') {
            if (!empty($job['dest_password_ref']) && SecretStore::isSecretRef($job['dest_password_ref'])) {
                return SecretStore::get($job['dest_password_ref']) ?? self::missingPassword($job, 'secret Infisical introuvable');
            }
            $secret = InfisicalClient::getSecret($job['dest_infisical_secret_name'] ?? '');
            return $secret ?? self::missingPassword($job, 'secret Infisical introuvable');
        }
        if (!empty($job['dest_password_ref']) && SecretStore::isSecretRef($job['dest_password_ref'])) {
            return SecretStore::get($job['dest_password_ref'], 'copy', ['copy_job_id' => (int) ($job['id'] ?? 0)]) ?? self::missingPassword($job, 'reference de secret introuvable');
        }
        if (!empty($job['dest_password_file']) && file_exists($job['dest_password_file'])) {
            return self::migrateLegacyPassword($job);
        }
        return self::missingPassword($job, 'aucune reference de secret configuree');
    }

    public static function notifyResult(array $job, bool $success, string $output): void {
        $policy = Notifier::getEntityPolicy('copy_job', $job);
        $event = $success ? 'success' : 'failure';
        $title = ($success ? 'Copie reussie - ' : 'Copie echouee - ') . ($job['name'] ?? 'job');

        // Feature 2: Create a sober body and pass full log content separately
        $jobName = (string) ($job['name'] ?? 'copy_job');
        $jobId = isset($job['id']) ? (int) $job['id'] : 0;
        $body = "**Statut** : " . ($success ? 'Succes' : 'Echec') . "\n"
               . "**Job** : $jobName\n"
               . "**Date** : " . formatCurrentDisplayDate() . "\n"
               . "[Voir les details dans l interface](". routePath('/copy_jobs.php', ['id' => $jobId]) . ")";

        Notifier::dispatchPolicy('copy_job', $policy, $event, $title, $body, [
            'context_type' => 'copy_job',
            'context_id' => $jobId ?: null,
            'context_name' => $jobName,
            'ntfy_priority' => $success ? 'default' : 'high',
            'log_content' => $output,
        ]);
    }

    // ── Executer a job of copie ──────────────────────────────────────────────
    public static function markRunning(int $jobId): void {
        Database::getInstance()->prepare(
            "UPDATE copy_jobs SET last_run = datetime('now'), last_status = 'running', last_output = '' WHERE id = ?"
        )->execute([$jobId]);
    }

    public static function run(int $jobId, ?string $snapshotId = null, bool $notifyNow = true): array {
        $job = self::getById($jobId);
        if (!$job) return ['success' => false, 'output' => 'Job introuvable', 'job' => null];

        $sourceRepo = RepoManager::getById($job['source_repo_id']);
        if (!$sourceRepo) return ['success' => false, 'output' => 'Dépôt source introuvable', 'job' => $job];

        self::markRunning($jobId);

        $restic      = RepoManager::getRestic($sourceRepo);
        $destPassword = self::getDestPassword($job);

        $log    = [];
        $log[]  = "→ Copie de {$job['source_name']} vers {$job['dest_path']}";
        if ($snapshotId) $log[] = "→ Snapshot spécifique: $snapshotId";
        else             $log[] = "→ Tous les snapshots";

        $preflight = DiskSpaceMonitor::preflightCopyJob($job, $sourceRepo, $snapshotId);
        if (empty($preflight['allowed'])) {
            $output = implode("\n", array_merge($log, ['[PRECHECK] ' . (string) ($preflight['message'] ?? 'Espace disque insufficient')]));
            $db = Database::getInstance();
            $db->prepare("
                UPDATE copy_jobs SET last_run = datetime('now'), last_status = ?, last_output = ? WHERE id = ?
            ")->execute(['failed', $output, $jobId]);
            $db->prepare("
                INSERT INTO cron_log (job_type, job_id, status, output) VALUES ('copy', ?, ?, ?)
            ")->execute([$jobId, 'failed', $output]);
            if ($notifyNow) self::notifyResult($job, false, $output);
            return ['success' => false, 'output' => $output, 'job' => $job];
        }
        if (!empty($preflight['supported'])) {
            $log[] = '[PRECHECK] ' . (string) ($preflight['message'] ?? 'Verification disque OK');
        }

        $resolvedRetryPolicy = JobRetryPolicy::resolvePolicy(JobRetryPolicy::getEntityPolicy($job));
        $retryCount = 0;
        $attempt = 1;
        $result = ['success' => false, 'output' => ''];

        while (true) {
            $log[] = "→ Tentative #{$attempt}";
            $result = $restic->copyTo($job['dest_path'], $destPassword, $snapshotId);
            $log[] = trim((string) ($result['output'] ?? ''));

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
                $log[] = "→ Pas de retry: " . ($decision['reason'] ?? 'politique non applicable');
                break;
            }

            $retryCount++;
            $attempt++;
            $delaySeconds = max(1, (int) ($decision['delay_seconds'] ?? 1));
            $log[] = "-> Retry #{$retryCount} dans {$delaySeconds}s";
            sleep($delaySeconds);
        }

        $output = implode("\n", $log);

        // Update job status
        $db = Database::getInstance();
        $db->prepare("
            UPDATE copy_jobs SET last_run = datetime('now'), last_status = ?, last_output = ? WHERE id = ?
        ")->execute([$result['success'] ? 'success' : 'failed', $output, $jobId]);

        // Log cron
        $db->prepare("
            INSERT INTO cron_log (job_type, job_id, status, output) VALUES ('copy', ?, ?, ?)
        ")->execute([$jobId, $result['success'] ? 'success' : 'failed', $output]);

        if ($notifyNow) self::notifyResult($job, (bool) $result['success'], $output);

        return ['success' => $result['success'], 'output' => $output, 'job' => $job];
    }

    // ── Check quels jobs must tourner maintenant ────────────────────────
    public static function getDueJobs(): array {
        $db  = Database::getInstance();
        $runtimeNow = new DateTimeImmutable('now', appServerTimezone());
        $scheduleNow = $runtimeNow->setTimezone(new DateTimeZone(SchedulerManager::getScheduleTimezoneName()));
        $day = (int) $scheduleNow->format('N'); // 1=Lundi... 7=Dimanche
        $h   = (int) $scheduleNow->format('G');

        $jobs = $db->query("SELECT * FROM copy_jobs WHERE schedule_enabled = 1")->fetchAll();
        $due  = [];

        foreach ($jobs as $job) {
            $days = explode(',', $job['schedule_days'] ?? '1');
            if (!in_array((string) $day, $days, true)) continue;
            if ((int) $job['schedule_hour'] !== $h) continue;

            // Check that it has not already run (or is running) during this time slot
            if ($job['last_run']) {
                $lastRun = parseAppDate((string) $job['last_run']);
                if ($lastRun) {
                    $lastRunSchedule = $lastRun->setTimezone(new DateTimeZone(SchedulerManager::getScheduleTimezoneName()));
                    if ($lastRunSchedule->format('Y-m-d H') === $scheduleNow->format('Y-m-d H')) {
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

    private static function migrateLegacyPassword(array $job): string {
        $path = (string) ($job['dest_password_file'] ?? '');
        if ($path === '' || !is_file($path)) {
            return '';
        }
        $password = trim((string) file_get_contents($path));
        $id = (int) ($job['id'] ?? 0);
        if ($id > 0 && $password !== '') {
            $ref = SecretStore::writableRef('copy-job', $id, 'dest-password');
            SecretStore::put($ref, $password, ['entity' => 'copy_job', 'id' => $id, 'legacy_file' => $path]);
            Database::getInstance()->prepare("UPDATE copy_jobs SET dest_password_ref = ?, dest_password_source = ? WHERE id = ?")->execute([$ref, SecretStore::providerNameForRef($ref), $id]);
            FileSystem::deleteFile($path);
        }
        return $password;
    }

    private static function normalizePasswordSource(string $source): string {
        if (trim($source) === '') {
            return SecretStore::defaultWritableSource();
        }
        return match ($source) {
            'infisical' => 'infisical',
            'local' => 'local',
            'file' => 'local',
            default => 'agent',
        };
    }

    private static function missingPassword(array $job, string $reason): string {
        $name = (string) ($job['name'] ?? ('#' . (string) ($job['id'] ?? '?')));
        throw new RuntimeException("Mot de passe destination du job {$name} indisponible: {$reason}.");
    }
}
