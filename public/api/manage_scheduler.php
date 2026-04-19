<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('scheduler.manage');
verifyCsrf();
rateLimitApi('manage_scheduler', 20, 60);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = (string) ($data['action'] ?? '');

function launchBackgroundPhp(string $phpBinary, string $scriptPath, array $args = []): bool {
    $launch = ProcessRunner::startBackgroundPhp($scriptPath, $args, null, null, ['PHP_BINARY' => $phpBinary]);
    return (bool) ($launch['success'] ?? false);
}

if ($action === 'run_global_task') {
    $task = (string) ($data['task'] ?? '');
    $allowedTasks = ['weekly_report', 'integrity_check', 'db_vacuum'];
    if (!in_array($task, $allowedTasks, true)) {
        jsonResponse(['error' => 'Tache inconnue'], 400);
    }

    $run = RunLogManager::createRun('scheduler_task');
    $runId = $run['run_id'];
    $logFile = $run['log_file'];
    $scriptPath = __DIR__ . '/run_scheduler_task_background.php';
    $phpCliBinary = WorkerManager::getPhpCliBinary();

    if (!file_exists($scriptPath)) {
        jsonResponse(['error' => 'Script background introuvable'], 500);
    }

    if (!launchBackgroundPhp($phpCliBinary, $scriptPath, [$task, $logFile])) {
        jsonResponse(['error' => 'Impossible de demarrer la tache en arriere-plan'], 500);
    }

    Auth::log('scheduler_run_manual', "Execution manuelle de la tache $task en arriere-plan");
    jsonResponse([
        'success' => true,
        'run_id' => $runId,
        'log_file' => $logFile,
        'started' => true,
    ]);
}

if ($action === 'save_scheduler') {
    $fields = [
        'weekly_report_enabled',
        'weekly_report_day',
        'weekly_report_hour',
        'integrity_check_enabled',
        'integrity_check_hour',
        'maintenance_vacuum_enabled',
        'maintenance_vacuum_hour',
    ];
    $checkboxes = [
        'weekly_report_enabled',
        'integrity_check_enabled',
        'maintenance_vacuum_enabled',
    ];

    foreach ($fields as $field) {
        if (in_array($field, $checkboxes, true)) {
            Database::setSetting($field, !empty($data[$field]) ? '1' : '0');
            continue;
        }

        Database::setSetting($field, trim((string) ($data[$field] ?? '')));
    }

    $integrityCheckDays = array_values(array_filter(array_map('trim', (array) ($data['integrity_check_days'] ?? []))));
    if (empty($integrityCheckDays)) {
        $integrityCheckDays = ['1'];
    }
    Database::setSetting('integrity_check_day', implode(',', $integrityCheckDays));

    $maintenanceVacuumDays = array_values(array_filter(array_map('trim', (array) ($data['maintenance_vacuum_days'] ?? []))));
    if (empty($maintenanceVacuumDays)) {
        $maintenanceVacuumDays = ['7'];
    }
    Database::setSetting('maintenance_vacuum_day', implode(',', $maintenanceVacuumDays));

    Database::setSetting(
        'weekly_report_notification_policy',
        Notifier::encodePolicy(
            Notifier::parsePolicyPost($data, 'weekly_report_task', 'weekly_report', Notifier::getSettingPolicy('weekly_report_notification_policy', 'weekly_report')),
            'weekly_report'
        )
    );
    Database::setSetting(
        'integrity_check_notification_policy',
        Notifier::encodePolicy(
            Notifier::parsePolicyPost($data, 'integrity_check_task', 'integrity_check', Notifier::getSettingPolicy('integrity_check_notification_policy', 'integrity_check')),
            'integrity_check'
        )
    );
    Database::setSetting(
        'maintenance_vacuum_notification_policy',
        Notifier::encodePolicy(
            Notifier::parsePolicyPost($data, 'db_vacuum_task', 'maintenance_vacuum', Notifier::getSettingPolicy('maintenance_vacuum_notification_policy', 'maintenance_vacuum')),
            'maintenance_vacuum'
        )
    );

    Auth::log('scheduler_settings_save', 'Planification globale mise a jour');
    jsonResponse([
        'success' => true,
        'message' => 'Planification globale enregistree.',
        'saved' => [
            'weekly_report_enabled' => Database::getSetting('weekly_report_enabled', '0'),
            'weekly_report_day' => Database::getSetting('weekly_report_day', '1'),
            'weekly_report_hour' => Database::getSetting('weekly_report_hour', '0'),
            'integrity_check_enabled' => Database::getSetting('integrity_check_enabled', '0'),
            'integrity_check_day' => Database::getSetting('integrity_check_day', '1'),
            'integrity_check_hour' => Database::getSetting('integrity_check_hour', '0'),
            'maintenance_vacuum_enabled' => Database::getSetting('maintenance_vacuum_enabled', '0'),
            'maintenance_vacuum_day' => Database::getSetting('maintenance_vacuum_day', '1'),
            'maintenance_vacuum_hour' => Database::getSetting('maintenance_vacuum_hour', '0'),
        ],
        'tasks' => SchedulerManager::getGlobalTasks(),
    ]);
}

jsonResponse(['error' => 'Action invalide'], 400);
