---
title: "Fulgurite developer docs - Themes"
description: "Guide de depart pour developper, structurer et livrer des themes Fulgurite."
lang: "fr"
section: "developer"
---
# Construire des themes complets pour Fulgurite

Cette section documente le moteur de themes du projet du point de vue developpeur. Elle couvre les themes a variables, les themes advanced, le contexte PHP disponible, les template tags, le packaging ZIP et les verifications utiles avant livraison.

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

- [01. Vue d’ensemble développeur](01-index.md) (page actuelle)
- [02. Thèmes à variables](02-theme-variables.md)
- [03. Thèmes avancés](03-advanced-themes.md)
- [04. Contexte et template tags](04-context-and-template-tags.md)
- [05. Packaging et debug](05-packaging-and-debugging.md)


## Choisir entre theme variables et theme advanced

Le moteur supporte deux formats de theme. Le premier se limite a un manifeste JSON avec des variables CSS. Le second ajoute un dossier complet, un `style.css` et des overrides PHP pour le layout ou certaines pages.

| Format | Contenu | Quand le choisir |
| --- | --- | --- |
| **variables** | Un fichier JSON avec `theme.json` ou `<id>.json` selon le mode d installation. | Pour changer la palette, les polices, les rayons et les ombres sans toucher au markup. |
| **advanced** | Un dossier avec `theme.json`, `style.css`, `slots/`, `parts/` et `pages/`. | Pour reorganiser le layout, remplacer des zones de rendu ou personnaliser le dashboard. |

## Commencer simple, complexifier seulement si necessaire

- Fixer d abord la palette et la typographie avec un theme `variables`.

- Passer ensuite a un theme `advanced` si le layout doit vraiment changer.

- Surcharger une part ciblee avant de recopier un slot complet.

- Reserver les pages custom au cas ou le core expose deja un point d extension pour cet ecran.

> Dans le code actuel, seule la surcharge de page `dashboard` est effectivement branchee par le core. Les autres noms de page sont reserves par le moteur, mais pas encore tous appeles.

## Ou regarder dans le projet

Le shell principal est rendu par `public/layout_top.php` et `public/layout_bottom.php`. Le moteur de themes se trouve dans `src/ThemeManager.php`, `src/ThemeRenderer.php`, `src/ThemeTemplateTags.php` et `src/ThemePackage.php`.

Pour des exemples concrets, partez de `src/themes_builtin/dark.json`, `src/themes_builtin/light.json` et surtout `src/themes_builtin/horizon/`, qui montre un theme advanced complet.

## Ce que cette doc vous aide a sortir

Le but n est pas seulement de documenter le format, mais de donner un cadre de travail complet: structure de dossier, variables valides, slots supportes, template tags a privilegier, contraintes de securite du ZIP, checks de debug et checklist de livraison.

## Continuer avec le format le plus simple

La page suivante detaille la structure des themes a variables, les variables supportees et les regles de validation appliquees par le moteur.

[Doc publique Themes et personnalisation](../15-themes-and-customization.md) [Suivant Themes a variables](02-theme-variables.md)

## Parcours

- Suivant: [02. Thèmes à variables](02-theme-variables.md)
