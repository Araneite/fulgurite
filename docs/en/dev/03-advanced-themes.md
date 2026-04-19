---
title: "Fulgurite developer docs - Advanced themes"
description: "Structure, style.css, overrides and limits of the advanced format."
lang: "en"
section: "developer"
---
# Building an advanced theme

The `advanced` format lets you ship a full theme with custom CSS and PHP templates. It is the right choice when you need to move shell areas around, redesign navigation or provide a truly custom dashboard.

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
- [03. Advanced Themes](03-advanced-themes.md) (current page)
- [04. Context and Template Tags](04-context-and-template-tags.md)
- [05. Packaging and Debugging](05-packaging-and-debugging.md)


## Expected structure

```text
data/themes/acme/
|- theme.json
|- style.css
|- slots/
| |- head.php
| |- sidebar.php
| |- topbar.php
| `- footer.php
|- parts/
| |- sidebar_logo.php
| |- sidebar_nav.php
| |- sidebar_user.php
| |- topbar_title.php
| `- topbar_notifications.php
`- pages/
   `- dashboard.php
```

Everything is optional except `theme.json`. The `horizon` theme in `src/themes_builtin/horizon/` is the concrete reference implementation.

## How to write style.css

The engine injects your CSS only when the theme is active, but it does not rewrite selectors for you. Your rules should therefore be scoped with the active theme attribute.

```text
[data-theme="acme"].sidebar {
  width: 100%;
  border-right: 0;
  border-bottom: 1px solid var(--border);
}

[data-theme="acme"].main {
  margin-left: 0;
}
```

> Without the `[data-theme="acme"]` prefix, your CSS may either miss the shell entirely or create collisions with other themes.

## Supported slots, parts and pages

| Type | Supported names | Use |
| --- | --- | --- |
| Slots | `head`, `sidebar`, `topbar`, `footer` | Replace a large shell area. |
| Parts | `sidebar_logo`, `sidebar_nav`, `sidebar_user`, `topbar_title`, `topbar_notifications` | Adjust a smaller subsection without copying the whole slot. |
| Pages | `dashboard`, then other ids reserved by the engine | Render a complete custom screen for a supported page. |

In practice, the page extension point currently wired by the core is `pages/dashboard.php`. Other names are prepared at engine level, but not all called yet.

## Important constraints and limits

- `style.css` goes through a security filter and remains size-limited.

- Only a fixed set of slot, part and page names is allowed.

- An advanced theme runs real PHP, so it must be treated as trusted application code.

- A theme cannot invent new extension points if the engine does not already know them.

## Read the context and theme API next

The next page describes what the engine exposes to templates: the `$ctx` array, navigation and the `rui_*` helpers.

[Previous Variable themes](02-theme-variables.md) [Next Context and template tags](04-context-and-template-tags.md)

## Reading Path

- Previous: [02. Variable Themes](02-theme-variables.md)
- Next: [04. Context and Template Tags](04-context-and-template-tags.md)
