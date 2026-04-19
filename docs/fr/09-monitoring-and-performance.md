---
title: "Documentation Fulgurite — Suivi et performance"
description: "Dashboard, statistiques, logs, page performance, caches, index, queue et métriques runtime."
lang: "fr"
section: "user"
---
# Surveiller la santé de l'instance et des dépôts

Le produit dispose de plusieurs vues de supervision. Elles vont du résumé très opérationnel du dashboard jusqu'aux informations plus techniques sur SQLite, les caches, la queue, le worker et certaines métriques système comme la mémoire ou la pression I/O.

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
- [09. Monitoring et performance](09-monitoring-and-performance.md) (page actuelle)
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


## Dashboard

Le dashboard donne une vue synthétique de l'instance, avec chargement dynamique et rafraîchissement périodique selon le paramètre de l'interface.

- Total de snapshots et nombre de dépôts en état OK ou en alerte.

- Statuts runtime des dépôts (voir détail ci-dessous).

- Graphique d'évolution des snapshots.

- Activité récente issue des logs d'activité.

- Raccourcis vers les zones les plus actives de l'application.

Les états runtime d'un dépôt, exposés dans le dashboard et sur la page Dépôts, ont une signification précise:

| État | Signification |
| --- | --- |
| `ok` | Le dépôt est joignable, le dernier run s'est déroulé correctement et l'âge du dernier snapshot est sous le seuil configuré. |
| `warning` | L'âge du dernier snapshot dépasse le seuil: le dépôt n'est pas forcément en panne, mais il vieillit. |
| `error` | Le dernier run a échoué ou le dépôt est injoignable (backend distant KO, credentials invalides, chemin manquant). |
| `no_snap` | Le dépôt est correctement initialisé mais ne contient encore aucun snapshot. |
| `pending` | Une analyse est en cours (init, scan, indexation): l'état final n'est pas encore arbitré. |

Le graphique d'évolution des snapshots utilise une granularité journalière sur la période sélectionnée: chaque point représente l'état cumulé au jour considéré.

## Statistiques

La page Stats expose des graphiques et des agrégats sur plusieurs périodes.

- Périodes usuelles de consultation: 7, 14, 30 ou 90 jours selon la configuration d'interface.

- Graphique d'évolution des snapshots, à granularité journalière sur la période sélectionnée.

- Graphique d'activité sous forme de barres.

- Résumé utile pour suivre les volumes et l'intensité d'usage.

## Logs d'activité et export CSV

La page Logs offre une lecture exploitable des actions utilisateur et système, avec filtres et export.

- Filtres par utilisateur, action, sévérité, IP et plage de dates.

- Export CSV direct des lignes visibles.

- Badges de sévérité distincts pour info, warning et critical.

- Complément utile aux logs cron et aux journaux plus techniques du worker.

## Page performance

La page performance n'est pas uniquement une page « serveur ». Elle combine plusieurs indicateurs applicatifs et système.

- État du worker dédié et ses derniers heartbeats.

- Résumé de la file interne et liste des jobs récents.

- Statistiques sur l'index snapshots et les tables associées à la navigation et à la recherche.

- Informations sur les bases SQLite, les caches, les archives et les tailles de dépôts.

- Métriques système comme la mémoire, la pression I/O et, selon le contexte, des informations PHP-FPM.

## Index, caches et seuils de lenteur

L'application expose déjà plusieurs structures d'optimisation et leurs réglages.

- Index de navigation et index de recherche séparés pour les snapshots.

- Caches Restic dédiés à la vue, la recherche, l'arbre, les snapshots et les statistiques.

- Seuils de lenteur pour les requêtes HTTP, le SQL, Restic et certaines commandes journalisées.

- Paramètres de rafraîchissement de la page performance et de son cache de métriques.

Les seuils de lenteur sont configurables dans **Paramètres → Données**. Au-delà de ces seuils, l'opération correspondante est marquée comme lente dans les logs d'activité et dans les métriques runtime, ce qui permet d'isoler rapidement les points de contention. Les clés usuelles sont:

- `performance_slow_request_threshold_ms`— seuil au-delà duquel une requête HTTP applicative est considérée comme lente.

- `performance_slow_sql_threshold_ms`— seuil pour les requêtes SQL exécutées sur SQLite.

