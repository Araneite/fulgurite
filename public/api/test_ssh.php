<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('sshkeys.manage');
verifyCsrf();

$data  = json_decode(file_get_contents('php://input'), true) ?? [];
$keyId = (int) ($data['key_id'] ?? 0);

if (!$keyId) {
    jsonResponse(['error' => 'key_id requis'], 400);
}

$result = SshKeyManager::test($keyId);
$key    = SshKeyManager::getById($keyId);

Auth::log('ssh_test', "Test connexion SSH vers {$key['user']}@{$key['host']} — " . ($result['success'] ? 'OK' : 'ECHEC'));

jsonResponse($result);
