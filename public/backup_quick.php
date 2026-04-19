<?php
require_once __DIR__ . '/../src/bootstrap.php';
RemoteBackupQuickFlow::requireManagePermissions();

$context = RemoteBackupQuickFlow::wizardContext();
$requestedTemplate = trim((string) ($_GET['template'] ?? ''));
$initialPayload = RemoteBackupQuickFlow::normalizePayload([
    'template_ref' => $requestedTemplate !== '' ? $requestedTemplate : ($context['default_template_ref'] ?? QuickBackupTemplateManager::defaultReference()),
    'repo_password_source' => $context['secret_storage_default'] ?? SecretStore::defaultWritableSource(),
    'host_mode' => 'create',
    'repo_mode' => 'create',
    'key_mode' => 'generate',
    'init_repo_if_missing' => true,
    'run_after_create' => true,
]);

$title = t('topbar.quick_backup_label');
$active = 'backup_quick';
$actions = '<a href="' . routePath('/backup_templates.php') . '" class="btn">Templates</a>';

$preflightUrl = routePath('/api/quick_backup_preflight.php');
$createUrl = routePath('/api/quick_backup_create.php');
$pollCreateUrl = routePath('/api/poll_quick_backup_log.php');
$jobsUrl = routePath('/backup_jobs.php');
$reposUrl = routePath('/repos.php');
$hostsUrl = routePath('/hosts.php');
$keysUrl = routePath('/sshkeys.php');

include 'layout_top.php';
?>

<style<?= cspNonceAttr() ?>>
/* ─── Layout ──────────────────────────────────────────────────────────────── */
.quick-shell{display:grid;grid-template-columns:272px minmax(0,1fr);gap:24px;align-items:start}
.quick-sidebar{position:sticky;top:16px}
.quick-sidebar .card,.quick-main .card{box-shadow:0 4px 24px rgba(0,0,0,.1)}

/* ─── Intro card ───────────────────────────────────────────────────────────── */
.quick-intro-card{margin-bottom:22px;overflow:hidden;border:1px solid color-mix(in srgb,var(--accent) 20%,var(--border));background:linear-gradient(135deg,color-mix(in srgb,var(--accent) 8%,var(--bg2)) 0%,var(--bg2) 60%)}
.quick-intro{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.quick-intro-icon{width:48px;height:48px;border-radius:14px;background:color-mix(in srgb,var(--accent) 14%,var(--bg3));border:1px solid color-mix(in srgb,var(--accent) 28%,var(--border));display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent)}
.quick-intro-copy{flex:1;min-width:0}
.quick-eyebrow{display:inline-flex;align-items:center;gap:6px;margin-bottom:6px;color:var(--accent);font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.quick-intro-title{font-size:22px;line-height:1.2;font-weight:700;margin-bottom:4px;color:var(--text)}
.quick-intro-text{color:var(--text2);font-size:13px;line-height:1.6}
.quick-intro-pills{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.quick-intro-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:color-mix(in srgb,var(--bg3) 80%,transparent);border:1px solid var(--border);font-size:12px;color:var(--text2)}
.quick-intro-pill svg{width:12px;height:12px;opacity:.7}

/* ─── Sidebar steps ─────────────────────────────────────────────────────────── */
.quick-sidebar-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px 0}
.quick-sidebar-title{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text2)}
.quick-progress-wrap{padding:12px 16px 4px}
.quick-progress-track{height:4px;border-radius:4px;background:var(--border);overflow:hidden}
.quick-progress-fill{height:100%;border-radius:4px;background:var(--accent2);transition:width .35s ease}
.quick-steps{display:grid;gap:2px;padding:6px 8px 12px}
.quick-step{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;border:1px solid transparent;cursor:pointer;width:100%;text-align:left;color:var(--text);font:inherit;appearance:none;-webkit-appearance:none;transition:background .15s,border-color .15s,color .15s;background:transparent}
.quick-step:hover{background:color-mix(in srgb,var(--accent) 5%,var(--bg2));border-color:color-mix(in srgb,var(--accent) 14%,var(--border))}
.quick-step.active{background:color-mix(in srgb,var(--accent) 9%,var(--bg2));border-color:color-mix(in srgb,var(--accent) 45%,var(--border))}
.quick-step.done{background:transparent}
.quick-step-num{width:26px;height:26px;border-radius:999px;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;color:var(--text2);background:var(--bg3);transition:all .2s}
.quick-step.active .quick-step-num{border-color:var(--accent);background:color-mix(in srgb,var(--accent) 16%,var(--bg3));color:var(--accent)}
.quick-step.done .quick-step-num{border-color:rgba(63,185,80,.5);background:rgba(63,185,80,.12);color:var(--green);font-size:13px}
.quick-step-text{display:grid;gap:1px;min-width:0}
.quick-step-label{font-size:13px;font-weight:600;color:var(--text);line-height:1.3}
.quick-step.done .quick-step-label{color:var(--text2)}
.quick-step.active .quick-step-label{color:var(--accent)}
.quick-step-hint{font-size:11px;color:var(--text2);line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ─── Panel ─────────────────────────────────────────────────────────────────── */
.quick-panel{display:none}
.quick-panel.active{display:block}
.quick-panel-head{display:flex;align-items:flex-start;gap:14px;padding:20px 22px 16px;border-bottom:1px solid var(--border)}
.quick-panel-step-badge{width:36px;height:36px;border-radius:10px;background:color-mix(in srgb,var(--accent) 10%,var(--bg3));border:1px solid color-mix(in srgb,var(--accent) 20%,var(--border));display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent)}
.quick-panel-step-badge svg{width:18px;height:18px}
.quick-panel-head-copy{min-width:0}
.quick-panel-title{font-size:15px;font-weight:700;color:var(--text);margin-bottom:3px;line-height:1.3}
.quick-panel-copy{color:var(--text2);font-size:13px;line-height:1.55;max-width:64ch}
.quick-card-body{padding:20px 22px}

/* ─── Section labels ────────────────────────────────────────────────────────── */
.quick-section-sep{display:flex;align-items:center;gap:8px;margin:18px 0 12px;color:var(--text2)}
.quick-section-sep::before,.quick-section-sep::after{content:'';flex:1;height:1px;background:var(--border)}
.quick-section-sep-label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap}
.quick-section-label{margin:4px 0 10px;color:var(--text2);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}

/* ─── Grid ──────────────────────────────────────────────────────────────────── */
.quick-grid{display:grid;gap:14px}
.quick-grid.two{grid-template-columns:repeat(2,minmax(0,1fr))}
.quick-grid.three{grid-template-columns:repeat(3,minmax(0,1fr))}
.quick-grid.auto{grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}

/* ─── Template cards ─────────────────────────────────────────────────────────── */
.quick-template{border:1px solid var(--border);border-radius:14px;padding:16px;background:var(--bg2);cursor:pointer;color:var(--text);width:100%;text-align:left;font:inherit;appearance:none;-webkit-appearance:none;transition:border-color .15s,background .15s,box-shadow .15s,transform .15s;min-height:100%}
.quick-template:hover{border-color:color-mix(in srgb,var(--accent) 28%,var(--border));background:color-mix(in srgb,var(--accent) 3%,var(--bg2));transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.1)}
.quick-template.active{border-color:var(--accent);background:color-mix(in srgb,var(--accent) 6%,var(--bg2));box-shadow:0 0 0 1px color-mix(in srgb,var(--accent) 35%,transparent),0 8px 24px rgba(0,0,0,.12)}
.quick-template-icon{width:36px;height:36px;border-radius:9px;background:color-mix(in srgb,var(--bg3) 80%,transparent);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin-bottom:12px;color:var(--text2);transition:background .15s,border-color .15s}
.quick-template.active .quick-template-icon{background:color-mix(in srgb,var(--accent) 12%,var(--bg3));border-color:color-mix(in srgb,var(--accent) 30%,var(--border));color:var(--accent)}
.quick-template-name{font-weight:700;color:var(--text);font-size:14px;line-height:1.3;margin-bottom:6px}
.quick-template-desc{font-size:12px;color:var(--text2);margin-bottom:10px;min-height:36px;line-height:1.5}
.quick-template-meta{display:flex;flex-wrap:wrap;gap:5px}

/* ─── Choice cards ───────────────────────────────────────────────────────────── */
.quick-choice{display:flex;gap:10px;align-items:flex-start;padding:13px 14px;border:1px solid var(--border);border-radius:12px;background:var(--bg2);transition:border-color .15s,background .15s,box-shadow .15s;cursor:pointer}
.quick-choice:hover{border-color:color-mix(in srgb,var(--accent) 24%,var(--border))}
.quick-choice.selected{border-color:color-mix(in srgb,var(--accent) 60%,var(--border));background:color-mix(in srgb,var(--accent) 5%,var(--bg2));box-shadow:0 0 0 1px color-mix(in srgb,var(--accent) 18%,transparent)}
.quick-choice input{margin-top:3px;accent-color:var(--accent2);flex-shrink:0}
.quick-choice-title{font-weight:600;color:var(--text);margin-bottom:2px;font-size:13px}
.quick-choice-desc{font-size:12px;color:var(--text2);line-height:1.5}

