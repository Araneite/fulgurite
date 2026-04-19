<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();
rateLimitApi('delete_snapshot', 20, 60);

$data        = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId      = (int) ($data['repo_id'] ?? 0);
$snapshotIds = $data['snapshot_ids'] ?? [];

if (!$repoId || empty($snapshotIds)) {
    jsonResponse(['error' => 'repo_id et snapshot_ids requis'], 400);
}

if (!is_array($snapshotIds)) {
    $snapshotIds = [$snapshotIds];
}

// Validate IDs (hexadecimal only)
foreach ($snapshotIds as $id) {
    if (!preg_match('/^[a-f0-9]+$/', $id)) {
        jsonResponse(['error' => "ID snapshot invalide: $id"], 400);
    }
}

$repo = RepoManager::getById($repoId);
if (!$repo) {
    jsonResponse(['error' => 'Depot introuvable'], 404);
}

$restic = RepoManager::getRestic($repo);
$result = $restic->deleteSnapshot($snapshotIds);

if (!empty($result['success'])) {
    RepoSnapshotCatalog::deleteSnapshots($repoId, $snapshotIds);
    SnapshotSearchIndex::deleteSnapshots($repoId, $snapshotIds);
    deleteSnapshotClearExploreCache();

    $remainingSnapshots = RepoSnapshotCatalog::getSnapshots($repoId);
    RepoStatusService::upsertStatuses([
        deleteSnapshotBuildRepoRuntimeStatus($repo, $remainingSnapshots),
    ]);
}

Auth::log(
    'snapshot_delete',
    count($snapshotIds) . ' snapshot(s) supprime(s): ' . implode(', ', $snapshotIds) .
    " sur {$repo['name']}"
);

jsonResponse($result);

function deleteSnapshotBuildRepoRuntimeStatus(array $repo, array $snapshots): array {
    $count = count($snapshots);
    $lastSnapshot = $count > 0 ? $snapshots[0] : null;
    $alertHours = (int) ($repo['alert_hours'] ?? AppConfig::backupAlertHours());
    $hoursAgo = null;
    $status = 'ok';

    if ($count === 0) {
        $status = 'no_snap';
    } elseif ($lastSnapshot && !empty($lastSnapshot['time'])) {
        try {
            $diff = (new DateTime())->getTimestamp() - (new DateTime($lastSnapshot['time']))->getTimestamp();
            $hoursAgo = round($diff / 3600, 1);
            if ($hoursAgo > $alertHours) {
                $status = 'warning';
            }
        } catch (Exception $e) {
            $hoursAgo = null;
        }
    }

    return [
        'id' => (int) $repo['id'],
        'name' => (string) ($repo['name'] ?? ''),
        'count' => $count,
        'last_time' => $lastSnapshot['time'] ?? null,
        'hours_ago' => $hoursAgo,
        'status' => $status,
        'repo' => $repo,
    ];
}

function deleteSnapshotClearExploreCache(): void {
    $cacheDir = dirname(DB_PATH) . '/cache/explore';
    foreach (glob($cacheDir . '/*.json') ?: [] as $path) {
        FileSystem::deleteFile($path);
    }
}
