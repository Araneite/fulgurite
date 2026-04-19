---
title: "Fulgurite Documentation — Backup Jobs"
description: "Configure Fulgurite backup jobs: sources, tags, exclusions, scheduling, retention, hooks, retries, and remote execution."
lang: "en"
section: "user"
---
# Backup Jobs

A backup job links source paths to a target repository. It can be purely local or execute via an SSH host, and can include retention policies, hooks, secret variables, a retry policy, and a tailored notification policy.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md)
- [03. Concepts and Navigation](03-concepts-and-navigation.md)
- [04. Restic Repositories](04-repositories.md)
- [05. Backup Jobs](05-backup-jobs.md) (current page)
- [06. Copy Jobs](06-copy-jobs.md)
- [07. Explorer and Restores](07-explorer-and-restores.md)
- [08. Scheduler and Worker](08-scheduler-and-worker.md)
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


## Backup job fields and options

The form covers most needs of an industrialized Restic backup.

| Section | What you configure | Key points |
| --- | --- | --- |
| **Identification** | Name, target repository, and description. | The job name is used everywhere: lists, logs, API, notifications. |
| **Sources** | Multi-line source paths, comma-separated tags, and multi-line exclusions. | Paths are absolute, one per line. Tags are used to filter or contextualize snapshots. |
| **Scheduling** | Enable/disable, execution time, and days. | The job remains manual until scheduling is active and the central cron is itself operational. |
| **Notifications** | Channel policy for job successes and failures. | Can inherit the global configuration or override it. See the note below on the coexistence of `notify_on_failure` and `notification_policy`. |
| **Retry** | Retry policy on failure. | Stored in the `retry_policy` column as JSON: maximum number of attempts, delay between each retry, and optional rules per error type. |
| **Last run status** | `last_status` and `last_run_at` columns. | These fields are updated on each execution and are used to display the last run status directly in the job list in the interface. |

> **Two notification systems coexist on a job.** The legacy field `notify_on_failure` is a simple boolean "notify on failure" inherited from the first version. The newer `notification_policy` system is a per-job JSON object that precisely describes channels, events (success, failure, warning), and overrides relative to the global policy. Both columns can be present in the database; **the JSON policy takes precedence** as soon as it is defined, and the boolean acts as a safety net for jobs that have not yet been migrated.

## When to use a remote host

The same screen handles backups launched from the Fulgurite server and those executed via SSH on another machine.

### Local mode

- The job runs on the server hosting Fulgurite.

- Source paths are seen as-is by this server.

- The repository path is the one accessible locally.

### Remote mode

- You associate the job with a **host_id**.

- The **remote_repo_path** field lets you provide the repository path as seen from that host.

- **hostname_override** forces the `--hostname` parameter to keep snapshots consistent.

> A host can also carry an optional sudo password. This is useful when the paths to back up require privilege escalation on the remote machine.

## Quick backup templates

The **Backup Templates** page and the **Quick Backup** wizard share the same template manager. They prefill a future backup job without hiding the final form.

- **Built-in templates**: shipped by the code for common profiles such as Linux server, web, MySQL or PostgreSQL. They are not edited directly.

- **Custom templates**: stored in the database and reusable in the quick wizard with the `custom:` prefix.

- **Duplication**: a customizable template can be created from an existing template, then adjusted without changing the source template.

- **Prefilled fields**: source paths (`source_paths`), excludes, tags, schedule, job name, remote repository path and retention (`keep_last/daily/weekly/monthly/yearly`, prune).

- **Permission**: template management uses `backup_jobs.manage`, just like creating and editing backup jobs.

> In the Quick Backup wizard, choosing a template does not run anything automatically: it initializes the flow values, then the user confirms the created job.

## Retention built into the job

The job can embed its own retention policy, applied after a successful backup.

- **Explicit activation** of the retention block.

- Counters: **keep_last**, **keep_daily**, **keep_weekly**, **keep_monthly**, and **keep_yearly**.

- **prune** option to purge data made obsolete by the forget operation.

- If no rule is set, retention does nothing.

- If retention fails after a successful backup, the backup is still considered successful but a warning is recorded in the log.

## Hooks and approved scripts

