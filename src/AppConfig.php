<?php

class AppConfig {
    private static ?array $defaultSettingsCache = null;
    private static ?array $defaultRolesCache = null;
    private static array $settingsCache = [];
    private static int $settingsCacheVersion = -1;
    private static ?bool $settingsPreloadEnabled = null;
    private static bool $settingsPreloadAttempted = false;
    private static bool $settingsPreloaded = false;

    private const SETTINGS_CACHE_VERSION_GLOBAL = '__fulgurite_settings_cache_version';

    private const ROLE_BADGES = [
        'gray' => 'badge-gray',
        'blue' => 'badge-blue',
        'yellow' => 'badge-yellow',
        'purple' => 'badge-purple',
        'green' => 'badge-green',
        'red' => 'badge-red',
    ];

    public static function defaultSettings(): array {
        if (self::$defaultSettingsCache !== null) {
            return self::$defaultSettingsCache;
        }

        self::$defaultSettingsCache = [
            'interface_app_name' => defined('APP_NAME') ? (string) APP_NAME : 'Fulgurite',
            'interface_app_subtitle' => 'Stack de sauvegarde self-hosted',
            'interface_logo_letter' => 'R',
            'interface_login_tagline' => 'Administration des sauvegardes restic',
            'interface_timezone' => 'server',
            'interface_dashboard_refresh_seconds' => '60',
            'interface_stats_default_period_days' => '7',
            'mail_enabled' => defined('MAIL_ENABLED') && MAIL_ENABLED ? '1' : '0',
            'mail_from' => defined('MAIL_FROM') ? (string) MAIL_FROM : 'fulgurite@localhost',
            'mail_from_name' => defined('MAIL_FROM_NAME') ? (string) MAIL_FROM_NAME : 'Fulgurite',
            'mail_to' => defined('MAIL_TO') ? (string) MAIL_TO : '',
            'smtp_host' => defined('SMTP_HOST') ? (string) SMTP_HOST : '',
            'smtp_port' => defined('SMTP_PORT') ? (string) SMTP_PORT : '587',
            'smtp_user' => defined('SMTP_USER') ? (string) SMTP_USER : '',
            'smtp_pass' => defined('SMTP_PASS') ? (string) SMTP_PASS : '',
            'smtp_tls' => defined('SMTP_TLS') && SMTP_TLS ? '1' : '0',
            'backup_alert_hours' => defined('BACKUP_ALERT_HOURS') ? (string) BACKUP_ALERT_HOURS : '25',
            'backup_server_host' => defined('BACKUP_SERVER_HOST') ? (string) BACKUP_SERVER_HOST : '',
            'backup_server_sftp_user' => defined('BACKUP_SERVER_SFTP_USER') ? (string) BACKUP_SERVER_SFTP_USER : 'backup',
            'backup_job_default_schedule_enabled' => '0',
            'backup_job_default_schedule_hour' => '2',
            'backup_job_default_schedule_days' => '1',
            'backup_job_default_notify_on_failure' => '1',
            'backup_job_default_retention_enabled' => '0',
            'backup_job_default_retention_keep_last' => '0',
            'backup_job_default_retention_keep_daily' => '0',
            'backup_job_default_retention_keep_weekly' => '0',
            'backup_job_default_retention_keep_monthly' => '0',
            'backup_job_default_retention_keep_yearly' => '0',
            'backup_job_default_retention_prune' => '1',
            'job_retry_policy' => '{"inherit":false,"enabled":true,"max_retries":1,"delay_seconds":20,"retry_on":["lock","network","timeout"]}',
            'restore_default_target' => '/',
            'restore_remote_default_path' => '/',
            'restore_partial_default_target' => '/tmp/restore-partial',
            'restore_partial_remote_default_path' => '/',
            'restore_managed_local_root' => dirname(DB_PATH) . '/restores',
            'restore_managed_remote_root' => '/var/tmp/fulgurite-restores',
            'restore_append_context_subdir' => '1',
            'restore_original_global_enabled' => '0',
            'restore_original_allowed_paths' => '',
            'restore_history_page_size' => '30',
            'session_absolute_lifetime_minutes' => defined('SESSION_LIFETIME') ? (string) max(5, (int) ceil(SESSION_LIFETIME / 60)) : '480',
            'session_inactivity_minutes' => '30',
            'session_warning_minutes' => '2',
            'session_db_touch_interval_seconds' => '180',
            'session_db_touch_coalesce_seconds' => '60',
            'session_strict_fingerprint' => '1',
            'reauth_max_age_seconds' => '300',
            'second_factor_pending_ttl_seconds' => '300',
            'discord_enabled' => '0',
            'discord_webhook_url' => '',
            'slack_enabled' => '0',
            'slack_webhook_url' => '',
            'telegram_enabled' => '0',
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
            'ntfy_enabled' => '0',
            'ntfy_url' => '',
            'ntfy_topic' => '',
            'webhook_enabled' => '0',
            'webhook_url' => '',
            'webhook_auth_token' => '',
            'teams_enabled' => '0',
            'teams_webhook_url' => '',
            'gotify_enabled' => '0',
            'gotify_url' => '',
            'gotify_token' => '',
            'in_app_enabled' => '0',
            'web_push_enabled' => '0',
            'app_notifications_retention_days' => '7',
            'weekly_report_enabled' => '0',
            'weekly_report_day' => '1',
            'weekly_report_hour' => '8',
            'weekly_report_notification_policy' => '{"inherit":true,"events":{"report":[]}}',
            'integrity_check_enabled' => '1',
            'integrity_check_day' => '1',
            'integrity_check_hour' => '3',
            'integrity_check_notification_policy' => '{"inherit":false,"events":{"failure":["discord","telegram","ntfy"],"success":[]}}',
            'maintenance_vacuum_enabled' => '1',
            'maintenance_vacuum_day' => '7',
            'maintenance_vacuum_hour' => '4',
            'maintenance_vacuum_notification_policy' => '{"inherit":false,"events":{"failure":[],"success":[]}}',
            'audit_logs_page_size' => '50',
            'audit_dashboard_recent_limit' => '8',
            'audit_activity_retention_days' => '180',
            'audit_restore_retention_days' => '180',
            'audit_cron_retention_days' => '90',
            'audit_rate_limit_retention_days' => '30',
            'audit_login_attempt_retention_days' => '30',
            'audit_job_queue_retention_days' => '14',
            'audit_archive_retention_days' => '365',
            'audit_repo_stats_hourly_retention_days' => '30',
            'audit_repo_stats_downsample_batch' => '5000',
            'login_max_attempts' => '5',
            'login_lockout_minutes' => '15',
            'infisical_enabled' => '0',
            'infisical_url' => '',
            'infisical_token' => '',
            'infisical_project_id' => '',
            'infisical_environment' => 'prod',
            'infisical_secret_path' => '/',
            'infisical_allowed_hosts' => '',
            'infisical_allowed_host_patterns' => '',
            'infisical_allowed_cidrs' => '',
            'infisical_allowed_port' => '',
            'infisical_allow_http' => '0',
            'api_webhook_store_response_body_debug' => '0',
            'max_sessions_per_user' => '5',
            'force_admin_2fa' => '0',
            'login_notifications_enabled' => '0',
            'login_notifications_new_ip_only' => '1',
            'login_notification_policy' => '{"inherit":true,"events":{"login":[]}}',
            'security_alert_notification_policy' => '{"inherit":true,"events":{"alert":[]}}',
            'api_rate_limit_default_hits' => '20',
            'api_rate_limit_default_window_seconds' => '60',
            'api_rate_limit_restore_hits' => '5',
            'api_rate_limit_restore_window_seconds' => '60',
            'api_rate_limit_restore_partial_hits' => '5',
            'api_rate_limit_restore_partial_window_seconds' => '60',
            'api_rate_limit_run_backup_hits' => '10',
            'api_rate_limit_run_backup_window_seconds' => '60',
            'api_rate_limit_run_copy_hits' => '10',
            'api_rate_limit_run_copy_window_seconds' => '60',
            'api_rate_limit_explore_view_hits' => '120',
            'api_rate_limit_explore_view_window_seconds' => '60',
            'api_rate_limit_search_files_hits' => '90',
            'api_rate_limit_search_files_window_seconds' => '60',
            'api_rate_limit_manage_cron_hits' => '20',
            'api_rate_limit_manage_cron_window_seconds' => '60',
            'api_rate_limit_manage_scheduler_hits' => '20',
            'api_rate_limit_manage_scheduler_window_seconds' => '60',
            'api_rate_limit_manage_worker_hits' => '20',
            'api_rate_limit_manage_worker_window_seconds' => '60',
            'api_rate_limit_reauth_hits' => '10',
            'api_rate_limit_reauth_window_seconds' => '60',
            'api_rate_limit_webauthn_auth_hits' => '10',
            'api_rate_limit_webauthn_auth_window_seconds' => '60',
            'api_rate_limit_webauthn_reg_hits' => '10',
            'api_rate_limit_webauthn_reg_window_seconds' => '60',
            'api_rate_limit_apcu_enabled' => '1',
            'api_rate_limit_apcu_lock_ttl_seconds' => '2',
            'api_rate_limit_apcu_ttl_padding_seconds' => '5',
            'cron_token' => '',
            'cron_entry_base_url' => 'http://127.0.0.1/cron.php',
            'cron_run_minute' => '0',
            'roles_json' => self::encodeRoles(self::defaultRoles()),
            'explore_view_cache_ttl' => '20',
            'explore_page_size' => '200',
            'explore_search_max_results' => '200',
            'explore_max_file_size_mb' => defined('MAX_FILE_SIZE') ? (string) max(1, (int) round(MAX_FILE_SIZE / 1048576)) : '5',
            'search_index_warm_batch_per_run' => '4',
            'search_index_adhoc_retention_days' => '7',
            'search_index_recent_snapshots' => '1',
            'restic_snapshots_cache_ttl' => '60',
            'restic_ls_cache_ttl' => '30',
            'restic_stats_cache_ttl' => '300',
            'restic_search_cache_ttl' => '60',
            'restic_tree_cache_ttl' => '900',
            'worker_default_name' => 'default',
            'worker_sleep_seconds' => '5',
            'worker_limit' => '3',
            'worker_stale_minutes' => '30',
            'worker_heartbeat_stale_seconds' => '20',
            'worker_heartbeat_write_min_interval_seconds' => '15',
            'performance_slow_request_threshold_ms' => '800',
            'performance_slow_sql_threshold_ms' => '150',
            'performance_slow_restic_threshold_ms' => '300',
            'performance_slow_command_threshold_ms' => '300',
            'performance_metrics_cache_ttl_seconds' => '5',
            'performance_metrics_refresh_interval_seconds' => '15',
            'performance_recent_jobs_limit' => '20',
            'performance_log_tail_lines' => '20',
            'performance_repo_size_scan_limit' => '8',
            'performance_repo_size_top_limit' => '5',
            'disk_monitoring_enabled' => '1',
            'disk_local_warning_percent' => '80',
            'disk_local_critical_percent' => '90',
            'disk_local_warning_free_gb' => '50',
            'disk_local_critical_free_gb' => '20',
            'disk_remote_warning_percent' => '80',
            'disk_remote_critical_percent' => '90',
            'disk_remote_warning_free_gb' => '50',
            'disk_remote_critical_free_gb' => '20',
            'disk_preflight_enabled' => '1',
            'disk_preflight_margin_percent' => '25',
            'disk_preflight_min_free_gb' => '10',
            'disk_monitor_history_retention_days' => '30',
            'disk_space_notification_policy' => '{"inherit":false,"events":{"warning":["in_app"],"critical":["in_app","discord","telegram","ntfy"],"recovered":["in_app"]}}',
            'webauthn_rp_name' => defined('APP_NAME') ? (string) APP_NAME : 'Fulgurite',
            'webauthn_rp_id_override' => '',
            'webauthn_registration_timeout_ms' => '60000',
            'webauthn_auth_timeout_ms' => '60000',
            'webauthn_user_verification' => 'preferred',
            'webauthn_resident_key' => 'preferred',
            'webauthn_require_resident_key' => '0',
            'webauthn_attestation' => 'none',
            'webauthn_login_autostart' => '1',
            'totp_issuer' => defined('APP_NAME') ? (string) APP_NAME : 'Fulgurite',

            // ── public API ────────────────────────────────────────────────
            'api_enabled' => '0',
            'api_default_token_lifetime_days' => '90',
            'api_default_rate_limit_per_minute' => '120',
            'api_log_retention_days' => '30',
            'api_idempotency_retention_hours' => '48',
            'api_cors_allowed_origins' => '',
            'api_token_usage_coalesce_seconds' => '60',
            'api_token_usage_coalesce_by_ip' => '0',
            'api_token_rate_limit_window_seconds' => '60',
            'api_token_rate_limit_apcu_enabled' => '1',
            'api_token_rate_limit_db_fallback_enabled' => '1',
            'api_token_rate_limit_apcu_lock_ttl_seconds' => '2',
            'api_token_rate_limit_apcu_ttl_padding_seconds' => '5',
            'api_token_log_sampling_percent' => '100',
            'api_token_log_buffer_batch_size' => '20',
            'api_token_log_buffer_flush_seconds' => '2',
            'api_token_log_buffer_max_entries' => '500',
        ];

        return self::$defaultSettingsCache;
    }

