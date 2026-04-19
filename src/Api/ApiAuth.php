<?php
// =============================================================================
// ApiAuth.php - Authentication and access control for the public API
// =============================================================================

class ApiAuth {

    private static ?array $context = null;

    /**
     * Authentifie the request courante. Termine the request with a code d error
     * if authentication fails.
     *
     * @return array Auth context (token, user, effective scopes, permissions)
     */
    public static function authenticate(): array {
        if (self::$context !== null) {
            return self::$context;
        }

        if (!AppConfig::isApiEnabled()) {
            ApiResponse::error(503, 'api_disabled', 'L API publique est desactivee dans les parametres.');
        }

        $rawToken = self::extractBearerToken();
        if ($rawToken === null) {
            ApiResponse::error(401, 'missing_token', 'Header Authorization: Bearer requis.');
        }

        $token = ApiTokenManager::findByPlainSecret($rawToken);
        if (!$token) {
            ApiResponse::error(401, 'invalid_token', 'Token invalide.');
        }

        if (!empty($token['revoked_at'])) {
            ApiResponse::error(401, 'token_revoked', 'Ce token a ete revoque.');
        }
        if (!empty($token['is_expired'])) {
            ApiResponse::error(401, 'token_expired', 'Ce token est expire.');
        }

        $ip = self::clientIp();
        if (!self::ipAllowed($token, $ip)) {
            ApiResponse::error(403, 'ip_not_allowed', 'Cette adresse IP n est pas autorisee pour ce token.');
        }

        $user = UserManager::getById((int) $token['user_id']);
        if (!$user) {
            ApiResponse::error(401, 'user_missing', 'Utilisateur lie au token introuvable.');
        }
        if (!empty($user['suspended_until']) && strtotime($user['suspended_until']) > time()) {
            ApiResponse::error(403, 'user_suspended', 'L utilisateur lie au token est suspendu.');
        }
        if (!empty($user['account_expires_at']) && strtotime($user['account_expires_at']) < time()) {
            ApiResponse::error(403, 'user_expired', 'Le compte utilisateur lie au token est expire.');
        }
        if (isset($user['enabled']) && (int) $user['enabled'] === 0) {
            ApiResponse::error(403, 'user_disabled', 'Le compte utilisateur lie au token est desactive.');
        }

        // Politique force_actions : if a action of security bloquante is en attente, the API is refusee.
        $forceActions = UserManager::normalizeForceActions($user['force_actions_json'] ?? []);
        $blockingForceActions = [
            UserManager::FORCE_ACTION_SETUP_2FA,
            UserManager::FORCE_ACTION_CHANGE_PASSWORD,
        ];
        if (!empty(array_intersect($forceActions, $blockingForceActions))) {
            ApiResponse::error(403, 'forced_action_pending',
                'Une action de securite obligatoire est en attente. Connectez-vous a l\'interface web pour la completer.');
        }

        $userPermissions = Auth::resolvedPermissionsForUser($user);
        $effectiveScopes = ApiScopes::filterAllowedForUser($token['scopes'] ?? [], $userPermissions);

        // Rate limiting by token
        self::enforceRateLimit($token);

        self::$context = [
            'token' => $token,
            'user' => $user,
            'permissions' => $userPermissions,
            'scopes' => $effectiveScopes,
            'ip' => $ip,
        ];

        // Record usage (last_used_at / ip)
        ApiTokenManager::recordUsage((int) $token['id'], $ip);

        return self::$context;
    }

    public static function context(): array {
        return self::$context ?? self::authenticate();
    }

    public static function currentToken(): array {
        return self::context()['token'];
    }

    public static function currentUser(): array {
        return self::context()['user'];
    }

    public static function currentScopes(): array {
        return self::context()['scopes'];
    }

    /**
     * Terminate request with 403 if scope is not granted to current token.
     */
    public static function requireScope(string $scope): void {
        $ctx = self::context();
        if (!in_array($scope, $ctx['scopes'], true)) {
            ApiResponse::error(403, 'insufficient_scope', "Scope manquant : $scope", ['required_scope' => $scope]);
        }

        // Read-only => stuckr toute action write
        if (!empty($ctx['token']['read_only']) && !ApiScopes::isReadOnly($scope)) {
            ApiResponse::error(403, 'read_only_token', 'Ce token est en lecture seule.');
        }

        // Also checks the underlying user permission (can change after token creation)
        $perm = ApiScopes::requiredPermission($scope);
        if ($perm !== null && empty($ctx['permissions'][$perm])) {
            ApiResponse::error(403, 'permission_revoked', "Permission utilisateur revoquee : $perm");
        }
    }

    public static function hasScope(string $scope): bool {
        $ctx = self::context();
        return in_array($scope, $ctx['scopes'], true);
    }

    public static function allowedRepoIds(): ?array {
        return self::allowedScopedIds('repo_scope_mode', 'repo_scope_json');
    }

    public static function allowedHostIds(): ?array {
        return self::allowedScopedIds('host_scope_mode', 'host_scope_json');
    }

