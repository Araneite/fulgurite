<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = rtrim(sys_get_temp_dir(), '/\\') . '/fulgurite-fs-scope-test-' . bin2hex(random_bytes(4));
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

function fsScopeAssertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

function fsScopeExpectFailure(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }

    fwrite(STDERR, $message . "\n");
    exit(1);
}

function fsScopeRemoveTree(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        @unlink($path);
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isLink() || $item->isFile()) {
            @unlink($item->getPathname());
        } else {
            @rmdir($item->getPathname());
        }
    }

    @rmdir($path);
}

try {
    $allowedDir = $dataDir . '/repos/repo-a';
    mkdir($allowedDir, 0700, true);
    file_put_contents($allowedDir . '/config.txt', "ok\n");

    $resolvedAllowed = FilesystemScopeGuard::assertPathAllowed($allowedDir, 'delete', true);
    fsScopeAssertTrue($resolvedAllowed === str_replace('\\', '/', $allowedDir), 'Allowed app-data path should resolve inside the scoped data root.');

    $creatable = FilesystemScopeGuard::assertPathCreatable($dataDir . '/repos/repo-b', 'write');
    fsScopeAssertTrue(str_ends_with($creatable, '/repos/repo-b'), 'Creatable app-data path should be accepted.');

    fsScopeExpectFailure(
        static fn() => FilesystemScopeGuard::assertPathCreatable($dataDir . '/../escape', 'write'),
        'Path traversal should be rejected.'
    );

    $unsafeTempFile = tempnam(sys_get_temp_dir(), 'unsafe_');
    fsScopeAssertTrue(is_string($unsafeTempFile) && is_file($unsafeTempFile), 'Unsafe temp file should exist for the denial test.');
    fsScopeExpectFailure(
        static fn() => FilesystemScopeGuard::assertPathAllowed((string) $unsafeTempFile, 'delete', true),
        'Unprefixed temp files should be rejected outside Fulgurite-managed prefixes.'
    );

    $secretPath = Restic::writeTempSecretFile('secret-value', 'restic_pass_');
    fsScopeAssertTrue(is_file($secretPath), 'Restic temp secret file should be created inside an allowed temp scope.');
    Restic::deleteTempSecretFile($secretPath);
    fsScopeAssertTrue(!file_exists($secretPath), 'Restic temp secret file should be deleted through the scoped helper.');

    if (PHP_OS_FAMILY !== 'Windows' && function_exists('symlink')) {
        $linkPath = $dataDir . '/repo-link';
        if (@symlink($allowedDir, $linkPath)) {
            fsScopeExpectFailure(
                static fn() => FilesystemScopeGuard::assertPathAllowed($linkPath, 'read', true),
                'Symlinked paths should be rejected.'
            );
        }
    }

    echo "Filesystem scope guard tests OK.\n";
} finally {
    if (isset($unsafeTempFile) && is_string($unsafeTempFile)) {
        @unlink($unsafeTempFile);
    }
    fsScopeRemoveTree($tmp);
}

// ── assertValidBackupSourcePath ───────────────────────────────────────────────

// Valid local backup source paths
$validLocalPaths = [
    '/etc',
    '/home/user',
    '/var/www/html',
    '/mnt/backup',
];
foreach ($validLocalPaths as $p) {
    $normalized = FilesystemScopeGuard::assertValidBackupSourcePath($p, true);
    fsScopeAssertTrue($normalized !== '', 'Valid local backup source path should be accepted: ' . $p);
}

// Valid remote backup source paths (virtual FS roots are allowed on remote)
$validRemotePaths = [
    '/proc/1',
    '/sys/block',
    '/dev/sda',
    '/etc',
    '/home',
];
foreach ($validRemotePaths as $p) {
    $normalized = FilesystemScopeGuard::assertValidBackupSourcePath($p, false);
    fsScopeAssertTrue($normalized !== '', 'Valid remote backup source path should be accepted: ' . $p);
}

// Filesystem root must be rejected for both local and remote
fsScopeExpectFailure(
    static fn() => FilesystemScopeGuard::assertValidBackupSourcePath('/', true),
    'Filesystem root / should be rejected as local backup source.'
);
fsScopeExpectFailure(
    static fn() => FilesystemScopeGuard::assertValidBackupSourcePath('/', false),
    'Filesystem root / should be rejected as remote backup source.'
);

// Traversal must be rejected
fsScopeExpectFailure(
    static fn() => FilesystemScopeGuard::assertValidBackupSourcePath('/home/../etc', true),
    'Path traversal must be rejected for local backup source.'
);
fsScopeExpectFailure(
    static fn() => FilesystemScopeGuard::assertValidBackupSourcePath('/home/../etc', false),
    'Path traversal must be rejected for remote backup source.'
);

// Control characters must be rejected
fsScopeExpectFailure(
    static fn() => FilesystemScopeGuard::assertValidBackupSourcePath("/home/user\x00evil", true),
    'Null byte in path must be rejected as local backup source.'
);

// Relative paths must be rejected
fsScopeExpectFailure(
    static fn() => FilesystemScopeGuard::assertValidBackupSourcePath('relative/path', true),
    'Relative path must be rejected as local backup source.'
);

// Virtual FS roots must be rejected for LOCAL backups
foreach (['/proc', '/sys', '/dev', '/proc/1', '/sys/block', '/dev/sda'] as $vpath) {
    fsScopeExpectFailure(
        fn() => FilesystemScopeGuard::assertValidBackupSourcePath($vpath, true),
        'Virtual FS path "' . $vpath . '" must be rejected for local backup source.'
    );
}

echo "assertValidBackupSourcePath tests OK.\n";
