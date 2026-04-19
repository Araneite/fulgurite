<?php
require_once __DIR__ . '/../src/bootstrap.php';

if (Auth::isLoggedIn()) {
    redirectTo(Auth::postLoginRedirect());
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$invite = $token !== '' ? UserManager::findInvitationByToken($token) : null;
$flash = null;
$accepted = false;

$inviteStatus = 'invalid';
if ($invite) {
    if (!empty($invite['revoked_at'])) {
        $inviteStatus = 'revoked';
    } elseif (!empty($invite['accepted_at'])) {
        $inviteStatus = 'accepted';
    } elseif (!empty($invite['expires_at']) && strtotime((string) $invite['expires_at']) < time()) {
        $inviteStatus = 'expired';
    } else {
        $inviteStatus = 'active';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($inviteStatus !== 'active') {
        $flash = ['type' => 'danger', 'msg' => t('flash.invite.inactive')];
    } elseif (strlen($password) < 6) {
        $flash = ['type' => 'danger', 'msg' => t('flash.invite.password_short')];
    } elseif ($password !== $confirmPassword) {
        $flash = ['type' => 'danger', 'msg' => t('flash.invite.password_mismatch')];
    } else {
        $hibpCount = checkHibp($password);
        if ($hibpCount > 0) {
            $flash = ['type' => 'danger', 'msg' => t('flash.invite.hibp', ['count' => $hibpCount])];
        } else {
            try {
                $acceptedUser = UserManager::acceptInvitation($token, [
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                ]);
                Auth::log('invitation_accept', 'Invitation acceptee pour ' . $acceptedUser['username']);
                $accepted = true;

                $loginResult = Auth::login($acceptedUser['username'], $password);
                if (!empty($loginResult['success'])) {
                    if (!empty($loginResult['second_factor_required'])) {
                        redirectTo('/login.php');
                    }

                    redirectTo(Auth::postLoginRedirect());
                }

                $flash = ['type' => 'success', 'msg' => t('flash.invite.accepted')];
            } catch (Throwable $e) {
                $flash = ['type' => 'danger', 'msg' => $e->getMessage()];
            }
        }
    }
}

$displayName = $invite ? UserManager::displayName($invite) : '';
?>
<!DOCTYPE html>
<html lang="<?= h(Translator::locale()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(t('invite.page_title', ['app' => AppConfig::appName()])) ?></title>
<style<?= cspNonceAttr() ?>>
:root{--bg:#0d1117;--bg2:#161b22;--bg3:#21262d;--border:#30363d;--text:#e6edf3;--text2:#8b949e;--accent:#58a6ff;--accent2:#1f6feb;--red:#f85149;--green:#3fb950;--yellow:#d29922;--radius:8px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{width:min(480px,100%);background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:28px;display:flex;flex-direction:column;gap:18px}
.logo{display:flex;align-items:center;gap:10px;justify-content:center}
.logo-icon{width:38px;height:38px;background:var(--accent2);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff}
.logo-text{font-size:18px;font-weight:600}
.meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.meta-card{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px}
.meta-label{font-size:11px;color:var(--text2);margin-bottom:4px}
.meta-value{font-size:14px;font-weight:500;word-break:break-word}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-label{font-size:12px;color:var(--text2)}
.form-control{width:100%;padding:10px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px}
.form-control:focus{outline:none;border-color:var(--accent)}
.btn{width:100%;padding:10px 14px;border:none;border-radius:8px;background:var(--accent2);color:#fff;font-size:14px;font-weight:600;cursor:pointer}
.btn:hover{background:var(--accent)}
.btn-secondary{display:block;text-align:center;padding:10px 14px;border:1px solid var(--border);border-radius:8px;color:var(--text2);text-decoration:none}
.alert{padding:11px 13px;border-radius:8px;font-size:13px}
.alert-danger{background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:var(--red)}
.alert-success{background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.3);color:var(--green)}
.alert-warning{background:rgba(210,153,34,.1);border:1px solid rgba(210,153,34,.3);color:var(--yellow)}
.helper{font-size:12px;color:var(--text2);line-height:1.5}
@media (max-width:640px){.box{padding:22px}.meta-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="box">
    <div class="logo">
        <div class="logo-icon"><?= h(AppConfig::appLogoLetter()) ?></div>
        <div class="logo-text"><?= h(AppConfig::appName()) ?></div>
    </div>

    <div style="text-align:center">
        <div style="font-size:20px;font-weight:600"><?= t('invite.heading') ?></div>
        <div class="helper" style="margin-top:6px"><?= t('invite.subtitle') ?></div>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if ($inviteStatus === 'active' && $invite): ?>
    <div class="meta-grid">
        <div class="meta-card">
            <div class="meta-label"><?= t('invite.label.user') ?></div>
            <div class="meta-value"><?= h((string) $invite['username']) ?></div>
        </div>
        <div class="meta-card">
            <div class="meta-label"><?= t('invite.label.role') ?></div>
            <div class="meta-value"><?= h(AppConfig::getRoleLabel((string) $invite['role'])) ?></div>
        </div>
        <div class="meta-card">
            <div class="meta-label"><?= t('invite.label.display_name') ?></div>
            <div class="meta-value"><?= h($displayName !== '' ? $displayName : t('invite.placeholder.display_name')) ?></div>
        </div>
        <div class="meta-card">
            <div class="meta-label"><?= t('invite.label.expires') ?></div>
            <div class="meta-value"><?= h(formatDate((string) $invite['expires_at'])) ?></div>
        </div>
    </div>

    <div class="helper">
        <?php if (!empty($invite['email'])): ?>
        <?= h(t('invite.email_prefilled', ['email' => (string) $invite['email']])) ?><br>
        <?php endif; ?>
        <?= t('invite.complete_info') ?>
    </div>

    <form method="POST" style="display:flex;flex-direction:column;gap:14px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="token" value="<?= h($token) ?>">
        <div class="form-group">
            <label class="form-label"><?= t('invite.password_label') ?></label>
            <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" required autofocus>
        </div>
        <div class="form-group">
            <label class="form-label"><?= t('invite.confirm_label') ?></label>
            <input type="password" name="confirm_password" class="form-control" minlength="6" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn"><?= t('invite.activate_btn') ?></button>
    </form>
    <?php elseif ($inviteStatus === 'revoked'): ?>
    <div class="alert alert-warning"><?= t('invite.revoked') ?></div>
    <?php elseif ($inviteStatus === 'accepted' || $accepted): ?>
    <div class="alert alert-success"><?= t('invite.already_used') ?></div>
    <?php elseif ($inviteStatus === 'expired'): ?>
    <div class="alert alert-warning"><?= t('invite.status.expired') ?></div>
    <?php else: ?>
    <div class="alert alert-danger"><?= t('invite.invalid') ?></div>
    <?php endif; ?>

    <a href="<?= routePath('/login.php') ?>" class="btn-secondary"><?= t('invite.goto_login') ?></a>
</div>
</body>
</html>