    public static function defaultRoles(): array {
        if (self::$defaultRolesCache !== null) {
            return self::$defaultRolesCache;
        }

        self::$defaultRolesCache = [
            [
                'key' => ROLE_VIEWER,
                'label' => 'Viewer',
                'description' => 'Lecture seule',
                'level' => 10,
                'badge' => 'gray',
                'system' => true,
            ],
            [
                'key' => ROLE_OPERATOR,
                'label' => 'Operator',
                'description' => 'Lancer les sauvegardes et copies',
                'level' => 20,
                'badge' => 'blue',
                'system' => true,
            ],
            [
                'key' => ROLE_RESTORE_OPERATOR,
                'label' => 'Restore Operator',
                'description' => 'Autorise aussi les restaurations',
                'level' => 30,
                'badge' => 'yellow',
                'system' => true,
            ],
            [
                'key' => ROLE_ADMIN,
                'label' => 'Admin',
                'description' => 'Acces complet',
                'level' => 40,
                'badge' => 'purple',
                'system' => true,
            ],
        ];

        return self::$defaultRolesCache;
    }

    // ── public API ─────────────────────────────────────────────────────────
    public static function isApiEnabled(): bool {
        return self::getBool('api_enabled', false);
    }

    public static function getApiDefaultRateLimit(): int {
        return self::getInt('api_default_rate_limit_per_minute', 120, 1, 100000);
    }

