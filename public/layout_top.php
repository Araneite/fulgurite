<?php
// Build render context (permission-filtered nav, user, theme...)
$_ctx = ThemeRenderer::buildContext([
    'title' => $title ?? null,
    'subtitle' => $subtitle ?? null,
    'active' => $active ?? '',
    'actions' => $actions ?? '',
    'flash' => $flash ?? null,
]);
$_layoutTheme = $_ctx['theme_id'];
$_layoutThemeCss = ThemeManager::renderCss();
$_layoutThemeStylesheet = ThemeManager::renderThemeStylesheet($_layoutTheme);
?><!DOCTYPE html>
<html lang="<?= h(Translator::locale()) ?>" data-theme="<?= h($_layoutTheme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title ?? AppConfig::appName()) ?></title>
<link rel="stylesheet" href="/assets/app.css?v=<?= urlencode(APP_VERSION) ?>">
<style<?= cspNonceAttr() ?> id="fulgurite-themes"><?= $_layoutThemeCss ?></style>
<?php if ($_layoutThemeStylesheet !== ''): ?>
<style<?= cspNonceAttr() ?> id="fulgurite-theme-custom"><?= $_layoutThemeStylesheet ?></style>
<?php endif; ?>
<?php ThemeRenderer::renderSlot('head', $_ctx); ?>
</head>
<body>
<a href="#main-content" class="skip-link"><?= t('layout.skip_to_content') ?></a>
<div class="app" id="app-shell">
    <button type="button" class="sidebar-backdrop" data-sidebar-close aria-label="<?= h(t('layout.close_menu')) ?>"></button>
    <?php if (!empty($_ctx['user'])) ThemeRenderer::renderSlot('sidebar', $_ctx); ?>
    <main class="main" id="main-content" tabindex="-1">
        <?php ThemeRenderer::renderSlot('topbar', $_ctx); ?>
        <div class="content">
            <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= h($flash['msg']) ?></div>
            <?php endif; ?>
