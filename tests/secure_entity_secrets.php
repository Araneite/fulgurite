<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-secure-entity-secrets-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

$key = base64_encode(random_bytes(32));
putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
putenv('FULGURITE_SECRET_KEY=' . $key);
putenv('FULGURITE_SECRET_PROVIDER=agent');
putenv('FULGURITE_SECRET_AGENT_SOCKET=' . $tmp . '/missing-agent.sock');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_ENV['FULGURITE_SECRET_KEY'] = $key;
$_ENV['FULGURITE_SECRET_PROVIDER'] = 'agent';
$_ENV['FULGURITE_SECRET_AGENT_SOCKET'] = $tmp . '/missing-agent.sock';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['FULGURITE_SECRET_KEY'] = $key;
$_SERVER['FULGURITE_SECRET_PROVIDER'] = 'agent';
$_SERVER['FULGURITE_SECRET_AGENT_SOCKET'] = $tmp . '/missing-agent.sock';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'fulgurite-test';

require_once $root . '/src/bootstrap.php';
require_once $root . '/src/Api/Handlers/UsersHandler.php';

final class MutableMemorySecretProvider implements SecretProvider {
    public array $store = [];

    public function __construct(private string $providerName, public bool $healthy = true) {}

    public function put(string $ref, string $value, array $metadata = []): string {
        $this->assertRef($ref);
        if (!$this->healthy) {
            throw new RuntimeException('provider ' . $this->providerName . ' down for ' . $ref);
        }
        $this->store[$ref] = $value;
        return $ref;
    }

    public function get(string $ref, string $purpose = 'runtime', array $context = []): ?string {
        $this->assertRef($ref);
        if (!$this->healthy) {
            throw new RuntimeException('provider ' . $this->providerName . ' down for ' . $ref);
        }
        return $this->store[$ref] ?? null;
    }

    public function delete(string $ref): void {
        $this->assertRef($ref);
        unset($this->store[$ref]);
    }

    public function exists(string $ref): bool {
        $this->assertRef($ref);
        return array_key_exists($ref, $this->store);
    }

    public function health(): array {
        return ['ok' => $this->healthy, 'provider' => $this->providerName];
    }

    private function assertRef(string $ref): void {
        if (!str_starts_with($ref, 'secret://' . $this->providerName . '/')) {
            throw new RuntimeException('unexpected ref for provider ' . $this->providerName);
        }
    }
}

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

function removeTree(string $path): void {
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($path);
}

