<?php
// =============================================================================
// ReposHandler.php — /api/v1/repos
// =============================================================================

class ReposHandler {

    public static function publicView(array $repo): array {
        return [
            'id' => (int) $repo['id'],
            'name' => $repo['name'],
            'path' => $repo['path'],
            'description' => $repo['description'] ?? null,
            'alert_hours' => isset($repo['alert_hours']) ? (int) $repo['alert_hours'] : null,
            'notify_email' => (bool) ($repo['notify_email'] ?? 0),
            'password_source' => $repo['password_source'] ?? 'agent',
            'created_at' => $repo['created_at'] ?? null,
        ];
    }

    public static function index(array $args): void {
        ApiAuth::requireScope('repos:read');
        $repos = ApiAuth::filterAllowedRepos(RepoManager::getAll());
        ApiResponse::ok(array_map([self::class, 'publicView'], $repos));
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('repos:read');
        $id = (int) $args['id'];
        ApiAuth::requireRepoAccess($id);
        $repo = RepoManager::getById($id);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        ApiResponse::ok(self::publicView($repo));
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('repos:write');
        ApiAuth::requireRepoCreateAccess();
        $body = ApiRequest::body();
        $name = trim((string) ($body['name'] ?? ''));
        $path = trim((string) ($body['path'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        if ($name === '' || $path === '') {
            ApiResponse::error(422, 'validation_error', 'Champs name et path requis');
        }
        if (ApiRequest::isDryRun()) {
            ApiResponse::ok(['dry_run' => true]);
        }
        $id = RepoManager::add(
            $name,
            $path,
            $password,
            (string) ($body['description'] ?? ''),
            (int) ($body['alert_hours'] ?? 25),
            !empty($body['notify_email']) ? 1 : 0,
            (string) ($body['password_source'] ?? 'agent'),
            (string) ($body['infisical_secret_name'] ?? ''),
        );
        ApiWebhookManager::dispatch('repo.created', ['id' => $id, 'name' => $name]);
        Auth::log('api_repo_created', "Depot $name cree via API", 'info');
        ApiResponse::created(self::publicView(RepoManager::getById($id)));
    }

    public static function update(array $args): void {
        ApiAuth::requireScope('repos:write');
        $id = (int) $args['id'];
        ApiAuth::requireRepoAccess($id);
        $repo = RepoManager::getById($id);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');

        $body = ApiRequest::body();
        $fields = [];
        $values = [];
        foreach (['name', 'path', 'description', 'alert_hours', 'notify_email', 'notification_policy'] as $f) {
            if (array_key_exists($f, $body)) {
                if ($f === 'path') {
                    $nextPath = trim((string) $body[$f]);
                    if ($nextPath === '') {
                        ApiResponse::error(422, 'validation_error', 'Champ path invalide');
                    }
                    if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $nextPath) && !preg_match('~^[^/]+:.+~', $nextPath)) {
                        if (is_dir($nextPath)) {
                            FilesystemScopeGuard::assertPathAllowed($nextPath, 'read', true);
                        } else {
                            FilesystemScopeGuard::assertPathCreatable($nextPath, 'write');
                        }
                    }
                    $body[$f] = $nextPath;
                }
                $fields[] = "$f = ?";
                $values[] = $body[$f];
            }
        }
        if ($fields) {
            $values[] = $id;
            Database::getInstance()
                ->prepare('UPDATE repos SET ' . implode(', ', $fields) . ' WHERE id = ?')
                ->execute($values);
        }
        ApiResponse::ok(self::publicView(RepoManager::getById($id)));
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('repos:write');
        $id = (int) $args['id'];
        ApiAuth::requireRepoAccess($id);
        $repo = RepoManager::getById($id);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true, 'would_delete' => self::publicView($repo)]);
        RepoManager::delete($id);
        ApiWebhookManager::dispatch('repo.deleted', ['id' => $id, 'name' => $repo['name']]);
        Auth::log('api_repo_deleted', "Depot {$repo['name']} supprime via API", 'warning');
        ApiResponse::noContent();
    }

    public static function check(array $args): void {
        ApiAuth::requireScope('repos:check');
        $id = (int) $args['id'];
        ApiAuth::requireRepoAccess($id);
        $repo = RepoManager::getById($id);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        $restic = RepoManager::getRestic($repo);
        $result = $restic->check();
        if (empty($result['success'])) {
            ApiWebhookManager::dispatch('repo.check.failure', ['id' => $id, 'name' => $repo['name'], 'output' => $result['output'] ?? '']);
        }
        ApiResponse::ok($result);
    }

    public static function stats(array $args): void {
        ApiAuth::requireScope('repos:read');
        $id = (int) $args['id'];
        ApiAuth::requireRepoAccess($id);
        $repo = RepoManager::getById($id);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        $restic = RepoManager::getRestic($repo);
        ApiResponse::ok($restic->stats());
    }
}
