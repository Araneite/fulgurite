---
title: "Fulgurite Documentation — Users & Security"
description: "Profile, users, roles, scopes, sessions, TOTP, WebAuthn, re-authentication and security policies."
lang: "en"
section: "user"
---
# Manage access, roles and authentication

Fulgurite provides fairly comprehensive account management for an operational application: user profile, preferences, password change, TOTP, WebAuthn, re-authentication, active sessions, invitations, repository/host scopes, account suspension and expiration.

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
- [11. Hosts and SSH](11-hosts-and-ssh.md)
- [12. Security and Access](12-security-and-access.md) (current page)
- [13. Settings Reference](13-settings-reference.md)
- [14. API and Integrations](14-api-and-integrations.md)
- [15. Themes and Customization](15-themes-and-customization.md)

### Developer Documentation

- [01. Developer Overview](dev/01-index.md)
- [02. Variable Themes](dev/02-theme-variables.md)
- [03. Advanced Themes](dev/03-advanced-themes.md)
- [04. Context and Template Tags](dev/04-context-and-template-tags.md)
- [05. Packaging and Debugging](dev/05-packaging-and-debugging.md)


## My Profile page

Each user has a personal page that centralizes their information, preferences, and authentication factors.

- Personal and professional information: name, email, phone, job title.

- Preferences: interface language, time zone, start page, preferred theme. The language setting is stored per user and persists across sessions. If no preference is set, the instance falls back to the default locale configured in Settings, then to French. Available languages depend on the translation catalogs installed on the instance.

- Password change with verification against compromised passwords.

- TOTP activation or deactivation.

- WebAuthn / passkeys management.

- View of active sessions, recent activity, and recent login attempts.

## Users page

The administration surface goes beyond simple account creation.

- Manual user creation or invitation issuance.

- Profile editing by an administrator.

- Role and access policy modification.

- Account activation or deactivation.

- Temporary suspension, suspension reason, and account expiration.

- Password reset, password change, and session revocation.

- Account deletion and management of active invitations.

## Roles, application permissions and scopes

The default system roles are `viewer`, `operator`, `restore-operator`, and `admin`. Roles, levels, and permissions are driven in `src/AppConfig.php` by `defaultRoles()`, `getRoles()`, `permissionDefinitions()`, and `rolePermissions()`. They can be renamed or enriched, but their level order must remain strict.

- **viewer**: read-only access to repositories, jobs, snapshots, and statistics.

- **operator**: can trigger backups, manage jobs, and consult logs.

- **restore-operator**: restore-oriented, with specific rights on restore operations and the explorer.

- **admin**: full access to configuration, users, settings, and the API.

- Permissions are resolved at the application level: repositories, jobs, restores, scheduler, settings, themes, users, logs, stats, performance, snapshots.

- **repo_scope** and **host_scope** scopes limit what a user can see or manipulate.

- A user can therefore have an elevated role while still being bounded to a restricted perimeter.

## Sessions, fingerprint, re-authentication and revocation

Active sessions are stored and verified in the database, enabling finer-grained controls than a plain PHP session.

- Absolute duration, inactivity, and warning time limits.

- Strict fingerprinting based on IP and User-Agent when enabled.

- Re-authentication required for certain sensitive actions.

- Maximum number of simultaneous sessions per user.

- Revocation of a targeted session, all other sessions, or all sessions for an account.

The session fingerprint combines the client's IP address and User-Agent. If strict comparison is enabled and either of these two vectors changes during the session's lifetime, the session is immediately invalidated and the user is forced to log in again. This protection limits the risks of cookie theft.

The `reauth_max_age_seconds` parameter controls the maximum age of a re-authentication for sensitive actions (secret rotation, repository deletion, 2FA deactivation, etc.). After this delay, the user must re-enter their password or validate a factor before continuing.

## TOTP and WebAuthn

Two mechanisms are already supported and can coexist depending on the instance policy: TOTP compliant with **RFC 6238** and WebAuthn (**FIDO2** passkeys).

### TOTP (RFC 6238)

- Secret generation and OTP URL for QR code.

- Code confirmation before activation.

- Option to disable with re-authentication.

### WebAuthn / FIDO2 passkeys

- Registration and deletion of security keys.

- Global settings for RP name, RP ID, timeouts, user verification, and resident keys.

- WebAuthn autostart option at login.

### Primary factor selection and login behavior

Each user chooses their primary 2FA factor in their profile. Available options are `classic_2fa`(TOTP), `webauthn`(hardware key), or `none` if the instance policy permits it. When multiple factors are enrolled, the primary factor is the one requested at login. Other enrolled factors remain available as fallback options.

If a user's primary factor is removed (e.g., all TOTP codes deleted), the system falls back to password-only authentication and prompts the user to reconfigure 2FA at their next login.

### Step-up re-authentication

Certain sensitive operations require re-confirmation of the primary factor even when the user is already logged in. The proof is cryptographically bound to the exact operation — it cannot be reused for a different action.

- Changing email address or password.

- Disabling 2FA or deleting a passkey.

- Creating an API key.

- Promoting or demoting a user's role by an administrator (privilege escalation prevention).

The `reauth_max_age_seconds` parameter defines how long a re-authentication proof remains valid. After this delay, a new confirmation is required.

### WebAuthn counter validation

On every WebAuthn assertion, the system verifies that the signature counter (`sign_count`) is strictly greater than the stored `use_count` value. A value that is equal to or lower than the stored value indicates a potentially cloned credential and triggers an immediate rejection. The UP (User Presence) and UV (User Verification) flags are required on every assertion.

## Other security mechanisms already present

- Login attempt rate limiting.

- Login notifications, with an option to notify only on new IPs.

- Possible 2FA requirement for administrators.

- **Forced actions** to impose a profile update or 2FA configuration.

- Sensitive credentials are stored through `secret://...` references instead of clear text in application records.

- Action auditing and configurable retention of security-related logs.

The **forced actions** mechanism allows an administrator to impose a blocking action on a user: password change, TOTP activation, passkey addition, profile update. Until the pending action is resolved, the user sees a blocking screen that prevents access to the rest of the application. Pending actions are stored in the database and automatically resolved as soon as the condition is met.

## Secret logs and HA broker

The **Secret Logs** page complements regular activity logs with a dedicated view of SecretStore and the secret broker. It is reserved for profiles with `settings.manage`.

- Secret access review: action, purpose, `secret://...` reference, success or failure.

- Broker cluster state: latest known status, healthy node count and link to the Performance page for diagnostics.

- Broker events: `degraded`, `node_failed`, `node_recovered`, `failover`, `down`, `recovered`.

- CSV export of the visible rows for external audit.

> If the broker is not configured or temporarily unavailable, the page may show an error and only present locally available information.

## Customize next

The next chapter explains how to change the application's appearance, install themes, use the store, and manage theme requests on a shared instance.

[Previous Hosts & SSH Keys](11-hosts-and-ssh.md) [Next Themes & Customization](15-themes-and-customization.md)

## Reading Path

- Previous: [11. Hosts and SSH](11-hosts-and-ssh.md)
- Next: [13. Settings Reference](13-settings-reference.md)
