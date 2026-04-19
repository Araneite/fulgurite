<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/RestoreTargetPlanner.php';
Auth::requirePermission('restore.run');
verifyCsrf();
rateLimitApi('restore_partial', 5, 60);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId = (int) ($data['repo_id'] ?? 0);
$snapshot = (string) ($data['snapshot'] ?? '');
$files = array_values(array_filter(array_map('strval', (array) ($data['files'] ?? []))));
$mode = ((string) ($data['mode'] ?? 'local')) === 'remote' ? 'remote' : 'local';
$hostId = (int) ($data['host_id'] ?? 0);
$destinationMode = RestoreTargetPlanner::normalizeStrategy((string) ($data['destination_mode'] ?? RestoreTargetPlanner::STRATEGY_MANAGED));
$appendContextSubdir = array_key_exists('append_context_subdir', $data)
    ? !empty($data['append_context_subdir'])
    : AppConfig::restoreAppendContextSubdir();

if ($repoId <= 0 || $snapshot === '' || empty($files)) {
    jsonResponse(['error' => t('api.restore_partial.error.required_params')], 400);
}

$repo = RepoManager::getById($repoId);
if (!$repo) {
    jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);
}

Auth::requireRepoAccess($repoId);

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
        'sample_paths' => $files,
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

$restic = RepoManager::getRestic($repo);
$tmpDir = null;

$db = Database::getInstance();
$db->prepare("
    INSERT INTO restore_history
    (repo_id, repo_name, snapshot_id, mode, target, include_path, remote_host, remote_user, remote_path, status, output, started_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'running', '', ?)
")->execute([
    $repoId,
    $repo['name'],
    $snapshot,
    RestoreTargetPlanner::buildModeTag(true, $plan['mode'], $plan['strategy']),
    $plan['mode'] === 'local' ? $plan['effective_target'] : null,
    implode(', ', array_slice($files, 0, 3)) . (count($files) > 3 ? '...' : ''),
    $key['host'] ?? null,
    $key['user'] ?? null,
    $plan['mode'] === 'remote' ? $plan['effective_target'] : null,
    $_SESSION['username'] ?? 'admin',
]);
$histId = (int) $db->lastInsertId();

$log = [];
$log[] = '-> Restauration partielle - ' . count($files) . ' fichier(s)/dossier(s)';
$log[] = '-> Snapshot : ' . $snapshot;
$log[] = '-> Mode : ' . $plan['mode_label'] . ' / ' . $plan['strategy_label'];
$log[] = '-> Destination resolue : ' . $plan['effective_target'];

$success = true;
$extractedFiles = [];

