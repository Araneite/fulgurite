<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);
define('APPCONFIG_PRELOAD_SETTINGS', true);

$root = dirname(__DIR__);
$tmp = rtrim(sys_get_temp_dir(), '/\\') . '/fulgurite-app-config-cache-' . bin2hex(random_bytes(4));
$dataDir = $tmp . '/data';
mkdir($dataDir, 0700, true);

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $dataDir . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $dataDir . '/fulgurite-search.db');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $dataDir . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $dataDir . '/fulgurite-search.db';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $dataDir . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $dataDir . '/fulgurite-search.db';

require_once $root . '/src/bootstrap.php';

$db = Database::getInstance();
$db->exec("
    CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL,
        updated_at TEXT DEFAULT (datetime('now'))
    )
");

function appConfigFail(string $message): void
{
    fwrite(STDERR, "FAIL: $message\n");
    exit(1);
}

function appConfigAssertSame(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        appConfigFail($message . " (expected={$expected}, actual={$actual})");
    }
}

function appConfigRemoveTree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($path);
}

try {
    Database::setSetting('interface_app_name', 'CachePrimed');
    Database::setSetting('interface_app_subtitle', 'OldSubtitle');

    // First read primes cache and preload.
    appConfigAssertSame('CachePrimed', AppConfig::get('interface_app_name', 'fallback'), 'AppConfig should read stored value.');

    // setSetting() must invalidate the in-memory AppConfig cache in-process.
    Database::setSetting('interface_app_subtitle', 'NewSubtitle');
    appConfigAssertSame('NewSubtitle', AppConfig::get('interface_app_subtitle', 'fallback'), 'setSetting should refresh AppConfig cache.');

    // Preload keeps sibling keys available even if settings table becomes unreadable later.
    $db->exec('DROP TABLE settings');
    appConfigAssertSame('CachePrimed', AppConfig::get('interface_app_name', 'fallback'), 'Memoized key should stay available after table drop.');
    appConfigAssertSame('NewSubtitle', AppConfig::get('interface_app_subtitle', 'fallback'), 'Preloaded key should stay available after table drop.');

    echo "AppConfig cache/preload tests OK.\n";
} finally {
    appConfigRemoveTree($tmp);
}
