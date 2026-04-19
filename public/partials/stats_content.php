<?php
$colors = ['#58a6ff', '#3fb950', '#bc8cff', '#d29922', '#f85149', '#79c0ff'];
$byRepo = [];
$snapshotLabels = [];
foreach ($history as $item) {
    $time = substr($item['recorded_at'], 0, 13);
    $byRepo[$item['repo_name']][$time] = (int) $item['snapshot_count'];
    $snapshotLabels[$time] = true;
}
ksort($snapshotLabels);
$snapshotLabels = array_keys($snapshotLabels);

$snapshotDatasets = [];
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
        'backgroundColor' => $colors[$colorIndex % count($colors)] . '33',
        'tension' => 0.3,
        'fill' => true,
        'spanGaps' => true,
    ];
    $colorIndex++;
}

$actLabels = array_column($activityByDay, 'day');
$actValues = array_column($activityByDay, 'count');
?>

<?php if ($pendingStatsCount > 0): ?>
<div class="alert alert-info mb-4" style="display:flex;align-items:center;gap:10px">
    <span><strong><?= t($pendingStatsCount > 1 ? 'stats.pending_repos_count_plural' : 'stats.pending_repos_count_singular', ['count' => (string) $pendingStatsCount]) ?></strong> - <?= t('stats.pending_repos_hint') ?></span>
</div>
<?php endif; ?>

<div class="stats-grid mb-4">
    <div class="stat-card"><div class="stat-value"><?= count($repos) ?></div><div class="stat-label"><?= t('stats.repos') ?></div></div>
    <div class="stat-card"><div class="stat-value"><?= array_sum(array_column($repoStats, 'count')) ?></div><div class="stat-label"><?= t('stats.total_snapshots') ?></div></div>
    <div class="stat-card"><div class="stat-value"><?= formatBytes(array_sum(array_column($repoStats, 'total_size'))) ?></div><div class="stat-label"><?= t('stats.total_size') ?></div></div>
    <div class="stat-card"><div class="stat-value"><?= $restoreStats['total'] ?? 0 ?></div><div class="stat-label">Restores (<?= $periodDays ?>j)</div></div>
</div>

<?php if (($diskSummary['critical'] ?? 0) > 0 || ($diskSummary['warning'] ?? 0) > 0 || ($diskSummary['error'] ?? 0) > 0): ?>
<div class="alert alert-warning mb-4" style="display:flex;align-items:center;gap:10px">
    <span>
        <strong><?= (int) (($diskSummary['critical'] ?? 0) + ($diskSummary['warning'] ?? 0) + ($diskSummary['error'] ?? 0)) ?> cible<?= (($diskSummary['critical'] ?? 0) + ($diskSummary['warning'] ?? 0) + ($diskSummary['error'] ?? 0)) > 1 ? 's' : '' ?> disque en alerte</strong>
        - warning <?= (int) ($diskSummary['warning'] ?? 0) ?>, critique <?= (int) ($diskSummary['critical'] ?? 0) ?>, probe KO <?= (int) ($diskSummary['error'] ?? 0) ?>.
    </span>
</div>
<?php endif; ?>

<?php if (($forecastSummary['critical'] ?? 0) > 0 || ($forecastSummary['warning'] ?? 0) > 0): ?>
<div class="alert alert-warning mb-4" style="display:flex;align-items:center;gap:10px">
    <span>
        <strong><?= t(((($forecastSummary['critical'] ?? 0) + ($forecastSummary['warning'] ?? 0)) > 1) ? 'stats.forecast_risk_count_plural' : 'stats.forecast_risk_count_singular', ['count' => (string) ((int) (($forecastSummary['critical'] ?? 0) + ($forecastSummary['warning'] ?? 0)))]) ?></strong>
        - <?= t('stats.forecast_risk_hint') ?>
    </span>
