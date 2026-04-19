<?php

class UserManager {
    public const FORCE_ACTION_CHANGE_PASSWORD = 'change_password';
    public const FORCE_ACTION_SETUP_2FA = 'setup_2fa';
    public const FORCE_ACTION_REVIEW_PROFILE = 'review_profile';

    private const FORCE_ACTION_DEFINITIONS = [
        self::FORCE_ACTION_CHANGE_PASSWORD => [
            'label' => 'Changer le mot de passe',
            'description' => 'Demande a l utilisateur de choisir un nouveau mot de passe.',
        ],
        self::FORCE_ACTION_SETUP_2FA => [
            'label' => 'Configurer le 2FA',
            'description' => 'Bloque le parcours normal tant qu un second facteur n est pas configure.',
        ],
        self::FORCE_ACTION_REVIEW_PROFILE => [
            'label' => 'Verifier le profil',
            'description' => 'Demande a l utilisateur de completer et valider ses informations personnelles.',
        ],
    ];

    private const ALLOWED_FORCE_ACTIONS = [
        self::FORCE_ACTION_CHANGE_PASSWORD,
        self::FORCE_ACTION_SETUP_2FA,
        self::FORCE_ACTION_REVIEW_PROFILE,
    ];

    private const ALLOWED_SCOPE_MODES = ['all', 'selected'];
    private const ALLOWED_LOCALES = ['fr', 'en'];
    private const ALLOWED_START_PAGES = ['dashboard', 'repos', 'restores', 'backup_jobs', 'copy_jobs', 'stats', 'logs', 'notifications'];

    public static function getById(int $userId): ?array {
        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ? self::hydrateUser($user) : null;
    }

    public static function getByUsername(string $username): ?array {
        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ? self::hydrateUser($user) : null;
    }

    public static function getAll(): array {
        $rows = Database::getInstance()->query('SELECT * FROM users ORDER BY created_at')->fetchAll();
        return array_map([self::class, 'hydrateUser'], $rows);
    }

