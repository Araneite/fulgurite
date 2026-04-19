<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('backup_jobs.manage');

$flash = null;
$db    = Database::getInstance();
$defaultBackupNotificationPolicy = Notifier::decodePolicy('', 'backup_job', [
    'notify_on_failure' => AppConfig::getBool('backup_job_default_notify_on_failure', true) ? 1 : 0,
]);
$defaultBackupRetryPolicy = JobRetryPolicy::defaultEntityPolicy();

function validateSelectedHookScript(?int $scriptId, ?int $hostId): ?string {
    if (!$scriptId) {
        return null;
    }
    $script = HookScriptManager::getById($scriptId);
    if (!$script || ($script['status'] ?? 'active') !== 'active') {
        return t('flash.backup_jobs.script_not_found');
    }
    $jobMode = $hostId ? 'remote' : 'local';
    $scope = (string) ($script['execution_scope'] ?? 'both');
    if ($scope !== 'both' && $scope !== $jobMode) {
        return t('flash.backup_jobs.script_incompatible', ['name' => $script['name']]);
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($_POST['action'] === 'add') {
        $name    = trim($_POST['name'] ?? '');
        $repoId  = (int) ($_POST['repo_id'] ?? 0);
        $rawPaths = trim($_POST['source_paths'] ?? '');
        $srcPaths = array_values(array_filter(
            array_map('trim', preg_split('/\r?\n/', $rawPaths))
        ));
        $rawTags  = trim($_POST['tags'] ?? '');
        $tags     = $rawTags ? array_values(array_filter(
            array_map('trim', explode(',', $rawTags))
        )) : [];
        $rawExc   = trim($_POST['excludes'] ?? '');
        $excludes = array_values(array_filter(
            array_map('trim', preg_split('/\r?\n/', $rawExc))
        ));
        $desc     = trim($_POST['description'] ?? '');
        $defaultScheduleDays = AppConfig::getCsvValues('backup_job_default_schedule_days', '1');
        if (empty($defaultScheduleDays)) {
            $defaultScheduleDays = ['1'];
        }
        $schedEn  = isset($_POST['schedule_enabled']) ? 1 : 0;
        $schedH   = (int) ($_POST['schedule_hour'] ?? AppConfig::getInt('backup_job_default_schedule_hour', 2, 0, 23));
        $schedD   = implode(',', $_POST['schedule_days'] ?? $defaultScheduleDays);
        $notificationPolicy = Notifier::encodePolicy(
            Notifier::parsePolicyPost($_POST, 'backup_add', 'backup_job', $defaultBackupNotificationPolicy),
            'backup_job'
        );
        $retryPolicy = JobRetryPolicy::encodePolicy(
            JobRetryPolicy::parsePolicyPost($_POST, 'backup_add', $defaultBackupRetryPolicy),
            true
        );
        $notify   = Notifier::policyHasChannels(json_decode($notificationPolicy, true) ?: [], 'backup_job') ? 1 : 0;
        $hostId           = ($_POST['host_id'] ?? '') !== '' ? (int) $_POST['host_id'] : null;
        $remoteRepoPath   = trim($_POST['remote_repo_path'] ?? '');
        $hostnameOverride = trim($_POST['hostname_override'] ?? '');

        $retEn       = isset($_POST['retention_enabled']) ? 1 : 0;
        $retLast     = max(0, (int)($_POST['retention_keep_last'] ?? AppConfig::getInt('backup_job_default_retention_keep_last', 0, 0, 1000)));
        $retDaily    = max(0, (int)($_POST['retention_keep_daily'] ?? AppConfig::getInt('backup_job_default_retention_keep_daily', 0, 0, 1000)));
        $retWeekly   = max(0, (int)($_POST['retention_keep_weekly'] ?? AppConfig::getInt('backup_job_default_retention_keep_weekly', 0, 0, 1000)));
        $retMonthly  = max(0, (int)($_POST['retention_keep_monthly'] ?? AppConfig::getInt('backup_job_default_retention_keep_monthly', 0, 0, 1000)));
        $retYearly   = max(0, (int)($_POST['retention_keep_yearly'] ?? AppConfig::getInt('backup_job_default_retention_keep_yearly', 0, 0, 1000)));
        $retPrune    = isset($_POST['retention_prune']) ? 1 : (AppConfig::getBool('backup_job_default_retention_prune', true) ? 1 : 0);
        $preHookScriptId  = max(0, (int) ($_POST['pre_hook_script_id'] ?? 0));
        $postHookScriptId = max(0, (int) ($_POST['post_hook_script_id'] ?? 0));

        if ($name && $repoId && !empty($srcPaths)) {
            $hookError = validateSelectedHookScript($preHookScriptId ?: null, $hostId)
                ?? validateSelectedHookScript($postHookScriptId ?: null, $hostId);
            if (!Auth::canAccessRepoId($repoId)) {
                $flash = ['type' => 'danger', 'msg' => t('flash.backup_jobs.repo_access_denied')];
            } elseif ($hostId !== null && !Auth::canAccessHostId($hostId)) {
                $flash = ['type' => 'danger', 'msg' => t('flash.backup_jobs.host_access_denied')];
            } elseif ($hookError !== null) {
                $flash = ['type' => 'danger', 'msg' => $hookError];
            } else {
            ProvisioningManager::createBackupJob([
                'name' => $name,
                'repo_id' => $repoId,
                'source_paths' => $srcPaths,
                'tags' => $tags,
                'excludes' => $excludes,
                'description' => $desc,
                'schedule_enabled' => $schedEn,
                'schedule_hour' => $schedH,
                'schedule_days' => $schedD,
                'notify_on_failure' => $notify,
                'host_id' => $hostId,
                'remote_repo_path' => $remoteRepoPath ?: null,
                'hostname_override' => $hostnameOverride ?: null,
                'retention_enabled' => $retEn,
                'retention_keep_last' => $retLast,
                'retention_keep_daily' => $retDaily,
                'retention_keep_weekly' => $retWeekly,
                'retention_keep_monthly' => $retMonthly,
                'retention_keep_yearly' => $retYearly,
                'retention_prune' => $retPrune,
                'pre_hook_script_id' => $preHookScriptId ?: null,
                'post_hook_script_id' => $postHookScriptId ?: null,
                'notification_policy' => $notificationPolicy,
                'retry_policy' => $retryPolicy,
            ]);
            $flash = ['type' => 'success', 'msg' => t('flash.backup_jobs.created', ['name' => $name])];
            }
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.backup_jobs.name_repo_paths_required')];
        }
    }

    if ($_POST['action'] === 'edit') {
        $id      = (int) ($_POST['job_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $rawPaths = trim($_POST['source_paths'] ?? '');
        $srcPaths = array_values(array_filter(
            array_map('trim', preg_split('/\r?\n/', $rawPaths))
        ));
        $rawTags  = trim($_POST['tags'] ?? '');
        $tags     = $rawTags ? array_values(array_filter(
            array_map('trim', explode(',', $rawTags))
        )) : [];
        $rawExc   = trim($_POST['excludes'] ?? '');
        $excludes = array_values(array_filter(
            array_map('trim', preg_split('/\r?\n/', $rawExc))
        ));
        $desc     = trim($_POST['description'] ?? '');
        $schedEn  = isset($_POST['schedule_enabled']) ? 1 : 0;
        $schedH   = (int) ($_POST['schedule_hour'] ?? 2);
        $schedD   = implode(',', $_POST['schedule_days'] ?? ['1']);
        $notificationPolicy = Notifier::encodePolicy(
            Notifier::parsePolicyPost($_POST, 'backup_edit', 'backup_job', $defaultBackupNotificationPolicy),
            'backup_job'
        );
        $retryPolicy = JobRetryPolicy::encodePolicy(
            JobRetryPolicy::parsePolicyPost($_POST, 'backup_edit', $defaultBackupRetryPolicy),
            true
        );
        $notify   = Notifier::policyHasChannels(json_decode($notificationPolicy, true) ?: [], 'backup_job') ? 1 : 0;
        $hostId           = ($_POST['host_id'] ?? '') !== '' ? (int) $_POST['host_id'] : null;
        $remoteRepoPath   = trim($_POST['remote_repo_path'] ?? '');
        $hostnameOverride = trim($_POST['hostname_override'] ?? '');

        $retEn       = isset($_POST['retention_enabled']) ? 1 : 0;
        $retLast     = max(0, (int)($_POST['retention_keep_last']    ?? 0));
        $retDaily    = max(0, (int)($_POST['retention_keep_daily']   ?? 0));
        $retWeekly   = max(0, (int)($_POST['retention_keep_weekly']  ?? 0));
        $retMonthly  = max(0, (int)($_POST['retention_keep_monthly'] ?? 0));
        $retYearly   = max(0, (int)($_POST['retention_keep_yearly']  ?? 0));
        $retPrune    = isset($_POST['retention_prune']) ? 1 : 0;
        $preHookScriptId  = max(0, (int) ($_POST['pre_hook_script_id'] ?? 0));
        $postHookScriptId = max(0, (int) ($_POST['post_hook_script_id'] ?? 0));
        $existingJob = $id ? BackupJobManager::getById($id) : null;

        if ($id && $name && !empty($srcPaths)) {
            $hookError = validateSelectedHookScript($preHookScriptId ?: null, $hostId)
                ?? validateSelectedHookScript($postHookScriptId ?: null, $hostId);
            if (!$existingJob || !Auth::canAccessRepoId((int) ($existingJob['repo_id'] ?? 0)) || (!empty($existingJob['host_id']) && !Auth::canAccessHostId((int) $existingJob['host_id']))) {
                $flash = ['type' => 'danger', 'msg' => t('flash.backup_jobs.job_access_denied')];
            } elseif ($hostId !== null && !Auth::canAccessHostId($hostId)) {
                $flash = ['type' => 'danger', 'msg' => t('flash.backup_jobs.host_access_denied')];
            } elseif ($hookError !== null) {
                $flash = ['type' => 'danger', 'msg' => $hookError];
            } else {
            BackupJobManager::update($id, [
                'name'                  => $name,
                'source_paths'          => $srcPaths,
                'tags'                  => $tags,
                'excludes'              => $excludes,
                'description'           => $desc,
                'schedule_enabled'      => $schedEn,
                'schedule_hour'         => $schedH,
                'schedule_days'         => $schedD,
                'notify_on_failure'     => $notify,
                'notification_policy'   => $notificationPolicy,
                'retry_policy'          => $retryPolicy,
                'host_id'               => $hostId,
                'remote_repo_path'      => $remoteRepoPath ?: null,
                'hostname_override'     => $hostnameOverride ?: null,
                'retention_enabled'     => $retEn,
                'retention_keep_last'   => $retLast,
                'retention_keep_daily'  => $retDaily,
                'retention_keep_weekly' => $retWeekly,
                'retention_keep_monthly'=> $retMonthly,
                'retention_keep_yearly' => $retYearly,
                'retention_prune'       => $retPrune,
                'pre_hook_script_id'    => $preHookScriptId ?: null,
                'post_hook_script_id'   => $postHookScriptId ?: null,
            ]);
            Auth::log('backup_job_edit', "Job backup modifié: $name");
            $flash = ['type' => 'success', 'msg' => t('flash.backup_jobs.updated_named', ['name' => $name])];
            }
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.backup_jobs.name_paths_required')];
        }
    }

    if ($_POST['action'] === 'delete') {
        $id  = (int) ($_POST['job_id'] ?? 0);
        $job = BackupJobManager::getById($id);
        if ($job && Auth::canAccessRepoId((int) ($job['repo_id'] ?? 0)) && (empty($job['host_id']) || Auth::canAccessHostId((int) $job['host_id']))) {
            BackupJobManager::delete($id);
            Auth::log('backup_job_delete', "Job backup supprimé: {$job['name']}");
            $flash = ['type' => 'success', 'msg' => t('flash.backup_jobs.deleted')];
        }
    }
}

$jobs     = array_values(array_filter(BackupJobManager::getAll(), static fn(array $job): bool => Auth::canAccessRepoId((int) ($job['repo_id'] ?? 0)) && (empty($job['host_id']) || Auth::canAccessHostId((int) $job['host_id']))));
$repos    = Auth::filterAccessibleRepos(RepoManager::getAll());
$hosts    = Auth::filterAccessibleHosts(HostManager::getAll());
$cronEngine = SchedulerManager::getCronEngineStatus();
$scheduledJobs = count(array_filter($jobs, fn(array $job): bool => (int) ($job['schedule_enabled'] ?? 0) === 1));
$cronLogs = $db->query("
    SELECT * FROM cron_log WHERE job_type = 'backup' ORDER BY ran_at DESC LIMIT 10
")->fetchAll();
$approvedScripts = HookScriptManager::getSelectable();
$scheduleTimezoneLabel = SchedulerManager::getScheduleTimezoneLabel();
$serverTimezoneName = SchedulerManager::getServerTimezoneName();
$addDefaults = [
    'notify_on_failure' => AppConfig::getBool('backup_job_default_notify_on_failure', true),
    'retention_enabled' => AppConfig::getBool('backup_job_default_retention_enabled', false),
    'retention_keep_last' => AppConfig::getInt('backup_job_default_retention_keep_last', 0, 0, 1000),
    'retention_keep_daily' => AppConfig::getInt('backup_job_default_retention_keep_daily', 0, 0, 1000),
    'retention_keep_weekly' => AppConfig::getInt('backup_job_default_retention_keep_weekly', 0, 0, 1000),
    'retention_keep_monthly' => AppConfig::getInt('backup_job_default_retention_keep_monthly', 0, 0, 1000),
    'retention_keep_yearly' => AppConfig::getInt('backup_job_default_retention_keep_yearly', 0, 0, 1000),
    'retention_prune' => AppConfig::getBool('backup_job_default_retention_prune', true),
    'schedule_enabled' => AppConfig::getBool('backup_job_default_schedule_enabled', false),
    'schedule_hour' => AppConfig::getInt('backup_job_default_schedule_hour', 2, 0, 23),
    'schedule_days' => AppConfig::getCsvValues('backup_job_default_schedule_days', '1'),
];
if (empty($addDefaults['schedule_days'])) {
    $addDefaults['schedule_days'] = ['1'];
}

function renderHookScriptOptions(array $scripts, ?int $selectedId = null): string {
    $html = '<option value="">' . h(t('common.none')) . '</option>';
    foreach ($scripts as $script) {
        $id = (int) ($script['id'] ?? 0);
        $scope = HookScriptManager::scopeLabel((string) ($script['execution_scope'] ?? 'both'));
        $selected = $selectedId !== null && $selectedId === $id ? ' selected' : '';
        $html .= '<option value="' . $id . '"' . $selected . '>' . h((string) ($script['name'] ?? 'Script')) . ' — ' . h($scope) . '</option>';
    }
    return $html;
}

$title   = t('backup_jobs.title');
$active  = 'backup_jobs';
$actions = '<a href="' . routePath('/backup_templates.php') . '" class="btn" style="margin-right:8px">' . h(t('backup_jobs.templates_link')) . '</a>'
         . '<button class="btn" onclick="document.getElementById(\'modal-add\').classList.add(\'show\')">' . h(t('backup_jobs.new_btn')) . '</button>';

include 'layout_top.php';

$daysMap = [
    '1' => t('common.day.mon_short'),
    '2' => t('common.day.tue_short'),
    '3' => t('common.day.wed_short'),
    '4' => t('common.day.thu_short'),
    '5' => t('common.day.fri_short'),
    '6' => t('common.day.sat_short'),
    '7' => t('common.day.sun_short'),
];
?>

<div class="alert alert-info" style="margin-bottom:16px">
    <?= t('backup_jobs.cron_info') ?>
            <a href="<?= routePath('/scheduler.php') ?>"><?= h(t('backup_jobs.open_scheduler')) ?></a>
    <div style="margin-top:6px;font-size:12px">
        <?= t('backup_jobs.timezone_hint', ['tz' => h($scheduleTimezoneLabel), 'cron_tz' => h($serverTimezoneName)]) ?>
    </div>
</div>


<?php if ($scheduledJobs > 0 && !$cronEngine['active']): ?>
<div class="alert <?= $cronEngine['supports_crontab'] ? 'alert-warning' : 'alert-danger' ?>" style="margin-bottom:16px">
    <?= t('backup_jobs.cron_inactive_warning', ['count' => $scheduledJobs]) ?>
    <?php if ($cronEngine['supports_crontab']): ?>
        <?= t('backup_jobs.cron_activate_hint', ['url' => routePath('/scheduler.php')]) ?>
    <?php else: ?>
    <?= t('backup_jobs.cron_external_hint') ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid-2-sidebar" style="display:grid;grid-template-columns:minmax(0,1.65fr) minmax(0,.85fr);gap:16px">

<div>
    <div class="card">
        <div class="card-header">
            <?= h(t('backup_jobs.configured')) ?>
            <span class="badge badge-blue"><?= count($jobs) ?></span>
        </div>
        <?php if (empty($jobs)): ?>
        <div class="empty-state" style="padding:32px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            <div><?= h(t('backup_jobs.empty_state')) ?></div>
            <div style="font-size:12px;margin-top:4px"><?= h(t('backup_jobs.empty_hint')) ?></div>
            <div style="margin-top:12px"><a class="btn btn-primary" href="<?= routePath('/quick_backup.php') ?>"><?= h(t('backup_jobs.quick_flow_btn')) ?></a></div>
        </div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= h(t('backup_jobs.table.name')) ?></th>
                    <th><?= h(t('backup_jobs.table.repo')) ?></th>
                    <th><?= h(t('backup_jobs.table.host')) ?></th>
                    <th><?= h(t('backup_jobs.table.paths')) ?></th>
                    <th><?= h(t('backup_jobs.table.scheduled')) ?></th>
                    <th><?= h(t('backup_jobs.table.notifications')) ?></th>
                    <th><?= h(t('backup_jobs.table.retry')) ?></th>
                    <th><?= h(t('backup_jobs.table.last_run')) ?></th>
                    <th><?= h(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                <?php
                    $paths   = json_decode($job['source_paths'], true) ?? [];
                    $firstPath = $paths[0] ?? '';
                    $extraCount = count($paths) - 1;
                ?>
                <tr>
                    <td style="font-weight:500"><?= h($job['name']) ?></td>
                    <td style="font-size:12px">
                        <?php if (!empty($job['repo_name'])): ?>
                        <?= h($job['repo_name']) ?>
                        <?php else: ?>
                        <span class="badge badge-red" style="font-size:10px"><?= h(t('backup_jobs.missing_repo', ['id' => (int) $job['repo_id']])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px">
                        <?php if ($job['host_name']): ?>
                        <span class="badge badge-purple" style="font-size:10px"><?= h($job['host_name']) ?></span>
                        <?php else: ?>
                        <span class="badge badge-gray" style="font-size:10px">Local</span>
                        <?php endif; ?>
                    </td>
                    <td class="mono" style="font-size:11px">
                        <?= h($firstPath) ?>
                        <?php if ($extraCount > 0): ?>
                        <span class="badge badge-gray" style="font-size:10px;margin-left:4px">+<?= $extraCount ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($job['schedule_enabled']): ?>
                        <?php $d = array_map(fn($d) => $daysMap[$d] ?? $d, explode(',', $job['schedule_days'])); ?>
                        <span class="badge badge-green" style="font-size:10px">
                            <?= implode(' ', $d) ?> <?= str_pad($job['schedule_hour'],2,'0',STR_PAD_LEFT) ?>h
                        </span>
                        <?php else: ?>
                        <span class="badge badge-gray" style="font-size:10px"><?= h(t('common.manual')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= renderNotificationPolicySummary(Notifier::getEntityPolicy('backup_job', $job), 'backup_job') ?></td>
                    <td><?= renderRetryPolicySummary(JobRetryPolicy::getEntityPolicy($job)) ?></td>
                    <td style="font-size:11px">
                        <?php if ($job['last_run']): ?>
                        <?php
                            $st = $job['last_status'] ?? '';
                            $badgeClass = match($st) {
                                'success' => 'badge-green',
                                'running' => 'badge-blue',
                                default   => 'badge-red',
                            };
                            $badgeLabel = match($st) {
                                'success' => '✓',
                                'running' => '⟳',
                                default   => '✗',
                            };
                            $runningAgeMinutes = null;
                            if ($st === 'running' && !empty($job['last_run'])) {
                                $lastRunTs = strtotime((string) $job['last_run']);
                                if ($lastRunTs !== false) {
                                    $runningAgeMinutes = max(0, (int) floor((time() - $lastRunTs) / 60));
                                }
                            }
                            $isLongRunning = $runningAgeMinutes !== null && $runningAgeMinutes >= 120;
                        ?>
                        <span class="badge <?= $badgeClass ?>" style="font-size:10px" title="<?= h($st) ?>">
                            <?= $badgeLabel ?>
                        </span>
                        <?= formatDate($job['last_run']) ?>
                        <?php if ($isLongRunning): ?>
                        <span class="badge badge-yellow" style="font-size:10px" title="Execution longue">
                            ⚠ <?= (int) floor($runningAgeMinutes / 60) ?>h<?= str_pad((string) ($runningAgeMinutes % 60), 2, '0', STR_PAD_LEFT) ?>
                        </span>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted"><?= h(t('common.never')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-2" style="flex-wrap:wrap">
                            <?php if (Auth::canRun()): ?>
                            <button class="btn btn-sm btn-success" onclick="runJob(<?= $job['id'] ?>, this)">
                                ▶ <?= h(t('backup_jobs.run_btn')) ?>
                            </button>
                            <?php endif; ?>
                            <?php if (Auth::isAdmin()): ?>
                            <button class="btn btn-sm" onclick="openEdit(<?= h(json_encode($job)) ?>)">
                                <?= h(t('common.edit')) ?>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm" onclick='testSavedNotificationPolicy("backup_job", "failure", <?= h(json_encode(Notifier::getEntityPolicy('backup_job', $job))) ?>, <?= h(json_encode($job["name"])) ?>)'>
                                <?= h(t('common.test_notif')) ?>
                            </button>
                            <?php if ($job['last_output']): ?>
                            <button class="btn btn-sm" onclick="showLog(<?= h(json_encode($job['last_output'])) ?>)">
                                <?= h(t('common.logs')) ?>
                            </button>
                            <?php endif; ?>
                            <?php if (Auth::isAdmin()): ?>
                            <button class="btn btn-sm btn-danger"
                                onclick="confirmAction('<?= h(t('backup_jobs.delete_confirm')) ?>', () => deleteJob(<?= $job['id'] ?>))">
                                <?= h(t('common.delete')) ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent logs -->
<div>
    <div class="card">
        <div class="card-header"><?= h(t('backup_jobs.recent_logs')) ?></div>
        <?php if (empty($cronLogs)): ?>
        <div class="empty-state" style="padding:24px"><?= h(t('common.no_logs')) ?></div>
        <?php else: ?>
        <div class="card-body table-wrap" style="padding:8px 12px;overflow:auto;max-height:260px">
            <table class="table" style="font-size:12px">
                <thead><tr><th><?= h(t('common.date')) ?></th><th><?= h(t('common.status')) ?></th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($cronLogs as $log): ?>
                    <tr>
                        <td style="font-size:12px"><?= formatDate($log['ran_at']) ?></td>
                        <td><span class="badge <?= $log['status']==='success' ? 'badge-green' : 'badge-red' ?>"><?= $log['status'] ?></span></td>
                        <td>
                            <?php if ($log['output']): ?>
                            <button class="btn btn-sm" onclick="showLog(<?= h(json_encode($log['output'])) ?>)"><?= h(t('common.view')) ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>

<!-- ── Add modal ───────────────────────────────────────────────────────────── -->
<div id="modal-add" class="modal-overlay">
    <div class="modal" style="max-width:600px">
        <div class="modal-title"><?= h(t('backup_jobs.modal_add.title')) ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label class="form-label"><?= h(t('common.name')) ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="web-prod-daily" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.target_repo')) ?> <span style="color:var(--red)">*</span></label>
                <select name="repo_id" class="form-control" required>
                    <option value=""><?= h(t('common.select')) ?></option>
                    <?php foreach ($repos as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= h($r['name']) ?> — <?= h($r['path']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.remote_host')) ?></label>
                <select name="host_id" class="form-control">
                    <option value=""><?= h(t('backup_jobs.modal.local_option')) ?></option>
                    <?php foreach ($hosts as $h): ?>
                    <option value="<?= $h['id'] ?>"><?= h($h['name']) ?> — <?= h($h['hostname']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('backup_jobs.modal.host_hint', ['url' => routePath('/hosts.php')]) ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.remote_repo_path')) ?></label>
                <input type="text" name="remote_repo_path" class="form-control"
                       placeholder="sftp:user@192.168.9.214:/backups/web-prod-01">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('backup_jobs.modal.remote_repo_path_hint') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.hostname_override')) ?></label>
                <input type="text" name="hostname_override" class="form-control"
                       placeholder="webserver">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('backup_jobs.modal.hostname_override_hint') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.source_paths')) ?> <span style="color:var(--red)">*</span></label>
                <textarea name="source_paths" class="form-control" rows="4"
                          placeholder="/var/www&#10;/etc/nginx&#10;/home/user" required></textarea>
                <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= h(t('backup_jobs.modal.paths_hint')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.tags')) ?></label>
                <input type="text" name="tags" class="form-control" placeholder="web, prod, daily">
                <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= h(t('backup_jobs.modal.tags_hint')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.excludes')) ?></label>
                <textarea name="excludes" class="form-control" rows="3"
                          placeholder="*.tmp&#10;*.log&#10;/tmp"></textarea>
                <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= h(t('backup_jobs.modal.excludes_hint')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('common.description')) ?></label>
                <input type="text" name="description" class="form-control" placeholder="<?= h(t('backup_jobs.modal.description_placeholder')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.notifications')) ?></label>
                <?= renderNotificationPolicyEditor('backup_add', 'backup_job', $defaultBackupNotificationPolicy) ?>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.retry')) ?></label>
                <?= renderRetryPolicyEditor('backup_add', $defaultBackupRetryPolicy) ?>
            </div>
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px">
                    <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h(t('backup_jobs.modal.approved_scripts')) ?></div>
                    <?php if (Auth::hasPermission('scripts.manage')): ?>
                    <a href="<?= routePath('/scripts.php') ?>" class="btn btn-sm"><?= h(t('backup_jobs.modal.manage_catalogue')) ?></a>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.pre_backup')) ?></label>
                    <select name="pre_hook_script_id" class="form-control">
                        <?= renderHookScriptOptions($approvedScripts) ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.post_backup')) ?></label>
                    <select name="post_hook_script_id" class="form-control">
                        <?= renderHookScriptOptions($approvedScripts) ?>
                    </select>
                </div>
                <div style="font-size:11px;color:var(--text2)">
                    <?= h(t('backup_jobs.modal.scripts_note')) ?>
                </div>
            </div>
            <!-- Hooks -->
            <div style="display:none;border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <div style="font-size:13px;font-weight:600;margin-bottom:12px;color:var(--text)"><?= h(t('backup_jobs.modal.hooks_section')) ?></div>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.pre_backup_hook')) ?></label>
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                        <select class="form-control" style="max-width:220px" onchange="applyHookTemplate(this.value,'add-pre-hook')">
                            <option value=""><?= h(t('backup_jobs.modal.template_placeholder')) ?></option>
                            <option value="mysql">MySQL / MariaDB dump</option>
                            <option value="pgsql">PostgreSQL dump (base unique)</option>
                            <option value="pgsql_all">PostgreSQL dump (toutes bases)</option>
                            <option value="compress"><?= h(t('backup_jobs.modal.compress_option')) ?></option>
                        </select>
                        <span style="font-size:11px;color:var(--text2)"><?= h(t('backup_jobs.modal.template_hint')) ?></span>
                    </div>
                    <textarea name="pre_hook" id="add-pre-hook" class="form-control mono" rows="5"
                              placeholder="#!/bin/bash&#10;# Script exécuté avant la sauvegarde&#10;# Code retour non-zéro → sauvegarde annulée" style="font-size:12px;font-family:monospace"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.post_backup_hook')) ?></label>
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                        <select class="form-control" style="max-width:220px" onchange="applyHookTemplate(this.value,'add-post-hook')">
                            <option value=""><?= h(t('backup_jobs.modal.template_placeholder')) ?></option>
                            <option value="cleanup_sql"><?= h(t('backup_jobs.modal.cleanup_sql_option')) ?></option>
                            <option value="cleanup_archive"><?= h(t('backup_jobs.modal.cleanup_archive_option')) ?></option>
                        </select>
                    </div>
                    <textarea name="post_hook" id="add-post-hook" class="form-control mono" rows="3"
                              placeholder="#!/bin/bash&#10;# Script exécuté après la sauvegarde (toujours)" style="font-size:12px;font-family:monospace"></textarea>
                </div>
                <?php if (InfisicalClient::isConfigured()): ?>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.infisical_vars')) ?></label>
                    <textarea name="hook_env" id="add-hook-env" class="form-control mono" rows="3"
                              placeholder="MYSQL_PASSWORD=prod/mysql_root_pass&#10;DB_USER=prod/db_user"
                              style="font-size:12px;font-family:monospace"></textarea>
                    <div style="font-size:11px;color:var(--text2);margin-top:4px">
                        <?= t('backup_jobs.modal.infisical_vars_hint') ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
<!-- retention -->
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px">
                    <input type="checkbox" name="retention_enabled" value="1" id="add-ret-check" <?= $addDefaults['retention_enabled'] ? 'checked' : '' ?>
                           style="accent-color:var(--accent);width:16px;height:16px"
                           onchange="document.getElementById('add-ret-fields').style.display=this.checked?'block':'none'">
                    <span style="font-size:13px;font-weight:500"><?= h(t('backup_jobs.modal.retention_label')) ?></span>
                </label>
                <div id="add-ret-fields" style="display:<?= $addDefaults['retention_enabled'] ? 'block' : 'none' ?>">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(80px,1fr));gap:8px;margin-bottom:10px">
                        <?php foreach ([
                            'last'    => t('backup_jobs.modal.ret_last'),
                            'daily'   => t('backup_jobs.modal.ret_daily'),
                            'weekly'  => t('backup_jobs.modal.ret_weekly'),
                            'monthly' => t('backup_jobs.modal.ret_monthly'),
                            'yearly'  => t('backup_jobs.modal.ret_yearly'),
                        ] as $k => $l): ?>
                        <div>
                            <label class="form-label" style="font-size:11px"><?= h($l) ?></label>
                            <input type="number" name="retention_keep_<?= $k ?>" class="form-control" min="0" value="<?= (int) ($addDefaults['retention_keep_' . $k] ?? 0) ?>" style="padding:4px 8px;font-size:12px">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="retention_prune" value="1" <?= $addDefaults['retention_prune'] ? 'checked' : '' ?> style="accent-color:var(--accent);width:14px;height:14px">
                        <span style="font-size:12px"><?= h(t('backup_jobs.modal.prune_label')) ?></span>
                    </label>
                    <div style="font-size:11px;color:var(--text2);margin-top:6px"><?= h(t('backup_jobs.modal.zero_hint')) ?></div>
                </div>
            </div>
            <!-- Scheduling -->
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px">
                    <input type="checkbox" name="schedule_enabled" value="1" <?= $addDefaults['schedule_enabled'] ? 'checked' : '' ?>
                           id="sched-check" style="accent-color:var(--accent);width:16px;height:16px"
                           onchange="document.getElementById('sched-fields').style.display=this.checked?'block':'none'">
                    <span style="font-size:13px;font-weight:500"><?= h(t('backup_jobs.modal.schedule_label')) ?></span>
                </label>
                <div id="sched-fields" style="display:<?= $addDefaults['schedule_enabled'] ? 'block' : 'none' ?>">
                    <div class="form-group">
                        <label class="form-label"><?= h(t('backup_jobs.modal.schedule_days')) ?></label>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <?php foreach ([
                                '1' => t('common.day.mon'),
                                '2' => t('common.day.tue'),
                                '3' => t('common.day.wed'),
                                '4' => t('common.day.thu'),
                                '5' => t('common.day.fri'),
                                '6' => t('common.day.sat'),
                                '7' => t('common.day.sun'),
                            ] as $v => $l): ?>
                            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-size:12px;
                                          padding:4px 8px;border:1px solid var(--border);border-radius:4px">
                                <input type="checkbox" name="schedule_days[]" value="<?= $v ?>"
                                       <?= in_array($v, $addDefaults['schedule_days'], true) ? 'checked' : '' ?>
                                       style="accent-color:var(--accent)">
                                <?= h($l) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= h(t('backup_jobs.modal.schedule_hour')) ?></label>
                        <select name="schedule_hour" class="form-control" style="max-width:120px">
                            <?php for ($h = 0; $h < 24; $h++): ?>
                            <option value="<?= $h ?>" <?= $h === (int) $addDefaults['schedule_hour'] ? 'selected' : '' ?>>
                                <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-add').classList.remove('show')"><?= h(t('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= h(t('common.create')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit modal ──────────────────────────────────────────────────────────── -->
<div id="modal-edit" class="modal-overlay">
    <div class="modal" style="max-width:600px">
        <div class="modal-title"><?= h(t('backup_jobs.modal_edit.title')) ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="job_id" id="edit-job-id">
            <div class="form-group">
                <label class="form-label"><?= h(t('common.name')) ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" id="edit-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.target_repo')) ?></label>
                <input type="text" id="edit-repo" class="form-control" disabled
                       style="color:var(--text2);opacity:.7">
                <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= h(t('backup_jobs.modal.repo_immutable')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.remote_host')) ?></label>
                <select name="host_id" id="edit-host-id" class="form-control">
                    <option value=""><?= h(t('backup_jobs.modal.local_option')) ?></option>
                    <?php foreach ($hosts as $h): ?>
                    <option value="<?= $h['id'] ?>"><?= h($h['name']) ?> — <?= h($h['hostname']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.remote_repo_path')) ?></label>
                <input type="text" name="remote_repo_path" id="edit-remote-repo-path" class="form-control"
                       placeholder="sftp:user@192.168.9.214:/backups/web-prod-01">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('backup_jobs.modal.remote_repo_path_hint_short') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.hostname_override')) ?></label>
                <input type="text" name="hostname_override" id="edit-hostname-override" class="form-control"
                       placeholder="webserver">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('backup_jobs.modal.hostname_override_hint_short') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.source_paths')) ?> <span style="color:var(--red)">*</span></label>
                <textarea name="source_paths" id="edit-source-paths" class="form-control" rows="4" required></textarea>
                <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= h(t('backup_jobs.modal.paths_hint')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.tags')) ?></label>
                <input type="text" name="tags" id="edit-tags" class="form-control">
                <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= h(t('backup_jobs.modal.tags_hint_short')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.excludes')) ?></label>
                <textarea name="excludes" id="edit-excludes" class="form-control" rows="3"></textarea>
                <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= h(t('backup_jobs.modal.excludes_hint_short')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('common.description')) ?></label>
                <input type="text" name="description" id="edit-description" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.notifications')) ?></label>
                <?= renderNotificationPolicyEditor('backup_edit', 'backup_job', $defaultBackupNotificationPolicy) ?>
            </div>
            <div class="form-group">
                <label class="form-label"><?= h(t('backup_jobs.modal.retry')) ?></label>
                <?= renderRetryPolicyEditor('backup_edit', $defaultBackupRetryPolicy) ?>
            </div>
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px">
                    <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h(t('backup_jobs.modal.approved_scripts')) ?></div>
                    <?php if (Auth::hasPermission('scripts.manage')): ?>
                    <a href="<?= routePath('/scripts.php') ?>" class="btn btn-sm"><?= h(t('backup_jobs.modal.manage_catalogue')) ?></a>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.pre_backup')) ?></label>
                    <select name="pre_hook_script_id" id="edit-pre-hook-script-id" class="form-control">
                        <?= renderHookScriptOptions($approvedScripts) ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.post_backup')) ?></label>
                    <select name="post_hook_script_id" id="edit-post-hook-script-id" class="form-control">
                        <?= renderHookScriptOptions($approvedScripts) ?>
                    </select>
                </div>
                <div style="font-size:11px;color:var(--text2)">
                    <?= h(t('backup_jobs.modal.scripts_note_edit')) ?>
                </div>
            </div>
            <!-- Hooks (edit) -->
            <div style="display:none;border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <div style="font-size:13px;font-weight:600;margin-bottom:12px;color:var(--text)"><?= h(t('backup_jobs.modal.hooks_section')) ?></div>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.pre_backup_hook')) ?></label>
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                        <select class="form-control" style="max-width:220px" onchange="applyHookTemplate(this.value,'edit-pre-hook')">
                            <option value=""><?= h(t('backup_jobs.modal.template_placeholder')) ?></option>
                            <option value="mysql">MySQL / MariaDB dump</option>
                            <option value="pgsql">PostgreSQL dump (base unique)</option>
                            <option value="pgsql_all">PostgreSQL dump (toutes bases)</option>
                            <option value="compress"><?= h(t('backup_jobs.modal.compress_option')) ?></option>
                        </select>
                    </div>
                    <textarea name="pre_hook" id="edit-pre-hook" class="form-control mono" rows="5"
                              placeholder="#!/bin/bash&#10;# Script exécuté avant la sauvegarde" style="font-size:12px;font-family:monospace"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.post_backup_hook')) ?></label>
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                        <select class="form-control" style="max-width:220px" onchange="applyHookTemplate(this.value,'edit-post-hook')">
                            <option value=""><?= h(t('backup_jobs.modal.template_placeholder')) ?></option>
                            <option value="cleanup_sql"><?= h(t('backup_jobs.modal.cleanup_sql_option')) ?></option>
                            <option value="cleanup_archive"><?= h(t('backup_jobs.modal.cleanup_archive_option')) ?></option>
                        </select>
                    </div>
                    <textarea name="post_hook" id="edit-post-hook" class="form-control mono" rows="3"
                              placeholder="#!/bin/bash&#10;# Script exécuté après la sauvegarde (toujours)" style="font-size:12px;font-family:monospace"></textarea>
                </div>
                <?php if (InfisicalClient::isConfigured()): ?>
                <div class="form-group">
                    <label class="form-label"><?= h(t('backup_jobs.modal.infisical_vars')) ?></label>
                    <textarea name="hook_env" id="edit-hook-env" class="form-control mono" rows="3"
                              placeholder="MYSQL_PASSWORD=prod/mysql_root_pass&#10;DB_USER=prod/db_user"
                              style="font-size:12px;font-family:monospace"></textarea>
                    <div style="font-size:11px;color:var(--text2);margin-top:4px">
                        <?= t('backup_jobs.modal.infisical_vars_hint') ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- retention (edit) -->
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px">
                    <input type="checkbox" name="retention_enabled" value="1" id="edit-ret-check"
                           style="accent-color:var(--accent);width:16px;height:16px"
                           onchange="document.getElementById('edit-ret-fields').style.display=this.checked?'block':'none'">
                    <span style="font-size:13px;font-weight:500"><?= h(t('backup_jobs.modal.retention_label')) ?></span>
                </label>
                <div id="edit-ret-fields" style="display:none">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(80px,1fr));gap:8px;margin-bottom:10px">
                        <?php foreach ([
                            'last'    => t('backup_jobs.modal.ret_last'),
                            'daily'   => t('backup_jobs.modal.ret_daily'),
                            'weekly'  => t('backup_jobs.modal.ret_weekly'),
                            'monthly' => t('backup_jobs.modal.ret_monthly'),
                            'yearly'  => t('backup_jobs.modal.ret_yearly'),
                        ] as $k => $l): ?>
                        <div>
                            <label class="form-label" style="font-size:11px"><?= h($l) ?></label>
                            <input type="number" name="retention_keep_<?= $k ?>" id="edit-ret-<?= $k ?>" class="form-control" min="0" value="0" style="padding:4px 8px;font-size:12px">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="retention_prune" id="edit-ret-prune" value="1" checked style="accent-color:var(--accent);width:14px;height:14px">
                        <span style="font-size:12px"><?= h(t('backup_jobs.modal.prune_label')) ?></span>
                    </label>
                    <div style="font-size:11px;color:var(--text2);margin-top:6px"><?= h(t('backup_jobs.modal.zero_hint')) ?></div>
                </div>
            </div>
            <!-- Scheduling -->
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px">
                    <input type="checkbox" name="schedule_enabled" value="1"
                           id="edit-sched-check" style="accent-color:var(--accent);width:16px;height:16px"
                           onchange="document.getElementById('edit-sched-fields').style.display=this.checked?'block':'none'">
                    <span style="font-size:13px;font-weight:500"><?= h(t('backup_jobs.modal.schedule_label')) ?></span>
                </label>
                <div id="edit-sched-fields" style="display:none">
                    <div class="form-group">
                        <label class="form-label"><?= h(t('backup_jobs.modal.schedule_days')) ?></label>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <?php foreach ([
                                '1' => t('common.day.mon'),
                                '2' => t('common.day.tue'),
                                '3' => t('common.day.wed'),
                                '4' => t('common.day.thu'),
                                '5' => t('common.day.fri'),
                                '6' => t('common.day.sat'),
                                '7' => t('common.day.sun'),
                            ] as $v => $l): ?>
                            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-size:12px;
                                          padding:4px 8px;border:1px solid var(--border);border-radius:4px">
                                <input type="checkbox" name="schedule_days[]" value="<?= $v ?>"
                                       id="edit-day-<?= $v ?>"
                                       style="accent-color:var(--accent)">
                                <?= h($l) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= h(t('backup_jobs.modal.schedule_hour')) ?></label>
                        <select name="schedule_hour" id="edit-sched-hour" class="form-control" style="max-width:120px">
                            <?php for ($h = 0; $h < 24; $h++): ?>
                            <option value="<?= $h ?>"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-edit').classList.remove('show')"><?= h(t('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= h(t('common.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal logs ───────────────────────────────────────────────────────────── -->
<div id="modal-log" class="modal-overlay">
    <div class="modal" style="max-width:680px">
        <div class="modal-title"><?= h(t('common.logs')) ?></div>
        <div class="code-viewer" id="log-content" style="max-height:320px"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn" onclick="document.getElementById('modal-log').classList.remove('show')"><?= h(t('common.close')) ?></button>
        </div>
    </div>
</div>

<!-- ── Real-time run modal ──────────────────────────────────────────────────── -->
<div id="modal-run" class="modal-overlay">
    <div class="modal" style="max-width:700px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <div class="modal-title" id="run-title" style="margin-bottom:0;flex:1"><?= h(t('backup_jobs.js.running')) ?></div>
            <span id="run-spinner" class="spinner"></span>
        </div>
        <div style="font-size:11px;color:var(--text2);margin-bottom:8px"><?= h(t('backup_jobs.js.realtime_logs')) ?></div>
        <div class="code-viewer" id="run-output"
             style="min-height:160px;max-height:320px;overflow-y:auto;white-space:pre-wrap;word-break:break-all"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn" onclick="document.getElementById('modal-run').classList.remove('show');location.reload()">
                <?= h(t('backup_jobs.js.close_reload')) ?>
            </button>
        </div>
    </div>
</div>

<form id="form-delete" method="POST" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="job_id" id="delete-job-id">
</form>

<script<?= cspNonceAttr() ?>>
const _bj = {
    runBtn:          '▶ <?= h(t('backup_jobs.run_btn')) ?>',
    running:         '<?= h(t('backup_jobs.js.running')) ?>',
    startError:      '<?= h(t('backup_jobs.js.start_error')) ?>',
    cannotStart:     '<?= h(t('backup_jobs.js.cannot_start')) ?>',
    successTitle:    '<?= h(t('backup_jobs.js.success_title')) ?>',
    successToast:    '<?= h(t('backup_jobs.js.success_toast')) ?>',
    errorTitle:      '<?= h(t('backup_jobs.js.error_title')) ?>',
    errorToast:      '<?= h(t('backup_jobs.js.error_toast')) ?>',
    initializing:    '<?= h(t('backup_jobs.js.initializing')) ?>',
    missingRepoPrefix: '<?= h(t('backup_jobs.js.missing_repo_prefix')) ?>',
    auditStale:      'Aucun evenement depuis',
    auditPidDead:    'processus arrete',
};

function showLog(output) {
    document.getElementById('log-content').textContent = output;
    document.getElementById('modal-log').classList.add('show');
}

function deleteJob(id) {
    document.getElementById('delete-job-id').value = id;
    document.getElementById('form-delete').submit();
}

const HOOK_TEMPLATES = {
    mysql: `#!/bin/bash
# MySQL/MariaDB dump — edit variables
DB_USER="root"
DB_PASS="your_password"
DB_NAME="--all-databases"  # or DB name, e.g.: "mydb"
DUMP_FILE="/tmp/mysql_dump_$(date +%Y%m%d_%H%M%S).sql"

mysqldump -u "$DB_USER" -p"$DB_PASS" $DB_NAME > "$DUMP_FILE" && \\
    echo "Dump créé : $DUMP_FILE ($(du -sh "$DUMP_FILE" | cut -f1))"`,

    pgsql: `#!/bin/bash
# PostgreSQL dump (single database) — edit variables
DB_USER="postgres"
DB_NAME="mydb"
DUMP_FILE="/tmp/pg_dump_$(date +%Y%m%d_%H%M%S).sql"

pg_dump -U "$DB_USER" -d "$DB_NAME" > "$DUMP_FILE" && \\
    echo "Dump créé : $DUMP_FILE ($(du -sh "$DUMP_FILE" | cut -f1))"`,

    pgsql_all: `#!/bin/bash
# Dump all PostgreSQL databases
DB_USER="postgres"
DUMP_FILE="/tmp/pg_dumpall_$(date +%Y%m%d_%H%M%S).sql"

pg_dumpall -U "$DB_USER" > "$DUMP_FILE" && \\
    echo "Dump créé : $DUMP_FILE ($(du -sh "$DUMP_FILE" | cut -f1))"`,

    compress: `#!/bin/bash
# Compress a directory before backup — edit variables
SOURCE_DIR="/var/data/uploads"
DEST_FILE="/tmp/archive_$(date +%Y%m%d_%H%M%S).tar.gz"

tar -czf "$DEST_FILE" -C "$(dirname "$SOURCE_DIR")" "$(basename "$SOURCE_DIR")" && \\
    echo "Archive créée : $DEST_FILE ($(du -sh "$DEST_FILE" | cut -f1))"`,

    cleanup_sql: `#!/bin/bash
# Clean temporary SQL dumps
rm -f /tmp/mysql_dump_*.sql /tmp/mariadb_dump_*.sql /tmp/pg_dump_*.sql /tmp/pg_dumpall_*.sql
echo "Dumps SQL nettoyés"`,

    cleanup_archive: `#!/bin/bash
# Clean temporary archives
rm -f /tmp/archive_*.tar.gz
echo "Archives nettoyées"`,
};

function applyHookTemplate(type, targetId) {
    if (!type) return;
    const el = document.getElementById(targetId);
    if (el && HOOK_TEMPLATES[type]) el.value = HOOK_TEMPLATES[type];
}

function openEdit(job) {
    document.getElementById('edit-job-id').value      = job.id;
    document.getElementById('edit-name').value        = job.name;
    document.getElementById('edit-repo').value        = job.repo_name
        ? (job.repo_name + ' — ' + job.repo_path)
        : (_bj.missingRepoPrefix + job.repo_id + ']');
    document.getElementById('edit-host-id').value          = job.host_id || '';
    document.getElementById('edit-remote-repo-path').value  = job.remote_repo_path || '';
    document.getElementById('edit-hostname-override').value = job.hostname_override || '';
    document.getElementById('edit-description').value       = job.description || '';
    let policy = null;
    if (job.notification_policy) {
        try {
            policy = JSON.parse(job.notification_policy);
        } catch (error) {
            policy = null;
        }
    }
    if (!policy) {
        policy = <?= json_encode($defaultBackupNotificationPolicy) ?>;
    }
    window.applyNotificationPolicyToEditor('backup_edit', policy);

    let retryPolicy = null;
    if (job.retry_policy) {
        try {
            retryPolicy = JSON.parse(job.retry_policy);
        } catch (error) {
            retryPolicy = null;
        }
    }
    if (!retryPolicy) {
        retryPolicy = <?= json_encode($defaultBackupRetryPolicy) ?>;
    }
    window.applyRetryPolicyToEditor('backup_edit', retryPolicy);

    const paths = JSON.parse(job.source_paths || '[]');
    document.getElementById('edit-source-paths').value = paths.join('\n');

    const tags = JSON.parse(job.tags || '[]');
    document.getElementById('edit-tags').value = tags.join(', ');

    const excludes = JSON.parse(job.excludes || '[]');
    document.getElementById('edit-excludes').value = excludes.join('\n');

    // Scripts approuves
    const preScriptSelect = document.getElementById('edit-pre-hook-script-id');
    const postScriptSelect = document.getElementById('edit-post-hook-script-id');
    if (preScriptSelect) preScriptSelect.value = job.pre_hook_script_id || '';
    if (postScriptSelect) postScriptSelect.value = job.post_hook_script_id || '';

    // Hooks historiques masques
    document.getElementById('edit-pre-hook').value  = job.pre_hook  || '';
    document.getElementById('edit-post-hook').value = job.post_hook || '';

    // Infisical variables for hooks    const hookEnvEl = document.getElementById('edit-hook-env');
    if (hookEnvEl) {
        try {
            const hookEnvArr = JSON.parse(job.hook_env || '[]');
            hookEnvEl.value = hookEnvArr.join('\n');
        } catch(e) { hookEnvEl.value = ''; }
    }

    // retention
    const retCheck  = document.getElementById('edit-ret-check');
    const retFields = document.getElementById('edit-ret-fields');
    retCheck.checked        = job.retention_enabled == 1;
    retFields.style.display = job.retention_enabled == 1 ? 'block' : 'none';
    document.getElementById('edit-ret-last').value    = job.retention_keep_last    || 0;
    document.getElementById('edit-ret-daily').value   = job.retention_keep_daily   || 0;
    document.getElementById('edit-ret-weekly').value  = job.retention_keep_weekly  || 0;
    document.getElementById('edit-ret-monthly').value = job.retention_keep_monthly || 0;
    document.getElementById('edit-ret-yearly').value  = job.retention_keep_yearly  || 0;
    document.getElementById('edit-ret-prune').checked = job.retention_prune != 0;

    const schedCheck  = document.getElementById('edit-sched-check');
    const schedFields = document.getElementById('edit-sched-fields');
    schedCheck.checked        = job.schedule_enabled == 1;
    schedFields.style.display = job.schedule_enabled == 1 ? 'block' : 'none';
    document.getElementById('edit-sched-hour').value = job.schedule_hour || 2;

    const activeDays = (job.schedule_days || '1').split(',');
    ['1','2','3','4','5','6','7'].forEach(d => {
        document.getElementById('edit-day-' + d).checked = activeDays.includes(d);
    });

    document.getElementById('modal-edit').classList.add('show');
}

async function runJob(jobId, btn) {
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner"></span>';

    const titleEl  = document.getElementById('run-title');
    const outputEl = document.getElementById('run-output');
    const spinner  = document.getElementById('run-spinner');

    titleEl.textContent  = _bj.running;
    outputEl.textContent = '';
    outputEl.style.color = 'var(--text)';
    spinner.style.display = 'inline-block';
    document.getElementById('modal-run').classList.add('show');

    const res = await apiPost('/api/run_backup_job.php', { job_id: jobId });

    if (!res.run_id) {
        btn.disabled    = false;
        btn.textContent = _bj.runBtn;
        spinner.style.display = 'none';
        titleEl.textContent  = '✗ ' + _bj.startError;
        outputEl.textContent = res.error || _bj.cannotStart;
        outputEl.style.color = 'var(--red)';
        return;
    }

    let offsetBytes = 0;
    let offset      = 0;
    let attempts = 0;

    const poll = async () => {
        try {
            const log = await apiPost('/api/poll_backup_log.php', { run_id: res.run_id, offset, last_offset_bytes: offsetBytes });

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
                btn.disabled    = false;
                btn.textContent = _bj.runBtn;
                spinner.style.display = 'none';

                if (log.status === 'success') {
                    titleEl.textContent  = '✓ ' + _bj.successTitle;
                    outputEl.style.color = 'var(--green)';
                    toast(_bj.successToast, 'success');
                } else {
                    titleEl.textContent  = '✗ ' + _bj.errorTitle;
                    outputEl.style.color = 'var(--red)';
                    toast(_bj.errorToast, 'error');
                }
                return;
            }

            const diagnostic = (log && typeof log === 'object' && log.diagnostic && typeof log.diagnostic === 'object')
                ? log.diagnostic
                : null;
            if (diagnostic && diagnostic.is_stale_running) {
                const age = Number.isFinite(Number(diagnostic.last_log_age_seconds))
                    ? Number(diagnostic.last_log_age_seconds)
                    : 0;
                const pidStopped = diagnostic.pid_running === false ? ' (' + _bj.auditPidDead + ')' : '';
                titleEl.textContent = _bj.running + ' - ⚠ ' + _bj.auditStale + ' ' + age + 's' + pidStopped;
            } else {
                attempts++;
            }
            if (attempts <= 3 && outputEl.textContent === '' && !(diagnostic && diagnostic.is_stale_running)) {
                titleEl.textContent = _bj.running + ' (' + _bj.initializing + ')';
            }

            setTimeout(poll, 1500);
        } catch (e) {
            setTimeout(poll, 3000);
        }
    };

    setTimeout(poll, 500);
}

window.showLog = showLog;
window.deleteJob = deleteJob;
window.applyHookTemplate = applyHookTemplate;
window.openEdit = openEdit;
window.runJob = runJob;
</script>

<?php include 'layout_bottom.php'; ?>
