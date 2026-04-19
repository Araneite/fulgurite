---
title: "Fulgurite Documentation — Functional Settings Reference"
description: "Functional reference for the main Fulgurite settings: interface, security, backup, scheduler, exploration, performance, integrations and API."
lang: "en"
section: "user"
---
# Functional reference for main settings

The Settings page is organized into functional tabs. This reference groups settings that are exposed or structurally important for operations: interface, security, backup defaults, the central cron, the worker, exploration, indexing, external integrations, roles and the public API. It does not claim to list every internal key defined by `AppConfig::defaultSettings()`.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md)
- [03. Concepts and Navigation](03-concepts-and-navigation.md)
- [04. Restic Repositories](04-repositories.md)
- [05. Backup Jobs](05-backup-jobs.md)
- [06. Copy Jobs](06-copy-jobs.md)
- [07. Explorer and Restores](07-explorer-and-restores.md)
- [08. Scheduler and Worker](08-scheduler-and-worker.md)
- [09. Monitoring and Performance](09-monitoring-and-performance.md)
- [10. Notifications](10-notifications.md)
- [11. Hosts and SSH](11-hosts-and-ssh.md)
- [12. Security and Access](12-security-and-access.md)
- [13. Settings Reference](13-settings-reference.md) (current page)
- [14. API and Integrations](14-api-and-integrations.md)
- [15. Themes and Customization](15-themes-and-customization.md)

### Developer Documentation

- [01. Developer Overview](dev/01-index.md)
- [02. Variable Themes](dev/02-theme-variables.md)
- [03. Advanced Themes](dev/03-advanced-themes.md)
- [04. Context and Template Tags](dev/04-context-and-template-tags.md)
- [05. Packaging and Debugging](dev/05-packaging-and-debugging.md)


## Interface and global notifications

- Application name, subtitle, letter/logo and login tagline.

- Timezone: either the server timezone or a custom one.

- Dashboard refresh interval.

- Default statistics period.

- Mail and SMTP configuration.

| Setting | Default value | Purpose |
| --- | --- | --- |
| `interface_timezone` | `server` | Timezone used for display and scheduling, or the server timezone when the value remains `server`. |
| `interface_dashboard_refresh_seconds` | 60 seconds | Auto-refresh interval for the home page. |
| `interface_stats_default_period_days` | 7 days | Default period used in charts. |

| Tab | Subsections in UI | Purpose |
| --- | --- | --- |
| **General** | Interface, Notifications | Branding, timezone, refresh, SMTP and global channels. |
| **Access** | Security, WebAuth, Audit, Roles | Sessions, 2FA, passkeys, log retention and RBAC hierarchy. |
| **Backup** | Backup, Restore | Job defaults and restore behaviors. |
| **Automation** | Scheduler, Worker | Central cron, global tasks and background processing. |
| **Data** | Exploration, Indexing, Performance | Caches, pagination, search, indexes and slow-query thresholds. |
| **Integrations / API** | Integrations, Public API | External secrets, third-party channels, CORS, tokens and API limits. |

## Security, WebAuth, audit and roles

- Absolute session lifetime, inactivity timeout, warning delay and database sync frequency.

- Strict session fingerprinting.

- Maximum re-authentication age and pending second-factor TTL.

- Maximum number of sessions per user.

- Enforced 2FA for administrators and login notifications.

- RP name, RP ID, timeouts, user verification, resident keys and attestation for WebAuthn.

- Audit retention for activity, restores, cron, logins, rate limits, queue and archives.

- Editing of the role hierarchy and role badges.

| Setting | Default value | Purpose |
| --- | --- | --- |
| `session_absolute_lifetime_minutes` | 480 minutes | Absolute session lifetime. |
| `reauth_max_age_seconds` | 300 seconds | Maximum age before a sensitive action requires re-entry of a password or factor. |
| `session_strict_fingerprint` | Enabled | Invalidates the session when the strict fingerprint no longer matches. |
| `max_sessions_per_user` | 5 | Maximum number of simultaneous sessions per account. |
| `app_notifications_retention_days` | 7 days | Retention period for internal notifications. |

## Backup, restore and default behaviors

- Global repository alert threshold.

- Backup job scheduling defaults: enabled or not, time, days, failure notification.

- Retention defaults: enable, `keep_last/daily/weekly/monthly/yearly` and prune.

- Global reference retry policy.

- Default paths for local, remote and partial restores.

- Page size for restore history.

## Disk Space Monitoring

The **DiskSpaceMonitor** service continuously monitors available disk space on the mountpoints involved in your backups. It runs as a global scheduled task and periodically probes the configured volumes.

- **What is monitored:** local Restic repository mountpoints, and the root `/` of each remote SSH host.

- **Remote probing:** performed via the SSH command `df -Pk` executed on the target host.

- **Data stored:** point-in-time history in the `disk_space_checks` table; latest known state in `disk_space_runtime_status`.

- **Forecast model:** uses 30 days of history (requires at least 3 daily data points) to project the number of days until the disk is full.

- **Preflight checks:** before each job (backup, copy, restore), disk space is checked — if the critical threshold is exceeded, the job is blocked with an explicit error.

- **Severity levels:** `ok`/`warning`/`critical`— thresholds are configurable as usage percentages. `error` indicates a probe failure itself (SSH host unreachable, command error). `unknown` indicates no data has been collected yet.

