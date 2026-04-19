---
title: "Fulgurite Documentation — API & Integrations"
description: "Public REST API v1, tokens, scopes, routes, OpenAPI, signed webhooks and external integrations."
lang: "en"
section: "user"
---
# Controlling the application from third-party tools

The public API v1 is already versioned, cleanly routed and documented via OpenAPI. It covers identity, repositories, snapshots, jobs, restores, hosts, SSH keys, notifications, stats, logs, the queue, users, settings, API tokens and webhooks.

## Navigation

### User Documentation

- [01. Overview](01-index.md)
- [02. Getting Started](02-getting-started.md)
- [03. Concepts and Navigation](03-concepts-and-navigation.md)
- [04. Restic Repositories](04-repositories.md)
- [05. Backup Jobs](05-backup-jobs.md)
- [06. Copy Jobs](06-copy-jobs.md)
- [07. Explorer and Restores](07-explorer-and-restores.md)
- [08. Scheduler and Worker](08-scheduler-and-worker.md)
- [09. Monitoring and Performance](09-monitoring-and-performance.md)
- [10. Notifications](10-notifications.md)
- [11. Hosts and SSH](11-hosts-and-ssh.md)
- [12. Security and Access](12-security-and-access.md)
- [13. Settings Reference](13-settings-reference.md)
- [14. API and Integrations](14-api-and-integrations.md) (current page)
- [15. Themes and Customization](15-themes-and-customization.md)

### Developer Documentation

- [01. Developer Overview](dev/01-index.md)
- [02. Variable Themes](dev/02-theme-variables.md)
- [03. Advanced Themes](dev/03-advanced-themes.md)
- [04. Context and Template Tags](dev/04-context-and-template-tags.md)
- [05. Packaging and Debugging](dev/05-packaging-and-debugging.md)


## Enabling the API and creating tokens

The public `/api/v1` API can be globally enabled or disabled. Once enabled, each user can manage their own tokens according to their permissions. This public surface uses bearer-token authentication only:

- **Application token**: header `Authorization: Bearer rui_...`, used by scripts, integrations and external tools.

- **No session cookie on `/api/v1`**: `ApiAuth` requires a valid `Authorization` header and does not reuse the web session.

- The token secret is revealed only once at creation and cannot be retrieved afterwards. If lost, the token must be revoked and a new one created.

- Expected format: bearer token starting with `rui_`.

- Token options: name, expiration, rate limit (requests per minute, configurable per token), allowed IPs, CORS origins, read-only mode, scopes.

- **read_only** mode to automatically filter out write or execution scopes.

> Do not mix the two API surfaces: `/public/api/*.php` serves session-backed UI AJAX, while `/api/v1/*` is the public token-auth surface (ApiKernel/ApiAuth) with CORS, idempotency and API audit.

## Scope catalogue

Scopes are grouped by functional domain and bound to the application permissions of the creating user. They allow each token to be restricted to a precise set of endpoints: read-only, administration, specific resources, etc.

- Identity: `me:read`

- Repositories and snapshots: `repos:read/write/check`, `snapshots:read/write`

- Jobs: `backup_jobs:read/write/run`, `copy_jobs:read/write/run`

- Restores: `restores:read/write`

- Hosts and SSH: `hosts:read/write`, `ssh_keys:read/write`

- Scheduler, notifications, observability, users, settings, webhooks and API tokens.

### Resource filtering: `repo_scope_mode` and `host_scope_mode`

Beyond endpoint scopes, each token can be further restricted to a subset of repositories and/or hosts via two independent modes:

- `all`(default): the token can access all repos/hosts visible to its creating user.

- `selected`: the token is limited to the specific repos/hosts explicitly listed in its configuration. Any attempt to access an out-of-scope resource returns **403**.

This filtering is enforced consistently across all operation types:

- **Creation endpoints**: creating a backup job is only allowed if both the target repository and target host are within the token's scope.

- **`run` endpoints**: triggering a job run requires the same scope permissions as deleting it — if the resource is out of scope, triggering a run is also denied.

- **Stats and aggregate endpoints**: return data filtered to in-scope resources only (no cross-scope leakage).

- **List endpoints**: return only resources within the token's scope.

