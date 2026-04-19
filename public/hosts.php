<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('hosts.manage');

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($_POST['action'] === 'add') {
        $name        = trim($_POST['name'] ?? '');
        $hostname    = trim($_POST['hostname'] ?? '');
        $port        = (int) ($_POST['port'] ?? 22);
        $user        = trim($_POST['user'] ?? 'root');
        $sshKeyId    = ($_POST['ssh_key_id'] ?? '') !== '' ? (int) $_POST['ssh_key_id'] : null;
        $restoreOriginal = !empty($_POST['restore_original_enabled']);
        $sudoPass    = $_POST['sudo_password'] ?? '';
        $description = trim($_POST['description'] ?? '');

        if ($name && $hostname && $sshKeyId) {
            ProvisioningManager::createHost([
                'name' => $name,
                'hostname' => $hostname,
                'port' => $port,
                'user' => $user,
                'ssh_key_id' => $sshKeyId,
                'restore_original_enabled' => $restoreOriginal,
                'sudo_password' => $sudoPass,
                'description' => $description,
            ]);
            $flash = ['type' => 'success', 'msg' => t('flash.hosts.created', ['name' => $name])];
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.hosts.required_fields')];
        }
    }

    if ($_POST['action'] === 'edit') {
        $id          = (int) ($_POST['host_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $hostname    = trim($_POST['hostname'] ?? '');
        $port        = (int) ($_POST['port'] ?? 22);
        $user        = trim($_POST['user'] ?? 'root');
        $sshKeyId    = ($_POST['ssh_key_id'] ?? '') !== '' ? (int) $_POST['ssh_key_id'] : null;
        $sudoPass    = $_POST['sudo_password'] ?? '';
        $clearSudo   = isset($_POST['clear_sudo_password']);
        $description = trim($_POST['description'] ?? '');

        if ($id && $name && $hostname) {
            HostManager::update($id, [
                'name'         => $name,
                'hostname'     => $hostname,
                'port'         => $port,
                'user'         => $user,
                'ssh_key_id'   => $sshKeyId,
                'restore_original_enabled' => !empty($_POST['restore_original_enabled']),
                'sudo_password'=> $sudoPass,
                'clear_sudo'   => $clearSudo,
                'description'  => $description,
            ]);
            Auth::log('host_edit', "Hôte modifié: $name");
            $flash = ['type' => 'success', 'msg' => t('flash.hosts.updated', ['name' => $name])];
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.hosts.edit_required_fields')];
        }
    }

    if ($_POST['action'] === 'delete') {
        $id   = (int) ($_POST['host_id'] ?? 0);
        $host = HostManager::getById($id);
        if ($host) {
            HostManager::delete($id);
            Auth::log('host_delete', "Hôte supprimé: {$host['name']}");
            $flash = ['type' => 'success', 'msg' => t('flash.hosts.deleted')];
        }
    }
}

$hosts   = HostManager::getAll();
$sshKeys = SshKeyManager::getAll();

$title   = t('hosts.title');
$active  = 'hosts';
$actions = '<button class="btn" onclick="document.getElementById(\'modal-add\').classList.add(\'show\')">' . h(t('hosts.new_btn')) . '</button>';

include 'layout_top.php';
?>

<div class="card">
    <div class="card-header">
        <?= t('hosts.configured') ?>
        <span class="badge badge-blue"><?= count($hosts) ?></span>
    </div>
    <?php if (empty($hosts)): ?>
    <div class="empty-state" style="padding:48px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        <div><?= t('hosts.empty') ?></div>
        <div style="font-size:12px;margin-top:4px"><?= t('hosts.empty_hint') ?></div>
        <div style="margin-top:12px"><a class="btn btn-primary" href="<?= routePath('/quick_backup.php') ?>"><?= t('hosts.quick_backup_link') ?></a></div>
    </div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th><?= t('common.name') ?></th><th><?= t('hosts.table.address') ?></th><th><?= t('hosts.table.port') ?></th><th><?= t('hosts.table.user') ?></th><th><?= t('hosts.table.ssh_key') ?></th><th><?= t('hosts.table.sudo') ?></th><th><?= t('common.description') ?></th><th><?= t('common.actions') ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($hosts as $host): ?>
            <tr>
                <td style="font-weight:500"><?php if (!empty($host['restore_original_enabled'])): ?><span class="badge badge-red" style="font-size:10px;margin-right:6px"><?= t('hosts.badge.orig') ?></span><?php endif; ?><?= h($host['name']) ?></td>
                <td class="mono"><?= h($host['hostname']) ?></td>
                <td class="mono" style="font-size:12px"><?= $host['port'] ?></td>
                <td class="mono" style="font-size:12px"><?= h($host['user']) ?></td>
                <td style="font-size:12px">
                    <?php if ($host['ssh_key_name']): ?>
                    <span class="badge badge-blue" style="font-size:10px"><?= h($host['ssh_key_name']) ?></span>
                    <?php else: ?>
                    <span class="badge badge-red" style="font-size:10px"><?= t('common.none_f') ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (HostManager::hasSudoPassword($host)): ?>
                    <span class="badge badge-yellow" style="font-size:10px"><?= t('hosts.sudo.configured') ?></span>
                    <?php else: ?>
                    <span class="badge badge-gray" style="font-size:10px"><?= t('common.no') ?></span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text2)"><?= h($host['description'] ?? '') ?></td>
                <td>
                    <div class="flex gap-2">
                        <button class="btn btn-sm btn-success" onclick="testHost(<?= $host['id'] ?>, this)">
                            <?= t('common.test') ?>
                        </button>
                        <button class="btn btn-sm" style="background:var(--bg3);color:var(--text2);border:1px solid var(--border)"
                                onclick="checkRestoreDir(<?= $host['id'] ?>, this)" title="<?= h(t('hosts.check_restore_dir_title')) ?>">
                            <?= t('hosts.check_restore_dir_btn') ?>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="openSetup(<?= h(json_encode($host)) ?>)">
                            <?= t('hosts.setup_btn') ?>
                        </button>
                        <button class="btn btn-sm" onclick="openEdit(<?= h(json_encode($host)) ?>)">
                            <?= t('common.edit') ?>
                        </button>
                        <button class="btn btn-sm btn-danger"
                            onclick="confirmAction('<?= h(t('hosts.delete_confirm')) ?>', () => deleteHost(<?= $host['id'] ?>))">
                            <?= t('common.delete') ?>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Add modal ───────────────────────────────────────────────────────────── -->
