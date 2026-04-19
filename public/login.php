<?php
require_once __DIR__ . '/../src/bootstrap.php';

if (Auth::isLoggedIn()) { redirectTo('/index.php'); }

$error   = '';
$warning = '';
$step    = 'credentials';
$expired = !empty($_GET['expired']);
$reason  = $_GET['reason'] ?? '';

$pending = Auth::getPendingSecondFactor();

// Resolves the current step: primary method by default, selection screaten
// only after an explicit "Try another method" action.
$resolveStep = static function (?array $pending): string {
    if (!$pending) return 'credentials';
    if (!empty($pending['switch_requested'])) {
        return 'choose_method';
    }
    return (string) ($pending['active_method'] ?? $pending['preferred_method']);
};

$ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateInfo = Auth::getRateLimitInfo($ip);
$totpRateInfo = Auth::getTotpRateLimitInfo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfRequest()) {
        http_response_code(403);
        Auth::clearPendingSecondFactor();
        $pending = null;
        $step = 'credentials';
        $error = t('error.csrf');
    }

    if (($_POST['step'] ?? '') === 'credentials' && $error === '') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $result   = Auth::login($username, $password);

        if (!empty($result['blocked'])) {
            $error = $result['message'];
        } elseif ($result['success']) {
            if (!empty($result['second_factor_required'])) {
                $pending = Auth::getPendingSecondFactor();
                $step = $resolveStep($pending);
                $totpRateInfo = Auth::getTotpRateLimitInfo();
            } else {
                redirectTo(Auth::postLoginRedirect());
            }
        } else {
            $rateInfo = Auth::getRateLimitInfo($ip);
            $error = !empty($result['requires_2fa_setup'])
                ? (string) ($result['message'] ?? t('auth.authentication_failed'))
                : t('auth.authentication_failed');
            if ($rateInfo['remaining'] <= 2 && $rateInfo['remaining'] > 0) {
                $warning = t('auth.attempts_warning', ['remaining' => $rateInfo['remaining']]);
            }
        }
    }

    if (($_POST['step'] ?? '') === 'request_method_switch' && $error === '') {
        if (!Auth::requestSecondFactorMethodSwitch()) {
            $error = t('auth.session_invalid_login');
        }
        $pending = Auth::getPendingSecondFactor();
        $step = $resolveStep($pending);
    }

    if (($_POST['step'] ?? '') === 'select_method' && $error === '') {
        $method = $_POST['method'] ?? '';
        if (!Auth::selectSecondFactorMethod($method)) {
            $error = t('auth.method_invalid');
        }
        $pending = Auth::getPendingSecondFactor();
        $step = $resolveStep($pending);
        $totpRateInfo = Auth::getTotpRateLimitInfo();
    }

    if (($_POST['step'] ?? '') === 'totp' && $error === '') {
        $code = preg_replace('/\s/', '', $_POST['totp_code'] ?? '');
        $result = Auth::verifyTotpResult($code);
        if (!empty($result['success'])) {
            redirectTo(Auth::postLoginRedirect());
        }
        $error = (string) ($result['message'] ?? t('auth.authentication_failed'));
        $pending = Auth::getPendingSecondFactor();
        $step = $resolveStep($pending);
        $totpRateInfo = is_array($result['rate_limit'] ?? null) ? $result['rate_limit'] : Auth::getTotpRateLimitInfo();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $step = $resolveStep($pending);
}

$pendingUserId = (int) ($pending['user_id'] ?? 0);
$pendingMethods = $pending['methods'] ?? [];
$currentMethod = (string) ($pending['active_method'] ?? $pending['preferred_method'] ?? '');
$alternateMethods = array_values(array_filter($pendingMethods, static fn (string $method): bool => $method !== $currentMethod));
$canChoose = count($pendingMethods) > 1;

// Generates (and stores in session) the CSRF token from the first GET render
// so it is available for all subsequent POST checks.
$loginCsrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="<?= h(Translator::locale()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(t('auth.page_title')) ?> - <?= h(AppConfig::appName()) ?></title>
<style<?= cspNonceAttr() ?>>
:root{--bg:#0d1117;--bg2:#161b22;--bg3:#21262d;--border:#30363d;--text:#e6edf3;--text2:#8b949e;
      --accent:#58a6ff;--accent2:#1f6feb;--red:#f85149;--green:#3fb950;--yellow:#d29922;--radius:6px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);
     min-height:100vh;display:flex;align-items:center;justify-content:center;font-size:14px;padding:20px}
