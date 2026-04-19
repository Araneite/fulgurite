<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$baseConfig = InfisicalConfigManager::currentConfig();
$candidate = InfisicalConfigManager::candidateFromInput(is_array($body) ? $body : [], $baseConfig);
$result = InfisicalConfigManager::testConfiguration($candidate);

Auth::log('infisical_test', 'Test connexion Infisical — ' . ($result['success'] ? 'OK' : 'ÉCHEC'));
jsonResponse($result);
