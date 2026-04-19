<?php
// =============================================================================
// ApiTokenManager.php — CRUD of jetons of public API
// =============================================================================

class ApiTokenManager {

    public const TOKEN_PREFIX = 'rui_';

    /**
     * creates a nouveau token and renvoie the secret en clair (one-time reveal).
     *
     * @return array{token: array, secret: string}
     */
    public static function create(int $userId, array $data, int $createdBy): array {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Nom du token requis.');
        }

        $user = UserManager::getById($userId);
        if (!$user) {
            throw new InvalidArgumentException('Utilisateur introuvable.');
        }

        $userPermissions = Auth::resolvedPermissionsForUser($user);
        $requestedScopes = (array) ($data['scopes'] ?? []);
        $allowedScopes = ApiScopes::filterAllowedForUser($requestedScopes, $userPermissions);
        if (empty($allowedScopes)) {
            throw new InvalidArgumentException('Aucun scope valide pour cet utilisateur.');
        }

        $readOnly = !empty($data['read_only']) ? 1 : 0;
        if ($readOnly) {
            $allowedScopes = array_values(array_filter($allowedScopes, [ApiScopes::class, 'isReadOnly']));
            if (empty($allowedScopes)) {
                throw new InvalidArgumentException('Aucun scope en lecture seule selectionne.');
            }
        }

        $allowedIps = self::normalizeIpList($data['allowed_ips'] ?? []);
        $allowedOrigins = self::normalizeOriginList($data['allowed_origins'] ?? []);

        $rateLimit = (int) ($data['rate_limit_per_minute'] ?? AppConfig::getApiDefaultRateLimit());
        if ($rateLimit < 1) {
            $rateLimit = AppConfig::getApiDefaultRateLimit();
        }

        $expiresAt = self::normalizeExpiresAt($data['expires_at'] ?? null);

        // Generation of secret : "rui_" + 8 chars of prefix + "_" + 40 chars secret
        $prefix = bin2hex(random_bytes(4)); // 8 chars
        $secretRandom = bin2hex(random_bytes(20)); // 40 chars
        $fullSecret = self::TOKEN_PREFIX . $prefix . '_' . $secretRandom;
        $hash = hash('sha256', $fullSecret);

        $db = Database::getInstance();
        $db->prepare("
            INSERT INTO api_tokens
                (user_id, name, prefix, token_hash, scopes_json, allowed_ips_json, allowed_origins_json,
                 rate_limit_per_minute, read_only, expires_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            $name,
            $prefix,
            $hash,
            json_encode(array_values($allowedScopes)),
            json_encode($allowedIps),
            json_encode($allowedOrigins),
            $rateLimit,
            $readOnly,
            $expiresAt,
            $createdBy,
        ]);

        $tokenId = (int) $db->lastInsertId();
        $token = self::getById($tokenId);

        // Notification in-app a the user proprietaire
        try {
            AppNotificationManager::store(
                'Nouveau token API cree',
                'Un nouveau token API "' . $name . '" a ete cree sur votre compte.',
                ['profile' => 'security', 'event' => 'api_token_created', 'severity' => 'info', 'user_ids' => [$userId]]
            );
        } catch (Throwable $e) { /* best effort */ }

        Auth::log('api_token_created', "Token API '$name' cree pour user_id=$userId", 'info');

