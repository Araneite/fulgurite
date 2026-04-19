---
title: "Documentation Fulgurite — Copy jobs"
description: "Configurer les copy jobs Fulgurite pour répliquer un dépôt vers une autre destination."
lang: "fr"
section: "user"
---
# Répliquer un dépôt vers une seconde cible

La logique est différente d'un backup job: ici, vous ne sauvegardez pas des chemins sources, vous recopiez le contenu d'un dépôt source vers une autre cible Restic. C'est la brique à privilégier pour une duplication, un offsite ou une seconde copie de sécurité.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md)
- [03. Concepts et navigation](03-concepts-and-navigation.md)
- [04. Dépôts Restic](04-repositories.md)
- [05. Jobs de sauvegarde](05-backup-jobs.md)
- [06. Jobs de copie](06-copy-jobs.md) (page actuelle)
- [07. Explorateur et restaurations](07-explorer-and-restores.md)
- [08. Planification et worker](08-scheduler-and-worker.md)
- [09. Monitoring et performance](09-monitoring-and-performance.md)
- [10. Notifications](10-notifications.md)
- [11. Hôtes et SSH](11-hosts-and-ssh.md)
- [12. Sécurité et accès](12-security-and-access.md)
- [13. Référence des paramètres](13-settings-reference.md)
- [14. API et intégrations](14-api-and-integrations.md)
- [15. Thèmes et personnalisation](15-themes-and-customization.md)

### Documentation développeur

- [01. Vue d’ensemble développeur](dev/01-index.md)
- [02. Thèmes à variables](dev/02-theme-variables.md)
- [03. Thèmes avancés](dev/03-advanced-themes.md)
- [04. Contexte et template tags](dev/04-context-and-template-tags.md)
- [05. Packaging et debug](dev/05-packaging-and-debugging.md)


## Quand utiliser un copy job

Le copy job est adapté aux scénarios suivants.

- Vous voulez une **seconde cible** pour un même dépôt source sans redéfinir des chemins sources.

- Vous voulez une **réplique offsite** ou un autre backend de stockage.

- Vous avez déjà des snapshots dans un dépôt principal et vous voulez les recopier vers une autre destination sans refaire le backup original.

## Ce que le formulaire permet de régler

| Champ | Rôle | Remarque |
| --- | --- | --- |
| **Nom** | Identifie la tâche de copie. | Choisissez un nom qui décrit la destination ou l'objectif de reprise. |
| **Dépôt source** | Dépôt Restic déjà connu de l'application. | Le job de copie ne crée pas le dépôt source, il le consomme. |
| **Destination** | Chemin ou backend cible. | Peut être un chemin classique ou un backend `rclone:...`. |
| **Description** | Contexte métier ou technique. | Utile pour distinguer une copie locale, cloud ou de reprise d'activité. |
| **Planification / notifications / retry** | Même logique générale que pour les backup jobs. | Le copy job a sa propre autonomie de planification et d'alerte. |

## Destinations supportées et gestion du mot de passe

Le copy job gère aussi bien une destination classique qu'une destination pilotée via rclone.

### Destination classique

- Vous fournissez un chemin de dépôt de destination.

- Le mot de passe de cette destination peut être stocké en fichier local ou résolu via Infisical.

- Le job gère son propre secret, indépendamment de celui du dépôt source.

### Destination `rclone:...`

- Les credentials du stockage distant sont alors portés par la configuration rclone du serveur.

- Aucune clé SSH n'est nécessaire dans Fulgurite pour ce mode.

- Le mot de passe Restic de la destination reste en revanche nécessaire.

Le champ `dest_password_source` peut prendre deux valeurs: `file`(le mot de passe est stocké dans un fichier local géré par Fulgurite) ou `infisical`(le mot de passe est résolu dynamiquement à chaque exécution). Dans ce second cas, le champ `dest_infisical_secret_name` correspond au path ou au nom du secret à résoudre au moment du run.

> Comme pour les dépôts et certains backup jobs, si vous choisissez Infisical et que l'instance n'est pas configurée ou que le secret est introuvable, l'enregistrement du job est refusé.

> Si la résolution Infisical échoue au moment du run (secret introuvable, token expiré, instance injoignable), le copy job est marqué en échec et une notification est émise selon sa politique de notification.

## Planification, lancement manuel et logs

Le copy job suit la même philosophie opérationnelle qu'un backup job: il peut être lancé à la main ou sélectionné par le cron central selon son planning.

- Le tableau affiche la source, la destination, le résumé de planning, la politique de notification et le résumé de retry.

- Le dernier run conserve son statut, sa date et son output.

- Les exécutions alimentent `cron_log` avec `job_type = 'copy'`.

- Le bouton **Exécuter maintenant** du panneau cron permet aussi de forcer un cycle global et d'observer le comportement du scheduler.

Comme pour les backup jobs, Fulgurite distingue deux mécanismes de notification qui peuvent coexister: le champ legacy `notify_on_failure`(booléen simple, conservé pour compatibilité) et le champ `notification_policy`(structure JSON décrivant précisément canaux, événements et seuils). La politique JSON est prioritaire dès qu'elle est renseignée; le drapeau legacy n'est consulté qu'en l'absence de politique structurée.

> Un copy job exécute `restic copy`— il réplique les snapshots de la source vers la destination, mais il ne lance **ni `forget` ni `prune`**. La rétention du dépôt de destination n'est pas gérée par le copy job. Pour appliquer une politique de rétention sur la destination, configurez un backup job dédié pointant vers ce dépôt, ou déclenchez une tâche de maintenance manuellement. Ce comportement est intentionnel: le copy job est une opération de *réplication pure*— les décisions de rétention sont découplées.

## Différences avec un backup job

Les deux modules se ressemblent visuellement, mais n'ont pas le même rôle.

| Aspect | Backup job | Copy job |
| --- | --- | --- |
| **Source** | Chemins de fichiers ou dossiers à sauvegarder. | Dépôt Restic déjà existant. |
| **Cible** | Dépôt principal recevant les snapshots. | Autre dépôt recevant une copie du dépôt source. |
| **Options avancées** | Tags, exclusions, hooks, rétention, hôte distant. | Destination, secret cible, planning, notifications et retry. |
| **Usage type** | Produire les snapshots. | Dupliquer ou déplacer la valeur de ces snapshots vers une autre cible. |

## Et pour retrouver les données?

Le chapitre suivant détaille l'explorateur de snapshots, les téléchargements, les comparaisons, les rétentions de dépôt et les restaurations complètes ou partielles.

[Précédent Backup jobs](05-backup-jobs.md) [Suivant Explorateur et restaurations](07-explorer-and-restores.md)

## Parcours

- Précédent: [05. Jobs de sauvegarde](05-backup-jobs.md)
- Suivant: [07. Explorateur et restaurations](07-explorer-and-restores.md)
