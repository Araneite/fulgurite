<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();

function formatNotificationTestResponse(array $result): array {
    $success = !empty($result['success']);
    $httpStatus = isset($result['http_status']) ? (int) $result['http_status'] : 0;
    $rawOutput = strtolower((string) ($result['output'] ?? ''));

    if ($success) {
        $message = $httpStatus > 0 ? 'Endpoint reachable' : 'Notification test sent';
    } elseif (
        str_contains($rawOutput, 'security policy')
        || str_contains($rawOutput, 'invalid outbound url')
        || str_contains($rawOutput, 'invalid outbound host address')
        || str_contains($rawOutput, 'unable to resolve outbound host')
    ) {
        $message = 'Outbound URL blocked by security policy';
    } elseif ($httpStatus > 0) {
        $message = 'Remote endpoint rejected the request';
    } elseif (str_contains($rawOutput, 'canal inconnu')) {
        $message = 'Unknown notification channel';
    } else {
        $message = 'Notification test failed';
    }

    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($httpStatus > 0) {
        $response['http_status'] = $httpStatus;
    }

    return $response;
}

$data    = json_decode(file_get_contents('php://input'), true) ?? [];
$channel = $data['channel'] ?? '';

if (!empty($data['profile']) && !empty($data['event'])) {
    $profile = (string) $data['profile'];
    $event = (string) $data['event'];
    $policy = Notifier::decodePolicy(
        isset($data['policy']) && is_string($data['policy']) ? $data['policy'] : json_encode($data['policy'] ?? []),
        $profile
    );
    $contextName = trim((string) ($data['context_name'] ?? ''));
    $result = Notifier::testPolicy($profile, $policy, $event, $contextName);
    Auth::log('notification_test', "Test policy: $profile/$event - " . ($result['success'] ? 'OK' : 'ECHEC'));
    jsonResponse(formatNotificationTestResponse($result));
}

$result = match($channel) {
    'discord'  => Notifier::testDiscord(),
    'slack'    => Notifier::testSlack(),
    'telegram' => Notifier::testTelegram(),
    'ntfy'     => Notifier::testNtfy(),
    'webhook'  => Notifier::testWebhook(),
    'teams'    => Notifier::testTeams(),
    'gotify'   => Notifier::testGotify(),
    'in_app'   => Notifier::testInApp(),
    'web_push' => Notifier::testWebPush(),
    'email'    => Notifier::sendTest(Database::getSetting('mail_to')),
    default    => ['success' => false, 'output' => 'Canal inconnu'],
};

Auth::log('notification_test', "Test canal: $channel — " . ($result['success'] ? 'OK' : 'ECHEC'));
jsonResponse(formatNotificationTestResponse($result));
