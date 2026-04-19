---
title: "Fulgurite Documentation — Concepts and Navigation"
description: "Understand the key concepts of Fulgurite: repository, snapshot, jobs, restores, scheduler, worker, scopes and navigation."
lang: "en"
section: "user"
---
# Concepts and Navigation

When you open the application for the first time, the breadth of features can make it feel like a very large toolset. In practice, everything is organized around a few stable objects and a simple lifecycle.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md)
- [03. Concepts and Navigation](03-concepts-and-navigation.md) (current page)
- [04. Restic Repositories](04-repositories.md)
- [05. Backup Jobs](05-backup-jobs.md)
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


## The fundamental objects of the application

Almost everything you work with in Fulgurite can be tied back to one of the building blocks below.

### Repository

A Restic backend known to the application, with its path, secret, alert threshold and notification policy.

### Backup job

A task that describes what to back up, to which repository and with which options: schedules, retention, hooks, retries, notifications and local or remote execution.

### Copy job

A replication task via `restic copy` to another destination, useful for offsite backups or a second target.

### Snapshot

A state saved in a repository. It can be explored, compared, downloaded, retagged, deleted or restored.

### Restore

A local or remote recovery operation, full or partial, tracked with status, destination, logs and author.

### Scheduler and worker

The scheduler decides what to run on each cron pass. The worker processes the internal queue and certain background tasks.

## Interface navigation map

The main menu can be read in three layers: daily management, backup operations and cross-cutting administration.

### Daily management

- **Dashboard**: global summary and recent activity.

- **Notifications**: in-app centre and tracking of visible alerts.

- **Stats / Logs / Performance**: diagnostic and audit views.

### Backup operations

- **Repositories**, **Backup Jobs** and **Copy Jobs**.

- **Explorer** to browse snapshots.

- **Restores** for the recovery history.

### Cross-cutting administration

- **Scheduler** for the central cron.

- **Hosts / SSH** for remote connectivity.

- **Users / Themes / Settings / API** for global administration.

## Lifecycle of a backup in Fulgurite

Understanding this cycle makes the product's architectural decisions much easier to read.

1. **Create a repository.** You register a Restic backend with its secret.

2. **Create a job.** You describe the sources, the target, the retention options, notifications and the retry policy.

3. **Trigger.** The job runs manually or via the central cron according to the configured scheduling time zone.

4. **Snapshot produced.** The repository grows, the application refreshes its indexes and its runtime status.

5. **Browse.** The user explores the snapshot, searches for a file or compares two versions.

6. **Restore.** The user restores all or part of the snapshot locally or remotely, and the operation is added to the history.

> A retention policy configured at the backup job level is applied **after a successful backup**. A repository-level retention can also be triggered from the explorer with a simulation mode.

## Roles, permissions and scopes

Fulgurite combines a role-based logic, a permission matrix and finer visibility scopes on repositories and hosts.

| Level | What it covers | Examples |
| --- | --- | --- |
| **Role** | The account's hierarchical position. System roles are compared by level. | viewer, operator, restore-operator, admin |
| **Permission** | Precise application rights used to display or authorize actions. | `repos.manage`, `restore.run`, `scheduler.manage` |
| **Repo / host scope** | Visibility restriction on a subset of repositories or hosts. | An operator can manage only certain repositories. |

> The `repo_scope` and `host_scope` scopes **silently** filter the visible resources. A scoped user sees neither in the interface nor via the API the repositories or hosts that fall outside their scope: the application does not display an "access denied" error — those resources are simply absent from the list returned to them. This is an intentional design, useful for isolating teams on a shared instance.

## Quick glossary

### Central cron

A single system cron entry that calls the scheduling engine. There is no separate cron line per job.

### Dedicated worker

A CLI process responsible for processing the internal queue, with a heartbeat, detection of stale workers and optional systemd management.

### Notification policy

Declarative configuration of the channels to use for a given event profile, with optional inheritance.

### Retry policy

The strategy for retrying a job on error, decided based on a classification of outputs and return codes.

## Move on to the practical side

The next chapter covers the first core building block of the product: the Restic repository, its secret, its access test, its alert threshold and its maintenance actions.

[Previous Getting Started](02-getting-started.md) [Next Restic Repositories](04-repositories.md)

## Reading Path

- Previous: [02. Getting Started](02-getting-started.md)
- Next: [04. Restic Repositories](04-repositories.md)
