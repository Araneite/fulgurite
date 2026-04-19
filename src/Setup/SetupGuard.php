<?php

class SetupGuard
{
    private const SESSION_KEY = 'setup_authorized_until';
    private const SESSION_TTL_SECONDS = 600;
    private const DEFAULT_BOOTSTRAP_TTL_SECONDS = 1800;
    private const AUTHORIZE_ATTEMPT_WINDOW_SECONDS = 900;
    private const AUTHORIZE_ATTEMPT_LIMIT = 8;
    private const AUTHORIZE_BACKOFF_MAX_SECONDS = 8;
    private const AUTHORIZE_ATTEMPTS_STATE_VERSION = 1;
    private const AUTHORIZE_PUBLIC_FAILURE_MESSAGE = 'Autorisation impossible. Verifiez le token bootstrap et reessayez dans quelques instants.';

    public static function installedFile(): string
    {
        return dirname(__DIR__, 2) . '/data/.installed';
    }

    public static function bootstrapFile(): string
    {
        return dirname(__DIR__, 2) . '/data/setup-bootstrap.json';
    }

    public static function authorizeAttemptsFile(): string
    {
        return dirname(__DIR__, 2) . '/data/setup-authorize-attempts.json';
    }

    public static function isInstalled(): bool
    {
        return is_file(self::installedFile());
    }

