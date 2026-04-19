<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/RestoreTargetPlanner.php';
Auth::requirePermission('settings.manage');

function settingsTabList(): array {
    return [
        'general' => t('settings.tabs.general'),
        'access' => t('settings.tabs.access'),
        'backup' => t('settings.tabs.backup'),
        'automation' => t('settings.tabs.automation'),
        'data' => t('settings.tabs.data'),
        'integrations' => t('settings.tabs.integrations'),
        'api' => t('settings.tabs.api'),
    ];
}

function settingsTabSections(): array {
    return [
        'general' => ['interface', 'notifications'],
        'access' => ['security', 'webauth', 'audit', 'roles'],
        'backup' => ['backup', 'restore'],
        'automation' => ['scheduler', 'worker'],
        'data' => ['exploration', 'indexation', 'performance'],
        'integrations' => ['integrations'],
        'api' => ['api_public'],
    ];
}

function settingsTabAliases(): array {
    $aliases = [];
    foreach (settingsTabSections() as $tab => $sections) {
        foreach ($sections as $section) {
            $aliases[$section] = $tab;
        }
    }

    return $aliases;
}

function normalizeSettingsTab(string $value): string {
    $tabs = settingsTabList();
    if (isset($tabs[$value])) {
        return $value;
    }

    return settingsTabAliases()[$value] ?? 'general';
}

function normalizeSettingsSection(string $tab, string $value = ''): string {
    $sections = settingsTabSections()[$tab] ?? [];
    if ($value !== '' && in_array($value, $sections, true)) {
        return $value;
    }

    return $sections[0] ?? '';
}

function disclosureOpenAttr(string $activeTab, string $activeSection, string $tab, string $section): string {
    return ($activeTab === $tab && $activeSection === $section) ? 'open' : '';
}

function disclosureSummary(string $title, string $meta): string {
    return '<summary class="settings-disclosure-summary"><span class="settings-disclosure-heading"><span class="settings-disclosure-title">' . h($title) . '</span></span></summary>';
}

function settingsSectionLabels(): array {
    return [
        'interface' => t('settings.sections.interface'),
        'notifications' => t('settings.sections.notifications'),
        'security' => t('settings.sections.security'),
        'webauth' => t('settings.sections.webauth'),
        'audit' => t('settings.sections.audit'),
        'roles' => t('settings.sections.roles'),
        'backup' => t('settings.sections.backup'),
        'restore' => t('settings.sections.restore'),
        'scheduler' => t('settings.sections.scheduler'),
        'worker' => t('settings.sections.worker'),
        'exploration' => t('settings.sections.exploration'),
        'indexation' => t('settings.sections.indexation'),
        'performance' => t('settings.sections.performance'),
        'integrations' => t('settings.sections.integrations'),
    ];
}

function currentSectionFromInput(string $value, string $tab): string {
    $aliases = settingsTabAliases();
    if (isset($aliases[$value]) && $aliases[$value] === $tab) {
        return $value;
    }

    return normalizeSettingsSection($tab, $value);
}

function tabLinkTarget(string $tab, string $activeSection): string {
    return $activeSection !== '' ? $activeSection : $tab;
}

function initialSectionForTab(string $requestedTab, string $normalizedTab): string {
    return currentSectionFromInput($requestedTab, $normalizedTab);
}

function settingsTabMeta(): array {
    return [
        'general' => t('settings.meta.general'),
        'access' => t('settings.meta.access'),
        'backup' => t('settings.meta.backup'),
        'automation' => t('settings.meta.automation'),
        'data' => t('settings.meta.data'),
        'integrations' => t('settings.meta.integrations'),
        'api' => t('settings.meta.api'),
    ];
}

function settingValue(string $key, string $default = ''): string {
    $defaults = AppConfig::defaultSettings();
    $fallback = $default !== '' ? $default : ($defaults[$key] ?? '');
    return htmlspecialchars(Database::getSetting($key, $fallback), ENT_QUOTES, 'UTF-8');
}

function secretPlaceholder(string $key, string $empty = ''): string {
    if ($empty === '') {
        $empty = t('settings.secret_placeholder_empty');
    }
    return Database::getSetting($key) !== '' ? t('settings.secret_placeholder_set') : $empty;
}

function settingCheck(string $key): string {
    return AppConfig::getBool($key) ? 'checked' : '';
}

