<?php
// =============================================================================
// quick_backup_create.php — orchestrated backup creation via the quick flow
// =============================================================================
require_once __DIR__ . '/../../src/bootstrap.php';
RemoteBackupQuickFlow::requireManagePermissions();
verifyCsrf();
rateLimitApi('quick_backup_create', 5, 60);

$data = requestJsonBody();

try {
    $run = RunLogManager::createRun('quick_backup');
    $runId = (string) $run['run_id'];
    $logFile = (string) $run['log_file'];
    $pidFile = (string) $run['pid_file'];
    $resultFile = (string) ($run['result_file'] ?? ($logFile . '.result.json'));

    $payloadFile = tempnam(sys_get_temp_dir(), 'fulgurite_quick_backup_');
    if ($payloadFile === false) {
        throw new RuntimeException(t('api.quick_backup_create.error.prepare_request_failed'));
    }

    $currentUser = Auth::currentUser();
    $payload = $data;
    $payload['__run_user'] = [
        'id' => (int) ($currentUser['id'] ?? 0),
        'username' => (string) ($currentUser['username'] ?? ''),
        'role' => (string) ($currentUser['role'] ?? ''),
    ];
    if (@file_put_contents($payloadFile, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
        @unlink($payloadFile);
        throw new RuntimeException(t('api.quick_backup_create.error.write_request_failed'));
    }
    @chmod($payloadFile, 0600);

    $scriptPath = __DIR__ . '/quick_backup_create_background.php';
    if (!is_file($scriptPath)) {
        @unlink($payloadFile);
        throw new RuntimeException(t('api.common.error.background_script_not_found'));
    }

    $launch = ProcessRunner::startBackgroundPhp($scriptPath, [$payloadFile, $logFile, $resultFile], $logFile, $pidFile);
    if (empty($launch['success'])) {
        @unlink($payloadFile);
        @unlink($resultFile);
        @unlink($pidFile);
        @unlink($logFile);
        RunLogManager::deleteRunMetadata($runId);
        throw new RuntimeException(t('api.quick_backup_create.error.background_start_failed'));
    }

    Auth::log('quick_backup_create', "Creation rapide demarree en background (run: $runId)");
    jsonResponse([
        'success' => true,
        'started' => true,
        'run_id' => $runId,
        'pid' => isset($launch['pid']) ? (string) $launch['pid'] : null,
    ], 202);
} catch (Throwable $e) {
    Auth::log('quick_backup_create', "Exception: " . $e->getMessage());
    jsonResponse(['error' => t('api.quick_backup_create.error.unexpected_prefix') . $e->getMessage()], 500);
}
