<?php
$snapshotLabels = [];
$snapshotDatasets = [];
if (!empty($history)) {
    $byRepo = [];
    $colors = ['#58a6ff', '#3fb950', '#bc8cff', '#d29922', '#f85149'];
    foreach ($history as $item) {
        $time = substr($item['recorded_at'], 0, 13);
        $byRepo[$item['repo_name']][$time] = (int) $item['snapshot_count'];
        $snapshotLabels[$time] = true;
    }
    ksort($snapshotLabels);
    $snapshotLabels = array_keys($snapshotLabels);
    $colorIndex = 0;
    foreach ($byRepo as $name => $data) {
        $values = [];
        foreach ($snapshotLabels as $label) {
            $values[] = $data[$label] ?? null;
        }
        $snapshotDatasets[] = [
            'label' => $name,
            'data' => $values,
            'borderColor' => $colors[$colorIndex % count($colors)],
            'backgroundColor' => $colors[$colorIndex % count($colors)] . '22',
            'tension' => 0.3,
            'fill' => true,
            'spanGaps' => true,
        ];
        $colorIndex++;
    }
}
?>

<?php if ($bjTotal === 0 && Auth::hasPermission('backup_jobs.manage')): ?>
<div style="background:linear-gradient(135deg,color-mix(in srgb,var(--accent) 12%,var(--bg2)) 0%,var(--bg2) 100%);
     border:1px solid color-mix(in srgb,var(--accent) 40%,var(--border));
     border-radius:10px;padding:20px 24px;margin-bottom:20px;
     display:flex;align-items:center;gap:20px;flex-wrap:wrap">
    <div style="font-size:32px">⚡</div>
    <div style="flex:1;min-width:200px">
        <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px">
            <?= h(t('dashboard.empty_backup.title')) ?>
        </div>
        <div style="font-size:13px;color:var(--text2)">
            <?= h(t('dashboard.empty_backup.desc')) ?>
        </div>
    </div>
    <a href="<?= routePath('/quick_backup.php') ?>" class="btn btn-primary" style="white-space:nowrap;font-size:14px">
        ⚡ <?= h(t('dashboard.empty_backup.cta')) ?>
    </a>
</div>
<?php endif; ?>

<?php if ($alertCount > 0): ?>
<div class="alert alert-warning mb-4" style="display:flex;align-items:center;gap:10px">
    <span><strong><?= h(t('dashboard.alerts.repos_in_alert', ['count' => (string) $alertCount])) ?></strong> - <?= h(t('dashboard.alerts.repos_in_alert_desc')) ?>.</span>
</div>
<?php endif; ?>

<?php if ($pendingRepoCount > 0): ?>
<div class="alert alert-info mb-4" style="display:flex;align-items:center;gap:10px">
    <span><strong><?= h(t('dashboard.pending.repos_waiting', ['count' => (string) $pendingRepoCount])) ?></strong> - <?= h(t('dashboard.pending.repos_waiting_desc')) ?>.</span>
</div>
<?php endif; ?>

<?php if (($diskSummary['critical'] ?? 0) > 0 || ($diskSummary['warning'] ?? 0) > 0 || ($diskSummary['error'] ?? 0) > 0): ?>
<div class="alert alert-warning mb-4" style="display:flex;align-items:center;gap:10px">
    <span>
        <strong><?= h(t('dashboard.disk.alert_targets', ['count' => (string) ((int) (($diskSummary['critical'] ?? 0) + ($diskSummary['warning'] ?? 0) + ($diskSummary['error'] ?? 0)))])) ?></strong>
        - <?= h(t('dashboard.disk.alert_targets_desc')) ?>.
    </span>
</div>
<?php endif; ?>

<?php if (($forecastSummary['critical'] ?? 0) > 0 || ($forecastSummary['warning'] ?? 0) > 0): ?>
<div class="alert alert-warning mb-4" style="display:flex;align-items:center;gap:10px">
    <span>
        <strong><?= h(t('dashboard.forecast.risk_repos', ['count' => (string) ((int) (($forecastSummary['critical'] ?? 0) + ($forecastSummary['warning'] ?? 0)))])) ?></strong>
        - <?= h(t('dashboard.forecast.breakdown', ['critical' => (string) ((int) ($forecastSummary['critical'] ?? 0)), 'warning' => (string) ((int) ($forecastSummary['warning'] ?? 0))])) ?>.
    </span>
