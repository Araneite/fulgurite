<?php

class JobQueue {
    private const STATUS_QUEUED = 'queued';
    private const STATUS_RUNNING = 'running';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_FAILED = 'failed';
    private const STATUS_DEAD_LETTER = 'dead_letter';
    private const MAX_ATTEMPTS = 5;

    private static function utcDateTime(int $delaySeconds = 0): string {
        $date = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if ($delaySeconds > 0) {
            $date = $date->modify('+' . $delaySeconds . ' seconds');
        }

        return $date->format('Y-m-d H:i:s');
    }

    public static function enqueue(string $type, array $payload, int $priority = 100, ?string $uniqueKey = null, int $delaySeconds = 0): int {
        $db = Database::getInstance();
        $priority = max(1, min(1000, $priority));

        if ($uniqueKey !== null && $uniqueKey !== '') {
            $existing = $db->prepare("
                SELECT id
                FROM job_queue
                WHERE type = ?
                  AND unique_key = ?
                  AND status IN (?, ?)
                ORDER BY id DESC
                LIMIT 1
            ");
            $existing->execute([$type, $uniqueKey, self::STATUS_QUEUED, self::STATUS_RUNNING]);
            $existingId = $existing->fetchColumn();
            if ($existingId) {
                return (int) $existingId;
            }
        }

        $availableAt = self::utcDateTime(max(0, $delaySeconds));
        $stmt = $db->prepare("
            INSERT INTO job_queue (type, unique_key, payload_json, status, priority, attempts, available_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 0, ?, datetime('now'), datetime('now'))
        ");
        $stmt->execute([
            $type,
            $uniqueKey,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            self::STATUS_QUEUED,
            $priority,
            $availableAt,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function enqueueRepoSnapshotRefresh(int $repoId, string $reason = 'manual', int $priority = 150, int $delaySeconds = 0): int {
        return self::enqueue(
            'repo_snapshot_refresh',
            ['repo_id' => $repoId, 'reason' => $reason],
            $priority,
            'repo_snapshot_refresh:' . $repoId,
            $delaySeconds
        );
    }

    public static function enqueueSnapshotFullIndex(int $repoId, string $snapshotId, string $reason = 'manual', int $priority = 200, int $delaySeconds = 0): int {
        return self::enqueue(
            'snapshot_full_index',
            ['repo_id' => $repoId, 'snapshot_id' => $snapshotId, 'reason' => $reason],
            $priority,
            'snapshot_full_index:' . $repoId . ':' . $snapshotId,
            $delaySeconds
        );
    }

    public static function processDueJobs(int $limit = 3, array $types = []): array {
        $results = [];
        for ($i = 0; $i < $limit; $i++) {
            $job = self::claimNextJob($types);
            if ($job === null) {
                break;
            }

            try {
                $message = self::handleJob($job);
                self::completeJob((int) $job['id'], $message);
                $results[] = ['id' => (int) $job['id'], 'type' => $job['type'], 'status' => 'success', 'message' => $message];
            } catch (Throwable $e) {
                $error = trim($e->getMessage()) ?: 'Erreur inconnue';
                $failure = self::failJob((int) $job['id'], (int) ($job['priority'] ?? 100), $error);
                $results[] = [
                    'id' => (int) $job['id'],
                    'type' => $job['type'],
                    'status' => $failure['status'],
                    'message' => $error,
                    'retry_at' => $failure['retry_at'] ?? null,
                ];
            }
        }

        return $results;
    }

    public static function secondsUntilNextDueJob(array $types = []): ?int {
        $db = Database::getInstance();
        $sql = "
            SELECT MIN(available_at)
            FROM job_queue
            WHERE status = ?
        ";
        $params = [self::STATUS_QUEUED];

        if (!empty($types)) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $sql .= " AND type IN ($placeholders)";
            $params = array_merge($params, array_values($types));
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $nextAt = $stmt->fetchColumn();
        if ($nextAt === false || $nextAt === null || trim((string) $nextAt) === '') {
            return null;
        }

        $nextUtc = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            (string) $nextAt,
            new DateTimeZone('UTC')
        );
        if (!$nextUtc) {
            $fallbackTs = strtotime((string) $nextAt);
            if ($fallbackTs === false) {
                return null;
            }
            $delta = $fallbackTs - time();
            return $delta > 0 ? $delta : 0;
        }

        $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $delta = $nextUtc->getTimestamp() - $nowUtc->getTimestamp();
        return $delta > 0 ? $delta : 0;
    }

    public static function getSummary(): array {
        $db = Database::getInstance();
        $counts = [
            self::STATUS_QUEUED => 0,
            self::STATUS_RUNNING => 0,
            self::STATUS_COMPLETED => 0,
            self::STATUS_FAILED => 0,
            self::STATUS_DEAD_LETTER => 0,
        ];

        foreach ($db->query("
            SELECT status, COUNT(*) AS total
            FROM job_queue
            GROUP BY status
        ")->fetchAll() as $row) {
            $status = (string) ($row['status'] ?? '');
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) ($row['total'] ?? 0);
            }
        }

        $oldestQueued = $db->query("
            SELECT MIN(available_at)
            FROM job_queue
            WHERE status = 'queued'
        ")->fetchColumn() ?: null;
        $oldestRunning = $db->query("
            SELECT MIN(started_at)
            FROM job_queue
            WHERE status = 'running'
        ")->fetchColumn() ?: null;
        $nextAvailable = $db->query("
            SELECT MIN(available_at)
            FROM job_queue
            WHERE status = 'queued'
        ")->fetchColumn() ?: null;
        $delayedCount = (int) $db->query("
            SELECT COUNT(*)
            FROM job_queue
            WHERE status = 'queued'
              AND available_at > datetime('now')
        ")->fetchColumn();
        $highestPriorityQueued = $db->query("
            SELECT MAX(priority)
            FROM job_queue
            WHERE status = 'queued'
        ")->fetchColumn();

        return [
            'counts' => $counts,
            'oldest_queued_at' => $oldestQueued,
            'oldest_running_at' => $oldestRunning,
            'next_available_at' => $nextAvailable,
            'delayed_count' => $delayedCount,
            'highest_priority_queued' => $highestPriorityQueued !== false && $highestPriorityQueued !== null ? (int) $highestPriorityQueued : null,
        ];
    }

    public static function getRecentJobs(int $limit = 25): array {
        $stmt = Database::getInstance()->prepare("
            SELECT id, type, unique_key, status, priority, attempts, available_at, started_at, finished_at, last_error, created_at, updated_at
            FROM job_queue
            ORDER BY id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function markWorkerHeartbeat(string $workerName = 'default', array $meta = []): void {
        $payload = array_merge([
            'at' => date(DATE_ATOM),
            'pid' => getmypid() ?: null,
            'sapi' => PHP_SAPI,
        ], $meta);
        Database::setSetting(
            'worker_heartbeat_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $workerName),
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function getWorkerHeartbeat(string $workerName = 'default'): ?array {
        $raw = Database::getSetting('worker_heartbeat_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $workerName), '');
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function recoverStaleRunningJobs(int $olderThanMinutes = 30): int {
        $stmt = Database::getInstance()->prepare("
            UPDATE job_queue
            SET status = ?,
                available_at = datetime('now'),
                last_error = CASE
                    WHEN last_error IS NULL OR last_error = '' THEN ?
                    ELSE last_error || char(10) || ?
                END,
                updated_at = datetime('now')
            WHERE status = ?
              AND started_at < datetime('now', '-' || ? || ' minutes')
        ");
        $message = 'Job requeue apres detection worker stale';
        $stmt->execute([self::STATUS_QUEUED, $message, $message, self::STATUS_RUNNING, max(1, $olderThanMinutes)]);
        return $stmt->rowCount();
    }

    private static function claimNextJob(array $types = []): ?array {
        $db = Database::getInstance();

        $sql = "
            SELECT *
            FROM job_queue
            WHERE status = ?
              AND available_at <= datetime('now')
        ";
        $params = [self::STATUS_QUEUED];

        if (!empty($types)) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $sql .= " AND type IN ($placeholders)";
            $params = array_merge($params, array_values($types));
        }

        $sql .= " ORDER BY priority DESC, attempts ASC, available_at ASC, id ASC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $job = $stmt->fetch();
        if (!$job) {
            return null;
        }

        $claim = $db->prepare("
            UPDATE job_queue
            SET status = ?, claimed_at = datetime('now'), started_at = datetime('now'), attempts = attempts + 1, updated_at = datetime('now')
            WHERE id = ? AND status = ?
        ");
        $claim->execute([self::STATUS_RUNNING, $job['id'], self::STATUS_QUEUED]);
        if ($claim->rowCount() !== 1) {
            return null;
        }

        $job['attempts'] = (int) ($job['attempts'] ?? 0) + 1;
        return $job;
    }

    private static function completeJob(int $jobId, string $message): void {
        Database::getInstance()->prepare("
            UPDATE job_queue
            SET status = ?, last_error = NULL, finished_at = datetime('now'), updated_at = datetime('now')
            WHERE id = ?
        ")->execute([self::STATUS_COMPLETED, $jobId]);
    }

    private static function failJob(int $jobId, int $priority, string $error): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT attempts FROM job_queue WHERE id = ?");
        $stmt->execute([$jobId]);
        $attempts = (int) ($stmt->fetchColumn() ?: 0);

        if ($attempts < self::MAX_ATTEMPTS) {
            $retryDelaySeconds = self::calculateRetryDelaySeconds($attempts, $priority);
            $retryAt = self::utcDateTime($retryDelaySeconds);
            $db->prepare("
                UPDATE job_queue
                SET status = ?, available_at = ?, last_error = ?, updated_at = datetime('now')
                WHERE id = ?
            ")->execute([self::STATUS_QUEUED, $retryAt, self::buildRetryErrorMessage($error, $attempts, $retryDelaySeconds), $jobId]);
            return [
                'status' => self::STATUS_QUEUED,
                'retry_at' => $retryAt,
            ];
        }

        $db->prepare("
            UPDATE job_queue
            SET status = ?, finished_at = datetime('now'), last_error = ?, updated_at = datetime('now')
            WHERE id = ?
        ")->execute([self::STATUS_DEAD_LETTER, self::buildDeadLetterMessage($error, $attempts), $jobId]);

        return [
            'status' => self::STATUS_DEAD_LETTER,
            'retry_at' => null,
        ];
    }

    private static function handleJob(array $job): string {
        $payload = json_decode((string) ($job['payload_json'] ?? '{}'), true);
        $payload = is_array($payload) ? $payload : [];

        return match ($job['type']) {
            'repo_snapshot_refresh' => self::handleRepoSnapshotRefresh($payload),
            'snapshot_full_index' => self::handleSnapshotFullIndex($payload),
            default => throw new RuntimeException("Type de job inconnu: {$job['type']}"),
        };
    }

    private static function handleRepoSnapshotRefresh(array $payload): string {
        $repoId = (int) ($payload['repo_id'] ?? 0);
        if ($repoId <= 0) {
            throw new RuntimeException('repo_id manquant');
        }

        $repo = RepoManager::getById($repoId);
        if (!$repo) {
            throw new RuntimeException("Depot introuvable (#$repoId)");
        }

        $restic = RepoManager::getRestic($repo);
        // A refresh must always reload the repository current state.
        $restic->clearCache('snapshots');
        $snapshots = $restic->snapshots();
        if (isset($snapshots['error'])) {
            $repairMessage = self::attemptRepoPermissionRepair($repo, (string) $snapshots['error']);
            if ($repairMessage !== null) {
                $restic->clearCache('snapshots');
                $snapshots = $restic->snapshots();
                if (isset($snapshots['error'])) {
                    throw new RuntimeException($repairMessage . "\n" . (string) $snapshots['error']);
                }
            } else {
                throw new RuntimeException((string) $snapshots['error']);
            }
        }

        RepoSnapshotCatalog::sync($repoId, $snapshots);
        SnapshotSearchIndex::syncRepoSnapshots($repoId, $restic, $snapshots);
        RepoStatusService::upsertStatuses([self::buildRepoRuntimeStatus($repo, $snapshots)]);
        RepoStatusService::upsertStatsHistorySample($repoId, count($snapshots));
        self::clearExploreCache();

        // Record the last refresh date to drive configured intervals.
        Database::getInstance()->prepare(
            "UPDATE repos SET last_snapshot_refreshed_at = datetime('now') WHERE id = ?"
        )->execute([$repoId]);

        return sprintf('Catalogue et index sync pour %s (%d snapshots)', $repo['name'], count($snapshots));
    }

    private static function attemptRepoPermissionRepair(array $repo, string $error): ?string {
        $repoPath = (string) ($repo['path'] ?? '');
        if ($repoPath === '' || !str_starts_with($repoPath, '/')) {
            return null;
        }

        if (!self::looksLikePermissionError($error)) {
            return null;
        }

        $report = RepoManager::fixGroupPermissions($repoPath);
        if (!empty($report['errors'])) {
            return sprintf(
                "Erreur de permissions detectee pendant le refresh. Tentative de reparation echec sur %s.",
                $repoPath
            );
        }

        return sprintf(
            "Erreur de permissions detectee pendant le refresh. Reparation automatique appliquee sur %s, nouvelle tentative en cours.",
            $repoPath
        );
    }

    private static function looksLikePermissionError(string $error): bool {
        return str_contains(strtolower($error), 'permission denied');
    }

    private static function handleSnapshotFullIndex(array $payload): string {
        $repoId = (int) ($payload['repo_id'] ?? 0);
        $snapshotId = (string) ($payload['snapshot_id'] ?? '');
        if ($repoId <= 0 || $snapshotId === '') {
            throw new RuntimeException('repo_id ou snapshot_id manquant');
        }

        $repo = RepoManager::getById($repoId);
        if (!$repo) {
            throw new RuntimeException("Depot introuvable (#$repoId)");
        }

        $restic = RepoManager::getRestic($repo);
        if (!SnapshotSearchIndex::forceFullIndex($repoId, $snapshotId, $restic)) {
            throw new RuntimeException("Impossible d'indexer completement le snapshot $snapshotId");
        }
        self::clearExploreCache();

        return sprintf('Index complet regenere pour %s:%s', $repo['name'], $snapshotId);
    }

    private static function buildRepoRuntimeStatus(array $repo, array $snapshots): array {
        $count = count($snapshots);
        $lastSnapshot = $count > 0 ? end($snapshots) : null;
        $alertHours = (int) ($repo['alert_hours'] ?? AppConfig::backupAlertHours());
        $hoursAgo = null;
        $status = 'ok';

        if ($count === 0) {
            $status = 'no_snap';
        } elseif ($lastSnapshot && !empty($lastSnapshot['time'])) {
            try {
                $diff = (new DateTime())->getTimestamp() - (new DateTime($lastSnapshot['time']))->getTimestamp();
                $hoursAgo = round($diff / 3600, 1);
                if ($hoursAgo > $alertHours) {
                    $status = 'warning';
                }
            } catch (Exception $e) {
                $hoursAgo = null;
            }
        }

        return [
            'id' => (int) $repo['id'],
            'name' => $repo['name'],
            'count' => $count,
            'last_time' => $lastSnapshot['time'] ?? null,
            'hours_ago' => $hoursAgo,
            'status' => $status,
            'repo' => $repo,
        ];
    }

    private static function clearExploreCache(): void {
        $cacheDir = dirname(DB_PATH) . '/cache/explore';
        foreach (glob($cacheDir . '/*.json') ?: [] as $path) {
            FileSystem::deleteFile($path);
        }
    }

    private static function calculateRetryDelaySeconds(int $attempts, int $priority): int {
        $baseDelay = 120;
        if ($priority >= 200) {
            $baseDelay = 30;
        } elseif ($priority >= 150) {
            $baseDelay = 60;
        }

        $multiplier = 2 ** max(0, $attempts - 1);
        return min(21600, $baseDelay * $multiplier);
    }

    private static function buildRetryErrorMessage(string $error, int $attempts, int $retryDelaySeconds): string {
        return sprintf(
            '[tentative %d/%d, retry dans %ds] %s',
            $attempts,
            self::MAX_ATTEMPTS,
            $retryDelaySeconds,
            $error
        );
    }

    private static function buildDeadLetterMessage(string $error, int $attempts): string {
        return sprintf(
            '[dead-letter apres %d tentatives] %s',
            $attempts,
            $error
        );
    }
}