- **Manual probe:** can be triggered from a repository or host page, or via the `probe_disk_space.php` script.

- **UI surfaces:** dashboard widget, stats page, individual repository and host pages.

| Setting | Default value | Purpose |
| --- | --- | --- |
| `disk_monitoring_enabled` | Enabled | Globally enables or disables the monitoring service. |
| `disk_local_warning_percent` | 80% | Local usage percentage at which the status changes to `warning`. |
| `disk_local_critical_percent` | 90% | Local usage percentage at which the status changes to `critical` and jobs are blocked. |
| `disk_remote_warning_percent` | 80% | Remote usage percentage at which the status changes to `warning`. |
| `disk_remote_critical_percent` | 90% | Remote usage percentage at which the status changes to `critical`. |
| `disk_preflight_enabled` | Enabled | Enables disk-space checks before jobs. |
| `disk_monitor_history_retention_days` | 30 days | Retention period for probe history. |

> The forecast model requires at least 3 daily data points over 30 days to be reliable. Below this threshold, the forecast is displayed as unavailable.

> HA broker health is also exposed as notifications — see the `secret_broker` notification profile.

## Scheduler, central cron and worker

- Central cron is CLI-only (legacy HTTP entry `cron.php?token=...` is disabled).

- Execution modes (`manual`, `diagnostic`, `quick`) controlled by `FULGURITE_CRON_MODE`.

- Weekly report, integrity check and vacuum maintenance with days, times and notifications.

- Default worker name, sleep interval, jobs per loop, stale minutes and heartbeat stale seconds.

| Setting | Default value | Purpose |
| --- | --- | --- |
| `worker_stale_minutes` | 30 minutes | Delay after which an inactive worker is considered dead and can be restarted. |
| `worker_heartbeat_stale_seconds` | 20 seconds | Heartbeat tolerance before an alert is triggered. |
| `worker_limit` | 3 | Maximum number of jobs processed per loop iteration. |

### Reconfiguration wizard (Resetup)

The **Reconfigure system** button in the Automation tab opens the reconfiguration wizard. It lets you change the PHP-FPM user, file permissions and worker configuration without reinstalling the application.

- **7-step flow:** preflight → user/group selection → permission validation → sudo password (if needed) → apply → audit trail → completion.

- **Sudo handling:** if a sudo password is required, it is held in session only for the duration of the wizard and is never stored. Each sudo command is individually validated (defense in depth).

- **Audit trail:** all actions are logged with a timestamp, the command executed and the actor.

- **Docker detection:** if the application is running inside a Docker container, systemd-related steps are automatically skipped.

- **No-sudo fallback:** in environments without sudo, the wizard outputs the manual commands to run directly on the server.

- **Session lock:** only one reconfiguration session can be active at a time. Navigating away from the wizard resets its state.

- **Rate limiting:** maximum 3 reconfiguration attempts per hour.

> The reconfiguration wizard requires the administrator role. Actions applied through it are not reversible via the interface — the audit trail records everything that was changed.

## Internal data, caches and thresholds

- Explorer view cache TTL and exploration page size.

- Maximum number of search results and maximum explorable file size.

- Warm index batch size, adhoc index retention and number of recently indexed snapshots.

- Restic cache TTLs: snapshots, ls, stats, search and tree.

- Slow-query thresholds for HTTP, SQL, Restic and logged commands.

- Auto-refresh and cache settings for the performance page.

| Setting | Default value | Purpose |
| --- | --- | --- |
| `explore_max_file_size_mb` | 5 MB | Maximum size of a file explorable from the interface. |
| `explore_view_cache_ttl` | 20 seconds | Cache lifetime for explorer views. |
| `explore_search_max_results` | 200 | Maximum number of results returned by a search. |
| `performance_slow_request_threshold_ms` | 800 ms | Threshold above which an HTTP request is logged as slow. |

## External integrations and public API

- Discord, Slack, Telegram, ntfy, webhook, Teams, Gotify, in-app and web push.

- Internal notification retention.

- `secret://`-based secret handling with agent provider by default and encrypted local fallback.

- Infisical configuration: URL, project, environment and secrets path.

- API enable/disable, default token lifetime, rate limit, API log retention, idempotency retention and allowed CORS origins.

- Rate limits per endpoint families, including restores, scheduler, worker, explore, search and WebAuthn.

| Setting | Default value | Purpose |
| --- | --- | --- |
| `api_enabled` | Deployment-specific | Globally enables the public API. |
| `api_default_rate_limit_per_minute` | 120 | Default rate limit applied per minute. |
| `api_log_retention_days` | 30 days | Retention period for API call logs. |
| `app_notifications_retention_days` | 7 days | Internal notification retention in the database. |

> Values marked "See `src/AppConfig.php`" may change between versions. Consult that file to confirm the exact value applied on your deployment, and use the Settings page to override them.

## Last chapter

The final chapter details the API v1 surface, tokens, scopes, exposed routes, signed webhooks and the major external integrations of the application.

[Previous Themes & Customization](15-themes-and-customization.md) [Next API & Integrations](14-api-and-integrations.md)

## Reading Path

- Previous: [12. Security and Access](12-security-and-access.md)
- Next: [14. API and Integrations](14-api-and-integrations.md)
