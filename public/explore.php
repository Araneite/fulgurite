<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/ExploreView.php';
require_once __DIR__ . '/../src/RestoreTargetPlanner.php';
Auth::check();
Auth::requirePermission('repos.view');

$repoId = (int) ($_GET['repo'] ?? 0);
$repo = RepoManager::getById($repoId);
if (!$repo) {
    redirectTo('/repos.php');
}
Auth::requireRepoAccess($repoId);

$snapshot = $_GET['snapshot'] ?? null;
$path = $_GET['path'] ?? '/';
$action = $_GET['action'] ?? 'browse';
$page = max(1, (int) ($_GET['page'] ?? 1));

$title = t('explore.title', ['name' => $repo['name']]);
$active = 'repos';
$subtitle = $repo['path'];
$restoreHosts = Auth::canRestore()
    ? array_values(array_filter(
        Auth::filterAccessibleHosts(HostManager::getAll()),
        static fn(array $host): bool => !empty($host['ssh_key_id']) && !empty($host['private_key_file'])
    ))
    : [];

include 'layout_top.php';
?>

<style<?= cspNonceAttr() ?>>
.restore-modal{max-width:940px!important;border:1px solid rgba(148,163,184,.22);background:radial-gradient(circle at top left,rgba(59,130,246,.14),transparent 32%),linear-gradient(180deg,var(--bg2),var(--bg));box-shadow:0 28px 80px rgba(2,6,23,.48)}
.restore-modal .modal-title{font-size:20px;letter-spacing:-.02em;margin-bottom:14px}
.restore-modal-shell{display:flex;flex-direction:column;gap:16px}
.restore-section{border:1px solid rgba(148,163,184,.2);border-radius:20px;padding:16px;background:linear-gradient(180deg,rgba(255,255,255,.045),rgba(255,255,255,.012));box-shadow:inset 0 1px 0 rgba(255,255,255,.04)}
.restore-section-title{font-size:13px;font-weight:700;margin-bottom:10px}
.restore-option-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px}
.restore-radio-card{display:flex;gap:10px;align-items:flex-start;padding:14px;border:1px solid rgba(148,163,184,.22);border-radius:18px;background:rgba(15,23,42,.2);cursor:pointer;min-height:88px;transition:border-color .16s ease,transform .16s ease,background .16s ease}
.restore-radio-card:hover{border-color:rgba(59,130,246,.45);transform:translateY(-1px);background:rgba(59,130,246,.08)}
.restore-radio-card:has(input:checked){border-color:rgba(59,130,246,.7);background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(14,165,233,.06))}
.restore-radio-card input{margin-top:3px;accent-color:var(--accent)}
.restore-radio-copy{display:flex;flex-direction:column;gap:4px}
.restore-radio-title{font-size:13px;font-weight:700}
.restore-radio-note{font-size:12px;color:var(--text2);line-height:1.45}
.restore-radio-card-danger{border-color:rgba(220,38,38,.35);background:rgba(220,38,38,.08)}
.restore-radio-card-danger:has(input:checked){border-color:rgba(220,38,38,.7);background:linear-gradient(135deg,rgba(220,38,38,.18),rgba(127,29,29,.08))}
.restore-preview-card{border:1px solid rgba(59,130,246,.26);background:linear-gradient(135deg,rgba(59,130,246,.14),rgba(15,23,42,.07));border-radius:20px;padding:16px;display:flex;flex-direction:column;gap:12px}
.restore-preview-label{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--text2)}
.restore-preview-path{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:1.5;padding:12px 14px;border-radius:14px;background:rgba(15,23,42,.44);border:1px solid rgba(148,163,184,.22);word-break:break-word}
.restore-preview-list{display:flex;flex-direction:column;gap:8px}
.restore-preview-line{font-size:12px;color:var(--text2);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:rgba(15,23,42,.24);border:1px solid rgba(148,163,184,.12);padding:8px 10px;border-radius:12px}
.restore-inline-note{font-size:12px;color:var(--text2);line-height:1.5}
.restore-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:rgba(59,130,246,.12);color:var(--text);font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.restore-host-safety{display:none;align-items:flex-start;gap:10px;border-radius:16px;padding:12px 14px;font-size:12px;line-height:1.5;border:1px solid rgba(148,163,184,.2);background:rgba(15,23,42,.18);color:var(--text2)}
.restore-host-safety::before{content:"";width:8px;height:8px;border-radius:999px;margin-top:6px;flex:0 0 auto;background:rgba(59,130,246,.75)}
.restore-host-safety-warning{border-color:rgba(245,158,11,.36);background:rgba(245,158,11,.1);color:var(--text)}
.restore-host-safety-warning::before{background:rgb(245,158,11)}
.restore-host-safety-danger{border-color:rgba(220,38,38,.44);background:rgba(220,38,38,.12);color:var(--text)}
.restore-host-safety-danger::before{background:rgb(220,38,38)}
.restore-overwrite-warning{display:none;border:1px solid rgba(245,158,11,.42);background:linear-gradient(135deg,rgba(245,158,11,.16),rgba(120,53,15,.08));border-radius:20px;padding:14px;gap:10px;flex-direction:column}
.restore-overwrite-title{font-size:13px;font-weight:800;color:rgb(245,158,11)}
.restore-overwrite-list{display:flex;flex-direction:column;gap:6px;max-height:170px;overflow:auto}
.restore-overwrite-item{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:1.45;padding:8px 10px;border-radius:12px;background:rgba(15,23,42,.28);border:1px solid rgba(245,158,11,.18);word-break:break-word}
.restore-confirm-card{display:none;border:1px solid rgba(220,38,38,.42);background:radial-gradient(circle at top left,rgba(248,113,113,.22),transparent 36%),linear-gradient(135deg,rgba(220,38,38,.13),rgba(127,29,29,.08));border-radius:20px;padding:16px;gap:10px;flex-direction:column;margin-top:14px}
.restore-confirm-title{font-size:13px;font-weight:800;color:var(--red)}
.restore-confirm-card code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:rgba(15,23,42,.36);border:1px solid rgba(148,163,184,.22);border-radius:8px;padding:2px 6px;color:var(--text)}
.restore-confirm-input{max-width:280px}
</style>

