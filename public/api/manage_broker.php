<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();
rateLimitApi('manage_broker', 20, 60);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = (string) ($data['action'] ?? '');

if ($action === 'analyze_health') {
    $result = BrokerClusterMonitor::checkAndNotify();
    jsonResponse([
        'success' => true,
        'message' => 'Analyse broker terminee.',
        'health' => $result['health'] ?? [],
        'status' => $result['status'] ?? [],
        'events' => $result['events'] ?? [],
    ]);
}

jsonResponse(['success' => false, 'error' => 'Action invalide'], 400);