</div>
<?php endif; ?>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <?php if (!empty($diskInfo)): ?>
    <div class="card">
        <div class="card-header">Espace disque (<?= REPOS_BASE_PATH ?>)</div>
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px">
                <span>Utilise : <strong><?= formatBytes($diskInfo['used']) ?></strong></span>
                <span>Libre : <strong style="color:var(--green)"><?= formatBytes($diskInfo['free']) ?></strong></span>
                <span>Total : <strong><?= formatBytes($diskInfo['total']) ?></strong></span>
            </div>
            <div style="background:var(--bg3);border-radius:99px;height:12px;overflow:hidden">
                <div style="height:100%;width:<?= $diskInfo['pct'] ?>%;border-radius:99px;background:<?= $diskInfo['pct'] > 90 ? 'var(--red)' : ($diskInfo['pct'] > 70 ? 'var(--yellow)' : 'var(--accent2)') ?>;"></div>
            </div>
            <div style="font-size:12px;color:var(--text2);margin-top:6px;text-align:right"><?= $diskInfo['pct'] ?>% utilise</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Restores (<?= $periodDays ?> derniers jours)</div>
        <div class="card-body">
            <?php if (($restoreStats['total'] ?? 0) == 0): ?>
            <div style="color:var(--text2);font-size:13px"><?= t('stats.no_restore_done') ?></div>
            <?php else: ?>
            <div class="grid-2" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                <div style="text-align:center;padding:12px;background:var(--bg3);border-radius:6px"><div style="font-size:22px;font-weight:600;color:var(--green)"><?= $restoreStats['success'] ?></div><div style="font-size:11px;color:var(--text2)"><?= t('stats.success') ?></div></div>
                <div style="text-align:center;padding:12px;background:var(--bg3);border-radius:6px"><div style="font-size:22px;font-weight:600;color:var(--red)"><?= $restoreStats['failed'] ?></div><div style="font-size:11px;color:var(--text2)"><?= t('stats.failures') ?></div></div>
                <div style="text-align:center;padding:12px;background:var(--bg3);border-radius:6px"><div style="font-size:22px;font-weight:600;color:var(--accent)"><?= $restoreStats['local'] ?></div><div style="font-size:11px;color:var(--text2)">Local</div></div>
                <div style="text-align:center;padding:12px;background:var(--bg3);border-radius:6px"><div style="font-size:22px;font-weight:600;color:var(--purple)"><?= $restoreStats['remote'] ?></div><div style="font-size:11px;color:var(--text2)">Distant</div></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('stats.forecast.title') ?></div>
    <?php if (!empty($repoForecasts)): ?>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th><?= t('stats.table.repo') ?></th><th><?= t('stats.table.growth_per_day') ?></th><th><?= t('stats.table.free') ?></th><th><?= t('stats.table.forecast') ?></th><th><?= t('stats.table.sample') ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($repoForecasts as $forecast): ?>
            <?php
                $forecastBadgeClass = match($forecast['severity']) {
                    'critical' => 'badge-red',
                    'warning' => 'badge-yellow',
                    'ok' => 'badge-green',
                    default => 'badge-gray'
                };
                $forecastBadgeLabel = match($forecast['status']) {
                    'probe_missing' => t('stats.forecast.probe_required'),
                    'stable' => 'Stable',
                    'insufficient_data' => 'Historique insuffisant',
                    default => $forecast['projected_days_until_full'] !== null
                        ? 'Dans ' . DiskSpaceMonitor::formatForecastHorizon((float) $forecast['projected_days_until_full'])
                        : 'Projection indisponible'
                };
            ?>
            <tr>
                <td>
                    <div style="font-weight:500"><?= h($forecast['repo_name']) ?></div>
                    <div style="font-size:11px;color:var(--text2);font-family:var(--font-mono)"><?= h($forecast['repo_path']) ?></div>
                    <div style="font-size:11px;color:var(--text2);margin-top:2px"><?= h($forecast['message']) ?></div>
                </td>
                <td><?= h(DiskSpaceMonitor::formatGrowthRate($forecast['growth_bytes_per_day'] !== null ? (float) $forecast['growth_bytes_per_day'] : null)) ?></td>
                <td><?= $forecast['free_bytes'] !== null ? formatBytes((int) $forecast['free_bytes']) : '-' ?></td>
                <td><span class="badge <?= $forecastBadgeClass ?>"><?= h($forecastBadgeLabel) ?></span></td>
                <td style="font-size:12px">
                    <?= (int) ($forecast['sample_points'] ?? 0) ?> point(s)
                    <?php if (($forecast['sample_days'] ?? null) !== null): ?>
                    <div style="font-size:11px;color:var(--text2)"><?= number_format((float) $forecast['sample_days'], 1, ',', ' ') ?> j observes</div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
            <div class="empty-state" style="padding:24px"><?= t('stats.forecast.no_history') ?></div>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-header">Detail par depot</div>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>Depot</th><th>Snapshots</th><th>Taille totale</th><th>Fichiers</th><th>Dernier backup</th><th>Anciennete</th><th>Statut</th></tr>
        </thead>
        <tbody>
            <?php foreach ($repoStats as $repoStat): ?>
            <tr>
                <td>
                        <a href="<?= routePath('/explore.php', ['repo' => $repoStat['repo']['id']]) ?>" style="font-weight:500"><?= h($repoStat['repo']['name']) ?></a>
                    <div style="font-size:11px;color:var(--text2);font-family:var(--font-mono)"><?= h($repoStat['repo']['path']) ?></div>
                </td>
                <td><?= $repoStat['count'] ?></td>
                <td><?= $repoStat['total_size'] > 0 ? formatBytes($repoStat['total_size']) : ($repoStat['is_pending'] ? 'En attente' : '-') ?></td>
                <td><?= $repoStat['file_count'] > 0 ? number_format($repoStat['file_count']) : ($repoStat['is_pending'] ? 'En attente' : '-') ?></td>
                <td style="font-size:12px"><?= $repoStat['is_pending'] ? 'En attente du cron' : ($repoStat['last'] ? formatDate($repoStat['last']['time']) : '-') ?></td>
                <td style="font-size:12px"><?= $repoStat['hours_ago'] !== null ? $repoStat['hours_ago'] . 'h' : '-' ?></td>
                <td>
                    <?php if ($repoStat['is_pending']): ?>
                    <span class="badge badge-gray">En attente</span>
                    <?php elseif ($repoStat['status'] === 'warning'): ?>
                    <span class="badge badge-yellow">Ancien</span>
                    <?php elseif (in_array($repoStat['status'], ['error', 'no_snap'], true)): ?>
                    <span class="badge badge-red"><?= $repoStat['status'] === 'error' ? t('common.error') : t('stats.status_empty') ?></span>
                    <?php else: ?>
                    <span class="badge badge-green">OK</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php if (!empty($diskStatuses)): ?>
