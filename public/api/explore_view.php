<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/ExploreView.php';
Auth::check();
Auth::requirePermission('repos.view');
rateLimitApi('explore_view', 120, 60);

$repoId = (int) ($_GET['repo'] ?? 0);
$snapshot = $_GET['snapshot'] ?? null;
$path = $_GET['path'] ?? '/';
$action = $_GET['action'] ?? 'browse';
$page = max(1, (int) ($_GET['page'] ?? 1));

if (!$repoId) {
    jsonResponse(['error' => 'repo requis'], 400);
}

$allowedActions = ['browse', 'view', 'search', 'partial', 'stats'];
if (!in_array($action, $allowedActions, true)) {
    $action = 'browse';
}

$repo = RepoManager::getById($repoId);
if (!$repo) {
    jsonResponse(['error' => 'Dépôt introuvable'], 404);
}

Auth::requireRepoAccess($repoId);
$restic = RepoManager::getRestic($repo);
$payload = buildExplorePayload($repo, $restic, $snapshot, $path, $action, $page);

$maxAge = (($payload['status'] ?? 'ready') === 'ready') ? 5 : 0;
jsonResponseCached($payload, 200, $maxAge);
