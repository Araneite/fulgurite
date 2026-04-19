<?php
// =============================================================================
// ApiKernel.php — Bootstrap, CORS, idempotency, audit logging of l public API
// =============================================================================

class ApiKernel {

    private static float $startTime = 0.0;
    private static ?string $idempotencyKey = null;
    private static ?int $idempotentReplayStatus = null;

    public static function bootstrap(): void {
        self::$startTime = microtime(true);

        // CORS preflight
        self::handlePreflightCors();

        register_shutdown_function([self::class, 'shutdownLog']);
    }

    /**
     * A appeler after ApiAuth::authenticate() for appliquer the en-tetes CORS
     * a the request reelle, with a validation complementaire by token.
     */
    public static function applyAuthenticatedCors(): void {
        if (ApiRequest::method() === 'OPTIONS') {
            return;
        }

        $origin = self::requestOrigin();
        if ($origin === '') {
            return;
        }

        if (!self::isOriginGloballyAllowed($origin)) {
            return;
        }

        $token = ApiAuth::currentToken();
        if (!self::isOriginAllowedForToken($origin, $token)) {
            ApiResponse::error(403, 'origin_not_allowed', 'Cette origine n est pas autorisee pour ce token.');
        }

        self::sendCorsHeaders($origin);
    }

    /**
     * A appeler after ApiAuth::authenticate(). checks l idempotency and termine
     * the request with the cached response if the key was already seen.
     */
    public static function checkIdempotency(): void {
        $key = ApiRequest::idempotencyKey();
        if ($key === null) return;

        $method = ApiRequest::method();
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;

        self::$idempotencyKey = $key;
        $tokenId = (int) (ApiAuth::currentToken()['id'] ?? 0);
        if ($tokenId === 0) return;

        $stmt = Database::getInstance()->prepare(
            'SELECT response_status, response_body FROM api_idempotency_keys WHERE token_id = ? AND idempotency_key = ?'
        );
        $stmt->execute([$tokenId, $key]);
        $row = $stmt->fetch();
        if ($row) {
            self::$idempotentReplayStatus = (int) $row['response_status'];
            header('Idempotent-Replay: true');
            http_response_code((int) $row['response_status']);
            header('Content-Type: application/json; charset=utf-8');
            echo $row['response_body'];
            exit;
        }
    }

