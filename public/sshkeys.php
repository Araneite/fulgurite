<?php
require_once __DIR__ . '/../src/bootstrap.php';
if (!Auth::hasPermission('sshkeys.manage') && !Auth::hasPermission('ssh_host_key.approve')) {
    Auth::requirePermission('sshkeys.manage');
}

$canManageKeys = Auth::hasPermission('sshkeys.manage');
$canApproveHostKeys = Auth::hasPermission('ssh_host_key.approve');

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        Auth::requirePermission('sshkeys.manage');
        $name = trim($_POST['name'] ?? '');
        $host = trim($_POST['host'] ?? '');
        $user = trim($_POST['user'] ?? 'root');
        $port = (int) ($_POST['port'] ?? 22);
        $desc = trim($_POST['description'] ?? '');

        if ($name && $host) {
            $result = ProvisioningManager::generateSshKey($name, $host, $user, $port, $desc);
            if ($result['success']) {
                // Store public key in session for display
                $_SESSION['new_pubkey'] = $result['public_key'];
                $_SESSION['new_keyid']  = $result['id'];
                $flash = ['type' => 'success', 'msg' => t('flash.sshkeys.generated', ['name' => $name])];
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.sshkeys.generate_error', ['details' => $result['error'] ?? ''])];
            }
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.sshkeys.name_host_required')];
        }
    }

    if ($action === 'import') {
        Auth::requirePermission('sshkeys.manage');
        $name       = trim($_POST['name'] ?? '');
        $host       = trim($_POST['host'] ?? '');
        $user       = trim($_POST['user'] ?? 'root');
        $port       = (int) ($_POST['port'] ?? 22);
        $desc       = trim($_POST['description'] ?? '');
        $privateKey = $_POST['private_key'] ?? '';

        if ($name && $host && $privateKey) {
            $result = ProvisioningManager::importSshKey($name, $host, $user, $port, $privateKey, $desc);
            if ($result['success']) {
                $flash = ['type' => 'success', 'msg' => t('flash.sshkeys.imported', ['name' => $name])];
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.sshkeys.import_error')];
            }
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.sshkeys.all_fields_required')];
        }
    }

    if ($action === 'delete') {
        Auth::requirePermission('sshkeys.manage');
        $id  = (int) ($_POST['key_id'] ?? 0);
        $key = SshKeyManager::getById($id);
        if ($key) {
            SshKeyManager::delete($id);
            Auth::log('ssh_key_delete', "Clé supprimée: {$key['name']}");
            $flash = ['type' => 'success', 'msg' => t('flash.sshkeys.deleted')];
        }
    }

    if ($action === 'approve_host_key') {
        Auth::requirePermission('ssh_host_key.approve');
        try {
            SshKeyManager::approveHostKey(
                (string) ($_POST['host'] ?? ''),
                (int) ($_POST['port'] ?? 22),
                (string) ($_POST['public_key'] ?? '')
            );
            $flash = ['type' => 'success', 'msg' => t('flash.sshkeys.host_key_approved')];
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'msg' => t('flash.sshkeys.host_key_approve_error', ['details' => $e->getMessage()])];
        }
    }

    if ($action === 'replace_host_key') {
        Auth::requirePermission('ssh_host_key.approve');
        try {
            SshKeyManager::replaceHostKey(
                (string) ($_POST['host'] ?? ''),
                (int) ($_POST['port'] ?? 22),
                (string) ($_POST['public_key'] ?? '')
            );
            $flash = ['type' => 'success', 'msg' => t('flash.sshkeys.host_key_replaced')];
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'msg' => t('flash.sshkeys.host_key_replace_error', ['details' => $e->getMessage()])];
        }
    }

    if ($action === 'reject_host_key') {
        Auth::requirePermission('ssh_host_key.approve');
        SshKeyManager::rejectHostKey((string) ($_POST['host'] ?? ''), (int) ($_POST['port'] ?? 22));
        $flash = ['type' => 'success', 'msg' => t('flash.sshkeys.host_key_rejected')];
    }
}

