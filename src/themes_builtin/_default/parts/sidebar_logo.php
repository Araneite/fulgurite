<?php
/**
 * Part by default : logo + bouton of fermeture mobile of the sidebar.
 * Reusable in any custom slot via:
 * ThemeRenderer::renderPart('sidebar_logo', $ctx);
 */
?>
<div class="sidebar-logo">
    <div class="logo-icon"><?= h((string) ($ctx['app_logo_letter'] ?? 'R')) ?></div>
    <span class="logo-text"><?= h((string) ($ctx['app_name'] ?? 'Fulgurite')) ?></span>
    <button type="button" class="sidebar-close" data-sidebar-close aria-label="<?= h(t('layout.close_menu')) ?>">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
