<?php
// =============================================================================
// backup_templates.php — quick backup template management
// =============================================================================
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('backup_jobs.manage');

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::isAdmin()) {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $defaults = QuickBackupTemplateManager::defaultsFromForm($_POST);
            $id = QuickBackupTemplateManager::create(
                $name,
                trim($_POST['description'] ?? ''),
                trim($_POST['category'] ?? t('backup_templates.default_category')),
                $defaults
            );
            Auth::log('backup_template_create', "Modèle créé : $name (#$id)");
            $flash = ['type' => 'success', 'msg' => t('backup_templates.flash.created', ['name' => $name])];
        } else {
            $flash = ['type' => 'danger', 'msg' => t('backup_templates.flash.name_required')];
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['template_id'] ?? 0);
        if ($id) {
            $tpl = QuickBackupTemplateManager::getCustomById($id);
            if ($tpl) {
                QuickBackupTemplateManager::delete($id);
                Auth::log('backup_template_delete', "Modèle supprimé : #$id");
                $flash = ['type' => 'success', 'msg' => t('backup_templates.flash.deleted')];
            }
        }
    }

    if ($action === 'duplicate') {
        $id = (int) ($_POST['template_id'] ?? 0);
        if ($id) {
            $newId = QuickBackupTemplateManager::duplicate($id);
            if ($newId) {
                Auth::log('backup_template_duplicate', "Modèle dupliqué : #{$id} → #{$newId}");
                $flash = ['type' => 'success', 'msg' => t('backup_templates.flash.duplicated', ['id' => (string) $newId])];
            }
        }
    }
}

$builtinTemplates = QuickBackupTemplateManager::getBuiltinTemplates();
$userTemplates    = QuickBackupTemplateManager::getCustomTemplates();

$title   = t('backup_templates.title');
$active  = 'backup_jobs';
$actions = '';

include 'layout_top.php';

if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>" style="margin-bottom:12px"><?= h($flash['msg']) ?></div>
<?php endif; ?>

<?php
function tplRetLabel(array $tpl): string {
    $d = $tpl['defaults'] ?? [];
    $parts = [];
    if ($d['retention_keep_daily']   ?? 0) $parts[] = t('backup_templates.retention.daily_short', ['count' => (string) $d['retention_keep_daily']]);
    if ($d['retention_keep_weekly']  ?? 0) $parts[] = t('backup_templates.retention.weekly_short', ['count' => (string) $d['retention_keep_weekly']]);
    if ($d['retention_keep_monthly'] ?? 0) $parts[] = t('backup_templates.retention.monthly_short', ['count' => (string) $d['retention_keep_monthly']]);
    if ($d['retention_keep_last']    ?? 0) $parts[] = t('backup_templates.retention.last_short', ['count' => (string) $d['retention_keep_last']]);
    return implode(', ', $parts) ?: '—';
}
function tplSchedLabel(array $tpl): string {
    $d    = $tpl['defaults'] ?? [];
    $dmap = [
        '1' => t('settings.days_short.mon'),
        '2' => t('settings.days_short.tue'),
        '3' => t('settings.days_short.wed'),
        '4' => t('settings.days_short.thu'),
        '5' => t('settings.days_short.fri'),
        '6' => t('settings.days_short.sat'),
        '7' => t('settings.days_short.sun'),
    ];
    $days = array_map(static fn($x) => $dmap[$x] ?? $x, (array) ($d['schedule_days'] ?? []));
    $hour = str_pad((string) ($d['schedule_hour'] ?? 2), 2, '0', STR_PAD_LEFT);
    return t('backup_templates.schedule.label', ['days' => implode(' ', $days), 'hour' => $hour]);
}
function tplPathsLabel(array $tpl): string {
    $d    = $tpl['defaults'] ?? [];
    $paths = (array) ($d['source_paths'] ?? []);
    $out   = implode(', ', array_slice($paths, 0, 2));
    if (count($paths) > 2) $out .= ' +' . (count($paths) - 2);
    return $out ?: '—';
}
?>

