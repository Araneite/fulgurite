<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();
rateLimitApi('apply_retention', 10, 60);

$data       = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId     = (int) ($data['repo_id']      ?? 0);
$keepLast   = (int) ($data['keep_last']    ?? 0);
$keepDaily  = (int) ($data['keep_daily']   ?? 0);
$keepWeekly = (int) ($data['keep_weekly']  ?? 0);
$keepMonthly= (int) ($data['keep_monthly'] ?? 0);
$keepYearly = (int) ($data['keep_yearly']  ?? 0);
$prune      = (bool) ($data['prune']       ?? true);
$dryRun     = (bool) ($data['dry_run']     ?? false);

if (!$repoId) jsonResponse(['error' => 'repo_id requis'], 400);

$repo = RepoManager::getById($repoId);
if (!$repo) jsonResponse(['error' => 'Dépôt introuvable'], 404);

// Save policy when not in dry-run mode
if (!$dryRun) {
    $db = Database::getInstance();
    $db->prepare("
        INSERT OR REPLACE INTO retention_policies
        (repo_id, keep_last, keep_daily, keep_weekly, keep_monthly, keep_yearly, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
    ")->execute([$repoId, $keepLast, $keepDaily, $keepWeekly, $keepMonthly, $keepYearly]);
}

$restic = RepoManager::getRestic($repo);
$result = $restic->forget($keepLast, $keepDaily, $keepWeekly, $keepMonthly, $keepYearly, $prune, $dryRun);

if ($result['success'] && !$dryRun) {
    JobQueue::enqueueRepoSnapshotRefresh($repoId, 'retention_applied', 200);
}

Auth::log('retention_apply',
    ($dryRun ? '[DRY-RUN] ' : '') .
    "Rétention sur {$repo['name']}: last=$keepLast daily=$keepDaily weekly=$keepWeekly monthly=$keepMonthly"
);

jsonResponse($result);
