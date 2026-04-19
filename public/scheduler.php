<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('scheduler.manage');

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (($_POST['action'] ?? '') === 'save_scheduler') {
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
                Database::setSetting($field, isset($_POST[$field]) ? '1' : '0');
                continue;
            }

            Database::setSetting($field, trim((string) ($_POST[$field] ?? '')));
        }

        $integrityCheckDays = array_values(array_filter(array_map('trim', (array) ($_POST['integrity_check_days'] ?? []))));
        if (empty($integrityCheckDays)) {
            $integrityCheckDays = ['1'];
        }
        Database::setSetting('integrity_check_day', implode(',', $integrityCheckDays));

        $maintenanceVacuumDays = array_values(array_filter(array_map('trim', (array) ($_POST['maintenance_vacuum_days'] ?? []))));
        if (empty($maintenanceVacuumDays)) {
            $maintenanceVacuumDays = ['7'];
        }
        Database::setSetting('maintenance_vacuum_day', implode(',', $maintenanceVacuumDays));

        Database::setSetting(
            'weekly_report_notification_policy',
            Notifier::encodePolicy(
                Notifier::parsePolicyPost($_POST, 'weekly_report_task', 'weekly_report', Notifier::getSettingPolicy('weekly_report_notification_policy', 'weekly_report')),
                'weekly_report'
            )
        );
        Database::setSetting(
            'integrity_check_notification_policy',
            Notifier::encodePolicy(
                Notifier::parsePolicyPost($_POST, 'integrity_check_task', 'integrity_check', Notifier::getSettingPolicy('integrity_check_notification_policy', 'integrity_check')),
                'integrity_check'
            )
        );
        Database::setSetting(
            'maintenance_vacuum_notification_policy',
            Notifier::encodePolicy(
                Notifier::parsePolicyPost($_POST, 'db_vacuum_task', 'maintenance_vacuum', Notifier::getSettingPolicy('maintenance_vacuum_notification_policy', 'maintenance_vacuum')),
                'maintenance_vacuum'
            )
        );

        Auth::log('scheduler_settings_save', 'Planification globale mise a jour');
        $flash = ['type' => 'success', 'msg' => t('flash.scheduler.saved')];
    }
}

$globalTasks = SchedulerManager::getGlobalTasks();
$engineTasks = SchedulerManager::getEngineTasks();
$backupJobs = SchedulerManager::getBackupJobSchedules();
$copyJobs = SchedulerManager::getCopyJobSchedules();
$recentLogs = SchedulerManager::getRecentCronEntries(10);
$dayOptions = SchedulerManager::getDayOptions();
$cronEngine = SchedulerManager::getCronEngineStatus();
$workerCronEnabled = WorkerManager::isCronEnabled();

$enabledGlobalTasks = count(array_filter($globalTasks, fn(array $task): bool => !empty($task['enabled'])));
$scheduledBackupCount = count(array_filter($backupJobs, fn(array $job): bool => !empty($job['next_run'])));
$scheduledCopyCount = count(array_filter($copyJobs, fn(array $job): bool => !empty($job['next_run'])));
$scheduledJobCount = $scheduledBackupCount + $scheduledCopyCount + $enabledGlobalTasks;
$needsEngineWarning = $scheduledJobCount > 0 && !$cronEngine['active'];
$scheduleTimezoneLabel = SchedulerManager::getScheduleTimezoneLabel();
$serverTimezoneName = SchedulerManager::getServerTimezoneName();
$cronSystemUser = (string) ($cronEngine['system_user'] ?? SchedulerManager::getSystemUser());

function schedulerStatusBadge(?string $status): string {
    return match ($status) {
        'success', 'completed' => 'badge-green',
        'failed', 'dead_letter' => 'badge-red',
        'running' => 'badge-blue',
        'queued' => 'badge-yellow',
        default => 'badge-gray',
    };
}

$title = t('scheduler.page_title');
$subtitle = t('scheduler.page_subtitle');
$active = 'scheduler';

