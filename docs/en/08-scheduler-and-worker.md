---
title: "Fulgurite Documentation — Scheduler & Worker"
description: "Understand the central cron, global tasks, the dedicated worker, and Fulgurite's internal job queue."
lang: "en"
section: "user"
---
# The central cron and the internal queue worker

The Scheduler page presents Fulgurite's central engine. This is where you install the single cron entry, control global tasks, and observe upcoming runs. The Performance page completes this view with management of the dedicated worker and the job queue.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md)
- [03. Concepts and Navigation](03-concepts-and-navigation.md)
- [04. Restic Repositories](04-repositories.md)
- [05. Backup Jobs](05-backup-jobs.md)
- [06. Copy Jobs](06-copy-jobs.md)
- [07. Explorer and Restores](07-explorer-and-restores.md)
- [08. Scheduler and Worker](08-scheduler-and-worker.md) (current page)
- [09. Monitoring and Performance](09-monitoring-and-performance.md)
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


## The central cron

The product relies on a single cron entry, not one cron line per job. This architecture simplifies maintenance and keeps control within the application.

- The page displays the cron engine status.

- It provides a cron line or an installation command ready to copy.

- It may offer enable or disable actions if the environment supports it.

- A **Run now** button lets you trigger a cycle on demand.

- `public/cron.php` is **CLI-only**: the legacy HTTP entry `cron.php?token=...` is disabled.

- For a quick CLI diagnostic run: `FULGURITE_CRON_MODE=quick php public/cron.php`.

To prevent concurrent executions (two overlapping cron triggers, or a manual re-run during an active cycle), the central cron relies on the *lock file*exposed by the scheduler: `sys_get_temp_dir()/fulgurite-cron.lock` in practice, depending on the environment's temporary directory. It is acquired via `flock`. As long as this lock is held, a new call to the central cron stops immediately without launching anything: only the already-running cycle continues.

> The code explicitly distinguishes between the scheduler's time zone and the server's time zone. This prevents scheduling at a time intended as "local" while executing in a different temporal context.

## Global tasks controlled by the scheduler

In addition to backup jobs and copy jobs, the central cron drives several global application tasks. They run inside the cron cycle just like regular jobs: same locks, same logging, same notification circuit.

| Task | Identifier | Purpose | What you configure |
| --- | --- | --- | --- |
| **Weekly report** | `weekly_report` | Produce a periodic summary view of activity or status. | Enable/disable, day, time, and notification policy. |
| **Integrity check** | `integrity_check` | Verify the integrity of repositories. | Enable/disable, execution days, time, and notifications. |
| **Vacuum maintenance** | `maintenance_vacuum` | Maintain SQLite databases and certain internal structures. | Enable/disable, days, time, and notifications. |
| **Retention cleanup** | `retention_cleanup` | Apply retention policies to internal histories (logs, notifications, runs). | Enable/disable, frequency, and execution window. |

## How scheduled jobs are chosen

Enabled backup jobs and copy jobs are filtered by the scheduler according to their scheduled days and times, then deduplicated to avoid running the same slot twice.

- The calculation is based on the scheduler's time zone, not just the server's time.

- A job is not re-triggered within the same **minute window** as its last run: the scheduler compares the current moment with the `last_run_at` value truncated to the minute. This ensures that a cron cycle triggered multiple times within the same minute (manually, or via a misconfigured external cron) does not re-launch the job.

- The Scheduler page shows summaries of backup jobs and copy jobs currently scheduled.

## The dedicated worker

The dedicated worker is visible from the Performance page. It can be started, stopped, restarted, or forced to immediately process the queue.

- Default worker name.

- `sleep` interval between loop iterations.

- Number of jobs processed per loop.

- Systemd status if the worker is supervised as a service.

- Equivalent CLI command: `php public/worker.php --name=... --sleep=... --limit=...`.

Fulgurite uses two distinct notions of "stale" to detect a failing worker:

- **`worker_stale_minutes`**: inactivity duration beyond which the worker is considered dead (no measurable activity at all, regardless of its status). This is the verdict "the process no longer exists".

- **`worker_heartbeat_stale_seconds`**: a shorter threshold applying only to the last emitted heartbeat. A worker that stops publishing its heartbeat is flagged as suspect even before its long inactivity window is reached.

When a worker is marked as stale, the `enqueued` jobs it held are released and reassigned to the next worker that picks them up at the start of its loop: no operation is lost, it is simply resumed by another process.

## The job queue and its role

The Performance page also exposes a view of the internal queue. It helps you understand whether tasks are accumulating, failing, or remaining pending.

- Summary view: number of jobs, maximum pending priority, next availability.

- List of recent jobs with status, priority, retry count, and last error message.

- Typical statuses: `queued`, `running`, `completed`, `failed`, `dead_letter`.

## What to check when "nothing is running"

Scheduling problems usually come from a few recurring causes.

1. **Check that the central cron is installed and healthy.**

2. **Check the scheduler time zone.** A job may be "correctly configured" but set for the wrong time slot according to the scheduler.

3. **Check the job's last run.** The per-minute deduplication can give the impression of a non-execution if you test within the same window as the last trigger.

4. **Check the `sys_get_temp_dir()/fulgurite-cron.lock` lock.** Its exact directory depends on the environment. An orphaned lock left by a brutally killed process can block all subsequent cycles.

5. **Check the worker and the queue.** Some background operations may be waiting for processing that never comes.

6. **Review the cron log and the activity logs.**

## Continuously monitoring the instance

The next chapter covers the dashboard, statistics, logs, the performance page, and the health indicators exposed to the operator.

[Previous Explorer & Restores](07-explorer-and-restores.md) [Next Monitoring & Performance](09-monitoring-and-performance.md)

## Reading Path

- Previous: [07. Explorer and Restores](07-explorer-and-restores.md)
- Next: [09. Monitoring and Performance](09-monitoring-and-performance.md)
