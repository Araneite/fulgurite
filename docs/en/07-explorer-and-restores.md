---
title: "Fulgurite Documentation — Explorer & Restores"
description: "Browse snapshots, compare versions, download files, and launch full or partial restores."
lang: "en"
section: "user"
---
# Browsing snapshots and restoring data

The explorer lets you navigate the snapshots of a repository, search or compare content, download files or folders, manage tags, delete snapshots, apply a retention policy, and launch local or remote restores.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md)
- [03. Concepts and Navigation](03-concepts-and-navigation.md)
- [04. Restic Repositories](04-repositories.md)
- [05. Backup Jobs](05-backup-jobs.md)
- [06. Copy Jobs](06-copy-jobs.md)
- [07. Explorer and Restores](07-explorer-and-restores.md) (current page)
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


## Browsing a repository and its snapshots

The explorer focuses on one repository, then on a snapshot and a path. This logic keeps navigation precise and preserves the technical context of the displayed content.

- You first choose the repository, then the snapshot to explore.

- Navigation then proceeds through the snapshot's directory tree.

- File search relies on the `snapshot_search_index` and the `snapshot_index_cache` table, fed incrementally with each new snapshot. A freshly created snapshot is therefore only searchable once its indexing is complete.

- The maximum size of certain displays or extractions may be bounded by the exploration settings.

## Comparing snapshots or a file between two snapshots

Two types of comparison are already available in the interface.

### Snapshot diff

- Compares a current snapshot with another.

- Differences are filtered by additions, deletions, and modifications.

- Useful for understanding the overall evolution of a backed-up system.

### File diff

- Compares the content of a specific file between two snapshots.

- The function extracts content via Restic and then computes the diff at the application layer.

- Practical for fine-grained validation before restoring.

## Downloading files or folders

The explorer allows you to extract content without going through a full restore.

- **Single file download** directly from a snapshot.

- **Folder download** as an archive, with support for `tar.gz` and `zip` formats.

- The folder is temporarily reconstituted before packaging.

- These actions are recorded in the activity logs.

> The maximum size of a single file download is bounded by the `explore_max_file_size_mb` parameter (Settings → Data). For archive downloads, keep in mind that very large directory trees can exhaust server RAM during temporary reconstitution: in that case, prefer a targeted restore instead.

## Full or partial restores

Fulgurite can restore an entire snapshot or a selection of files. The same repository can be restored locally or remotely, depending on your permissions and the SSH keys available.

| Mode | What it does | Fields used |
| --- | --- | --- |
| **Full local restore** | Replays an entire snapshot to a path on the Fulgurite server. | `restore_default_target` and the target path entered in the modal. |
| **Full remote restore** | Sends content to a remote machine via SSH. | SSH key, remote path, optional `include` filter. |
| **Partial local restore** | Restores only the files or folders selected in the explorer. | Default local path for partial restores. |
| **Partial remote restore** | Sends that selection to a remote machine. | SSH key and default remote path for partial restores. |

### Managed destination vs. original path

Before launching a restore, the `RestoreTargetPlanner` computes the destination and shows a preview of the resolved path. No free-form path input is accepted.

### Managed mode (default)

- Places files in a safe subdirectory under the configured root (`restore_default_target` in Settings).

- Resolved path: `< root > /< hostname > /< job-name >` for backup jobs, or `< root > /< hostname > /< repo-name >` for direct repository restores.

- For remote restores, the destination directory is created via SSH if it does not already exist.

- API parameter: `destination_mode=managed`, `append_context_subdir=true`.

### Original mode (admin only)

- Restores to the exact path recorded in the snapshot.

- Restricted to the `admin` role. The UI displays an explicit warning when this mode is selected.

- For remote restores, the `--delete` flag is NOT used to prevent accidental deletions.

- API parameter: `destination_mode=original`.

The resolved destination path (`resolved_target`) is returned in the API response and recorded in the restore history. Both full and partial restores (file/folder selection) support both modes.

For remote restores, transfer is performed over SSH with `rsync`. The `rsync` binary is required for remote synchronizations and restores: there is no automatic fallback to `scp`. The setup wizard checks for it and can propose installation. Locally only, if `rsync` is not usable, Fulgurite can copy the extracted tree through PHP.

> The full restore modal explicitly warns that the operation will overwrite existing files. This is an important point to keep in mind in production environments.

## Restore history

The dedicated restores page lets you keep a searchable, paginated record of all recoveries.

- Date and time of launch.

- Repository, snapshot, and mode used.

- Local or remote destination (resolved `resolved_target` path recorded).

- Destination mode used (managed or original).

- Any path filter applied.

- Initiating user, status, duration, and technical logs.

- Page size configurable in Settings.

Each restore is recorded in the `restore_runs` table and remains viewable from the **Restore History** tab: even if an operator no longer has access to the explorer, the operation record (author, date, status, destination) remains available for audit.

## Tags, snapshot deletion, retention, and integrity checks

The explorer is not just a read-only tool. It also provides access to advanced management actions on snapshots and the repository.

- **Snapshot tag management.**

- **Snapshot deletion** when the appropriate permissions are present.

- **Repository retention** with simulation or application, and storage of the policy in the database.

- **Repository integrity check.**

> Deletion and retention actions are permanent from the perspective of the target data. Use the retention simulation mode when available, and restrict these permissions to appropriate profiles.

## What about automation?

The next chapter explains how the central cron, global tasks, and the worker cooperate to launch jobs, process the internal queue, and execute background operations.

[Previous Copy Jobs](06-copy-jobs.md) [Next Scheduler & Worker](08-scheduler-and-worker.md)

## Reading Path

- Previous: [06. Copy Jobs](06-copy-jobs.md)
- Next: [08. Scheduler and Worker](08-scheduler-and-worker.md)
