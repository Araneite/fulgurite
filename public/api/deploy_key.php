<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('sshkeys.manage');
verifyCsrf();

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$keyId    = (int) ($data['key_id']  ?? 0);
$password = $data['password'] ?? '';

if (!$keyId || !$password) {
    jsonResponse(['error' => 'key_id et password requis'], 400);
}

$result = SshKeyManager::deployKey($keyId, $password);
$key    = SshKeyManager::getById($keyId);
Auth::log('ssh_key_deploy', "Déploiement clé vers {$key['user']}@{$key['host']} — " . ($result['success'] ? 'OK' : 'ECHEC'));

jsonResponse($result);
