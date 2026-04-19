<?php
// =============================================================================
// quick_backup_createate_background.php — createation quick orchestree en background
// Arguments : $argv[1] = payload_file, $argv[2] = log_file, $argv[3] = result_file
// =============================================================================

set_time_limit(0);
ini_set('max_execution_time', '0');

$payloadFile = $argv[1] ?? '';
$logFile = $argv[2] ?? (rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'fulgurite_quick_backup.log');
$resultFile = $argv[3] ?? ($logFile . '.result.json');
$doneFile = $logFile . '.done';

if (!is_string($payloadFile) || trim($payloadFile) === '') {
    exit(1);
}

$_SESSION = [];
define('FULGURITE_CLI', true);

require_once __DIR__ . '/../../src/bootstrap.php';
ProcessRunner::daemonizeFromEnvironment();
$_SERVER['REQUEST_URI'] = '/api/quick_backup_create_background.php';
$_SERVER['SCRIPT_NAME'] = '/api/quick_backup_create_background.php';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

function quickBackupBgLog(string $message, string $logFile): void {
    $line = '[' . formatCurrentDisplayDate('H:i:s') . '] ' . trim($message) . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}

function quickBackupBgFinalize(string $logFile, string $resultFile, string $doneFile, array $result, string $status): void {
    @file_put_contents($resultFile, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    @chmod($resultFile, 0600);
    @file_put_contents($doneFile, $status, LOCK_EX);
}

@file_put_contents($logFile, '');

$rawPayload = @file_get_contents($payloadFile);
@unlink($payloadFile);
if ($rawPayload === false || trim($rawPayload) === '') {
    quickBackupBgLog('ERREUR: Demande de creation rapide introuvable.', $logFile);
    $result = ['success' => false, 'error' => 'Demande de creation rapide introuvable.'];
    RemoteBackupQuickFlow::persistCreationHistory(null, false, trim((string) @file_get_contents($logFile)));
    quickBackupBgFinalize($logFile, $resultFile, $doneFile, $result, 'error');
    exit(1);
}

$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    quickBackupBgLog('ERREUR: Le payload de creation rapide est invalide.', $logFile);
    $result = ['success' => false, 'error' => 'Payload de creation rapide invalide.'];
    RemoteBackupQuickFlow::persistCreationHistory(null, false, trim((string) @file_get_contents($logFile)));
    quickBackupBgFinalize($logFile, $resultFile, $doneFile, $result, 'error');
    exit(1);
}

$runUser = is_array($payload['__run_user'] ?? null) ? $payload['__run_user'] : [];
unset($payload['__run_user']);

if ((int) ($runUser['id'] ?? 0) > 0) {
    $_SESSION['user_id'] = (int) $runUser['id'];
    $_SESSION['username'] = (string) ($runUser['username'] ?? 'background');
    $_SESSION['role'] = (string) ($runUser['role'] ?? ROLE_VIEWER);
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

quickBackupBgLog('Demarrage du flux rapide en arriere-plan.', $logFile);

try {
    $result = RemoteBackupQuickFlow::create($payload, static function (string $message) use ($logFile): void {
        quickBackupBgLog($message, $logFile);
    });

    $status = !empty($result['success']) ? 'success' : 'error';
    if (!empty($result['success'])) {
        quickBackupBgLog('Creation rapide terminee.', $logFile);
        $created = is_array($result['created'] ?? null) ? $result['created'] : [];
        quickBackupBgLog(
            'Objets crees : cle #' . (string) ($created['ssh_key_id'] ?? '—')
            . ', hote #' . (string) ($created['host_id'] ?? '—')
            . ', depot #' . (string) ($created['repo_id'] ?? '—')
            . ', job #' . (string) ($created['job_id'] ?? '—'),
            $logFile
        );
        Auth::log('quick_backup_create', 'Creation rapide terminee avec succes');
    } else {
        quickBackupBgLog('Creation rapide interrompue : ' . (string) ($result['error'] ?? 'Erreur inconnue'), $logFile);
        Auth::log('quick_backup_create', 'Creation rapide terminee en erreur');
    }

    $logOutput = trim((string) @file_get_contents($logFile));
    $jobId = (int) (($result['created']['job_id'] ?? 0));
    RemoteBackupQuickFlow::persistCreationHistory($jobId > 0 ? $jobId : null, !empty($result['success']), $logOutput);
    quickBackupBgFinalize($logFile, $resultFile, $doneFile, $result, $status);
    exit(!empty($result['success']) ? 0 : 1);
} catch (Throwable $e) {
    quickBackupBgLog('ERREUR INATTENDUE: ' . $e->getMessage(), $logFile);
    $result = ['success' => false, 'error' => 'Erreur inattendue : ' . $e->getMessage()];
    $logOutput = trim((string) @file_get_contents($logFile));
    RemoteBackupQuickFlow::persistCreationHistory(null, false, $logOutput);
    Auth::log('quick_backup_create', 'Exception background: ' . $e->getMessage(), 'warning');
    quickBackupBgFinalize($logFile, $resultFile, $doneFile, $result, 'error');
    exit(1);
}
