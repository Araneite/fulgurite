<?php
// =============================================================================
// UsersHandler.php — /api/v1/users (admin)
// =============================================================================

class UsersHandler {

    public static function publicView(array $user): array {
        return [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'] ?? 'viewer',
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'email' => $user['email'] ?? null,
            'job_title' => $user['job_title'] ?? null,
            'preferred_locale' => $user['preferred_locale'] ?? null,
            'preferred_timezone' => $user['preferred_timezone'] ?? null,
            'repo_scope_mode' => $user['repo_scope_mode'] ?? 'all',
            'host_scope_mode' => $user['host_scope_mode'] ?? 'all',
            'suspended_until' => $user['suspended_until'] ?? null,
            'account_expires_at' => $user['account_expires_at'] ?? null,
            'last_login_at' => $user['last_login_at'] ?? null,
            'created_at' => $user['created_at'] ?? null,
        ];
    }

    public static function index(array $args): void {
        ApiAuth::requireScope('users:read');
        $users = UserManager::getAll();
        ApiResponse::ok(array_map([self::class, 'publicView'], $users));
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('users:read');
        $user = UserManager::getById((int) $args['id']);
        if (!$user) ApiResponse::error(404, 'not_found', 'Utilisateur introuvable');
        ApiResponse::ok(self::publicView($user));
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('users:write');
        $body = ApiRequest::body();
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        if ($username === '' || strlen($password) < 8) {
            ApiResponse::error(422, 'validation_error', 'username et password (>=8) requis');
        }
        if (UserManager::getByUsername($username)) {
            ApiResponse::error(409, 'user_exists', 'Utilisateur deja existant');
        }
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);

        // Cap role to caller level
        $requestedRole = trim((string) ($body['role'] ?? ROLE_VIEWER));
        $knownRoles = array_map(fn($r) => $r['key'], AppConfig::getRoles());
        if (!in_array($requestedRole, $knownRoles, true)) {
            $requestedRole = ROLE_VIEWER;
        }
        $callerLevel    = AppConfig::getRoleLevel((string) (ApiAuth::currentUser()['role'] ?? ''), 0);
        $requestedLevel = AppConfig::getRoleLevel($requestedRole, PHP_INT_MAX);
        if ($requestedLevel > $callerLevel) {
            ApiResponse::error(403, 'role_escalation_forbidden',
                'Impossible de créer un utilisateur avec un rôle supérieur au vôtre.');
        }
        $body['role'] = $requestedRole;

        $body['username'] = $username;
        $body['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $body['created_by'] = (int) ApiAuth::currentUser()['id'];
        try {
            $id = UserManager::createUser($body);
        } catch (InvalidArgumentException $e) {
            ApiResponse::error(422, 'validation_error', $e->getMessage());
        }
        ApiResponse::created(self::publicView(UserManager::getById($id)));
    }

    public static function update(array $args): void {
        ApiAuth::requireScope('users:write');
        $id = (int) $args['id'];
        $user = UserManager::getById($id);
        if (!$user) ApiResponse::error(404, 'not_found', 'Utilisateur introuvable');
        $body = ApiRequest::body();

        if (array_key_exists('role', $body)) {
            // Validate role value
            $knownRoles = array_map(fn($r) => $r['key'], AppConfig::getRoles());
            if (!in_array((string) $body['role'], $knownRoles, true)) {
                ApiResponse::error(422, 'invalid_role', 'Rôle inconnu');
            }
            // Block self role change
            if ($id === (int) ApiAuth::currentUser()['id']) {
                ApiResponse::error(403, 'self_role_change_forbidden', 'Vous ne pouvez pas modifier votre propre rôle via l\'API');
            }
            // Block role escalation beyond caller's own level
            $callerLevel  = AppConfig::getRoleLevel((string) (ApiAuth::currentUser()['role'] ?? ''), 0);
            $requestedLevel = AppConfig::getRoleLevel((string) $body['role'], PHP_INT_MAX);
            if ($requestedLevel > $callerLevel) {
                ApiResponse::error(403, 'role_escalation_forbidden', 'Impossible d\'attribuer un rôle supérieur au vôtre');
            }
        }

        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        UserManager::updateProfile($id, array_merge($user, $body));
        if (!empty($body['role'])) {
            UserManager::updateRole($id, (string) $body['role']);
        }
        if (array_key_exists('permissions_json', $body) || array_key_exists('repo_scope_mode', $body) || array_key_exists('host_scope_mode', $body)) {
            UserManager::updateAccessPolicy($id, array_merge($user, $body));
        }
        ApiResponse::ok(self::publicView(UserManager::getById($id)));
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('users:write');
        $id = (int) $args['id'];
        $user = UserManager::getById($id);
        if (!$user) ApiResponse::error(404, 'not_found', 'Utilisateur introuvable');
        if ($id === (int) ApiAuth::currentUser()['id']) {
            ApiResponse::error(409, 'self_delete_forbidden', 'Impossible de supprimer son propre compte via API');
        }
        if ((string) ($user['role'] ?? '') === ROLE_ADMIN) {
            $stmt = Database::getInstance()->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
            $stmt->execute([ROLE_ADMIN]);
            $adminCount = (int) $stmt->fetchColumn();
            if ($adminCount <= 1) {
                ApiResponse::error(409, 'last_admin',
                    'Impossible de supprimer le dernier compte administrateur.');
            }
        }
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        Database::getInstance()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        ApiResponse::noContent();
    }
}
