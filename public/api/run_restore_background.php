<?php
// =============================================================================
// run_restore_background.php — executed in background by restore.php
// Arguments : $argv[1] = histId
// =============================================================================

set_time_limit(0);
ini_set('max_execution_time', 0);

$histId = (int) ($argv[1] ?? 0);

if (!$histId) {
    exit(1);
}

$_SESSION = [];
define('FULGURITE_CLI', true);

require_once __DIR__ . '/../../src/bootstrap.php';
ProcessRunner::daemonizeFromEnvironment();

$db = Database::getInstance();
$histStmt = $db->prepare("SELECT * FROM restore_history WHERE id = ?");
$histStmt->execute([$histId]);
$hist = $histStmt->fetch();

if (!$hist) {
    exit(1);
}

// Retrieve the repository
$repo = RepoManager::getById((int)$hist['repo_id']);
if (!$repo) {
    $db->prepare("UPDATE restore_history SET status = 'failed', output = 'Dépôt introuvable', finished_at = datetime('now') WHERE id = ?")
       ->execute([$histId]);
    exit(1);
}

$restic = RepoManager::getRestic($repo);
$result = ['success' => false, 'output' => ''];

try {
    // Mode is stored as a tag (ex: "local:managed", "remote:managed")
    // It must be parsed to retrieve mode and strategy
    $modeParts = explode(':', $hist['mode']);
    $mode = $modeParts[0] ?? 'local';
    $strategy = $modeParts[1] ?? 'managed';

    if ($mode === 'local') {
        $preflight = DiskSpaceMonitor::preflightRestore($repo, (string) $hist['snapshot_id'], 'local', (string) $hist['target']);
        if (empty($preflight['allowed'])) {
            throw new RuntimeException((string) ($preflight['message'] ?? 'Espace disque insuffisant'));
        }
        if ($strategy === 'managed') {
            RestoreTargetPlanner::ensureLocalManagedDirectory($hist['target']);
        }
        $result = $restic->restore($hist['snapshot_id'], $hist['target'], $hist['include_path']);
        Auth::log('restore_local_' . $strategy, "Snapshot {$hist['snapshot_id']} -> {$hist['target']} sur {$repo['name']}");
    } else {
        $host = HostManager::getById((int)$hist['remote_host_id']);
        if (!$host) {
            throw new RuntimeException("Hôte distant introuvable");
        }

        $preflight = DiskSpaceMonitor::preflightRestore($repo, (string) $hist['snapshot_id'], 'remote', (string) $hist['remote_path'], $host);
        if (empty($preflight['allowed'])) {
            throw new RuntimeException((string) ($preflight['message'] ?? 'Espace disque insuffisant'));
        }

        if ($strategy === 'managed') {
            $prepare = RestoreTargetPlanner::prepareRemoteManagedDirectory($host, $hist['remote_path']);
            if (!$prepare['success']) {
                throw new RuntimeException("Préparation du dossier distant échouée:\n" . ($prepare['output'] ?? ''));
            }
        }

        $result = $restic->restoreRemote(
            $hist['snapshot_id'],
            (string) $host['user'],
            (string) $host['hostname'],
            (int) ($host['port'] ?? 22),
            $hist['remote_path'],
            (string) $host['private_key_file'],
            $hist['include_path']
        );
        Auth::log('restore_remote_' . $strategy, "Snapshot {$hist['snapshot_id']} -> {$host['user']}@{$host['hostname']}:{$hist['remote_path']} sur {$repo['name']}");
    }
} catch (Throwable $e) {
    $result = [
        'success' => false,
        'output' => $e->getMessage(),
    ];
}

$db->prepare("
    UPDATE restore_history
    SET status = ?, output = ?, finished_at = datetime('now')
    WHERE id = ?
")->execute([
    $result['success'] ? 'success' : 'failed',
    (string) ($result['output'] ?? ''),
    $histId,
]);
