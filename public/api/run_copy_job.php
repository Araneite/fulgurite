<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('copy_jobs.manage');
verifyCsrf();
rateLimitApi('run_copy', 10, 60);

$data       = json_decode(file_get_contents('php://input'), true) ?? [];
$jobId      = (int) ($data['job_id']     ?? 0);
$snapshotId = $data['snapshot_id'] ?? null;

if (!$jobId) jsonResponse(['error' => t('api.common.error.job_id_required')], 400);

// createate a unique run ID and temporary log file
$job = CopyJobManager::getById($jobId);
if (!$job) jsonResponse(['error' => t('api.common.error.job_not_found')], 404);
Auth::requireRepoAccess((int) ($job['source_repo_id'] ?? 0));
$sourceRepo = RepoManager::getById((int) ($job['source_repo_id'] ?? 0));
if (!$sourceRepo) jsonResponse(['error' => t('api.run_copy_job.error.source_repo_not_found')], 404);

$preflight = DiskSpaceMonitor::preflightCopyJob($job, $sourceRepo, is_string($snapshotId) && $snapshotId !== '' ? $snapshotId : null);
if (empty($preflight['allowed'])) {
    jsonResponse(['error' => (string) ($preflight['message'] ?? t('api.common.error.insufficient_disk_space'))], 422);
}

$run = RunLogManager::createRun('copy');
$runId = $run['run_id'];
$logFile = $run['log_file'];
$pidFile = $run['pid_file'];

// Launch the job in background via a separate PHP script
$scriptPath = __DIR__ . '/run_copy_background.php';
if (!file_exists($scriptPath)) {
    jsonResponse(['error' => t('api.common.error.background_script_not_found') . ': ' . $scriptPath], 500);
}
$launch = ProcessRunner::startBackgroundPhp($scriptPath, [$jobId, $snapshotId ?? '', $logFile], $logFile, $pidFile);
if (empty($launch['success'])) {
    jsonResponse(['error' => t('api.run_copy_job.error.background_start_failed')], 500);
}

$pid = isset($launch['pid']) ? (string) $launch['pid'] : null;

Auth::log('copy_job_run', "Job #$jobId démarré en background (run: $runId)");

jsonResponse([
    'run_id'   => $runId,
    'log_file' => $logFile,
    'pid'      => $pid,
    'started'  => true,
]);
