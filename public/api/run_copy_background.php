<?php
// =============================================================================
// run_copy_background.php - execute en background par run_copy_job.php
// Arguments : $argv[1] = job_id, $argv[2] = snapshot_id (optionnel), $argv[3] = log_file
// =============================================================================

set_time_limit(0);
ini_set('max_execution_time', 0);

$jobId = (int) ($argv[1] ?? 0);
$snapshotId = !empty($argv[2]) ? $argv[2] : null;
$logFile = $argv[3] ?? (rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'fulgurite_copy_bg.log');

if (!$jobId) {
    exit(1);
}

$_SESSION = [];
define('FULGURITE_CLI', true);

require_once __DIR__ . '/../../src/bootstrap.php';
ProcessRunner::daemonizeFromEnvironment();

function bgLog(string $msg, string $logFile): void {
    $line = '[' . formatCurrentDisplayDate('H:i:s') . '] ' . $msg . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}

function bgRunDetailed(array $cmd, array $env, string $logFile): array {
    $outputLines = [];
    $result = ProcessRunner::run($cmd, [
        'env' => $env,
        'stdout_callback' => static function (string $line) use ($logFile, &$outputLines): void {
            $line = trim($line);
            if ($line === '') {
                return;
            }
            $outputLines[] = $line;
            bgLog($line, $logFile);
        },
        'stderr_callback' => static function (string $line) use ($logFile, &$outputLines): void {
            $line = trim($line);
            if ($line === '') {
                return;
            }
            $outputLines[] = $line;
            bgLog($line, $logFile);
        },
    ]);

    return [
        'code' => (int) ($result['code'] ?? 1),
        'output' => implode("\n", $outputLines),
    ];
}

file_put_contents($logFile, '');
bgLog("Demarrage du job #$jobId", $logFile);

$job = CopyJobManager::getById($jobId);
if (!$job) {
    bgLog("ERREUR: Job #$jobId introuvable", $logFile);
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}

$sourceRepo = RepoManager::getById((int) $job['source_repo_id']);
if (!$sourceRepo) {
    bgLog('ERREUR: Depot source introuvable', $logFile);
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}

$preflight = DiskSpaceMonitor::preflightCopyJob($job, $sourceRepo, $snapshotId);
if (empty($preflight['allowed'])) {
    bgLog('[PRECHECK] ' . (string) ($preflight['message'] ?? 'Espace disque insuffisant'), $logFile);
    $output = file_get_contents($logFile);
    Database::getInstance()->prepare("
        UPDATE copy_jobs
        SET last_run = datetime('now'), last_status = ?, last_output = ?
        WHERE id = ?
    ")->execute(['failed', $output, $jobId]);
    Database::getInstance()->prepare("
        INSERT INTO cron_log (job_type, job_id, status, output)
        VALUES ('copy', ?, ?, ?)
    ")->execute([$jobId, 'failed', $output]);
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}
if (!empty($preflight['supported'])) {
    bgLog('[PRECHECK] ' . (string) ($preflight['message'] ?? 'Verification disque OK'), $logFile);
}

bgLog("Job : {$job['name']}", $logFile);
bgLog("Source : {$job['source_name']} ({$job['source_path']})", $logFile);
bgLog("Destination : {$job['dest_path']}", $logFile);
bgLog($snapshotId ? "Snapshot : {$snapshotId}" : 'Snapshots : tous', $logFile);
bgLog('---', $logFile);
bgLog('Connexion au repo source...', $logFile);

$runtimeCacheDir = Restic::getRuntimeCacheRootForCurrentProcess();
$destPassword = CopyJobManager::getDestPassword($job);
$sourcePassword = RepoManager::getPassword($sourceRepo);
$resolvedRetryPolicy = JobRetryPolicy::resolvePolicy(JobRetryPolicy::getEntityPolicy($job));

$passFile = Restic::writeTempSecretFile($destPassword, 'rui_pass_');
$fromPassFile = Restic::writeTempSecretFile($sourcePassword, 'rui_fpass_');

$cmd = [
    RESTIC_BIN,
    '-r', $job['dest_path'],
    '--password-file', $passFile,
    '--from-repo', $sourceRepo['path'],
    '--from-password-file', $fromPassFile,
    'copy',
];
if ($snapshotId) {
    $cmd[] = $snapshotId;
}

$env = [
    'RESTIC_CACHE_DIR' => $runtimeCacheDir,
    'HOME' => '/var/www',
    'RCLONE_CONFIG' => '/var/www/.config/rclone/rclone.conf',
    'XDG_CACHE_HOME' => '/tmp',
    'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
];

$retryCount = 0;
$attempt = 1;
$returnCode = 1;

while (true) {
    bgLog("Tentative #{$attempt}", $logFile);
    $attemptResult = bgRunDetailed($cmd, $env, $logFile);
    $returnCode = (int) ($attemptResult['code'] ?? 1);

    if ($returnCode === 0) {
        break;
    }

    $decision = JobRetryPolicy::shouldRetry(
        $resolvedRetryPolicy,
        (string) ($attemptResult['output'] ?? ''),
        $returnCode,
        $retryCount
    );
    $classification = $decision['classification'] ?? ['label' => 'Erreur non classee'];
    bgLog('Classification: ' . ($classification['label'] ?? 'Erreur non classee'), $logFile);

    if (empty($decision['retry'])) {
        bgLog('Pas de retry: ' . ($decision['reason'] ?? 'politique non applicable'), $logFile);
        break;
    }

    $retryCount++;
    $attempt++;
    $delaySeconds = max(1, (int) ($decision['delay_seconds'] ?? 1));
    bgLog("Retry #{$retryCount} dans {$delaySeconds}s", $logFile);
    sleep($delaySeconds);
}

Restic::deleteTempSecretFile($passFile);
Restic::deleteTempSecretFile($fromPassFile);

$success = $returnCode === 0;
bgLog('---', $logFile);
bgLog($success ? 'Copie terminee avec succes' : "Erreur (code: {$returnCode})", $logFile);

$output = file_get_contents($logFile);
$db = Database::getInstance();
$db->prepare("
    UPDATE copy_jobs
    SET last_run = datetime('now'), last_status = ?, last_output = ?
    WHERE id = ?
")->execute([$success ? 'success' : 'failed', $output, $jobId]);

$db->prepare("
    INSERT INTO cron_log (job_type, job_id, status, output)
    VALUES ('copy', ?, ?, ?)
")->execute([$jobId, $success ? 'success' : 'failed', $output]);

CopyJobManager::notifyResult($job, $success, $output);
file_put_contents($logFile . '.done', $success ? 'success' : 'error');