include 'layout_top.php';
?>
<style<?= cspNonceAttr() ?>>
@media (max-width: 640px) {
    .worker-explain-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php if ($needsEngineWarning): ?>
<div class="alert alert-warning" id="cron-engine-warning" style="margin-bottom:16px">
    <strong><?= t('scheduler.engine_warning_title') ?></strong>
    <?= t('scheduler.engine_warning_body') ?>
    <?= t('scheduler.engine_warning_user', [':user' => h($cronSystemUser)]) ?>
    <?php if ($cronEngine['supports_crontab']): ?>
    <?= t('scheduler.engine_warning_enable') ?>
    <?php else: ?>
    <?= t('scheduler.engine_warning_local') ?>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="alert alert-info" id="cron-engine-warning" style="margin-bottom:16px;display:none"></div>
<?php endif; ?>

<div class="card mb-4" style="background:linear-gradient(135deg, rgba(88,166,255,.08), rgba(63,185,80,.08));border-color:rgba(88,166,255,.25)">
    <div class="card-body" style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap">
        <div style="max-width:760px">
            <div style="font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--accent);margin-bottom:8px"><?= t('scheduler.single_entry_label') ?></div>
            <div style="font-size:20px;font-weight:700;margin-bottom:8px"><?= t('scheduler.single_entry_title') ?></div>
            <div style="font-size:13px;color:var(--text2);line-height:1.6">
                <?= t('scheduler.single_entry_desc') ?>
            </div>
            <div style="font-size:13px;color:var(--text2);line-height:1.6;margin-top:10px">
                <?= t('scheduler.timezone_display', [':tz' => '<code>' . h($scheduleTimezoneLabel) . '</code>']) ?>
                <?= t('scheduler.timezone_system', [':tz' => '<code>' . h($serverTimezoneName) . '</code>']) ?>
            </div>
        </div>
        <div style="min-width:320px;flex:1">
            <div style="font-size:12px;color:var(--text2);margin-bottom:6px"><?= t('scheduler.cron_line_label') ?></div>
            <div class="code-viewer" style="font-size:11px"><?= h($cronEngine['cron_line']) ?></div>
            <div style="font-size:12px;color:var(--text2);margin:10px 0 6px"><?= t('scheduler.cron_add_cmd_label') ?></div>
            <div class="code-viewer" id="cron-install-command" style="font-size:11px"><?= h($cronEngine['install_command']) ?></div>
            <div style="font-size:12px;color:var(--text2);margin-top:8px">
                <?= t('scheduler.cron_install_note', [':user' => h($cronSystemUser)]) ?>
            </div>
            <div style="font-size:12px;color:var(--text2);margin-top:8px">
                <?= t('scheduler.cron_install_detail') ?>
            </div>
            <div style="font-size:12px;color:var(--text2);margin-top:8px">
                <?= t('scheduler.cron_status_label') ?> <span id="cron-status-inline" class="badge <?= $cronEngine['active'] ? 'badge-green' : ($cronEngine['supports_crontab'] ? 'badge-gray' : 'badge-blue') ?>"><?= h($cronEngine['label']) ?></span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                <?php if ($cronEngine['supports_crontab']): ?>
                <button type="button" class="btn btn-sm btn-success" onclick="enableCron()"><?= t('scheduler.btn_enable_engine') ?></button>
                <button type="button" class="btn btn-sm" onclick="runCronNow('quick')"><?= t('scheduler.btn_test_config') ?></button>
                <?php else: ?>
                <button type="button" class="btn btn-sm" onclick="runCronNow('quick')"><?= t('scheduler.btn_test_config') ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="grid-4" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px">
    <div class="card">
        <div class="card-body">
            <div style="font-size:12px;color:var(--text2)"><?= t('scheduler.kpi_global_tasks') ?></div>
            <div style="font-size:28px;font-weight:700;margin-top:6px" id="enabled-global-task-count"><?= $enabledGlobalTasks ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="font-size:12px;color:var(--text2)"><?= t('scheduler.kpi_scheduled_backups') ?></div>
            <div style="font-size:28px;font-weight:700;margin-top:6px"><?= $scheduledBackupCount ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="font-size:12px;color:var(--text2)"><?= t('scheduler.kpi_scheduled_copies') ?></div>
            <div style="font-size:28px;font-weight:700;margin-top:6px"><?= $scheduledCopyCount ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="font-size:12px;color:var(--text2)"><?= t('scheduler.kpi_last_run') ?></div>
            <div style="font-size:15px;font-weight:600;margin-top:8px">
                <?php $lastCronRun = $engineTasks[0]['last_run'] ?? null; ?>
                <?= $lastCronRun ? h(formatDate($lastCronRun)) : t('common.never') ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <span><?= t('scheduler.engine_title') ?></span>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span id="cron-status-badge" class="badge <?= $cronEngine['active'] ? 'badge-green' : ($cronEngine['supports_crontab'] ? 'badge-gray' : 'badge-blue') ?>"><?= h($cronEngine['label']) ?></span>
            <button class="btn btn-sm btn-success" id="btn-enable-cron" onclick="enableCron()" style="display:none"><?= t('common.enable') ?></button>
            <button class="btn btn-sm btn-danger" id="btn-disable-cron" onclick="disableCron()" style="display:none"><?= t('common.disable') ?></button>
            <button class="btn btn-sm" onclick="runCronNow('manual')"><?= t('scheduler.btn_run_now') ?></button>
        </div>
    </div>
    <div class="card-body">
        <div style="font-size:13px;color:var(--text2);margin-bottom:10px">
            <?= t('scheduler.engine_desc1') ?>
        </div>
        <div style="font-size:13px;color:var(--text2);margin-bottom:10px">
            <?= t('scheduler.engine_desc2') ?>
        </div>
        <div id="cron-update-alert" style="display:<?= !empty($cronEngine['needs_update']) ? 'block' : 'none' ?>;margin-bottom:10px" class="alert alert-warning">
            <?= t('scheduler.cron_obsolete_warning') ?>
        </div>
        <div id="cron-line-display" class="code-viewer" style="display:none;font-size:11px"></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <span><?= t('scheduler.worker_title') ?></span>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span id="worker-cron-badge" class="badge <?= $workerCronEnabled ? 'badge-green' : 'badge-gray' ?>"><?= $workerCronEnabled ? t('common.active') : t('common.inactive') ?></span>
            <button class="btn btn-sm btn-success" id="btn-enable-worker-cron" onclick="enableWorkerCron()" style="<?= $workerCronEnabled ? 'display:none' : '' ?>"><?= t('common.enable') ?></button>
            <button class="btn btn-sm btn-danger" id="btn-disable-worker-cron" onclick="disableWorkerCron()" style="<?= $workerCronEnabled ? '' : 'display:none' ?>"><?= t('common.disable') ?></button>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px" class="worker-explain-grid">
            <div style="background:var(--bg2);border-radius:8px;padding:14px">
                <div style="font-weight:600;margin-bottom:6px;font-size:13px"><?= t('scheduler.without_worker_title') ?> <span class="badge badge-gray" style="font-size:10px;vertical-align:middle"><?= t('scheduler.without_worker_badge') ?></span></div>
                <div style="font-size:12px;color:var(--text2);line-height:1.7">
                    <?= t('scheduler.without_worker_desc') ?>
                </div>
            </div>
            <div style="background:var(--bg2);border-radius:8px;padding:14px">
                <div style="font-weight:600;margin-bottom:6px;font-size:13px"><?= t('scheduler.with_worker_title') ?> <span class="badge badge-blue" style="font-size:10px;vertical-align:middle"><?= t('scheduler.with_worker_badge') ?></span></div>
                <div style="font-size:12px;color:var(--text2);line-height:1.7">
                    <?= t('scheduler.with_worker_desc') ?>
                </div>
            </div>
        </div>
        <div id="worker-cron-line-display" class="code-viewer" style="font-size:11px;<?= $workerCronEnabled ? '' : 'display:none' ?>"><?= h(WorkerManager::getCronLine()) ?></div>
        <div id="worker-cron-install-hint" style="font-size:12px;color:var(--text2);margin-top:8px;<?= $workerCronEnabled ? '' : 'display:none' ?>">
            <?= t('scheduler.worker_cron_installed_hint') ?>
        </div>
        <div id="worker-cron-disabled-hint" style="font-size:12px;color:var(--text2);margin-top:8px;<?= $workerCronEnabled ? 'display:none' : '' ?>">
            <?= t('scheduler.worker_cron_disabled_hint') ?>
        </div>
    </div>
</div>

<form method="POST" class="card mb-4" id="scheduler-settings-form">
    <div class="card-header">
        <span><?= t('scheduler.global_tasks_title') ?></span>
        <button type="submit" class="btn btn-primary btn-sm"><?= t('common.save') ?></button>
    </div>
    <div class="card-body">
        <input type="hidden" name="action" value="save_scheduler">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="table-wrap" style="overflow:auto">
            <table class="table scheduler-global-table">
                <thead>
                    <tr>
                        <th><?= t('scheduler.col_task') ?></th>
                        <th><?= t('scheduler.col_active') ?></th>
                        <th><?= t('scheduler.col_days') ?></th>
                        <th><?= t('scheduler.col_hour') ?></th>
                        <th><?= t('scheduler.col_notifications') ?></th>
                        <th><?= t('scheduler.col_next_run') ?></th>
                        <th><?= t('scheduler.col_last_run') ?></th>
                        <th><?= t('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($globalTasks as $task): ?>
                    <?php $editorPrefix = $task['key'] . '_task'; ?>
                    <tr class="scheduler-task-main-row" data-task-key="<?= h($task['key']) ?>">
                        <td style="min-width:260px">
                            <div style="font-weight:600"><?= h($task['title']) ?></div>
                            <div style="font-size:12px;color:var(--text2);margin-top:4px"><?= h($task['description']) ?></div>
                        </td>
                        <td>
                            <label style="display:flex;align-items:center;justify-content:center">
                                <input type="checkbox"
                                       name="<?= h($task['enabled_key']) ?>"
                                       value="1"
                                       <?= $task['enabled'] ? 'checked' : '' ?>
                                       style="accent-color:var(--accent);width:16px;height:16px">
                            </label>
                        </td>
                        <td>
                            <?php if ($task['key'] === 'weekly_report'): ?>
                            <select name="<?= h($task['day_key']) ?>" class="form-control" style="min-width:120px">
                                <?php foreach ($dayOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= (int) $task['day'] === (int) $value ? 'selected' : '' ?>><?= h($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;min-width:180px">
                                <?php foreach ($dayOptions as $value => $label): ?>
                                <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-size:12px;padding:6px 8px;border:1px solid var(--border);border-radius:8px;white-space:nowrap">
                                    <input type="checkbox"
                                           name="<?= h($task['day_key']) ?>s[]"
                                           value="<?= h((string) $value) ?>"
                                           <?= in_array((int) $value, (array) ($task['days'] ?? []), true) ? 'checked' : '' ?>
                                           style="accent-color:var(--accent)">
                                    <?= h(SchedulerManager::getDayOptions()[(int) $value]) ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <select name="<?= h($task['hour_key']) ?>" class="form-control" style="min-width:110px">
                                <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                <option value="<?= $hour ?>" <?= (int) $task['hour'] === $hour ? 'selected' : '' ?>>
                                    <?= str_pad((string) $hour, 2, '0', STR_PAD_LEFT) ?>:00
                                </option>
                                <?php endfor; ?>
                            </select>
                        </td>
                        <td style="min-width:320px">
                            <div class="scheduler-notification-cell">
                                <div data-role="notification-summary" class="scheduler-notification-summary">
                                    <?= renderNotificationPolicySummary($task['notification_policy'], $task['notification_profile']) ?>
                                </div>
                                <button type="button"
                                        class="btn btn-sm scheduler-editor-toggle"
                                        data-role="notification-toggle"
                                        data-task-key="<?= h($task['key']) ?>"
                                        aria-expanded="false"
                                        aria-controls="task-editor-<?= h($task['key']) ?>"
                                        onclick="toggleTaskNotificationEditor('<?= h($task['key']) ?>')">
                                    <?= t('common.configure') ?>
                                </button>
                            </div>
                        </td>
                        <td style="font-size:12px" data-role="next-run">
                            <?= $task['next_run'] ? h(formatDate($task['next_run'])) : '<span class="text-muted">' . t('common.inactive') . '</span>' ?>
                        </td>
                        <td style="font-size:12px">
                            <?php if ($task['last_run']): ?>
                            <span class="badge <?= schedulerStatusBadge($task['last_status']) ?>"><?= h($task['last_status']) ?></span>
                            <?= h(formatDate($task['last_run'])) ?>
                            <?php else: ?>
                            <span class="text-muted"><?= t('common.never') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-2" style="flex-wrap:wrap">
                                <button type="button" class="btn btn-sm btn-success" onclick="runGlobalTask('<?= h($task['key']) ?>', '<?= h($task['title']) ?>')">
                                    <?= t('scheduler.btn_run') ?>
                                </button>
                                <button type="button" class="btn btn-sm" onclick='testSavedNotificationPolicy("<?= h($task["notification_profile"]) ?>", "<?= h(array_key_first($task["notification_policy"]["events"])) ?>", <?= h(json_encode($task["notification_policy"])) ?>, <?= h(json_encode($task["title"])) ?>)'>
                                    <?= t('common.test_notif') ?>
                                </button>
                                <?php if (!empty($task['last_output'])): ?>
                                <button type="button" class="btn btn-sm" onclick="showLog(<?= h(json_encode($task['last_output'])) ?>)">
                                    <?= t('common.logs') ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr id="task-editor-<?= h($task['key']) ?>" class="scheduler-task-editor-row" data-editor-for="<?= h($task['key']) ?>" hidden>
                        <td colspan="8">
                            <div class="scheduler-task-editor-shell">
                                <div class="scheduler-task-editor-header">
                                    <div>
                                        <div class="scheduler-task-editor-title"><?= t('scheduler.notifications_for_prefix') ?> <?= h($task['title']) ?></div>
                                        <div class="scheduler-task-editor-help"><?= t('scheduler.notification_editor_hint') ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm" onclick="toggleTaskNotificationEditor('<?= h($task['key']) ?>', false)"><?= t('common.close') ?></button>
                                </div>
                                <?= renderNotificationPolicyEditor($editorPrefix, $task['notification_profile'], $task['notification_policy']) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<div class="card mb-4">
    <div class="card-header"><?= t('scheduler.engine_tasks_title') ?></div>
    <div class="card-body">
        <div class="table-wrap" style="overflow:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= t('scheduler.col_task') ?></th>
                        <th><?= t('scheduler.col_cadence') ?></th>
                        <th><?= t('scheduler.col_last_run') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($engineTasks as $task): ?>
                    <tr>
                        <td style="min-width:280px">
                            <div style="font-weight:600"><?= h($task['title']) ?></div>
                            <div style="font-size:12px;color:var(--text2);margin-top:4px"><?= h($task['description']) ?></div>
                        </td>
                        <td><?= h($task['cadence']) ?></td>
                        <td style="font-size:12px">
                            <?php if ($task['last_run']): ?>
                            <span class="badge <?= schedulerStatusBadge($task['last_status']) ?>"><?= h((string) $task['last_status']) ?></span>
                            <?= h(formatDate($task['last_run'])) ?>
                            <?php else: ?>
                            <span class="text-muted"><?= t('common.never') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div class="card">
        <div class="card-header">
            <?= t('scheduler.backup_jobs_title') ?>
            <span class="badge badge-blue"><?= count($backupJobs) ?></span>
        </div>
        <div class="card-body table-wrap" style="overflow:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= t('common.name') ?></th>
                        <th><?= t('scheduler.col_schedule') ?></th>
                        <th><?= t('scheduler.col_next_run') ?></th>
                        <th><?= t('scheduler.col_last_run') ?></th>
                        <th><?= t('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backupJobs as $job): ?>
                    <tr>
                        <td style="min-width:180px">
                            <div style="font-weight:600"><?= h($job['name']) ?></div>
                            <div style="font-size:12px;color:var(--text2);margin-top:4px"><?= h($job['repo_name'] ?? t('scheduler.missing_repo')) ?></div>
                        </td>
                        <td><?= h($job['schedule_summary']) ?></td>
                        <td style="font-size:12px"><?= $job['next_run'] ? h(formatDate($job['next_run'])) : '<span class="text-muted">-</span>' ?></td>
                        <td style="font-size:12px">
                            <?php if (!empty($job['last_run'])): ?>
                            <span class="badge <?= schedulerStatusBadge($job['last_status'] ?? null) ?>"><?= h((string) $job['last_status']) ?></span>
                            <?= h(formatDate($job['last_run'])) ?>
                            <?php else: ?>
                            <span class="text-muted"><?= t('common.never') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-2" style="flex-wrap:wrap">
                                <button type="button" class="btn btn-sm btn-success" onclick="runBackupJob(<?= (int) $job['id'] ?>, this)"><?= t('scheduler.btn_run') ?></button>
                        <a href="<?= routePath('/backup_jobs.php') ?>" class="btn btn-sm"><?= t('scheduler.btn_manage') ?></a>
                                <?php if (!empty($job['last_output'])): ?>
                                <button type="button" class="btn btn-sm" onclick="showLog(<?= h(json_encode($job['last_output'])) ?>)"><?= t('common.logs') ?></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <?= t('scheduler.copy_jobs_title') ?>
            <span class="badge badge-blue"><?= count($copyJobs) ?></span>
        </div>
        <div class="card-body table-wrap" style="overflow:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= t('common.name') ?></th>
                        <th><?= t('scheduler.col_schedule') ?></th>
                        <th><?= t('scheduler.col_next_run') ?></th>
                        <th><?= t('scheduler.col_last_run') ?></th>
                        <th><?= t('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($copyJobs as $job): ?>
                    <tr>
                        <td style="min-width:180px">
                            <div style="font-weight:600"><?= h($job['name']) ?></div>
                            <div style="font-size:12px;color:var(--text2);margin-top:4px"><?= h($job['source_name'] ?? t('scheduler.missing_repo')) ?></div>
                        </td>
                        <td><?= h($job['schedule_summary']) ?></td>
                        <td style="font-size:12px"><?= $job['next_run'] ? h(formatDate($job['next_run'])) : '<span class="text-muted">-</span>' ?></td>
                        <td style="font-size:12px">
                            <?php if (!empty($job['last_run'])): ?>
                            <span class="badge <?= schedulerStatusBadge($job['last_status'] ?? null) ?>"><?= h((string) $job['last_status']) ?></span>
                            <?= h(formatDate($job['last_run'])) ?>
                            <?php else: ?>
                            <span class="text-muted"><?= t('common.never') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-2" style="flex-wrap:wrap">
                                <button type="button" class="btn btn-sm btn-success" onclick="runCopyJob(<?= (int) $job['id'] ?>, this)"><?= t('scheduler.btn_run') ?></button>
                        <a href="<?= routePath('/copy_jobs.php') ?>" class="btn btn-sm"><?= t('scheduler.btn_manage') ?></a>
                                <?php if (!empty($job['last_output'])): ?>
                                <button type="button" class="btn btn-sm" onclick="showLog(<?= h(json_encode($job['last_output'])) ?>)"><?= t('common.logs') ?></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header"><?= t('scheduler.history_title') ?></div>
    <div class="card-body table-wrap" style="padding:8px 12px;overflow:auto;max-height:260px">
        <table class="table" style="font-size:12px">
            <thead>
                <tr>
                    <th><?= t('common.date') ?></th>
                    <th><?= t('common.name') ?></th>
                    <th><?= t('common.status') ?></th>
                    <th><?= t('common.actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $logRow): ?>
                <tr>
                    <td style="font-size:12px"><?= h(formatDate($logRow['ran_at'])) ?></td>
                    <td><code><?= h($logRow['job_type']) ?></code></td>
                    <td><span class="badge <?= schedulerStatusBadge($logRow['status']) ?>"><?= h($logRow['status']) ?></span></td>
                    <td>
                        <?php if (!empty($logRow['output'])): ?>
                        <button type="button" class="btn btn-sm" onclick="showLog(<?= h(json_encode($logRow['output'])) ?>)"><?= t('scheduler.btn_view') ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modal-log" class="modal-overlay">
    <div class="modal" style="max-width:760px">
        <div class="modal-title"><?= t('common.logs') ?></div>
        <div class="code-viewer" id="log-content" style="max-height:320px"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button type="button" class="btn" onclick="document.getElementById('modal-log').classList.remove('show')"><?= t('common.close') ?></button>
        </div>
    </div>
</div>

<div id="modal-run" class="modal-overlay">
    <div class="modal" style="max-width:760px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <div class="modal-title" id="run-title" style="margin-bottom:0;flex:1"><?= t('scheduler.running_label') ?></div>
            <span id="run-spinner" class="spinner"></span>
        </div>
        <div style="font-size:11px;color:var(--text2);margin-bottom:8px"><?= t('scheduler.output_label') ?></div>
        <div class="code-viewer" id="run-output"
             style="min-height:160px;max-height:320px;overflow-y:auto;white-space:pre-wrap;word-break:break-all"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button type="button" class="btn" onclick="closeRunModal()"><?= t('common.close') ?></button>
        </div>
    </div>
</div>

<script<?= cspNonceAttr() ?>>
function showLog(output) {
    document.getElementById('log-content').textContent = output || '';
    document.getElementById('modal-log').classList.add('show');
}

function openRunModal(title) {
    document.getElementById('run-title').textContent = title;
    document.getElementById('run-output').textContent = '';
    document.getElementById('run-output').style.color = 'var(--text)';
    document.getElementById('run-spinner').style.display = 'inline-block';
    document.getElementById('modal-run').classList.add('show');
}

function closeRunModal() {
    document.getElementById('modal-run').classList.remove('show');
}

function finishRunModal(title, output, success) {
    document.getElementById('run-title').textContent = title;
    document.getElementById('run-output').textContent = output || '';
    document.getElementById('run-output').style.color = success ? 'var(--green)' : 'var(--red)';
    document.getElementById('run-spinner').style.display = 'none';
}

function renderCronEngineState(res) {
    const badge = document.getElementById('cron-status-badge');
    const inlineBadge = document.getElementById('cron-status-inline');
    const btnEnable = document.getElementById('btn-enable-cron');
    const btnDisable = document.getElementById('btn-disable-cron');
    const lineEl = document.getElementById('cron-line-display');
    const installCommandEl = document.getElementById('cron-install-command');
    const systemUserEl = document.getElementById('cron-system-user');
    const warningEl = document.getElementById('cron-engine-warning');
    const supportsCrontab = !!res.supports_crontab;
    const scheduledCount = <?= (int) $scheduledJobCount ?>;
    const systemUser = res.system_user || '<?= h(t('scheduler.js.web_user_default')) ?>';

    let badgeClass = 'badge badge-gray';
    let badgeLabel = '<?= h(t('common.inactive')) ?>';

    if (!supportsCrontab) {
        badgeClass = 'badge badge-blue';
        badgeLabel = '<?= h(t('scheduler.js.manual_local_mode')) ?>';
        btnEnable.style.display = 'none';
        btnDisable.style.display = 'none';
    } else if (res.active && res.needs_update) {
        badgeClass = 'badge badge-yellow';
        badgeLabel = '<?= h(t('scheduler.js.update_required')) ?>';
        btnEnable.style.display = 'none';
        btnDisable.style.display = 'inline-flex';
        document.getElementById('cron-update-alert').style.display = 'block';
    } else if (res.active) {
        badgeClass = 'badge badge-green';
        badgeLabel = '<?= h(t('common.active')) ?>';
        btnEnable.style.display = 'none';
        btnDisable.style.display = 'inline-flex';
        document.getElementById('cron-update-alert').style.display = 'none';
    } else {
        badgeClass = 'badge badge-gray';
        badgeLabel = '<?= h(t('common.inactive')) ?>';
        btnEnable.style.display = 'inline-flex';
        btnDisable.style.display = 'none';
    }

    badge.className = badgeClass;
    badge.textContent = badgeLabel;
    inlineBadge.className = badgeClass;
    inlineBadge.textContent = badgeLabel;

    lineEl.style.display = 'block';
    lineEl.textContent = res.cron_line || '';
    if (installCommandEl) {
        installCommandEl.textContent = res.install_command || '';
    }
    if (systemUserEl) {
        systemUserEl.textContent = systemUser;
    }

    if (scheduledCount > 0 && !res.active) {
        warningEl.style.display = 'block';
        warningEl.className = supportsCrontab ? 'alert alert-warning' : 'alert alert-danger';
        warningEl.innerHTML = supportsCrontab
            ? `<strong><?= h(t('scheduler.js.warning_inactive_strong')) ?></strong> <?= h(t('scheduler.js.warning_inactive_hint')) ?> <code>${systemUser}</code>.`
            : `<strong><?= h(t('scheduler.js.warning_no_cron_strong')) ?></strong> <?= h(t('scheduler.js.warning_no_cron_hint')) ?> <code>php /.../public/cron.php</code> <?= h(t('scheduler.js.warning_no_cron_suffix')) ?> <code>${systemUser}</code>, <?= h(t('scheduler.js.warning_no_cron_end')) ?>.`;
    } else if (scheduledCount > 0 && res.active) {
        warningEl.style.display = 'block';
        warningEl.className = 'alert alert-success';
        warningEl.innerHTML = `<strong><?= h(t('scheduler.js.warning_active_strong')) ?></strong> <?= h(t('scheduler.js.warning_active_hint')) ?> <code>${systemUser}</code>.`;
    } else {
        warningEl.style.display = 'none';
    }
}

async function loadCronStatus() {
    const res = await apiPost('/api/manage_cron.php', { action: 'status' });
    renderCronEngineState(res);
}

async function enableCron() {
    const res = await apiPost('/api/manage_cron.php', { action: 'enable' });
    toast(res.output || (res.success ? '<?= h(t('scheduler.js.cron_enabled')) ?>' : '<?= h(t('common.error')) ?>'), res.success ? 'success' : 'error');
    if (res.success) {
        loadCronStatus();
    }
}

async function disableCron() {
    const confirmed = await window.confirmActionAsync('<?= h(t('scheduler.js.disable_cron_confirm')) ?>');
    if (!confirmed) return;
    const res = await apiPost('/api/manage_cron.php', { action: 'disable' });
    toast(res.output || (res.success ? '<?= h(t('scheduler.js.cron_disabled')) ?>' : '<?= h(t('common.error')) ?>'), res.success ? 'success' : 'error');
    if (res.success) {
        loadCronStatus();
    }
}

async function enableWorkerCron() {
    const res = await apiPost('/api/manage_worker.php', { action: 'install_cron', name: 'default', limit: 3, stale_minutes: 30 });
    toast(res.message || (res.success ? '<?= h(t('scheduler.js.worker_enabled')) ?>' : '<?= h(t('common.error')) ?>'), res.success ? 'success' : 'error');
    if (res.success) {
        document.getElementById('worker-cron-badge').className = 'badge badge-green';
        document.getElementById('worker-cron-badge').textContent = '<?= h(t('common.active')) ?>';
        document.getElementById('btn-enable-worker-cron').style.display = 'none';
        document.getElementById('btn-disable-worker-cron').style.display = '';
        document.getElementById('worker-cron-line-display').style.display = '';
        document.getElementById('worker-cron-install-hint').style.display = '';
        document.getElementById('worker-cron-disabled-hint').style.display = 'none';
    }
}

async function disableWorkerCron() {
    const confirmed = await window.confirmActionAsync('<?= h(t('scheduler.js.disable_worker_confirm')) ?>');
    if (!confirmed) return;
    const res = await apiPost('/api/manage_worker.php', { action: 'uninstall_cron', name: 'default' });
    toast(res.message || (res.success ? '<?= h(t('scheduler.js.worker_disabled')) ?>' : '<?= h(t('common.error')) ?>'), res.success ? 'success' : 'error');
    if (res.success) {
        document.getElementById('worker-cron-badge').className = 'badge badge-gray';
        document.getElementById('worker-cron-badge').textContent = '<?= h(t('common.inactive')) ?>';
        document.getElementById('btn-enable-worker-cron').style.display = '';
        document.getElementById('btn-disable-worker-cron').style.display = 'none';
        document.getElementById('worker-cron-line-display').style.display = 'none';
        document.getElementById('worker-cron-install-hint').style.display = 'none';
        document.getElementById('worker-cron-disabled-hint').style.display = '';
    }
}

async function runCronNow(mode = 'manual') {
    const quickTest = mode === 'quick';
    const diagnostic = mode === 'diagnostic';
    openRunModal(
        quickTest
            ? '<?= h(t('scheduler.js.quick_test_running')) ?>'
            : (diagnostic ? '<?= h(t('scheduler.js.diagnostic_running')) ?>' : '<?= h(t('scheduler.js.cycle_running')) ?>')
    );
    const res = await apiPost('/api/manage_cron.php', { action: 'run_now', mode });
    if (!res.run_id) {
        finishRunModal(
            quickTest
                ? '<?= h(t('scheduler.js.quick_test_error')) ?>'
                : (diagnostic ? '<?= h(t('scheduler.js.diagnostic_error')) ?>' : '<?= h(t('scheduler.js.cycle_error')) ?>'),
            res.error || '<?= h(t('scheduler.js.start_impossible')) ?>',
            false
        );
        return;
    }

    let offsetBytes = 0;
    let offset = 0;
    const outputEl = document.getElementById('run-output');

    const poll = async () => {
        try {
            const log = await apiPost('/api/poll_cron_log.php', { run_id: res.run_id, offset, last_offset_bytes: offsetBytes });

            if (log.lines && log.lines.length > 0) {
                outputEl.textContent += log.lines.join('\n') + '\n';
                outputEl.scrollTop = outputEl.scrollHeight;
            }

            if (Number.isFinite(Number(log.next_offset_bytes))) {
                offsetBytes = Number(log.next_offset_bytes);
            }
            if (Number.isFinite(Number(log.offset))) {
                offset = Number(log.offset);
            }

            if (log.done) {
                finishRunModal(
                    log.status === 'success'
                        ? (quickTest ? '<?= h(t('scheduler.js.quick_test_done')) ?>' : (diagnostic ? '<?= h(t('scheduler.js.diagnostic_done')) ?>' : '<?= h(t('scheduler.js.cycle_done')) ?>'))
                        : (quickTest ? '<?= h(t('scheduler.js.quick_test_error')) ?>' : (diagnostic ? '<?= h(t('scheduler.js.diagnostic_error')) ?>' : '<?= h(t('scheduler.js.cycle_error')) ?>')),
                    outputEl.textContent || '<?= h(t('scheduler.js.exec_done')) ?>',
                    log.status === 'success'
                );
                return;
            }

            setTimeout(poll, 1500);
        } catch (error) {
            setTimeout(poll, 3000);
        }
    };

    setTimeout(poll, 400);
}

async function runGlobalTask(taskKey, title) {
    openRunModal(title + ' <?= h(t('scheduler.js.running_suffix')) ?>');
    const res = await apiPost('/api/manage_scheduler.php', { action: 'run_global_task', task: taskKey });
    if (!res.run_id) {
        finishRunModal('<?= h(t('scheduler.js.error_title_prefix')) ?>' + title, res.error || '<?= h(t('scheduler.js.start_impossible')) ?>', false);
        return;
    }

    let offsetBytes = 0;
    let offset = 0;
    const outputEl = document.getElementById('run-output');

    const poll = async () => {
        try {
            const log = await apiPost('/api/poll_scheduler_task_log.php', { run_id: res.run_id, offset, last_offset_bytes: offsetBytes });

            if (log.lines && log.lines.length > 0) {
                outputEl.textContent += log.lines.join('\n') + '\n';
                outputEl.scrollTop = outputEl.scrollHeight;
            }

            if (Number.isFinite(Number(log.next_offset_bytes))) {
                offsetBytes = Number(log.next_offset_bytes);
            }
            if (Number.isFinite(Number(log.offset))) {
                offset = Number(log.offset);
            }

            if (log.done) {
                finishRunModal(
                    log.status === 'success' ? (title + ' <?= h(t('scheduler.js.done_suffix')) ?>') : ('<?= h(t('scheduler.js.error_title_prefix')) ?>' + title),
                    outputEl.textContent || '<?= h(t('scheduler.js.exec_done')) ?>',
                    log.status === 'success'
                );
                return;
            }

            setTimeout(poll, 1500);
        } catch (error) {
            setTimeout(poll, 3000);
        }
    };

    setTimeout(poll, 400);
}

async function startBackgroundRun(startUrl, pollUrl, payload, label, btn) {
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';

    openRunModal(label + ' <?= h(t('scheduler.js.running_suffix')) ?>');

    try {
        const res = await apiPost(startUrl, payload);
        if (!res.run_id) {
            finishRunModal('<?= h(t('scheduler.js.start_error')) ?>', res.error || '<?= h(t('scheduler.js.start_impossible')) ?>', false);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            return;
        }

        let offsetBytes = 0;
        let offset = 0;
        const outputEl = document.getElementById('run-output');

        const poll = async () => {
            try {
                const log = await apiPost(pollUrl, { run_id: res.run_id, offset, last_offset_bytes: offsetBytes });

                if (log.lines && log.lines.length > 0) {
                    outputEl.textContent += log.lines.join('\n') + '\n';
                    outputEl.scrollTop = outputEl.scrollHeight;
                }

                if (Number.isFinite(Number(log.next_offset_bytes))) {
                    offsetBytes = Number(log.next_offset_bytes);
                }
                if (Number.isFinite(Number(log.offset))) {
                    offset = Number(log.offset);
                }

                if (log.done) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    finishRunModal(
                        log.status === 'success' ? (label + ' <?= h(t('scheduler.js.done_suffix')) ?>') : ('<?= h(t('scheduler.js.error_during_prefix')) ?>' + label.toLowerCase()),
                        outputEl.textContent || '<?= h(t('scheduler.js.exec_done')) ?>',
                        log.status === 'success'
                    );
                    return;
                }

                setTimeout(poll, 1500);
            } catch (error) {
                setTimeout(poll, 3000);
            }
        };

        setTimeout(poll, 500);
    } catch (error) {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        finishRunModal('<?= h(t('common.error')) ?>', error.message || '<?= h(t('scheduler.js.exec_impossible')) ?>', false);
    }
}

function runBackupJob(jobId, btn) {
    startBackgroundRun('/api/run_backup_job.php', '/api/poll_backup_log.php', { job_id: jobId }, '<?= h(t('scheduler.js.backup_label')) ?>', btn);
}

function runCopyJob(jobId, btn) {
    startBackgroundRun('/api/run_copy_job.php', '/api/poll_copy_log.php', { job_id: jobId }, '<?= h(t('scheduler.js.copy_label')) ?>', btn);
}

function renderSchedulerTasks(tasks) {
    if (!Array.isArray(tasks)) {
        return;
    }

    let enabledCount = 0;

    tasks.forEach((task) => {
        if (task.enabled) {
            enabledCount += 1;
        }

        const row = document.querySelector(`[data-task-key="${task.key}"]`);
        if (!row) {
            return;
        }

        const nextRunCell = row.querySelector('[data-role="next-run"]');
        if (nextRunCell) {
            nextRunCell.innerHTML = task.next_run
                ? (window.formatAppDateTime ? window.formatAppDateTime(task.next_run) : task.next_run)
                : '<span class="text-muted"><?= h(t('common.inactive')) ?></span>';
        }

        const notificationSummary = row.querySelector('[data-role="notification-summary"]');
        if (notificationSummary && Array.isArray(task.notification_summary)) {
            notificationSummary.innerHTML = renderPolicySummary(task.notification_summary);
        }
    });

    const enabledCountEl = document.getElementById('enabled-global-task-count');
    if (enabledCountEl) {
        enabledCountEl.textContent = String(enabledCount);
    }
}

function renderPolicySummary(lines) {
    if (!Array.isArray(lines) || lines.length === 0) {
        return '<div class="policy-summary policy-summary-notification"><span class="policy-chip policy-chip-gray"><?= h(t('scheduler.js.no_channel')) ?></span></div>';
    }

    const chips = lines.map((line) => {
        const tone = String(line).startsWith('Global') ? 'policy-chip-blue' : 'policy-chip-gray';
        return `<span class="policy-chip ${tone}">${escapeHtml(String(line))}</span>`;
    }).join('');

    return `<div class="policy-summary policy-summary-notification">${chips}</div>`;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function toggleTaskNotificationEditor(taskKey, forceOpen = null) {
    document.querySelectorAll('.scheduler-task-editor-row').forEach((editorRow) => {
        const key = editorRow.getAttribute('data-editor-for');
        const toggle = document.querySelector(`[data-role="notification-toggle"][data-task-key="${key}"]`);
        const shouldOpen = key === taskKey
            ? (forceOpen === null ? editorRow.hidden : forceOpen)
            : false;

        editorRow.hidden = !shouldOpen;
        if (toggle) {
            toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            toggle.textContent = shouldOpen ? '<?= h(t('scheduler.js.hide')) ?>' : '<?= h(t('common.configure')) ?>';
        }
    });
}

const schedulerSettingsForm = document.getElementById('scheduler-settings-form');
if (schedulerSettingsForm) {
    schedulerSettingsForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitButton = schedulerSettingsForm.querySelector('button[type="submit"]');
        const originalHtml = submitButton ? submitButton.innerHTML : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner"></span>';
        }

        try {
            const formData = new FormData(schedulerSettingsForm);
            const payload = {};
            formData.forEach((value, key) => {
                const normalizedKey = key.endsWith('[]') ? key.slice(0, -2) : key;
                if (Object.prototype.hasOwnProperty.call(payload, normalizedKey)) {
                    if (Array.isArray(payload[normalizedKey])) {
                        payload[normalizedKey].push(value);
                    } else {
                        payload[normalizedKey] = [payload[normalizedKey], value];
                    }
                } else {
                    payload[normalizedKey] = value;
                }
            });
            payload.action = 'save_scheduler';

            const res = await apiPost('/api/manage_scheduler.php', payload);
            if (!res.success) {
                throw new Error(res.error || '<?= h(t('scheduler.js.save_impossible')) ?>');
            }

            renderSchedulerTasks(res.tasks || []);
            toast(res.message || '<?= h(t('scheduler.js.save_success')) ?>', 'success');
        } catch (error) {
            toast(error.message || '<?= h(t('scheduler.js.save_error')) ?>', 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalHtml;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', loadCronStatus);

window.closeRunModal = closeRunModal;
window.showLog = showLog;
window.runCronNow = runCronNow;
window.enableCron = enableCron;
window.disableCron = disableCron;
window.enableWorkerCron = enableWorkerCron;
window.disableWorkerCron = disableWorkerCron;
window.runBackupJob = runBackupJob;
window.runCopyJob = runCopyJob;
window.runGlobalTask = runGlobalTask;
window.toggleTaskNotificationEditor = toggleTaskNotificationEditor;
</script>

<?php include 'layout_bottom.php'; ?>
