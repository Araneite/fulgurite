---
title: "Documentation Fulgurite — Backup jobs"
description: "Configurer des backup jobs Fulgurite: sources, tags, exclusions, planning, rétention, hooks, retries et exécution distante."
lang: "fr"
section: "user"
---
# Backup jobs

Un backup job fait le lien entre des chemins sources et un dépôt cible. Il peut être purement local ou s'exécuter via un hôte SSH, embarquer une rétention, des hooks, des variables de secrets, une politique de retry et une politique de notification adaptée.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md)
- [03. Concepts et navigation](03-concepts-and-navigation.md)
- [04. Dépôts Restic](04-repositories.md)
- [05. Jobs de sauvegarde](05-backup-jobs.md) (page actuelle)
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


## Champs et options du backup job

Le formulaire couvre la plupart des besoins d'une sauvegarde Restic industrialisée.

| Zone | Ce que vous réglez | Ce qu'il faut retenir |
| --- | --- | --- |
| **Identification** | Nom, dépôt cible et description. | Le nom du job sert partout: liste, logs, API, notifications. |
| **Sources** | Chemins sources multilignes, tags séparés par des virgules et exclusions multilignes. | Les chemins sont absolus, un par ligne. Les tags servent ensuite à filtrer ou contextualiser les snapshots. |
| **Planification** | Activation, heure et jours d'exécution. | Le job reste manuel tant que la planification n'est pas active et tant que le cron central n'est pas lui-même opérationnel. |
| **Notifications** | Politique de canaux pour les succès et échecs du job. | Peut hériter de la configuration globale ou la surcharger. Voir l'encart ci-dessous sur la coexistence entre `notify_on_failure` et `notification_policy`. |
| **Retry** | Politique de reprise en cas d'échec. | Stockée dans la colonne `retry_policy` sous forme JSON: nombre maximal de tentatives, délai entre chaque reprise, éventuelles règles par type d'erreur. |
| **État du dernier run** | Colonnes `last_status` et `last_run_at`. | Ces champs sont mis à jour à chaque exécution et servent à afficher l'état du dernier run directement dans la liste des jobs de l'interface. |

> **Deux systèmes de notification coexistent sur un job.** Le champ historique `notify_on_failure` est un simple booléen « prévenir sur échec » hérité de la première version. Le système récent `notification_policy` est un objet JSON par job qui décrit finement les canaux, les événements (succès, échec, warning) et les surcharges par rapport à la politique globale. Les deux colonnes peuvent être présentes en base; **la politique JSON a priorité** dès qu'elle est définie, et le booléen sert de filet de sécurité pour les jobs qui n'ont pas encore été migrés.

## Quand utiliser un hôte distant

Le même écran sert aux sauvegardes lancées depuis le serveur Fulgurite et à celles exécutées via SSH sur une autre machine.

### Mode local

- Le job s'exécute sur le serveur qui héberge Fulgurite.

- Les chemins sources sont vus tels quels par ce serveur.

- Le chemin de dépôt est celui accessible localement.

### Mode distant

- Vous associez le job à un **host_id**.

- Le champ **remote_repo_path** permet de fournir le chemin du dépôt tel qu'il est vu depuis cet hôte.

- **hostname_override** force le paramètre `--hostname` pour garder des snapshots cohérents.

> Un hôte peut aussi porter un mot de passe sudo optionnel. Il est utile quand les chemins à sauvegarder exigent une élévation de droits sur la machine distante.

## Templates de sauvegarde rapide

La page **Templates de sauvegarde** et le wizard **Sauvegarde rapide** partagent le même gestionnaire de modèles. Ils servent à préremplir un futur backup job sans masquer le formulaire final.

- **Templates intégrés**: fournis par le code pour les profils courants comme serveur Linux, web, MySQL ou PostgreSQL. Ils ne sont pas éditables directement.

- **Templates personnalisés**: enregistrés en base et réutilisables dans le wizard rapide avec le préfixe `custom:`.

- **Duplication**: un modèle personnalisable peut être créé à partir d'un modèle existant, puis ajusté sans modifier le modèle source.

- **Champs préremplis**: chemins sources (`source_paths`), exclusions, tags, planification, nom de job, chemin de dépôt distant et rétention (`keep_last/daily/weekly/monthly/yearly`, prune).

- **Permission**: la gestion des templates passe par `backup_jobs.manage`, comme la création et la modification des backup jobs.

> Dans le wizard Sauvegarde rapide, choisir un template ne lance rien automatiquement: il initialise les valeurs du flux, puis l'utilisateur confirme le job créé.

## Rétention intégrée au job

Le job peut embarquer sa propre politique de rétention, appliquée après une sauvegarde réussie.

- **Activation explicite** du bloc rétention.

- Compteurs **keep_last**, **keep_daily**, **keep_weekly**, **keep_monthly** et **keep_yearly**.

- Option **prune** pour purger les données rendues inutiles par le forget.

- Si aucune règle n'est renseignée, la rétention ne fait rien.

- Si la rétention échoue après une sauvegarde réussie, la sauvegarde reste considérée comme réussie mais un avertissement est consigné dans le log.

## Hooks et scripts approuvés

Les hooks permettent d'encadrer l'exécution d'un backup par des scripts préalables ou de post-traitement. Les hooks reposent sur le catalogue de **scripts approuvés** de l'instance — il n'est pas possible de saisir des commandes shell arbitraires directement dans le job.

