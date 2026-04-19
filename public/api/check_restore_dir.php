<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();
rateLimitApi('check_restore_dir', 60, 60);

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$hostId = (int) ($data['host_id'] ?? 0);

if (!$hostId) jsonResponse(['error' => t('api.common.error.host_id_required')], 400);

$host = HostManager::getById($hostId);
if (!$host) jsonResponse(['error' => t('api.common.error.host_not_found')], 404);

if (empty($host['ssh_key_id'])) {
    jsonResponse(['success' => false, 'exists' => false, 'output' => t('api.common.error.ssh_key_not_associated')]);
}

$restoreRoot = trim((string) ($host['restore_managed_root'] ?? ''));
if ($restoreRoot === '') {
    $restoreRoot = AppConfig::restoreManagedRemoteRoot();
}

// Check folder existence and permissions (stat -c = GNU coreutils)
$checkCmd = 'if [ -d ' . escapeshellarg($restoreRoot) . ' ]; then'
    . ' stat -c "%a %U:%G" ' . escapeshellarg($restoreRoot) . ' && echo STATUS_EXISTS;'
    . ' else echo STATUS_MISSING; fi';
try {
    $result = HostManager::runRemoteCommand($host, $checkCmd);
    $diskProbe = HostManager::probeFilesystem($host, $restoreRoot);
} catch (RuntimeException $e) {
    jsonResponse([
        'success' => false,
        'exists' => false,
        'restore_root' => $restoreRoot,
        'ssh_user' => (string) ($host['user'] ?? 'root'),
        'output' => $e->getMessage(),
        'disk' => null,
    ]);
}

$exists = $result['success'] && str_contains($result['output'], 'STATUS_EXISTS');

Auth::log(
    'host_check_restore_dir',
    "Vérif. dossier restore #{$hostId} ({$host['name']}): " . ($exists ? 'EXISTS' : ($result['success'] ? 'MISSING' : 'SSH_ERROR'))
);

jsonResponse([
    'success' => $result['success'],
    'exists'  => $exists,
    'restore_root' => $restoreRoot,
    'ssh_user' => (string) ($host['user'] ?? 'root'),
    'output'  => $result['output'],
    'disk' => !empty($diskProbe['success']) ? [
        'probe_path' => (string) ($diskProbe['probe_path'] ?? $restoreRoot),
        'free_bytes' => (int) ($diskProbe['free_bytes'] ?? 0),
        'total_bytes' => (int) ($diskProbe['total_bytes'] ?? 0),
        'used_percent' => (float) ($diskProbe['used_percent'] ?? 0),
    ] : null,
]);
