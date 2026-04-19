<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Setup/ResetupWizard.php';
require_once __DIR__ . '/../src/Setup/ResetupPermissions.php';
require_once __DIR__ . '/../src/Setup/ResetupSudoRunner.php';
Auth::requireAdmin();

$title    = t('resetup.title');
$active   = 'settings';
$subtitle = t('resetup.subtitle');

$db = Database::getInstance();
$stepUpConfig = StepUpAuth::currentUserConfig();
$hasTOTP = !empty($stepUpConfig['totp_enabled']) && ($stepUpConfig['primary_factor'] ?? StepUpAuth::FACTOR_NONE) === StepUpAuth::FACTOR_CLASSIC;
$requiresWebAuthn = ($stepUpConfig['primary_factor'] ?? StepUpAuth::FACTOR_NONE) === StepUpAuth::FACTOR_WEBAUTHN;

// Checks whether confirmation session is already active
$alreadyConfirmed = !empty($_SESSION['resetup_confirmed']);

require_once 'layout_top.php';
?>

<style<?= cspNonceAttr() ?>>
/* ─── Danger screaten ──────────────────────────────────────────────────────────── */
.resetup-danger-wrap{display:flex;align-items:flex-start;justify-content:center;min-height:60vh;padding:40px 16px}
.resetup-danger-card{max-width:620px;width:100%;border:2px solid var(--red,#e53e3e);border-radius:18px;background:color-mix(in srgb,var(--red,#e53e3e) 6%,var(--bg2));padding:0;overflow:hidden}
.resetup-danger-header{background:color-mix(in srgb,var(--red,#e53e3e) 14%,var(--bg3));padding:28px 32px 20px;display:flex;align-items:center;gap:18px;border-bottom:1px solid color-mix(in srgb,var(--red,#e53e3e) 20%,var(--border))}
.resetup-danger-icon{font-size:36px;flex-shrink:0}
.resetup-danger-header-copy h1{font-size:20px;font-weight:700;color:var(--red,#e53e3e);margin:0 0 4px}
.resetup-danger-header-copy p{font-size:13px;color:var(--text2);margin:0;line-height:1.5}
.resetup-danger-body{padding:24px 32px}
.resetup-risk-list{margin:16px 0;padding:0;list-style:none;display:grid;gap:8px}
.resetup-risk-list li{display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);line-height:1.5}
.resetup-risk-list li::before{content:'⚠';flex-shrink:0;color:var(--yellow,#d69e2e);margin-top:1px}
.resetup-confirm-check{display:flex;align-items:flex-start;gap:10px;padding:14px 16px;border-radius:10px;background:color-mix(in srgb,var(--red,#e53e3e) 8%,var(--bg3));border:1px solid color-mix(in srgb,var(--red,#e53e3e) 20%,var(--border));margin:20px 0;cursor:pointer}
.resetup-confirm-check input[type=checkbox]{margin-top:3px;accent-color:var(--red,#e53e3e);width:16px;height:16px;flex-shrink:0}
.resetup-confirm-check label{font-size:13px;font-weight:600;color:var(--text);cursor:pointer;line-height:1.5}
.resetup-danger-actions{display:flex;gap:12px;flex-wrap:wrap;padding-top:4px}

/* ─── Wizard shell ───────────────────────────────────────────────────────────── */
.resetup-shell{display:grid;grid-template-columns:minmax(220px,260px) minmax(0,1fr);gap:20px;align-items:start}
@media (max-width: 900px) {
    .resetup-shell { grid-template-columns: 1fr !important; }
}
.resetup-sidebar{position:sticky;top:16px}
.resetup-sidebar .card{box-shadow:0 4px 24px rgba(0,0,0,.1)}
.resetup-main .card{box-shadow:0 4px 24px rgba(0,0,0,.1)}

/* ─── Progress bar ───────────────────────────────────────────────────────────── */
.resetup-progress-wrap{padding:12px 16px 4px}
.resetup-progress-label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text2);margin-bottom:6px;display:flex;justify-content:space-between}
.resetup-progress-track{height:5px;border-radius:5px;background:var(--border);overflow:hidden}
.resetup-progress-fill{height:100%;border-radius:5px;background:var(--accent2,#3fb950);transition:width .4s ease}

/* ─── Step nav ───────────────────────────────────────────────────────────────── */
.resetup-steps{display:grid;gap:2px;padding:6px 8px 12px}
.resetup-step-btn{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;border:1px solid transparent;width:100%;text-align:left;color:var(--text);font:inherit;appearance:none;-webkit-appearance:none;background:transparent;cursor:default;transition:background .15s,border-color .15s}
.resetup-step-btn.unlocked{cursor:pointer}
.resetup-step-btn.active{background:color-mix(in srgb,var(--accent) 9%,var(--bg2));border-color:color-mix(in srgb,var(--accent) 45%,var(--border))}
.resetup-step-btn.done{background:transparent}
.resetup-step-btn.unlocked:hover:not(.active){background:color-mix(in srgb,var(--accent) 5%,var(--bg2));border-color:color-mix(in srgb,var(--accent) 14%,var(--border))}
.resetup-step-num{width:26px;height:26px;border-radius:999px;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;color:var(--text2);background:var(--bg3);transition:all .2s}
.resetup-step-btn.active .resetup-step-num{border-color:var(--accent);background:color-mix(in srgb,var(--accent) 16%,var(--bg3));color:var(--accent)}
.resetup-step-btn.done .resetup-step-num{border-color:rgba(63,185,80,.5);background:rgba(63,185,80,.12);color:var(--green,#3fb950);font-size:14px}
.resetup-step-text{display:grid;gap:1px;min-width:0}
.resetup-step-label{font-size:13px;font-weight:600;color:var(--text);line-height:1.3}
.resetup-step-btn.done .resetup-step-label{color:var(--text2)}
.resetup-step-btn.active .resetup-step-label{color:var(--accent)}
.resetup-step-hint{font-size:11px;color:var(--text2);line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ─── Panels ─────────────────────────────────────────────────────────────────── */
.resetup-panel{display:none;animation:fadeInUp .2s ease both}
.resetup-panel.active{display:block}
@keyframes fadeInUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.resetup-panel-head{display:flex;align-items:flex-start;gap:14px;padding:20px 22px 16px;border-bottom:1px solid var(--border)}
.resetup-panel-icon{width:38px;height:38px;border-radius:10px;background:color-mix(in srgb,var(--accent) 10%,var(--bg3));border:1px solid color-mix(in srgb,var(--accent) 20%,var(--border));display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent);font-size:18px}
.resetup-panel-head-copy{min-width:0}
.resetup-panel-title{font-size:15px;font-weight:700;color:var(--text);margin-bottom:3px}
.resetup-panel-desc{font-size:13px;color:var(--text2);line-height:1.55;max-width:64ch}
.resetup-card-body{padding:20px 22px}

/* ─── Log area ───────────────────────────────────────────────────────────────── */
.resetup-log{background:var(--bg,#0d1117);border:1px solid var(--border);border-radius:8px;padding:12px 14px;font-family:monospace;font-size:12px;color:var(--text2);min-height:80px;max-height:220px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;margin-top:14px}
.resetup-log-line{line-height:1.6}
.resetup-log-line.ok{color:var(--green,#3fb950)}
.resetup-log-line.err{color:var(--red,#e53e3e)}
.resetup-log-line.warn{color:var(--yellow,#d69e2e)}

/* ─── Diag table ─────────────────────────────────────────────────────────────── */
.resetup-diag-table{width:100%;border-collapse:collapse;font-size:13px}
.resetup-diag-table th,.resetup-diag-table td{padding:9px 12px;text-align:left;border-bottom:1px solid var(--border)}
.resetup-diag-table th{color:var(--text2);font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase}
.resetup-diag-table td:first-child{color:var(--text2);width:40%}
.resetup-diag-table td:last-child{font-weight:600;color:var(--text)}

/* ─── Manual commands ────────────────────────────────────────────────────────── */
.resetup-cmd-block{background:var(--bg,#0d1117);border:1px solid var(--border);border-radius:8px;padding:14px;font-family:monospace;font-size:12px;color:var(--text2);margin:10px 0}
.resetup-cmd-block code{display:block;line-height:1.8;color:#79c0ff;word-break:break-all}

/* ─── Nav footer ─────────────────────────────────────────────────────────────── */
.resetup-footer{display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-top:1px solid var(--border);gap:10px}
.resetup-footer-left{display:flex;gap:8px}
.resetup-footer-right{display:flex;gap:8px}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ÉCRAN DE DANGER (affiché si la session de confirmation n'existe pas)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="resetup-danger-screen" <?= $alreadyConfirmed ? 'style="display:none"' : '' ?>>
    <div class="resetup-danger-wrap">
        <div class="resetup-danger-card" role="main">
            <div class="resetup-danger-header">
                <div class="resetup-danger-icon" aria-hidden="true">⚠️</div>
                <div class="resetup-danger-header-copy">
                    <h1><?= t('resetup.danger.title') ?></h1>
                    <p><?= t('resetup.danger.subtitle') ?></p>
                </div>
            </div>
            <div class="resetup-danger-body">
                <p style="font-size:14px;color:var(--text);font-weight:600;margin:0 0 6px"><?= t('resetup.danger.risks_intro') ?></p>
                <ul class="resetup-risk-list">
                    <li><?= t('resetup.danger.risk1') ?></li>
                    <li><?= t('resetup.danger.risk2') ?></li>
                    <li><?= t('resetup.danger.risk3') ?></li>
                    <li><?= t('resetup.danger.risk4') ?></li>
                    <li><?= t('resetup.danger.risk5') ?></li>
                </ul>

                <label class="resetup-confirm-check" for="resetup-understand-check">
                    <input type="checkbox" id="resetup-understand-check" onchange="document.getElementById('btn-start-resetup').disabled=!this.checked">
                    <span><?= t('resetup.danger.confirm_label') ?></span>
                </label>

                <div class="resetup-danger-actions">
                    <button
                        type="button"
                        id="btn-start-resetup"
                        class="btn btn-danger"
                        disabled
                        onclick="confirmAndStartResetup()"
                    >
                        <?= t('resetup.danger.start_btn') ?>
                    </button>
                    <a href="<?= routePath('/settings.php') ?>" class="btn">
                        <?= t('resetup.danger.cancel_btn') ?>
                    </a>
                </div>

                <p style="font-size:11px;color:var(--text2);margin-top:18px;border-top:1px solid var(--border);padding-top:12px">
                    <?= t('resetup.danger.audit_notice') ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     WIZARD (caché jusqu'à confirmation, ou visible si déjà confirmé)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="resetup-wizard" <?= !$alreadyConfirmed ? 'style="display:none"' : '' ?>>

    <!-- Global progress bar -->
    <div class="resetup-progress-wrap" style="margin-bottom:16px">
        <div class="resetup-progress-label">
            <span><?= t('resetup.progress.label') ?></span>
            <span id="resetup-step-counter"><?= h(t('resetup.progress.step_counter', [':current' => 1, ':total' => 7])) ?></span>
        </div>
        <div class="resetup-progress-track">
            <div class="resetup-progress-fill" id="resetup-progress-fill" style="width:14.28%"></div>
        </div>
    </div>

    <div class="resetup-shell">
        <!-- ── Sidebar navigation ─────────────────────────────────────────────── -->
        <aside class="resetup-sidebar">
            <div class="card">
                <div class="card-header" style="padding:12px 16px">
                    <span style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text2)"><?= t('resetup.sidebar.title') ?></span>
                </div>
                <nav class="resetup-steps" aria-label="<?= h(t('resetup.sidebar.aria_label')) ?>">
                    <button type="button" class="resetup-step-btn active" id="nav-step-1" onclick="goToStep(1)">
                        <span class="resetup-step-num" id="num-step-1">1</span>
                        <span class="resetup-step-text">
                            <span class="resetup-step-label"><?= t('resetup.step.1.label') ?></span>
                            <span class="resetup-step-hint"><?= t('resetup.step.1.hint') ?></span>
                        </span>
                    </button>
                    <button type="button" class="resetup-step-btn" id="nav-step-2" onclick="goToStep(2)">
                        <span class="resetup-step-num" id="num-step-2">2</span>
                        <span class="resetup-step-text">
                            <span class="resetup-step-label"><?= t('resetup.step.2.label') ?></span>
                            <span class="resetup-step-hint"><?= t('resetup.step.2.hint') ?></span>
                        </span>
                    </button>
                    <button type="button" class="resetup-step-btn" id="nav-step-3" onclick="goToStep(3)">
                        <span class="resetup-step-num" id="num-step-3">3</span>
                        <span class="resetup-step-text">
                            <span class="resetup-step-label"><?= t('resetup.step.3.label') ?></span>
                            <span class="resetup-step-hint"><?= t('resetup.step.3.hint') ?></span>
                        </span>
                    </button>
                    <button type="button" class="resetup-step-btn" id="nav-step-4" onclick="goToStep(4)">
                        <span class="resetup-step-num" id="num-step-4">4</span>
                        <span class="resetup-step-text">
                            <span class="resetup-step-label"><?= t('resetup.step.4.label') ?></span>
                            <span class="resetup-step-hint"><?= t('resetup.step.4.hint') ?></span>
                        </span>
                    </button>
                    <button type="button" class="resetup-step-btn" id="nav-step-5" onclick="goToStep(5)">
                        <span class="resetup-step-num" id="num-step-5">5</span>
                        <span class="resetup-step-text">
                            <span class="resetup-step-label"><?= t('resetup.step.5.label') ?></span>
                            <span class="resetup-step-hint"><?= t('resetup.step.5.hint') ?></span>
                        </span>
                    </button>
                    <button type="button" class="resetup-step-btn" id="nav-step-6" onclick="goToStep(6)">
                        <span class="resetup-step-num" id="num-step-6">6</span>
                        <span class="resetup-step-text">
                            <span class="resetup-step-label"><?= t('resetup.step.6.label') ?></span>
                            <span class="resetup-step-hint"><?= t('resetup.step.6.hint') ?></span>
                        </span>
                    </button>
                    <button type="button" class="resetup-step-btn" id="nav-step-7" onclick="goToStep(7)">
                        <span class="resetup-step-num" id="num-step-7">7</span>
                        <span class="resetup-step-text">
                            <span class="resetup-step-label"><?= t('resetup.step.7.label') ?></span>
                            <span class="resetup-step-hint"><?= t('resetup.step.7.hint') ?></span>
                        </span>
                    </button>
                </nav>
            </div>
        </aside>

        <!-- ── Main content area ─────────────────────────────────────── -->
        <div class="resetup-main">
            <div class="card">

                <!-- ────────────────────────────────────────────────────────────
                     STEP 1 — Authentification renforcée
                     ──────────────────────────────────────────────────────────── -->
                <div class="resetup-panel active" id="step-panel-1">
                    <div class="resetup-panel-head">
                        <div class="resetup-panel-icon" aria-hidden="true">🔐</div>
                        <div class="resetup-panel-head-copy">
                            <div class="resetup-panel-title"><?= t('resetup.panel.1.title') ?></div>
                            <div class="resetup-panel-desc">
                                <?= t('resetup.panel.1.desc_base') ?>
                                <?php if ($hasTOTP): ?>
                                <?= t('resetup.panel.1.desc_totp') ?>
                                <?php elseif ($requiresWebAuthn): ?>
                                <?= t('resetup.panel.1.desc_webauthn') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="resetup-card-body">
                        <div class="form-group">
                            <label for="step1-password" style="font-size:13px;font-weight:600;color:var(--text)"><?= t('resetup.panel.1.password_label') ?></label>
                            <input
                                type="password"
                                id="step1-password"
                                class="form-control"
                                placeholder="Votre mot de passe actuel"
                                autocomplete="current-password"
                                style="margin-top:6px;width:100%;max-width:400px"
                            >
                        </div>
                        <?php if ($hasTOTP): ?>
                        <div class="form-group" style="margin-top:16px">
                            <label for="step1-totp" style="font-size:13px;font-weight:600;color:var(--text)"><?= t('resetup.panel.1.totp_label') ?></label>
                            <input
                                type="text"
                                id="step1-totp"
                                class="form-control"
                                placeholder="123456"
                                inputmode="numeric"
                                maxlength="6"
                                autocomplete="one-time-code"
                                style="margin-top:6px;width:160px;letter-spacing:4px;font-family:monospace;font-size:18px"
                            >
                        </div>
                        <?php else: ?>
                        <input type="hidden" id="step1-totp" value="">
                        <?php endif; ?>
                        <?php if ($requiresWebAuthn): ?>
                        <div style="margin-top:12px;font-size:12px;color:var(--text2);max-width:480px">
                            <?= t('resetup.panel.1.desc_webauthn') ?>
                        </div>
                        <?php endif; ?>
                        <div id="step1-error" class="alert alert-danger" style="display:none;margin-top:14px;max-width:420px"></div>
                        <div style="margin-top:20px">
                            <button type="button" class="btn btn-danger" onclick="runStep1Auth()" id="btn-step1-verify">
                                <span id="btn-step1-label"><?= t('resetup.panel.1.next_btn') ?></span>
                                <span id="btn-step1-spinner" class="spinner" style="display:none;margin-left:8px"></span>
                            </button>
                        </div>
                    </div>
                    <div class="resetup-footer">
                        <div class="resetup-footer-left">
                            <button type="button" class="btn btn-warning" onclick="cancelResetup()"><?= t('common.cancel') ?></button>
                        </div>
                        <div class="resetup-footer-right">
                            <button type="button" class="btn" id="btn-next-1" onclick="goToStep(2)" disabled><?= t('resetup.nav.next') ?></button>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────────────────────
                     STEP 2 — Diagnostic
                     ──────────────────────────────────────────────────────────── -->
                <div class="resetup-panel" id="step-panel-2">
                    <div class="resetup-panel-head">
                        <div class="resetup-panel-icon" aria-hidden="true">🔍</div>
                        <div class="resetup-panel-head-copy">
                            <div class="resetup-panel-title"><?= t('resetup.panel.2.title') ?></div>
                            <div class="resetup-panel-desc"><?= t('resetup.panel.2.desc') ?></div>
                        </div>
                    </div>
                    <div class="resetup-card-body">
                        <button type="button" class="btn" onclick="runStep2Diagnostic()" id="btn-run-diag">
                            <span id="btn-diag-label"><?= t('resetup.panel.2.loading') ?></span>
                            <span id="btn-diag-spinner" class="spinner" style="display:none;margin-left:8px"></span>
                        </button>
                        <div id="step2-error" class="alert alert-danger" style="display:none;margin-top:14px"></div>
                        <div id="step2-results" style="display:none;margin-top:20px">
                            <div class="table-wrap">
                            <table class="resetup-diag-table">
                                <thead>
                                    <tr>
                                        <th>Paramètre</th>
                                        <th>Valeur détectée</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody id="step2-tbody"></tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                    <div class="resetup-footer">
                        <div class="resetup-footer-left">
                            <button type="button" class="btn" onclick="goToStep(1)"><?= t('resetup.nav.prev') ?></button>
                            <button type="button" class="btn btn-warning" onclick="cancelResetup()"><?= t('common.cancel') ?></button>
                        </div>
                        <div class="resetup-footer-right">
                            <button type="button" class="btn" id="btn-next-2" onclick="goToStep(3)" disabled><?= t('resetup.nav.next') ?></button>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────────────────────
                     STEP 3 — Sélection
                     ──────────────────────────────────────────────────────────── -->
                <div class="resetup-panel" id="step-panel-3">
                    <div class="resetup-panel-head">
                        <div class="resetup-panel-icon" aria-hidden="true">⚙️</div>
                        <div class="resetup-panel-head-copy">
                            <div class="resetup-panel-title"><?= t('resetup.panel.3.title') ?></div>
                            <div class="resetup-panel-desc"><?= t('resetup.panel.3.desc') ?></div>
                        </div>
                    </div>
                    <div class="resetup-card-body">
                        <div class="form-group">
                            <label for="sel-fpm-user" style="font-size:13px;font-weight:600;color:var(--text)"><?= t('resetup.panel.3.fpm_user_label') ?></label>
                            <input
                                type="text"
                                id="sel-fpm-user"
                                class="form-control"
                                placeholder="www-data"
                                pattern="^[a-z_][a-z0-9_-]{0,31}$"
                                style="margin-top:6px;width:100%;max-width:300px"
                                oninput="validateStep3()"
                            >
                            <div id="sel-fpm-user-error" style="font-size:12px;color:var(--red,#e53e3e);margin-top:4px;display:none">
                                Nom d'utilisateur invalide (lettres minuscules, chiffres, tirets, 1–32 caractères).
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:16px">
                            <label for="sel-fpm-group" style="font-size:13px;font-weight:600;color:var(--text)"><?= t('resetup.panel.3.group_label') ?></label>
                            <input
                                type="text"
                                id="sel-fpm-group"
                                class="form-control"
                                placeholder="www-data"
                                pattern="^[a-z_][a-z0-9_-]{0,31}$"
                                style="margin-top:6px;width:100%;max-width:300px"
                                oninput="validateStep3()"
                            >
                            <div id="sel-fpm-group-error" style="font-size:12px;color:var(--red,#e53e3e);margin-top:4px;display:none">
                                Nom de groupe invalide (lettres minuscules, chiffres, tirets, 1–32 caractères).
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:16px">
                            <label for="sel-worker-mode" style="font-size:13px;font-weight:600;color:var(--text)"><?= t('resetup.panel.3.mode_label') ?></label>
                            <select id="sel-worker-mode" class="form-control" style="margin-top:6px;width:100%;max-width:300px" onchange="validateStep3()">
                                <option value="systemd">systemd (service)</option>
                                <option value="cron">cron (tâche planifiée)</option>
                                <option value="daemon">daemon (processus en arrière-plan)</option>
                            </select>
                        </div>
                        <div id="step3-error" class="alert alert-danger" style="display:none;margin-top:14px"></div>
                    </div>
                    <div class="resetup-footer">
                        <div class="resetup-footer-left">
                            <button type="button" class="btn" onclick="goToStep(2)"><?= t('resetup.nav.prev') ?></button>
                            <button type="button" class="btn btn-warning" onclick="cancelResetup()"><?= t('common.cancel') ?></button>
                        </div>
                        <div class="resetup-footer-right">
                            <button type="button" class="btn" id="btn-next-3" onclick="saveStep3Selection()" disabled><?= t('resetup.panel.3.next_btn') ?></button>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────────────────────
                     STEP 4 — Test sudo
                     ──────────────────────────────────────────────────────────── -->
                <div class="resetup-panel" id="step-panel-4">
                    <div class="resetup-panel-head">
                        <div class="resetup-panel-icon" aria-hidden="true">🔑</div>
                        <div class="resetup-panel-head-copy">
                            <div class="resetup-panel-title"><?= t('resetup.panel.4.title') ?></div>
                            <div class="resetup-panel-desc"><?= t('resetup.panel.4.desc') ?></div>
                        </div>
                    </div>
                    <div class="resetup-card-body">
                        <!-- Passwordless test -->
                        <div id="sudo-nopass-section">
                            <p style="font-size:13px;color:var(--text2);margin:0 0 14px;line-height:1.5">
                                <?= t('resetup.panel.4.nopass_hint') ?>
                            </p>
                            <button type="button" class="btn" onclick="runStep4SudoTest()" id="btn-test-sudo">
                                <span id="btn-sudo-label"><?= t('resetup.panel.4.test_nopass_btn') ?></span>
                                <span id="btn-sudo-spinner" class="spinner" style="display:none;margin-left:8px"></span>
                            </button>
                        </div>

                        <!-- Fallback: sudo password -->
                        <div id="sudo-pass-section" style="display:none;margin-top:20px;padding-top:18px;border-top:1px solid var(--border)">
                            <p style="font-size:13px;color:var(--text2);margin:0 0 14px;line-height:1.5">
                                <?= t('resetup.panel.4.pass_hint') ?>
                            </p>
                            <div class="form-group">
                                <label for="sudo-password-input" style="font-size:13px;font-weight:600;color:var(--text)"><?= t('resetup.panel.4.pass_label') ?></label>
                                <input
                                    type="password"
                                    id="sudo-password-input"
                                    class="form-control"
                                    placeholder="••••••••"
                                    autocomplete="off"
                                    style="margin-top:6px;width:100%;max-width:300px"
                                >
                            </div>
                            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
                                <button type="button" class="btn" onclick="runStep4SudoWithPass()" id="btn-sudo-withpass">
                                    <span id="btn-sudopass-label"><?= t('resetup.panel.4.test_withpass_btn') ?></span>
                                    <span id="btn-sudopass-spinner" class="spinner" style="display:none;margin-left:8px"></span>
                                </button>
                                <button type="button" class="btn btn-warning" onclick="showManualCommands()">
                                    <?= t('resetup.panel.4.manual_btn') ?>
                                </button>
                            </div>
                        </div>

                        <!-- Mode manuel -->
                        <div id="sudo-manual-section" style="display:none;margin-top:20px;padding-top:18px;border-top:1px solid var(--border)">
                            <p style="font-size:13px;font-weight:600;color:var(--text);margin:0 0 8px">
                                <?= t('resetup.panel.4.manual_title') ?>
                            </p>
                            <p style="font-size:12px;color:var(--text2);margin:0 0 12px;line-height:1.5">
                                <?= t('resetup.panel.4.manual_hint') ?>
                            </p>
                            <div id="manual-commands-block" class="resetup-cmd-block">
                                <span id="manual-commands-spinner" class="spinner"></span>
                            </div>
                            <label class="resetup-confirm-check" for="manual-done-check" style="margin-top:12px">
                                <input type="checkbox" id="manual-done-check" onchange="document.getElementById('btn-next-4').disabled=!this.checked">
                                <span><?= t('resetup.panel.4.manual_done_label') ?></span>
                            </label>
                        </div>

                        <div id="step4-result" style="display:none;margin-top:14px">
                            <span class="badge badge-green" id="step4-ok-badge" style="display:none"><?= t('resetup.panel.4.sudo_ok_badge') ?></span>
                        </div>
                        <div id="step4-error" class="alert alert-danger" style="display:none;margin-top:14px"></div>
                    </div>
                    <div class="resetup-footer">
                        <div class="resetup-footer-left">
                            <button type="button" class="btn" onclick="goToStep(3)"><?= t('resetup.nav.prev') ?></button>
                            <button type="button" class="btn btn-warning" onclick="cancelResetup()"><?= t('common.cancel') ?></button>
                        </div>
                        <div class="resetup-footer-right">
                            <button type="button" class="btn" id="btn-next-4" onclick="goToStep(5)" disabled><?= t('resetup.nav.next') ?></button>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────────────────────
                     STEP 5 — Permissions
                     ──────────────────────────────────────────────────────────── -->
                <div class="resetup-panel" id="step-panel-5">
                    <div class="resetup-panel-head">
                        <div class="resetup-panel-icon" aria-hidden="true">📁</div>
                        <div class="resetup-panel-head-copy">
                            <div class="resetup-panel-title"><?= t('resetup.panel.5.title') ?></div>
                            <div class="resetup-panel-desc"><?= t('resetup.panel.5.desc') ?></div>
                        </div>
                    </div>
                    <div class="resetup-card-body">
                        <div id="step5-dirs-table" style="margin-bottom:16px">
                            <p style="font-size:13px;color:var(--text2)"><?= t('resetup.panel.5.hint') ?></p>
                        </div>
                        <button type="button" class="btn btn-danger" onclick="runStep5Permissions()" id="btn-apply-perms">
                            <span id="btn-perms-label"><?= t('resetup.panel.5.apply_btn') ?></span>
                            <span id="btn-perms-spinner" class="spinner" style="display:none;margin-left:8px"></span>
                        </button>
                        <div id="step5-log" class="resetup-log" style="display:none"></div>
                        <div id="step5-error" class="alert alert-danger" style="display:none;margin-top:14px"></div>
                    </div>
                    <div class="resetup-footer">
                        <div class="resetup-footer-left">
                            <button type="button" class="btn" onclick="goToStep(4)"><?= t('resetup.nav.prev') ?></button>
                            <button type="button" class="btn btn-warning" onclick="cancelResetup()"><?= t('common.cancel') ?></button>
                        </div>
                        <div class="resetup-footer-right">
                            <button type="button" class="btn" id="btn-next-5" onclick="goToStep(6)" disabled><?= t('resetup.nav.next') ?></button>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────────────────────
                     STEP 6 — Worker
                     ──────────────────────────────────────────────────────────── -->
                <div class="resetup-panel" id="step-panel-6">
                    <div class="resetup-panel-head">
                        <div class="resetup-panel-icon" aria-hidden="true">⚙️</div>
                        <div class="resetup-panel-head-copy">
                            <div class="resetup-panel-title"><?= t('resetup.panel.6.title') ?></div>
                            <div class="resetup-panel-desc"><?= t('resetup.panel.6.desc') ?></div>
                        </div>
                    </div>
                    <div class="resetup-card-body">
                        <div id="step6-status-wrap" style="margin-bottom:16px">
                            <p style="font-size:13px;color:var(--text2)"><?= t('resetup.panel.6.status_prefix') ?> <span id="step6-worker-status" class="badge"><?= t('resetup.panel.6.unknown_badge') ?></span></p>
                        </div>
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <button type="button" class="btn btn-danger" onclick="runStep6Worker()" id="btn-apply-worker">
                                <span id="btn-worker-label"><?= t('resetup.panel.6.apply_btn') ?></span>
                                <span id="btn-worker-spinner" class="spinner" style="display:none;margin-left:8px"></span>
                            </button>
                        </div>
                        <div id="step6-log" class="resetup-log" style="display:none"></div>
                        <div id="step6-error" class="alert alert-danger" style="display:none;margin-top:14px"></div>
                    </div>
                    <div class="resetup-footer">
                        <div class="resetup-footer-left">
                            <button type="button" class="btn" onclick="goToStep(5)"><?= t('resetup.nav.prev') ?></button>
                            <button type="button" class="btn btn-warning" onclick="cancelResetup()"><?= t('common.cancel') ?></button>
                        </div>
                        <div class="resetup-footer-right">
                            <button type="button" class="btn" id="btn-next-6" onclick="goToStep(7)" disabled><?= t('resetup.nav.next') ?></button>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────────────────────
                     STEP 7 — Secret agent
                     ──────────────────────────────────────────────────────────── -->
                <div class="resetup-panel" id="step-panel-7">
                    <div class="resetup-panel-head">
                        <div class="resetup-panel-icon" aria-hidden="true">🔒</div>
                        <div class="resetup-panel-head-copy">
                            <div class="resetup-panel-title"><?= t('resetup.panel.7.title') ?></div>
                            <div class="resetup-panel-desc"><?= t('resetup.panel.7.desc') ?></div>
                        </div>
                    </div>
                    <div class="resetup-card-body">
                        <div id="step7-socket-wrap" style="margin-bottom:16px">
                            <p style="font-size:13px;color:var(--text2)"><?= t('resetup.panel.7.socket_status_prefix') ?> <span id="step7-socket-status" class="badge"><?= t('resetup.panel.7.socket_unverified') ?></span></p>
                            <p style="font-size:12px;color:var(--text2);margin-top:6px"><?= t('resetup.panel.7.socket_path_prefix') ?> <code id="step7-socket-path">—</code></p>
                        </div>
                        <button type="button" class="btn" onclick="runStep7Agent()" id="btn-apply-agent">
                            <span id="btn-agent-label"><?= t('resetup.panel.7.verify_btn') ?></span>
                            <span id="btn-agent-spinner" class="spinner" style="display:none;margin-left:8px"></span>
                        </button>
                        <div id="step7-log" class="resetup-log" style="display:none"></div>
                        <div id="step7-error" class="alert alert-danger" style="display:none;margin-top:14px"></div>

                        <!-- Final summary -->
                        <div id="step7-summary" style="display:none;margin-top:24px;padding:18px;border-radius:12px;background:color-mix(in srgb,var(--green,#3fb950) 8%,var(--bg3));border:1px solid color-mix(in srgb,var(--green,#3fb950) 20%,var(--border))">
                            <p style="font-weight:700;font-size:15px;color:var(--green,#3fb950);margin:0 0 8px"><?= t('resetup.panel.7.summary_title') ?></p>
                            <div id="step7-summary-body" style="font-size:13px;color:var(--text2);line-height:1.7"></div>
                            <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
                                <a href="<?= routePath('/settings.php') ?>" class="btn"><?= t('resetup.panel.7.back_settings') ?></a>
                                <a href="<?= routePath('/') ?>" class="btn"><?= t('resetup.panel.7.dashboard') ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="resetup-footer">
                        <div class="resetup-footer-left">
                            <button type="button" class="btn" onclick="goToStep(6)"><?= t('resetup.nav.prev') ?></button>
                            <button type="button" class="btn btn-warning" onclick="cancelResetup()"><?= t('common.cancel') ?></button>
                        </div>
                        <div class="resetup-footer-right">
                            <!-- No "next" on the last step -->
                        </div>
                    </div>
                </div>

            </div><!-- /.card -->
        </div><!-- /.resetup-main -->
    </div><!-- /.resetup-shell -->
</div><!-- /#resetup-wizard -->

<!-- ═══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════════════════════════════ -->
<script<?= cspNonceAttr() ?>>
'use strict';

// ── Constantes globales ────────────────────────────────────────────────────────
const CSRF_TOKEN  = <?= json_encode(csrfToken()) ?>;
const RESETUP_API = '<?= routePath('/api/resetup_action.php') ?>';
const HAS_TOTP    = <?= json_encode($hasTOTP) ?>;

// ── Wizard state ─────────────────────────────────────────────────────────────
let currentStep = <?= $alreadyConfirmed ? 1 : 0 ?>;

/** wizardData — NEVER contains the sudo password after use */
const wizardData = {
    fpmUser:    '',
    fpmGroup:   '',
    workerMode: 'systemd',
    diagDone:   false,
    // NOTE: sudoPassword is a local variable in runStep4SudoWithPass(),
    //        it is never stored in wizardData, localStorage, or sessionStorage.
};

// ── Fetch utility ──────────────────────────────────────────────────────────────

/**
 * Appel API centralise.
 * @param {string} action
 * @param {Object} data — must NEVER contain sudo_password after use
 * @returns {Promise<Object>}
 */
async function apiCall(action, data = {}) {
    const body = JSON.stringify({ action, ...data });
    const resp = await fetch(RESETUP_API, {
        method:  'POST',
        headers: {
            'Content-Type':  'application/json',
            'X-CSRF-Token':  CSRF_TOKEN,
        },
        body,
    });

    if (!resp.ok) {
        throw new Error(`HTTP error ${resp.status}`);
    }

    return resp.json();
}

// ── Navigation ─────────────────────────────────────────────────────────────────

function goToStep(n) {
    if (n < 1 || n > 7) return;

    // Hide the previous panel
    if (currentStep > 0) {
        const old = document.getElementById('step-panel-' + currentStep);
        if (old) old.classList.remove('active');
        const oldNav = document.getElementById('nav-step-' + currentStep);
        if (oldNav) {
            oldNav.classList.remove('active');
            if (n > currentStep) oldNav.classList.add('done');
        }
    }

    currentStep = n;

    // Show the new panel
    const panel = document.getElementById('step-panel-' + n);
    if (panel) panel.classList.add('active');

    const nav = document.getElementById('nav-step-' + n);
    if (nav) {
        nav.classList.remove('done');
        nav.classList.add('active');
    }

    // Progress bar
    const fill = document.getElementById('resetup-progress-fill');
    if (fill) fill.style.width = Math.round((n / 7) * 100) + '%';

    const counter = document.getElementById('resetup-step-counter');
    if (counter) counter.textContent = '<?= h(t('resetup.js.step_prefix')) ?> ' + n + ' / 7';

    // Update numeric indicators (checkmark if done)
    for (let i = 1; i <= 7; i++) {
        const num = document.getElementById('num-step-' + i);
        if (!num) continue;
        if (i < n) {
            num.textContent = '✓';
        } else {
            num.textContent = String(i);
        }
    }

    // load automatiquement certaines Steps
    if (n === 6) fetchStep6WorkerStatus();
}

// ── ECRAN DANGER : confirmation initiale ──────────────────────────────────────

async function confirmAndStartResetup() {
    const btn = document.getElementById('btn-start-resetup');
    btn.disabled = true;
    btn.textContent = '<?= h(t('resetup.js.initializing')) ?>';

    try {
        const r = await apiCall('confirm_start');
        if (!r.success) {
            throw new Error(r.error || '<?= h(t('common.unknown_error')) ?>');
        }
        // Show the wizard
        document.getElementById('resetup-danger-screen').style.display = 'none';
        document.getElementById('resetup-wizard').style.display = '';
        currentStep = 0;
        goToStep(1);
    } catch (e) {
        btn.disabled = false;
        btn.textContent = '<?= h(t('resetup.danger.start_btn')) ?>';
        alert('<?= h(t('resetup.js.init_error_prefix')) ?>' + e.message);
    }
}

// ── STEP 1 : authentication ──────────────────────────────────────────────────

async function runStep1Auth() {
    const password  = document.getElementById('step1-password').value;
    const totpInput = document.getElementById('step1-totp');
    const totpCode  = totpInput ? totpInput.value.trim() : '';
    const errBox    = document.getElementById('step1-error');
    const btn       = document.getElementById('btn-step1-verify');
    const lbl       = document.getElementById('btn-step1-label');
    const spin      = document.getElementById('btn-step1-spinner');

    errBox.style.display = 'none';

    if (!password) {
        errBox.textContent  = '<?= h(t('resetup.js.password_required')) ?>';
        errBox.style.display = 'block';
        return;
    }

    btn.disabled     = true;
    lbl.textContent  = '<?= h(t('resetup.js.verifying')) ?>';
    spin.style.display = '';

    try {
        const r = await apiCall('step1_verify_auth', {
            password,
            totp_code: totpCode,
        });

        if (!r.success) {
            errBox.textContent   = r.error || '<?= h(t('resetup.js.auth_failed')) ?>';
            errBox.style.display = 'block';
            return;
        }

        if (r.completed === false && r.next_factor === 'webauthn') {
            try {
                await window.startWebAuthnStepUp('resetup.step1');
            } catch (error) {
                errBox.textContent = error.message || '<?= h(t('resetup.js.webauthn_failed')) ?>';
                errBox.style.display = 'block';
                return;
            }
        }

        // successs — unlock "Next step"
        document.getElementById('btn-next-1').disabled = false;
        document.getElementById('num-step-1').textContent = '✓';
        lbl.textContent = '<?= h(t('resetup.js.identity_verified')) ?>';
        btn.disabled    = true; // do not repeat

        // Clear the password from the DOM
        document.getElementById('step1-password').value = '';
        if (totpInput) totpInput.value = '';

    } catch (e) {
        errBox.textContent   = '<?= h(t('common.network_error')) ?> : ' + e.message;
        errBox.style.display = 'block';
    } finally {
        spin.style.display = 'none';
        if (!btn.disabled) {
            btn.disabled    = false;
            lbl.textContent = '<?= h(t('resetup.js.verify_identity')) ?>';
        }
    }
}

// ── STEP 2 : Diagnostic ────────────────────────────────────────────────────────

async function runStep2Diagnostic() {
    const btn   = document.getElementById('btn-run-diag');
    const lbl   = document.getElementById('btn-diag-label');
    const spin  = document.getElementById('btn-diag-spinner');
    const errBox = document.getElementById('step2-error');
    const results = document.getElementById('step2-results');

    errBox.style.display   = 'none';
    results.style.display  = 'none';
    btn.disabled           = true;
    lbl.textContent        = '<?= h(t('resetup.js.analyzing')) ?>';
    spin.style.display     = '';

    try {
        const r = await apiCall('step2_diagnostic');

        if (!r.success) {
            throw new Error(r.error || '<?= h(t('resetup.js.diag_failed')) ?>');
        }

        // Populate the table
        const tbody = document.getElementById('step2-tbody');
        tbody.innerHTML = '';
        const items = r.data || [];

        items.forEach(item => {
            const tr = document.createElement('tr');
            const statusClass = item.status === 'ok' ? 'badge-green'
                              : item.status === 'warn' ? 'badge-yellow'
                              : 'badge-red';
            tr.innerHTML = `
                <td>${escHtml(item.label)}</td>
                <td><code>${escHtml(item.value)}</code></td>
                <td><span class="badge ${statusClass}">${escHtml(item.status)}</span></td>
            `;
            tbody.appendChild(tr);
        });

        results.style.display = '';
        wizardData.diagDone   = true;

        // Pre-fill Step 3 fields if data is available
        if (r.detected_user) {
            document.getElementById('sel-fpm-user').value  = r.detected_user;
            document.getElementById('sel-fpm-group').value = r.detected_group || r.detected_user;
            validateStep3();
        }

        document.getElementById('btn-next-2').disabled = false;
        lbl.textContent = '<?= h(t('resetup.js.diag_done')) ?>';

    } catch (e) {
        errBox.textContent   = '<?= h(t('resetup.js.error_prefix')) ?>' + e.message;
        errBox.style.display = 'block';
        btn.disabled         = false;
        lbl.textContent      = '<?= h(t('resetup.js.run_diag')) ?>';
    } finally {
        spin.style.display = 'none';
    }
}

// ── STEP 3: Real-time validation ──────────────────────────────────────────────

const USER_GROUP_RE = /^[a-z_][a-z0-9_-]{0,31}$/;

function validateStep3() {
    const userVal  = document.getElementById('sel-fpm-user').value.trim();
    const groupVal = document.getElementById('sel-fpm-group').value.trim();
    const userErr  = document.getElementById('sel-fpm-user-error');
    const groupErr = document.getElementById('sel-fpm-group-error');

    const userOk  = USER_GROUP_RE.test(userVal);
    const groupOk = USER_GROUP_RE.test(groupVal);

    userErr.style.display  = userOk || userVal === '' ? 'none' : '';
    groupErr.style.display = groupOk || groupVal === '' ? 'none' : '';

    document.getElementById('btn-next-3').disabled = !(userOk && groupOk);
}

async function saveStep3Selection() {
    const fpmUser   = document.getElementById('sel-fpm-user').value.trim();
    const fpmGroup  = document.getElementById('sel-fpm-group').value.trim();
    const workerMode = document.getElementById('sel-worker-mode').value;
    const errBox    = document.getElementById('step3-error');

    errBox.style.display = 'none';

    const btn = document.getElementById('btn-next-3');
    btn.disabled    = true;
    btn.textContent = '<?= h(t('resetup.js.saving')) ?>';

    try {
        const r = await apiCall('step3_save_selection', {
            fpm_user:    fpmUser,
            fpm_group:   fpmGroup,
            worker_mode: workerMode,
        });

        if (!r.success) {
            throw new Error(r.error || '<?= h(t('resetup.js.validation_error')) ?>');
        }

        wizardData.fpmUser    = fpmUser;
        wizardData.fpmGroup   = fpmGroup;
        wizardData.workerMode = workerMode;

        goToStep(4);

    } catch (e) {
        errBox.textContent   = '<?= h(t('resetup.js.error_prefix')) ?>' + e.message;
        errBox.style.display = 'block';
        btn.disabled         = false;
        btn.textContent      = '<?= h(t('resetup.panel.3.next_btn')) ?>';
    }
}

// ── STEP 4 : Sudo ──────────────────────────────────────────────────────────────

async function runStep4SudoTest() {
    const btn    = document.getElementById('btn-test-sudo');
    const lbl    = document.getElementById('btn-sudo-label');
    const spin   = document.getElementById('btn-sudo-spinner');
    const errBox = document.getElementById('step4-error');

    errBox.style.display = 'none';
    btn.disabled         = true;
    lbl.textContent      = '<?= h(t('resetup.js.testing')) ?>';
    spin.style.display   = '';

    try {
        const r = await apiCall('step4_test_sudo_nopass');

        if (r.success && r.sudo_ok) {
            // Sudo NOPASSWD available
            document.getElementById('step4-result').style.display = '';
            document.getElementById('step4-ok-badge').style.display = '';
            document.getElementById('btn-next-4').disabled = false;
            lbl.textContent = '<?= h(t('resetup.js.sudo_nopass_ok')) ?>';
        } else {
            // Show password section
            lbl.textContent = '<?= h(t('resetup.js.sudo_nopass_fail')) ?>';
            document.getElementById('sudo-pass-section').style.display = '';
            btn.disabled = false;
        }
    } catch (e) {
        errBox.textContent   = '<?= h(t('resetup.js.error_prefix')) ?>' + e.message;
        errBox.style.display = 'block';
        btn.disabled         = false;
        lbl.textContent      = '<?= h(t('resetup.panel.4.test_nopass_btn')) ?>';
    } finally {
        spin.style.display = 'none';
    }
}

async function runStep4SudoWithPass() {
    // The password is read once, sent to the API, then cleared
    const passwordInput = document.getElementById('sudo-password-input');
    const sudoPassword  = passwordInput.value; // local variable only

    const btn    = document.getElementById('btn-sudo-withpass');
    const lbl    = document.getElementById('btn-sudopass-label');
    const spin   = document.getElementById('btn-sudopass-spinner');
    const errBox = document.getElementById('step4-error');

    if (!sudoPassword) {
        errBox.textContent   = '<?= h(t('resetup.js.sudo_pass_required')) ?>';
        errBox.style.display = 'block';
        return;
    }

    errBox.style.display = 'none';
    btn.disabled         = true;
    lbl.textContent      = '<?= h(t('resetup.js.verifying')) ?>';
    spin.style.display   = '';

    try {
        const r = await apiCall('step4_test_sudo_withpass', {
            sudo_password: sudoPassword, // sent only once
        });

        // Immediate clearing of the password from the field and local scope
        passwordInput.value = '';
        // sudoPassword is a local variable — it goes out of scope here

        if (r.success && r.sudo_ok) {
            document.getElementById('sudo-pass-section').style.display = 'none';
            document.getElementById('step4-result').style.display = '';
            document.getElementById('step4-ok-badge').style.display = '';
            document.getElementById('btn-next-4').disabled = false;
            lbl.textContent = '<?= h(t('resetup.js.sudo_pass_ok')) ?>';
        } else {
            errBox.textContent   = r.error || '<?= h(t('resetup.js.sudo_pass_wrong')) ?>';
            errBox.style.display = 'block';
            btn.disabled         = false;
            lbl.textContent      = '<?= h(t('resetup.panel.4.test_withpass_btn')) ?>';
        }
    } catch (e) {
        passwordInput.value  = '';
        errBox.textContent   = '<?= h(t('resetup.js.error_prefix')) ?>' + e.message;
        errBox.style.display = 'block';
        btn.disabled         = false;
        lbl.textContent      = '<?= h(t('resetup.panel.4.test_withpass_btn')) ?>';
    } finally {
        spin.style.display = 'none';
    }
}

async function showManualCommands() {
    document.getElementById('sudo-manual-section').style.display = '';
    const block   = document.getElementById('manual-commands-block');
    const spinner = document.getElementById('manual-commands-spinner');

    spinner.style.display = '';
    block.innerHTML       = '';

    try {
        const r = await apiCall('get_manual_commands');

        if (!r.success) throw new Error(r.error || '<?= h(t('common.error')) ?>');

        const lines = r.commands || [];
        block.innerHTML = lines.map(l => `<code>${escHtml(l)}</code>`).join('');

    } catch (e) {
        block.innerHTML = `<span style="color:var(--red)"><?= h(t('resetup.js.error_prefix')) ?>${escHtml(e.message)}</span>`;
    }
}

// ── STEP 5 : Permissions ───────────────────────────────────────────────────────

async function runStep5Permissions() {
    const btn    = document.getElementById('btn-apply-perms');
    const lbl    = document.getElementById('btn-perms-label');
    const spin   = document.getElementById('btn-perms-spinner');
    const log    = document.getElementById('step5-log');
    const errBox = document.getElementById('step5-error');

    errBox.style.display = 'none';
    btn.disabled         = true;
    lbl.textContent      = '<?= h(t('resetup.js.applying')) ?>';
    spin.style.display   = '';
    log.style.display    = '';
    log.innerHTML        = '';

    try {
        const r = await apiCall('step5_apply_permissions');

        if (!r.success) {
            throw new Error(r.error || '<?= h(t('resetup.js.perms_failed')) ?>');
        }

        const lines = r.log || [];
        lines.forEach(line => {
            const div = document.createElement('div');
            div.className = 'resetup-log-line ' + (line.type || 'ok');
            div.textContent = line.message || line;
            log.appendChild(div);
        });

        lbl.textContent = '<?= h(t('resetup.js.perms_done')) ?>';
        document.getElementById('btn-next-5').disabled = false;

    } catch (e) {
        errBox.textContent   = '<?= h(t('resetup.js.error_prefix')) ?>' + e.message;
        errBox.style.display = 'block';
        btn.disabled         = false;
        lbl.textContent      = '<?= h(t('resetup.panel.5.apply_btn')) ?>';
    } finally {
        spin.style.display = 'none';
    }
}

// ── STEP 6 : Worker ────────────────────────────────────────────────────────────

async function fetchStep6WorkerStatus() {
    try {
        const r = await apiCall('step6_get_status');
        const badge = document.getElementById('step6-worker-status');
        if (badge && r.status) {
            badge.textContent = r.status.running ? '<?= h(t('resetup.js.worker_running')) ?>' : '<?= h(t('resetup.js.worker_stopped')) ?>';
            badge.className   = 'badge ' + (r.status.running ? 'badge-green' : 'badge-yellow');
        }
    } catch (_) { /* silent */ }
}

async function runStep6Worker() {
    const btn    = document.getElementById('btn-apply-worker');
    const lbl    = document.getElementById('btn-worker-label');
    const spin   = document.getElementById('btn-worker-spinner');
    const log    = document.getElementById('step6-log');
    const errBox = document.getElementById('step6-error');

    errBox.style.display = 'none';
    btn.disabled         = true;
    lbl.textContent      = '<?= h(t('resetup.js.reconfig_running')) ?>';
    spin.style.display   = '';
    log.style.display    = '';
    log.innerHTML        = '';

    try {
        const r = await apiCall('step6_apply_worker');

        if (!r.success) {
            throw new Error(r.error || '<?= h(t('resetup.js.worker_failed')) ?>');
        }

        const lines = r.log || [];
        lines.forEach(line => {
            const div = document.createElement('div');
            div.className = 'resetup-log-line ' + (line.type || 'ok');
            div.textContent = line.message || line;
            log.appendChild(div);
        });

        lbl.textContent = '<?= h(t('resetup.js.worker_done')) ?>';
        document.getElementById('btn-next-6').disabled = false;

        // Update the status badge
        const badge = document.getElementById('step6-worker-status');
        if (badge) {
            badge.textContent = '<?= h(t('resetup.js.worker_restarted')) ?>';
            badge.className   = 'badge badge-green';
        }

    } catch (e) {
        errBox.textContent   = '<?= h(t('resetup.js.error_prefix')) ?>' + e.message;
        errBox.style.display = 'block';
        btn.disabled         = false;
        lbl.textContent      = '<?= h(t('resetup.panel.6.apply_btn')) ?>';
    } finally {
        spin.style.display = 'none';
    }
}

// ── STEP 7 : Secreatet agent ──────────────────────────────────────────────────────

async function runStep7Agent() {
    const btn    = document.getElementById('btn-apply-agent');
    const lbl    = document.getElementById('btn-agent-label');
    const spin   = document.getElementById('btn-agent-spinner');
    const log    = document.getElementById('step7-log');
    const errBox = document.getElementById('step7-error');

    errBox.style.display = 'none';
    btn.disabled         = true;
    lbl.textContent      = '<?= h(t('resetup.js.checking')) ?>';
    spin.style.display   = '';
    log.style.display    = '';
    log.innerHTML        = '';

    try {
        const r = await apiCall('step7_apply_agent');

        if (!r.success) {
            throw new Error(r.error || '<?= h(t('resetup.js.socket_failed')) ?>');
        }

        const lines = r.log || [];
        lines.forEach(line => {
            const div = document.createElement('div');
            div.className = 'resetup-log-line ' + (line.type || 'ok');
            div.textContent = line.message || line;
            log.appendChild(div);
        });

        // Update status badges
        const statusBadge = document.getElementById('step7-socket-status');
        if (statusBadge) {
            statusBadge.textContent = r.socket_ok ? '<?= h(t('resetup.js.socket_active')) ?>' : '<?= h(t('resetup.js.socket_inactive')) ?>';
            statusBadge.className   = 'badge ' + (r.socket_ok ? 'badge-green' : 'badge-red');
        }

        const pathEl = document.getElementById('step7-socket-path');
        if (pathEl && r.socket_path) {
            pathEl.textContent = r.socket_path;
        }

        // Show the final summary
        const summary     = document.getElementById('step7-summary');
        const summaryBody = document.getElementById('step7-summary-body');

        summaryBody.innerHTML = `
            <ul style="margin:0;padding-left:18px">
                <li><?= h(t('resetup.js.summary_fpm_user')) ?> <strong>${escHtml(wizardData.fpmUser)}</strong></li>
                <li><?= h(t('resetup.js.summary_group')) ?> <strong>${escHtml(wizardData.fpmGroup)}</strong></li>
                <li><?= h(t('resetup.js.summary_worker_mode')) ?> <strong>${escHtml(wizardData.workerMode)}</strong></li>
                <li><?= h(t('resetup.js.summary_socket')) ?> <strong>${r.socket_ok ? '<?= h(t('resetup.js.socket_ok')) ?>' : '<?= h(t('resetup.js.socket_check_manual')) ?>'}</strong></li>
            </ul>
        `;

        summary.style.display = '';
        lbl.textContent = '<?= h(t('resetup.js.socket_done')) ?>';

    } catch (e) {
        errBox.textContent   = '<?= h(t('resetup.js.error_prefix')) ?>' + e.message;
        errBox.style.display = 'block';
        btn.disabled         = false;
        lbl.textContent      = '<?= h(t('resetup.panel.7.verify_btn')) ?>';
    } finally {
        spin.style.display = 'none';
    }
}

// ── cancellation ─────────────────────────────────────────────────────────────────

async function cancelResetup() {
    if (!confirm('<?= h(t('resetup.js.cancel_confirm')) ?>')) {
        return;
    }

    try {
        await apiCall('cancel');
    } catch (_) { /* silent — we still redirect */ }

    window.location.href = '<?= routePath('/settings.php') ?>';
}

// ── XSS utility ───────────────────────────────────────────────────────────────

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}

// ── Init: if already confirmed, start at step 1 ──────────────────────────────
<?php if ($alreadyConfirmed): ?>
goToStep(1);
<?php endif; ?>

window.confirmAndStartResetup = confirmAndStartResetup;
window.goToStep = goToStep;
window.validateStep3 = validateStep3;
window.saveStep3Selection = saveStep3Selection;
window.runStep1Auth = runStep1Auth;
window.runStep2Diagnostic = runStep2Diagnostic;
window.runStep4SudoTest = runStep4SudoTest;
window.runStep4SudoWithPass = runStep4SudoWithPass;
window.runStep5Permissions = runStep5Permissions;
window.runStep6Worker = runStep6Worker;
window.runStep7Agent = runStep7Agent;
window.showManualCommands = showManualCommands;
window.cancelResetup = cancelResetup;
</script>

<?php require_once 'layout_bottom.php'; ?>
