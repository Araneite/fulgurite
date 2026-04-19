---
title: "Fulgurite Documentation — Restic Repositories"
description: "Declare, test, and manage Restic repositories in Fulgurite."
lang: "en"
section: "user"
---
# Restic Repositories

The Repositories page is used to register the Restic backends known to Fulgurite. Each repository carries not only its path, but also its secret mechanism, its alert threshold, and its notification policy.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md)
- [03. Concepts and Navigation](03-concepts-and-navigation.md)
- [04. Restic Repositories](04-repositories.md) (current page)
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


## Creating a repository

The add form does not simply save a label. It first attempts to resolve the effective secret, then tests access to the backend before considering the repository usable.

| Field | Purpose | Practical notes |
| --- | --- | --- |
| **Name** | Human-readable identifier used in lists, jobs, and the explorer. | Consider a consistent naming convention per environment or application. |
| **Path** | Address of the Restic backend. | Can be a local path or a backend such as `sftp:user@host:/backups/repo`. |
| **Description** | Human context for the repository. | Useful for distinguishing an infrastructure repository from an application one. |
| **Alert threshold** | Number of hours after which a repository becomes suspect if it has not received a recent snapshot. | Feeds runtime statuses and "stale" alerts. |
| **Notification policy** | Selection of channels to use for repository events. | Stored in the repository's `notification_policy` column as JSON. Each repository can thus have its own policy that **overrides the global policy** of the instance: enabled channels, severity thresholds, specific recipients. |

## Managing the repository password

Repository passwords are managed through the **SecretStore** abstraction. The UI handles all secret creation and retrieval — no manual file management is required. The active provider is selected via the `FULGURITE_SECRET_PROVIDER` environment variable.

### HaBrokerSecretProvider (recommended)

- **High-availability broker**: connects to one or more endpoints (comma-separated), with automatic failover. Each unhealthy endpoint is bypassed for 10 seconds.

- Configure via `FULGURITE_SECRET_BROKER_ENDPOINTS`(e.g. `http://broker1:7777,http://broker2:7777`) and `FULGURITE_SECRET_BROKER_TIMEOUT`(default: `2.0` s).

- Internal references: `secret://agent/{type}/{id}/{name}`

- Enabled by `FULGURITE_SECRET_PROVIDER=broker`.

### AgentSecretProvider (single socket)

- Direct connection over a **Unix socket**. Simpler setup, no high-availability.

- Default socket: `/run/fulgurite/secrets.sock`— override with `FULGURITE_SECRET_AGENT_SOCKET`.

- Internal references: `secret://agent/{type}/{id}/{name}`

- Enabled by `FULGURITE_SECRET_PROVIDER=agent` or `secret-agent`.

### LocalEncryptedSecretProvider (no daemon)

- **AES-256-GCM** encryption, key stored in `data/broker/`. No external service required.

- Each repository has its own secret: passwords are never shared between repositories.

- Autonomous mode for a self-hosted server with no secrets daemon.

- Internal references: `secret://local/{type}/{id}/{name}`

- Enabled by `FULGURITE_SECRET_PROVIDER=local` or `encrypted`.

### InfisicalSecretProvider (external)

- Delegates resolution to the **Infisical API**. Requires configuration in Settings > Integrations.

- Enter the Infisical **secret name** instead of the value when creating a repository.

- The application **resolves and tests the secret at creation time**: if resolution fails (secret not found, environment unreachable, invalid token), the registration is cancelled and the error is reported.

- Internal references: `secret://infisical/{secretName}`

| Environment variable | Purpose |
| --- | --- |
| `FULGURITE_SECRET_PROVIDER` | Selects the provider: `agent`, `broker`, `secret-agent`, `local`, `encrypted`, or `fallback`. |
| `FULGURITE_SECRET_BROKER_ENDPOINTS` | Comma-separated URIs for HA broker endpoints (e.g. `http://broker1:7777,http://broker2:7777`). |
| `FULGURITE_SECRET_BROKER_TIMEOUT` | Request timeout in seconds for the HA broker (default: `2.0`). |
| `FULGURITE_SECRET_AGENT_SOCKET` | Unix socket path for `AgentSecretProvider`(default: `/run/fulgurite/secrets.sock`). |

> The `data/passwords/` directory is a legacy from earlier versions. New installations use the provider chain described above. If you are migrating an older installation, existing files remain readable.

> If you use Infisical, a secret access error during creation blocks the repository from being saved. Verify the name, environment, and path configured before retrying.

## Automatically initializing an empty repository

The **Create the repository if it does not exist** checkbox changes the behavior when the backend exists as a path or target, but has not yet been initialized as a Restic repository.

- For a **local path**, the application can attempt to create the directory if it does not exist.

- It then runs the Restic initialization of the repository.

- If initialization succeeds on a local repository, it applies a group permission correction to the directory tree.

- If initialization fails, the repository is not saved and the Restic error message is reported.

## Available actions once the repository is registered

The repository then becomes a controllable resource from several places within the application.

### From the repository list

- **Explore** to open the explorer directly on this repository.

- **Edit** to adjust description, alert threshold, and notifications.

- **Test notification** to validate the repository's alert policy.

- **Delete** if the repository should no longer be managed by this instance.

### From the explorer

- **Check integrity** of the repository.

- **Initialize** the repository if the action is still relevant in this context.

- **Apply a retention policy** on the repository in dry-run or live mode.

## Alert threshold, runtime statuses, and notifications

The repository feeds the instance's monitoring. Its state is not stored in the database: it is **calculated in real time** by the `RepoStatusService` on every consultation, based on the freshness of observed snapshots and the associated technical checks.

- **`ok`** if the repository is accessible and recent snapshots meet the expected threshold.

- **`warning`** if the age of the last snapshot exceeds the alert threshold configured on the repository (`alert_hours`). This is the classic "stale" state.

- **`no_snap`** if the repository is known but does not yet have any usable snapshot.

- **`error`** if technical checks report a failure or an inability to access the repository.

- **`pending`** for a repository whose status is being evaluated or that was just created and is awaiting its first calculation.

## Permissions, scopes, and best practices

Repository management is often the first place where the effects of user scopes are felt.

- **`repos.view`** is sufficient to display the list and open the explorer on a visible repository.

- **`repos.manage`** is required for adding, deleting, running checks, and certain sensitive actions.

- If the user is limited to a **selected repo scope**, only authorized repositories will be visible and accessible to them.

- API tokens indirectly inherit this logic: a user can only assign to a token scopes compatible with their own permissions.

> A good practice is to first create repositories per environment or functional scope, then attach clearly named jobs to each one.

## Logical next step

Once repositories are declared, you can create the jobs that feed them. The next chapter covers backup jobs in detail, including hooks, retention policies, retry strategies, and remote execution.

[Previous Concepts & Navigation](03-concepts-and-navigation.md) [Next Backup Jobs](05-backup-jobs.md)

## Reading Path

- Previous: [03. Concepts and Navigation](03-concepts-and-navigation.md)
- Next: [05. Backup Jobs](05-backup-jobs.md)
