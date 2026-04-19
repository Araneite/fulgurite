<?php

/**
 * ThemeRenderer — construit the contexte of rendering of page and delegue
 * the rendering of slots (sidebar, topbar, footer...) to the theme active. *
 * the context (navigation filtered by permissions, user, title...) is
 * computed by the core. the slots of themes receive only data
 * pre-validated: they can reorganize HTML but cannot
 * bypass permissions or access sensitive objects.
 */
class ThemeRenderer {

    /**
     * Construit the tableau of navigation principal en filtrant by
     * permissions. Each entry contains:
     * key, label, href, active, section, icon (svg inline)
     * and, optionnellement, group_key/group_label/group_icon for regrouper
     * certain links in of dropdowns side theme.
     */
    public static function buildNavigation(string $active): array {
        $items = [];
        $sectionOverview = t('nav.section.overview');
        $sectionOperations = t('nav.section.operations');
        $sectionConfig = t('nav.section.configuration');
        $sectionAdmin = t('nav.section.administration');
        $groupMonitoring = t('nav.group.monitoring');
        $groupJobs = t('nav.group.jobs');
        $groupInfrastructure = t('nav.group.infrastructure');
        $groupIntegrations = t('nav.group.integrations');
        $groupIcons = [
            'monitoring' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2 12.5h12"/><path d="M3.5 10l2.2-3 2 1.6 3-4.6 1.8 1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="5.7" cy="7" r="1" fill="currentColor" stroke="none"/><circle cx="7.7" cy="8.6" r="1" fill="currentColor" stroke="none"/><circle cx="10.7" cy="4" r="1" fill="currentColor" stroke="none"/></svg>',
            'jobs' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 5h6M5 8h6M5 11h3" stroke-linecap="round"/></svg>',
            'infrastructure' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="2" width="12" height="5" rx="1.2"/><rect x="2" y="9" width="12" height="5" rx="1.2"/><path d="M5 4.5h.01M5 11.5h.01" stroke-linecap="round"/></svg>',
            'integrations' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M6 4.5H4.5A2.5 2.5 0 0 0 2 7v2a2.5 2.5 0 0 0 2.5 2.5H6M10 4.5h1.5A2.5 2.5 0 0 1 14 7v2a2.5 2.5 0 0 1-2.5 2.5H10M6 8h4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];

        $items[] = self::navItem(
            'dashboard', t('nav.dashboard'), routePath('/index.php'), $active, $sectionOverview,
            '<svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>'
        );

        if (Auth::hasPermission('repos.view')) {
            $items[] = self::navItem('repos', t('nav.repos'), routePath('/repos.php'), $active, $sectionOperations,
                '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v2H2zm0 4h12v2H2zm0 4h12v2H2z"/></svg>');
        }
        $items[] = self::navItem('notifications', t('nav.notifications'), routePath('/notifications.php'), $active, $sectionOverview,
            '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M8 14a1.8 1.8 0 0 0 1.7-1H6.3A1.8 1.8 0 0 0 8 14Z" fill="currentColor" stroke="none"/><path d="M4 11V7a4 4 0 1 1 8 0v4l1.2 1.4c.3.3.1.6-.3.6H3.1c-.4 0-.6-.3-.3-.6L4 11Z"/></svg>',
            'monitoring', $groupMonitoring, $groupIcons['monitoring']);

        if (Auth::hasPermission('logs.view')) {
            $items[] = self::navItem('logs', t('nav.logs'), routePath('/logs.php'), $active, $sectionOverview,
                '<svg viewBox="0 0 16 16" fill="currentColor"><rect x="2" y="1" width="12" height="14" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M5 5h6M5 8h6M5 11h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>',
                'monitoring', $groupMonitoring, $groupIcons['monitoring']);
        }
        if (Auth::hasPermission('stats.view')) {
            $items[] = self::navItem('stats', t('nav.stats'), routePath('/stats.php'), $active, $sectionOverview,
                '<svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="8" width="3" height="7"/><rect x="6" y="5" width="3" height="10"/><rect x="11" y="2" width="3" height="13"/></svg>',
                'monitoring', $groupMonitoring, $groupIcons['monitoring']);
        }
        if (Auth::hasPermission('copy_jobs.manage')) {
            $items[] = self::navItem('copy_jobs', t('nav.copy_jobs'), routePath('/copy_jobs.php'), $active, $sectionOperations,
                '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M1 1h6v6H1zm8 0h6v6H9zM1 9h6v6H1zm8 0h6v6H9z" opacity=".4"/><path d="M4 4l4 4M4 12l4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
                'jobs', $groupJobs, $groupIcons['jobs']);
        }
        if (Auth::hasPermission('backup_jobs.manage')) {
            $items[] = self::navItem('backup_jobs', t('nav.backup_jobs'), routePath('/backup_jobs.php'), $active, $sectionOperations,
                '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v2H2zm0 3h12v2H2zm0 3h8v2H2z"/><circle cx="13" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M13 10.5V12l1 1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>',
                'jobs', $groupJobs, $groupIcons['jobs']);
            $items[] = self::navItem('backup_templates', t('nav.backup_templates'), routePath('/backup_templates.php'), $active, $sectionOperations,
                '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 5h6M5 8h6M5 11h4" stroke-linecap="round"/></svg>',
                'jobs', $groupJobs, $groupIcons['jobs']);
        }
        if (Auth::hasPermission('scheduler.manage')) {
            $items[] = self::navItem('scheduler', t('nav.scheduler'), routePath('/scheduler.php'), $active, $sectionOperations,
                '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="5.5" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M8 4.5V8l2.5 1.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.8 2.8l1.4-1.4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>',
                'jobs', $groupJobs, $groupIcons['jobs']);
        }
        if (Auth::canRestore()) {
            $items[] = self::navItem('restores', t('nav.restores'), routePath('/restores.php'), $active, $sectionOperations,
                '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>');
        }
        if (Auth::hasPermission('hosts.manage')) {
            $items[] = self::navItem('hosts', t('nav.hosts'), routePath('/hosts.php'), $active, $sectionConfig,
                '<svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="2" width="14" height="9" rx="1" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M5 11v3M11 11v3M3 14h10" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="8" cy="6.5" r="1.5"/><path d="M5.5 9.5a2.5 2.5 0 0 1 5 0" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>',
                'infrastructure', $groupInfrastructure, $groupIcons['infrastructure']);
        }

        // Themes : visible for all user authentifie. the page it-same        // handles the permissions (management vs simple submission of request).
        $items[] = self::navItem('themes', t('nav.themes'), routePath('/themes.php'), $active, $sectionConfig,
            '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="8" cy="8" r="6"/><path d="M8 2a6 6 0 0 0 0 12" fill="currentColor" stroke="none"/><circle cx="5" cy="6" r="1" fill="currentColor"/><circle cx="11" cy="6" r="1" fill="currentColor"/><circle cx="12" cy="10" r="1" fill="currentColor"/></svg>');

        $items[] = self::navItem('api_tokens', t('nav.api_tokens'), routePath('/api_tokens.php'), $active, $sectionConfig,
            '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="6" width="12" height="6" rx="1"/><path d="M5 6V4a3 3 0 0 1 6 0v2"/></svg>',
            'integrations', $groupIntegrations, $groupIcons['integrations']);

        // Section Administration
        $hasAdmin = Auth::hasPermission('users.manage') || Auth::hasPermission('sshkeys.manage')
            || Auth::hasPermission('ssh_host_key.approve')
            || Auth::hasPermission('scripts.manage')
            || Auth::hasPermission('settings.manage') || Auth::hasPermission('performance.view');

        if ($hasAdmin) {
            if (Auth::hasPermission('users.manage')) {
                $items[] = self::navItem('users', t('nav.users'), routePath('/users.php'), $active, $sectionAdmin,
                    '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="5" r="3.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>');
            }
            if (Auth::hasPermission('sshkeys.manage') || Auth::hasPermission('ssh_host_key.approve')) {
                $items[] = self::navItem('sshkeys', t('nav.sshkeys'), routePath('/sshkeys.php'), $active, $sectionConfig,
                    '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="6" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M9 8h5.5M12 6.5v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
                    'infrastructure', $groupInfrastructure, $groupIcons['infrastructure']);
            }
            if (Auth::hasPermission('scripts.manage')) {
                $items[] = self::navItem('scripts', t('nav.scripts'), routePath('/scripts.php'), $active, $sectionAdmin,
                    '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M4 3.5h8M4 6.5h8M4 9.5h5M3 13h10" stroke-linecap="round"/><path d="M11.5 10.5 13 12l-1.5 1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>');
            }
            if (Auth::hasPermission('settings.manage')) {
                $items[] = self::navItem('settings', t('nav.settings'), routePath('/settings.php'), $active, $sectionAdmin,
                    '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="3"/><path d="M8 1v2M8 13v2M1 8h2M13 8h2M3.22 3.22l1.42 1.42M11.36 11.36l1.42 1.42M3.22 12.78l1.42-1.42M11.36 4.64l1.42-1.42"/></svg>');
                $items[] = self::navItem('secret_logs', t('nav.secret_logs'), routePath('/secret_logs.php'), $active, $sectionAdmin,
                    '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="7" width="10" height="7" rx="1.5"/><path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2"/><path d="M6 10h4" stroke-linecap="round"/></svg>');
                $items[] = self::navItem('api_webhooks', t('nav.api_webhooks'), routePath('/api_webhooks.php'), $active, $sectionConfig,
                    '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M5 9a3 3 0 1 1 6 0M3 13l2.5-3M13 13l-2.5-3M5.5 13h5"/></svg>',
                    'integrations', $groupIntegrations, $groupIcons['integrations']);
            }
            if (Auth::hasPermission('performance.view')) {
                $items[] = self::navItem('performance', t('nav.performance'), routePath('/performance.php'), $active, $sectionOverview,
                    '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2 13h12"/><path d="M3 11l3-4 2 2 4-6 1 1"/><circle cx="6" cy="7" r="1" fill="currentColor" stroke="none"/><circle cx="8" cy="9" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="3" r="1" fill="currentColor" stroke="none"/></svg>',
                    'monitoring', $groupMonitoring, $groupIcons['monitoring']);
            }
        }

        return $items;
    }

