<?php
/**
 * Slot by default : barre laterale.
 *
 * This slot n'is qu'a COMPOSITION of parts reusable. a theme custom * can :
 * a) override whole slot (copy this file)
 * b) override only one part (e.g., parts/sidebar_nav.php)
 * c) from son propre slot, rappeler the parts non modifiees :
 * <?php ThemeRenderer::renderPart('sidebar_logo', $ctx); ?>
 * <?php ThemeRenderer::renderPart('sidebar_nav', $ctx); ?>
 * <?php ThemeRenderer::renderPart('sidebar_user', $ctx); ?>
 */
?>
<aside class="sidebar" id="app-sidebar">
    <?php ThemeRenderer::renderPart('sidebar_logo', $ctx); ?>
    <?php ThemeRenderer::renderPart('sidebar_nav', $ctx); ?>
    <?php ThemeRenderer::renderPart('sidebar_user', $ctx); ?>
</aside>
