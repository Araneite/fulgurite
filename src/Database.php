<?php

class Database {
    private static ?PDO $instance = null;
    private static ?PDO $indexInstance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = self::open(self::dbPath());
        }

        return self::$instance;
    }

    public static function getIndexInstance(): PDO {
        $searchDbPath = self::searchDbPath();
        if ($searchDbPath === self::dbPath()) {
            return self::getInstance();
        }

        if (self::$indexInstance === null) {
            self::$indexInstance = self::open($searchDbPath);
        }

        return self::$indexInstance;
    }

    /**
     * Point of compatibility: migration versioned of schema historical applicatif.
     * Do not call from normal web runtime.
     */
    public static function runLegacyAppSchemaMigration(PDO $db): void {
        self::initAppSchema($db);
    }

    /**
     * Point of compatibility: migration versioned of schema historical index.
     * Do not call from normal web runtime.
     */
    public static function runLegacyIndexSchemaMigration(PDO $db): void {
        self::initIndexSchema($db);
    }

    /**
     * Point of compatibility: migration versioned of optimizations of search snapshot.
     * Do not call from normal web runtime.
     */
    public static function runSnapshotSearchOptimizationMigration(PDO $db): void {
        self::optimizeSnapshotSearchSchema($db);
    }

    /**
     * Point of compatibility: migration versioned of tables of search legacy.
     * Do not call from normal web runtime.
     */
    public static function runLegacySearchTableMigration(PDO $mainDb, PDO $indexDb): void {
        self::migrateLegacySearchTables($mainDb, $indexDb);
    }

    private static function cfg(string $key, string $constant, string $default = ''): string {
        if (function_exists('fulguriteEnv')) {
            $value = fulguriteEnv($key, '');
            if ($value !== '') {
                return $value;
            }
        }

        return defined($constant) ? (string) constant($constant) : $default;
    }

    public static function dbPath(): string {
        return self::cfg('DB_PATH', 'DB_PATH', dirname(__DIR__) . '/data/fulgurite.db');
    }

    public static function searchDbPath(): string {
        return self::cfg('SEARCH_DB_PATH', 'SEARCH_DB_PATH', dirname(__DIR__) . '/data/fulgurite-search.db');
    }

    private static function driver(): string {
        return self::cfg('DB_DRIVER', 'DB_DRIVER', 'sqlite');
    }

    private static function nowExpr(): string {
        return match(self::driver()) {
            'mysql', 'pgsql' => 'NOW()',
            default          => "datetime('now')",
        };
    }

    public static function nowExpression(): string {
        return self::nowExpr();
    }

    /**
     * Adapte the DDL SQLite for MySQL or PostgreSQL.
     * the CREATE TABLE, INDEX, ALTER TABLE passent by this methode before exec().     */
    private static function adaptDdl(string $sql): string {
        $driver = self::driver();
        if ($driver === 'sqlite') {
            return $sql;
        }

        // AUTO INCREMENT
        if ($driver === 'mysql') {
            $sql = preg_replace(
                '/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i',
                'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                $sql
            );
            // TEXT PRIMARY KEY (table settings) → VARCHAR
            $sql = preg_replace('/(?<=\s)TEXT\s+PRIMARY\s+KEY\b/i', 'VARCHAR(255) PRIMARY KEY', $sql);
            // Defauts datetime
            $sql = str_replace("DEFAULT (datetime('now'))", 'DEFAULT CURRENT_TIMESTAMP', $sql);
            // repo_id INTEGER PRIMARY KEY (no AUTOINCREMENT)
            $sql = preg_replace('/\bINTEGER\s+PRIMARY\s+KEY\b(?!\s+AUTOINCREMENT)/i', 'INT UNSIGNED NOT NULL PRIMARY KEY', $sql);
        }

        if ($driver === 'pgsql') {
            $sql = preg_replace(
                '/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i',
                'SERIAL PRIMARY KEY',
                $sql
            );
            $sql = preg_replace('/\bINTEGER\s+PRIMARY\s+KEY\b(?!\s+AUTOINCREMENT)/i', 'INTEGER PRIMARY KEY', $sql);
            $sql = str_replace("DEFAULT (datetime('now'))", 'DEFAULT NOW()', $sql);
        }

        // Remove SQLite PRAGMA for all non-sqlite drivers
        $sql = preg_replace('/^\s*PRAGMA\s+[^\n;]+[;\n]/im', '', $sql);

        return $sql;
    }

    public static function adaptDdlPublic(string $sql): string {
        return self::adaptDdl($sql);
    }

    private static function open(string $path): PDO {
        $driver = self::driver();

        if ($driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::cfg('DB_HOST', 'DB_HOST', 'localhost'),
                self::cfg('DB_PORT', 'DB_PORT', '3306'),
                self::cfg('DB_NAME', 'DB_NAME', 'fulgurite'),
                self::cfg('DB_CHARSET', 'DB_CHARSET', 'utf8mb4')
            );
            $db = new ProfilingPDO($dsn, self::cfg('DB_USER', 'DB_USER', ''), self::cfg('DB_PASS', 'DB_PASS', ''));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            return $db;
        }

        if ($driver === 'pgsql') {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                self::cfg('DB_HOST', 'DB_HOST', 'localhost'),
                self::cfg('DB_PORT', 'DB_PORT', '5432'),
                self::cfg('DB_NAME', 'DB_NAME', 'fulgurite')
            );
            $db = new ProfilingPDO($dsn, self::cfg('DB_USER', 'DB_USER', ''), self::cfg('DB_PASS', 'DB_PASS', ''));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec("SET client_encoding TO 'UTF8'");
            return $db;
        }

        // SQLite (default)
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $db = new ProfilingPDO('sqlite:' . $path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA busy_timeout = 5000');
        $db->exec('PRAGMA journal_mode = WAL');
        $db->exec('PRAGMA synchronous = NORMAL');

        return $db;
    }

    private static function initAppSchema(PDO $db): void {
        $driver = self::driver();
        $db->exec(self::adaptDdl("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'viewer',
                first_name TEXT,
                last_name TEXT,
                email TEXT,
                phone TEXT,
                job_title TEXT,
                preferred_locale TEXT NOT NULL DEFAULT 'fr',
                preferred_timezone TEXT,
                preferred_start_page TEXT NOT NULL DEFAULT 'dashboard',
                preferred_theme TEXT NOT NULL DEFAULT 'dark',
                permissions_json TEXT NOT NULL DEFAULT '{}',
                repo_scope_mode TEXT NOT NULL DEFAULT 'all',
                repo_scope_json TEXT NOT NULL DEFAULT '[]',
                host_scope_mode TEXT NOT NULL DEFAULT 'all',
                host_scope_json TEXT NOT NULL DEFAULT '[]',
                force_actions_json TEXT NOT NULL DEFAULT '[]',
                suspended_until TEXT,
                suspension_reason TEXT,
                account_expires_at TEXT,
                admin_notes TEXT,
                created_by INTEGER,
                totp_secret TEXT,
                totp_secret_ref TEXT,
                totp_enabled INTEGER DEFAULT 0,
                primary_second_factor TEXT NOT NULL DEFAULT '',
                password_set_at TEXT DEFAULT (datetime('now')),
                created_at TEXT DEFAULT (datetime('now')),
                last_login TEXT
            );
            CREATE TABLE IF NOT EXISTS repos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                path TEXT NOT NULL,
                password_file TEXT,
                password_ref TEXT,
                description TEXT,
                alert_hours INTEGER DEFAULT 25,
                notify_email INTEGER DEFAULT 1,
                password_source TEXT NOT NULL DEFAULT 'agent',
                infisical_secret_name TEXT,
                notification_policy TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                username TEXT,
                action TEXT NOT NULL,
                details TEXT,
                ip TEXT,
                user_agent TEXT,
                severity TEXT NOT NULL DEFAULT 'info',
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS ssh_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                host TEXT NOT NULL,
                user TEXT NOT NULL DEFAULT 'root',
                port INTEGER NOT NULL DEFAULT 22,
                private_key_file TEXT NOT NULL,
                public_key TEXT,
                description TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS ssh_host_trust (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                host TEXT NOT NULL,
                port INTEGER NOT NULL DEFAULT 22,
                approved_key_ref TEXT,
                detected_key_ref TEXT,
                approved_key_type TEXT,
                approved_fingerprint TEXT,
                detected_key_type TEXT,
                detected_fingerprint TEXT,
                previous_fingerprint TEXT,
                status TEXT NOT NULL DEFAULT 'HOST_KEY_UNKNOWN',
                last_context TEXT,
                last_seen_at TEXT,
                approved_by INTEGER,
                approved_at TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now')),
                UNIQUE(host, port)
            );
            CREATE TABLE IF NOT EXISTS hook_scripts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                execution_scope TEXT NOT NULL DEFAULT 'both',
                status TEXT NOT NULL DEFAULT 'active',
                content_path TEXT NOT NULL,
                checksum TEXT NOT NULL,
                instruction_count INTEGER NOT NULL DEFAULT 0,
                created_by INTEGER,
                updated_by INTEGER,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS alert_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                repo_id INTEGER NOT NULL,
                repo_name TEXT NOT NULL,
                alert_type TEXT NOT NULL,
                message TEXT,
                notified INTEGER DEFAULT 0,
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS notification_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                context_type TEXT NOT NULL,
                context_id INTEGER,
                context_name TEXT,
                profile_key TEXT NOT NULL,
                event_key TEXT NOT NULL,
                channel TEXT NOT NULL,
                success INTEGER NOT NULL DEFAULT 0,
                output TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS app_notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                profile_key TEXT NOT NULL,
                event_key TEXT NOT NULL,
                context_type TEXT NOT NULL,
                context_id INTEGER,
                context_name TEXT,
                title TEXT NOT NULL,
                body TEXT NOT NULL,
                severity TEXT NOT NULL DEFAULT 'info',
                link_url TEXT,
                browser_delivery INTEGER NOT NULL DEFAULT 0,
                is_read INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT (datetime('now')),
                read_at TEXT
            );
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT,
                updated_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS restore_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                repo_id INTEGER NOT NULL,
                repo_name TEXT NOT NULL,
                snapshot_id TEXT NOT NULL,
                mode TEXT NOT NULL DEFAULT 'local',
                target TEXT,
                include_path TEXT,
                remote_host TEXT,
                remote_user TEXT,
                remote_path TEXT,
                status TEXT NOT NULL DEFAULT 'pending',
                output TEXT,
                started_by TEXT,
                started_at TEXT DEFAULT (datetime('now')),
                finished_at TEXT
            );
            CREATE TABLE IF NOT EXISTS repo_stats_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                repo_id INTEGER NOT NULL,
                snapshot_count INTEGER DEFAULT 0,
                total_size INTEGER DEFAULT 0,
                total_file_count INTEGER DEFAULT 0,
                recorded_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS repo_runtime_status (
                repo_id INTEGER PRIMARY KEY,
                repo_name TEXT NOT NULL,
                snapshot_count INTEGER DEFAULT 0,
                last_snapshot_time TEXT,
                hours_ago REAL,
                status TEXT NOT NULL DEFAULT 'unknown',
                updated_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS disk_space_checks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                subject_key TEXT NOT NULL,
                scope TEXT NOT NULL,
                context_type TEXT NOT NULL,
                context_id INTEGER,
                context_name TEXT NOT NULL,
                host_id INTEGER,
                host_name TEXT,
                path TEXT NOT NULL,
                probe_path TEXT,
                total_bytes INTEGER DEFAULT 0,
                free_bytes INTEGER DEFAULT 0,
                used_bytes INTEGER DEFAULT 0,
                used_percent REAL,
                required_bytes INTEGER DEFAULT 0,
                severity TEXT NOT NULL DEFAULT 'unknown',
                status TEXT NOT NULL DEFAULT 'unknown',
                message TEXT,
                checked_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS disk_space_runtime_status (
                subject_key TEXT PRIMARY KEY,
                scope TEXT NOT NULL,
                context_type TEXT NOT NULL,
                context_id INTEGER,
                context_name TEXT NOT NULL,
                host_id INTEGER,
                host_name TEXT,
                path TEXT NOT NULL,
                probe_path TEXT,
                total_bytes INTEGER DEFAULT 0,
                free_bytes INTEGER DEFAULT 0,
                used_bytes INTEGER DEFAULT 0,
                used_percent REAL,
                required_bytes INTEGER DEFAULT 0,
                severity TEXT NOT NULL DEFAULT 'unknown',
                status TEXT NOT NULL DEFAULT 'unknown',
                message TEXT,
                checked_at TEXT DEFAULT (datetime('now')),
                last_change_at TEXT DEFAULT (datetime('now')),
                last_notified_event TEXT,
                last_notified_at TEXT
            );
            CREATE TABLE IF NOT EXISTS copy_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                source_repo_id INTEGER NOT NULL,
                dest_path TEXT NOT NULL,
                dest_password_file TEXT,
                dest_password_ref TEXT,
                dest_password_source TEXT NOT NULL DEFAULT 'agent',
                dest_infisical_secret_name TEXT,
                description TEXT,
                schedule_enabled INTEGER DEFAULT 0,
                schedule_hour INTEGER DEFAULT 2,
                schedule_days TEXT DEFAULT '1',
                notification_policy TEXT,
                retry_policy TEXT,
                last_run TEXT,
                last_status TEXT,
                last_output TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS hosts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                hostname TEXT NOT NULL,
                port INTEGER NOT NULL DEFAULT 22,
                user TEXT NOT NULL DEFAULT 'root',
                ssh_key_id INTEGER,
                restore_managed_root TEXT,
                sudo_password_file TEXT,
                sudo_password_ref TEXT,
                description TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS app_secrets (
                ref TEXT PRIMARY KEY,
                provider TEXT NOT NULL,
                algorithm TEXT NOT NULL,
                nonce TEXT NOT NULL,
                ciphertext TEXT NOT NULL,
                metadata_json TEXT NOT NULL DEFAULT '{}',
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS backup_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                repo_id INTEGER NOT NULL,
                source_paths TEXT NOT NULL DEFAULT '[]',
                tags TEXT NOT NULL DEFAULT '[]',
                excludes TEXT NOT NULL DEFAULT '[]',
                description TEXT,
                schedule_enabled INTEGER NOT NULL DEFAULT 0,
                schedule_hour INTEGER NOT NULL DEFAULT 2,
                schedule_days TEXT NOT NULL DEFAULT '1',
                notify_on_failure INTEGER NOT NULL DEFAULT 1,
                notification_policy TEXT,
                retry_policy TEXT,
                host_id INTEGER DEFAULT NULL,
                pre_hook_script_id INTEGER,
                post_hook_script_id INTEGER,
                last_run TEXT,
                last_status TEXT,
                last_output TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS retention_policies (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                repo_id INTEGER NOT NULL UNIQUE,
                keep_last INTEGER DEFAULT 7,
                keep_daily INTEGER DEFAULT 14,
                keep_weekly INTEGER DEFAULT 4,
                keep_monthly INTEGER DEFAULT 3,
                keep_yearly INTEGER DEFAULT 1,
                auto_apply INTEGER DEFAULT 0,
                updated_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS cron_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_type TEXT NOT NULL,
                job_id INTEGER,
                status TEXT NOT NULL,
                output TEXT,
                ran_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip TEXT NOT NULL,
                username TEXT,
                success INTEGER DEFAULT 0,
                attempted_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS active_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                session_token TEXT UNIQUE NOT NULL,
                ip TEXT,
                user_agent TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                last_activity TEXT DEFAULT (datetime('now')),
                expires_at TEXT
            );
            CREATE TABLE IF NOT EXISTS job_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                unique_key TEXT,
                payload_json TEXT NOT NULL DEFAULT '{}',
                status TEXT NOT NULL DEFAULT 'queued',
                priority INTEGER NOT NULL DEFAULT 100,
                attempts INTEGER NOT NULL DEFAULT 0,
                available_at TEXT DEFAULT (datetime('now')),
                claimed_at TEXT,
                started_at TEXT,
                finished_at TEXT,
                last_error TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS job_log_runs (
                run_id VARCHAR(64) PRIMARY KEY,
                type VARCHAR(32) NOT NULL,
                user_id INTEGER NOT NULL,
                permission_required VARCHAR(80) NOT NULL,
                created_at TEXT DEFAULT (datetime('now')),
                expires_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS user_invitations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                email TEXT,
                first_name TEXT,
                last_name TEXT,
                phone TEXT,
                job_title TEXT,
                role TEXT NOT NULL DEFAULT 'viewer',
                preferred_locale TEXT NOT NULL DEFAULT 'fr',
                preferred_timezone TEXT,
                preferred_start_page TEXT NOT NULL DEFAULT 'dashboard',
                permissions_json TEXT NOT NULL DEFAULT '{}',
                repo_scope_mode TEXT NOT NULL DEFAULT 'all',
                repo_scope_json TEXT NOT NULL DEFAULT '[]',
                host_scope_mode TEXT NOT NULL DEFAULT 'all',
                host_scope_json TEXT NOT NULL DEFAULT '[]',
                force_actions_json TEXT NOT NULL DEFAULT '[]',
                admin_notes TEXT,
                invited_by INTEGER,
                token_hash TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                created_at TEXT DEFAULT (datetime('now')),
                accepted_at TEXT,
                revoked_at TEXT
            );
        "));

        try { $db->exec(self::adaptDdl("ALTER TABLE repos ADD COLUMN alert_hours INTEGER DEFAULT 25")); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repos ADD COLUMN notify_email INTEGER DEFAULT 1"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repos ADD COLUMN notification_policy TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN totp_secret TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN totp_secret_ref TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN totp_enabled INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN first_name TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN last_name TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN email TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN phone TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN job_title TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN preferred_locale TEXT NOT NULL DEFAULT 'fr'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN preferred_timezone TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN preferred_start_page TEXT NOT NULL DEFAULT 'dashboard'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN preferred_theme TEXT NOT NULL DEFAULT 'dark'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN permissions_json TEXT NOT NULL DEFAULT '{}'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN repo_scope_mode TEXT NOT NULL DEFAULT 'all'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN repo_scope_json TEXT NOT NULL DEFAULT '[]'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN host_scope_mode TEXT NOT NULL DEFAULT 'all'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN host_scope_json TEXT NOT NULL DEFAULT '[]'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN force_actions_json TEXT NOT NULL DEFAULT '[]'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN suspended_until TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN suspension_reason TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN account_expires_at TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN admin_notes TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN created_by INTEGER"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN password_set_at TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE activity_logs ADD COLUMN user_agent TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE activity_logs ADD COLUMN severity TEXT NOT NULL DEFAULT 'info'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN notify_on_failure INTEGER NOT NULL DEFAULT 1"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN notification_policy TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN retry_policy TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE copy_jobs ADD COLUMN notification_policy TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE copy_jobs ADD COLUMN retry_policy TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN host_id INTEGER DEFAULT NULL"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN remote_repo_path TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN hostname_override TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN retention_enabled INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN retention_keep_last INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN retention_keep_daily INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN retention_keep_weekly INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN retention_keep_monthly INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN retention_keep_yearly INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN retention_prune INTEGER NOT NULL DEFAULT 1"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN pre_hook TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN post_hook TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN hook_env TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN pre_hook_script_id INTEGER"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE backup_jobs ADD COLUMN post_hook_script_id INTEGER"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE hosts ADD COLUMN restore_managed_root TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repos ADD COLUMN password_source TEXT NOT NULL DEFAULT 'agent'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repos ADD COLUMN infisical_secret_name TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repos ADD COLUMN password_ref TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repos ADD COLUMN snapshot_refresh_enabled INTEGER NOT NULL DEFAULT 1"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repos ADD COLUMN snapshot_refresh_interval_minutes INTEGER DEFAULT NULL"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repos ADD COLUMN last_snapshot_refreshed_at TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE copy_jobs ADD COLUMN dest_password_source TEXT NOT NULL DEFAULT 'agent'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE copy_jobs ADD COLUMN dest_infisical_secret_name TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE copy_jobs ADD COLUMN dest_password_ref TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE hosts ADD COLUMN sudo_password_ref TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE hosts ADD COLUMN restore_original_enabled INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS ssh_host_trust (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                host TEXT NOT NULL,
                port INTEGER NOT NULL DEFAULT 22,
                approved_key_ref TEXT,
                detected_key_ref TEXT,
                approved_key_type TEXT,
                approved_fingerprint TEXT,
                detected_key_type TEXT,
                detected_fingerprint TEXT,
                previous_fingerprint TEXT,
                status TEXT NOT NULL DEFAULT 'HOST_KEY_UNKNOWN',
                last_context TEXT,
                last_seen_at TEXT,
                approved_by INTEGER,
                approved_at TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now')),
                UNIQUE(host, port)
            )"));
        } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN approved_key_ref TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN detected_key_ref TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN approved_key_type TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN approved_fingerprint TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN detected_key_type TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN detected_fingerprint TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN previous_fingerprint TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN status TEXT NOT NULL DEFAULT 'HOST_KEY_UNKNOWN'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN last_context TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN last_seen_at TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN approved_by INTEGER"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN approved_at TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN created_at TEXT DEFAULT (datetime('now'))"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE ssh_host_trust ADD COLUMN updated_at TEXT DEFAULT (datetime('now'))"); } catch (Exception $e) {}
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS app_secrets (
                ref TEXT PRIMARY KEY,
                provider TEXT NOT NULL,
                algorithm TEXT NOT NULL,
                nonce TEXT NOT NULL,
                ciphertext TEXT NOT NULL,
                metadata_json TEXT NOT NULL DEFAULT '{}',
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_app_secrets_provider ON app_secrets (provider, updated_at)"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN enabled INTEGER NOT NULL DEFAULT 1"); } catch (Exception $e) {}
        try { $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS api_rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT, ip TEXT NOT NULL, endpoint TEXT NOT NULL, hit_at TEXT DEFAULT (datetime('now')))")); } catch (Exception $e) {}
        try { $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS webauthn_credentials (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, name TEXT NOT NULL, credential_id TEXT UNIQUE NOT NULL, public_key TEXT NOT NULL, transports TEXT, counter INTEGER DEFAULT 0, counter_supported INTEGER NOT NULL DEFAULT 0, use_count INTEGER NOT NULL DEFAULT 0, last_used_at TEXT, created_at TEXT DEFAULT (datetime('now')))")); } catch (Exception $e) {}
        try { $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS user_invitations (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL, email TEXT, first_name TEXT, last_name TEXT, phone TEXT, job_title TEXT, role TEXT NOT NULL DEFAULT 'viewer', preferred_locale TEXT NOT NULL DEFAULT 'fr', preferred_timezone TEXT, preferred_start_page TEXT NOT NULL DEFAULT 'dashboard', permissions_json TEXT NOT NULL DEFAULT '{}', repo_scope_mode TEXT NOT NULL DEFAULT 'all', repo_scope_json TEXT NOT NULL DEFAULT '[]', host_scope_mode TEXT NOT NULL DEFAULT 'all', host_scope_json TEXT NOT NULL DEFAULT '[]', force_actions_json TEXT NOT NULL DEFAULT '[]', admin_notes TEXT, invited_by INTEGER, token_hash TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL, created_at TEXT DEFAULT (datetime('now')), accepted_at TEXT, revoked_at TEXT)")); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE webauthn_credentials ADD COLUMN transports TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN primary_second_factor TEXT NOT NULL DEFAULT ''"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE webauthn_credentials ADD COLUMN counter_supported INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE webauthn_credentials ADD COLUMN use_count INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE webauthn_credentials ADD COLUMN last_used_at TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE repo_stats_history ADD COLUMN total_file_count INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN unique_key TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN payload_json TEXT NOT NULL DEFAULT '{}'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN status TEXT NOT NULL DEFAULT 'queued'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN priority INTEGER NOT NULL DEFAULT 100"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN attempts INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN available_at TEXT DEFAULT (datetime('now'))"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN claimed_at TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN started_at TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN finished_at TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN last_error TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN created_at TEXT DEFAULT (datetime('now'))"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE job_queue ADD COLUMN updated_at TEXT DEFAULT (datetime('now'))"); } catch (Exception $e) {}
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS job_log_runs (
                run_id VARCHAR(64) PRIMARY KEY,
                type VARCHAR(32) NOT NULL,
                user_id INTEGER NOT NULL,
                permission_required VARCHAR(80) NOT NULL,
                created_at TEXT DEFAULT (datetime('now')),
                expires_at TEXT NOT NULL
            )"));
        } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE app_notifications ADD COLUMN severity TEXT NOT NULL DEFAULT 'info'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE app_notifications ADD COLUMN link_url TEXT"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE app_notifications ADD COLUMN browser_delivery INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE app_notifications ADD COLUMN is_read INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE app_notifications ADD COLUMN read_at TEXT"); } catch (Exception $e) {}
        // ── Quick Backup wizard ──────────────────────────────────────────────
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS quick_backup_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT DEFAULT '',
                category TEXT DEFAULT 'Personnalise',
                source_paths TEXT NOT NULL DEFAULT '[]',
                excludes TEXT NOT NULL DEFAULT '[]',
                tags TEXT NOT NULL DEFAULT '[]',
                schedule_hour INTEGER NOT NULL DEFAULT 2,
                schedule_days TEXT NOT NULL DEFAULT '1,2,3,4,5,6,7',
                retention_keep_last INTEGER NOT NULL DEFAULT 0,
                retention_keep_daily INTEGER NOT NULL DEFAULT 7,
                retention_keep_weekly INTEGER NOT NULL DEFAULT 4,
                retention_keep_monthly INTEGER NOT NULL DEFAULT 3,
                retention_keep_yearly INTEGER NOT NULL DEFAULT 0,
                retention_prune INTEGER NOT NULL DEFAULT 1,
                defaults_json TEXT NOT NULL DEFAULT '{}',
                updated_at TEXT DEFAULT (datetime('now')),
                created_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE quick_backup_templates ADD COLUMN category TEXT DEFAULT 'Personnalise'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE quick_backup_templates ADD COLUMN retention_keep_yearly INTEGER NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE quick_backup_templates ADD COLUMN retention_prune INTEGER NOT NULL DEFAULT 1"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE quick_backup_templates ADD COLUMN defaults_json TEXT NOT NULL DEFAULT '{}'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE quick_backup_templates ADD COLUMN updated_at TEXT DEFAULT (datetime('now'))"); } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_quick_backup_templates_name ON quick_backup_templates (name, updated_at DESC)"); } catch (Exception $e) {}
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS theme_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                requested_by INTEGER NOT NULL,
                source_type TEXT NOT NULL,
                source_url TEXT,
                source_file TEXT,
                store_id TEXT,
                theme_name TEXT,
                theme_description TEXT,
                status TEXT NOT NULL DEFAULT 'pending',
                reviewed_by INTEGER,
                reviewed_at TEXT,
                review_notes TEXT,
                installed_theme_id TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_theme_requests_status ON theme_requests (status, created_at DESC)"); } catch (Exception $e) {}

        // ── public API : tokens, audit, webhooks, idempotency ─────────────
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS api_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                prefix TEXT NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                scopes_json TEXT NOT NULL DEFAULT '[]',
                allowed_ips_json TEXT NOT NULL DEFAULT '[]',
                allowed_origins_json TEXT NOT NULL DEFAULT '[]',
                rate_limit_per_minute INTEGER NOT NULL DEFAULT 120,
                read_only INTEGER NOT NULL DEFAULT 0,
                expires_at TEXT,
                last_used_at TEXT,
                last_used_ip TEXT,
                revoked_at TEXT,
                revoked_reason TEXT,
                created_by INTEGER,
                created_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_api_tokens_user ON api_tokens (user_id, revoked_at)"); } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_api_tokens_hash ON api_tokens (token_hash)"); } catch (Exception $e) {}

        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS api_token_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token_id INTEGER,
                user_id INTEGER,
                method TEXT NOT NULL,
                endpoint TEXT NOT NULL,
                status INTEGER NOT NULL,
                ip TEXT,
                user_agent TEXT,
                latency_ms INTEGER,
                error TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_api_token_logs_token_created ON api_token_logs (token_id, created_at DESC)"); } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_api_token_logs_created ON api_token_logs (created_at DESC)"); } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_hook_scripts_status_name ON hook_scripts (status, name)"); } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_backup_jobs_pre_hook_script ON backup_jobs (pre_hook_script_id)"); } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_backup_jobs_post_hook_script ON backup_jobs (post_hook_script_id)"); } catch (Exception $e) {}

        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS api_webhooks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                url TEXT NOT NULL,
                secret TEXT NOT NULL DEFAULT '',
                secret_ref TEXT,
                events_json TEXT NOT NULL DEFAULT '[]',
                enabled INTEGER NOT NULL DEFAULT 1,
                last_status INTEGER,
                last_attempt_at TEXT,
                last_error TEXT,
                created_by INTEGER,
                created_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE api_webhooks ADD COLUMN secret_ref TEXT"); } catch (Exception $e) {}
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS api_webhook_deliveries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                webhook_id INTEGER NOT NULL,
                event TEXT NOT NULL,
                payload TEXT NOT NULL,
                status INTEGER,
                response TEXT,
                error TEXT,
                attempted_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_api_webhook_deliveries_webhook ON api_webhook_deliveries (webhook_id, attempted_at DESC)"); } catch (Exception $e) {}
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS secret_broker_status (
                endpoint TEXT PRIMARY KEY,
                node_id TEXT,
                node_label TEXT,
                cluster_name TEXT,
                status TEXT NOT NULL DEFAULT 'unknown',
                error_message TEXT,
                last_seen_at TEXT,
                last_change_at TEXT,
                updated_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS secret_broker_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                endpoint TEXT,
                event_type TEXT NOT NULL,
                severity TEXT NOT NULL DEFAULT 'warning',
                node_id TEXT,
                node_label TEXT,
                message TEXT NOT NULL,
                details_json TEXT NOT NULL DEFAULT '{}',
                created_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_secret_broker_events_created ON secret_broker_events (created_at DESC, id DESC)"); } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_secret_broker_events_type ON secret_broker_events (event_type, created_at DESC)"); } catch (Exception $e) {}
        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS infisical_config_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT NOT NULL DEFAULT 'update',
                changed_by INTEGER NOT NULL,
                previous_url TEXT,
                new_url TEXT,
                source_ip TEXT,
                validation_success INTEGER NOT NULL DEFAULT 0,
                validation_result_json TEXT NOT NULL DEFAULT '{}',
                config_json TEXT NOT NULL,
                restored_from_id INTEGER,
                created_at TEXT DEFAULT (datetime('now'))
            )"));
        } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_infisical_config_history_created ON infisical_config_history (created_at DESC, id DESC)"); } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_infisical_config_history_changed_by ON infisical_config_history (changed_by, created_at DESC)"); } catch (Exception $e) {}

        try {
            $db->exec(self::adaptDdl("CREATE TABLE IF NOT EXISTS api_idempotency_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token_id INTEGER NOT NULL,
                idempotency_key TEXT NOT NULL,
                method TEXT NOT NULL,
                endpoint TEXT NOT NULL,
                response_status INTEGER NOT NULL,
                response_body TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                UNIQUE (token_id, idempotency_key)
            )"));
        } catch (Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_api_idempotency_created ON api_idempotency_keys (created_at DESC)"); } catch (Exception $e) {}

        $db->exec("
            CREATE INDEX IF NOT EXISTS idx_repo_stats_history_repo_recorded_at
                ON repo_stats_history (repo_id, recorded_at DESC);
            CREATE INDEX IF NOT EXISTS idx_repo_stats_history_recorded_at
                ON repo_stats_history (recorded_at DESC);
            CREATE INDEX IF NOT EXISTS idx_repo_runtime_status_updated_at
                ON repo_runtime_status (updated_at DESC);
            CREATE INDEX IF NOT EXISTS idx_disk_space_checks_checked_at
                ON disk_space_checks (checked_at DESC);
            CREATE INDEX IF NOT EXISTS idx_disk_space_checks_context
                ON disk_space_checks (context_type, context_id, checked_at DESC);
            CREATE INDEX IF NOT EXISTS idx_disk_space_runtime_status_severity
                ON disk_space_runtime_status (severity, checked_at DESC);
            CREATE INDEX IF NOT EXISTS idx_disk_space_runtime_status_host
                ON disk_space_runtime_status (host_id, checked_at DESC);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at
                ON activity_logs (created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_user_created_at
                ON activity_logs (user_id, created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_username_created_at
                ON activity_logs (username, created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_ssh_host_trust_host_port
                ON ssh_host_trust (host, port);
            CREATE INDEX IF NOT EXISTS idx_ssh_host_trust_status_seen
                ON ssh_host_trust (status, last_seen_at DESC, updated_at DESC);
            CREATE INDEX IF NOT EXISTS idx_restore_history_started_at
                ON restore_history (started_at DESC);
            CREATE INDEX IF NOT EXISTS idx_active_sessions_user_created_at
                ON active_sessions (user_id, created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_api_rate_limits_lookup
                ON api_rate_limits (ip, endpoint, hit_at DESC);
            CREATE INDEX IF NOT EXISTS idx_login_attempts_username_attempted_at
                ON login_attempts (username, attempted_at DESC);
            CREATE INDEX IF NOT EXISTS idx_cron_log_job_type_ran_at
                ON cron_log (job_type, ran_at DESC);
            CREATE INDEX IF NOT EXISTS idx_notification_log_created_at
                ON notification_log (created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_notification_log_context
                ON notification_log (context_type, context_id, created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_app_notifications_user_created_at
                ON app_notifications (user_id, created_at DESC, id DESC);
            CREATE INDEX IF NOT EXISTS idx_app_notifications_user_unread
                ON app_notifications (user_id, is_read, created_at DESC, id DESC);
            CREATE INDEX IF NOT EXISTS idx_app_notifications_user_browser
                ON app_notifications (user_id, browser_delivery, id DESC);
            CREATE INDEX IF NOT EXISTS idx_job_queue_status_available_priority
                ON job_queue (status, available_at, priority DESC);
            CREATE INDEX IF NOT EXISTS idx_job_queue_status_priority_attempts
                ON job_queue (status, priority DESC, attempts, available_at);
            CREATE INDEX IF NOT EXISTS idx_job_queue_unique_key
                ON job_queue (type, unique_key, status);
            CREATE INDEX IF NOT EXISTS idx_job_log_runs_user_type
                ON job_log_runs (user_id, type, expires_at);
            CREATE INDEX IF NOT EXISTS idx_job_log_runs_expires
                ON job_log_runs (expires_at);
            CREATE INDEX IF NOT EXISTS idx_user_invitations_token_hash
                ON user_invitations (token_hash);
            CREATE INDEX IF NOT EXISTS idx_user_invitations_status
                ON user_invitations (accepted_at, revoked_at, expires_at DESC);
        ");

        try { $db->exec("UPDATE users SET permissions_json = '{}' WHERE permissions_json IS NULL OR permissions_json = ''"); } catch (Exception $e) {}
        try { $db->exec("UPDATE users SET repo_scope_mode = 'all' WHERE repo_scope_mode IS NULL OR repo_scope_mode = ''"); } catch (Exception $e) {}
        try { $db->exec("UPDATE users SET repo_scope_json = '[]' WHERE repo_scope_json IS NULL OR repo_scope_json = ''"); } catch (Exception $e) {}
        try { $db->exec("UPDATE users SET host_scope_mode = 'all' WHERE host_scope_mode IS NULL OR host_scope_mode = ''"); } catch (Exception $e) {}
        try { $db->exec("UPDATE users SET host_scope_json = '[]' WHERE host_scope_json IS NULL OR host_scope_json = ''"); } catch (Exception $e) {}
        try { $db->exec("UPDATE users SET force_actions_json = '[]' WHERE force_actions_json IS NULL OR force_actions_json = ''"); } catch (Exception $e) {}
        try { $db->exec("UPDATE users SET preferred_locale = 'fr' WHERE preferred_locale IS NULL OR preferred_locale = ''"); } catch (Exception $e) {}
        try { $db->exec("UPDATE users SET preferred_start_page = 'dashboard' WHERE preferred_start_page IS NULL OR preferred_start_page = ''"); } catch (Exception $e) {}
        try { $db->exec("UPDATE users SET password_set_at = COALESCE(password_set_at, created_at, datetime('now'))"); } catch (Exception $e) {}

        self::seedDefaultSettings($db);

        // Ne never create of compte admin by default from the runtime web.
        // the recuperation must passer by the commande CLI locale.
        $installedFile = dirname(__DIR__, 2) . '/data/.installed';
        if (is_file($installedFile)) {
            $count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            if ((int) $count === 0) {
                error_log('Fulgurite security: installed instance has no users; run bin/fulgurite-recover-admin locally to recover access.');
            }
        }
    }

    private static function initIndexSchema(PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS snapshot_navigation_index (
                repo_id INTEGER NOT NULL,
                snapshot_id TEXT NOT NULL,
                path TEXT NOT NULL,
                parent_path TEXT NOT NULL,
                name TEXT NOT NULL,
                type TEXT,
                size INTEGER DEFAULT 0,
                mtime TEXT,
                indexed_at TEXT DEFAULT (datetime('now')),
                PRIMARY KEY (repo_id, snapshot_id, path)
            );
            CREATE TABLE IF NOT EXISTS snapshot_file_index (
                repo_id INTEGER NOT NULL,
                snapshot_id TEXT NOT NULL,
                path TEXT NOT NULL,
                name TEXT NOT NULL,
                name_lc TEXT NOT NULL,
                type TEXT,
                size INTEGER DEFAULT 0,
                mtime TEXT,
                indexed_at TEXT DEFAULT (datetime('now')),
                PRIMARY KEY (repo_id, snapshot_id, path)
            );
            CREATE TABLE IF NOT EXISTS snapshot_search_index_status (
                repo_id INTEGER NOT NULL,
                snapshot_id TEXT NOT NULL,
                indexed_at TEXT DEFAULT (datetime('now')),
                file_count INTEGER DEFAULT 0,
                scope TEXT NOT NULL DEFAULT 'full',
                PRIMARY KEY (repo_id, snapshot_id)
            );
            CREATE TABLE IF NOT EXISTS repo_snapshot_catalog (
                repo_id INTEGER NOT NULL,
                snapshot_id TEXT NOT NULL,
                full_id TEXT,
                snapshot_time TEXT,
                hostname TEXT,
                username TEXT,
                tags_json TEXT NOT NULL DEFAULT '[]',
                paths_json TEXT NOT NULL DEFAULT '[]',
                indexed_at TEXT DEFAULT (datetime('now')),
                PRIMARY KEY (repo_id, snapshot_id)
            );
        ");

        try { $db->exec("ALTER TABLE snapshot_search_index_status ADD COLUMN scope TEXT NOT NULL DEFAULT 'full'"); } catch (Exception $e) {}
        try { $db->exec("UPDATE snapshot_search_index_status SET scope = 'full' WHERE scope IS NULL OR scope = ''"); } catch (Exception $e) {}

        $db->exec("
            CREATE INDEX IF NOT EXISTS idx_snapshot_navigation_index_lookup
                ON snapshot_navigation_index (repo_id, snapshot_id, parent_path, name);
            CREATE INDEX IF NOT EXISTS idx_snapshot_file_index_lookup
                ON snapshot_file_index (repo_id, snapshot_id, name_lc);
            CREATE INDEX IF NOT EXISTS idx_snapshot_search_index_status_repo_snapshot
                ON snapshot_search_index_status (repo_id, snapshot_id);
            CREATE INDEX IF NOT EXISTS idx_snapshot_search_index_status_repo_scope_time
                ON snapshot_search_index_status (repo_id, scope, indexed_at DESC);
            CREATE INDEX IF NOT EXISTS idx_repo_snapshot_catalog_repo_time
                ON repo_snapshot_catalog (repo_id, snapshot_time DESC);
        ");

        self::optimizeSnapshotSearchSchema($db);
    }

    private static function optimizeSnapshotSearchSchema(PDO $db): void {
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $db->exec("
                CREATE INDEX IF NOT EXISTS idx_snapshot_file_index_repo_snapshot_name_path
                    ON snapshot_file_index (repo_id, snapshot_id, name_lc, path);
            ");

            try {
                $db->exec("
                    CREATE VIRTUAL TABLE IF NOT EXISTS snapshot_file_index_fts
                    USING fts5(name_lc, content='snapshot_file_index', content_rowid='rowid');
                ");
                $db->exec("
                    CREATE TRIGGER IF NOT EXISTS snapshot_file_index_ai
                    AFTER INSERT ON snapshot_file_index
                    BEGIN
                        INSERT INTO snapshot_file_index_fts(rowid, name_lc) VALUES (new.rowid, new.name_lc);
                    END;
                ");
                $db->exec("
                    CREATE TRIGGER IF NOT EXISTS snapshot_file_index_ad
                    AFTER DELETE ON snapshot_file_index
                    BEGIN
                        INSERT INTO snapshot_file_index_fts(snapshot_file_index_fts, rowid, name_lc)
                        VALUES ('delete', old.rowid, old.name_lc);
                    END;
                ");
                $db->exec("
                    CREATE TRIGGER IF NOT EXISTS snapshot_file_index_au
                    AFTER UPDATE ON snapshot_file_index
                    BEGIN
                        INSERT INTO snapshot_file_index_fts(snapshot_file_index_fts, rowid, name_lc)
                        VALUES ('delete', old.rowid, old.name_lc);
                        INSERT INTO snapshot_file_index_fts(rowid, name_lc) VALUES (new.rowid, new.name_lc);
                    END;
                ");
                $db->exec("INSERT INTO snapshot_file_index_fts(snapshot_file_index_fts) VALUES ('rebuild')");
            } catch (Throwable $e) {
                error_log('Snapshot search optimization: SQLite FTS5 unavailable, fallback mode kept: ' . $e->getMessage());
            }
            return;
        }

        if ($driver === 'mysql') {
            if (!self::indexExists($db, 'snapshot_file_index', 'idx_snapshot_file_index_repo_snapshot_name_path')) {
                $db->exec("
                    CREATE INDEX idx_snapshot_file_index_repo_snapshot_name_path
                        ON snapshot_file_index (repo_id, snapshot_id, name_lc, path)
                ");
            }

            if (!self::indexExists($db, 'snapshot_file_index', 'idx_snapshot_file_index_name_fulltext')) {
                try {
                    $db->exec("CREATE FULLTEXT INDEX idx_snapshot_file_index_name_fulltext ON snapshot_file_index (name_lc)");
                } catch (Throwable $e) {
                    error_log('Snapshot search optimization: MySQL FULLTEXT unavailable, fallback mode kept: ' . $e->getMessage());
                }
            }
            return;
        }

        if ($driver === 'pgsql') {
            $db->exec("
                CREATE INDEX IF NOT EXISTS idx_snapshot_file_index_repo_snapshot_name_path
                    ON snapshot_file_index (repo_id, snapshot_id, name_lc, path)
            ");
            $db->exec("
                CREATE INDEX IF NOT EXISTS idx_snapshot_file_index_name_tsv
                    ON snapshot_file_index USING GIN (to_tsvector('simple', COALESCE(name_lc, '')))
            ");
        }
    }

    private static function migrateLegacySearchTables(PDO $mainDb, PDO $indexDb): void {
        if (self::getRawSetting($mainDb, 'search_db_migrated_v1', '0') === '1') {
            return;
        }

        $legacyTables = [
            'snapshot_file_index',
            'snapshot_search_index_status',
            'repo_snapshot_catalog',
        ];

        $hasLegacyTables = false;
        foreach ($legacyTables as $table) {
            if (self::hasTable($mainDb, $table)) {
                $hasLegacyTables = true;
                break;
            }
        }

        if (!$hasLegacyTables) {
            self::setRawSetting($mainDb, 'search_db_migrated_v1', '1');
            return;
        }

        foreach ($legacyTables as $table) {
            if (!self::hasTable($mainDb, $table)) {
                continue;
            }

            self::copyLegacyTable($mainDb, $indexDb, $table);
        }

        foreach ($legacyTables as $table) {
            if (self::hasTable($mainDb, $table)) {
                $mainDb->exec("DROP TABLE IF EXISTS $table");
            }
        }

        self::setRawSetting($mainDb, 'search_db_migrated_v1', '1');
    }

    private static function copyLegacyTable(PDO $mainDb, PDO $indexDb, string $table): void {
        $statusScopeSql = self::hasColumn($mainDb, 'snapshot_search_index_status', 'scope')
            ? 'scope'
            : "'full' AS scope";
        $query = match ($table) {
            'snapshot_file_index' => "SELECT repo_id, snapshot_id, path, name, name_lc, type, size, mtime, indexed_at FROM snapshot_file_index",
            'snapshot_search_index_status' => "SELECT repo_id, snapshot_id, indexed_at, file_count, {$statusScopeSql} FROM snapshot_search_index_status",
            'repo_snapshot_catalog' => "SELECT repo_id, snapshot_id, full_id, snapshot_time, hostname, username, tags_json, paths_json, indexed_at FROM repo_snapshot_catalog",
            default => null,
        };
        if ($query === null) {
            return;
        }

        $rows = $mainDb->query($query);
        $insert = match ($table) {
            'snapshot_file_index' => $indexDb->prepare("
                INSERT INTO snapshot_file_index (repo_id, snapshot_id, path, name, name_lc, type, size, mtime, indexed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(repo_id, snapshot_id, path) DO UPDATE SET
                    name = excluded.name,
                    name_lc = excluded.name_lc,
                    type = excluded.type,
                    size = excluded.size,
                    mtime = excluded.mtime,
                    indexed_at = excluded.indexed_at
            "),
            'snapshot_search_index_status' => $indexDb->prepare("
                INSERT INTO snapshot_search_index_status (repo_id, snapshot_id, indexed_at, file_count, scope)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT(repo_id, snapshot_id) DO UPDATE SET
                    indexed_at = excluded.indexed_at,
                    file_count = excluded.file_count,
                    scope = excluded.scope
            "),
            'repo_snapshot_catalog' => $indexDb->prepare("
                INSERT INTO repo_snapshot_catalog (repo_id, snapshot_id, full_id, snapshot_time, hostname, username, tags_json, paths_json, indexed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(repo_id, snapshot_id) DO UPDATE SET
                    full_id = excluded.full_id,
                    snapshot_time = excluded.snapshot_time,
                    hostname = excluded.hostname,
                    username = excluded.username,
                    tags_json = excluded.tags_json,
                    paths_json = excluded.paths_json,
                    indexed_at = excluded.indexed_at
            "),
            default => null,
        };

        if ($insert === null) {
            return;
        }

        $indexDb->beginTransaction();
        try {
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                if ($table === 'snapshot_search_index_status' && empty($row['scope'])) {
                    $row['scope'] = 'full';
                }

                $insert->execute(array_values($row));
            }

            $indexDb->commit();
        } catch (Throwable $e) {
            if ($indexDb->inTransaction()) {
                $indexDb->rollBack();
            }
            throw $e;
        }
    }

    private static function hasTable(PDO $db, string $table): bool {
        $driver = self::driver();
        try {
            if ($driver === 'mysql') {
                $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
                $stmt->execute([$table]);
                return (bool) $stmt->fetchColumn();
            }
            if ($driver === 'pgsql') {
                $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
                $stmt->execute([$table]);
                return (bool) $stmt->fetchColumn();
            }
            // SQLite
            $stmt = $db->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1");
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function indexExists(PDO $db, string $table, string $index): bool {
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        try {
            if ($driver === 'mysql') {
                $stmt = $db->prepare("
                    SELECT COUNT(*)
                    FROM information_schema.statistics
                    WHERE table_schema = DATABASE()
                      AND table_name = ?
                      AND index_name = ?
                ");
                $stmt->execute([$table, $index]);
                return (int) $stmt->fetchColumn() > 0;
            }
            if ($driver === 'pgsql') {
                $stmt = $db->prepare("
                    SELECT COUNT(*)
                    FROM pg_indexes
                    WHERE schemaname = 'public'
                      AND tablename = ?
                      AND indexname = ?
                ");
                $stmt->execute([$table, $index]);
                return (int) $stmt->fetchColumn() > 0;
            }

            $stmt = $db->query("PRAGMA index_list({$table})");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $info) {
                if (($info['name'] ?? '') === $index) {
                    return true;
                }
            }
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function hasColumn(PDO $db, string $table, string $column): bool {
        $driver = self::driver();
        try {
            if ($driver === 'mysql') {
                $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
                $stmt->execute([$table, $column]);
                return (bool) $stmt->fetchColumn();
            }
            if ($driver === 'pgsql') {
                $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?");
                $stmt->execute([$table, $column]);
                return (bool) $stmt->fetchColumn();
            }
            // SQLite
            $stmt = $db->query("PRAGMA table_info({$table})");
            foreach ($stmt->fetchAll() as $info) {
                if (($info['name'] ?? null) === $column) {
                    return true;
                }
            }
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function getRawSetting(PDO $db, string $key, string $default = ''): string {
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = ? LIMIT 1");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : $default;
    }

    private static function setRawSetting(PDO $db, string $key, string $value): void {
        $now = self::nowExpr();
        $driver = self::driver();

        if ($driver === 'mysql') {
            $db->prepare("
                INSERT INTO settings (key, value, updated_at)
                VALUES (?, ?, {$now})
                ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at)
            ")->execute([$key, $value]);
        } else {
            // SQLite and PostgreSQL support the same ON CONFLICT syntax.
            $db->prepare("
                INSERT INTO settings (key, value, updated_at)
                VALUES (?, ?, {$now})
                ON CONFLICT(key) DO UPDATE SET
                    value = excluded.value,
                    updated_at = excluded.updated_at
            ")->execute([$key, $value]);
        }
    }

    /** Expose nowExpr() for the classes externes (ex: SetupWizard). */
    public static function nowExprPublic(): string {
        return self::nowExpr();
    }

    public static function getSetting(string $key, string $default = ''): string {
        try {
            $db = self::getInstance();
            $value = self::getRawSetting($db, $key, $default);
            if (self::isSensitiveSetting($key) && class_exists('SecretStore', false)) {
                if (SecretStore::isSecretRef($value)) {
                    return SecretStore::get($value) ?? $default;
                }
                if ($value !== '') {
                    try {
                        $ref = SecretStore::writableRef('setting', self::settingRefId($key), 'value', SecretStore::resolvedWritableSource());
                        SecretStore::put($ref, $value, ['entity' => 'setting', 'key' => $key]);
                        self::setRawSetting($db, $key, $ref);
                    } catch (Throwable $e) {
                        SecretRedaction::errorLog('Fulgurite security warning: unable to migrate sensitive setting ' . $key . ' to SecretStore: ' . SecretRedaction::safeThrowableMessage($e));
                    }
                }
            }
            return $value;
        } catch (Exception $e) {
            return $default;
        }
    }

    public static function getStoredSetting(string $key, string $default = ''): string {
        try {
            return self::getRawSetting(self::getInstance(), $key, $default);
        } catch (Exception $e) {
            return $default;
        }
    }

    public static function setSetting(string $key, string $value): void {
        if (self::isSensitiveSetting($key) && $value !== '' && !class_exists('SecretStore', false)) {
            throw new RuntimeException('SecretStore doit etre charge avant de stocker un parametre sensible.');
        }
        if (self::isSensitiveSetting($key) && $value !== '' && class_exists('SecretStore', false) && !SecretStore::isSecretRef($value)) {
            try {
                $ref = SecretStore::writableRef('setting', self::settingRefId($key), 'value', SecretStore::resolvedWritableSource());
                SecretStore::put($ref, $value, ['entity' => 'setting', 'key' => $key]);
                $value = $ref;
            } catch (Throwable $e) {
                SecretRedaction::errorLog('Fulgurite security warning: refusing plaintext fallback for sensitive setting '
                    . $key . ' because secure secret storage failed: ' . SecretRedaction::safeThrowableMessage($e));
                throw new RuntimeException('Impossible de stocker le parametre sensible ' . $key . ' de maniere securisee.', 0, $e);
            }
        }
        self::setRawSetting(self::getInstance(), $key, $value);
        self::bumpAppConfigSettingsVersion();
    }

    public static function isSensitiveSetting(string $key): bool {
        return in_array($key, [
            'smtp_pass',
            'webhook_auth_token',
            'webhook_url',
            'infisical_token',
            'gotify_token',
            'telegram_bot_token',
            'discord_webhook_url',
            'slack_webhook_url',
            'teams_webhook_url',
        ], true);
    }

    private static function settingRefId(string $key): int {
        return (int) sprintf('%u', crc32($key));
    }

    private static function bumpAppConfigSettingsVersion(): void {
        $version = (int) ($GLOBALS['__fulgurite_settings_cache_version'] ?? 0);
        $GLOBALS['__fulgurite_settings_cache_version'] = $version + 1;
    }

    private static function seedDefaultSettings(PDO $db): void {
        $now    = self::nowExpr();
        $driver = self::driver();

        if ($driver === 'mysql') {
            $stmt = $db->prepare("INSERT IGNORE INTO settings (key, value, updated_at) VALUES (?, ?, {$now})");
        } elseif ($driver === 'pgsql') {
            $stmt = $db->prepare("INSERT INTO settings (key, value, updated_at) VALUES (?, ?, {$now}) ON CONFLICT(key) DO NOTHING");
        } else {
            $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value, updated_at) VALUES (?, ?, {$now})");
        }

        foreach (AppConfig::defaultSettings() as $key => $value) {
            $stmt->execute([$key, (string) $value]);
        }
    }
}
