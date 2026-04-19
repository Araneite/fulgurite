---
title: "Fulgurite developer docs - Advanced themes"
description: "Structure, style.css, overrides et limites du format advanced."
lang: "fr"
section: "developer"
---
# Construire un theme advanced

Le format `advanced` permet de livrer un theme complet avec CSS custom et templates PHP. C est le bon choix quand il faut deplacer des zones du shell, changer la navigation ou produire un dashboard vraiment specifique.

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
- [03. Thèmes avancés](03-advanced-themes.md) (page actuelle)
- [04. Contexte et template tags](04-context-and-template-tags.md)
- [05. Packaging et debug](05-packaging-and-debugging.md)


## Structure attendue

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

Tout est optionnel sauf `theme.json`. Le theme `horizon` dans `src/themes_builtin/horizon/` sert de reference concrete.

## Comment ecrire le style.css

Le moteur injecte votre CSS uniquement quand le theme est actif, mais il ne reecrit pas vos selecteurs. Il faut donc scoper vos regles avec l attribut du theme actif.

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

> Sans prefixe `[data-theme="acme"]`, votre CSS risque soit de ne pas cibler correctement le shell, soit d introduire des collisions avec d autres themes.

## Slots, parts et pages supportes

| Type | Noms supportes | Usage |
| --- | --- | --- |
| Slots | `head`, `sidebar`, `topbar`, `footer` | Remplacer une grande zone du shell. |
| Parts | `sidebar_logo`, `sidebar_nav`, `sidebar_user`, `topbar_title`, `topbar_notifications` | Modifier finement une sous-partie sans recopier tout le slot. |
| Pages | `dashboard`, puis d autres ids reserves par le moteur | Produire un rendu complet pour une page supportee. |

En pratique, le point d extension page actuellement branche par le core est `pages/dashboard.php`. Les autres noms sont prepares au niveau du moteur, mais pas encore tous appeles.

## Contraintes et limites importantes

- `style.css` passe par un filtre de securite et reste limite en taille.

- Seuls certains noms de slots, parts et pages sont autorises.

- Un theme advanced execute du vrai PHP: il faut le traiter comme du code applicatif de confiance.

- Le theme ne peut pas redefinir arbitrairement de nouveaux points d extension si le moteur ne les connait pas deja.

## Lire le contexte et l API de theme

La page suivante decrit ce que le moteur expose aux templates: le tableau `$ctx`, la navigation et les helpers `rui_*`.

[Precedent Themes a variables](02-theme-variables.md) [Suivant Contexte et template tags](04-context-and-template-tags.md)

## Parcours

- Précédent: [02. Thèmes à variables](02-theme-variables.md)
- Suivant: [04. Contexte et template tags](04-context-and-template-tags.md)
