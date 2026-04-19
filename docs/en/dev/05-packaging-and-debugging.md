---
title: "Fulgurite developer docs - Packaging and debugging"
description: "Theme packaging, security constraints, installation and debugging checklist."
lang: "en"
section: "developer"
---
# Preparing the final package and debugging a theme

A theme is not done just because the rendering looks right. You also need to verify that the archive respects engine constraints, that files stay inside the allowlist and that the shell holds up on desktop as well as mobile.

## Navigation

### User Documentation

- [01. Overview](../01-index.md)
- [02. Getting Started](../02-getting-started.md)
- [03. Concepts and Navigation](../03-concepts-and-navigation.md)
- [04. Restic Repositories](../04-repositories.md)
- [05. Backup Jobs](../05-backup-jobs.md)
- [06. Copy Jobs](../06-copy-jobs.md)
- [07. Explorer and Restores](../07-explorer-and-restores.md)
- [08. Scheduler and Worker](../08-scheduler-and-worker.md)
- [09. Monitoring and Performance](../09-monitoring-and-performance.md)
- [10. Notifications](../10-notifications.md)
- [11. Hosts and SSH](../11-hosts-and-ssh.md)
- [12. Security and Access](../12-security-and-access.md)
- [13. Settings Reference](../13-settings-reference.md)
- [14. API and Integrations](../14-api-and-integrations.md)
- [15. Themes and Customization](../15-themes-and-customization.md)

### Developer Documentation

- [01. Developer Overview](01-index.md)
- [02. Variable Themes](02-theme-variables.md)
- [03. Advanced Themes](03-advanced-themes.md)
- [04. Context and Template Tags](04-context-and-template-tags.md)
- [05. Packaging and Debugging](05-packaging-and-debugging.md) (current page)


## Expected ZIP structure

The archive should contain `theme.json` at its root and, when needed, `style.css`, `README.md`, `LICENSE` plus the `slots/`, `parts/` and `pages/` directories. A single top-level directory is tolerated and removed automatically during installation.

- Maximum archive size: 2 MB.

- Maximum extracted size: 8 MB.

- File count is limited.

- Directory depth is limited.

- No hidden files, no path traversal and no directories outside the allowlist.

## Installation modes to keep in mind

A theme can be installed from disk, from a ZIP archive or from an HTTPS URL. In all cases, an advanced theme must be treated as PHP code that executes on the server.

> Do not ship an advanced theme as if it were only a visual resource. It is an application extension and should be reviewed with the same level of care as a core patch.

## Quick debugging checklist

- The theme does not appear: check `theme.json`, `id`, type and required variables.

- The CSS does not change: check the active theme and the `[data-theme="id"]` scope.

- An override is ignored: check the exact slot or part name and its location.

- A custom page does not load: check that the core really calls the targeted extension point.

- The rendering breaks on mobile: check shell width, sidebar, topbar and oversized tables.

## Final checklist

- Stable, short `id`.

- Valid `theme.json` consistent with the directory name.

- Required variables present.

- `style.css` scoped with `[data-theme="id"]`.

- Dynamic output escaped.

- No unnecessary direct access to internal classes.

- Desktop and mobile visual check completed.

- Dashboard verification if `pages/dashboard.php` is shipped.

## Section complete

You now have a complete web reference to build, verify and distribute Fulgurite themes.

[Previous Context and template tags](04-context-and-template-tags.md) [Back Overview](01-index.md)

## Reading Path

- Previous: [04. Context and Template Tags](04-context-and-template-tags.md)