    private static function navItem(
        string $key,
        string $label,
        string $href,
        string $active,
        string $section,
        string $icon,
        ?string $groupKey = null,
        ?string $groupLabel = null,
        ?string $groupIcon = null
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'href' => $href,
            'active' => $key === $active,
            'section' => $section,
            'icon' => $icon,
            'group_key' => $groupKey,
            'group_label' => $groupLabel,
            'group_icon' => $groupIcon,
        ];
    }

    /**
     * Builds full context passed to all slots.
     */
    public static function buildContext(array $page): array {
        $user = Auth::isLoggedIn() ? Auth::currentUser() : null;
        $themeId = ThemeManager::DEFAULT_THEME_ID;
        if ($user !== null) {
            // Auth::currentUser() returns only session data, which
            // does not contain preferred_theme. So we read the value from
            // the enregistrement complet (mis en cache by request).
            $record = Auth::currentUserRecord();
            $rawTheme = (string) ($record['preferred_theme'] ?? $user['preferred_theme'] ?? '');
            $themeId = ThemeManager::resolveThemeId($rawTheme);
            $user['preferred_theme'] = $themeId;
        }

        $active = (string) ($page['active'] ?? '');
        return [
            'title' => (string) ($page['title'] ?? AppConfig::appName()),
            'subtitle' => (string) ($page['subtitle'] ?? ''),
            'active' => $active,
            'actions' => (string) ($page['actions'] ?? ''),
            'flash' => $page['flash'] ?? null,
            'user' => $user,
            'theme_id' => $themeId,
            'app_name' => AppConfig::appName(),
            'app_logo_letter' => AppConfig::appLogoLetter(),
            'nav' => $user ? self::buildNavigation($active) : [],
        ];
    }

    /**
     * Rend a slot — uses the template of theme active s'il existe,
     * otherwise retombe on the template by default. the overrides PHP ne are
     * resolved only for trusted themes exposed by ThemeManager.
     */
    public static function renderSlot(string $slot, array $ctx): void {
        $themeId = (string) ($ctx['theme_id'] ?? ThemeManager::DEFAULT_THEME_ID);
        $path = ThemeManager::resolveSlotPath($slot, $themeId);
        self::includeIsolated($path, $ctx);
    }

    /**
     * Rend always the slot by default, same if the theme active the override.
     * Useful quand a theme wants wrap the slot by default to the instead of the rewrite :     *
     * <header class="mon-wrapper">
     * <?php ThemeRenderer::renderDefaultSlot('topbar', $ctx); ?>
     * </header>
     */
    public static function renderDefaultSlot(string $slot, array $ctx): void {
        $path = ThemeManager::defaultSlotPath($slot);
        self::includeIsolated($path, $ctx);
    }

    /**
     * Rend a "part" (partial reutilisable). Cherche of first in the theme
     * actif, otherwise retombe on the version by default. the parts by default
     * composent the slots by default (sidebar_logo, sidebar_nav, sidebar_user,
     * topbar_title, topbar_notifications).
     *
     * Exemple of usage in a slot custom :
     * <aside class="sidebar">
     * <?php ThemeRenderer::renderPart('sidebar_nav', $ctx); ?>
     * </aside>
     */
    public static function renderPart(string $part, array $ctx): void {
        $themeId = (string) ($ctx['theme_id'] ?? ThemeManager::DEFAULT_THEME_ID);
        $path = ThemeManager::resolvePartPath($part, $themeId);
        self::includeIsolated($path, $ctx);
    }

    /**
     * Tries of render a page fully overridden by the theme.
     * Returns true if the theme a taken the main (the core must then s'abstenir
     * of rendre son propre body), false otherwise.
     *
     * Usage in a page :
     * include 'layout_top.php';
     * $data = [...data collectees by the page... ];
     * if (!ThemeRenderer::renderPageOverride('dashboard', $data)) {
     * // markup by default
     * }
     * include 'layout_bottom.php';
     */
    public static function renderPageOverride(string $pageId, array $data = []): bool {
        $ctx = self::buildContext([
            'title' => $GLOBALS['title'] ?? null,
            'subtitle' => $GLOBALS['subtitle'] ?? null,
            'active' => $GLOBALS['active'] ?? '',
        ]);
        $themeId = $ctx['theme_id'];
        $path = ThemeManager::resolvePagePath($pageId, $themeId);
        if ($path === null) return false;

        $ctx['page_id'] = $pageId;
        $ctx['data'] = $data;
        self::includeIsolated($path, $ctx);
        return true;
    }

    /**
     * Returns true if the theme active provides a template of page for $pageId.
     */
    public static function hasPageTemplate(string $pageId, ?string $themeId = null): bool {
        $themeId = $themeId ?? self::activeThemeId();
        return ThemeManager::resolvePagePath($pageId, $themeId) !== null;
    }

    /** Resolves the id of theme active (user current or default). */
    public static function activeThemeId(): string {
        if (Auth::isLoggedIn()) {
            $record = Auth::currentUserRecord();
            return ThemeManager::resolveThemeId((string) ($record['preferred_theme'] ?? ''));
        }
        return ThemeManager::DEFAULT_THEME_ID;
    }

    /**
     * Inclut a file PHP in a portee isolee : only $ctx is accessible,     * not caller-scope variables nor $this.
     */
    private static function includeIsolated(string $path, array $ctx): void {
        if ($path === '' || !is_file($path)) return;
        (static function (string $__path, array $ctx): void {
            include $__path;
        })($path, $ctx);
    }
}
