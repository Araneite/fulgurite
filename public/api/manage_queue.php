<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
Auth::requirePermission('repos.manage');
verifyCsrf();
rateLimitApi('manage_queue', 30, 60);

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = (string) ($data['action'] ?? '');

if ($action === 'enqueue_snapshot_refresh') {
    $repoId = (int) ($data['repo_id'] ?? 0);
    if (!$repoId) {
        jsonResponse(['error' => t('api.manage_queue.error.repo_id_required')], 400);
    }

    $repo = RepoManager::getById($repoId);
    if (!$repo) {
        jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);
    }

    Auth::requireRepoAccess($repoId);

    $jobId = JobQueue::enqueueRepoSnapshotRefresh($repoId, 'manual', 100);

    Auth::log('queue_enqueue', "Refresh snapshot manuel enfilé pour le dépôt {$repo['name']} (repo_id=$repoId, job_id=$jobId)");

    jsonResponse(['success' => true, 'queued' => true, 'job_id' => $jobId]);
}

jsonResponse(['error' => t('api.common.error.unknown_action')], 400);
