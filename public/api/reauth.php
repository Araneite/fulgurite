<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
verifyCsrf();
rateLimitApi('reauth', 10, 60);

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$password  = $body['password'] ?? '';
$totpCode  = trim($body['totp_code'] ?? '');
$operation = trim(strtolower((string) ($body['operation'] ?? 'generic.sensitive')));

if (!$password) {
    jsonResponse(['success' => false, 'error' => 'Mot de passe requis.']);
}

$user = Auth::currentUserRecord();
if (!$user) {
    jsonResponse(['success' => false, 'error' => 'Session invalide.'], 403);
}

$result = StepUpAuth::beginInteractiveReauth($user, (string) $password, $totpCode, $operation);
jsonResponse($result);