/* ─── Form helpers ───────────────────────────────────────────────────────────── */
.form-help{font-size:12px;color:var(--text2);margin-top:4px;line-height:1.5}
.quick-inline-note{font-size:12px;color:var(--text2);line-height:1.5}
.quick-inline-note strong{color:var(--text)}

/* ─── Row actions ────────────────────────────────────────────────────────────── */
.quick-row-actions{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:22px;padding-top:16px;border-top:1px solid color-mix(in srgb,var(--border) 65%,transparent)}
.quick-row-actions .flex{align-items:center;flex-wrap:wrap}

/* ─── Preflight checks ───────────────────────────────────────────────────────── */
.quick-result-box{display:grid;gap:10px}
.quick-check{display:flex;gap:12px;padding:13px 14px;border-radius:12px;border:1px solid var(--border);border-left-width:3px;background:var(--bg2)}
.quick-check.success{border-left-color:var(--green)}
.quick-check.warning{border-left-color:var(--yellow)}
.quick-check.error{border-left-color:var(--red)}
.quick-check.info{border-left-color:var(--accent)}
.quick-check-icon{width:22px;height:22px;flex-shrink:0;margin-top:1px}
.quick-check-icon svg{width:100%;height:100%}
.quick-check-body{flex:1;min-width:0}
.quick-check-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap;margin-bottom:4px}
.quick-check-title{font-weight:700;color:var(--text);font-size:13px}
.quick-check-message{color:var(--text2);line-height:1.55;font-size:13px}

/* ─── Summary ────────────────────────────────────────────────────────────────── */
.quick-summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}
.quick-summary-item{padding:13px 14px;border-radius:12px;border:1px solid var(--border);background:var(--bg2)}
.quick-summary-label{margin-bottom:5px;color:var(--text2);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.quick-summary-value{color:var(--text);font-weight:600;line-height:1.55;white-space:pre-wrap;word-break:break-word;font-size:13px}

/* ─── Code / log ─────────────────────────────────────────────────────────────── */
.quick-code{white-space:pre-wrap;word-break:break-word;font-family:var(--font-mono,ui-monospace,SFMono-Regular,Consolas,monospace);font-size:12px;background:color-mix(in srgb,var(--bg3) 72%,transparent);padding:12px;border-radius:10px;border:1px solid var(--border)}
.quick-badges{display:flex;gap:8px;flex-wrap:wrap}
.quick-detail-block{margin-top:16px}
.quick-detail-title{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text2);margin-bottom:7px}
.quick-live-log{min-height:200px;max-height:400px;overflow:auto;font-size:12px;line-height:1.6}

/* ─── Finish screaten ──────────────────────────────────────────────────────────── */
.quick-status-stack{display:grid;gap:10px}
.quick-created-items{display:flex;flex-wrap:wrap;gap:8px;margin-top:4px}
.quick-created-item{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;background:color-mix(in srgb,var(--accent) 10%,var(--bg3));border:1px solid color-mix(in srgb,var(--accent) 22%,var(--border));font-size:12px;font-weight:600;color:var(--accent)}
.quick-created-item svg{width:13px;height:13px}

/* ─── History ────────────────────────────────────────────────────────────────── */
.quick-history-list{display:grid;gap:8px}
.quick-history-item{border:1px solid var(--border);border-radius:10px;background:var(--bg2);overflow:hidden}
.quick-history-item summary{list-style:none;cursor:pointer;padding:11px 13px;display:flex;justify-content:space-between;align-items:center;gap:10px}
.quick-history-item summary::-webkit-details-marker{display:none}
.quick-history-meta{display:grid;gap:3px;min-width:0}
.quick-history-title{font-weight:600;color:var(--text);font-size:13px}
.quick-history-sub{font-size:11px;color:var(--text2)}
.quick-history-item .quick-code{margin:0 12px 12px}

/* ─── Password row ───────────────────────────────────────────────────────────── */
.quick-password-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center}
.quick-password-wrap{position:relative}
.quick-password-wrap .form-control{padding-right:42px}
.quick-password-toggle{position:absolute;top:50%;right:8px;transform:translateY(-50%);border:0;background:transparent;color:var(--text2);width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:color .15s,background .15s}
.quick-password-toggle:hover{color:var(--text);background:color-mix(in srgb,var(--accent) 10%,transparent)}
.quick-password-toggle svg{width:16px;height:16px}

/* ─── Responsive ─────────────────────────────────────────────────────────────── */
@media (max-width:1060px){
    .quick-shell{grid-template-columns:1fr}
    .quick-sidebar{position:static}
    .quick-steps{grid-template-columns:repeat(auto-fit,minmax(160px,1fr));padding:6px}
    .quick-step-hint{display:none}
}
@media (max-width:720px){
    .quick-card-body{padding:16px}
    .quick-grid.two,.quick-grid.three{grid-template-columns:1fr}
    .quick-password-row{grid-template-columns:1fr}
    .quick-intro-title{font-size:20px}
    .quick-steps{display:flex;overflow-x:auto;padding-bottom:4px;scrollbar-width:thin;gap:4px}
    .quick-step{min-width:120px}
    .quick-row-actions{flex-direction:column}
    .quick-row-actions > *{width:100%}
    .quick-row-actions .flex{justify-content:flex-start}
    .quick-panel-head{padding:16px}
}
</style>

