---
title: "Documentation Fulgurite — API et intégrations"
description: "API publique REST v1, tokens, scopes, routes, OpenAPI, webhooks signés et intégrations externes."
lang: "fr"
section: "user"
---
# Piloter l'application depuis des outils tiers

L'API publique v1 est déjà versionnée, routée proprement et documentée via OpenAPI. Elle couvre l'identité, les dépôts, les snapshots, les jobs, les restaurations, les hôtes, les clés SSH, les notifications, les stats, les logs, la queue, les utilisateurs, les settings, les tokens API et les webhooks.

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
- [14. API et intégrations](14-api-and-integrations.md) (page actuelle)
- [15. Thèmes et personnalisation](15-themes-and-customization.md)

### Documentation développeur

- [01. Vue d’ensemble développeur](dev/01-index.md)
- [02. Thèmes à variables](dev/02-theme-variables.md)
- [03. Thèmes avancés](dev/03-advanced-themes.md)
- [04. Contexte et template tags](dev/04-context-and-template-tags.md)
- [05. Packaging et debug](dev/05-packaging-and-debugging.md)


## Activation de l'API et création de tokens

L'API publique `/api/v1` peut être globalement activée ou désactivée. Une fois activée, chaque utilisateur peut gérer ses propres tokens selon ses permissions. Cette surface publique utilise exclusivement l'authentification par token bearer:

- **Token d'application**: header `Authorization: Bearer rui_...`, utilisé par les scripts, intégrations et outils externes.

- **Pas de session cookie sur `/api/v1`**: `ApiAuth` exige un header `Authorization` valide et ne réutilise pas la session web.

- Le secret du token est révélé une seule fois à la création et ne peut être récupéré ensuite. En cas de perte, il faut révoquer le token et en créer un nouveau.

- Format attendu: bearer commençant par `rui_`.

- Options de token: nom, expiration, rate limit (requêtes par minute, configurable par token), IPs autorisées, origines CORS, lecture seule, scopes.

- Mode **read_only** pour filtrer automatiquement les scopes d'écriture ou d'exécution.

> Ne confondez pas les deux surfaces: `/public/api/*.php` sert l'UI session-based, alors que `/api/v1/*` est la surface publique token-auth (ApiKernel/ApiAuth), avec CORS, idempotency et audit API.

## Catalogue de scopes

Les scopes sont regroupés par domaine fonctionnel et reliés aux permissions applicatives de l'utilisateur créateur. Ils permettent de limiter chaque token à un périmètre d'endpoints précis: lecture seule, administration, ressources spécifiques, etc.

- Identité: `me:read`

- Dépôts et snapshots: `repos:read/write/check`, `snapshots:read/write`

- Jobs: `backup_jobs:read/write/run`, `copy_jobs:read/write/run`

- Restaurations: `restores:read/write`

- Hôtes et SSH: `hosts:read/write`, `ssh_keys:read/write`

- Scheduler, notifications, observabilité, users, settings, webhooks et tokens API.

### Filtrage par ressource: `repo_scope_mode` et `host_scope_mode`

En plus des scopes d'endpoints, chaque token peut être restreint à un sous-ensemble de dépôts et/ou d'hôtes via deux modes indépendants:

- `all`(défaut): le token accède à tous les dépôts/hôtes visibles par son utilisateur créateur.

- `selected`: le token est limité aux dépôts/hôtes explicitement listés dans sa configuration. Toute tentative d'accès à une ressource hors périmètre retourne **403**.

Ce filtrage s'applique de manière cohérente à l'ensemble des opérations:

- **Endpoints de création**: la création d'un backup job n'est autorisée que si le dépôt cible ET l'hôte cible sont tous les deux dans le périmètre du token.

- **Endpoints `run`**: délencher l'exécution d'un job requiert les mêmes permissions de périmètre que sa suppression — si la ressource est hors périmètre, l'exécution est également refusée.

- **Endpoints de stats et agrégats**: retournent uniquement les données relatives aux ressources dans le périmètre (aucune fuite inter-périmètre).

- **Endpoints de liste**: ne retournent que les ressources dans le périmètre du token.

> Exemple: un token avec `repo_scope_mode=selected` limité au dépôt ID 3 ne peut pas lire les stats du dépôt ID 5, créer des jobs ciblant le dépôt ID 5, ni déclencher des exécutions sur des jobs associés au dépôt ID 5. Cas d'usage typiques: token dédié à un script de monitoring externe, ou token CI/CD limité à un hôte unique.

