<?php
// =============================================================================
// WebhooksHandler.php — /api/v1/webhooks
// =============================================================================

class WebhooksHandler {

    public static function index(array $args): void {
        ApiAuth::requireScope('webhooks:read');
        ApiResponse::ok(array_map([ApiWebhookManager::class, 'publicView'], ApiWebhookManager::getAll()));
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('webhooks:read');
        $hook = ApiWebhookManager::getById((int) $args['id']);
        if (!$hook) ApiResponse::error(404, 'not_found', 'Webhook introuvable');
        ApiResponse::ok(ApiWebhookManager::publicView($hook));
    }

    public static function events(array $args): void {
        ApiAuth::requireScope('webhooks:read');
        ApiResponse::ok(['events' => ApiWebhookManager::EVENTS]);
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('webhooks:write');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        try {
            $hook = ApiWebhookManager::create(ApiRequest::body(), (int) ApiAuth::currentUser()['id']);
        } catch (InvalidArgumentException $e) {
            ApiResponse::error(422, 'validation_error', $e->getMessage());
        }
        $view = ApiWebhookManager::publicView($hook);
        $view['secret'] = $hook['revealed_secret'] ?? ''; // one-time reveal
        ApiResponse::created($view);
    }

    public static function update(array $args): void {
        ApiAuth::requireScope('webhooks:write');
        $id = (int) $args['id'];
        $hook = ApiWebhookManager::getById($id);
        if (!$hook) ApiResponse::error(404, 'not_found', 'Webhook introuvable');
        try {
            $updated = ApiWebhookManager::update($id, ApiRequest::body());
        } catch (InvalidArgumentException $e) {
            ApiResponse::error(422, 'validation_error', $e->getMessage());
        }
        ApiResponse::ok(ApiWebhookManager::publicView($updated));
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('webhooks:write');
        $id = (int) $args['id'];
        if (!ApiWebhookManager::getById($id)) ApiResponse::error(404, 'not_found', 'Webhook introuvable');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        ApiWebhookManager::delete($id);
        ApiResponse::noContent();
    }

    public static function test(array $args): void {
        ApiAuth::requireScope('webhooks:write');
        $id = (int) $args['id'];
        $hook = ApiWebhookManager::getById($id);
        if (!$hook) ApiResponse::error(404, 'not_found', 'Webhook introuvable');
        $result = ApiWebhookManager::send($hook, 'webhook.test', ['message' => 'Test webhook delivery', 'at' => gmdate('c')]);
        ApiResponse::ok($result);
    }

    public static function deliveries(array $args): void {
        ApiAuth::requireScope('webhooks:read');
        $id = (int) $args['id'];
        if (!ApiWebhookManager::getById($id)) ApiResponse::error(404, 'not_found', 'Webhook introuvable');
        [$page, $perPage] = ApiRequest::pageParams(30, 200);
        $offset = ($page - 1) * $perPage;
        $db = Database::getInstance();
        $countStmt = $db->prepare('SELECT COUNT(*) FROM api_webhook_deliveries WHERE webhook_id = ?');
        $countStmt->execute([$id]);
        $total = (int) $countStmt->fetchColumn();
        $stmt = $db->prepare('SELECT * FROM api_webhook_deliveries WHERE webhook_id = ? ORDER BY id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        ApiResponse::paginated($stmt->fetchAll(), $page, $perPage, $total);
    }
}
