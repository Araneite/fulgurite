<?php

class SnapshotSearchIndex {
    public static function search(int $repoId, string $snapshotId, string $query, ?int $limit = null): ?array {
        if (!self::isFullyIndexed($repoId, $snapshotId)) {
            return null;
        }

        $limit = $limit ?? AppConfig::exploreSearchMaxResults();
        $normalizedQuery = self::normalizeSearchQuery($query);
        if ($normalizedQuery === '') {
            return [];
        }

        $db = Database::getIndexInstance();
        $driver = self::getIndexDriver($db);

        $results = [];
        if ($driver === 'sqlite') {
            $results = self::searchSqliteFts($db, $repoId, $snapshotId, $normalizedQuery, $limit) ?? [];
        } elseif ($driver === 'mysql') {
            $results = self::searchMysqlFulltext($db, $repoId, $snapshotId, $normalizedQuery, $limit) ?? [];
        } elseif ($driver === 'pgsql') {
            $results = self::searchPgsqlFulltext($db, $repoId, $snapshotId, $normalizedQuery, $limit) ?? [];
        }

        if (count($results) >= $limit) {
            return $results;
        }

        return self::searchHybridPrefixThenContains(
            $db,
            $repoId,
            $snapshotId,
            $normalizedQuery,
            $limit,
            $results
        );
    }

    public static function isIndexed(int $repoId, string $snapshotId): bool {
        return self::getIndexScope($repoId, $snapshotId) !== null;
    }

    public static function isFullyIndexed(int $repoId, string $snapshotId): bool {
        return self::getIndexScope($repoId, $snapshotId) === 'full';
    }

    public static function isNavigationIndexed(int $repoId, string $snapshotId): bool {
        $scope = self::getIndexScope($repoId, $snapshotId);
        return $scope === 'nav' || $scope === 'full';
    }

    public static function enqueueRepoSync(int $repoId, string $reason = 'manual', int $priority = 150): int {
        return JobQueue::enqueueRepoSnapshotRefresh($repoId, $reason, $priority);
    }

    public static function forceFullIndex(int $repoId, string $snapshotId, Restic $restic): bool {
        return self::indexSnapshot($repoId, $snapshotId, $restic);
    }

