<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-step-up-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';

require_once $root . '/src/bootstrap.php';

function fail(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertTrueValue(bool $value, string $message): void {
    if (!$value) {
        fail($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fail($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

$db = Database::getInstance();

$passwordHash = password_hash('S3cret!Pass', PASSWORD_BCRYPT);
$db->prepare("
    INSERT INTO users (username, password, role, totp_secret, totp_enabled, primary_second_factor)
    VALUES (?, ?, 'admin', NULL, 0, '')
")->execute(['plain-user', $passwordHash]);
$plainUserId = (int) $db->lastInsertId();

$db->prepare("
    INSERT INTO users (username, password, role, totp_secret, totp_enabled, primary_second_factor)
    VALUES (?, ?, 'admin', ?, 1, '')
")->execute(['totp-user', $passwordHash, 'JBSWY3DPEHPK3PXP']);
$totpUserId = (int) $db->lastInsertId();

$db->prepare("
    INSERT INTO users (username, password, role, totp_secret, totp_enabled, primary_second_factor)
    VALUES (?, ?, 'admin', NULL, 0, '')
")->execute(['webauthn-user', $passwordHash]);
$webauthnUserId = (int) $db->lastInsertId();

$db->prepare("
    INSERT INTO users (username, password, role, totp_secret, totp_enabled, primary_second_factor)
    VALUES (?, ?, 'admin', ?, 1, '')
")->execute(['hybrid-user', $passwordHash, 'JBSWY3DPEHPK3PXP']);
$hybridUserId = (int) $db->lastInsertId();

$db->prepare("
    INSERT INTO webauthn_credentials (user_id, name, credential_id, public_key, transports, counter, counter_supported, use_count)
    VALUES (?, ?, ?, ?, ?, 0, 0, 0)
")->execute([
    $webauthnUserId,
    'Primary key',
    'cred-primary',
    json_encode(['alg' => 'test'], JSON_UNESCAPED_SLASHES),
    null,
]);
$db->prepare("
    INSERT INTO webauthn_credentials (user_id, name, credential_id, public_key, transports, counter, counter_supported, use_count)
    VALUES (?, ?, ?, ?, ?, 0, 0, 0)
")->execute([
    $hybridUserId,
    'Hybrid key',
    'cred-hybrid',
    json_encode(['alg' => 'test'], JSON_UNESCAPED_SLASHES),
    null,
]);

assertSameValue(StepUpAuth::FACTOR_NONE, StepUpAuth::syncPrimaryFactor($plainUserId), 'Plain accounts should resolve to no primary second factor.');
assertSameValue(StepUpAuth::FACTOR_CLASSIC, StepUpAuth::syncPrimaryFactor($totpUserId), 'TOTP-only accounts should resolve to classic 2FA.');
assertSameValue(StepUpAuth::FACTOR_WEBAUTHN, StepUpAuth::syncPrimaryFactor($webauthnUserId), 'Existing WebAuthn credentials should become the primary second factor.');
assertSameValue(StepUpAuth::FACTOR_WEBAUTHN, StepUpAuth::syncPrimaryFactor($hybridUserId), 'Hybrid accounts should fall back to WebAuthn until an explicit choice is stored.');

foreach ([
    'generic.sensitive',
    'profile.sensitive',
    'users.sensitive',
    'settings.sensitive',
    'webauthn.manage',
    'resetup.step1',
] as $operation) {
    assertSameValue($operation, (string) (StepUpAuth::policyForOperation($operation)['operation'] ?? ''), 'Sensitive step-up operations must remain explicitly mapped.');
}

$hybridUser = UserManager::getById($hybridUserId);
assertTrueValue(is_array($hybridUser), 'The hybrid test user should exist.');
assertSameValue(StepUpAuth::FACTOR_CLASSIC, StepUpAuth::choosePrimaryFactor($hybridUserId, StepUpAuth::FACTOR_CLASSIC), 'Hybrid accounts should allow choosing classic 2FA explicitly.');
assertSameValue(StepUpAuth::FACTOR_CLASSIC, StepUpAuth::syncPrimaryFactor($hybridUserId), 'An explicit classic choice must not be overwritten by WebAuthn fallback.');
assertSameValue(StepUpAuth::FACTOR_NONE, StepUpAuth::validateRequestedPrimaryFactor(UserManager::getById($plainUserId), StepUpAuth::FACTOR_NONE), 'Accounts without strong factors should keep the none marker.');
try {
    StepUpAuth::choosePrimaryFactor($plainUserId, StepUpAuth::FACTOR_WEBAUTHN);
    fail('Selecting an unavailable primary factor should fail.');
} catch (RuntimeException $e) {
    assertTrueValue(str_contains($e->getMessage(), 'pas disponible'), 'Unavailable factor errors should be explicit.');
}

$webauthnUser = UserManager::getById($webauthnUserId);
assertTrueValue(is_array($webauthnUser), 'The WebAuthn test user should exist.');

$totpUser = UserManager::getById($totpUserId);
assertTrueValue(is_array($totpUser), 'The TOTP test user should exist.');

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $totpUserId;
$_SESSION['username'] = 'totp-user';
$_SESSION['role'] = 'admin';
Auth::refreshSessionUser();

$totpResult = StepUpAuth::beginInteractiveReauth($totpUser, 'S3cret!Pass', Totp::getCode('JBSWY3DPEHPK3PXP'), 'profile.sensitive');
assertTrueValue(!empty($totpResult['success']) && !empty($totpResult['completed']), 'Classic 2FA reauth should complete with a valid TOTP code.');
assertSameValue(StepUpAuth::FACTOR_CLASSIC, (string) ($totpResult['factor'] ?? ''), 'Classic 2FA reauth should record the classic factor.');
assertTrueValue(StepUpAuth::checkCurrentUserReauth('profile.sensitive'), 'A classic 2FA proof should satisfy its target operation.');
assertTrueValue(!StepUpAuth::checkCurrentUserReauth('users.sensitive'), 'A classic 2FA proof must not satisfy a different sensitive operation.');
assertTrueValue(StepUpAuth::consumeCurrentUserReauth('profile.sensitive'), 'A valid classic 2FA proof should be consumable for its own operation.');
assertTrueValue(!StepUpAuth::checkCurrentUserReauth('profile.sensitive'), 'A consumed classic 2FA proof should not remain reusable.');

$_SESSION['step_up_reauth'] = [
    'user_id' => $totpUserId,
    'factor' => StepUpAuth::FACTOR_CLASSIC,
    'time' => time(),
];
assertTrueValue(!StepUpAuth::checkCurrentUserReauth('profile.sensitive'), 'Proofs missing an operation must not fall back to a valid sensitive operation.');

$_SESSION['step_up_reauth'] = [
    'user_id' => $totpUserId,
    'factor' => StepUpAuth::FACTOR_CLASSIC,
    'operation' => 'profile.sensitive',
    'time' => time() - 5,
];
assertTrueValue(!StepUpAuth::checkCurrentUserReauth('profile.sensitive', 1), 'Expired classic 2FA proofs must be rejected.');
StepUpAuth::clearReauth();

$result = StepUpAuth::beginInteractiveReauth($webauthnUser, 'S3cret!Pass', '', 'generic.sensitive');
assertTrueValue(!empty($result['success']), 'Password verification should pass before WebAuthn step-up.');
assertSameValue(false, (bool) ($result['completed'] ?? true), 'WebAuthn-primary accounts should require a WebAuthn second step.');
assertSameValue(StepUpAuth::FACTOR_WEBAUTHN, (string) ($result['next_factor'] ?? ''), 'The second step should require WebAuthn.');
assertTrueValue(!StepUpAuth::completePendingWebAuthn($webauthnUserId, 'webauthn.manage'), 'A pending WebAuthn proof must not complete for a different operation.');

$result = StepUpAuth::beginInteractiveReauth($webauthnUser, 'S3cret!Pass', '', 'generic.sensitive');
assertTrueValue(!empty($result['success']), 'WebAuthn reauth should restart cleanly after a rejected completion attempt.');
assertTrueValue(StepUpAuth::completePendingWebAuthn($webauthnUserId, 'generic.sensitive'), 'A pending WebAuthn step-up should complete successfully.');

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $webauthnUserId;
$_SESSION['username'] = 'webauthn-user';
$_SESSION['role'] = 'admin';
Auth::refreshSessionUser();
assertTrueValue(StepUpAuth::checkCurrentUserReauth('generic.sensitive'), 'Completed WebAuthn step-up should satisfy sensitive reauth checks.');
assertTrueValue(!StepUpAuth::checkCurrentUserReauth('webauthn.manage'), 'A WebAuthn proof must not be replayable for a different sensitive operation.');
assertTrueValue(StepUpAuth::consumeCurrentUserReauth('generic.sensitive'), 'Sensitive reauth should be consumable exactly once.');
assertTrueValue(!StepUpAuth::checkCurrentUserReauth('generic.sensitive'), 'Consumed reauth proof should no longer be reusable.');

$plainUser = UserManager::getById($plainUserId);
assertTrueValue(is_array($plainUser), 'The plain test user should exist.');
$plainResult = StepUpAuth::beginInteractiveReauth($plainUser, 'S3cret!Pass', '', 'generic.sensitive');
assertTrueValue(!empty($plainResult['success']) && !empty($plainResult['completed']), 'Accounts without a primary second factor should still complete password-only reauth.');
assertSameValue(StepUpAuth::FACTOR_NONE, (string) ($plainResult['factor'] ?? ''), 'Password-only reauth should be marked with the none factor.');

$reflection = new ReflectionClass(WebAuthn::class);
$counterMethod = $reflection->getMethod('updateCountersAfterSuccessfulAssertion');
$counterMethod->setAccessible(true);

$stmt = $db->prepare('SELECT * FROM webauthn_credentials WHERE user_id = ? AND credential_id = ?');
$stmt->execute([$webauthnUserId, 'cred-primary']);
$credential = $stmt->fetch();
assertTrueValue(is_array($credential), 'The primary WebAuthn credential should exist.');

$counterMethod->invoke(null, $credential, 0, $webauthnUserId);
$stmt->execute([$webauthnUserId, 'cred-primary']);
$credential = $stmt->fetch();
assertSameValue(0, (int) $credential['counter'], 'Zero signCount authenticators should keep the stored signCount at zero.');
assertSameValue(0, (int) $credential['counter_supported'], 'Zero signCount authenticators should remain marked as unsupported counters.');
assertSameValue(1, (int) $credential['use_count'], 'Zero signCount authenticators should still increment the usage counter.');

$db->prepare("
    INSERT INTO webauthn_credentials (user_id, name, credential_id, public_key, transports, counter, counter_supported, use_count)
    VALUES (?, ?, ?, ?, ?, 0, 0, 0)
")->execute([
    $webauthnUserId,
    'Incrementing key',
    'cred-incrementing',
    json_encode(['alg' => 'test'], JSON_UNESCAPED_SLASHES),
    null,
]);
$stmt->execute([$webauthnUserId, 'cred-incrementing']);
$incrementingCredential = $stmt->fetch();
assertTrueValue(is_array($incrementingCredential), 'The incrementing WebAuthn credential should exist.');

$counterMethod->invoke(null, $incrementingCredential, 7, $webauthnUserId);
$stmt->execute([$webauthnUserId, 'cred-incrementing']);
$incrementingCredential = $stmt->fetch();
assertSameValue(7, (int) $incrementingCredential['counter'], 'Reliable signCount values should be persisted.');
assertSameValue(1, (int) $incrementingCredential['counter_supported'], 'Positive signCount values should mark the counter as supported.');
assertSameValue(1, (int) $incrementingCredential['use_count'], 'Successful assertions should increment the usage counter.');

assertTrueValue(WebAuthn::deleteCredential((int) ($db->query("SELECT id FROM webauthn_credentials WHERE credential_id = 'cred-hybrid'")->fetchColumn()), $hybridUserId), 'Deleting the last WebAuthn key should succeed.');
assertSameValue(StepUpAuth::FACTOR_CLASSIC, StepUpAuth::syncPrimaryFactor($hybridUserId), 'Removing the last WebAuthn key should fall back to classic 2FA when it is still available.');
Auth::totpDisable($hybridUserId);
assertSameValue(StepUpAuth::FACTOR_NONE, StepUpAuth::syncPrimaryFactor($hybridUserId), 'Removing the remaining classic factor should fall back to none.');

echo "Step-up and WebAuthn counter tests OK.\n";