</div>
<?php endif; ?>

<div style="background:linear-gradient(135deg,#1a2332 0%,#0d1117 60%,#1a2332 100%);
     border:1px solid var(--border);border-radius:12px;padding:28px 32px;margin-bottom:20px;
     position:relative;overflow:hidden">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
        <div style="width:44px;height:44px;background:var(--accent2);border-radius:10px;
             display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff"><?= h(AppConfig::appLogoLetter()) ?></div>
        <div>
            <div style="font-size:18px;font-weight:600"><?= h(AppConfig::appName()) ?></div>
            <div style="font-size:12px;color:var(--text2)"><?= h(AppConfig::appSubtitle()) ?> - <?= h(formatCurrentDisplayDate('l j F Y')) ?></div>
        </div>
        <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
            <span id="last-update" style="font-size:11px;color:var(--text2)"></span>
            <button class="btn btn-sm" onclick="refreshDashboard()" title="<?= h(t('dashboard.refresh')) ?>"><?= h(t('dashboard.refresh')) ?></button>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px">
        <?php
        $heroStats = [
            ['val' => count($repos), 'label' => t('dashboard.stat.repos'), 'color' => 'var(--accent)', 'id' => 'stat-repos'],
            ['val' => $totalSnapshots, 'label' => t('dashboard.stat.snapshots'), 'color' => 'var(--green)', 'id' => 'stat-snapshots'],
            ['val' => $okRepoCount, 'label' => t('dashboard.stat.repos_ok'), 'color' => 'var(--green)', 'id' => 'stat-ok'],
            ['val' => $alertCount, 'label' => t('dashboard.stat.alerts'), 'color' => $alertCount > 0 ? 'var(--red)' : 'var(--text2)', 'id' => 'stat-alerts'],
        ];
        foreach ($heroStats as $stat):
        ?>
        <div style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:16px">
            <div id="<?= $stat['id'] ?>" style="font-size:28px;font-weight:700;color:<?= $stat['color'] ?>;line-height:1"><?= $stat['val'] ?></div>
            <div style="font-size:12px;color:var(--text2);margin-top:4px"><?= $stat['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <?= h(t('dashboard.disk.title')) ?>
        <div class="flex gap-2">
            <button type="button" class="btn btn-sm" onclick="probeDiskNow()"><?= h(t('dashboard.disk.probe_now')) ?></button>
            <a href="<?= routePath('/stats.php') ?>" class="btn btn-sm"><?= h(t('dashboard.disk.open_stats')) ?></a>
        </div>
    </div>
    <?php if (!empty($diskStatuses)): ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= h(t('dashboard.disk.target')) ?></th><th><?= h(t('dashboard.disk.free')) ?></th><th><?= h(t('dashboard.disk.usage')) ?></th><th><?= h(t('dashboard.disk.status')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($diskStatuses as $diskStatus): ?>
        <?php
            $badgeClass = match($diskStatus['severity']) {
                'critical', 'error' => 'badge-red',
                'warning' => 'badge-yellow',
                default => 'badge-green'
            };
            $badgeLabel = match($diskStatus['severity']) {
                'critical' => t('dashboard.disk.severity.critical'),
                'error' => t('dashboard.disk.severity.probe_ko'),
                'warning' => 'Warning',
                default => 'OK'
            };
        ?>
        <tr>
            <td>
                <div style="font-weight:500"><?= h($diskStatus['context_name']) ?></div>
                <div style="font-size:11px;color:var(--text2)"><?= h($diskStatus['path']) ?><?= !empty($diskStatus['host_name']) ? ' @ ' . h($diskStatus['host_name']) : '' ?></div>
            </td>
            <td><?= formatBytes((int) $diskStatus['free_bytes']) ?></td>
            <td><?= $diskStatus['used_percent'] !== null ? number_format((float) $diskStatus['used_percent'], 1) . '%' : '-' ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:24px"><?= h(t('dashboard.disk.empty')) ?></div>
    <?php endif; ?>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <?= h(t('dashboard.forecast.title')) ?>
        <a href="<?= routePath('/repos.php') ?>" class="btn btn-sm"><?= h(t('dashboard.forecast.view_repos')) ?></a>
    </div>
    <?php if (!empty($forecastHighlights)): ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= h(t('dashboard.repo')) ?></th><th><?= h(t('dashboard.forecast.growth')) ?></th><th><?= h(t('dashboard.disk.free')) ?></th><th><?= h(t('dashboard.forecast.projection')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($forecastHighlights as $forecast): ?>
        <?php
            $forecastBadgeClass = match($forecast['severity']) {
                'critical' => 'badge-red',
                'warning' => 'badge-yellow',
                'ok' => 'badge-green',
                default => 'badge-gray'
            };
            $forecastBadgeLabel = match($forecast['status']) {
                'probe_missing' => t('dashboard.forecast.status.to_probe'),
                'stable' => t('dashboard.forecast.status.stable'),
                'insufficient_data' => t('dashboard.forecast.status.pending'),
                default => $forecast['projected_days_until_full'] !== null
                    ? t('dashboard.forecast.status.in', ['horizon' => DiskSpaceMonitor::formatForecastHorizon((float) $forecast['projected_days_until_full'])])
                    : t('dashboard.forecast.status.unavailable')
            };
        ?>
        <tr>
            <td>
                <div style="font-weight:500"><?= h($forecast['repo_name']) ?></div>
                <div style="font-size:11px;color:var(--text2)"><?= h($forecast['message']) ?></div>
            </td>
            <td><?= h(DiskSpaceMonitor::formatGrowthRate($forecast['growth_bytes_per_day'] !== null ? (float) $forecast['growth_bytes_per_day'] : null)) ?></td>
            <td><?= $forecast['free_bytes'] !== null ? formatBytes((int) $forecast['free_bytes']) : '-' ?></td>
            <td><span class="badge <?= $forecastBadgeClass ?>"><?= h($forecastBadgeLabel) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:24px"><?= h(t('dashboard.forecast.empty')) ?></div>
    <?php endif; ?>
</div>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <div class="card">
        <div class="card-header">
            <?= h(t('dashboard.repos_state.title')) ?>
            <a href="<?= routePath('/repos.php') ?>" class="btn btn-sm"><?= h(t('dashboard.manage')) ?></a>
        </div>
        <?php if (empty($repoStatuses)): ?>
        <div class="empty-state" style="padding:32px"><?= h(t('dashboard.repos_state.empty')) ?></div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table" id="repos-table">
            <thead><tr><th><?= h(t('dashboard.repo')) ?></th><th><?= h(t('dashboard.stat.snapshots')) ?></th><th><?= h(t('dashboard.repos_state.last_backup')) ?></th><th><?= h(t('dashboard.disk.status')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($repoStatuses as $status):
                $badgeClass = match($status['status']) {
                    'ok' => 'badge-green',
                    'warning' => 'badge-yellow',
                    'pending' => 'badge-gray',
                    default => 'badge-red'
                };
                $badgeLabel = match($status['status']) {
                    'ok' => 'OK',
                     'warning' => t('dashboard.repos_state.status.old'),
                     'error' => t('common.error'),
                     'no_snap' => t('dashboard.repos_state.status.empty'),
                     'pending' => t('dashboard.repos_state.status.pending'),
                    default => '?'
                };
            ?>
            <tr>
                <td><a href="<?= routePath('/explore.php', ['repo' => $status['repo']['id']]) ?>" style="font-weight:500"><?= h($status['repo']['name']) ?></a></td>
                <td><?= (int) $status['count'] ?></td>
                <td style="font-size:12px">
                    <?php if ($status['status'] === 'pending'): ?>
                     <span class="text-muted"><?= h(t('dashboard.repos_state.waiting_cron')) ?></span>
                    <?php elseif (!empty($status['last_time'])): ?>
                    <?= formatDate($status['last_time']) ?>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><?= h(t('dashboard.activity.title')) ?> <a href="<?= routePath('/logs.php') ?>" class="btn btn-sm"><?= h(t('dashboard.activity.view_all')) ?></a></div>
        <?php if (empty($logs)): ?>
        <div class="empty-state" style="padding:24px"><?= h(t('dashboard.activity.empty')) ?></div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?= h(t('dashboard.activity.user')) ?></th><th><?= h(t('dashboard.activity.action')) ?></th><th><?= h(t('dashboard.activity.date')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= h($log['username']) ?></td>
                <td>
                    <span class="badge badge-gray"><?= h($log['action']) ?></span>
                    <?php if (!empty($log['details'])): ?>
                    <div style="font-size:11px;color:var(--text2);margin-top:2px"><?= h(mb_substr($log['details'], 0, 55)) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:11px;color:var(--text2);white-space:nowrap"><?= formatDate($log['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:16px">
    <?php
    $shortcuts = [
        ['href'=>routePath('/explore.php'), 'icon'=>t('dashboard.shortcuts.explore.icon'), 'label'=>t('dashboard.shortcuts.explore.label'), 'desc'=>t('dashboard.shortcuts.explore.desc')],
        ['href'=>routePath('/restores.php'), 'icon'=>t('dashboard.shortcuts.restores.icon'), 'label'=>t('dashboard.shortcuts.restores.label'), 'desc'=>t('dashboard.shortcuts.restores.desc', ['count' => (string) $restoreCount])],
        ['href'=>routePath('/backup_jobs.php'), 'icon'=>t('dashboard.shortcuts.backup_jobs.icon'), 'label'=>t('dashboard.shortcuts.backup_jobs.label'), 'desc'=>t('dashboard.shortcuts.backup_jobs.desc', ['count' => (string) $bjTotal])],
        ['href'=>routePath('/copy_jobs.php'), 'icon'=>t('dashboard.shortcuts.copy_jobs.icon'), 'label'=>t('dashboard.shortcuts.copy_jobs.label'), 'desc'=>t('dashboard.shortcuts.copy_jobs.desc', ['count' => (string) $copyJobsCount])],
        ['href'=>routePath('/quick_backup.php'), 'icon'=>'⚡', 'label'=>t('dashboard.shortcuts.quick_backup.label'), 'desc'=>t('dashboard.shortcuts.quick_backup.desc')],
    ];
    foreach ($shortcuts as $shortcut):
    ?>
    <a href="<?= $shortcut['href'] ?>" style="display:block;background:var(--bg2);border:1px solid var(--border);
       border-radius:8px;padding:16px;text-decoration:none;transition:border-color .15s">
        <div style="font-size:16px;margin-bottom:6px"><?= $shortcut['icon'] ?></div>
        <div style="font-size:13px;font-weight:500;color:var(--text)"><?= $shortcut['label'] ?></div>
        <div style="font-size:11px;color:var(--text2);margin-top:2px"><?= $shortcut['desc'] ?></div>
    </a>
    <?php endforeach; ?>
</div>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <?php if (!empty($snapshotDatasets)): ?>
    <div class="card">
        <div class="card-header"><?= h(t('dashboard.charts.snapshots_7d')) ?></div>
        <div class="card-body" style="position:relative;height:200px">
            <canvas id="snapshots-chart"></canvas>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($diskHistoryChart['datasets'])): ?>
    <div class="card">
        <div class="card-header"><?= h(t('dashboard.charts.disk_7d')) ?></div>
        <div class="card-body" style="position:relative;height:200px">
            <canvas id="disk-chart"></canvas>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header"><?= h(t('dashboard.charts.disk_7d')) ?></div>
        <div class="empty-state" style="padding:24px"><?= h(t('dashboard.charts.disk_empty')) ?></div>
    </div>
    <?php endif; ?>
</div>

<script<?= cspNonceAttr() ?> type="application/json" id="dashboard-page-data"><?= json_encode([
    'chart' => [
        'labels' => array_map(static fn($label) => formatDateForDisplay($label . ':00:00', 'd/m H:i'), $snapshotLabels),
        'datasets' => $snapshotDatasets,
    ],
    'disk_chart' => [
        'labels' => array_map(static fn($label) => formatDateForDisplay($label . ':00:00', 'd/m H:i'), $diskHistoryChart['labels'] ?? []),
        'datasets' => $diskHistoryChart['datasets'] ?? [],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
