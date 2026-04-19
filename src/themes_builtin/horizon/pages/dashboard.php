<?php
/**
 * Dashboard page fully overridden by Horizon.
 *
 * This page completely replaces the default dashboard markup.
 * It uses rui_* template tags to access core data
 * without touching internal classes. All returned data
 * by rui_* is already filtered by user permissions.
 *
 * Core already preloads a summary via $ctx['data']['summary'],
 * but tags can also be called directly to show usage.
 */

$user = rui_current_user();
$summary = $ctx['data']['summary'] ?? rui_dashboard_summary();
$repos = rui_list_repos();
$jobs = rui_list_backup_jobs();
$notifications = rui_list_notifications(5);
?>

<div class="horizon-hero" style="margin-bottom:24px">
    <h1 style="font-size:28px;margin:0 0 4px">
        <?= h(t('horizon.dashboard.greeting', ['name' => (string) ($user['display_name'] ?? $user['username'] ?? '')])) ?>
    </h1>
    <p style="color:var(--text2);margin:0">
        <?= h(t('horizon.dashboard.hero_desc')) ?>
    </p>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <div class="card">
        <div class="card-body">
            <div style="font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.08em"><?= h(t('dashboard.stat.repos')) ?></div>
            <div style="font-size:28px;font-weight:600;margin-top:6px"><?= (int) $summary['total'] ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.08em"><?= h(t('dashboard.stat.snapshots')) ?></div>
            <div style="font-size:28px;font-weight:600;margin-top:6px;color:var(--accent)">
                <?= (int) $summary['snapshots'] ?>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.08em"><?= h(t('dashboard.stat.repos_ok')) ?></div>
            <div style="font-size:28px;font-weight:600;margin-top:6px;color:var(--green)">
                <?= (int) $summary['ok'] ?>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.08em"><?= h(t('dashboard.stat.alerts')) ?></div>
            <div style="font-size:28px;font-weight:600;margin-top:6px;color:<?= $summary['alerts'] > 0 ? 'var(--red)' : 'var(--text2)' ?>">
                <?= (int) $summary['alerts'] ?>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
    <div class="card">
        <div class="card-header"><?= h(t('dashboard.stat.repos')) ?></div>
        <div class="card-body" style="padding:0">
            <?php if (empty($repos)): ?>
                <div class="empty-state" style="padding:24px"><?= h(t('horizon.dashboard.repos.empty_accessible')) ?></div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= h(t('common.name')) ?></th>
                            <th><?= h(t('common.type')) ?></th>
                            <th style="text-align:right"><?= h(t('dashboard.stat.snapshots')) ?></th>
                            <th><?= h(t('common.status')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $statuses = [];
                        foreach (rui_repo_statuses() as $row) {
                            $statuses[(int) $row['repo_id']] = $row;
                        }
                        foreach ($repos as $repo):
                            $s = $statuses[(int) $repo['id']] ?? [];
                            $status = (string) ($s['status'] ?? 'unknown');
                            $color = match ($status) {
                                'ok' => 'var(--green)',
                                'warning' => 'var(--yellow)',
                                'error', 'no_snap' => 'var(--red)',
                                default => 'var(--text2)',
                            };
                        ?>
                        <tr>
                            <td>
                                <a href="<?= h(routePath('/explore.php', ['repo' => (int) $repo['id']])) ?>">
                                    <?= h((string) $repo['name']) ?>
                                </a>
                            </td>
                            <td style="color:var(--text2);font-size:12px"><?= h((string) ($repo['type'] ?? '')) ?></td>
                            <td style="text-align:right"><?= (int) ($s['count'] ?? 0) ?></td>
                            <td>
                                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $color ?>;margin-right:6px"></span>
                                <?= h($status) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?= h(t('dashboard.activity.title')) ?></div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div style="color:var(--text2);font-size:13px"><?= h(t('horizon.dashboard.notifications.empty')) ?></div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div style="padding:10px 0;border-bottom:1px solid var(--border)">
                        <div style="font-size:13px;font-weight:500"><?= h((string) ($notif['title'] ?? '')) ?></div>
                        <div style="font-size:12px;color:var(--text2);margin-top:2px">
                            <?= h(formatDate((string) ($notif['created_at'] ?? ''))) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (rui_can('backup_jobs.manage') && !empty($jobs)): ?>
        <div class="card-header" style="border-top:1px solid var(--border)"><?= h(t('nav.backup_jobs')) ?></div>
        <div class="card-body">
            <div style="font-size:13px;color:var(--text2)">
                <?= h(t('horizon.dashboard.jobs.configured_count', ['count' => (string) count($jobs)])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
