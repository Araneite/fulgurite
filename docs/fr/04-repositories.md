---
title: "Documentation Fulgurite — Dépôts Restic"
description: "Déclarer, tester et administrer des dépôts Restic dans Fulgurite."
lang: "fr"
section: "user"
---
# Dépôts Restic

La page Dépôts sert à enregistrer les backends Restic connus par Fulgurite. Chaque dépôt emporte non seulement son chemin d'accès, mais aussi son mécanisme de secret, son seuil d'alerte et sa politique de notification.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md)
- [03. Concepts et navigation](03-concepts-and-navigation.md)
- [04. Dépôts Restic](04-repositories.md) (page actuelle)
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


## Création d'un dépôt

Le formulaire d'ajout n'enregistre pas un simple label. Il tente d'abord de résoudre le secret effectif, puis de tester l'accès au backend avant de considérer que le dépôt est exploitable.

| Champ | Rôle | Notes pratiques |
| --- | --- | --- |
| **Nom** | Identifiant lisible dans les listes, les jobs et l'explorateur. | Pensez à une convention stable par environnement ou application. |
| **Chemin** | Adresse du backend Restic. | Peut être un chemin local ou un backend type `sftp:user@host:/backups/repo`. |
| **Description** | Contexte humain du dépôt. | Utile pour distinguer un dépôt infra d'un dépôt applicatif. |
| **Seuil d'alerte** | Nombre d'heures après lequel un dépôt devient suspect s'il ne reçoit plus de snapshot récent. | Alimente les statuts runtime et les alertes « stale ». |
| **Politique de notification** | Choix des canaux à utiliser pour les événements de dépôt. | Stockée dans la colonne `notification_policy` du dépôt, sous forme JSON. Chaque dépôt peut ainsi disposer de sa propre politique qui **surcharge la politique globale** de l'instance: canaux activés, seuils de sévérité, destinataires spécifiques. |

## Gestion du mot de passe du dépôt

Les mots de passe de dépôts sont gérés via l'abstraction **SecretStore**. L'interface s'occupe de toute la création et la récupération des secrets — aucune manipulation manuelle de fichiers n'est nécessaire. Le provider actif est sélectionné par la variable d'environnement `FULGURITE_SECRET_PROVIDER`.

### HaBrokerSecretProvider (recommandé)

- Provider **haute disponibilité**: se connecte à un ou plusieurs endpoints (liste séparée par des virgules), avec basculement automatique. Chaque endpoint défaillant est écarté pendant 10 secondes.

- Configurer via `FULGURITE_SECRET_BROKER_ENDPOINTS`(ex.: `http://broker1:7777,http://broker2:7777`) et `FULGURITE_SECRET_BROKER_TIMEOUT`(défaut: `2.0` s).

- Références internes: `secret://agent/{type}/{id}/{name}`

- Activé par `FULGURITE_SECRET_PROVIDER=broker`.

### AgentSecretProvider (socket unique)

- Connexion directe sur un **socket Unix**. Configuration plus simple, sans haute disponibilité.

- Socket par défaut: `/run/fulgurite/secrets.sock`— modifiable via `FULGURITE_SECRET_AGENT_SOCKET`.

- Références internes: `secret://agent/{type}/{id}/{name}`

- Activé par `FULGURITE_SECRET_PROVIDER=agent` ou `secret-agent`.

### LocalEncryptedSecretProvider (sans daemon)

- Chiffrement **AES-256-GCM**, clé stockée dans `data/broker/`. Aucun service externe requis.

- Chaque dépôt possède son propre secret: les mots de passe ne sont jamais mutualisés entre deux dépôts.

- Mode autonome pour un serveur auto-hébergé sans daemon de secrets.

- Références internes: `secret://local/{type}/{id}/{name}`

- Activé par `FULGURITE_SECRET_PROVIDER=local` ou `encrypted`.

### InfisicalSecretProvider (externe)

- Délègue la résolution à l' **API Infisical**. Nécessite une configuration dans Paramètres > Intégrations.

- Saisir le **nom du secret** Infisical à la place de la valeur lors de la création du dépôt.

- L'application **résout et teste le secret au moment de la création**: si la résolution échoue (secret introuvable, environnement inaccessible, token invalide), l'enregistrement est annulé.

- Références internes: `secret://infisical/{secretName}`