// Retrieve newly generated public key from session
$newPubkey = $_SESSION['new_pubkey'] ?? null;
$newKeyId  = $_SESSION['new_keyid'] ?? null;
unset($_SESSION['new_pubkey'], $_SESSION['new_keyid']);

$keys   = SshKeyManager::getAll();
$hostTrustRecords = SshKeyManager::getAllHostTrust();
$title  = t('sshkeys.title');
$active = 'sshkeys';
$actions = '';
if ($canManageKeys) {
    $actions .= '
<button class="btn btn-primary" onclick="document.getElementById(\'modal-generate\').classList.add(\'show\')">+ ' . h(t('sshkeys.generate_btn')) . '</button>
<button class="btn" onclick="document.getElementById(\'modal-import\').classList.add(\'show\')" style="margin-left:8px">↑ ' . h(t('sshkeys.import_btn')) . '</button>
';
}

$hostKeyStatusMeta = static function (string $status): array {
    return match ($status) {
        SshKeyManager::HOST_KEY_VALID => ['label' => 'VALID', 'class' => 'badge badge-green'],
        SshKeyManager::HOST_KEY_CHANGED => ['label' => 'CHANGED', 'class' => 'badge badge-red'],
        SshKeyManager::HOST_KEY_PENDING_APPROVAL => ['label' => 'PENDING_APPROVAL', 'class' => 'badge badge-yellow'],
        default => ['label' => 'UNKNOWN', 'class' => 'badge badge-gray'],
    };
};

include 'layout_top.php';
?>

