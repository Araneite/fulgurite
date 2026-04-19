<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();
rateLimitApi('manage_worker', 20, 60);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = (string) ($data['action'] ?? 'status');
$name = trim((string) ($data['name'] ?? AppConfig::workerDefaultName()));
$sleepSeconds = max(1, (int) ($data['sleep'] ?? AppConfig::workerSleepSeconds()));
$limit = max(1, (int) ($data['limit'] ?? AppConfig::workerLimit()));
$staleMinutes = max(1, (int) ($data['stale_minutes'] ?? AppConfig::workerStaleMinutes()));

$response = match ($action) {
    'status' => [
        'success' => true,
        'message' => 'Statut charge',
        'status' => WorkerManager::getStatus($name),
    ],
    'start'   => WorkerManager::start($name, $sleepSeconds, $limit, $staleMinutes),
    'stop'    => WorkerManager::stop($name),
    'restart' => WorkerManager::restart($name, $sleepSeconds, $limit, $staleMinutes),
    'run_once' => WorkerManager::runOnce($name, $limit, $staleMinutes),

    // Phase 1 — cron explicite
    'install_cron'   => WorkerManager::installCron($name, $limit, $staleMinutes),
    'uninstall_cron' => WorkerManager::uninstallCron($name),

    // Phase 2 — guided generation of the systemd file
    'generate_systemd_unit' => WorkerManager::generateSystemdUnit($name),

    // Phase 3 — auto-install systemd via sudo helper
    'check_auto_install' => (function () use ($name): array {
        $check = WorkerManager::canAutoInstallSystemd();
        return [
            'success' => true,
            'can_auto_install' => $check['can'],
            'reason' => $check['reason'],
            'helper' => $check['helper'] ?? '',
            'status' => WorkerManager::getStatus($name),
        ];
    })(),
    'install_systemd'   => WorkerManager::installSystemd($name),
    'uninstall_systemd' => WorkerManager::uninstallSystemd($name),

    default => null,
};

if ($response === null) {
    jsonResponse(['success' => false, 'error' => 'Action invalide'], 400);
}

jsonResponse($response);
