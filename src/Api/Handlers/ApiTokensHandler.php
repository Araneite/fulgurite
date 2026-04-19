<?php
// =============================================================================
// ApiTokensHandler.php — /api/v1/api-tokens
// =============================================================================

class ApiTokensHandler {

    public static function index(array $args): void {
        ApiAuth::requireScope('api_tokens:read');
        $userId = (int) ApiAuth::currentUser()['id'];
        $tokens = ApiTokenManager::listForUser($userId);
        ApiResponse::ok(array_map([ApiTokenManager::class, 'publicView'], $tokens));
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('api_tokens:read');
        $token = ApiTokenManager::getById((int) $args['id']);
        if (!$token) ApiResponse::error(404, 'not_found', 'Token introuvable');
        if ((int) $token['user_id'] !== (int) ApiAuth::currentUser()['id']) {
            ApiResponse::error(403, 'forbidden', 'Vous ne pouvez pas voir ce token');
        }
        ApiResponse::ok(ApiTokenManager::publicView($token));
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('api_tokens:write');
        $userId = (int) ApiAuth::currentUser()['id'];
        $body = ApiRequest::body();
        try {
            $result = ApiTokenManager::create($userId, $body, $userId);
        } catch (InvalidArgumentException $e) {
            ApiResponse::error(422, 'validation_error', $e->getMessage());
            return;
        }
        $view = ApiTokenManager::publicView($result['token']);
        $view['secret'] = $result['secret']; // one-time reveal
        ApiResponse::created($view);
    }

    public static function update(array $args): void {
        ApiAuth::requireScope('api_tokens:write');
        $token = ApiTokenManager::getById((int) $args['id']);
        if (!$token || (int) $token['user_id'] !== (int) ApiAuth::currentUser()['id']) {
            ApiResponse::error(404, 'not_found', 'Token introuvable');
        }
        $updated = ApiTokenManager::update((int) $args['id'], ApiRequest::body());
        ApiResponse::ok(ApiTokenManager::publicView($updated));
    }

    public static function revoke(array $args): void {
        ApiAuth::requireScope('api_tokens:write');
        $token = ApiTokenManager::getById((int) $args['id']);
        if (!$token || (int) $token['user_id'] !== (int) ApiAuth::currentUser()['id']) {
            ApiResponse::error(404, 'not_found', 'Token introuvable');
        }
        ApiTokenManager::revoke((int) $args['id'], (string) ApiRequest::input('reason', 'revoked via API'));
        ApiResponse::noContent();
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('api_tokens:write');
        $token = ApiTokenManager::getById((int) $args['id']);
        if (!$token || (int) $token['user_id'] !== (int) ApiAuth::currentUser()['id']) {
            ApiResponse::error(404, 'not_found', 'Token introuvable');
        }
        ApiTokenManager::delete((int) $args['id']);
        ApiResponse::noContent();
    }
}
