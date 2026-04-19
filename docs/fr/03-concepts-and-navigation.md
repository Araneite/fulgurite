---
title: "Documentation Fulgurite — Concepts et navigation"
description: "Comprendre les concepts clés de Fulgurite: dépôt, snapshot, jobs, restaurations, scheduler, worker, scopes et navigation."
lang: "fr"
section: "user"
---
# Concepts et navigation

Quand on ouvre l'application pour la première fois, la richesse fonctionnelle peut donner l'impression d'un outillage très large. En pratique, tout s'organise autour de quelques objets stables et d'un cycle de vie simple.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md)
- [03. Concepts et navigation](03-concepts-and-navigation.md) (page actuelle)
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
- [15. Thèmes et personnalisation](15-themes-and-customization.md)

### Documentation développeur

- [01. Vue d’ensemble développeur](dev/01-index.md)
- [02. Thèmes à variables](dev/02-theme-variables.md)
- [03. Thèmes avancés](dev/03-advanced-themes.md)
- [04. Contexte et template tags](dev/04-context-and-template-tags.md)
- [05. Packaging et debug](dev/05-packaging-and-debugging.md)


## Les objets fondamentaux de l'application

Presque tout ce que vous manipulez dans Fulgurite peut être rattaché à l'une des briques ci-dessous.

### Dépôt

Backend Restic connu par l'application, avec son chemin, son secret, son seuil d'alerte et sa politique de notification.

### Backup job

Tâche qui décrit quoi sauvegarder, vers quel dépôt et avec quelles options: horaires, rétention, hooks, retries, notifications et exécution locale ou distante.

### Copy job

Tâche de réplication via `restic copy` vers une autre destination, utile pour l'offsite ou une deuxième cible.

### Snapshot

État sauvegardé dans un dépôt. Il peut être exploré, comparé, téléchargé, retagué, supprimé ou restauré.

### Restauration

Opération de récupération locale ou distante, complète ou partielle, historisée avec statut, destination, logs et auteur.

### Scheduler et worker

Le scheduler décide quoi lancer à chaque passage du cron central. Le worker traite la file interne et certaines tâches de fond.

## Carte de navigation de l'interface

Le menu principal se lit en trois couches: pilotage, exploitation des sauvegardes et administration transverse.

### Pilotage quotidien

- **Dashboard**: résumé global et activité récente.

- **Notifications**: centre in-app et suivi des alertes visibles.

- **Stats / Logs / Performance**: vues de diagnostic et d'audit.

### Métier backup

- **Dépôts**, **Backup jobs** et **Copy jobs**.

- **Explorateur** pour naviguer dans les snapshots.

- **Restaurations** pour l'historique des récupérations.

### Administration transverse

- **Planification** pour le cron central.

- **Hôtes / SSH** pour la connectivité distante.

- **Utilisateurs / Thèmes / Paramètres / API** pour l'admin globale.

## Cycle de vie d'une sauvegarde dans Fulgurite

Comprendre ce cycle rend beaucoup plus lisibles les décisions d'architecture du produit.

1. **Création d'un dépôt.** Vous enregistrez un backend Restic avec son secret.

2. **Création d'un job.** Vous décrivez les sources, la cible, les options de rétention, les notifications et la politique de retry.

3. **Déclenchement.** Le job part manuellement ou via le cron central selon le fuseau horaire de planification configuré.

4. **Production d'un snapshot.** Le dépôt s'enrichit, l'application rafraîchit ses indexes et son statut runtime.

5. **Consultation.** L'utilisateur explore le snapshot, recherche un fichier ou compare deux versions.

6. **Restauration.** Il restaure tout ou partie du snapshot en local ou à distance, puis l'opération est ajoutée à l'historique.

> Une rétention configurée au niveau d'un backup job est appliquée **après une sauvegarde réussie**. Une rétention de dépôt peut aussi être lancée depuis l'explorateur avec un mode simulation.

## Rôles, permissions et scopes

Fulgurite combine une logique de rôle, une matrice de permissions et des périmètres de visibilité plus fins sur les dépôts et les hôtes.

| Niveau | Ce qu'il porte | Exemples |
| --- | --- | --- |
| **Rôle** | Position hiérarchique du compte. Les rôles système sont comparés par niveau. | viewer, operator, restore-operator, admin |
| **Permission** | Droits applicatifs précis utilisés pour afficher ou autoriser les actions. | `repos.manage`, `restore.run`, `scheduler.manage` |
| **Scope repo / host** | Restriction de visibilité sur un sous-ensemble de dépôts ou d'hôtes. | Un opérateur peut gérer seulement certains dépôts. |

> Les scopes `repo_scope` et `host_scope` filtrent **silencieusement** les ressources visibles. Un utilisateur scopé ne voit ni dans l'interface, ni via l'API, les dépôts ou les hôtes qui sortent de son périmètre: l'application n'affiche pas d'erreur « accès refusé », ces ressources sont simplement absentes de la liste qui lui est renvoyée. C'est une conception voulue, utile pour cloisonner des équipes sur une même instance.

## Glossaire rapide

### Cron central

Entrée cron système unique qui appelle le moteur de planification. Il n'existe pas une ligne cron par job.

### Worker dédié

Processus CLI chargé du traitement de la file interne, avec heartbeat, détection des workers obsolètes et gestion systemd possible.

### Notification policy

Configuration déclarative des canaux à utiliser pour un profil d'événement donné, avec héritage possible.

### Retry policy

Stratégie de reprise d'un job en cas d'erreur, décidée à partir d'une classification des sorties et codes de retour.

## Passer au concret

Le chapitre suivant entre dans la première brique métier du produit: le dépôt Restic, son secret, son test d'accès, son seuil d'alerte et ses actions de maintenance.

[Précédent Installation et prise en main](02-getting-started.md) [Suivant Dépôts Restic](04-repositories.md)

## Parcours

- Précédent: [02. Installation et prise en main](02-getting-started.md)
- Suivant: [04. Dépôts Restic](04-repositories.md)
