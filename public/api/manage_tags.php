<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();

$data       = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId     = (int) ($data['repo_id']     ?? 0);
$snapshotId = $data['snapshot_id'] ?? '';
$action     = $data['action']      ?? 'add'; // add | remove | set
$tags       = $data['tags']        ?? [];

if (!$repoId || !$snapshotId || empty($tags)) {
    jsonResponse(['error' => t('api.manage_tags.error.required_params')], 400);
}

if (!is_array($tags)) $tags = [$tags];
$tags = array_filter(array_map('trim', $tags));

$repo   = RepoManager::getById($repoId);
if (!$repo) jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);

$restic = RepoManager::getRestic($repo);

$result = match($action) {
    'add'    => $restic->addTags($snapshotId, $tags),
    'remove' => $restic->removeTags($snapshotId, $tags),
    'set'    => $restic->setTags($snapshotId, $tags),
    default  => ['success' => false, 'output' => t('api.common.error.invalid_action')],
};

Auth::log('snapshot_tag', "$action tags [" . implode(',', $tags) . "] sur $snapshotId ({$repo['name']})");
jsonResponse($result);
