<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-ssh-host-trust-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

$key = base64_encode(random_bytes(32));
putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
putenv('FULGURITE_SECRET_KEY=' . $key);
putenv('FULGURITE_SECRET_PROVIDER=local');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_ENV['FULGURITE_SECRET_KEY'] = $key;
$_ENV['FULGURITE_SECRET_PROVIDER'] = 'local';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['FULGURITE_SECRET_KEY'] = $key;
$_SERVER['FULGURITE_SECRET_PROVIDER'] = 'local';

require_once $root . '/src/bootstrap.php';

if (PHP_OS_FAMILY === 'Windows') {
    echo "SKIP: ssh_host_trust requires OpenSSH key generation in a Unix-like runtime.\n";
    exit(0);
}

function assertTrueValue(bool $value, string $message): void {
    if (!$value) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function generatePublicKey(string $tmpDir, string $name): string {
    $path = rtrim($tmpDir, '/\\') . DIRECTORY_SEPARATOR . $name;
    $binary = ProcessRunner::locateBinary('ssh-keygen', ['/usr/bin/ssh-keygen', '/usr/local/bin/ssh-keygen']);
    if ($binary === '') {
        echo "SKIP: ssh-keygen unavailable for ssh_host_trust test.\n";
        exit(0);
    }

    $result = ProcessRunner::run([$binary, '-q', '-t', 'ed25519', '-N', '', '-f', $path], [
        'env' => [
            'HOME' => sys_get_temp_dir(),
            'PATH' => (string) getenv('PATH'),
        ],
    ]);
    if (empty($result['success'])) {
        fwrite(STDERR, "ssh-keygen failed for ssh_host_trust test.\n" . (string) ($result['output'] ?? '') . "\n");
        exit(1);
    }

    $publicKey = trim((string) file_get_contents($path . '.pub'));
    @unlink($path);
    @unlink($path . '.pub');
    return $publicKey;
}

try {
    $firstKey = generatePublicKey($tmp, 'hostkey-one');
    $secondKey = generatePublicKey($tmp, 'hostkey-two');

    assertTrueValue(isset(AppConfig::permissionDefinitions()['ssh_host_key.approve']), 'ssh_host_key.approve permission must be registered.');

    SshKeyManager::approveHostKey('backup.example', 2222, $firstKey);
    $record = SshKeyManager::getHostTrustRecord('backup.example', 2222);
    assertTrueValue(is_array($record), 'Host trust record should exist after approval.');
    assertSameValue(SshKeyManager::HOST_KEY_VALID, $record['status'], 'Approved host key should become VALID.');
    assertTrueValue(str_starts_with((string) $record['approved_key_ref'], 'secret://local/ssh_host_key/'), 'Approved host key should be stored through SecretStore.');
    assertTrueValue(SshKnownHosts::isHostKeyKnown('backup.example', 2222), 'Known host lookup should use approved host keys.');

    $knownHosts = file_get_contents(SshKnownHosts::knownHostsFile()) ?: '';
    assertTrueValue(str_contains($knownHosts, '[backup.example]:2222 '), 'known_hosts file should render non-default SSH ports.');

    $analysis = SshKeyManager::analyzeHostPublicKey($secondKey);
    SshKeyManager::recordDetectedHostKey('backup.example', 2222, SshKeyManager::HOST_KEY_CHANGED, $analysis['type'], $analysis['fingerprint'], 'unit_test', (string) $record['approved_fingerprint']);
    $record = SshKeyManager::getHostTrustRecord('backup.example', 2222);
    assertSameValue(SshKeyManager::HOST_KEY_CHANGED, $record['status'], 'Changed host key should be tracked as CHANGED.');
    assertSameValue($analysis['fingerprint'], $record['detected_fingerprint'], 'Detected fingerprint should be persisted for approval UI.');

    SshKeyManager::replaceHostKey('backup.example', 2222, $secondKey);
    $record = SshKeyManager::getHostTrustRecord('backup.example', 2222);
    assertSameValue(SshKeyManager::HOST_KEY_VALID, $record['status'], 'Replacing a host key should restore VALID status.');
    assertSameValue($analysis['fingerprint'], $record['approved_fingerprint'], 'Approved fingerprint should update after replacement.');

    echo "SSH host trust tests OK.\n";
} finally {
    if (is_dir($tmp)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($tmp);
    }
}
