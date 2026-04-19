<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
verifyCsrf();
rateLimitApi('webauthn_reg', 10, 60);

if (!StepUpAuth::checkCurrentUserReauth('webauthn.manage')) {
    jsonResponse(['success' => false, 'error' => 'Re-authentification recente requise avant l ajout d une cle WebAuthn.']);
}

$user    = Auth::currentUser();
$options = WebAuthn::registrationOptions((int)$user['id'], $user['username']);
jsonResponse(['success' => true, 'options' => $options]);