<?php if (Auth::hasPermission('repos.manage')): ?>
<div class="flex items-center gap-2 mb-4" style="flex-wrap:wrap">
    <span style="font-size:12px;color:var(--text2)"><?= t('explore.repo_actions') ?></span>
    <button class="btn btn-sm" onclick="checkRepo()"><?= t('explore.check_integrity') ?></button>
    <button class="btn btn-sm btn-warning" onclick="initRepo()"><?= t('explore.init_repo') ?></button>
    <div id="repo-action-output" style="display:none;margin-top:8px;width:100%">
        <div class="code-viewer" id="repo-action-log" style="max-height:150px"></div>
    </div>
</div>
<?php endif; ?>

<div class="grid-panel" style="display:grid;grid-template-columns:260px 1fr;gap:16px">
    <div id="explore-snapshots-panel">
        <div class="card" style="height:fit-content">
            <div class="card-header">
                <span><?= t('explore.snapshots') ?></span>
                <span class="badge badge-gray">...</span>
            </div>
            <div class="skeleton-panel">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="skeleton-list-item">
                    <div class="skeleton-line" style="width:42%"></div>
                    <div class="skeleton-line short" style="width:68%"></div>
                    <div class="skeleton-line short" style="width:35%"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div id="explore-main-panel">
        <div class="card mb-4">
            <div class="card-body">
                <div class="skeleton-toolbar">
                    <div class="skeleton-chip"></div>
                    <div class="skeleton-chip"></div>
                    <div class="skeleton-chip"></div>
                </div>
                <div class="skeleton-card-grid" style="margin-top:18px">
                    <div class="skeleton-block"></div>
                    <div class="skeleton-block"></div>
                    <div class="skeleton-block"></div>
                    <div class="skeleton-block"></div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="skeleton-file-list">
                <?php for ($i = 0; $i < 10; $i++): ?>
                <div class="skeleton-file-row">
                    <div class="skeleton-dot"></div>
                    <div class="skeleton-line" style="width:<?= $i % 3 === 0 ? '30%' : ($i % 3 === 1 ? '48%' : '40%') ?>"></div>
                    <div class="skeleton-line short" style="width:18%;margin-left:auto"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<?php if (Auth::canRestore()): ?>
