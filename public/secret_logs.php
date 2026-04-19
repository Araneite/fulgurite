<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/BrokerClusterMonitor.php';

Auth::check();
Auth::requirePermission('settings.manage');

$limit = max(20, min(500, (int) ($_GET['limit'] ?? 200)));
$filterEvent = trim((string) ($_GET['filter_event'] ?? ''));
$result = ['ok' => false, 'logs' => [], 'audit_path' => '/var/lib/fulgurite-secrets/audit.log'];
try {
    $result = SecretStore::auditLogs($limit);
} catch (Throwable $e) {
    $result['error'] = SecretRedaction::safeThrowableMessage($e);
}
$logs = is_array($result['logs'] ?? null) ? $result['logs'] : [];
$brokerEventRows = SecretBrokerEvents::recentEvents($limit);

// Apply optional event filter.
$brokerEvents = ['degraded', 'node_failed', 'node_recovered', 'recovered', 'failover', 'down'];
if ($filterEvent !== '') {
    $logs = array_values(array_filter($logs, static fn(array $row): bool => (string) ($row['action'] ?? '') === $filterEvent));
    $brokerEventRows = array_values(array_filter($brokerEventRows, static fn(array $row): bool => (string) ($row['event_type'] ?? '') === $filterEvent));
}

// Broker cluster last known state for the status panel.
$brokerLastStatus = BrokerClusterMonitor::getLastStatus();
$brokerDbEvents   = (class_exists('SecretBrokerEvents', false)) ? SecretBrokerEvents::recentEvents(50) : [];
$auditPath = (string) ($result['audit_path'] ?? '/var/lib/fulgurite-secrets/audit.log');

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="secret_access_logs.csv"');
    $out = fopen('php://output', 'wb');
    fputcsv($out, ['ts_utc', 'ts_interface', 'interface_timezone', 'action', 'purpose', 'secret_ref', 'ok']);
    foreach ($logs as $row) {
        $ts = (string) ($row['ts'] ?? '');
        fputcsv($out, [
            $ts,
            $ts !== '' ? formatDateForDisplay($ts) : '',
            AppConfig::timezoneLabel(),
            (string) ($row['action'] ?? ''),
            (string) ($row['purpose'] ?? ''),
            (string) ($row['secret_ref'] ?? ''),
            !empty($row['ok']) ? '1' : '0',
        ]);
    }
    fclose($out);
    exit;
}

$title = t('nav.secret_logs');
$active = 'secret_logs';
$actions = '<a class="btn" href="' . h(routePath('/secret_logs.php', ['limit' => $limit, 'export' => 'csv'])) . '">' . h(t('logs.filter.export_csv')) . '</a>';
include 'layout_top.php';
?>

<?php if (empty($result['ok'])): ?>
<div class="alert alert-danger mb-4">
    <?= t('secret_logs.error.broker_unavailable') ?>
    <code><?= h((string) ($result['error'] ?? t('common.unknown_error'))) ?></code>
</div>
<?php endif; ?>

