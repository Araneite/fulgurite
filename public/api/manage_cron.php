<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('scheduler.manage');
verifyCsrf();
rateLimitApi('manage_cron', 20, 60);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = (string) ($data['action'] ?? '');
$cronLine = SchedulerManager::getCronLine();
$marker = '# Fulgurite cron';

function readCrontab(): string {
    if (DIRECTORY_SEPARATOR === '\\') {
        return '';
    }

    $result = ProcessRunner::run(['crontab', '-l'], ['validate_binary' => false]);
    return $result['code'] === 0 ? trim((string) ($result['stdout'] ?? '')) : '';
}

function writeCrontab(string $content): bool {
    if (DIRECTORY_SEPARATOR === '\\') {
        return false;
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'fulgurite_cron_');
    if ($tmpFile === false) {
        return false;
    }

    file_put_contents($tmpFile, $content);
    $result = ProcessRunner::run(['crontab', $tmpFile], ['validate_binary' => false]);
    @unlink($tmpFile);
    return ($result['code'] ?? 1) === 0;
}

function launchBackgroundPhp(string $phpBinary, string $scriptPath, array $args = []): bool {
    $launch = ProcessRunner::startBackgroundPhp($scriptPath, $args, null, null, ['PHP_BINARY' => $phpBinary]);
    return (bool) ($launch['success'] ?? false);
}

if ($action === 'status') {
    $status = SchedulerManager::getCronEngineStatus();
    jsonResponse($status);
}

if ($action === 'enable') {
    if (DIRECTORY_SEPARATOR === '\\') {
        jsonResponse(['success' => false, 'output' => t('api.manage_cron.output.enable_unsupported_windows')], 400);
    }

    $current = readCrontab();
    $lines = explode("\n", $current);
    $cleaned = array_filter($lines, fn($line) => !str_contains($line, SchedulerManager::getCronScriptPath()) && !str_contains($line, '/cron.php?token=') && !str_contains($line, '/cron?token=') && !str_contains($line, $marker));
    $cleaned[] = $marker;
    $cleaned[] = $cronLine;
    $newContent = implode("\n", $cleaned) . "\n";

    $ok = writeCrontab($newContent);
    Auth::log('cron_enable', 'Cron Fulgurite active');
    jsonResponse(['success' => $ok, 'output' => $ok ? t('api.manage_cron.output.enabled') : t('api.manage_cron.output.crontab_write_error')]);
}

if ($action === 'disable') {
    if (DIRECTORY_SEPARATOR === '\\') {
        jsonResponse(['success' => false, 'output' => t('api.manage_cron.output.disable_unsupported_windows')], 400);
    }

    $current = readCrontab();
    $lines = explode("\n", $current);
    $cleaned = array_filter($lines, fn($line) => !str_contains($line, SchedulerManager::getCronScriptPath()) && !str_contains($line, '/cron.php?token=') && !str_contains($line, '/cron?token=') && !str_contains($line, $marker));
    $newContent = implode("\n", $cleaned) . "\n";

    $ok = writeCrontab($newContent);
    Auth::log('cron_disable', 'Cron Fulgurite desactive');
    jsonResponse(['success' => $ok, 'output' => $ok ? t('api.manage_cron.output.disabled') : t('api.manage_cron.output.crontab_write_error')]);
}

if ($action === 'run_now') {
    $run = RunLogManager::createRun('cron');
    $runId = $run['run_id'];
    $logFile = $run['log_file'];
    $scriptPath = __DIR__ . '/run_cron_background.php';
    $mode = (string) ($data['mode'] ?? 'manual');
    $allowedModes = ['manual', 'diagnostic', 'quick'];
    $mode = in_array($mode, $allowedModes, true) ? $mode : 'manual';
    $phpCliBinary = WorkerManager::getPhpCliBinary();

    if (!file_exists($scriptPath)) {
        jsonResponse(['error' => t('api.common.error.background_script_not_found')], 500);
    }

    $bootstrapLines = [
        '[' . formatCurrentDisplayDate('H:i:s') . '] Requete recue pour lancer un cycle cron',
        '[' . formatCurrentDisplayDate('H:i:s') . '] Mode: ' . $mode,
        '[' . formatCurrentDisplayDate('H:i:s') . '] PHP_BINARY: ' . PHP_BINARY,
        '[' . formatCurrentDisplayDate('H:i:s') . '] PHP CLI resolu: ' . $phpCliBinary,
        '[' . formatCurrentDisplayDate('H:i:s') . '] Script background: ' . $scriptPath,
        '[' . formatCurrentDisplayDate('H:i:s') . '] Fichier log: ' . $logFile,
        '[' . formatCurrentDisplayDate('H:i:s') . '] Demarrage du process en arriere-plan...',
    ];
    file_put_contents($logFile, implode("\n", $bootstrapLines) . "\n");

    if (!launchBackgroundPhp($phpCliBinary, $scriptPath, [$logFile, $mode])) {
        file_put_contents($logFile, '[' . formatCurrentDisplayDate('H:i:s') . "] ERREUR: impossible de demarrer le process de fond\n", FILE_APPEND);
        jsonResponse(['error' => t('api.manage_cron.error.background_start_failed')], 500);
    }

    Auth::log('cron_run_manual', 'Execution manuelle du cron en arriere-plan (' . $mode . ')');
    jsonResponse([
        'success' => true,
        'run_id' => $runId,
        'log_file' => $logFile,
        'started' => true,
        'mode' => $mode,
    ]);
}

jsonResponse(['error' => t('api.common.error.invalid_action')], 400);
