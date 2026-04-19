<?php
// =============================================================================
// StatsHandler.php — /api/v1/stats
// =============================================================================

class StatsHandler {

    public static function summary(array $args): void {
        ApiAuth::requireScope('stats:read');
        $db = Database::getInstance();
        $allowedRepoIds = ApiAuth::allowedRepoIds();
        $allowedHostIds = ApiAuth::allowedHostIds();
        $repoRuntimeRows = ApiAuth::filterAllowedRepos(
            $db->query('SELECT * FROM repo_runtime_status ORDER BY repo_name')->fetchAll(),
            'repo_id'
        );

        ApiResponse::ok([
            'repos' => count(ApiAuth::filterAllowedRepos(RepoManager::getAll())),
            'hosts' => count(ApiAuth::filterAllowedHosts(HostManager::getAll())),
            'backup_jobs' => count(BackupJobManager::getAll($allowedRepoIds, $allowedHostIds)),
            'copy_jobs' => count(CopyJobManager::getAll($allowedRepoIds)),
            'users' => ApiAuth::hasResourceScopeRestriction() ? null : (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'restores' => self::countRepoScopedRows($db, 'restore_history', 'repo_id', $allowedRepoIds),
            'repo_status_breakdown' => self::buildRepoStatusBreakdown($repoRuntimeRows),
        ]);
    }

    public static function repoHistory(array $args): void {
        ApiAuth::requireScope('stats:read');
        $repoId = (int) $args['id'];
        ApiAuth::requireRepoAccess($repoId);
        $limit = ApiRequest::queryInt('limit', 100);
        $limit = max(1, min(1000, $limit));
        $stmt = Database::getInstance()->prepare('
            SELECT * FROM repo_stats_history WHERE repo_id = ? ORDER BY recorded_at DESC LIMIT ?
        ');
        $stmt->bindValue(1, $repoId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        ApiResponse::ok($stmt->fetchAll());
    }

    public static function repoRuntime(array $args): void {
        ApiAuth::requireScope('stats:read');
        $rows = Database::getInstance()->query('SELECT * FROM repo_runtime_status ORDER BY repo_name')->fetchAll();
        ApiResponse::ok(ApiAuth::filterAllowedRepos($rows, 'repo_id'));
    }

    private static function countRepoScopedRows(PDO $db, string $table, string $column, ?array $allowedRepoIds): int {
        if ($allowedRepoIds === null) {
            return (int) $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
        }
        if ($allowedRepoIds === []) {
            return 0;
        }

        $params = [];
        $where = self::inClause($column, $allowedRepoIds, $params);
        $stmt = $db->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private static function buildRepoStatusBreakdown(array $rows): array {
        $statusMap = [];
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if ($status === '') {
                continue;
            }
            $statusMap[$status] = ($statusMap[$status] ?? 0) + 1;
        }

        ksort($statusMap);
        return $statusMap;
    }

    private static function inClause(string $column, array $ids, array &$params): string {
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        foreach ($ids as $id) {
            $params[] = (int) $id;
        }

        return $column . ' IN (' . $placeholders . ')';
    }
}
