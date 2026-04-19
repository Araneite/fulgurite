<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
verifyCsrf();

if (!StepUpAuth::checkCurrentUserReauth('webauthn.manage')) {
    jsonResponse(['success' => false, 'error' => 'Re-authentification recente requise avant la suppression d une cle WebAuthn.']);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$credId = (int) ($body['credential_id'] ?? 0);
$user   = Auth::currentUser();

if (!$credId) {
    jsonResponse(['success' => false, 'error' => 'ID manquant.']);
}

$deleted = WebAuthn::deleteCredential($credId, (int) $user['id']);
if ($deleted) {
    Auth::log('webauthn_key_deleted', "Cle WebAuthn supprimee #$credId");
}

jsonResponse([
    'success' => $deleted,
    'error' => $deleted ? null : 'Impossible de supprimer cette cle WebAuthn.',
]);
