<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('ssh_host_key.approve');
verifyCsrf();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$host = trim((string) ($data['host'] ?? ''));
$port = (int) ($data['port'] ?? 22);

if ($host === '') {
    jsonResponse(['success' => false, 'error' => 'host requis'], 400);
}

try {
    $record = SshKeyManager::fetchDetectedHostKey($host, $port);
    jsonResponse(['success' => true, 'record' => $record]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 200);
}
