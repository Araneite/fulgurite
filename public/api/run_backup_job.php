<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('backup_jobs.manage');
verifyCsrf();
rateLimitApi('run_backup', 10, 60);

$data  = json_decode(file_get_contents('php://input'), true) ?? [];
$jobId = (int) ($data['job_id'] ?? 0);

if (!$jobId) jsonResponse(['error' => t('api.common.error.job_id_required')], 400);
$job = BackupJobManager::getById($jobId);
if (!$job) jsonResponse(['error' => t('api.common.error.job_not_found')], 404);
Auth::requireRepoAccess((int) ($job['repo_id'] ?? 0));
$repo = RepoManager::getById((int) ($job['repo_id'] ?? 0));
if (!$repo) jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);

$preflight = DiskSpaceMonitor::preflightBackupJob($job, $repo);
if (empty($preflight['allowed'])) {
    jsonResponse(['error' => (string) ($preflight['message'] ?? t('api.common.error.insufficient_disk_space'))], 422);
}

$run = RunLogManager::createRun('backup');
$runId = $run['run_id'];
$logFile = $run['log_file'];
$pidFile = $run['pid_file'];

$scriptPath = __DIR__ . '/run_backup_background.php';
if (!file_exists($scriptPath)) {
    jsonResponse(['error' => t('api.common.error.background_script_not_found') . ': ' . $scriptPath], 500);
}

$launch = ProcessRunner::startBackgroundPhp($scriptPath, [$jobId, $logFile, $runId], $logFile, $pidFile);
if (empty($launch['success'])) {
    jsonResponse(['error' => t('api.run_backup_job.error.background_start_failed')], 500);
}

$pid = isset($launch['pid']) ? (string) $launch['pid'] : null;

Auth::log('backup_job_run', "Job backup #$jobId démarré en background (run: $runId)");

jsonResponse([
    'run_id'   => $runId,
    'log_file' => $logFile,
    'pid'      => $pid,
    'started'  => true,
]);
