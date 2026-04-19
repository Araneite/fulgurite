<?php
// =============================================================================
// run_cron_background.php - execute en background par manage_cron.php
// Arguments : $argv[1] = log_file, $argv[2] = mode
// =============================================================================

set_time_limit(0);
ini_set('max_execution_time', 0);
ignore_user_abort(true);

$_SESSION = [];
if (!defined('FULGURITE_CLI')) {
    define('FULGURITE_CLI', true);
}

require_once __DIR__ . '/../../src/bootstrap.php';
ProcessRunner::daemonizeFromEnvironment();

$logFile = $argv[1] ?? (rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'fulgurite_cron_bg.log');
$mode = (string) ($argv[2] ?? 'manual');
if (!in_array($mode, ['manual', 'diagnostic', 'quick'], true)) {
    $mode = 'manual';
}
$cronScript = realpath(__DIR__ . '/../cron.php');

function cronBgLog(string $message, string $logFile): void {
    $line = '[' . formatCurrentDisplayDate('H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}

function cronBgResolvePhpCliBinary(): string {
    $candidates = [];

    if (defined('PHP_CLI_BIN') && PHP_CLI_BIN !== '') {
        $candidates[] = PHP_CLI_BIN;
    }

    if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
        $candidates[] = rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php';
    }

    $candidates[] = '/usr/bin/php';
    $candidates[] = '/usr/local/bin/php';

    foreach (array_unique($candidates) as $candidate) {
        if ($candidate === '') {
            continue;
        }

        $basename = strtolower(basename($candidate));
        if (str_contains($basename, 'php-fpm')) {
            continue;
        }

        if (is_file($candidate) && is_executable($candidate)) {
            return $candidate;
        }
    }

    if (!str_contains(strtolower(basename(PHP_BINARY)), 'php-fpm')) {
        return PHP_BINARY;
    }

    return ProcessRunner::resolvePhpCliBinary();
}

cronBgLog('Runner initialise', $logFile);
cronBgLog('PID runner: ' . (getmypid() ?: 'n/a'), $logFile);
cronBgLog('Mode runner: ' . $mode, $logFile);
cronBgLog('PHP runner: ' . PHP_BINARY, $logFile);

if ($cronScript === false || !file_exists($cronScript)) {
    cronBgLog('ERREUR: cron.php introuvable', $logFile);
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}

cronBgLog('cron.php detecte: ' . $cronScript, $logFile);

$phpCliBinary = cronBgResolvePhpCliBinary();
cronBgLog('PHP CLI pour cron.php: ' . $phpCliBinary, $logFile);

$cmd = [$phpCliBinary, $cronScript];
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$env = null;
if ($mode !== 'manual') {
    $env = array_merge($_ENV, $_SERVER, [
        'FULGURITE_CRON_MODE' => $mode,
        'FULGURITE_CRON_DIAGNOSTIC' => $mode === 'diagnostic' ? '1' : '0',
    ]);
}

cronBgLog('Lancement du sous-processus cron...', $logFile);
$lastActivityAt = time();
$lastHeartbeatAt = 0;
$tick = static function () use (&$lastActivityAt, &$lastHeartbeatAt, $mode, $logFile): void {
    if ($mode === 'diagnostic' && (time() - $lastActivityAt) >= 2 && (time() - $lastHeartbeatAt) >= 2) {
        cronBgLog('Diagnostic: sous-processus vivant, attente de sortie...', $logFile);
        $lastHeartbeatAt = time();
    }
};
$result = ProcessRunner::run($cmd, [
    'cwd' => dirname($cronScript),
    'env' => $env ?? [],
    'stdout_callback' => static function (string $line) use ($logFile, &$lastActivityAt, $tick): void {
        $lastActivityAt = time();
        $line = trim($line);
        if ($line !== '') {
            cronBgLog($line, $logFile);
        }
        $tick();
    },
    'stderr_callback' => static function (string $line) use ($logFile, &$lastActivityAt, $tick): void {
        $lastActivityAt = time();
        $line = trim($line);
        if ($line !== '') {
            cronBgLog('STDERR: ' . $line, $logFile);
        }
        $tick();
    },
]);
$exitCode = (int) ($result['code'] ?? 1);
if ($exitCode === 0) {
    cronBgLog('Cycle cron termine', $logFile);
    file_put_contents($logFile . '.done', 'success');
    exit(0);
}

cronBgLog('ERREUR: cycle cron termine avec le code ' . $exitCode, $logFile);
file_put_contents($logFile . '.done', 'error');
exit(1);