    public static function deleteSnapshots(int $repoId, array $snapshotIds): void {
        $snapshotIds = array_values(array_unique(array_filter(
            array_map('strval', $snapshotIds),
            static fn(string $snapshotId): bool => $snapshotId !== ''
        )));
        if (empty($snapshotIds)) {
            return;
        }

        $db = Database::getIndexInstance();
        $placeholders = implode(',', array_fill(0, count($snapshotIds), '?'));
        $params = array_merge([$repoId], $snapshotIds);

        $db->prepare("
            DELETE FROM snapshot_search_index_status
            WHERE repo_id = ?
              AND snapshot_id IN ($placeholders)
        ")->execute($params);
        $db->prepare("
            DELETE FROM snapshot_file_index
            WHERE repo_id = ?
              AND snapshot_id IN ($placeholders)
        ")->execute($params);
        $db->prepare("
            DELETE FROM snapshot_navigation_index
            WHERE repo_id = ?
              AND snapshot_id IN ($placeholders)
        ")->execute($params);
    }

    public static function syncRepoSnapshots(int $repoId, Restic $restic, array $snapshots): void {
        $db = Database::getIndexInstance();
        $snapshotIds = [];
        foreach ($snapshots as $snapshot) {
            $snapshotId = RepoSnapshotCatalog::resolveSnapshotId($snapshot);
            if ($snapshotId !== '') {
                $snapshotIds[] = $snapshotId;
            }
        }

        if (empty($snapshotIds)) {
            $db->prepare("DELETE FROM snapshot_search_index_status WHERE repo_id = ?")->execute([$repoId]);
            $db->prepare("DELETE FROM snapshot_file_index WHERE repo_id = ?")->execute([$repoId]);
            $db->prepare("DELETE FROM snapshot_navigation_index WHERE repo_id = ?")->execute([$repoId]);
            return;
        }

        $snapshotsByRecency = $snapshots;
        usort($snapshotsByRecency, static fn($a, $b) => strcmp((string) ($b['time'] ?? ''), (string) ($a['time'] ?? '')));

        $searchTargets = array_slice($snapshotsByRecency, 0, AppConfig::searchIndexRecentSnapshots());
        $warmTargets = array_slice($snapshotsByRecency, 0, AppConfig::searchIndexWarmBatchPerRun());

        $availableSnapshotIds = array_fill_keys($snapshotIds, true);
        $keepSnapshotIds = [];
        foreach (array_merge($searchTargets, $warmTargets) as $target) {
            $targetId = RepoSnapshotCatalog::resolveSnapshotId($target);
            if ($targetId !== '') {
                $keepSnapshotIds[$targetId] = true;
            }
        }

        foreach (self::getRecentAdhocSnapshotIds($repoId) as $recentSnapshotId) {
            if (isset($availableSnapshotIds[$recentSnapshotId])) {
                $keepSnapshotIds[$recentSnapshotId] = true;
            }
        }

        self::pruneRepoIndexes($db, $repoId, array_keys($keepSnapshotIds));

        foreach ($searchTargets as $snapshot) {
            $snapshotId = RepoSnapshotCatalog::resolveSnapshotId($snapshot);
            if ($snapshotId === '' || self::isFullyIndexed($repoId, $snapshotId)) {
                continue;
            }

            self::indexSnapshot($repoId, $snapshotId, $restic);
        }

        foreach ($warmTargets as $snapshot) {
            $snapshotId = RepoSnapshotCatalog::resolveSnapshotId($snapshot);
            if ($snapshotId === '' || self::isIndexed($repoId, $snapshotId)) {
                continue;
            }

            self::warmSnapshot($repoId, $snapshotId, $restic, $snapshot);
        }
    }

    public static function listDirectory(int $repoId, string $snapshotId, string $path = '/'): ?array {
        if (!self::isNavigationIndexed($repoId, $snapshotId)) {
            return null;
        }

        $normalizedPath = self::normalizePath($path);
        $stmt = Database::getIndexInstance()->prepare("
            SELECT path, name, type, size, mtime
            FROM snapshot_navigation_index
            WHERE repo_id = ?
              AND snapshot_id = ?
              AND parent_path = ?
            ORDER BY CASE WHEN type = 'dir' THEN 0 ELSE 1 END, name ASC
        ");
        $stmt->execute([$repoId, $snapshotId, $normalizedPath]);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $rowPath = (string) ($row['path'] ?? '');
            if ($rowPath === '') {
                continue;
            }

            $items[] = [
                'path' => $rowPath,
                'name' => (string) ($row['name'] ?? basename($rowPath)),
                'type' => (string) ($row['type'] ?? 'file'),
                'size' => (int) ($row['size'] ?? 0),
                'mtime' => $row['mtime'] ?? null,
            ];
        }

        return $items;
    }

    private static function getIndexScope(int $repoId, string $snapshotId): ?string {
        $stmt = Database::getIndexInstance()->prepare("
            SELECT scope
            FROM snapshot_search_index_status
            WHERE repo_id = ? AND snapshot_id = ?
            LIMIT 1
        ");
        $stmt->execute([$repoId, $snapshotId]);
        $scope = $stmt->fetchColumn();
        return $scope !== false ? (string) $scope : null;
    }

    private static function getRecentAdhocSnapshotIds(int $repoId): array {
        $db = Database::getIndexInstance();
        $retentionDays = max(1, AppConfig::searchIndexAdhocRetentionDays());
        $driver = self::getIndexDriver($db);

        if ($driver === 'mysql') {
            $stmt = $db->prepare("
                SELECT snapshot_id
                FROM snapshot_search_index_status
                WHERE repo_id = ?
                  AND indexed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$repoId, $retentionDays]);
        } elseif ($driver === 'pgsql') {
            $stmt = $db->prepare("
                SELECT snapshot_id
                FROM snapshot_search_index_status
                WHERE repo_id = ?
                  AND indexed_at >= (NOW() - (? * INTERVAL '1 day'))
            ");
            $stmt->execute([$repoId, $retentionDays]);
        } else {
            $stmt = $db->prepare("
                SELECT snapshot_id
                FROM snapshot_search_index_status
                WHERE repo_id = ?
                  AND indexed_at >= datetime('now', '-' || ? || ' days')
            ");
            $stmt->execute([$repoId, $retentionDays]);
        }

        $snapshotIds = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $snapshotId) {
            $snapshotId = (string) $snapshotId;
            if ($snapshotId !== '') {
                $snapshotIds[] = $snapshotId;
            }
        }

        return $snapshotIds;
    }

    private static function pruneRepoIndexes(PDO $db, int $repoId, array $keepSnapshotIds): void {
        if (empty($keepSnapshotIds)) {
            $db->prepare("DELETE FROM snapshot_search_index_status WHERE repo_id = ?")->execute([$repoId]);
            $db->prepare("DELETE FROM snapshot_file_index WHERE repo_id = ?")->execute([$repoId]);
            $db->prepare("DELETE FROM snapshot_navigation_index WHERE repo_id = ?")->execute([$repoId]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($keepSnapshotIds), '?'));
        $params = array_merge([$repoId], $keepSnapshotIds);

        $db->prepare("
            DELETE FROM snapshot_search_index_status
            WHERE repo_id = ?
              AND snapshot_id NOT IN ($placeholders)
        ")->execute($params);

        $db->prepare("
            DELETE FROM snapshot_file_index
            WHERE repo_id = ?
              AND snapshot_id NOT IN ($placeholders)
        ")->execute($params);
        $db->prepare("
            DELETE FROM snapshot_navigation_index
            WHERE repo_id = ?
              AND snapshot_id NOT IN ($placeholders)
        ")->execute($params);
    }

    private static function indexSnapshot(int $repoId, string $snapshotId, Restic $restic): bool {
        $tree = $restic->tree($snapshotId);
        if (isset($tree['error'])) {
            return false;
        }

        $db = Database::getIndexInstance();
        $nowExpr = self::nowExpr($db);
        $db->beginTransaction();
        try {
            $db->prepare("
                DELETE FROM snapshot_file_index
                WHERE repo_id = ? AND snapshot_id = ?
            ")->execute([$repoId, $snapshotId]);
            $db->prepare("
                DELETE FROM snapshot_navigation_index
                WHERE repo_id = ? AND snapshot_id = ?
            ")->execute([$repoId, $snapshotId]);

            $insertStmt = $db->prepare("
                INSERT INTO snapshot_file_index (repo_id, snapshot_id, path, name, name_lc, type, size, mtime, indexed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, {$nowExpr})
            ");
            $navInsertStmt = $db->prepare("
                INSERT INTO snapshot_navigation_index (repo_id, snapshot_id, path, parent_path, name, type, size, mtime, indexed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, {$nowExpr})
            ");

            $fileCount = 0;
            foreach ($tree as $item) {
                if (!is_array($item) || empty($item['path'])) {
                    continue;
                }

                $path = (string) $item['path'];
                $name = basename($path);
                $insertStmt->execute([
                    $repoId,
                    $snapshotId,
                    $path,
                    $name,
                    mb_strtolower($name, 'UTF-8'),
                    (string) ($item['type'] ?? 'file'),
                    (int) ($item['size'] ?? 0),
                    $item['mtime'] ?? null,
                ]);
                $navInsertStmt->execute([
                    $repoId,
                    $snapshotId,
                    $path,
                    self::normalizePath(dirname($path)),
                    $name,
                    (string) ($item['type'] ?? 'file'),
                    (int) ($item['size'] ?? 0),
                    $item['mtime'] ?? null,
                ]);
                $fileCount++;
            }

            self::upsertStatus($db, $repoId, $snapshotId, $fileCount, 'full');
            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    private static function warmSnapshot(int $repoId, string $snapshotId, Restic $restic, array $snapshot): void {
        $pathsToWarm = ['/'];
        foreach (array_values(array_unique(array_map('strval', $snapshot['paths'] ?? []))) as $savedPath) {
            if (count($pathsToWarm) >= AppConfig::searchIndexWarmBatchPerRun()) {
                break;
            }
            $pathsToWarm[] = self::normalizePath($savedPath);
        }

        $items = [];
        foreach ($pathsToWarm as $pathToWarm) {
            $rows = $restic->lsWithTimeout($snapshotId, $pathToWarm, 4);
            if (isset($rows['error'])) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row) || empty($row['path'])) {
                    continue;
                }

                $path = (string) $row['path'];
                $items[$path] = [
                    'path' => $path,
                    'name' => basename($path),
                    'type' => (string) ($row['type'] ?? 'file'),
                    'size' => (int) ($row['size'] ?? 0),
                    'mtime' => $row['mtime'] ?? null,
                ];
            }
        }

        if (empty($items)) {
            return;
        }

        $db = Database::getIndexInstance();
        $nowExpr = self::nowExpr($db);
        $db->beginTransaction();
        try {
            $db->prepare("
                DELETE FROM snapshot_navigation_index
                WHERE repo_id = ? AND snapshot_id = ?
            ")->execute([$repoId, $snapshotId]);

            $db->prepare("
                DELETE FROM snapshot_file_index
                WHERE repo_id = ? AND snapshot_id = ?
            ")->execute([$repoId, $snapshotId]);

            $navInsertStmt = $db->prepare("
                INSERT INTO snapshot_navigation_index (repo_id, snapshot_id, path, parent_path, name, type, size, mtime, indexed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, {$nowExpr})
            ");

            foreach ($items as $item) {
                $navInsertStmt->execute([
                    $repoId,
                    $snapshotId,
                    $item['path'],
                    self::normalizePath(dirname((string) $item['path'])),
                    $item['name'],
                    $item['type'],
                    $item['size'],
                    $item['mtime'],
                ]);
            }

            self::upsertStatus($db, $repoId, $snapshotId, count($items), 'nav');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
        }
    }

    private static function upsertStatus(PDO $db, int $repoId, string $snapshotId, int $fileCount, string $scope): void {
        $nowExpr = self::nowExpr($db);
        $driver = self::getIndexDriver($db);
        if ($driver === 'mysql') {
            $db->prepare("
                INSERT INTO snapshot_search_index_status (repo_id, snapshot_id, indexed_at, file_count, scope)
                VALUES (?, ?, {$nowExpr}, ?, ?)
                ON DUPLICATE KEY UPDATE
                    indexed_at = VALUES(indexed_at),
                    file_count = VALUES(file_count),
                    scope = VALUES(scope)
            ")->execute([$repoId, $snapshotId, $fileCount, $scope]);
            return;
        }

        $db->prepare("
            INSERT INTO snapshot_search_index_status (repo_id, snapshot_id, indexed_at, file_count, scope)
            VALUES (?, ?, {$nowExpr}, ?, ?)
            ON CONFLICT(repo_id, snapshot_id) DO UPDATE SET
                indexed_at = excluded.indexed_at,
                file_count = excluded.file_count,
                scope = excluded.scope
        ")->execute([$repoId, $snapshotId, $fileCount, $scope]);
    }

    private static function normalizePath(string $path): string {
        $path = trim($path);
        if ($path === '' || $path === '.') {
            return '/';
        }

        $normalized = '/' . trim($path, '/');
        return $normalized === '//' ? '/' : $normalized;
    }

    private static function getIndexDriver(PDO $db): string {
        return (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private static function normalizeSearchQuery(string $query): string {
        return mb_strtolower(trim($query), 'UTF-8');
    }

    private static function nowExpr(PDO $db): string {
        $driver = self::getIndexDriver($db);
        return ($driver === 'mysql' || $driver === 'pgsql') ? 'NOW()' : "datetime('now')";
    }

    private static function splitSearchTerms(string $query): array {
        $terms = preg_split('/\s+/u', $query) ?: [];
        $normalizedTerms = [];
        foreach ($terms as $term) {
            $term = preg_replace('/[^\p{L}\p{N}_]+/u', '', trim((string) $term));
            if ($term === '') {
                continue;
            }
            $normalizedTerms[] = $term;
        }
        return array_values(array_unique($normalizedTerms));
    }

    private static function searchSqliteFts(PDO $db, int $repoId, string $snapshotId, string $query, int $limit): ?array {
        $terms = self::splitSearchTerms($query);
        if (empty($terms)) {
            return null;
        }

        $match = implode(' AND ', array_map(static fn(string $term): string => $term . '*', $terms));
        try {
            $stmt = $db->prepare("
                SELECT sfi.path, sfi.type, sfi.size, sfi.mtime
                FROM snapshot_file_index AS sfi
                INNER JOIN snapshot_file_index_fts AS fts ON fts.rowid = sfi.rowid
                WHERE sfi.repo_id = ?
                  AND sfi.snapshot_id = ?
                  AND fts.name_lc MATCH ?
                ORDER BY bm25(snapshot_file_index_fts), CASE WHEN sfi.type = 'dir' THEN 0 ELSE 1 END, sfi.path ASC
                LIMIT ?
            ");
            $stmt->execute([$repoId, $snapshotId, $match, $limit]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function searchMysqlFulltext(PDO $db, int $repoId, string $snapshotId, string $query, int $limit): ?array {
        $terms = self::splitSearchTerms($query);
        if (empty($terms)) {
            return null;
        }

        $booleanQuery = implode(' ', array_map(static fn(string $term): string => '+' . $term . '*', $terms));
        if ($booleanQuery === '') {
            return null;
        }

        try {
            $stmt = $db->prepare("
                SELECT path, type, size, mtime
                FROM snapshot_file_index
                WHERE repo_id = ?
                  AND snapshot_id = ?
                  AND MATCH(name_lc) AGAINST (? IN BOOLEAN MODE)
                ORDER BY
                    MATCH(name_lc) AGAINST (? IN BOOLEAN MODE) DESC,
                    CASE WHEN type = 'dir' THEN 0 ELSE 1 END,
                    path ASC
                LIMIT ?
            ");
            $stmt->execute([$repoId, $snapshotId, $booleanQuery, $booleanQuery, $limit]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function searchPgsqlFulltext(PDO $db, int $repoId, string $snapshotId, string $query, int $limit): ?array {
        $terms = self::splitSearchTerms($query);
        if (empty($terms)) {
            return null;
        }

        $tsQuery = implode(' & ', array_map(static fn(string $term): string => $term . ':*', $terms));
        if ($tsQuery === '') {
            return null;
        }

        try {
            $stmt = $db->prepare("
                SELECT path, type, size, mtime
                FROM snapshot_file_index
                WHERE repo_id = ?
                  AND snapshot_id = ?
                  AND to_tsvector('simple', COALESCE(name_lc, '')) @@ to_tsquery('simple', ?)
                ORDER BY
                    ts_rank_cd(to_tsvector('simple', COALESCE(name_lc, '')), to_tsquery('simple', ?)) DESC,
                    CASE WHEN type = 'dir' THEN 0 ELSE 1 END,
                    path ASC
                LIMIT ?
            ");
            $stmt->execute([$repoId, $snapshotId, $tsQuery, $tsQuery, $limit]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function searchHybridPrefixThenContains(
        PDO $db,
        int $repoId,
        string $snapshotId,
        string $query,
        int $limit,
        array $seedResults = []
    ): array {
        $results = self::appendUniqueRows($seedResults, [], $limit);
        if (count($results) >= $limit) {
            return $results;
        }

        $terms = self::splitSearchTerms($query);
        if (!empty($terms)) {
            $prefix = $terms[0] . '%';
            $remaining = $limit - count($results);
            $prefixStmt = $db->prepare("
                SELECT path, type, size, mtime
                FROM snapshot_file_index
                WHERE repo_id = ?
                  AND snapshot_id = ?
                  AND name_lc LIKE ?
                ORDER BY CASE WHEN type = 'dir' THEN 0 ELSE 1 END, path ASC
                LIMIT ?
            ");
            $prefixStmt->execute([$repoId, $snapshotId, $prefix, $remaining]);
            $results = self::appendUniqueRows($results, $prefixStmt->fetchAll(), $limit);
        }

        if (count($results) >= $limit) {
            return $results;
        }

        $remaining = $limit - count($results);
        if ($remaining <= 0) {
            return $results;
        }

        $params = [$repoId, $snapshotId, '%' . $query . '%'];
        $excludePaths = [];
        foreach ($results as $row) {
            $path = (string) ($row['path'] ?? '');
            if ($path !== '') {
                $excludePaths[] = $path;
            }
        }

        $excludeSql = '';
        if (!empty($excludePaths)) {
            $placeholders = implode(',', array_fill(0, count($excludePaths), '?'));
            $excludeSql = " AND path NOT IN ($placeholders)";
            $params = array_merge($params, $excludePaths);
        }
        $params[] = min(max($remaining, 1), max(20, $limit));

        $containsStmt = $db->prepare("
            SELECT path, type, size, mtime
            FROM snapshot_file_index
            WHERE repo_id = ?
              AND snapshot_id = ?
              AND name_lc LIKE ?
              $excludeSql
            ORDER BY CASE WHEN type = 'dir' THEN 0 ELSE 1 END, path ASC
            LIMIT ?
        ");
        $containsStmt->execute($params);
        return self::appendUniqueRows($results, $containsStmt->fetchAll(), $limit);
    }

    private static function appendUniqueRows(array $baseRows, array $newRows, int $limit): array {
        $result = [];
        $seenPaths = [];

        foreach ($baseRows as $row) {
            $path = (string) ($row['path'] ?? '');
            if ($path === '' || isset($seenPaths[$path])) {
                continue;
            }
            $seenPaths[$path] = true;
            $result[] = $row;
            if (count($result) >= $limit) {
                return $result;
            }
        }

        foreach ($newRows as $row) {
            $path = (string) ($row['path'] ?? '');
            if ($path === '' || isset($seenPaths[$path])) {
                continue;
            }
            $seenPaths[$path] = true;
            $result[] = $row;
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }
}