## Routes principales exposées en v1

La table ci-dessous regroupe les familles principales. La référence exhaustive et toujours à jour reste le document `/api/v1/openapi.json` généré depuis le routeur réel.

| Ressource | Routes | Exemples d'usage |
| --- | --- | --- |
| **Profil** | `GET /api/v1/me` | Récupérer l'identité du token courant. |
| **Dépôts** | `GET/POST /repos`, `GET/PATCH/DELETE /repos/{id}`, `POST /repos/{id}/check` | Lire, créer, modifier, supprimer ou vérifier un dépôt. |
| **Snapshots** | `GET /repos/{id}/snapshots`, `GET /repos/{id}/snapshots/{sid}`, `GET /repos/{id}/snapshots/{sid}/files`, `DELETE`, `PUT.../tags` | Explorer, supprimer ou retaguer des snapshots. |
| **Jobs** | `/backup-jobs`, `/copy-jobs` avec read/write/run | Piloter les sauvegardes et copies depuis un outil externe. |
| **Restaurations** | `GET/POST /restores`, `GET /restores/{id}` | Lancer et suivre des restaurations. |
| **Observabilité** | `/stats/*`, `/logs/*`, `/jobs/*` | Lire les stats, les logs et la queue interne. |
| **Administration** | `/users`, `/settings`, `/api-tokens`, `/webhooks` | Gérer l'administration globale à distance. |

```text
Routes v1 présentes dans la référence OpenAPI
GET    /api/v1/me

GET    /api/v1/api-tokens
POST   /api/v1/api-tokens
GET    /api/v1/api-tokens/{id}
PATCH  /api/v1/api-tokens/{id}
POST   /api/v1/api-tokens/{id}/revoke
DELETE /api/v1/api-tokens/{id}

GET    /api/v1/webhooks
POST   /api/v1/webhooks
GET    /api/v1/webhooks/events
GET    /api/v1/webhooks/{id}
PATCH  /api/v1/webhooks/{id}
DELETE /api/v1/webhooks/{id}
POST   /api/v1/webhooks/{id}/test
GET    /api/v1/webhooks/{id}/deliveries

GET    /api/v1/repos
POST   /api/v1/repos
GET    /api/v1/repos/{id}
PATCH  /api/v1/repos/{id}
DELETE /api/v1/repos/{id}
POST   /api/v1/repos/{id}/check
GET    /api/v1/repos/{id}/stats

GET    /api/v1/repos/{id}/snapshots
GET    /api/v1/repos/{id}/snapshots/{sid}
GET    /api/v1/repos/{id}/snapshots/{sid}/files
DELETE /api/v1/repos/{id}/snapshots/{sid}
PUT    /api/v1/repos/{id}/snapshots/{sid}/tags

GET    /api/v1/backup-jobs
POST   /api/v1/backup-jobs
GET    /api/v1/backup-jobs/{id}
PATCH  /api/v1/backup-jobs/{id}
DELETE /api/v1/backup-jobs/{id}
POST   /api/v1/backup-jobs/{id}/run

GET    /api/v1/copy-jobs
POST   /api/v1/copy-jobs
GET    /api/v1/copy-jobs/{id}
PATCH  /api/v1/copy-jobs/{id}
DELETE /api/v1/copy-jobs/{id}
POST   /api/v1/copy-jobs/{id}/run

GET    /api/v1/restores
POST   /api/v1/restores
GET    /api/v1/restores/{id}

GET    /api/v1/hosts
POST   /api/v1/hosts
GET    /api/v1/hosts/{id}
PATCH  /api/v1/hosts/{id}
DELETE /api/v1/hosts/{id}
POST   /api/v1/hosts/{id}/test

GET    /api/v1/ssh-keys
POST   /api/v1/ssh-keys
GET    /api/v1/ssh-keys/{id}
DELETE /api/v1/ssh-keys/{id}
POST   /api/v1/ssh-keys/{id}/test

GET    /api/v1/scheduler/tasks
GET    /api/v1/scheduler/backup-schedules
GET    /api/v1/scheduler/copy-schedules
GET    /api/v1/scheduler/cron-log
POST   /api/v1/scheduler/tasks/{key}/run

GET    /api/v1/notifications
GET    /api/v1/notifications/unread-count
POST   /api/v1/notifications/{id}/read
POST   /api/v1/notifications/read-all
DELETE /api/v1/notifications/{id}
DELETE /api/v1/notifications/read

GET    /api/v1/stats/summary
GET    /api/v1/stats/repo-runtime
GET    /api/v1/stats/repos/{id}/history

GET    /api/v1/logs/activity
GET    /api/v1/logs/cron
GET    /api/v1/logs/api-tokens

GET    /api/v1/jobs/summary
GET    /api/v1/jobs/recent
GET    /api/v1/jobs/worker
POST   /api/v1/jobs/process
POST   /api/v1/jobs/recover

GET    /api/v1/users
POST   /api/v1/users
GET    /api/v1/users/{id}
PATCH  /api/v1/users/{id}
DELETE /api/v1/users/{id}

GET    /api/v1/settings
GET    /api/v1/settings/{key}
PUT    /api/v1/settings/{key}
```

