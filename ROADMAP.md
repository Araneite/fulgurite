# Roadmap

This roadmap describes the main product directions planned for Fulgurite. It is not a release promise or a fixed schedule.

## Restore From Backup Copies

Allow restores to be launched from backup copies, not only from the primary repository. This should make recovery more flexible when the main repository is unavailable, degraded or intentionally kept separate from the restore target.

## Multi-Workspace Management

Introduce workspace separation so one Fulgurite instance can manage several operational contexts. Workspaces should help isolate repositories, jobs, users, scopes and settings for different teams, customers or environments.

## Past And Future Execution Calendar

Add a calendar view for scheduled activity. It should show past executions, upcoming planned runs and useful status markers for backups, copies, global tasks and maintenance operations.

## Advanced Log Management

Improve how logs are stored, searched, filtered and reviewed. The goal is to make troubleshooting easier without forcing users to inspect raw output everywhere.

## Automated Secret Rotation

Add controlled rotation workflows for sensitive secrets such as repository passwords, API tokens, SSH material or external integration credentials. Rotation should be auditable and designed to reduce manual operational risk.

## Related Documentation

- [README.md](README.md)
- [Getting Started](docs/en/02-getting-started.md)
- [Settings Reference](docs/en/13-settings-reference.md)
- [Security Policy](SECURITY.md)
