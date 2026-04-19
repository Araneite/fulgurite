<?php
/**
 * Part by default : badge user + liens profil/logout.
 * Ne rend rien if $ctx['user'] is null (visiteur non authentifie).
 */
$user = $ctx['user'] ?? null;
if (!$user) return;
?>
<div class="sidebar-footer">
    <div class="user-badge">
        <div class="user-avatar"><?= h(strtoupper(substr((string) $user['username'], 0, 1))) ?></div>
        <div>
            <div class="user-name"><?= h((string) $user['username']) ?></div>
            <?php if (($user['display_name'] ?? '') !== '' && ($user['display_name'] ?? '') !== ($user['username'] ?? '')): ?>
            <div class="user-role" style="margin-bottom:2px"><?= h((string) $user['display_name']) ?></div>
            <?php endif; ?>
            <div class="user-role"><?= h(AppConfig::getRoleLabel((string) $user['role'])) ?></div>
        </div>
    </div>
    <a href="<?= routePath('/profile.php') ?>" class="btn-logout" style="margin-bottom:4px;border-color:var(--border);color:var(--text2)">
        <?= h(t('nav.profile_2fa')) ?>
    </a>
    <form method="POST" action="<?= routePath('/logout.php') ?>" style="margin:0">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <button type="submit" class="btn-logout" style="width:100%;border:none;cursor:pointer"><?= t('auth.logout') ?></button>
    </form>
</div>
