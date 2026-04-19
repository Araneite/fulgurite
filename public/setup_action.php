<?php
// =============================================================================
// setup_action.php — AJAX endpoint for the installation wizard
// =============================================================================
define('FULGURITE_SETUP', true);

// Bootstrap minimal : config + classes necessaires
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Profiler.php';
RequestProfiler::bootstrap();
require_once __DIR__ . '/../src/AppConfig.php';
require_once __DIR__ . '/../src/JobRetryPolicy.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/DatabaseMigrations.php';
require_once __DIR__ . '/../src/ProcessRunner.php';
require_once __DIR__ . '/../src/FilesystemScopeConfigWriter.php';
require_once __DIR__ . '/../src/FilesystemScopeGuard.php';
require_once __DIR__ . '/../src/Setup/SetupGuard.php';
require_once __DIR__ . '/../src/Setup/SetupWizard.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Check that setup is not already completed
if (SetupGuard::isInstalled()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Fulgurite est déjà installé.']);
    exit;
}
// Session is already started by config.php

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'authorize') {
    $result = SetupGuard::authorizeWithBootstrapToken((string) ($_POST['setup_token'] ?? ''));
    $statusCode = max(200, (int) ($result['status_code'] ?? 200));
    $retryAfter = max(0, (int) ($result['retry_after'] ?? 0));
    unset($result['status_code'], $result['retry_after']);

    if ($statusCode !== 200) {
        http_response_code($statusCode);
    }
    if ($retryAfter > 0) {
        header('Retry-After: ' . $retryAfter);
    }

    echo json_encode($result);
    exit;
}

if (!SetupGuard::isSessionAuthorized()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Session de setup non autorisee. Debloquez le wizard avec un token bootstrap.']);
    exit;
}

SetupGuard::refreshSession();

// ── Dispatcher ──────────────────────────────────────────────────────────────

switch ($action) {

    // Step 1: Prerequisite verification
    case 'check_prerequisites':
        echo json_encode(SetupWizard::checkPrerequisites());
        break;

    case 'install_rsync':
        echo json_encode(SetupWizard::installRsync(isset($_POST['sudo_password']) ? (string) $_POST['sudo_password'] : null));
        break;

    // Step 2: Database connection test
    case 'test_database':
        $params = [
            'driver' => $_POST['driver'] ?? 'sqlite',
            'host'   => trim($_POST['host'] ?? 'localhost'),
            'port'   => trim($_POST['port'] ?? '3306'),
            'name'   => trim($_POST['name'] ?? 'fulgurite'),
            'user'   => trim($_POST['user'] ?? ''),
            'pass'   => $_POST['pass'] ?? '',
        ];
        echo json_encode(SetupWizard::testDatabaseConnection($params));
        break;

    // Step 3: web server detection + config generation
    case 'detect_webserver':
        $info = SetupWizard::detectWebServer();
        echo json_encode($info);
        break;

    case 'generate_webserver_config':
        $type       = $_POST['type'] ?? 'apache';
        $docRoot    = trim($_POST['doc_root'] ?? realpath(dirname(__DIR__)));
        $serverName = trim($_POST['server_name'] ?? '');
        $webUser    = trim($_POST['web_user'] ?? '');
        $webGroup   = trim($_POST['web_group'] ?? '');
        $phpFpmSocket = trim($_POST['php_fpm_socket'] ?? '');

        if ($docRoot === '') {
            $docRoot = realpath(dirname(__DIR__)) ?: '/var/www/fulgurite';
        }

        if ($type === 'nginx') {
            $config = SetupWizard::generateNginxConfig($docRoot, $serverName, $webUser, $webGroup, $phpFpmSocket);
        } else {
            $config = SetupWizard::generateApacheConfig($docRoot, $serverName, $webUser, $webGroup);
        }

        echo json_encode(['ok' => true, 'config' => $config]);
        break;

    // Step final : Installation complete
    case 'finalize':
        $data = [
            'db_driver'        => $_POST['db_driver'] ?? 'sqlite',
            'db_host'          => trim($_POST['db_host'] ?? 'localhost'),
            'db_port'          => trim($_POST['db_port'] ?? '3306'),
            'db_name'          => trim($_POST['db_name'] ?? 'fulgurite'),
            'db_user'          => trim($_POST['db_user'] ?? ''),
            'db_pass'          => $_POST['db_pass'] ?? '',
            'admin_username'   => trim($_POST['admin_username'] ?? ''),
            'admin_password'   => $_POST['admin_password'] ?? '',
            'admin_email'      => trim($_POST['admin_email'] ?? ''),
            'admin_first_name' => trim($_POST['admin_first_name'] ?? ''),
            'admin_last_name'  => trim($_POST['admin_last_name'] ?? ''),
            'app_name'         => trim($_POST['app_name'] ?? 'Fulgurite'),
            'timezone'         => trim($_POST['timezone'] ?? 'UTC'),
            'web_user'         => trim($_POST['web_user'] ?? ''),
            'web_group'        => trim($_POST['web_group'] ?? ''),
            'php_fpm_socket'   => trim($_POST['php_fpm_socket'] ?? ''),
        ];

        $errors = SetupWizard::validateFinalData($data);
        if (!empty($errors)) {
            echo json_encode(['ok' => false, 'message' => implode(' ', $errors), 'errors' => $errors]);
            break;
        }

        $result = SetupWizard::finalize($data);

        // if installation succeeds, generate a one-time session token for
        // post-install worker configuration (used by setup_worker.php)
        if (!empty($result['ok'])) {
            $setupWorkerToken = bin2hex(random_bytes(16));
            $_SESSION['setup_worker_token'] = $setupWorkerToken;
            $_SESSION['setup_worker_token_expires'] = time() + 600; // 10 minutes
            $result['setup_worker_token'] = $setupWorkerToken;
        }

        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Action inconnue.']);
}
