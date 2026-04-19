<?php
// =============================================================================
// config.php — Configuration generale of Fulgurite
// =============================================================================
// Infrastructure paths/binaries and constants are defined here.
// Certains reglages business are maintenant stockes en base; the constantes
// corresponding constants serve as default values if DB has none yet.

define('APP_NAME',    'Fulgurite');
define('APP_VERSION', '1.1.0');

function fulguriteLoadEnvFile(string $path): void {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    foreach ((array) file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '' || preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) !== 1) {
            continue;
        }

        $value = trim($value);
        if (
            strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function fulguriteEnv(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }

    return $value !== null && $value !== '' ? (string) $value : $default;
}

function cspNonceAttr(): string {
    if (!defined('CSP_NONCE') || CSP_NONCE === '') {
        return '';
    }

    return ' nonce="' . htmlspecialchars((string) CSP_NONCE, ENT_QUOTES, 'UTF-8') . '"';
}

fulguriteLoadEnvFile(dirname(__DIR__) . '/.env');

// ── Configuration base of data ─────────────────────────────────────────────
if (is_file(__DIR__ . '/database.php')) {
    require_once __DIR__ . '/database.php';
} else {
    // Default values (setup backward compatibility)
    define('DB_DRIVER', fulguriteEnv('DB_DRIVER', 'sqlite'));
    define('DB_PATH', fulguriteEnv('DB_PATH', dirname(__DIR__) . '/data/fulgurite.db'));
    define('SEARCH_DB_PATH', fulguriteEnv('SEARCH_DB_PATH', dirname(__DIR__) . '/data/fulgurite-search.db'));
    define('DB_HOST', fulguriteEnv('DB_HOST', 'localhost'));
    define('DB_PORT', fulguriteEnv('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306'));
    define('DB_NAME', fulguriteEnv('DB_NAME', 'fulgurite'));
    define('DB_USER', fulguriteEnv('DB_USER', ''));
    define('DB_PASS', fulguriteEnv('DB_PASS', ''));
    define('DB_CHARSET', fulguriteEnv('DB_CHARSET', 'utf8mb4'));
}

// paths to the binaires
define('RESTIC_BIN', '/usr/bin/restic');
define('RSYNC_BIN',  '/usr/bin/rsync');
define('SSH_BIN',    '/usr/bin/ssh');

// Directory root of repos restic on this serverdefine('REPOS_BASE_PATH', '/backups');

// backup server (used for SFTP return test during host setup)
define('BACKUP_SERVER_HOST',      '192.168.9.214');
define('BACKUP_SERVER_SFTP_USER', 'backup');