try {
    if ($plan['mode'] === 'local') {
        if ($plan['strategy'] === RestoreTargetPlanner::STRATEGY_MANAGED) {
            RestoreTargetPlanner::ensureLocalManagedDirectory($plan['effective_target']);
        }

        $log[] = '-> Restauration directe vers ' . $plan['effective_target'];
        foreach ($files as $filePath) {
            $log[] = '-> Extraction : ' . $filePath;
        }

        $result = $restic->restoreIncludes($snapshot, $plan['effective_target'], $files);
        if (empty($result['success'])) {
            throw new RuntimeException(t('api.restore_partial.error.local_restore_failed') . "\n" . (string) ($result['output'] ?? t('api.common.error.unknown_error')));
        }

        $extractedFiles = $files;
        $log[] = '   OK';

        Auth::log('restore_partial_local_' . $plan['strategy'], "Restauration partielle $snapshot -> {$plan['effective_target']} sur {$repo['name']}");
    } else {
        $tmpDir = RestoreTargetPlanner::createLocalRestoreStagingDirectory('fulgurite_partial_');

        $log[] = '-> Staging local temporaire : ' . $tmpDir;
        $log[] = '-> Extraction temporaire en une seule passe';
        foreach ($files as $filePath) {
            $log[] = '-> Extraction : ' . $filePath;
        }
        $result = $restic->restoreIncludes($snapshot, $tmpDir, $files);
        if (!empty($result['success'])) {
            $extractedFiles = $files;
            $log[] = '   OK';
        } else {
            $log[] = '   ' . t('api.common.error_prefix') . (string) ($result['output'] ?? t('api.common.error.unknown_error'));
            $log[] = '-> Fallback : extraction isolee fichier par fichier';

            foreach ($files as $filePath) {
                try {
                    $singleTmp = RestoreTargetPlanner::createLocalRestoreStagingDirectory('fulgurite_partial_item_');
                } catch (Throwable $e) {
                    $success = false;
                    $log[] = '   ' . t('api.common.error_prefix') . t('api.restore_partial.error.cannot_create_temp_dir_for', ['path' => $filePath]) . ' : ' . $e->getMessage();
                    continue;
                }

                try {
                    $singleResult = $restic->restore($snapshot, $singleTmp, $filePath);
                    if (empty($singleResult['success'])) {
                        $success = false;
                        $log[] = '   ' . t('api.common.error_prefix') . $filePath . ' : ' . (string) ($singleResult['output'] ?? t('api.common.error.unknown_error'));
                        continue;
                    }

                    RestoreTargetPlanner::relaxExtractedTreePermissions($singleTmp);
                    $merge = RestoreTargetPlanner::copyExtractedTreeWithPhp($singleTmp, $tmpDir);
                    if (empty($merge['success'])) {
                        $success = false;
                        $log[] = '   ' . t('api.restore_partial.error.merge_prefix') . $filePath . ' : ' . (string) ($merge['output'] ?? t('api.common.error.unknown_error'));
                        continue;
                    }

                    RestoreTargetPlanner::relaxExtractedTreePermissions($tmpDir);
                    $extractedFiles[] = $filePath;
                    $log[] = '   OK fallback : ' . $filePath;
                } finally {
                    RestoreTargetPlanner::removeTree($singleTmp);
                }
            }
        }

        if (empty($extractedFiles)) {
            throw new RuntimeException(t('api.restore_partial.error.no_file_extracted'));
        }
        RestoreTargetPlanner::relaxExtractedTreePermissions($tmpDir);

        if ($plan['strategy'] === RestoreTargetPlanner::STRATEGY_MANAGED) {
            $prepare = RestoreTargetPlanner::prepareRemoteManagedDirectory($key, $plan['effective_target']);
            if (!$prepare['success']) {
                throw new RuntimeException(t('api.restore_partial.error.remote_prepare_failed') . "\n" . ($prepare['output'] ?? ''));
            }
        }

        $log[] = '-> Synchronisation distante vers ' . $key['user'] . '@' . $key['host'] . ':' . $plan['effective_target'];
        $sync = RestoreTargetPlanner::syncExtractedTreeToRemote($tmpDir, $key, $plan['effective_target']);
        if (!$sync['success']) {
            throw new RuntimeException(t('api.restore_partial.error.remote_transfer_failed') . "\n" . ($sync['output'] ?? ''));
        }

        $log[] = '   OK';
        Auth::log('restore_partial_remote_' . $plan['strategy'], "Restauration partielle $snapshot -> {$key['user']}@{$key['host']}:{$plan['effective_target']} sur {$repo['name']}");
    }
} catch (Throwable $e) {
    $success = false;
    $log[] = '-> Echec : ' . $e->getMessage();
} finally {
    if ($tmpDir !== null && is_dir($tmpDir)) {
        RestoreTargetPlanner::removeTree($tmpDir);
    }
}

$output = implode("\n", $log);

$db->prepare("
    UPDATE restore_history
    SET status = ?, output = ?, finished_at = datetime('now')
    WHERE id = ?
")->execute([
    $success ? 'success' : 'failed',
    $output,
    $histId,
]);

jsonResponse([
    'success' => $success,
    'output' => $output,
    'destination_mode' => $plan['strategy'],
    'resolved_target' => $plan['effective_target'],
    'preview_paths' => $plan['preview_paths'],
]);
