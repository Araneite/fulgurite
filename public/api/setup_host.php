<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('hosts.manage');
verifyCsrf();

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$hostId = (int) ($data['host_id'] ?? 0);

if (!$hostId) jsonResponse(['error' => t('api.common.error.host_id_required')], 400);

$host = HostManager::getById($hostId);
if (!$host) jsonResponse(['error' => t('api.common.error.host_not_found')], 404);

// Temporary sudo createdentials (if provided by UI for setup)
$tempSudoUser = trim($data['temp_sudo_user']     ?? '');
$tempSudoPass = trim($data['temp_sudo_password'] ?? '');

$run = RunLogManager::createRun('setup');
$runId   = $run['run_id'];
$logFile = $run['log_file'];
$pidFile = $run['pid_file'];

// Write temporary createdentials into a secure file
$credsFile = '';
if ($tempSudoPass) {
    $credsFile = tempnam('/tmp', 'rui_screds_');
    file_put_contents($credsFile, json_encode([
        'user' => $tempSudoUser,
        'pass' => $tempSudoPass,
    ]));
    chmod($credsFile, 0600);
}

$scriptPath = __DIR__ . '/setup_host_background.php';
if (!file_exists($scriptPath)) {
    jsonResponse(['error' => t('api.common.error.background_script_not_found')], 500);
}

$launch = ProcessRunner::startBackgroundPhp($scriptPath, [$hostId, $logFile, $credsFile], $logFile, $pidFile);
if (empty($launch['success'])) {
    jsonResponse(['error' => t('api.setup_host.error.background_start_failed')], 500);
}

$pid = isset($launch['pid']) ? (string) $launch['pid'] : null;
Auth::log('host_setup', "Setup hôte #{$hostId} ({$host['name']}) lancé (run: $runId)");

jsonResponse(['run_id' => $runId, 'pid' => $pid, 'started' => true]);
