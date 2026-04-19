<?php
/**
 * Slot sidebar override by Horizon — lightweight version that REUSES
 * the parts to the lieu of all reecrire. *
 * Only wrapper markup (<aside>) and part order are changed.
 * navigation item list always comes from core (permissions-aware), and
 * user badge is customized via targeted override in
 * parts/sidebar_user.php a cote of this file. */
?>
<aside class="sidebar" id="app-sidebar">
    <?php ThemeRenderer::renderPart('sidebar_logo', $ctx); ?>
    <?php ThemeRenderer::renderPart('sidebar_nav', $ctx); ?>
    <?php ThemeRenderer::renderPart('sidebar_user', $ctx); /* surcharge locale ci-dessous */ ?>
</aside>