.box{width:min(400px,100%);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:32px}
.logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;justify-content:center}
.logo-icon{width:38px;height:38px;background:var(--accent2);border-radius:var(--radius);
           display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:700;color:#fff}
.logo-text{font-size:18px;font-weight:600}
.form-group{margin-bottom:14px}
.form-label{display:block;font-size:12px;font-weight:500;color:var(--text2);margin-bottom:6px}
.form-control{width:100%;padding:9px 12px;background:var(--bg3);border:1px solid var(--border);
              border-radius:var(--radius);color:var(--text);font-size:13px;transition:border-color .15s}
.form-control:focus{outline:none;border-color:var(--accent)}
.btn-submit,.btn-secondary{width:100%;padding:9px;background:var(--accent2);border:none;border-radius:var(--radius);
            color:#fff;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;margin-top:4px}
.btn-submit:hover,.btn-secondary:hover{background:var(--accent)}
.btn-secondary{background:var(--bg3);border:1px solid var(--border)}
.btn-secondary:hover{border-color:var(--accent);color:#fff}
.btn-submit:disabled,.btn-secondary:disabled{opacity:.5;cursor:not-allowed}
.alert{padding:10px 12px;border-radius:var(--radius);font-size:13px;margin-bottom:14px}
.alert-danger {background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:var(--red)}
.alert-warning{background:rgba(210,153,34,.1);border:1px solid rgba(210,153,34,.3);color:var(--yellow)}
.alert-info   {background:rgba(88,166,255,.1);border:1px solid rgba(88,166,255,.3);color:var(--accent)}
.attempts-bar{height:3px;background:var(--bg3);border-radius:99px;margin-top:8px;overflow:hidden}
.attempts-fill{height:100%;border-radius:99px;transition:width .3s}
.totp-input{font-family:monospace;font-size:24px;letter-spacing:8px;text-align:center;padding:12px}
.back-link{display:block;text-align:center;margin-top:16px;font-size:12px;color:var(--text2)}
.back-link a{color:var(--accent)}
.choice-buttons{display:flex;flex-direction:column;gap:10px}
.choice-buttons form{margin:0}
.helper{font-size:12px;color:var(--text2);margin-bottom:16px;line-height:1.5}
@media (max-width: 640px){
    body{padding:12px}
    .box{padding:22px}
    .totp-input{font-size:20px;letter-spacing:6px}
}
</style>
</head>
<body>
<div class="box">
    <div class="logo">
        <div class="logo-icon"><?= h(AppConfig::appLogoLetter()) ?></div>
        <span class="logo-text"><?= h(AppConfig::appName()) ?></span>
    </div>
    <?php if (AppConfig::loginTagline() !== ''): ?>
    <div style="text-align:center;font-size:12px;color:var(--text2);margin:-14px 0 18px"><?= h(AppConfig::loginTagline()) ?></div>
    <?php endif; ?>

    <?php if ($expired): ?>
    <div class="alert alert-warning">
        <?= match($reason) {
            'inactivity'  => t('auth.session_expired_inactivity'),
            'revoked'     => t('auth.session_revoked'),
            'fingerprint' => t('auth.session_invalid_fingerprint'),
            'ip_mismatch' => t('auth.session_invalid_ip'),
            default       => t('auth.session_expired'),
        } ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($warning): ?>
    <div class="alert alert-warning"><?= h($warning) ?></div>
    <?php endif; ?>

    <?php if ($rateInfo['blocked']): ?>
    <div class="alert alert-danger">
        <?= t('auth.blocked') ?><br>
        <span style="font-size:12px"><?= t('auth.blocked_retry', ['min' => $rateInfo['lockout_min']]) ?></span>
    </div>

    <?php elseif ($step === 'credentials'): ?>
    <form method="POST">
        <input type="hidden" name="step" value="credentials">
        <input type="hidden" name="csrf_token" value="<?= h($loginCsrfToken) ?>">
        <div class="form-group">
            <label class="form-label"><?= t('auth.username') ?></label>
            <input type="text" name="username" class="form-control"
                   value="<?= h($_POST['username'] ?? '') ?>"
                   autofocus autocomplete="username" required
                   <?= $rateInfo['blocked'] ? 'disabled' : '' ?>>
        </div>
        <div class="form-group">
            <label class="form-label"><?= t('auth.password') ?></label>
            <input type="password" name="password" class="form-control"
                   autocomplete="current-password" required
                   <?= $rateInfo['blocked'] ? 'disabled' : '' ?>>
        </div>

        <?php if ($rateInfo['attempts'] > 0 && !$rateInfo['blocked']): ?>
        <div style="font-size:11px;color:var(--text2);margin-bottom:12px">
            <?= t('auth.attempts_counter', ['attempts' => $rateInfo['attempts'], 'max' => $rateInfo['max']]) ?>
            <div class="attempts-bar">
                <div class="attempts-fill" style="
                    width:<?= round($rateInfo['attempts'] / $rateInfo['max'] * 100) ?>%;
                    background:<?= $rateInfo['remaining'] <= 1 ? 'var(--red)' : ($rateInfo['remaining'] <= 2 ? 'var(--yellow)' : 'var(--accent)') ?>">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn-submit" <?= $rateInfo['blocked'] ? 'disabled' : '' ?>>
            <?= t('auth.login') ?>
        </button>
    </form>

    <?php elseif ($step === 'choose_method'): ?>
    <div style="text-align:center;font-size:34px;margin-bottom:12px"><?= h(AppConfig::appLogoLetter()) ?></div>
    <div style="font-size:14px;font-weight:600;text-align:center;margin-bottom:6px"><?= t('auth.choose_method') ?></div>
    <div class="helper"><?= t('auth.choose_method_helper') ?></div>
    <div class="choice-buttons">
        <?php if (in_array('webauthn', $alternateMethods, true)): ?>
        <form method="POST">
            <input type="hidden" name="step" value="select_method">
            <input type="hidden" name="method" value="webauthn">
            <input type="hidden" name="csrf_token" value="<?= h($loginCsrfToken) ?>">
            <button type="submit" class="btn-submit"><?= t('auth.use_hardware_key') ?></button>
        </form>
        <?php endif; ?>
        <?php if (in_array('totp', $alternateMethods, true)): ?>
        <form method="POST">
            <input type="hidden" name="step" value="select_method">
            <input type="hidden" name="method" value="totp">
            <input type="hidden" name="csrf_token" value="<?= h($loginCsrfToken) ?>">
            <button type="submit" class="btn-secondary"><?= t('auth.use_totp') ?></button>
        </form>
        <?php endif; ?>
    </div>

    <?php elseif ($step === 'webauthn'): ?>
    <div style="text-align:center;font-size:34px;margin-bottom:12px"><?= h(AppConfig::appLogoLetter()) ?></div>
    <div style="font-size:14px;font-weight:600;text-align:center;margin-bottom:6px"><?= t('auth.webauthn_title') ?></div>
    <div class="helper"><?= t('auth.webauthn_helper') ?></div>
    <div id="webauthn-status" class="alert alert-info" style="display:none"></div>
    <button type="button" class="btn-submit" id="btn-webauthn-login" onclick="startWebAuthnLogin()"><?= t('auth.webauthn_verify') ?></button>
    <?php if ($canChoose): ?>
    <form method="POST" style="margin-top:10px">
        <input type="hidden" name="step" value="request_method_switch">
        <input type="hidden" name="csrf_token" value="<?= h($loginCsrfToken) ?>">
        <button type="submit" class="btn-secondary"><?= t('auth.try_another_method') ?></button>
    </form>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;font-size:34px;margin-bottom:12px">2FA</div>
    <div style="font-size:14px;font-weight:600;text-align:center;margin-bottom:6px"><?= t('auth.totp_title') ?></div>
    <div class="helper"><?= t('auth.totp_helper') ?></div>
    <?php if (!empty($totpRateInfo['blocked'])): ?>
    <div class="alert alert-danger">
        <?= t('auth.blocked') ?><br>
        <span style="font-size:12px"><?= t('auth.blocked_retry_seconds', ['seconds' => (int) ($totpRateInfo['retry_after'] ?? 0)]) ?></span>
    </div>
    <?php endif; ?>
    <form method="POST" id="totp-form">
        <input type="hidden" name="step" value="totp">
        <input type="hidden" name="csrf_token" value="<?= h($loginCsrfToken) ?>">
        <div class="form-group">
            <input type="text" name="totp_code" class="form-control totp-input"
                    placeholder="000000" maxlength="6" pattern="\d{6}"
                    autocomplete="one-time-code" autofocus required <?= !empty($totpRateInfo['blocked']) ? 'disabled' : '' ?>
                    oninput="this.value=this.value.replace(/\D/g,'');if(this.value.length===6)this.form.submit()">
        </div>
        <button type="submit" class="btn-submit" <?= !empty($totpRateInfo['blocked']) ? 'disabled' : '' ?>><?= t('auth.totp_verify') ?></button>
    </form>
    <?php if ($canChoose): ?>
    <form method="POST" style="margin-top:10px">
        <input type="hidden" name="step" value="request_method_switch">
        <input type="hidden" name="csrf_token" value="<?= h($loginCsrfToken) ?>">
        <button type="submit" class="btn-secondary"><?= t('auth.try_another_method') ?></button>
    </form>
    <?php endif; ?>
    <?php endif; ?>

    <div class="back-link"><a href="<?= routePath('/login.php') ?>"><?= t('auth.back_to_login') ?></a></div>
</div>

<script nonce="<?= CSP_NONCE ?>">
const pendingUserId = <?= $pendingUserId ?>;
const currentStep = <?= json_encode($step) ?>;
const loginCsrfToken = <?= json_encode($loginCsrfToken) ?>;

function base64UrlToUint8Array(value) {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = normalized + '='.repeat((4 - normalized.length % 4) % 4);
    const binary = atob(padded);
    return Uint8Array.from(binary, char => char.charCodeAt(0));
}

function arrayBufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach(byte => { binary += String.fromCharCode(byte); });
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

function setWebAuthnStatus(message, type = 'info') {
    const el = document.getElementById('webauthn-status');
    if (!el) return;
    el.className = 'alert ' + (type === 'danger' ? 'alert-danger' : type === 'warning' ? 'alert-warning' : 'alert-info');
    el.textContent = message;
    el.style.display = 'block';
}

async function startWebAuthnLogin() {
    const btn = document.getElementById('btn-webauthn-login');
    if (!window.PublicKeyCredential || !navigator.credentials?.get) {
        setWebAuthnStatus(window.FULGURITE_STRINGS?.webauthn_unavailable_browser || 'WebAuthn unavailable', 'danger');
        return;
    }
    if (!pendingUserId) {
        setWebAuthnStatus(window.FULGURITE_STRINGS?.webauthn_session_invalid || 'Invalid session', 'danger');
        return;
    }

    btn.disabled = true;
    setWebAuthnStatus(window.FULGURITE_STRINGS?.webauthn_waiting || '...', 'info');

    try {
        const optRes = await fetch('/api/webauthn_auth_options.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': loginCsrfToken },
            body: JSON.stringify({ user_id: pendingUserId, csrf_token: loginCsrfToken }),
        });
        const optData = await optRes.json();
        if (!optData.success) throw new Error(optData.error || (window.FULGURITE_STRINGS?.webauthn_prepare_error || 'Unable to prepare WebAuthn'));

        const options = optData.options;
        options.challenge = base64UrlToUint8Array(options.challenge);
        options.allowCredentials = (options.allowCredentials || []).map(cred => ({
            ...cred,
            id: base64UrlToUint8Array(cred.id),
        }));

        const assertion = await navigator.credentials.get({ publicKey: options });
        const payload = {
            id: assertion.id,
            rawId: arrayBufferToBase64Url(assertion.rawId),
            clientDataJSON: arrayBufferToBase64Url(assertion.response.clientDataJSON),
            authenticatorData: arrayBufferToBase64Url(assertion.response.authenticatorData),
            signature: arrayBufferToBase64Url(assertion.response.signature),
            userHandle: assertion.response.userHandle ? arrayBufferToBase64Url(assertion.response.userHandle) : '',
        };

        const verifyRes = await fetch('/api/webauthn_auth_verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': loginCsrfToken },
            body: JSON.stringify({ assertion: payload, csrf_token: loginCsrfToken }),
        });
        const verifyData = await verifyRes.json();
        if (!verifyData.success) throw new Error(verifyData.error || (window.FULGURITE_STRINGS?.webauthn_verify_error || 'Verification failed'));

        window.location.href = verifyData.redirect || '<?= routePath('/index.php') ?>';
    } catch (error) {
        setWebAuthnStatus(error.message || (window.FULGURITE_STRINGS?.operation_cancelled || 'Cancelled'), 'danger');
    } finally {
        btn.disabled = false;
    }
}

if (<?= AppConfig::webauthnLoginAutostart() ? 'true' : 'false' ?> && currentStep === 'webauthn' && pendingUserId) {
    window.setTimeout(() => {
        const btn = document.getElementById('btn-webauthn-login');
        if (btn && !btn.disabled) startWebAuthnLogin();
    }, 200);
}

window.startWebAuthnLogin = startWebAuthnLogin;
</script>
</body>
</html>
