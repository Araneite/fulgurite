<?php

declare(strict_types=1);

/**
 * Verify ThemePackage::fetchUrl() uses OutboundHttpClient with IP pinning.
 *
 * These tests cover validation rejections (without real network connection):
 *  - Empty URL
 *  - Scheme HTTP (non-HTTPS)
 *  - localhost target
 *  - loopback IP target (127.0.0.1)
 *  - private IP target (RFC 1918)
 *  - non-standard port (not 443)
 *
 * They confirm validation is performed via PublicOutboundUrlValidator
 * and that the old file_get_contents stack is no longer used.
 */

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-theme-fetch-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

putenv('DB_PATH=' . $tmp . '/app.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/search.db');
$_ENV['DB_PATH'] = $tmp . '/app.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/search.db';

require_once $root . '/src/bootstrap.php';

function failThemeFetchTest(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertThemeFetchFails(array $result, string $description): void {
    if (!empty($result['ok'])) {
        failThemeFetchTest("Expected fetchUrl() to fail for: $description");
    }
    if (empty($result['errors']) || !is_array($result['errors'])) {
        failThemeFetchTest("Expected non-empty errors array for: $description");
    }
}

function cleanupThemeFetchTestDir(string $path): void {
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $item = $path . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($item)) {
            cleanupThemeFetchTestDir($item);
            @rmdir($item);
        } else {
            @unlink($item);
        }
    }
}

try {
    // Empty URL
    $result = ThemePackage::fetchUrl('');
    assertThemeFetchFails($result, 'empty URL');

    // HTTP scheme (PublicOutboundUrlValidator accepts only https)
    $result = ThemePackage::fetchUrl('http://example.com/theme.zip');
    assertThemeFetchFails($result, 'HTTP scheme');

    // localhost target
    $result = ThemePackage::fetchUrl('https://localhost/theme.zip');
    assertThemeFetchFails($result, 'localhost target');

    // .localhost target (subdomain)
    $result = ThemePackage::fetchUrl('https://attacker.localhost/theme.zip');
    assertThemeFetchFails($result, '.localhost subdomain target');

    // Loopback IP target — 127.0.0.1 must be rejected as non-public
    $result = ThemePackage::fetchUrl('https://127.0.0.1/theme.zip');
    assertThemeFetchFails($result, 'loopback IP 127.0.0.1');

    // Private RFC 1918 IP target
    $result = ThemePackage::fetchUrl('https://192.168.1.100/theme.zip');
    assertThemeFetchFails($result, 'private IP 192.168.1.100');

    // Non-standard port (PublicOutboundUrlValidator rejects any port != 443)
    $result = ThemePackage::fetchUrl('https://example.com:8443/theme.zip');
    assertThemeFetchFails($result, 'non-standard port 8443');

    // URL with embedded createdentials
    $result = ThemePackage::fetchUrl('https://user:pass@example.com/theme.zip');
    assertThemeFetchFails($result, 'embedded credentials');

    echo "theme_package_url_fetch tests OK.\n";
} finally {
    cleanupThemeFetchTestDir($tmp);
    @rmdir($tmp);
}
