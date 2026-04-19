<?php
// =============================================================================
// NotificationsHandler.php — /api/v1/notifications
// =============================================================================

class NotificationsHandler {

    public static function index(array $args): void {
        ApiAuth::requireScope('notifications:read');
        $userId = (int) ApiAuth::currentUser()['id'];
        [$page, $perPage] = ApiRequest::pageParams(25, 100);
        $offset = ($page - 1) * $perPage;
        $unreadOnly = !empty(ApiRequest::query('unread'));
        $items = AppNotificationManager::listForUser($userId, $perPage, $offset, $unreadOnly);
        $total = AppNotificationManager::countForUser($userId, $unreadOnly);
        ApiResponse::paginated($items, $page, $perPage, $total);
    }

    public static function unreadCount(array $args): void {
        ApiAuth::requireScope('notifications:read');
        $userId = (int) ApiAuth::currentUser()['id'];
        ApiResponse::ok(['unread' => AppNotificationManager::countForUser($userId, true)]);
    }

    public static function markRead(array $args): void {
        ApiAuth::requireScope('notifications:write');
        $userId = (int) ApiAuth::currentUser()['id'];
        $id = (int) $args['id'];
        if (!AppNotificationManager::markRead($userId, $id)) {
            ApiResponse::error(404, 'not_found', 'Notification introuvable');
        }
        ApiResponse::noContent();
    }

    public static function markAllRead(array $args): void {
        ApiAuth::requireScope('notifications:write');
        $userId = (int) ApiAuth::currentUser()['id'];
        $count = AppNotificationManager::markAllRead($userId);
        ApiResponse::ok(['updated' => $count]);
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('notifications:write');
        $userId = (int) ApiAuth::currentUser()['id'];
        $id = (int) $args['id'];
        if (!AppNotificationManager::delete($userId, $id)) {
            ApiResponse::error(404, 'not_found', 'Notification introuvable');
        }
        ApiResponse::noContent();
    }

    public static function deleteAllRead(array $args): void {
        ApiAuth::requireScope('notifications:write');
        $userId = (int) ApiAuth::currentUser()['id'];
        $count = AppNotificationManager::deleteAllRead($userId);
        ApiResponse::ok(['deleted' => $count]);
    }
}
