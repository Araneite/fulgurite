<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = rtrim(sys_get_temp_dir(), '/\\') . '/fulgurite-snapshot-search-index-' . bin2hex(random_bytes(4));
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
require_once $root . '/src/DatabaseMigrations.php';

function failSnapshotSearchIndex(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function assertTrueSnapshotSearchIndex(bool $condition, string $message): void
{
    if (!$condition) {
        failSnapshotSearchIndex($message);
    }
}

function assertSameSnapshotSearchIndex(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        failSnapshotSearchIndex(
            $message
            . ' (expected=' . var_export($expected, true)
            . ', actual=' . var_export($actual, true) . ')'
        );
    }
}

function removeTreeSnapshotSearchIndex(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($path);
}

try {
    DatabaseMigrations::migrateConfiguredDatabases();

    $indexDb = Database::getIndexInstance();
    $nowExpr = Database::nowExpression();

    $indexDb->prepare("
        INSERT INTO snapshot_search_index_status (repo_id, snapshot_id, indexed_at, file_count, scope)
        VALUES (?, ?, {$nowExpr}, ?, ?)
    ")->execute([1, 'snap-a', 4, 'full']);
    $indexDb->prepare("
        INSERT INTO snapshot_search_index_status (repo_id, snapshot_id, indexed_at, file_count, scope)
        VALUES (?, ?, {$nowExpr}, ?, ?)
    ")->execute([1, 'snap-b', 1, 'full']);

    $insert = $indexDb->prepare("
        INSERT INTO snapshot_file_index (repo_id, snapshot_id, path, name, name_lc, type, size, mtime, indexed_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, {$nowExpr})
    ");

    $insert->execute([1, 'snap-a', '/docs', 'docs', 'docs', 'dir', 0, null]);
    $insert->execute([1, 'snap-a', '/docs.txt', 'docs.txt', 'docs.txt', 'file', 12, null]);
    $insert->execute([1, 'snap-a', '/documents/report-final.txt', 'report-final.txt', 'report-final.txt', 'file', 128, null]);
    $insert->execute([1, 'snap-a', '/images/portfolio.png', 'portfolio.png', 'portfolio.png', 'file', 64, null]);
    $insert->execute([1, 'snap-b', '/docs-other.txt', 'docs-other.txt', 'docs-other.txt', 'file', 8, null]);

    $resultsPrefix = SnapshotSearchIndex::search(1, 'snap-a', 'doc', 10);
    assertTrueSnapshotSearchIndex(is_array($resultsPrefix), 'search() should return an array for indexed snapshots.');
    assertSameSnapshotSearchIndex('/docs', $resultsPrefix[0]['path'] ?? null, 'Directories should stay ordered before files.');
    assertTrueSnapshotSearchIndex(
        !in_array('/docs-other.txt', array_column($resultsPrefix, 'path'), true),
        'search() should remain scoped to the requested snapshot.'
    );

    $resultsSubstring = SnapshotSearchIndex::search(1, 'snap-a', 'port', 10);
    assertTrueSnapshotSearchIndex(
        in_array('/documents/report-final.txt', array_column($resultsSubstring, 'path'), true),
        'Substring fallback should still find existing business matches.'
    );

    echo "SnapshotSearchIndex tests OK.\n";
} finally {
    removeTreeSnapshotSearchIndex($tmp);
}

