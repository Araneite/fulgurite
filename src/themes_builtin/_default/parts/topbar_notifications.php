<?php
/**
 * Part by default : lien notifications + badge.
 * Renders nothing if user is not logged in.
 */
if (empty($ctx['user'])) return;
?>
<a href="<?= routePath('/notifications.php') ?>" class="topbar-notification-link <?= ($ctx['active'] ?? '') === 'notifications' ? 'active' : '' ?>">
    <span><?= h(t('nav.notifications')) ?></span>
    <span id="global-notifications-badge" class="topbar-notification-badge" style="display:none">0</span>
</a>