    public static function getApiDefaultTokenLifetimeDays(): int {
        return self::getInt('api_default_token_lifetime_days', 90, 0, 36500);
    }

    public static function getApiLogRetentionDays(): int {
        return self::getInt('api_log_retention_days', 30, 1, 3650);
    }

    public static function getApiIdempotencyRetentionHours(): int {
        return self::getInt('api_idempotency_retention_hours', 48, 1, 8760);
    }

    public static function getApiCorsAllowedOrigins(): array {
        return self::getCsvValues('api_cors_allowed_origins', '');
    }

    public static function apiTokenUsageCoalesceSeconds(): int {
        return self::getInt('api_token_usage_coalesce_seconds', 60, 1, 3600);
    }

    public static function apiTokenUsageCoalesceByIp(): bool {
        return self::getBool('api_token_usage_coalesce_by_ip', false);
    }

    public static function apiTokenRateLimitWindowSeconds(): int {
        return self::getInt('api_token_rate_limit_window_seconds', 60, 1, 3600);
    }

    public static function apiTokenRateLimitApcuEnabled(): bool {
        return self::getBool('api_token_rate_limit_apcu_enabled', true);
    }

    public static function apiTokenRateLimitDbFallbackEnabled(): bool {
        return self::getBool('api_token_rate_limit_db_fallback_enabled', true);
    }

    public static function apiTokenRateLimitApcuLockTtlSeconds(): int {
        return self::getInt('api_token_rate_limit_apcu_lock_ttl_seconds', 2, 1, 10);
    }

    public static function apiTokenRateLimitApcuTtlPaddingSeconds(): int {
        return self::getInt('api_token_rate_limit_apcu_ttl_padding_seconds', 5, 0, 60);
    }

    public static function apiTokenLogSamplingPercent(): int {
        return self::getInt('api_token_log_sampling_percent', 100, 1, 100);
    }

