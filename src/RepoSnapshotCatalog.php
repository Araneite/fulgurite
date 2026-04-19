<?php

class RepoSnapshotCatalog {
    public static function sync(int $repoId, array $snapshots): void {
        $db = Database::getIndexInstance();

        $snapshotIds = [];
        foreach ($snapshots as $snapshot) {
            $shortId = self::resolveSnapshotId($snapshot);
            if ($shortId !== '') {
                $snapshotIds[] = $shortId;
            }
        }

        if (empty($snapshotIds)) {
            $db->prepare("DELETE FROM repo_snapshot_catalog WHERE repo_id = ?")->execute([$repoId]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($snapshotIds), '?'));
        $params = array_merge([$repoId], $snapshotIds);
        $db->prepare("
            DELETE FROM repo_snapshot_catalog
            WHERE repo_id = ?
              AND snapshot_id NOT IN ($placeholders)
        ")->execute($params);

        $stmt = $db->prepare("
            INSERT INTO repo_snapshot_catalog (
                repo_id, snapshot_id, full_id, snapshot_time, hostname, username, tags_json, paths_json, indexed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ON CONFLICT(repo_id, snapshot_id) DO UPDATE SET
                full_id = excluded.full_id,
                snapshot_time = excluded.snapshot_time,
                hostname = excluded.hostname,
                username = excluded.username,
                tags_json = excluded.tags_json,
                paths_json = excluded.paths_json,
                indexed_at = excluded.indexed_at
        ");

        foreach ($snapshots as $snapshot) {
            $shortId = self::resolveSnapshotId($snapshot);
            if ($shortId === '') {
                continue;
            }

            $stmt->execute([
                $repoId,
                $shortId,
                (string) ($snapshot['id'] ?? ''),
                (string) ($snapshot['time'] ?? ''),
                (string) ($snapshot['hostname'] ?? ''),
                (string) ($snapshot['username'] ?? ''),
                json_encode(array_values(array_map('strval', $snapshot['tags'] ?? [])), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                json_encode(array_values(array_map('strval', $snapshot['paths'] ?? [])), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public static function deleteSnapshots(int $repoId, array $snapshotIds): void {
        $snapshotIds = array_values(array_unique(array_filter(
            array_map('strval', $snapshotIds),
            static fn(string $snapshotId): bool => $snapshotId !== ''
        )));
        if (empty($snapshotIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($snapshotIds), '?'));
        $params = array_merge([$repoId], $snapshotIds);
        Database::getIndexInstance()->prepare("
            DELETE FROM repo_snapshot_catalog
            WHERE repo_id = ?
              AND snapshot_id IN ($placeholders)
        ")->execute($params);
    }

    public static function getSnapshots(int $repoId): array {
        $stmt = Database::getIndexInstance()->prepare("
            SELECT snapshot_id, full_id, snapshot_time, hostname, username, tags_json, paths_json
            FROM repo_snapshot_catalog
            WHERE repo_id = ?
            ORDER BY snapshot_time DESC, snapshot_id DESC
        ");
        $stmt->execute([$repoId]);

        $snapshots = [];
        foreach ($stmt->fetchAll() as $row) {
            $snapshots[] = [
                'short_id' => (string) ($row['snapshot_id'] ?? ''),
                'id' => (string) ($row['full_id'] ?? ''),
                'time' => (string) ($row['snapshot_time'] ?? ''),
                'hostname' => (string) ($row['hostname'] ?? ''),
                'username' => (string) ($row['username'] ?? ''),
                'tags' => self::decodeJsonList($row['tags_json'] ?? '[]'),
                'paths' => self::decodeJsonList($row['paths_json'] ?? '[]'),
            ];
        }

        return $snapshots;
    }

    public static function findSnapshot(int $repoId, string $snapshotId): ?array {
        foreach (self::getSnapshots($repoId) as $snapshot) {
            if (($snapshot['short_id'] ?? '') === $snapshotId) {
                return $snapshot;
            }
        }

        return null;
    }

    private static function decodeJsonList(string $json): array {
        $value = json_decode($json, true);
        return is_array($value) ? array_values(array_map('strval', $value)) : [];
    }

    public static function resolveSnapshotId(array $snapshot): string {
        $shortId = trim((string) ($snapshot['short_id'] ?? ''));
        if ($shortId !== '') {
            return $shortId;
        }

        $fullId = trim((string) ($snapshot['id'] ?? ''));
        if ($fullId === '') {
            return '';
        }

        return substr($fullId, 0, 8);
    }
}
