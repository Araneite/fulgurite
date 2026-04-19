---
title: "Documentation Fulgurite — Référence fonctionnelle des paramètres"
description: "Référence fonctionnelle des paramètres principaux Fulgurite: interface, sécurité, backup, scheduler, exploration, performance, intégrations et API."
lang: "fr"
section: "user"
---
# Référence fonctionnelle des paramètres principaux

La page Paramètres est organisée en onglets fonctionnels. Cette référence regroupe les paramètres exposés ou structurants pour l'exploitation: interface, sécurité, valeurs par défaut des sauvegardes, cron central, worker, exploration, indexation, intégrations externes, rôles et API publique. Elle ne prétend pas lister chaque clé interne définie dans `AppConfig::defaultSettings()`.

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
- [13. Référence des paramètres](13-settings-reference.md) (page actuelle)
- [14. API et intégrations](14-api-and-integrations.md)
- [15. Thèmes et personnalisation](15-themes-and-customization.md)

### Documentation développeur

- [01. Vue d’ensemble développeur](dev/01-index.md)
- [02. Thèmes à variables](dev/02-theme-variables.md)
- [03. Thèmes avancés](dev/03-advanced-themes.md)
- [04. Contexte et template tags](dev/04-context-and-template-tags.md)
- [05. Packaging et debug](dev/05-packaging-and-debugging.md)


## Interface et notifications globales

- Nom de l'application, sous-titre, lettre/logo et tagline de connexion.

- Fuseau horaire: soit celui du serveur, soit un fuseau personnalisé.

- Intervalle de rafraîchissement du dashboard.

- Période par défaut des statistiques.

- Configuration mail et SMTP.

| Paramètre | Valeur par défaut | Rôle |
| --- | --- | --- |
| `interface_timezone` | `server` | Fuseau utilisé pour l'affichage et la planification, ou fuseau serveur si la valeur reste `server`. |
| `interface_dashboard_refresh_seconds` | 60 secondes | Intervalle d'auto-refresh de la page d'accueil. |
| `interface_stats_default_period_days` | 7 jours | Période par défaut utilisée dans les graphiques. |

| Onglet | Sous-sections vues dans l'UI | But |
| --- | --- | --- |
| **Général** | Interface, Notifications | Branding, fuseau, rafraîchissement, SMTP et canaux globaux. |
| **Accès** | Security, WebAuth, Audit, Roles | Sessions, 2FA, passkeys, rétention des journaux et hiérarchie RBAC. |
| **Backup** | Backup, Restore | Valeurs par défaut des jobs et comportements de restauration. |
| **Automation** | Scheduler, Worker | Cron central, tâches globales et traitement de fond. |
| **Données** | Exploration, Indexation, Performance | Caches, pagination, recherche, index et seuils de lenteur. |
| **Intégrations / API** | Intégrations, API publique | Secrets externes, canaux tiers, CORS, tokens et limites API. |

## Sécurité, WebAuth, audit et rôles

- Durée de session absolue, inactivité, délai d'avertissement et fréquence de synchronisation en base.

- Fingerprint strict de session.

- Âge maximum de ré-authentification et TTL du second facteur en attente.

- Nombre maximum de sessions par utilisateur.

- Force du 2FA pour les administrateurs et notifications de login.

- RP name, RP ID, timeouts, user verification, resident keys et attestation pour WebAuthn.

- Rétentions d'audit pour activité, restaurations, cron, logins, rate limits, queue et archives.

- Édition de la hiérarchie des rôles et de leurs badges.

| Paramètre | Valeur par défaut | Rôle |
| --- | --- | --- |
| `session_absolute_lifetime_minutes` | 480 minutes | Durée de vie absolue d'une session. |
| `reauth_max_age_seconds` | 300 secondes | Âge maximum avant qu'une action sensible n'exige une nouvelle saisie du mot de passe ou d'un facteur. |
| `session_strict_fingerprint` | Activé | Invalide la session si l'empreinte stricte ne correspond plus. |
| `max_sessions_per_user` | 5 | Nombre maximum de sessions simultanées par compte. |
| `app_notifications_retention_days` | 7 jours | Durée de rétention des notifications internes. |

