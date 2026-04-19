<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
verifyCsrf();

$data = requestJsonBody();
$runId = RunLogManager::sanitizeRunId((string) ($data['run_id'] ?? ''));

if ($runId === '') {
    jsonResponse(['error' => 'run_id requis'], 400);
}

RunLogManager::requireAccessibleRun('quick_backup', $runId);
$runFiles = RunLogManager::getRunFiles('quick_backup', $runId);
$logFile = $runFiles['log_file'];
$doneFile = $runFiles['done_file'];
$pidFile = $runFiles['pid_file'];
$resultFile = $runFiles['result_file'] ?? ($logFile . '.result.json');

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
        'result' => null,
    ]);
}
$chunk = RunLogManager::readIncrementalLog($logFile, $data);

$done = file_exists($doneFile);
$status = $done ? trim((string) file_get_contents($doneFile)) : 'running';
$result = null;

if ($done && file_exists($resultFile)) {
    $decoded = json_decode((string) file_get_contents($resultFile), true);
    if (is_array($decoded)) {
        $result = $decoded;
    }
}

if ($done) {
    $doneTime = filemtime($doneFile);
    if ($doneTime !== false && time() - $doneTime > 900) {
        @unlink($logFile);
        @unlink($doneFile);
        @unlink($pidFile);
        @unlink($resultFile);
        RunLogManager::deleteRunMetadata($runId);
    }
}

jsonResponse([
    'lines' => $chunk['lines'],
    'offset' => $chunk['offset'],
    'next_offset_bytes' => $chunk['next_offset_bytes'],
    'offset_bytes' => $chunk['offset_bytes'],
    'max_bytes' => $chunk['max_bytes'],
    'max_lines' => $chunk['max_lines'],
    'has_more' => $chunk['has_more'],
    'eof' => $chunk['eof'],
    'offset_reset' => $chunk['offset_reset'],
    'protocol' => $chunk['protocol'],
    'done' => $done,
    'status' => $status,
    'result' => $result,
]);