try {
    $db = Database::getInstance();
    $passwordHash = password_hash('S3cret!Pass', PASSWORD_BCRYPT);

    $db->prepare("
        INSERT INTO users (username, password, role, totp_enabled)
        VALUES (?, ?, 'admin', 0)
    ")->execute(['owner', $passwordHash]);
    $ownerId = (int) $db->lastInsertId();

    $createdHook = ApiWebhookManager::create([
        'name' => 'Primary webhook',
        'url' => 'https://example.com/hooks/fulgurite',
        'events' => ['backup_job.success'],
        'enabled' => true,
    ], $ownerId);
    $createdHookId = (int) $createdHook['id'];
    $createdWebhookSecret = (string) ($createdHook['revealed_secret'] ?? '');

    assertTrueValue($createdWebhookSecret !== '', 'Webhook creation should reveal the secret once.');
    $webhookRow = $db->prepare('SELECT secret, secret_ref FROM api_webhooks WHERE id = ?');
    $webhookRow->execute([$createdHookId]);
    $webhookRow = $webhookRow->fetch();
    assertSameValue('', (string) ($webhookRow['secret'] ?? ''), 'New webhook writes must not keep plaintext in api_webhooks.secret.');
    assertTrueValue(
        is_string($webhookRow['secret_ref'] ?? null) && str_starts_with((string) $webhookRow['secret_ref'], 'secret://local/api-webhook/'),
        'Webhook secret_ref should fall back to local encrypted storage when the broker is unavailable.'
    );
    assertTrueValue(!array_key_exists('secret', $createdHook), 'Hydrated webhook objects must not expose the resolved secret.');
    assertTrueValue(!array_key_exists('secret', ApiWebhookManager::publicView($createdHook)), 'Webhook public views must not expose the secret.');
    assertSameValue($createdWebhookSecret, SensitiveEntitySecretManager::getSecret(
        SensitiveEntitySecretManager::CONTEXT_API_WEBHOOK,
        ApiWebhookManager::getById($createdHookId),
        'runtime',
        ['scope' => 'test']
    ), 'Stored webhook secret should resolve from secure storage.');

    $db->prepare("
        INSERT INTO api_webhooks (name, url, secret, events_json, enabled, created_by)
        VALUES (?, ?, ?, ?, 1, ?)
    ")->execute([
        'Legacy webhook',
        'https://example.com/legacy',
        'legacy-webhook-secret',
        json_encode(['backup_job.failure'], JSON_UNESCAPED_SLASHES),
        $ownerId,
    ]);
    $legacyHookId = (int) $db->lastInsertId();
    $legacyHook = ApiWebhookManager::getById($legacyHookId);
    assertSameValue('legacy-webhook-secret', SensitiveEntitySecretManager::getSecret(
        SensitiveEntitySecretManager::CONTEXT_API_WEBHOOK,
        $legacyHook,
        'runtime',
        ['scope' => 'test']
    ), 'Legacy webhook reads should still return the legacy plaintext secret.');
    $legacyWebhookRow = $db->prepare('SELECT secret, secret_ref FROM api_webhooks WHERE id = ?');
    $legacyWebhookRow->execute([$legacyHookId]);
    $legacyWebhookRow = $legacyWebhookRow->fetch();
    assertSameValue('', (string) ($legacyWebhookRow['secret'] ?? ''), 'Legacy webhook migration should clear the plaintext column.');
    assertTrueValue(
        is_string($legacyWebhookRow['secret_ref'] ?? null) && str_starts_with((string) $legacyWebhookRow['secret_ref'], 'secret://local/api-webhook/'),
        'Legacy webhook migration should persist a secure secret reference.'
    );

    Database::setSetting('smtp_pass', 'smtp-local-secret');
    $sensitiveSettingRow = $db->prepare('SELECT value FROM settings WHERE key = ?');
    $sensitiveSettingRow->execute(['smtp_pass']);
    $storedSensitiveSetting = (string) $sensitiveSettingRow->fetchColumn();
    assertTrueValue(
        str_starts_with($storedSensitiveSetting, 'secret://local/setting/'),
        'Sensitive settings should fall back to local encrypted storage instead of plaintext when the broker is unavailable.'
    );
    assertSameValue('smtp-local-secret', Database::getSetting('smtp_pass'), 'Sensitive settings should resolve from secure storage.');

    $db->prepare("
        INSERT INTO settings (key, value, updated_at)
        VALUES ('webhook_auth_token', 'legacy-setting-secret', datetime('now'))
        ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at
    ")->execute();
    assertSameValue('legacy-setting-secret', Database::getSetting('webhook_auth_token'), 'Legacy plaintext settings should still resolve during migration.');
    $migratedSettingRow = $db->prepare('SELECT value FROM settings WHERE key = ?');
    $migratedSettingRow->execute(['webhook_auth_token']);
    $migratedSensitiveSetting = (string) $migratedSettingRow->fetchColumn();
    assertTrueValue(
        str_starts_with($migratedSensitiveSetting, 'secret://local/setting/'),
        'Legacy sensitive settings should be migrated to secure refs instead of remaining plaintext.'
    );

    $secureUserId = UserManager::createUser([
        'username' => 'secure-user',
        'password_hash' => $passwordHash,
        'role' => ROLE_ADMIN,
    ]);
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $secureUserId;
    $_SESSION['username'] = 'secure-user';
    $_SESSION['role'] = ROLE_ADMIN;

    $totpSetup = Auth::totpSetupStart();
    $setupSecret = (string) ($_SESSION['totp_setup_secret'] ?? '');
    assertTrueValue($setupSecret !== '', 'TOTP setup must keep a pending secret in session.');
    assertTrueValue(Auth::totpSetupConfirm(Totp::getCode($setupSecret)), 'Secure TOTP setup confirmation should succeed.');

    $secureUserRow = $db->prepare('SELECT totp_secret, totp_secret_ref, totp_enabled FROM users WHERE id = ?');
    $secureUserRow->execute([$secureUserId]);
    $secureUserRow = $secureUserRow->fetch();
    assertSameValue(null, $secureUserRow['totp_secret'] ?? null, 'New TOTP writes must not keep plaintext in users.totp_secret.');
    assertTrueValue(
        is_string($secureUserRow['totp_secret_ref'] ?? null) && str_starts_with((string) $secureUserRow['totp_secret_ref'], 'secret://local/user/'),
        'New TOTP writes should persist a secure secret ref when the broker is unavailable.'
    );
    assertSameValue(1, (int) ($secureUserRow['totp_enabled'] ?? 0), 'TOTP setup should enable the factor.');

    $hydratedSecureUser = UserManager::getById($secureUserId);
    assertTrueValue(is_array($hydratedSecureUser), 'Secure TOTP user should be loadable.');
    assertTrueValue(!array_key_exists('totp_secret', $hydratedSecureUser), 'Hydrated user records must not expose the TOTP secret.');
    assertTrueValue(!array_key_exists('totp_secret', UsersHandler::publicView($hydratedSecureUser)), 'User API public views must not expose the TOTP secret.');

    $_SESSION['login_pending_user_id'] = $secureUserId;
    $_SESSION['login_pending_username'] = 'secure-user';
    $_SESSION['login_pending_role'] = ROLE_ADMIN;
    $_SESSION['login_pending_methods'] = ['totp'];
    $_SESSION['login_pending_preferred_method'] = 'totp';
    $_SESSION['login_pending_time'] = time();
    assertTrueValue(Auth::verifyTotp(Totp::getCode($setupSecret)), 'Securely stored TOTP secrets must remain verifiable.');

    $db->prepare("
        INSERT INTO users (username, password, role, totp_secret, totp_enabled)
        VALUES (?, ?, 'admin', ?, 1)
    ")->execute(['legacy-user', $passwordHash, 'JBSWY3DPEHPK3PXP']);
    $legacyUserId = (int) $db->lastInsertId();
    $_SESSION['login_pending_user_id'] = $legacyUserId;
    $_SESSION['login_pending_username'] = 'legacy-user';
    $_SESSION['login_pending_role'] = ROLE_ADMIN;
    $_SESSION['login_pending_methods'] = ['totp'];
    $_SESSION['login_pending_preferred_method'] = 'totp';
    $_SESSION['login_pending_time'] = time();
    assertTrueValue(Auth::verifyTotp(Totp::getCode('JBSWY3DPEHPK3PXP')), 'Legacy plaintext TOTP secrets must still verify during migration.');
    $legacyUserRow = $db->prepare('SELECT totp_secret, totp_secret_ref FROM users WHERE id = ?');
    $legacyUserRow->execute([$legacyUserId]);
    $legacyUserRow = $legacyUserRow->fetch();
    assertSameValue(null, $legacyUserRow['totp_secret'] ?? null, 'Legacy TOTP migration should clear the plaintext column.');
    assertTrueValue(
        is_string($legacyUserRow['totp_secret_ref'] ?? null) && str_starts_with((string) $legacyUserRow['totp_secret_ref'], 'secret://local/user/'),
        'Legacy TOTP verification should migrate the secret to secure storage.'
    );

    $agentProvider = new MutableMemorySecretProvider('agent', true);
    $localProvider = new MutableMemorySecretProvider('local', true);
    SecretStore::useProvidersForTests($agentProvider, $localProvider);
    SensitiveEntitySecretManager::resetRuntimeCache();

    $agentUpHook = ApiWebhookManager::create([
        'name' => 'Agent up webhook',
        'url' => 'https://example.com/agent-up',
        'events' => ['backup_job.success'],
        'enabled' => true,
        'secret' => 'agent-up-secret',
    ], $ownerId);
    $agentUpRow = $db->prepare('SELECT secret_ref FROM api_webhooks WHERE id = ?');
    $agentUpRow->execute([(int) $agentUpHook['id']]);
    $agentUpRef = (string) $agentUpRow->fetchColumn();
    assertTrueValue(str_starts_with($agentUpRef, 'secret://agent/api-webhook/'), 'Broker-up writes should target the agent provider.');
    assertSameValue('agent-up-secret', $agentProvider->store[$agentUpRef] ?? null, 'Agent provider should receive the secret when healthy.');

    Database::setSetting('gotify_token', 'agent-setting-secret');
    $agentSettingRow = $db->prepare('SELECT value FROM settings WHERE key = ?');
    $agentSettingRow->execute(['gotify_token']);
    $agentSettingRef = (string) $agentSettingRow->fetchColumn();
    assertTrueValue(str_starts_with($agentSettingRef, 'secret://agent/setting/'), 'Healthy brokers should receive sensitive settings too.');
    assertSameValue('agent-setting-secret', $agentProvider->store[$agentSettingRef] ?? null, 'Agent provider should store sensitive settings when healthy.');

    $agentProvider->healthy = false;
    SensitiveEntitySecretManager::resetRuntimeCache();
    $fallbackHook = ApiWebhookManager::create([
        'name' => 'Fallback webhook',
        'url' => 'https://example.com/fallback',
        'events' => ['backup_job.success'],
        'enabled' => true,
        'secret' => 'fallback-secret',
    ], $ownerId);
    $fallbackRow = $db->prepare('SELECT secret_ref FROM api_webhooks WHERE id = ?');
    $fallbackRow->execute([(int) $fallbackHook['id']]);
    $fallbackRef = (string) $fallbackRow->fetchColumn();
    assertTrueValue(str_starts_with($fallbackRef, 'secret://local/api-webhook/'), 'Broker-down writes should fall back to local storage.');
    assertSameValue('fallback-secret', $localProvider->store[$fallbackRef] ?? null, 'Local provider should receive the fallback secret.');

    $agentProvider->healthy = true;
    SensitiveEntitySecretManager::resetRuntimeCache();
    $resolvedFallbackSecret = SensitiveEntitySecretManager::getSecret(
        SensitiveEntitySecretManager::CONTEXT_API_WEBHOOK,
        ApiWebhookManager::getById((int) $fallbackHook['id']),
        'runtime',
        ['scope' => 'test-remigration']
    );
    assertSameValue('fallback-secret', $resolvedFallbackSecret, 'Fallback secret should still resolve when the broker comes back.');
    $remigratedRow = $db->prepare('SELECT secret_ref FROM api_webhooks WHERE id = ?');
    $remigratedRow->execute([(int) $fallbackHook['id']]);
    $remigratedRef = (string) $remigratedRow->fetchColumn();
    assertTrueValue(str_starts_with($remigratedRef, 'secret://agent/api-webhook/'), 'Reading a local fallback secret should remigrate it back to the broker.');
    assertSameValue('fallback-secret', $agentProvider->store[$remigratedRef] ?? null, 'Remigration should copy the secret into the broker provider.');
    assertTrueValue(!isset($localProvider->store[$fallbackRef]), 'Remigration should remove the old local fallback secret.');

    $redactedMessage = SecretRedaction::redactText(
        'provider down for secret://agent/api-webhook/99/secret with value fallback-secret',
        ['fallback-secret']
    );
    assertTrueValue(!str_contains($redactedMessage, 'fallback-secret'), 'Redacted messages must hide explicit secret values.');
    assertTrueValue(!str_contains($redactedMessage, 'secret://agent/api-webhook/99/secret'), 'Redacted messages must hide secret refs.');
    $redactedContext = SecretRedaction::redactValue([
        'secret' => 'alpha',
        'secret_ref' => 'secret://local/user/1/totp',
        'nested' => ['totp_secret' => 'beta', 'ok' => 'value'],
    ]);
    assertSameValue('[redacted-secret]', $redactedContext['secret'], 'SecretRedaction should mask direct secret keys.');
    assertSameValue('[redacted-secret]', $redactedContext['secret_ref'], 'SecretRedaction should mask secret ref keys.');
    assertSameValue('[redacted-secret]', $redactedContext['nested']['totp_secret'], 'SecretRedaction should mask nested secret keys.');
    assertSameValue('value', $redactedContext['nested']['ok'], 'SecretRedaction should preserve non-sensitive fields.');

    echo "Secure entity secret tests OK.\n";
} finally {
    SecretStore::resetRuntimeState();
    SensitiveEntitySecretManager::resetRuntimeCache();
    removeTree($tmp);
}
