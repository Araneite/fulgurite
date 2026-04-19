<?php
require_once __DIR__ . '/../../src/bootstrap.php';
rateLimitApi('webauthn_auth', 10, 60);
verifyCsrf();

$body = requestJsonBody();
$assertion = $body['assertion'] ?? [];
$authMode = (string) ($_SESSION['webauthn_auth_request']['mode'] ?? 'login');

if (empty($assertion)) {
    jsonResponse(['success' => false, 'error' => 'Donnees manquantes.']);
}

$result = WebAuthn::authVerify($assertion);

if ($result['success']) {
    $userId = (int) $result['user_id'];
    $context = is_array($result['context'] ?? null) ? $result['context'] : [];
    if (($context['mode'] ?? 'login') === 'reauth') {
        Auth::check();

        $operation = (string) ($context['operation'] ?? 'generic.sensitive');
        if (!StepUpAuth::completePendingWebAuthn($userId, $operation)) {
            jsonResponse(['success' => false, 'error' => 'Session de re-authentification WebAuthn invalide ou expiree.']);
        }

        jsonResponse(['success' => true]);
    }

    if (!Auth::completePendingWebAuthnLogin($userId)) {
        jsonResponse(['success' => false, 'error' => 'Session de second facteur invalide ou expiree.']);
    }
    jsonResponse(['success' => true, 'redirect' => Auth::postLoginRedirect()]);
}

if ($authMode === 'login') {
    jsonResponse(['success' => false, 'error' => t('auth.authentication_failed')]);
}

jsonResponse($result);
