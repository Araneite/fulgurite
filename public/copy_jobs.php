<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('copy_jobs.manage');

$flash = null;
$db    = Database::getInstance();
$defaultCopyNotificationPolicy = Notifier::decodePolicy('', 'copy_job');
$defaultCopyRetryPolicy = JobRetryPolicy::defaultEntityPolicy();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($_POST['action'] === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $srcId    = (int) ($_POST['source_repo_id'] ?? 0);
        $destPath = trim($_POST['dest_path'] ?? '');
        $destPasswordSource = $_POST['dest_password_source'] ?? 'agent';
        $destPass = $_POST['dest_password'] ?? '';
        $destInfisicalSecretName = trim($_POST['dest_infisical_secret_name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $schedEn  = isset($_POST['schedule_enabled']) ? 1 : 0;
        $schedH   = (int) ($_POST['schedule_hour'] ?? 2);
        $schedD   = implode(',', $_POST['schedule_days'] ?? ['1']);
        $notificationPolicy = Notifier::encodePolicy(
            Notifier::parsePolicyPost($_POST, 'copy_add', 'copy_job', $defaultCopyNotificationPolicy),
            'copy_job'
        );
        $retryPolicy = JobRetryPolicy::encodePolicy(
            JobRetryPolicy::parsePolicyPost($_POST, 'copy_add', $defaultCopyRetryPolicy),
            true
        );

        $passwordOk = ($destPasswordSource === 'infisical') ? $destInfisicalSecretName !== '' : $destPass !== '';

        if ($name && $srcId && $destPath && $passwordOk) {
            if (!Auth::canAccessRepoId($srcId)) {
                $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.source_denied')];
            } elseif ($destPasswordSource === 'infisical' && !InfisicalClient::isConfigured()) {
                $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.infisical_not_configured')];
            } elseif ($destPasswordSource === 'infisical' && InfisicalClient::getSecret($destInfisicalSecretName) === null) {
                $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.infisical_secret_error', ['name' => $destInfisicalSecretName])];
            } else {
            CopyJobManager::add($name, $srcId, $destPath, $destPass, $desc, $schedEn, $schedH, $schedD, $destPasswordSource, $destInfisicalSecretName, $notificationPolicy, $retryPolicy);
            Auth::log('copy_job_add', "Job copie créé: $name");
            $flash = ['type' => 'success', 'msg' => t('flash.copy_jobs.created', ['name' => $name])];
            }
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.required_fields')];
        }
    }

    if ($_POST['action'] === 'edit') {
        $id       = (int) ($_POST['job_id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $destPath = trim($_POST['dest_path'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $schedEn  = isset($_POST['schedule_enabled']) ? 1 : 0;
        $schedH   = (int) ($_POST['schedule_hour'] ?? 2);
        $schedD   = implode(',', $_POST['schedule_days'] ?? ['1']);
        $destPasswordSource = $_POST['dest_password_source'] ?? 'agent';
        $destPass = $_POST['dest_password'] ?? '';
        $destInfisicalSecretName = trim($_POST['dest_infisical_secret_name'] ?? '');
        $notificationPolicy = Notifier::encodePolicy(
            Notifier::parsePolicyPost($_POST, 'copy_edit', 'copy_job', $defaultCopyNotificationPolicy),
            'copy_job'
        );
        $retryPolicy = JobRetryPolicy::encodePolicy(
            JobRetryPolicy::parsePolicyPost($_POST, 'copy_edit', $defaultCopyRetryPolicy),
            true
        );
        $job = $id ? CopyJobManager::getById($id) : null;

        if ($job && !Auth::canAccessRepoId((int) ($job['source_repo_id'] ?? 0))) {
            $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.access_denied')];
        } elseif (in_array($destPasswordSource, ['agent', 'local'], true) && $destPass === '' && (($job['dest_password_source'] ?? 'agent') === 'infisical' || (empty($job['dest_password_ref']) && empty($job['dest_password_file'])))) {
            $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.password_required_for_direct')];
        } elseif ($destPasswordSource === 'infisical' && !InfisicalClient::isConfigured()) {
            $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.infisical_not_configured')];
        } elseif ($destPasswordSource === 'infisical' && $destInfisicalSecretName === '') {
            $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.infisical_secret_name_required')];
        } elseif ($destPasswordSource === 'infisical' && InfisicalClient::getSecret($destInfisicalSecretName) === null) {
            $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.infisical_secret_error', ['name' => $destInfisicalSecretName])];
        } elseif ($id && $name && $destPath) {
            CopyJobManager::update($id, [
                'name'             => $name,
                'dest_path'        => $destPath,
                'description'      => $desc,
                'schedule_enabled' => $schedEn,
                'schedule_hour'    => $schedH,
                'schedule_days'    => $schedD,
                'notification_policy' => $notificationPolicy,
                'retry_policy'     => $retryPolicy,
                'dest_password_source' => $destPasswordSource,
                'dest_infisical_secret_name' => $destPasswordSource === 'infisical' ? ($destInfisicalSecretName ?: null) : null,
                'dest_password'    => $destPass ?: null,
            ]);
            Auth::log('copy_job_edit', "Job modifié: $name");
            $flash = ['type' => 'success', 'msg' => t('flash.copy_jobs.updated', ['name' => $name])];
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.copy_jobs.edit_required_fields')];
        }
    }

    if ($_POST['action'] === 'delete') {
        $id  = (int) ($_POST['job_id'] ?? 0);
        $job = CopyJobManager::getById($id);
        if ($job && Auth::canAccessRepoId((int) ($job['source_repo_id'] ?? 0))) {
            CopyJobManager::delete($id);
            Auth::log('copy_job_delete', "Job supprimé: {$job['name']}");
            $flash = ['type' => 'success', 'msg' => t('flash.copy_jobs.deleted')];
        }
    }
}

$jobs      = array_values(array_filter(CopyJobManager::getAll(), static fn(array $job): bool => Auth::canAccessRepoId((int) ($job['source_repo_id'] ?? 0))));
$repos     = Auth::filterAccessibleRepos(RepoManager::getAll());
$cronEngine = SchedulerManager::getCronEngineStatus();
$scheduledJobs = count(array_filter($jobs, fn(array $job): bool => (int) ($job['schedule_enabled'] ?? 0) === 1));

$cronLogs = $db->query("
    SELECT * FROM cron_log WHERE job_type = 'copy' ORDER BY ran_at DESC LIMIT 10
")->fetchAll();
$scheduleTimezoneLabel = SchedulerManager::getScheduleTimezoneLabel();
$serverTimezoneName = SchedulerManager::getServerTimezoneName();

$title   = t('copy_jobs.title');
$active  = 'copy_jobs';
$actions = '<button class="btn btn-primary" onclick="document.getElementById(\'modal-add\').classList.add(\'show\')">+ ' . h(t('copy_jobs.new_btn')) . '</button>';

include 'layout_top.php';

// Helper jours
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
    <?= t('copy_jobs.scheduler_info') ?>
    <a href="<?= routePath('/scheduler.php') ?>"><?= t('copy_jobs.open_scheduler') ?></a>
    <div style="margin-top:6px;font-size:12px">
        <?= t('copy_jobs.timezone_hint', ['tz' => h($scheduleTimezoneLabel), 'cron_tz' => h($serverTimezoneName)]) ?>
    </div>
</div>

<?php if ($scheduledJobs > 0 && !$cronEngine['active']): ?>
<div class="alert <?= $cronEngine['supports_crontab'] ? 'alert-warning' : 'alert-danger' ?>" style="margin-bottom:16px">
    <?= t('copy_jobs.cron_inactive_warning', ['count' => $scheduledJobs]) ?>
    <?php if ($cronEngine['supports_crontab']): ?>
        <?= t('copy_jobs.cron_activate_hint', ['url' => routePath('/scheduler.php')]) ?>
    <?php else: ?>
    <?= t('copy_jobs.cron_external_hint') ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- panel management cron -->
<div class="card mb-4">
    <div class="card-header">
        <span>⏰ <?= t('copy_jobs.scheduler_title') ?></span>
        <div style="display:flex;align-items:center;gap:10px">
            <span id="cron-status-badge" class="badge badge-gray"><?= t('common.loading') ?></span>
            <button class="btn btn-sm btn-success" id="btn-enable-cron" onclick="enableCron()" style="display:none"><?= t('common.enable') ?></button>
            <button class="btn btn-sm btn-danger" id="btn-disable-cron" onclick="disableCron()" style="display:none"><?= t('common.disable') ?></button>
            <button class="btn btn-sm" onclick="runCronNow()">▶ <?= t('copy_jobs.run_now_btn') ?></button>
        </div>
    </div>
    <div class="card-body">
        <div style="font-size:13px;color:var(--text2);margin-bottom:10px">
            <?= t('copy_jobs.scheduler_desc') ?>
        </div>
        <div id="cron-line-display" style="font-family:var(--font-mono);font-size:11px;
             background:var(--bg);padding:8px;border-radius:4px;color:var(--text2);display:none"></div>
        <div id="cron-run-output" style="display:none;margin-top:12px">
            <div class="code-viewer" id="cron-run-log" style="max-height:150px"></div>
        </div>
    </div>
</div>

<div class="grid-2-sidebar" style="display:grid;grid-template-columns:minmax(0,1.65fr) minmax(0,.85fr);gap:16px">

<div>
    <div class="card">
        <div class="card-header">
            <?= t('copy_jobs.configured') ?>
            <span class="badge badge-blue"><?= count($jobs) ?></span>
        </div>
        <?php if (empty($jobs)): ?>
        <div class="empty-state" style="padding:32px"><?= t('copy_jobs.empty') ?></div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th><?= t('common.name') ?></th><th><?= t('copy_jobs.table.source') ?></th><th><?= t('copy_jobs.table.dest') ?></th><th><?= t('copy_jobs.table.scheduled') ?></th><th><?= t('copy_jobs.table.notifications') ?></th><th><?= t('copy_jobs.table.retry') ?></th><th><?= t('copy_jobs.table.last_run') ?></th><th><?= t('common.actions') ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                <tr>
                    <td style="font-weight:500"><?= h($job['name']) ?></td>
                    <td style="font-size:12px">
                        <?php if (!empty($job['source_name'])): ?>
                        <?= h($job['source_name']) ?>
                        <?php else: ?>
                        <span class="badge badge-red" style="font-size:10px"><?= t('copy_jobs.missing_repo', ['id' => (int) $job['source_repo_id']]) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="mono" style="font-size:11px"><?= h($job['dest_path']) ?></td>
                    <td>
                        <?php if ($job['schedule_enabled']): ?>
                        <?php $d = array_map(fn($d) => $daysMap[$d] ?? $d, explode(',', $job['schedule_days'])); ?>
                        <span class="badge badge-green" style="font-size:10px">
                            <?= implode(' ', $d) ?> <?= str_pad($job['schedule_hour'],2,'0',STR_PAD_LEFT) ?>h
                        </span>
                        <?php else: ?>
                        <span class="badge badge-gray" style="font-size:10px"><?= t('copy_jobs.manual') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= renderNotificationPolicySummary(Notifier::getEntityPolicy('copy_job', $job), 'copy_job') ?></td>
                    <td><?= renderRetryPolicySummary(JobRetryPolicy::getEntityPolicy($job)) ?></td>
                    <td style="font-size:11px">
                        <?php if ($job['last_run']): ?>
                        <span class="badge <?= $job['last_status']==='success' ? 'badge-green' : 'badge-red' ?>" style="font-size:10px">
                            <?= $job['last_status'] === 'success' ? '✓' : '✗' ?>
                        </span>
                        <?= formatDate($job['last_run']) ?>
                        <?php else: ?>
                        <span class="text-muted"><?= t('common.never') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-2" style="flex-wrap:wrap">
                            <button class="btn btn-sm btn-success" onclick="runJob(<?= $job['id'] ?>, this)">
                                ▶ <?= t('copy_jobs.run_btn') ?>
                            </button>
                            <button class="btn btn-sm" onclick="openEdit(<?= h(json_encode($job)) ?>)">
                                <?= t('common.edit') ?>
                            </button>
                            <button class="btn btn-sm" onclick='testSavedNotificationPolicy("copy_job", "failure", <?= h(json_encode(Notifier::getEntityPolicy('copy_job', $job))) ?>, <?= h(json_encode($job["name"])) ?>)'>
                                <?= t('common.test_notif') ?>
                            </button>
                            <?php if ($job['last_output']): ?>
                            <button class="btn btn-sm" onclick="showLog(<?= h(json_encode($job['last_output'])) ?>)">
                                <?= t('common.logs') ?>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-danger"
                                onclick="confirmAction('<?= h(t('copy_jobs.delete_confirm')) ?>', () => deleteJob(<?= $job['id'] ?>))">
                                <?= t('common.delete') ?>
                            </button>
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
        <div class="card-header"><?= t('copy_jobs.recent_logs') ?></div>
        <?php if (empty($cronLogs)): ?>
        <div class="empty-state" style="padding:24px"><?= t('common.no_logs') ?></div>
        <?php else: ?>
        <div class="card-body table-wrap" style="padding:8px 12px;overflow:auto;max-height:260px">
            <table class="table" style="font-size:12px">
                <thead><tr><th><?= t('common.date') ?></th><th><?= t('common.status') ?></th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($cronLogs as $log): ?>
                    <tr>
                        <td style="font-size:12px"><?= formatDate($log['ran_at']) ?></td>
                        <td><span class="badge <?= $log['status']==='success' ? 'badge-green' : 'badge-red' ?>"><?= $log['status'] ?></span></td>
                        <td>
                            <?php if ($log['output']): ?>
                            <button class="btn btn-sm" onclick="showLog(<?= h(json_encode($log['output'])) ?>)"><?= t('common.view') ?></button>
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
    <div class="modal" style="max-width:560px">
        <div class="modal-title"><?= t('copy_jobs.modal_add.title') ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label class="form-label"><?= t('common.name') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="backup-offsite" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.source_repo_label') ?> <span style="color:var(--red)">*</span></label>
                <select name="source_repo_id" class="form-control" required>
                    <option value=""><?= t('common.select') ?></option>
                    <?php foreach ($repos as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= h($r['name']) ?> — <?= h($r['path']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.dest_path_label') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="dest_path" class="form-control"
                       placeholder="/backups/offsite ou rclone:hetzner:restic/web-prod-01" required>
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('copy_jobs.rclone_hint') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.dest_pass_source_label') ?> <span style="color:var(--red)">*</span></label>
                <select name="dest_password_source" id="add-dest-pass-source" class="form-control"
                        onchange="toggleCopyPassSource('add', this.value)">
                    <option value="local"><?= t('copy_jobs.pass_source.local') ?></option>
                    <option value="agent" selected><?= t('copy_jobs.pass_source.agent') ?></option>
                    <?php if (InfisicalClient::isConfigured()): ?>
                    <option value="infisical"><?= t('copy_jobs.pass_source.infisical') ?></option>
                    <?php else: ?>
                    <option value="infisical" disabled title="<?= h(t('copy_jobs.infisical_not_configured_title')) ?>"><?= t('copy_jobs.pass_source.infisical_disabled') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div id="add-dest-pass-file" class="form-group">
                <label class="form-label"><?= t('copy_jobs.dest_password_label') ?> <span style="color:var(--red)">*</span></label>
                <input type="password" name="dest_password" class="form-control">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('copy_jobs.dest_password_hint') ?>
                </div>
            </div>
            <div id="add-dest-pass-infisical" class="form-group" style="display:none">
                <label class="form-label"><?= t('copy_jobs.infisical_secret_label') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="dest_infisical_secret_name" class="form-control" placeholder="RESTIC_COPY_DEST_PASSWORD">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('copy_jobs.infisical_secret_hint') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('common.description') ?></label>
                <input type="text" name="description" class="form-control" placeholder="<?= h(t('copy_jobs.description_placeholder')) ?>">
            </div>
            <!-- Scheduling -->
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px">
                    <input type="checkbox" name="schedule_enabled" value="1"
                           id="sched-check" style="accent-color:var(--accent);width:16px;height:16px"
                           onchange="document.getElementById('sched-fields').style.display=this.checked?'block':'none'">
                    <span style="font-size:13px;font-weight:500"><?= t('copy_jobs.schedule_auto_label') ?></span>
                </label>
                <div id="sched-fields" style="display:none">
                    <div class="form-group">
                        <label class="form-label"><?= t('copy_jobs.schedule_days_label') ?></label>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <?php foreach (['1' => t('common.day.mon'), '2' => t('common.day.tue'), '3' => t('common.day.wed'), '4' => t('common.day.thu'), '5' => t('common.day.fri'), '6' => t('common.day.sat'), '7' => t('common.day.sun')] as $v => $l): ?>
                            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-size:12px;
                                          padding:4px 8px;border:1px solid var(--border);border-radius:4px">
                                <input type="checkbox" name="schedule_days[]" value="<?= $v ?>"
                                       <?= $v === '1' ? 'checked' : '' ?>
                                       style="accent-color:var(--accent)">
                                <?= $l ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('copy_jobs.schedule_hour_label') ?></label>
                        <select name="schedule_hour" class="form-control" style="max-width:120px">
                            <?php for ($h = 0; $h < 24; $h++): ?>
                            <option value="<?= $h ?>" <?= $h === 2 ? 'selected' : '' ?>>
                                <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.notifications_label') ?></label>
                <?= renderNotificationPolicyEditor('copy_add', 'copy_job', $defaultCopyNotificationPolicy) ?>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.retry_label') ?></label>
                <?= renderRetryPolicyEditor('copy_add', $defaultCopyRetryPolicy) ?>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-add').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('common.create') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit modal ──────────────────────────────────────────────────────────── -->
<div id="modal-edit" class="modal-overlay">
    <div class="modal" style="max-width:560px">
        <div class="modal-title"><?= t('copy_jobs.modal_edit.title') ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="job_id" id="edit-job-id">
            <div class="form-group">
                <label class="form-label"><?= t('common.name') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" id="edit-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.source_repo_label') ?></label>
                <input type="text" id="edit-source" class="form-control" disabled
                       style="color:var(--text2);opacity:.7">
                <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= t('copy_jobs.source_immutable_hint') ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.dest_path_label') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="dest_path" id="edit-dest-path" class="form-control" required>
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('copy_jobs.rclone_hint') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.dest_pass_source_label') ?> <span style="color:var(--red)">*</span></label>
                <select name="dest_password_source" id="edit-dest-pass-source" class="form-control"
                        onchange="toggleCopyPassSource('edit', this.value)">
                    <option value="local"><?= t('copy_jobs.pass_source.local') ?></option>
                    <option value="agent"><?= t('copy_jobs.pass_source.agent') ?></option>
                    <?php if (InfisicalClient::isConfigured()): ?>
                    <option value="infisical"><?= t('copy_jobs.pass_source.infisical') ?></option>
                    <?php else: ?>
                    <option value="infisical" disabled title="<?= h(t('copy_jobs.infisical_not_configured_title')) ?>"><?= t('copy_jobs.pass_source.infisical_disabled') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div id="edit-dest-pass-file" class="form-group">
                <label class="form-label"><?= t('copy_jobs.new_dest_password_label') ?></label>
                <input type="password" name="dest_password" class="form-control"
                       placeholder="<?= h(t('copy_jobs.password_unchanged_placeholder')) ?>">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('copy_jobs.dest_password_hint') ?>
                </div>
            </div>
            <div id="edit-dest-pass-infisical" class="form-group" style="display:none">
                <label class="form-label"><?= t('copy_jobs.infisical_secret_label') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="dest_infisical_secret_name" id="edit-dest-infisical-secret-name" class="form-control" placeholder="RESTIC_COPY_DEST_PASSWORD">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('copy_jobs.infisical_edit_secret_hint') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('common.description') ?></label>
                <input type="text" name="description" id="edit-description" class="form-control">
            </div>

            <!-- Scheduling -->
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px">
                    <input type="checkbox" name="schedule_enabled" value="1"
                           id="edit-sched-check" style="accent-color:var(--accent);width:16px;height:16px"
                           onchange="document.getElementById('edit-sched-fields').style.display=this.checked?'block':'none'">
                    <span style="font-size:13px;font-weight:500"><?= t('copy_jobs.schedule_auto_label') ?></span>
                </label>
                <div id="edit-sched-fields" style="display:none">
                    <div class="form-group">
                        <label class="form-label"><?= t('copy_jobs.schedule_days_label') ?></label>
                        <div style="display:flex;gap:6px;flex-wrap:wrap" id="edit-days-container">
                            <?php foreach (['1' => t('common.day.mon'), '2' => t('common.day.tue'), '3' => t('common.day.wed'), '4' => t('common.day.thu'), '5' => t('common.day.fri'), '6' => t('common.day.sat'), '7' => t('common.day.sun')] as $v => $l): ?>
                            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-size:12px;
                                          padding:4px 8px;border:1px solid var(--border);border-radius:4px"
                                   id="edit-day-label-<?= $v ?>">
                                <input type="checkbox" name="schedule_days[]" value="<?= $v ?>"
                                       id="edit-day-<?= $v ?>"
                                       style="accent-color:var(--accent)">
                                <?= $l ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('copy_jobs.schedule_hour_label') ?></label>
                        <select name="schedule_hour" id="edit-sched-hour" class="form-control" style="max-width:120px">
                            <?php for ($h = 0; $h < 24; $h++): ?>
                            <option value="<?= $h ?>"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.notifications_label') ?></label>
                <?= renderNotificationPolicyEditor('copy_edit', 'copy_job', $defaultCopyNotificationPolicy) ?>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('copy_jobs.retry_label') ?></label>
                <?= renderRetryPolicyEditor('copy_edit', $defaultCopyRetryPolicy) ?>
            </div>

            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-edit').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal logs ───────────────────────────────────────────────────────────── -->
<div id="modal-log" class="modal-overlay">
    <div class="modal" style="max-width:680px">
        <div class="modal-title"><?= t('common.logs') ?></div>
        <div class="code-viewer" id="log-content" style="max-height:320px"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn" onclick="document.getElementById('modal-log').classList.remove('show')"><?= t('common.close') ?></button>
        </div>
    </div>
</div>

<!-- ── Run result modal ─────────────────────────────────────────────────────── -->
<div id="modal-run" class="modal-overlay">
    <div class="modal" style="max-width:700px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <div class="modal-title" id="run-title" style="margin-bottom:0;flex:1"><?= t('copy_jobs.js.copy_in_progress') ?></div>
            <span id="run-spinner" class="spinner"></span>
        </div>
        <div style="font-size:11px;color:var(--text2);margin-bottom:8px"><?= t('copy_jobs.realtime_logs_label') ?></div>
        <div class="code-viewer" id="run-output"
             style="min-height:160px;max-height:320px;overflow-y:auto;white-space:pre-wrap;word-break:break-all"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn" onclick="document.getElementById('modal-run').classList.remove('show');location.reload()">
                <?= t('copy_jobs.close_and_reload') ?>
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
function showLog(output) {
    document.getElementById('log-content').textContent = output;
    document.getElementById('modal-log').classList.add('show');
}

function toggleCopyPassSource(prefix, value) {
    document.getElementById(prefix + '-dest-pass-file').style.display = (value === 'agent' || value === 'local' || value === 'file') ? '' : 'none';
    document.getElementById(prefix + '-dest-pass-infisical').style.display = value === 'infisical' ? '' : 'none';
}

function deleteJob(id) {
    document.getElementById('delete-job-id').value = id;
    document.getElementById('form-delete').submit();
}

// ── Modifier un job ───────────────────────────────────────────────────────────
function openEdit(job) {
    document.getElementById('edit-job-id').value       = job.id;
    document.getElementById('edit-name').value         = job.name;
    document.getElementById('edit-source').value       = job.source_name
        ? (job.source_name + ' — ' + job.source_path)
        : ('<?= h(t('copy_jobs.js.missing_repo_prefix')) ?>' + job.source_repo_id + ']');
    document.getElementById('edit-dest-path').value    = job.dest_path;
    document.getElementById('edit-dest-pass-source').value = (job.dest_password_source === 'infisical') ? 'infisical' : (job.dest_password_source || 'agent');
    document.getElementById('edit-dest-infisical-secret-name').value = job.dest_infisical_secret_name || '';
    document.getElementById('edit-description').value  = job.description || '';
    toggleCopyPassSource('edit', job.dest_password_source || 'agent');

    // Scheduling
    const schedCheck  = document.getElementById('edit-sched-check');
    const schedFields = document.getElementById('edit-sched-fields');
    schedCheck.checked          = job.schedule_enabled == 1;
    schedFields.style.display   = job.schedule_enabled == 1 ? 'block' : 'none';
    document.getElementById('edit-sched-hour').value = job.schedule_hour || 2;

    // Check matching days
    const activeDays = (job.schedule_days || '1').split(',');
    ['1','2','3','4','5','6','7'].forEach(d => {
        document.getElementById('edit-day-' + d).checked = activeDays.includes(d);
    });

    let policy = null;
    if (job.notification_policy) {
        try {
            policy = JSON.parse(job.notification_policy);
        } catch (error) {
            policy = null;
        }
    }
    if (!policy) {
        policy = <?= json_encode($defaultCopyNotificationPolicy) ?>;
    }
    window.applyNotificationPolicyToEditor('copy_edit', policy);

    let retryPolicy = null;
    if (job.retry_policy) {
        try {
            retryPolicy = JSON.parse(job.retry_policy);
        } catch (error) {
            retryPolicy = null;
        }
    }
    if (!retryPolicy) {
        retryPolicy = <?= json_encode($defaultCopyRetryPolicy) ?>;
    }
    window.applyRetryPolicyToEditor('copy_edit', retryPolicy);

    document.getElementById('modal-edit').classList.add('show');
}

// ── management cron ──────────────────────────────────────────────────────────────
async function loadCronStatus() {
    const res    = await apiPost('/api/manage_cron.php', { action: 'status' });
    const badge  = document.getElementById('cron-status-badge');
    const btnEn  = document.getElementById('btn-enable-cron');
    const btnDis = document.getElementById('btn-disable-cron');
    const lineEl = document.getElementById('cron-line-display');

    if (res.active) {
        badge.className   = 'badge badge-green';
        badge.textContent = '✓ <?= h(t('copy_jobs.js.cron_active')) ?>';
        btnEn.style.display  = 'none';
        btnDis.style.display = 'inline-flex';
    } else {
        badge.className   = 'badge badge-gray';
        badge.textContent = '<?= h(t('copy_jobs.js.cron_inactive')) ?>';
        btnEn.style.display  = 'inline-flex';
        btnDis.style.display = 'none';
    }
    lineEl.style.display = 'block';
    lineEl.textContent   = res.cron_line;
}

async function enableCron() {
    const res = await apiPost('/api/manage_cron.php', { action: 'enable' });
    toast(res.output || (res.success ? '<?= h(t('copy_jobs.js.cron_enabled')) ?>' : '<?= h(t('common.error')) ?>'), res.success ? 'success' : 'error');
    if (res.success) loadCronStatus();
}

async function disableCron() {
    const confirmed = await window.confirmActionAsync('<?= h(t('copy_jobs.js.cron_disable_confirm')) ?>');
    if (!confirmed) return;
    const res = await apiPost('/api/manage_cron.php', { action: 'disable' });
    toast(res.output || (res.success ? '<?= h(t('copy_jobs.js.cron_disabled')) ?>' : '<?= h(t('common.error')) ?>'), res.success ? 'success' : 'error');
    if (res.success) loadCronStatus();
}

async function runCronNow() {
    const out = document.getElementById('cron-run-output');
    const log = document.getElementById('cron-run-log');
    out.style.display = 'block';
    log.textContent   = '<?= h(t('copy_jobs.js.running')) ?>';
    const res = await apiPost('/api/manage_cron.php', { action: 'run_now' });
    log.textContent  = res.output || res.error;
    log.style.color  = res.success ? 'var(--green)' : 'var(--red)';
    toast('<?= h(t('copy_jobs.js.run_done')) ?>', 'success');
}

loadCronStatus();

// ── Launch a job with real-time logs ──────────────────────────────────────────
async function runJob(jobId, btn) {
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner"></span>';

    const titleEl  = document.getElementById('run-title');
    const outputEl = document.getElementById('run-output');
    const spinner  = document.getElementById('run-spinner');

    titleEl.textContent  = '<?= h(t('copy_jobs.js.copy_in_progress')) ?>';
    outputEl.textContent = '';
    outputEl.style.color = 'var(--text)';
    spinner.style.display = 'inline-block';
    document.getElementById('modal-run').classList.add('show');

    const res = await apiPost('/api/run_copy_job.php', { job_id: jobId });

    if (!res.run_id) {
        btn.disabled    = false;
        btn.textContent = '▶ <?= h(t('copy_jobs.run_btn')) ?>';
        spinner.style.display = 'none';
        titleEl.textContent  = '✗ <?= h(t('copy_jobs.js.start_error')) ?>';
        outputEl.textContent = res.error || '<?= h(t('copy_jobs.js.cannot_start')) ?>';
        outputEl.style.color = 'var(--red)';
        return;
    }

    let offsetBytes = 0;
    let offset      = 0;
    let attempts = 0;

    const poll = async () => {
        try {
            const log = await apiPost('/api/poll_copy_log.php', { run_id: res.run_id, offset, last_offset_bytes: offsetBytes });

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
                btn.textContent = '▶ <?= h(t('copy_jobs.run_btn')) ?>';
                spinner.style.display = 'none';

                if (log.status === 'success') {
                    titleEl.textContent  = '✓ <?= h(t('copy_jobs.js.copy_success')) ?>';
                    outputEl.style.color = 'var(--green)';
                    toast('<?= h(t('copy_jobs.js.copy_success_toast')) ?>', 'success');
                } else {
                    titleEl.textContent  = '✗ <?= h(t('copy_jobs.js.copy_error')) ?>';
                    outputEl.style.color = 'var(--red)';
                    toast('<?= h(t('copy_jobs.js.copy_error')) ?>', 'error');
                }
                return;
            }

            attempts++;
            if (attempts <= 3 && outputEl.textContent === '') {
                titleEl.textContent = '<?= h(t('copy_jobs.js.copy_in_progress')) ?> (<?= h(t('copy_jobs.js.initializing')) ?>)';
            }

            setTimeout(poll, 1500);
        } catch (e) {
            setTimeout(poll, 3000);
        }
    };

    setTimeout(poll, 500);
}

async function loadCronStatus() {
    const res = await apiPost('/api/manage_cron.php', { action: 'status' });
    const badge = document.getElementById('cron-status-badge');
    const btnEn = document.getElementById('btn-enable-cron');
    const btnDis = document.getElementById('btn-disable-cron');
    const lineEl = document.getElementById('cron-line-display');
    const supportsCrontab = !!res.supports_crontab;

    if (!supportsCrontab) {
        badge.className = 'badge badge-blue';
        badge.textContent = '<?= h(t('copy_jobs.js.cron_manual')) ?>';
        btnEn.style.display = 'none';
        btnDis.style.display = 'none';
    } else if (res.active) {
        badge.className = 'badge badge-green';
        badge.textContent = '<?= h(t('copy_jobs.js.cron_active')) ?>';
        btnEn.style.display = 'none';
        btnDis.style.display = 'inline-flex';
    } else {
        badge.className = 'badge badge-gray';
        badge.textContent = '<?= h(t('copy_jobs.js.cron_inactive')) ?>';
        btnEn.style.display = 'inline-flex';
        btnDis.style.display = 'none';
    }

    lineEl.style.display = 'block';
    lineEl.textContent = res.cron_line || '';
}

async function runCronNow() {
    const out = document.getElementById('cron-run-output');
    const log = document.getElementById('cron-run-log');
    out.style.display = 'block';
    log.textContent = '<?= h(t('copy_jobs.js.running')) ?>';
    log.style.color = 'var(--text)';

    const res = await apiPost('/api/manage_cron.php', { action: 'run_now' });
    if (!res.run_id) {
        log.textContent = res.error || '<?= h(t('copy_jobs.js.cannot_start')) ?>';
        log.style.color = 'var(--red)';
        toast('<?= h(t('copy_jobs.js.start_error')) ?>', 'error');
        return;
    }

    let offsetBytes = 0;
    let offset = 0;
    const poll = async () => {
        try {
            const state = await apiPost('/api/poll_cron_log.php', { run_id: res.run_id, offset, last_offset_bytes: offsetBytes });

            if (state.lines && state.lines.length > 0) {
                log.textContent += (log.textContent ? '\n' : '') + state.lines.join('\n');
            }

            if (Number.isFinite(Number(state.next_offset_bytes))) {
                offsetBytes = Number(state.next_offset_bytes);
            }
            if (Number.isFinite(Number(state.offset))) {
                offset = Number(state.offset);
            }

            if (state.done) {
                log.style.color = state.status === 'success' ? 'var(--green)' : 'var(--red)';
                toast(state.status === 'success' ? '<?= h(t('copy_jobs.js.run_done')) ?>' : '<?= h(t('copy_jobs.js.run_error')) ?>', state.status === 'success' ? 'success' : 'error');
                return;
            }

            setTimeout(poll, 1500);
        } catch (error) {
            setTimeout(poll, 3000);
        }
    };

    setTimeout(poll, 400);
}

loadCronStatus();

window.showLog = showLog;
window.toggleCopyPassSource = toggleCopyPassSource;
window.deleteJob = deleteJob;
window.openEdit = openEdit;
window.enableCron = enableCron;
window.disableCron = disableCron;
window.runCronNow = runCronNow;
window.runJob = runJob;
</script>

<?php include 'layout_bottom.php'; ?>
