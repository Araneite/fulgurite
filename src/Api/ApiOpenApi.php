<?php
// =============================================================================
// ApiOpenApi.php — Generation dynamique of document OpenAPI 3 of the public API
// =============================================================================

class ApiOpenApi {

    public static function document(): array {
        $appName = Database::getSetting('app_name', 'Fulgurite');
        $appUrl = rtrim((string) Database::getSetting('app_url', ''), '/');
        $serverUrl = ($appUrl !== '' ? $appUrl : '') . '/api/v1';

        $paths = self::buildPaths();

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => $appName . ' API',
                'description' => 'API REST publique de Fulgurite : pilotage des depots restic, jobs, restaurations, scheduler, notifications, administration. '
                    . 'Toutes les requetes doivent etre authentifiees par un token API (header Authorization: Bearer rui_xxx).',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => $serverUrl ?: '/api/v1'],
            ],
            'security' => [['BearerAuth' => []]],
            'tags' => self::tags(),
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'rui_<prefix>_<secret>',
                    ],
                ],
                'parameters' => [
                    'PageParam' => [
                        'name' => 'page', 'in' => 'query', 'required' => false,
                        'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                    ],
                    'PerPageParam' => [
                        'name' => 'per_page', 'in' => 'query', 'required' => false,
                        'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 200],
                    ],
                ],
                'headers' => [
                    'X-RateLimit-Limit' => ['schema' => ['type' => 'integer'], 'description' => 'Limite par minute pour ce token'],
                    'X-RateLimit-Remaining' => ['schema' => ['type' => 'integer'], 'description' => 'Hits restants dans la fenetre courante'],
                    'X-RateLimit-Reset' => ['schema' => ['type' => 'integer'], 'description' => 'Timestamp Unix de fin de fenetre'],
                ],
                'schemas' => self::schemas(),
                'responses' => [
                    'Unauthorized' => self::errorResponse('Token absent, invalide ou revoque'),
                    'Forbidden' => self::errorResponse('Scope ou permissions insufficientes'),
                    'NotFound' => self::errorResponse('Ressource introuvable'),
                    'ValidationError' => self::errorResponse('Requete invalide'),
                    'RateLimited' => self::errorResponse('Trop de requetes (rate limit du token)'),
                ],
            ],
        ];
    }

    private static function tags(): array {
        return [
            ['name' => 'Identite'], ['name' => 'API Tokens'], ['name' => 'Webhooks'],
            ['name' => 'Depots'], ['name' => 'Snapshots'], ['name' => 'Backup jobs'],
            ['name' => 'Copy jobs'], ['name' => 'Restores'], ['name' => 'Hotes'],
            ['name' => 'Cles SSH'], ['name' => 'Scheduler'], ['name' => 'Notifications'],
            ['name' => 'Stats'], ['name' => 'Logs'], ['name' => 'Job queue'],
            ['name' => 'Utilisateurs'], ['name' => 'Settings'],
        ];
    }

    private static function schemas(): array {
        return [
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'error' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'Pagination' => [
                'type' => 'object',
                'properties' => [
                    'page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                    'total_pages' => ['type' => 'integer'],
                ],
            ],
            'Repo' => self::object([
                'id' => 'integer', 'name' => 'string', 'path' => 'string',
                'description' => 'string', 'alert_hours' => 'integer',
                'notify_email' => 'boolean', 'password_source' => 'string', 'created_at' => 'string',
            ]),
            'BackupJob' => self::object([
                'id' => 'integer', 'name' => 'string', 'repo_id' => 'integer', 'repo_name' => 'string',
                'host_id' => 'integer', 'source_paths' => 'array', 'tags' => 'array', 'excludes' => 'array',
                'schedule_enabled' => 'boolean', 'schedule_hour' => 'integer', 'schedule_days' => 'string',
                'last_run' => 'string', 'last_status' => 'string',
            ]),
            'CopyJob' => self::object([
                'id' => 'integer', 'name' => 'string', 'source_repo_id' => 'integer', 'dest_path' => 'string',
                'schedule_enabled' => 'boolean', 'schedule_hour' => 'integer', 'schedule_days' => 'string',
                'last_run' => 'string', 'last_status' => 'string',
            ]),
            'Host' => self::object([
                'id' => 'integer', 'name' => 'string', 'hostname' => 'string', 'port' => 'integer',
                'user' => 'string', 'ssh_key_id' => 'integer', 'description' => 'string',
            ]),
            'SshKey' => self::object([
                'id' => 'integer', 'name' => 'string', 'host' => 'string', 'user' => 'string',
                'port' => 'integer', 'public_key' => 'string',
            ]),
            'ApiToken' => self::object([
                'id' => 'integer', 'name' => 'string', 'prefix' => 'string', 'scopes' => 'array',
                'rate_limit_per_minute' => 'integer', 'read_only' => 'boolean',
                'expires_at' => 'string', 'last_used_at' => 'string', 'created_at' => 'string',
            ]),
            'Webhook' => self::object([
                'id' => 'integer', 'name' => 'string', 'url' => 'string', 'events' => 'array',
                'enabled' => 'boolean', 'last_status' => 'integer',
            ]),
            'User' => self::object([
                'id' => 'integer', 'username' => 'string', 'role' => 'string',
                'email' => 'string', 'first_name' => 'string', 'last_name' => 'string',
            ]),
            'Notification' => self::object([
                'id' => 'integer', 'title' => 'string', 'body' => 'string', 'severity' => 'string',
                'is_read' => 'boolean', 'created_at' => 'string',
            ]),
        ];
    }

    private static function object(array $fields): array {
        $properties = [];
        foreach ($fields as $name => $type) {
            $properties[$name] = $type === 'array' ? ['type' => 'array', 'items' => []] : ['type' => $type];
        }
        return ['type' => 'object', 'properties' => $properties];
    }

    private static function buildPaths(): array {
        $paths = [];

        // Identite
        $paths['/me']['get'] = self::op('Identite', 'me:read', 'Profil et token courants', null, '#/components/schemas/User');

        // API Tokens
        $paths['/api-tokens']['get'] = self::op('API Tokens', 'api_tokens:read', 'Lister mes tokens', null, '#/components/schemas/ApiToken', true);
        $paths['/api-tokens']['post'] = self::op('API Tokens', 'api_tokens:write', 'Creer un token (revele le secret une seule fois)', self::bodyApiTokenCreate(), '#/components/schemas/ApiToken');
        $paths['/api-tokens/{id}']['get'] = self::op('API Tokens', 'api_tokens:read', 'Detail token', null, '#/components/schemas/ApiToken', false, true);
        $paths['/api-tokens/{id}']['patch'] = self::op('API Tokens', 'api_tokens:write', 'Modifier un token', self::bodyApiTokenUpdate(), '#/components/schemas/ApiToken', false, true);
        $paths['/api-tokens/{id}/revoke']['post'] = self::op('API Tokens', 'api_tokens:write', 'Revoquer un token', null, null, false, true);
        $paths['/api-tokens/{id}']['delete'] = self::op('API Tokens', 'api_tokens:write', 'Supprimer un token', null, null, false, true);

        // Webhooks
        $paths['/webhooks']['get'] = self::op('Webhooks', 'webhooks:read', 'Lister les webhooks', null, '#/components/schemas/Webhook', true);
        $paths['/webhooks']['post'] = self::op('Webhooks', 'webhooks:write', 'Creer un webhook (revele le secret HMAC une seule fois)', self::bodyWebhookCreate(), '#/components/schemas/Webhook');
        $paths['/webhooks/events']['get'] = self::op('Webhooks', 'webhooks:read', 'Lister les events disponibles');
        $paths['/webhooks/{id}']['get'] = self::op('Webhooks', 'webhooks:read', 'Detail webhook', null, '#/components/schemas/Webhook', false, true);
        $paths['/webhooks/{id}']['patch'] = self::op('Webhooks', 'webhooks:write', 'Modifier un webhook', self::bodyWebhookUpdate(), '#/components/schemas/Webhook', false, true);
        $paths['/webhooks/{id}']['delete'] = self::op('Webhooks', 'webhooks:write', 'Supprimer un webhook', null, null, false, true);
        $paths['/webhooks/{id}/test']['post'] = self::op('Webhooks', 'webhooks:write', 'Envoyer une livraison de test', null, null, false, true);
        $paths['/webhooks/{id}/deliveries']['get'] = self::op('Webhooks', 'webhooks:read', 'Historique de livraisons', null, null, true, true);

        // Repositories
        $paths['/repos']['get'] = self::op('Depots', 'repos:read', 'Lister les depots', null, '#/components/schemas/Repo', true);
        $paths['/repos']['post'] = self::op('Depots', 'repos:write', 'Creer un depot', self::bodyRepoCreate(), '#/components/schemas/Repo');
        $paths['/repos/{id}']['get'] = self::op('Depots', 'repos:read', 'Detail depot', null, '#/components/schemas/Repo', false, true);
        $paths['/repos/{id}']['patch'] = self::op('Depots', 'repos:write', 'Modifier un depot', self::bodyRepoUpdate(), '#/components/schemas/Repo', false, true);
        $paths['/repos/{id}']['delete'] = self::op('Depots', 'repos:write', 'Supprimer un depot', null, null, false, true);
        $paths['/repos/{id}/check']['post'] = self::op('Depots', 'repos:check', 'Lancer restic check', null, null, false, true);
        $paths['/repos/{id}/stats']['get'] = self::op('Depots', 'repos:read', 'Statistiques d un depot', null, null, false, true);

        // Snapshots
        $paths['/repos/{id}/snapshots']['get'] = self::op('Snapshots', 'snapshots:read', 'Lister les snapshots d un depot', null, null, true, true);
        $paths['/repos/{id}/snapshots/{sid}']['get'] = self::op('Snapshots', 'snapshots:read', 'Detail snapshot', null, null, false, true);
        $paths['/repos/{id}/snapshots/{sid}/files']['get'] = self::op('Snapshots', 'snapshots:read', 'Lister les fichiers d un snapshot', null, null, false, true);
        $paths['/repos/{id}/snapshots/{sid}']['delete'] = self::op('Snapshots', 'snapshots:write', 'Supprimer un snapshot', null, null, false, true);
        $paths['/repos/{id}/snapshots/{sid}/tags']['put'] = self::op('Snapshots', 'snapshots:write', 'Modifier les tags d un snapshot', self::bodySnapshotTags(), null, false, true);

        // Backup jobs
        $paths['/backup-jobs']['get'] = self::op('Backup jobs', 'backup_jobs:read', 'Lister les backup jobs', null, '#/components/schemas/BackupJob', true);
        $paths['/backup-jobs']['post'] = self::op('Backup jobs', 'backup_jobs:write', 'Creer un backup job', self::bodyBackupJobCreate(), '#/components/schemas/BackupJob');
        $paths['/backup-jobs/{id}']['get'] = self::op('Backup jobs', 'backup_jobs:read', 'Detail backup job', null, '#/components/schemas/BackupJob', false, true);
        $paths['/backup-jobs/{id}']['patch'] = self::op('Backup jobs', 'backup_jobs:write', 'Modifier un backup job', self::bodyBackupJobUpdate(), '#/components/schemas/BackupJob', false, true);
        $paths['/backup-jobs/{id}']['delete'] = self::op('Backup jobs', 'backup_jobs:write', 'Supprimer un backup job', null, null, false, true);
        $paths['/backup-jobs/{id}/run']['post'] = self::op('Backup jobs', 'backup_jobs:run', 'Declencher l execution', null, null, false, true);

        // Copy jobs
        $paths['/copy-jobs']['get'] = self::op('Copy jobs', 'copy_jobs:read', 'Lister les copy jobs', null, '#/components/schemas/CopyJob', true);
        $paths['/copy-jobs']['post'] = self::op('Copy jobs', 'copy_jobs:write', 'Creer un copy job', self::bodyCopyJobCreate(), '#/components/schemas/CopyJob');
        $paths['/copy-jobs/{id}']['get'] = self::op('Copy jobs', 'copy_jobs:read', 'Detail copy job', null, '#/components/schemas/CopyJob', false, true);
        $paths['/copy-jobs/{id}']['patch'] = self::op('Copy jobs', 'copy_jobs:write', 'Modifier un copy job', self::bodyCopyJobUpdate(), '#/components/schemas/CopyJob', false, true);
        $paths['/copy-jobs/{id}']['delete'] = self::op('Copy jobs', 'copy_jobs:write', 'Supprimer un copy job', null, null, false, true);
        $paths['/copy-jobs/{id}/run']['post'] = self::op('Copy jobs', 'copy_jobs:run', 'Declencher l execution', null, null, false, true);

        // Restores
        $paths['/restores']['get'] = self::op('Restores', 'restores:read', 'Historique des restaurations', null, null, true);
        $paths['/restores']['post'] = self::op('Restores', 'restores:write', 'Lancer une restauration', self::bodyRestoreCreate());
        $paths['/restores/{id}']['get'] = self::op('Restores', 'restores:read', 'Detail restauration', null, null, false, true);

        // Hosts
        $paths['/hosts']['get'] = self::op('Hotes', 'hosts:read', 'Lister les hotes', null, '#/components/schemas/Host', true);
        $paths['/hosts']['post'] = self::op('Hotes', 'hosts:write', 'Creer un hote', self::bodyHostCreate(), '#/components/schemas/Host');
        $paths['/hosts/{id}']['get'] = self::op('Hotes', 'hosts:read', 'Detail hote', null, '#/components/schemas/Host', false, true);
        $paths['/hosts/{id}']['patch'] = self::op('Hotes', 'hosts:write', 'Modifier un hote', self::bodyHostUpdate(), '#/components/schemas/Host', false, true);
        $paths['/hosts/{id}']['delete'] = self::op('Hotes', 'hosts:write', 'Supprimer un hote', null, null, false, true);
        $paths['/hosts/{id}/test']['post'] = self::op('Hotes', 'hosts:read', 'Tester la connexion SSH', null, null, false, true);

        // SSH keys
        $paths['/ssh-keys']['get'] = self::op('Cles SSH', 'ssh_keys:read', 'Lister les cles SSH', null, '#/components/schemas/SshKey', true);
        $paths['/ssh-keys']['post'] = self::op('Cles SSH', 'ssh_keys:write', 'Creer ou importer une cle SSH', self::bodySshKeyCreate(), '#/components/schemas/SshKey');
        $paths['/ssh-keys/{id}']['get'] = self::op('Cles SSH', 'ssh_keys:read', 'Detail cle SSH', null, '#/components/schemas/SshKey', false, true);
        $paths['/ssh-keys/{id}']['delete'] = self::op('Cles SSH', 'ssh_keys:write', 'Supprimer une cle SSH', null, null, false, true);
        $paths['/ssh-keys/{id}/test']['post'] = self::op('Cles SSH', 'ssh_keys:read', 'Tester une cle SSH', null, null, false, true);

        // Scheduler
        $paths['/scheduler/tasks']['get'] = self::op('Scheduler', 'scheduler:read', 'Vue scheduler (taches globales + moteur)');
        $paths['/scheduler/backup-schedules']['get'] = self::op('Scheduler', 'scheduler:read', 'Plannings backup');
        $paths['/scheduler/copy-schedules']['get'] = self::op('Scheduler', 'scheduler:read', 'Plannings copy');
        $paths['/scheduler/cron-log']['get'] = self::op('Scheduler', 'scheduler:read', 'Historique cron recent');
        $paths['/scheduler/tasks/{key}/run']['post'] = self::op('Scheduler', 'scheduler:write', 'Forcer une tache (weekly_report, integrity_check, db_vacuum)', null, null, false, true);

        // Notifications
        $paths['/notifications']['get'] = self::op('Notifications', 'notifications:read', 'Mes notifications', null, '#/components/schemas/Notification', true);
        $paths['/notifications/unread-count']['get'] = self::op('Notifications', 'notifications:read', 'Compteur non lues');
        $paths['/notifications/{id}/read']['post'] = self::op('Notifications', 'notifications:write', 'Marquer comme lue', null, null, false, true);
        $paths['/notifications/read-all']['post'] = self::op('Notifications', 'notifications:write', 'Tout marquer comme lu');
        $paths['/notifications/{id}']['delete'] = self::op('Notifications', 'notifications:write', 'Supprimer une notification', null, null, false, true);
        $paths['/notifications/read']['delete'] = self::op('Notifications', 'notifications:write', 'Supprimer toutes les notifications lues');

        // Stats
        $paths['/stats/summary']['get'] = self::op('Stats', 'stats:read', 'Resume global');
        $paths['/stats/repo-runtime']['get'] = self::op('Stats', 'stats:read', 'Statut runtime des depots');
        $paths['/stats/repos/{id}/history']['get'] = self::op('Stats', 'stats:read', 'Historique stats d un depot', null, null, false, true);

        // Logs
        $paths['/logs/activity']['get'] = self::op('Logs', 'logs:read', 'Activity logs', null, null, true);
        $paths['/logs/cron']['get'] = self::op('Logs', 'logs:read', 'Cron log', null, null, true);
        $paths['/logs/api-tokens']['get'] = self::op('Logs', 'logs:read', 'Audit log des tokens API', null, null, true);

        // Job queue
        $paths['/jobs/summary']['get'] = self::op('Job queue', 'jobs_queue:read', 'Resume file de jobs');
        $paths['/jobs/recent']['get'] = self::op('Job queue', 'jobs_queue:read', 'Jobs recents');
        $paths['/jobs/worker']['get'] = self::op('Job queue', 'jobs_queue:read', 'Heartbeat worker');
        $paths['/jobs/process']['post'] = self::op('Job queue', 'jobs_queue:write', 'Forcer un cycle de traitement', self::bodyJobsProcess());
        $paths['/jobs/recover']['post'] = self::op('Job queue', 'jobs_queue:write', 'Recuperer les jobs running stucks', self::bodyJobsRecover());

        // Users
        $paths['/users']['get'] = self::op('Utilisateurs', 'users:read', 'Lister les utilisateurs', null, '#/components/schemas/User', true);
        $paths['/users']['post'] = self::op('Utilisateurs', 'users:write', 'Creer un utilisateur', self::bodyUserCreate(), '#/components/schemas/User');
        $paths['/users/{id}']['get'] = self::op('Utilisateurs', 'users:read', 'Detail utilisateur', null, '#/components/schemas/User', false, true);
        $paths['/users/{id}']['patch'] = self::op('Utilisateurs', 'users:write', 'Modifier un utilisateur', self::bodyUserUpdate(), '#/components/schemas/User', false, true);
        $paths['/users/{id}']['delete'] = self::op('Utilisateurs', 'users:write', 'Supprimer un utilisateur', null, null, false, true);

        // Settings
        $paths['/settings']['get'] = self::op('Settings', 'settings:read', 'Lister les parametres exposes');
        $paths['/settings/{key}']['get'] = self::op('Settings', 'settings:read', 'Lire un parametre', null, null, false, true, 'key');
        $paths['/settings/{key}']['put'] = self::op('Settings', 'settings:write', 'Modifier un parametre', self::bodySettingUpdate(), null, false, true, 'key');

        return $paths;
    }

    /**
     * @param array{required?:string[], properties:array<string,array>}|null $body
     * Null = no body. Array = inline requestBody schema.
     * Each propriete : ['type'=>..., 'description'=>..., 'default'=>..., 'enum'=>[...]]     */
    private static function op(
        string $tag,
        string $scope,
        string $summary,
        ?array $body = null,
        ?string $itemSchemaRef = null,
        bool $paginated = false,
        bool $hasIdParam = false,
        string $idName = 'id'
    ): array {
        $op = [
            'tags' => [$tag],
            'summary' => $summary,
            'description' => 'Scope requis : `' . $scope . '`',
            'security' => [['BearerAuth' => []]],
            'responses' => [
                '200' => ['description' => 'OK'],
                '401' => ['$ref' => '#/components/responses/Unauthorized'],
                '403' => ['$ref' => '#/components/responses/Forbidden'],
                '429' => ['$ref' => '#/components/responses/RateLimited'],
            ],
        ];
        if ($hasIdParam) {
            $op['parameters'][] = [
                'name' => $idName, 'in' => 'path', 'required' => true,
                'schema' => ['type' => $idName === 'sid' || $idName === 'key' ? 'string' : 'integer'],
            ];
            if ($idName === 'id' && str_contains($summary, 'snapshot')) {
                $op['parameters'][] = [
                    'name' => 'sid', 'in' => 'path', 'required' => true,
                    'schema' => ['type' => 'string'],
                ];
            }
            $op['responses']['404'] = ['$ref' => '#/components/responses/NotFound'];
        }
        if ($paginated) {
            $op['parameters'][] = ['$ref' => '#/components/parameters/PageParam'];
            $op['parameters'][] = ['$ref' => '#/components/parameters/PerPageParam'];
        }
        if ($body !== null) {
            $schema = ['type' => 'object', 'properties' => $body['properties']];
            if (!empty($body['required'])) {
                $schema['required'] = $body['required'];
            }
            $op['requestBody'] = [
                'required' => true,
                'content' => ['application/json' => ['schema' => $schema]],
            ];
            $op['responses']['422'] = ['$ref' => '#/components/responses/ValidationError'];
        }
        if ($itemSchemaRef !== null) {
            $schema = $paginated
                ? ['type' => 'object', 'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => $itemSchemaRef]],
                    'meta' => ['$ref' => '#/components/schemas/Pagination'],
                ]]
                : ['type' => 'object', 'properties' => ['data' => ['$ref' => $itemSchemaRef]]];
            $op['responses']['200'] = [
                'description' => 'OK',
                'content' => ['application/json' => ['schema' => $schema]],
            ];
        }
        return $op;
    }

    // ── Corps of requests documentees ────────────────────────────────────────

    private static function bodyApiTokenCreate(): array {
        return ['required' => ['name', 'scopes'], 'properties' => [
            'name'                  => ['type' => 'string', 'description' => 'Nom descriptif du token'],
            'scopes'                => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Liste des scopes accordes (ex: ["repos:read","backup_jobs:run"])'],
            'read_only'             => ['type' => 'boolean', 'default' => false, 'description' => 'Si true, seuls les scopes en lecture sont conserves'],
            'expires_days'          => ['type' => 'integer', 'default' => 90, 'description' => 'Duree de vie en jours (0 = jamais)'],
            'rate_limit_per_minute' => ['type' => 'integer', 'default' => 120, 'description' => 'Max requetes par minute pour ce token'],
            'allowed_ips'           => ['type' => 'string', 'description' => 'IPs/CIDR autorises, CSV (vide = toutes) — ex: "10.0.0.0/24,192.168.1.10"'],
            'allowed_origins'       => ['type' => 'string', 'description' => 'Origines CORS autorisees, CSV (vide = aucune) — ex: "https://app.example.com"'],
        ]];
    }

    private static function bodyApiTokenUpdate(): array {
        return ['properties' => [
            'name'                  => ['type' => 'string', 'description' => 'Nouveau nom'],
            'scopes'                => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Nouveaux scopes'],
            'read_only'             => ['type' => 'boolean'],
            'rate_limit_per_minute' => ['type' => 'integer'],
            'allowed_ips'           => ['type' => 'string'],
            'allowed_origins'       => ['type' => 'string'],
        ]];
    }

    private static function bodyWebhookCreate(): array {
        return ['required' => ['name', 'url'], 'properties' => [
            'name'    => ['type' => 'string', 'description' => 'Nom du webhook'],
            'url'     => ['type' => 'string', 'format' => 'uri', 'description' => 'URL HTTPS de destination'],
            'events'  => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Evenements a ecouter (vide = tous). Voir GET /webhooks/events'],
            'enabled' => ['type' => 'boolean', 'default' => true],
        ]];
    }

    private static function bodyWebhookUpdate(): array {
        return ['properties' => [
            'name'    => ['type' => 'string'],
            'url'     => ['type' => 'string', 'format' => 'uri'],
            'events'  => ['type' => 'array', 'items' => ['type' => 'string']],
            'enabled' => ['type' => 'boolean'],
        ]];
    }

    private static function bodyRepoCreate(): array {
        return ['required' => ['name', 'path'], 'properties' => [
            'name'                  => ['type' => 'string', 'description' => 'Nom du depot'],
            'path'                  => ['type' => 'string', 'description' => 'Chemin restic (local ou sftp:user@host:/path)'],
            'password'              => ['type' => 'string', 'description' => 'Mot de passe restic (si password_source = plain ou file)'],
            'description'           => ['type' => 'string'],
            'alert_hours'           => ['type' => 'integer', 'default' => 25, 'description' => 'Alerte si aucun backup depuis N heures'],
            'notify_email'          => ['type' => 'boolean', 'default' => false],
            'password_source'       => ['type' => 'string', 'enum' => ['file', 'plain', 'env', 'infisical'], 'default' => 'file'],
            'infisical_secret_name' => ['type' => 'string', 'description' => 'Nom du secret Infisical (si password_source = infisical)'],
        ]];
    }

    private static function bodyRepoUpdate(): array {
        return ['properties' => [
            'name'                => ['type' => 'string'],
            'path'                => ['type' => 'string'],
            'description'         => ['type' => 'string'],
            'alert_hours'         => ['type' => 'integer'],
            'notify_email'        => ['type' => 'boolean'],
            'notification_policy' => ['type' => 'string'],
        ]];
    }

    private static function bodyBackupJobCreate(): array {
        return ['required' => ['name', 'repo_id'], 'properties' => [
            'name'              => ['type' => 'string', 'description' => 'Nom du backup job'],
            'repo_id'           => ['type' => 'integer', 'description' => 'ID du depot de destination'],
            'host_id'           => ['type' => 'integer', 'description' => 'ID de l hote source (null = local)'],
            'source_paths'      => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Chemins a sauvegarder'],
            'tags'              => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Tags restic'],
            'excludes'          => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Patterns a exclure'],
            'description'       => ['type' => 'string'],
            'schedule_enabled'  => ['type' => 'boolean', 'default' => false],
            'schedule_hour'     => ['type' => 'integer', 'default' => 2, 'description' => 'Heure d execution (0-23)'],
            'schedule_days'     => ['type' => 'string', 'default' => '1', 'description' => 'Jours de la semaine CSV (1=Lun … 7=Dim) ou "1,2,3,4,5"'],
            'notify_on_failure' => ['type' => 'boolean', 'default' => false],
            'remote_repo_path'  => ['type' => 'string', 'description' => 'Chemin repo sur l hote distant (si host_id defini)'],
            'hostname_override' => ['type' => 'string', 'description' => 'Hostname restic personnalise'],
        ]];
    }

    private static function bodyBackupJobUpdate(): array {
        return ['properties' => [
            'name'              => ['type' => 'string'],
            'repo_id'           => ['type' => 'integer'],
            'host_id'           => ['type' => 'integer'],
            'source_paths'      => ['type' => 'array', 'items' => ['type' => 'string']],
            'tags'              => ['type' => 'array', 'items' => ['type' => 'string']],
            'excludes'          => ['type' => 'array', 'items' => ['type' => 'string']],
            'description'       => ['type' => 'string'],
            'schedule_enabled'  => ['type' => 'boolean'],
            'schedule_hour'     => ['type' => 'integer'],
            'schedule_days'     => ['type' => 'string'],
            'notify_on_failure' => ['type' => 'boolean'],
            'remote_repo_path'  => ['type' => 'string'],
            'hostname_override' => ['type' => 'string'],
        ]];
    }

    private static function bodyCopyJobCreate(): array {
        return ['required' => ['name', 'source_repo_id', 'dest_path'], 'properties' => [
            'name'             => ['type' => 'string'],
            'source_repo_id'   => ['type' => 'integer', 'description' => 'ID du depot source'],
            'dest_path'        => ['type' => 'string', 'description' => 'Chemin restic de destination'],
            'dest_password'    => ['type' => 'string', 'description' => 'Mot de passe du depot de destination'],
            'description'      => ['type' => 'string'],
            'schedule_enabled' => ['type' => 'boolean', 'default' => false],
            'schedule_hour'    => ['type' => 'integer', 'default' => 2],
            'schedule_days'    => ['type' => 'string', 'default' => '1'],
        ]];
    }

    private static function bodyCopyJobUpdate(): array {
        return ['properties' => [
            'name'             => ['type' => 'string'],
            'source_repo_id'   => ['type' => 'integer'],
            'dest_path'        => ['type' => 'string'],
            'dest_password'    => ['type' => 'string'],
            'description'      => ['type' => 'string'],
            'schedule_enabled' => ['type' => 'boolean'],
            'schedule_hour'    => ['type' => 'integer'],
            'schedule_days'    => ['type' => 'string'],
        ]];
    }

    private static function bodyHostCreate(): array {
        return ['required' => ['name', 'hostname'], 'properties' => [
            'name'        => ['type' => 'string'],
            'hostname'    => ['type' => 'string', 'description' => 'Adresse IP ou FQDN'],
            'port'        => ['type' => 'integer', 'default' => 22],
            'user'        => ['type' => 'string', 'default' => 'root'],
            'ssh_key_id'  => ['type' => 'integer', 'description' => 'ID de la cle SSH a utiliser'],
            'restore_original_enabled' => ['type' => 'boolean', 'default' => false, 'description' => 'Permet les restaurations "original" sur cet hote (admin only)'],
            'description' => ['type' => 'string'],
        ]];
    }

    private static function bodyHostUpdate(): array {
        return ['properties' => [
            'name'        => ['type' => 'string'],
            'hostname'    => ['type' => 'string'],
            'port'        => ['type' => 'integer'],
            'user'        => ['type' => 'string'],
            'ssh_key_id'  => ['type' => 'integer'],
            'restore_original_enabled' => ['type' => 'boolean', 'description' => 'Permet les restaurations "original"'],
            'description' => ['type' => 'string'],
        ]];
    }

    private static function bodySshKeyCreate(): array {
        return ['required' => ['name', 'host'], 'properties' => [
            'name'        => ['type' => 'string'],
            'host'        => ['type' => 'string', 'description' => 'Adresse de l hote cible (pour le known_hosts)'],
            'user'        => ['type' => 'string', 'default' => 'root'],
            'port'        => ['type' => 'integer', 'default' => 22],
            'private_key' => ['type' => 'string', 'description' => 'Cle privee PEM a importer. Si absent, une paire est generee automatiquement.'],
            'description' => ['type' => 'string'],
        ]];
    }

    private static function bodyRestoreCreate(): array {
        return ['required' => ['repo_id', 'snapshot_id'], 'properties' => [
            'repo_id'     => ['type' => 'integer', 'description' => 'ID du depot'],
            'snapshot_id' => ['type' => 'string', 'description' => 'ID du snapshot restic (ou "latest")'],
            'target'      => ['type' => 'string', 'default' => '/', 'description' => 'Repertoire de destination de la restauration'],
            'include'     => ['type' => 'string', 'description' => 'Filtre de chemin a inclure (ex: /var/www)'],
        ]];
    }

    private static function bodyUserCreate(): array {
        return ['required' => ['username', 'password'], 'properties' => [
            'username'   => ['type' => 'string'],
            'password'   => ['type' => 'string', 'description' => 'Minimum 8 caracteres'],
            'role'       => ['type' => 'string', 'enum' => ['viewer', 'operator', 'admin'], 'default' => 'viewer'],
            'email'      => ['type' => 'string', 'format' => 'email'],
            'first_name' => ['type' => 'string'],
            'last_name'  => ['type' => 'string'],
        ]];
    }

    private static function bodyUserUpdate(): array {
        return ['properties' => [
            'password'   => ['type' => 'string', 'description' => 'Minimum 8 caracteres'],
            'role'       => ['type' => 'string', 'enum' => ['viewer', 'operator', 'admin']],
            'email'      => ['type' => 'string', 'format' => 'email'],
            'first_name' => ['type' => 'string'],
            'last_name'  => ['type' => 'string'],
        ]];
    }

    private static function bodySnapshotTags(): array {
        return ['required' => ['tags'], 'properties' => [
            'tags' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Nouveaux tags (remplace les existants)'],
        ]];
    }

    private static function bodySettingUpdate(): array {
        return ['required' => ['value'], 'properties' => [
            'value' => ['description' => 'Nouvelle valeur du parametre'],
        ]];
    }

    private static function bodyJobsProcess(): array {
        return ['properties' => [
            'limit' => ['type' => 'integer', 'default' => 3, 'description' => 'Nombre max de jobs a traiter'],
            'types' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Types de jobs a filtrer (vide = tous)'],
        ]];
    }

    private static function bodyJobsRecover(): array {
        return ['properties' => [
            'older_than_minutes' => ['type' => 'integer', 'default' => 30, 'description' => 'Recuperer les jobs en etat running depuis plus de N minutes'],
        ]];
    }

    private static function errorResponse(string $description): array {
        return [
            'description' => $description,
            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]],
        ];
    }
}