    public static function ensureDataDirectory(): void
    {
        $dir = dirname(self::installedFile());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public static function sessionTtlSeconds(): int
    {
        return self::SESSION_TTL_SECONDS;
    }

    public static function isSessionAuthorized(): bool
    {
        return (int) ($_SESSION[self::SESSION_KEY] ?? 0) > time();
    }

    public static function authorizeSession(?int $ttlSeconds = null): int
    {
        $ttlSeconds = max(60, (int) ($ttlSeconds ?? self::sessionTtlSeconds()));
        $expiresAt = time() + $ttlSeconds;
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $expiresAt;
        return $expiresAt;
    }

    public static function refreshSession(): int
    {
        return self::authorizeSession(self::sessionTtlSeconds());
    }

    public static function clearSession(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function createBootstrapToken(?int $ttlSeconds = null): array
    {
        if (self::isInstalled()) {
            return ['ok' => false, 'message' => 'Fulgurite est deja installe.'];
        }

        self::ensureDataDirectory();

        $ttlSeconds = max(300, (int) ($ttlSeconds ?? self::DEFAULT_BOOTSTRAP_TTL_SECONDS));
        $token = bin2hex(random_bytes(24));
        $now = time();
        $record = [
            'version' => 1,
            'token_hash' => hash('sha256', $token),
            'created_at' => $now,
            'expires_at' => $now + $ttlSeconds,
            'used_at' => null,
            'used_by' => null,
        ];

        self::writeBootstrapRecord($record);

        return [
            'ok' => true,
            'token' => $token,
            'expires_at' => $record['expires_at'],
            'path' => self::bootstrapFile(),
        ];
    }

    public static function clearBootstrapToken(): bool
    {
        self::clearSession();
        $path = self::bootstrapFile();
        return !is_file($path) || @unlink($path);
    }

    public static function clientIp(): string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    public static function authorizeWithBootstrapToken(string $token, string $usedBy = ''): array
    {
        if (self::isInstalled()) {
            return ['ok' => false, 'message' => 'Fulgurite est deja installe.'];
        }

        $ip = self::clientIp();
        $usedBy = trim($usedBy) !== '' ? trim($usedBy) : $ip;
        $rateLimit = self::authorizeAttemptStatus($ip);
        if (!empty($rateLimit['blocked'])) {
            self::logAuthorizeFailure('rate_limited', $ip, [
                'used_by' => $usedBy,
                'retry_after' => (int) ($rateLimit['retry_after'] ?? 0),
                'window_failures' => (int) ($rateLimit['window_failures'] ?? 0),
                'consecutive_failures' => (int) ($rateLimit['consecutive_failures'] ?? 0),
            ]);
            return self::authorizeFailureResponse(429, (int) ($rateLimit['retry_after'] ?? 0));
        }

        $token = trim($token);
        $failureReason = null;
        if ($token === '') {
            $failureReason = 'missing_token';
        }

        $record = self::readBootstrapRecord();
        if ($failureReason === null && $record === null) {
            $failureReason = 'token_not_configured';
        }

        if ($failureReason === null && !empty($record['used_at'])) {
            $failureReason = 'token_already_used';
        }

        if ($failureReason === null && (int) ($record['expires_at'] ?? 0) <= time()) {
            $failureReason = 'token_expired';
        }

        $expectedHash = (string) ($record['token_hash'] ?? '');
        $providedHash = hash('sha256', $token);
        if ($failureReason === null && ($expectedHash === '' || !hash_equals($expectedHash, $providedHash))) {
            $failureReason = 'token_invalid';
        }

        if ($failureReason !== null) {
            $failure = self::recordAuthorizeFailure($ip, $failureReason, $usedBy);
            $delaySeconds = (int) ($failure['delay_seconds'] ?? 0);
            if ($delaySeconds > 0) {
                usleep($delaySeconds * 1000000);
            }
            return self::authorizeFailureResponse(
                (int) ($failure['status_code'] ?? 200),
                (int) ($failure['retry_after'] ?? 0)
            );
        }

        $record['used_at'] = time();
        $record['used_by'] = $usedBy !== '' ? $usedBy : null;
        self::writeBootstrapRecord($record);
        self::clearAuthorizeFailures($ip);

        // authorizeSession() already calls session_regenerate_id(true) internally
        // to prevent session fixation during privilege elevation.
        $expiresAt = self::authorizeSession();

        return [
            'ok' => true,
            'message' => 'Session de setup autorisee.',
            'session_expires_at' => $expiresAt,
        ];
    }

    public static function finalizeInstall(string $driver): bool
    {
        self::ensureDataDirectory();

        $content = implode(PHP_EOL, [
            date('c'),
            trim($driver) !== '' ? trim($driver) : 'unknown',
            'bootstrap=locked',
        ]) . PHP_EOL;

        if (@file_put_contents(self::installedFile(), $content) === false) {
            return false;
        }

        @chmod(self::installedFile(), 0640);
        self::clearBootstrapToken();

        return true;
    }

    public static function bootstrapStatus(): array
    {
        $record = self::readBootstrapRecord();
        if ($record === null) {
            return [
                'configured' => false,
                'installed' => self::isInstalled(),
                'path' => self::bootstrapFile(),
                'expired' => false,
                'used' => false,
                'created_at' => null,
                'expires_at' => null,
            ];
        }

        return [
            'configured' => true,
            'installed' => self::isInstalled(),
            'path' => self::bootstrapFile(),
            'expired' => (int) ($record['expires_at'] ?? 0) <= time(),
            'used' => !empty($record['used_at']),
            'created_at' => isset($record['created_at']) ? (int) $record['created_at'] : null,
            'expires_at' => isset($record['expires_at']) ? (int) $record['expires_at'] : null,
            'used_at' => isset($record['used_at']) ? (int) $record['used_at'] : null,
            'used_by' => (string) ($record['used_by'] ?? ''),
        ];
    }

    private static function readBootstrapRecord(): ?array
    {
        $path = self::bootstrapFile();
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function writeBootstrapRecord(array $record): void
    {
        self::ensureDataDirectory();
        $path = self::bootstrapFile();
        file_put_contents($path, json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        @chmod($path, 0600);
    }

    private static function authorizeAttemptStatus(string $ip): array
    {
        $now = time();
        $state = self::readAuthorizeAttemptsState();
        $entries = is_array($state['ips'] ?? null) ? $state['ips'] : [];
        $entry = self::normalizeAuthorizeAttemptEntry(is_array($entries[$ip] ?? null) ? $entries[$ip] : [], $now);
        $entries = self::pruneAuthorizeAttemptEntries($entries, $now);
        self::writeAuthorizeAttemptsState([
            'version' => self::AUTHORIZE_ATTEMPTS_STATE_VERSION,
            'ips' => $entries,
        ]);

        $retryAfter = max(0, (int) ($entry['blocked_until'] ?? 0) - $now);

        return [
            'blocked' => $retryAfter > 0,
            'retry_after' => $retryAfter,
            'window_failures' => (int) ($entry['window_failures'] ?? 0),
            'consecutive_failures' => (int) ($entry['consecutive_failures'] ?? 0),
        ];
    }

    private static function recordAuthorizeFailure(string $ip, string $reason, string $usedBy): array
    {
        $now = time();
        $state = self::readAuthorizeAttemptsState();
        $entries = is_array($state['ips'] ?? null) ? $state['ips'] : [];
        $entry = self::normalizeAuthorizeAttemptEntry(is_array($entries[$ip] ?? null) ? $entries[$ip] : [], $now);

        if ((int) ($entry['window_started_at'] ?? 0) <= 0) {
            $entry['window_started_at'] = $now;
        }

        $entry['window_failures'] = max(0, (int) ($entry['window_failures'] ?? 0)) + 1;
        $entry['consecutive_failures'] = max(0, (int) ($entry['consecutive_failures'] ?? 0)) + 1;
        $entry['last_failed_at'] = $now;

        $delaySeconds = self::authorizeFailureDelay((int) $entry['consecutive_failures']);
        $blockedUntil = $delaySeconds > 0 ? $now + $delaySeconds : 0;
        $statusCode = 200;

        if ((int) $entry['window_failures'] >= self::AUTHORIZE_ATTEMPT_LIMIT) {
            $blockedUntil = max($blockedUntil, (int) $entry['window_started_at'] + self::AUTHORIZE_ATTEMPT_WINDOW_SECONDS);
            $statusCode = 429;
        }

        $entry['blocked_until'] = $blockedUntil;
        $entries[$ip] = $entry;
        $entries = self::pruneAuthorizeAttemptEntries($entries, $now);

        self::writeAuthorizeAttemptsState([
            'version' => self::AUTHORIZE_ATTEMPTS_STATE_VERSION,
            'ips' => $entries,
        ]);

        $retryAfter = max(0, $blockedUntil - $now);
        self::logAuthorizeFailure($reason, $ip, [
            'used_by' => $usedBy,
            'window_failures' => (int) $entry['window_failures'],
            'consecutive_failures' => (int) $entry['consecutive_failures'],
            'delay_seconds' => $delaySeconds,
            'retry_after' => $retryAfter,
        ]);

        return [
            'delay_seconds' => $delaySeconds,
            'status_code' => $statusCode,
            'retry_after' => $statusCode === 429 ? $retryAfter : 0,
        ];
    }

    private static function clearAuthorizeFailures(string $ip): void
    {
        $state = self::readAuthorizeAttemptsState();
        $entries = is_array($state['ips'] ?? null) ? $state['ips'] : [];
        unset($entries[$ip]);
        self::writeAuthorizeAttemptsState([
            'version' => self::AUTHORIZE_ATTEMPTS_STATE_VERSION,
            'ips' => self::pruneAuthorizeAttemptEntries($entries, time()),
        ]);
    }

    private static function authorizeFailureResponse(int $statusCode = 200, int $retryAfter = 0): array
    {
        $response = [
            'ok' => false,
            'message' => self::AUTHORIZE_PUBLIC_FAILURE_MESSAGE,
            'status_code' => max(200, $statusCode),
        ];

        if ($retryAfter > 0) {
            $response['retry_after'] = $retryAfter;
        }

        return $response;
    }

    private static function authorizeFailureDelay(int $consecutiveFailures): int
    {
        if ($consecutiveFailures <= 0) {
            return 0;
        }

        $power = min($consecutiveFailures - 1, 3);
        return min(self::AUTHORIZE_BACKOFF_MAX_SECONDS, 1 << $power);
    }

    private static function readAuthorizeAttemptsState(): array
    {
        $path = self::authorizeAttemptsFile();
        if (!is_file($path)) {
            return [
                'version' => self::AUTHORIZE_ATTEMPTS_STATE_VERSION,
                'ips' => [],
            ];
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return [
                'version' => self::AUTHORIZE_ATTEMPTS_STATE_VERSION,
                'ips' => [],
            ];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [
                'version' => self::AUTHORIZE_ATTEMPTS_STATE_VERSION,
                'ips' => [],
            ];
        }

        return [
            'version' => self::AUTHORIZE_ATTEMPTS_STATE_VERSION,
            'ips' => is_array($decoded['ips'] ?? null) ? $decoded['ips'] : [],
        ];
    }

    private static function writeAuthorizeAttemptsState(array $state): void
    {
        self::ensureDataDirectory();
        $path = self::authorizeAttemptsFile();
        file_put_contents($path, json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        @chmod($path, 0600);
    }

    private static function normalizeAuthorizeAttemptEntry(array $entry, int $now): array
    {
        $windowStartedAt = max(0, (int) ($entry['window_started_at'] ?? 0));
        $windowFailures = max(0, (int) ($entry['window_failures'] ?? 0));
        if ($windowStartedAt <= 0 || ($now - $windowStartedAt) >= self::AUTHORIZE_ATTEMPT_WINDOW_SECONDS) {
            $windowStartedAt = 0;
            $windowFailures = 0;
        }

        $lastFailedAt = max(0, (int) ($entry['last_failed_at'] ?? 0));
        $consecutiveFailures = max(0, (int) ($entry['consecutive_failures'] ?? 0));
        if ($lastFailedAt <= 0 || ($now - $lastFailedAt) >= self::AUTHORIZE_ATTEMPT_WINDOW_SECONDS) {
            $lastFailedAt = 0;
            $consecutiveFailures = 0;
        }

        $blockedUntil = max(0, (int) ($entry['blocked_until'] ?? 0));
        if ($blockedUntil <= $now) {
            $blockedUntil = 0;
        }

        return [
            'window_started_at' => $windowStartedAt,
            'window_failures' => $windowFailures,
            'consecutive_failures' => $consecutiveFailures,
            'last_failed_at' => $lastFailedAt,
            'blocked_until' => $blockedUntil,
        ];
    }

    private static function pruneAuthorizeAttemptEntries(array $entries, int $now): array
    {
        $filtered = [];

        foreach ($entries as $entryIp => $entry) {
            if (!is_string($entryIp) || $entryIp === '' || !is_array($entry)) {
                continue;
            }

            $normalizedEntry = self::normalizeAuthorizeAttemptEntry($entry, $now);
            if (
                (int) $normalizedEntry['window_failures'] <= 0
                && (int) $normalizedEntry['consecutive_failures'] <= 0
                && (int) $normalizedEntry['blocked_until'] <= 0
            ) {
                continue;
            }

            $filtered[$entryIp] = $normalizedEntry;
        }

        return $filtered;
    }

    private static function logAuthorizeFailure(string $reason, string $ip, array $context = []): void
    {
        $payload = [
            'event' => 'setup_authorize_failure',
            'reason' => $reason,
            'ip' => $ip,
            'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ];

        foreach ($context as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $payload[$key] = $value;
        }

        error_log('[Fulgurite] ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
