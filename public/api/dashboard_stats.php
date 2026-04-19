<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();

if (!Auth::hasPermission('stats.view')) {
    jsonResponse(['error' => 'Permission insuffisante'], 403);
}

$accessibleRepos = Auth::filterAccessibleRepos(RepoManager::getAll());
$accessibleIds   = array_map('intval', array_column($accessibleRepos, 'id'));

$allStatuses = RepoStatusService::getStatuses(false);
$statuses    = array_values(array_filter(
    $allStatuses,
    fn($s) => in_array((int) ($s['id'] ?? -1), $accessibleIds, true)
));

$db = Database::getInstance();

if (empty($accessibleIds)) {
    $history = [];
} else {
    $placeholders = implode(',', array_fill(0, count($accessibleIds), '?'));
    $stmt = $db->prepare("
        SELECT repo_id, snapshot_count, total_size, total_file_count, recorded_at
        FROM repo_stats_history
        WHERE repo_id IN ($placeholders)
          AND recorded_at >= datetime('now', '-30 days')
        ORDER BY recorded_at ASC
    ");
    $stmt->execute($accessibleIds);
    $history = $stmt->fetchAll();
}

$payload = [
    'statuses'  => $statuses,
    'history'   => $history,
    'updated'   => formatCurrentDisplayDate('H:i:s'),
    'cached_at' => RepoStatusService::latestUpdate(),
];

jsonResponseCached($payload, 200, 5);