| Variable d'environnement | Rôle |
| --- | --- |
| `FULGURITE_SECRET_PROVIDER` | Sélectionne le provider: `agent`, `broker`, `secret-agent`, `local`, `encrypted` ou `fallback`. |
| `FULGURITE_SECRET_BROKER_ENDPOINTS` | Liste d'URIs séparés par des virgules pour le broker HA (ex.: `http://broker1:7777,http://broker2:7777`). |
| `FULGURITE_SECRET_BROKER_TIMEOUT` | Délai de requête en secondes pour le broker HA (défaut: `2.0`). |
| `FULGURITE_SECRET_AGENT_SOCKET` | Chemin du socket Unix pour `AgentSecretProvider`(défaut: `/run/fulgurite/secrets.sock`). |

> Le répertoire `data/passwords/` est hérité des versions antérieures. Les nouvelles installations utilisent la chaîne de providers décrite ci-dessus. Si vous migrez une installation ancienne, les fichiers existants restent lisibles.

> Si vous utilisez Infisical, une erreur d'accès au secret pendant la création bloque l'enregistrement du dépôt. Vérifiez le nom, l'environnement et le chemin configurés avant de relancer.

## Initialiser automatiquement un dépôt vide

La case **Créer le dépôt s'il n'existe pas** change le comportement si le backend existe comme chemin ou comme cible, mais pas encore comme dépôt Restic initialisé.

- Pour un **chemin local**, l'application peut tenter de créer le répertoire si celui-ci n'existe pas.

- Ensuite, elle lance l'initialisation Restic du dépôt.

- Si l'initialisation réussit sur un dépôt local, elle applique une correction de permissions de groupe sur l'arborescence.

- Si l'initialisation échoue, le dépôt n'est pas enregistré et le message d'erreur de Restic est remonté.

## Actions disponibles une fois le dépôt enregistré

Le dépôt devient alors une ressource pilotable depuis plusieurs endroits de l'application.

### Depuis la liste des dépôts

- **Explorer** pour ouvrir directement l'explorateur sur ce dépôt.

- **Modifier** pour ajuster description, seuil d'alerte et notifications.

- **Tester notif** pour valider la politique d'alerte du dépôt.

- **Supprimer** si le dépôt ne doit plus être géré par l'instance.

### Depuis l'explorateur

- **Vérifier l'intégrité** du dépôt.

- **Initialiser** le dépôt si l'action est encore pertinente dans ce contexte.

- **Appliquer une rétention** de dépôt avec mode simulation ou exécution.

## Seuil d'alerte, statuts runtime et notifications

Le dépôt alimente la supervision de l'instance. Son état n'est pas stocké en base: il est **calculé en temps réel** par le service `RepoStatusService` à chaque consultation, à partir de la fraîcheur des snapshots observés et des vérifications techniques associées.

- **`ok`** si le dépôt est accessible et que les snapshots récents respectent le seuil attendu.

- **`warning`** si l'âge du dernier snapshot dépasse le seuil d'alerte configuré sur le dépôt (`alert_hours`). C'est l'état « stale » classique.

- **`no_snap`** si le dépôt est connu mais n'a pas encore de snapshot utilisable.

- **`error`** si les contrôles techniques remontent un échec ou une impossibilité d'accès.

- **`pending`** pour un dépôt dont le statut est en cours d'évaluation ou qui vient d'être créé et attend son premier calcul.

## Permissions, scopes et bonnes pratiques

La gestion des dépôts est souvent le premier endroit où l'on ressent les effets des scopes utilisateur.

- **`repos.view`** suffit pour afficher la liste et ouvrir l'explorateur d'un dépôt visible.

- **`repos.manage`** est nécessaire pour l'ajout, la suppression, les checks et certaines actions sensibles.

- Si l'utilisateur est limité à un **scope repo sélectionné**, seuls les dépôts autorisés lui seront visibles et exploitables.

- Les tokens API héritent indirectement de cette logique: un utilisateur ne peut attribuer à un token que des scopes compatibles avec ses propres permissions.

> Une bonne pratique consiste à créer d'abord les dépôts par environnement ou par périmètre fonctionnel, puis à rattacher des jobs clairement nommés à chacun.

## Suite logique

Une fois les dépôts déclarés, vous pouvez créer les tâches qui les alimentent. Le chapitre suivant couvre les backup jobs en détail, y compris les hooks, les rétentions, les stratégies de retry et les exécutions distantes.

[Précédent Concepts et navigation](03-concepts-and-navigation.md) [Suivant Backup jobs](05-backup-jobs.md)

## Parcours

- Précédent: [03. Concepts et navigation](03-concepts-and-navigation.md)
- Suivant: [05. Jobs de sauvegarde](05-backup-jobs.md)
