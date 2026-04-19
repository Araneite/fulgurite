<?php
// =============================================================================
// ApiScopes.php — catalog of scopes granulaires of the public API
// =============================================================================

class ApiScopes {

    /**
     * API scopes catalog. Each scope contains:
     * - label: UI label
     * - group: UI section
     * - read_only: true if the scope only performs reads
     * - permission: required user permission (RBAC mapping)
     */
    public static function catalog(): array {
        return [
            // Identite ----------------------------------------------------
            'me:read'                => ['label' => 'Lire mon profil',                    'group' => 'Identite',     'read_only' => true,  'permission' => null],

            // Repositories ------------------------------------------------
            'repos:read'             => ['label' => 'Lister / lire les depots',           'group' => 'Depots',       'read_only' => true,  'permission' => 'repos.view'],
            'repos:write'            => ['label' => 'Creer / modifier / supprimer des depots', 'group' => 'Depots',  'read_only' => false, 'permission' => 'repos.manage'],
            'repos:check'            => ['label' => 'Lancer un check de depot',           'group' => 'Depots',       'read_only' => false, 'permission' => 'repos.manage'],

            // Snapshots ---------------------------------------------------
            'snapshots:read'         => ['label' => 'Lister les snapshots',               'group' => 'Snapshots',    'read_only' => true,  'permission' => 'repos.view'],
            'snapshots:write'        => ['label' => 'Modifier tags / supprimer snapshots', 'group' => 'Snapshots',   'read_only' => false, 'permission' => 'snapshots.manage'],

            // Backup jobs -------------------------------------------------
            'backup_jobs:read'       => ['label' => 'Lister les backup jobs',             'group' => 'Backup jobs',  'read_only' => true,  'permission' => 'backup_jobs.manage'],
            'backup_jobs:write'      => ['label' => 'Creer / modifier / supprimer des backup jobs', 'group' => 'Backup jobs', 'read_only' => false, 'permission' => 'backup_jobs.manage'],
            'backup_jobs:run'        => ['label' => 'Declencher un backup job',           'group' => 'Backup jobs',  'read_only' => false, 'permission' => 'backup_jobs.manage'],

            // Copy jobs ---------------------------------------------------
            'copy_jobs:read'         => ['label' => 'Lister les copy jobs',               'group' => 'Copy jobs',    'read_only' => true,  'permission' => 'copy_jobs.manage'],
            'copy_jobs:write'        => ['label' => 'Creer / modifier / supprimer des copy jobs',   'group' => 'Copy jobs',   'read_only' => false, 'permission' => 'copy_jobs.manage'],
            'copy_jobs:run'          => ['label' => 'Declencher un copy job',             'group' => 'Copy jobs',    'read_only' => false, 'permission' => 'copy_jobs.manage'],

            // Restores ----------------------------------------------------
            'restores:read'          => ['label' => 'Lire l historique des restores',     'group' => 'Restores',     'read_only' => true,  'permission' => 'restore.view'],
            'restores:write'         => ['label' => 'Lancer des restores',                'group' => 'Restores',     'read_only' => false, 'permission' => 'restore.run'],

            // Hosts -------------------------------------------------------
            'hosts:read'             => ['label' => 'Lister les hotes',                   'group' => 'Hotes',        'read_only' => true,  'permission' => 'hosts.manage'],
            'hosts:write'            => ['label' => 'Gerer les hotes',                    'group' => 'Hotes',        'read_only' => false, 'permission' => 'hosts.manage'],

            // SSH keys ----------------------------------------------------
            'ssh_keys:read'          => ['label' => 'Lister les cles SSH',                'group' => 'Cles SSH',     'read_only' => true,  'permission' => 'sshkeys.manage'],
            'ssh_keys:write'         => ['label' => 'Gerer les cles SSH',                 'group' => 'Cles SSH',     'read_only' => false, 'permission' => 'sshkeys.manage'],

            // Scheduler ---------------------------------------------------
            'scheduler:read'         => ['label' => 'Voir la planification',              'group' => 'Scheduler',    'read_only' => true,  'permission' => 'scheduler.manage'],
            'scheduler:write'        => ['label' => 'Lancer une tache planifiee',         'group' => 'Scheduler',    'read_only' => false, 'permission' => 'scheduler.manage'],

            // Notifications -----------------------------------------------
            'notifications:read'     => ['label' => 'Lire mes notifications',             'group' => 'Notifications','read_only' => true,  'permission' => null],
            'notifications:write'    => ['label' => 'Marquer / supprimer mes notifications', 'group' => 'Notifications', 'read_only' => false, 'permission' => null],

            // Stats / logs / queue ---------------------------------------
            'stats:read'             => ['label' => 'Lire les statistiques',              'group' => 'Observabilite','read_only' => true,  'permission' => 'stats.view'],
            'logs:read'              => ['label' => 'Lire les activity logs',             'group' => 'Observabilite','read_only' => true,  'permission' => 'logs.view'],
            'jobs_queue:read'        => ['label' => 'Voir la job queue',                  'group' => 'Observabilite','read_only' => true,  'permission' => 'performance.view'],
            'jobs_queue:write'       => ['label' => 'Forcer le traitement de la file',    'group' => 'Observabilite','read_only' => false, 'permission' => 'settings.manage'],

            // Admin -------------------------------------------------------
            'users:read'             => ['label' => 'Lister les utilisateurs',            'group' => 'Administration','read_only' => true,  'permission' => 'users.manage'],
            'users:write'            => ['label' => 'Gerer les utilisateurs',             'group' => 'Administration','read_only' => false, 'permission' => 'users.manage'],
            'api_tokens:read'        => ['label' => 'Lister mes tokens API',              'group' => 'Administration','read_only' => true,  'permission' => null],
            'api_tokens:write'       => ['label' => 'Creer / revoquer mes tokens API',    'group' => 'Administration','read_only' => false, 'permission' => null],
            'webhooks:read'          => ['label' => 'Lister les webhooks',                'group' => 'Administration','read_only' => true,  'permission' => 'settings.manage'],
            'webhooks:write'         => ['label' => 'Gerer les webhooks',                 'group' => 'Administration','read_only' => false, 'permission' => 'settings.manage'],
            'settings:read'          => ['label' => 'Lire les parametres',                'group' => 'Administration','read_only' => true,  'permission' => 'settings.manage'],
            'settings:write'         => ['label' => 'Modifier les parametres',            'group' => 'Administration','read_only' => false, 'permission' => 'settings.manage'],
        ];
    }

