---
title: "Fulgurite Documentation — Overview"
description: "Complete public documentation for Fulgurite: installation, repositories, jobs, snapshots, restores, security, API, themes and administration."
lang: "en"
section: "user"
---
# Understand the full scope
of Fulgurite before using it

## Navigation

### User Documentation

- [01. Overview](01-index.md) (current page)
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
- [13. Settings Reference](13-settings-reference.md)
- [14. API and Integrations](14-api-and-integrations.md)
- [15. Themes and Customization](15-themes-and-customization.md)

### Developer Documentation

- [01. Developer Overview](dev/01-index.md)
- [02. Variable Themes](dev/02-theme-variables.md)
- [03. Advanced Themes](dev/03-advanced-themes.md)
- [04. Context and Template Tags](dev/04-context-and-template-tags.md)
- [05. Packaging and Debugging](dev/05-packaging-and-debugging.md)


Strike. Save. Restore.

Fulgurite is a source-available web interface for managing Restic repositories, automating backups and copies, exploring snapshots, launching restores, managing users, monitoring instance health and integrating the tool into your infrastructure.

License PolyForm Noncommercial 1.0.0 Source-available, self-hosted, with no mandatory cloud dependency. The root LICENSE file controls. Documented sections 15 From installation to the API, the entire functional surface is covered. 3 Supported databases: SQLite, MySQL/MariaDB, PostgreSQL 10 Built-in notification channels, from email to browser push 4 System roles: viewer, operator, restore-operator, admin

## Documentation structure

Organized into 3 logical groups to match user profiles: deployment, day-to-day operations and administration.

Get Started · 01–03

### Installation & Concepts

Prerequisites, wizard, initial configuration and the tool's mental model.

Setup Stable Operations · 04–10

### Repositories, Jobs & Monitoring

Restic repositories, backup jobs, copy jobs, explorer, scheduler and notifications.

Ops Stable Administration · 11–15

### Security & Integrations

SSH hosts, users, roles, 2FA, themes, settings and the public API.

Admin Stable Single cron entry

### Scheduler architecture

A single cron line drives all jobs, copies and global tasks.

Architecture Must read Repository Job Snapshot Restore

## Recommended paths

The best entry point depends on your context. You don't need to read everything in order.

I'm deploying a new instance Getting Started → Repositories → Backup Jobs → Scheduler 02→04→05→08 I'm taking over an existing instance Concepts → Explorer → Notifications → Monitoring 03→07→10→09 I manage access and security Users & Security → Settings Reference 12→14 I'm integrating Fulgurite into my infrastructure API & Integrations → Notifications → Webhooks 15→10

## Functional architecture

## Current functional scope

The project is not limited to a Restic viewing interface. It already exposes a coherent set of operational, administrative and integration functions.

### Storage and data

- **Restic repositories** with secrets stored in files or via Infisical.

- **Snapshot exploration** with browsing, search, diff and downloads.

- **Retention policies** applied at the job or repository level.

### Execution and automation

- **Backup jobs**— local or remote — with hooks, tags, exclusions and a retry policy.

- **Copy jobs** for replication to a second target, including `rclone:...` destinations.

- **Central cron**, global tasks and a dedicated worker for the internal queue.

### Security and administration

- **Users, roles, permissions and scopes** on repositories and hosts.

- **TOTP, WebAuthn, sessions and re-auth** for sensitive actions.

- **Themes, API, webhooks and observability** to adapt and integrate the instance.

> One key idea runs throughout the entire documentation: Fulgurite relies on a **single cron entry**. There is not one cron line per job. The central cron decides which backups, copies and global tasks should run.

## Recommended paths based on your needs

You don't need to read everything in order. The best entry point depends on the context in which you come to Fulgurite.

1. **I'm deploying a new instance.** Start with [Getting Started](02-getting-started.md), then read [Restic Repositories](04-repositories.md), [Backup Jobs](05-backup-jobs.md) and [Scheduler & Worker](08-scheduler-and-worker.md).

2. **I need to operate an existing instance.** Begin with [Concepts and Navigation](03-concepts-and-navigation.md), then check [Explorer & Restores](07-explorer-and-restores.md), [Notifications](10-notifications.md) and [Monitoring, Logs & Performance](09-monitoring-and-performance.md).

3. **I manage access or security.** Read [Users & Security](12-security-and-access.md), then complete it with [Settings Reference](13-settings-reference.md).

4. **I want to integrate Fulgurite into my infrastructure.** Go to [API & Integrations](14-api-and-integrations.md), then come back to [Notifications](10-notifications.md) for channels and delivery policies.

## Documentation map

Each page is designed to be readable on its own, but the whole follows a logical progression from installation to advanced operations.

| Page | What you will find | When to read it |
| --- | --- | --- |
| **Getting Started** | Prerequisites, installation wizard, database setup, creating the first admin account and initial configuration. | Before the first launch or when picking up an existing project. |
| **Concepts and Navigation** | Fulgurite's mental model, the relationship between repositories, snapshots, jobs, restores, the scheduler and access scopes. | When the interface feels rich or when onboarding a new user. |
| **Repositories / Jobs / Explorer** | The core functional building blocks for backing up, replicating and retrieving data. | For any backup or restore operation. |
| **Security / Settings / API** | Access, roles, 2FA, passkeys, policies, public API, signed webhooks and external integrations. | To industrialize the tool or open access to multiple teams. |

## Functional architecture at a glance

The product can be understood as a simple processing chain: you declare repositories, attach tasks, produce snapshots, explore or restore them, then oversee everything with notifications, roles and integrations.

### Main business flow

- **1. Repository**: declare a Restic backend with its secret and alert policy.

- **2. Job**: add a backup job or copy job with scheduling, notifications, retry and retention.

- **3. Execution**: manual trigger, central cron or worker depending on the context.

- **4. Snapshot**: content becomes browsable from the explorer and indexable for search.

- **5. Restore**: local or remote restore, full or partial, with history and logs.

### Cross-cutting administration flow

- **Access**: users, invitations, roles, permissions, scopes, sessions and 2FA.

- **Monitoring**: dashboard, notifications, activity logs, cron logs, performance page and job queue.

- **Customization**: themes, branding, user preferences and start page.

- **Integrations**: SMTP, chat ops, web push, Infisical, REST API and outgoing webhooks.

> The most natural next step if you are just getting started is [Getting Started](02-getting-started.md). If you want to understand the application's language before configuring anything, go to [Concepts and Navigation](03-concepts-and-navigation.md) instead.

## Next step

Open the installation chapter now if you are deploying a new instance, or the concepts chapter if you want to familiarize yourself with the tool's model first.

[Next Getting Started](02-getting-started.md)

## Reading Path

- Next: [02. Getting Started](02-getting-started.md)