> Example: a token with `repo_scope_mode=selected` scoped to repo ID 3 cannot read stats for repo ID 5, cannot create jobs targeting repo ID 5, and cannot trigger runs on jobs associated with repo ID 5. Typical use cases: a token dedicated to an external monitoring script, or a CI/CD token limited to a single host.

## Main routes exposed in v1

The table below groups the main route families. The exhaustive, always-current reference is the `/api/v1/openapi.json` document generated from the real router.

| Resource | Routes | Example usage |
| --- | --- | --- |
| **Profile** | `GET /api/v1/me` | Retrieve the identity of the current token. |
| **Repositories** | `GET/POST /repos`, `GET/PATCH/DELETE /repos/{id}`, `POST /repos/{id}/check` | Read, create, update, delete or verify a repository. |
| **Snapshots** | `GET /repos/{id}/snapshots`, `GET /repos/{id}/snapshots/{sid}`, `GET /repos/{id}/snapshots/{sid}/files`, `DELETE`, `PUT.../tags` | Browse, delete or retag snapshots. |
| **Jobs** | `/backup-jobs`, `/copy-jobs` with read/write/run | Control backups and copies from an external tool. |
| **Restores** | `GET/POST /restores`, `GET /restores/{id}` | Trigger and track restores. |
| **Observability** | `/stats/*`, `/logs/*`, `/jobs/*` | Read stats, logs and the internal queue. |
| **Administration** | `/users`, `/settings`, `/api-tokens`, `/webhooks` | Manage global administration remotely. |

```text
V1 routes present in the OpenAPI reference
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

## OpenAPI, JSON schema and Swagger UI

The instance already exposes its own interactive documentation. The full OpenAPI document can be downloaded directly from the API screen of the application, making it easy to feed any client generator.

- `/api/v1/openapi.json` for the OpenAPI 3 document (downloadable from the API screen).

- `/api/v1/docs` or `/api/v1/swagger` for the Swagger UI.

- API server based on `/api/v1`.

## HMAC-signed webhooks

API webhooks are outgoing integrations distinct from generic notifications. They emit signed application events to an HTTP receiver.

- Create, update, delete and test from the UI or the API.

- HMAC secret revealed only once at creation — not retrievable afterwards. If lost, rotation is mandatory.

- Signature header: `X-Fulgurite-Signature: sha256= < hex >`.

- Other notable headers: delivery identifier and a specific User-Agent.

- Supported events: backup job success/failure, copy job success/failure, restore success/failure, repository creation/deletion, repository check failure, snapshot deletion, API token creation/revocation.

- Delivery history available per webhook.

### Signature validation on the receiver side

The signature is computed with `HMAC-SHA256(body, secret)` over the **raw body** of the request, before any JSON parsing. The receiver must recompute the same signature and compare it in constant time.

```text
// PHP example of receiver-side validation
$body   = file_get_contents('php://input');
$header = $_SERVER['HTTP_X_FULGURITE_SIGNATURE']?? '';
$secret = getenv('FULGURITE_WEBHOOK_SECRET');

$expected = 'sha256='. hash_hmac('sha256', $body, $secret);

if (!hash_equals($expected, $header)) {
    http_response_code(401);
    exit('invalid signature');
}

// Valid signature: the event can be processed
$event = json_decode($body, true);
```

```text
# Bash validation example
BODY=$(cat)
EXPECTED="sha256=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')"
["$EXPECTED" = "$HTTP_X_FULGURITE_SIGNATURE"] || { echo "invalid"; exit 1; }
```

## Other major external integrations

- **Infisical** for repository secrets, copy destinations and certain hooks.

- **Rclone** for copy job destinations using `rclone:...`.

- **SMTP and chat ops** for notifications.

- **SSH and rsync** for certain restores and remote executions.

> For clean automation, combine narrowly scoped tokens with the repo/host scopes of the creating user. This opens up a powerful API without exposing the entire instance.

## End of the tour

You now have a complete view of Fulgurite's current public surface, from installation through advanced administration and external integration. Back to the [Overview](01-index.md).

[Previous Settings Reference](13-settings-reference.md)

## Reading Path

- Previous: [13. Settings Reference](13-settings-reference.md)
- Next: [15. Themes and Customization](15-themes-and-customization.md)