    public static function all(): array {
        return array_keys(self::catalog());
    }

    public static function exists(string $scope): bool {
        return array_key_exists($scope, self::catalog());
    }

    public static function isReadOnly(string $scope): bool {
        $cat = self::catalog();
        return !empty($cat[$scope]['read_only']);
    }

    public static function requiredPermission(string $scope): ?string {
        $cat = self::catalog();
        return $cat[$scope]['permission'] ?? null;
    }

    /** Filters a scope list to keep only scopes compatible with user permissions. */
    public static function filterAllowedForUser(array $scopes, array $userPermissions): array {
        $allowed = [];
        foreach ($scopes as $scope) {
            $scope = trim((string) $scope);
            if (!self::exists($scope)) continue;
            $required = self::requiredPermission($scope);
            if ($required === null || !empty($userPermissions[$required])) {
                $allowed[] = $scope;
            }
        }
        return array_values(array_unique($allowed));
    }

    /** All scopes a user can potentially obtain, given their permissions. */
    public static function maxScopesForUser(array $userPermissions): array {
        $result = [];
        foreach (self::catalog() as $scope => $meta) {
            $required = $meta['permission'] ?? null;
            if ($required === null || !empty($userPermissions[$required])) {
                $result[] = $scope;
            }
        }
        return $result;
    }

    /** Groupes for affichage UI. */
    public static function grouped(): array {
        $groups = [];
        foreach (self::catalog() as $scope => $meta) {
            $groups[$meta['group']][] = ['scope' => $scope] + $meta;
        }
        return $groups;
    }
}
