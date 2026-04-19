<?php
// =============================================================================
// SnapshotsHandler.php — /api/v1/repos/{id}/snapshots
// =============================================================================

class SnapshotsHandler {

    public static function index(array $args): void {
        ApiAuth::requireScope('snapshots:read');
        $repoId = (int) $args['id'];
        ApiAuth::requireRepoAccess($repoId);
        $repo = RepoManager::getById($repoId);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        $restic = RepoManager::getRestic($repo);
        $snapshots = $restic->snapshots();
        if (isset($snapshots['error'])) {
            ApiResponse::error(502, 'restic_error', $snapshots['error']);
        }
        ApiResponse::ok($snapshots);
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('snapshots:read');
        $repoId = (int) $args['id'];
        ApiAuth::requireRepoAccess($repoId);
        $repo = RepoManager::getById($repoId);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        $snapshotId = (string) $args['sid'];
        $restic = RepoManager::getRestic($repo);
        $snapshots = $restic->snapshots();
        $snapshot = null;
        foreach ($snapshots as $s) {
            if (($s['short_id'] ?? '') === $snapshotId || ($s['id'] ?? '') === $snapshotId) {
                $snapshot = $s;
                break;
            }
        }
        if (!$snapshot) ApiResponse::error(404, 'not_found', 'Snapshot introuvable');
        ApiResponse::ok($snapshot);
    }

    public static function listFiles(array $args): void {
        ApiAuth::requireScope('snapshots:read');
        $repoId = (int) $args['id'];
        ApiAuth::requireRepoAccess($repoId);
        $repo = RepoManager::getById($repoId);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        $snapshotId = (string) $args['sid'];
        $path = (string) ApiRequest::query('path', '/');
        $restic = RepoManager::getRestic($repo);
        $files = $restic->ls($snapshotId, $path);
        if (isset($files['error'])) {
            ApiResponse::error(502, 'restic_error', $files['error']);
        }
        ApiResponse::ok($files);
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('snapshots:write');

        // Snapshot deletion is admin-only
        $caller = ApiAuth::currentUser();
        if (AppConfig::getRoleLevel((string) ($caller['role'] ?? ''), 0) < AppConfig::getRoleLevel(ROLE_ADMIN, 40)) {
            ApiResponse::error(403, 'admin_required',
                'La suppression de snapshots est réservée aux administrateurs.');
        }

        $repoId = (int) $args['id'];
        ApiAuth::requireRepoAccess($repoId);
        $repo = RepoManager::getById($repoId);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        $snapshotId = (string) $args['sid'];
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $restic = RepoManager::getRestic($repo);
        $result = $restic->deleteSnapshot([$snapshotId]);
        ApiWebhookManager::dispatch('snapshot.deleted', ['repo_id' => $repoId, 'snapshot_id' => $snapshotId]);
        Auth::log('api_snapshot_deleted', "Snapshot $snapshotId supprime du depot {$repo['name']} via API", 'warning');
        ApiResponse::ok($result);
    }

    public static function setTags(array $args): void {
        ApiAuth::requireScope('snapshots:write');
        $repoId = (int) $args['id'];
        ApiAuth::requireRepoAccess($repoId);
        $repo = RepoManager::getById($repoId);
        if (!$repo) ApiResponse::error(404, 'not_found', 'Depot introuvable');
        $snapshotId = (string) $args['sid'];
        $body = ApiRequest::body();
        $tags = (array) ($body['tags'] ?? []);
        $mode = (string) ($body['mode'] ?? 'set'); // set | add | remove
        $restic = RepoManager::getRestic($repo);
        $result = match ($mode) {
            'add' => $restic->addTags($snapshotId, $tags),
            'remove' => $restic->removeTags($snapshotId, $tags),
            default => $restic->setTags($snapshotId, $tags),
        };
        ApiResponse::ok($result);
    }
}
