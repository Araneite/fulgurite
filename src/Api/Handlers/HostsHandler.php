<?php
// =============================================================================
// HostsHandler.php — /api/v1/hosts
// =============================================================================

class HostsHandler {

    public static function publicView(array $host): array {
        return [
            'id' => (int) $host['id'],
            'name' => $host['name'],
            'hostname' => $host['hostname'],
            'port' => (int) $host['port'],
            'user' => $host['user'],
            'ssh_key_id' => isset($host['ssh_key_id']) ? (int) $host['ssh_key_id'] : null,
            'description' => $host['description'] ?? null,
            'restore_original_enabled' => !empty($host['restore_original_enabled']) ? true : false,
            'created_at' => $host['created_at'] ?? null,
        ];
    }

    public static function index(array $args): void {
        ApiAuth::requireScope('hosts:read');
        $hosts = ApiAuth::filterAllowedHosts(HostManager::getAll());
        ApiResponse::ok(array_map([self::class, 'publicView'], $hosts));
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('hosts:read');
        $id = (int) $args['id'];
        ApiAuth::requireHostAccess($id);
        $host = HostManager::getById($id);
        if (!$host) ApiResponse::error(404, 'not_found', 'Hote introuvable');
        ApiResponse::ok(self::publicView($host));
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('hosts:write');
        ApiAuth::requireHostCreateAccess();
        $body = ApiRequest::body();
        $name = trim((string) ($body['name'] ?? ''));
        $hostname = trim((string) ($body['hostname'] ?? ''));
        if ($name === '' || $hostname === '') {
            ApiResponse::error(422, 'validation_error', 'Champs name et hostname requis');
        }
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $id = HostManager::add(
            $name,
            $hostname,
            (int) ($body['port'] ?? 22),
            (string) ($body['user'] ?? 'root'),
            isset($body['ssh_key_id']) ? (int) $body['ssh_key_id'] : null,
            isset($body['restore_managed_root']) ? (string) $body['restore_managed_root'] : null,
            !empty($body['restore_original_enabled']) ? true : false,
            (string) ($body['sudo_password'] ?? ''),
            (string) ($body['description'] ?? ''),
        );
        ApiResponse::created(self::publicView(HostManager::getById($id)));
    }

    public static function update(array $args): void {
        ApiAuth::requireScope('hosts:write');
        $id = (int) $args['id'];
        ApiAuth::requireHostAccess($id);
        $host = HostManager::getById($id);
        if (!$host) ApiResponse::error(404, 'not_found', 'Hote introuvable');
        HostManager::update($id, ApiRequest::body());
        ApiResponse::ok(self::publicView(HostManager::getById($id)));
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('hosts:write');
        $id = (int) $args['id'];
        ApiAuth::requireHostAccess($id);
        $host = HostManager::getById($id);
        if (!$host) ApiResponse::error(404, 'not_found', 'Hote introuvable');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        HostManager::delete($id);
        ApiResponse::noContent();
    }

    public static function test(array $args): void {
        ApiAuth::requireScope('hosts:read');
        $id = (int) $args['id'];
        ApiAuth::requireHostAccess($id);
        $host = HostManager::getById($id);
        if (!$host) ApiResponse::error(404, 'not_found', 'Hote introuvable');
        ApiResponse::ok(HostManager::testConnection($host));
    }
}
