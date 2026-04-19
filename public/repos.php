<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();
Auth::requirePermission('repos.view');

$flash = null;
$defaultRepoPolicy = Notifier::decodePolicy('', 'repo', ['notify_email' => 1]);
$configuredWebUser = getenv('FULGURITE_WEB_USER') ?: 'www-data';
$configuredWebGroup = getenv('FULGURITE_WEB_GROUP') ?: $configuredWebUser;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();

    if ($_POST['action'] === 'add' && Auth::hasPermission('repos.manage')) {
        $name               = trim($_POST['name'] ?? '');
        $path               = trim($_POST['path'] ?? '');
        $passwordSource     = $_POST['password_source'] ?? 'agent';
        $password           = $_POST['password'] ?? '';
        $infisicalSecretName = trim($_POST['infisical_secret_name'] ?? '');
        $desc               = trim($_POST['description'] ?? '');
        $alertHours         = (int) ($_POST['alert_hours'] ?? AppConfig::backupAlertHours());
        $notificationPolicy = Notifier::encodePolicy(
            Notifier::parsePolicyPost($_POST, 'repo_add', 'repo', $defaultRepoPolicy),
            'repo'
        );
        $notifyEmail        = Notifier::policyHasChannels(json_decode($notificationPolicy, true) ?: [], 'repo') ? 1 : 0;
        $initIfMissing      = isset($_POST['init_if_missing']);

        // Validate according to password source
        $passwordOk = ($passwordSource === 'infisical') ? !empty($infisicalSecretName) : !empty($password);

        if ($name && $path && $passwordOk) {
            try {
                $created = ProvisioningManager::createRepo([
                    'name' => $name,
                    'path' => $path,
                    'password_source' => $passwordSource,
                    'password' => $password,
                    'infisical_secret_name' => $infisicalSecretName,
                    'description' => $desc,
                    'alert_hours' => $alertHours,
                    'notify_email' => $notifyEmail,
                    'notification_policy' => $notificationPolicy,
                    'init_if_missing' => $initIfMissing,
                ]);
                $flash = ['type' => 'success', 'msg' => !empty($created['initialized'])
                    ? t('flash.repos.initialized', ['name' => $name])
                    : t('flash.repos.added', ['name' => $name])];
            } catch (Throwable $e) {
                $flash = ['type' => 'danger', 'msg' => htmlspecialchars($e->getMessage())];
            }
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.repos.required_fields')];
        }
    }

    if ($_POST['action'] === 'edit' && Auth::hasPermission('repos.manage')) {
        $id          = (int) ($_POST['repo_id'] ?? 0);
        $alertHours  = (int) ($_POST['alert_hours'] ?? AppConfig::backupAlertHours());
        $desc        = trim($_POST['description'] ?? '');
        $notificationPolicy = Notifier::encodePolicy(
            Notifier::parsePolicyPost($_POST, 'repo_edit', 'repo', $defaultRepoPolicy),
            'repo'
        );
        $notifyEmail = Notifier::policyHasChannels(json_decode($notificationPolicy, true) ?: [], 'repo') ? 1 : 0;
        $snapshotRefreshEnabled = isset($_POST['snapshot_refresh_enabled']) ? 1 : 0;
        $snapshotRefreshMode = $_POST['snapshot_refresh_mode'] ?? 'auto';
        $snapshotRefreshInterval = $snapshotRefreshMode === 'interval'
            ? max(1, (int) ($_POST['snapshot_refresh_interval_minutes'] ?? 30))
            : null;
        if ($id) {
            $db = Database::getInstance();
            $db->prepare("UPDATE repos SET alert_hours=?, notify_email=?, description=?, notification_policy=?, snapshot_refresh_enabled=?, snapshot_refresh_interval_minutes=? WHERE id=?")
               ->execute([$alertHours, $notifyEmail, $desc, $notificationPolicy, $snapshotRefreshEnabled, $snapshotRefreshInterval, $id]);
            $flash = ['type' => 'success', 'msg' => t('flash.repos.updated')];
        }
    }

    if ($_POST['action'] === 'delete' && Auth::hasPermission('repos.manage')) {
        $id   = (int) ($_POST['repo_id'] ?? 0);
        $repo = RepoManager::getById($id);
        $deleteFiles = isset($_POST['delete_files']);
        if ($repo) {
            try {
                RepoManager::delete($id, $deleteFiles);
                Auth::log('repo_delete', "Dépôt supprimé: {$repo['name']}" . ($deleteFiles ? ' (fichiers supprimés)' : ''));
                $flash = ['type' => 'success', 'msg' => t('flash.repos.deleted')];
            } catch (Throwable $e) {
                $flash = ['type' => 'danger', 'msg' => htmlspecialchars($e->getMessage())];
            }
        }
    }
}

