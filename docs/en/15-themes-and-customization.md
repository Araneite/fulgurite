---
title: "Fulgurite Documentation — Themes & Customization"
description: "Installed themes, store, user requests and interface customization options."
lang: "en"
section: "user"
---
# Customizing the instance appearance

The theme system is organized around several tabs: installed themes, store, admin requests and personal requests. It supports both lightweight and advanced customization, depending on your comfort with code and trust in installed packages.

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
- [12. Security and Access](12-security-and-access.md)
- [13. Settings Reference](13-settings-reference.md)
- [14. API and Integrations](14-api-and-integrations.md)
- [15. Themes and Customization](15-themes-and-customization.md) (current page)

### Developer Documentation

- [01. Developer Overview](dev/01-index.md)
- [02. Variable Themes](dev/02-theme-variables.md)
- [03. Advanced Themes](dev/03-advanced-themes.md)
- [04. Context and Template Tags](dev/04-context-and-template-tags.md)
- [05. Packaging and Debugging](dev/05-packaging-and-debugging.md)


## Variable themes and advanced themes

A theme customizes colors, typography and, depending on the level, the logo and certain rendering zones. Themes built into the product are stored in `src/themes_builtin/`, while imported custom themes land in `data/themes/`.

> If you need to build, package or maintain a complete theme, the developer documentation is available at [/docs/dev/en](dev/01-index.md).

| Type | Contents | Use case |
| --- | --- | --- |
| **Variables** | JSON theme based on style variables. | The simplest and safest way to change colors, typography and visual identity. |
| **Advanced** | Zip package that may contain `theme.json`, `style.css`, `slots/`, `parts/`, `pages/` and PHP. | For deep customization, including replacement of certain rendered sections. |

## Available installation methods

- Upload a variables-only JSON theme.

- Upload an advanced package as a `.zip` file.

- Install from a URL or a GitHub repository converted to an archive.

- Install from the built-in store.

- Delete a non-builtin theme.

> Uploading a custom theme in `.zip` format requires the PHP `zip` extension to be active on the server. If the extension is missing, only variables-only JSON themes can be imported.

## Store and extended catalogue

The store showcases themes considered trustworthy and ready to install quickly. Its catalogue can be extended locally via `data/theme_store_extra.json` using the same format.

## Theme requests and moderation

On a shared instance, users can submit themes without having direct installation rights.

- Submission by upload or URL.

- Request queue for administrators.

- Archive download and review before approval.

- Approval, rejection, notes and status tracking from the user side in **My Requests**.

## What must be treated as executable code

Advanced themes can contain PHP. They must therefore be evaluated as application code running on your server, not as a simple cosmetic resource.

> On a production server, only approve packages that have been read, audited and sourced from a trusted origin. The request workflow exists precisely to introduce a review phase.

## Continue to the settings reference

The next chapter details the global configuration groups of the application: interface, security, scheduler, exploration, integrations, API and more.

[Previous Users & Security](12-security-and-access.md) [Next Settings Reference](13-settings-reference.md)

## Reading Path

- Previous: [14. API and Integrations](14-api-and-integrations.md)
