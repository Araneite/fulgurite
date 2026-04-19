---
title: "Fulgurite developer docs - Context and template tags"
description: "Contexte disponible dans les themes, structure de navigation et helpers rui_*."
lang: "fr"
section: "developer"
---
# Utiliser le contexte de rendu et les template tags

Les themes advanced recoivent un contexte PHP deja prepare par le core. L idee est de reposer sur cette API de theme au lieu d aller chercher directement des managers internes ou d ecrire de la logique metier dans les templates.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](../01-index.md)
- [02. Installation et prise en main](../02-getting-started.md)
- [03. Concepts et navigation](../03-concepts-and-navigation.md)
- [04. Dépôts Restic](../04-repositories.md)
- [05. Jobs de sauvegarde](../05-backup-jobs.md)
- [06. Jobs de copie](../06-copy-jobs.md)
- [07. Explorateur et restaurations](../07-explorer-and-restores.md)
- [08. Planification et worker](../08-scheduler-and-worker.md)
- [09. Monitoring et performance](../09-monitoring-and-performance.md)
- [10. Notifications](../10-notifications.md)
- [11. Hôtes et SSH](../11-hosts-and-ssh.md)
- [12. Sécurité et accès](../12-security-and-access.md)
- [13. Référence des paramètres](../13-settings-reference.md)
- [14. API et intégrations](../14-api-and-integrations.md)
- [15. Thèmes et personnalisation](../15-themes-and-customization.md)

### Documentation développeur

- [01. Vue d’ensemble développeur](01-index.md)
- [02. Thèmes à variables](02-theme-variables.md)
- [03. Thèmes avancés](03-advanced-themes.md)
- [04. Contexte et template tags](04-context-and-template-tags.md) (page actuelle)
- [05. Packaging et debug](05-packaging-and-debugging.md)


## Ce que contient le tableau $ctx

| Cle | Type | Usage |
| --- | --- | --- |
| `title`, `subtitle` | string | Titre et sous-titre de la page. |
| `active` | string | Entree de navigation active. |
| `actions` | string | Bloc HTML d actions pre-rendu par le core. |
| `flash` | array\|null | Message flash eventuel. |
| `user` | array\|null | Utilisateur courant. |
| `theme_id` | string | Identifiant du theme actif. |
| `app_name`, `app_logo_letter` | string | Identite de l instance. |
| `nav` | array | Navigation deja filtree par permissions. |

Dans une page surchargee, vous pouvez aussi recevoir `page_id` et `data`. Pour le dashboard, `data` contient aujourd hui le resume charge par le core.

## Structure de $ctx['nav']

```text
[
  'key' => 'dashboard',
  'label' => 'Dashboard',
  'href' => '/index.php',
  'active' => true,
  'section' => 'Vue d ensemble',
  'icon' => '<svg>...</svg>',
  'group_key' => 'monitoring',
  'group_label' => 'Suivi',
  'group_icon' => '<svg>...</svg>',
]
```

La navigation est deja filtree selon les permissions de l utilisateur. Un theme peut la reordonner ou la restyler, mais ne doit pas essayer de contourner ces controles.

## Template tags a privilegier

- `rui_current_user()`, `rui_can()`, `rui_can_restore()`

- `rui_app_name()`, `rui_app_logo_letter()`, `rui_theme_id()`

- `rui_nav()`, `rui_route()`, `rui_escape()`

- `rui_part()`, `rui_slot()`, `rui_default_slot()`

- `rui_dashboard_summary()`, `rui_list_repos()`, `rui_list_hosts()`, `rui_list_users()`

Ces helpers sont exposes par `src/ThemeTemplateTags.php`. Ils constituent une surface plus stable que des appels directs a la base, a des managers internes ou a des objets non prevus pour les themes.

## Composer un theme sans le rendre fragile

- Favoriser une surcharge de part plutot qu une recopie complete de slot.

- Utiliser `rui_default_slot()` pour enrober le rendu natif quand cela suffit.

- Echapper les sorties dynamiques avec `rui_escape()` ou les helpers existants.

- Eviter la logique metier et les acces directs a la couche interne depuis les templates du theme.

> Traitez les classes internes comme une implementation du core, pas comme une API stable pour les themes. La meilleure defense contre les regressions reste de passer par le contexte et les template tags.

## Terminer avec la livraison

La derniere page couvre le packaging ZIP, les limites de securite, les modes d installation et la checklist de debug.

[Precedent Themes advanced](03-advanced-themes.md) [Suivant Packaging et debug](05-packaging-and-debugging.md)

## Parcours

- Précédent: [03. Thèmes avancés](03-advanced-themes.md)
- Suivant: [05. Packaging et debug](05-packaging-and-debugging.md)