<!-- Built-in templates -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <?= h(t('backup_templates.builtin.title')) ?>
        <span class="badge badge-blue"><?= count($builtinTemplates) ?></span>
        <span style="font-size:11px;color:var(--text2);margin-left:8px;font-weight:400"><?= h(t('backup_templates.builtin.subtitle')) ?></span>
    </div>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= h(t('common.name')) ?></th><th><?= h(t('backup_templates.category')) ?></th><th><?= h(t('backup_templates.default_paths')) ?></th><th><?= h(t('backup_templates.schedule')) ?></th><th><?= h(t('backup_templates.retention')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($builtinTemplates as $tpl): ?>
        <tr>
            <td style="font-weight:600"><?= h($tpl['name']) ?>
                <div style="font-size:11px;color:var(--text2);font-weight:400"><?= h($tpl['description'] ?? '') ?></div>
            </td>
            <td><span class="badge badge-gray" style="font-size:11px"><?= h($tpl['category']) ?></span></td>
            <td class="mono" style="font-size:11px"><?= h(tplPathsLabel($tpl)) ?></td>
            <td style="font-size:12px"><?= h(tplSchedLabel($tpl)) ?></td>
            <td style="font-size:12px"><?= h(tplRetLabel($tpl)) ?></td>
            <td>
                <a href="<?= routePath('/quick_backup.php') ?>?template=<?= urlencode((string) $tpl['reference']) ?>"
                   class="btn btn-sm btn-primary"><?= h(t('backup_templates.use')) ?></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Custom templates -->
<div class="card">
    <div class="card-header">
        <?= h(t('backup_templates.custom.title')) ?>
        <span class="badge badge-blue"><?= count($userTemplates) ?></span>
    </div>
    <?php if (empty($userTemplates)): ?>
    <div class="empty-state" style="padding:40px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12h6M9 16h6M7 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2"/><rect x="7" y="2" width="10" height="4" rx="1"/></svg>
        <div><?= h(t('backup_templates.custom.empty_title')) ?></div>
        <div style="font-size:12px;margin-top:4px;color:var(--text2)">
            <?= h(t('backup_templates.custom.empty_desc')) ?>
        </div>
    </div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= h(t('common.name')) ?></th><th><?= h(t('backup_templates.category')) ?></th><th><?= h(t('backup_templates.paths')) ?></th><th><?= h(t('backup_templates.schedule')) ?></th><th><?= h(t('backup_templates.retention')) ?></th><th><?= h(t('common.actions')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($userTemplates as $tpl): ?>
        <tr>
            <td style="font-weight:600"><?= h($tpl['name']) ?>
                <div style="font-size:11px;color:var(--text2);font-weight:400"><?= h($tpl['description'] ?? '') ?></div>
            </td>
            <td><span class="badge badge-blue" style="font-size:11px"><?= h($tpl['category'] ?? t('backup_templates.default_category')) ?></span></td>
            <td class="mono" style="font-size:11px"><?= h(tplPathsLabel($tpl)) ?></td>
            <td style="font-size:12px"><?= h(tplSchedLabel($tpl)) ?></td>
            <td style="font-size:12px"><?= h(tplRetLabel($tpl)) ?></td>
            <td>
                <div class="flex gap-2">
                    <a href="<?= routePath('/quick_backup.php') ?>?template=<?= urlencode((string) $tpl['reference']) ?>"
                       class="btn btn-sm btn-primary"><?= h(t('backup_templates.use')) ?></a>
                    <?php if (Auth::isAdmin()): ?>
                    <button class="btn btn-sm" onclick="openEdit(<?= h(json_encode($tpl)) ?>)"><?= h(t('common.edit')) ?></button>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="duplicate">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="template_id" value="<?= $tpl['id'] ?>">
                        <button type="submit" class="btn btn-sm"><?= h(t('backup_templates.duplicate')) ?></button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('<?= h(t('backup_templates.confirm_delete')) ?>')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="template_id" value="<?= $tpl['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><?= h(t('common.delete')) ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php if (Auth::isAdmin()): ?>
<!-- Template createation -->
<div class="card" style="margin-top:16px">
    <div class="card-header">+ <?= h(t('backup_templates.create_new')) ?></div>
    <div class="card-body" style="padding:20px">
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= h(t('common.name')) ?> <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= h(t('backup_templates.category')) ?></label>
                    <input type="text" name="category" class="form-control" placeholder="<?= h(t('backup_templates.default_category')) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= h(t('common.description')) ?></label>
                    <input type="text" name="description" class="form-control">
                </div>
            </div>

            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= h(t('backup_templates.source_paths')) ?></label>
                    <textarea name="source_paths" class="form-control mono" rows="4" placeholder="/var/www&#10;/etc/nginx"></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= h(t('backup_templates.exclusions')) ?></label>
                    <textarea name="excludes" class="form-control mono" rows="4" placeholder="*.log&#10;node_modules"></textarea>
                </div>
            </div>

            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= h(t('backup_templates.tags')) ?></label>
                    <input type="text" name="tags" class="form-control" placeholder="web, prod">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= h(t('backup_templates.schedule_hour')) ?></label>
                    <select name="schedule_hour" class="form-control" style="max-width:120px">
                        <?php for ($h = 0; $h < 24; $h++): ?>
                        <option value="<?= $h ?>" <?= $h === 2 ? 'selected' : '' ?>><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>h00</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div style="margin-top:12px">
                    <label class="form-label"><?= h(t('backup_templates.schedule_days')) ?></label>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <?php foreach (['1'=>t('settings.days_short.mon'),'2'=>t('settings.days_short.tue'),'3'=>t('settings.days_short.wed'),'4'=>t('settings.days_short.thu'),'5'=>t('settings.days_short.fri'),'6'=>t('settings.days_short.sat'),'7'=>t('settings.days_short.sun')] as $v => $l): ?>
                    <label style="display:flex;align-items:center;gap:3px;cursor:pointer;font-size:11px;padding:3px 8px;border:1px solid var(--border);border-radius:4px">
                        <input type="checkbox" name="schedule_days[]" value="<?= $v ?>" checked style="accent-color:var(--accent)"> <?= $l ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:12px">
                <?php foreach (['last'=>[t('backup_templates.retention_last'),0],'daily'=>[t('backup_templates.retention_daily'),7],'weekly'=>[t('backup_templates.retention_weekly'),4],'monthly'=>[t('backup_templates.retention_monthly'),3]] as $rk => [$rl,$rv]): ?>
                <div>
                    <label class="form-label" style="font-size:11px"><?= $rl ?></label>
                    <input type="number" name="retention_keep_<?= $rk ?>" class="form-control" min="0" value="<?= $rv ?>" style="padding:4px 8px;font-size:12px">
                </div>
                <?php endforeach; ?>
            </div>

            <div style="text-align:right;margin-top:16px">
                <button type="submit" class="btn btn-primary"><?= h(t('backup_templates.create_btn')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit modal -->
<div id="modal-edit-tpl" class="modal-overlay">
    <div class="modal" style="max-width:560px">
        <div class="modal-title"><?= h(t('backup_templates.edit_title')) ?></div>
        <form id="form-edit-tpl">
            <input type="hidden" id="edit-id">
            <div class="form-group"><label class="form-label"><?= h(t('common.name')) ?></label><input type="text" id="edit-name" class="form-control" required></div>
            <div class="form-group"><label class="form-label"><?= h(t('common.description')) ?></label><input type="text" id="edit-desc" class="form-control"></div>
            <div class="form-group"><label class="form-label"><?= h(t('backup_templates.source_paths_short')) ?></label><textarea id="edit-paths" class="form-control mono" rows="3"></textarea></div>
            <div class="form-group"><label class="form-label"><?= h(t('backup_templates.exclusions_short')) ?></label><textarea id="edit-excl" class="form-control mono" rows="2"></textarea></div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn" onclick="document.getElementById('modal-edit-tpl').classList.remove('show')"><?= h(t('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= h(t('common.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<script<?= cspNonceAttr() ?>>
window.openEdit = function(tpl) {
    document.getElementById('edit-id').value    = tpl.id;
    document.getElementById('edit-name').value  = tpl.name || '';
    document.getElementById('edit-desc').value  = tpl.description || '';
    var d = tpl.defaults || {};
    document.getElementById('edit-paths').value = (d.source_paths || []).join('\n');
    document.getElementById('edit-excl').value  = (d.excludes || []).join('\n');
    document.getElementById('modal-edit-tpl').classList.add('show');
};

document.getElementById('form-edit-tpl').addEventListener('submit', function(e) {
    e.preventDefault();
    var id   = parseInt(document.getElementById('edit-id').value, 10);
    var name = document.getElementById('edit-name').value.trim();
    var desc = document.getElementById('edit-desc').value.trim();
    var paths= document.getElementById('edit-paths').value.split('\n').map(s => s.trim()).filter(Boolean);
    var excl = document.getElementById('edit-excl').value.split('\n').map(s => s.trim()).filter(Boolean);

    window.fetchJsonSafe('<?= routePath('/api/backup_templates.php') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: window.FULGURITE_CONFIG.csrfToken,
            action: 'update', id: id, name: name, description: desc,
            source_paths: paths.join('\n'), excludes: excl.join('\n'),
        }),
    }).then(function(data) {
        if (data.success) location.reload();
        else alert(data.error || '<?= h(t('common.error')) ?>');
    });
});
</script>
<?php endif; ?>

<?php include 'layout_bottom.php'; ?>