<div id="modal-partial-restore" class="modal-overlay">
    <div class="modal restore-modal">
        <div class="modal-title"><?= t('explore.partial_restore.title') ?></div>
        <div class="alert alert-warning" style="font-size:12px">⚠ <span id="partial-file-count">0</span> <?= t('explore.partial_restore.files_selected') ?></div>
        <div class="form-group">
            <label class="form-label"><?= t('explore.restore_type_label') ?></label>
            <div style="display:flex;gap:8px">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);flex:1">
                    <input type="radio" name="partial_mode" value="local" checked style="accent-color:var(--accent)"> <?= t('explore.local') ?>
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);flex:1">
                    <input type="radio" name="partial_mode" value="remote" style="accent-color:var(--accent)"> <?= t('explore.remote_ssh') ?>
                </label>
            </div>
        </div>
        <div class="restore-section">
            <div class="restore-section-title"><?= t('explore.destination_strategy') ?></div>
            <div class="restore-option-grid">
                <label class="restore-radio-card">
                    <input type="radio" name="partial_destination_mode" value="managed" checked>
                    <span class="restore-radio-copy">
                        <span class="restore-radio-title"><?= t('explore.managed_folder') ?></span>
                        <span class="restore-radio-note"><?= t('explore.managed_folder_note_partial') ?></span>
                    </span>
                </label>
                <?php if (Auth::isAdmin() && AppConfig::restoreOriginalGlobalEnabled()): ?>
                <label class="restore-radio-card restore-radio-card-danger" data-original-option="partial">
                    <input type="radio" name="partial_destination_mode" value="original">
                    <span class="restore-radio-copy">
                        <span class="restore-radio-title"><?= t('explore.original_destination') ?></span>
                        <span class="restore-radio-note"><?= t('explore.original_destination_note') ?></span>
                    </span>
                </label>
                <?php endif; ?>
            </div>
            <label class="settings-toggle" style="margin-top:14px">
                <input type="checkbox" id="partial-append-context" value="1" <?= AppConfig::restoreAppendContextSubdir() ? 'checked' : '' ?>>
                <span><?= t('explore.append_context_subdir') ?></span>
            </label>
        </div>
        <div id="partial-local-fields">
            <div class="restore-inline-note"><?= t('explore.local_path_fixed_note') ?></div>
        </div>
        <div id="partial-remote-fields" style="display:none">
            <?php if (!empty($restoreHosts)): ?>
            <div class="form-group">
                <label class="form-label"><?= t('explore.target_host') ?></label>
                <select id="partial-host-id" class="form-control">
                    <?php foreach ($restoreHosts as $host): ?>
                    <option value="<?= $host['id'] ?>"><?= h($host['name']) ?> — <?= h($host['user']) ?>@<?= h($host['hostname']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="restore-inline-note"><?= t('explore.remote_dest_note') ?></div>
            <div id="partial-host-safety" class="restore-host-safety"></div>
            <?php else: ?>
        <div class="alert alert-warning" style="font-size:12px"><?= t('explore.no_ssh_host') ?> <a href="<?= routePath('/hosts.php') ?>"><?= t('common.configure') ?> →</a></div>
            <?php endif; ?>
        </div>
        <div class="restore-preview-card">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
                <div>
                    <div class="restore-preview-label"><?= t('explore.effective_dest') ?></div>
                    <div id="partial-preview-root" class="restore-preview-path"><?= h(AppConfig::restoreManagedLocalRoot()) ?></div>
                </div>
                <span id="partial-preview-mode" class="restore-pill">managed</span>
            </div>
            <div id="partial-preview-warning" class="restore-inline-note"></div>
            <div>
                <div class="restore-preview-label"><?= t('explore.exact_paths') ?></div>
                <div id="partial-preview-example" class="restore-preview-list"></div>
            </div>
        </div>
        <div id="partial-overwrite-warning" class="restore-overwrite-warning">
            <div class="restore-overwrite-title"><?= t('explore.existing_files_title') ?></div>
            <div class="restore-inline-note" id="partial-overwrite-summary"></div>
            <div id="partial-overwrite-list" class="restore-overwrite-list"></div>
        </div>
        <?php if (Auth::isAdmin()): ?>
        <div id="partial-original-confirm" class="restore-confirm-card">
            <div class="restore-confirm-title"><?= t('explore.admin_confirm_title') ?></div>
            <div class="restore-inline-note"><?= t('explore.admin_confirm_note', ['word' => '<code>' . h(RestoreTargetPlanner::ORIGINAL_CONFIRMATION_WORD) . '</code>']) ?></div>
            <input type="text" id="partial-original-confirm-input" class="form-control restore-confirm-input" autocomplete="off" placeholder="<?= h(RestoreTargetPlanner::ORIGINAL_CONFIRMATION_WORD) ?>">
            <div id="partial-original-confirm-help" class="restore-inline-note"></div>
        </div>
        <?php endif; ?>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
            <button class="btn" onclick="document.getElementById('modal-partial-restore').classList.remove('show')"><?= t('common.cancel') ?></button>
            <button class="btn btn-success" id="btn-partial-restore" onclick="launchPartialRestore()">↩ <?= t('explore.restore_btn') ?></button>
        </div>
        <div id="partial-restore-output" style="display:none;margin-top:16px">
            <div class="code-viewer" id="partial-restore-log" style="max-height:250px"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="modal-file-diff" class="modal-overlay">
    <div class="modal" style="max-width:800px">
        <div class="modal-title"><?= t('explore.file_diff.title') ?></div>
        <div class="grid-2 mb-4" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group">
                <label class="form-label"><?= t('explore.file_diff.snap_a') ?></label>
                <select id="diff-snap-a" class="form-control"><option value=""><?= h(t('explore.loading')) ?></option></select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('explore.file_diff.snap_b') ?></label>
                <select id="diff-snap-b-file" class="form-control"><option value=""><?= h(t('explore.loading')) ?></option></select>
            </div>
        </div>
        <div class="form-group mb-4">
            <label class="form-label"><?= t('explore.file_path_label') ?></label>
            <input type="text" id="diff-file-path" class="form-control mono" value="<?= h($path) ?>">
        </div>
        <div class="flex gap-2 mb-4" style="justify-content:flex-end">
            <button class="btn" onclick="document.getElementById('modal-file-diff').classList.remove('show')"><?= t('common.cancel') ?></button>
            <button class="btn btn-primary" onclick="launchFileDiff()"><?= t('explore.compare_btn') ?></button>
        </div>
        <div id="file-diff-output" style="display:none">
            <div style="display:flex;gap:12px;margin-bottom:8px;font-size:12px"><span id="diff-file-stats" style="color:var(--text2)"></span></div>
            <div class="code-viewer" id="file-diff-content" style="max-height:450px;font-size:12px;line-height:1.5"></div>
        </div>
    </div>
</div>

<?php if (Auth::canRestore() && $snapshot): ?>
<div id="modal-restore" class="modal-overlay">
    <div class="modal restore-modal">
        <div class="modal-title"><?= t('explore.restore_modal.title') ?> <span id="restore-modal-snapshot"><?= h($snapshot) ?></span></div>
        <div class="alert alert-warning" style="font-size:12px">⚠ <?= t('explore.restore_modal.overwrite_warning') ?></div>
        <div class="form-group">
            <label class="form-label"><?= t('explore.restore_type_label') ?></label>
            <div style="display:flex;gap:8px">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);flex:1">
                    <input type="radio" name="restore_mode" value="local" checked style="accent-color:var(--accent)"> <span><?= t('explore.local') ?> <span style="color:var(--text2);font-size:11px">(VM backup)</span></span>
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);flex:1">
                    <input type="radio" name="restore_mode" value="remote" style="accent-color:var(--accent)"> <span><?= t('explore.remote') ?> <span style="color:var(--text2);font-size:11px">(SSH/rsync)</span></span>
                </label>
            </div>
        </div>
        <div class="restore-section">
            <div class="restore-section-title"><?= t('explore.destination_strategy') ?></div>
            <div class="restore-option-grid">
                <label class="restore-radio-card">
                    <input type="radio" name="restore_destination_mode" value="managed" checked>
                    <span class="restore-radio-copy">
                        <span class="restore-radio-title"><?= t('explore.managed_folder') ?></span>
                        <span class="restore-radio-note"><?= t('explore.managed_folder_note_restore') ?></span>
                    </span>
                </label>
                <?php if (Auth::isAdmin() && AppConfig::restoreOriginalGlobalEnabled()): ?>
                <label class="restore-radio-card restore-radio-card-danger" data-original-option="restore">
                    <input type="radio" name="restore_destination_mode" value="original">
                    <span class="restore-radio-copy">
                        <span class="restore-radio-title"><?= t('explore.original_destination') ?></span>
                        <span class="restore-radio-note"><?= t('explore.original_destination_note') ?></span>
                    </span>
                </label>
                <?php endif; ?>
            </div>
            <label class="settings-toggle" style="margin-top:14px">
                <input type="checkbox" id="restore-append-context" value="1" <?= AppConfig::restoreAppendContextSubdir() ? 'checked' : '' ?>>
                <span><?= t('explore.append_context_subdir') ?></span>
            </label>
        </div>
        <div id="fields-local"><div class="restore-inline-note"><?= t('explore.local_path_fixed_note') ?></div></div>
        <div id="fields-remote" style="display:none">
            <?php if (empty($restoreHosts)): ?>
            <div class="alert alert-warning" style="font-size:12px"><?= t('explore.no_ssh_host') ?> <a href="<?= routePath('/hosts.php') ?>"><?= t('common.configure') ?> →</a></div>
            <?php else: ?>
            <div class="form-group"><label class="form-label"><?= t('explore.target_host') ?></label><select id="restore-host" class="form-control"><?php foreach ($restoreHosts as $host): ?><option value="<?= $host['id'] ?>"><?= h($host['name']) ?> — <?= h($host['user']) ?>@<?= h($host['hostname']) ?></option><?php endforeach; ?></select></div>
            <div class="restore-inline-note"><?= t('explore.remote_dest_note_full') ?></div>
            <div id="restore-host-safety" class="restore-host-safety"></div>
            <?php endif; ?>
        </div>
        <div class="form-group"><label class="form-label"><?= t('explore.include_only_label') ?></label><input type="text" id="restore-include" class="form-control" placeholder="/etc/nginx"></div>
        <div class="restore-preview-card">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
                <div>
                    <div class="restore-preview-label"><?= t('explore.effective_dest') ?></div>
                    <div id="restore-preview-root" class="restore-preview-path"><?= h(AppConfig::restoreManagedLocalRoot()) ?></div>
                </div>
                <span id="restore-preview-mode" class="restore-pill">managed</span>
            </div>
            <div id="restore-preview-warning" class="restore-inline-note"></div>
            <div>
                <div class="restore-preview-label"><?= t('explore.exact_paths') ?></div>
                <div id="restore-preview-example" class="restore-preview-list"></div>
            </div>
        </div>
        <div id="restore-overwrite-warning" class="restore-overwrite-warning">
            <div class="restore-overwrite-title"><?= t('explore.existing_files_title') ?></div>
            <div class="restore-inline-note" id="restore-overwrite-summary"></div>
            <div id="restore-overwrite-list" class="restore-overwrite-list"></div>
        </div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
            <button class="btn" onclick="document.getElementById('modal-restore').classList.remove('show')"><?= t('common.cancel') ?></button>
            <button class="btn btn-success" id="btn-restore" onclick="launchRestore()">↩ <?= t('explore.restore_btn') ?></button>
        </div>
        <?php if (Auth::isAdmin()): ?>
        <div id="restore-original-confirm" class="restore-confirm-card">
            <div class="restore-confirm-title"><?= t('explore.admin_confirm_title') ?></div>
            <div class="restore-inline-note"><?= t('explore.admin_confirm_note', ['word' => '<code>' . h(RestoreTargetPlanner::ORIGINAL_CONFIRMATION_WORD) . '</code>']) ?></div>
            <input type="text" id="restore-original-confirm-input" class="form-control restore-confirm-input" autocomplete="off" placeholder="<?= h(RestoreTargetPlanner::ORIGINAL_CONFIRMATION_WORD) ?>">
            <div id="restore-original-confirm-help" class="restore-inline-note"></div>
        </div>
        <?php endif; ?>
        <div id="restore-output" style="display:none;margin-top:16px"><div class="code-viewer" id="restore-log" style="max-height:250px"></div></div>
    </div>
