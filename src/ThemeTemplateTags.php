<?php

/**
 * Template tags — "rui_*" helper functions exposed to advanced themes.
 *
 * Inspired by WordPress template tags: they encapsulate access to core data
 * while respecting current user permissions.
 * A theme can call them freely from slots, parts, or pages
 * without directly touching internal classes.
 * All these functions:
 * - verify permissions and return an empty array when denied
 * - are READ-only (no side effects)
 * - always return native PHP types (array / string / int / null)
 */

// ─── Identity / context ──────────────────────────────────────────────────────

function rui_current_user(): ?array {
    return Auth::isLoggedIn() ? Auth::currentUser() : null;
}

function rui_can(string $permission): bool {
    return Auth::hasPermission($permission);
}

function rui_can_restore(): bool {
    return Auth::canRestore();
}

function rui_app_name(): string {
    return AppConfig::appName();
}

function rui_app_logo_letter(): string {
    return AppConfig::appLogoLetter();
}

function rui_theme_id(): string {
    return ThemeRenderer::activeThemeId();
}

// ─── Navigation / rendering ──────────────────────────────────────────────────────

function rui_nav(string $active = ''): array {
    if (!Auth::isLoggedIn()) return [];
    return ThemeRenderer::buildNavigation($active);
}

function rui_route(string $path, array $query = []): string {
    return routePath($path, $query);
}

function rui_escape(string $str): string {
    return h($str);
}

/** Rend a part (theme or default). */
function rui_part(string $part, array $ctx): void {
    ThemeRenderer::renderPart($part, $ctx);
}

/** Renders a slot (theme or default). */
function rui_slot(string $slot, array $ctx): void {
    ThemeRenderer::renderSlot($slot, $ctx);
}

/** Forces rendering of the default slot (ignores theme override). */
function rui_default_slot(string $slot, array $ctx): void {
    ThemeRenderer::renderDefaultSlot($slot, $ctx);
}

// ─── business data (read single, with permission gating) ──────────────────

/**
 * List of accessible restic repositories (respects repos.view).
 * @return array<int,array<string,mixed>>
 */
function rui_list_repos(): array {
    if (!Auth::hasPermission('repos.view')) return [];
    return RepoManager::getAll();
}

function rui_get_repo(int $id): ?array {
    if (!Auth::hasPermission('repos.view')) return null;
    return RepoManager::getById($id);
}

/** Cached dashboard statuses (ok / warning / error / no_snap per repository). */
function rui_repo_statuses(): array {
    if (!Auth::hasPermission('repos.view')) return [];
    return RepoStatusService::getCachedStatuses();
}

/**
 * Quick aggregate: total snapshots, OK repositories, and alerts.
 * @return array{snapshots:int,ok:int,alerts:int,total:int,last_update:?string}
 */
function rui_dashboard_summary(): array {
    $summary = ['snapshots' => 0, 'ok' => 0, 'alerts' => 0, 'total' => 0, 'last_update' => null];
    if (!Auth::hasPermission('repos.view')) return $summary;

    $statuses = RepoStatusService::getCachedStatuses();
    $summary['total'] = count($statuses);
    foreach ($statuses as $s) {
        $summary['snapshots'] += (int) ($s['count'] ?? 0);
        if (($s['status'] ?? '') === 'ok') {
            $summary['ok']++;
        } elseif (in_array($s['status'] ?? '', ['warning', 'error', 'no_snap'], true)) {
            $summary['alerts']++;
        }
    }
    $summary['last_update'] = RepoStatusService::latestUpdate();
    return $summary;
}

/** Liste of backup jobs (respecte backup_jobs.manage). */
function rui_list_backup_jobs(): array {
    if (!Auth::hasPermission('backup_jobs.manage')) return [];
    return BackupJobManager::getAll();
}

/** Liste of jobs of copie (respecte copy_jobs.manage). */
function rui_list_copy_jobs(): array {
    if (!Auth::hasPermission('copy_jobs.manage')) return [];
    return CopyJobManager::getAll();
}

/** Liste of hotes (respecte hosts.manage). */
function rui_list_hosts(): array {
    if (!Auth::hasPermission('hosts.manage')) return [];
    return HostManager::getAll();
}

/** List of users (respects users.manage). */
function rui_list_users(): array {
    if (!Auth::hasPermission('users.manage')) return [];
    return UserManager::getAll();
}

/**
 * In-app notifications for the current user.
 * @return array<int,array<string,mixed>>
 */
function rui_list_notifications(int $limit = 10, bool $unreadOnly = false): array {
    if (!Auth::isLoggedIn()) return [];
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) return [];
    return AppNotificationManager::listForUser($userId, $limit, 0, $unreadOnly);
}

// ─── Helpers generaux ────────────────────────────────────────────────────────

function rui_format_bytes(int $bytes): string {
    return formatBytes($bytes);
}

function rui_format_date(string $date): string {
    return formatDate($date);
}