        return ['token' => $token, 'secret' => $fullSecret];
    }

    public static function update(int $tokenId, array $data): ?array {
        $token = self::getById($tokenId);
        if (!$token) return null;

        $fields = [];
        $values = [];

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ($name !== '') {
                $fields[] = 'name = ?';
                $values[] = $name;
            }
        }

        if (array_key_exists('scopes', $data)) {
            $user = UserManager::getById((int) $token['user_id']);
            $userPermissions = $user ? Auth::resolvedPermissionsForUser($user) : [];
            $newScopes = ApiScopes::filterAllowedForUser((array) $data['scopes'], $userPermissions);
            $readOnly = array_key_exists('read_only', $data) ? !empty($data['read_only']) : (bool) $token['read_only'];
            if ($readOnly) {
                $newScopes = array_values(array_filter($newScopes, [ApiScopes::class, 'isReadOnly']));
            }
            $fields[] = 'scopes_json = ?';
            $values[] = json_encode(array_values($newScopes));
        }

        if (array_key_exists('read_only', $data)) {
            $fields[] = 'read_only = ?';
            $values[] = !empty($data['read_only']) ? 1 : 0;
        }

        if (array_key_exists('allowed_ips', $data)) {
            $fields[] = 'allowed_ips_json = ?';
            $values[] = json_encode(self::normalizeIpList($data['allowed_ips']));
        }

        if (array_key_exists('allowed_origins', $data)) {
            $fields[] = 'allowed_origins_json = ?';
            $values[] = json_encode(self::normalizeOriginList($data['allowed_origins']));
        }

        if (array_key_exists('rate_limit_per_minute', $data)) {
            $fields[] = 'rate_limit_per_minute = ?';
            $values[] = max(1, (int) $data['rate_limit_per_minute']);
        }

        if (array_key_exists('expires_at', $data)) {
            $fields[] = 'expires_at = ?';
            $values[] = self::normalizeExpiresAt($data['expires_at']);
        }

        if (empty($fields)) return $token;
        $values[] = $tokenId;
        Database::getInstance()
            ->prepare('UPDATE api_tokens SET ' . implode(', ', $fields) . ' WHERE id = ?')
            ->execute($values);

        return self::getById($tokenId);
    }

    public static function revoke(int $tokenId, string $reason = ''): bool {
        $token = self::getById($tokenId);
        if (!$token || $token['revoked_at']) return false;

        Database::getInstance()
            ->prepare("UPDATE api_tokens SET revoked_at = datetime('now'), revoked_reason = ? WHERE id = ?")
            ->execute([$reason ?: null, $tokenId]);

        try {
            AppNotificationManager::store(
                'Token API revoque',
                'Le token API "' . $token['name'] . '" a ete revoque.',
                ['profile' => 'security', 'event' => 'api_token_revoked', 'severity' => 'warning', 'user_ids' => [(int) $token['user_id']]]
            );
        } catch (Throwable $e) {}

        Auth::log('api_token_revoked', "Token API id=$tokenId revoque ($reason)", 'warning');
        return true;
    }

    public static function delete(int $tokenId): bool {
        $token = self::getById($tokenId);
        if (!$token) return false;
        Database::getInstance()->prepare('DELETE FROM api_tokens WHERE id = ?')->execute([$tokenId]);
        Database::getInstance()->prepare('DELETE FROM api_idempotency_keys WHERE token_id = ?')->execute([$tokenId]);
        return true;
    }

    public static function getById(int $tokenId): ?array {
        $stmt = Database::getInstance()->prepare('SELECT * FROM api_tokens WHERE id = ?');
        $stmt->execute([$tokenId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByPlainSecret(string $plainSecret): ?array {
        if ($plainSecret === '') return null;
        $hash = hash('sha256', $plainSecret);
        $stmt = Database::getInstance()->prepare('SELECT * FROM api_tokens WHERE token_hash = ? LIMIT 1');
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function listForUser(int $userId): array {
        $stmt = Database::getInstance()->prepare('SELECT * FROM api_tokens WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function listAll(): array {
        $rows = Database::getInstance()->query('
            SELECT t.*, u.username
            FROM api_tokens t
            LEFT JOIN users u ON u.id = t.user_id
            ORDER BY t.created_at DESC
        ')->fetchAll();
        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function recordUsage(int $tokenId, string $ip): void {
        if ($tokenId <= 0) {
            return;
        }

        $coalesceSeconds = max(1, AppConfig::apiTokenUsageCoalesceSeconds());
        if (self::usageWriteRecentlyRecorded($tokenId, $ip, $coalesceSeconds)) {
            return;
        }

        self::recordUsageInDatabase($tokenId, $ip, $coalesceSeconds);
    }

    private static function usageWriteRecentlyRecorded(int $tokenId, string $ip, int $coalesceSeconds): bool {
        if (!self::isApcuAvailable()) {
            return false;
        }

        $scope = AppConfig::apiTokenUsageCoalesceByIp() ? $ip : '*';
        $cacheKey = 'fulgurite:api_token_usage:' . hash('sha256', $tokenId . '|' . $scope);
        try {
            return !apcu_add($cacheKey, 1, $coalesceSeconds);
        } catch (Throwable $e) {
            SecretRedaction::errorLog(
                'Fulgurite API warning: APCu coalescing failure in token usage update: '
                . SecretRedaction::safeThrowableMessage($e)
            );
            return false;
        }
    }

    private static function recordUsageInDatabase(int $tokenId, string $ip, int $coalesceSeconds): void {
        $db = Database::getInstance();
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = Database::nowExpression();

        $conditionSql = "(last_used_at IS NULL OR last_used_at < datetime('now', '-' || ? || ' seconds'))";
        $params = [$ip, $tokenId, $coalesceSeconds];

        if ($driver === 'mysql') {
            $conditionSql = "(last_used_at IS NULL OR last_used_at < DATE_SUB(NOW(), INTERVAL {$coalesceSeconds} SECOND))";
            $params = [$ip, $tokenId];
        } elseif ($driver === 'pgsql') {
            $conditionSql = "(last_used_at IS NULL OR last_used_at < (NOW() - INTERVAL '{$coalesceSeconds} seconds'))";
            $params = [$ip, $tokenId];
        }

        try {
            $db->prepare(
                "UPDATE api_tokens
                 SET last_used_at = {$nowExpr}, last_used_ip = ?
                 WHERE id = ? AND {$conditionSql}"
            )->execute($params);
        } catch (Throwable $e) {
            SecretRedaction::errorLog(
                'Fulgurite API warning: token usage update failed: ' . SecretRedaction::safeThrowableMessage($e)
            );
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

    public static function purgeIdempotencyKeys(): int {
        $hours = AppConfig::getApiIdempotencyRetentionHours();
        $stmt = Database::getInstance()->prepare(
            "DELETE FROM api_idempotency_keys WHERE created_at < datetime('now', '-' || ? || ' hours')"
        );
        $stmt->execute([$hours]);
        return $stmt->rowCount();
    }

    public static function purgeLogs(): int {
        $days = AppConfig::getApiLogRetentionDays();
        $stmt = Database::getInstance()->prepare(
            "DELETE FROM api_token_logs WHERE created_at < datetime('now', '-' || ? || ' days')"
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function hydrate(array $row): array {
        $row['scopes']          = json_decode($row['scopes_json'] ?? '[]', true) ?: [];
        $row['allowed_ips']     = json_decode($row['allowed_ips_json'] ?? '[]', true) ?: [];
        $row['allowed_origins'] = json_decode($row['allowed_origins_json'] ?? '[]', true) ?: [];
        $row['read_only']       = (bool) ($row['read_only'] ?? 0);
        $row['is_revoked']      = !empty($row['revoked_at']);
        $row['is_expired']      = !empty($row['expires_at']) && strtotime($row['expires_at']) < time();
        $row['display_token']   = self::TOKEN_PREFIX . ($row['prefix'] ?? '') . '_********';
        return $row;
    }

    public static function publicView(array $token): array {
        return [
            'id' => (int) $token['id'],
            'user_id' => (int) $token['user_id'],
            'name' => $token['name'],
            'display_token' => $token['display_token'] ?? '',
            'scopes' => $token['scopes'] ?? [],
            'allowed_ips' => $token['allowed_ips'] ?? [],
            'allowed_origins' => $token['allowed_origins'] ?? [],
            'rate_limit_per_minute' => (int) $token['rate_limit_per_minute'],
            'read_only' => (bool) $token['read_only'],
            'expires_at' => $token['expires_at'] ?? null,
            'last_used_at' => $token['last_used_at'] ?? null,
            'last_used_ip' => $token['last_used_ip'] ?? null,
            'revoked_at' => $token['revoked_at'] ?? null,
            'revoked_reason' => $token['revoked_reason'] ?? null,
            'created_at' => $token['created_at'] ?? null,
        ];
    }

    private static function normalizeIpList(mixed $value): array {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }
        if (!is_array($value)) return [];
        $out = [];
        foreach ($value as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') continue;
            $out[] = $entry;
        }
        return array_values(array_unique($out));
    }

    private static function normalizeOriginList(mixed $value): array {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }
        if (!is_array($value)) return [];
        $out = [];
        foreach ($value as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') continue;
            if (!preg_match('#^https?://#', $entry) && $entry !== '*') continue;
            $out[] = rtrim($entry, '/');
        }
        return array_values(array_unique($out));
    }

    private static function normalizeExpiresAt(mixed $value): ?string {
        if ($value === null || $value === '') return null;
        if (is_int($value) || ctype_digit((string) $value)) {
            return gmdate('Y-m-d H:i:s', (int) $value);
        }
        $ts = strtotime((string) $value);
        if ($ts === false) return null;
        return gmdate('Y-m-d H:i:s', $ts);
    }
}