function renderSelectOptions(string $name, array $options, string $selected): string {
    $html = '<select name="' . h($name) . '" class="form-control">';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . h((string) $value) . '" ' . ((string) $selected === (string) $value ? 'selected' : '') . '>' . h((string) $label) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

function renderDayOptions(string $name, string $selected): string {
    return renderSelectOptions($name, [
        '1' => t('settings.days.mon'), '2' => t('settings.days.tue'),
        '3' => t('settings.days.wed'), '4' => t('settings.days.thu'),
        '5' => t('settings.days.fri'), '6' => t('settings.days.sat'),
        '7' => t('settings.days.sun'),
    ], $selected);
}

function renderHourOptions(string $name, string $selected): string {
    $options = [];
    for ($hour = 0; $hour < 24; $hour++) {
        $options[(string) $hour] = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
    }
    return renderSelectOptions($name, $options, $selected);
}

function normalizePostedTimezonePreference(string $mode, string $custom): ?string {
    if ($mode !== 'custom') {
        return 'server';
    }

    $timezone = trim($custom);
    if ($timezone === '') {
        return null;
    }

    return AppConfig::isValidTimezone($timezone) ? $timezone : null;
}

function renderDayCheckboxes(string $name, array $selectedDays): string {
    $days = [
        '1' => t('settings.days_short.mon'), '2' => t('settings.days_short.tue'),
        '3' => t('settings.days_short.wed'), '4' => t('settings.days_short.thu'),
        '5' => t('settings.days_short.fri'), '6' => t('settings.days_short.sat'),
        '7' => t('settings.days_short.sun'),
    ];
    $html = '<div style="display:flex;gap:6px;flex-wrap:wrap">';
    foreach ($days as $value => $label) {
        $checked = in_array($value, $selectedDays, true) ? 'checked' : '';
        $html .= '<label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-size:12px;padding:6px 10px;border:1px solid var(--border);border-radius:8px">';
        $html .= '<input type="checkbox" name="' . h($name) . '[]" value="' . h($value) . '" ' . $checked . ' style="accent-color:var(--accent)">';
        $html .= h($label) . '</label>';
    }
    $html .= '</div>';
    return $html;
}

function parsePostedRoles(array $rows): array {
    $parsed = [];
    $errors = [];
    $seenKeys = [];
    $systemKeys = array_map(static fn(array $role): string => $role['key'], AppConfig::defaultRoles());

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $key = strtolower(trim((string) ($row['key'] ?? '')));
        $label = trim((string) ($row['label'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $levelRaw = trim((string) ($row['level'] ?? ''));
        $badge = strtolower(trim((string) ($row['badge'] ?? 'gray')));

        if ($key === '' && $label === '' && $description === '' && $levelRaw === '') {
            continue;
        }
        if ($key === '' || !AppConfig::isValidRoleKey($key)) {
            $errors[] = $key === '' ? t('settings.error.role_no_key') : t('settings.error.role_invalid_key', ['key' => $key]);
            continue;
        }
        if (isset($seenKeys[$key])) {
            $errors[] = t('settings.error.role_duplicate_key', ['key' => $key]);
            continue;
        }
        if ($label === '') {
            $errors[] = t('settings.error.role_no_label', ['key' => $key]);
            continue;
        }

        $level = filter_var($levelRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]);
        if ($level === false) {
            $errors[] = t('settings.error.role_invalid_level', ['key' => $key]);
            continue;
        }

        $seenKeys[$key] = true;
        $parsed[] = [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'level' => (int) $level,
            'badge' => in_array($badge, AppConfig::allowedRoleBadges(), true) ? $badge : 'gray',
            'system' => in_array($key, $systemKeys, true),
        ];
    }

    foreach (AppConfig::defaultRoles() as $defaultRole) {
        if (!isset($seenKeys[$defaultRole['key']])) {
            $parsed[] = $defaultRole;
            $errors[] = t('settings.error.role_system_removed');
        }
    }

    $parsed = AppConfig::sanitizeRoles($parsed);
    $levels = [];
    foreach ($parsed as $role) {
        $levels[$role['key']] = (int) $role['level'];
    }
    if (($levels[ROLE_VIEWER] ?? 0) >= ($levels[ROLE_OPERATOR] ?? PHP_INT_MAX)
        || ($levels[ROLE_OPERATOR] ?? 0) >= ($levels[ROLE_RESTORE_OPERATOR] ?? PHP_INT_MAX)
        || ($levels[ROLE_RESTORE_OPERATOR] ?? 0) >= ($levels[ROLE_ADMIN] ?? PHP_INT_MAX)) {
        $errors[] = t('settings.error.role_order_strict');
    }

    return [$parsed, array_values(array_unique($errors))];
}

function renderRoleRow(array $role, int $index): string {
    $isSystem = !empty($role['system']);
    $options = '';
    foreach (AppConfig::allowedRoleBadges() as $badge) {
        $options .= '<option value="' . h($badge) . '" ' . (($role['badge'] ?? 'gray') === $badge ? 'selected' : '') . '>' . ucfirst($badge) . '</option>';
    }

    ob_start();
    ?>
    <div class="role-row" data-system="<?= $isSystem ? '1' : '0' ?>">
        <div class="role-row-grid">
            <div class="form-group">
                <label class="form-label"><?= t('settings.role_row.key') ?></label>
                <input type="text" name="roles[<?= $index ?>][key]" class="form-control" value="<?= h($role['key']) ?>" <?= $isSystem ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('settings.role_row.label') ?></label>
                <input type="text" name="roles[<?= $index ?>][label]" class="form-control" value="<?= h($role['label']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('settings.role_row.level') ?></label>
                <input type="number" name="roles[<?= $index ?>][level]" class="form-control" value="<?= (int) $role['level'] ?>" min="1" max="999">
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('settings.role_row.badge') ?></label>
                <select name="roles[<?= $index ?>][badge]" class="form-control"><?= $options ?></select>
            </div>
        </div>
        <div class="role-row-footer">
            <div class="form-group" style="flex:1;margin-bottom:0">
                <label class="form-label"><?= t('settings.role_row.description') ?></label>
                <input type="text" name="roles[<?= $index ?>][description]" class="form-control" value="<?= h($role['description'] ?? '') ?>">
            </div>
            <?php if ($isSystem): ?>
            <span class="badge badge-gray"><?= t('settings.role_row.system_badge') ?></span>
            <?php else: ?>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeRoleRow(this)"><?= t('common.delete') ?></button>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

$tabs = settingsTabList();
$requestedTab = (string) ($_GET['tab'] ?? 'general');
$activeTab = normalizeSettingsTab($requestedTab);
$activeSection = initialSectionForTab($requestedTab, $activeTab);

$flash = null;
$roleRows = AppConfig::getRoles();
$backupDefaultDays = AppConfig::getCsvValues('backup_job_default_schedule_days', '1');
if (empty($backupDefaultDays)) {
    $backupDefaultDays = ['1'];
}
$integrityCheckDaysSelected = AppConfig::getCsvValues('integrity_check_day', '1');
if (empty($integrityCheckDaysSelected)) {
    $integrityCheckDaysSelected = ['1'];
}
$maintenanceVacuumDaysSelected = AppConfig::getCsvValues('maintenance_vacuum_day', '7');
if (empty($maintenanceVacuumDaysSelected)) {
    $maintenanceVacuumDaysSelected = ['7'];
}
$timezoneIdentifiers = DateTimeZone::listIdentifiers();
sort($timezoneIdentifiers);
$commonTimezones = array_values(array_unique([
    AppConfig::serverTimezone(),
    'UTC',
    'Europe/Paris',
    'Europe/London',
    'Europe/Berlin',
    'Europe/Zurich',
    'America/New_York',
    'America/Chicago',
    'America/Los_Angeles',
    'Asia/Tokyo',
]));
$timezoneMode = AppConfig::timezoneUsesServerDefault() ? 'server' : 'custom';
$timezoneCustom = AppConfig::timezoneUsesServerDefault() ? AppConfig::serverTimezone() : AppConfig::timezone();
$canManageInfisical = Auth::isAdmin();
$currentInfisicalConfig = InfisicalConfigManager::currentConfig();
$infisicalHistory = InfisicalConfigManager::history(8);
$infisicalFormValues = $currentInfisicalConfig;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $activeTab = normalizeSettingsTab((string) ($_POST['active_tab'] ?? $activeTab));
    $activeSection = currentSectionFromInput((string) ($_POST['active_section'] ?? ''), $activeTab);
    $timezoneMode = (string) ($_POST['interface_timezone_mode'] ?? $timezoneMode) === 'custom' ? 'custom' : 'server';
    $timezoneCustom = trim((string) ($_POST['interface_timezone_custom'] ?? $timezoneCustom));

    if (isset($_POST['restore_infisical_history_id'])) {
        $activeTab = 'integrations';
        $activeSection = 'integrations';
        $historyId = (int) $_POST['restore_infisical_history_id'];

        if (!$canManageInfisical) {
            $flash = ['type' => 'danger', 'msg' => t('settings.flash.infisical_admin_only')];
        } elseif (!StepUpAuth::consumeCurrentUserReauth('settings.sensitive')) {
            $flash = ['type' => 'danger', 'msg' => t('settings.flash.infisical_reauth_required')];
        } else {
            $historyEntry = InfisicalConfigManager::historyEntry($historyId);
            if (!$historyEntry || !is_array($historyEntry['config'] ?? null)) {
                $flash = ['type' => 'danger', 'msg' => t('settings.flash.infisical_history_not_found')];
            } else {
                $candidateInfisicalConfig = InfisicalConfigManager::snapshotToConfig((array) $historyEntry['config']);
                $validation = InfisicalConfigManager::testConfiguration($candidateInfisicalConfig);
                if (empty($validation['success'])) {
                    Auth::log('infisical_config_restore_failed', 'Rollback Infisical refuse — ' . ($validation['output'] ?? 'validation echouee'), 'warning');
                    $flash = ['type' => 'danger', 'msg' => (string) ($validation['output'] ?? 'Validation Infisical echouee.')];
                } else {
                    $beforeUrl = $currentInfisicalConfig['infisical_url'];
                    $currentInfisicalConfig = InfisicalConfigManager::persistConfiguration(
                        $candidateInfisicalConfig,
                        (int) Auth::currentUser()['id'],
                        $validation,
                        'rollback',
                        $historyId
                    );
                    Auth::log(
                        'infisical_config_rollback',
                        'Rollback Infisical applique — URL precedente: ' . ($beforeUrl !== '' ? $beforeUrl : '(vide)')
                        . ' ; URL restauree: ' . ($currentInfisicalConfig['infisical_url'] !== '' ? $currentInfisicalConfig['infisical_url'] : '(vide)')
                        . ' ; test: ' . (string) ($validation['output'] ?? 'OK')
                    );
                    $flash = ['type' => 'success', 'msg' => t('settings.flash.infisical_restored')];
                }
            }
        }
    }

    if (!isset($_POST['restore_infisical_history_id']) && (($_POST['action'] ?? '') === 'save_settings')) {
        $allFields = [
            'interface_app_name', 'interface_app_subtitle', 'interface_logo_letter', 'interface_login_tagline',
            'interface_timezone', 'interface_dashboard_refresh_seconds', 'interface_stats_default_period_days',
            'mail_enabled', 'mail_from', 'mail_from_name', 'mail_to', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_tls',
            'backup_alert_hours', 'backup_server_host', 'backup_server_sftp_user',
            'backup_job_default_schedule_enabled', 'backup_job_default_schedule_hour', 'backup_job_default_notify_on_failure',
            'backup_job_default_retention_enabled', 'backup_job_default_retention_keep_last', 'backup_job_default_retention_keep_daily',
            'backup_job_default_retention_keep_weekly', 'backup_job_default_retention_keep_monthly', 'backup_job_default_retention_keep_yearly',
            'backup_job_default_retention_prune',
            'restore_managed_local_root', 'restore_managed_remote_root', 'restore_append_context_subdir', 'restore_original_global_enabled', 'restore_original_allowed_paths',
            'restore_history_page_size',
            'session_absolute_lifetime_minutes', 'session_inactivity_minutes', 'session_warning_minutes',
            'session_db_touch_interval_seconds', 'session_strict_fingerprint', 'login_max_attempts', 'login_lockout_minutes',
            'reauth_max_age_seconds', 'second_factor_pending_ttl_seconds', 'max_sessions_per_user', 'force_admin_2fa',
            'login_notifications_enabled', 'login_notifications_new_ip_only',
            'api_rate_limit_default_hits', 'api_rate_limit_default_window_seconds',
            'api_rate_limit_restore_hits', 'api_rate_limit_restore_window_seconds',
            'api_rate_limit_restore_partial_hits', 'api_rate_limit_restore_partial_window_seconds',
            'api_rate_limit_run_backup_hits', 'api_rate_limit_run_backup_window_seconds',
            'api_rate_limit_run_copy_hits', 'api_rate_limit_run_copy_window_seconds',
            'api_rate_limit_explore_view_hits', 'api_rate_limit_explore_view_window_seconds',
            'api_rate_limit_search_files_hits', 'api_rate_limit_search_files_window_seconds',
            'api_rate_limit_manage_cron_hits', 'api_rate_limit_manage_cron_window_seconds',
            'api_rate_limit_manage_scheduler_hits', 'api_rate_limit_manage_scheduler_window_seconds',
            'api_rate_limit_manage_worker_hits', 'api_rate_limit_manage_worker_window_seconds',
            'api_rate_limit_reauth_hits', 'api_rate_limit_reauth_window_seconds',
            'api_rate_limit_webauthn_auth_hits', 'api_rate_limit_webauthn_auth_window_seconds',
            'api_rate_limit_webauthn_reg_hits', 'api_rate_limit_webauthn_reg_window_seconds',
            'audit_logs_page_size', 'audit_dashboard_recent_limit', 'audit_activity_retention_days',
            'audit_restore_retention_days', 'audit_cron_retention_days', 'audit_rate_limit_retention_days',
            'audit_login_attempt_retention_days', 'audit_job_queue_retention_days', 'audit_archive_retention_days',
            'audit_repo_stats_hourly_retention_days', 'audit_repo_stats_downsample_batch',
            'discord_enabled', 'discord_webhook_url', 'slack_enabled', 'slack_webhook_url',
            'telegram_enabled', 'telegram_bot_token', 'telegram_chat_id', 'ntfy_enabled', 'ntfy_url', 'ntfy_topic',
            'webhook_enabled', 'webhook_url', 'teams_enabled', 'teams_webhook_url',
            'gotify_enabled', 'gotify_url', 'in_app_enabled', 'web_push_enabled', 'app_notifications_retention_days',
            'cron_run_minute',
            'weekly_report_enabled', 'weekly_report_day', 'weekly_report_hour',
            'integrity_check_enabled', 'integrity_check_hour',
            'maintenance_vacuum_enabled', 'maintenance_vacuum_hour',
            'worker_default_name', 'worker_sleep_seconds', 'worker_limit', 'worker_stale_minutes', 'worker_heartbeat_stale_seconds',
            'explore_view_cache_ttl', 'explore_page_size', 'explore_search_max_results', 'explore_max_file_size_mb',
            'search_index_warm_batch_per_run', 'search_index_adhoc_retention_days', 'search_index_recent_snapshots',
            'restic_snapshots_cache_ttl', 'restic_ls_cache_ttl', 'restic_stats_cache_ttl', 'restic_search_cache_ttl', 'restic_tree_cache_ttl',
            'performance_slow_request_threshold_ms', 'performance_slow_sql_threshold_ms',
            'performance_slow_restic_threshold_ms', 'performance_slow_command_threshold_ms',
            'performance_metrics_cache_ttl_seconds', 'performance_metrics_refresh_interval_seconds',
            'performance_recent_jobs_limit', 'performance_log_tail_lines',
            'performance_repo_size_scan_limit', 'performance_repo_size_top_limit',
            'disk_monitoring_enabled', 'disk_local_warning_percent', 'disk_local_critical_percent',
            'disk_local_warning_free_gb', 'disk_local_critical_free_gb',
            'disk_remote_warning_percent', 'disk_remote_critical_percent',
            'disk_remote_warning_free_gb', 'disk_remote_critical_free_gb',
            'disk_preflight_enabled', 'disk_preflight_margin_percent', 'disk_preflight_min_free_gb',
            'disk_monitor_history_retention_days',
            'webauthn_rp_name', 'webauthn_rp_id_override', 'webauthn_registration_timeout_ms', 'webauthn_auth_timeout_ms',
            'webauthn_user_verification', 'webauthn_resident_key', 'webauthn_require_resident_key',
            'webauthn_attestation', 'webauthn_login_autostart', 'totp_issuer',
            'api_enabled', 'api_default_token_lifetime_days', 'api_default_rate_limit_per_minute',
            'api_log_retention_days', 'api_idempotency_retention_hours', 'api_cors_allowed_origins',
        ];
        $checkboxes = [
            'mail_enabled', 'smtp_tls', 'backup_job_default_schedule_enabled', 'backup_job_default_notify_on_failure',
            'backup_job_default_retention_enabled', 'backup_job_default_retention_prune', 'session_strict_fingerprint',
            'force_admin_2fa', 'login_notifications_enabled', 'login_notifications_new_ip_only',
            'discord_enabled', 'slack_enabled', 'telegram_enabled', 'ntfy_enabled',
            'webhook_enabled', 'teams_enabled', 'gotify_enabled', 'in_app_enabled', 'web_push_enabled',
            'weekly_report_enabled', 'integrity_check_enabled', 'maintenance_vacuum_enabled', 'infisical_enabled',
            'api_enabled', 'disk_monitoring_enabled', 'disk_preflight_enabled',
            'webauthn_require_resident_key', 'webauthn_login_autostart',
            'restore_append_context_subdir', 'restore_original_global_enabled',
        ];
        $sensitiveSettingFields = [
            'smtp_pass',
            'discord_webhook_url',
            'slack_webhook_url',
            'telegram_bot_token',
            'webhook_url',
            'webhook_auth_token',
            'teams_webhook_url',
            'gotify_token',
        ];
        $infisicalSettingFields = [
            'infisical_enabled',
            'infisical_url',
            'infisical_token',
            'infisical_project_id',
            'infisical_environment',
            'infisical_secret_path',
            'infisical_allowed_hosts',
            'infisical_allowed_host_patterns',
            'infisical_allowed_cidrs',
            'infisical_allowed_port',
            'infisical_allow_http',
        ];
        $allFields = array_values(array_diff($allFields, $infisicalSettingFields));
        $allFields = array_values(array_diff($allFields, $sensitiveSettingFields));

        [$parsedRoles, $roleErrors] = parsePostedRoles($_POST['roles'] ?? []);
        $roleRows = $parsedRoles;
        $settingsErrors = [];
        $timezonePreference = normalizePostedTimezonePreference($timezoneMode, $timezoneCustom);
        if ($timezonePreference === null) {
            $settingsErrors[] = t('settings.error.invalid_timezone');
        }
        $integrityCheckDaysSelected = array_values(array_filter(array_map('trim', (array) ($_POST['integrity_check_days'] ?? []))));
        if (empty($integrityCheckDaysSelected)) {
            $integrityCheckDaysSelected = ['1'];
        }
        $maintenanceVacuumDaysSelected = array_values(array_filter(array_map('trim', (array) ($_POST['maintenance_vacuum_days'] ?? []))));
        if (empty($maintenanceVacuumDaysSelected)) {
            $maintenanceVacuumDaysSelected = ['7'];
        }
        $postedRestoreLocalRoot = trim((string) ($_POST['restore_managed_local_root'] ?? AppConfig::restoreManagedLocalRoot()));
        $postedRestoreRemoteRoot = trim((string) ($_POST['restore_managed_remote_root'] ?? AppConfig::restoreManagedRemoteRoot()));

        try {
            $postedRestoreLocalRoot = RestoreTargetPlanner::validateManagedRoot($postedRestoreLocalRoot, 'locale');
        } catch (InvalidArgumentException $e) {
            $settingsErrors[] = $e->getMessage();
        }

        try {
            $postedRestoreRemoteRoot = RestoreTargetPlanner::validateManagedRoot($postedRestoreRemoteRoot, 'distante');
        } catch (InvalidArgumentException $e) {
            $settingsErrors[] = $e->getMessage();
        }

        if (empty($settingsErrors)) {
            try {
                RestoreTargetPlanner::ensureLocalManagedDirectory($postedRestoreLocalRoot);
            } catch (RuntimeException $e) {
                $settingsErrors[] = $e->getMessage();
            }
        }

        if (empty($roleErrors)) {
            $currentRoleKeys = array_map(static fn(array $role): string => $role['key'], AppConfig::getRoles());
            $submittedRoleKeys = array_map(static fn(array $role): string => $role['key'], $parsedRoles);
            $removedRoleKeys = array_values(array_diff($currentRoleKeys, $submittedRoleKeys));
            if (!empty($removedRoleKeys)) {
                $placeholders = implode(',', array_fill(0, count($removedRoleKeys), '?'));
                $stmt = Database::getInstance()->prepare("
                    SELECT role, COUNT(*) AS total
                    FROM users
                    WHERE role IN ($placeholders)
                    GROUP BY role
                ");
                $stmt->execute($removedRoleKeys);
                foreach ($stmt->fetchAll() as $row) {
                    $roleErrors[] = t('settings.error.role_in_use', ['role' => AppConfig::getRoleLabel((string) $row['role']), 'count' => (int) $row['total']]);
                }
            }
        }

        $candidateInfisicalConfig = InfisicalConfigManager::candidateFromInput($_POST, $currentInfisicalConfig);
        $infisicalConfigChanged = InfisicalConfigManager::hasProtectedChanges($currentInfisicalConfig, $candidateInfisicalConfig);
        $infisicalValidationResult = ['success' => true, 'output' => 'Aucune validation active requise.'];

        if ($infisicalConfigChanged) {
            if (!$canManageInfisical) {
                $settingsErrors[] = t('settings.flash.infisical_admin_only');
            }
            if (!StepUpAuth::consumeCurrentUserReauth('settings.sensitive')) {
                $settingsErrors[] = t('settings.flash.infisical_reauth_required');
            }
            if (empty($_POST['confirm_infisical_change'])) {
                $settingsErrors[] = t('settings.flash.infisical_confirm_required');
            }
            if (empty($settingsErrors) && InfisicalConfigManager::requiresConnectivityValidation($currentInfisicalConfig, $candidateInfisicalConfig)) {
                $infisicalValidationResult = InfisicalConfigManager::testConfiguration($candidateInfisicalConfig);
                if (empty($infisicalValidationResult['success'])) {
                    Auth::log('infisical_config_update_failed', 'Validation Infisical refusee — ' . ($infisicalValidationResult['output'] ?? 'echec inconnu'), 'warning');
                    $settingsErrors[] = (string) ($infisicalValidationResult['output'] ?? 'Validation Infisical echouee.');
                }
            }
        }

        $allErrors = array_values(array_unique(array_merge($roleErrors, $settingsErrors)));

        if (empty($allErrors)) {
            foreach ($allFields as $field) {
                if ($field === 'interface_timezone') {
                    Database::setSetting($field, (string) $timezonePreference);
                    continue;
                }

                if ($field === 'restore_managed_local_root') {
                    Database::setSetting($field, $postedRestoreLocalRoot);
                    continue;
                }

                if ($field === 'restore_managed_remote_root') {
                    Database::setSetting($field, $postedRestoreRemoteRoot);
                    continue;
                }

                Database::setSetting($field, in_array($field, $checkboxes, true) ? (isset($_POST[$field]) ? '1' : '0') : trim((string) ($_POST[$field] ?? '')));
            }
            $scheduleDays = array_values(array_filter(array_map('trim', (array) ($_POST['backup_job_default_schedule_days'] ?? []))));
            if (empty($scheduleDays)) {
                $scheduleDays = ['1'];
            }
            Database::setSetting('backup_job_default_schedule_days', implode(',', $scheduleDays));
            $backupDefaultDays = $scheduleDays;
            Database::setSetting('integrity_check_day', implode(',', $integrityCheckDaysSelected));
            Database::setSetting('maintenance_vacuum_day', implode(',', $maintenanceVacuumDaysSelected));

            foreach ($sensitiveSettingFields as $sensitiveField) {
                if (!empty($_POST[$sensitiveField])) {
                    Database::setSetting($sensitiveField, (string) $_POST[$sensitiveField]);
                }
            }

            Database::setSetting(
                'login_notification_policy',
                Notifier::encodePolicy(
                    Notifier::parsePolicyPost($_POST, 'login_notification', 'login', Notifier::getSettingPolicy('login_notification_policy', 'login')),
                    'login'
                )
            );
            Database::setSetting(
                'security_alert_notification_policy',
                Notifier::encodePolicy(
                    Notifier::parsePolicyPost($_POST, 'security_alert', 'security', Notifier::getSettingPolicy('security_alert_notification_policy', 'security')),
                    'security'
                )
            );
            Database::setSetting(
                'theme_request_notification_policy',
                Notifier::encodePolicy(
                    Notifier::parsePolicyPost($_POST, 'theme_request_submitted', 'theme_request', Notifier::getSettingPolicy('theme_request_notification_policy', 'theme_request')),
                    'theme_request'
                )
            );
            Database::setSetting(
                'weekly_report_notification_policy',
                Notifier::encodePolicy(
                    Notifier::parsePolicyPost($_POST, 'weekly_report_settings', 'weekly_report', Notifier::getSettingPolicy('weekly_report_notification_policy', 'weekly_report')),
                    'weekly_report'
                )
            );
            Database::setSetting(
                'integrity_check_notification_policy',
                Notifier::encodePolicy(
                    Notifier::parsePolicyPost($_POST, 'integrity_check_settings', 'integrity_check', Notifier::getSettingPolicy('integrity_check_notification_policy', 'integrity_check')),
                    'integrity_check'
                )
            );
            Database::setSetting(
                'maintenance_vacuum_notification_policy',
                Notifier::encodePolicy(
                    Notifier::parsePolicyPost($_POST, 'maintenance_vacuum_settings', 'maintenance_vacuum', Notifier::getSettingPolicy('maintenance_vacuum_notification_policy', 'maintenance_vacuum')),
                    'maintenance_vacuum'
                )
            );
            Database::setSetting(
                'disk_space_notification_policy',
                Notifier::encodePolicy(
                    Notifier::parsePolicyPost($_POST, 'disk_space_settings', 'disk_space', Notifier::getSettingPolicy('disk_space_notification_policy', 'disk_space')),
                    'disk_space'
                )
            );
            Database::setSetting(
                'secret_broker_notification_policy',
                Notifier::encodePolicy(
                    Notifier::parsePolicyPost($_POST, 'secret_broker_settings', 'secret_broker', Notifier::getSettingPolicy('secret_broker_notification_policy', 'secret_broker')),
                    'secret_broker'
                )
            );
            Database::setSetting(
                'job_retry_policy',
                JobRetryPolicy::encodePolicy(
                    JobRetryPolicy::parsePolicyPost($_POST, 'job_retry_settings', JobRetryPolicy::getGlobalPolicy(), false),
                    false
                )
            );

            Database::setSetting('roles_json', AppConfig::encodeRoles($parsedRoles));
            if ($infisicalConfigChanged) {
                $beforeUrl = $currentInfisicalConfig['infisical_url'];
                $currentInfisicalConfig = InfisicalConfigManager::persistConfiguration(
                    $candidateInfisicalConfig,
                    (int) Auth::currentUser()['id'],
                    $infisicalValidationResult
                );
                Auth::log(
                    'infisical_config_updated',
                    'Configuration Infisical mise a jour — ancienne URL: ' . ($beforeUrl !== '' ? $beforeUrl : '(vide)')
                    . ' ; nouvelle URL: ' . ($currentInfisicalConfig['infisical_url'] !== '' ? $currentInfisicalConfig['infisical_url'] : '(vide)')
                    . ' ; test: ' . (string) ($infisicalValidationResult['output'] ?? 'OK')
                );
            }
            $roleRows = AppConfig::getRoles();
            $timezoneMode = AppConfig::timezoneUsesServerDefault() ? 'server' : 'custom';
            $timezoneCustom = AppConfig::timezoneUsesServerDefault() ? AppConfig::serverTimezone() : AppConfig::timezone();
            $flash = ['type' => 'success', 'msg' => t('settings.flash.saved')];
        } else {
            $flash = ['type' => 'danger', 'msg' => implode(' ', $allErrors)];
        }
    }

    if (!isset($_POST['restore_infisical_history_id']) && (($_POST['action'] ?? '') === 'test_email')) {
        $result = Notifier::sendTest(trim($_POST['test_to'] ?? Database::getSetting('mail_to')));
        $flash = ['type' => $result['success'] ? 'success' : 'danger', 'msg' => $result['output']];
        $activeTab = 'general';
        $activeSection = 'notifications';
    }
}

$currentInfisicalConfig = InfisicalConfigManager::currentConfig();
$infisicalHistory = InfisicalConfigManager::history(8);
$preservePostedInfisicalValues = $_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['restore_infisical_history_id'])
    && (($_POST['action'] ?? '') === 'save_settings')
    && is_array($flash)
    && (($flash['type'] ?? '') === 'danger');
$infisicalFormValues = $preservePostedInfisicalValues
    ? InfisicalConfigManager::candidateFromInput($_POST, $currentInfisicalConfig)
    : $currentInfisicalConfig;

$rateRows = [
    [t('settings.rate.full_restore'), 'api_rate_limit_restore_hits', 'api_rate_limit_restore_window_seconds'],
    [t('settings.rate.partial_restore'), 'api_rate_limit_restore_partial_hits', 'api_rate_limit_restore_partial_window_seconds'],
    [t('settings.rate.run_backup'), 'api_rate_limit_run_backup_hits', 'api_rate_limit_run_backup_window_seconds'],
    [t('settings.rate.run_copy'), 'api_rate_limit_run_copy_hits', 'api_rate_limit_run_copy_window_seconds'],
    [t('settings.rate.explorer'), 'api_rate_limit_explore_view_hits', 'api_rate_limit_explore_view_window_seconds'],
    [t('settings.rate.file_search'), 'api_rate_limit_search_files_hits', 'api_rate_limit_search_files_window_seconds'],
    [t('settings.rate.manage_cron'), 'api_rate_limit_manage_cron_hits', 'api_rate_limit_manage_cron_window_seconds'],
    [t('settings.rate.manage_scheduler'), 'api_rate_limit_manage_scheduler_hits', 'api_rate_limit_manage_scheduler_window_seconds'],
    [t('settings.rate.manage_worker'), 'api_rate_limit_manage_worker_hits', 'api_rate_limit_manage_worker_window_seconds'],
    [t('settings.rate.reauth'), 'api_rate_limit_reauth_hits', 'api_rate_limit_reauth_window_seconds'],
];
$webauthRateRows = [
    ['WebAuthn login', 'api_rate_limit_webauthn_auth_hits', 'api_rate_limit_webauthn_auth_window_seconds'],
    [t('settings.rate.webauthn_reg'), 'api_rate_limit_webauthn_reg_hits', 'api_rate_limit_webauthn_reg_window_seconds'],
];
$channels = [
    ['label' => 'Discord', 'enabled_key' => 'discord_enabled', 'value_key' => 'discord_webhook_url', 'placeholder' => 'https://discord.com/api/webhooks/...', 'test_id' => 'discord'],
    ['label' => 'Slack', 'enabled_key' => 'slack_enabled', 'value_key' => 'slack_webhook_url', 'placeholder' => 'https://hooks.slack.com/services/...', 'test_id' => 'slack'],
    ['label' => 'Telegram', 'enabled_key' => 'telegram_enabled', 'value_key' => 'telegram_bot_token', 'placeholder' => '123456:ABC...', 'test_id' => 'telegram'],
    ['label' => 'ntfy', 'enabled_key' => 'ntfy_enabled', 'value_key' => 'ntfy_url', 'placeholder' => 'https://ntfy.sh', 'test_id' => 'ntfy'],
    ['label' => t('settings.channel.generic_webhook'), 'enabled_key' => 'webhook_enabled', 'value_key' => 'webhook_url', 'placeholder' => 'https://automation.example/webhook/fulgurite', 'test_id' => 'webhook'],
    ['label' => 'Microsoft Teams', 'enabled_key' => 'teams_enabled', 'value_key' => 'teams_webhook_url', 'placeholder' => 'https://outlook.office.com/webhook/...', 'test_id' => 'teams'],
    ['label' => 'Gotify', 'enabled_key' => 'gotify_enabled', 'value_key' => 'gotify_url', 'placeholder' => 'https://gotify.example.tld', 'test_id' => 'gotify'],
    ['label' => t('settings.channel.in_app'), 'enabled_key' => 'in_app_enabled', 'value_key' => '', 'placeholder' => '', 'test_id' => 'in_app'],
    ['label' => t('settings.channel.web_push'), 'enabled_key' => 'web_push_enabled', 'value_key' => '', 'placeholder' => '', 'test_id' => 'web_push'],
];
$notificationHistory = Notifier::getRecentNotificationLogs(30);
$serverTimezone = AppConfig::serverTimezone();
$activeTimezone = AppConfig::timezone();
$timezoneUsesServer = AppConfig::timezoneUsesServerDefault();
$serverNow = (new DateTimeImmutable('now', appServerTimezone()))->format('d/m/Y H:i:s');
$displayNow = formatCurrentDisplayDate();

$title = t('settings.title');
$active = 'settings';
include 'layout_top.php';
?>

<style<?= cspNonceAttr() ?>>
.infisical-settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.infisical-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 12px;
    margin: 14px 0 10px;
}

.infisical-option-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: color-mix(in srgb, var(--panel, #1b1f2a) 92%, white 8%);
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
}

.infisical-option-card:hover {
    border-color: var(--accent);
    background: color-mix(in srgb, var(--panel, #1b1f2a) 88%, var(--accent) 12%);
}

.infisical-option-card input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: var(--accent);
    flex: 0 0 auto;
}

.infisical-option-card input[type="checkbox"]:disabled {
    opacity: .55;
}

.infisical-option-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.infisical-option-title {
    font-weight: 600;
    color: var(--text);
    line-height: 1.35;
}

.infisical-option-help {
    font-size: 12px;
    color: var(--text2);
    line-height: 1.45;
}

.infisical-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 4px;
}

@media (max-width: 900px) {
    .infisical-settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="settings-shell">
    <div class="settings-tabs-wrap">
        <div class="settings-tabs-heading">
            <div class="settings-tabs-title"><?= t('settings.heading') ?></div>
            <div class="settings-tabs-subtitle"><?= t('settings.heading_subtitle') ?></div>
        </div>
        <div class="settings-tabs" role="tablist" aria-label="<?= h(t('settings.tabs_aria_label')) ?>">
            <?php foreach ($tabs as $key => $label): ?>
                    <a href="<?= routePath('/settings.php', ['tab' => tabLinkTarget($key, currentSectionFromInput($activeSection, $key))]) ?>" class="settings-tab <?= $activeTab === $key ? 'active' : '' ?>" data-tab-link data-tab="<?= h($key) ?>"><span class="settings-tab-label"><?= h($label) ?></span></a>
            <?php endforeach; ?>
        </div>
        <div class="settings-subtabs" id="settings-subtabs" aria-label="Sections du groupe actif">
            <?php foreach (settingsTabSections()[$activeTab] ?? [] as $section): ?>
                            <a href="<?= routePath('/settings.php', ['tab' => $section]) ?>" class="settings-subtab <?= $activeSection === $section ? 'active' : '' ?>" data-section-link data-tab="<?= h($activeTab) ?>" data-section="<?= h($section) ?>"><?= h(settingsSectionLabels()[$section] ?? ucfirst($section)) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <form method="POST" class="settings-form">
        <input type="hidden" name="action" value="save_settings">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="active_tab" id="active-tab-input" value="<?= h($activeTab) ?>">
        <input type="hidden" name="active_section" id="active-section-input" value="<?= h($activeSection) ?>">
        <section class="settings-panel <?= $activeTab === 'general' ? 'active' : '' ?>" data-tab-panel="general">
            <details class="settings-disclosure" data-section="interface" <?= disclosureOpenAttr($activeTab, $activeSection, 'general', 'interface') ?>>
                <?= disclosureSummary(t('settings.disc.interface.title'), t('settings.disc.interface.meta')) ?>
                <div class="settings-disclosure-body">
            <div class="card">
                <div class="card-header"><?= t('settings.card.brand') ?></div>
                <div class="card-body settings-grid-two">
                    <div class="form-group">
                        <label class="form-label"><?= t('settings.label.app_name') ?></label>
                        <input type="text" name="interface_app_name" class="form-control" value="<?= settingValue('interface_app_name', AppConfig::appName()) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('settings.label.logo_letter') ?></label>
                        <input type="text" name="interface_logo_letter" class="form-control" value="<?= settingValue('interface_logo_letter', AppConfig::appLogoLetter()) ?>" maxlength="2">
                    </div>
                    <div class="form-group" style="grid-column:1 / -1">
                        <label class="form-label"><?= t('settings.label.app_subtitle') ?></label>
                        <input type="text" name="interface_app_subtitle" class="form-control" value="<?= settingValue('interface_app_subtitle', AppConfig::appSubtitle()) ?>">
                    </div>
                    <div class="form-group" style="grid-column:1 / -1">
                        <label class="form-label"><?= t('settings.label.login_tagline') ?></label>
                        <input type="text" name="interface_login_tagline" class="form-control" value="<?= settingValue('interface_login_tagline', AppConfig::loginTagline()) ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><?= t('settings.card.visual_timing') ?></div>
                <div class="card-body settings-grid-two">
                    <div class="form-group" style="grid-column:1 / -1">
                        <label class="form-label"><?= t('settings.label.timezone') ?></label>
                        <div style="display:grid;gap:10px">
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid var(--border);border-radius:10px;cursor:pointer">
                                <input type="radio" name="interface_timezone_mode" value="server" <?= $timezoneMode === 'server' ? 'checked' : '' ?> style="margin-top:2px;accent-color:var(--accent)" onchange="toggleTimezoneMode()">
                                <span>
                                    <strong><?= t('settings.tz.follow_server') ?></strong><br>
                                    <span class="settings-help"><?= t('settings.tz.follow_server_hint', ['tz' => h($serverTimezone)]) ?></span>
                                </span>
                            </label>
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid var(--border);border-radius:10px;cursor:pointer">
                                <input type="radio" name="interface_timezone_mode" value="custom" <?= $timezoneMode === 'custom' ? 'checked' : '' ?> style="margin-top:2px;accent-color:var(--accent)" onchange="toggleTimezoneMode()">
                                <span style="flex:1">
                                    <strong><?= t('settings.tz.custom') ?></strong><br>
                                    <span class="settings-help"><?= t('settings.tz.custom_hint') ?></span>
                                </span>
                            </label>
                        </div>
                        <div id="timezone-custom-wrap" style="margin-top:12px;<?= $timezoneMode === 'custom' ? '' : 'display:none' ?>">
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">
                                <?php foreach ($commonTimezones as $commonTimezone): ?>
                                <button type="button"
                                        class="btn btn-sm <?= $timezoneCustom === $commonTimezone ? 'btn-primary' : '' ?>"
                                        data-timezone-choice="<?= h($commonTimezone) ?>"
                                        onclick="applyTimezoneChoice('<?= h($commonTimezone) ?>')">
                                    <?= h($commonTimezone) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="text" name="interface_timezone_custom" id="timezone-custom-input" class="form-control" list="timezone-options" value="<?= h($timezoneCustom) ?>" placeholder="Europe/Paris" autocomplete="off" spellcheck="false">
                            <datalist id="timezone-options">
                                <?php foreach ($timezoneIdentifiers as $timezoneIdentifier): ?>
                                <option value="<?= h($timezoneIdentifier) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <div id="timezone-validation-message" class="settings-help" style="margin-top:8px">
                                <?= t('settings.tz.validation_hint') ?>
                            </div>
                        </div>
                        <div class="settings-help" style="margin-top:8px">
                            <?= t('settings.tz.detected', ['server' => h($serverTimezone), 'active' => h($activeTimezone)]) ?><?= $timezoneUsesServer ? ' (' . t('settings.tz.server_label') . ')' : '' ?>.
                        </div>
                        <div class="settings-help">
                            <?= t('settings.tz.preview', ['server' => h($serverNow), 'app' => h($displayNow)]) ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('settings.label.dashboard_refresh') ?></label>
                        <input type="number" name="interface_dashboard_refresh_seconds" class="form-control" value="<?= settingValue('interface_dashboard_refresh_seconds', (string) AppConfig::dashboardRefreshSeconds()) ?>" min="10" max="3600">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('settings.label.stats_period') ?></label>
                        <?= renderSelectOptions('interface_stats_default_period_days', ['7' => t('settings.period.7d'), '14' => t('settings.period.14d'), '30' => t('settings.period.30d'), '90' => t('settings.period.90d')], settingValue('interface_stats_default_period_days', (string) AppConfig::statsDefaultPeriodDays())) ?>
                    </div>
                </div>
            </div>
                </div>
            </details>
        </section>
        <section class="settings-panel <?= $activeTab === 'backup' ? 'active' : '' ?>" data-tab-panel="backup">
            <details class="settings-disclosure" data-section="backup" <?= disclosureOpenAttr($activeTab, $activeSection, 'backup', 'backup') ?>>
                <?= disclosureSummary(t('settings.disc.backup.title'), t('settings.disc.backup.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.backup_alerts') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group">
                                <label class="form-label"><?= t('settings.label.backup_alert_hours') ?></label>
                                <input type="number" name="backup_alert_hours" class="form-control" value="<?= settingValue('backup_alert_hours', '25') ?>" min="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= t('settings.label.sftp_user') ?></label>
                                <input type="text" name="backup_server_sftp_user" class="form-control" value="<?= settingValue('backup_server_sftp_user', 'backup') ?>">
                            </div>
                            <div class="form-group" style="grid-column:1 / -1">
                                <label class="form-label"><?= t('settings.label.sftp_host') ?></label>
                                <input type="text" name="backup_server_host" class="form-control" value="<?= settingValue('backup_server_host') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.job_defaults') ?></div>
                        <div class="card-body">
                            <div style="display:flex;flex-direction:column;gap:10px">
                                <label class="settings-toggle"><input type="checkbox" name="backup_job_default_schedule_enabled" value="1" <?= settingCheck('backup_job_default_schedule_enabled') ?>><span><?= t('settings.toggle.default_schedule') ?></span></label>
                                <label class="settings-toggle"><input type="checkbox" name="backup_job_default_notify_on_failure" value="1" <?= settingCheck('backup_job_default_notify_on_failure') ?>><span><?= t('settings.toggle.notify_on_failure') ?></span></label>
                            </div>
                            <div style="display:grid;grid-template-columns:220px 1fr;gap:12px;margin-top:16px">
                                <div class="form-group">
                                    <label class="form-label"><?= t('settings.label.default_hour') ?></label>
                                    <?= renderHourOptions('backup_job_default_schedule_hour', Database::getSetting('backup_job_default_schedule_hour', '2')) ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('settings.label.default_days') ?></label>
                                    <?= renderDayCheckboxes('backup_job_default_schedule_days', $backupDefaultDays) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.default_retention') ?></div>
                        <div class="card-body">
                            <label class="settings-toggle" style="margin-bottom:14px"><input type="checkbox" name="backup_job_default_retention_enabled" value="1" <?= settingCheck('backup_job_default_retention_enabled') ?>><span><?= t('settings.toggle.default_retention') ?></span></label>
                            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px">
                                <div class="form-group"><label class="form-label"><?= t('settings.label.keep_last') ?></label><input type="number" name="backup_job_default_retention_keep_last" class="form-control" value="<?= settingValue('backup_job_default_retention_keep_last', '0') ?>" min="0"></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.keep_daily') ?></label><input type="number" name="backup_job_default_retention_keep_daily" class="form-control" value="<?= settingValue('backup_job_default_retention_keep_daily', '0') ?>" min="0"></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.keep_weekly') ?></label><input type="number" name="backup_job_default_retention_keep_weekly" class="form-control" value="<?= settingValue('backup_job_default_retention_keep_weekly', '0') ?>" min="0"></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.keep_monthly') ?></label><input type="number" name="backup_job_default_retention_keep_monthly" class="form-control" value="<?= settingValue('backup_job_default_retention_keep_monthly', '0') ?>" min="0"></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.keep_yearly') ?></label><input type="number" name="backup_job_default_retention_keep_yearly" class="form-control" value="<?= settingValue('backup_job_default_retention_keep_yearly', '0') ?>" min="0"></div>
                            </div>
                            <label class="settings-toggle" style="margin-top:12px"><input type="checkbox" name="backup_job_default_retention_prune" value="1" <?= settingCheck('backup_job_default_retention_prune') ?>><span><?= t('settings.toggle.default_prune') ?></span></label>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.disk_monitoring') ?></div>
                        <div class="card-body">
                            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px">
                                <label class="settings-toggle"><input type="checkbox" name="disk_monitoring_enabled" value="1" <?= settingCheck('disk_monitoring_enabled') ?>><span><?= t('settings.toggle.disk_monitoring') ?></span></label>
                                <label class="settings-toggle"><input type="checkbox" name="disk_preflight_enabled" value="1" <?= settingCheck('disk_preflight_enabled') ?>><span><?= t('settings.toggle.disk_preflight') ?></span></label>
                            </div>

                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px">
                                <div class="form-group"><label class="form-label"><?= t('settings.label.preflight_margin') ?></label><input type="number" name="disk_preflight_margin_percent" class="form-control" value="<?= settingValue('disk_preflight_margin_percent', '25') ?>" min="0" max="500"></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.preflight_min_free') ?></label><input type="number" name="disk_preflight_min_free_gb" class="form-control" value="<?= settingValue('disk_preflight_min_free_gb', '10') ?>" min="1"></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.disk_history_days') ?></label><input type="number" name="disk_monitor_history_retention_days" class="form-control" value="<?= settingValue('disk_monitor_history_retention_days', '30') ?>" min="1" max="3650"></div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                                <div style="border:1px solid var(--border);border-radius:10px;padding:14px">
                                    <div style="font-weight:600;margin-bottom:10px"><?= t('settings.disk.local_thresholds') ?></div>
                                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                                        <div class="form-group"><label class="form-label"><?= t('settings.disk.warning_pct') ?></label><input type="number" name="disk_local_warning_percent" class="form-control" value="<?= settingValue('disk_local_warning_percent', '80') ?>" min="1" max="100"></div>
                                        <div class="form-group"><label class="form-label"><?= t('settings.disk.critical_pct') ?></label><input type="number" name="disk_local_critical_percent" class="form-control" value="<?= settingValue('disk_local_critical_percent', '90') ?>" min="1" max="100"></div>
                                        <div class="form-group"><label class="form-label"><?= t('settings.disk.warning_free') ?></label><input type="number" name="disk_local_warning_free_gb" class="form-control" value="<?= settingValue('disk_local_warning_free_gb', '50') ?>" min="1"></div>
                                        <div class="form-group"><label class="form-label"><?= t('settings.disk.critical_free') ?></label><input type="number" name="disk_local_critical_free_gb" class="form-control" value="<?= settingValue('disk_local_critical_free_gb', '20') ?>" min="1"></div>
                                    </div>
                                </div>
                                <div style="border:1px solid var(--border);border-radius:10px;padding:14px">
                                    <div style="font-weight:600;margin-bottom:10px"><?= t('settings.disk.remote_thresholds') ?></div>
                                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                                        <div class="form-group"><label class="form-label"><?= t('settings.disk.warning_pct') ?></label><input type="number" name="disk_remote_warning_percent" class="form-control" value="<?= settingValue('disk_remote_warning_percent', '80') ?>" min="1" max="100"></div>
                                        <div class="form-group"><label class="form-label"><?= t('settings.disk.critical_pct') ?></label><input type="number" name="disk_remote_critical_percent" class="form-control" value="<?= settingValue('disk_remote_critical_percent', '90') ?>" min="1" max="100"></div>
                                        <div class="form-group"><label class="form-label"><?= t('settings.disk.warning_free') ?></label><input type="number" name="disk_remote_warning_free_gb" class="form-control" value="<?= settingValue('disk_remote_warning_free_gb', '50') ?>" min="1"></div>
                                        <div class="form-group"><label class="form-label"><?= t('settings.disk.critical_free') ?></label><input type="number" name="disk_remote_critical_free_gb" class="form-control" value="<?= settingValue('disk_remote_critical_free_gb', '20') ?>" min="1"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="settings-help" style="margin-top:12px">
                                <?= t('settings.disk.help') ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.disk_notifications') ?></div>
                        <div class="card-body">
                            <?= renderNotificationPolicyEditor('disk_space_settings', 'disk_space', Notifier::getSettingPolicy('disk_space_notification_policy', 'disk_space')) ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.job_retry') ?></div>
                        <div class="card-body">
                            <div class="settings-help" style="margin-bottom:14px"><?= t('settings.help.job_retry') ?></div>
                            <?= renderRetryPolicyEditor('job_retry_settings', JobRetryPolicy::getGlobalPolicy(), false) ?>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'backup' ? 'active' : '' ?>" data-tab-panel="backup">
            <details class="settings-disclosure" data-section="restore" <?= disclosureOpenAttr($activeTab, $activeSection, 'backup', 'restore') ?>>
                <?= disclosureSummary(t('settings.disc.restore.title'), t('settings.disc.restore.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.restore_folders') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group">
                                <label class="form-label"><?= t('settings.label.restore_local_root') ?></label>
                                <input type="text" name="restore_managed_local_root" class="form-control" value="<?= settingValue('restore_managed_local_root', dirname(DB_PATH) . '/restores') ?>">
                                <div class="settings-help"><?= t('settings.help.restore_local_root', ['mode' => h(AppConfig::restoreManagedDirectoryModeLabel())]) ?></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= t('settings.label.restore_remote_root') ?></label>
                                <input type="text" name="restore_managed_remote_root" class="form-control" value="<?= settingValue('restore_managed_remote_root', '/var/tmp/fulgurite-restores') ?>">
                                <div class="settings-help"><?= t('settings.help.restore_remote_root') ?></div>
                            </div>
                            <div class="form-group" style="grid-column:1 / -1">
                                <label class="settings-toggle">
                                    <input type="checkbox" name="restore_append_context_subdir" value="1" <?= settingCheck('restore_append_context_subdir') ?>>
                                    <span><?= t('settings.toggle.restore_context_subdir') ?></span>
                                </label>
                                <div class="settings-help"><?= t('settings.help.restore_context_subdir') ?></div>
                            </div>

                            <?php if (Auth::isAdmin()): ?>
                            <div class="form-group" style="grid-column:1 / -1">
                                <label class="settings-toggle"><input type="checkbox" name="restore_original_global_enabled" value="1" <?= settingCheck('restore_original_global_enabled') ?>><span><?= t('settings.toggle.restore_original') ?></span></label>
                                <div class="settings-help"><?= t('settings.help.restore_original') ?></div>
                            </div>

                            <div class="form-group" style="grid-column:1 / -1">
                                <label class="form-label"><?= t('settings.label.restore_allowed_paths') ?></label>
                                <input type="text" name="restore_original_allowed_paths" class="form-control" value="<?= settingValue('restore_original_allowed_paths') ?>" placeholder="/etc,/home,/var/www,/">
                                <div class="settings-help"><?= t('settings.help.restore_allowed_paths') ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="alert alert-warning" style="grid-column:1 / -1;font-size:12px">
                                <?= t('settings.warning.restore_original') ?>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.history') ?></div>
                        <div class="card-body" style="max-width:260px">
                            <label class="form-label"><?= t('settings.label.history_page_size') ?></label>
                            <input type="number" name="restore_history_page_size" class="form-control" value="<?= settingValue('restore_history_page_size', '30') ?>" min="10" max="500">
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'access' ? 'active' : '' ?>" data-tab-panel="access">
            <details class="settings-disclosure" data-section="security" <?= disclosureOpenAttr($activeTab, $activeSection, 'access', 'security') ?>>
                <?= disclosureSummary(t('settings.disc.security.title'), t('settings.disc.security.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.sessions') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.session_lifetime') ?></label><input type="number" name="session_absolute_lifetime_minutes" class="form-control" value="<?= settingValue('session_absolute_lifetime_minutes', '480') ?>" min="5"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.session_inactivity') ?></label><input type="number" name="session_inactivity_minutes" class="form-control" value="<?= settingValue('session_inactivity_minutes', '30') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.session_warning') ?></label><input type="number" name="session_warning_minutes" class="form-control" value="<?= settingValue('session_warning_minutes', '2') ?>" min="0"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.session_db_sync') ?></label><input type="number" name="session_db_touch_interval_seconds" class="form-control" value="<?= settingValue('session_db_touch_interval_seconds', '180') ?>" min="15"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.max_sessions') ?></label><input type="number" name="max_sessions_per_user" class="form-control" value="<?= settingValue('max_sessions_per_user', '5') ?>" min="0"></div>
                            <div class="form-group" style="display:flex;align-items:end"><label class="settings-toggle"><input type="checkbox" name="session_strict_fingerprint" value="1" <?= settingCheck('session_strict_fingerprint') ?>><span><?= t('settings.toggle.strict_fingerprint') ?></span></label></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.login_2fa') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.login_max_attempts') ?></label><input type="number" name="login_max_attempts" class="form-control" value="<?= settingValue('login_max_attempts', '5') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.lockout_minutes') ?></label><input type="number" name="login_lockout_minutes" class="form-control" value="<?= settingValue('login_lockout_minutes', '15') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.reauth_seconds') ?></label><input type="number" name="reauth_max_age_seconds" class="form-control" value="<?= settingValue('reauth_max_age_seconds', '300') ?>" min="30"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.second_factor_ttl') ?></label><input type="number" name="second_factor_pending_ttl_seconds" class="form-control" value="<?= settingValue('second_factor_pending_ttl_seconds', '300') ?>" min="30"></div>
                            <div class="form-group" style="grid-column:1 / -1"><label class="settings-toggle"><input type="checkbox" name="force_admin_2fa" value="1" <?= settingCheck('force_admin_2fa') ?>><span><?= t('settings.toggle.force_admin_2fa') ?></span></label><div class="settings-help"><?= t('settings.help.force_admin_2fa') ?></div></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.login_notifications') ?></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                            <label class="settings-toggle"><input type="checkbox" name="login_notifications_enabled" value="1" <?= settingCheck('login_notifications_enabled') ?>><span><?= t('settings.toggle.notify_on_login') ?></span></label>
                            <label class="settings-toggle"><input type="checkbox" name="login_notifications_new_ip_only" value="1" <?= settingCheck('login_notifications_new_ip_only') ?>><span><?= t('settings.toggle.notify_new_ip_only') ?></span></label>
                            <?= renderNotificationPolicyEditor('login_notification', 'login', Notifier::getSettingPolicy('login_notification_policy', 'login')) ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.security_alerts') ?></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                            <div class="settings-help"><?= t('settings.help.security_alerts') ?></div>
                            <?= renderNotificationPolicyEditor('security_alert', 'security', Notifier::getSettingPolicy('security_alert_notification_policy', 'security')) ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.secret_broker') ?></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                            <div class="settings-help"><?= t('settings.help.secret_broker') ?></div>
                            <?= renderNotificationPolicyEditor('secret_broker_settings', 'secret_broker', Notifier::getSettingPolicy('secret_broker_notification_policy', 'secret_broker')) ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.theme_requests') ?></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                            <div class="settings-help"><?= t('settings.help.theme_requests') ?></div>
                            <?= renderNotificationPolicyEditor('theme_request_submitted', 'theme_request', Notifier::getSettingPolicy('theme_request_notification_policy', 'theme_request')) ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.api_rate_limits') ?></div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;align-items:end">
                                <div class="form-group"><label class="form-label"><?= t('settings.label.global_value') ?></label><input type="text" class="form-control" value="<?= h(t('settings.rate.all_apis')) ?>" readonly></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.max_requests') ?></label><input type="number" name="api_rate_limit_default_hits" class="form-control" value="<?= settingValue('api_rate_limit_default_hits', '20') ?>" min="1"></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.window_s') ?></label><input type="number" name="api_rate_limit_default_window_seconds" class="form-control" value="<?= settingValue('api_rate_limit_default_window_seconds', '60') ?>" min="1"></div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:12px;margin-top:16px">
                                <?php foreach ($rateRows as [$label, $hitsKey, $windowKey]): ?>
                                <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;align-items:end">
                                    <div class="form-group"><label class="form-label"><?= h($label) ?></label><input type="text" class="form-control" value="<?= h($label) ?>" readonly></div>
                                    <div class="form-group"><label class="form-label"><?= t('settings.label.max_requests') ?></label><input type="number" name="<?= h($hitsKey) ?>" class="form-control" value="<?= settingValue($hitsKey) ?>" min="1"></div>
                                    <div class="form-group"><label class="form-label"><?= t('settings.label.window') ?></label><input type="number" name="<?= h($windowKey) ?>" class="form-control" value="<?= settingValue($windowKey) ?>" min="1"></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'access' ? 'active' : '' ?>" data-tab-panel="access">
            <details class="settings-disclosure" data-section="audit" <?= disclosureOpenAttr($activeTab, $activeSection, 'access', 'audit') ?>>
                <?= disclosureSummary(t('settings.disc.audit.title'), t('settings.disc.audit.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.log_viewing') ?></div>
                        <div class="card-body settings-grid-two">
                            <div class="form-group">
                                <label class="form-label"><?= t('settings.label.audit_logs_page_size') ?></label>
                                <input type="number" name="audit_logs_page_size" class="form-control" value="<?= settingValue('audit_logs_page_size', '50') ?>" min="10" max="500">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= t('settings.label.audit_dashboard_limit') ?></label>
                                <input type="number" name="audit_dashboard_recent_limit" class="form-control" value="<?= settingValue('audit_dashboard_recent_limit', '8') ?>" min="1" max="50">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.retention_archiving') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_activity_days') ?></label><input type="number" name="audit_activity_retention_days" class="form-control" value="<?= settingValue('audit_activity_retention_days', '180') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_restore_days') ?></label><input type="number" name="audit_restore_retention_days" class="form-control" value="<?= settingValue('audit_restore_retention_days', '180') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_cron_days') ?></label><input type="number" name="audit_cron_retention_days" class="form-control" value="<?= settingValue('audit_cron_retention_days', '90') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_rate_limit_days') ?></label><input type="number" name="audit_rate_limit_retention_days" class="form-control" value="<?= settingValue('audit_rate_limit_retention_days', '30') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_login_attempt_days') ?></label><input type="number" name="audit_login_attempt_retention_days" class="form-control" value="<?= settingValue('audit_login_attempt_retention_days', '30') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_job_queue_days') ?></label><input type="number" name="audit_job_queue_retention_days" class="form-control" value="<?= settingValue('audit_job_queue_retention_days', '14') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_archive_days') ?></label><input type="number" name="audit_archive_retention_days" class="form-control" value="<?= settingValue('audit_archive_retention_days', '365') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_stats_hourly_days') ?></label><input type="number" name="audit_repo_stats_hourly_retention_days" class="form-control" value="<?= settingValue('audit_repo_stats_hourly_retention_days', '30') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.audit_stats_downsample') ?></label><input type="number" name="audit_repo_stats_downsample_batch" class="form-control" value="<?= settingValue('audit_repo_stats_downsample_batch', '5000') ?>" min="100"></div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'general' ? 'active' : '' ?>" data-tab-panel="general">
            <details class="settings-disclosure" data-section="notifications" <?= disclosureOpenAttr($activeTab, $activeSection, 'general', 'notifications') ?>>
                <?= disclosureSummary(t('settings.disc.notifications.title'), t('settings.disc.notifications.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><span>Email</span><label class="settings-toggle"><input type="checkbox" name="mail_enabled" value="1" <?= settingCheck('mail_enabled') ?>><span><?= t('settings.toggle.enabled') ?></span></label></div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                <div class="form-group"><label class="form-label"><?= t('settings.label.mail_from') ?></label><input type="email" name="mail_from" class="form-control" value="<?= settingValue('mail_from') ?>"></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.mail_from_name') ?></label><input type="text" name="mail_from_name" class="form-control" value="<?= settingValue('mail_from_name', 'Fulgurite') ?>"></div>
                                <div class="form-group" style="grid-column:1 / -1"><label class="form-label"><?= t('settings.label.mail_to') ?></label><input type="text" name="mail_to" class="form-control" value="<?= settingValue('mail_to') ?>"></div>
                            </div>
                            <div class="settings-subsection">
                                <div class="settings-subtitle">SMTP</div>
                                <div style="display:grid;grid-template-columns:1fr 140px;gap:12px">
                                    <div class="form-group"><label class="form-label"><?= t('settings.label.smtp_host') ?></label><input type="text" name="smtp_host" class="form-control" value="<?= settingValue('smtp_host') ?>"></div>
                                    <div class="form-group"><label class="form-label"><?= t('settings.label.smtp_port') ?></label><input type="number" name="smtp_port" class="form-control" value="<?= settingValue('smtp_port', '587') ?>" min="1"></div>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                    <div class="form-group"><label class="form-label"><?= t('settings.label.smtp_user') ?></label><input type="text" name="smtp_user" class="form-control" value="<?= settingValue('smtp_user') ?>"></div>
                                    <div class="form-group"><label class="form-label"><?= t('settings.label.smtp_pass') ?></label><input type="password" name="smtp_pass" class="form-control" placeholder="<?= h(secretPlaceholder('smtp_pass', t('settings.label.smtp_pass_empty'))) ?>" autocomplete="new-password"></div>
                                </div>
                                <label class="settings-toggle"><input type="checkbox" name="smtp_tls" value="1" <?= settingCheck('smtp_tls') ?>><span><?= t('settings.label.smtp_tls') ?></span></label>
                            </div>
                            <div class="settings-subsection">
                                <div class="settings-subtitle"><?= t('settings.label.smtp_test_title') ?></div>
                                <div class="settings-inline-form">
                                    <div style="display:flex;gap:12px;align-items:flex-end">
                                        <div class="form-group" style="flex:1;margin-bottom:0">
                                            <label class="form-label" for="test-email-recipient"><?= t('settings.label.smtp_test_to') ?></label>
                                            <input type="email" id="test-email-recipient" name="test_to" class="form-control" value="<?= h(Database::getSetting('mail_to')) ?>" form="test-email-form">
                                        </div>
                                        <button type="submit" class="btn btn-warning" form="test-email-form"><?= t('settings.btn.send_test') ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-grid-two">
                        <?php foreach ($channels as $channel): ?>
                        <?php
                        $label = $channel['label'];
                        $enabledKey = $channel['enabled_key'];
                        $valueKey = $channel['value_key'];
                        $placeholder = $channel['placeholder'];
                        $testId = $channel['test_id'];
                        ?>
                        <div class="card">
                            <div class="card-header"><span><?= h($label) ?></span><label class="settings-toggle"><input type="checkbox" name="<?= h($enabledKey) ?>" value="1" <?= settingCheck($enabledKey) ?>><span><?= t('settings.channel.active') ?></span></label></div>
                            <div class="card-body">
                                <?php if ($testId === 'telegram'): ?>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                    <div class="form-group"><label class="form-label"><?= t('settings.channel.telegram_token') ?></label><input type="password" name="telegram_bot_token" class="form-control" placeholder="<?= h(secretPlaceholder('telegram_bot_token', '123456:ABC...')) ?>" autocomplete="new-password"></div>
                                    <div class="form-group"><label class="form-label">Chat ID</label><input type="text" name="telegram_chat_id" class="form-control" value="<?= settingValue('telegram_chat_id') ?>" placeholder="-100123456789"></div>
                                </div>
                                <?php elseif ($testId === 'ntfy'): ?>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                    <div class="form-group"><label class="form-label"><?= t('settings.channel.server_url') ?></label><input type="text" name="ntfy_url" class="form-control" value="<?= settingValue('ntfy_url') ?>" placeholder="<?= h($placeholder) ?>"></div>
                                    <div class="form-group"><label class="form-label">Topic</label><input type="text" name="ntfy_topic" class="form-control" value="<?= settingValue('ntfy_topic') ?>" placeholder="fulgurite-alerts"></div>
                                </div>
                                <?php elseif ($testId === 'webhook'): ?>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                    <div class="form-group"><label class="form-label"><?= t('settings.channel.webhook_url') ?></label><input type="password" name="webhook_url" class="form-control" placeholder="<?= h(secretPlaceholder('webhook_url', $placeholder)) ?>" autocomplete="new-password"></div>
                                    <div class="form-group"><label class="form-label">Bearer token</label><input type="password" name="webhook_auth_token" class="form-control" placeholder="<?= h(secretPlaceholder('webhook_auth_token', t('settings.channel.webhook_bearer_empty'))) ?>" autocomplete="new-password"></div>
                                </div>
                                <div class="settings-help" style="margin-bottom:12px"><?= t('settings.channel.webhook_help') ?></div>
                                <?php elseif ($testId === 'gotify'): ?>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                    <div class="form-group"><label class="form-label"><?= t('settings.channel.server_url') ?></label><input type="text" name="gotify_url" class="form-control" value="<?= settingValue('gotify_url') ?>" placeholder="<?= h($placeholder) ?>"></div>
                                    <div class="form-group"><label class="form-label"><?= t('settings.channel.gotify_token') ?></label><input type="password" name="gotify_token" class="form-control" placeholder="<?= h(secretPlaceholder('gotify_token', t('settings.channel.gotify_token_empty'))) ?>" autocomplete="new-password"></div>
                                </div>
                                <div class="settings-help" style="margin-bottom:12px"><?= t('settings.channel.gotify_help') ?></div>
                                <?php elseif ($testId === 'in_app'): ?>
                                <div class="settings-help" style="margin-bottom:12px"><?= t('settings.channel.in_app_help') ?></div>
                                <?php elseif ($testId === 'web_push'): ?>
                                <div class="settings-help" style="margin-bottom:12px"><?= t('settings.channel.web_push_help') ?></div>
                                <?php else: ?>
                                <div class="form-group"><label class="form-label">Webhook</label><input type="password" name="<?= h($valueKey) ?>" class="form-control" placeholder="<?= h(secretPlaceholder($valueKey, $placeholder)) ?>" autocomplete="new-password"></div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm" onclick="testChannel('<?= h($testId) ?>')"><?= t('settings.btn.send_test') ?></button>
                                <span id="test-<?= h($testId) ?>-result" class="settings-inline-result"></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.notif_history.card_title') ?></div>
                        <div class="card-body table-wrap" style="overflow:auto">
                            <table class="table">
                                <thead>
                                    <tr><th>Date</th><th><?= t('settings.notif_history.col_context') ?></th><th><?= t('settings.notif_history.col_channel') ?></th><th><?= t('settings.notif_history.col_status') ?></th><th><?= t('settings.notif_history.col_details') ?></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notificationHistory as $item): ?>
                                    <tr>
                                        <td style="font-size:12px;white-space:nowrap"><?= h(formatDate((string) $item['created_at'])) ?></td>
                                        <td style="font-size:12px">
                                            <strong><?= h((string) $item['context_name']) ?></strong><br>
                                            <span style="color:var(--text2)"><?= h((string) $item['profile_key']) ?> / <?= h((string) $item['event_key']) ?></span>
                                        </td>
                                        <td><span class="badge badge-gray"><?= h((string) $item['channel']) ?></span></td>
                                        <td><span class="badge <?= !empty($item['success']) ? 'badge-green' : 'badge-red' ?>"><?= !empty($item['success']) ? 'OK' : t('settings.notif_history.failure') ?></span></td>
                                        <td style="font-size:12px;color:var(--text2)"><?= h((string) $item['output']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.notif_retention') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:minmax(220px,320px);gap:12px">
                            <div class="form-group">
                                <label class="form-label"><?= t('settings.label.notif_retention_days') ?></label>
                                <input type="number" name="app_notifications_retention_days" class="form-control" value="<?= settingValue('app_notifications_retention_days', '7') ?>" min="1" max="3650">
                                <div class="settings-help" style="margin-top:8px"><?= t('settings.help.notif_retention') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </details>
        </section>
        
        <section class="settings-panel <?= $activeTab === 'automation' ? 'active' : '' ?>" data-tab-panel="automation">
            <details class="settings-disclosure" data-section="scheduler" <?= disclosureOpenAttr($activeTab, $activeSection, 'automation', 'scheduler') ?>>
                <?= disclosureSummary(t('settings.disc.scheduler.title'), t('settings.disc.scheduler.meta')) ?>
                <div class="settings-disclosure-body">
            <div class="card">
                <div class="card-header"><?= t('settings.card.cron_central') ?></div>
                <div class="card-body" style="display:grid;grid-template-columns:1fr 160px;gap:12px">
                    <div class="form-group">
                        <label class="form-label"><?= t('settings.label.cron_line') ?></label>
                        <div class="code-viewer" style="font-size:11px"><?= h(SchedulerManager::getCronLine()) ?></div>
                        <div class="settings-help"><?= t('settings.help.cron_install') ?></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('settings.label.cron_minute') ?></label>
                        <input type="number" name="cron_run_minute" class="form-control" value="<?= settingValue('cron_run_minute', '0') ?>" min="0" max="59">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><?= t('settings.card.global_tasks') ?></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:18px">
                    <div>
                        <label class="settings-toggle" style="margin-bottom:12px"><input type="checkbox" name="weekly_report_enabled" value="1" <?= settingCheck('weekly_report_enabled') ?>><span><?= t('settings.toggle.weekly_report') ?></span></label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.day') ?></label><?= renderDayOptions('weekly_report_day', Database::getSetting('weekly_report_day', '1')) ?></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.hour') ?></label><?= renderHourOptions('weekly_report_hour', Database::getSetting('weekly_report_hour', '8')) ?></div>
                        </div>
                        <?= renderNotificationPolicyEditor('weekly_report_settings', 'weekly_report', Notifier::getSettingPolicy('weekly_report_notification_policy', 'weekly_report')) ?>
                    </div>
                    <div class="settings-subsection">
                        <label class="settings-toggle" style="margin-bottom:12px"><input type="checkbox" name="integrity_check_enabled" value="1" <?= settingCheck('integrity_check_enabled') ?>><span><?= t('settings.toggle.integrity_check') ?></span></label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.days') ?></label><?= renderDayCheckboxes('integrity_check_days', $integrityCheckDaysSelected) ?><div class="settings-help" style="margin-top:8px"><?= t('settings.help.multiple_days') ?></div></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.hour') ?></label><?= renderHourOptions('integrity_check_hour', Database::getSetting('integrity_check_hour', '3')) ?></div>
                        </div>
                        <?= renderNotificationPolicyEditor('integrity_check_settings', 'integrity_check', Notifier::getSettingPolicy('integrity_check_notification_policy', 'integrity_check')) ?>
                    </div>
                    <div class="settings-subsection">
                        <label class="settings-toggle" style="margin-bottom:12px"><input type="checkbox" name="maintenance_vacuum_enabled" value="1" <?= settingCheck('maintenance_vacuum_enabled') ?>><span><?= t('settings.toggle.vacuum') ?></span></label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.days') ?></label><?= renderDayCheckboxes('maintenance_vacuum_days', $maintenanceVacuumDaysSelected) ?><div class="settings-help" style="margin-top:8px"><?= t('settings.help.multiple_days') ?></div></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.hour') ?></label><?= renderHourOptions('maintenance_vacuum_hour', Database::getSetting('maintenance_vacuum_hour', '4')) ?></div>
                        </div>
                        <?= renderNotificationPolicyEditor('maintenance_vacuum_settings', 'maintenance_vacuum', Notifier::getSettingPolicy('maintenance_vacuum_notification_policy', 'maintenance_vacuum')) ?>
                    </div>
                </div>
            </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'automation' ? 'active' : '' ?>" data-tab-panel="automation">
            <details class="settings-disclosure" data-section="worker" <?= disclosureOpenAttr($activeTab, $activeSection, 'automation', 'worker') ?>>
                <?= disclosureSummary(t('settings.disc.worker.title'), t('settings.disc.worker.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.worker_dedicated') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.worker_name') ?></label><input type="text" name="worker_default_name" class="form-control" value="<?= settingValue('worker_default_name', AppConfig::workerDefaultName()) ?>"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.worker_sleep') ?></label><input type="number" name="worker_sleep_seconds" class="form-control" value="<?= settingValue('worker_sleep_seconds', '5') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.worker_jobs_per_loop') ?></label><input type="number" name="worker_limit" class="form-control" value="<?= settingValue('worker_limit', '3') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.worker_stale_window') ?></label><input type="number" name="worker_stale_minutes" class="form-control" value="<?= settingValue('worker_stale_minutes', '30') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.worker_heartbeat_stale') ?></label><input type="number" name="worker_heartbeat_stale_seconds" class="form-control" value="<?= settingValue('worker_heartbeat_stale_seconds', '20') ?>" min="5"></div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <!-- ── Danger zone: System reconfiguration ────────────────────────────── -->
        <section class="settings-panel <?= $activeTab === 'automation' ? 'active' : '' ?>" data-tab-panel="automation">
            <div class="card" style="border-color:rgba(248,81,73,.35);background:rgba(248,81,73,.04)">
                <div class="card-header" style="border-bottom-color:rgba(248,81,73,.2);display:flex;align-items:center;gap:10px">
                    <span style="font-size:15px" aria-hidden="true">⚠️</span>
                    <span style="color:var(--red)"><?= t('settings.danger.card_title') ?></span>
                </div>
                <div class="card-body">
                    <p style="font-size:13px;color:var(--text2);margin:0 0 14px;line-height:1.6;max-width:72ch">
                        <?= t('settings.danger.desc') ?>
                    </p>
                    <a href="<?= routePath('/resetup.php') ?>" class="btn btn-danger">
                        <?= t('settings.danger.btn') ?>
                    </a>
                </div>
            </div>
        </section>

        <section class="settings-panel <?= $activeTab === 'data' ? 'active' : '' ?>" data-tab-panel="data">
            <details class="settings-disclosure" data-section="exploration" <?= disclosureOpenAttr($activeTab, $activeSection, 'data', 'exploration') ?>>
                <?= disclosureSummary(t('settings.disc.exploration.title'), t('settings.disc.exploration.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.explorer') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.explore_view_cache') ?></label><input type="number" name="explore_view_cache_ttl" class="form-control" value="<?= settingValue('explore_view_cache_ttl', '20') ?>" min="0"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.explore_page_size') ?></label><input type="number" name="explore_page_size" class="form-control" value="<?= settingValue('explore_page_size', '200') ?>" min="10"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.explore_search_max') ?></label><input type="number" name="explore_search_max_results" class="form-control" value="<?= settingValue('explore_search_max_results', '200') ?>" min="10"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.explore_max_file') ?></label><input type="number" name="explore_max_file_size_mb" class="form-control" value="<?= settingValue('explore_max_file_size_mb', '5') ?>" min="1"></div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'data' ? 'active' : '' ?>" data-tab-panel="data">
            <details class="settings-disclosure" data-section="indexation" <?= disclosureOpenAttr($activeTab, $activeSection, 'data', 'indexation') ?>>
                <?= disclosureSummary(t('settings.disc.indexation.title'), t('settings.disc.indexation.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.search_index') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.index_warm_batch') ?></label><input type="number" name="search_index_warm_batch_per_run" class="form-control" value="<?= settingValue('search_index_warm_batch_per_run', '4') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.index_adhoc_days') ?></label><input type="number" name="search_index_adhoc_retention_days" class="form-control" value="<?= settingValue('search_index_adhoc_retention_days', '7') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.index_recent_snapshots') ?></label><input type="number" name="search_index_recent_snapshots" class="form-control" value="<?= settingValue('search_index_recent_snapshots', '1') ?>" min="1"></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.restic_caches') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.cache_snapshots') ?></label><input type="number" name="restic_snapshots_cache_ttl" class="form-control" value="<?= settingValue('restic_snapshots_cache_ttl', '60') ?>" min="0"></div>
                            <div class="form-group"><label class="form-label">ls</label><input type="number" name="restic_ls_cache_ttl" class="form-control" value="<?= settingValue('restic_ls_cache_ttl', '30') ?>" min="0"></div>
                            <div class="form-group"><label class="form-label">stats</label><input type="number" name="restic_stats_cache_ttl" class="form-control" value="<?= settingValue('restic_stats_cache_ttl', '300') ?>" min="0"></div>
                            <div class="form-group"><label class="form-label">search</label><input type="number" name="restic_search_cache_ttl" class="form-control" value="<?= settingValue('restic_search_cache_ttl', '60') ?>" min="0"></div>
                            <div class="form-group"><label class="form-label">tree</label><input type="number" name="restic_tree_cache_ttl" class="form-control" value="<?= settingValue('restic_tree_cache_ttl', '900') ?>" min="0"></div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'data' ? 'active' : '' ?>" data-tab-panel="data">
            <details class="settings-disclosure" data-section="performance" <?= disclosureOpenAttr($activeTab, $activeSection, 'data', 'performance') ?>>
                <?= disclosureSummary(t('settings.disc.performance.title'), t('settings.disc.performance.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.slow_thresholds') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.slow_http_ms') ?></label><input type="number" name="performance_slow_request_threshold_ms" class="form-control" value="<?= settingValue('performance_slow_request_threshold_ms', '800') ?>" min="50"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.slow_sql_ms') ?></label><input type="number" name="performance_slow_sql_threshold_ms" class="form-control" value="<?= settingValue('performance_slow_sql_threshold_ms', '150') ?>" min="10"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.slow_restic_ms') ?></label><input type="number" name="performance_slow_restic_threshold_ms" class="form-control" value="<?= settingValue('performance_slow_restic_threshold_ms', '300') ?>" min="10"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.slow_cmd_ms') ?></label><input type="number" name="performance_slow_command_threshold_ms" class="form-control" value="<?= settingValue('performance_slow_command_threshold_ms', '300') ?>" min="10"></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.health_perf') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.metrics_cache_s') ?></label><input type="number" name="performance_metrics_cache_ttl_seconds" class="form-control" value="<?= settingValue('performance_metrics_cache_ttl_seconds', '5') ?>" min="0"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.metrics_refresh_s') ?></label><input type="number" name="performance_metrics_refresh_interval_seconds" class="form-control" value="<?= settingValue('performance_metrics_refresh_interval_seconds', '15') ?>" min="5"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.recent_jobs_limit') ?></label><input type="number" name="performance_recent_jobs_limit" class="form-control" value="<?= settingValue('performance_recent_jobs_limit', '20') ?>" min="5"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.log_tail_lines') ?></label><input type="number" name="performance_log_tail_lines" class="form-control" value="<?= settingValue('performance_log_tail_lines', '20') ?>" min="5"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.repo_scan_limit') ?></label><input type="number" name="performance_repo_size_scan_limit" class="form-control" value="<?= settingValue('performance_repo_size_scan_limit', '8') ?>" min="1"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.repo_top_limit') ?></label><input type="number" name="performance_repo_size_top_limit" class="form-control" value="<?= settingValue('performance_repo_size_top_limit', '5') ?>" min="1"></div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'access' ? 'active' : '' ?>" data-tab-panel="access">
            <details class="settings-disclosure" data-section="webauth" <?= disclosureOpenAttr($activeTab, $activeSection, 'access', 'webauth') ?>>
                <?= disclosureSummary(t('settings.disc.webauth.title'), t('settings.disc.webauth.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.identity_totp') ?></div>
                        <div class="card-body settings-grid-two">
                            <div class="form-group"><label class="form-label">RP name WebAuthn</label><input type="text" name="webauthn_rp_name" class="form-control" value="<?= settingValue('webauthn_rp_name', AppConfig::webauthnRpName()) ?>"></div>
                            <div class="form-group"><label class="form-label">RP ID override</label><input type="text" name="webauthn_rp_id_override" class="form-control" value="<?= settingValue('webauthn_rp_id_override') ?>" placeholder="<?= h(t('settings.label.webauthn_rp_id_hint')) ?>"></div>
                            <div class="form-group" style="grid-column:1 / -1"><label class="form-label">Issuer TOTP</label><input type="text" name="totp_issuer" class="form-control" value="<?= settingValue('totp_issuer', AppConfig::totpIssuer()) ?>"></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.webauthn_behavior') ?></div>
                        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                            <div class="form-group"><label class="form-label"><?= t('settings.label.webauthn_reg_timeout') ?></label><input type="number" name="webauthn_registration_timeout_ms" class="form-control" value="<?= settingValue('webauthn_registration_timeout_ms', '60000') ?>" min="1000"></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.webauthn_auth_timeout') ?></label><input type="number" name="webauthn_auth_timeout_ms" class="form-control" value="<?= settingValue('webauthn_auth_timeout_ms', '60000') ?>" min="1000"></div>
                            <div class="form-group"><label class="form-label">User verification</label><?= renderSelectOptions('webauthn_user_verification', ['required' => 'required', 'preferred' => 'preferred', 'discouraged' => 'discouraged'], settingValue('webauthn_user_verification', 'preferred')) ?></div>
                            <div class="form-group"><label class="form-label">Resident key</label><?= renderSelectOptions('webauthn_resident_key', ['required' => 'required', 'preferred' => 'preferred', 'discouraged' => 'discouraged'], settingValue('webauthn_resident_key', 'preferred')) ?></div>
                            <div class="form-group"><label class="form-label">Attestation</label><?= renderSelectOptions('webauthn_attestation', ['none' => 'none', 'direct' => 'direct', 'indirect' => 'indirect', 'enterprise' => 'enterprise'], settingValue('webauthn_attestation', 'none')) ?></div>
                        </div>
                        <div class="card-body" style="padding-top:0;display:flex;flex-direction:column;gap:10px">
                            <label class="settings-toggle"><input type="checkbox" name="webauthn_require_resident_key" value="1" <?= settingCheck('webauthn_require_resident_key') ?>><span><?= t('settings.toggle.resident_key_required') ?></span></label>
                            <label class="settings-toggle"><input type="checkbox" name="webauthn_login_autostart" value="1" <?= settingCheck('webauthn_login_autostart') ?>><span><?= t('settings.toggle.webauthn_autostart') ?></span></label>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><?= t('settings.card.webauth_rate_limits') ?></div>
                        <div class="card-body">
                            <div class="table-wrap">
                            <table class="table">
                                <thead><tr><th>Endpoint</th><th>Hits</th><th><?= t('settings.label.rate_window_s') ?></th></tr></thead>
                                <tbody>
                                    <?php foreach ($webauthRateRows as [$label, $hitsKey, $windowKey]): ?>
                                    <tr>
                                        <td><?= h($label) ?></td>
                                        <td><input type="number" name="<?= h($hitsKey) ?>" class="form-control" value="<?= settingValue($hitsKey) ?>" min="1"></td>
                                        <td><input type="number" name="<?= h($windowKey) ?>" class="form-control" value="<?= settingValue($windowKey) ?>" min="1"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'integrations' ? 'active' : '' ?>" data-tab-panel="integrations">
            <details class="settings-disclosure" data-section="integrations" <?= disclosureOpenAttr($activeTab, $activeSection, 'integrations', 'integrations') ?>>
                <?= disclosureSummary(t('settings.disc.integrations.title'), t('settings.disc.integrations.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><span>Infisical</span><label class="settings-toggle"><?php if ($canManageInfisical): ?><input type="hidden" name="infisical_enabled_present" value="1"><?php endif; ?><input type="checkbox" name="infisical_enabled" value="1" <?= $infisicalFormValues['infisical_enabled'] === '1' ? 'checked' : '' ?> <?= $canManageInfisical ? '' : 'disabled' ?>><span><?= t('settings.channel.active') ?></span></label></div>
                        <div class="card-body">
                            <div class="settings-help" style="margin-bottom:14px"><?= t('settings.help.infisical_intro') ?></div>
                            <?php if (!$canManageInfisical): ?>
                                <div class="alert alert-warning" style="margin-bottom:14px"><?= t('settings.alert.infisical_admin_only') ?></div>
                            <?php endif; ?>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.infisical_url') ?></label><input type="text" name="infisical_url" class="form-control" value="<?= h($infisicalFormValues['infisical_url']) ?>" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.infisical_token') ?></label><input type="password" name="infisical_token" class="form-control" placeholder="<?= h(secretPlaceholder('infisical_token', t('settings.label.infisical_token_empty'))) ?>" autocomplete="new-password" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                            <div class="infisical-settings-grid">
                                <div class="form-group"><label class="form-label">Project ID</label><input type="text" name="infisical_project_id" class="form-control" value="<?= h($infisicalFormValues['infisical_project_id']) ?>" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.infisical_env') ?></label><input type="text" name="infisical_environment" class="form-control" value="<?= h($infisicalFormValues['infisical_environment']) ?>" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                            </div>
                            <div class="form-group"><label class="form-label"><?= t('settings.label.infisical_secret_path') ?></label><input type="text" name="infisical_secret_path" class="form-control" value="<?= h($infisicalFormValues['infisical_secret_path']) ?>" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                            <div class="infisical-settings-grid">
                                <div class="form-group"><label class="form-label"><?= t('settings.label.infisical_allowed_hosts') ?></label><input type="text" name="infisical_allowed_hosts" class="form-control" value="<?= h($infisicalFormValues['infisical_allowed_hosts']) ?>" placeholder="infisical, infisical.internal" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.infisical_allowed_port') ?></label><input type="number" name="infisical_allowed_port" class="form-control" value="<?= h($infisicalFormValues['infisical_allowed_port']) ?>" min="1" max="65535" placeholder="443" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                            </div>
                            <div class="infisical-settings-grid">
                                <div class="form-group"><label class="form-label"><?= t('settings.label.infisical_allowed_patterns') ?></label><input type="text" name="infisical_allowed_host_patterns" class="form-control" value="<?= h($infisicalFormValues['infisical_allowed_host_patterns']) ?>" placeholder="*.internal" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                                <div class="form-group"><label class="form-label"><?= t('settings.label.infisical_allowed_cidrs') ?></label><input type="text" name="infisical_allowed_cidrs" class="form-control" value="<?= h($infisicalFormValues['infisical_allowed_cidrs']) ?>" placeholder="172.18.0.10/32, fd00::/64" <?= $canManageInfisical ? '' : 'disabled' ?>></div>
                            </div>
                            <div class="settings-help" style="margin:-2px 0 12px"><?= t('settings.help.infisical_freeze') ?></div>
                            <div class="infisical-options-grid">
                                <label class="infisical-option-card">
                                    <?php if ($canManageInfisical): ?><input type="hidden" name="infisical_allow_http_present" value="1"><?php endif; ?>
                                    <input type="checkbox" name="infisical_allow_http" value="1" <?= $infisicalFormValues['infisical_allow_http'] === '1' ? 'checked' : '' ?> <?= $canManageInfisical ? '' : 'disabled' ?>>
                                    <span class="infisical-option-body">
                                        <span class="infisical-option-title"><?= t('settings.infisical.allow_http_title') ?></span>
                                        <span class="infisical-option-help"><?= t('settings.infisical.allow_http_help') ?></span>
                                    </span>
                                </label>
                                <label class="infisical-option-card">
                                    <input type="checkbox" name="confirm_infisical_change" value="1" <?= $canManageInfisical ? '' : 'disabled' ?>>
                                    <span class="infisical-option-body">
                                        <span class="infisical-option-title"><?= t('settings.infisical.confirm_title') ?></span>
                                        <span class="infisical-option-help"><?= t('settings.infisical.confirm_help') ?></span>
                                    </span>
                                </label>
                            </div>
                            <div class="infisical-actions">
                                <button type="button" class="btn btn-sm" onclick="testInfisical()" <?= $canManageInfisical ? '' : 'disabled' ?>><?= t('settings.btn.test_infisical') ?></button>
                                <span id="test-infisical-result" class="settings-inline-result"></span>
                            </div>
                            <?php if (!empty($infisicalHistory)): ?>
                                <div style="margin-top:18px">
                                    <div class="form-label" style="margin-bottom:8px"><?= t('settings.infisical.history_title') ?></div>
                                    <div class="table-wrap" style="overflow-x:auto">
                                        <table class="table">
                                            <thead>
                                                <tr><th>Date</th><th><?= t('settings.infisical.col_action') ?></th><th><?= t('settings.infisical.col_user') ?></th><th>URL</th><th><?= t('settings.infisical.col_validation') ?></th><th><?= t('settings.infisical.col_rollback') ?></th></tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($infisicalHistory as $historyRow): ?>
                                                <?php $validation = $historyRow['validation_result'] ?? []; ?>
                                                <tr>
                                                    <td style="font-size:12px"><?= h((string) ($historyRow['created_at'] ?? '')) ?></td>
                                                    <td style="font-size:12px"><?= h((string) ($historyRow['action'] ?? 'update')) ?></td>
                                                    <td style="font-size:12px"><?= h((string) (($historyRow['username'] ?? '') !== '' ? $historyRow['username'] : ($historyRow['changed_by'] ?? ''))) ?></td>
                                                    <td class="mono" style="font-size:11px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h((string) ($historyRow['new_url'] ?? '')) ?></td>
                                                    <td style="font-size:12px">
                                                        <?= h((string) ($validation['output'] ?? '')) ?>
                                                        <?php if (!empty($validation['http_status'])): ?>
                                                            <div style="color:var(--text2)">HTTP <?= (int) $validation['http_status'] ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button type="submit"
                                                                class="btn btn-sm"
                                                                name="restore_infisical_history_id"
                                                                value="<?= (int) $historyRow['id'] ?>"
                                                                data-infisical-restore-id="<?= (int) $historyRow['id'] ?>"
                                                                data-reauth-message="<?= h(t('settings.js.infisical_restore_reauth')) ?>"
                                                                data-confirm-message="<?= h(t('settings.js.infisical_restore_confirm')) ?>"
                                                                <?= $canManageInfisical ? '' : 'disabled' ?>>
                                                            <?= t('settings.btn.restore_infisical') ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'access' ? 'active' : '' ?>" data-tab-panel="access">
            <details class="settings-disclosure" data-section="roles" <?= disclosureOpenAttr($activeTab, $activeSection, 'access', 'roles') ?>>
                <?= disclosureSummary(t('settings.disc.roles.title'), t('settings.disc.roles.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header"><?= t('settings.card.roles_hierarchy') ?></div>
                        <div class="card-body">
                            <div class="settings-help" style="margin-bottom:16px"><?= t('settings.help.roles') ?></div>
                            <div id="roles-container" class="roles-container"><?php foreach (array_values($roleRows) as $index => $role): ?><?= renderRoleRow($role, $index) ?><?php endforeach; ?></div>
                            <div style="margin-top:16px"><button type="button" class="btn" onclick="addRoleRow()"><?= t('settings.btn.add_role') ?></button></div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="settings-panel <?= $activeTab === 'api' ? 'active' : '' ?>" data-tab-panel="api">
            <details class="settings-disclosure" data-section="api_public" <?= disclosureOpenAttr($activeTab, $activeSection, 'api', 'api_public') ?>>
                <?= disclosureSummary(t('settings.disc.api.title'), t('settings.disc.api.meta')) ?>
                <div class="settings-disclosure-body">
                    <div class="card">
                        <div class="card-header">
                            <span><?= t('settings.card.api_public') ?></span>
                            <label class="settings-toggle">
                                <input type="checkbox" name="api_enabled" value="1" <?= settingCheck('api_enabled') ?>>
                                <span><?= t('settings.toggle.api_enabled') ?></span>
                            </label>
                        </div>
                        <div class="card-body">
                            <div class="settings-help" style="margin-bottom:14px">
                                <?= t('settings.help.api_intro') ?>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                <div class="form-group">
                                    <label class="form-label"><?= t('settings.label.api_token_days') ?></label>
                                    <input type="number" name="api_default_token_lifetime_days" class="form-control"
                                        min="0" max="3650" value="<?= settingValue('api_default_token_lifetime_days', '90') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('settings.label.api_rate_limit_default') ?></label>
                                    <input type="number" name="api_default_rate_limit_per_minute" class="form-control"
                                        min="1" max="10000" value="<?= settingValue('api_default_rate_limit_per_minute', '120') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('settings.label.api_log_days') ?></label>
                                    <input type="number" name="api_log_retention_days" class="form-control"
                                        min="1" max="365" value="<?= settingValue('api_log_retention_days', '30') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('settings.label.api_idempotency_hours') ?></label>
                                    <input type="number" name="api_idempotency_retention_hours" class="form-control"
                                        min="1" max="720" value="<?= settingValue('api_idempotency_retention_hours', '48') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= t('settings.label.api_cors') ?></label>
                                <input type="text" name="api_cors_allowed_origins" class="form-control"
                                    placeholder="https://app.example.com, https://autre.example.com"
                                    value="<?= settingValue('api_cors_allowed_origins') ?>">
                                <div class="settings-help" style="margin-top:6px">
                                    <?= t('settings.help.api_cors') ?>
                                </div>
                            </div>
                            <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
                                <a href="<?= routePath('/api_tokens.php') ?>" class="btn btn-sm"><?= t('settings.btn.api_tokens') ?></a>
                                <a href="<?= routePath('/api_webhooks.php') ?>" class="btn btn-sm"><?= t('settings.btn.api_webhooks') ?></a>
                                <a href="/api/v1/docs" target="_blank" class="btn btn-sm"><?= t('settings.btn.api_docs') ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <div class="settings-submit"><button type="submit" class="btn btn-primary" style="padding:10px 24px"><?= t('settings.btn.submit_all') ?></button></div>
    </form>

    <form method="POST" id="test-email-form">
        <input type="hidden" name="action" value="test_email">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="active_tab" value="general">
        <input type="hidden" name="active_section" value="notifications">
    </form>

</div>

<script<?= cspNonceAttr() ?>>
function toggleTimezoneMode() {
    const customWrap = document.getElementById('timezone-custom-wrap');
    const customRadio = document.querySelector('input[name="interface_timezone_mode"][value="custom"]');
    const customInput = document.getElementById('timezone-custom-input');
    if (!customWrap || !customRadio) {
        return;
    }

    customWrap.style.display = customRadio.checked ? 'block' : 'none';
    if (customRadio.checked && customInput) {
        window.setTimeout(() => customInput.focus(), 0);
    }
}

const settingsTabs = Array.from(document.querySelectorAll('.settings-tab'));
const settingsPanels = Array.from(document.querySelectorAll('.settings-panel'));
const activeTabInput = document.getElementById('active-tab-input');
const activeSectionInput = document.getElementById('active-section-input');
const settingsDisclosures = Array.from(document.querySelectorAll('.settings-disclosure'));
const settingsSubtabs = document.getElementById('settings-subtabs');
const tabSections = <?= json_encode(settingsTabSections(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const sectionLabels = <?= json_encode(settingsSectionLabels(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const roleBadgeOptions = <?= json_encode(array_map(static fn(string $badge): array => ['value' => $badge, 'label' => ucfirst($badge)], AppConfig::allowedRoleBadges()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const timezoneCanonicalMap = <?= json_encode(array_reduce($timezoneIdentifiers, static function (array $carry, string $timezone): array {
    $carry[strtolower($timezone)] = $timezone;
    return $carry;
}, []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let roleRowIndex = <?= count($roleRows) ?>;

toggleTimezoneMode();

function getTimezoneInput() {
    return document.getElementById('timezone-custom-input');
}

function getTimezoneValidationMessage() {
    return document.getElementById('timezone-validation-message');
}

function normalizeTimezoneValue(value) {
    const trimmed = String(value || '').trim();
    if (!trimmed) {
        return '';
    }

    return timezoneCanonicalMap[trimmed.toLowerCase()] || '';
}

function setTimezoneValidationState(message, isValid) {
    const input = getTimezoneInput();
    const messageEl = getTimezoneValidationMessage();
    if (!input || !messageEl) {
        return;
    }

    input.setCustomValidity(isValid ? '' : message);
    input.style.borderColor = isValid ? '' : 'var(--red)';
    messageEl.textContent = message;
    messageEl.style.color = isValid ? 'var(--text2)' : 'var(--red)';
}

function refreshTimezoneChoiceButtons(selectedTimezone) {
    document.querySelectorAll('[data-timezone-choice]').forEach((button) => {
        button.classList.toggle('btn-primary', button.dataset.timezoneChoice === selectedTimezone);
    });
}

function validateTimezoneInput() {
    const customRadio = document.querySelector('input[name="interface_timezone_mode"][value="custom"]');
    const input = getTimezoneInput();
    if (!customRadio || !input) {
        return true;
    }

    if (!customRadio.checked) {
        setTimezoneValidationState('<?= h(t('settings.js.tz_hint')) ?>', true);
        return true;
    }

    const normalized = normalizeTimezoneValue(input.value);
    if (!normalized) {
        setTimezoneValidationState('<?= h(t('settings.js.tz_invalid')) ?>', false);
        refreshTimezoneChoiceButtons('');
        return false;
    }

    input.value = normalized;
    refreshTimezoneChoiceButtons(normalized);
    setTimezoneValidationState(`<?= h(t('settings.js.tz_valid_prefix')) ?>${normalized}.`, true);
    return true;
}

function applyTimezoneChoice(value) {
    const customRadio = document.querySelector('input[name="interface_timezone_mode"][value="custom"]');
    const input = getTimezoneInput();
    if (!customRadio || !input) {
        return;
    }

    customRadio.checked = true;
    toggleTimezoneMode();
    input.value = value;
    validateTimezoneInput();
}

function sectionsForTab(tab) {
    return Array.isArray(tabSections[tab]) ? tabSections[tab] : [];
}

function defaultSectionForTab(tab) {
    return sectionsForTab(tab)[0] || '';
}

function normalizeSection(tab, section) {
    return sectionsForTab(tab).includes(section) ? section : defaultSectionForTab(tab);
}

function renderSectionLinks(tab, section) {
    if (!settingsSubtabs) {
        return;
    }
    const normalizedSection = normalizeSection(tab, section);
    settingsSubtabs.innerHTML = sectionsForTab(tab).map((item) => {
        const isActive = item === normalizedSection ? ' active' : '';
        const label = sectionLabels[item] || item;
        const href = `/settings?tab=${encodeURIComponent(item)}`;
        return `<a href="${href}" class="settings-subtab${isActive}" data-section-link data-tab="${tab}" data-section="${item}">${label}</a>`;
    }).join('');
}

function updateSettingsUrl(tab, section) {
    if (!window.history?.replaceState) {
        return;
    }
    const nextUrl = new URL(window.location.href);
    nextUrl.searchParams.set('tab', section || tab);
    window.history.replaceState({}, '', nextUrl);
}

function syncDisclosureState(tab) {
    const section = normalizeSection(tab, activeSectionInput.value);
    activeSectionInput.value = section;
    settingsDisclosures.forEach((disclosure) => {
        const panel = disclosure.closest('[data-tab-panel]');
        if (!panel) {
            return;
        }
        disclosure.open = panel.dataset.tabPanel === tab && disclosure.dataset.section === section;
    });
}

function switchSettingsTab(tab, updateUrl = true) {
    settingsTabs.forEach((button) => button.classList.toggle('active', button.dataset.tab === tab));
    settingsPanels.forEach((panel) => panel.classList.toggle('active', panel.dataset.tabPanel === tab));
    activeTabInput.value = tab;
    syncDisclosureState(tab);
    renderSectionLinks(tab, activeSectionInput.value);
    if (updateUrl) {
        updateSettingsUrl(tab, activeSectionInput.value);
    }
}

function roleBadgeSelect(name) {
    return `<select name="${name}" class="form-control">${roleBadgeOptions.map((option) => `<option value="${option.value}">${option.label}</option>`).join('')}</select>`;
}

function addRoleRow() {
    const index = roleRowIndex++;
    const wrapper = document.createElement('div');
    wrapper.className = 'role-row';
    wrapper.dataset.system = '0';
    wrapper.innerHTML = `
        <div class="role-row-grid">
            <div class="form-group"><label class="form-label"><?= h(t('settings.js.role_key')) ?></label><input type="text" name="roles[${index}][key]" class="form-control" placeholder="auditor"></div>
            <div class="form-group"><label class="form-label"><?= h(t('settings.js.role_label')) ?></label><input type="text" name="roles[${index}][label]" class="form-control" placeholder="Auditor"></div>
            <div class="form-group"><label class="form-label"><?= h(t('settings.js.role_level')) ?></label><input type="number" name="roles[${index}][level]" class="form-control" value="25" min="1" max="999"></div>
            <div class="form-group"><label class="form-label"><?= h(t('settings.js.role_badge')) ?></label>${roleBadgeSelect(`roles[${index}][badge]`)}</div>
        </div>
        <div class="role-row-footer">
            <div class="form-group" style="flex:1;margin-bottom:0"><label class="form-label"><?= h(t('settings.js.role_description')) ?></label><input type="text" name="roles[${index}][description]" class="form-control"></div>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeRoleRow(this)"><?= h(t('settings.js.role_delete')) ?></button>
        </div>`;
    document.getElementById('roles-container').appendChild(wrapper);
}

function removeRoleRow(button) {
    const row = button.closest('.role-row');
    if (row) row.remove();
}

settingsTabs.forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        switchSettingsTab(link.dataset.tab);
    });
});
settingsSubtabs?.addEventListener('click', (event) => {
    const link = event.target.closest('[data-section-link]');
    if (!link) {
        return;
    }
    event.preventDefault();
    activeTabInput.value = link.dataset.tab;
    activeSectionInput.value = link.dataset.section;
    switchSettingsTab(link.dataset.tab);
});
settingsDisclosures.forEach((disclosure) => {
    disclosure.addEventListener('toggle', () => {
        if (!disclosure.open) {
            return;
        }
        const panel = disclosure.closest('[data-tab-panel]');
        if (!panel) {
            return;
        }
        const tab = panel.dataset.tabPanel;
        activeTabInput.value = tab;
        activeSectionInput.value = disclosure.dataset.section || defaultSectionForTab(tab);
        settingsDisclosures.forEach((otherDisclosure) => {
            if (otherDisclosure === disclosure) {
                return;
            }
            const otherPanel = otherDisclosure.closest('[data-tab-panel]');
            if (otherPanel && otherPanel.dataset.tabPanel === tab) {
                otherDisclosure.open = false;
            }
        });
        settingsTabs.forEach((button) => button.classList.toggle('active', button.dataset.tab === tab));
        renderSectionLinks(tab, activeSectionInput.value);
        updateSettingsUrl(tab, activeSectionInput.value);
    });
});
switchSettingsTab(activeTabInput.value, false);

const timezoneInput = getTimezoneInput();
if (timezoneInput) {
    timezoneInput.addEventListener('input', () => {
        const normalized = normalizeTimezoneValue(timezoneInput.value);
        if (!timezoneInput.value.trim()) {
            refreshTimezoneChoiceButtons('');
            setTimezoneValidationState('<?= h(t('settings.js.tz_hint')) ?>', true);
            return;
        }

        if (normalized) {
            refreshTimezoneChoiceButtons(normalized);
            setTimezoneValidationState(`<?= h(t('settings.js.tz_recognized_prefix')) ?>${normalized}.`, true);
            return;
        }

        refreshTimezoneChoiceButtons('');
        setTimezoneValidationState('<?= h(t('settings.js.tz_unknown')) ?>', false);
    });

    timezoneInput.addEventListener('blur', () => {
        validateTimezoneInput();
    });
}

const settingsForm = document.querySelector('.settings-form');
const infisicalInitialState = <?= json_encode([
    'infisical_enabled' => $currentInfisicalConfig['infisical_enabled'],
    'infisical_url' => $currentInfisicalConfig['infisical_url'],
    'infisical_project_id' => $currentInfisicalConfig['infisical_project_id'],
    'infisical_environment' => $currentInfisicalConfig['infisical_environment'],
    'infisical_secret_path' => $currentInfisicalConfig['infisical_secret_path'],
    'infisical_allowed_hosts' => $currentInfisicalConfig['infisical_allowed_hosts'],
    'infisical_allowed_host_patterns' => $currentInfisicalConfig['infisical_allowed_host_patterns'],
    'infisical_allowed_cidrs' => $currentInfisicalConfig['infisical_allowed_cidrs'],
    'infisical_allowed_port' => $currentInfisicalConfig['infisical_allowed_port'],
    'infisical_allow_http' => $currentInfisicalConfig['infisical_allow_http'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

function collectInfisicalDraft() {
    return {
        infisical_enabled: document.querySelector('input[name="infisical_enabled"]')?.checked ? '1' : '0',
        infisical_url: document.querySelector('input[name="infisical_url"]')?.value.trim() || '',
        infisical_project_id: document.querySelector('input[name="infisical_project_id"]')?.value.trim() || '',
        infisical_environment: document.querySelector('input[name="infisical_environment"]')?.value.trim() || '',
        infisical_secret_path: document.querySelector('input[name="infisical_secret_path"]')?.value.trim() || '',
        infisical_allowed_hosts: document.querySelector('input[name="infisical_allowed_hosts"]')?.value.trim() || '',
        infisical_allowed_host_patterns: document.querySelector('input[name="infisical_allowed_host_patterns"]')?.value.trim() || '',
        infisical_allowed_cidrs: document.querySelector('input[name="infisical_allowed_cidrs"]')?.value.trim() || '',
        infisical_allowed_port: document.querySelector('input[name="infisical_allowed_port"]')?.value.trim() || '',
        infisical_allow_http: document.querySelector('input[name="infisical_allow_http"]')?.checked ? '1' : '0',
        infisical_token: document.querySelector('input[name="infisical_token"]')?.value.trim() || '',
    };
}

function hasInfisicalProtectedChanges() {
    const draft = collectInfisicalDraft();
    const keys = [
        'infisical_enabled',
        'infisical_url',
        'infisical_project_id',
        'infisical_environment',
        'infisical_secret_path',
        'infisical_allowed_hosts',
        'infisical_allowed_host_patterns',
        'infisical_allowed_cidrs',
        'infisical_allowed_port',
        'infisical_allow_http',
    ];

    for (const key of keys) {
        if ((draft[key] || '') !== (infisicalInitialState[key] || '')) {
            return true;
        }
    }

    return draft.infisical_token !== '';
}

settingsForm?.addEventListener('submit', (event) => {
    const submitter = event.submitter;
    if (submitter?.dataset?.infisicalRestoreId) {
        if (submitter.dataset.reauthVerified === '1') {
            delete submitter.dataset.reauthVerified;
            return;
        }

        event.preventDefault();
        if (!window.confirm(submitter.dataset.confirmMessage || '<?= h(t('settings.js.infisical_restore_confirm')) ?>')) {
            return;
        }

        requireReauth(() => {
            submitter.dataset.reauthVerified = '1';
            settingsForm.requestSubmit(submitter);
        }, submitter.dataset.reauthMessage || '<?= h(t('settings.js.infisical_restore_reauth')) ?>');
        return;
    }

    if (!validateTimezoneInput()) {
        event.preventDefault();
        getTimezoneInput()?.focus();
        return;
    }

    if (!hasInfisicalProtectedChanges()) {
        if (settingsForm.dataset.infisicalReauthVerified === '1') {
            delete settingsForm.dataset.infisicalReauthVerified;
        }
        return;
    }

    if (settingsForm.dataset.infisicalReauthVerified === '1') {
        delete settingsForm.dataset.infisicalReauthVerified;
        return;
    }

    event.preventDefault();
    requireReauth(() => {
        settingsForm.dataset.infisicalReauthVerified = '1';
        settingsForm.requestSubmit(submitter);
    }, '<?= h(t('settings.js.infisical_change_reauth')) ?>');
});

validateTimezoneInput();

async function testInfisical() {
    const resultEl = document.getElementById('test-infisical-result');
    resultEl.textContent = '<?= h(t('settings.js.testing')) ?>';
    resultEl.style.color = 'var(--text2)';
    const res = await apiPost('/api/test_infisical.php', collectInfisicalDraft());
    resultEl.textContent = res.output || (res.success ? 'OK' : '<?= h(t('settings.js.failure')) ?>');
    resultEl.style.color = res.success ? 'var(--green)' : 'var(--red)';
}

async function testChannel(channel) {
    const resultEl = document.getElementById('test-' + channel + '-result');
    resultEl.textContent = '<?= h(t('settings.js.sending')) ?>';
    resultEl.style.color = 'var(--text2)';
    const res = await apiPost('/api/test_notification.php', { channel });
    resultEl.textContent = res.output || (res.success ? 'OK' : '<?= h(t('settings.js.failure')) ?>');
    resultEl.style.color = res.success ? 'var(--green)' : 'var(--red)';

    if (channel === 'web_push' && res.success) {
        if (!('Notification' in window)) {
            return;
        }

        if (Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                return;
            }
        }

        if (Notification.permission === 'granted') {
            const notification = new Notification('<?= h(t('settings.js.webpush_title')) ?>', {
                body: '<?= h(t('settings.js.webpush_body')) ?>',
                tag: 'fulgurite-webpush-test',
            });
            notification.onclick = () => {
                window.focus();
                window.location.href = '<?= h(routePath('/settings.php', ['tab' => 'notifications'])) ?>';
                notification.close();
            };
        }
    }
}

window.addRoleRow = addRoleRow;
window.removeRoleRow = removeRoleRow;
window.toggleTimezoneMode = toggleTimezoneMode;
window.applyTimezoneChoice = applyTimezoneChoice;
window.testInfisical = testInfisical;
window.testChannel = testChannel;
</script>

<?php include 'layout_bottom.php'; ?>
