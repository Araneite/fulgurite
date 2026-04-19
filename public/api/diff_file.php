<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
Auth::requirePermission('repos.view');
verifyCsrf();

$data       = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId     = (int) ($data['repo_id']    ?? 0);
$snapshotA  = $data['snapshot_a'] ?? '';
$snapshotB  = $data['snapshot_b'] ?? '';
$filePath   = $data['file_path']  ?? '';

if (!$repoId || !$snapshotA || !$snapshotB || !$filePath) {
    jsonResponse(['error' => t('api.diff_file.error.required_params')], 400);
}

$repo = RepoManager::getById($repoId);
if (!$repo) jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);

Auth::requireRepoAccess($repoId);
$restic = RepoManager::getRestic($repo);

// Retrieve content from both versions
$resultA = $restic->dump($snapshotA, $filePath);
$resultB = $restic->dump($snapshotB, $filePath);

if (!$resultA['success']) jsonResponse(['error' => t('api.diff_file.error.file_not_found_snapshot_a') . ': ' . $resultA['error']]);
if (!$resultB['success']) jsonResponse(['error' => t('api.diff_file.error.file_not_found_snapshot_b') . ': ' . $resultB['error']]);

$linesA = explode("\n", $resultA['content']);
$linesB = explode("\n", $resultB['content']);

// Line-by-line diff (simplified LCS)
$diff = computeDiff($linesA, $linesB);

Auth::log('file_diff', "Diff: $filePath entre $snapshotA et $snapshotB ({$repo['name']})");

jsonResponse([
    'diff'       => $diff,
    'lines_a'    => count($linesA),
    'lines_b'    => count($linesB),
    'snapshot_a' => $snapshotA,
    'snapshot_b' => $snapshotB,
    'file_path'  => $filePath,
]);

// ── Diff algorithm ────────────────────────────────────────────────────────
function computeDiff(array $linesA, array $linesB): array {
    $result = [];
    $lenA   = count($linesA);
    $lenB   = count($linesB);

    // for large files, limit to 2000 lines each
    if ($lenA > 2000 || $lenB > 2000) {
        return [['type' => 'info', 'content' => t('api.diff_file.info.too_large_inline_diff', ['len_a' => (string) $lenA, 'len_b' => (string) $lenB])]];
    }

    // LCS (Longest Common Subsequence) via DP
    $dp = [];
    for ($i = 0; $i <= $lenA; $i++) $dp[$i][0] = 0;
    for ($j = 0; $j <= $lenB; $j++) $dp[0][$j] = 0;

    for ($i = 1; $i <= $lenA; $i++) {
        for ($j = 1; $j <= $lenB; $j++) {
            if ($linesA[$i-1] === $linesB[$j-1]) {
                $dp[$i][$j] = $dp[$i-1][$j-1] + 1;
            } else {
                $dp[$i][$j] = max($dp[$i-1][$j], $dp[$i][$j-1]);
            }
        }
    }

    // Rebuild the diff
    $i = $lenA; $j = $lenB;
    $ops = [];
    while ($i > 0 || $j > 0) {
        if ($i > 0 && $j > 0 && $linesA[$i-1] === $linesB[$j-1]) {
            array_unshift($ops, ['type' => 'equal', 'content' => $linesA[$i-1], 'line_a' => $i, 'line_b' => $j]);
            $i--; $j--;
        } elseif ($j > 0 && ($i === 0 || $dp[$i][$j-1] >= $dp[$i-1][$j])) {
            array_unshift($ops, ['type' => 'add', 'content' => $linesB[$j-1], 'line_b' => $j]);
            $j--;
        } else {
            array_unshift($ops, ['type' => 'remove', 'content' => $linesA[$i-1], 'line_a' => $i]);
            $i--;
        }
    }

    // Condense identical lines (show max 3 context lines)
    $condensed = [];
    $equalBuf  = [];

    foreach ($ops as $idx => $op) {
        if ($op['type'] === 'equal') {
            $equalBuf[] = $op;
        } else {
            if (count($equalBuf) > 6) {
                // Garder 3 lignes before and 3 after
                foreach (array_slice($equalBuf, 0, 3) as $eq) $condensed[] = $eq;
                $condensed[] = ['type' => 'ellipsis', 'content' => '...', 'count' => count($equalBuf) - 6];
                foreach (array_slice($equalBuf, -3) as $eq) $condensed[] = $eq;
            } else {
                foreach ($equalBuf as $eq) $condensed[] = $eq;
            }
            $equalBuf = [];
            $condensed[] = $op;
        }
    }

    // Process remaining buffer
    if (count($equalBuf) > 3) {
        foreach (array_slice($equalBuf, 0, 3) as $eq) $condensed[] = $eq;
        $condensed[] = ['type' => 'ellipsis', 'content' => '...', 'count' => count($equalBuf) - 3];
    } else {
        foreach ($equalBuf as $eq) $condensed[] = $eq;
    }

    return $condensed;
}
