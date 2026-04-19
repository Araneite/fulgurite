<?php
// =============================================================================
// run_scheduler_task_background.php - execute une tache globale en background
// Arguments : $argv[1] = task, $argv[2] = log_file
// =============================================================================

set_time_limit(0);
ini_set('max_execution_time', 0);
ignore_user_abort(true);

$task = (string) ($argv[1] ?? '');
$logFile = $argv[2] ?? (rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'fulgurite_scheduler_task.log');

$_SESSION = [];
if (!defined('FULGURITE_CLI')) {
    define('FULGURITE_CLI', true);
}

require_once __DIR__ . '/../../src/bootstrap.php';
ProcessRunner::daemonizeFromEnvironment();

function schedulerTaskLog(string $message, string $logFile): void {
    $line = '[' . formatCurrentDisplayDate('H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}

file_put_contents($logFile, '');
schedulerTaskLog('Demarrage de la tache ' . $task, $logFile);

$logger = function (string $line) use ($logFile): void {
    schedulerTaskLog($line, $logFile);
};

try {
    $result = match ($task) {
        'weekly_report' => SchedulerManager::runWeeklyReportTask(true, null, $logger),
        'integrity_check' => SchedulerManager::runIntegrityCheckTask(true, null, $logger),
        'db_vacuum' => SchedulerManager::runDbVacuumTask(true, null, $logger),
        default => null,
    };

    if ($result === null) {
        schedulerTaskLog('ERREUR: tache inconnue', $logFile);
        file_put_contents($logFile . '.done', 'error');
        exit(1);
    }

    $output = trim((string) ($result['output'] ?? ''));
    if ($output !== '') {
        schedulerTaskLog($output, $logFile);
    }

    schedulerTaskLog(($result['success'] ?? false) ? 'Tache terminee avec succes' : 'Tache terminee avec erreur', $logFile);
    file_put_contents($logFile . '.done', ($result['success'] ?? false) ? 'success' : 'error');
    exit(($result['success'] ?? false) ? 0 : 1);
} catch (Throwable $e) {
    schedulerTaskLog('ERREUR: ' . (trim($e->getMessage()) ?: 'Erreur inconnue'), $logFile);
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}
