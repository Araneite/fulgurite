<?php
/**
 * Part overridden by Horizon : badge user condense, adapte
 * for horizontal navbar. Reuse data already exposed in $ctx.
 */
$user = $ctx['user'] ?? null;
if (!$user) return;
?>
<div class="sidebar-footer">
    <div class="user-badge">
        <div class="user-avatar"><?= h(strtoupper(substr((string) $user['username'], 0, 1))) ?></div>
        <div>
            <div class="user-name"><?= h((string) $user['username']) ?></div>
            <div class="user-role"><?= h(AppConfig::getRoleLabel((string) $user['role'])) ?></div>
        </div>
    </div>
    <a href="<?= routePath('/profile.php') ?>" class="btn-logout"><?= h(t('nav.profile')) ?></a>
    <form method="POST" action="<?= routePath('/logout.php') ?>" style="margin:0">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <button type="submit" class="btn-logout" style="width:100%;border:none;cursor:pointer"><?= h(t('auth.logout')) ?></button>
    </form>
</div>