## Backup, restauration et comportements par défaut

- Seuil global d'alerte des dépôts.

- Valeurs par défaut de planification des backup jobs: actif ou non, heure, jours, notification en cas d'échec.

- Valeurs par défaut de rétention: activation, `keep_last/daily/weekly/monthly/yearly` et prune.

- Politique de retry globale de référence.

- Chemins par défaut des restaurations locales, distantes et partielles.

- Taille de page de l'historique des restaurations.

## Monitoring de l'espace disque

Le service **DiskSpaceMonitor** surveille en continu l'espace disponible sur les montages concernés par vos sauvegardes. Il s'exécute comme tâche planifiée globale et sonde périodiquement les espaces disques configurés.

- **Ce qui est surveillé:** les points de montage des dépôts Restic locaux, ainsi que la racine `/` de chaque hôte SSH distant.

- **Sonde distante:** réalisée via la commande SSH `df -Pk` exécutée sur l'hôte cible.

- **Stockage des données:** historique ponctuel dans la table `disk_space_checks`, dernier état connu dans `disk_space_runtime_status`.

- **Modèle de prévision:** utilise 30 jours d'historique (nécessite au moins 3 points de données quotidiens) pour projeter le nombre de jours avant saturation.

- **Contrôles preflight:** avant chaque job (backup, copie, restauration), l'espace disque est vérifié — si le seuil critique est dépassé, le job est bloqué avec une erreur explicite.

- **Niveaux de sévérité:** `ok`/`warning`/`critical`— seuils configurables en pourcentage d'utilisation. `error` indique un échec de la sonde elle-même (hôte SSH inaccessible, erreur de commande). `unknown` indique qu'aucune donnée n'a encore été collectée.

- **Sonde manuelle:** déclenchable depuis la page d'un dépôt ou d'un hôte, ou via le script `probe_disk_space.php`.

- **Surfaces UI:** widget du dashboard, page des statistiques, pages individuelles des dépôts et hôtes.

| Paramètre | Valeur par défaut | Rôle |
| --- | --- | --- |
| `disk_monitoring_enabled` | Activé | Active ou désactive globalement le service de surveillance. |
| `disk_local_warning_percent` | 80% | Pourcentage d'utilisation locale à partir duquel le statut passe à `warning`. |
| `disk_local_critical_percent` | 90% | Pourcentage d'utilisation locale à partir duquel le statut passe à `critical` et les jobs sont bloqués. |
| `disk_remote_warning_percent` | 80% | Pourcentage d'utilisation distante à partir duquel le statut passe à `warning`. |
| `disk_remote_critical_percent` | 90% | Pourcentage d'utilisation distante à partir duquel le statut passe à `critical`. |
| `disk_preflight_enabled` | Activé | Active les contrôles d'espace disque avant les jobs. |
| `disk_monitor_history_retention_days` | 30 jours | Durée de conservation de l'historique des sondes. |

> Le modèle de prévision nécessite au minimum 3 points de données quotidiens sur 30 jours pour être fiable. En dessous de ce seuil, la prévision est affichée comme indisponible.

> La santé du broker HA est également exposée sous forme de notifications — voir le profil de notification `secret_broker`.

## Scheduler, cron central et worker

