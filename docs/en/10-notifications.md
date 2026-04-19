---
title: "Fulgurite Documentation — Notifications"
description: "Notification channels, internal notification center, browser push, event profiles and delivery policies in Fulgurite."
lang: "en"
section: "user"
---
# Configure notification channels and policies

Fulgurite's notification system goes beyond simple email delivery. It can send messages to multiple external channels, store internal per-user notifications, produce browser push notifications, and apply policies by event type.

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
- [10. Notifications](10-notifications.md) (current page)
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


## Supported notification channels

The currently available integrations cover most common operational use cases. The full list of supported channels is as follows:

- **Email / SMTP**— classic delivery via an SMTP server configured in Settings.

- **Discord**— incoming Discord webhook.

- **Slack**— incoming Slack webhook.

- **Telegram**— Telegram bot with token and chat ID.

- **ntfy**— publish to an ntfy topic (self-hosted or public).

- **Generic Webhook**— JSON POST to a free-form URL (to integrate a SIEM, an on-call platform, or a custom tool).

- **Microsoft Teams**— incoming Teams webhook.

- **Gotify**— self-hosted Gotify server.

- **In-app (bell)**— internal notification center, accessible via the bell icon in the interface.

- **Browser Web Push**— native browser notifications for open sessions.

## Event profiles and delivery policies

Notifications are not simply "enabled" or "disabled." They are structured by profile and by event, then resolved according to rules defined globally or locally.

- Each repository, backup job, copy job, or global task can have its own policy.

- Policies can inherit from the global configuration and then add or remove specific channels.

- Distinct profiles allow for differentiated alerting needs per job, per repository, per connection, or for a weekly report.

The effective resolution of a policy follows a strict **priority order**, from most specific to most general:

1. **Job policy**(backup job, copy job, global task): if a policy is defined at the job level, it takes precedence.

2. **Parent repository policy**: if the job has no policy of its own, Fulgurite falls back to the relevant repository.

3. **Global policy**: in the absence of a more local policy, the instance's global configuration applies.

At each of these three levels, a channel can be **explicitly excluded** to locally disable delivery that would otherwise be inherited from higher in the chain. This makes it possible, for example, to enable Slack globally for all alerts but remove it from a particularly noisy repository, or conversely to re-enable it only for a critical job.

## Internal notifications and browser push

Notifications do not only go out to external services. They can also live within the application and be targeted at eligible users.

- Database storage of notifications linked to specific users.

- Optional filtering by unread status.

- Mark a notification as read, or mark all at once.

- Delete a single notification or delete all already-read notifications.

- Browser delivery option for open sessions via the web push channel.

Internal notifications are automatically purged according to the `app_notifications_retention_days` setting, which defines the maximum lifetime of a notification stored in the database. The default value can be consulted in Settings: beyond this window, entries are deleted by the retention cleanup task, whether they have been read or not.

## Already-covered events

The codebase already handles several event families, enabling fairly granular delivery. The explicit event profiles are:

| Profile | Covers |
| --- | --- |
| `repo` | Repository events: stale repository, access error, missing snapshot, unreachable repository. |
| `backup_job` | Backup job success and failure, including retried runs. |
| `copy_job` | Copy job success and failure. |
| `weekly_report` | Weekly report delivery. |
| `integrity_check` | Scheduled integrity check success and failure. |
| `maintenance_vacuum` | Internal database vacuum maintenance success and failure. |
| `login` | User login events (success, new device, etc.). |
| `security` | Security alerts: repeated failures, password change, TOTP / WebAuthn activation, suspicious IP. |
| `theme_request` | Theme requests submitted by users. |
| `disk_space` | Disk space monitoring: warning threshold crossed, critical state, recovery to normal. |
| `secret_broker` | HA broker events: one or more nodes degraded, a node became unreachable, failover to a backup endpoint, full cluster recovery, entire cluster unavailable (all endpoints failed). Active only when using `HaBrokerSecretProvider` with multiple endpoints. |

## Throttling repeated notifications

To prevent notification spam during persistent incidents, the system applies a 24-hour deduplication window per profile + event combination. If an alert of the same type was already sent within the last 24 hours, it is silently suppressed.

> Deduplication is calculated per profile and per event, not globally. A `disk_space.warning` alert and a `repo.error` alert are tracked independently.

The following events are **exempt from throttling** and are always sent regardless of when the last notification was dispatched:

- `security`— security alerts (highest priority, never suppressed).

- `login`— user login events.

- `weekly_report`— weekly report (emitted once per week by design).

- `backup_job.success` and `copy_job.success`— per-run success confirmations.

Practical example: if disk space remains critical for 3 consecutive days, only one `disk_space.critical` notification will be sent per 24-hour period, not an alert on every monitoring task pass.

## Testing channels and delivering cleanly

The application already exposes useful test points before the first real incident occurs.

- Notification test from a repository or certain business screens.

- SMTP and generic webhook validation via Settings.

- Using internal notifications to verify targeting logic without leaving the browser.

> The notification **Generic Webhook** should not be confused with **signed API webhooks**. The former is used to broadcast alerts. The latter is used to emit HMAC-signed application events.

## Continue to remote administration

The next section covers hosts and SSH keys, which are essential for remote backups, connectivity tests, and certain restores outside the Fulgurite server.

[Previous Monitoring & Performance](09-monitoring-and-performance.md) [Next Hosts & SSH Keys](11-hosts-and-ssh.md)

## Reading Path

- Previous: [09. Monitoring and Performance](09-monitoring-and-performance.md)
- Next: [11. Hosts and SSH](11-hosts-and-ssh.md)
