---
title: "Fulgurite Documentation — Getting Started"
description: "Install Fulgurite, understand the wizard, choose your database and run your first backup."
lang: "en"
section: "user"
---
# Getting Started

Setting up Fulgurite requires neither a build pipeline nor heavy dependencies. That said, it deserves a minimum of method: checking prerequisites, choosing the right database, generating the appropriate web server configuration, creating the initial administrator and preparing the directories the application needs.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md) (current page)
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


## Prerequisites checked by the application

The wizard starts with an environment check. This step quickly tells you whether the server is in a deployable state.

| Check | Why it matters | What the wizard verifies |
| --- | --- | --- |
| **PHP version** | The project targets a modern runtime and recent server-side APIs. | PHP 8.2 or higher, with the extensions required by the product core. |
| **PDO and drivers** | The database can be SQLite, MySQL/MariaDB or PostgreSQL. | `pdo_sqlite`, `pdo_mysql` and `pdo_pgsql` depending on the chosen engine. The `pdo_sqlite` extension is **strictly required** in all cases, even if you choose MySQL or PostgreSQL as the primary database — Fulgurite uses it for its internal search database and certain runtime operations. |
| **Optional functions** | Some features depend on optional extensions. | `gd` and `zip` are optional. However, `zip` becomes required if you want to import custom themes in zip format from the administration panel. |
| **Write permissions** | The application must be able to store its configuration and runtime data. | Write access to `config/` (runtime configuration) and `data/` (SQLite database, logs, secrets, passwords, SSH keys, cache, themes). |

## One-shot bootstrap token unlock

Access to `public/setup.php` is no longer open by default. The setup wizard is gated by `SetupGuard` and requires a one-shot bootstrap token generated on the server.

```text
php scripts/setup-bootstrap.php create --ttl=30
```

- The token is generated from CLI and then entered on the setup unlock screen.

- The bootstrap payload is stored temporarily in `data/setup-bootstrap.json`.

- Setup opens a short installation session that is refreshed while you progress.

- At finalization, the bootstrap token is consumed and removed automatically.

> This mechanism avoids exposing an installation wizard publicly without proof of server control.

## The 6 wizard steps

The installation wizard follows a concrete order. Understanding it helps you know what to prepare in advance and what will be automated for you.

1. **Prerequisites.** Environment check for the PHP version, useful extensions and directory permissions.

2. **Database.** Driver selection and connection test.

3. **Web server.** Apache, Nginx or LiteSpeed detection and generation of an appropriate configuration block.

4. **Administrator.** Entry of the first admin account, with password and basic contact details.

5. **Application.** Initial settings such as the application name and time zone.

6. **Installation.** Directory creation, writing `config/database.php`, schema initialization, creation of the first admin account and marking the instance as installed.

## Choosing the right database

The code supports three engines. The right choice depends on volume, operational habits and the expected level of sharing.

### SQLite

- The simplest option to deploy.

- Main file: `data/fulgurite.db`.

- Separate search database: `data/fulgurite-search.db`.

- Setup already enables WAL mode and a wait timeout on locks.

### MySQL / MariaDB / PostgreSQL

- Better suited if your internal standard already relies on a centralized DBMS.

- The wizard tests connectivity and credentials before proceeding.

- The application schema will be initialized via the project's database layer.

- Even in this mode, the `pdo_sqlite` PHP extension remains essential.

## Web server configuration generated by the wizard

The setup generates useful configuration blocks for Apache, Nginx or LiteSpeed, pointing to `/public` with examples for log and asset cache settings.

```text
Minimum expected structure after installation
config/database.php
data/fulgurite.db
data/fulgurite-search.db
data/passwords/
data/ssh_keys/
data/cache/
data/themes/
data/.installed
```

## Files and directories created automatically

The final phase of the wizard prepares the foundations of the instance.

- **Writing the DB config** to `config/database.php`.

- **Creating runtime directories**: `data/passwords`, `data/ssh_keys`, `data/cache` and `data/themes`.

- **Initializing the application schema** via the database layer.

- **Creating the first admin user**.

- **Applying initial settings** such as the application name and time zone.

- **Marking the instance as installed** using `data/.installed`.

## Set up your first backup with the Quick Backup wizard

The fastest way to set up a first backup is to use the guided **Quick Backup** flow. This 8-step wizard takes you from target selection to job confirmation without any manual pre-configuration.

Access: the **Quick Backup** button in the top navigation bar, or from the dashboard.

1. **Target selection.** Choose where to store the backup: remote SSH host, local path, etc.

2. **SSH key setup.** The wizard can generate a new SSH key pair. The public key is displayed for copying into the target's `authorized_keys` file. One SSH key per machine is enforced — if a key already exists for that host, it is reused.

3. **Host verification.** Validation of the host key fingerprint. The wizard can refresh the fingerprint if the server was reinstalled.

4. **Repository creation.** Initialization of the Restic repository on the target.

5. **Job naming.** Assign a name to the backup job.

6. **Schedule.** Define the automatic execution schedule.

7. **Retention policy.** Configure snapshot retention rules.

8. **Confirmation.** Summary and effective job creation.

- **Preflight checks at each step:** the wizard validates SSH connectivity, host key fingerprint, Restic presence on the target and available disk space. Results are shown as success / warning / error / info icons.

- **Template system:** four built-in templates cover common use cases, all fully internationalized:

 - `builtin:system-server`— Generic Linux server: `/etc`, `/home`, `/root`, `/var/spool/cron`

 - `builtin:linux-web`— Web server: `/etc`, `/var/www`, `/var/spool/cron`

 - `builtin:mysql-server`— MySQL server: `/etc/mysql`, `/var/lib/mysql`

 - `builtin:postgres-server`— PostgreSQL server: `/etc/postgresql`, `/var/lib/postgresql`
Users can also create and save their own templates (using the `custom:` prefix) to reuse for future quick backups, or duplicate an existing template before adapting it.

- **Error humanization:** SSH and SFTP errors are translated into clear, actionable messages.

- **Security:** one SSH key per machine is enforced — the wizard does not create a duplicate if a key has already been generated for that host.

> Once the wizard is complete, the backup job is created and the first backup can be triggered immediately from the job page.

## The first settings to configure right away

Here is the most efficient order to turn a freshly installed instance into a truly operational backup tool.

1. **Check Settings.** Set the time zone, application name, email and any relevant integrations.

2. **Launch the Quick Backup wizard.** This is the most direct path to creating your first job — see the [section above](#quick-backup).

3. **Or add a repository manually.** Create your first target in [Restic Repositories](04-repositories.md), then define a backup job with source paths, exclusions, tags and retention.

4. **Enable the central cron.** Go to [Scheduler & Worker](08-scheduler-and-worker.md).

5. **Run a manual backup.** Check the log before relying on the schedule.

6. **Test a restore.** Validate early that data can be recovered.

> Once this foundation is in place, you can move on to [Concepts and Navigation](03-concepts-and-navigation.md) to gain a deeper understanding of the tool's logic.

## What's next?

The next chapter explains Fulgurite's mental model: what a repository is, what a backup job controls, why the cron is centralized and how scopes affect user visibility.

[Previous Overview](01-index.md) [Next Concepts and Navigation](03-concepts-and-navigation.md)

## Reading Path

- Previous: [01. Overview](01-index.md)
- Next: [03. Concepts and Navigation](03-concepts-and-navigation.md)
