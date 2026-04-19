---
title: "Fulgurite Documentation — Monitoring & Performance"
description: "Dashboard, statistics, logs, performance page, caches, indexes, queue, and runtime metrics."
lang: "en"
section: "user"
---
# Monitoring the health of the instance and repositories

The product provides several supervision views. They range from the highly operational dashboard summary to more technical information about SQLite, caches, the queue, the worker, and certain system metrics such as memory and I/O pressure.

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
- [09. Monitoring and Performance](09-monitoring-and-performance.md) (current page)
- [10. Notifications](10-notifications.md)
- [11. Hosts and SSH](11-hosts-and-ssh.md)
- [12. Security and Access](12-security-and-access.md)
- [13. Settings Reference](13-settings-reference.md)
- [14. API and Integrations](14-api-and-integrations.md)
- [15. Themes and Customization](15-themes-and-customization.md)

### Developer Documentation

- [01. Developer Overview](dev/01-index.md)
- [02. Variable Themes](dev/02-theme-variables.md)
- [03. Advanced Themes](dev/03-advanced-themes.md)
- [04. Context and Template Tags](dev/04-context-and-template-tags.md)
- [05. Packaging and Debugging](dev/05-packaging-and-debugging.md)


## Dashboard

The dashboard provides a summary view of the instance, with dynamic loading and periodic refresh according to the interface setting.

- Total snapshots and number of repositories in OK state or in alert.

- Runtime statuses of repositories (see details below).

- Snapshot evolution chart.

- Recent activity from the activity logs.

- Shortcuts to the most active areas of the application.

The runtime states of a repository, exposed on the dashboard and on the Repositories page, have a precise meaning:

| State | Meaning |
| --- | --- |
| `ok` | The repository is reachable, the last run completed successfully, and the age of the latest snapshot is below the configured threshold. |
| `warning` | The age of the latest snapshot exceeds the threshold: the repository is not necessarily broken, but it is getting stale. |
| `error` | The last run failed or the repository is unreachable (remote backend down, invalid credentials, missing path). |
| `no_snap` | The repository is correctly initialized but contains no snapshots yet. |
| `pending` | An analysis is in progress (init, scan, indexing): the final state has not yet been determined. |

The snapshot evolution chart uses daily granularity over the selected period: each point represents the cumulative state on the given day.

## Statistics

The Stats page exposes charts and aggregates over several time periods.

- Typical viewing periods: 7, 14, 30, or 90 days depending on the interface configuration.

- Snapshot evolution chart, with daily granularity over the selected period.

- Activity chart as a bar graph.

- Useful summary for tracking volumes and usage intensity.

## Activity logs and CSV export

The Logs page provides an actionable view of user and system actions, with filters and export.

- Filters by user, action, severity, IP, and date range.

- Direct CSV export of visible rows.

- Distinct severity badges for info, warning, and critical.

- A useful complement to the cron logs and the more technical worker logs.

## Performance page

The performance page is not simply a "server" page. It combines multiple application and system indicators.

- Dedicated worker status and its latest heartbeats.

- Internal queue summary and list of recent jobs.

- Statistics on the snapshot index and the tables associated with navigation and search.

- Information on SQLite databases, caches, archives, and repository sizes.

- System metrics such as memory, I/O pressure, and, depending on the context, PHP-FPM information.

## Indexes, caches, and slowness thresholds

The application already exposes several optimization structures and their settings.

- Separate navigation index and search index for snapshots.

- Restic caches dedicated to browsing, search, tree, snapshots, and statistics.

- Slowness thresholds for HTTP requests, SQL queries, Restic commands, and certain logged system commands.

- Refresh settings for the performance page and its metrics cache.

Slowness thresholds are configurable in **Settings → Data**. Beyond these thresholds, the corresponding operation is marked as slow in the activity logs and in runtime metrics, making it easy to quickly isolate contention points. The usual keys are:

- `performance_slow_request_threshold_ms`— threshold beyond which an application HTTP request is considered slow.

- `performance_slow_sql_threshold_ms`— threshold for SQL queries executed on SQLite.

- `performance_slow_restic_threshold_ms`— threshold for Restic commands (list, cat, stats, diff, etc.).

- `performance_slow_command_threshold_ms`— generic threshold for logged system commands (rsync, scp, rclone, hooks…).

The exact names may evolve across versions: when in doubt, refer to the [Settings Reference](13-settings-reference.md) page.

> The fact that the application exposes these structures in the UI is very useful for troubleshooting search or exploration problems that are not necessarily related to Restic itself, but rather to indexing or the cache layer.

## Disk Space Monitoring

The `DiskSpaceMonitor` service continuously monitors available space on local restic repository mount points and on the `/` root of each remote SSH host (probed via `df -Pk`).

Each probe returns one of the following states:

| State | Meaning |
| --- | --- |
| `ok` | Available space is above both configured thresholds. |
| `warning` | Available space is below the warning threshold but above the critical threshold. |
| `critical` | Available space is below the critical threshold: jobs are blocked (see below). |
| `error` | The probe failed: SSH host unreachable, command timeout, etc. |
| `unknown` | No data has been collected yet for this mount point. |

Warning and critical thresholds are configurable in **Settings → Monitoring**.

- **Forecast model**: with at least 30 days of data (minimum 3 data points per day), the system projects the number of days until the disk fills up. This projection is displayed alongside the current state.

- **Preflight checks**: before every backup, copy, or restore operation, disk space is verified. If the critical threshold is exceeded, the job is blocked with an explicit error before restic is even launched.

- **Manual probe**: from a repository page or the dashboard, a button allows triggering an immediate check without waiting for the next scheduler cycle.

Disk space monitoring data is visible across multiple UI surfaces: a widget on the dashboard, the Statistics tab, repository pages, and remote SSH host pages.

Disk space events (`warning`, `critical`, `restored`) can be routed through the `disk_space` notification profile.

## Secret Broker Health

The Performance page displays a dedicated card for the secret broker showing its real-time health state. Three states are possible: `ok`, `degraded`, `down`.

The **Analyze** button triggers a fresh check via `BrokerHealthAnalyzer`: connectivity, response time, backend type, and available secrets count are verified on demand, without waiting for the next automatic cycle.

- **Flapping protection**: grace periods and retry logic prevent transient errors from triggering false alarms.

- **HA configuration**: multiple endpoints can be declared in `FULGURITE_SECRET_BROKER_ENDPOINTS`. If one node is unreachable, the system automatically fails over to the next — each failed endpoint is bypassed for 10 seconds and then re-tested.

- **Notifications**: broker events (`degraded`, `failover`, `down`, `restored`) can be tracked via the `secret_broker` notification profile.

- **Secret logs**: the **Secret Logs** page exposes SecretStore accesses, failover events, node/cluster events and CSV export. It requires `settings.manage`.

> If the broker is not configured or is unavailable, the logs page may show a broker error and limit the visible data. The Performance page remains the quick diagnostic point for the current cluster state.

## Completing the supervision picture

The monitoring pages show the state of the instance. The next chapter covers reacting to that state: internal notifications, external channels, event profiles, and alerting policies.

[Previous Scheduler & Worker](08-scheduler-and-worker.md) [Next Notifications](10-notifications.md)

## Reading Path

- Previous: [08. Scheduler and Worker](08-scheduler-and-worker.md)
- Next: [10. Notifications](10-notifications.md)