</div>

<div id="modal-diff" class="modal-overlay">
    <div class="modal" style="max-width:700px">
        <div class="modal-title"><?= t('explore.diff_modal.title') ?></div>
        <div class="form-group">
            <label class="form-label"><?= t('explore.diff_modal.compare_with_prefix') ?> <strong id="diff-current-snapshot"><?= h($snapshot) ?></strong> <?= t('explore.diff_modal.compare_with_suffix') ?></label>
            <select id="diff-snapshot-b" class="form-control"><option value=""><?= h(t('explore.loading')) ?></option></select>
        </div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-bottom:12px">
            <button class="btn" onclick="document.getElementById('modal-diff').classList.remove('show')"><?= t('common.cancel') ?></button>
            <button class="btn btn-primary" onclick="launchDiff()"><?= t('explore.compare_btn') ?></button>
        </div>
        <div id="diff-output" style="display:none">
            <div id="diff-summary" style="display:flex;gap:12px;margin-bottom:12px"></div>
            <div class="card">
                <div class="diff-tab-group" style="padding:8px 12px;border-bottom:1px solid var(--border);font-size:12px;font-weight:500" role="tablist" aria-label="Filtres de comparaison">
                    <button type="button" id="diff-tab-all" class="diff-tab" role="tab" aria-selected="true" onclick="showDiffTab('all')"><?= t('common.all') ?></button>
                    <span id="diff-tab-added" style="cursor:pointer;color:var(--text2)" onclick="showDiffTab('added')"><?= t('explore.diff_added') ?></span>
                    <span id="diff-tab-removed" style="cursor:pointer;color:var(--text2)" onclick="showDiffTab('removed')"><?= t('explore.diff_removed') ?></span>
                    <span id="diff-tab-changed" style="cursor:pointer;color:var(--text2)" onclick="showDiffTab('changed')"><?= t('explore.diff_changed') ?></span>
                </div>
                <div class="code-viewer" id="diff-content" style="max-height:350px"></div>
            </div>
        </div>
    </div>