<?php if ($newPubkey): ?>
<div class="alert alert-success mb-4">
    <div style="font-weight:500;margin-bottom:8px">
        ✓ <?= t('sshkeys.new_key_banner') ?>
    </div>
    <div class="pubkey-box"><?= h($newPubkey) ?></div>
    <div style="margin-top:8px;display:flex;gap:8px;align-items:center">
        <button class="btn btn-sm btn-success" onclick="copyPubkey(this, <?= h(json_encode($newPubkey)) ?>)">
            <?= t('common.copy') ?>
        </button>
        <span style="font-size:12px;color:var(--green)">
            <?= t('sshkeys.target_command_label') ?><br>
            <code style="font-family:var(--font-mono)">echo "<?= h($newPubkey) ?>" >> ~/.ssh/authorized_keys</code>
        </span>
    </div>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <?= t('sshkeys.host_keys.header') ?>
        <span class="badge badge-blue"><?= count($hostTrustRecords) ?></span>
    </div>
        <div class="card-body" style="font-size:13px;color:var(--text2)">
        <?= t('sshkeys.host_keys.strict_mode_desc') ?>
        </div>
    <?php if (empty($hostTrustRecords)): ?>
    <div class="empty-state" style="padding-top:4px">
        <div style="font-size:14px;margin-bottom:8px"><?= t('sshkeys.host_keys.empty') ?></div>
        <div style="font-size:12px;color:var(--text2)"><?= t('sshkeys.host_keys.empty_hint') ?></div>
    </div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Host</th>
                <th><?= t('hosts.table.port') ?></th>
                <th><?= t('common.status') ?></th>
                <th><?= t('themes.table.type') ?></th>
                <th><?= t('sshkeys.host_keys.fingerprint') ?></th>
                <th><?= t('sshkeys.host_keys.old_fingerprint') ?></th>
                <th><?= t('sshkeys.host_keys.context') ?></th>
                <th><?= t('common.actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($hostTrustRecords as $record): ?>
            <?php $statusMeta = $hostKeyStatusMeta((string) ($record['status'] ?? '')); ?>
            <?php
                $displayType = (string) ($record['detected_key_type'] ?: $record['approved_key_type'] ?: '');
                $displayFingerprint = (string) ($record['detected_fingerprint'] ?: $record['approved_fingerprint'] ?: '');
                $replaceMode = !empty($record['approved_key_ref']);
                $detectedKeyReady = !empty($record['detected_public_key']);
            ?>
            <tr>
                <td class="mono"><?= h($record['host']) ?></td>
                <td class="mono"><?= h((string) $record['port']) ?></td>
                <td><span class="<?= h($statusMeta['class']) ?>"><?= h($statusMeta['label']) ?></span></td>
                <td class="mono"><?= h($displayType !== '' ? $displayType : '-') ?></td>
                <td class="mono" style="font-size:12px"><?= h($displayFingerprint !== '' ? $displayFingerprint : '-') ?></td>
                <td class="mono" style="font-size:12px"><?= h((string) (($record['previous_fingerprint'] ?? '') !== '' ? $record['previous_fingerprint'] : '-')) ?></td>
                <td style="font-size:12px;color:var(--text2)"><?= h((string) ($record['last_context'] ?? '')) ?></td>
                <td>
                    <?php if ($canApproveHostKeys): ?>
                    <div class="flex gap-2">
                        <button class="btn btn-sm btn-primary"
                                onclick='openHostKeyModal(<?= h(json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>, "<?= $replaceMode ? 'replace' : 'approve' ?>")'>
                            <?= $replaceMode ? t('sshkeys.host_keys.replace_btn') : t('sshkeys.host_keys.approve_btn') ?>
                        </button>
                        <?php if (!$detectedKeyReady): ?>
                        <button class="btn btn-sm"
                                onclick="loadDetectedHostKey(this, '<?= h($record['host']) ?>', <?= (int) $record['port'] ?>)">
                            <?= t('sshkeys.host_keys.load_btn') ?>
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-danger"
                                onclick="confirmAction('<?= h(t('sshkeys.host_keys.reject_confirm', ['host' => $record['host'], 'port' => (string) $record['port']])) ?>', () => rejectHostKey('<?= h($record['host']) ?>', <?= (int) $record['port'] ?>))">
                            <?= t('sshkeys.host_keys.reject_btn') ?>
                        </button>
                    </div>
                    <?php else: ?>
                    <span style="font-size:12px;color:var(--text2)"><?= t('sshkeys.host_keys.permission_required') ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($keys)): ?>