$repos  = Auth::filterAccessibleRepos(RepoManager::getAll());
$repoDiskStatuses = DiskSpaceMonitor::getStatusMapByContext('repo');
$repoForecastMap = [];
$repoForecasts = DiskSpaceMonitor::getRepoForecasts(array_map(static fn(array $repo): int => (int) ($repo['id'] ?? 0), $repos));
foreach ($repoForecasts as $forecast) {
    $repoForecastMap[(int) ($forecast['repo_id'] ?? 0)] = $forecast;
}
$latestRepoStats = [];
$latestRepoStatsStmt = Database::getInstance()->query("
    SELECT h.repo_id, h.total_size, h.total_file_count, h.recorded_at
    FROM repo_stats_history h
    JOIN (
        SELECT repo_id, MAX(recorded_at) AS recorded_at
        FROM repo_stats_history
        GROUP BY repo_id
    ) latest
      ON latest.repo_id = h.repo_id
     AND latest.recorded_at = h.recorded_at
");
foreach ($latestRepoStatsStmt->fetchAll() as $row) {
    $latestRepoStats[(int) $row['repo_id']] = $row;
}

foreach ($repos as &$repo) {
    $repoId = (int) ($repo['id'] ?? 0);
    $repo['_latest_total_size'] = (int) ($latestRepoStats[$repoId]['total_size'] ?? 0);
    $repo['_latest_file_count'] = (int) ($latestRepoStats[$repoId]['total_file_count'] ?? 0);
    $repo['_latest_stats_at'] = (string) ($latestRepoStats[$repoId]['recorded_at'] ?? '');
    $repo['_disk_status'] = $repoDiskStatuses[$repoId] ?? null;
    $repo['_forecast'] = $repoForecastMap[$repoId] ?? null;
}
unset($repo);

$title  = t('repos.title');
$active = 'repos';
$actions = Auth::hasPermission('repos.manage')
    ? '<button type="button" class="btn" onclick="document.getElementById(\'modal-add\').classList.add(\'show\')">+ ' . h(t('repos.add_btn')) . '</button>'
    : '';

include 'layout_top.php';
?>

<?php if (empty($repos)): ?>
<div class="empty-state">
    <div style="font-size:15px;margin-bottom:8px"><?= t('repos.empty_title') ?></div>
    <?php if (Auth::hasPermission('repos.manage')): ?>
    <div class="flex gap-2" style="justify-content:center">
        <a class="btn btn-primary" href="<?= routePath('/quick_backup.php') ?>"><?= t('repos.quick_flow') ?></a>
        <button type="button" class="btn" onclick="document.getElementById('modal-add').classList.add('show')"><?= t('repos.add_short') ?></button>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th><?= t('repos.table.name') ?></th><th><?= t('repos.table.path') ?></th><th><?= t('repos.table.storage') ?></th><th><?= t('repos.table.alert_threshold') ?></th><th><?= t('repos.table.notifications') ?></th><th><?= t('repos.table.actions') ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($repos as $repo): ?>
            <tr>
                <?php
                    $diskStatus = is_array($repo['_disk_status'] ?? null) ? $repo['_disk_status'] : null;
                    $forecast = is_array($repo['_forecast'] ?? null) ? $repo['_forecast'] : null;
                    $repoSize = (int) ($repo['_latest_total_size'] ?? 0);
                    $repoFileCount = (int) ($repo['_latest_file_count'] ?? 0);
                    $storagePct = null;
                    if ($diskStatus && (int) ($diskStatus['total_bytes'] ?? 0) > 0 && $repoSize > 0) {
                        $storagePct = round(($repoSize / (int) $diskStatus['total_bytes']) * 100, 2);
                    }
                    $storageBadgeClass = 'badge-gray';
                    $storageBadgeLabel = t('repos.storage.not_probed');
                    $storageHint = t('repos.storage.hint_not_probed');
                    if ($diskStatus) {
                        $storageBadgeClass = match($diskStatus['severity']) {
                            'critical', 'error' => 'badge-red',
                            'warning' => 'badge-yellow',
                            default => 'badge-green'
                        };
                        $storageBadgeLabel = match($diskStatus['severity']) {
                            'critical' => t('repos.storage.critical'),
                            'error' => t('repos.storage.error'),
                            'warning' => t('repos.storage.warning'),
                            default => t('repos.storage.ok')
                        };
                        $storageHint = t('repos.storage.hint_last', ['date' => formatDateForDisplay((string) ($diskStatus['checked_at'] ?? ''), 'd/m/Y H:i:s', appServerTimezone())]);
                    }
                ?>
                <td>
                    <a href="<?= routePath('/explore.php', ['repo' => $repo['id']]) ?>" style="font-weight:500"><?= h($repo['name']) ?></a>
                    <?php if (!empty($repo['description'])): ?>
                    <div style="font-size:11px;color:var(--text2);margin-top:2px"><?= h($repo['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="mono">
                    <?= h($repo['path']) ?>
                    <?php if (!empty($repo['_latest_stats_at'])): ?>
                    <div style="font-size:11px;color:var(--text2);margin-top:2px"><?= t('repos.stats_at', ['date' => h(formatDateForDisplay($repo['_latest_stats_at'], 'd/m/Y H:i:s', appServerTimezone()))]) ?></div>
                    <?php endif; ?>
                </td>
                <td style="min-width:240px">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                        <span id="repo-storage-badge-<?= (int) $repo['id'] ?>" class="badge <?= $storageBadgeClass ?>"><?= $storageBadgeLabel ?></span>
                        <?php if ($repoSize > 0): ?>
                        <span style="font-size:12px;font-weight:600"><?= formatBytes($repoSize) ?></span>
                        <?php else: ?>
                        <span style="font-size:12px;color:var(--text2)"><?= t('repos.size_unknown') ?></span>
                        <?php endif; ?>
                        <?php if ($storagePct !== null): ?>
                        <span style="font-size:12px;color:var(--text2)"><?= number_format($storagePct, 2) ?>% du volume</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px;color:var(--text2)">
                        <?= $repoFileCount > 0 ? t('repos.files_indexed', ['count' => number_format($repoFileCount)]) : t('repos.files_unavailable') ?>
                    </div>
                    <div id="repo-storage-hint-<?= (int) $repo['id'] ?>" style="font-size:11px;color:var(--text2);margin-top:4px"><?= h($storageHint) ?></div>
                    <?php if ($forecast): ?>
                    <div style="font-size:11px;color:var(--text2);margin-top:4px">
                        <?= t('repos.forecast.trend', ['rate' => h(DiskSpaceMonitor::formatGrowthRate($forecast['growth_bytes_per_day'] !== null ? (float) $forecast['growth_bytes_per_day'] : null))]) ?>
                        <?php if (($forecast['status'] ?? '') === 'growing' && $forecast['projected_days_until_full'] !== null): ?>
                        <?= t('repos.forecast.full_in', ['horizon' => h(DiskSpaceMonitor::formatForecastHorizon((float) $forecast['projected_days_until_full']))]) ?>
                        <?php else: ?>
                        <?= t('repos.forecast.msg', ['message' => h($forecast['message'])]) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div id="repo-storage-capacity-<?= (int) $repo['id'] ?>" style="font-size:11px;color:var(--text2);margin-top:4px;<?= $diskStatus ? '' : 'display:none' ?>">
                        <?= t('repos.capacity', ['free' => formatBytes((int) ($diskStatus['free_bytes'] ?? 0)), 'total' => formatBytes((int) ($diskStatus['total_bytes'] ?? 0))]) ?>
                        <?php if (($diskStatus['used_percent'] ?? null) !== null): ?>
                        <?= t('repos.capacity_pct', ['pct' => number_format((float) $diskStatus['used_percent'], 1)]) ?>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div><span class="badge badge-gray"><?= (int)($repo['alert_hours'] ?? AppConfig::backupAlertHours()) ?>h</span></div>
                    <div id="repo-probe-time-<?= (int) $repo['id'] ?>" style="font-size:11px;color:var(--text2);margin-top:4px;<?= $diskStatus ? '' : 'display:none' ?>">
                        <?php if ($diskStatus): ?><?= t('repos.probe_time', ['date' => h(formatDateForDisplay((string) ($diskStatus['checked_at'] ?? ''), 'd/m/Y H:i:s', appServerTimezone()))]) ?><?php endif; ?>
                    </div>
                    <?php if ($forecast && ($forecast['status'] ?? '') === 'growing' && $forecast['projected_days_until_full'] !== null): ?>
                    <?php
                        $forecastBadgeClass = match($forecast['severity']) {
                            'critical' => 'badge-red',
                            'warning' => 'badge-yellow',
                            default => 'badge-green'
                        };
                    ?>
                    <div style="margin-top:6px"><span class="badge <?= $forecastBadgeClass ?>"><?= t('repos.projection', ['horizon' => h(DiskSpaceMonitor::formatForecastHorizon((float) $forecast['projected_days_until_full']))]) ?></span></div>
                    <?php endif; ?>
                </td>
                <td><?= renderNotificationPolicySummary(Notifier::getEntityPolicy('repo', $repo), 'repo') ?></td>
                <td>
                    <div class="flex gap-2">
                                <a href="<?= routePath('/explore.php', ['repo' => $repo['id']]) ?>" class="btn btn-sm"><?= t('repos.explore') ?></a>
                        <?php if (Auth::hasPermission('repos.manage')): ?>
                        <button type="button" class="btn btn-sm" onclick="openEdit(<?= h(json_encode($repo)) ?>)"><?= t('repos.edit') ?></button>
                        <button type="button" class="btn btn-sm" onclick="return probeRepoDisk(event, <?= (int) $repo['id'] ?>, <?= h(json_encode($repo['name'])) ?>)"><?= t('repos.probe') ?></button>
                        <button type="button" class="btn btn-sm" onclick="enqueueSnapshotRefresh(<?= $repo['id'] ?>, <?= h(json_encode($repo['name'])) ?>)" title="<?= h(t('repos.refresh_snapshots_title')) ?>"><?= t('repos.refresh_snapshots') ?></button>
                        <button type="button" class="btn btn-sm" onclick='testSavedNotificationPolicy("repo", "stale", <?= h(json_encode(Notifier::getEntityPolicy('repo', $repo))) ?>, <?= h(json_encode($repo["name"])) ?>)'><?= t('repos.test_notif') ?></button>
                        <button type="button" class="btn btn-sm btn-danger"
                            onclick="requireReauth(() => showDeleteModal(<?= h(json_encode($repo)) ?>), '<?= h(t('repos.delete_reauth', ['name' => $repo['name']])) ?>')">
                            <?= t('repos.delete_btn') ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if (Auth::hasPermission('repos.manage')): ?>
<div id="modal-add" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('repos.modal_add.title') ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label class="form-label"><?= t('repos.modal_add.name') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="web-prod-01" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('repos.modal_add.path') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="path" class="form-control" placeholder="/backups/web-prod-01  ou  sftp:user@host:/backups/repo  ou  sftp://user@host/backups/repo" required>
            </div>

            <!-- Password source -->
            <div class="form-group">
                <label class="form-label"><?= t('repos.modal_add.password_source') ?> <span style="color:var(--red)">*</span></label>
                <select name="password_source" id="add-pass-source" class="form-control"
                        onchange="togglePassSource('add', this.value)">
                    <option value="local"><?= t('repos.modal_add.pass_local') ?></option>
                    <option value="agent" selected><?= t('repos.modal_add.pass_agent') ?></option>
                    <?php if (InfisicalClient::isConfigured()): ?>
                    <option value="infisical"><?= t('repos.modal_add.pass_infisical') ?></option>
                    <?php else: ?>
                    <option value="infisical" disabled title="<?= h(t('repos.modal_add.pass_infisical_disabled_title')) ?>"><?= t('repos.modal_add.pass_infisical_disabled') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div id="add-pass-file" class="form-group">
                <label class="form-label"><?= t('repos.modal_add.password') ?></label>
                <input type="password" name="password" class="form-control">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('repos.modal_add.password_hint') ?>
                </div>
            </div>
            <div id="add-pass-infisical" class="form-group" style="display:none">
                <label class="form-label"><?= t('repos.modal_add.infisical_name') ?></label>
                <input type="text" name="infisical_secret_name" class="form-control" placeholder="RESTIC_REPO_PASSWORD">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('repos.modal_add.infisical_hint') ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= t('repos.modal_add.description') ?></label>
                <input type="text" name="description" class="form-control" placeholder="Serveur web production">
            </div>
            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('repos.modal_add.alert_hours') ?></label>
                    <input type="number" name="alert_hours" class="form-control" value="<?= AppConfig::backupAlertHours() ?>" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('repos.modal_add.options') ?></label>
                    <label style="display:flex;align-items:center;gap:8px;margin-top:6px;cursor:pointer">
                        <input type="checkbox" name="init_if_missing" value="1" style="accent-color:var(--accent);width:16px;height:16px">
                        <span style="font-size:13px"><?= t('repos.modal_add.init_if_missing') ?></span>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('repos.modal_add.notifications') ?></label>
                <?= renderNotificationPolicyEditor('repo_add', 'repo', $defaultRepoPolicy) ?>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-add').classList.remove('show')"><?= t('repos.modal_add.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('repos.modal_add.submit') ?></button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('repos.modal_edit.title') ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="repo_id" id="edit-repo-id">
            <div class="form-group">
                <label class="form-label"><?= t('repos.modal_edit.description') ?></label>
                <input type="text" name="description" id="edit-description" class="form-control">
            </div>
            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('repos.modal_edit.alert_hours') ?></label>
                    <input type="number" name="alert_hours" id="edit-alert-hours" class="form-control" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('repos.modal_edit.notifications') ?></label>
                    <?= renderNotificationPolicyEditor('repo_edit', 'repo', $defaultRepoPolicy) ?>
                </div>
            </div>
            <div class="form-group" style="margin-top:12px;border-top:1px solid var(--border);padding-top:14px">
                <label class="form-label" style="margin-bottom:8px"><?= t('repos.modal_edit.snapshot_refresh') ?></label>
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:10px">
                    <input type="checkbox" name="snapshot_refresh_enabled" id="edit-snapshot-refresh-enabled" value="1"
                           style="accent-color:var(--accent);width:16px;height:16px;margin-top:2px;flex-shrink:0"
                           onchange="toggleRefreshMode()">
                    <span>
                        <span style="font-weight:500"><?= t('repos.modal_edit.snapshot_auto_label') ?></span><br>
                        <span style="font-size:12px;color:var(--text2)"><?= t('repos.modal_edit.snapshot_auto_hint') ?></span>
                    </span>
                </label>
                <div id="edit-refresh-mode-section">
                    <div style="font-size:12px;font-weight:500;color:var(--text2);margin-bottom:8px"><?= t('repos.modal_edit.refresh_mode') ?></div>
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:8px">
                        <input type="radio" name="snapshot_refresh_mode" id="edit-refresh-mode-auto" value="auto"
                               style="accent-color:var(--accent);width:15px;height:15px;margin-top:2px;flex-shrink:0"
                               onchange="toggleRefreshInterval()">
                        <span>
                            <span style="font-weight:500"><?= t('repos.modal_edit.refresh_auto') ?></span><br>
                            <span style="font-size:12px;color:var(--text2)"><?= t('repos.modal_edit.refresh_auto_desc') ?></span>
                        </span>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:8px">
                        <input type="radio" name="snapshot_refresh_mode" id="edit-refresh-mode-interval" value="interval"
                               style="accent-color:var(--accent);width:15px;height:15px;margin-top:2px;flex-shrink:0"
                               onchange="toggleRefreshInterval()">
                        <span>
                            <span style="font-weight:500"><?= t('repos.modal_edit.refresh_interval') ?></span> <span style="font-size:12px;color:var(--text2)"><?= t('repos.modal_edit.refresh_interval_hint') ?></span><br>
                            <span style="font-size:12px;color:var(--text2)"><?= t('repos.modal_edit.refresh_interval_desc') ?></span>
                        </span>
                    </label>
                    <div id="edit-refresh-interval-row" style="display:none;margin-left:25px;margin-top:4px">
                        <label style="font-size:12px;color:var(--text2)"><?= t('repos.modal_edit.interval_label') ?></label>
                        <select name="snapshot_refresh_interval_minutes" id="edit-refresh-interval-minutes"
                                style="margin-left:8px;padding:4px 8px;border-radius:6px;border:1px solid var(--border);background:var(--bg2);color:var(--text1);font-size:13px">
                            <option value="5">5 minutes</option>
                            <option value="10">10 minutes</option>
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="60">1 heure</option>
                            <option value="120">2 heures</option>
                            <option value="360">6 heures</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-edit').classList.remove('show')"><?= t('repos.modal_edit.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('repos.modal_edit.save') ?></button>
            </div>
        </form>
    </div>
</div>

<div id="modal-delete" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('repos.modal_delete.title') ?></div>
        <div id="delete-modal-content" style="margin-bottom:20px;color:var(--text1)">
            <p id="delete-repo-name-display" style="font-weight:500;margin-bottom:12px"></p>
            <p style="margin-bottom:12px"><?= t('repos.modal_delete.question') ?></p>
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;padding:12px;background:var(--bg2);border-radius:6px;border:1px solid var(--border)">
                <input type="checkbox" id="delete-files-checkbox" style="accent-color:var(--accent);width:16px;height:16px;margin-top:2px;flex-shrink:0">
                <span>
                    <span style="font-weight:500;display:block" id="delete-files-label"><?= t('repos.modal_delete.files_label') ?></span>
                    <span style="font-size:12px;color:var(--text2);display:block;margin-top:4px" id="delete-files-hint-prefix"><?= t('repos.modal_delete.files_hint', ['path' => '']) ?><span id="delete-repo-path-display" class="mono"></span></span>
                </span>
            </label>
        </div>
        <div class="flex gap-2" style="justify-content:flex-end">
            <button type="button" class="btn" onclick="document.getElementById('modal-delete').classList.remove('show')"><?= t('repos.modal_delete.cancel') ?></button>
            <button type="button" class="btn btn-danger" onclick="submitDeleteConfirmed()"><?= t('repos.modal_delete.confirm') ?></button>
        </div>
    </div>
</div>

<form id="form-delete" method="POST" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="repo_id" id="delete-repo-id">
    <input type="hidden" name="delete_files" id="delete-files-input" value="0">
</form>

<script<?= cspNonceAttr() ?>>
function showDeleteModal(repo) {
    document.getElementById('delete-repo-id').value = repo.id;
    document.getElementById('delete-repo-name-display').textContent = '<?= h(t('repos.js.repo_prefix')) ?>' + repo.name;
    document.getElementById('delete-repo-path-display').textContent = repo.path;
    document.getElementById('delete-files-checkbox').checked = false;
    document.getElementById('modal-delete').classList.add('show');
}
function submitDeleteConfirmed() {
    const deleteFiles = document.getElementById('delete-files-checkbox').checked;
    document.getElementById('delete-files-input').value = deleteFiles ? '1' : '0';
    document.getElementById('form-delete').submit();
}
function openEdit(repo) {
    document.getElementById('edit-repo-id').value     = repo.id;
    document.getElementById('edit-description').value = repo.description || '';
    document.getElementById('edit-alert-hours').value = repo.alert_hours || <?= AppConfig::backupAlertHours() ?>;
    const refreshEnabled = repo.snapshot_refresh_enabled === undefined || repo.snapshot_refresh_enabled == 1;
    document.getElementById('edit-snapshot-refresh-enabled').checked = refreshEnabled;
    const intervalMinutes = repo.snapshot_refresh_interval_minutes ? parseInt(repo.snapshot_refresh_interval_minutes) : 0;
    const mode = intervalMinutes > 0 ? 'interval' : 'auto';
    document.getElementById('edit-refresh-mode-auto').checked = mode === 'auto';
    document.getElementById('edit-refresh-mode-interval').checked = mode === 'interval';
    const sel = document.getElementById('edit-refresh-interval-minutes');
    const availableVals = Array.from(sel.options).map(o => parseInt(o.value));
    sel.value = availableVals.includes(intervalMinutes) ? intervalMinutes : 30;
    toggleRefreshMode();
    toggleRefreshInterval();
    let policy = null;
    if (repo.notification_policy) {
        try {
            policy = JSON.parse(repo.notification_policy);
        } catch (error) {
            policy = null;
        }
    }
    if (!policy) {
        policy = <?= json_encode($defaultRepoPolicy) ?>;
    }
    window.applyNotificationPolicyToEditor('repo_edit', policy);
    document.getElementById('modal-edit').classList.add('show');
}
function toggleRefreshMode() {
    const enabled = document.getElementById('edit-snapshot-refresh-enabled').checked;
    document.getElementById('edit-refresh-mode-section').style.display = enabled ? '' : 'none';
}
function toggleRefreshInterval() {
    const isInterval = document.getElementById('edit-refresh-mode-interval').checked;
    document.getElementById('edit-refresh-interval-row').style.display = isInterval ? '' : 'none';
}
function enqueueSnapshotRefresh(repoId, repoName) {
    fetch('<?= routePath('/api/manage_queue.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrfToken() ?>'},
        body: JSON.stringify({action: 'enqueue_snapshot_refresh', repo_id: repoId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toast('<?= h(t('repos.js.refresh_queued_prefix')) ?>' + repoName + '<?= h(t('repos.js.refresh_queued_suffix')) ?>', 'success');
        } else {
            toast('<?= h(t('repos.js.error_prefix')) ?>' + (data.error || '<?= h(t('common.unknown')) ?>'), 'error');
        }
    })
    .catch(() => {
        toast('<?= h(t('repos.js.network_error_enqueue')) ?>', 'error');
    });
}
function updateRepoProbeUi(repoId, display) {
    if (!display) return;
    const badgeEl = document.getElementById('repo-storage-badge-' + repoId);
    const hintEl = document.getElementById('repo-storage-hint-' + repoId);
    const capacityEl = document.getElementById('repo-storage-capacity-' + repoId);
    const timeEl = document.getElementById('repo-probe-time-' + repoId);
    if (badgeEl) {
        badgeEl.className = 'badge ' + (display.badge_class || 'badge-green');
        badgeEl.textContent = display.badge_label || 'OK';
    }
    if (hintEl && display.storage_hint) {
        hintEl.textContent = display.storage_hint;
    }
    if (capacityEl && display.capacity_text) {
        capacityEl.style.display = '';
        capacityEl.textContent = display.capacity_text;
    }
    if (timeEl && display.checked_at_display) {
        timeEl.style.display = '';
        timeEl.textContent = 'Sonde ' + display.checked_at_display;
    }
}
function probeRepoDisk(event, repoId, repoName) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    fetch('<?= routePath('/api/probe_disk_space.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrfToken() ?>'},
        body: JSON.stringify({context_type: 'repo', context_id: repoId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateRepoProbeUi(repoId, data.check_display || null);
            toast((data.message || '<?= h(t('repos.js.probe_done_prefix')) ?>' + repoName + '<?= h(t('repos.js.probe_done_suffix')) ?>') + (data.check_display && data.check_display.checked_at_display ? ' (' + data.check_display.checked_at_display + ')' : ''), 'success');
        } else {
            toast('<?= h(t('repos.js.error_prefix')) ?>' + (data.error || '<?= h(t('common.unknown')) ?>'), 'error');
        }
    })
    .catch(() => {
        toast('<?= h(t('repos.js.network_error_probe')) ?>', 'error');
    });
    return false;
}
function togglePassSource(prefix, val) {
    document.getElementById(prefix + '-pass-file').style.display      = (val === 'agent' || val === 'local' || val === 'file') ? '' : 'none';
    document.getElementById(prefix + '-pass-infisical').style.display = val === 'infisical' ? '' : 'none';
}

window.showDeleteModal = showDeleteModal;
window.submitDeleteConfirmed = submitDeleteConfirmed;
window.openEdit = openEdit;
window.toggleRefreshMode = toggleRefreshMode;
window.toggleRefreshInterval = toggleRefreshInterval;
window.enqueueSnapshotRefresh = enqueueSnapshotRefresh;
window.probeRepoDisk = probeRepoDisk;
window.togglePassSource = togglePassSource;
</script>
<?php endif; ?>

<?php include 'layout_bottom.php'; ?>