</div>

<div id="modal-tags" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('explore.tags_modal.title') ?> — <span id="tags-modal-snapshot"><?= h($snapshot) ?></span></div>
        <div class="form-group">
            <label class="form-label"><?= t('explore.tags_modal.current_tags') ?></label>
            <div id="current-tags" style="display:flex;gap:6px;flex-wrap:wrap;min-height:32px;background:var(--bg3);padding:8px;border-radius:6px;border:1px solid var(--border)"></div>
            <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= t('explore.tags_modal.click_to_remove') ?></div>
        </div>
        <div class="form-group"><label class="form-label"><?= t('explore.tags_modal.add_tag') ?></label><div style="display:flex;gap:8px"><input type="text" id="new-tag-input" class="form-control" placeholder="nom-du-tag" onkeydown="if(event.key==='Enter'){event.preventDefault();addTagBadge()}"><button class="btn btn-primary" onclick="addTagBadge()"><?= t('common.add') ?></button></div></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
            <button class="btn" onclick="document.getElementById('modal-tags').classList.remove('show')"><?= t('common.cancel') ?></button>
            <button class="btn btn-primary" id="btn-save-tags" onclick="saveTags()"><?= t('common.save') ?></button>
        </div>
        <div id="tags-output" style="display:none;margin-top:12px"><div class="code-viewer" id="tags-log" style="max-height:100px"></div></div>
    </div>
