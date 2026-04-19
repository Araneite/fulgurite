<?php
/**
 * Slot by default : barre superieure. Compose 2 parts + the HTML of
 * actions injected by page (already escaped at caller level).
 */
$_canQuickBackup = !empty($ctx['user'])
    && Auth::hasPermission('backup_jobs.manage')
    && Auth::hasPermission('hosts.manage')
    && Auth::hasPermission('repos.manage')
    && Auth::hasPermission('sshkeys.manage');
$_isQuickPage = ($ctx['active'] ?? '') === 'backup_quick';
?>
<div class="topbar">
    <?php ThemeRenderer::renderPart('topbar_title', $ctx); ?>
    <div class="topbar-actions flex items-center gap-2">
        <?php ThemeRenderer::renderPart('topbar_notifications', $ctx); ?>
        <?php if ($_canQuickBackup): ?>
        <a href="<?= routePath('/quick_backup.php') ?>"
           class="topbar-quick-btn<?= $_isQuickPage ? ' active' : '' ?>"
           title="<?= h(t('topbar.quick_backup_title')) ?>">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" width="15" height="15">
                <path d="M9 2 6 8.5h4L7 14" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span><?= h(t('topbar.quick_backup_label')) ?></span>
        </a>
        <?php endif; ?>
        <?= $ctx['actions'] ?? '' /* already-built HTML from the page */ ?>
    </div>
</div>
