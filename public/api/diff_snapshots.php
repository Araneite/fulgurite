<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
Auth::requirePermission('repos.view');
verifyCsrf();

$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId    = (int) ($data['repo_id']    ?? 0);
$snapshotA = $data['snapshot_a'] ?? '';
$snapshotB = $data['snapshot_b'] ?? '';

if (!$repoId || !$snapshotA || !$snapshotB) {
    jsonResponse(['error' => t('api.diff_snapshots.error.required_params')], 400);
}

$repo = RepoManager::getById($repoId);
if (!$repo) jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);

Auth::requireRepoAccess($repoId);
$restic = RepoManager::getRestic($repo);
$result = $restic->diff($snapshotA, $snapshotB);

Auth::log('snapshot_diff', "Diff $snapshotA ↔ $snapshotB sur {$repo['name']}");

jsonResponse($result);