- `performance_slow_restic_threshold_ms`— seuil pour les commandes Restic (list, cat, stats, diff, etc.).

- `performance_slow_command_threshold_ms`— seuil générique pour les commandes système journalisées (rsync, scp, rclone, hooks…).

Les noms exacts peuvent évoluer au fil des versions: dans le doute, reportez-vous à la page [Référence des paramètres](13-settings-reference.md).

> Le fait que l'application expose ces structures dans l'UI est très utile pour dépanner des problèmes de recherche ou d'exploration qui ne sont pas forcément liés à Restic lui-même, mais plutôt à l'indexation ou à la couche cache.

## Surveillance de l'espace disque

Le service `DiskSpaceMonitor` surveille en continu l'espace disponible sur les points de montage des dépôts restic locaux et sur la racine `/` de chaque hôte SSH distant (sondée via `df -Pk`).

Chaque sonde retourne l'un des états suivants:

| État | Signification |
| --- | --- |
| `ok` | L'espace disponible est au-dessus des deux seuils configurés. |
| `avertissement` | L'espace disponible est sous le seuil d'avertissement mais au-dessus du seuil critique. |
| `critique` | L'espace disponible est sous le seuil critique: les jobs sont bloqués (voir ci-dessous). |
| `erreur` | La sonde a échoué: hôte SSH injoignable, commande en timeout, etc. |
| `inconnu` | Aucune donnée n'a encore été collectée pour ce point de montage. |

Les seuils d'avertissement et critique sont configurables dans **Paramètres → Surveillance**.

- **Modèle de prévision**: avec au moins 30 jours de données (minimum 3 points par jour), le système projette le nombre de jours avant saturation. Cette projection est affichée à côté de l'état courant.

- **Contrôles preflight**: avant chaque sauvegarde, copie ou restauration, l'espace disque est vérifié. Si le seuil critique est dépassé, le job est bloqué avec une erreur explicite avant même de lancer restic.

- **Sonde manuelle**: depuis la page d'un dépôt ou du tableau de bord, un bouton permet de déclencher une vérification immédiate sans attendre le passage du planificateur.

Les données de surveillance de l'espace disque sont visibles sur plusieurs surfaces de l'interface: widget sur le tableau de bord, onglet Statistiques, pages des dépôts et pages des hôtes SSH distants.

Les événements d'espace disque (`avertissement`, `critique`, `rétabli`) peuvent être acheminés via le profil de notification `disk_space`.

## Santé du broker de secrets

La page Performance affiche une carte dédiée au broker de secrets avec son état en temps réel. Trois états sont possibles: `ok`, `dégradé`, `hors service`.

Le bouton **Analyser** déclenche une vérification fraîche via `BrokerHealthAnalyzer`: connectivité, temps de réponse, type de backend, et nombre de secrets disponibles sont contrôlés à la demande, sans attendre le prochain cycle automatique.

- **Protection anti-flapping**: des délais de grâce et une logique de retry empêchent les erreurs transitoires de générer de fausses alarmes.

- **Configuration HA**: plusieurs endpoints peuvent être déclarés dans `FULGURITE_SECRET_BROKER_ENDPOINTS`. Si un nœud est injoignable, le système bascule automatiquement sur le suivant — chaque endpoint défaillant est bypassé pendant 10 secondes puis re-testé.

- **Notifications**: les événements broker (`dégradé`, `basculement`, `hors service`, `rétabli`) peuvent être suivis via le profil de notification `secret_broker`.

- **Journaux secrets**: la page **Journaux secrets** expose les accès SecretStore, les événements de failover, les événements de nœud/cluster et un export CSV. Elle nécessite `settings.manage`.

> Si le broker n'est pas configuré ou indisponible, la page de journaux peut afficher une erreur de broker et limiter les données consultables. La page Performance reste le point de diagnostic rapide de l'état courant du cluster.

## Compléter la supervision

Les pages de suivi montrent l'état de l'instance. Le chapitre suivant couvre la réaction à cet état: notifications internes, canaux externes, profils d'événements et politiques d'alerte.

[Précédent Planification et worker](08-scheduler-and-worker.md) [Suivant Notifications](10-notifications.md)

## Parcours

- Précédent: [08. Planification et worker](08-scheduler-and-worker.md)
- Suivant: [10. Notifications](10-notifications.md)
