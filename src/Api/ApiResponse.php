<?php
// =============================================================================
// ApiResponse.php — Helpers of response JSON for l public API
// =============================================================================

class ApiResponse {

    private static int $statusCode = 200;
    private static ?array $bodyForLog = null;

    public static function statusCode(): int {
        return self::$statusCode;
    }

    public static function lastBody(): ?array {
        return self::$bodyForLog;
    }

    public static function ok(mixed $data = null, array $meta = []): void {
        self::send(200, ['data' => $data, 'meta' => $meta ?: null, 'error' => null]);
    }

    public static function created(mixed $data = null, array $meta = []): void {
        self::send(201, ['data' => $data, 'meta' => $meta ?: null, 'error' => null]);
    }

    public static function noContent(): void {
        self::$statusCode = 204;
        http_response_code(204);
        exit;
    }

    public static function paginated(array $items, int $page, int $perPage, int $total): void {
        self::send(200, [
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
            ],
            'error' => null,
        ]);
    }

    public static function error(int $status, string $code, string $message, array $extra = []): void {
        self::send($status, [
            'data' => null,
            'meta' => null,
            'error' => array_merge(['code' => $code, 'message' => $message], $extra),
        ]);
    }

    public static function send(int $status, array $body): void {
        self::$statusCode = $status;
        self::$bodyForLog = $body;
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function rawJson(int $status, array $body): void {
        self::$statusCode = $status;
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
