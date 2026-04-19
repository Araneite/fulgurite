<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
verifyCsrf();

$data    = json_decode(file_get_contents('php://input'), true) ?? [];
$runId   = RunLogManager::sanitizeRunId((string) ($data['run_id'] ?? ''));

if (empty($runId)) jsonResponse(['error' => 'run_id requis'], 400);

RunLogManager::requireAccessibleRun('copy', $runId);
$runFiles = RunLogManager::getRunFiles('copy', $runId);
$logFile  = $runFiles['log_file'];
$doneFile = $runFiles['done_file'];
$pidFile = $runFiles['pid_file'];

if (!file_exists($logFile)) {
    jsonResponse([
        'lines' => [],
        'offset' => 0,
        'next_offset_bytes' => 0,
        'offset_bytes' => 0,
        'max_bytes' => 262144,
        'max_lines' => 600,
        'has_more' => false,
        'eof' => true,
        'offset_reset' => false,
        'protocol' => 'bytes',
        'done' => false,
        'status' => 'waiting',
    ]);
}
$chunk = RunLogManager::readIncrementalLog($logFile, $data);

$done   = file_exists($doneFile);
$status = $done ? trim(file_get_contents($doneFile)) : 'running';

// Clean temporary files if finished for more than 5 minutes
if ($done) {
    $doneTime = filemtime($doneFile);
    if (time() - $doneTime > 300) {
        @unlink($logFile);
        @unlink($doneFile);
        @unlink($pidFile);
        RunLogManager::deleteRunMetadata($runId);
    }
}

jsonResponse([
    'lines'  => $chunk['lines'],
    'offset' => $chunk['offset'],
    'next_offset_bytes' => $chunk['next_offset_bytes'],
    'offset_bytes' => $chunk['offset_bytes'],
    'max_bytes' => $chunk['max_bytes'],
    'max_lines' => $chunk['max_lines'],
    'has_more' => $chunk['has_more'],
    'eof' => $chunk['eof'],
    'offset_reset' => $chunk['offset_reset'],
    'protocol' => $chunk['protocol'],
    'done'   => $done,
    'status' => $status, // 'running' | 'success' | 'error'
]);
