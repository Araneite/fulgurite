---
title: "Fulgurite developer docs - Themes"
description: "Starting guide to build, structure and ship Fulgurite themes."
lang: "en"
section: "developer"
---
# Building complete themes for Fulgurite

This section documents the theme engine from a developer perspective. It covers variable themes, advanced themes, the available PHP context, template tags, ZIP packaging and the checks that matter before shipping.

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

- [01. Developer Overview](01-index.md) (current page)
- [02. Variable Themes](02-theme-variables.md)
- [03. Advanced Themes](03-advanced-themes.md)
- [04. Context and Template Tags](04-context-and-template-tags.md)
- [05. Packaging and Debugging](05-packaging-and-debugging.md)


## Choosing between variable themes and advanced themes

The engine supports two theme formats. The first one is limited to a JSON manifest with CSS variables. The second adds a full directory, a `style.css` file and PHP overrides for the layout or some pages.

| Format | Contents | When to choose it |
| --- | --- | --- |
| **variables** | A JSON file with `theme.json` or `<id>.json` depending on the installation mode. | To change the palette, fonts, radius values and shadows without touching markup. |
| **advanced** | A directory with `theme.json`, `style.css`, `slots/`, `parts/` and `pages/`. | To reorganize the layout, replace rendered areas or customize the dashboard. |

## Start simple, add complexity only when needed

- Start by locking the palette and typography with a `variables` theme.

- Move to an `advanced` theme only if the layout really needs to change.

- Override a targeted part before copying a full slot.

- Use page overrides only when the core already exposes an extension point for that screen.

> In the current codebase, only the `dashboard` page override is actually wired by the core. Other page names are reserved by the engine but are not all called yet.

## Where to look in the project

The main shell is rendered by `public/layout_top.php` and `public/layout_bottom.php`. The theme engine lives in `src/ThemeManager.php`, `src/ThemeRenderer.php`, `src/ThemeTemplateTags.php` and `src/ThemePackage.php`.

For concrete examples, start from `src/themes_builtin/dark.json`, `src/themes_builtin/light.json` and especially `src/themes_builtin/horizon/`, which shows a complete advanced theme.

## What this documentation helps you ship

The goal is not only to describe the format, but to give you a full working frame: directory structure, valid variables, supported slots, template tags to prefer, ZIP security constraints, debug checks and a delivery checklist.

## Continue with the simplest format

The next page details the structure of variable themes, supported variables and the validation rules enforced by the engine.

[Public docs Themes & Customization](../15-themes-and-customization.md) [Next Variable themes](02-theme-variables.md)

## Reading Path

- Next: [02. Variable Themes](02-theme-variables.md)