function fulguriteEarlySetting(string $key, string $default = ''): string {
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        $driver = fulguriteEnv('DB_DRIVER', defined('DB_DRIVER') ? DB_DRIVER : 'sqlite');

        try {
            if ($driver === 'sqlite') {
                $dbPath = fulguriteEnv('DB_PATH', defined('DB_PATH') ? DB_PATH : (dirname(__DIR__) . '/data/fulgurite.db'));
                if (!is_file($dbPath)) return $default;
                $pdo = new PDO('sqlite:' . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'settings' LIMIT 1")->fetchColumn();
            } elseif ($driver === 'mysql') {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    fulguriteEnv('DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost'),
                    fulguriteEnv('DB_PORT', defined('DB_PORT') ? DB_PORT : '3306'),
                    fulguriteEnv('DB_NAME', defined('DB_NAME') ? DB_NAME : 'fulgurite'),
                    fulguriteEnv('DB_CHARSET', defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4')
                );
                $pdo = new PDO($dsn, fulguriteEnv('DB_USER', defined('DB_USER') ? DB_USER : ''), fulguriteEnv('DB_PASS', defined('DB_PASS') ? DB_PASS : ''), [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $tableExists = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings'")->fetchColumn();
            } elseif ($driver === 'pgsql') {
                $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
                    fulguriteEnv('DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost'),
                    fulguriteEnv('DB_PORT', defined('DB_PORT') ? DB_PORT : '5432'),
                    fulguriteEnv('DB_NAME', defined('DB_NAME') ? DB_NAME : 'fulgurite')
                );
                $pdo = new PDO($dsn, fulguriteEnv('DB_USER', defined('DB_USER') ? DB_USER : ''), fulguriteEnv('DB_PASS', defined('DB_PASS') ? DB_PASS : ''), [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $tableExists = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'settings'")->fetchColumn();
            } else {
                return $default;
            }

            if ($tableExists) {
                $rows = $pdo->query('SELECT key, value FROM settings')->fetchAll();
                foreach ($rows as $row) {
                    $settings[(string) ($row['key'] ?? '')] = (string) ($row['value'] ?? '');
                }
            }
        } catch (Throwable $e) {
            $settings = [];
        }
    }

    return $settings[$key] ?? $default;
}

// ── Guard of installation ──────────────────────────────────────────────────────
if (!defined('FULGURITE_SETUP') && !defined('FULGURITE_CLI') && php_sapi_name() !== 'cli') {
    if (!is_file(dirname(__DIR__) . '/data/.installed')) {
        header('Location: /setup.php');
        exit;
    }
}

// Worker dedie
define('WORKER_SYSTEMD_SERVICE', 'fulgurite-worker.service');
define('WORKER_SYSTEMD_USE_SUDO', true);
define('WORKER_SYSTEMCTL_BIN', '/bin/systemctl');
define('WORKER_SUDO_BIN', '/usr/bin/sudo');

// Session duration in seconds (8 hours)
define('SESSION_LIFETIME', 28800);

// size max of a file affichable (5 Mo)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Extensions of files a afficher comme texte
define('TEXT_EXTENSIONS', [
    'txt', 'log', 'conf', 'cfg', 'ini', 'yaml', 'yml', 'json', 'xml',
    'php', 'py', 'sh', 'bash', 'js', 'css', 'html', 'htm', 'md',
    'env', 'toml', 'sql', 'nginx', 'htaccess', 'pem', 'crt', 'key'
]);

// Available roles (ascending permission order)
define('ROLE_VIEWER',           'viewer');           // lecture seule
define('ROLE_OPERATOR',         'operator');         // + lancer backups/copies
define('ROLE_RESTORE_OPERATOR', 'restore-operator'); // + restaurer
define('ROLE_ADMIN',            'admin');            // acces total

// ── alerts backups ───────────────────────────────────────────────────────────
// threshold en heures — to the-dela, the repo is considere en alertdefine('BACKUP_ALERT_HOURS', 25);

// ── Notifications email ───────────────────────────────────────────────────────
// Set a true for activer the emails of alertdefine('MAIL_ENABLED',  false);
define('MAIL_FROM',     'fulgurite@araneite.dev');
define('MAIL_FROM_NAME','Fulgurite');
// Recipients of alerts (separated by comma)define('MAIL_TO',       'admin@araneite.dev');
// SMTP (laisser vide for utiliser the sendmail local)
define('SMTP_HOST',     '');
define('SMTP_PORT',     587);
define('SMTP_USER',     '');
define('SMTP_PASS',     '');
define('SMTP_TLS',      true);

// Timezone server
$fulguriteServerTimezone = trim((string) ini_get('date.timezone'));
if ($fulguriteServerTimezone === '') {
    $fulguriteServerTimezone = date_default_timezone_get() ?: 'UTC';
}
date_default_timezone_set($fulguriteServerTimezone);

// ── HTTP security headers (web only) ─────────────────────────────────────────
if (php_sapi_name() !== 'cli' && !defined('FULGURITE_CLI')) {
    // Nonce unique by request for the CSP (anti-XSS inline-script)
    if (!defined('CSP_NONCE')) {
        define('CSP_NONCE', base64_encode(random_bytes(16)));
    }
    $cspNonce = CSP_NONCE;

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    // script-src uses a nonce - remove unsafe-inline for scripts
    // CSP progressive : the scripts inline must porter the nonce; the styles
    // inline remains temporarily allowed during migration.
    header(
        "Content-Security-Policy: "
        . "default-src 'self'; "
        . "script-src 'self' 'nonce-$cspNonce'; "
        . "script-src-elem 'self' 'nonce-$cspNonce'; "
        . "script-src-attr 'unsafe-inline'; "
        . "style-src 'self' 'nonce-$cspNonce' 'unsafe-inline'; "
        . "style-src-elem 'self' 'nonce-$cspNonce'; "
        . "style-src-attr 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "font-src 'self' data:; "
        . "connect-src 'self'; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'none'"
    );
} else {
    if (!defined('CSP_NONCE')) define('CSP_NONCE', '');
}

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
    $sessionLifetimeMinutes = max(
        5,
        (int) fulguriteEarlySetting('session_absolute_lifetime_minutes', (string) max(5, (int) ceil(SESSION_LIFETIME / 60)))
    );
    session_set_cookie_params([
        'lifetime' => $sessionLifetimeMinutes * 60,
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => $isHttps,
    ]);
    session_start();
}