    public static function hasResourceScopeRestriction(): bool {
        return self::allowedRepoIds() !== null || self::allowedHostIds() !== null;
    }

    public static function filterAllowedRepos(array $rows, string $idKey = 'id'): array {
        return self::filterRowsByAllowedIds($rows, $idKey, self::allowedRepoIds());
    }

    public static function filterAllowedHosts(array $rows, string $idKey = 'id'): array {
        return self::filterRowsByAllowedIds($rows, $idKey, self::allowedHostIds());
    }

    /** checks l access a a repo specifique en respectant the scoping user. */
    public static function requireRepoAccess(int $repoId): void {
        $allowed = self::allowedRepoIds();
        if ($allowed !== null && !in_array($repoId, $allowed, true)) {
            ApiResponse::error(403, 'repo_not_in_scope', 'Ce depot est hors du scope utilisateur.');
        }
    }

    public static function requireHostAccess(int $hostId): void {
        $allowed = self::allowedHostIds();
        if ($allowed !== null && !in_array($hostId, $allowed, true)) {
            ApiResponse::error(403, 'host_not_in_scope', 'Cet hote est hors du scope utilisateur.');
        }
    }

    public static function requireRepoCreateAccess(): void {
        if (self::allowedRepoIds() !== null) {
            ApiResponse::error(403, 'repo_create_not_in_scope', 'Creation de depot impossible avec un scope depots limite.');
        }
    }

    public static function requireHostCreateAccess(): void {
        if (self::allowedHostIds() !== null) {
            ApiResponse::error(403, 'host_create_not_in_scope', 'Creation d hote impossible avec un scope hotes limite.');
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function clientIp(): string {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private static function extractBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('getallheaders')) {
            $headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
            $header = $headers['authorization'] ?? '';
        }
        if (!is_string($header) || $header === '') return null;
        if (stripos($header, 'Bearer ') !== 0) return null;
        $token = trim(substr($header, 7));
        return $token !== '' ? $token : null;
    }

    private static function ipAllowed(array $token, string $ip): bool {
        $allowedIps = $token['allowed_ips'] ?? [];
        if (empty($allowedIps)) return true;

        foreach ($allowedIps as $entry) {
            if (self::ipMatches($ip, (string) $entry)) {
                return true;
            }
        }
        return false;
    }

    /** checks if a IP correspond a a entry (IP exacte or CIDR). */
    private static function ipMatches(string $ip, string $entry): bool {
        if ($entry === $ip) return true;
        if (str_contains($entry, '/')) {
            [$subnet, $bits] = explode('/', $entry, 2);
            $bits = (int) $bits;
            $ipBin = @inet_pton($ip);
            $subnetBin = @inet_pton($subnet);
            if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
                return false;
            }
            $bytes = intdiv($bits, 8);
            $remainder = $bits % 8;
            if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }
            if ($remainder !== 0) {
                $mask = chr(0xff << (8 - $remainder) & 0xff);
                if ((ord($ipBin[$bytes]) & ord($mask)) !== (ord($subnetBin[$bytes]) & ord($mask))) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    private static function enforceRateLimit(array $token): void {
        $limit = (int) ($token['rate_limit_per_minute'] ?? 120);
        if ($limit <= 0) {
            return;
        }

        $windowSeconds = AppConfig::apiTokenRateLimitWindowSeconds();
        $tokenId = (int) ($token['id'] ?? 0);
        if ($tokenId <= 0) {
            return;
        }

        $decision = self::rateLimitFromApcu($tokenId, $limit, $windowSeconds);
        if ($decision === null && AppConfig::apiTokenRateLimitDbFallbackEnabled()) {
            $decision = self::rateLimitFromDatabase($tokenId, $limit, $windowSeconds);
        }

        if ($decision === null) {
            $decision = [
                'hits' => 0,
                'remaining' => max(0, $limit),
                'reset_at' => time() + $windowSeconds,
                'retry_after' => 0,
                'limited' => false,
            ];
        }

        $remaining = max(0, (int) ($decision['remaining'] ?? 0));
        $resetAt = max(time(), (int) ($decision['reset_at'] ?? (time() + $windowSeconds)));
        $retryAfter = max(0, (int) ($decision['retry_after'] ?? 0));
        $limited = (bool) ($decision['limited'] ?? false);

        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $resetAt);

        if ($limited) {
            header('Retry-After: ' . max(1, $retryAfter));
            ApiResponse::error(429, 'rate_limited', 'Limite de requetes par minute atteinte pour ce token.');
        }
    }

    private static function rateLimitFromApcu(int $tokenId, int $limit, int $windowSeconds): ?array {
        if (!AppConfig::apiTokenRateLimitApcuEnabled() || !self::isApcuAvailable()) {
            return null;
        }

        $cacheKey = 'fulgurite:api_token_rate_limit:' . hash('sha256', $tokenId . '|' . $limit . '|' . $windowSeconds);
        $lockKey = $cacheKey . ':lock';
        if (!apcu_add($lockKey, 1, AppConfig::apiTokenRateLimitApcuLockTtlSeconds())) {
            return null;
        }

        try {
            $now = time();
            $windowStart = $now - $windowSeconds + 1;
            $slots = apcu_fetch($cacheKey);
            if (!is_array($slots)) {
                $slots = [];
            }

            foreach ($slots as $ts => $count) {
                $ts = (int) $ts;
                $count = (int) $count;
                if ($ts < $windowStart || $count <= 0) {
                    unset($slots[$ts]);
                    continue;
                }
                $slots[$ts] = $count;
            }

            $hitsBeforeCurrent = array_sum($slots);
            if ($hitsBeforeCurrent >= $limit) {
                $oldestSlot = self::oldestRateLimitSlot($slots, $now);
                $retryAfter = max(1, ($oldestSlot + $windowSeconds) - $now);
                apcu_store($cacheKey, $slots, $windowSeconds + AppConfig::apiTokenRateLimitApcuTtlPaddingSeconds());
                return [
                    'hits' => $hitsBeforeCurrent,
                    'remaining' => 0,
                    'reset_at' => $oldestSlot + $windowSeconds,
                    'retry_after' => $retryAfter,
                    'limited' => true,
                ];
            }

            $slot = (string) $now;
            $slots[$slot] = ((int) ($slots[$slot] ?? 0)) + 1;
            $hitsAfterCurrent = $hitsBeforeCurrent + 1;
            $oldestSlot = self::oldestRateLimitSlot($slots, $now);
            apcu_store($cacheKey, $slots, $windowSeconds + AppConfig::apiTokenRateLimitApcuTtlPaddingSeconds());
            return [
                'hits' => $hitsAfterCurrent,
                'remaining' => max(0, $limit - $hitsBeforeCurrent),
                'reset_at' => max($now, $oldestSlot + $windowSeconds),
                'retry_after' => 0,
                'limited' => false,
            ];
        } catch (Throwable $e) {
            SecretRedaction::errorLog(
                'Fulgurite API warning: APCu token rate-limit fallback triggered: '
                . SecretRedaction::safeThrowableMessage($e)
            );
            return null;
        } finally {
            apcu_delete($lockKey);
        }
    }

    private static function oldestRateLimitSlot(array $slots, int $fallback): int {
        $oldest = null;
        foreach (array_keys($slots) as $key) {
            $slot = (int) $key;
            if ($oldest === null || $slot < $oldest) {
                $oldest = $slot;
            }
        }
        return $oldest ?? $fallback;
    }

    private static function rateLimitFromDatabase(int $tokenId, int $limit, int $windowSeconds): ?array {
        $db = Database::getInstance();
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $params = [$tokenId, $windowSeconds];
        $whereTime = "created_at >= datetime('now', '-' || ? || ' seconds')";

        if ($driver === 'mysql') {
            $whereTime = "created_at >= DATE_SUB(NOW(), INTERVAL {$windowSeconds} SECOND)";
            $params = [$tokenId];
        } elseif ($driver === 'pgsql') {
            $whereTime = "created_at >= (NOW() - INTERVAL '{$windowSeconds} seconds')";
            $params = [$tokenId];
        }

        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM api_token_logs
                WHERE token_id = ? AND {$whereTime}
            ");
            $stmt->execute($params);
            $hits = (int) $stmt->fetchColumn();

            $limited = $hits >= $limit;
            return [
                'hits' => $hits,
                'remaining' => max(0, $limit - $hits),
                'reset_at' => time() + $windowSeconds,
                'retry_after' => $limited ? $windowSeconds : 0,
                'limited' => $limited,
            ];
        } catch (Throwable $e) {
            SecretRedaction::errorLog(
                'Fulgurite API warning: DB token rate-limit query failed: '
                . SecretRedaction::safeThrowableMessage($e)
            );
            return null;
        }
    }