    public static function apiTokenLogBufferBatchSize(): int {
        return self::getInt('api_token_log_buffer_batch_size', 20, 1, 500);
    }

    public static function apiTokenLogBufferFlushSeconds(): int {
        return self::getInt('api_token_log_buffer_flush_seconds', 2, 1, 60);
    }

    public static function apiTokenLogBufferMaxEntries(): int {
        return self::getInt('api_token_log_buffer_max_entries', 500, 50, 20000);
    }

    public static function get(string $key, ?string $default = null): string {
        self::ensureSettingsCacheFresh();

        $defaults = self::defaultSettings();
        $fallback = $default ?? ($defaults[$key] ?? '');

        if (array_key_exists($key, self::$settingsCache)) {
            return self::$settingsCache[$key];
        }

        if (self::shouldPreloadSettings() && !Database::isSensitiveSetting($key)) {
            self::preloadSettingsCache();
            if (array_key_exists($key, self::$settingsCache)) {
                return self::$settingsCache[$key];
            }
        }

        $value = Database::getSetting($key, $fallback);
        self::$settingsCache[$key] = $value;
        return $value;
    }

    public static function getBool(string $key, bool $default = false): bool {
        return self::get($key, $default ? '1' : '0') === '1';
    }

    public static function getInt(string $key, int $default, ?int $min = null, ?int $max = null): int {
        $value = (int) self::get($key, (string) $default);
        if ($min !== null && $value < $min) {
            $value = $min;
        }
        if ($max !== null && $value > $max) {
            $value = $max;
        }
        return $value;
    }

