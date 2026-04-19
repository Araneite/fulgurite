<?php
// =============================================================================
// RestoresHandler.php — /api/v1/restores
// =============================================================================

require_once __DIR__ . '/../../RestoreTargetPlanner.php';

class RestoresHandler {

    public static function index(array $args): void {
        ApiAuth::requireScope('restores:read');
        [$page, $perPage] = ApiRequest::pageParams(30, 200);
        $offset = ($page - 1) * $perPage;
        $db = Database::getInstance();
        $params = [];
        $where = self::repoWhereClause('repo_id', ApiAuth::allowedRepoIds(), $params);

        $countSql = 'SELECT COUNT(*) FROM restore_history';
        $itemsSql = 'SELECT * FROM restore_history';
        if ($where !== null) {
            $countSql .= ' WHERE ' . $where;
            $itemsSql .= ' WHERE ' . $where;
        }

        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare($itemsSql . ' ORDER BY started_at DESC LIMIT ? OFFSET ?');
        $position = 1;
        foreach ($params as $param) {
            $stmt->bindValue($position++, $param, PDO::PARAM_INT);
        }
        $stmt->bindValue($position++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($position, $offset, PDO::PARAM_INT);
        $stmt->execute();
        ApiResponse::paginated($stmt->fetchAll(), $page, $perPage, $total);
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('restores:read');
        $stmt = Database::getInstance()->prepare('SELECT * FROM restore_history WHERE id = ?');
        $stmt->execute([(int) $args['id']]);
        $row = $stmt->fetch();
        if (!$row) ApiResponse::error(404, 'not_found', 'Restore introuvable');
        ApiAuth::requireRepoAccess((int) $row['repo_id']);
        ApiResponse::ok($row);
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('restores:write');
        $body = ApiRequest::body();
        $repoId = (int) ($body['repo_id'] ?? 0);
        $snapshotId = trim((string) ($body['snapshot_id'] ?? ''));
        $include = trim((string) ($body['include'] ?? ''));
        $destinationMode = RestoreTargetPlanner::normalizeStrategy((string) ($body['destination_mode'] ?? RestoreTargetPlanner::STRATEGY_MANAGED));
        $appendContextSubdir = array_key_exists('append_context_subdir', $body)
            ? !empty($body['append_context_subdir'])
            : AppConfig::restoreAppendContextSubdir();
        if ($repoId <= 0 || $snapshotId === '') {
            ApiResponse::error(422, 'validation_error', 'Champs repo_id et snapshot_id requis');
        }
        ApiAuth::requireRepoAccess($repoId);
        $repo = RepoManager::getById($repoId);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);

        $apiUser = ApiAuth::currentUser();
        $permissions = Auth::resolvedPermissionsForUser($apiUser);
        try {
            $plan = RestoreTargetPlanner::plan([
                'mode' => 'local',
                'destination_mode' => $destinationMode,
                'append_context_subdir' => $appendContextSubdir,
                'repo' => $repo,
                'snapshot' => RestoreTargetPlanner::findSnapshot($repoId, $snapshotId),
                'include' => $include,
                'can_restore_original' => !empty($permissions['settings.manage']),
            ]);
        } catch (InvalidArgumentException $e) {
            ApiResponse::error(422, 'validation_error', $e->getMessage());
        }

        $db = Database::getInstance();
        $db->prepare("
            INSERT INTO restore_history (repo_id, repo_name, snapshot_id, mode, target, include_path, status, started_by)
            VALUES (?, ?, ?, ?, ?, ?, 'running', ?)
        ")->execute([
            $repoId,
            $repo['name'],
            $snapshotId,
            RestoreTargetPlanner::buildModeTag(false, 'local', $plan['strategy']),
            $plan['effective_target'],
            $include ?: null,
            'api:' . $apiUser['username'],
        ]);
        $restoreId = (int) $db->lastInsertId();

        $restic = RepoManager::getRestic($repo);
        try {
            if ($plan['strategy'] === RestoreTargetPlanner::STRATEGY_MANAGED) {
                RestoreTargetPlanner::ensureLocalManagedDirectory($plan['effective_target']);
            }
            $result = $restic->restore($snapshotId, $plan['effective_target'], $include);
        } catch (Throwable $e) {
            $result = ['success' => false, 'output' => $e->getMessage()];
        }
        $status = !empty($result['success']) ? 'success' : 'failed';
        $db->prepare("UPDATE restore_history SET status = ?, output = ?, finished_at = datetime('now') WHERE id = ?")
           ->execute([$status, $result['output'] ?? '', $restoreId]);

        ApiWebhookManager::dispatch(
            $status === 'success' ? 'restore.success' : 'restore.failure',
            ['id' => $restoreId, 'repo_id' => $repoId, 'snapshot_id' => $snapshotId]
        );
        Auth::log('api_restore', "Restore $snapshotId du depot {$repo['name']} via API ($status)", 'info');

        ApiResponse::created([
            'id' => $restoreId,
            'status' => $status,
            'output' => $result['output'] ?? '',
            'resolved_target' => $plan['effective_target'],
            'destination_mode' => $plan['strategy'],
        ]);
    }

    private static function repoWhereClause(string $column, ?array $repoIds, array &$params): ?string {
        if ($repoIds === null) {
            return null;
        }
        if ($repoIds === []) {
            return '1 = 0';
        }

        $placeholders = implode(', ', array_fill(0, count($repoIds), '?'));
        foreach ($repoIds as $repoId) {
            $params[] = (int) $repoId;
        }

        return $column . ' IN (' . $placeholders . ')';
    }
}