- Cron central en mode CLI-only (l'entrée HTTP legacy `cron.php?token=...` est désactivée).

- Modes d'exécution (`manual`, `diagnostic`, `quick`) pilotés par `FULGURITE_CRON_MODE`.

- Rapport hebdomadaire, integrity check et maintenance vacuum avec jours, heures et notifications.

- Nom par défaut du worker, sleep, jobs par boucle, stale minutes et heartbeat stale seconds.

| Paramètre | Valeur par défaut | Rôle |
| --- | --- | --- |
| `worker_stale_minutes` | 30 minutes | Délai après lequel un worker inactif est considéré comme mort et peut être relancé. |
| `worker_heartbeat_stale_seconds` | 20 secondes | Tolérance du heartbeat avant alerte. |
| `worker_limit` | 3 | Nombre maximum de jobs traités par itération de boucle. |

### Assistant de reconfiguration (Resetup Wizard)

Le bouton **Reconfigurer le système** dans l'onglet Automation ouvre l'assistant de reconfiguration. Il permet de modifier l'utilisateur PHP-FPM, les permissions de fichiers et la configuration du worker sans réinstaller l'application.

- **7 étapes:** contrôle preflight → sélection utilisateur/groupe → validation des permissions → mot de passe sudo (si nécessaire) → application → journal d'audit → fin.

- **Gestion sudo:** si un mot de passe sudo est requis, il est conservé uniquement en session le temps de l'assistant et n'est jamais stocké. Chaque commande sudo est validée individuellement (défense en profondeur).

- **Journal d'audit:** toutes les actions sont enregistrées avec horodatage, commande exécutée et acteur.

- **Détection Docker:** si l'application tourne dans un conteneur Docker, les étapes liées à systemd sont automatiquement ignorées.

- **Mode sans sudo:** dans les environnements sans sudo disponible, l'assistant génère les commandes manuelles à exécuter directement sur le serveur.

- **Verrou de session:** une seule session de reconfiguration peut être active simultanément. Naviguer hors de l'assistant réinitialise l'état.

- **Limitation de débit:** maximum 3 tentatives de reconfiguration par heure.

> L'assistant de reconfiguration nécessite le rôle administrateur. Les opérations appliquées sont irréversibles via l'interface — le journal d'audit permet de tracer ce qui a été modifié.

## Données internes, caches et seuils

- TTL du cache de vue explorateur et taille de page d'exploration.

- Nombre maximum de résultats de recherche et taille max de fichier exploitable.

- Batch du warm index, rétention de l'index adhoc et nombre de snapshots récents indexés.

- TTL des caches Restic: snapshots, ls, stats, search et tree.

- Seuils de lenteur HTTP, SQL, Restic et commandes loggées.

- Paramètres d'auto-refresh et de cache de la page performance.

| Paramètre | Valeur par défaut | Rôle |
| --- | --- | --- |
| `explore_max_file_size_mb` | 5 Mo | Taille maximum d'un fichier explorable depuis l'interface. |
| `explore_view_cache_ttl` | 20 secondes | Durée de vie du cache des vues explorateur. |
| `explore_search_max_results` | 200 | Nombre maximum de résultats renvoyés par la recherche. |
| `performance_slow_request_threshold_ms` | 800 ms | Seuil au-dessus duquel une requête HTTP est loggée comme lente. |

## Intégrations externes et API publique

- Discord, Slack, Telegram, ntfy, webhook, Teams, Gotify, in-app et web push.

- Rétention des notifications internes.

- Gestion des secrets via `secret://` avec provider agent (par défaut) et fallback local chiffré.

- Configuration Infisical: URL, projet, environnement et chemin de secrets.

- Activation de l'API, durée de vie par défaut des tokens, rate limit, rétention des logs API, rétention de l'idempotency et CORS autorisés.

- Rate limits par familles d'endpoints, y compris restaurations, scheduler, worker, explore, recherche et WebAuthn.

| Paramètre | Valeur par défaut | Rôle |
| --- | --- | --- |
| `api_enabled` | À définir selon le déploiement | Active globalement l'API publique. |
| `api_default_rate_limit_per_minute` | 120 | Limite de requêtes par minute appliquée par défaut. |
| `api_log_retention_days` | 30 jours | Rétention des logs d'appels API. |
| `app_notifications_retention_days` | 7 jours | Rétention des notifications internes en base. |

> Les valeurs marquées "Voir `src/AppConfig.php`" sont susceptibles d'évoluer entre versions. Consultez ce fichier pour confirmer la valeur exacte appliquée sur votre déploiement, et utilisez la page Paramètres pour les surcharger.

## Dernier chapitre

Le chapitre final détaille la surface API v1, les tokens, les scopes, les routes exposées, les webhooks signés et les intégrations externes majeures de l'application.

[Précédent Thèmes et personnalisation](15-themes-and-customization.md) [Suivant API et intégrations](14-api-and-integrations.md)

## Parcours

- Précédent: [12. Sécurité et accès](12-security-and-access.md)
- Suivant: [14. API et intégrations](14-api-and-integrations.md)
