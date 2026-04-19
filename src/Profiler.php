<?php

class RequestProfiler {
    private const LOG_DIR = '/logs';
    private const REQUEST_LOG_FILE = 'request-profiler.log';
    private const LOCK_LOG_FILE = 'sqlite-locks.log';
    private const SLOW_REQUEST_THRESHOLD_MS = 800.0;
    private const SLOW_SQL_THRESHOLD_MS = 150.0;
    private const SLOW_RESTIC_THRESHOLD_MS = 300.0;

    private static float $requestStartedAt = 0.0;
    private static bool $bootstrapped = false;
    private static float $sqlTimeMs = 0.0;
    private static int $sqlCount = 0;
    private static float $slowestSqlMs = 0.0;
    private static string $slowestSql = '';
    private static int $sqlLockCount = 0;
    private static float $resticTimeMs = 0.0;
    private static int $resticCount = 0;
    private static float $slowestResticMs = 0.0;
    private static string $slowestRestic = '';

    private static function earlyIntSetting(string $key, int $default, int $min, int $max): int {
        if (!function_exists('fulguriteEarlySetting')) {
            return $default;
        }

        $value = (int) fulguriteEarlySetting($key, (string) $default);
        if ($value < $min) {
            $value = $min;
        }
        if ($value > $max) {
            $value = $max;
        }

        return $value;
    }

    private static function slowRequestThresholdMs(): float {
        return (float) self::earlyIntSetting('performance_slow_request_threshold_ms', (int) self::SLOW_REQUEST_THRESHOLD_MS, 50, 600000);
    }

    private static function slowSqlThresholdMs(): float {
        return (float) self::earlyIntSetting('performance_slow_sql_threshold_ms', (int) self::SLOW_SQL_THRESHOLD_MS, 10, 600000);
    }

    private static function slowResticThresholdMs(): float {
        return (float) self::earlyIntSetting('performance_slow_restic_threshold_ms', (int) self::SLOW_RESTIC_THRESHOLD_MS, 10, 600000);
    }

    public static function bootstrap(): void {
        if (self::$bootstrapped) {
            return;
        }

        self::$bootstrapped = true;
        self::$requestStartedAt = microtime(true);
        register_shutdown_function([self::class, 'flush']);
    }

