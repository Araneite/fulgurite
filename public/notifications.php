<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();

$user = Auth::currentUser();
$userId = (int) ($user['id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$filter = (string) ($_GET['filter'] ?? 'all');
$unreadOnly = $filter === 'unread';
$limit = 25;
$offset = ($page - 1) * $limit;
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'mark_all_read') {
        $count = AppNotificationManager::markAllRead($userId);
        $flash = [
            'type' => 'success',
            'msg' => $count > 0 ? t('flash.notifications.marked_read', ['count' => $count]) : t('flash.notifications.none_unread'),
        ];
    }

    if ($action === 'mark_read') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $updated = $notificationId > 0 && AppNotificationManager::markRead($userId, $notificationId);
        $flash = [
            'type' => $updated ? 'success' : 'danger',
            'msg' => $updated ? t('flash.notifications.read_updated') : t('flash.notifications.read_error'),
        ];
    }

    if ($action === 'delete_notification') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $deleted = $notificationId > 0 && AppNotificationManager::delete($userId, $notificationId);
        $flash = [
            'type' => $deleted ? 'success' : 'danger',
            'msg' => $deleted ? t('flash.notifications.deleted') : t('flash.notifications.delete_error'),
        ];
    }

    if ($action === 'delete_read') {
        $count = AppNotificationManager::deleteAllRead($userId);
        $flash = [
            'type' => 'success',
            'msg' => $count > 0 ? t('flash.notifications.read_deleted', ['count' => $count]) : t('flash.notifications.none_to_delete'),
        ];
    }
}

$total = AppNotificationManager::countForUser($userId, $unreadOnly);
$pages = max(1, (int) ceil($total / $limit));
if ($page > $pages) {
    $page = $pages;
    $offset = ($page - 1) * $limit;
}

$notifications = AppNotificationManager::listForUser($userId, $limit, $offset, $unreadOnly);
$unreadCount = AppNotificationManager::countForUser($userId, true);
$webPushEnabled = AppConfig::getBool('web_push_enabled');
$browserStatus = $webPushEnabled
    ? t('notifications.browser_push_help_enabled')
    : t('notifications.browser_push_help_disabled');

$filterLinks = [
    ['label' => t('notifications.filter.all'),    'value' => 'all'],
    ['label' => t('notifications.filter.unread'), 'value' => 'unread'],
];

$actions = '';
$actionFragments = [];
if ($unreadCount > 0) {
    $actionFragments[] = '<form method="POST" style="margin:0">'
        . '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">'
        . '<input type="hidden" name="action" value="mark_all_read">'
        . '<button type="submit" class="btn btn-sm">' . h(t('notifications.mark_all_read_btn')) . '</button>'
        . '</form>';
}
$actionFragments[] = '<form method="POST" style="margin:0">'
    . '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">'
    . '<input type="hidden" name="action" value="delete_read">'
    . '<button type="submit" class="btn btn-sm">' . h(t('notifications.delete_read_btn')) . '</button>'
    . '</form>';
$actions = implode('', $actionFragments);

$title = t('notifications.title');
$active = 'notifications';
$subtitle = $unreadCount > 0 ? t('notifications.subtitle_unread', ['count' => $unreadCount]) : t('notifications.subtitle_none');
include 'layout_top.php';
?>

