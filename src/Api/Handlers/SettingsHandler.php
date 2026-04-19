<?php
// =============================================================================
// SettingsHandler.php — /api/v1/settings (admin)
// =============================================================================

class SettingsHandler {

    /** Keys allowed for API read/write (no sensitive secrets). */
    private const ALLOWED_KEYS = [
        'app_name',
        'app_url',
        'timezone',
        'server_timezone',
        'cron_run_minute',
        'api_enabled',
        'api_default_token_lifetime_days',
        'api_default_rate_limit_per_minute',
        'api_log_retention_days',
        'api_idempotency_retention_hours',
        'api_cors_allowed_origins',
        'weekly_report_enabled',
        'weekly_report_day',
        'weekly_report_hour',
        'integrity_check_enabled',
        'integrity_check_day',
        'integrity_check_hour',
        'maintenance_vacuum_enabled',
        'maintenance_vacuum_day',
        'maintenance_vacuum_hour',
    ];

    public static function index(array $args): void {
        ApiAuth::requireScope('settings:read');
        $values = [];
        foreach (self::ALLOWED_KEYS as $key) {
            $values[$key] = Database::getSetting($key, '');
        }
        ApiResponse::ok($values);
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('settings:read');
        $key = (string) $args['key'];
        if (!in_array($key, self::ALLOWED_KEYS, true)) {
            ApiResponse::error(404, 'unknown_setting', 'Cle inconnue ou non exposee');
        }
        ApiResponse::ok(['key' => $key, 'value' => Database::getSetting($key, '')]);
    }

    public static function update(array $args): void {
        ApiAuth::requireScope('settings:write');
        $key = (string) $args['key'];
        if (!in_array($key, self::ALLOWED_KEYS, true)) {
            ApiResponse::error(404, 'unknown_setting', 'Cle inconnue ou non exposee');
        }
        $body = ApiRequest::body();
        if (!array_key_exists('value', $body)) {
            ApiResponse::error(422, 'validation_error', 'Champ value requis');
        }
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        Database::setSetting($key, (string) $body['value']);
        ApiResponse::ok(['key' => $key, 'value' => Database::getSetting($key, '')]);
    }
}
