<?php
require_once __DIR__ . '/../../src/bootstrap.php';
rateLimitApi('webauthn_auth', 10, 60);
verifyCsrf();

$body = requestJsonBody();
$mode = trim(strtolower((string) ($body['mode'] ?? 'login')));

if ($mode === 'reauth') {
    Auth::check();

    $operation = trim(strtolower((string) ($body['operation'] ?? 'generic.sensitive')));
    $user = Auth::currentUserRecord();
    if (!$user) {
        jsonResponse(['success' => false, 'error' => t('api.webauthn.error.invalid_session')], 403);
    }

    $pending = StepUpAuth::pendingWebAuthnRequest((int) $user['id'], $operation);
    if ($pending === null) {
        jsonResponse(['success' => false, 'error' => t('api.webauthn.error.invalid_or_expired_reauth_session')]);
    }

    $userId = (int) $user['id'];
    $context = [
        'mode' => 'reauth',
        'operation' => (string) ($pending['operation'] ?? $operation),
        'require_user_verification' => !empty($pending['require_webauthn_uv']),
    ];
} else {
    $userId  = (int) ($body['user_id'] ?? 0);
    $pending = Auth::getPendingSecondFactor();

    if (
        !$userId
        || !$pending
        || (int) $pending['user_id'] !== $userId
        || !in_array('webauthn', $pending['methods'], true)
        || (string) ($pending['active_method'] ?? '') !== 'webauthn'
    ) {
        jsonResponse(['success' => false, 'error' => t('api.webauthn.error.invalid_or_expired_login_session')]);
    }

    $context = [
        'mode' => 'login',
        'operation' => 'login.second_factor',
        'require_user_verification' => AppConfig::webauthnUserVerification() === 'required',
    ];
}

$creds = WebAuthn::getUserCredentials($userId);
if (empty($creds)) {
    jsonResponse(['success' => false, 'error' => t('api.webauthn.error.no_registered_key')]);
}

$options = WebAuthn::authOptions($userId, $context);
jsonResponse(['success' => true, 'options' => $options]);