<div class="card quick-intro-card">
    <div class="card-body quick-intro">
        <div class="quick-intro-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <div class="quick-intro-copy">
            <div class="quick-eyebrow"><?= h(t('quick_backup.intro.eyebrow')) ?></div>
            <div class="quick-intro-title"><?= h(t('quick_backup.intro.title')) ?></div>
            <div class="quick-intro-text"><?= h(t('quick_backup.intro.text')) ?></div>
            <div class="quick-intro-pills">
                <span class="quick-intro-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg> <?= h(t('quick_backup.intro.pill_ssh')) ?></span>
                <span class="quick-intro-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg> <?= h(t('quick_backup.intro.pill_source_host')) ?></span>
                <span class="quick-intro-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/></svg> <?= h(t('quick_backup.intro.pill_repo')) ?></span>
                <span class="quick-intro-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> <?= h(t('quick_backup.intro.pill_job')) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="quick-shell">
    <aside class="quick-sidebar">
        <div class="card">
            <div class="quick-sidebar-header">
                <span class="quick-sidebar-title"><?= h(t('quick_backup.sidebar.progress')) ?></span>
                <span id="quick-progress-badge" style="font-size:11px;color:var(--text2);font-weight:600"><?= h(t('quick_backup.sidebar.initial_progress')) ?></span>
            </div>
            <div class="quick-progress-wrap">
                <div class="quick-progress-track"><div class="quick-progress-fill" id="quick-progress-fill" style="width:12.5%"></div></div>
            </div>
            <div class="quick-steps" id="quick-steps">
                <button type="button" class="quick-step" data-step="0"><span class="quick-step-num">1</span><span class="quick-step-text"><span class="quick-step-label"><?= h(t('quick_backup.step.template.label')) ?></span><span class="quick-step-hint"><?= h(t('quick_backup.step.template.hint')) ?></span></span></button>
                <button type="button" class="quick-step" data-step="1"><span class="quick-step-num">2</span><span class="quick-step-text"><span class="quick-step-label"><?= h(t('quick_backup.step.source_host.label')) ?></span><span class="quick-step-hint"><?= h(t('quick_backup.step.source_host.hint')) ?></span></span></button>
                <button type="button" class="quick-step" data-step="2"><span class="quick-step-num">3</span><span class="quick-step-text"><span class="quick-step-label"><?= h(t('quick_backup.step.content.label')) ?></span><span class="quick-step-hint"><?= h(t('quick_backup.step.content.hint')) ?></span></span></button>
                <button type="button" class="quick-step" data-step="3"><span class="quick-step-num">4</span><span class="quick-step-text"><span class="quick-step-label"><?= h(t('quick_backup.step.target_repo.label')) ?></span><span class="quick-step-hint"><?= h(t('quick_backup.step.target_repo.hint')) ?></span></span></button>
                <button type="button" class="quick-step" data-step="4"><span class="quick-step-num">5</span><span class="quick-step-text"><span class="quick-step-label"><?= h(t('quick_backup.step.schedule.label')) ?></span><span class="quick-step-hint"><?= h(t('quick_backup.step.schedule.hint')) ?></span></span></button>
                <button type="button" class="quick-step" data-step="5"><span class="quick-step-num">6</span><span class="quick-step-text"><span class="quick-step-label"><?= h(t('quick_backup.step.check.label')) ?></span><span class="quick-step-hint"><?= h(t('quick_backup.step.check.hint')) ?></span></span></button>
                <button type="button" class="quick-step" data-step="6"><span class="quick-step-num">7</span><span class="quick-step-text"><span class="quick-step-label"><?= h(t('quick_backup.step.summary.label')) ?></span><span class="quick-step-hint"><?= h(t('quick_backup.step.summary.hint')) ?></span></span></button>
                <button type="button" class="quick-step" data-step="7"><span class="quick-step-num">8</span><span class="quick-step-text"><span class="quick-step-label"><?= h(t('quick_backup.step.done.label')) ?></span><span class="quick-step-hint"><?= h(t('quick_backup.step.done.hint')) ?></span></span></button>
            </div>
        </div>
    </aside>

    <section class="quick-main">
        <div class="quick-panel active" data-panel="0">
            <div class="card">
                <div class="quick-panel-head">
                    <div class="quick-panel-step-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></div>
                    <div class="quick-panel-head-copy">
                        <div class="quick-panel-title"><?= h(t('quick_backup.panel.template.title')) ?></div>
                        <div class="quick-panel-copy"><?= h(t('quick_backup.panel.template.copy')) ?></div>
                    </div>
                </div>
                <div class="quick-card-body">
                    <div class="quick-grid auto" id="template-grid"></div>
                    <div class="quick-row-actions">
                        <div class="quick-inline-note"><?= h(t('quick_backup.panel.template.note')) ?></div>
                        <div class="flex gap-2">
                            <a class="btn" href="<?= h($jobsUrl) ?>"><?= h(t('common.cancel')) ?></a>
                            <button type="button" class="btn btn-primary" id="btn-template-next"><?= h(t('common.continue')) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-panel" data-panel="1">
            <div class="card">
                <div class="quick-panel-head">
                    <div class="quick-panel-step-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></div>
                    <div class="quick-panel-head-copy">
                        <div class="quick-panel-title"><?= h(t('quick_backup.panel.source.title')) ?></div>
                        <div class="quick-panel-copy"><?= h(t('quick_backup.panel.source.copy')) ?></div>
                    </div>
                </div>
                <div class="quick-card-body">

                    <div class="quick-section-sep"><span class="quick-section-sep-label"><?= h(t('quick_backup.panel.source.connection_mode')) ?></span></div>
                    <div class="quick-grid two" style="margin-bottom:14px">
                        <label class="quick-choice">
                            <input type="radio" name="host_mode" value="create" checked>
                            <span><div class="quick-choice-title"><?= h(t('quick_backup.panel.source.new_machine')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.panel.source.new_machine_desc')) ?></div></span>
                        </label>
                        <label class="quick-choice">
                            <input type="radio" name="host_mode" value="existing">
                            <span><div class="quick-choice-title"><?= h(t('quick_backup.panel.source.existing_host')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.panel.source.existing_host_desc')) ?></div></span>
                        </label>
                    </div>

                    <div id="host-existing-box" style="display:none;margin-bottom:14px">
                        <div class="form-group">
                            <label class="form-label"><?= h(t('quick_backup.panel.source.existing_host')) ?></label>
                            <select class="form-control" id="existing_host_id"></select>
                        </div>
                    </div>

                    <div id="host-create-box">
                        <div class="quick-section-sep"><span class="quick-section-sep-label"><?= h(t('quick_backup.panel.source.machine_identity')) ?></span></div>
                        <div class="quick-grid two">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.host_name')) ?></label>
                                <input type="text" class="form-control" id="host_name" placeholder="web-prod-01">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.hostname')) ?></label>
                                <input type="text" class="form-control" id="hostname" placeholder="web-prod-01.example">
                            </div>
                        </div>
                        <div class="quick-grid three">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.ssh_user')) ?></label>
                                <input type="text" class="form-control" id="user" placeholder="root">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= h(t('common.port')) ?></label>
                                <input type="number" class="form-control" id="port" min="1" max="65535">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.key_name')) ?></label>
                                <input type="text" class="form-control" id="key_name" placeholder="host-quick">
                            </div>
                        </div>

                        <div class="quick-section-sep"><span class="quick-section-sep-label"><?= h(t('quick_backup.form.ssh_key')) ?></span></div>
                        <div class="quick-grid three" style="margin-bottom:14px">
                            <label class="quick-choice">
                                <input type="radio" name="key_mode" value="generate" checked>
                                <span><div class="quick-choice-title"><?= h(t('quick_backup.panel.source.key_generate')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.panel.source.key_generate_desc')) ?></div></span>
                            </label>
                            <label class="quick-choice">
                                <input type="radio" name="key_mode" value="existing">
                                <span><div class="quick-choice-title"><?= h(t('quick_backup.panel.source.key_existing')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.panel.source.key_existing_desc')) ?></div></span>
                            </label>
                            <label class="quick-choice">
                                <input type="radio" name="key_mode" value="import">
                                <span><div class="quick-choice-title"><?= h(t('quick_backup.panel.source.key_import')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.panel.source.key_import_desc')) ?></div></span>
                            </label>
                        </div>

                        <div id="key-existing-box" style="display:none">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.panel.source.existing_ssh_key')) ?></label>
                                <select class="form-control" id="existing_key_id"></select>
                            </div>
                        </div>
                        <div id="key-import-box" style="display:none">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.private_key')) ?></label>
                                <textarea class="form-control" id="private_key" rows="8" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?= h(t('quick_backup.form.deploy_password')) ?> <span style="color:var(--text2);font-weight:400">(<?= h(t('common.optional')) ?>)</span></label>
                            <input type="password" class="form-control" id="deploy_password" autocomplete="new-password">
                            <div class="quick-inline-note"><?= h(t('quick_backup.form.deploy_password_note')) ?></div>
                        </div>
                    </div>

                    <div class="quick-row-actions">
                        <div class="flex gap-2">
                            <a class="btn" href="<?= h($hostsUrl) ?>"><?= h(t('quick_backup.actions.view_hosts')) ?></a>
                            <a class="btn" href="<?= h($keysUrl) ?>"><?= h(t('quick_backup.actions.view_keys')) ?></a>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn" data-prev><?= h(t('common.back')) ?></button>
                            <button type="button" class="btn btn-primary" data-next><?= h(t('common.continue')) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-panel" data-panel="2">
            <div class="card">
                <div class="quick-panel-head">
                    <div class="quick-panel-step-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h3l2 2h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div>
                    <div class="quick-panel-head-copy">
                        <div class="quick-panel-title"><?= h(t('quick_backup.panel.content.title')) ?></div>
                        <div class="quick-panel-copy"><?= h(t('quick_backup.panel.content.copy')) ?></div>
                    </div>
                </div>
                <div class="quick-card-body">
                    <div class="quick-grid two">
                        <div class="form-group">
                            <label class="form-label"><?= h(t('quick_backup.form.source_paths')) ?></label>
                            <textarea class="form-control mono" id="source_paths" rows="8" placeholder="/etc&#10;/var/www"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= h(t('quick_backup.form.excludes')) ?></label>
                            <textarea class="form-control mono" id="excludes" rows="8" placeholder="*.log&#10;node_modules"></textarea>
                        </div>
                    </div>
                    <div class="quick-grid two">
                        <div class="form-group">
                            <label class="form-label"><?= h(t('common.tags')) ?></label>
                            <input type="text" class="form-control" id="tags" placeholder="web, prod">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= h(t('quick_backup.form.job_name')) ?></label>
                            <input type="text" class="form-control" id="job_name" placeholder="<?= h(t('quick_backup.form.job_name_placeholder')) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= h(t('quick_backup.form.job_description')) ?></label>
                        <input type="text" class="form-control" id="job_description" placeholder="<?= h(t('quick_backup.form.job_description_placeholder')) ?>">
                    </div>
                    <div class="quick-row-actions">
                        <div class="quick-inline-note"><?= h(t('quick_backup.panel.content.note')) ?></div>
                        <div class="flex gap-2">
                            <button type="button" class="btn" data-prev><?= h(t('common.back')) ?></button>
                            <button type="button" class="btn btn-primary" data-next><?= h(t('common.continue')) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-panel" data-panel="3">
            <div class="card">
                <div class="quick-panel-head">
                    <div class="quick-panel-step-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/></svg></div>
                    <div class="quick-panel-head-copy">
                        <div class="quick-panel-title"><?= h(t('quick_backup.panel.repo.title')) ?></div>
                        <div class="quick-panel-copy"><?= h(t('quick_backup.panel.repo.copy')) ?></div>
                    </div>
                </div>
                <div class="quick-card-body">

                    <div class="quick-section-sep"><span class="quick-section-sep-label"><?= h(t('quick_backup.panel.repo.mode')) ?></span></div>
                    <div class="quick-grid two" style="margin-bottom:14px">
                        <label class="quick-choice">
                            <input type="radio" name="repo_mode" value="create" checked>
                            <span><div class="quick-choice-title"><?= h(t('quick_backup.panel.repo.create')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.panel.repo.create_desc')) ?></div></span>
                        </label>
                        <label class="quick-choice">
                            <input type="radio" name="repo_mode" value="existing">
                            <span><div class="quick-choice-title"><?= h(t('quick_backup.panel.repo.existing')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.panel.repo.existing_desc')) ?></div></span>
                        </label>
                    </div>

                    <div id="repo-existing-box" style="display:none;margin-bottom:14px">
                        <div class="form-group">
                            <label class="form-label"><?= h(t('quick_backup.panel.repo.existing')) ?></label>
                            <select class="form-control" id="existing_repo_id"></select>
                        </div>
                    </div>

                    <div id="repo-create-box">
                        <div class="quick-grid two">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.repo_name')) ?></label>
                                <input type="text" class="form-control" id="repo_name" placeholder="web-prod-01">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.repo_path')) ?></label>
                                <input type="text" class="form-control mono" id="repo_path" placeholder="<?= h(t('quick_backup.form.repo_path_placeholder')) ?>">
                            </div>
                        </div>
                        <div class="quick-grid two">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.remote_repo_path')) ?></label>
                                <input type="text" class="form-control mono" id="remote_repo_path" placeholder="<?= h(t('quick_backup.form.remote_repo_path_placeholder')) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.hostname_override')) ?> (<?= h(t('common.optional')) ?>)</label>
                                <input type="text" class="form-control" id="hostname_override" placeholder="nom-visible-dans-restic">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= h(t('quick_backup.form.repo_description')) ?></label>
                            <input type="text" class="form-control" id="repo_description" placeholder="<?= h(t('quick_backup.form.repo_description_placeholder')) ?>">
                        </div>

                        <div class="quick-section-sep"><span class="quick-section-sep-label"><?= h(t('quick_backup.panel.repo.secret_section')) ?></span></div>
                        <div class="form-group">
                            <label class="form-label"><?= h(t('quick_backup.form.secret_storage')) ?></label>
                            <select class="form-control" id="repo_password_source"></select>
                            <div class="quick-inline-note"><?= h(t('quick_backup.form.secret_storage_note')) ?></div>
                        </div>

                        <div id="repo-password-box">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.repo_password')) ?></label>
                                <div class="quick-password-row">
                                    <div class="quick-password-wrap">
                                        <input type="password" class="form-control" id="repo_password" autocomplete="new-password">
                                        <button type="button" class="quick-password-toggle" id="btn-toggle-password" aria-label="<?= h(t('quick_backup.js.show_password')) ?>" aria-pressed="false">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                    <button type="button" class="btn" id="btn-generate-password"><?= h(t('common.generate')) ?></button>
                                </div>
                                <div class="quick-inline-note"><?= h(t('quick_backup.form.random_only_broker_local')) ?></div>
                            </div>
                        </div>

                        <div id="repo-infisical-box" style="display:none">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.infisical_secret_name')) ?></label>
                                <input type="text" class="form-control" id="repo_infisical_secret_name" placeholder="RESTIC_REPO_PASSWORD">
                            </div>
                        </div>

                        <label class="quick-choice" style="margin-top:8px">
                            <input type="checkbox" id="init_repo_if_missing" checked>
                            <span><div class="quick-choice-title"><?= h(t('quick_backup.form.init_repo_if_missing')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.form.init_repo_if_missing_desc')) ?></div></span>
                        </label>
                    </div>

                    <div class="quick-row-actions">
                        <div class="flex gap-2">
                            <a class="btn" href="<?= h($reposUrl) ?>"><?= h(t('quick_backup.actions.view_repos')) ?></a>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn" data-prev><?= h(t('common.back')) ?></button>
                            <button type="button" class="btn btn-primary" data-next><?= h(t('common.continue')) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-panel" data-panel="4">
            <div class="card">
                <div class="quick-panel-head">
                    <div class="quick-panel-step-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
                    <div class="quick-panel-head-copy">
                        <div class="quick-panel-title"><?= h(t('quick_backup.panel.schedule.title')) ?></div>
                        <div class="quick-panel-copy"><?= h(t('quick_backup.panel.schedule.copy')) ?></div>
                    </div>
                </div>
                <div class="quick-card-body">

                    <label class="quick-choice" style="margin-bottom:14px">
                        <input type="checkbox" id="schedule_enabled">
                        <span><div class="quick-choice-title"><?= h(t('quick_backup.form.enable_schedule')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.form.enable_schedule_desc')) ?></div></span>
                    </label>
                    <div id="schedule-box">
                        <div class="quick-grid two">
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.hour')) ?></label>
                                <select class="form-control" id="schedule_hour"></select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= h(t('quick_backup.form.days')) ?></label>
                                <div class="quick-badges" id="schedule-days"></div>
                            </div>
                        </div>
                    </div>

                    <label class="quick-choice" style="margin:14px 0">
                        <input type="checkbox" id="retention_enabled">
                        <span><div class="quick-choice-title"><?= h(t('quick_backup.form.enable_retention')) ?></div><div class="quick-choice-desc"><?= t('quick_backup.form.enable_retention_desc') ?></div></span>
                    </label>
                    <div id="retention-box">
                        <div class="quick-grid three">
                            <div class="form-group"><label class="form-label">Keep last</label><input type="number" class="form-control" id="retention_keep_last" min="0"></div>
                            <div class="form-group"><label class="form-label">Daily</label><input type="number" class="form-control" id="retention_keep_daily" min="0"></div>
                            <div class="form-group"><label class="form-label">Weekly</label><input type="number" class="form-control" id="retention_keep_weekly" min="0"></div>
                        </div>
                        <div class="quick-grid two">
                            <div class="form-group"><label class="form-label">Monthly</label><input type="number" class="form-control" id="retention_keep_monthly" min="0"></div>
                            <div class="form-group"><label class="form-label">Yearly</label><input type="number" class="form-control" id="retention_keep_yearly" min="0"></div>
                        </div>
                        <label class="quick-choice" style="margin-top:8px">
                            <input type="checkbox" id="retention_prune">
                            <span><div class="quick-choice-title"><?= h(t('quick_backup.form.retention_prune')) ?></div><div class="quick-choice-desc"><?= h(t('quick_backup.form.retention_prune_desc')) ?></div></span>
                        </label>
                    </div>

                    <label class="quick-choice" style="margin-top:14px">
                        <input type="checkbox" id="run_after_create" checked>
                        <span><div class="quick-choice-title"><?= t('quick_backup.form.run_test_after_create') ?></div><div class="quick-choice-desc"><?= t('quick_backup.form.run_test_after_create_desc') ?></div></span>
                    </label>

                    <div class="quick-row-actions">
                        <div class="quick-inline-note"><?= h(t('quick_backup.panel.schedule.note')) ?></div>
                        <div class="flex gap-2">
                            <button type="button" class="btn" data-prev><?= h(t('common.back')) ?></button>
                            <button type="button" class="btn btn-primary" id="btn-run-preflight"><?= h(t('quick_backup.js.btn_check')) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-panel" data-panel="5">
            <div class="card">
                <div class="quick-panel-head">
                    <div class="quick-panel-step-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></div>
                    <div class="quick-panel-head-copy">
                        <div class="quick-panel-title"><?= h(t('quick_backup.panel.check.title')) ?></div>
                        <div class="quick-panel-copy"><?= h(t('quick_backup.panel.check.copy')) ?></div>
                    </div>
                </div>
                <div class="quick-card-body">
                    <div id="quick-checks" class="quick-result-box">
                        <div class="alert alert-info"><?= h(t('quick_backup.panel.check.initial_info')) ?></div>
                    </div>
                    <div class="quick-row-actions">
                        <div class="quick-inline-note"><?= h(t('quick_backup.panel.check.note')) ?></div>
                        <div class="flex gap-2">
                            <button type="button" class="btn" data-prev><?= h(t('common.back')) ?></button>
                            <button type="button" class="btn btn-primary" id="btn-to-summary" disabled><?= h(t('quick_backup.panel.check.to_summary')) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-panel" data-panel="6">
            <div class="card">
                <div class="quick-panel-head">
                    <div class="quick-panel-step-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6M9 16h6M7 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2"/><rect x="7" y="2" width="10" height="4" rx="1"/></svg></div>
                    <div class="quick-panel-head-copy">
                        <div class="quick-panel-title"><?= h(t('quick_backup.panel.summary.title')) ?></div>
                        <div class="quick-panel-copy"><?= h(t('quick_backup.panel.summary.copy')) ?></div>
                    </div>
                </div>
                <div class="quick-card-body">
                    <div id="quick-summary">
                        <div class="alert alert-info"><?= h(t('quick_backup.panel.summary.initial_info')) ?></div>
                    </div>
                    <div id="quick-create-error" style="display:none;margin-top:12px"></div>
                    <div class="quick-row-actions">
                        <div class="quick-inline-note"><?= h(t('quick_backup.panel.summary.note')) ?></div>
                        <div class="flex gap-2">
                            <button type="button" class="btn" data-prev><?= h(t('common.back')) ?></button>
                            <button type="button" class="btn btn-primary" id="btn-create-now" disabled><?= h(t('quick_backup.js.btn_create')) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-panel" data-panel="7">
            <div class="card">
                <div class="quick-panel-head">
                    <div class="quick-panel-step-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg></div>
                    <div class="quick-panel-head-copy">
                        <div class="quick-panel-title"><?= h(t('quick_backup.panel.done.title')) ?></div>
                        <div class="quick-panel-copy"><?= h(t('quick_backup.panel.done.copy')) ?></div>
                    </div>
                </div>
                <div class="quick-card-body">
                    <div id="quick-finish" class="quick-status-stack">
                        <div class="alert alert-info"><?= h(t('quick_backup.panel.done.initial_info')) ?></div>
                    </div>
                    <div class="quick-detail-block">
                        <div class="quick-detail-title"><?= h(t('quick_backup.panel.done.live_log_title')) ?></div>
                        <div id="quick-live-log" class="quick-code quick-live-log"><?= h(t('quick_backup.js.log_empty')) ?></div>
                    </div>
                    <div class="quick-detail-block">
                        <div class="quick-detail-title"><?= h(t('quick_backup.panel.done.history_title')) ?></div>
                        <div id="quick-history" class="quick-history-list">
                            <div class="alert alert-info"><?= h(t('quick_backup.js.history_empty')) ?></div>
                        </div>
                    </div>
                    <div class="quick-row-actions">
                        <div class="flex gap-2">
                            <a class="btn" href="<?= h($jobsUrl) ?>"><?= h(t('quick_backup.actions.view_backup_jobs')) ?></a>
                            <a class="btn" href="<?= h($reposUrl) ?>"><?= h(t('quick_backup.actions.view_repos')) ?></a>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn" id="btn-restart-flow"><?= t('quick_backup.actions.new_backup') ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script<?= cspNonceAttr() ?>>
(function() {
    const context = <?= json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const initialPayload = <?= json_encode($initialPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const preflightUrl = <?= json_encode($preflightUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const createUrl = <?= json_encode($createUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const pollCreateUrl = <?= json_encode($pollCreateUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const appName = <?= json_encode(AppConfig::appName(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const csrfToken = <?= json_encode(csrfToken(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?> || ((window.FULGURITE_CONFIG && window.FULGURITE_CONFIG.csrfToken) || '');
    const i18n = <?= json_encode([
        'step_progress' => t('quick_backup.js.step_progress'),
        'hide_password' => t('quick_backup.js.hide_password'),
        'show_password' => t('quick_backup.js.show_password'),
        'no_checks' => t('quick_backup.js.no_checks'),
        'status_ready' => t('quick_backup.js.status_ready'),
        'status_blocking' => t('quick_backup.js.status_blocking'),
        'status_review' => t('quick_backup.js.status_review'),
        'status_info' => t('quick_backup.js.status_info'),
        'check_default_title' => t('quick_backup.js.check_default_title'),
        'action_required' => t('quick_backup.js.action_required'),
        'summary_unavailable' => t('quick_backup.js.summary_unavailable'),
        'history_empty' => t('quick_backup.js.history_empty'),
        'history_status_success' => t('quick_backup.js.history_status_success'),
        'history_status_running' => t('quick_backup.js.history_status_running'),
        'history_status_error' => t('quick_backup.js.history_status_error'),
        'history_quick_flow' => t('quick_backup.js.history_quick_flow'),
        'history_no_log' => t('quick_backup.js.history_no_log'),
        'history_run_default' => t('quick_backup.js.history_run_default'),
        'log_init' => t('quick_backup.js.log_init'),
        'log_empty' => t('quick_backup.js.log_empty'),
        'running_title' => t('quick_backup.js.running_title'),
        'running_body' => t('quick_backup.js.running_body'),
        'create_interrupted' => t('quick_backup.js.create_interrupted'),
        'create_success_title' => t('quick_backup.js.create_success_title'),
        'create_success_body' => t('quick_backup.js.create_success_body'),
        'created_ssh_key' => t('quick_backup.js.created_ssh_key'),
        'created_host' => t('quick_backup.js.created_host'),
        'created_repo' => t('quick_backup.js.created_repo'),
        'created_job' => t('quick_backup.js.created_job'),
        'first_test_started' => t('quick_backup.js.first_test_started'),
        'next_step_title' => t('quick_backup.js.next_step_title'),
        'manual_public_key_title' => t('quick_backup.js.manual_public_key_title'),
        'fallback_status' => t('quick_backup.js.fallback_status'),
        'network_tag' => t('quick_backup.js.network_tag'),
        'polling_failed' => t('quick_backup.js.polling_failed'),
        'preflight_network_title' => t('quick_backup.js.preflight_network_title'),
        'preflight_network_message' => t('quick_backup.js.preflight_network_message'),
        'btn_checking' => t('quick_backup.js.btn_checking'),
        'btn_check' => t('quick_backup.js.btn_check'),
        'btn_creating' => t('quick_backup.js.btn_creating'),
        'run_started' => t('quick_backup.js.run_started'),
        'start_failed' => t('quick_backup.js.start_failed'),
        'create_failed' => t('quick_backup.js.create_failed'),
        'btn_create' => t('quick_backup.js.btn_create'),
        'select_host' => t('quick_backup.js.select_host'),
        'select_repo' => t('quick_backup.js.select_repo'),
        'select_key' => t('quick_backup.js.select_key'),
        'mode_not_configured' => t('quick_backup.js.mode_not_configured'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    let currentStep = 0;
    let lastPreview = null;
    let flowDirty = true;
    let createRunState = null;
    let historyEntries = Array.isArray(context.recent_history) ? context.recent_history.slice() : [];

    const steps = Array.from(document.querySelectorAll('.quick-step'));
    const panels = Array.from(document.querySelectorAll('.quick-panel'));

    function el(id) { return document.getElementById(id); }
    function esc(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }
    function splitLines(value) {
        return String(value || '').split(/\r?\n/).map((item) => item.trim()).filter(Boolean);
    }
    function splitCsv(value) {
        return String(value || '').split(',').map((item) => item.trim()).filter(Boolean);
    }
    function joinLines(values) {
        return Array.isArray(values) ? values.join('\n') : '';
    }
    function setValue(id, value) {
        const node = el(id);
        if (node) node.value = value ?? '';
    }
    function setChecked(id, value) {
        const node = el(id);
        if (node) node.checked = !!value;
    }
    function selectedRadio(name) {
        const node = document.querySelector(`input[name="${name}"]:checked`);
        return node ? node.value : '';
    }
    function setRadio(name, value) {
        document.querySelectorAll(`input[name="${name}"]`).forEach((node) => {
            node.checked = node.value === value;
        });
    }
    function markDirty() {
        flowDirty = true;
        lastPreview = null;
        el('btn-to-summary').disabled = true;
        el('btn-create-now').disabled = true;
    }
    function refreshChoiceStates() {
        document.querySelectorAll('.quick-choice').forEach((choice) => {
            const input = choice.querySelector('input');
            choice.classList.toggle('selected', !!(input && input.checked));
        });
    }
    function renderSteps() {
        steps.forEach((stepNode, index) => {
            const active = index === currentStep;
            const done = index < currentStep;
            stepNode.classList.toggle('active', active);
            stepNode.classList.toggle('done', done);
            stepNode.setAttribute('aria-current', active ? 'step' : 'false');
            const stepNum = stepNode.querySelector('.quick-step-num');
            if (stepNum) {
                stepNum.textContent = done ? '✓' : String(index + 1);
            }
        });
        panels.forEach((panelNode, index) => panelNode.classList.toggle('active', index === currentStep));
        const progressBadge = el('quick-progress-badge');
        if (progressBadge) {
            progressBadge.textContent = (i18n.step_progress || 'Step :current / :total')
                .replace(':current', String(currentStep + 1))
                .replace(':total', String(panels.length));
        }
        const progressFill = el('quick-progress-fill');
        if (progressFill) {
            progressFill.style.width = `${((currentStep + 1) / panels.length) * 100}%`;
        }
    }
    function goToStep(step) {
        currentStep = Math.max(0, Math.min(panels.length - 1, step));
        renderSteps();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function resolvePattern(pattern) {
        const hostName = String(el('host_name').value || '').trim();
        const hostname = String(el('hostname').value || '').trim();
        const base = hostName || hostname || 'host';
        const slug = base.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'host';
        return String(pattern || '')
            .replaceAll('{{host_name}}', hostName || hostname)
            .replaceAll('{{hostname}}', hostname || hostName)
            .replaceAll('{{host_slug}}', slug)
            .replaceAll('{{app_name}}', appName);
    }
    function templateByRef(reference) {
        return (context.templates || []).find((template) => template.reference === reference) || null;
    }
    function fillSelect(selectId, items, formatter, emptyLabel) {
        const select = el(selectId);
        if (!select) return;
        const html = [];
        if (emptyLabel) {
            html.push(`<option value="">${esc(emptyLabel)}</option>`);
        }
        (items || []).forEach((item) => {
            html.push(`<option value="${esc(item.id)}">${formatter(item)}</option>`);
        });
        select.innerHTML = html.join('');
    }
    function fillSecretModes() {
        const select = el('repo_password_source');
        const options = (context.secret_storage_modes || []).map((mode) => {
            const disabled = mode.available ? '' : ' disabled';
            const suffix = mode.available ? '' : (' ' + (i18n.mode_not_configured || '(not configured)'));
            return `<option value="${esc(mode.value)}"${disabled}>${esc(mode.label + suffix)}</option>`;
        });
        select.innerHTML = options.join('');
    }
    function fillHours() {
        const options = [];
        for (let hour = 0; hour < 24; hour++) {
            const label = String(hour).padStart(2, '0') + ':00';
            options.push(`<option value="${hour}">${label}</option>`);
        }
        el('schedule_hour').innerHTML = options.join('');
    }
    function fillDays() {
        const map = context.days_map || {};
        el('schedule-days').innerHTML = Object.entries(map).map(([value, label]) => (
            `<label class="quick-choice" style="padding:6px 10px"><input type="checkbox" name="schedule_day" value="${esc(value)}"><span>${esc(label)}</span></label>`
        )).join('');
        refreshChoiceStates();
    }
    function renderTemplateCards() {
        const grid = el('template-grid');
        grid.innerHTML = (context.templates || []).map((template) => `
            <button type="button" class="quick-template${template.reference === initialPayload.template_ref ? ' active' : ''}" data-template-ref="${esc(template.reference)}" aria-pressed="${template.reference === initialPayload.template_ref ? 'true' : 'false'}">
                <div class="quick-template-title">
                    <div class="quick-template-name">${esc(template.name)}</div>
                    <span class="badge badge-${template.source === 'custom' ? 'blue' : 'gray'}">${esc(template.category || template.badge || 'Template')}</span>
                </div>
                <div class="quick-template-desc">${esc(template.description || template.blurb || '')}</div>
                <div class="quick-template-meta">
                    ${(template.defaults && template.defaults.source_paths ? template.defaults.source_paths.slice(0, 2) : []).map((path) => `<span class="badge badge-gray">${esc(path)}</span>`).join('')}
                </div>
            </button>
        `).join('');
    }
    function selectedTemplateRef() {
        const active = document.querySelector('.quick-template.active');
        return active ? active.dataset.templateRef : (context.default_template_ref || '');
    }
    function applyTemplate(force) {
        const template = templateByRef(selectedTemplateRef());
        const defaults = template && template.defaults ? template.defaults : {};
        const shouldFill = (node) => force || !node.value;

        const userNode = el('user');
        if (defaults.host_user && shouldFill(userNode)) userNode.value = defaults.host_user;
        const portNode = el('port');
        if (typeof defaults.host_port !== 'undefined' && shouldFill(portNode)) portNode.value = String(defaults.host_port);
        const sourceNode = el('source_paths');
        if (Array.isArray(defaults.source_paths) && shouldFill(sourceNode)) sourceNode.value = defaults.source_paths.join('\n');
        const excludeNode = el('excludes');
        if (Array.isArray(defaults.excludes) && shouldFill(excludeNode)) excludeNode.value = defaults.excludes.join('\n');
        const tagsNode = el('tags');
        if (Array.isArray(defaults.tags) && shouldFill(tagsNode)) tagsNode.value = defaults.tags.join(', ');

        if (shouldFill(el('repo_name'))) setValue('repo_name', resolvePattern(defaults.repo_name_pattern || ''));
        if (shouldFill(el('repo_path'))) setValue('repo_path', resolvePattern(defaults.repo_path_pattern || ''));
        if (shouldFill(el('remote_repo_path'))) setValue('remote_repo_path', resolvePattern(defaults.remote_repo_path_pattern || ''));
        if (shouldFill(el('job_name'))) setValue('job_name', resolvePattern(defaults.job_name_pattern || ''));

        if (typeof defaults.schedule_enabled !== 'undefined') setChecked('schedule_enabled', defaults.schedule_enabled);
        if (typeof defaults.schedule_hour !== 'undefined') setValue('schedule_hour', defaults.schedule_hour);
        document.querySelectorAll('input[name="schedule_day"]').forEach((node) => {
            node.checked = Array.isArray(defaults.schedule_days) ? defaults.schedule_days.includes(node.value) : false;
        });

        if (typeof defaults.retention_enabled !== 'undefined') setChecked('retention_enabled', defaults.retention_enabled);
        ['last', 'daily', 'weekly', 'monthly', 'yearly'].forEach((suffix) => {
            const key = `retention_keep_${suffix}`;
            if (typeof defaults[key] !== 'undefined') setValue(key, defaults[key]);
        });
        if (typeof defaults.retention_prune !== 'undefined') setChecked('retention_prune', defaults.retention_prune);
        if (defaults.repo_password_source) setValue('repo_password_source', defaults.repo_password_source);

        toggleHostMode();
        toggleKeyMode();
        toggleRepoMode();
        toggleSecretMode();
        toggleSchedule();
        toggleRetention();
        refreshChoiceStates();
        markDirty();
    }
    function toggleHostMode() {
        const existing = selectedRadio('host_mode') === 'existing';
        el('host-existing-box').style.display = existing ? '' : 'none';
        el('host-create-box').style.display = existing ? 'none' : '';
    }
    function toggleKeyMode() {
        const mode = selectedRadio('key_mode');
        el('key-existing-box').style.display = mode === 'existing' ? '' : 'none';
        el('key-import-box').style.display = mode === 'import' ? '' : 'none';
    }
    function toggleRepoMode() {
        const existing = selectedRadio('repo_mode') === 'existing';
        el('repo-existing-box').style.display = existing ? '' : 'none';
        el('repo-create-box').style.display = existing ? 'none' : '';
    }
    function toggleSecretMode() {
        const source = el('repo_password_source').value || context.secret_storage_default || 'agent';
        const infisical = source === 'infisical';
        el('repo-password-box').style.display = infisical ? 'none' : '';
        el('repo-infisical-box').style.display = infisical ? '' : 'none';
        el('btn-generate-password').style.display = infisical ? 'none' : '';
    }
    function setPasswordVisibility(visible) {
        const input = el('repo_password');
        const button = el('btn-toggle-password');
        if (!input || !button) return;
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? (i18n.hide_password || 'Masquer le mot de passe') : (i18n.show_password || 'Afficher le mot de passe'));
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
    }
    function togglePasswordVisibility() {
        const input = el('repo_password');
        if (!input) return;
        setPasswordVisibility(input.type === 'password');
    }
    function toggleSchedule() {
        el('schedule-box').style.display = el('schedule_enabled').checked ? '' : 'none';
    }
    function toggleRetention() {
        el('retention-box').style.display = el('retention_enabled').checked ? '' : 'none';
    }
    function generatePassword() {
        const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*()-_=+';
        let password = '';
        const bytes = new Uint32Array(24);
        window.crypto.getRandomValues(bytes);
        bytes.forEach((item) => { password += charset[item % charset.length]; });
        el('repo_password').value = password;
        markDirty();
    }
    function collectPayload() {
        return {
            template_ref: selectedTemplateRef(),
            host_mode: selectedRadio('host_mode'),
            existing_host_id: el('existing_host_id').value,
            host_name: el('host_name').value,
            hostname: el('hostname').value,
            user: el('user').value,
            port: el('port').value,
            key_mode: selectedRadio('key_mode'),
            existing_key_id: el('existing_key_id').value,
            key_name: el('key_name').value,
            private_key: el('private_key').value,
            deploy_password: el('deploy_password').value,
            repo_mode: selectedRadio('repo_mode'),
            existing_repo_id: el('existing_repo_id').value,
            repo_name: el('repo_name').value,
            repo_path: el('repo_path').value,
            remote_repo_path: el('remote_repo_path').value,
            repo_password_source: el('repo_password_source').value,
            repo_password: el('repo_password').value,
            repo_infisical_secret_name: el('repo_infisical_secret_name').value,
            repo_description: el('repo_description').value,
            init_repo_if_missing: el('init_repo_if_missing').checked,
            job_name: el('job_name').value,
            job_description: el('job_description').value,
            hostname_override: el('hostname_override').value,
            source_paths: el('source_paths').value,
            excludes: el('excludes').value,
            tags: el('tags').value,
            schedule_enabled: el('schedule_enabled').checked,
            schedule_hour: el('schedule_hour').value,
            schedule_days: Array.from(document.querySelectorAll('input[name="schedule_day"]:checked')).map((node) => node.value),
            retention_enabled: el('retention_enabled').checked,
            retention_keep_last: el('retention_keep_last').value,
            retention_keep_daily: el('retention_keep_daily').value,
            retention_keep_weekly: el('retention_keep_weekly').value,
            retention_keep_monthly: el('retention_keep_monthly').value,
            retention_keep_yearly: el('retention_keep_yearly').value,
            retention_prune: el('retention_prune').checked,
            run_after_create: el('run_after_create').checked,
            csrf_token: csrfToken,
        };
    }
    function renderChecks(checks) {
        const wrap = el('quick-checks');
        if (!Array.isArray(checks) || !checks.length) {
            wrap.innerHTML = `<div class="alert alert-info">${esc(i18n.no_checks || 'No check results.')}</div>`;
            return;
        }
        const statusMap = {
            success: {
                cls: 'badge-green', label: i18n.status_ready || 'Ready',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
            },
            error: {
                cls: 'badge-red', label: i18n.status_blocking || 'Bloquant',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>',
            },
            warning: {
                cls: 'badge-yellow', label: i18n.status_review || 'Needs review',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.29 3.86-8.3 14.35A1 1 0 0 0 2.84 20h18.32a1 1 0 0 0 .85-1.53L13.71 4.14a1 1 0 0 0-1.42-.28z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            },
            info: {
                cls: 'badge-blue', label: i18n.status_info || 'Information',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            },
        };
        wrap.innerHTML = checks.map((check) => {
            const s = statusMap[check.status] || statusMap.warning;
            return `
            <div class="quick-check ${esc(check.status || 'warning')}">
                <div class="quick-check-icon">${s.icon}</div>
                <div class="quick-check-body">
                    <div class="quick-check-head">
                        <div class="quick-check-title">${esc(check.title || i18n.check_default_title || 'Check')}</div>
                        <span class="badge ${s.cls}">${s.label}</span>
                    </div>
                    <div class="quick-check-message">${esc(check.message || '')}</div>
                    ${check.action ? `<div class="quick-inline-note" style="margin-top:6px"><strong>${esc(i18n.action_required || 'Action requise :')}</strong> ${esc(check.action)}</div>` : ''}
                    ${check.details ? `<div class="quick-code" style="margin-top:8px">${esc(check.details)}</div>` : ''}
                </div>
            </div>
        `;
        }).join('');
    }
    function renderSummary(summary) {
        const wrap = el('quick-summary');
        if (!Array.isArray(summary) || !summary.length) {
            wrap.innerHTML = `<div class="alert alert-warning">${esc(i18n.summary_unavailable || 'Resume indisponible.')}</div>`;
            return;
        }
        wrap.innerHTML = `<div class="quick-summary-grid">${summary.map((item) => `
            <div class="quick-summary-item">
                <div class="quick-summary-label">${esc(item.label || '')}</div>
                <div class="quick-summary-value">${esc(item.value || '—')}</div>
            </div>
        `).join('')}</div>`;
    }
    function formatRunDate(value) {
        if (!value) return '';
        try {
            const parsed = new Date(String(value).replace(' ', 'T') + 'Z');
            if (!Number.isNaN(parsed.getTime())) {
                return parsed.toLocaleString('fr-FR');
            }
        } catch (error) {}
        return String(value);
    }
    function renderHistory() {
        const wrap = el('quick-history');
        if (!wrap) return;
        if (!Array.isArray(historyEntries) || historyEntries.length === 0) {
            wrap.innerHTML = `<div class="alert alert-info">${esc(i18n.history_empty || 'No history available yet.')}</div>`;
            return;
        }
        wrap.innerHTML = historyEntries.map((entry) => {
            const status = String(entry.status || 'failed');
            const badgeClass = status === 'success' ? 'badge-green' : (status === 'running' ? 'badge-blue' : 'badge-red');
            const badgeLabel = status === 'success'
                ? (i18n.history_status_success || 'Succes')
                : (status === 'running' ? (i18n.history_status_running || '') : (i18n.history_status_error || ''));
            const jobLabel = Number(entry.job_id || 0) > 0 ? `Job #${esc(entry.job_id)}` : (i18n.history_quick_flow || 'Flux rapide');
            const output = String(entry.output || '').trim() || (i18n.history_no_log || 'No log recorded.');
            const firstLine = output.split(/\r?\n/).find(Boolean) || (i18n.history_run_default || 'Run rapide');
            return `
                <details class="quick-history-item">
                    <summary>
                        <div class="quick-history-meta">
                            <div class="quick-history-title">${esc(firstLine)}</div>
                            <div class="quick-history-sub">${esc(jobLabel)} • ${esc(formatRunDate(entry.ran_at || ''))}</div>
                        </div>
                        <span class="badge ${badgeClass}">${badgeLabel}</span>
                    </summary>
                    <div class="quick-code">${esc(output)}</div>
                </details>
            `;
        }).join('');
    }
    function resetLiveLog() {
        const node = el('quick-live-log');
        if (node) {
            node.textContent = i18n.log_init || 'Initialisation du journal...';
        }
    }
    function appendLiveLog(lines) {
        if (!Array.isArray(lines) || lines.length === 0) return;
        const node = el('quick-live-log');
        if (!node) return;
        const existing = node.textContent === (i18n.log_init || 'Initializing log...') || node.textContent === (i18n.log_empty || 'No log yet.') ? '' : node.textContent;
        node.textContent = `${existing}${existing ? '\n' : ''}${lines.join('\n')}`.trim();
        node.scrollTop = node.scrollHeight;
    }
    function currentLiveLogText() {
        const node = el('quick-live-log');
        if (!node) return '';
        const value = String(node.textContent || '').trim();
        if (value === (i18n.log_init || 'Initializing log...') || value === (i18n.log_empty || 'No log yet.')) {
            return '';
        }
        return value;
    }
    function renderFinishState(html) {
        const wrap = el('quick-finish');
        if (wrap) {
            wrap.innerHTML = html;
        }
    }
    function renderRunningState() {
        renderFinishState(`
            <div class="alert alert-info"><strong>${esc(i18n.running_title || '')}</strong><br>${esc(i18n.running_body || '')}</div>
        `);
    }
    function renderCreateResult(response, fallbackStatus) {
        if (!response || !response.success) {
            renderFinishState(`<div class="alert alert-danger">${esc((response && response.error) || i18n.create_interrupted || 'Creation interrupted.')}</div>`);
            return;
        }
        const created = response.created || {};
        const nextSteps = Array.isArray(response.next_steps) ? response.next_steps : [];
        const manualKey = response.manual_key || {};
        const test = response.test || null;
        const keyIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>';
        const hostIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>';
        const repoIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/></svg>';
        const jobIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>';
        const parts = [];
        parts.push(`<div class="alert alert-success" style="margin-bottom:14px"><strong>${esc(i18n.create_success_title || 'Creation successful!')}</strong> ${esc(i18n.create_success_body || 'Resources have been created and saved.')}</div>`);
        parts.push(`<div class="quick-created-items">
            ${created.ssh_key_id ? `<span class="quick-created-item">${keyIcon} ${esc(i18n.created_ssh_key || 'SSH key')} #${esc(created.ssh_key_id)}</span>` : ''}
            ${created.host_id ? `<span class="quick-created-item">${hostIcon} ${esc(i18n.created_host || 'Host')} #${esc(created.host_id)}</span>` : ''}
            ${created.repo_id ? `<span class="quick-created-item">${repoIcon} ${esc(i18n.created_repo || 'Repository')} #${esc(created.repo_id)}</span>` : ''}
            ${created.job_id ? `<span class="quick-created-item">${jobIcon} ${esc(i18n.created_job || 'Job')} #${esc(created.job_id)}</span>` : ''}
        </div>`);
        if (test && test.started) {
            parts.push(`<div class="alert alert-info" style="margin-top:12px">${esc(i18n.first_test_started || 'An initial test has been started in background')}${test.run_id ? ` (run #${esc(test.run_id)})` : ''}.</div>`);
        }
        if (nextSteps.length) {
            parts.push('<div style="margin-top:12px">' + nextSteps.map((step) => `<div class="alert alert-${step.tone === 'warning' ? 'warning' : 'info'}" style="margin-bottom:8px"><strong>${esc(step.title || i18n.next_step_title || 'Next step')}</strong><br>${esc(step.message || '')}${step.action ? `<div class="quick-code" style="margin-top:8px">${esc(step.action)}</div>` : ''}</div>`).join('') + '</div>');
        }
        if (manualKey.public_key) {
            parts.push(`<div class="quick-detail-block" style="margin-top:12px"><div class="quick-detail-title">${esc(i18n.manual_public_key_title || 'Public key to deploy manually')}</div><div class="quick-code">${esc(manualKey.public_key)}</div></div>`);
        }
        if (fallbackStatus && fallbackStatus !== 'success') {
            parts.push(`<div class="alert alert-warning" style="margin-top:8px">${esc(i18n.fallback_status || 'Run finished with status')} « ${esc(fallbackStatus)} ».</div>`);
        }
        renderFinishState(parts.join(''));
    }
    function updateHistoryFromRun(result, status) {
        const output = currentLiveLogText();
        historyEntries.unshift({
            job_id: result && result.created ? result.created.job_id : null,
            status: (result && result.success) ? 'success' : (status || 'failed'),
            output,
            ran_at: new Date().toISOString(),
        });
        historyEntries = historyEntries.slice(0, 10);
        renderHistory();
    }
    async function pollCreateRun() {
        if (!createRunState || !createRunState.runId) return;
        try {
            const log = await window.fetchJsonSafe(pollCreateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    run_id: createRunState.runId,
                    offset: createRunState.offset,
                    last_offset_bytes: createRunState.offsetBytes,
                    csrf_token: csrfToken
                }),
                timeoutMs: 30000,
            });
            if (Array.isArray(log.lines) && log.lines.length > 0) {
                appendLiveLog(log.lines);
            }
            if (Number.isFinite(Number(log.next_offset_bytes))) {
                createRunState.offsetBytes = Number(log.next_offset_bytes);
            }
            if (Number.isFinite(Number(log.offset))) {
                createRunState.offset = Number(log.offset);
            }
            if (log.done) {
                const result = log.result || null;
                if (result && result.success) {
                    renderCreateResult(result, String(log.status || 'success'));
                } else {
                    renderFinishState(`<div class="alert alert-danger">${esc((result && result.error) || i18n.create_interrupted || 'Creation interrompue.')}</div>`);
                }
                updateHistoryFromRun(result, String(log.status || 'failed'));
                createRunState = null;
                return;
            }
        } catch (error) {
            appendLiveLog([`[${i18n.network_tag || 'reseau'}] ${error.message || i18n.polling_failed || 'Polling impossible, nouvelle tentative...'}`]);
        }
        window.setTimeout(pollCreateRun, 1500);
    }
    async function runPreflight(goSummary) {
        const payload = collectPayload();
        const btn = el('btn-run-preflight');
        if (btn) {
            btn.disabled = true;
            btn.textContent = i18n.btn_checking || 'Checking…';
        }
        el('quick-create-error').style.display = 'none';
        try {
            const response = await window.fetchJsonSafe(preflightUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify(payload),
                timeoutMs: 30000,
            });
            lastPreview = response;
            flowDirty = false;
            renderChecks(response.checks || []);
            renderSummary(response.summary || []);
            el('btn-to-summary').disabled = false;
            el('btn-create-now').disabled = !response.can_create;
            goToStep(goSummary ? 6 : 5);
        } catch (error) {
            renderChecks([{ status: 'error', title: i18n.preflight_network_title || '', message: error.message || i18n.preflight_network_message || '' }]);
            el('btn-to-summary').disabled = true;
            el('btn-create-now').disabled = true;
            goToStep(5);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = i18n.btn_check || 'Check configuration';
            }
        }
    }
    async function createNow() {
        if (createRunState && createRunState.runId) {
            goToStep(7);
            return;
        }
        if (flowDirty || !lastPreview) {
            await runPreflight(true);
            if (flowDirty || !lastPreview || !lastPreview.can_create) {
                return;
            }
        }

        const btn = el('btn-create-now');
        btn.disabled = true;
        btn.textContent = i18n.btn_creating || 'Creating…';
        try {
            resetLiveLog();
            renderRunningState();
            goToStep(7);
            const response = await window.fetchJsonSafe(createUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify(collectPayload()),
                timeoutMs: 20000,
            });
            if (!response.run_id) {
                renderFinishState(`<div class="alert alert-danger">${esc(response.error || i18n.start_failed || '')}</div>`);
                return;
            }
            createRunState = { runId: response.run_id, offset: 0, offsetBytes: 0 };
            appendLiveLog([(i18n.run_started || 'Run :id demarre en arriere-plan.').replace(':id', String(response.run_id))]);
            pollCreateRun();
        } catch (error) {
            renderFinishState(`<div class="alert alert-danger">${esc(error.message || i18n.create_failed || 'Creation impossible')}</div>`);
        } finally {
            btn.disabled = false;
            btn.textContent = i18n.btn_create || 'Create now';
        }
    }
    function loadInitialPayload() {
        setValue('host_name', initialPayload.host_name || '');
        setValue('hostname', initialPayload.hostname || '');
        setValue('user', initialPayload.user || '');
        setValue('port', initialPayload.port || 22);
        setValue('key_name', initialPayload.key_name || '');
        setValue('repo_name', initialPayload.repo_name || '');
        setValue('repo_path', initialPayload.repo_path || '');
        setValue('remote_repo_path', initialPayload.remote_repo_path || '');
        setValue('repo_password_source', initialPayload.repo_password_source || context.secret_storage_default || 'agent');
        setValue('repo_infisical_secret_name', initialPayload.repo_infisical_secret_name || '');
        setValue('job_name', initialPayload.job_name || '');
        setValue('job_description', initialPayload.job_description || '');
        setValue('hostname_override', initialPayload.hostname_override || '');
        setValue('repo_description', initialPayload.repo_description || '');
        setValue('source_paths', joinLines(initialPayload.source_paths || []));
        setValue('excludes', joinLines(initialPayload.excludes || []));
        setValue('tags', (initialPayload.tags || []).join(', '));
        setValue('schedule_hour', initialPayload.schedule_hour || 2);
        setChecked('schedule_enabled', !!initialPayload.schedule_enabled);
        document.querySelectorAll('input[name="schedule_day"]').forEach((node) => {
            node.checked = Array.isArray(initialPayload.schedule_days) && initialPayload.schedule_days.includes(node.value);
        });
        setChecked('retention_enabled', !!initialPayload.retention_enabled);
        setValue('retention_keep_last', initialPayload.retention_keep_last || 0);
        setValue('retention_keep_daily', initialPayload.retention_keep_daily || 0);
        setValue('retention_keep_weekly', initialPayload.retention_keep_weekly || 0);
        setValue('retention_keep_monthly', initialPayload.retention_keep_monthly || 0);
        setValue('retention_keep_yearly', initialPayload.retention_keep_yearly || 0);
        setChecked('retention_prune', !!initialPayload.retention_prune);
        setChecked('init_repo_if_missing', !!initialPayload.init_repo_if_missing);
        setChecked('run_after_create', !!initialPayload.run_after_create);
        setRadio('host_mode', initialPayload.host_mode || 'create');
        setRadio('repo_mode', initialPayload.repo_mode || 'create');
        setRadio('key_mode', initialPayload.key_mode || 'generate');
        fillSelect('existing_host_id', context.hosts || [], (host) => `${host.name} — ${host.username}@${host.hostname}:${host.port}`, i18n.select_host || 'Choose a host');
        fillSelect('existing_repo_id', context.repos || [], (repo) => `${repo.name} — ${repo.path}`, i18n.select_repo || 'Choose a repository');
        fillSelect('existing_key_id', context.ssh_keys || [], (key) => `${key.name} — ${key.user}@${key.host}`, i18n.select_key || 'Choose a key');
        toggleHostMode();
        toggleKeyMode();
        toggleRepoMode();
        toggleSecretMode();
        toggleSchedule();
        toggleRetention();
        refreshChoiceStates();
    }

    renderTemplateCards();
    fillSecretModes();
    fillHours();
    fillDays();
    loadInitialPayload();
    renderSteps();
    renderHistory();

    steps.forEach((stepNode) => stepNode.addEventListener('click', () => goToStep(Number(stepNode.dataset.step || 0))));
    document.querySelectorAll('[data-next]').forEach((button) => button.addEventListener('click', () => goToStep(currentStep + 1)));
    document.querySelectorAll('[data-prev]').forEach((button) => button.addEventListener('click', () => goToStep(currentStep - 1)));
    el('btn-template-next').addEventListener('click', () => { applyTemplate(true); goToStep(1); });
    el('btn-run-preflight').addEventListener('click', () => runPreflight(false));
    el('btn-to-summary').addEventListener('click', () => goToStep(6));
    el('btn-create-now').addEventListener('click', createNow);
    el('btn-generate-password').addEventListener('click', generatePassword);
    el('btn-toggle-password').addEventListener('click', togglePasswordVisibility);
    el('btn-restart-flow').addEventListener('click', () => window.location.href = window.location.pathname + window.location.search);

    document.querySelectorAll('.quick-template').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.quick-template').forEach((node) => {
                node.classList.remove('active');
                node.setAttribute('aria-pressed', 'false');
            });
            button.classList.add('active');
            button.setAttribute('aria-pressed', 'true');
            applyTemplate(true);
        });
    });
    document.querySelectorAll('input[name="host_mode"]').forEach((node) => node.addEventListener('change', () => { toggleHostMode(); markDirty(); }));
    document.querySelectorAll('input[name="key_mode"]').forEach((node) => node.addEventListener('change', () => { toggleKeyMode(); markDirty(); }));
    document.querySelectorAll('input[name="repo_mode"]').forEach((node) => node.addEventListener('change', () => { toggleRepoMode(); markDirty(); }));
    document.querySelectorAll('#repo_password_source,#schedule_enabled,#retention_enabled,#init_repo_if_missing,#run_after_create,#existing_host_id,#existing_repo_id,#existing_key_id,#schedule_hour').forEach((node) => {
        node.addEventListener('change', () => {
            toggleSecretMode();
            toggleSchedule();
            toggleRetention();
            markDirty();
        });
    });
    document.querySelectorAll('input, textarea, select').forEach((node) => {
        node.addEventListener('input', markDirty);
        node.addEventListener('change', () => {
            refreshChoiceStates();
            markDirty();
        });
    });
})();
</script>

<?php include 'layout_bottom.php'; ?>
