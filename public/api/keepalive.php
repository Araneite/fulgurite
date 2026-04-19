<?php
require_once __DIR__ . '/../../src/bootstrap.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

Auth::check(); // Valide le token DB, gere revocations and expirations
$info = Auth::sessionInfo();
echo json_encode(['ok' => true, 'seconds_left' => $info['seconds_left']]);
