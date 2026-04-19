<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
rateLimitApi('stats_page', 60, 60);

if (!Auth::hasPermission('stats.view')) {
    jsonResponse(['error' => 'Permission insuffisante'], 403);
}

$db = Database::getInstance();
$repos = Auth::filterAccessibleRepos(RepoManager::getAll());
$accessibleIds = array_map('intval', array_column($repos, 'id'));
$section = $_GET['section'] ?? 'all';
$section = is_string($section) && in_array($section, ['all', 'summary', 'heavy'], true) ? $section : 'all';
$repoStatuses = RepoStatusService::getStatuses(false);
$repoStatusesById = [];
foreach ($repoStatuses as $status) {
    $repoStatusesById[(int) $status['id']] = $status;
}

$period = $_GET['period'] ?? '7';
$periodDays = in_array($period, ['7', '14', '30', '90'], true) ? (int) $period : 7;
$periodDaysExpr = '-' . $periodDays . ' days';

$latestRepoStats = [];
if (!empty($accessibleIds)) {
    $latestPlaceholders = implode(',', array_fill(0, count($accessibleIds), '?'));
    $latestParams = array_merge($accessibleIds, $accessibleIds);
    $latestStmt = $db->prepare("
        SELECT h.repo_id, h.snapshot_count, h.total_size, h.total_file_count, h.recorded_at
        FROM repo_stats_history h
        JOIN (
            SELECT repo_id, MAX(recorded_at) AS recorded_at
            FROM repo_stats_history
            WHERE repo_id IN ($latestPlaceholders)
            GROUP BY repo_id
        ) latest
          ON latest.repo_id = h.repo_id
         AND latest.recorded_at = h.recorded_at
        WHERE h.repo_id IN ($latestPlaceholders)
    ");
    $latestStmt->execute($latestParams);
    foreach ($latestStmt->fetchAll() as $row) {
        $latestRepoStats[(int) $row['repo_id']] = $row;
    }
}

$repoStats = [];
foreach ($repos as $repo) {
    $repoStatus = $repoStatusesById[(int) $repo['id']] ?? null;
    $statusCode = $repoStatus['status'] ?? 'pending';
    $repoStats[] = [
        'repo' => $repo,
        'count' => (int) ($repoStatus['count'] ?? 0),
        'status' => $statusCode,
        'is_pending' => $statusCode === 'pending',
        'last' => !empty($repoStatus['last_time']) ? ['time' => $repoStatus['last_time']] : null,
        'hours_ago' => $repoStatus['hours_ago'] ?? null,
        'total_size' => (int) ($latestRepoStats[(int) $repo['id']]['total_size'] ?? 0),
        'file_count' => (int) ($latestRepoStats[(int) $repo['id']]['total_file_count'] ?? 0),
    ];
}

$pendingStatsCount = count(array_filter($repoStats, fn($repoStat) => $repoStat['is_pending']));

$history = [];
$restoreStats = [
    'total' => 0,
    'success' => 0,
    'failed' => 0,
    'remote' => 0,
    'local' => 0,
];

if (!empty($accessibleIds)) {
    $placeholders = implode(',', array_fill(0, count($accessibleIds), '?'));
    $restoreStmt = $db->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN mode = 'remote' THEN 1 ELSE 0 END) as remote,
            SUM(CASE WHEN mode = 'local' THEN 1 ELSE 0 END) as local
        FROM restore_history
        WHERE repo_id IN ($placeholders)
          AND started_at >= datetime('now', ?)
    ");
    $restoreStmt->execute(array_merge($accessibleIds, [$periodDaysExpr]));
    $restoreStats = $restoreStmt->fetch() ?: $restoreStats;
}

$activityStmt = $db->prepare("
    SELECT DATE(created_at) as day, COUNT(*) as count
    FROM activity_logs
    WHERE created_at >= datetime('now', ?)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$activityStmt->execute([$periodDaysExpr]);
$activityByDay = $activityStmt->fetchAll();

$diskInfo = [];
if (is_dir(REPOS_BASE_PATH)) {
    $total = disk_total_space(REPOS_BASE_PATH);
    $free = disk_free_space(REPOS_BASE_PATH);
    $used = $total - $free;
    $diskInfo = [
        'total' => $total,
        'used' => $used,
        'free' => $free,
        'pct' => $total > 0 ? round($used / $total * 100, 1) : 0,
    ];
}
$diskSummary = DiskSpaceMonitor::getSummary();
$diskStatuses = DiskSpaceMonitor::getLatestStatuses(false);
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

if ($section !== 'summary') {
    $cacheParts = [
        'v1',
        $periodDays,
        $accessibleIds,
    ];
    $heavyPayload = RuntimeTtlCache::rememberArray('stats-page-heavy', $cacheParts, 20, static function () use ($db, $accessibleIds, $periodDaysExpr): array {
        $historyRows = [];
        if (!empty($accessibleIds)) {
            $placeholders = implode(',', array_fill(0, count($accessibleIds), '?'));
            $stmt = $db->prepare("
                SELECT r.id as repo_id, r.name as repo_name, h.snapshot_count, h.total_size, h.total_file_count, h.recorded_at
                FROM repo_stats_history h
                JOIN repos r ON r.id = h.repo_id
                WHERE h.repo_id IN ($placeholders)
                  AND h.recorded_at >= datetime('now', ?)
                ORDER BY h.recorded_at ASC
            ");
            $stmt->execute(array_merge($accessibleIds, [$periodDaysExpr]));
            $historyRows = $stmt->fetchAll();
        }

        $forecasts = DiskSpaceMonitor::getRepoForecasts($accessibleIds);
        return [
            'history' => $historyRows,
            'repo_forecasts' => $forecasts,
            'forecast_summary' => DiskSpaceMonitor::getForecastSummary($forecasts),
        ];
    });
    $history = $heavyPayload['history'] ?? [];
    $repoForecasts = $heavyPayload['repo_forecasts'] ?? [];
    $forecastSummary = $heavyPayload['forecast_summary'] ?? $forecastSummary;
}

ob_start();
require __DIR__ . '/../partials/stats_content.php';
$html = ob_get_clean();

jsonResponseCached([
    'status' => 'ready',
    'html' => $html,
], 200, 5);
