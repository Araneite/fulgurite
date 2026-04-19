<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();
Auth::requirePermission('logs.view');

$db = Database::getInstance();
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = AppConfig::auditLogsPageSize();
$offset = ($page - 1) * $limit;

$filters = [
    'username' => trim((string) ($_GET['username'] ?? '')),
    'action' => trim((string) ($_GET['action'] ?? '')),
    'severity' => trim((string) ($_GET['severity'] ?? '')),
    'ip' => trim((string) ($_GET['ip'] ?? '')),
    'from' => trim((string) ($_GET['from'] ?? '')),
    'to' => trim((string) ($_GET['to'] ?? '')),
];

$where = [];
$params = [];

if ($filters['username'] !== '') {
    $where[] = 'username = ?';
    $params[] = $filters['username'];
}
if ($filters['action'] !== '') {
    $where[] = 'action = ?';
    $params[] = $filters['action'];
}
if ($filters['severity'] !== '' && in_array($filters['severity'], ['info', 'warning', 'critical'], true)) {
    $where[] = 'severity = ?';
    $params[] = $filters['severity'];
}
if ($filters['ip'] !== '') {
    $where[] = 'ip = ?';
    $params[] = $filters['ip'];
}
if ($filters['from'] !== '') {
    $where[] = 'created_at >= ?';
    $params[] = $filters['from'] . ' 00:00:00';
}
if ($filters['to'] !== '') {
    $where[] = 'created_at <= ?';
    $params[] = $filters['to'] . ' 23:59:59';
}

$whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

if (($_GET['export'] ?? '') === 'csv') {
    $stmt = $db->prepare("SELECT * FROM activity_logs $whereSql ORDER BY created_at DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="activity_logs.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['date', 'username', 'ip', 'severity', 'action', 'details']);
    foreach ($rows as $row) {
        fputcsv($output, [
            (string) ($row['created_at'] ?? ''),
            (string) ($row['username'] ?? ''),
            (string) ($row['ip'] ?? ''),
            (string) ($row['severity'] ?? 'info'),
            (string) ($row['action'] ?? ''),
            (string) ($row['details'] ?? ''),
        ]);
    }
    fclose($output);
    exit;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $limit));
if ($page > $pages) {
    $page = $pages;
    $offset = ($page - 1) * $limit;
}

$stmt = $db->prepare("SELECT * FROM activity_logs $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$index = 1;
foreach ($params as $param) {
    $stmt->bindValue($index++, $param);
}
$stmt->bindValue($index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($index, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$actionsList = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];
$usernamesList = $db->query("SELECT DISTINCT username FROM activity_logs WHERE username IS NOT NULL AND username != '' ORDER BY username ASC LIMIT 200")->fetchAll(PDO::FETCH_COLUMN) ?: [];
$title = t('logs.activity_title');
$active = 'logs';
$queryWithoutPage = array_filter(array_merge($filters), static fn(string $value): bool => $value !== '');

include 'layout_top.php';
?>

<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
        <div class="card-header"><?= t('logs.filters') ?></div>
        <div class="card-body">
            <form method="GET" style="display:flex;flex-direction:column;gap:12px">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
                    <div class="form-group">
                        <label class="form-label"><?= t('logs.table.user') ?></label>
                        <input type="text" name="username" class="form-control" value="<?= h($filters['username']) ?>" list="log-users">
                        <datalist id="log-users">
                            <?php foreach ($usernamesList as $username): ?>
                            <option value="<?= h((string) $username) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('logs.table.action') ?></label>
                        <select name="action" class="form-control">
                            <option value=""><?= t('logs.filter.all') ?></option>
                            <?php foreach ($actionsList as $action): ?>
                            <option value="<?= h((string) $action) ?>" <?= $filters['action'] === (string) $action ? 'selected' : '' ?>><?= h((string) $action) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('logs.filter.severity') ?></label>
                        <select name="severity" class="form-control">
                            <option value=""><?= t('logs.filter.all') ?></option>
                            <?php foreach (['info' => 'Info', 'warning' => 'Warning', 'critical' => 'Critical'] as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $filters['severity'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('logs.table.ip') ?></label>
                        <input type="text" name="ip" class="form-control" value="<?= h($filters['ip']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('logs.filter.from') ?></label>
                        <input type="date" name="from" class="form-control" value="<?= h($filters['from']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('logs.filter.to') ?></label>
                        <input type="date" name="to" class="form-control" value="<?= h($filters['to']) ?>">
                    </div>
                </div>
                <div class="flex gap-2" style="justify-content:flex-end">
                    <a href="<?= routePath('/logs.php') ?>" class="btn"><?= t('logs.filter.reset') ?></a>
                    <a href="<?= routePath('/logs.php', array_merge($queryWithoutPage, ['export' => 'csv'])) ?>" class="btn"><?= t('logs.filter.export_csv') ?></a>
                    <button type="submit" class="btn btn-primary"><?= t('logs.filter.apply') ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span><?= h(t('logs.activity_count', ['total' => $total])) ?></span>
        </div>
        <?php if (empty($logs)): ?>
        <div class="empty-state" style="padding:32px"><?= t('logs.empty_filter') ?></div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('common.date') ?></th>
                    <th><?= t('logs.table.user') ?></th>
                    <th><?= t('logs.table.ip') ?></th>
                    <th><?= t('logs.filter.severity') ?></th>
                    <th><?= t('logs.table.action') ?></th>
                    <th><?= t('common.details') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <?php
                $severity = (string) ($log['severity'] ?? 'info');
                $severityBadge = match ($severity) {
                    'critical' => 'badge-red',
                    'warning' => 'badge-yellow',
                    default => 'badge-blue',
                };
                ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap"><?= h(formatDate((string) $log['created_at'])) ?></td>
                    <td>
                        <?php if (!empty($log['username'])): ?>
                        <a href="<?= routePath('/logs.php', array_merge($queryWithoutPage, ['username' => (string) $log['username']])) ?>"><?= h((string) $log['username']) ?></a>
                        <?php else: ?>
                        <?= t('logs.anonymous') ?>
                        <?php endif; ?>
                    </td>
                    <td class="mono"><?= h((string) ($log['ip'] ?? '')) ?></td>
                    <td><span class="badge <?= $severityBadge ?>"><?= h(strtoupper($severity)) ?></span></td>
                    <td><span class="badge badge-gray"><?= h((string) ($log['action'] ?? '')) ?></span></td>
                    <td style="font-size:12px;color:var(--text2)"><?= h((string) ($log['details'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php if ($pages > 1): ?>
        <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="<?= routePath('/logs.php', array_merge($queryWithoutPage, ['page' => $i])) ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'layout_bottom.php'; ?>