<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
        <div class="card-header">
            <span><?= t('notifications.native_channels') ?></span>
            <?php if ($webPushEnabled): ?>
            <span class="badge badge-blue"><?= t('notifications.web_push_available') ?></span>
            <?php else: ?>
            <span class="badge badge-gray"><?= t('notifications.web_push_disabled') ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <div class="settings-help"><?= h($browserStatus) ?></div>
            <div class="settings-help"><?= h(t('notifications.retention_help', ['days' => (int) AppConfig::appNotificationsRetentionDays()])) ?></div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <button type="button" class="btn btn-sm" onclick="enableBrowserNotifications()"><?= t('notifications.enable_browser') ?></button>
                <span id="browser-notification-status" class="settings-inline-result"></span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span><?= t('notifications.center') ?></span>
            <span class="badge <?= $unreadCount > 0 ? 'badge-yellow' : 'badge-gray' ?>"><?= h(t('notifications.unread_badge', ['count' => $unreadCount])) ?></span>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php foreach ($filterLinks as $link): ?>
                <a href="<?= routePath('/notifications.php', ['filter' => $link['value']]) ?>" class="btn btn-sm <?= $filter === $link['value'] ? 'btn-primary' : '' ?>"><?= h($link['label']) ?></a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($notifications)): ?>
            <div class="empty-state" style="padding:32px"><?= $unreadOnly ? t('notifications.empty_unread') : t('notifications.empty_all') ?></div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px">
                <?php foreach ($notifications as $notification): ?>
                <?php
                $severity = (string) ($notification['severity'] ?? 'info');
                $badgeClass = match ($severity) {
                    'critical' => 'badge-red',
                    'warning' => 'badge-yellow',
                    'success' => 'badge-green',
                    default => 'badge-blue',
                };
                $isRead = !empty($notification['is_read']);
                $linkUrl = trim((string) ($notification['link_url'] ?? ''));
                ?>
                <div class="notification-item <?= $isRead ? 'is-read' : 'is-unread' ?>">
                    <div class="notification-item-head">
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                            <span class="badge <?= $badgeClass ?>"><?= h(strtoupper($severity)) ?></span>
                            <span class="badge <?= $isRead ? 'badge-gray' : 'badge-yellow' ?>"><?= $isRead ? t('notifications.badge.read') : t('notifications.badge.unread') ?></span>
                            <span class="badge badge-gray"><?= h((string) $notification['profile_key']) ?> / <?= h((string) $notification['event_key']) ?></span>
                        </div>
                        <div style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) $notification['created_at'])) ?></div>
                    </div>
                    <div class="notification-item-title"><?= h((string) $notification['title']) ?></div>
                    <div class="notification-item-body"><?= nl2br(h((string) $notification['body'])) ?></div>
                    <div class="notification-item-meta">
                        <span><?= h((string) $notification['context_name']) ?></span>
                        <?php if (!empty($notification['browser_delivery'])): ?>
                        <span class="badge badge-blue">Navigateur</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                        <?php if ($linkUrl !== ''): ?>
                        <a href="<?= h($linkUrl) ?>" class="btn btn-sm"><?= t('notifications.open') ?></a>
                        <?php endif; ?>
                        <?php if (!$isRead): ?>
                        <form method="POST" style="margin:0">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                            <button type="submit" class="btn btn-sm"><?= t('notifications.mark_read') ?></button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="margin:0" data-confirm-message="<?= h(t('notifications.delete_confirm')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete_notification">
                            <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><?= t('common.delete') ?></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($pages > 1): ?>
        <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="<?= routePath('/notifications.php', ['page' => $i, 'filter' => $filter]) ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script<?= cspNonceAttr() ?>>
function refreshBrowserNotificationStatus() {
    const target = document.getElementById('browser-notification-status');
    if (!target) {
        return;
    }

    if (!('Notification' in window)) {
        target.textContent = '<?= h(t('notifications.browser_unavailable')) ?>';
        target.style.color = 'var(--red)';
        return;
    }

    const permission = Notification.permission || 'default';
    if (permission === 'granted') {
        target.textContent = '<?= h(t('notifications.browser_granted')) ?>';
        target.style.color = 'var(--green)';
        return;
    }

    if (permission === 'denied') {
        target.textContent = '<?= h(t('notifications.browser_denied')) ?>';
        target.style.color = 'var(--red)';
        return;
    }

    target.textContent = '<?= h(t('notifications.browser_pending')) ?>';
    target.style.color = 'var(--text2)';
}

async function enableBrowserNotifications() {
    const target = document.getElementById('browser-notification-status');
    if (!target) {
        return;
    }

    if (!('Notification' in window)) {
        target.textContent = '<?= h(t('notifications.browser_unavailable')) ?>';
        target.style.color = 'var(--red)';
        return;
    }

    const permission = await Notification.requestPermission();
    refreshBrowserNotificationStatus();
    if (permission === 'granted' && typeof window.toast === 'function') {
        window.toast('<?= h(t('notifications.browser_enabled_toast')) ?>', 'success');
    }
}

refreshBrowserNotificationStatus();
</script>

<?php include 'layout_bottom.php'; ?>
