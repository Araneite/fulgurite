<?php
// =============================================================================
// setup.php — Fulgurite installation wizard (first launch)
// =============================================================================
define('FULGURITE_SETUP', true);

// Minimal bootstrap without database
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Setup/SetupGuard.php';

// Check that setup is not already completed
if (SetupGuard::isInstalled()) {
    header('Location: /');
    exit;
}

if (!function_exists('renderSetupUnlockPage')) {
    function renderSetupUnlockPage(array $status, string $appVersion): void
    {
        $createdAt = !empty($status['created_at']) ? date('Y-m-d H:i:s', (int) $status['created_at']) : 'N/A';
        $expiresAt = !empty($status['expires_at']) ? date('Y-m-d H:i:s', (int) $status['expires_at']) : 'N/A';
        $statusLabel = !$status['configured']
            ? t('setup.unlock.status_no_bootstrap_token')
            : (!empty($status['used'])
                ? t('setup.unlock.status_token_consumed')
                : (!empty($status['expired']) ? t('setup.unlock.status_token_expired') : t('setup.unlock.status_token_active')));
        $statusClass = !$status['configured'] || !empty($status['used']) || !empty($status['expired']) ? 'warn' : 'ok';
        $cliCommand = 'php scripts/setup-bootstrap.php create --ttl=30';
        $path = (string) ($status['path'] ?? '');
        ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars(t('setup.unlock.page_title'), ENT_QUOTES, 'UTF-8') ?> - Fulgurite <?= htmlspecialchars($appVersion) ?></title>
<style<?= cspNonceAttr() ?>>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,rgba(30,64,175,.22),transparent 32%),linear-gradient(180deg,#081122,#0f172a);color:#e2e8f0;font:14px/1.5 Inter,system-ui,sans-serif;padding:24px}
.shell{width:min(720px,100%);display:grid;gap:18px}
.card{background:rgba(15,23,42,.88);border:1px solid rgba(148,163,184,.16);border-radius:22px;padding:28px;box-shadow:0 24px 60px rgba(2,6,23,.34)}
.eyebrow{font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#93c5fd;font-weight:700}
.title{font-size:30px;font-weight:800;letter-spacing:-.03em;margin:8px 0 10px}
.text{color:#cbd5e1}
.status{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;font-weight:700;font-size:12px}
.status.ok{background:rgba(34,197,94,.14);color:#86efac;border:1px solid rgba(34,197,94,.26)}
.status.warn{background:rgba(245,158,11,.14);color:#fcd34d;border:1px solid rgba(245,158,11,.28)}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px}
.meta{padding:14px 16px;border-radius:16px;background:rgba(255,255,255,.03);border:1px solid rgba(148,163,184,.12)}
.meta-label{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px}
.meta-value{font-weight:600;word-break:break-word}
label{display:block;font-size:12px;font-weight:700;color:#cbd5e1;margin-bottom:8px}
input{width:100%;padding:14px 16px;border-radius:14px;border:1px solid rgba(148,163,184,.18);background:rgba(15,23,42,.6);color:#f8fafc;font:inherit}
input:focus{outline:none;border-color:#60a5fa;box-shadow:0 0 0 3px rgba(96,165,250,.18)}
button{margin-top:14px;width:100%;padding:14px 16px;border:0;border-radius:14px;background:linear-gradient(135deg,#2563eb,#0ea5e9);color:white;font:inherit;font-weight:700;cursor:pointer}
button:disabled{opacity:.7;cursor:wait}
.hint,.error,.success{margin-top:12px;font-size:13px}
.hint{color:#94a3b8}
.error{color:#fca5a5}
.success{color:#86efac}
code,pre{font-family:ui-monospace,SFMono-Regular,Consolas,monospace}
pre{margin:0;padding:14px 16px;border-radius:16px;background:#020617;border:1px solid rgba(148,163,184,.14);overflow:auto;color:#bfdbfe}
@media (max-width:720px){.grid{grid-template-columns:1fr}.title{font-size:24px}}
</style>
</head>
<body>
<div class="shell">
    <div class="card">
        <div class="eyebrow"><?= htmlspecialchars(t('setup.unlock.eyebrow'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="title"><?= htmlspecialchars(t('setup.unlock.title'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="text"><?= htmlspecialchars(t('setup.unlock.description'), ENT_QUOTES, 'UTF-8') ?></div>
        <div style="margin-top:18px"><span class="status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusLabel) ?></span></div>
        <div class="grid">
            <div class="meta">
                <div class="meta-label">Fichier bootstrap</div>
                <div class="meta-value"><?= htmlspecialchars($path) ?></div>
            </div>
            <div class="meta">
                <div class="meta-label">Expire le</div>
                <div class="meta-value"><?= htmlspecialchars($expiresAt) ?></div>
            </div>
            <div class="meta">
                <div class="meta-label">Cree le</div>
                <div class="meta-value"><?= htmlspecialchars($createdAt) ?></div>
            </div>
            <div class="meta">
                <div class="meta-label">Session setup</div>
                <div class="meta-value"><?= SetupGuard::sessionTtlSeconds() ?> secondes glissantes</div>
            </div>
        </div>
    </div>

    <div class="card">
        <label for="setup-bootstrap-token"><?= htmlspecialchars(t('setup.unlock.token_label'), ENT_QUOTES, 'UTF-8') ?></label>
        <input id="setup-bootstrap-token" type="password" autocomplete="one-time-code" placeholder="<?= htmlspecialchars(t('setup.unlock.token_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
        <button id="setup-bootstrap-submit" type="button"><?= htmlspecialchars(t('setup.unlock.submit'), ENT_QUOTES, 'UTF-8') ?></button>
        <div class="hint"><?= htmlspecialchars(t('setup.unlock.recommended_command'), ENT_QUOTES, 'UTF-8') ?></div>
        <pre><code><?= htmlspecialchars($cliCommand) ?></code></pre>
        <div id="setup-bootstrap-message" class="hint"><?= htmlspecialchars(t('setup.unlock.token_storage_hint'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>
<script<?= cspNonceAttr() ?>>
(function() {
    const input = document.getElementById('setup-bootstrap-token');
    const button = document.getElementById('setup-bootstrap-submit');
    const message = document.getElementById('setup-bootstrap-message');
    async function unlock() {
        const token = (input.value || '').trim();
        if (!token) {
            message.className = 'error';
            message.textContent = <?= json_encode(t('setup.unlock.token_required'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            return;
        }
        button.disabled = true;
        message.className = 'hint';
        message.textContent = <?= json_encode(t('setup.unlock.verifying'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const formData = new FormData();
        formData.append('action', 'authorize');
        formData.append('setup_token', token);
        try {
            const res = await fetch('setup_action', { method: 'POST', body: formData });
            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || ('HTTP ' + res.status));
            }
            message.className = 'success';
            message.textContent = <?= json_encode(t('setup.unlock.session_opened'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            window.location.reload();
        } catch (error) {
            button.disabled = false;
            message.className = 'error';
            message.textContent = error.message || <?= json_encode(t('setup.unlock.verification_error'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        }
    }
    button.addEventListener('click', unlock);
    input.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            unlock();
        }
    });
})();
</script>
</body>
</html>
        <?php
    }
}

if (!SetupGuard::isSessionAuthorized()) {
    renderSetupUnlockPage(SetupGuard::bootstrapStatus(), defined('APP_VERSION') ? APP_VERSION : '1.1.0');
    exit;
}

SetupGuard::refreshSession();

$appVersion = defined('APP_VERSION') ? APP_VERSION : '1.1.0';

// PHP timezone list for selector
$timezones = DateTimeZone::listIdentifiers();

// Real project path to pre-fill web server configs
$docRoot = realpath(dirname(__DIR__)) ?: '/var/www/fulgurite';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Installation — Fulgurite <?= htmlspecialchars($appVersion) ?></title>
<style<?= cspNonceAttr() ?>>
:root {
    --bg:       #0d1117;
    --bg2:      #161b22;
    --bg3:      #21262d;
    --bg4:      #2d333b;
    --border:   #30363d;
    --text:     #e6edf3;
    --text2:    #8b949e;
    --text3:    #656d76;
    --accent:   #58a6ff;
    --accent2:  #1f6feb;
    --red:      #f85149;
    --green:    #3fb950;
    --yellow:   #d29922;
    --purple:   #bc8cff;
    --radius:   6px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    font-size: 14px;
    line-height: 1.5;
}

/* Layout */
.setup-layout {
    display: flex;
    min-height: 100vh;
}
.setup-sidebar {
    width: 260px;
    flex-shrink: 0;
    background: var(--bg2);
    border-right: 1px solid var(--border);
    padding: 32px 0;
    display: flex;
    flex-direction: column;
}
.setup-main {
    flex: 1;
    padding: 40px;
    max-width: 720px;
    margin: 0 auto;
}

/* Logo sidebar */
.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 24px 28px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 16px;
}
.logo-icon {
    width: 36px; height: 36px;
    background: var(--accent2);
    border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 700; color: #fff;
}
.logo-text { font-size: 16px; font-weight: 600; }
.logo-ver  { font-size: 11px; color: var(--text3); }

/* Steps nav */
.steps-nav { padding: 0 16px; }
.step-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 12px;
    border-radius: var(--radius);
    margin-bottom: 2px;
    cursor: default;
    transition: background .15s;
}
.step-item.active  { background: var(--bg3); }
.step-item.done    { opacity: .7; }
.step-num {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: var(--bg4);
    border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 600;
    flex-shrink: 0;
    color: var(--text2);
}
.step-item.active .step-num {
    background: var(--accent2);
    border-color: var(--accent);
    color: #fff;
}
.step-item.done .step-num {
    background: var(--green);
    border-color: var(--green);
    color: #fff;
    font-size: 12px;
}
.step-label { font-size: 13px; color: var(--text2); }
.step-item.active .step-label { color: var(--text); font-weight: 500; }

/* Main content */
.step-panel { display: none; }
.step-panel.active { display: block; }
.step-header { margin-bottom: 28px; }
.step-title { font-size: 22px; font-weight: 600; margin-bottom: 6px; }
.step-desc  { color: var(--text2); font-size: 14px; }

/* Cards */
.card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
}
.card-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--text2);
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 14px;
}

/* Forms */
.form-group { margin-bottom: 16px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-row-3 { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; }
.form-label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: var(--text2);
    margin-bottom: 6px;
}
.form-label span { color: var(--text3); font-weight: 400; }
.form-control {
    width: 100%;
    padding: 8px 12px;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-size: 13px;
    font-family: inherit;
    transition: border-color .15s;
    appearance: none;
}
.form-control:focus { outline: none; border-color: var(--accent); }
.form-hint { font-size: 12px; color: var(--text3); margin-top: 5px; }

/* Radio / driver selector */
.driver-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
.driver-option input { display: none; }
.driver-card {
    padding: 14px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    text-align: center;
    background: var(--bg3);
    transition: border-color .15s, background .15s;
}
.driver-card:hover { border-color: var(--accent2); }
.driver-option input:checked + .driver-card {
    border-color: var(--accent);
    background: rgba(88,166,255,.08);
}
.driver-icon { font-size: 22px; margin-bottom: 6px; }
.driver-name { font-size: 12px; font-weight: 600; }
.driver-sub  { font-size: 11px; color: var(--text3); margin-top: 2px; }

/* Connection fields */
.db-fields { display: none; }
.db-fields.visible { display: block; }

/* Alerts */
.alert {
    padding: 10px 14px;
    border-radius: var(--radius);
    font-size: 13px;
    margin-bottom: 14px;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.alert-danger  { background: rgba(248,81,73,.1);  border: 1px solid rgba(248,81,73,.3);  color: var(--red); }
.alert-success { background: rgba(63,185,80,.1);  border: 1px solid rgba(63,185,80,.3);  color: var(--green); }
.alert-info    { background: rgba(88,166,255,.1); border: 1px solid rgba(88,166,255,.3); color: var(--accent); }
.alert-warning { background: rgba(210,153,34,.1); border: 1px solid rgba(210,153,34,.3); color: var(--yellow); }

/* Checklist */
.check-list { list-style: none; }
.check-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
}
.check-item:last-child { border-bottom: none; }
.check-badge {
    width: 20px; height: 20px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px;
    flex-shrink: 0;
}
.badge-ok      { background: rgba(63,185,80,.2);  color: var(--green); }
.badge-fail    { background: rgba(248,81,73,.2);  color: var(--red); }
.badge-warn    { background: rgba(210,153,34,.2); color: var(--yellow); }
.check-label   { flex: 1; }
.check-value   { color: var(--text2); font-size: 12px; }

/* Webserver */
.ws-tabs { display: flex; gap: 6px; margin-bottom: 16px; }
.ws-tab {
    padding: 7px 16px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    background: var(--bg3);
    color: var(--text2);
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all .15s;
}
.ws-tab.active {
    background: var(--accent2);
    border-color: var(--accent);
    color: #fff;
}
.code-block {
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 14px;
    font-family: 'JetBrains Mono', 'Cascadia Code', 'Fira Code', monospace;
    font-size: 12px;
    line-height: 1.6;
    overflow-x: auto;
    white-space: pre;
    color: var(--text2);
    max-height: 360px;
    overflow-y: auto;
}
.copy-btn {
    margin-top: 8px;
    padding: 6px 14px;
    background: var(--bg4);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text2);
    font-size: 12px;
    cursor: pointer;
    transition: all .15s;
}
.copy-btn:hover { border-color: var(--accent); color: var(--accent); }
.command-group + .command-group { margin-top: 18px; }
.command-title { font-size: 13px; font-weight: 600; margin-bottom: 6px; }
.command-desc {
    font-size: 12px;
    color: var(--text2);
    margin-bottom: 8px;
    white-space: pre-line;
}

/* Password strength */
.pw-strength { margin-top: 6px; }
.pw-bar { height: 3px; background: var(--bg4); border-radius: 99px; overflow: hidden; margin-bottom: 4px; }
.pw-fill { height: 100%; border-radius: 99px; transition: width .3s, background .3s; width: 0; }
.pw-text { font-size: 11px; color: var(--text3); }

/* Buttons */
.btn-row { display: flex; gap: 10px; margin-top: 24px; }
.btn {
    padding: 10px 22px;
    border-radius: var(--radius);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all .15s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-primary {
    background: var(--accent2);
    color: #fff;
}
.btn-primary:hover { background: var(--accent); }
.btn-primary:disabled { opacity: .5; cursor: not-allowed; }
.btn-secondary {
    background: var(--bg3);
    border: 1px solid var(--border);
    color: var(--text2);
}
.btn-secondary:hover { border-color: var(--accent); color: var(--text); }
.btn-ghost {
    background: transparent;
    color: var(--text2);
    padding-left: 0;
}
.btn-ghost:hover { color: var(--text); }

/* Progress step final */
.progress-steps { margin: 20px 0; }
.prog-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
}
.prog-item:last-child { border-bottom: none; }
.prog-spinner {
    width: 20px; height: 20px;
    border: 2px solid var(--border);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin .7s linear infinite;
    flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Finish screaten */
.finish-icon {
    width: 64px; height: 64px;
    background: rgba(63,185,80,.15);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 30px;
    margin: 0 auto 20px;
}

/* Loader overlay */
.loader-overlay {
    position: fixed;
    inset: 0;
    background: rgba(13,17,23,.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    display: none;
}
.loader-box {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 28px 32px;
    text-align: center;
}
.loader-spinner {
    width: 36px; height: 36px;
    border: 3px solid var(--border);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 0 auto 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .setup-layout { flex-direction: column; }
    .setup-sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--border); padding: 20px 0; }
    .steps-nav { display: flex; overflow-x: auto; padding: 0 16px; gap: 4px; white-space: nowrap; }
    .step-item { flex-shrink: 0; margin-bottom: 0; }
    .setup-main { padding: 24px 16px; }
    .driver-grid { grid-template-columns: 1fr 1fr; }
    .form-row, .form-row-3 { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .driver-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="loader-overlay" id="loader">
    <div class="loader-box">
        <div class="loader-spinner"></div>
        <div id="loader-text">Traitement en cours...</div>
    </div>
</div>

<div class="setup-layout">

    <!-- Sidebar navigation -->
    <aside class="setup-sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">R</div>
            <div>
                <div class="logo-text">Fulgurite</div>
                <div class="logo-ver">v<?= htmlspecialchars($appVersion) ?> — Installation</div>
            </div>
        </div>

        <nav class="steps-nav" id="steps-nav">
            <div class="step-item active" data-step="1">
                <div class="step-num" id="sn-1">1</div>
                <div class="step-label">Prérequis</div>
            </div>
            <div class="step-item" data-step="2">
                <div class="step-num" id="sn-2">2</div>
                <div class="step-label">Base de données</div>
            </div>
            <div class="step-item" data-step="3">
                <div class="step-num" id="sn-3">3</div>
                <div class="step-label">Serveur web</div>
            </div>
            <div class="step-item" data-step="4">
                <div class="step-num" id="sn-4">4</div>
                <div class="step-label">Administrateur</div>
            </div>
            <div class="step-item" data-step="5">
                <div class="step-num" id="sn-5">5</div>
                <div class="step-label">Application</div>
            </div>
            <div class="step-item" data-step="6">
                <div class="step-num" id="sn-6">6</div>
                <div class="step-label">Installation</div>
            </div>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="setup-main">

        <!-- ═══ Step 1: Prerequisites ══════════════════════════════════════════ -->
        <div class="step-panel active" id="panel-1">
            <div class="step-header">
                <div class="step-title">Vérification des prérequis</div>
                <div class="step-desc">Contrôle de votre environnement PHP et des permissions du système de fichiers.</div>
            </div>

            <div class="card">
                <div class="card-title">Résultats</div>
                <div id="prereq-loading" class="alert alert-info">Vérification en cours...</div>
                <ul class="check-list" id="prereq-list" style="display:none"></ul>
            </div>

            <div id="prereq-alert" style="display:none"></div>

            <div class="card" id="prereq-commands-card" style="display:none">
                <div class="card-title">Commandes recommandees</div>
                <div id="prereq-commands-list"></div>
            </div>

            <div class="card" id="prereq-rsync-card" style="display:none">
                <div class="card-title">Restore et rsync</div>
                <div id="prereq-rsync-body"></div>
            </div>

            <div class="btn-row">
                <button class="btn btn-primary" id="btn-prereq-next" disabled onclick="goToStep(2)">
                    Continuer →
                </button>
                <button class="btn btn-secondary" onclick="runPrereqCheck()">
                    Vérifier à nouveau
                </button>
            </div>
        </div>

        <!-- ═══ Step 2: Database ════════════════════════════════════ -->
        <div class="step-panel" id="panel-2">
            <div class="step-header">
                <div class="step-title">Moteur de base de données</div>
                <div class="step-desc">Choisissez et configurez votre moteur de stockage. SQLite ne nécessite aucun serveur.</div>
            </div>

            <div class="card">
                <div class="card-title">Pilote</div>
                <div class="driver-grid">
                    <label class="driver-option">
                        <input type="radio" name="db_driver" value="sqlite" checked onchange="onDriverChange(this.value)">
                        <div class="driver-card">
                            <div class="driver-icon">🗄️</div>
                            <div class="driver-name">SQLite</div>
                            <div class="driver-sub">Fichier local</div>
                        </div>
                    </label>
                    <label class="driver-option">
                        <input type="radio" name="db_driver" value="mysql" onchange="onDriverChange(this.value)">
                        <div class="driver-card">
                            <div class="driver-icon">🐬</div>
                            <div class="driver-name">MySQL / MariaDB</div>
                            <div class="driver-sub">Serveur distant</div>
                        </div>
                    </label>
                    <label class="driver-option">
                        <input type="radio" name="db_driver" value="pgsql" onchange="onDriverChange(this.value)">
                        <div class="driver-card">
                            <div class="driver-icon">🐘</div>
                            <div class="driver-name">PostgreSQL</div>
                            <div class="driver-sub">Serveur distant</div>
                        </div>
                    </label>
                </div>

                <!-- SQLite fields (info) -->
                <div class="db-fields visible" id="db-fields-sqlite">
                    <div class="alert alert-info">
                        ℹ️ SQLite utilise un fichier local. Aucune configuration supplémentaire n'est requise.
                        Le fichier sera créé dans <code>data/fulgurite.db</code>.
                    </div>
                </div>

                <!-- MySQL/PostgreSQL fields -->
                <div class="db-fields" id="db-fields-remote">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label class="form-label">Hôte</label>
                            <input type="text" class="form-control" id="db_host" value="localhost" placeholder="localhost">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Port</label>
                            <input type="number" class="form-control" id="db_port" value="3306" min="1" max="65535">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nom de la base</label>
                            <input type="text" class="form-control" id="db_name" value="fulgurite" placeholder="fulgurite">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Utilisateur</label>
                            <input type="text" class="form-control" id="db_user" placeholder="fulgurite">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="db_pass" placeholder="Mot de passe">
                    </div>
                </div>
            </div>

            <div id="db-test-result" style="display:none"></div>

            <div class="btn-row">
                <button class="btn btn-primary" id="btn-db-test" onclick="testDatabase()">
                    Tester la connexion
                </button>
                <button class="btn btn-ghost" onclick="goToStep(1)">← Retour</button>
            </div>
        </div>

        <!-- ═══ Step 3: Web server ═════════════════════════════════════════ -->
        <div class="step-panel" id="panel-3">
            <div class="step-header">
                <div class="step-title">Configuration du serveur web</div>
                <div class="step-desc">Serveur détecté automatiquement. Copiez la configuration générée dans votre serveur web.</div>
            </div>

            <div class="card" id="ws-detect-card">
                <div class="card-title">Serveur détecté</div>
                <div id="ws-detect-info">
                    <div class="alert alert-info">Détection en cours...</div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">Configuration à appliquer</div>

                <div class="form-row" style="margin-bottom:14px">
                    <div class="form-group">
                        <label class="form-label">Nom de domaine / ServerName</label>
                        <input type="text" class="form-control" id="ws_server_name" placeholder="fulgurite.exemple.com"
                               oninput="refreshWsConfig()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Répertoire racine du projet</label>
                        <input type="text" class="form-control" id="ws_doc_root"
                               value="<?= htmlspecialchars($docRoot) ?>" oninput="refreshWsConfig()">
                    </div>
                </div>

                <div class="form-row" style="margin-bottom:14px">
                    <div class="form-group">
                        <label class="form-label">Utilisateur PHP-FPM / web</label>
                        <input type="text" class="form-control" id="ws_web_user"
                               value="www-data" placeholder="www-data" oninput="syncWebGroupDefault(); refreshWsConfig()">
                        <div class="form-hint">Par defaut, l'utilisateur detecte par PHP. Pour une meilleure isolation, utilisez un utilisateur dedie comme fulgurite-web.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Groupe autorise sur le socket broker</label>
                        <input type="text" class="form-control" id="ws_web_group"
                               value="www-data" placeholder="www-data" oninput="refreshWsConfig()">
                        <div class="form-hint">Le socket /run/fulgurite/secrets.sock sera accessible a ce groupe, pas au stockage brut des secrets.</div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Socket PHP-FPM</label>
                    <input type="text" class="form-control" id="ws_php_fpm_socket"
                           value="/run/php/php<?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?>-fpm.sock"
                           placeholder="/run/php/php8.2-fpm.sock" oninput="refreshWsConfig()">
                    <div class="form-hint">Utilise dans la configuration Nginx. Si vous creez un pool dedie Fulgurite, indiquez son socket ici.</div>
                </div>

                <div class="ws-tabs">
                    <button class="ws-tab active" id="tab-apache" onclick="switchWsTab('apache')">Apache / LiteSpeed</button>
                    <button class="ws-tab" id="tab-nginx" onclick="switchWsTab('nginx')">Nginx</button>
                </div>

                <div id="ws-config-block">
                    <pre class="code-block" id="ws-config-content">Chargement...</pre>
                    <button class="copy-btn" onclick="copyWsConfig()">Copier la configuration</button>
                </div>

                <div style="margin-top:16px" id="ws-apache-note">
                    <div class="alert alert-success">
                        ✅ Le fichier <code>public/.htaccess</code> est déjà présent et configuré pour Apache.
                        Il vous suffit d'activer <code>mod_rewrite</code> et <code>AllowOverride All</code>.
                    </div>
                </div>
                <div style="margin-top:16px;display:none" id="ws-nginx-note">
                    <div class="alert alert-warning">
                        ⚠️ Nginx ne lit pas les fichiers <code>.htaccess</code>.
                        Copiez la configuration ci-dessus dans <code>/etc/nginx/sites-available/fulgurite</code>
                        et activez-la avec <code>ln -s</code>.
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <button class="btn btn-primary" onclick="goToStep(4)">Continuer →</button>
                <button class="btn btn-ghost" onclick="goToStep(2)">← Retour</button>
            </div>
        </div>

        <!-- ═══ Step 4: Admin account ══════════════════════════════ -->
        <div class="step-panel" id="panel-4">
            <div class="step-header">
                <div class="step-title">Compte administrateur</div>
                <div class="step-desc"><?= htmlspecialchars(t('setup.ui.admin_step_desc'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="card">
                <div class="card-title">Identifiants</div>
                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" class="form-control" id="admin_username"
                           placeholder="admin" autocomplete="off"
                           oninput="validateAdminForm()">
                    <div class="form-hint">Lettres, chiffres, _, - et . uniquement</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="admin_password"
                               autocomplete="new-password" oninput="updatePasswordStrength(); validateAdminForm()">
                        <div class="pw-strength">
                            <div class="pw-bar"><div class="pw-fill" id="pw-fill"></div></div>
                            <div class="pw-text" id="pw-text"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="admin_password2"
                               autocomplete="new-password" oninput="validateAdminForm()">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">Informations <span>(optionnel)</span></div>
                <div class="form-group">
                    <label class="form-label">Adresse email <span>(optionnel)</span></label>
                    <input type="email" class="form-control" id="admin_email" placeholder="admin@exemple.com">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Prénom <span>(optionnel)</span></label>
                        <input type="text" class="form-control" id="admin_first_name" placeholder="Jean">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom <span>(optionnel)</span></label>
                        <input type="text" class="form-control" id="admin_last_name" placeholder="Dupont">
                    </div>
                </div>
            </div>

            <div id="admin-error" style="display:none"></div>

            <div class="btn-row">
                <button class="btn btn-primary" id="btn-admin-next" disabled onclick="goToStep(5)">
                    Continuer →
                </button>
                <button class="btn btn-ghost" onclick="goToStep(3)">← Retour</button>
            </div>
        </div>

        <!-- ═══ Step 5: Application configuration ══════════════════════ -->
        <div class="step-panel" id="panel-5">
            <div class="step-header">
                <div class="step-title">Configuration de l'application</div>
                <div class="step-desc">Paramètres généraux de votre instance Fulgurite.</div>
            </div>

            <div class="card">
                <div class="card-title">Identité</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nom de l'application</label>
                        <input type="text" class="form-control" id="app_name" value="Fulgurite" placeholder="Fulgurite">
                        <div class="form-hint"><?= htmlspecialchars(t('setup.ui.app_name_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fuseau horaire</label>
                        <select class="form-control" id="app_timezone">
                            <?php foreach ($timezones as $tz): ?>
                            <option value="<?= htmlspecialchars($tz) ?>"
                                <?= ($tz === 'Europe/Paris' || $tz === 'UTC') && $tz === 'Europe/Paris' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tz) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <button class="btn btn-primary" onclick="goToStep(6)">Continuer →</button>
                <button class="btn btn-ghost" onclick="goToStep(4)">← Retour</button>
            </div>
        </div>

        <!-- ═══ Step 6: Installation ════════════════════════════════════════ -->
        <div class="step-panel" id="panel-6">
            <div class="step-header">
                <div class="step-title">Installation</div>
                <div class="step-desc"><?= htmlspecialchars(t('setup.ui.install_step_desc'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <!-- Summary -->
            <div class="card" id="recap-card">
                <div class="card-title">Récapitulatif</div>
                <div class="table-wrap">
                <table style="width:100%;font-size:13px;border-collapse:collapse" id="recap-table">
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:7px 0;color:var(--text2);width:180px">Base de données</td>
                        <td id="recap-db">—</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:7px 0;color:var(--text2)">Administrateur</td>
                        <td id="recap-admin">—</td>
                    </tr>
                    <tr>
                        <td style="padding:7px 0;color:var(--text2)">Application</td>
                        <td id="recap-app">—</td>
                    </tr>
                </table>
                </div>
            </div>

            <!-- Progress (hidden until launch) -->
            <div class="card" id="install-progress" style="display:none">
                <div class="card-title">Progression</div>
                <div class="progress-steps" id="progress-steps"></div>
            </div>

            <!-- Result -->
            <div id="install-result" style="display:none"></div>

            <!-- successs -->
            <div id="install-success" style="display:none">
                <div style="text-align:center;padding:20px 0 24px">
                    <div class="finish-icon">✅</div>
                    <div style="font-size:20px;font-weight:600;margin-bottom:8px">Installation réussie !</div>
                    <div style="color:var(--text2)">Fulgurite est prêt. Configurez le worker ci-dessous, puis connectez-vous.</div>
                </div>

                <!-- Worker post-install ──────────────────────────────────── -->
                <div class="card" style="margin-bottom:20px">
                    <div class="card-title">Configurer le worker de tâches</div>
                    <p style="font-size:13px;color:var(--text2);margin-bottom:16px">
                        Le worker exécute les backups et jobs planifiés en arrière-plan. Sans lui, les tâches ne se déclencheront pas automatiquement.
                    </p>

                    <!-- Onglets mode -->
                    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
                        <button class="btn btn-sm" id="setup-worker-tab-cron" onclick="setupWorkerTab('cron')" style="border-color:var(--accent);color:var(--accent)">Mode Cron</button>
                        <button class="btn btn-sm" id="setup-worker-tab-systemd" onclick="setupWorkerTab('systemd')">Mode Systemd</button>
                        <button class="btn btn-sm" id="setup-worker-tab-skip" onclick="setupWorkerTab('skip')">Configurer plus tard</button>
                    </div>

                    <!-- panel Cron -->
                    <div id="setup-worker-panel-cron">
                        <div style="font-size:13px;color:var(--text2);margin-bottom:12px">
                            Lance le worker une fois par minute via <code>crontab</code> de l'utilisateur web.<br>
                            <strong>Aucune permission root requise.</strong> Solution recommandée pour commencer.
                        </div>
                        <button class="btn btn-primary btn-sm" id="btn-setup-install-cron" onclick="setupInstallCron()">Installer le cron automatiquement</button>
                        <div id="setup-cron-result" style="margin-top:12px;font-size:13px"></div>
                    </div>

                    <!-- panel Systemd -->
                    <div id="setup-worker-panel-systemd" style="display:none">
                        <div style="font-size:13px;color:var(--text2);margin-bottom:12px">
                            Daemon permanent géré par systemd — redémarre au boot et en cas de crash.
                        </div>

                        <!-- Auto-install -->
                        <div id="setup-systemd-auto-section" style="margin-bottom:16px">
                            <div style="font-size:13px;font-weight:500;margin-bottom:6px">Installation automatique</div>
                            <div id="setup-auto-install-status" style="font-size:12px;color:var(--text2);margin-bottom:8px">Vérification en cours...</div>
                            <button class="btn btn-primary btn-sm" id="btn-setup-auto-systemd" onclick="setupAutoInstallSystemd()" style="display:none">Auto-installer systemd</button>
                        </div>

                        <hr style="border:none;border-top:1px solid var(--border);margin:12px 0">

                        <!-- Guided -->
                        <div>
                            <div style="font-size:13px;font-weight:500;margin-bottom:6px">Installation manuelle</div>
                            <button class="btn btn-sm" onclick="setupShowSystemdUnit()">Afficher le fichier service</button>
                            <div id="setup-systemd-unit-section" style="display:none;margin-top:12px">
                                <div style="font-size:12px;color:var(--text2);margin-bottom:4px">Contenu du fichier <code id="setup-systemd-service-file"></code> :</div>
                                <pre id="setup-systemd-unit-content" style="background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:12px;font-size:11px;overflow:auto;max-height:200px;white-space:pre;user-select:text"></pre>
                                <div style="font-size:12px;color:var(--text2);margin:10px 0 4px">Commandes d'installation (en root) :</div>
                                <pre id="setup-systemd-instructions" style="background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:12px;font-size:11px;overflow:auto;max-height:180px;white-space:pre;user-select:text"></pre>
                            </div>
                        </div>
                        <div id="setup-systemd-result" style="margin-top:12px;font-size:13px"></div>
                    </div>

                    <!-- panel Skip -->
                    <div id="setup-worker-panel-skip" style="display:none">
                        <div style="font-size:13px;color:var(--text2)">
                            Vous pourrez configurer le worker plus tard depuis <strong>Performance → Worker dédié</strong> dans l'interface.
                        </div>
                    </div>
                </div>

                <div style="text-align:center">
                    <a href="/" class="btn btn-primary" style="display:inline-flex;text-decoration:none">
                        Accéder à l'application →
                    </a>
                </div>
            </div>

            <div class="btn-row" id="install-btn-row">
                <button class="btn btn-primary" onclick="launchInstall()" id="btn-install">
                    🚀 Lancer l'installation
                </button>
                <button class="btn btn-ghost" onclick="goToStep(5)">← Retour</button>
            </div>
        </div>

    </main>
</div>

<script<?= cspNonceAttr() ?>>
// ── State global ─────────────────────────────────────────────────────────────
const state = {
    currentStep: 1,
    prereqOk: false,
    dbOk: false,
    wsType: 'apache',
    dbDriver: 'sqlite',
    setupWorkerToken: '',
};
const i18n = <?= json_encode([
    'copy_command' => t('setup.js.copy_command'),
    'copied' => t('setup.js.copied'),
    'command' => t('setup.js.command'),
    'rsync_not_installed' => t('setup.js.rsync_not_installed'),
    'rsync_prompt_install' => t('setup.js.rsync_prompt_install'),
    'rsync_install_now' => t('setup.js.rsync_install_now'),
    'rsync_show_install_command' => t('setup.js.rsync_show_install_command'),
    'continue_without_rsync' => t('setup.js.continue_without_rsync'),
    'sudo_password' => t('setup.js.sudo_password'),
    'sudo_password_once' => t('setup.js.sudo_password_once'),
    'install_with_password' => t('setup.js.install_with_password'),
    'manual_command_hint' => t('setup.js.manual_command_hint'),
    'auto_install_unavailable' => t('setup.js.auto_install_unavailable'),
    'continue_setup_without_rsync' => t('setup.js.continue_setup_without_rsync'),
    'sudo_password_required' => t('setup.js.sudo_password_required'),
    'install_in_progress' => t('setup.js.install_in_progress'),
    'installing_rsync' => t('setup.js.installing_rsync'),
    'rsync_install_failed' => t('setup.js.rsync_install_failed'),
    'error_prefix' => t('setup.js.error_prefix'),
    'prereq_rsync_missing' => t('setup.js.prereq_rsync_missing'),
    'prereq_ok' => t('setup.js.prereq_ok'),
    'prereq_failed' => t('setup.js.prereq_failed'),
    'network_error' => t('setup.js.network_error'),
    'db_test_running' => t('setup.js.db_test_running'),
    'db_test_button' => t('setup.js.db_test_button'),
    'webserver_apache_detected' => t('setup.js.webserver_apache_detected'),
    'webserver_nginx_detected' => t('setup.js.webserver_nginx_detected'),
    'webserver_unknown' => t('setup.js.webserver_unknown'),
    'webserver_detect_failed' => t('setup.js.webserver_detect_failed'),
    'copy_config_button' => t('setup.js.copy_config_button'),
    'copy_config_done' => t('setup.js.copy_config_done'),
    'password_strength_very_weak' => t('setup.js.password_strength_very_weak'),
    'password_strength_weak' => t('setup.js.password_strength_weak'),
    'password_strength_medium' => t('setup.js.password_strength_medium'),
    'password_strength_strong' => t('setup.js.password_strength_strong'),
    'password_strength_very_strong' => t('setup.js.password_strength_very_strong'),
    'error_username_too_short' => t('setup.js.error_username_too_short'),
    'error_username_invalid' => t('setup.js.error_username_invalid'),
    'error_password_too_short' => t('setup.js.error_password_too_short'),
    'error_password_mismatch' => t('setup.js.error_password_mismatch'),
    'install_step_dirs' => t('setup.js.install_step_dirs'),
    'install_step_config' => t('setup.js.install_step_config'),
    'install_step_db' => t('setup.js.install_step_db'),
    'install_step_admin' => t('setup.js.install_step_admin'),
    'install_step_app' => t('setup.js.install_step_app'),
    'install_step_finalize' => t('setup.js.install_step_finalize'),
    'worker_token_missing' => t('setup.js.worker_token_missing'),
    'worker_cron_installing' => t('setup.js.worker_cron_installing'),
    'worker_cron_success' => t('setup.js.worker_cron_success'),
    'unknown_error' => t('setup.js.unknown_error'),
    'worker_auto_install_unavailable' => t('setup.js.worker_auto_install_unavailable'),
    'worker_check_failed' => t('setup.js.worker_check_failed'),
    'worker_systemd_installing' => t('setup.js.worker_systemd_installing'),
    'worker_systemd_success' => t('setup.js.worker_systemd_success'),
    'generation_error' => t('setup.js.generation_error'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

// ── Navigation ──────────────────────────────────────────────────────────────
function goToStep(n) {
    // Hide current panel
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.step-item').forEach(item => {
        const s = parseInt(item.dataset.step);
        item.classList.remove('active');
        if (s < n) item.classList.add('done');
        else item.classList.remove('done');
    });

    // Activate new panel
    document.getElementById('panel-' + n).classList.add('active');
    document.querySelector('[data-step="' + n + '"]').classList.add('active');

    // Mark previous steps as done (number → ✓)
    for (let i = 1; i < n; i++) {
        const numEl = document.getElementById('sn-' + i);
        if (numEl) numEl.textContent = '✓';
    }

    state.currentStep = n;

    // Actions on each step load
    if (n === 1) runPrereqCheck();
    if (n === 3) loadWebServerStep();
    if (n === 6) buildRecap();
}

// ── Step 1 : Prerequisites ─────────────────────────────────────────────────────
function copyText(text, button, idleLabel = i18n.copy_command || 'Copy command') {
    navigator.clipboard.writeText(text).then(() => {
        if (!button) return;
        button.textContent = i18n.copied || 'Copied!';
        setTimeout(() => { button.textContent = idleLabel; }, 2000);
    });
}

function renderPrereqCommands(commands) {
    const card = document.getElementById('prereq-commands-card');
    const list = document.getElementById('prereq-commands-list');
    if (!card || !list) return;

    list.innerHTML = '';
    if (!Array.isArray(commands) || !commands.length) {
        card.style.display = 'none';
        return;
    }

    commands.forEach((group) => {
        const wrap = document.createElement('div');
        wrap.className = 'command-group';

        const title = document.createElement('div');
        title.className = 'command-title';
        title.textContent = group.title || (i18n.command || 'Command');
        wrap.appendChild(title);

        if (group.description) {
            const desc = document.createElement('div');
            desc.className = 'command-desc';
            desc.textContent = group.description;
            wrap.appendChild(desc);
        }

        const code = document.createElement('div');
        code.className = 'code-block';
        code.textContent = group.command || '';
        wrap.appendChild(code);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'copy-btn';
        button.textContent = i18n.copy_command || 'Copy command';
        button.addEventListener('click', () => copyText(code.textContent, button));
        wrap.appendChild(button);

        list.appendChild(wrap);
    });

    card.style.display = 'block';
}

function setRsyncFeedback(kind, message) {
    const box = document.getElementById('prereq-rsync-feedback');
    if (!box) return;

    let alertClass = 'alert-warning';
    if (kind === 'success') alertClass = 'alert-success';
    else if (kind === 'danger') alertClass = 'alert-danger';
    else if (kind === 'info') alertClass = 'alert-info';

    box.innerHTML = `<div class="alert ${alertClass}" style="margin-top:14px">${escHtml(message)}</div>`;
}

function showRsyncCommand(command) {
    const wrap = document.getElementById('prereq-rsync-command-wrap');
    const code = document.getElementById('prereq-rsync-command');
    if (!wrap || !code || !command) return;
    code.textContent = command;
    wrap.style.display = 'block';
}

function renderRsyncSetup(info) {
    const card = document.getElementById('prereq-rsync-card');
    const body = document.getElementById('prereq-rsync-body');
    if (!card || !body) return;

    if (!info || info.installed) {
        card.style.display = 'none';
        body.innerHTML = '';
        return;
    }

    const canAutoInstall = !!info.can_auto_install;
    const installCommand = info.install_command || '';
    const platformLabel = info.platform_label ? ` sur ${escHtml(info.platform_label)}` : '';

    body.innerHTML = `
        <div class="alert alert-warning">⚠️ ${escHtml(i18n.rsync_not_installed || 'rsync is not installed.')}${info.restore_warning ? ' ' + escHtml(info.restore_warning) : ''}</div>
        <p style="color:var(--text2);font-size:13px;margin:0 0 14px;line-height:1.5">
            ${escHtml((i18n.rsync_prompt_install || 'Do you want Fulgurite to try installing rsync:platform now?').replace(':platform', platformLabel))}
        </p>
        <div class="btn-row" style="justify-content:flex-start">
            <button class="btn btn-primary" type="button" id="btn-rsync-install">${canAutoInstall ? (i18n.rsync_install_now || 'Install rsync now') : (i18n.rsync_show_install_command || 'Show install command')}</button>
            <button class="btn btn-secondary" type="button" id="btn-rsync-skip">${i18n.continue_without_rsync || 'Continue without rsync'}</button>
        </div>
        <div id="prereq-rsync-sudo" style="display:none;margin-top:16px">
            <div class="form-group" style="margin-bottom:10px">
                <label class="form-label" for="rsync-sudo-password">${i18n.sudo_password || 'sudo password'}</label>
                <input type="password" class="form-control" id="rsync-sudo-password" autocomplete="off" placeholder="••••••••">
                <div class="form-hint">${i18n.sudo_password_once || 'The password is used once for this install.'}</div>
            </div>
            <button class="btn btn-primary" type="button" id="btn-rsync-install-with-password">${i18n.install_with_password || 'Install with this password'}</button>
        </div>
        <div id="prereq-rsync-command-wrap" style="display:none;margin-top:16px">
            <div class="form-hint" style="margin-bottom:8px">${i18n.manual_command_hint || 'Command to run manually on server:'}</div>
            <div class="code-block" id="prereq-rsync-command"></div>
            <button class="copy-btn" type="button" id="btn-rsync-copy-command" style="margin-top:10px">${i18n.copy_command || 'Copy command'}</button>
        </div>
        <div id="prereq-rsync-feedback"></div>
    `;

    const copyBtn = document.getElementById('btn-rsync-copy-command');
    const commandEl = document.getElementById('prereq-rsync-command');
    if (copyBtn && commandEl) {
        copyBtn.addEventListener('click', () => copyText(commandEl.textContent, copyBtn));
    }

    const installBtn = document.getElementById('btn-rsync-install');
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (!canAutoInstall) {
                showRsyncCommand(installCommand);
                setRsyncFeedback('warning', i18n.auto_install_unavailable || 'Automatic install is unavailable on this server. Without rsync, restore will not work.');
                return;
            }
            await installRsync(false);
        });
    }

    const skipBtn = document.getElementById('btn-rsync-skip');
    if (skipBtn) {
        skipBtn.addEventListener('click', () => {
            if (installCommand) {
                showRsyncCommand(installCommand);
            }
            setRsyncFeedback('warning', i18n.continue_setup_without_rsync || 'You can continue setup, but restore will not work until rsync is installed.');
        });
    }

    const passwordBtn = document.getElementById('btn-rsync-install-with-password');
    if (passwordBtn) {
        passwordBtn.addEventListener('click', async () => {
            await installRsync(true);
        });
    }

    card.style.display = 'block';
}

async function installRsync(withPassword) {
    const installBtn = document.getElementById('btn-rsync-install');
    const passwordBtn = document.getElementById('btn-rsync-install-with-password');
    const passwordWrap = document.getElementById('prereq-rsync-sudo');
    const passwordInput = document.getElementById('rsync-sudo-password');
    const sudoPassword = withPassword && passwordInput ? passwordInput.value : '';

    if (withPassword && !sudoPassword) {
        setRsyncFeedback('danger', i18n.sudo_password_required || 'sudo password is required to install rsync.');
        return;
    }

    if (installBtn) {
        installBtn.disabled = true;
        installBtn.textContent = i18n.install_in_progress || 'Installing...';
    }
    if (passwordBtn) {
        passwordBtn.disabled = true;
        passwordBtn.textContent = i18n.install_in_progress || 'Installing...';
    }
    setRsyncFeedback('info', i18n.installing_rsync || 'Installing rsync on server...');

    try {
        const params = {};
        if (sudoPassword) {
            params.sudo_password = sudoPassword;
        }

        const res = await apiCall('install_rsync', params);
        if (passwordInput) {
            passwordInput.value = '';
        }

        if (res.ok && res.installed) {
            if (passwordWrap) {
                passwordWrap.style.display = 'none';
            }
            await runPrereqCheck();
            return;
        }

        if (res.manual_command) {
            showRsyncCommand(res.manual_command);
        }

        if (res.needs_sudo_password) {
            if (passwordWrap) {
                passwordWrap.style.display = 'block';
            }
            setRsyncFeedback('warning', res.message || (i18n.sudo_password_required || 'sudo password is required to install rsync.'));
            return;
        }

        setRsyncFeedback(res.requires_manual_install ? 'warning' : 'danger', res.message || (i18n.rsync_install_failed || 'rsync installation failed.'));
    } catch (e) {
        if (passwordInput) {
            passwordInput.value = '';
        }
        setRsyncFeedback('danger', (i18n.error_prefix || 'Error: ') + e.message);
    } finally {
        if (installBtn) {
            installBtn.disabled = false;
            installBtn.textContent = i18n.rsync_install_now || 'Install rsync now';
        }
        if (passwordBtn) {
            passwordBtn.disabled = false;
            passwordBtn.textContent = i18n.install_with_password || 'Install with this password';
        }
    }
}

async function runPrereqCheck() {
    document.getElementById('prereq-loading').style.display = 'flex';
    document.getElementById('prereq-list').style.display = 'none';
    document.getElementById('prereq-alert').style.display = 'none';
    document.getElementById('prereq-commands-card').style.display = 'none';
    document.getElementById('prereq-rsync-card').style.display = 'none';
    document.getElementById('btn-prereq-next').disabled = true;
    state.prereqOk = false;

    try {
        const res  = await apiCall('check_prerequisites');
        const list = document.getElementById('prereq-list');
        list.innerHTML = '';

        for (const check of res.checks) {
            const li   = document.createElement('li');
            li.className = 'check-item';

            let badgeClass = 'badge-ok', symbol = '✓';
            if (!check.ok && check.fatal) { badgeClass = 'badge-fail'; symbol = '✗'; }
            else if (!check.ok)           { badgeClass = 'badge-warn'; symbol = '!'; }

            li.innerHTML = `
                <span class="check-badge ${badgeClass}">${symbol}</span>
                <span class="check-label">${escHtml(check.label)}</span>
                <span class="check-value">${escHtml(check.value)}</span>
            `;
            list.appendChild(li);
        }

        document.getElementById('prereq-loading').style.display = 'none';
        list.style.display = 'block';
        renderPrereqCommands(res.commands || []);
        renderRsyncSetup(res.rsync || null);

        const alertEl = document.getElementById('prereq-alert');
        if (res.ok && res.rsync && !res.rsync.installed) {
            alertEl.innerHTML = `<div class="alert alert-warning">⚠️ ${escHtml(i18n.prereq_rsync_missing || 'Essential prerequisites are met, but rsync is missing. You can continue, but restore will not work until rsync is installed.')}</div>`;
            alertEl.style.display = 'block';
            document.getElementById('btn-prereq-next').disabled = false;
            state.prereqOk = true;
        } else if (res.ok) {
            alertEl.innerHTML = `<div class="alert alert-success">✅ ${escHtml(i18n.prereq_ok || 'All essential prerequisites are met.')}</div>`;
            alertEl.style.display = 'block';
            document.getElementById('btn-prereq-next').disabled = false;
            state.prereqOk = true;
        } else {
            alertEl.innerHTML = `<div class="alert alert-danger">❌ ${escHtml(i18n.prereq_failed || 'Some required prerequisites are not met. Fix them before continuing.')}</div>`;
            alertEl.style.display = 'block';
        }
    } catch (e) {
        document.getElementById('prereq-loading').textContent = (i18n.error_prefix || 'Error: ') + e.message;
    }
}

// ── Step 2: Database ────────────────────────────────────────────────
function onDriverChange(driver) {
    state.dbDriver = driver;
    state.dbOk = false;

    document.getElementById('db-fields-sqlite').classList.toggle('visible', driver === 'sqlite');
    document.getElementById('db-fields-remote').classList.toggle('visible', driver !== 'sqlite');

    if (driver === 'pgsql') {
        document.getElementById('db_port').value = '5432';
    } else if (driver === 'mysql') {
        document.getElementById('db_port').value = '3306';
    }

    document.getElementById('db-test-result').style.display = 'none';
}

async function testDatabase() {
    const btn = document.getElementById('btn-db-test');
    btn.disabled = true;
    btn.textContent = i18n.db_test_running || 'Testing...';

    const params = {
        action: 'test_database',
        driver: state.dbDriver,
        host:   document.getElementById('db_host')?.value || 'localhost',
        port:   document.getElementById('db_port')?.value || '3306',
        name:   document.getElementById('db_name')?.value || 'fulgurite',
        user:   document.getElementById('db_user')?.value || '',
        pass:   document.getElementById('db_pass')?.value || '',
    };

    try {
        const res     = await apiCall('test_database', params);
        const el      = document.getElementById('db-test-result');
        el.style.display = 'block';

        if (res.ok) {
            el.innerHTML = `<div class="alert alert-success">✅ ${escHtml(res.message)}</div>`;
            state.dbOk   = true;
            setTimeout(() => goToStep(3), 800);
        } else {
            el.innerHTML = `<div class="alert alert-danger">❌ ${escHtml(res.message)}</div>`;
            state.dbOk   = false;
        }
    } catch (e) {
        document.getElementById('db-test-result').innerHTML =
            `<div class="alert alert-danger">❌ ${escHtml(i18n.network_error || 'Network error:')} ${escHtml(e.message)}</div>`;
        document.getElementById('db-test-result').style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = i18n.db_test_button || 'Test connection';
}

// ── Step 3 : server web ────────────────────────────────────────────────────
async function loadWebServerStep() {
    try {
        const info = await apiCall('detect_webserver');
        const el   = document.getElementById('ws-detect-info');

        if (info.detected === 'apache') {
            el.innerHTML = `<div class="alert alert-success">✅ ${escHtml(i18n.webserver_apache_detected || 'Apache detected:')} <strong>${escHtml(info.version)}</strong></div>`;
            switchWsTab('apache');
        } else if (info.detected === 'nginx') {
            el.innerHTML = `<div class="alert alert-info">🔵 ${escHtml(i18n.webserver_nginx_detected || 'Nginx detected:')} <strong>${escHtml(info.version)}</strong></div>`;
            switchWsTab('nginx');
        } else {
            el.innerHTML = `<div class="alert alert-warning">⚠️ ${escHtml(i18n.webserver_unknown || 'Unidentified web server')} (${escHtml(info.version)}).</div>`;
        }
        if (info.web_user && document.getElementById('ws_web_user')) {
            document.getElementById('ws_web_user').value = info.web_user;
        }
        if (info.web_group && document.getElementById('ws_web_group')) {
            document.getElementById('ws_web_group').value = info.web_group;
            document.getElementById('ws_web_group').dataset.lastAuto = info.web_group;
        }
        if (info.php_fpm_socket && document.getElementById('ws_php_fpm_socket')) {
            document.getElementById('ws_php_fpm_socket').value = info.php_fpm_socket;
        }
    } catch (e) {
        document.getElementById('ws-detect-info').innerHTML =
            `<div class="alert alert-warning">⚠️ ${escHtml(i18n.webserver_detect_failed || 'Detection failed:')} ${escHtml(e.message)}</div>`;
    }
    refreshWsConfig();
}

function syncWebGroupDefault() {
    const userEl = document.getElementById('ws_web_user');
    const groupEl = document.getElementById('ws_web_group');
    if (!userEl || !groupEl) return;
    const previous = groupEl.dataset.lastAuto || 'www-data';
    if (!groupEl.value || groupEl.value === previous) {
        groupEl.value = userEl.value || 'www-data';
        groupEl.dataset.lastAuto = groupEl.value;
    }
}

function switchWsTab(type) {
    state.wsType = type;
    document.getElementById('tab-apache').classList.toggle('active', type === 'apache');
    document.getElementById('tab-nginx').classList.toggle('active', type === 'nginx');
    document.getElementById('ws-apache-note').style.display = type === 'apache' ? 'block' : 'none';
    document.getElementById('ws-nginx-note').style.display  = type === 'nginx'  ? 'block' : 'none';
    refreshWsConfig();
}

async function refreshWsConfig() {
    const serverName = document.getElementById('ws_server_name')?.value || '';
    const docRoot    = document.getElementById('ws_doc_root')?.value || '';
    const webUser    = document.getElementById('ws_web_user')?.value || '';
    const webGroup   = document.getElementById('ws_web_group')?.value || '';
    const phpFpmSocket = document.getElementById('ws_php_fpm_socket')?.value || '';

    try {
        const res = await apiCall('generate_webserver_config', {
            type: state.wsType,
            server_name: serverName,
            doc_root: docRoot,
            web_user: webUser,
            web_group: webGroup,
            php_fpm_socket: phpFpmSocket
        });
        if (res.ok) {
            document.getElementById('ws-config-content').textContent = res.config;
        }
    } catch (e) {}
}

function copyWsConfig() {
    const text = document.getElementById('ws-config-content').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.copy-btn');
        btn.textContent = `✅ ${i18n.copy_config_done || 'Copied!'}`;
        setTimeout(() => { btn.textContent = i18n.copy_config_button || 'Copy configuration'; }, 2000);
    });
}

// ── Step 4 : Admin ──────────────────────────────────────────────────────────
function updatePasswordStrength() {
    const pw   = document.getElementById('admin_password').value;
    const fill = document.getElementById('pw-fill');
    const text = document.getElementById('pw-text');

    let score = 0;
    if (pw.length >= 8)  score++;
    if (pw.length >= 12) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    const levels = [
        { pct: '0%',   bg: 'transparent', label: '' },
        { pct: '20%',  bg: '#f85149',     label: i18n.password_strength_very_weak || 'Very weak' },
        { pct: '40%',  bg: '#f85149',     label: i18n.password_strength_weak || 'Weak' },
        { pct: '60%',  bg: '#d29922',     label: i18n.password_strength_medium || 'Medium' },
        { pct: '80%',  bg: '#3fb950',     label: i18n.password_strength_strong || 'Strong' },
        { pct: '100%', bg: '#3fb950',     label: i18n.password_strength_very_strong || 'Very strong' },
    ];
    const lvl = levels[Math.min(score, 5)];
    fill.style.width = pw.length ? lvl.pct : '0%';
    fill.style.background = lvl.bg;
    text.textContent = pw.length ? lvl.label : '';
}

function validateAdminForm() {
    const username  = document.getElementById('admin_username').value.trim();
    const password  = document.getElementById('admin_password').value;
    const password2 = document.getElementById('admin_password2').value;
    const btn       = document.getElementById('btn-admin-next');
    const errEl     = document.getElementById('admin-error');

    let errors = [];
    if (username.length < 3) errors.push(i18n.error_username_too_short || "Username too short (min. 3 characters).");
    if (!/^[a-zA-Z0-9_\-\.]+$/.test(username) && username.length > 0) errors.push(i18n.error_username_invalid || "Invalid characters in username.");
    if (password.length < 8) errors.push(i18n.error_password_too_short || "Password too short (min. 8 characters).");
    if (password && password !== password2) errors.push(i18n.error_password_mismatch || "Passwords do not match.");

    if (errors.length) {
        errEl.innerHTML = '<div class="alert alert-danger">' + errors.map(e => escHtml(e)).join('<br>') + '</div>';
        errEl.style.display = 'block';
        btn.disabled = true;
    } else {
        errEl.style.display = 'none';
        btn.disabled = (username.length < 3 || password.length < 8 || password !== password2);
    }
}

// ── Step 6: Summary + installation ─────────────────────────────────────────
function buildRecap() {
    const driver = state.dbDriver;
    let dbInfo = driver.toUpperCase();
    if (driver !== 'sqlite') {
        const host = document.getElementById('db_host')?.value || '';
        const name = document.getElementById('db_name')?.value || '';
        dbInfo += ` — ${host}/${name}`;
    } else {
        dbInfo += ' — data/fulgurite.db';
    }

    const adminUser = document.getElementById('admin_username')?.value || '—';
    const adminEmail = document.getElementById('admin_email')?.value;
    const appName   = document.getElementById('app_name')?.value || 'Fulgurite';
    const tz        = document.getElementById('app_timezone')?.value || 'UTC';

    document.getElementById('recap-db').textContent    = dbInfo;
    document.getElementById('recap-admin').textContent = adminUser + (adminEmail ? ` (${adminEmail})` : '');
    document.getElementById('recap-app').textContent   = appName + ' — ' + tz;
}

async function launchInstall() {
    const btn = document.getElementById('btn-install');
    btn.disabled = true;

    document.getElementById('recap-card').style.display     = 'none';
    document.getElementById('install-btn-row').style.display = 'none';
    document.getElementById('install-progress').style.display = 'block';

    const steps = [
        i18n.install_step_dirs || 'Creating directories',
        i18n.install_step_config || 'Writing configuration',
        i18n.install_step_db || 'Initializing database',
        i18n.install_step_admin || 'Creating administrator account',
        i18n.install_step_app || 'Applying settings',
        i18n.install_step_finalize || 'Finalizing',
    ];

    const stepsEl = document.getElementById('progress-steps');
    stepsEl.innerHTML = '';
    const items = steps.map((label, i) => {
        const div = document.createElement('div');
        div.className = 'prog-item';
        div.id = 'prog-' + i;
        div.innerHTML = `<div class="prog-spinner"></div><span>${escHtml(label)}</span>`;
        stepsEl.appendChild(div);
        return div;
    });

    // Simulate steps visually (API does everything at once)
    for (let i = 0; i < items.length - 1; i++) {
        await sleep(300);
        items[i].querySelector('.prog-spinner').outerHTML =
            '<span style="width:20px;height:20px;color:var(--green);font-size:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center">✓</span>';
    }

    // Start the real installation
    const params = {
        db_driver:        state.dbDriver,
        db_host:          document.getElementById('db_host')?.value || 'localhost',
        db_port:          document.getElementById('db_port')?.value || '3306',
        db_name:          document.getElementById('db_name')?.value || 'fulgurite',
        db_user:          document.getElementById('db_user')?.value || '',
        db_pass:          document.getElementById('db_pass')?.value || '',
        admin_username:   document.getElementById('admin_username')?.value || '',
        admin_password:   document.getElementById('admin_password')?.value || '',
        admin_email:      document.getElementById('admin_email')?.value || '',
        admin_first_name: document.getElementById('admin_first_name')?.value || '',
        admin_last_name:  document.getElementById('admin_last_name')?.value || '',
        app_name:         document.getElementById('app_name')?.value || 'Fulgurite',
        timezone:         document.getElementById('app_timezone')?.value || 'UTC',
        web_user:         document.getElementById('ws_web_user')?.value || '',
        web_group:        document.getElementById('ws_web_group')?.value || '',
        php_fpm_socket:   document.getElementById('ws_php_fpm_socket')?.value || '',
    };

    try {
        const res = await apiCall('finalize', params);

        // Finalize progress bar
        items[items.length - 1].querySelector('.prog-spinner').outerHTML =
            '<span style="width:20px;height:20px;font-size:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center">' +
            (res.ok ? '<span style="color:var(--green)">✓</span>' : '<span style="color:var(--red)">✗</span>') +
            '</span>';

        if (res.ok) {
            if (res.setup_worker_token) {
                state.setupWorkerToken = res.setup_worker_token;
            }
            document.getElementById('install-success').style.display = 'block';
            document.getElementById('install-progress').style.display = 'none';
            // Check background systemd auto-install
            setupCheckAutoInstall();
        } else {
            document.getElementById('install-result').innerHTML =
                '<div class="alert alert-danger">❌ ' + escHtml(res.message) + '</div>';
            document.getElementById('install-result').style.display = 'block';
            document.getElementById('install-btn-row').style.display = 'flex';
            btn.disabled = false;
        }
    } catch (e) {
        document.getElementById('install-result').innerHTML =
            '<div class="alert alert-danger">❌ ' + escHtml(i18n.network_error || 'Network error:') + ' ' + escHtml(e.message) + '</div>';
        document.getElementById('install-result').style.display = 'block';
        document.getElementById('install-btn-row').style.display = 'flex';
        btn.disabled = false;
    }
}

// ── Worker post-install ───────────────────────────────────────────────────────

function setupWorkerTab(tab) {
    ['cron', 'systemd', 'skip'].forEach(t => {
        const btn = document.getElementById('setup-worker-tab-' + t);
        const panel = document.getElementById('setup-worker-panel-' + t);
        const active = t === tab;
        if (btn) {
            btn.style.borderColor = active ? 'var(--accent)' : '';
            btn.style.color = active ? 'var(--accent)' : '';
        }
        if (panel) panel.style.display = active ? '' : 'none';
    });
}

async function workerApiCall(action) {
    const token = state.setupWorkerToken || '';
    if (!token) {
        return { ok: false, message: i18n.worker_token_missing || 'Session token missing. Reload or go through the interface after login.' };
    }
    const formData = new FormData();
    formData.append('action', action);
    formData.append('setup_token', token);
    const res = await fetch('setup_worker', { method: 'POST', body: formData });
    if (!res.ok && res.status !== 400 && res.status !== 403) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

async function setupInstallCron() {
    const btn = document.getElementById('btn-setup-install-cron');
    const resultEl = document.getElementById('setup-cron-result');
    if (btn) btn.disabled = true;
    if (resultEl) resultEl.textContent = i18n.worker_cron_installing || 'Installing...';
    try {
        const res = await workerApiCall('install_cron');
        if (resultEl) {
            if (res.ok || res.success) {
                resultEl.innerHTML = '<span style="color:var(--green)">✔ ' + escHtml(i18n.worker_cron_success || 'Cron installed successfully.') + '</span>';
                if (btn) btn.style.display = 'none';
            } else {
                resultEl.innerHTML = '<span style="color:var(--red)">✗ ' + escHtml(res.message || (i18n.unknown_error || 'Unknown error')) + '</span>';
                if (btn) btn.disabled = false;
            }
        }
    } catch (e) {
        if (resultEl) resultEl.innerHTML = '<span style="color:var(--red)">✗ ' + escHtml(e.message) + '</span>';
        if (btn) btn.disabled = false;
    }
}

async function setupCheckAutoInstall() {
    const statusEl = document.getElementById('setup-auto-install-status');
    const btnAuto = document.getElementById('btn-setup-auto-systemd');
    try {
        const res = await workerApiCall('check_auto_install');
        if (statusEl) {
            if (res.can) {
                statusEl.innerHTML = '<span style="color:var(--green)">✔ Script helper disponible. L\'installation automatique est possible.</span>';
                if (btnAuto) btnAuto.style.display = '';
            } else {
                statusEl.textContent = (res.reason || (i18n.worker_auto_install_unavailable || 'Auto-install unavailable.')) + ' ' + (i18n.manual_command_hint || 'Command to run manually on server:');
            }
        }
    } catch (e) {
        if (statusEl) statusEl.textContent = (i18n.worker_check_failed || 'Unable to check:') + ' ' + e.message;
    }
}

async function setupAutoInstallSystemd() {
    const btn = document.getElementById('btn-setup-auto-systemd');
    const resultEl = document.getElementById('setup-systemd-result');
    if (btn) btn.disabled = true;
    if (resultEl) resultEl.textContent = i18n.worker_systemd_installing || 'Installing systemd...';
    try {
        const res = await workerApiCall('install_systemd');
        if (resultEl) {
            if (res.ok || res.success) {
                resultEl.innerHTML = '<span style="color:var(--green)">✔ ' + escHtml(i18n.worker_systemd_success || 'Systemd worker installed and started.') + '</span>';
            } else {
                resultEl.innerHTML = '<span style="color:var(--red)">✗ ' + escHtml(res.message || (i18n.error_prefix || 'Error:')) + '</span>';
                if (res.output) resultEl.innerHTML += `<pre style="font-size:11px;margin-top:6px;color:var(--text2)">${escHtml(res.output)}</pre>`;
                if (btn) btn.disabled = false;
            }
        }
    } catch (e) {
        if (resultEl) resultEl.innerHTML = '<span style="color:var(--red)">✗ ' + escHtml(e.message) + '</span>';
        if (btn) btn.disabled = false;
    }
}

async function setupShowSystemdUnit() {
    const section = document.getElementById('setup-systemd-unit-section');
    if (section && section.style.display !== 'none') { section.style.display = 'none'; return; }
    try {
        const res = await workerApiCall('generate_systemd_unit');
        if (!res.ok && !res.success) { window.toast(res.message || (i18n.generation_error || 'Generation error'), 'error'); return; }
        const fileEl = document.getElementById('setup-systemd-service-file');
        const unitEl = document.getElementById('setup-systemd-unit-content');
        const instrEl = document.getElementById('setup-systemd-instructions');
        if (fileEl) fileEl.textContent = res.service_file || '';
        if (unitEl) unitEl.textContent = res.unit_content || '';
        if (instrEl) instrEl.textContent = res.instructions || '';
        if (section) section.style.display = '';
    } catch (e) {
        window.toast((i18n.error_prefix || 'Error: ') + e.message, 'error');
    }
}

// ── Utilitaires ──────────────────────────────────────────────────────────────
async function apiCall(action, extraParams = {}) {
    const formData = new FormData();
    formData.append('action', action);
    for (const [k, v] of Object.entries(extraParams)) {
        formData.append(k, v);
    }
    const res  = await fetch('setup_action', { method: 'POST', body: formData });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

// ── Initialisation ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    runPrereqCheck();

    // Pre-selectionner Europe/Paris if disponible
    const tzSelect = document.getElementById('app_timezone');
    if (tzSelect) {
        const preferred = ['Europe/Paris', 'UTC'];
        for (const tz of preferred) {
            const opt = tzSelect.querySelector(`option[value="${tz}"]`);
            if (opt) { tzSelect.value = tz; break; }
        }
    }
});

window.goToStep = goToStep;
window.validateAdminForm = validateAdminForm;
window.onDriverChange = onDriverChange;
window.runPrereqCheck = runPrereqCheck;
window.launchInstall = launchInstall;
window.refreshWsConfig = refreshWsConfig;
window.copyWsConfig = copyWsConfig;
window.switchWsTab = switchWsTab;
window.setupWorkerTab = setupWorkerTab;
window.syncWebGroupDefault = syncWebGroupDefault;
window.testDatabase = testDatabase;
window.updatePasswordStrength = updatePasswordStrength;
window.setupInstallCron = setupInstallCron;
window.setupAutoInstallSystemd = setupAutoInstallSystemd;
window.setupShowSystemdUnit = setupShowSystemdUnit;
</script>
</body>
</html>