</div>

<div id="modal-retention" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('explore.retention_modal.title') ?> — <?= h($repo['name']) ?></div>
        <div class="alert alert-warning" style="font-size:12px">⚠ <?= t('explore.retention_modal.warning') ?></div>
        <?php
        $retentionDb = Database::getInstance();
        $retentionStmt = $retentionDb->prepare("SELECT * FROM retention_policies WHERE repo_id = ?");
        $retentionStmt->execute([$repoId]);
        $retPolicy = $retentionStmt->fetch() ?: ['keep_last' => 7, 'keep_daily' => 14, 'keep_weekly' => 4, 'keep_monthly' => 3, 'keep_yearly' => 1];
        ?>
        <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <?php foreach (['keep_last' => t('explore.retention_modal.keep_last'), 'keep_daily' => t('explore.retention_modal.keep_daily'), 'keep_weekly' => t('explore.retention_modal.keep_weekly'), 'keep_monthly' => t('explore.retention_modal.keep_monthly'), 'keep_yearly' => t('explore.retention_modal.keep_yearly')] as $key => $label): ?>
            <div class="form-group"><label class="form-label"><?= $label ?></label><input type="number" id="ret-<?= $key ?>" class="form-control" value="<?= (int) ($retPolicy[$key] ?? 0) ?>" min="0"></div>
            <?php endforeach; ?>
        </div>
        <div class="form-group"><label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" id="ret-prune" checked style="accent-color:var(--accent);width:16px;height:16px"><span style="font-size:13px"><?= t('explore.retention_modal.prune_label') ?></span></label></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px"><button class="btn" onclick="document.getElementById('modal-retention').classList.remove('show')"><?= t('common.cancel') ?></button><button class="btn btn-warning" onclick="applyRetention(true)"><?= t('explore.retention_modal.simulate_btn') ?></button><button class="btn btn-danger" onclick="applyRetention(false)"><?= t('explore.retention_modal.apply_btn') ?></button></div>
        <div id="retention-output" style="display:none;margin-top:16px"><div class="code-viewer" id="retention-log" style="max-height:250px"></div></div>
    </div>
