---
title: "Fulgurite Documentation — Copy Jobs"
description: "Configure Fulgurite copy jobs to replicate a repository to another destination."
lang: "en"
section: "user"
---
# Replicating a repository to a second target

The logic differs from a backup job: here, you are not backing up source paths — you are copying the contents of a source repository to another Restic target. This is the preferred building block for duplication, offsite storage, or a second safety copy.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md)
- [03. Concepts and Navigation](03-concepts-and-navigation.md)
- [04. Restic Repositories](04-repositories.md)
- [05. Backup Jobs](05-backup-jobs.md)
- [06. Copy Jobs](06-copy-jobs.md) (current page)
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


## When to use a copy job

A copy job is suited to the following scenarios.

- You want a **second target** for the same source repository without redefining source paths.

- You want an **offsite replica** or a different storage backend.

- You already have snapshots in a primary repository and want to copy them to another destination without re-running the original backup.

## What the form lets you configure

| Field | Purpose | Note |
| --- | --- | --- |
| **Name** | Identifies the copy task. | Choose a name that describes the destination or the recovery objective. |
| **Source repository** | A Restic repository already known to the application. | The copy job does not create the source repository — it consumes it. |
| **Destination** | Target path or backend. | Can be a standard path or an `rclone:...` backend. |
| **Description** | Business or technical context. | Useful for distinguishing a local copy, a cloud copy, or a disaster recovery copy. |
| **Scheduling / notifications / retry** | Same general logic as for backup jobs. | The copy job has its own independent scheduling and alert configuration. |

## Supported destinations and password management

The copy job handles both a standard destination and one managed via rclone.

### Standard destination

- You provide a destination repository path.

- The password for this destination can be stored in a local file or resolved via Infisical.

- The job manages its own secret, independently from the source repository's secret.

### `rclone:...` destination

- The remote storage credentials are handled by the server's rclone configuration.

- No SSH key is required in Fulgurite for this mode.

- The Restic password for the destination is still required.

The `dest_password_source` field can take two values: `file`(the password is stored in a local file managed by Fulgurite) or `infisical`(the password is resolved dynamically at each execution). In the latter case, the `dest_infisical_secret_name` field corresponds to the path or name of the secret to resolve at run time.

> As with repositories and certain backup jobs, if you choose Infisical and the instance is not configured or the secret cannot be found, the job registration is refused.

> If Infisical resolution fails at run time (secret not found, expired token, unreachable instance), the copy job is marked as failed and a notification is sent according to its notification policy.

## Scheduling, manual launch, and logs

The copy job follows the same operational philosophy as a backup job: it can be launched manually or selected by the central cron according to its schedule.

- The table shows the source, the destination, the schedule summary, the notification policy, and the retry summary.

- The last run retains its status, date, and output.

- Executions feed `cron_log` with `job_type = 'copy'`.

- The **Run now** button in the cron panel can also be used to force a global cycle and observe the scheduler's behavior.

As with backup jobs, Fulgurite distinguishes two notification mechanisms that can coexist: the legacy field `notify_on_failure`(a simple boolean, retained for compatibility) and the field `notification_policy`(a JSON structure precisely describing channels, events, and thresholds). The JSON policy takes precedence as soon as it is set; the legacy flag is only consulted when no structured policy is defined.

> A copy job executes `restic copy`— it replicates snapshots from the source to the destination, but it does **not run `forget` or `prune`**. Retention on the destination repository is not managed by the copy job. To apply a retention policy on the destination, configure a dedicated backup job pointing to that repository, or trigger a maintenance task manually. This is intentional: a copy job is a *replication-only*operation — retention decisions are decoupled.

## Differences from a backup job

The two modules look similar visually, but serve different purposes.

| Aspect | Backup job | Copy job |
| --- | --- | --- |
| **Source** | File or directory paths to back up. | An existing Restic repository. |
| **Target** | Primary repository receiving the snapshots. | Another repository receiving a copy of the source repository. |
| **Advanced options** | Tags, exclusions, hooks, retention, remote host. | Destination, target secret, scheduling, notifications, and retry. |
| **Typical use** | Producing snapshots. | Duplicating or moving the value of those snapshots to another target. |

## What about retrieving data?

The next chapter details the snapshot explorer, downloads, comparisons, repository retention policies, and full or partial restores.

[Previous Backup Jobs](05-backup-jobs.md) [Next Explorer & Restores](07-explorer-and-restores.md)

## Reading Path

- Previous: [05. Backup Jobs](05-backup-jobs.md)
- Next: [07. Explorer and Restores](07-explorer-and-restores.md)
