<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
verifyCsrf();
rateLimitApi('webauthn_reg', 10, 60);

if (!StepUpAuth::checkCurrentUserReauth('webauthn.manage')) {
    jsonResponse(['success' => false, 'error' => 'Re-authentification recente requise avant l ajout d une cle WebAuthn.']);
}

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$credential = $body['credential'] ?? [];
$keyName    = trim($body['name'] ?? 'Clé ' . date('d/m/Y'));

if (empty($credential)) {
    jsonResponse(['success' => false, 'error' => 'Données manquantes.']);
}

$result = WebAuthn::registrationVerify($credential, $keyName);
if ($result['success']) {
    $user = Auth::currentUser();
    Auth::log('webauthn_key_added', "Clé WebAuthn ajoutée: $keyName");
}
jsonResponse($result);
