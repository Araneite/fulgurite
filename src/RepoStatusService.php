<?php

class RepoStatusService {
    private const STATUS_STALE_AFTER_SECONDS = 300;

    public static function getStatuses(bool $allowRefresh = true): array {
        $repos = RepoManager::getAll();
        $rawStatuses = self::getRawCachedStatuses($repos);
        if (empty($rawStatuses) && $allowRefresh) {
            return self::refreshAll();
        }

        if ($allowRefresh && self::isCacheStale($rawStatuses)) {
            return self::refreshAll();
        }

        return self::hydrateStatuses($repos, $rawStatuses);
    }

    public static function refreshAll(): array {
        $repos = RepoManager::getAll();
        $statuses = [];

        foreach ($repos as $repo) {
            $restic     = RepoManager::getRestic($repo);
            $snaps      = $restic->snapshots();
            $ok         = is_array($snaps) && !isset($snaps['error']);
            $count      = $ok ? count($snaps) : 0;
            $alertHours = (int) ($repo['alert_hours'] ?? AppConfig::backupAlertHours());
            $lastSnap   = ($ok && $count > 0) ? end($snaps) : null;
            $hoursAgo   = null;
            $status     = 'ok';

            if (!$ok) {
                $status = 'error';
            } elseif ($count === 0) {
                $status = 'no_snap';
            } elseif ($lastSnap) {
                try {
                    $diff     = (new DateTime())->getTimestamp() - (new DateTime($lastSnap['time']))->getTimestamp();
                    $hoursAgo = round($diff / 3600, 1);
                    if ($hoursAgo > $alertHours) {
                        $status = 'warning';
                    }
                } catch (Exception $e) {
                    $hoursAgo = null;
                }
            }

            $statuses[] = [
                'id'        => (int) $repo['id'],
                'name'      => $repo['name'],
                'count'     => $count,
                'last_time' => $lastSnap['time'] ?? null,
                'hours_ago' => $hoursAgo,
                'status'    => $status,
                'repo'      => $repo,
            ];
        }

        self::upsertStatuses($statuses);
        return $statuses;
    }

    public static function upsertStatuses(array $statuses): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO repo_runtime_status (repo_id, repo_name, snapshot_count, last_snapshot_time, hours_ago, status, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
            ON CONFLICT(repo_id) DO UPDATE SET
                repo_name = excluded.repo_name,
                snapshot_count = excluded.snapshot_count,
                last_snapshot_time = excluded.last_snapshot_time,
                hours_ago = excluded.hours_ago,
                status = excluded.status,
                updated_at = excluded.updated_at
        ");

        foreach ($statuses as $status) {
            $stmt->execute([
                $status['id'],
                $status['name'],
                $status['count'],
                $status['last_time'],
                $status['hours_ago'],
                $status['status'],
            ]);
        }
    }

