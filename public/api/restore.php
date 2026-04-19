<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/RestoreTargetPlanner.php';
Auth::requirePermission('restore.run');
verifyCsrf();
rateLimitApi('restore', 5, 60);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId = (int) ($data['repo_id'] ?? 0);
$snapshot = (string) ($data['snapshot'] ?? '');
$include = (string) ($data['include'] ?? '');
$mode = ((string) ($data['mode'] ?? 'local')) === 'remote' ? 'remote' : 'local';
$hostId = (int) ($data['host_id'] ?? 0);
$destinationMode = RestoreTargetPlanner::normalizeStrategy((string) ($data['destination_mode'] ?? RestoreTargetPlanner::STRATEGY_MANAGED));
$appendContextSubdir = array_key_exists('append_context_subdir', $data)
    ? !empty($data['append_context_subdir'])
    : AppConfig::restoreAppendContextSubdir();

if ($repoId <= 0 || $snapshot === '') {
    jsonResponse(['error' => t('api.restore.error.missing_params')], 400);
}

$repo = RepoManager::getById($repoId);
if (!$repo) {
    jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);
}

Auth::requireRepoAccess($repoId);

$db = Database::getInstance();
$user = Auth::currentUser();
$host = null;
$key = null;
if ($mode === 'remote') {
    if ($hostId <= 0) {
        jsonResponse(['error' => t('api.restore.error.remote_host_required')], 422);
    }

    $host = HostManager::getById($hostId);
    if (!$host) {
        jsonResponse(['error' => t('api.common.error.host_not_found')], 404);
    }

    Auth::requireHostAccess($hostId);
    if (empty($host['ssh_key_id'])) {
        jsonResponse(['error' => t('api.common.error.no_ssh_key_on_host')], 422);
    }

    $key = $host;
    $key['host'] = (string) ($host['hostname'] ?? '');
}

try {
    if ($destinationMode === RestoreTargetPlanner::STRATEGY_ORIGINAL) {
        if (empty($data['preview_confirmed'])) {
            jsonResponse(['error' => t('api.restore.error.original_preview_required')], 422);
        }
        RestoreTargetPlanner::assertOriginalConfirmation((string) ($data['original_confirmation'] ?? ''));
    }

    $plan = RestoreTargetPlanner::plan([
        'mode' => $mode,
        'destination_mode' => $destinationMode,
        'append_context_subdir' => $appendContextSubdir,
        'repo_id' => $repoId,
        'repo' => $repo,
        'snapshot' => RestoreTargetPlanner::findSnapshot($repoId, $snapshot),
        'ssh_key' => $key,
        'host' => $host,
        'include' => $include,
        'can_restore_original' => Auth::isAdmin(),
        'preview_confirmed' => !empty($data['preview_confirmed']),
    ]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 422);
}

$preflight = DiskSpaceMonitor::preflightRestore($repo, $snapshot, $plan['mode'], $plan['effective_target'], $host);
if (empty($preflight['allowed'])) {
    jsonResponse(['error' => (string) ($preflight['message'] ?? t('api.common.error.insufficient_disk_space'))], 422);
}

$db->prepare("
    INSERT INTO restore_history
    (repo_id, repo_name, snapshot_id, mode, target, include_path, remote_host, remote_user, remote_path, remote_host_id, status, started_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'running', ?)
")->execute([
    $repoId,
    $repo['name'],
    $snapshot,
    RestoreTargetPlanner::buildModeTag(false, $plan['mode'], $plan['strategy']),
    $plan['mode'] === 'local' ? $plan['effective_target'] : null,
    $include !== '' ? $include : null,
    $key['host'] ?? null,
    $key['user'] ?? null,
    $plan['mode'] === 'remote' ? $plan['effective_target'] : null,
    $hostId,
    $user['username'],
]);
$histId = (int) $db->lastInsertId();

$scriptPath = __DIR__ . '/run_restore_background.php';
$launch = ProcessRunner::startBackgroundPhp($scriptPath, [$histId]);
if (empty($launch['success'])) {
    jsonResponse(['error' => t('api.restore.error.background_launch_failed')], 500);
}

jsonResponse([
    'success' => true,
    'histId'  => $histId,
    'message' => t('api.restore.message.background_started'),
    'destination_mode' => $plan['strategy'],
    'resolved_target' => $plan['effective_target'],
    'preview_paths' => $plan['preview_paths'],
]);
