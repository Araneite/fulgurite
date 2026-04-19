<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();

$accessibleRepos  = Auth::filterAccessibleRepos(RepoManager::getAll());
$accessibleIds    = array_map('intval', array_column($accessibleRepos, 'id'));
$allStatuses      = RepoStatusService::getStatuses(false);
$filteredStatuses = array_values(array_filter(
    $allStatuses,
    fn($s) => in_array((int) ($s['id'] ?? -1), $accessibleIds, true)
));

$payload = [
    'statuses'  => $filteredStatuses,
    'updated'   => formatCurrentDisplayDate('H:i:s'),
    'cached_at' => RepoStatusService::latestUpdate(),
];

jsonResponseCached($payload, 200, 5);
