---
title: "Documentation Fulgurite — Thèmes et personnalisation"
description: "Thèmes installés, store, demandes utilisateurs et options de personnalisation de l'interface."
lang: "fr"
section: "user"
---
# Personnaliser l'apparence de l'instance

Le système de thèmes est déjà organisé autour de plusieurs onglets: thèmes installés, store, demandes admin et demandes personnelles. Il permet un niveau de personnalisation léger ou avancé, selon votre appétence pour le code et le niveau de confiance dans les packages installés.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md)
- [03. Concepts et navigation](03-concepts-and-navigation.md)
- [04. Dépôts Restic](04-repositories.md)
- [05. Jobs de sauvegarde](05-backup-jobs.md)
- [06. Jobs de copie](06-copy-jobs.md)
- [07. Explorateur et restaurations](07-explorer-and-restores.md)
- [08. Planification et worker](08-scheduler-and-worker.md)
- [09. Monitoring et performance](09-monitoring-and-performance.md)
- [10. Notifications](10-notifications.md)
- [11. Hôtes et SSH](11-hosts-and-ssh.md)
- [12. Sécurité et accès](12-security-and-access.md)
- [13. Référence des paramètres](13-settings-reference.md)
- [14. API et intégrations](14-api-and-integrations.md)
- [15. Thèmes et personnalisation](15-themes-and-customization.md) (page actuelle)

### Documentation développeur

- [01. Vue d’ensemble développeur](dev/01-index.md)
- [02. Thèmes à variables](dev/02-theme-variables.md)
- [03. Thèmes avancés](dev/03-advanced-themes.md)
- [04. Contexte et template tags](dev/04-context-and-template-tags.md)
- [05. Packaging et debug](dev/05-packaging-and-debugging.md)


## Thèmes variables et thèmes advanced

Un thème personnalise les couleurs, la typographie et, selon le niveau, le logo et certaines zones du rendu. Les thèmes intégrés au produit sont stockés dans `src/themes_builtin/`, tandis que les thèmes personnalisés importés atterrissent dans `data/themes/`.

> Vous souhaitez creer, packager ou maintenir un theme complet? La documentation developpeur est disponible dans [/docs/dev](dev/01-index.md).

| Type | Contenu | Usage |
| --- | --- | --- |
| **Variables** | Thème JSON basé sur des variables de style. | Le plus simple et le plus sûr pour changer les couleurs, la typographie et l'identité visuelle. |
| **Advanced** | Package zip pouvant contenir `theme.json`, `style.css`, `slots/`, `parts/`, `pages/` et du PHP. | Pour une personnalisation profonde, voire un remplacement de certains rendus. |

## Méthodes d'installation disponibles

- Upload d'un thème JSON variables-only.

- Upload d'un package advanced en `.zip`.

- Installation depuis une URL ou un dépôt GitHub converti en archive.

- Installation depuis le store intégré.

- Suppression d'un thème non builtin.

> L'upload d'un thème personnalisé au format `.zip` nécessite l'extension PHP `zip` active sur le serveur. Si l'extension est absente, seuls les thèmes JSON variables-only peuvent être importés.

## Store et catalogue étendu

Le store sert de vitrine à des thèmes jugés de confiance et installables rapidement. Son catalogue peut être enrichi localement via `data/theme_store_extra.json` au même format.

## Demandes de thèmes et modération

Sur une instance partagée, les utilisateurs peuvent soumettre des thèmes sans disposer directement des droits d'installation.

- Soumission par upload ou URL.

- File de demandes pour les administrateurs.

- Téléchargement de l'archive et relecture avant approbation.

- Approbation, rejet, notes et suivi de l'état côté utilisateur dans **Mes demandes**.

## Ce qu'il faut traiter comme du code exécutable

Les thèmes advanced peuvent contenir du PHP. Ils doivent donc être évalués comme du code applicatif exécuté sur votre serveur, et non comme une simple ressource cosmétique.

> Sur un serveur de production, ne validez que des packages lus, audités et provenant d'une source de confiance. Le workflow de demandes existe justement pour introduire une phase de revue.

## Poursuivre vers la référence des paramètres

Le chapitre suivant détaille les groupes de configuration globaux de l'application: interface, sécurité, scheduler, exploration, intégrations, API et plus encore.

[Précédent Utilisateurs et sécurité](12-security-and-access.md) [Suivant Référence des paramètres](13-settings-reference.md)

## Parcours

- Précédent: [14. API et intégrations](14-api-and-integrations.md)
