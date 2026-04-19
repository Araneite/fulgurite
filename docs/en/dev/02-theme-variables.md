---
title: "Fulgurite developer docs - Variable themes"
description: "Reference for the variables format: manifest, supported variables, validation and workflow."
lang: "en"
section: "developer"
---
# Developing a variable theme

The `variables` format is the simplest way to customize Fulgurite. It lets you change colors, typography and a few visual tokens without introducing PHP or layout overrides.

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
- [02. Variable Themes](02-theme-variables.md) (current page)
- [03. Advanced Themes](03-advanced-themes.md)
- [04. Context and Template Tags](04-context-and-template-tags.md)
- [05. Packaging and Debugging](05-packaging-and-debugging.md)


## Minimal manifest

A variable theme is built around a JSON manifest containing a stable identifier, metadata and the `variables` block.

```text
{
  "id": "ocean",
  "name": "Ocean",
  "description": "Deep blue palette",
  "author": "Jane Doe",
  "version": "1.0",
  "type": "variables",
  "variables": {
    "--bg": "#0a1628",
    "--bg2": "#0d1f3c",
    "--bg3": "#152849",
    "--border": "#1e3a5f",
    "--text": "#cdd9e5",
    "--text2": "#7d9ab5",
    "--accent": "#39c5cf",
    "--accent2": "#1a9aa4",
    "--green": "#26a641",
    "--red": "#f85149",
    "--yellow": "#e3b341",
    "--purple": "#bc8cff"
  }
}
```

## Required and optional variables

| Variable | Main role | Status |
| --- | --- | --- |
| `--bg`, `--bg2`, `--bg3` | Primary, secondary and tertiary backgrounds | Required |
| `--border` | Borders and separators | Required |
| `--text`, `--text2` | Primary and secondary text | Required |
| `--accent`, `--accent2` | Links, focus states and active states | Required |
| `--green`, `--red`, `--yellow`, `--purple` | States and complementary accents | Required |
| `--font-mono`, `--font-sans` | Shell fonts | Optional |
| `--radius`, `--shadow` | Radius and shadow tokens | Optional |

The shell default values live in `public/assets/app.css`. A variable theme only replaces these tokens, it does not modify HTML structure.

## Rules enforced by the engine

- `id` must be 1 to 32 characters long and only use `a-z`, digits, dashes and underscores.

- `name` is required, while `description` and `author` are length-limited.

- `version` only accepts a short format based on letters, digits, dots, underscores and dashes.

- CSS values are filtered to reject unsafe `@import`, `javascript:`, `expression`, HTML tags and dangerous characters.

> Validation is centralized in `src/ThemeManager.php`. When in doubt about a token or format, that is the right file to inspect before shipping.

## Recommended workflow

1. Create a minimal manifest with all required variables.

2. Check baseline contrast on the sidebar, cards and form states.

3. Add optional variables afterward to align typography and density.

4. Move to an `advanced` theme only if the need goes beyond tokens.

## Move to the next level

The next section explains how to structure an advanced theme, wire a `style.css` file and override slots, parts or pages.

[Previous Overview](01-index.md) [Next Advanced themes](03-advanced-themes.md)

## Reading Path

- Previous: [01. Developer Overview](01-index.md)
- Next: [03. Advanced Themes](03-advanced-themes.md)
