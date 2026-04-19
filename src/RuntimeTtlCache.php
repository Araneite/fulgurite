<?php
declare(strict_types=1);

final class RuntimeTtlCache
{
    /**
     * @param callable():array $resolver
     */
    public static function rememberArray(string $namespace, array $parts, int $ttlSeconds, callable $resolver): array
    {
        $ttlSeconds = max(1, $ttlSeconds);
        $key = self::buildKey($namespace, $parts);
        $now = time();

        $apcuValue = self::readApcu($key, $now);
        if ($apcuValue !== null) {
            return $apcuValue;
        }

        $fileValue = self::readFile($key, $now);
        if ($fileValue !== null) {
            self::writeApcu($key, $fileValue, $ttlSeconds, $now);
            return $fileValue;
        }

        $value = $resolver();
        self::writeApcu($key, $value, $ttlSeconds, $now);
        self::writeFile($key, $value, $ttlSeconds, $now);

        return $value;
    }

    private static function buildKey(string $namespace, array $parts): string
    {
        $payload = json_encode([$namespace, $parts], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($payload) || $payload === '') {
            $payload = $namespace . '|' . serialize($parts);
        }

        return 'fulgurite:runtime-cache:' . hash('sha256', $payload);
    }

    private static function cacheDir(): string
    {
        $baseDir = dirname(Database::dbPath()) . '/cache/runtime';
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0700, true);
        }

        return $baseDir;
    }

    private static function cachePath(string $key): string
    {
        return self::cacheDir() . '/' . sha1($key) . '.json';
    }

    private static function readApcu(string $key, int $now): ?array
    {
        if (!fulguriteApcuAvailable()) {
            return null;
        }

        $payload = apcu_fetch($key);
        if (!is_array($payload)) {
            return null;
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        $value = $payload['value'] ?? null;
        if ($expiresAt <= $now || !is_array($value)) {
            apcu_delete($key);
            return null;
        }

        return $value;
    }

    private static function writeApcu(string $key, array $value, int $ttlSeconds, int $now): void
    {
        if (!fulguriteApcuAvailable()) {
            return;
        }

        apcu_store($key, [
            'expires_at' => $now + $ttlSeconds,
            'value' => $value,
        ], $ttlSeconds + 5);
    }

    private static function readFile(string $key, int $now): ?array
    {
        $path = self::cachePath($key);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return null;
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        $value = $payload['value'] ?? null;
        if ($expiresAt <= $now || !is_array($value)) {
            @unlink($path);
            return null;
        }

        return $value;
    }

    private static function writeFile(string $key, array $value, int $ttlSeconds, int $now): void
    {
        $path = self::cachePath($key);
        $payload = json_encode([
            'expires_at' => $now + $ttlSeconds,
            'value' => $value,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($payload) || $payload === '') {
            return;
        }

        $tmpPath = $path . '.tmp';
        if (@file_put_contents($tmpPath, $payload, LOCK_EX) === false) {
            @unlink($tmpPath);
            return;
        }

        @rename($tmpPath, $path);
    }
}
