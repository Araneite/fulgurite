<?php
/**
 * Default slot: global overlays (toasts, session warning).
 * The page footer is rendered directly by layout_bottom.php.
 * JS and global config are injected by layout_bottom.php
 * (outside the slot system for security reasons).
 */
?>
<div id="toast-container"></div>

<div id="session-warning" style="display:none;position:fixed;bottom:24px;right:24px;z-index:9998;
     background:var(--bg2);border:1px solid var(--yellow);border-radius:8px;padding:16px 20px;
     box-shadow:0 8px 24px rgba(0,0,0,.4);max-width:320px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <span style="font-size:20px">&#9888;&#65039;</span>
        <div>
            <div style="font-weight:600;font-size:13px;color:var(--yellow)"><?= h(t('layout.session_expiring_title')) ?></div>
            <div style="font-size:12px;color:var(--text2)"><?= h(t('layout.session_expiring_prefix')) ?> <span id="session-countdown" style="color:var(--yellow);font-weight:600"></span></div>
        </div>
    </div>
    <div style="display:flex;gap:8px">
        <button onclick="extendSession()" style="flex:1;padding:6px;background:var(--accent2);border:none;
                border-radius:6px;color:#fff;font-size:12px;cursor:pointer;font-weight:500">
            <?= h(t('layout.stay_connected')) ?>
        </button>
        <form method="POST" action="<?= routePath('/logout.php') ?>" style="flex:1;margin:0">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <button type="submit" style="width:100%;padding:6px;background:var(--bg3);border:1px solid var(--border);
               border-radius:6px;color:var(--text2);font-size:12px;cursor:pointer">
                <?= h(t('auth.logout')) ?>
            </button>
        </form>
    </div>
</div>
