<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-outbound-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

putenv('DB_PATH=' . $tmp . '/app.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/search.db');
$_ENV['DB_PATH'] = $tmp . '/app.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/search.db';

require_once $root . '/src/bootstrap.php';

function failTest(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertTrueTest(bool $condition, string $message): void {
    if (!$condition) {
        failTest($message);
    }
}

function assertSameTest(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        failTest($message . ' Expected: ' . var_export($expected, true) . ' Got: ' . var_export($actual, true));
    }
}

function assertThrowsTest(callable $callable, string $message): void {
    try {
        $callable();
    } catch (Throwable $e) {
        return;
    }

    failTest($message);
}

$publicValidator = new PublicOutboundUrlValidator();
assertThrowsTest(static fn() => $publicValidator->validate('http://1.1.1.1/hook'), 'Public validator must reject non-HTTPS URLs.');
assertThrowsTest(static fn() => $publicValidator->validate('https://127.0.0.1/hook'), 'Public validator must reject loopback URLs.');
assertThrowsTest(static fn() => $publicValidator->validate('https://1.1.1.1:8443/hook'), 'Public validator must reject non-standard ports.');
assertThrowsTest(static fn() => $publicValidator->validate('https://[::1]/hook'), 'Public validator must reject IPv6 loopback URLs.');
assertThrowsTest(static fn() => $publicValidator->validate('https://[fe80::1]/hook'), 'Public validator must reject IPv6 link-local URLs.');
assertThrowsTest(static fn() => $publicValidator->validate('https://[fd00::1]/hook'), 'Public validator must reject IPv6 ULA URLs.');

$publicResult = $publicValidator->validate('https://1.1.1.1/webhook');
assertSameTest('https', $publicResult['scheme'], 'Public validator should preserve HTTPS scheme.');
assertSameTest(443, $publicResult['port'], 'Public validator should normalize the default HTTPS port.');
assertTrueTest(in_array('1.1.1.1', $publicResult['resolved_ips'], true), 'Public validator should keep the resolved public IP.');

$publicIpv6Result = $publicValidator->validate('https://[2606:4700:4700::1111]/webhook');
assertSameTest('https', $publicIpv6Result['scheme'], 'Public validator should preserve HTTPS scheme for IPv6 targets.');
assertTrueTest(
    in_array('2606:4700:4700::1111', $publicIpv6Result['resolved_ips'], true),
    'Public validator should keep the resolved public IPv6 target.'
);

$trustedValidator = new TrustedServiceEndpointValidator([
    'allowed_hosts' => ['10.20.30.40'],
    'allowed_cidrs' => ['10.20.30.40/32'],
    'allow_http' => true,
    'expected_port' => 8080,
]);

$trustedResult = $trustedValidator->validate('http://10.20.30.40:8080/api/status');
assertSameTest('http', $trustedResult['scheme'], 'Trusted validator should allow HTTP only when explicitly enabled.');
assertSameTest(8080, $trustedResult['port'], 'Trusted validator should enforce the configured Infisical port.');

assertThrowsTest(
    static fn() => $trustedValidator->validate('http://10.20.30.41:8080/api/status'),
    'Trusted validator must reject hosts outside the allowlist.'
);
assertThrowsTest(
    static fn() => $trustedValidator->validate('http://10.20.30.40:9090/api/status'),
    'Trusted validator must reject unexpected ports.'
);
assertThrowsTest(
    static fn() => $trustedValidator->validate('http://127.0.0.1:8080/api/status'),
    'Trusted validator must reject loopback targets.'
);

$materialized = InfisicalConfigManager::materializeTrustedPolicyConfig([
    'infisical_enabled' => '1',
    'infisical_url' => 'http://10.20.30.40:8080',
    'infisical_token' => 'token',
    'infisical_token_value' => 'token',
    'infisical_project_id' => '',
    'infisical_environment' => 'prod',
    'infisical_secret_path' => '/',
    'infisical_allowed_hosts' => '',
    'infisical_allowed_host_patterns' => '',
    'infisical_allowed_cidrs' => '',
    'infisical_allowed_port' => '',
    'infisical_allow_http' => '1',
]);
assertSameTest('10.20.30.40', $materialized['infisical_allowed_hosts'], 'Infisical policy should infer the trusted host when omitted.');
assertSameTest('10.20.30.40/32', $materialized['infisical_allowed_cidrs'], 'Infisical policy should infer the current resolved IP perimeter when omitted.');
assertSameTest('8080', $materialized['infisical_allowed_port'], 'Infisical policy should infer the expected port when omitted.');

echo "outbound_url_validators tests OK.\n";
