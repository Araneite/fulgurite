<?php
/**
 * Part by default : bloc titre + under-titre + bouton hamburger mobile.
 */
?>
<div class="topbar-heading">
    <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="<?= h(t('layout.open_menu')) ?>">
        <span aria-hidden="true">&#9776;</span>
    </button>
    <div class="topbar-titleblock">
        <div class="page-title"><?= h((string) ($ctx['title'] ?? '')) ?></div>
        <?php if (!empty($ctx['subtitle'])): ?>
            <div class="page-sub"><?= h((string) $ctx['subtitle']) ?></div>
        <?php endif; ?>
    </div>
</div>