    public static function storeIdempotentResponse(int $status, string $body): void {
        if (self::$idempotencyKey === null) return;
        $tokenId = (int) (ApiAuth::currentToken()['id'] ?? 0);
        if ($tokenId === 0) return;
        try {
            Database::getInstance()->prepare(
                'INSERT OR IGNORE INTO api_idempotency_keys (token_id, idempotency_key, method, endpoint, response_status, response_body)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $tokenId,
                self::$idempotencyKey,
                ApiRequest::method(),
                ApiRequest::path(),
                $status,
                $body,
            ]);
        } catch (Throwable $e) {
            SecretRedaction::errorLog(
                'Fulgurite API warning: idempotency response storage failed: '
                . SecretRedaction::safeThrowableMessage($e)
            );
        }
    }

    public static function shutdownLog(): void {
        try {
            $latency = (int) round((microtime(true) - self::$startTime) * 1000);
            $status = http_response_code() ?: 200;
            $tokenCtxToken = null;
            try {
                $tokenCtxToken = ApiAuth::currentToken();
            } catch (Throwable $e) {
                $tokenCtxToken = null;
                SecretRedaction::errorLog(
                    'Fulgurite API warning: token context unavailable during shutdown log: '
                    . SecretRedaction::safeThrowableMessage($e)
                );
            }
            $tokenId = $tokenCtxToken['id'] ?? null;
            $userId = $tokenCtxToken['user_id'] ?? null;
            self::recordTokenLog([
                $tokenId,
                $userId,
                ApiRequest::method(),
                ApiRequest::path(),
                (int) $status,
                ApiAuth::clientIp(),
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                $latency,
            ]);

            // Stocke the response idempotente after a POST/PUT/PATCH/DELETE 2xx reussi
            if (self::$idempotencyKey !== null && $status >= 200 && $status < 300) {
                $body = ApiResponse::lastBody();
                if ($body !== null) {
                    self::storeIdempotentResponse((int) $status, json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                }
            }

            // Lazy purge
            if (random_int(1, 50) === 1) {
                ApiTokenManager::purgeLogs();
                ApiTokenManager::purgeIdempotencyKeys();
            }
        } catch (Throwable $e) {
            SecretRedaction::errorLog(
                'Fulgurite API warning: shutdown logging failed: ' . SecretRedaction::safeThrowableMessage($e)
            );
        }
    }

    private static function recordTokenLog(array $entry): void {
        $method = strtoupper((string) ($entry[2] ?? ''));
        $status = (int) ($entry[4] ?? 200);
        $critical = $status >= 400 || !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);

        if (!$critical) {
            $sampling = AppConfig::apiTokenLogSamplingPercent();
            if ($sampling < 100) {
                try {
                    if (random_int(1, 100) > $sampling) {
                        return;
                    }
                } catch (Throwable $e) {
                    // En cas of failure RNG, on garde a strategie conservative: on log.
                }
            }
        }

        if (self::bufferTokenLogEntry($entry)) {
            return;
        }

        // under contention APCu, on evite the insertions synchrones en rafale on flux non critique.
        if (!$critical && self::isApcuAvailable() && !self::allowNonCriticalDirectInsert()) {
            return;
        }

        if ($critical) {
            self::insertTokenLogsBatch([$entry]);
            return;
        }

        self::insertTokenLogsBatch([$entry]);
    }

    private static function bufferTokenLogEntry(array $entry): bool {
        $batchSize = AppConfig::apiTokenLogBufferBatchSize();
        $flushSeconds = AppConfig::apiTokenLogBufferFlushSeconds();
        if ($batchSize <= 1 || $flushSeconds <= 0 || !self::isApcuAvailable()) {
            return false;
        }

        $bufferKey = 'fulgurite:api_token_logs:buffer';
        $lastFlushKey = 'fulgurite:api_token_logs:last_flush_at';
        $lockKey = 'fulgurite:api_token_logs:lock';
        if (!apcu_add($lockKey, 1, 2)) {
            return false;
        }

        try {
            $buffer = apcu_fetch($bufferKey);
            if (!is_array($buffer)) {
                $buffer = [];
            }

            $buffer[] = $entry;

            $lastFlush = (int) (apcu_fetch($lastFlushKey) ?: 0);
            if ($lastFlush <= 0) {
                $lastFlush = time();
                apcu_store($lastFlushKey, $lastFlush, max(60, $flushSeconds * 2));
            }
            $maxEntries = AppConfig::apiTokenLogBufferMaxEntries();
            $shouldFlush = count($buffer) >= $batchSize
                || count($buffer) >= $maxEntries
                || ($lastFlush > 0 && (time() - $lastFlush) >= $flushSeconds);

            if ($shouldFlush && !empty($buffer)) {
                if (self::insertTokenLogsBatch($buffer)) {
                    $buffer = [];
                } else {
                    // under contention DB, on conserve the buffer for a tentative ulterieure.
                    if (count($buffer) > $maxEntries) {
                        $buffer = array_slice($buffer, -$maxEntries);
                    }
                }
                if (empty($buffer)) {
                    apcu_store($lastFlushKey, time(), max(60, $flushSeconds * 2));
                }
            }

            apcu_store($bufferKey, $buffer, max(30, $flushSeconds * 3));
            return true;
        } catch (Throwable $e) {
            SecretRedaction::errorLog(
                'Fulgurite API warning: APCu token log buffer failed: ' . SecretRedaction::safeThrowableMessage($e)
            );
            return false;
        } finally {
            apcu_delete($lockKey);
        }
    }

    private static function insertTokenLogsBatch(array $entries): bool {
        if (empty($entries)) {
            return true;
        }

        $db = Database::getInstance();
        $ownsTransaction = !$db->inTransaction();
        try {
            if ($ownsTransaction) {
                $db->beginTransaction();
            }

            foreach (array_chunk($entries, 100) as $chunk) {
                $placeholders = implode(', ', array_fill(0, count($chunk), '(?, ?, ?, ?, ?, ?, ?, ?)'));
                $params = [];
                foreach ($chunk as $entry) {
                    $params[] = $entry[0] ?? null;
                    $params[] = $entry[1] ?? null;
                    $params[] = (string) ($entry[2] ?? '');
                    $params[] = (string) ($entry[3] ?? '');
                    $params[] = (int) ($entry[4] ?? 0);
                    $params[] = (string) ($entry[5] ?? '');
                    $params[] = substr((string) ($entry[6] ?? ''), 0, 255);
                    $params[] = (int) ($entry[7] ?? 0);
                }

                $db->prepare(
                    'INSERT INTO api_token_logs (token_id, user_id, method, endpoint, status, ip, user_agent, latency_ms)
                     VALUES ' . $placeholders
                )->execute($params);
            }

            if ($ownsTransaction && $db->inTransaction()) {
                $db->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            SecretRedaction::errorLog(
                'Fulgurite API warning: token log batch insert failed: ' . SecretRedaction::safeThrowableMessage($e)
            );
            return false;
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

    private static function allowNonCriticalDirectInsert(): bool {
        if (!self::isApcuAvailable()) {
            return true;
        }

        try {
            return apcu_add('fulgurite:api_token_logs:direct_insert_gate', 1, 1);
        } catch (Throwable $e) {
            SecretRedaction::errorLog(
                'Fulgurite API warning: APCu direct insert gate failed: ' . SecretRedaction::safeThrowableMessage($e)
            );
            return true;
        }
    }

    private static function handlePreflightCors(): void {
        if (ApiRequest::method() !== 'OPTIONS') {
            return;
        }

        $origin = self::requestOrigin();
        if ($origin !== '' && self::isOriginGloballyAllowed($origin)) {
            self::sendCorsHeaders($origin);
        }

        http_response_code(204);
        exit;
    }

    private static function requestOrigin(): string {
        return rtrim(trim((string) ($_SERVER['HTTP_ORIGIN'] ?? '')), '/');
    }

    private static function isOriginGloballyAllowed(string $origin): bool {
        $allowed = AppConfig::getApiCorsAllowedOrigins();
        if (in_array('*', $allowed, true)) {
            return true;
        }

        return in_array($origin, $allowed, true);
    }

    private static function isOriginAllowedForToken(string $origin, array $token): bool {
        $allowedOrigins = $token['allowed_origins'] ?? [];
        if (empty($allowedOrigins)) {
            return true;
        }
        if (in_array('*', $allowedOrigins, true)) {
            return true;
        }

        return in_array($origin, $allowedOrigins, true);
    }

    private static function sendCorsHeaders(string $origin): void {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Idempotency-Key, X-Dry-Run, Idempotency-Key');
        header('Access-Control-Max-Age: 600');
    }
}