## OpenAPI, JSON schema et interface Swagger

L'instance expose déjà sa propre documentation interactive. Le document OpenAPI complet est téléchargeable directement depuis l'écran API de l'application, ce qui permet d'alimenter n'importe quel générateur de client.

- `/api/v1/openapi.json` pour le document OpenAPI 3 (téléchargeable depuis l'écran API).

- `/api/v1/docs` ou `/api/v1/swagger` pour l'interface Swagger UI.

- Serveur d'API basé sur `/api/v1`.

## Webhooks signés HMAC

Les webhooks API sont des intégrations sortantes distinctes des notifications génériques. Ils émettent des événements applicatifs signés à destination d'un récepteur HTTP.

- Création, mise à jour, suppression et test depuis l'UI ou l'API.

- Secret HMAC révélé une seule fois à la création — non récupérable par la suite. En cas de perte, rotation obligatoire.

- Header de signature: `X-Fulgurite-Signature: sha256= < hex >`.

- Autres headers notables: identifiant de livraison et User-Agent spécifique.

- Événements supportés: succès/échec backup job, succès/échec copy job, succès/échec restauration, création/suppression de dépôt, échec de check de dépôt, suppression de snapshot, création/révocation de token API.

- Historique des deliveries disponible par webhook.

### Validation de la signature côté récepteur

La signature est calculée avec `HMAC-SHA256(body, secret)` sur le **body brut** de la requête, avant tout parsing JSON. Le récepteur doit recalculer la même signature et la comparer en temps constant.

```text
// Exemple PHP de validation côté récepteur
$body   = file_get_contents('php://input');
$header = $_SERVER['HTTP_X_FULGURITE_SIGNATURE']?? '';
$secret = getenv('FULGURITE_WEBHOOK_SECRET');

$expected = 'sha256='. hash_hmac('sha256', $body, $secret);

if (!hash_equals($expected, $header)) {
    http_response_code(401);
    exit('invalid signature');
}

// Signature valide: on peut traiter l'événement
$event = json_decode($body, true);
```

```text
# Exemple bash de validation
BODY=$(cat)
EXPECTED="sha256=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')"
["$EXPECTED" = "$HTTP_X_FULGURITE_SIGNATURE"] || { echo "invalid"; exit 1; }
```

## Autres intégrations externes majeures

- **Infisical** pour les secrets de dépôts, de destinations de copies et de certains hooks.

- **Rclone** pour les destinations de copy jobs en `rclone:...`.

- **SMTP et chat ops** pour les notifications.

- **SSH et rsync** pour certaines restaurations et exécutions distantes.

> Pour automatiser proprement, combinez des tokens scopés au plus juste avec les scopes repo/host de l'utilisateur créateur. Cela permet d'ouvrir une API puissante sans exposer toute l'instance.

## Fin de la visite

Vous avez maintenant une vue complète de la surface publique actuelle de Fulgurite, de l'installation jusqu'à l'administration avancée et l'intégration externe. Retour à la [Vue d'ensemble](01-index.md).

[Précédent Référence des paramètres](13-settings-reference.md)

## Parcours

- Précédent: [13. Référence des paramètres](13-settings-reference.md)
- Suivant: [15. Thèmes et personnalisation](15-themes-and-customization.md)
