<?php
// =============================================================================
// SchedulerHandler.php — /api/v1/scheduler
// =============================================================================

class SchedulerHandler {

    public static function tasks(array $args): void {
        ApiAuth::requireScope('scheduler:read');
        ApiResponse::ok([
            'global' => SchedulerManager::getGlobalTasks(),
            'engine' => SchedulerManager::getEngineTasks(),
            'cron_engine' => SchedulerManager::getCronEngineStatus(),
            'timezone' => SchedulerManager::getScheduleTimezoneName(),
            'timezone_label' => SchedulerManager::getScheduleTimezoneLabel(),
        ]);
    }

    public static function backupSchedules(array $args): void {
        ApiAuth::requireScope('scheduler:read');
        ApiResponse::ok(SchedulerManager::getBackupJobSchedules());
    }

    public static function copySchedules(array $args): void {
        ApiAuth::requireScope('scheduler:read');
        ApiResponse::ok(SchedulerManager::getCopyJobSchedules());
    }

    public static function cronLog(array $args): void {
        ApiAuth::requireScope('scheduler:read');
        $limit = ApiRequest::queryInt('limit', 30);
        $limit = max(1, min(200, $limit));
        ApiResponse::ok(SchedulerManager::getRecentCronEntries($limit));
    }

    public static function runTask(array $args): void {
        ApiAuth::requireScope('scheduler:write');
        $key = (string) ($args['key'] ?? '');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $result = match ($key) {
            'weekly_report' => SchedulerManager::runWeeklyReportTask(true),
            'integrity_check' => SchedulerManager::runIntegrityCheckTask(true),
            'db_vacuum' => SchedulerManager::runDbVacuumTask(true),
            default => null,
        };
        if ($result === null) {
            ApiResponse::error(404, 'unknown_task', 'Tache scheduler inconnue');
        }
        ApiResponse::ok($result);
    }
}
