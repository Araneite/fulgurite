---
title: "Fulgurite Documentation — Hosts & SSH Keys"
description: "Configure remote hosts and SSH keys for remote backups and restores."
lang: "en"
section: "user"
---
# Manage remote hosts and SSH keys

Remote functionality does not rely solely on Restic. It also relies on an SSH layer managed within the interface: hosts for SSH-based backups, keys for remote restores, connectivity tests, and initial access deployment.

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
- [10. Notifications](10-notifications.md)
- [11. Hosts and SSH](11-hosts-and-ssh.md) (current page)
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


## Hosts page

Hosts represent the machines on which certain backup jobs can run via SSH. Each host registered in the database groups a set of structured fields that fully describe the connection.

- **Display name**: human-readable label shown in the interface to identify the machine.

- **`hostname`**: DNS name or IP address of the target machine.

- **`user`**: SSH user used for the connection.

- **`port`**: SSH port (default `22`).

- **`ssh_key_id`**: reference to an SSH key already declared on the SSH Keys page.

- **`sudo_password_ref`**: optional SecretStore reference containing the sudo password, for operations requiring targeted privilege escalation.

- **`sudo_password_file`**: historical compatibility field, read only as a legacy fallback and migrated to SecretStore when possible.

- Free-text description, connection test, and guided setup assistant.

The connection check (`check`) uses the server's native `ssh` command and captures the result (return code, stdout, stderr) to display a readable diagnostic in the interface.

## SSH Keys page

Key management makes it possible to generate, import, test, and deploy the access credentials required for certain remote workflows.

- Generate a private/public key pair with name, user, host, and port.

- Import an existing key.

- Display the public key with a copy button.

- Ready-to-paste command for adding the key to `authorized_keys`.

- Connection test.

- Assisted key deployment to a remote machine using a one-time password.

Generated or imported private keys are stored in `SecretStore`. The historical `private_key_file` field keeps a reference, often in the `secret://...` form, and may still point to an old local file on migrated installations. When the native SSH client needs a file path, Fulgurite materializes a strictly protected temporary file at runtime and removes it after use; that temporary file is not the primary key store.

## Difference between a host and an SSH key

| Component | Primary use | Examples |
| --- | --- | --- |
| **Host** | Represent a remote execution machine for a backup job. | Back up `/var/www` on an application server via SSH. |
| **SSH Key** | Used for remote restores, connectivity tests, and access deployment. | Send a restore to another machine via rsync/SSH. |

## Sudo and protected paths

The optional sudo password on a host is not required in every case, but it becomes useful when the paths to back up are not readable by the standard SSH user. It allows Fulgurite to attempt executions that require targeted privilege escalation. The password is never stored directly in the database: the current mechanism writes a `sudo_password_ref` pointing to `SecretStore`. The `sudo_password_file` field remains only as a legacy fallback for older installations and can be migrated automatically to SecretStore.

> As with any privileged automation, limit these configurations to necessary cases, isolate hosts by scope, and combine them with strict user scopes.

## Next

The next chapter covers the user profile, roles, the permissions matrix, sessions, 2FA, WebAuthn, and the product's security policies.

[Previous Notifications](10-notifications.md) [Next Users & Security](12-security-and-access.md)

## Reading Path

- Previous: [10. Notifications](10-notifications.md)
- Next: [12. Security and Access](12-security-and-access.md)