<div class="empty-state">
    <svg viewBox="0 0 16 16" fill="currentColor" style="display:block;margin:0 auto 12px"><circle cx="6" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M9 8h5.5M12 6.5v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
    <div style="font-size:15px;margin-bottom:8px"><?= t('sshkeys.empty') ?></div>
    <div style="font-size:13px;margin-bottom:16px"><?= t('sshkeys.empty_hint') ?></div>
    <?php if ($canManageKeys): ?>
    <button class="btn btn-primary" onclick="document.getElementById('modal-generate').classList.add('show')">+ <?= t('sshkeys.generate_btn') ?></button>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card mb-4">
    <div class="card-header">
        <?= t('sshkeys.configured') ?>
        <span class="badge badge-blue"><?= count($keys) ?></span>
    </div>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th><?= t('common.name') ?></th>
                <th><?= t('sshkeys.table.target_host') ?></th>
                <th><?= t('hosts.table.user') ?></th>
                <th><?= t('hosts.table.port') ?></th>
                <th><?= t('common.description') ?></th>
                <th><?= t('common.created_at') ?></th>
                <th><?= t('common.actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($keys as $key): ?>
            <tr>
                <td style="font-weight:500"><?= h($key['name']) ?></td>
                <td class="mono"><?= h($key['host']) ?></td>
                <td class="mono"><?= h($key['user']) ?></td>
                <td class="mono"><?= h($key['port']) ?></td>
                <td style="color:var(--text2);font-size:12px"><?= h($key['description'] ?? '') ?></td>
                <td style="font-size:12px;color:var(--text2)"><?= formatDate($key['created_at']) ?></td>
                <td>
                    <div class="flex gap-2">
                        <button class="btn btn-sm" onclick="showPubkey(<?= h(json_encode($key)) ?>)">
                            <?= t('sshkeys.client_pubkey_btn') ?>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="testKey(<?= $key['id'] ?>, this)">
                            <?= t('common.test') ?>
                        </button>
                        <button class="btn btn-sm btn-primary"
                            onclick="openDeploy(<?= $key['id'] ?>, '<?= h($key['user']) ?>', '<?= h($key['host']) ?>')">
                            <?= t('sshkeys.deploy_btn') ?>
                        </button>
                        <?php if ($canManageKeys): ?>
                        <button class="btn btn-sm btn-danger"
                            onclick="confirmAction('<?= h(t('sshkeys.delete_confirm', ['name' => $key['name']])) ?>', () => deleteKey(<?= $key['id'] ?>))">
                            <?= t('common.delete') ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="alert alert-info" style="font-size:12px">
    <?= t('sshkeys.usage_hint') ?>
</div>
<?php endif; ?>

<!-- generation modal -->
<div id="modal-generate" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('sshkeys.modal_generate.title') ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="generate">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('sshkeys.key_name_label') ?> <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="web-prod-01" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('sshkeys.target_host_label') ?> <span style="color:var(--red)">*</span></label>
                    <input type="text" name="host" class="form-control" placeholder="192.168.9.100" required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 80px;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('hosts.table.user') ?></label>
                    <input type="text" name="user" class="form-control" value="root" placeholder="elkarbackup">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('hosts.table.port') ?></label>
                    <input type="number" name="port" class="form-control" value="22">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('common.description') ?></label>
                <input type="text" name="description" class="form-control" placeholder="<?= h(t('sshkeys.description_placeholder')) ?>">
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-generate').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('sshkeys.generate_btn') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal import -->
<div id="modal-import" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('sshkeys.modal_import.title') ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="import">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('common.name') ?> <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="web-prod-01" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('sshkeys.target_host_label') ?> <span style="color:var(--red)">*</span></label>
                    <input type="text" name="host" class="form-control" placeholder="192.168.9.100" required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 80px;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('hosts.table.user') ?></label>
                    <input type="text" name="user" class="form-control" value="root">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('hosts.table.port') ?></label>
                    <input type="number" name="port" class="form-control" value="22">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('sshkeys.private_key_label') ?> <span style="color:var(--red)">*</span></label>
                <textarea name="private_key" class="form-control" rows="6"
                    placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('common.description') ?></label>
                <input type="text" name="description" class="form-control">
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
                <button type="button" class="btn" onclick="document.getElementById('modal-import').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('sshkeys.import_btn') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Public key display modal -->
<div id="modal-pubkey" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('sshkeys.modal_pubkey.title') ?></div>
        <div style="font-size:12px;color:var(--text2);margin-bottom:8px">
            <?= t('sshkeys.authorized_keys_hint') ?>
        </div>
        <div class="pubkey-box" id="pubkey-content"></div>
        <div style="font-size:12px;color:var(--text2);margin-top:10px;margin-bottom:4px">
            <?= t('sshkeys.target_command_label') ?>
        </div>
        <div class="pubkey-box" id="pubkey-cmd" style="color:var(--accent)"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn btn-success btn-sm" onclick="copyPubkeyFromModal()"><?= t('sshkeys.copy_key_btn') ?></button>
            <button class="btn" onclick="document.getElementById('modal-pubkey').classList.remove('show')"><?= t('common.close') ?></button>
        </div>
    </div>
</div>

<!-- SSH test result modal -->
<div id="modal-test" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('sshkeys.modal_test.title') ?></div>
        <div id="test-output" class="code-viewer" style="max-height:200px"></div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
            <button class="btn" onclick="document.getElementById('modal-test').classList.remove('show')"><?= t('common.close') ?></button>
        </div>
    </div>
</div>

<form id="form-delete" method="POST" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="key_id" id="delete-key-id">
</form>

<form id="form-reject-host-key" method="POST" style="display:none">
    <input type="hidden" name="action" value="reject_host_key">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="host" id="reject-host-key-host">
    <input type="hidden" name="port" id="reject-host-key-port">
</form>

<div id="modal-deploy" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('sshkeys.modal_deploy.title') ?></div>
        <div class="alert alert-info" style="font-size:12px">
            <?= t('sshkeys.modal_deploy.info') ?>
        </div>
        <div class="form-group">
            <label class="form-label"><?= t('sshkeys.modal_deploy.dest_label') ?></label>
            <div id="deploy-target" style="font-family:var(--font-mono);font-size:13px;color:var(--accent)"></div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= t('sshkeys.modal_deploy.password_label') ?></label>
            <input type="password" id="deploy-password" class="form-control"
                   placeholder="<?= h(t('sshkeys.modal_deploy.password_placeholder')) ?>">
        </div>
        <div class="flex gap-2" style="justify-content:flex-end;margin-top:20px">
            <button type="button" class="btn"
                onclick="document.getElementById('modal-deploy').classList.remove('show')"><?= t('common.cancel') ?></button>
            <button type="button" class="btn btn-primary" id="btn-deploy" onclick="deployKey()">
                <?= t('sshkeys.deploy_btn') ?>
            </button>
        </div>
        <div id="deploy-output" style="display:none;margin-top:16px">
            <div class="code-viewer" id="deploy-log" style="max-height:150px"></div>
        </div>
    </div>
</div>

<div id="modal-host-key" class="modal-overlay">
    <div class="modal">
        <div class="modal-title" id="host-key-modal-title"><?= t('sshkeys.host_keys.approve_modal_title') ?></div>
        <div class="alert alert-warning" style="font-size:12px">
            <?= t('sshkeys.host_keys.approve_warning') ?>
        </div>
        <div class="alert alert-info" style="font-size:12px">
            <?= t('sshkeys.host_keys.approve_info') ?>
        </div>
        <form method="POST">
            <input type="hidden" name="action" id="host-key-action" value="approve_host_key">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="host" id="host-key-host">
            <input type="hidden" name="port" id="host-key-port">
            <div style="display:grid;grid-template-columns:120px 1fr;gap:8px 12px;font-size:13px;margin-bottom:16px">
                <div style="color:var(--text2)">Host</div><div class="mono" id="host-key-display-host">-</div>
                <div style="color:var(--text2)"><?= t('hosts.table.port') ?></div><div class="mono" id="host-key-display-port">-</div>
                <div style="color:var(--text2)"><?= t('common.status') ?></div><div class="mono" id="host-key-display-status">-</div>
                <div style="color:var(--text2)"><?= t('themes.table.type') ?></div><div class="mono" id="host-key-display-type">-</div>
                <div style="color:var(--text2)"><?= t('sshkeys.host_keys.fingerprint') ?></div><div class="mono" id="host-key-display-fingerprint">-</div>
                <div style="color:var(--text2)"><?= t('sshkeys.host_keys.previous_label') ?></div><div class="mono" id="host-key-display-old-fingerprint">-</div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('sshkeys.host_keys.verified_pubkey_label') ?></label>
                <textarea name="public_key" id="host-key-public-key" class="form-control" rows="5"
                    placeholder="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI... admin@host" required></textarea>
                <div style="font-size:12px;color:var(--text2);margin-top:6px" id="host-key-help">
                    <?= t('sshkeys.host_keys.paste_hint') ?>
                </div>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn" id="host-key-load-btn" onclick="loadDetectedHostKey(this, document.getElementById('host-key-host').value, parseInt(document.getElementById('host-key-port').value || '22', 10), true)">
                    <?= t('sshkeys.host_keys.load_detected_btn') ?>
                </button>
                <button type="button" class="btn" onclick="document.getElementById('modal-host-key').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary" id="host-key-submit-label"><?= t('sshkeys.host_keys.approve_btn') ?></button>
            </div>
        </form>
    </div>
</div>

<script<?= cspNonceAttr() ?>>
function deleteKey(id) {
    document.getElementById('delete-key-id').value = id;
    document.getElementById('form-delete').submit();
}

function rejectHostKey(host, port) {
    document.getElementById('reject-host-key-host').value = host;
    document.getElementById('reject-host-key-port').value = port;
    document.getElementById('form-reject-host-key').submit();
}

function showPubkey(key) {
    document.getElementById('pubkey-content').textContent = key.public_key || '<?= h(t('sshkeys.js.pubkey_unavailable')) ?>';
    document.getElementById('pubkey-cmd').textContent = `echo "${key.public_key}" >> ~/.ssh/authorized_keys`;
    document.getElementById('modal-pubkey').classList.add('show');
}

function copyPubkeyFromModal() {
    const text = document.getElementById('pubkey-content').textContent;
    navigator.clipboard.writeText(text).then(() => toast('<?= h(t('sshkeys.js.key_copied')) ?>', 'success'));
}

function copyPubkey(btn, pubkey) {
    navigator.clipboard.writeText(pubkey).then(() => {
        btn.textContent = '✓ <?= h(t('sshkeys.js.copied')) ?>';
        setTimeout(() => btn.textContent = '<?= h(t('common.copy')) ?>', 2000);
    });
}

async function fetchDetectedHostKey(host, port) {
    return apiPost('/api/fetch_detected_host_key.php', { host, port });
}

async function loadDetectedHostKey(btn, host, port, reopen = false) {
    if (!host) return;
    const original = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span>';
    }
    try {
        const res = await fetchDetectedHostKey(host, port);
        if (!res.success) {
            toast(res.error || '<?= h(t('sshkeys.js.load_host_key_error')) ?>', 'error');
            return;
        }
        toast('<?= h(t('sshkeys.js.host_key_loaded')) ?>', 'success');
        if (reopen) {
            openHostKeyModal(res.record || {}, (res.record && res.record.approved_key_ref) ? 'replace' : 'approve');
        } else {
            window.location.reload();
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }
}

function formatHostKeyResult(res) {
    if (!res || !res.host_key) return res.output || res.error || '<?= h(t('sshkeys.js.no_output')) ?>';
    const hk = res.host_key;
    const lines = [
        hk.status || 'HOST_KEY',
        `Host: ${hk.host || '-'}`,
        `Port: ${hk.port || '-'}`,
    ];
    if (hk.key_type) lines.push(`Type: ${hk.key_type}`);
    if (hk.fingerprint) lines.push(`Fingerprint: ${hk.fingerprint}`);
    if (hk.previous_fingerprint) lines.push(`<?= h(t('sshkeys.js.old_fingerprint')) ?> ${hk.previous_fingerprint}`);
    if (res.output) lines.push('', res.output);
    return lines.join('\n');
}

async function testKey(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';
    const res = await apiPost('/api/test_ssh.php', { key_id: id });
    btn.disabled = false;
    btn.textContent = '<?= h(t('sshkeys.js.test_btn')) ?>';
    document.getElementById('test-output').textContent = formatHostKeyResult(res);
    document.getElementById('test-output').style.color = res.success ? 'var(--green)' : 'var(--red)';
    document.getElementById('modal-test').classList.add('show');
    toast(res.success ? '<?= h(t('sshkeys.js.ssh_success')) ?>' : '<?= h(t('sshkeys.js.ssh_failure')) ?>', res.success ? 'success' : 'error');
}

let currentDeployKeyId = null;

function openDeploy(id, user, host) {
    currentDeployKeyId = id;
    document.getElementById('deploy-target').textContent = `${user}@${host}`;
    document.getElementById('deploy-password').value = '';
    document.getElementById('deploy-output').style.display = 'none';
    document.getElementById('modal-deploy').classList.add('show');
}

async function deployKey() {
    const password = document.getElementById('deploy-password').value;
    if (!password) { toast('<?= h(t('sshkeys.js.password_required')) ?>', 'error'); return; }

    const btn = document.getElementById('btn-deploy');
    const log = document.getElementById('deploy-log');
    const out = document.getElementById('deploy-output');

    btn.disabled    = true;
    btn.innerHTML   = '<span class="spinner"></span> <?= h(t('sshkeys.js.deploying_short')) ?>';
    out.style.display = 'block';
    log.textContent = '<?= h(t('sshkeys.js.deploying')) ?>';

    const res = await apiPost('/api/deploy_key.php', {
        key_id:   currentDeployKeyId,
        password: password,
    });

    btn.disabled  = false;
    btn.innerHTML = '<?= h(t('sshkeys.js.deploy_btn')) ?>';
    log.textContent = formatHostKeyResult(res);
    log.style.color = res.success ? 'var(--green)' : 'var(--red)';
    toast(res.success ? '<?= h(t('sshkeys.js.deploy_success')) ?>' : '<?= h(t('sshkeys.js.deploy_failure')) ?>', res.success ? 'success' : 'error');

    // Clear password from memory immediately
    document.getElementById('deploy-password').value = '';
}

async function openHostKeyModal(record, mode) {
    if (record.host && record.port && !record.detected_public_key) {
        const fetched = await fetchDetectedHostKey(record.host, record.port);
        if (fetched && fetched.success && fetched.record) {
            record = fetched.record;
            toast('<?= h(t('sshkeys.js.host_key_preloaded')) ?>', 'success');
        }
    }
    document.getElementById('host-key-action').value = mode === 'replace' ? 'replace_host_key' : 'approve_host_key';
    document.getElementById('host-key-host').value = record.host || '';
    document.getElementById('host-key-port').value = record.port || 22;
    document.getElementById('host-key-display-host').textContent = record.host || '-';
    document.getElementById('host-key-display-port').textContent = record.port || '-';
    document.getElementById('host-key-display-status').textContent = record.status || '-';
    document.getElementById('host-key-display-type').textContent = record.detected_key_type || record.approved_key_type || '-';
    document.getElementById('host-key-display-fingerprint').textContent = record.detected_fingerprint || record.approved_fingerprint || '-';
    document.getElementById('host-key-display-old-fingerprint').textContent = record.previous_fingerprint || '-';
    document.getElementById('host-key-public-key').value = record.detected_public_key || '';
    document.getElementById('host-key-modal-title').textContent = mode === 'replace' ? '<?= h(t('sshkeys.js.replace_host_key')) ?>' : '<?= h(t('sshkeys.js.approve_host_key')) ?>';
    document.getElementById('host-key-submit-label').textContent = mode === 'replace' ? '<?= h(t('sshkeys.js.replace_btn')) ?>' : '<?= h(t('sshkeys.js.approve_btn')) ?>';
    document.getElementById('host-key-help').textContent = record.detected_public_key
        ? '<?= h(t('sshkeys.js.host_key_help_loaded')) ?>'
        : '<?= h(t('sshkeys.js.host_key_help_empty')) ?>';
    document.getElementById('modal-host-key').classList.add('show');
}

window.copyPubkey = copyPubkey;
window.copyPubkeyFromModal = copyPubkeyFromModal;
window.showPubkey = showPubkey;
window.testKey = testKey;
window.openDeploy = openDeploy;
window.deployKey = deployKey;
window.deleteKey = deleteKey;
window.rejectHostKey = rejectHostKey;
window.loadDetectedHostKey = loadDetectedHostKey;
</script>

<?php include 'layout_bottom.php'; ?>