</div>
<?php endif; ?>

<script<?= cspNonceAttr() ?>>
window.RESTIC_EXPLORE_CONFIG = <?= json_encode([
    'repoId' => $repoId,
    'repoName' => $repo['name'],
    'snapshot' => $snapshot,
    'path' => $path,
    'action' => $action,
    'page' => $page,
    'canManageRepo' => Auth::hasPermission('repos.manage'),
    'canRestore' => Auth::canRestore(),
    'isAdmin' => Auth::isAdmin(),
    'restore' => [
        'managedLocalRoot' => AppConfig::restoreManagedLocalRoot(),
        'managedRemoteRoot' => AppConfig::restoreManagedRemoteRoot(),
        'appendContextDefault' => AppConfig::restoreAppendContextSubdir(),
    ],
    'hosts' => array_map(static function (array $host): array {
        return [
            'id' => (int) ($host['id'] ?? 0),
            'name' => (string) ($host['name'] ?? ''),
            'hostname' => (string) ($host['hostname'] ?? ''),
            'user' => (string) ($host['user'] ?? ''),
            'port' => (int) ($host['port'] ?? 22),
            'restoreManagedRoot' => (string) ($host['restore_managed_root'] ?? ''),
            'restoreOriginalEnabled' => !empty($host['restore_original_enabled']) ? true : false,
        ];
    }, $restoreHosts),
]) ?>;
</script>
<script<?= cspNonceAttr() ?> src="/assets/explore.js?v=<?= urlencode(APP_VERSION) ?>" defer></script>

<?php include 'layout_bottom.php'; ?>
