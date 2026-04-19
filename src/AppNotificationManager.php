<?php

class AppNotificationManager {
    public static function store(string $title, string $body, array $meta = []): array {
        self::purgeExpiredIfNeeded();

        $permission = trim((string) ($meta['recipient_permission'] ?? ''));
        $userIds = $permission !== ''
            ? self::recipientUserIdsByPermission($permission)
            : self::recipientUserIds();
        if (empty($userIds)) {
            return ['success' => false, 'output' => 'Aucun destinataire interne eligible'];
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO app_notifications (
                user_id, profile_key, event_key,
                context_type, context_id, context_name,
                title, body, severity, link_url, browser_delivery
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $profileKey = (string) ($meta['profile_key'] ?? 'system');
        $eventKey = (string) ($meta['event_key'] ?? 'alert');
        $contextType = (string) ($meta['context_type'] ?? $profileKey);
        $contextId = isset($meta['context_id']) ? (int) $meta['context_id'] : null;
        $contextName = (string) ($meta['context_name'] ?? $title);
        $severity = self::normalizeSeverity((string) ($meta['severity'] ?? 'info'));
        $linkUrl = self::normalizeLinkUrl((string) ($meta['link_url'] ?? self::defaultLinkUrl($contextType)));
        $browserDelivery = !empty($meta['browser_delivery']) ? 1 : 0;

        $db->beginTransaction();
        try {
            foreach ($userIds as $userId) {
                $stmt->execute([
                    $userId,
                    $profileKey,
                    $eventKey,
                    $contextType,
                    $contextId,
                    $contextName,
                    mb_substr($title, 0, 180),
                    mb_substr($body, 0, 4000),
                    $severity,
                    $linkUrl,
                    $browserDelivery,
                ]);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return ['success' => false, 'output' => 'Echec de stockage des notifications internes'];
        }

        return [
            'success' => true,
            'output' => $browserDelivery
                ? 'Notification interne enregistree et disponible pour le navigateur'
                : 'Notification interne enregistree',
            'count' => count($userIds),
        ];
    }

    public static function listForUser(int $userId, int $limit = 25, int $offset = 0, bool $unreadOnly = false): array {
        self::purgeExpiredIfNeeded();

        $sql = "
            SELECT *
            FROM app_notifications
            WHERE user_id = ?
        ";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";

        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(3, max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countForUser(int $userId, bool $unreadOnly = false): int {
        self::purgeExpiredIfNeeded();

        $sql = "SELECT COUNT(*) FROM app_notifications WHERE user_id = ?";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }

        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markAllRead(int $userId): int {
        $stmt = Database::getInstance()->prepare("
            UPDATE app_notifications
            SET is_read = 1, read_at = COALESCE(read_at, datetime('now'))
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    public static function markRead(int $userId, int $notificationId): bool {
        $stmt = Database::getInstance()->prepare("
            UPDATE app_notifications
            SET is_read = 1, read_at = COALESCE(read_at, datetime('now'))
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $userId, int $notificationId): bool {
        $stmt = Database::getInstance()->prepare("
            DELETE FROM app_notifications
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteAllRead(int $userId): int {
        $stmt = Database::getInstance()->prepare("
            DELETE FROM app_notifications
            WHERE user_id = ? AND is_read = 1
        ");
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    public static function getFeedForUser(int $userId, int $afterId = 0, int $limit = 10): array {
        self::purgeExpiredIfNeeded();

        $stmt = Database::getInstance()->prepare("
            SELECT id, title, body, severity, link_url, created_at
            FROM app_notifications
            WHERE user_id = ?
              AND browser_delivery = 1
              AND id > ?
            ORDER BY id ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, max(0, $afterId), PDO::PARAM_INT);
        $stmt->bindValue(3, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        $browserItems = $stmt->fetchAll();

        $latestIdStmt = Database::getInstance()->prepare("
            SELECT COALESCE(MAX(id), 0)
            FROM app_notifications
            WHERE user_id = ?
        ");
        $latestIdStmt->execute([$userId]);

        return [
            'unread_count' => self::countForUser($userId, true),
            'latest_id' => (int) $latestIdStmt->fetchColumn(),
            'browser_items' => array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'body' => (string) $row['body'],
                    'severity' => (string) ($row['severity'] ?? 'info'),
                    'link_url' => (string) ($row['link_url'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }, $browserItems),
        ];
    }

    public static function purgeExpired(?int $retentionDays = null): int {
        $days = $retentionDays ?? AppConfig::appNotificationsRetentionDays();
        $stmt = Database::getInstance()->prepare("
            DELETE FROM app_notifications
            WHERE created_at < datetime('now', '-' || ? || ' days')
        ");
        $stmt->execute([max(1, $days)]);
        Database::setSetting('app_notifications_last_cleanup_at', gmdate('Y-m-d H:i:s'));
        return $stmt->rowCount();
    }

    public static function purgeExpiredIfNeeded(int $minIntervalSeconds = 21600): int {
        static $done = false;
        if ($done) {
            return 0;
        }
        $done = true;

        $lastCleanup = trim(Database::getSetting('app_notifications_last_cleanup_at', ''));
        if ($lastCleanup !== '') {
            $timestamp = strtotime($lastCleanup . ' UTC');
            if ($timestamp !== false && (time() - $timestamp) < $minIntervalSeconds) {
                return 0;
            }
        }

        return self::purgeExpired();
    }

    private static function recipientUserIdsByPermission(string $permission): array {
        $stmt = Database::getInstance()->query("
            SELECT id, role, permissions_json
            FROM users
            WHERE enabled IS NULL OR enabled = 1
        ");

        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $user = [
                'role' => (string) ($row['role'] ?? ROLE_VIEWER),
                'permissions' => $row['permissions_json'] ?? '{}',
            ];
            $resolved = Auth::resolvedPermissionsForUser($user);
            if (!empty($resolved[$permission])) {
                $ids[] = (int) $row['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    private static function recipientUserIds(): array {
        $minimumLevel = AppConfig::getRoleLevel(ROLE_OPERATOR, 20);
        $stmt = Database::getInstance()->query("
            SELECT id, role
            FROM users
            WHERE enabled IS NULL OR enabled = 1
        ");

        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $role = (string) ($row['role'] ?? ROLE_VIEWER);
            if (AppConfig::getRoleLevel($role, 0) >= $minimumLevel) {
                $ids[] = (int) $row['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    private static function normalizeSeverity(string $severity): string {
        $value = strtolower(trim($severity));
        return in_array($value, ['info', 'warning', 'critical', 'success'], true) ? $value : 'info';
    }

    private static function normalizeLinkUrl(string $linkUrl): string {
        if ($linkUrl === '') {
            return routePath('/notifications.php');
        }

        if (str_starts_with($linkUrl, 'http://') || str_starts_with($linkUrl, 'https://') || str_starts_with($linkUrl, '/')) {
            return $linkUrl;
        }

        return routePath($linkUrl);
    }

    private static function defaultLinkUrl(string $contextType): string {
        return match ($contextType) {
            'repo' => routePath('/repos.php'),
            'backup_job' => routePath('/backup_jobs.php'),
            'copy_job' => routePath('/copy_jobs.php'),
            'scheduler_task' => routePath('/scheduler.php'),
            'security', 'login' => routePath('/logs.php'),
            default => routePath('/notifications.php'),
        };
    }
}
