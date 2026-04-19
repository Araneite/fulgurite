---
title: "Documentation Fulgurite — Planification et worker"
description: "Comprendre le cron central, les tâches globales, le worker dédié et la file de jobs de Fulgurite."
lang: "fr"
section: "user"
---
# Le cron central et le worker de file interne

La page Planification présente le moteur central de Fulgurite. C'est ici que vous installez la ligne cron unique, que vous pilotez les tâches globales et que vous observez les prochains passages. La page Performance complète cette vue avec la gestion du worker dédié et de la file de jobs.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md)
- [03. Concepts et navigation](03-concepts-and-navigation.md)
- [04. Dépôts Restic](04-repositories.md)
- [05. Jobs de sauvegarde](05-backup-jobs.md)
- [06. Jobs de copie](06-copy-jobs.md)
- [07. Explorateur et restaurations](07-explorer-and-restores.md)
- [08. Planification et worker](08-scheduler-and-worker.md) (page actuelle)
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


## Le cron central

Le produit repose sur une entrée cron unique, et non sur une ligne cron par job. Cette architecture simplifie la maintenance et garde le pilotage dans l'application.

- La page affiche l'état du moteur cron.

- Elle fournit une ligne cron ou une commande d'installation prête à copier.

- Elle peut proposer des actions d'activation ou de désactivation si l'environnement le supporte.

- Un bouton **Exécuter maintenant** permet de lancer un cycle à la demande.

- `public/cron.php` est **CLI-only**: l'entrée HTTP legacy `cron.php?token=...` est désactivée.

- Pour un diagnostic rapide en ligne de commande: `FULGURITE_CRON_MODE=quick php public/cron.php`.

Pour éviter les exécutions concurrentes (deux déclenchements cron qui se chevauchent, une relance manuelle pendant un cycle en cours), le cron central s'appuie sur le *lock file*exposé par le scheduler: `sys_get_temp_dir()/fulgurite-cron.lock` en pratique, selon le répertoire temporaire de l'environnement. Il est posé via `flock`. Tant que ce verrou est tenu, un nouvel appel du cron central s'interrompt immédiatement sans rien lancer: seul le cycle déjà en cours continue.

> Le code distingue explicitement le fuseau horaire du scheduler et celui du serveur. Cela évite de planifier à une heure pensée « locale » tout en exécutant dans un autre contexte temporel.

## Les tâches globales pilotées par le scheduler

En plus des backup jobs et copy jobs, le cron central pilote plusieurs tâches applicatives globales. Elles s'exécutent à l'intérieur du cycle cron comme des jobs normaux: mêmes verrous, même journalisation, même circuit de notification.

| Tâche | Identifiant | But | Ce que vous réglez |
| --- | --- | --- | --- |
| **Rapport hebdomadaire** | `weekly_report` | Produire une vue synthétique périodique de l'activité ou de l'état. | Activation, jour, heure et politique de notification. |
| **Vérification d'intégrité** | `integrity_check` | Vérifier l'intégrité des dépôts. | Activation, jours d'exécution, heure et notifications. |
| **Maintenance vacuum** | `maintenance_vacuum` | Entretenir les bases SQLite et certaines structures internes. | Activation, jours, heure et notifications. |
| **Nettoyage de rétention** | `retention_cleanup` | Appliquer les politiques de rétention sur les historiques internes (logs, notifications, runs). | Activation, fréquence et fenêtre d'exécution. |

## Comment les jobs planifiés sont choisis

Les backup jobs et copy jobs activés sont filtrés par le scheduler selon leurs jours et heures de planification, puis dédupliqués pour ne pas repartir deux fois sur le même créneau.

- Le calcul se fait sur le fuseau horaire du scheduler, pas seulement sur l'heure du serveur.

- Un job n'est pas relancé dans la même **fenêtre minute** que son dernier déclenchement: le scheduler compare le moment courant avec la valeur `last_run_at` tronquée à la minute. Cela garantit qu'un cycle cron déclenché plusieurs fois à l'intérieur d'une même minute (manuellement, ou via un cron externe mal configuré) ne relancera pas le job.

- La page Planification montre des synthèses sur les backup jobs et les copy jobs actuellement planifiés.

## Le worker dédié

Le worker dédié est visible depuis la page Performance. Il peut être lancé, arrêté, relancé ou forcé à traiter immédiatement la file.

- Nom par défaut du worker.

- `sleep` entre deux boucles.

- Nombre de jobs traités par boucle.

- État systemd si le worker est supervisé comme service.

- Commande CLI équivalente: `php public/worker.php --name=... --sleep=... --limit=...`.

Fulgurite utilise deux notions distinctes de « stale » pour détecter un worker défaillant:

- **`worker_stale_minutes`**: durée d'inactivité au-delà de laquelle le worker est considéré comme mort (plus aucune activité mesurable, quel que soit son statut). C'est le verdict « le processus n'existe plus ».

- **`worker_heartbeat_stale_seconds`**: seuil plus court portant uniquement sur le dernier heartbeat émis. Un worker qui ne publie plus son pouls est marqué comme suspect avant même que sa fenêtre d'inactivité longue ne soit atteinte.

Quand un worker est marqué comme stale, les jobs `enqueued` qu'il tenait sont libérés et réassignés au prochain worker qui les récupère au début de sa boucle: aucune opération n'est perdue, elle est simplement reprise par un autre traitement.

## La file de jobs et son rôle

La page Performance expose aussi une vue de la file interne. Elle aide à comprendre si des tâches s'accumulent, échouent ou restent en attente.

- Vue de synthèse: nombre de jobs, priorité maximale en attente, prochaine disponibilité.

- Liste des jobs récents avec statut, priorité, nombre de tentatives et dernier message d'erreur.

- Statuts typiques: `queued`, `running`, `completed`, `failed`, `dead_letter`.

## Que vérifier quand « rien ne tourne »

Les problèmes de planification viennent souvent de quelques causes récurrentes.

1. **Vérifier que le cron central est installé et sain.**

2. **Vérifier le fuseau horaire de planification.** Un job peut être « bien configuré » mais au mauvais créneau selon le scheduler.

3. **Vérifier le dernier run du job.** Le dédoublonnage à la minute peut donner l'impression d'une non-exécution si vous testez dans la même fenêtre que le dernier déclenchement.

4. **Vérifier le verrou `sys_get_temp_dir()/fulgurite-cron.lock`.** Son répertoire exact dépend de l'environnement. Un verrou orphelin laissé par un processus tué brutalement peut bloquer tous les cycles suivants.

5. **Vérifier le worker et la file.** Certaines opérations de fond peuvent attendre un traitement qui ne vient pas.

6. **Consulter le cron log et les logs d'activité.**

## Observer l'instance en continu

Le chapitre suivant couvre le dashboard, les statistiques, les logs, la page performance et les indicateurs de santé exposés à l'opérateur.

[Précédent Explorateur et restaurations](07-explorer-and-restores.md) [Suivant Suivi et performance](09-monitoring-and-performance.md)

## Parcours

- Précédent: [07. Explorateur et restaurations](07-explorer-and-restores.md)
- Suivant: [09. Monitoring et performance](09-monitoring-and-performance.md)