    public static function recordSql(string $sql, float $durationSeconds, bool $success = true, ?string $error = null): void {
        self::bootstrap();

        $durationMs = round($durationSeconds * 1000, 2);
        self::$sqlTimeMs += $durationMs;
        self::$sqlCount++;

        if ($durationMs > self::$slowestSqlMs) {
            self::$slowestSqlMs = $durationMs;
            self::$slowestSql = self::normalizeSql($sql);
        }

        if ((!$success || $durationMs >= self::slowSqlThresholdMs()) && self::looksLikeSqliteLock($error)) {
            self::$sqlLockCount++;
            self::appendLog(self::LOCK_LOG_FILE, json_encode([
                'ts' => date('c'),
                'route' => self::route(),
                'duration_ms' => $durationMs,
                'sql' => self::normalizeSql($sql),
                'error' => $error,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }

    public static function recordRestic(array $cmd, float $durationSeconds, int $returnCode): void {
        self::bootstrap();

        $durationMs = round($durationSeconds * 1000, 2);
        self::$resticTimeMs += $durationMs;
        self::$resticCount++;

        if ($durationMs > self::$slowestResticMs) {
            self::$slowestResticMs = $durationMs;
            self::$slowestRestic = self::commandToString($cmd);
        }
    }

    public static function flush(): void {
        if (!self::$bootstrapped) {
            return;
        }

        $totalMs = round((microtime(true) - self::$requestStartedAt) * 1000, 2);
        $status = http_response_code();

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header(sprintf(
                'X-Fulgurite-Profile: total=%.1fms; sql=%.1fms; sql_count=%d; restic=%.1fms; restic_count=%d',
                $totalMs,
                self::$sqlTimeMs,
                self::$sqlCount,
                self::$resticTimeMs,
                self::$resticCount
            ));
        }

        $shouldLog = $totalMs >= self::slowRequestThresholdMs()
            || self::$slowestSqlMs >= self::slowSqlThresholdMs()
            || self::$slowestResticMs >= self::slowResticThresholdMs()
            || self::$sqlLockCount > 0
            || $status >= 500;

        if (!$shouldLog) {
            return;
        }

        $payload = [
            'ts' => date('c'),
            'sapi' => PHP_SAPI,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => self::route(),
            'status' => $status,
            'total_ms' => $totalMs,
            'sql_ms' => round(self::$sqlTimeMs, 2),
            'sql_count' => self::$sqlCount,
            'slowest_sql_ms' => round(self::$slowestSqlMs, 2),
            'slowest_sql' => self::$slowestSql,
            'sqlite_lock_count' => self::$sqlLockCount,
            'restic_ms' => round(self::$resticTimeMs, 2),
            'restic_count' => self::$resticCount,
            'slowest_restic_ms' => round(self::$slowestResticMs, 2),
            'slowest_restic' => self::$slowestRestic,
            'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            'user' => $_SESSION['username'] ?? null,
        ];

        self::appendLog(
            self::REQUEST_LOG_FILE,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    private static function route(): string {
        if (PHP_SAPI === 'cli') {
            return $_SERVER['argv'][0] ?? 'cli';
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return trim($method . ' ' . $uri);
    }

    private static function normalizeSql(string $sql): string {
        $sql = preg_replace('/\s+/', ' ', trim($sql)) ?? trim($sql);
        if (strlen($sql) > 220) {
            return substr($sql, 0, 217) . '...';
        }
        return $sql;
    }

    private static function looksLikeSqliteLock(?string $error): bool {
        if ($error === null || $error === '') {
            return false;
        }

        $error = strtolower($error);
        return str_contains($error, 'database is locked')
            || str_contains($error, 'database table is locked')
            || str_contains($error, 'busy');
    }

    private static function commandToString(array $cmd): string {
        $parts = array_map(static function(mixed $part): string {
            $part = (string) $part;
            return preg_match('/\s/', $part) ? '"' . addcslashes($part, "\"\\") . '"' : $part;
        }, $cmd);

        $command = implode(' ', $parts);
        if (strlen($command) > 220) {
            return substr($command, 0, 217) . '...';
        }
        return $command;
    }

    private static function appendLog(string $filename, string|false $line): void {
        if ($line === false || $line === '') {
            return;
        }

        $dir = dirname(DB_PATH) . self::LOG_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        @file_put_contents($dir . '/' . $filename, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

class ProfilingPDO extends PDO {
    public function prepare(string $query, array $options = []): PDOStatement|false {
        $options[PDO::ATTR_STATEMENT_CLASS] = [ProfilingPDOStatement::class];
        $statement = parent::prepare($query, $options);
        if ($statement instanceof ProfilingPDOStatement) {
            $statement->setProfileSql($query);
        }
        return $statement;
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
        $startedAt = microtime(true);
        try {
            $statement = $fetchMode === null
                ? parent::query($query)
                : parent::query($query, $fetchMode, ...$fetchModeArgs);

            RequestProfiler::recordSql($query, microtime(true) - $startedAt, $statement !== false);

            if ($statement instanceof ProfilingPDOStatement) {
                $statement->setProfileSql($query);
            }

            return $statement;
        } catch (Throwable $e) {
            RequestProfiler::recordSql($query, microtime(true) - $startedAt, false, $e->getMessage());
            throw $e;
        }
    }

    public function exec(string $statement): int|false {
        $startedAt = microtime(true);
        try {
            $result = parent::exec($statement);
            RequestProfiler::recordSql($statement, microtime(true) - $startedAt, $result !== false);
            return $result;
        } catch (Throwable $e) {
            RequestProfiler::recordSql($statement, microtime(true) - $startedAt, false, $e->getMessage());
            throw $e;
        }
    }

    public function beginTransaction(): bool {
        $startedAt = microtime(true);
        try {
            $result = parent::beginTransaction();
            RequestProfiler::recordSql('BEGIN TRANSACTION', microtime(true) - $startedAt, $result);
            return $result;
        } catch (Throwable $e) {
            RequestProfiler::recordSql('BEGIN TRANSACTION', microtime(true) - $startedAt, false, $e->getMessage());
            throw $e;
        }
    }

    public function commit(): bool {
        $startedAt = microtime(true);
        try {
            $result = parent::commit();
            RequestProfiler::recordSql('COMMIT', microtime(true) - $startedAt, $result);
            return $result;
        } catch (Throwable $e) {
            RequestProfiler::recordSql('COMMIT', microtime(true) - $startedAt, false, $e->getMessage());
            throw $e;
        }
    }

    public function rollBack(): bool {
        $startedAt = microtime(true);
        try {
            $result = parent::rollBack();
            RequestProfiler::recordSql('ROLLBACK', microtime(true) - $startedAt, $result);
            return $result;
        } catch (Throwable $e) {
            RequestProfiler::recordSql('ROLLBACK', microtime(true) - $startedAt, false, $e->getMessage());
            throw $e;
        }
    }
}

class ProfilingPDOStatement extends PDOStatement {
    private string $profileSql = '';

    protected function __construct() {}

    public function setProfileSql(string $sql): void {
        $this->profileSql = $sql;
    }

    public function execute(?array $params = null): bool {
        $startedAt = microtime(true);
        try {
            $result = $params === null ? parent::execute() : parent::execute($params);
            RequestProfiler::recordSql($this->profileSql, microtime(true) - $startedAt, $result);
            return $result;
        } catch (Throwable $e) {
            RequestProfiler::recordSql($this->profileSql, microtime(true) - $startedAt, false, $e->getMessage());
            throw $e;
        }
    }
}
