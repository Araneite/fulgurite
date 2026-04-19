<?php

define('FULGURITE_CLI', true);
require_once __DIR__ . '/../src/bootstrap.php';
ProcessRunner::daemonizeFromEnvironment();

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI uniquement\n";
    exit(1);
}

$argv = $_SERVER['argv'] ?? [];
$options = [
    'once' => in_array('--once', $argv, true),
    'sleep' => 5,
    'limit' => 3,
    'stale_minutes' => 30,
    'name' => 'default',
    'pid_file' => '',
];

foreach ($argv as $arg) {
    if (preg_match('/^--sleep=(\d+)$/', $arg, $m)) {
        $options['sleep'] = max(1, (int) $m[1]);
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $options['limit'] = max(1, (int) $m[1]);
    } elseif (preg_match('/^--stale-minutes=(\d+)$/', $arg, $m)) {
        $options['stale_minutes'] = max(1, (int) $m[1]);
    } elseif (preg_match('/^--name=(.+)$/', $arg, $m)) {
        $options['name'] = trim($m[1]) !== '' ? trim($m[1]) : 'default';
    } elseif (preg_match('/^--pid-file=(.+)$/', $arg, $m)) {
        $options['pid_file'] = trim($m[1]);
    }
}

set_time_limit(0);
ini_set('max_execution_time', '0');

$logPath = dirname(DB_PATH) . '/logs/job-worker.log';
if (!is_dir(dirname($logPath))) {
    mkdir(dirname($logPath), 0700, true);
}

$log = static function(string $message) use ($logPath): void {
    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    echo $line;
};

$pidFile = $options['pid_file'] !== ''
    ? $options['pid_file']
    : WorkerManager::getPidFilePath($options['name']);
$lockFile = WorkerManager::getLockFilePath($options['name']);

if (!is_dir(dirname($pidFile))) {
    mkdir(dirname($pidFile), 0755, true);
}

$lockDir = dirname($lockFile);
if (!is_dir($lockDir)) {
    mkdir($lockDir, 0755, true);
}

$lockHandle = fopen($lockFile, 'c+');
if (!is_resource($lockHandle)) {
    $log(sprintf('Impossible d\'ouvrir le verrou du worker %s (%s)', $options['name'], $lockFile));
    exit(1);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    rewind($lockHandle);
    $blockingPid = trim((string) stream_get_contents($lockHandle));
    fclose($lockHandle);

    $log(sprintf(
        'Worker %s ignore : une execution est deja en cours%s',
        $options['name'],
        $blockingPid !== '' ? ' (PID ' . $blockingPid . ')' : ''
    ));
    exit(0);
}

$currentPid = (string) (getmypid() ?: '');
ftruncate($lockHandle, 0);
if ($currentPid !== '') {
    fwrite($lockHandle, $currentPid);
    fflush($lockHandle);
}

file_put_contents($pidFile, $currentPid, LOCK_EX);
register_shutdown_function(static function() use ($pidFile, $lockHandle): void {
    $currentPid = getmypid();
    $recordedPid = @file_get_contents($pidFile);
    if ($recordedPid !== false && trim((string) $recordedPid) === (string) $currentPid) {
        @unlink($pidFile);
    }

    if (is_resource($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
});

$running = true;
if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static function() use (&$running): void { $running = false; });
    pcntl_signal(SIGINT, static function() use (&$running): void { $running = false; });
}

$log(sprintf(
    'Worker %s demarre (once=%s, sleep=%ds, limit=%d)',
    $options['name'],
    $options['once'] ? 'yes' : 'no',
    $options['sleep'],
    $options['limit']
));

$lastHeartbeatMeta = null;
$lastHeartbeatAt = 0;
$heartbeatMinInterval = AppConfig::workerHeartbeatWriteMinIntervalSeconds();
$idleMaxSleepSeconds = max($options['sleep'], min(300, $options['sleep'] * 12));
$staleRecoverIntervalSeconds = max(10, min(120, $options['sleep'] * 6));
$nextStaleRecoverAt = 0;

do {
    $nowTs = time();
    $recovered = 0;
    if ($nowTs >= $nextStaleRecoverAt) {
        $recovered = JobQueue::recoverStaleRunningJobs($options['stale_minutes']);
        $nextStaleRecoverAt = $nowTs + $staleRecoverIntervalSeconds;
    }

    if ($recovered > 0) {
        $log("Jobs stale requeues: {$recovered}");
    }

    // Queue periodic refreshes configured per repository
    // A repo with snapshot_refresh_interval_minutes set receives a refresh
    // as soon as the interval has elapsed since last_snapshot_refreshed_at.
    foreach (RepoManager::getDueSnapshotRefreshRepos($options['limit']) as $repo) {
        $jobId = JobQueue::enqueueRepoSnapshotRefresh((int) $repo['id'], 'interval', 150);
        $intervalMinutes = (int) ($repo['snapshot_refresh_interval_minutes'] ?? 0);
        $log(sprintf('Refresh periodique enfile pour %s (intervalle %dmin, job #%d)', $repo['name'], $intervalMinutes, $jobId));
    }

    $results = JobQueue::processDueJobs($options['limit']);
    if (!empty($results)) {
        foreach ($results as $result) {
            $log(sprintf(
                'Job #%d %s => %s (%s)',
                (int) $result['id'],
                $result['type'],
                $result['status'],
                $result['message']
            ));
        }
    } elseif ($options['once']) {
        $log('Aucun job a traiter');
    }

    $shouldRefreshSummary = $lastHeartbeatMeta === null
        || !empty($results)
        || $recovered > 0
        || (($nowTs - $lastHeartbeatAt) >= $heartbeatMinInterval);
    if ($shouldRefreshSummary) {
        $summary = JobQueue::getSummary();
        $heartbeatMeta = [
            'queue' => $summary['counts'],
            'recovered_jobs' => $recovered,
        ];
        $shouldWriteHeartbeat = $lastHeartbeatMeta === null
            || $heartbeatMeta !== $lastHeartbeatMeta
            || (($nowTs - $lastHeartbeatAt) >= $heartbeatMinInterval);
        if ($shouldWriteHeartbeat) {
            JobQueue::markWorkerHeartbeat($options['name'], $heartbeatMeta);
            $lastHeartbeatMeta = $heartbeatMeta;
            $lastHeartbeatAt = $nowTs;
        }
    }

    if ($options['once']) {
        break;
    }

    $sleepSeconds = $idleMaxSleepSeconds;
    $nextDueJobIn = JobQueue::secondsUntilNextDueJob();
    $nextRefreshIn = RepoManager::secondsUntilNextScheduledSnapshotRefresh();
    $waitCandidates = array_filter([$nextDueJobIn, $nextRefreshIn], static fn($seconds): bool => $seconds !== null);
    if (!empty($waitCandidates)) {
        $sleepSeconds = min($sleepSeconds, max(1, (int) min($waitCandidates)));
    }
    if (!empty($results)) {
        $sleepSeconds = 1;
    }

    sleep(max(1, (int) $sleepSeconds));
} while ($running);

$log(sprintf('Worker %s arrete', $options['name']));