<!-- ═══════════════════════ Broker cluster state panel ═══════════════════════ -->
<div class="card mb-4" id="broker-cluster-state">
    <div class="card-header" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span><?= t('secret_logs.cluster_state.title') ?></span>
        <?php
        $bcState = (string) ($brokerLastStatus['state'] ?? 'unknown');
        $bcBadgeClass = match ($bcState) {
            'ok'           => 'badge-green',
            'degraded'     => 'badge-yellow',
            'down'         => 'badge-red',
            'unconfigured' => 'badge-gray',
            default        => 'badge-gray',
        };
        ?>
        <span class="badge <?= $bcBadgeClass ?>"><?= h($bcState) ?></span>
        <?php if (!empty($brokerLastStatus['checked_at'])): ?>
        <span style="font-size:12px;color:var(--text2)"><?= t('secret_logs.cluster_state.checked_at_prefix') ?> <?= h(formatDateForDisplay((string) $brokerLastStatus['checked_at'])) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body" style="padding:12px 18px">
        <div style="display:flex;gap:24px;flex-wrap:wrap;font-size:13px">
            <div><span style="color:var(--text2)"><?= t('secret_logs.cluster_state.healthy_nodes') ?>&nbsp;:</span> <strong><?= (int) ($brokerLastStatus['healthy'] ?? 0) ?>/<?= (int) ($brokerLastStatus['total'] ?? 0) ?></strong></div>
            <div><span style="color:var(--text2)"><?= t('secret_logs.cluster_state.view_diagnostics') ?>&nbsp;:</span> <a href="<?= h(routePath('/performance.php')) ?>#broker-cluster" style="color:var(--accent)"><?= t('secret_logs.cluster_state.performance_page') ?></a></div>
            <div><span style="color:var(--text2)"><?= t('secret_logs.cluster_state.failover_notifications') ?>&nbsp;:</span> <a href="<?= h(routePath('/settings.php')) ?>?tab=general&section=notifications" style="color:var(--accent)"><?= t('secret_logs.cluster_state.settings_notifications') ?></a></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <?= t('secret_logs.access.title') ?>
        <span class="badge badge-blue"><?= count($logs) ?></span>
    </div>
    <div class="card-body">
        <div class="settings-help" style="margin-bottom:12px">
            <?= t('secret_logs.access.help_intro') ?>
            <?= t('secret_logs.access.archive_path_prefix') ?> <code><?= h($auditPath) ?></code>.
            <?= t('secret_logs.access.utc_notice') ?>
            <code><?= h(AppConfig::timezoneLabel()) ?></code>.
        </div>
        <form method="get" class="flex gap-2" style="align-items:end;flex-wrap:wrap">
            <div class="form-group" style="margin-bottom:0;width:160px">
                <label class="form-label"><?= t('secret_logs.filter.lines') ?></label>
                <input type="number" class="form-control" name="limit" value="<?= (int) $limit ?>" min="20" max="500">
            </div>
            <div class="form-group" style="margin-bottom:0;width:200px">
                <label class="form-label"><?= t('secret_logs.filter.action') ?></label>
                <select name="filter_event" class="form-control">
                    <option value=""><?= t('secret_logs.filter.all_actions') ?></option>
                    <?php foreach (array_merge(['get', 'put', 'delete', 'exists', 'health', 'audit_tail'], $brokerEvents) as $ev): ?>
                    <option value="<?= h($ev) ?>" <?= $filterEvent === $ev ? 'selected' : '' ?>><?= h($ev) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn"><?= t('secret_logs.filter.refresh') ?></button>
        </form>
    </div>

    <?php if (empty($logs)): ?>
        <div class="empty-state"><?= t('secret_logs.access.empty') ?></div>
    <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('secret_logs.table.utc_date') ?></th>
                    <th><?= t('secret_logs.table.interface_date') ?></th>
                    <th><?= t('logs.table.action') ?></th>
                    <th>Purpose</th>
                    <th>Reference</th>
                    <th><?= t('common.status') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (array_reverse($logs) as $row): ?>
                <?php
                $ts = (string) ($row['ts'] ?? '');
                $rowAction = (string) ($row['action'] ?? '');
                $isBrokerEvent = in_array($rowAction, $brokerEvents, true);
                ?>
                <tr<?= $isBrokerEvent ? ' style="background:color-mix(in srgb,var(--yellow,#d69e2e) 5%,var(--bg2))"' : '' ?>>
                    <td class="mono" style="font-size:12px;white-space:nowrap"><?= h($ts) ?></td>
                    <td style="font-size:12px;white-space:nowrap"><?= h($ts !== '' ? formatDateForDisplay($ts) : '') ?></td>
                    <td>
                        <?php if ($isBrokerEvent): ?>
                        <span class="badge badge-yellow" style="background:color-mix(in srgb,var(--yellow,#d69e2e) 14%,var(--bg2));color:var(--yellow,#d69e2e);border:1px solid color-mix(in srgb,var(--yellow,#d69e2e) 35%,var(--border))"><?= h($rowAction) ?></span>
                        <?php else: ?>
                        <span class="badge badge-gray"><?= h($rowAction) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= h((string) ($row['purpose'] ?? '')) ?></td>
                    <td class="mono" style="font-size:12px;word-break:break-all"><?= h((string) ($row['secret_ref'] ?? '')) ?></td>
                    <td>
                        <?php if (!empty($row['ok'])): ?>
                            <span class="badge badge-green">OK</span>
                        <?php else: ?>
                            <span class="badge badge-red"><?= t('logs.filter.error') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-header">
        <?= t('secret_logs.events.title') ?>
        <span class="badge badge-blue"><?= count($brokerEventRows) ?></span>
    </div>
    <?php if (empty($brokerEventRows)): ?>
        <div class="empty-state"><?= t('secret_logs.events.empty') ?></div>
    <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('common.date') ?></th>
                    <th><?= t('secret_logs.events.table.event') ?></th>
                    <th><?= t('secret_logs.events.table.severity') ?></th>
                    <th>Endpoint</th>
                    <th><?= t('secret_logs.events.table.message') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($brokerEventRows as $row): ?>
                <?php
                $severity = (string) ($row['severity'] ?? 'info');
                $severityBadge = match ($severity) {
                    'critical' => 'badge-red',
                    'warning' => 'badge-yellow',
                    'success' => 'badge-green',
                    default => 'badge-gray',
                };
                ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap"><?= h(formatDateForDisplay((string) ($row['created_at'] ?? ''))) ?></td>
                    <td><span class="badge badge-gray"><?= h((string) ($row['event_type'] ?? '')) ?></span></td>
                    <td><span class="badge <?= $severityBadge ?>"><?= h($severity) ?></span></td>
                    <td class="mono" style="font-size:12px;word-break:break-all"><?= h((string) ($row['endpoint'] ?? '')) ?></td>
                    <td><?= h((string) ($row['message'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<div class="alert alert-info" style="font-size:12px">
    <?= t('secret_logs.security_notice') ?>
</div>

<?php if (!empty($brokerDbEvents)): ?>
<div class="card mb-4">
    <div class="card-header">
        <?= t('secret_logs.db_events.title') ?>
        <span class="badge badge-blue"><?= count($brokerDbEvents) ?></span>
    </div>
    <div class="card-body">
        <div class="settings-help" style="margin-bottom:12px">
            <?= t('secret_logs.db_events.help') ?>
        </div>
    </div>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th><?= t('common.date') ?></th>
                <th><?= t('common.type') ?></th>
                <th><?= t('secret_logs.events.table.severity') ?></th>
                <th>Endpoint</th>
                <th><?= t('secret_logs.db_events.table.node') ?></th>
                <th><?= t('secret_logs.events.table.message') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($brokerDbEvents as $ev): ?>
            <?php
            $evType = (string) ($ev['event_type'] ?? '');
            $evSev  = (string) ($ev['severity']   ?? '');
            $evBadgeClass = match ($evSev) {
                'critical' => 'badge-red',
                'warning'  => 'badge-yellow',
                'success'  => 'badge-green',
                default    => 'badge-gray',
            };
            ?>
            <tr>
                <td style="font-size:12px;white-space:nowrap"><?= h((string) ($ev['created_at'] ?? '')) ?></td>
                <td><span class="badge badge-gray"><?= h($evType) ?></span></td>
                <td><span class="badge <?= $evBadgeClass ?>"><?= h($evSev) ?></span></td>
                <td class="mono" style="font-size:11px;word-break:break-all"><?= h((string) ($ev['endpoint'] ?? '')) ?></td>
                <td style="font-size:12px;color:var(--text2)"><?= h((string) ($ev['node_label'] ?? '')) ?></td>
                <td style="font-size:12px;color:var(--text2)"><?= h((string) ($ev['message'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php include 'layout_bottom.php'; ?>