    public static function upsertStatsHistorySample(int $repoId, int $snapshotCount, ?array $stats = null, ?DateTimeImmutable $bucketAt = null): void {
        $db = Database::getInstance();
        $bucketAt ??= new DateTimeImmutable('now', appServerTimezone());
        $bucketStart = $bucketAt->format('Y-m-d H:00:00');
        $bucketEnd = $bucketAt->modify('+1 hour')->format('Y-m-d H:00:00');

        $totalSize = isset($stats['total_size']) ? (int) $stats['total_size'] : null;
        $totalFileCount = isset($stats['total_file_count']) ? (int) $stats['total_file_count'] : null;

        $existingStmt = $db->prepare("
            SELECT id, total_size, total_file_count
            FROM repo_stats_history
            WHERE repo_id = ?
              AND recorded_at >= ?
              AND recorded_at < ?
            ORDER BY recorded_at DESC, id DESC
            LIMIT 1
        ");
        $existingStmt->execute([$repoId, $bucketStart, $bucketEnd]);
        $existing = $existingStmt->fetch();

        if (!$existing && ($totalSize === null || $totalFileCount === null)) {
            $lastKnownStmt = $db->prepare("
                SELECT total_size, total_file_count
                FROM repo_stats_history
                WHERE repo_id = ?
                ORDER BY recorded_at DESC, id DESC
                LIMIT 1
            ");
            $lastKnownStmt->execute([$repoId]);
            $lastKnown = $lastKnownStmt->fetch() ?: [];
            $totalSize ??= (int) ($lastKnown['total_size'] ?? 0);
            $totalFileCount ??= (int) ($lastKnown['total_file_count'] ?? 0);
        }

        $totalSize ??= (int) ($existing['total_size'] ?? 0);
        $totalFileCount ??= (int) ($existing['total_file_count'] ?? 0);

        if ($existing) {
            $db->prepare("
                UPDATE repo_stats_history
                SET snapshot_count = ?, total_size = ?, total_file_count = ?, recorded_at = ?
                WHERE id = ?
            ")->execute([
                $snapshotCount,
                $totalSize,
                $totalFileCount,
                $bucketStart,
                (int) $existing['id'],
            ]);
            return;
        }

        $db->prepare("
            INSERT INTO repo_stats_history (repo_id, snapshot_count, total_size, total_file_count, recorded_at)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $repoId,
            $snapshotCount,
            $totalSize,
            $totalFileCount,
            $bucketStart,
        ]);
    }

    public static function getCachedStatuses(): array {
        return self::hydrateStatuses(RepoManager::getAll(), self::getRawCachedStatuses());
    }

    private static function getRawCachedStatuses(?array $repos = null): array {
        $db = Database::getInstance();
        $repos ??= RepoManager::getAll();
        $reposById = [];
        foreach ($repos as $repo) {
            $reposById[(int) $repo['id']] = $repo;
        }

        $rows = $db->query("
            SELECT repo_id, repo_name, snapshot_count, last_snapshot_time, hours_ago, status, updated_at
            FROM repo_runtime_status
            ORDER BY repo_name
        ")->fetchAll();

        $statuses = [];
        foreach ($rows as $row) {
            $repoId = (int) $row['repo_id'];
            if (!isset($reposById[$repoId])) {
                continue;
            }
            $statuses[] = [
                'id'         => $repoId,
                'name'       => $row['repo_name'],
                'count'      => (int) $row['snapshot_count'],
                'last_time'  => $row['last_snapshot_time'],
                'hours_ago'  => $row['hours_ago'] !== null ? (float) $row['hours_ago'] : null,
                'status'     => $row['status'],
                'updated_at' => $row['updated_at'],
                'repo'       => $reposById[$repoId],
            ];
        }

        return $statuses;
    }

    private static function hydrateStatuses(array $repos, array $rawStatuses): array {
        $statusesByRepoId = [];
        foreach ($rawStatuses as $status) {
            $statusesByRepoId[(int) $status['id']] = $status;
        }

        $statuses = [];
        foreach ($repos as $repo) {
            $repoId = (int) $repo['id'];
            $status = $statusesByRepoId[$repoId] ?? [
                'id'         => $repoId,
                'name'       => $repo['name'],
                'count'      => 0,
                'last_time'  => null,
                'hours_ago'  => null,
                'status'     => 'pending',
                'updated_at' => null,
            ];

            $status['repo'] = $repo;
            $statuses[] = $status;
        }

        return $statuses;
    }

    public static function latestUpdate(): ?string {
        return Database::getInstance()
            ->query("SELECT MAX(updated_at) FROM repo_runtime_status")
            ->fetchColumn() ?: null;
    }

    private static function isCacheStale(array $statuses): bool {
        $latest = null;
        foreach ($statuses as $status) {
            if (!empty($status['updated_at']) && ($latest === null || $status['updated_at'] > $latest)) {
                $latest = $status['updated_at'];
            }
        }

        if ($latest === null) {
            return true;
        }

        try {
            $updatedAt = new DateTime($latest);
        } catch (Exception $e) {
            return true;
        }

        return ((new DateTime())->getTimestamp() - $updatedAt->getTimestamp()) > self::STATUS_STALE_AFTER_SECONDS;
    }
}