    public static function getEnum(string $key, string $default, array $allowed): string {
        $value = trim(strtolower(self::get($key, $default)));
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function getCsvValues(string $key, string $default = ''): array {
        $raw = self::get($key, $default);
        $values = array_values(array_filter(array_map(
            static fn(string $value): string => trim($value),
            explode(',', $raw)
        ), static fn(string $value): bool => $value !== ''));

        return $values;
    }

    public static function backupAlertHours(): int {
        return self::getInt('backup_alert_hours', 25, 1, 8760);
    }

    public static function appName(): string {
        $name = trim(self::get('interface_app_name', defined('APP_NAME') ? (string) APP_NAME : 'Fulgurite'));
        return $name !== '' ? $name : (defined('APP_NAME') ? (string) APP_NAME : 'Fulgurite');
    }

    public static function appSubtitle(): string {
        return trim(self::get('interface_app_subtitle', 'Stack de sauvegarde self-hosted'));
    }

    public static function appLogoLetter(): string {
        $letter = trim(self::get('interface_logo_letter', 'R'));
        return $letter !== '' ? strtoupper(substr($letter, 0, 1)) : 'R';
    }

    public static function loginTagline(): string {
        return trim(self::get('interface_login_tagline', 'Administration des sauvegardes restic'));
    }

    public static function serverTimezone(): string {
        $timezone = trim((string) ini_get('date.timezone'));
        if ($timezone !== '' && self::isValidTimezone($timezone)) {
            return $timezone;
        }

        $fallback = date_default_timezone_get() ?: 'UTC';
        return self::isValidTimezone($fallback) ? $fallback : 'UTC';
    }

    public static function timezonePreference(): string {
        $timezone = trim(self::get('interface_timezone', 'server'));
        if ($timezone === '' || in_array(strtolower($timezone), ['server', '__server__'], true)) {
            return 'server';
        }

        return self::isValidTimezone($timezone) ? $timezone : 'server';
    }

    public static function timezoneUsesServerDefault(): bool {
        return self::timezonePreference() === 'server';
    }

    public static function timezone(): string {
        $timezone = self::timezonePreference();
        return $timezone === 'server' ? self::serverTimezone() : $timezone;
    }

    public static function timezoneLabel(): string {
        $timezone = self::timezone();
        return self::timezoneUsesServerDefault() ? ($timezone . ' (serveur)') : $timezone;
    }

    public static function isValidTimezone(string $timezone): bool {
        try {
            new DateTimeZone($timezone);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function dashboardRefreshSeconds(): int {
        return self::getInt('interface_dashboard_refresh_seconds', 60, 10, 3600);
    }

    public static function statsDefaultPeriodDays(): int {
        $value = self::getInt('interface_stats_default_period_days', 7, 7, 90);
        return in_array($value, [7, 14, 30, 90], true) ? $value : 7;
    }

    public static function backupServerHost(): string {
        return trim(self::get('backup_server_host'));
    }

    public static function backupServerSftpUser(): string {
        $user = trim(self::get('backup_server_sftp_user', 'backup'));
        return $user !== '' ? $user : 'backup';
    }

    public static function mailFromName(): string {
        $name = trim(self::get('mail_from_name', 'Fulgurite'));
        return $name !== '' ? $name : 'Fulgurite';
    }

    public static function sessionAbsoluteLifetimeSeconds(): int {
        return self::getInt('session_absolute_lifetime_minutes', defined('SESSION_LIFETIME') ? (int) ceil(SESSION_LIFETIME / 60) : 480, 5, 10080) * 60;
    }

    public static function auditLogsPageSize(): int {
        return self::getInt('audit_logs_page_size', 50, 10, 500);
    }

    public static function auditDashboardRecentLimit(): int {
        return self::getInt('audit_dashboard_recent_limit', 8, 1, 50);
    }

    public static function auditActivityRetentionDays(): int {
        return self::getInt('audit_activity_retention_days', 180, 1, 3650);
    }

    public static function auditRestoreRetentionDays(): int {
        return self::getInt('audit_restore_retention_days', 180, 1, 3650);
    }

    public static function auditCronRetentionDays(): int {
        return self::getInt('audit_cron_retention_days', 90, 1, 3650);
    }

    public static function auditRateLimitRetentionDays(): int {
        return self::getInt('audit_rate_limit_retention_days', 30, 1, 3650);
    }

    public static function auditLoginAttemptRetentionDays(): int {
        return self::getInt('audit_login_attempt_retention_days', 30, 1, 3650);
    }

    public static function auditJobQueueRetentionDays(): int {
        return self::getInt('audit_job_queue_retention_days', 14, 1, 3650);
    }

    public static function auditArchiveRetentionDays(): int {
        return self::getInt('audit_archive_retention_days', 365, 1, 3650);
    }

    public static function auditRepoStatsHourlyRetentionDays(): int {
        return self::getInt('audit_repo_stats_hourly_retention_days', 30, 1, 3650);
    }

    public static function auditRepoStatsDownsampleBatch(): int {
        return self::getInt('audit_repo_stats_downsample_batch', 5000, 100, 50000);
    }

    public static function appNotificationsRetentionDays(): int {
        return self::getInt('app_notifications_retention_days', 7, 1, 3650);
    }

    public static function sessionDbTouchIntervalSeconds(): int {
        return self::getInt('session_db_touch_interval_seconds', 180, 15, 3600);
    }

    public static function sessionDbTouchCoalesceSeconds(): int {
        return self::getInt('session_db_touch_coalesce_seconds', 60, 5, 3600);
    }

    public static function sessionStrictFingerprint(): bool {
        return self::getBool('session_strict_fingerprint', true);
    }

    public static function reauthMaxAgeSeconds(): int {
        return self::getInt('reauth_max_age_seconds', 300, 30, 86400);
    }

    public static function secondFactorPendingTtlSeconds(): int {
        return self::getInt('second_factor_pending_ttl_seconds', 300, 30, 3600);
    }

    public static function forceAdminTwoFactor(): bool {
        return self::getBool('force_admin_2fa', false);
    }

    public static function restoreDefaultTarget(): string {
        return self::restoreManagedLocalRoot();
    }

    public static function restoreRemoteDefaultPath(): string {
        return self::restoreManagedRemoteRoot();
    }

    public static function restorePartialDefaultTarget(): string {
        return self::restoreManagedLocalRoot();
    }

    public static function restorePartialRemoteDefaultPath(): string {
        return self::restoreManagedRemoteRoot();
    }

    public static function restoreManagedLocalRoot(): string {
        $default = dirname(DB_PATH) . '/restores';
        $target = trim(self::get('restore_managed_local_root', $default));
        return $target !== '' ? $target : $default;
    }

    public static function restoreManagedRemoteRoot(): string {
        $default = '/var/tmp/fulgurite-restores';
        $path = trim(self::get('restore_managed_remote_root', $default));
        return $path !== '' ? $path : $default;
    }

    public static function restoreAppendContextSubdir(): bool {
        return self::getBool('restore_append_context_subdir', true);
    }

    public static function restoreOriginalGlobalEnabled(): bool {
        return self::getBool('restore_original_global_enabled', false);
    }

    public static function restoreOriginalAllowedPaths(): array {
        return self::getCsvValues('restore_original_allowed_paths', '');
    }

    public static function restoreManagedDirectoryMode(): int {
        return 0750;
    }

    public static function restoreManagedDirectoryModeLabel(): string {
        return '0750';
    }

    public static function restoreHistoryPageSize(): int {
        return self::getInt('restore_history_page_size', 30, 10, 500);
    }

    public static function exploreViewCacheTtl(): int {
        return self::getInt('explore_view_cache_ttl', 20, 0, 3600);
    }

    public static function explorePageSize(): int {
        return self::getInt('explore_page_size', 200, 10, 2000);
    }

    public static function exploreSearchMaxResults(): int {
        return self::getInt('explore_search_max_results', 200, 10, 5000);
    }

    public static function exploreMaxFileSizeBytes(): int {
        $mb = self::getInt('explore_max_file_size_mb', defined('MAX_FILE_SIZE') ? (int) round(MAX_FILE_SIZE / 1048576) : 5, 1, 1024);
        return $mb * 1048576;
    }

    public static function searchIndexWarmBatchPerRun(): int {
        return self::getInt('search_index_warm_batch_per_run', 4, 1, 50);
    }

    public static function searchIndexAdhocRetentionDays(): int {
        return self::getInt('search_index_adhoc_retention_days', 7, 1, 365);
    }

    public static function searchIndexRecentSnapshots(): int {
        return self::getInt('search_index_recent_snapshots', 1, 1, 50);
    }

    public static function resticSnapshotsCacheTtl(): int {
        return self::getInt('restic_snapshots_cache_ttl', 60, 0, 3600);
    }

    public static function resticLsCacheTtl(): int {
        return self::getInt('restic_ls_cache_ttl', 30, 0, 3600);
    }

    public static function resticStatsCacheTtl(): int {
        return self::getInt('restic_stats_cache_ttl', 300, 0, 86400);
    }

    public static function resticSearchCacheTtl(): int {
        return self::getInt('restic_search_cache_ttl', 60, 0, 3600);
    }

    public static function resticTreeCacheTtl(): int {
        return self::getInt('restic_tree_cache_ttl', 900, 0, 86400);
    }

    public static function cronEntryBaseUrl(): string {
        $url = trim(self::get('cron_entry_base_url', 'http://127.0.0.1/cron.php'));
        return $url !== '' ? $url : 'http://127.0.0.1/cron.php';
    }

    public static function cronRunMinute(): int {
        return self::getInt('cron_run_minute', 0, 0, 59);
    }

    public static function workerSleepSeconds(): int {
        return self::getInt('worker_sleep_seconds', 5, 1, 300);
    }

    public static function workerDefaultName(): string {
        $name = trim(self::get('worker_default_name', 'default'));
        return $name !== '' ? preg_replace('/[^a-zA-Z0-9._-]/', '-', $name) ?: 'default' : 'default';
    }

    public static function workerLimit(): int {
        return self::getInt('worker_limit', 3, 1, 100);
    }

    public static function workerStaleMinutes(): int {
        return self::getInt('worker_stale_minutes', 30, 1, 1440);
    }

    public static function workerHeartbeatStaleSeconds(): int {
        return self::getInt('worker_heartbeat_stale_seconds', 20, 5, 3600);
    }

    public static function workerHeartbeatWriteMinIntervalSeconds(): int {
        return self::getInt('worker_heartbeat_write_min_interval_seconds', 15, 1, 3600);
    }

    public static function performanceSlowRequestThresholdMs(): float {
        return (float) self::getInt('performance_slow_request_threshold_ms', 800, 50, 600000);
    }

    public static function performanceSlowSqlThresholdMs(): float {
        return (float) self::getInt('performance_slow_sql_threshold_ms', 150, 10, 600000);
    }

    public static function performanceSlowResticThresholdMs(): float {
        return (float) self::getInt('performance_slow_restic_threshold_ms', 300, 10, 600000);
    }

    public static function performanceSlowCommandThresholdMs(): int {
        return self::getInt('performance_slow_command_threshold_ms', 300, 10, 600000);
    }

    public static function performanceMetricsCacheTtlSeconds(): int {
        return self::getInt('performance_metrics_cache_ttl_seconds', 5, 0, 300);
    }

    public static function performanceMetricsRefreshIntervalSeconds(): int {
        return self::getInt('performance_metrics_refresh_interval_seconds', 15, 5, 3600);
    }

    public static function performanceRecentJobsLimit(): int {
        return self::getInt('performance_recent_jobs_limit', 20, 5, 200);
    }

    public static function performanceLogTailLines(): int {
        return self::getInt('performance_log_tail_lines', 20, 5, 200);
    }

    public static function performanceRepoSizeScanLimit(): int {
        return self::getInt('performance_repo_size_scan_limit', 8, 1, 100);
    }

    public static function performanceRepoSizeTopLimit(): int {
        return self::getInt('performance_repo_size_top_limit', 5, 1, 50);
    }

    public static function diskMonitoringEnabled(): bool {
        return self::getBool('disk_monitoring_enabled', true);
    }

    public static function diskLocalWarningPercent(): float {
        return (float) self::getInt('disk_local_warning_percent', 80, 1, 100);
    }

    public static function diskLocalCriticalPercent(): float {
        return (float) self::getInt('disk_local_critical_percent', 90, 1, 100);
    }

    public static function diskLocalWarningFreeBytes(): int {
        return self::getInt('disk_local_warning_free_gb', 50, 1, 1048576) * 1073741824;
    }

    public static function diskLocalCriticalFreeBytes(): int {
        return self::getInt('disk_local_critical_free_gb', 20, 1, 1048576) * 1073741824;
    }

    public static function diskRemoteWarningPercent(): float {
        return (float) self::getInt('disk_remote_warning_percent', 80, 1, 100);
    }

    public static function diskRemoteCriticalPercent(): float {
        return (float) self::getInt('disk_remote_critical_percent', 90, 1, 100);
    }

    public static function diskRemoteWarningFreeBytes(): int {
        return self::getInt('disk_remote_warning_free_gb', 50, 1, 1048576) * 1073741824;
    }

    public static function diskRemoteCriticalFreeBytes(): int {
        return self::getInt('disk_remote_critical_free_gb', 20, 1, 1048576) * 1073741824;
    }

    public static function diskPreflightEnabled(): bool {
        return self::getBool('disk_preflight_enabled', true);
    }

    public static function diskPreflightMarginPercent(): int {
        return self::getInt('disk_preflight_margin_percent', 25, 0, 500);
    }

    public static function diskPreflightMinFreeBytes(): int {
        return self::getInt('disk_preflight_min_free_gb', 10, 1, 1048576) * 1073741824;
    }

    public static function diskMonitorHistoryRetentionDays(): int {
        return self::getInt('disk_monitor_history_retention_days', 30, 1, 3650);
    }

    public static function webauthnRpName(): string {
        $name = trim(self::get('webauthn_rp_name', self::appName()));
        return $name !== '' ? $name : self::appName();
    }

    public static function webauthnRpIdOverride(): string {
        return trim(strtolower(self::get('webauthn_rp_id_override', '')));
    }

    public static function webauthnRegistrationTimeoutMs(): int {
        return self::getInt('webauthn_registration_timeout_ms', 60000, 1000, 300000);
    }

    public static function webauthnAuthTimeoutMs(): int {
        return self::getInt('webauthn_auth_timeout_ms', 60000, 1000, 300000);
    }

    public static function webauthnUserVerification(): string {
        return self::getEnum('webauthn_user_verification', 'preferred', ['required', 'preferred', 'discouraged']);
    }

    public static function webauthnResidentKey(): string {
        return self::getEnum('webauthn_resident_key', 'preferred', ['required', 'preferred', 'discouraged']);
    }

    public static function webauthnRequireResidentKey(): bool {
        return self::getBool('webauthn_require_resident_key', false);
    }

    public static function webauthnAttestation(): string {
        return self::getEnum('webauthn_attestation', 'none', ['none', 'direct', 'indirect', 'enterprise']);
    }

    public static function webauthnLoginAutostart(): bool {
        return self::getBool('webauthn_login_autostart', true);
    }

    public static function totpIssuer(): string {
        $issuer = trim(self::get('totp_issuer', self::appName()));
        return $issuer !== '' ? $issuer : self::appName();
    }

    public static function getApiRateLimit(string $endpoint, int $defaultHits = 20, int $defaultWindow = 60): array {
        $normalizedEndpoint = preg_replace('/[^a-z0-9_]+/', '_', strtolower($endpoint)) ?: 'default';
        $globalHits = self::getInt('api_rate_limit_default_hits', $defaultHits, 1, 100000);
        $globalWindow = self::getInt('api_rate_limit_default_window_seconds', $defaultWindow, 1, 86400);

        return [
            'hits' => self::getInt('api_rate_limit_' . $normalizedEndpoint . '_hits', $globalHits, 1, 100000),
            'window_seconds' => self::getInt('api_rate_limit_' . $normalizedEndpoint . '_window_seconds', $globalWindow, 1, 86400),
        ];
    }

    public static function apiRateLimitApcuEnabled(): bool {
        return self::getBool('api_rate_limit_apcu_enabled', true);
    }

    public static function apiRateLimitApcuLockTtlSeconds(): int {
        return self::getInt('api_rate_limit_apcu_lock_ttl_seconds', 2, 1, 10);
    }

    public static function apiRateLimitApcuTtlPaddingSeconds(): int {
        return self::getInt('api_rate_limit_apcu_ttl_padding_seconds', 5, 0, 60);
    }

    public static function getRoles(): array {
        $raw = self::get('roles_json', self::defaultSettings()['roles_json']);
        $decoded = json_decode($raw, true);
        return self::sanitizeRoles(is_array($decoded) ? $decoded : []);
    }

    private static function ensureSettingsCacheFresh(): void {
        $currentVersion = (int) ($GLOBALS[self::SETTINGS_CACHE_VERSION_GLOBAL] ?? 0);
        if (self::$settingsCacheVersion === $currentVersion) {
            return;
        }

        self::$settingsCache = [];
        self::$settingsPreloadAttempted = false;
        self::$settingsPreloaded = false;
        self::$settingsCacheVersion = $currentVersion;
    }

    private static function shouldPreloadSettings(): bool {
        if (self::$settingsPreloadEnabled !== null) {
            return self::$settingsPreloadEnabled;
        }

        if (defined('APPCONFIG_PRELOAD_SETTINGS')) {
            self::$settingsPreloadEnabled = (bool) APPCONFIG_PRELOAD_SETTINGS;
            return self::$settingsPreloadEnabled;
        }

        $raw = '';
        if (function_exists('fulguriteEnv')) {
            $raw = trim((string) fulguriteEnv('APPCONFIG_PRELOAD_SETTINGS', ''));
        }
        if ($raw === '') {
            $envValue = getenv('APPCONFIG_PRELOAD_SETTINGS');
            if ($envValue !== false) {
                $raw = trim((string) $envValue);
            }
        }
        if ($raw === '') {
            self::$settingsPreloadEnabled = true;
            return true;
        }

        self::$settingsPreloadEnabled = in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
        return self::$settingsPreloadEnabled;
    }

    private static function preloadSettingsCache(): void {
        if (self::$settingsPreloaded || self::$settingsPreloadAttempted) {
            return;
        }
        self::$settingsPreloadAttempted = true;

        try {
            $db = Database::getInstance();
            $stmt = $db->query('SELECT key, value FROM settings');
            if ($stmt === false) {
                return;
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $rowKey = isset($row['key']) ? (string) $row['key'] : '';
                if ($rowKey === '' || Database::isSensitiveSetting($rowKey)) {
                    continue;
                }
                self::$settingsCache[$rowKey] = isset($row['value']) ? (string) $row['value'] : '';
            }

            self::$settingsPreloaded = true;
        } catch (Throwable $e) {
            // the fallback standard remains Database::getSetting() on each key.
        }
    }

    public static function getRoleMap(): array {
        $map = [];
        foreach (self::getRoles() as $role) {
            $map[$role['key']] = $role;
        }
        return $map;
    }

    public static function getRoleLabel(string $roleKey): string {
        $map = self::getRoleMap();
        return $map[$roleKey]['label'] ?? ($roleKey !== '' ? $roleKey : 'Inconnu');
    }

    public static function getRoleDescription(string $roleKey): string {
        $map = self::getRoleMap();
        return $map[$roleKey]['description'] ?? '';
    }

    public static function getRoleLevel(string $roleKey, int $default = 0): int {
        $map = self::getRoleMap();
        return isset($map[$roleKey]) ? (int) $map[$roleKey]['level'] : $default;
    }

    public static function getRoleBadgeClass(string $roleKey): string {
        $map = self::getRoleMap();
        $badge = $map[$roleKey]['badge'] ?? 'gray';
        return self::ROLE_BADGES[$badge] ?? self::ROLE_BADGES['gray'];
    }

    public static function encodeRoles(array $roles): string {
        return json_encode(array_values($roles), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    public static function sanitizeRoles(array $roles): array {
        $defaults = [];
        foreach (self::defaultRoles() as $role) {
            $defaults[$role['key']] = $role;
        }

        $normalized = [];
        foreach ($roles as $index => $role) {
            if (!is_array($role)) {
                continue;
            }

            $key = strtolower(trim((string) ($role['key'] ?? '')));
            if (!self::isValidRoleKey($key)) {
                continue;
            }

            $default = $defaults[$key] ?? null;
            $label = trim((string) ($role['label'] ?? ($default['label'] ?? ucfirst(str_replace('-', ' ', $key)))));
            $description = trim((string) ($role['description'] ?? ($default['description'] ?? '')));
            $level = (int) ($role['level'] ?? ($default['level'] ?? (($index + 1) * 10)));
            $badge = strtolower(trim((string) ($role['badge'] ?? ($default['badge'] ?? 'gray'))));
            if (!isset(self::ROLE_BADGES[$badge])) {
                $badge = $default['badge'] ?? 'gray';
            }

            $normalized[$key] = [
                'key' => $key,
                'label' => $label !== '' ? $label : ucfirst(str_replace('-', ' ', $key)),
                'description' => $description,
                'level' => $level > 0 ? $level : ($default['level'] ?? 10),
                'badge' => $badge,
                'system' => isset($defaults[$key]),
            ];
        }

        foreach ($defaults as $key => $role) {
            if (!isset($normalized[$key])) {
                $normalized[$key] = $role;
            } else {
                $normalized[$key]['system'] = true;
            }
        }

        uasort($normalized, static function (array $left, array $right): int {
            $levelCompare = ((int) $left['level']) <=> ((int) $right['level']);
            if ($levelCompare !== 0) {
                return $levelCompare;
            }

            if (!empty($left['system']) && empty($right['system'])) {
                return -1;
            }
            if (empty($left['system']) && !empty($right['system'])) {
                return 1;
            }

            return strcasecmp($left['label'], $right['label']);
        });

        return array_values($normalized);
    }

    public static function allowedRoleBadges(): array {
        return array_keys(self::ROLE_BADGES);
    }

    public static function isValidRoleKey(string $key): bool {
        return (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?$/', $key);
    }

    public static function permissionDefinitions(): array {
        return [
            'repos.view' => ['label' => 'Voir les depots', 'group' => 'Consultation'],
            'repos.view_sensitive_files' => ['label' => 'Voir l apercu inline des fichiers sensibles', 'group' => 'Consultation'],
            'repos.manage' => ['label' => 'Administrer les depots', 'group' => 'Administration'],
            'backup_jobs.manage' => ['label' => 'Gerer les backup jobs', 'group' => 'Execution'],
            'copy_jobs.manage' => ['label' => 'Gerer les jobs de copie', 'group' => 'Execution'],
            'restore.view' => ['label' => 'Voir l historique des restores', 'group' => 'Consultation'],
            'restore.run' => ['label' => 'Lancer des restores', 'group' => 'Execution'],
            'hosts.manage' => ['label' => 'Gerer les hotes', 'group' => 'Administration'],
            'sshkeys.manage' => ['label' => 'Gerer les cles SSH', 'group' => 'Administration'],
            'ssh_host_key.approve' => ['label' => 'Approuver les host keys SSH', 'group' => 'Administration'],
            'scripts.manage' => ['label' => 'Gerer les scripts approuves', 'group' => 'Administration'],
            'scheduler.manage' => ['label' => 'Gerer la planification', 'group' => 'Administration'],
            'settings.manage' => ['label' => 'Gerer les parametres', 'group' => 'Administration'],
            'themes.manage' => ['label' => 'Gerer les themes (installer, supprimer, approuver)', 'group' => 'Administration'],
            'users.manage' => ['label' => 'Gerer les utilisateurs', 'group' => 'Administration'],
            'logs.view' => ['label' => 'Consulter les logs', 'group' => 'Consultation'],
            'stats.view' => ['label' => 'Voir les statistiques', 'group' => 'Consultation'],
            'performance.view' => ['label' => 'Voir la sante et les performances', 'group' => 'Administration'],
            'snapshots.manage' => ['label' => 'Administrer snapshots, tags et retention', 'group' => 'Administration'],
        ];
    }

    public static function rolePermissions(string $roleKey): array {
        $level = self::getRoleLevel($roleKey, 0);
        $permissions = ['repos.view', 'logs.view', 'stats.view'];

        if ($level >= self::getRoleLevel(ROLE_OPERATOR, PHP_INT_MAX)) {
            $permissions[] = 'backup_jobs.manage';
            $permissions[] = 'copy_jobs.manage';
        }

        if ($level >= self::getRoleLevel(ROLE_RESTORE_OPERATOR, PHP_INT_MAX)) {
            $permissions[] = 'restore.view';
            $permissions[] = 'restore.run';
        }

        if ($level >= self::getRoleLevel(ROLE_ADMIN, PHP_INT_MAX)) {
            $permissions = array_keys(self::permissionDefinitions());
        }

        $permissions = array_values(array_unique($permissions));
        sort($permissions);
        return $permissions;
    }

    public static function startPageOptions(): array {
        return [
            'dashboard' => ['label' => 'Dashboard', 'path' => routePath('/index.php')],
            'repos' => ['label' => 'Depots', 'path' => routePath('/repos.php')],
            'restores' => ['label' => 'Restores', 'path' => routePath('/restores.php')],
            'backup_jobs' => ['label' => 'Backup jobs', 'path' => routePath('/backup_jobs.php')],
            'copy_jobs' => ['label' => 'Copie depots', 'path' => routePath('/copy_jobs.php')],
            'stats' => ['label' => 'Statistiques', 'path' => routePath('/stats.php')],
            'logs' => ['label' => 'Logs', 'path' => routePath('/logs.php')],
            'notifications' => ['label' => 'Notifications', 'path' => routePath('/notifications.php')],
        ];
    }

    public static function localeOptions(): array {
        return [
            'fr' => 'Francais',
            'en' => 'English',
        ];
    }
}