    private static function isApcuAvailable(): bool {
        if (function_exists('fulguriteApcuAvailable')) {
            return fulguriteApcuAvailable();
        }

        if (!function_exists('apcu_fetch') || !function_exists('apcu_store') || !function_exists('apcu_add') || !function_exists('apcu_delete')) {
            return false;
        }

        $enabledRaw = ini_get('apc.enabled');
        if ($enabledRaw === false || $enabledRaw === '') {
            return false;
        }

        return filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN);
    }

    private static function allowedScopedIds(string $scopeModeKey, string $scopeJsonKey): ?array {
        $user = self::context()['user'];
        if ((string) ($user[$scopeModeKey] ?? 'all') !== 'selected') {
            return null;
        }

        return UserManager::normalizeIdList($user[$scopeJsonKey] ?? []);
    }

    private static function filterRowsByAllowedIds(array $rows, string $idKey, ?array $allowedIds): array {
        if ($allowedIds === null) {
            return array_values($rows);
        }
        if ($allowedIds === []) {
            return [];
        }

        $allowedMap = [];
        foreach ($allowedIds as $allowedId) {
            $allowedMap[(int) $allowedId] = true;
        }

        return array_values(array_filter($rows, static function ($row) use ($idKey, $allowedMap): bool {
            return is_array($row) && isset($allowedMap[(int) ($row[$idKey] ?? 0)]);
        }));
    }
}