<div class="card mb-4">
    <div class="card-header">Cibles disque surveillees</div>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>Cible</th><th>Chemin</th><th>Libre</th><th>Total</th><th>Utilisation</th><th>Statut</th></tr>
        </thead>
        <tbody>
            <?php foreach ($diskStatuses as $diskStatus): ?>
            <?php
                $badgeClass = match($diskStatus['severity']) {
                    'critical', 'error' => 'badge-red',
                    'warning' => 'badge-yellow',
                    default => 'badge-green'
                };
                $badgeLabel = match($diskStatus['severity']) {
                    'critical' => 'Critique',
                    'error' => 'Probe KO',
                    'warning' => 'Warning',
                    default => 'OK'
                };
            ?>
            <tr>
                <td>
                    <div style="font-weight:500"><?= h($diskStatus['context_name']) ?></div>
                    <div style="font-size:11px;color:var(--text2)"><?= h($diskStatus['scope'] === 'remote' ? 'Distant' : 'Local') ?><?= !empty($diskStatus['host_name']) ? ' @ ' . h($diskStatus['host_name']) : '' ?></div>
                </td>
                <td style="font-family:var(--font-mono);font-size:11px"><?= h($diskStatus['path']) ?></td>
                <td><?= formatBytes((int) $diskStatus['free_bytes']) ?></td>
                <td><?= formatBytes((int) $diskStatus['total_bytes']) ?></td>
                <td><?= $diskStatus['used_percent'] !== null ? number_format((float) $diskStatus['used_percent'], 1) . '%' : '-' ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($history)): ?>
<div class="card mb-4">
    <div class="card-header">Evolution du nombre de snapshots (<?= $periodDays ?> jours)</div>
    <div class="card-body" style="position:relative;height:240px"><canvas id="chart-snapshots"></canvas></div>
</div>
<?php endif; ?>

<?php if (!empty($activityByDay)): ?>
<div class="card mb-4">
    <div class="card-header">Activite (<?= $periodDays ?> jours)</div>
    <div class="card-body" style="position:relative;height:180px"><canvas id="chart-activity"></canvas></div>
</div>
<?php endif; ?>

<script<?= cspNonceAttr() ?> type="application/json" id="stats-page-data"><?= json_encode([
    'snapshots' => [
        'labels' => array_map(static fn($label) => formatDateForDisplay($label . ':00:00', 'd/m H:i', appServerTimezone()), $snapshotLabels),
        'datasets' => $snapshotDatasets,
    ],
    'activity' => [
        'labels' => array_map(static fn($day) => formatDateForDisplay($day, 'd/m'), $actLabels),
        'values' => array_map('intval', $actValues),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
