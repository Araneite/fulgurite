<?php
// =============================================================================
// SshKeysHandler.php — /api/v1/ssh-keys
// =============================================================================

class SshKeysHandler {

    public static function publicView(array $key): array {
        return [
            'id' => (int) $key['id'],
            'name' => $key['name'],
            'host' => $key['host'],
            'user' => $key['user'],
            'port' => (int) $key['port'],
            'public_key' => $key['public_key'] ?? null,
            'description' => $key['description'] ?? null,
            'created_at' => $key['created_at'] ?? null,
        ];
    }

    public static function index(array $args): void {
        ApiAuth::requireScope('ssh_keys:read');
        ApiResponse::ok(array_map([self::class, 'publicView'], SshKeyManager::getAll()));
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('ssh_keys:read');
        $key = SshKeyManager::getById((int) $args['id']);
        if (!$key) ApiResponse::error(404, 'not_found', 'Cle SSH introuvable');
        ApiResponse::ok(self::publicView($key));
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('ssh_keys:write');
        $body = ApiRequest::body();
        $name = trim((string) ($body['name'] ?? ''));
        $host = trim((string) ($body['host'] ?? ''));
        $user = trim((string) ($body['user'] ?? 'root'));
        $port = (int) ($body['port'] ?? 22);
        if ($name === '' || $host === '') {
            ApiResponse::error(422, 'validation_error', 'Champs name et host requis');
        }
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $description = (string) ($body['description'] ?? '');
        $privateKey = (string) ($body['private_key'] ?? '');
        if ($privateKey !== '') {
            $result = SshKeyManager::import($name, $host, $user, $port, $privateKey, $description);
        } else {
            $result = SshKeyManager::generate($name, $host, $user, $port, $description);
        }
        if (empty($result['success'])) {
            ApiResponse::error(500, 'ssh_key_failed', $result['error'] ?? 'Echec de creation');
        }
        $created = SshKeyManager::getById((int) $result['id']);
        ApiResponse::created(self::publicView($created));
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('ssh_keys:write');
        $id = (int) $args['id'];
        $key = SshKeyManager::getById($id);
        if (!$key) ApiResponse::error(404, 'not_found', 'Cle SSH introuvable');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        SshKeyManager::delete($id);
        ApiResponse::noContent();
    }

    public static function test(array $args): void {
        ApiAuth::requireScope('ssh_keys:read');
        $id = (int) $args['id'];
        $key = SshKeyManager::getById($id);
        if (!$key) ApiResponse::error(404, 'not_found', 'Cle SSH introuvable');
        ApiResponse::ok(SshKeyManager::test($id));
    }
}
