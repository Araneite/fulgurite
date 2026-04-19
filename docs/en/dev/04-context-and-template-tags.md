---
title: "Fulgurite developer docs - Context and template tags"
description: "Available theme context, navigation structure and rui_* helpers."
lang: "en"
section: "developer"
---
# Using the rendering context and template tags

Advanced themes receive a PHP context already prepared by the core. The idea is to rely on this theme API instead of reaching into internal managers or writing business logic inside templates.

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
- [04. Context and Template Tags](04-context-and-template-tags.md) (current page)
- [05. Packaging and Debugging](05-packaging-and-debugging.md)


## What the $ctx array contains

| Key | Type | Use |
| --- | --- | --- |
| `title`, `subtitle` | string | Page title and subtitle. |
| `active` | string | Active navigation item. |
| `actions` | string | HTML actions block pre-rendered by the core. |
| `flash` | array\|null | Optional flash message. |
| `user` | array\|null | Current user. |
| `theme_id` | string | Active theme identifier. |
| `app_name`, `app_logo_letter` | string | Instance identity. |
| `nav` | array | Navigation already filtered by permissions. |

Inside an overridden page, you may also receive `page_id` and `data`. For the dashboard, `data` currently contains the summary loaded by the core.

## Shape of $ctx['nav']

```text
[
  'key' => 'dashboard',
  'label' => 'Dashboard',
  'href' => '/index.php',
  'active' => true,
  'section' => 'Overview',
  'icon' => '<svg>...</svg>',
  'group_key' => 'monitoring',
  'group_label' => 'Monitoring',
  'group_icon' => '<svg>...</svg>',
]
```

The navigation is already filtered according to user permissions. A theme may reorder or restyle it, but should not try to bypass those checks.

## Template tags to prefer

- `rui_current_user()`, `rui_can()`, `rui_can_restore()`

- `rui_app_name()`, `rui_app_logo_letter()`, `rui_theme_id()`

- `rui_nav()`, `rui_route()`, `rui_escape()`

- `rui_part()`, `rui_slot()`, `rui_default_slot()`

- `rui_dashboard_summary()`, `rui_list_repos()`, `rui_list_hosts()`, `rui_list_users()`

These helpers are exposed by `src/ThemeTemplateTags.php`. They offer a more stable surface than direct calls to the database, internal managers or objects that were not designed for themes.

## Composing a theme without making it fragile

- Prefer a part override over a full slot copy when possible.

- Use `rui_default_slot()` to wrap native rendering when that is enough.

- Escape dynamic output with `rui_escape()` or existing helpers.

- Avoid business logic and direct access to internal layers from theme templates.

> Treat internal classes as core implementation details, not as a stable API for themes. The safest way to avoid regressions is to go through the context and template tags.

## Finish with delivery

The last page covers ZIP packaging, security limits, installation modes and the debugging checklist.

[Previous Advanced themes](03-advanced-themes.md) [Next Packaging and debugging](05-packaging-and-debugging.md)

## Reading Path

- Previous: [03. Advanced Themes](03-advanced-themes.md)
- Next: [05. Packaging and Debugging](05-packaging-and-debugging.md)