### Pre-hook / Post-hook

- Les champs **Pre-hook** et **Post-hook** référencent chacun l' **identifiant d'un script approuvé** défini dans Administration > Scripts.

- **Pre-hook** s'exécute avant le backup: utile pour figer une base, générer un export, stopper un process temporairement ou prendre un lock.

- **Post-hook** s'exécute après le backup: utile pour nettoyer, relancer un service ou produire un signal externe.

- L'exécution peut être **locale**(utilisateur du serveur web) ou **distante**(SSH sur l'hôte cible du job).

### Mini-langage des scripts approuvés

- Un script approuvé est composé d' **une instruction par ligne**— aucune syntaxe shell (pas de `;`, `& &`, `|`, `>`, backticks, etc.).

- Instructions autorisées: `echo`, `curl`, `mkdir`, `cp`, `mv`, `rm`, `chmod`, `chown`, `sleep`, `set`, `export` et les appels `restic`.

- Instructions interdites: `eval`, `exec`, `source`, `.`, `bash`, `sh` et tout opérateur shell.

- Les scripts se créent et se gèrent dans **Administration > Scripts**.

### Injection de variables dans les scripts

- Utilisez la syntaxe `{{ENV_VAR}}` pour injecter des variables de contexte du job dans les lignes du script.

- Pour référencer un secret du broker, utilisez `{{SECRET_ma_cle}}` directement dans la ligne du script. C'est l'approche recommandée.

**Variables de contexte disponibles:**

| Variable | Description |
| --- | --- |
| `BACKUP_JOB_ID` | Identifiant du backup job. |
| `BACKUP_JOB_NAME` | Nom du backup job. |
| `SNAPSHOT_ID` | Identifiant du snapshot créé ou ciblé (disponible dans le post-hook). |
| `REPO_ID` | Identifiant du dépôt. |
| `REPO_NAME` | Nom du dépôt. |
| `HOST_ID` | Identifiant de l'hôte SSH (si exécution distante). |
| `HOST_NAME` | Nom d'hôte de la machine SSH (si exécution distante). |
| `EXIT_CODE` | Code de sortie de l'opération de sauvegarde (disponible dans le post-hook). |
| `RUN_ID` | Identifiant unique de l'exécution en cours. |

**Contraintes des scripts approuvés:**

- Taille maximale: **16 384 octets**.

- Nombre de lignes maximal: **64**.

- Longueur maximale du nom: **120 caractères**.

- Portée d'exécution: `local`(sur le serveur web), `remote`(via SSH sur l'hôte cible) ou `both`.

### Hook environment (compatibilité)

- Le champ `hook_env` est un objet JSON `{"VAR_NAME": "valeur"}` qui injecte des variables d'environnement supplémentaires lors de l'exécution.

- Ce champ est conservé pour la compatibilité. L'approche privilégiée est désormais d'utiliser `{{SECRET_cle}}` directement dans les lignes du script approuvé, sans passer par `hook_env`.

> Les scripts approuvés sont mutualisables entre plusieurs jobs. Modifier un script dans le catalogue met à jour immédiatement le comportement de tous les jobs qui l'utilisent.

## Lancement, liste des jobs et logs

La liste de jobs donne déjà de nombreuses informations opérationnelles avant même de lancer quoi que ce soit.

- Le tableau affiche le dépôt, l'hôte utilisé, le premier chemin source et le nombre de chemins supplémentaires.

- Le mode planifié ou manuel est visible d'un coup d'œil, avec les jours et l'heure si le job est actif.

- Les colonnes `last_status` et `last_run_at` sont lues directement depuis la ligne du job pour afficher le statut et l'horodatage du dernier run dans la liste.

- Un lancement manuel ouvre un suivi de log en temps réel.

- Chaque exécution alimente aussi `cron_log` avec `job_type = 'backup'`.

## Retry policy et évaluation des erreurs

Le moteur d'exécution ne se contente pas de « retenter X fois ». Il s'appuie sur une classification de l'erreur pour déterminer s'il faut reprendre, attendre ou abandonner. La configuration elle-même vit dans la colonne `retry_policy` du job sous forme JSON: c'est elle qui fixe le nombre maximal de tentatives et le délai entre chaque reprise.

- La politique de retry est résolue au moment du run.

- Chaque tentative produit une classification lisible dans le log.

- Le nombre de retries et le délai avant reprise dépendent de la décision calculée à partir du JSON de la politique.

- Le planificateur évite aussi de relancer deux fois le même job dans la même fenêtre horaire de planification.

> Le job peut être planifié, mais ne partira jamais tout seul si le cron central n'est pas installé ou si son exécution est en erreur. C'est l'une des premières choses à vérifier lorsqu'un job « ne tourne pas ».

## Aller plus loin

Si votre objectif est de répliquer un dépôt vers une autre cible plutôt que de sauvegarder des chemins sources, poursuivez avec le chapitre sur les copy jobs.

[Précédent Dépôts Restic](04-repositories.md) [Suivant Copy jobs](06-copy-jobs.md)

## Parcours

- Précédent: [04. Dépôts Restic](04-repositories.md)
- Suivant: [06. Jobs de copie](06-copy-jobs.md)
