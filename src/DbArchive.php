<?php

class DbArchive {
    private const BATCH_SIZE = 1000;

    public static function archiveAndPurgeByAge(PDO $db, string $table, string $timeColumn, int $retentionDays, int $batchSize = self::BATCH_SIZE): int {
        $totalDeleted = 0;
        $cutoff = (new DateTimeImmutable())->modify('-' . max(1, $retentionDays) . ' days')->format('Y-m-d H:i:s');

        while (true) {
            $stmt = $db->prepare("
                SELECT *
                FROM {$table}
                WHERE {$timeColumn} < ?
                ORDER BY {$timeColumn} ASC, id ASC
                LIMIT ?
            ");
            $stmt->bindValue(1, $cutoff, PDO::PARAM_STR);
            $stmt->bindValue(2, max(1, $batchSize), PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            if (empty($rows)) {
                break;
            }

            self::archiveRows($table, $rows, 'age-purge');
            $totalDeleted += self::deleteRowsByIds($db, $table, array_column($rows, 'id'));
        }

        return $totalDeleted;
    }

    public static function downsampleRepoStatsHistory(int $keepHourlyDays = 30, int $batchSize = 5000): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT h.id, h.repo_id, h.snapshot_count, h.total_size, h.total_file_count, h.recorded_at
            FROM repo_stats_history h
            INNER JOIN (
                SELECT id
                FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (
                               PARTITION BY repo_id, substr(recorded_at, 1, 10)
                               ORDER BY recorded_at DESC, id DESC
                           ) AS rn
                    FROM repo_stats_history
                    WHERE recorded_at < datetime('now', '-' || ? || ' days')
                )
                WHERE rn > 1
                LIMIT ?
            ) stale ON stale.id = h.id
            ORDER BY h.recorded_at ASC, h.id ASC
        ");
        $stmt->bindValue(1, max(1, $keepHourlyDays), PDO::PARAM_INT);
        $stmt->bindValue(2, max(1, $batchSize), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        if (empty($rows)) {
            return 0;
        }

        self::archiveRows('repo_stats_history', $rows, 'daily-downsample');
        return self::deleteRowsByIds($db, 'repo_stats_history', array_column($rows, 'id'));
    }

    public static function archiveAndPurgeJobQueue(PDO $db, int $retentionDays = 14, int $batchSize = self::BATCH_SIZE): int {
        $totalDeleted = 0;
        $cutoff = (new DateTimeImmutable())->modify('-' . max(1, $retentionDays) . ' days')->format('Y-m-d H:i:s');

        while (true) {
            $stmt = $db->prepare("
                SELECT *
                FROM job_queue
                WHERE status IN ('completed', 'failed', 'dead_letter')
                  AND updated_at < ?
                ORDER BY updated_at ASC, id ASC
                LIMIT ?
            ");
            $stmt->bindValue(1, $cutoff, PDO::PARAM_STR);
            $stmt->bindValue(2, max(1, $batchSize), PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            if (empty($rows)) {
                break;
            }

            self::archiveRows('job_queue', $rows, 'queue-retention');
            $totalDeleted += self::deleteRowsByIds($db, 'job_queue', array_column($rows, 'id'));
        }

        return $totalDeleted;
    }

    public static function purgeArchiveFiles(int $retentionDays = 365): int {
        $archiveRoot = dirname(DB_PATH) . '/archive/db';
        if (!is_dir($archiveRoot)) {
            return 0;
        }

        $cutoff = time() - (max(1, $retentionDays) * 86400);
        $deleted = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($archiveRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $modifiedAt = $item->getMTime();
                if ($modifiedAt <= $cutoff && @unlink($item->getPathname())) {
                    $deleted++;
                }
                continue;
            }

            if ($item->isDir()) {
                @rmdir($item->getPathname());
            }
        }

        return $deleted;
    }

    private static function deleteRowsByIds(PDO $db, string $table, array $ids): int {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn($id) => $id > 0));
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    private static function archiveRows(string $table, array $rows, string $reason): void {
        if (empty($rows)) {
            return;
        }

        $archiveDir = self::getArchiveDir($table);
        $timestamp = (new DateTimeImmutable())->format('Ymd-His');
        $filename = sprintf('%s/%s-%s-%s-%s.jsonl.gz', $archiveDir, $table, $reason, $timestamp, substr(sha1((string) mt_rand()), 0, 8));

        $lines = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $lines[] = json_encode([
                'archived_at' => date(DATE_ATOM),
                'table' => $table,
                'reason' => $reason,
                'row' => $row,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (empty($lines)) {
            return;
        }

        $payload = implode("\n", $lines) . "\n";
        $compressed = gzencode($payload, 9);
        if ($compressed === false) {
            return;
        }

        @file_put_contents($filename, $compressed, LOCK_EX);
    }

    private static function getArchiveDir(string $table): string {
        $dir = dirname(DB_PATH) . '/archive/db/' . $table . '/' . date('Y') . '/' . date('m');
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        return $dir;
    }
}
