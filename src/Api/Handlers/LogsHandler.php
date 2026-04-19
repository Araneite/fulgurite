<?php
// =============================================================================
// LogsHandler.php — /api/v1/logs
// =============================================================================

class LogsHandler {

    public static function activity(array $args): void {
        ApiAuth::requireScope('logs:read');
        [$page, $perPage] = ApiRequest::pageParams(50, 200);
        $offset = ($page - 1) * $perPage;
        $query = ApiRequest::query();
        $where = [];
        $params = [];
        if (!empty($query['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = (int) $query['user_id'];
        }
        if (!empty($query['action'])) {
            $where[] = 'action = ?';
            $params[] = (string) $query['action'];
        }
        if (!empty($query['severity'])) {
            $where[] = 'severity = ?';
            $params[] = (string) $query['severity'];
        }
        $whereSql = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
        $db = Database::getInstance();
        $countStmt = $db->prepare('SELECT COUNT(*) FROM activity_logs' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $stmt = $db->prepare('SELECT * FROM activity_logs' . $whereSql . ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p);
        }
        $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();
        ApiResponse::paginated($stmt->fetchAll(), $page, $perPage, $total);
    }

    public static function cron(array $args): void {
        ApiAuth::requireScope('logs:read');
        [$page, $perPage] = ApiRequest::pageParams(50, 200);
        $offset = ($page - 1) * $perPage;
        $db = Database::getInstance();
        $total = (int) $db->query('SELECT COUNT(*) FROM cron_log')->fetchColumn();
        $stmt = $db->prepare('SELECT * FROM cron_log ORDER BY ran_at DESC, id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        ApiResponse::paginated($stmt->fetchAll(), $page, $perPage, $total);
    }

    public static function apiTokens(array $args): void {
        ApiAuth::requireScope('logs:read');
        [$page, $perPage] = ApiRequest::pageParams(50, 200);
        $offset = ($page - 1) * $perPage;
        $db = Database::getInstance();
        $total = (int) $db->query('SELECT COUNT(*) FROM api_token_logs')->fetchColumn();
        $stmt = $db->prepare('SELECT * FROM api_token_logs ORDER BY id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        ApiResponse::paginated($stmt->fetchAll(), $page, $perPage, $total);
    }
}
