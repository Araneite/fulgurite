<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
Auth::requirePermission('repos.view');
verifyCsrf();
rateLimitApi('search_files', 90, 60);

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId   = (int) ($data['repo_id']  ?? 0);
$snapshot = $data['snapshot'] ?? '';
$query    = trim($data['query'] ?? '');

if (!$repoId || !$snapshot || !$query) {
    jsonResponse(['error' => t('api.search_files.error.required_params')], 400);
}

if (strlen($query) < 2) {
    jsonResponse(['error' => t('api.search_files.error.query_too_short')], 400);
}

$repo = RepoManager::getById($repoId);
if (!$repo) jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);

Auth::requireRepoAccess($repoId);
$results = SnapshotSearchIndex::search($repoId, $snapshot, $query);
if ($results === null) {
    JobQueue::enqueueSnapshotFullIndex($repoId, $snapshot, 'search_missing_index', 210);
    jsonResponse([
        'status' => 'pending',
        'message' => t('api.search_files.message.index_building'),
        'results' => [],
        'count' => 0,
    ], 202);
}

if (isset($results['error'])) {
    jsonResponse(['error' => $results['error']], 500);
}

jsonResponse(['results' => $results, 'count' => count($results)]);