<div id="modal-add" class="modal-overlay">
    <div class="modal" style="max-width:520px">
        <div class="modal-title"><?= t('hosts.modal_add.title') ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div style="display:grid;grid-template-columns:1fr auto;gap:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= t('common.name') ?> <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="web-prod-01" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= t('hosts.table.port') ?></label>
                    <input type="number" name="port" class="form-control" value="22" min="1" max="65535" style="width:80px">
                </div>
            </div>
            <div class="form-group" style="margin-top:16px">
                <label class="form-label"><?= t('hosts.address_label') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="hostname" class="form-control" placeholder="192.168.1.10 ou srv.example.com" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('hosts.ssh_user_label') ?></label>
                <input type="text" name="user" class="form-control" value="root">
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('hosts.table.ssh_key') ?> <span style="color:var(--red)">*</span></label>
                <select name="ssh_key_id" class="form-control" required>
                    <option value=""><?= t('hosts.select_key') ?></option>
                    <?php foreach ($sshKeys as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= h($k['name']) ?> — <?= h($k['host']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($sshKeys)): ?>
                <div style="font-size:11px;color:var(--yellow);margin-top:4px">
                    <?= t('hosts.no_ssh_keys') ?> <a href="<?= routePath('/sshkeys.php') ?>"><?= t('hosts.create_key_first') ?></a>
                </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('hosts.sudo_password_label') ?></label>
                <input type="password" name="sudo_password" class="form-control"
                       placeholder="<?= h(t('hosts.sudo_password_placeholder')) ?>">
                <div style="font-size:11px;color:var(--text2);margin-top:4px">
                    <?= t('hosts.sudo_hint') ?>
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="restore_original_enabled" value="1" style="accent-color:var(--accent);width:16px;height:16px">
                    <span style="font-size:12px;color:var(--yellow)"><?= t('hosts.restore_original_label') ?></span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('common.description') ?></label>
                <input type="text" name="description" class="form-control" placeholder="<?= h(t('hosts.description_placeholder')) ?>">
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-add').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('common.add') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit modal ──────────────────────────────────────────────────────────── -->
<div id="modal-edit" class="modal-overlay">
    <div class="modal" style="max-width:520px">
        <div class="modal-title"><?= t('hosts.modal_edit.title') ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="host_id" id="edit-host-id">
            <div style="display:grid;grid-template-columns:1fr auto;gap:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= t('common.name') ?> <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= t('hosts.table.port') ?></label>
                    <input type="number" name="port" id="edit-port" class="form-control" min="1" max="65535" style="width:80px">
                </div>
            </div>
            <div class="form-group" style="margin-top:16px">
                <label class="form-label"><?= t('hosts.address_label') ?> <span style="color:var(--red)">*</span></label>
                <input type="text" name="hostname" id="edit-hostname" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('hosts.ssh_user_label') ?></label>
                <input type="text" name="user" id="edit-user" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('hosts.table.ssh_key') ?></label>
                <select name="ssh_key_id" id="edit-ssh-key" class="form-control">
                    <option value=""><?= t('common.none_f') ?></option>
                    <?php foreach ($sshKeys as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= h($k['name']) ?> — <?= h($k['host']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('hosts.sudo_new_password_label') ?></label>
                <input type="password" name="sudo_password" class="form-control"
                       placeholder="<?= h(t('hosts.sudo_edit_placeholder')) ?>">
                <div id="edit-sudo-status" style="font-size:11px;color:var(--text2);margin-top:4px"></div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer" id="clear-sudo-label" style="display:none">
                    <input type="checkbox" name="clear_sudo_password" value="1"
                           style="accent-color:var(--accent);width:16px;height:16px">
                    <span style="font-size:12px;color:var(--yellow)"><?= t('hosts.clear_sudo_label') ?></span>
                </label>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="restore_original_enabled" id="edit-restore-original" value="1" style="accent-color:var(--accent);width:16px;height:16px">
                    <span style="font-size:12px;color:var(--yellow)"><?= t('hosts.restore_original_label') ?></span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('common.description') ?></label>
                <input type="text" name="description" id="edit-description" class="form-control">
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-edit').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal test SSH ────────────────────────────────────────────────────────── -->
<div id="modal-test" class="modal-overlay">
    <div class="modal" style="max-width:560px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <div class="modal-title" id="test-title" style="margin-bottom:0;flex:1"><?= t('hosts.test_modal_title') ?></div>
            <span id="test-spinner" class="spinner"></span>
        </div>
        <div class="code-viewer" id="test-output" style="min-height:80px;max-height:300px"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn" onclick="document.getElementById('modal-test').classList.remove('show')"><?= t('common.close') ?></button>
        </div>
    </div>
</div>

<!-- ── Host setup modal ──────────────────────────────────────────────────────── -->
<div id="modal-setup" class="modal-overlay">
    <div class="modal" style="max-width:620px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <div class="modal-title" id="setup-title" style="margin-bottom:0;flex:1"><?= t('hosts.setup_modal_title') ?></div>
            <span id="setup-spinner" class="spinner" style="display:none"></span>
        </div>

        <!-- createdentials sudo temporary -->
        <div id="setup-sudo-section" style="margin-bottom:16px;padding:12px;background:var(--bg2);border-radius:8px;border:1px solid var(--border)">
            <div id="setup-sudo-hint" style="font-size:12px;color:var(--text2);margin-bottom:10px">
                <?= t('hosts.setup_sudo_hint') ?>
            </div>
            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-size:12px"><?= t('hosts.setup_sudo_user_label') ?></label>
                    <input type="text" id="setup-temp-sudo-user" class="form-control"
                           placeholder="<?= h(t('hosts.setup_sudo_user_placeholder')) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-size:12px"><?= t('hosts.sudo_password_label') ?></label>
                    <input type="password" id="setup-temp-sudo" class="form-control"
                           placeholder="<?= h(t('auth.password')) ?>">
                </div>
            </div>
        </div>

        <!-- Log output -->
        <div class="code-viewer" id="setup-output"
             style="min-height:120px;max-height:420px;overflow-y:auto;white-space:pre;font-size:12px;display:none"></div>

        <div id="setup-status-bar" style="display:none;margin-top:10px;font-size:13px;font-weight:500"></div>

        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn" id="setup-close-btn"
                    onclick="document.getElementById('modal-setup').classList.remove('show')"><?= t('common.close') ?></button>
            <button class="btn btn-primary" id="setup-launch-btn" onclick="startSetup()"><?= t('hosts.setup_launch_btn') ?></button>
        </div>
    </div>
</div>

<form id="form-delete" method="POST" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="host_id" id="delete-host-id">
</form>

<script<?= cspNonceAttr() ?>>
let _setupHostId  = null;
let _setupRunId   = null;
let _setupPollInt = null;
let _setupOffset  = 0;
let _setupOffsetBytes = 0;

function openSetup(host) {
    _setupHostId = host.id;
    _setupRunId  = null;
    _setupOffset = 0;
    _setupOffsetBytes = 0;

    document.getElementById('setup-title').textContent    = '<?= h(t('hosts.setup_modal_title')) ?> : ' + host.name;
    document.getElementById('setup-output').textContent   = '';
    document.getElementById('setup-output').style.display = 'none';
    document.getElementById('setup-status-bar').style.display = 'none';
    document.getElementById('setup-spinner').style.display    = 'none';
    document.getElementById('setup-temp-sudo-user').value     = '';
    document.getElementById('setup-temp-sudo').value          = '';
    document.getElementById('setup-launch-btn').disabled      = false;
    document.getElementById('setup-launch-btn').textContent   = '<?= h(t('hosts.setup_launch_btn')) ?>';

    // Show temporary sudo section only if sudo is not configured
    const sudoSection = document.getElementById('setup-sudo-section');
    sudoSection.style.display = (host.sudo_password_file || host.sudo_password_ref) ? 'none' : 'block';

    document.getElementById('modal-setup').classList.add('show');
}

async function startSetup() {
    if (!_setupHostId) return;

    const launchBtn = document.getElementById('setup-launch-btn');
    const spinner   = document.getElementById('setup-spinner');
    const output    = document.getElementById('setup-output');
    const statusBar = document.getElementById('setup-status-bar');

    launchBtn.disabled    = true;
    launchBtn.textContent = '<?= h(t('common.loading')) ?>';
    spinner.style.display = 'inline-block';
    output.textContent    = '';
    output.style.display  = 'block';
    statusBar.style.display = 'none';
    _setupOffset = 0;
    _setupOffsetBytes = 0;

    const tempSudoUser = document.getElementById('setup-temp-sudo-user').value;
    const tempSudo     = document.getElementById('setup-temp-sudo').value;

    const res = await apiPost('/api/setup_host.php', {
        host_id:            _setupHostId,
        temp_sudo_user:     tempSudoUser,
        temp_sudo_password: tempSudo,
    });

    if (!res.started) {
        sLog('ERREUR: ' + (res.error || '<?= h(t("hosts.js.setup_start_failed")) ?>'));
        launchBtn.disabled    = false;
        launchBtn.textContent = '<?= h(t('hosts.setup_launch_btn')) ?>';
        spinner.style.display = 'none';
        return;
    }

    _setupRunId = res.run_id;
    _setupPollInt = setInterval(pollSetupLog, 1500);
}

async function pollSetupLog() {
    if (!_setupRunId) return;

    const res = await apiPost('/api/poll_setup_log.php', {
        run_id: _setupRunId,
        offset: _setupOffset,
        last_offset_bytes: _setupOffsetBytes,
    });

    if (!res) return;

    const output = document.getElementById('setup-output');
    if (res.lines && res.lines.length > 0) {
        res.lines.forEach(line => {
            output.textContent += line + '\n';
        });
        output.scrollTop = output.scrollHeight;
    }
    if (Number.isFinite(Number(res.next_offset_bytes))) {
        _setupOffsetBytes = Number(res.next_offset_bytes);
    } else if (Number.isFinite(Number(res.offset_bytes))) {
        _setupOffsetBytes = Number(res.offset_bytes);
    }
    if (Number.isFinite(Number(res.offset))) {
        _setupOffset = Number(res.offset);
    }

    if (res.done) {
        clearInterval(_setupPollInt);
        _setupPollInt = null;

        document.getElementById('setup-spinner').style.display    = 'none';
        document.getElementById('setup-launch-btn').disabled      = false;
        document.getElementById('setup-launch-btn').textContent   = '<?= h(t('hosts.setup_relaunch_btn')) ?>';

        const statusBar = document.getElementById('setup-status-bar');
        statusBar.style.display = 'block';
        if (res.status === 'success') {
            statusBar.textContent  = '✓ <?= h(t('hosts.setup_success')) ?>';
            statusBar.style.color  = 'var(--green)';
            toast('<?= h(t('hosts.setup_success')) ?> ✓', 'success');
        } else if (res.status === 'warning') {
            statusBar.textContent  = '⚠ <?= h(t('hosts.setup_warning')) ?>';
            statusBar.style.color  = 'var(--yellow)';
            toast('<?= h(t('hosts.setup_warning')) ?>', 'warning');
        } else {
            statusBar.textContent  = '✗ <?= h(t('hosts.setup_failed')) ?>';
            statusBar.style.color  = 'var(--red)';
            toast('<?= h(t('hosts.setup_failed')) ?>', 'error');
        }
    }
}

function deleteHost(id) {
    document.getElementById('delete-host-id').value = id;
    document.getElementById('form-delete').submit();
}

function openEdit(host) {
    document.getElementById('edit-host-id').value   = host.id;
    document.getElementById('edit-name').value      = host.name;
    document.getElementById('edit-hostname').value  = host.hostname;
    document.getElementById('edit-port').value      = host.port || 22;
    document.getElementById('edit-user').value      = host.user || 'root';
    document.getElementById('edit-description').value = host.description || '';
    document.getElementById('edit-ssh-key').value   = host.ssh_key_id || '';

    const sudoStatus = document.getElementById('edit-sudo-status');
    const clearLabel = document.getElementById('clear-sudo-label');
    if (host.sudo_password_file || host.sudo_password_ref) {
        sudoStatus.textContent = '✓ <?= h(t('hosts.sudo_already_configured')) ?>';
        sudoStatus.style.color = 'var(--yellow)';
        clearLabel.style.display = 'flex';
    } else {
        sudoStatus.textContent = '<?= h(t('hosts.sudo_not_configured')) ?>';
        sudoStatus.style.color = 'var(--text2)';
        clearLabel.style.display = 'none';
    }

    document.getElementById('modal-edit').classList.add('show');
    document.getElementById('edit-restore-original').checked = !!host.restore_original_enabled;
}

async function checkRestoreDir(hostId, btn) {
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner"></span>';

    const titleEl  = document.getElementById('test-title');
    const outputEl = document.getElementById('test-output');
    const spinner  = document.getElementById('test-spinner');

    titleEl.textContent  = '<?= h(t('hosts.js.checking_restore_dir')) ?>';
    outputEl.textContent = '';
    outputEl.style.color = 'var(--text)';
    spinner.style.display = 'inline-block';
    document.getElementById('modal-test').classList.add('show');

    const res = await apiPost('/api/check_restore_dir.php', { host_id: hostId });

    btn.disabled    = false;
    btn.textContent = '<?= h(t('hosts.js.restore_dir_btn')) ?>';
    spinner.style.display = 'none';

    if (!res || !res.success) {
        titleEl.textContent  = '✗ <?= h(t('hosts.js.ssh_conn_failed')) ?>';
        outputEl.style.color = 'var(--red)';
        outputEl.textContent = res?.output || res?.error || '<?= h(t('hosts.js.error_unknown')) ?>';
        toast('<?= h(t('hosts.js.ssh_conn_failed')) ?>', 'error');
        return;
    }

    const root = res.restore_root || '/var/tmp/fulgurite-restores';
    const user = res.ssh_user || 'root';

    if (res.exists) {
        titleEl.textContent  = '✓ <?= h(t('hosts.js.restore_dir_present')) ?>';
        outputEl.style.color = 'var(--green)';
        outputEl.textContent = '<?= h(t('hosts.js.restore_dir_path_prefix')) ?>' + root + '\n'
            + (res.output || '').replace('STATUS_EXISTS', '').trim();
        toast('<?= h(t('hosts.js.restore_dir_ok_toast')) ?>', 'success');
    } else {
        titleEl.textContent  = '⚠ <?= h(t('hosts.js.restore_dir_missing')) ?>';
        outputEl.style.color = 'var(--yellow)';
        outputEl.textContent = '<?= h(t('hosts.js.restore_dir_missing_prefix')) ?>' + root + '<?= h(t('hosts.js.restore_dir_missing_suffix')) ?>\n\n'
            + '<?= h(t('hosts.js.restore_dir_create_intro')) ?>\n\n'
            + '  mkdir -p ' + root + '\n'
            + '  chown ' + user + ':' + user + ' ' + root + '\n'
            + '  chmod 770 ' + root;
        toast('<?= h(t('hosts.js.restore_dir_missing_toast')) ?>', 'warning');
    }
}

async function testHost(hostId, btn) {
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner"></span>';

    const titleEl  = document.getElementById('test-title');
    const outputEl = document.getElementById('test-output');
    const spinner  = document.getElementById('test-spinner');

    titleEl.textContent  = '<?= h(t('hosts.js.testing')) ?>';
    outputEl.textContent = '';
    outputEl.style.color = 'var(--text)';
    spinner.style.display = 'inline-block';
    document.getElementById('modal-test').classList.add('show');

    const res = await apiPost('/api/test_host.php', { host_id: hostId });

    btn.disabled    = false;
    btn.textContent = '<?= h(t('hosts.js.test_btn')) ?>';
    spinner.style.display = 'none';

    if (res.success) {
        titleEl.textContent  = '✓ <?= h(t('hosts.js.conn_success')) ?>';
        outputEl.style.color = 'var(--green)';
        toast('<?= h(t('hosts.js.conn_success_toast')) ?>', 'success');
    } else {
        titleEl.textContent  = '✗ <?= h(t('hosts.js.conn_failed')) ?>';
        outputEl.style.color = 'var(--red)';
        toast('<?= h(t('hosts.js.conn_failed_toast')) ?>', 'error');
    }
    outputEl.textContent = res.output || res.error || '';
}

window.deleteHost = deleteHost;
window.openEdit = openEdit;
window.openSetup = openSetup;
window.startSetup = startSetup;
window.checkRestoreDir = checkRestoreDir;
window.testHost = testHost;
</script>

<?php include 'layout_bottom.php'; ?>
