<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
rateLimitApi('dashboard_page', 60, 60);

$repos = Auth::filterAccessibleRepos(RepoManager::getAll());
$db = Database::getInstance();
$accessibleIds  = array_map('intval', array_column($repos, 'id'));
$section = $_GET['section'] ?? 'all';
$section = is_string($section) && in_array($section, ['all', 'summary', 'heavy'], true) ? $section : 'all';

$repoStatuses   = array_values(array_filter(
    RepoStatusService::getStatuses(false),
    fn($s) => in_array((int) ($s['id'] ?? -1), $accessibleIds, true)
));
$totalSnapshots = array_sum(array_map(fn($status) => (int) $status['count'], $repoStatuses));
$alertCount = count(array_filter($repoStatuses, fn($status) => in_array($status['status'], ['warning', 'error', 'no_snap'], true)));
$okRepoCount = count(array_filter($repoStatuses, fn($status) => $status['status'] === 'ok'));
$pendingRepoCount = count(array_filter($repoStatuses, fn($status) => $status['status'] === 'pending'));
$history = [];
$diskHistoryChart = ['labels' => [], 'datasets' => []];
$repoForecasts = [];
$forecastSummary = [
    'total' => 0,
    'critical' => 0,
    'warning' => 0,
    'ok' => 0,
    'stable' => 0,
    'insufficient_data' => 0,
    'probe_missing' => 0,
];
$forecastHighlights = [];

$logs = Auth::hasPermission('logs.view')
    ? $db->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT " . AppConfig::auditDashboardRecentLimit())->fetchAll()
    : [];
$copyJobsCount = 0;
$restoreCount = 0;
if (!empty($accessibleIds)) {
    $placeholders = implode(',', array_fill(0, count($accessibleIds), '?'));
    $copyStmt = $db->prepare("
        SELECT COUNT(*)
        FROM copy_jobs
        WHERE source_repo_id IN ($placeholders)
    ");
    $copyStmt->execute($accessibleIds);
    $copyJobsCount = (int) $copyStmt->fetchColumn();
}
if (!empty($accessibleIds)) {
    $restorePlaceholders = implode(',', array_fill(0, count($accessibleIds), '?'));
    $restoreStmt = $db->prepare("
        SELECT COUNT(*)
        FROM restore_history
        WHERE repo_id IN ($restorePlaceholders)
    ");
    $restoreStmt->execute($accessibleIds);
    $restoreCount = (int) $restoreStmt->fetchColumn();
}

$diskSummary = DiskSpaceMonitor::getSummary();
$diskStatuses = array_slice(DiskSpaceMonitor::getLatestStatuses(false), 0, 5);

if ($section !== 'summary') {
    $cacheParts = [
        'v1',
        $accessibleIds,
    ];
    $heavyPayload = RuntimeTtlCache::rememberArray('dashboard-page-heavy', $cacheParts, 20, static function () use ($db, $accessibleIds): array {
        $historyRows = [];
        if (!empty($accessibleIds)) {
            $placeholders = implode(',', array_fill(0, count($accessibleIds), '?'));
            $stmt = $db->prepare("
                SELECT r.name as repo_name, h.snapshot_count, h.recorded_at
                FROM repo_stats_history h JOIN repos r ON r.id = h.repo_id
                WHERE h.repo_id IN ($placeholders)
                  AND h.recorded_at >= datetime('now', '-7 days')
                ORDER BY h.recorded_at ASC
            ");
            $stmt->execute($accessibleIds);
            $historyRows = $stmt->fetchAll();
        }

        $forecasts = DiskSpaceMonitor::getRepoForecasts($accessibleIds);
        return [
            'history' => $historyRows,
            'disk_history_chart' => DiskSpaceMonitor::getHistoryChart(7, 4, $accessibleIds),
            'repo_forecasts' => $forecasts,
            'forecast_summary' => DiskSpaceMonitor::getForecastSummary($forecasts),
            'forecast_highlights' => array_slice(array_values(array_filter($forecasts, static fn(array $forecast): bool => in_array((string) ($forecast['status'] ?? ''), ['growing', 'probe_missing'], true))), 0, 5),
        ];
    });
    $history = $heavyPayload['history'] ?? [];
    $diskHistoryChart = $heavyPayload['disk_history_chart'] ?? ['labels' => [], 'datasets' => []];
    $repoForecasts = $heavyPayload['repo_forecasts'] ?? [];
    $forecastSummary = $heavyPayload['forecast_summary'] ?? $forecastSummary;
    $forecastHighlights = $heavyPayload['forecast_highlights'] ?? [];
}

if (empty($accessibleIds)) {
    $backupJobs = [];
} else {
    $placeholders = implode(',', array_fill(0, count($accessibleIds), '?'));
    $stmt = $db->prepare("
        SELECT bj.*
        FROM backup_jobs bj
        WHERE bj.repo_id IN ($placeholders)
        ORDER BY bj.last_run DESC, bj.name
    ");
    $stmt->execute($accessibleIds);
    $backupJobs = $stmt->fetchAll();
}
$bjTotal = count($backupJobs);

ob_start();
require __DIR__ . '/../partials/dashboard_content.php';
$html = ob_get_clean();

jsonResponseCached([
    'status' => 'ready',
    'html' => $html,
    'updated' => formatCurrentDisplayDate('H:i:s'),
], 200, 5);
