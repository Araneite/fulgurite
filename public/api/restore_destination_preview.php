<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/RestoreTargetPlanner.php';
Auth::requirePermission('restore.run');
verifyCsrf();
rateLimitApi('restore_destination_preview', 120, 60);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId = (int) ($data['repo_id'] ?? 0);
$snapshot = (string) ($data['snapshot'] ?? '');
$include = (string) ($data['include'] ?? '');
$files = array_values(array_filter(array_map('strval', (array) ($data['files'] ?? []))));
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
        'sample_paths' => $files,
        'can_restore_original' => Auth::isAdmin(),
    ]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 422);
}

$paths = array_values(array_unique(array_map('strval', $plan['preview_paths'] ?? [])));
$paths = array_slice($paths, 0, 100);

if (empty($paths)) {
    jsonResponse([
        'success' => true,
        'resolved_target' => $plan['effective_target'],
        'existing' => [],
        'checked_count' => 0,
        'truncated' => false,
    ]);
}

if ($mode === 'local') {
    $existing = [];
    foreach ($paths as $path) {
        if (file_exists($path) || is_link($path)) {
            $existing[] = [
                'path' => $path,
                'type' => is_dir($path) && !is_link($path) ? 'dir' : 'file',
            ];
        }
    }

    jsonResponse([
        'success' => true,
        'resolved_target' => $plan['effective_target'],
        'existing' => $existing,
        'checked_count' => count($paths),
        'truncated' => count($existing) > 50,
    ]);
}

$command = '';
foreach ($paths as $path) {
    $quoted = escapeshellarg($path);
    $command .= 'if [ -e ' . $quoted . ' ]; then '
        . 'if [ -d ' . $quoted . ' ]; then printf "dir\t%s\n" ' . $quoted . '; '
        . 'else printf "file\t%s\n" ' . $quoted . '; fi; fi; ';
}

$tmpKey = SshKeyManager::getTemporaryKeyFile((int) ($key['ssh_key_id'] ?? 0));
try {
    $check = Restic::runShell(array_merge([
        SSH_BIN,
        '-i', (string) $tmpKey,
        '-p', (string) ((int) ($key['port'] ?? 22)),
    ], SshKnownHosts::sshOptions((string) ($key['host'] ?? ''), (int) ($key['port'] ?? 22), 8), [
        (string) ($key['user'] ?? '') . '@' . (string) ($key['host'] ?? ''),
        $command,
    ]));
    $check = SshKnownHosts::finalizeSshResult($check, (string) ($key['host'] ?? ''), (int) ($key['port'] ?? 22), 'restore_destination_preview');
} finally {
    @unlink($tmpKey);
}

if (empty($check['success'])) {
    jsonResponse([
        'success' => false,
        'resolved_target' => $plan['effective_target'],
        'existing' => [],
        'checked_count' => count($paths),
        'error' => t('api.restore_destination_preview.error.remote_check_failed') . ': ' . (string) ($check['output'] ?? t('api.common.error.unknown_error')),
        'host_key' => $check['host_key'] ?? null,
    ], 200);
}

$existing = [];
foreach (preg_split('/\r?\n/', trim((string) ($check['output'] ?? ''))) as $line) {
    if ($line === '') {
        continue;
    }
    [$type, $path] = array_pad(explode("\t", $line, 2), 2, '');
    if ($path !== '') {
        $existing[] = [
            'path' => $path,
            'type' => $type === 'dir' ? 'dir' : 'file',
        ];
    }
}

jsonResponse([
    'success' => true,
    'resolved_target' => $plan['effective_target'],
    'existing' => $existing,
    'checked_count' => count($paths),
    'truncated' => count($existing) > 50,
]);
