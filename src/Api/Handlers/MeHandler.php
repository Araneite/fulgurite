<?php
// =============================================================================
// MeHandler.php — /api/v1/me
// =============================================================================

class MeHandler {

    public static function index(array $args): void {
        ApiAuth::requireScope('me:read');
        $ctx = ApiAuth::context();
        $user = $ctx['user'];
        ApiResponse::ok([
            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'email' => $user['email'] ?? null,
                'first_name' => $user['first_name'] ?? null,
                'last_name' => $user['last_name'] ?? null,
                'permissions' => array_keys(array_filter($ctx['permissions'])),
                'repo_scope_mode' => $user['repo_scope_mode'] ?? 'all',
                'host_scope_mode' => $user['host_scope_mode'] ?? 'all',
            ],
            'token' => [
                'id' => (int) $ctx['token']['id'],
                'name' => $ctx['token']['name'],
                'scopes' => $ctx['scopes'],
                'read_only' => (bool) $ctx['token']['read_only'],
                'rate_limit_per_minute' => (int) $ctx['token']['rate_limit_per_minute'],
                'expires_at' => $ctx['token']['expires_at'] ?? null,
            ],
            'server' => [
                'name' => AppConfig::appName(),
                'version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
                'time' => gmdate('c'),
            ],
        ]);
    }
}
