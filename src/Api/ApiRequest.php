<?php
// =============================================================================
// ApiRequest.php — Access normalized to the corps and to the settings of the request// =============================================================================

class ApiRequest {

    private static ?array $cachedBody = null;

    public static function method(): string {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function path(): string {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        // Normalise: enleve the prefixe /api/v1
        $prefix = '/api/v1';
        if (str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }
        if ($path === '' || $path === false) $path = '/';
        return rtrim($path, '/') ?: '/';
    }

    public static function query(string $key, mixed $default = null): mixed {
        return $_GET[$key] ?? $default;
    }

    public static function queryInt(string $key, int $default = 0): int {
        return (int) ($_GET[$key] ?? $default);
    }

    public static function pageParams(int $defaultPerPage = 25, int $maxPerPage = 200): array {
        $page = max(1, self::queryInt('page', 1));
        $perPage = self::queryInt('per_page', $defaultPerPage);
        if ($perPage < 1) $perPage = $defaultPerPage;
        if ($perPage > $maxPerPage) $perPage = $maxPerPage;
        return [$page, $perPage];
    }

    public static function body(): array {
        if (self::$cachedBody !== null) {
            return self::$cachedBody;
        }
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') {
            self::$cachedBody = $_POST ?: [];
            return self::$cachedBody;
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::error(400, 'invalid_json', 'Corps JSON invalide : ' . json_last_error_msg());
        }
        self::$cachedBody = is_array($decoded) ? $decoded : [];
        return self::$cachedBody;
    }

    public static function input(string $key, mixed $default = null): mixed {
        $body = self::body();
        if (array_key_exists($key, $body)) return $body[$key];
        return $_GET[$key] ?? $default;
    }

    public static function require(string $key): mixed {
        $body = self::body();
        if (!array_key_exists($key, $body) || $body[$key] === null || $body[$key] === '') {
            ApiResponse::error(422, 'missing_field', "Champ requis : $key", ['field' => $key]);
        }
        return $body[$key];
    }

    public static function isDryRun(): bool {
        $h = $_SERVER['HTTP_X_DRY_RUN'] ?? '';
        return $h === '1' || strcasecmp((string) $h, 'true') === 0;
    }

    public static function idempotencyKey(): ?string {
        $h = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '';
        $h = trim((string) $h);
        return $h !== '' ? substr($h, 0, 128) : null;
    }
}