Hooks allow you to wrap a backup execution with pre- or post-processing scripts. Hooks are based on the instance's **Approved Scripts** catalog — it is not possible to enter arbitrary shell commands directly in a job.

### Pre-hook / Post-hook

- The **Pre-hook** and **Post-hook** fields each reference the **ID of an approved script** defined in Administration > Scripts.

- **Pre-hook** runs before the backup — useful for freezing a database, generating an export, temporarily stopping a process, or acquiring a lock.

- **Post-hook** runs after the backup — useful for cleanup, restarting a service, or sending an external signal.

- Execution can be **local**(web server user) or **remote**(SSH on the job's target host).

### Approved script mini-language

- An approved script is composed of **one instruction per line**— no shell syntax (no `;`, `& &`, `|`, `>`, backticks, etc.).

- Allowed instructions: `echo`, `curl`, `mkdir`, `cp`, `mv`, `rm`, `chmod`, `chown`, `sleep`, `set`, `export`, and `restic` calls.

- Banned instructions: `eval`, `exec`, `source`, `.`, `bash`, `sh`, and all shell operators.

- Scripts are created and managed in **Administration > Scripts**.

### Variable injection in scripts

- Use the `{{ENV_VAR}}` syntax to inject job context variables into script lines.

- To reference a secret from the broker, use `{{SECRET_my_key}}` directly in the script line. This is the recommended approach.

**Available context variables:**

| Variable | Description |
| --- | --- |
| `BACKUP_JOB_ID` | ID of the backup job. |
| `BACKUP_JOB_NAME` | Name of the backup job. |
| `SNAPSHOT_ID` | ID of the created or targeted snapshot (available in post-hook). |
| `REPO_ID` | ID of the repository. |
| `REPO_NAME` | Name of the repository. |
| `HOST_ID` | ID of the SSH host (if remote execution). |
| `HOST_NAME` | Hostname of the SSH host (if remote execution). |
| `EXIT_CODE` | Exit code of the backup operation (available in post-hook). |
| `RUN_ID` | Unique run identifier for the current job execution. |

**Approved script constraints:**

- Maximum size: **16,384 bytes**.

- Maximum lines: **64**.

- Maximum name length: **120 characters**.

- Execution scope: `local`(on the web server), `remote`(via SSH on the target host), or `both`.

### Hook environment (compatibility)

- The `hook_env` field is a JSON object `{"VAR_NAME": "value"}` that injects additional environment variables at execution time.

- This field is retained for compatibility. The preferred approach is now to use `{{SECRET_key}}` placeholders directly in the approved script lines, without going through `hook_env`.

> Approved scripts can be shared across multiple jobs. Editing a script in the catalog immediately updates the behavior of all jobs that reference it.

## Launching jobs, job list, and logs

The job list already provides a great deal of operational information before anything is launched.

- The table shows the repository, the host used, the first source path, and the number of additional paths.

- The scheduled or manual mode is visible at a glance, with the days and time if the job is active.

- The `last_status` and `last_run_at` columns are read directly from the job row to display the status and timestamp of the last run in the list.

- A manual launch opens a real-time log viewer.

- Each execution also feeds `cron_log` with `job_type = 'backup'`.

## Retry policy and error evaluation

The execution engine does not simply "retry X times." It relies on error classification to determine whether to retry, wait, or give up. The configuration itself lives in the job's `retry_policy` column as JSON: this is what sets the maximum number of attempts and the delay between each retry.

- The retry policy is resolved at run time.

- Each attempt produces a classification readable in the log.

- The number of retries and the delay before each retry depend on the decision calculated from the policy JSON.

- The scheduler also avoids triggering the same job twice within the same scheduling time window.

> The job can be scheduled, but it will never run on its own if the central cron is not installed or if its execution is failing. This is one of the first things to check when a job "is not running."

## Going further

If your goal is to replicate a repository to another target rather than back up source paths, continue with the chapter on copy jobs.

[Previous Restic Repositories](04-repositories.md) [Next Copy Jobs](06-copy-jobs.md)

## Reading Path

- Previous: [04. Restic Repositories](04-repositories.md)
- Next: [06. Copy Jobs](06-copy-jobs.md)