    public static function createUser(array $data): int {
        $db = Database::getInstance();
        $username = trim((string) ($data['username'] ?? ''));
        $passwordHash = (string) ($data['password_hash'] ?? '');
        $role = trim((string) ($data['role'] ?? ROLE_VIEWER));
        if ($username === '' || $passwordHash === '') {
            throw new InvalidArgumentException('Nom d utilisateur et mot de passe requis.');
        }

        $record = self::normalizeUserRecord($data);
        $stmt = $db->prepare("
            INSERT INTO users (
                username, password, role, first_name, last_name, email, phone, job_title,
                preferred_locale, preferred_timezone, preferred_start_page, preferred_theme, permissions_json,
                repo_scope_mode, repo_scope_json, host_scope_mode, host_scope_json,
                force_actions_json, suspended_until, suspension_reason, account_expires_at, primary_second_factor,
                admin_notes, created_by, password_set_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        $stmt->execute([
            $username,
            $passwordHash,
            $role,
            $record['first_name'],
            $record['last_name'],
            $record['email'],
            $record['phone'],
            $record['job_title'],
            $record['preferred_locale'],
            $record['preferred_timezone'],
            $record['preferred_start_page'],
            $record['preferred_theme'],
            $record['permissions_json'],
            $record['repo_scope_mode'],
            $record['repo_scope_json'],
            $record['host_scope_mode'],
            $record['host_scope_json'],
            $record['force_actions_json'],
            $record['suspended_until'],
            $record['suspension_reason'],
            $record['account_expires_at'],
            $record['primary_second_factor'],
            $record['admin_notes'],
            $record['created_by'],
        ]);

        return (int) $db->lastInsertId();
    }

    public static function updateProfile(int $userId, array $data): void {
        $record = self::normalizeUserRecord($data);
        Database::getInstance()->prepare("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                job_title = ?,
                preferred_locale = ?,
                preferred_timezone = ?,
                preferred_start_page = ?,
                preferred_theme = ?,
                admin_notes = ?
            WHERE id = ?
        ")->execute([
            $record['first_name'],
            $record['last_name'],
            $record['email'],
            $record['phone'],
            $record['job_title'],
            $record['preferred_locale'],
            $record['preferred_timezone'],
            $record['preferred_start_page'],
            $record['preferred_theme'],
            $record['admin_notes'],
            $userId,
        ]);
    }

    public static function updateRole(int $userId, string $role): void {
        Database::getInstance()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $userId]);
    }

    public static function updateAccessPolicy(int $userId, array $data): void {
        $record = self::normalizeUserRecord($data);
        Database::getInstance()->prepare("
            UPDATE users SET
                permissions_json = ?,
                repo_scope_mode = ?,
                repo_scope_json = ?,
                host_scope_mode = ?,
                host_scope_json = ?,
                force_actions_json = ?,
                suspended_until = ?,
                suspension_reason = ?,
                account_expires_at = ?
            WHERE id = ?
        ")->execute([
            $record['permissions_json'],
            $record['repo_scope_mode'],
            $record['repo_scope_json'],
            $record['host_scope_mode'],
            $record['host_scope_json'],
            $record['force_actions_json'],
            $record['suspended_until'],
            $record['suspension_reason'],
            $record['account_expires_at'],
            $userId,
        ]);
    }

    public static function markPasswordChanged(int $userId): void {
        Database::getInstance()->prepare("UPDATE users SET password_set_at = datetime('now') WHERE id = ?")->execute([$userId]);
        self::removeForceAction($userId, self::FORCE_ACTION_CHANGE_PASSWORD);
    }

    public static function addForceAction(int $userId, string $action): void {
        $user = self::getById($userId);
        if (!$user) {
            return;
        }

        $actions = self::normalizeForceActions($user['force_actions'] ?? []);
        if (!in_array($action, $actions, true)) {
            $actions[] = $action;
        }

        self::setForceActions($userId, $actions);
    }

    public static function removeForceAction(int $userId, string $action): void {
        $user = self::getById($userId);
        if (!$user) {
            return;
        }

        $actions = array_values(array_filter(
            self::normalizeForceActions($user['force_actions'] ?? []),
            static fn(string $item): bool => $item !== $action
        ));

        self::setForceActions($userId, $actions);
    }

    public static function setForceActions(int $userId, array $actions): void {
        $encoded = json_encode(self::normalizeForceActions($actions), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
        Database::getInstance()->prepare('UPDATE users SET force_actions_json = ? WHERE id = ?')->execute([$encoded, $userId]);
    }

    public static function listInvitations(): array {
        $rows = Database::getInstance()->query("
            SELECT i.*, u.username AS invited_by_username
            FROM user_invitations i
            LEFT JOIN users u ON u.id = i.invited_by
            WHERE i.revoked_at IS NULL AND i.accepted_at IS NULL
            ORDER BY i.created_at DESC
        ")->fetchAll();

        foreach ($rows as &$row) {
            $row = self::hydrateInvitation($row);
        }

        return $rows;
    }

    public static function createInvitation(array $data): array {
        $token = bin2hex(random_bytes(24));
        $record = self::normalizeUserRecord($data);
        $username = trim((string) ($data['username'] ?? ''));
        $role = trim((string) ($data['role'] ?? ROLE_VIEWER));
        $email = $record['email'];
        $expiresAt = self::normalizeDateTime($data['expires_at'] ?? date('Y-m-d H:i:s', time() + 7 * 86400));

        if ($username === '') {
            throw new InvalidArgumentException('Nom d utilisateur requis.');
        }
        if (self::getByUsername($username)) {
            throw new InvalidArgumentException('Ce nom d utilisateur existe deja.');
        }

        $pendingInviteStmt = Database::getInstance()->prepare("
            SELECT id
            FROM user_invitations
            WHERE username = ?
              AND revoked_at IS NULL
              AND accepted_at IS NULL
            LIMIT 1
        ");
        $pendingInviteStmt->execute([$username]);
        if ($pendingInviteStmt->fetch()) {
            throw new InvalidArgumentException('Une invitation active existe deja pour ce nom d utilisateur.');
        }

        Database::getInstance()->prepare("
            INSERT INTO user_invitations (
                username, email, first_name, last_name, phone, job_title, role,
                preferred_locale, preferred_timezone, preferred_start_page,
                permissions_json, repo_scope_mode, repo_scope_json, host_scope_mode, host_scope_json,
                force_actions_json, admin_notes, invited_by, token_hash, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $username,
            $email,
            $record['first_name'],
            $record['last_name'],
            $record['phone'],
            $record['job_title'],
            $role,
            $record['preferred_locale'],
            $record['preferred_timezone'],
            $record['preferred_start_page'],
            $record['permissions_json'],
            $record['repo_scope_mode'],
            $record['repo_scope_json'],
            $record['host_scope_mode'],
            $record['host_scope_json'],
            $record['force_actions_json'],
            $record['admin_notes'],
            $record['created_by'],
            hash('sha256', $token),
            $expiresAt,
        ]);

        return [
            'id' => (int) Database::getInstance()->lastInsertId(),
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public static function revokeInvitation(int $invitationId): void {
        Database::getInstance()->prepare("UPDATE user_invitations SET revoked_at = datetime('now') WHERE id = ?")->execute([$invitationId]);
    }

    public static function findInvitationByToken(string $token): ?array {
        $tokenHash = hash('sha256', $token);
        $stmt = Database::getInstance()->prepare("
            SELECT i.*, u.username AS invited_by_username
            FROM user_invitations i
            LEFT JOIN users u ON u.id = i.invited_by
            WHERE i.token_hash = ?
            LIMIT 1
        ");
        $stmt->execute([$tokenHash]);
        $invite = $stmt->fetch();
        if (!$invite) {
            return null;
        }

        return self::hydrateInvitation($invite);
    }

    public static function acceptInvitation(string $token, array $data): array {
        $invite = self::findInvitationByToken($token);
        if (!$invite) {
            throw new RuntimeException('Invitation introuvable.');
        }
        if (!empty($invite['revoked_at'])) {
            throw new RuntimeException('Cette invitation a ete revoquee.');
        }
        if (!empty($invite['accepted_at'])) {
            throw new RuntimeException('Cette invitation a deja ete utilisee.');
        }
        if (!empty($invite['expires_at']) && strtotime((string) $invite['expires_at']) < time()) {
            throw new RuntimeException('Cette invitation a expire.');
        }

        $passwordHash = (string) ($data['password_hash'] ?? '');
        if ($passwordHash === '') {
            throw new InvalidArgumentException('Mot de passe requis.');
        }

        $userId = self::createUser([
            'username' => $invite['username'],
            'password_hash' => $passwordHash,
            'role' => $invite['role'],
            'first_name' => $invite['first_name'],
            'last_name' => $invite['last_name'],
            'email' => $invite['email'],
            'phone' => $invite['phone'],
            'job_title' => $invite['job_title'],
            'preferred_locale' => $invite['preferred_locale'],
            'preferred_timezone' => $invite['preferred_timezone'],
            'preferred_start_page' => $invite['preferred_start_page'],
            'permissions_json' => $invite['permissions_json'],
            'repo_scope_mode' => $invite['repo_scope_mode'],
            'repo_scope_json' => $invite['repo_scope_json'],
            'host_scope_mode' => $invite['host_scope_mode'],
            'host_scope_json' => $invite['host_scope_json'],
            'force_actions_json' => $invite['force_actions_json'],
            'admin_notes' => $invite['admin_notes'],
            'created_by' => $invite['invited_by'] ?: null,
        ]);

        Database::getInstance()->prepare("UPDATE user_invitations SET accepted_at = datetime('now') WHERE id = ?")->execute([$invite['id']]);

        return [
            'user_id' => $userId,
            'username' => $invite['username'],
        ];
    }

    public static function getUserSecurityOverview(array $user): array {
        $db = Database::getInstance();
        $userId = (int) ($user['id'] ?? 0);
        $username = (string) ($user['username'] ?? '');

        $sessions = Auth::getActiveSessions($userId);
        $webauthnStmt = $db->prepare('SELECT id, name, created_at, counter, counter_supported, use_count, last_used_at FROM webauthn_credentials WHERE user_id = ? ORDER BY created_at DESC');
        $webauthnStmt->execute([$userId]);
        $webauthnKeys = $webauthnStmt->fetchAll();

        $activityStmt = $db->prepare("
            SELECT * FROM activity_logs
            WHERE user_id = ? OR username = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $activityStmt->execute([$userId, $username]);
        $activity = $activityStmt->fetchAll();

        $attemptsStmt = $db->prepare("
            SELECT * FROM login_attempts
            WHERE username = ?
            ORDER BY attempted_at DESC
            LIMIT 20
        ");
        $attemptsStmt->execute([$username]);
        $attempts = $attemptsStmt->fetchAll();

        return [
            'sessions' => $sessions,
            'webauthn_keys' => $webauthnKeys,
            'recent_activity' => $activity,
            'recent_login_attempts' => $attempts,
        ];
    }

    public static function displayName(array $user): string {
        $firstName = trim((string) ($user['first_name'] ?? ''));
        $lastName = trim((string) ($user['last_name'] ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);
        return $fullName !== '' ? $fullName : (string) ($user['username'] ?? '');
    }

    public static function status(array $user): array {
        $enabled = !isset($user['enabled']) || (int) $user['enabled'] === 1;
        if (!$enabled) {
            return ['key' => 'disabled', 'label' => 'Desactive', 'badge' => 'badge-red'];
        }

        $suspendedUntil = trim((string) ($user['suspended_until'] ?? ''));
        if ($suspendedUntil !== '' && strtotime($suspendedUntil) > time()) {
            return ['key' => 'suspended', 'label' => 'Suspendu', 'badge' => 'badge-yellow'];
        }

        $expiresAt = trim((string) ($user['account_expires_at'] ?? ''));
        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            return ['key' => 'expired', 'label' => 'Expire', 'badge' => 'badge-red'];
        }

        if ($expiresAt !== '') {
            return ['key' => 'temporary', 'label' => 'Temporaire', 'badge' => 'badge-blue'];
        }

        return ['key' => 'active', 'label' => 'Actif', 'badge' => 'badge-green'];
    }

    public static function summarizePermissions(array $user): string {
        $grants = [];
        foreach (Auth::resolvedPermissionsForUser($user) as $permission => $allowed) {
            if ($allowed) {
                $grants[] = $permission;
            }
        }

        return empty($grants) ? 'Aucune permission' : count($grants) . ' permission(s)';
    }

    public static function summarizeScope(array $user, string $type): string {
        $modeKey = $type . '_scope_mode';
        $jsonKey = $type . '_scope';
        $mode = (string) ($user[$modeKey] ?? 'all');
        $items = self::normalizeIdList($user[$jsonKey] ?? []);
        if ($mode !== 'selected') {
            return 'Tous';
        }

        return empty($items) ? 'Aucun' : count($items) . ' selectionne(s)';
    }

    public static function normalizePermissionMap(mixed $value): array {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        $knownPermissions = array_keys(AppConfig::permissionDefinitions());
        foreach ($value as $key => $allowed) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }
            $trimmedKey = trim($key);
            if (!in_array($trimmedKey, $knownPermissions, true)) {
                continue;
            }
            $normalized[$trimmedKey] = (bool) $allowed;
        }

        ksort($normalized);
        return $normalized;
    }

    public static function normalizeIdList(mixed $value): array {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            $id = (int) $item;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    public static function normalizeForceActions(mixed $value): array {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $actions = [];
        foreach ($value as $action) {
            $key = trim((string) $action);
            if (in_array($key, self::ALLOWED_FORCE_ACTIONS, true)) {
                $actions[$key] = $key;
            }
        }

        return array_values($actions);
    }

    public static function allowedForceActions(): array {
        return self::ALLOWED_FORCE_ACTIONS;
    }

    public static function forceActionDefinitions(): array {
        return self::FORCE_ACTION_DEFINITIONS;
    }

    public static function allowedLocales(): array {
        return self::ALLOWED_LOCALES;
    }

    public static function allowedStartPages(): array {
        return self::ALLOWED_START_PAGES;
    }

    public static function hydrateUser(array $user): array {
        $user['permissions'] = self::normalizePermissionMap($user['permissions_json'] ?? []);
        $user['repo_scope'] = self::normalizeIdList($user['repo_scope_json'] ?? []);
        $user['host_scope'] = self::normalizeIdList($user['host_scope_json'] ?? []);
        $user['force_actions'] = self::normalizeForceActions($user['force_actions_json'] ?? []);
        $user['primary_second_factor'] = StepUpAuth::normalizeFactor($user['primary_second_factor'] ?? '');
        $user['has_totp_secret'] = SensitiveEntitySecretManager::hasSecret(SensitiveEntitySecretManager::CONTEXT_USER_TOTP, $user);
        unset($user['totp_secret']);
        $user['display_name'] = self::displayName($user);
        $user['status_meta'] = self::status($user);
        return $user;
    }

    public static function setPrimarySecondFactor(int $userId, string $factor): void {
        $normalized = StepUpAuth::normalizeFactor($factor);
        Database::getInstance()
            ->prepare('UPDATE users SET primary_second_factor = ? WHERE id = ?')
            ->execute([$normalized, $userId]);
    }

    private static function hydrateInvitation(array $invite): array {
        $invite['permissions'] = self::normalizePermissionMap($invite['permissions_json'] ?? []);
        $invite['repo_scope'] = self::normalizeIdList($invite['repo_scope_json'] ?? []);
        $invite['host_scope'] = self::normalizeIdList($invite['host_scope_json'] ?? []);
        $invite['force_actions'] = self::normalizeForceActions($invite['force_actions_json'] ?? []);
        return $invite;
    }

    private static function normalizeUserRecord(array $data): array {
        $permissions = self::normalizePermissionMap($data['permissions_json'] ?? $data['permissions'] ?? []);
        $repoScopeMode = self::normalizeScopeMode($data['repo_scope_mode'] ?? 'all');
        $repoScope = self::normalizeIdList($data['repo_scope_json'] ?? $data['repo_scope'] ?? []);
        $hostScopeMode = self::normalizeScopeMode($data['host_scope_mode'] ?? 'all');
        $hostScope = self::normalizeIdList($data['host_scope_json'] ?? $data['host_scope'] ?? []);
        $forceActions = self::normalizeForceActions($data['force_actions_json'] ?? $data['force_actions'] ?? []);
        $timezone = trim((string) ($data['preferred_timezone'] ?? ''));
        $timezone = $timezone !== '' && AppConfig::isValidTimezone($timezone) ? $timezone : '';
        $locale = strtolower(trim((string) ($data['preferred_locale'] ?? 'fr')));
        if (!in_array($locale, self::ALLOWED_LOCALES, true)) {
            $locale = 'fr';
        }
        $startPage = trim((string) ($data['preferred_start_page'] ?? 'dashboard'));
        if (!in_array($startPage, self::ALLOWED_START_PAGES, true)) {
            $startPage = 'dashboard';
        }
        $theme = ThemeManager::resolveThemeId((string) ($data['preferred_theme'] ?? ThemeManager::DEFAULT_THEME_ID));

        return [
            'first_name' => self::normalizeText($data['first_name'] ?? ''),
            'last_name' => self::normalizeText($data['last_name'] ?? ''),
            'email' => self::normalizeEmail($data['email'] ?? ''),
            'phone' => self::normalizeText($data['phone'] ?? ''),
            'job_title' => self::normalizeText($data['job_title'] ?? ''),
            'preferred_locale' => $locale,
            'preferred_timezone' => $timezone,
            'preferred_start_page' => $startPage,
            'preferred_theme' => $theme,
            'permissions_json' => json_encode($permissions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            'repo_scope_mode' => $repoScopeMode,
            'repo_scope_json' => json_encode($repoScopeMode === 'selected' ? $repoScope : [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            'host_scope_mode' => $hostScopeMode,
            'host_scope_json' => json_encode($hostScopeMode === 'selected' ? $hostScope : [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            'force_actions_json' => json_encode($forceActions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            'suspended_until' => self::normalizeDateTime($data['suspended_until'] ?? null),
            'suspension_reason' => self::normalizeText($data['suspension_reason'] ?? ''),
            'account_expires_at' => self::normalizeDateTime($data['account_expires_at'] ?? null),
            'primary_second_factor' => StepUpAuth::normalizeFactor($data['primary_second_factor'] ?? ''),
            'admin_notes' => self::normalizeText($data['admin_notes'] ?? ''),
            'created_by' => isset($data['created_by']) && (int) $data['created_by'] > 0 ? (int) $data['created_by'] : null,
        ];
    }

    private static function normalizeScopeMode(mixed $value): string {
        $mode = strtolower(trim((string) $value));
        return in_array($mode, self::ALLOWED_SCOPE_MODES, true) ? $mode : 'all';
    }

    private static function normalizeText(mixed $value): string {
        return trim((string) $value);
    }

    private static function normalizeEmail(mixed $value): string {
        $email = trim((string) $value);
        if ($email === '') {
            return '';
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    private static function normalizeDateTime(mixed $value): ?string {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($raw))->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }
}
