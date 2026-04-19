<?php
declare(strict_types=1);

final class DatabaseMigrations
{
    private const APP_MIGRATIONS = [
        [
            'version' => '202604160001_app_legacy_schema',
            'checksum' => 'legacy-app-schema-v1',
        ],
    ];

    private const INDEX_MIGRATIONS = [
        [
            'version' => '202604160002_index_legacy_schema',
            'checksum' => 'legacy-index-schema-v1',
        ],
        [
            'version' => '202604170001_index_snapshot_search_optimization_v1',
            'checksum' => 'index-snapshot-search-optimization-v1',
        ],
    ];

    private const CROSS_DB_MIGRATIONS = [
        [
            'version' => '202604160003_search_tables_to_index_v1',
            'checksum' => 'legacy-search-table-move-v1',
        ],
    ];

    public static function migrateConfiguredDatabases(): void
    {
        $mainDb = Database::getInstance();
        self::migrateMainDatabase($mainDb);

        $indexDb = Database::getIndexInstance();
        self::migrateIndexDatabase($indexDb);

        if (Database::searchDbPath() !== Database::dbPath()) {
            self::migrateCrossDatabaseSteps($mainDb, $indexDb);
        }
    }

    public static function migrateMainDatabase(PDO $db): void
    {
        self::ensureMigrationsTable($db);

        foreach (self::APP_MIGRATIONS as $migration) {
            $version = (string) $migration['version'];
            self::runMigration($db, $version, (string) $migration['checksum'], static function () use ($db): void {
                Database::runLegacyAppSchemaMigration($db);
            });
        }
    }

    public static function migrateIndexDatabase(PDO $db): void
    {
        self::ensureMigrationsTable($db);

        foreach (self::INDEX_MIGRATIONS as $migration) {
            $version = (string) $migration['version'];
            self::runMigration($db, $version, (string) $migration['checksum'], static function () use ($db, $version): void {
                if ($version === '202604160002_index_legacy_schema') {
                    Database::runLegacyIndexSchemaMigration($db);
                    return;
                }
                if ($version === '202604170001_index_snapshot_search_optimization_v1') {
                    Database::runSnapshotSearchOptimizationMigration($db);
                }
            });
        }
    }

    public static function migrateCrossDatabaseSteps(PDO $mainDb, PDO $indexDb): void
    {
        self::ensureMigrationsTable($mainDb);

        foreach (self::CROSS_DB_MIGRATIONS as $migration) {
            $version = (string) $migration['version'];
            self::runMigration($mainDb, $version, (string) $migration['checksum'], static function () use ($mainDb, $indexDb): void {
                Database::runLegacySearchTableMigration($mainDb, $indexDb);
            });
        }
    }

    private static function ensureMigrationsTable(PDO $db): void
    {
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = match ($driver) {
            'mysql' => "CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(191) PRIMARY KEY,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                checksum VARCHAR(128) NULL
            )",
            'pgsql' => "CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(191) PRIMARY KEY,
                applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                checksum VARCHAR(128) NULL
            )",
            default => "CREATE TABLE IF NOT EXISTS schema_migrations (
                version TEXT PRIMARY KEY,
                applied_at TEXT NOT NULL DEFAULT (datetime('now')),
                checksum TEXT NULL
            )",
        };

        $db->exec($sql);
    }

    private static function runMigration(PDO $db, string $version, string $checksum, callable $callback): void
    {
        $appliedChecksum = self::appliedChecksum($db, $version);
        if ($appliedChecksum !== null) {
            if ($appliedChecksum !== '' && $appliedChecksum !== $checksum) {
                throw new RuntimeException(
                    sprintf(
                        'Checksum mismatch for migration %s (db=%s, code=%s)',
                        $version,
                        $appliedChecksum,
                        $checksum
                    )
                );
            }
            return;
        }

        $startedTransaction = false;
        if (!self::isLikelyAutoCommitDdlDriver($db) && !$db->inTransaction()) {
            if (!$db->beginTransaction()) {
                throw new RuntimeException(sprintf('Unable to start migration transaction for %s', $version));
            }
            $startedTransaction = true;
        }

        try {
            $callback();
            self::recordApplied($db, $version, $checksum);
            if ($startedTransaction && $db->inTransaction()) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw new RuntimeException(
                sprintf('Database migration failed (%s): %s', $version, $e->getMessage()),
                0,
                $e
            );
        }
    }

    private static function appliedChecksum(PDO $db, string $version): ?string
    {
        $stmt = $db->prepare('SELECT checksum FROM schema_migrations WHERE version = ? LIMIT 1');
        $stmt->execute([$version]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            return null;
        }

        return $value === null ? '' : (string) $value;
    }

    private static function isLikelyAutoCommitDdlDriver(PDO $db): bool
    {
        return (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    private static function recordApplied(PDO $db, string $version, string $checksum): void
    {
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = match ($driver) {
            'mysql', 'pgsql' => 'NOW()',
            default => "datetime('now')",
        };

        $stmt = $db->prepare("INSERT INTO schema_migrations(version, applied_at, checksum) VALUES(?, {$nowExpr}, ?)");
        $stmt->execute([$version, $checksum]);
    }
}

