<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$hostId = (int) ($data['host_id'] ?? 0);

if (!$hostId) jsonResponse(['error' => t('api.common.error.host_id_required')], 400);

$host = HostManager::getById($hostId);
if (!$host) jsonResponse(['error' => t('api.common.error.host_not_found')], 404);

$result = HostManager::testConnection($host);

Auth::log('host_test', "Test connexion hôte #{$hostId} ({$host['name']}): " . ($result['success'] ? 'OK' : 'ECHEC'));

jsonResponse([
    'success' => $result['success'],
    'output'  => $result['output'],
]);
