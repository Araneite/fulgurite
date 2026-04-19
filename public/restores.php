<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();
Auth::requirePermission('restore.view');

$db     = Database::getInstance();
$page   = max(1, (int) ($_GET['page'] ?? 1));
$limit  = AppConfig::restoreHistoryPageSize();
$offset = ($page - 1) * $limit;

$accessibleRepoIds = array_map(static fn(array $repo): int => (int) ($repo['id'] ?? 0), Auth::filterAccessibleRepos(RepoManager::getAll()));
$accessibleRepoIds = array_values(array_filter($accessibleRepoIds, static fn(int $repoId): bool => $repoId > 0));

if (empty($accessibleRepoIds)) {
    $total = 0;
    $pages = 1;
    $items = [];
} else {
    $placeholders = implode(',', array_fill(0, count($accessibleRepoIds), '?'));
    $countStmt = $db->prepare("SELECT COUNT(*) FROM restore_history WHERE repo_id IN ($placeholders)");
    $countStmt->execute($accessibleRepoIds);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $limit));

    $itemsStmt = $db->prepare("SELECT * FROM restore_history WHERE repo_id IN ($placeholders) ORDER BY started_at DESC LIMIT ? OFFSET ?");
    $index = 1;
    foreach ($accessibleRepoIds as $repoId) {
        $itemsStmt->bindValue($index++, $repoId, PDO::PARAM_INT);
    }
    $itemsStmt->bindValue($index++, $limit, PDO::PARAM_INT);
    $itemsStmt->bindValue($index, $offset, PDO::PARAM_INT);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll();
}

$title  = t('restores.title');
$active = 'restores';

include 'layout_top.php';
?>

<div class="card">
    <div class="card-header">
        <span><?= h(t('restores.header', ['total' => $total])) ?></span>
    </div>

    <?php if (empty($items)): ?>
    <div class="empty-state" style="padding:48px">
        <div><?= t('restores.empty') ?></div>
    </div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th><?= t('common.date') ?></th>
                <th><?= t('restores.table.repo') ?></th>
                <th><?= t('restores.table.snapshot') ?></th>
                <th><?= t('restores.table.mode') ?></th>
                <th><?= t('restores.table.destination') ?></th>
                <th><?= t('restores.table.filter') ?></th>
                <th><?= t('restores.table.by') ?></th>
                <th><?= t('common.status') ?></th>
                <th><?= t('restores.table.duration') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item):
                $statusBadge = match($item['status']) {
                    'success' => ['class' => 'badge-green',  'label' => t('restores.status.success')],
                    'failed'  => ['class' => 'badge-red',    'label' => t('restores.status.failed')],
                    'running' => ['class' => 'badge-yellow', 'label' => t('restores.status.running')],
                    default   => ['class' => 'badge-gray',   'label' => $item['status']],
                };
                $duration = formatDurationBetween($item['started_at'] ?? null, $item['finished_at'] ?? null);
                $dest = $item['mode'] === 'remote'
                    ? "{$item['remote_user']}@{$item['remote_host']}:{$item['remote_path']}"
                    : ($item['target'] ?? '/');
            ?>
            <tr>
                <td style="font-size:12px;white-space:nowrap"><?= formatDate($item['started_at']) ?></td>
                        <td><a href="<?= routePath('/explore.php', ['repo' => $item['repo_id']]) ?>"><?= h($item['repo_name']) ?></a></td>
                <td class="mono"><?= h(substr($item['snapshot_id'], 0, 8)) ?></td>
                <td>
                    <span class="badge <?= $item['mode'] === 'remote' ? 'badge-purple' : 'badge-blue' ?>">
                        <?= $item['mode'] ?>
                    </span>
                </td>
                <td class="mono" style="font-size:11px"><?= h($dest) ?></td>
                <td style="font-size:11px;color:var(--text2)"><?= h($item['include_path'] ?? '—') ?></td>
                <td><?= h($item['started_by']) ?></td>
                <td><span class="badge <?= $statusBadge['class'] ?>"><?= $statusBadge['label'] ?></span></td>
                <td style="font-size:12px;color:var(--text2)"><?= $duration ?: '—' ?></td>
                <td>
                    <?php if (!empty($item['output'])): ?>
                    <button class="btn btn-sm" onclick="showOutput(<?= h(json_encode($item['output'])) ?>)"><?= t('restores.logs_btn') ?></button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
    <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:8px">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div id="modal-output" class="modal-overlay">
    <div class="modal" style="max-width:680px">
        <div class="modal-title"><?= t('restores.logs_modal_title') ?></div>
        <div class="code-viewer" id="output-content" style="max-height:400px"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn" onclick="document.getElementById('modal-output').classList.remove('show')"><?= t('common.close') ?></button>
        </div>
    </div>
</div>

<script<?= cspNonceAttr() ?>>
function showOutput(output) {
    document.getElementById('output-content').textContent = output;
    document.getElementById('modal-output').classList.add('show');
}

window.showOutput = showOutput;
</script>

<?php include 'layout_bottom.php'; ?>
