<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();

$user = Auth::currentUser();
$userId = (int) $user['id'];
$userPermissions = Auth::resolvedPermissionsForUser(Auth::currentUserRecord());
$flash = null;
$revealedSecret = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        try {
            $scopes = (array) ($_POST['scopes'] ?? []);
            $expiresDays = (int) ($_POST['expires_days'] ?? 0);
            $expiresAt = $expiresDays > 0 ? gmdate('Y-m-d H:i:s', time() + $expiresDays * 86400) : null;
            $result = ApiTokenManager::create($userId, [
                'name' => $_POST['name'] ?? '',
                'scopes' => $scopes,
                'read_only' => !empty($_POST['read_only']),
                'allowed_ips' => $_POST['allowed_ips'] ?? '',
                'allowed_origins' => $_POST['allowed_origins'] ?? '',
                'rate_limit_per_minute' => (int) ($_POST['rate_limit'] ?? 0) ?: AppConfig::getApiDefaultRateLimit(),
                'expires_at' => $expiresAt,
            ], $userId);
            $revealedSecret = $result['secret'];
            $flash = ['type' => 'success', 'msg' => t('flash.api_tokens.created')];
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'msg' => t('common.error_prefix') . $e->getMessage()];
        }
    }

    if ($action === 'revoke') {
        $id = (int) ($_POST['token_id'] ?? 0);
        $token = ApiTokenManager::getById($id);
        if ($token && (int) $token['user_id'] === $userId) {
            ApiTokenManager::revoke($id, 'manual');
            $flash = ['type' => 'success', 'msg' => t('flash.api_tokens.revoked')];
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['token_id'] ?? 0);
        $token = ApiTokenManager::getById($id);
        if ($token && (int) $token['user_id'] === $userId) {
            ApiTokenManager::delete($id);
            $flash = ['type' => 'success', 'msg' => t('flash.api_tokens.deleted')];
        }
    }
}

$tokens = ApiTokenManager::listForUser($userId);
$grouped = ApiScopes::grouped();
$apiEnabled = AppConfig::isApiEnabled();

$title = t('api_tokens.title');
$active = 'api_tokens';
$actions = '<button class="btn btn-primary" onclick="document.getElementById(\'modal-create\').classList.add(\'show\')">' . h(t('api_tokens.new_btn')) . '</button>';
include 'layout_top.php';
?>

<?php if (!$apiEnabled): ?>
<div class="alert alert-warning mb-4">
    <?= t('api_tokens.api_disabled_warning') ?>
    <a href="<?= routePath('/settings.php', ['tab' => 'api']) ?>"><?= t('api_tokens.api_disabled_link') ?></a>.
</div>
<?php endif; ?>

<?php if ($revealedSecret): ?>
<div class="alert alert-success mb-4">
    <div style="font-weight:600;margin-bottom:8px"><?= t('api_tokens.revealed_title') ?></div>
    <div class="pubkey-box mono" style="word-break:break-all"><?= h($revealedSecret) ?></div>
    <div style="margin-top:10px">
        <button class="btn btn-sm btn-success"
            onclick="navigator.clipboard.writeText(<?= json_encode($revealedSecret) ?>).then(()=>{this.textContent='<?= h(t('api_tokens.copy_done')) ?>'})">
            <?= t('api_tokens.copy_btn') ?>
        </button>
    </div>
    <div style="font-size:12px;color:var(--text2);margin-top:8px">
        <?= t('api_tokens.bearer_hint') ?> <code>Authorization: Bearer <?= h($revealedSecret) ?></code>.
        <?= t('api_tokens.secret_storage_hint') ?>
    </div>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <?= t('api_tokens.my_tokens') ?>
        <span class="badge badge-blue"><?= count($tokens) ?></span>
    </div>
    <?php if (empty($tokens)): ?>
    <div class="empty-state">
        <div style="font-size:14px;margin:24px 0">
            <?= t('api_tokens.empty') ?>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-create').classList.add('show')">
            <?= t('api_tokens.create_btn') ?>
        </button>
    </div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr>
            <th><?= t('api_tokens.table.name') ?></th><th><?= t('api_tokens.table.token') ?></th><th><?= t('api_tokens.table.scopes') ?></th><th><?= t('api_tokens.table.readonly') ?></th>
            <th><?= t('api_tokens.table.expires') ?></th><th><?= t('api_tokens.table.last_used') ?></th><th><?= t('common.status') ?></th><th><?= t('common.actions') ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($tokens as $t): ?>
        <tr>
            <td style="font-weight:500"><?= h($t['name']) ?></td>
            <td class="mono" style="font-size:12px"><?= h($t['display_token']) ?></td>
            <td style="font-size:11px;color:var(--text2)">
                <?= h(implode(', ', array_slice($t['scopes'], 0, 4))) ?>
                <?= count($t['scopes']) > 4 ? ' +' . (count($t['scopes']) - 4) : '' ?>
            </td>
            <td><?= $t['read_only'] ? t('common.yes') : '—' ?></td>
            <td style="font-size:12px"><?= $t['expires_at'] ? formatDate($t['expires_at']) : '—' ?></td>
            <td style="font-size:12px"><?= $t['last_used_at'] ? formatDate($t['last_used_at']) : '—' ?></td>
            <td>
                <?php if ($t['is_revoked']): ?>
                    <span class="badge badge-red"><?= t('api_tokens.status.revoked') ?></span>
                <?php elseif ($t['is_expired']): ?>
                    <span class="badge badge-yellow"><?= t('api_tokens.status.expired') ?></span>
                <?php else: ?>
                    <span class="badge badge-green"><?= t('api_tokens.status.active') ?></span>
                <?php endif; ?>
            </td>
            <td>
                <div class="flex gap-2">
                    <?php if (!$t['is_revoked']): ?>
                    <form method="post" style="display:inline" data-confirm-message="<?= h(t('api_tokens.revoke_confirm')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="revoke">
                        <input type="hidden" name="token_id" value="<?= (int) $t['id'] ?>">
                        <button class="btn btn-sm btn-warning"><?= t('api_tokens.revoke') ?></button>
                    </form>
                    <?php endif; ?>
                    <form method="post" style="display:inline" data-confirm-message="<?= h(t('api_tokens.delete_confirm')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="token_id" value="<?= (int) $t['id'] ?>">
                        <button class="btn btn-sm btn-danger"><?= t('common.delete') ?></button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="alert alert-info" style="font-size:12px">
    <strong><?= t('api_tokens.usage_hint') ?></strong> <?= t('api_tokens.usage_desc') ?> <code>/api/v1/&hellip;</code>.
    <?= t('api_tokens.docs_label') ?> :
    <a href="/api/v1/docs" target="_blank">/api/v1/docs</a> —
    <?= t('api_tokens.openapi_label') ?> :
    <a href="/api/v1/openapi.json" target="_blank">/api/v1/openapi.json</a>
</div>

<!-- Modal createation -->
<div id="modal-create" class="modal-overlay">
    <div class="modal" style="max-width:720px">
        <div class="modal-title"><?= t('api_tokens.modal.title') ?></div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label"><?= t('api_tokens.modal.name_label') ?></label>
                <input type="text" name="name" class="form-control" required placeholder="ex: Backup Reporting CI">
            </div>

            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('api_tokens.modal.expires_label') ?></label>
                    <input type="number" name="expires_days" class="form-control" min="0" max="3650"
                        value="<?= (int) AppConfig::getApiDefaultTokenLifetimeDays() ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('api_tokens.modal.rate_limit_label') ?></label>
                    <input type="number" name="rate_limit" class="form-control" min="1" max="10000"
                        value="<?= (int) AppConfig::getApiDefaultRateLimit() ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= t('api_tokens.modal.ips_label') ?></label>
                <input type="text" name="allowed_ips" class="form-control" placeholder="10.0.0.0/24, 192.168.1.10">
            </div>

            <div class="form-group">
                <label class="form-label"><?= t('api_tokens.modal.origins_label') ?></label>
                <input type="text" name="allowed_origins" class="form-control" placeholder="https://app.example.com">
            </div>

            <div class="form-group">
                <label class="settings-toggle">
                    <input type="checkbox" name="read_only" value="1">
                    <span><?= t('api_tokens.modal.readonly_label') ?></span>
                </label>
            </div>

            <div class="form-group">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <label class="form-label" style="margin:0"><?= t('api_tokens.modal.scopes_label') ?></label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);cursor:pointer;user-select:none">
                        <input type="checkbox" id="scopes-toggle-all" onchange="
                            var boxes = document.querySelectorAll('#scopes-list input[type=checkbox]:not(:disabled)');
                            boxes.forEach(function(b){ b.checked = this.checked; }.bind(this));
                        ">
                        <?= t('api_tokens.modal.select_all') ?>
                    </label>
                </div>
                <div id="scopes-list" style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;padding:12px"
                    onchange="
                        var boxes = document.querySelectorAll('#scopes-list input[type=checkbox]:not(:disabled)');
                        var all = Array.from(boxes).every(function(b){ return b.checked; });
                        document.getElementById('scopes-toggle-all').checked = all;
                    ">
                <?php foreach ($grouped as $groupName => $items): ?>
                    <div style="font-weight:600;font-size:11px;color:var(--text2);margin:10px 0 4px;text-transform:uppercase;letter-spacing:.5px">
                        <?= h($groupName) ?>
                    </div>
                    <?php foreach ($items as $entry): ?>
                        <?php
                        $scope = $entry['scope'];
                        $required = $entry['permission'];
                        $allowed = $required === null || !empty($userPermissions[$required]);
                        ?>
                        <label class="policy-channel-toggle" style="opacity:<?= $allowed ? '1' : '.4' ?>">
                            <input type="checkbox" name="scopes[]" value="<?= h($scope) ?>" <?= $allowed ? '' : 'disabled' ?>>
                            <span>
                                <code style="font-size:11px"><?= h($scope) ?></code>
                                <span style="font-size:11px;color:var(--text2)"> &mdash; <?= h($entry['label']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </div>
            </div>

            <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn" onclick="document.getElementById('modal-create').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('api_tokens.modal.submit') ?></button>
            </div>
        </form>
    </div>
</div>

<?php include 'layout_bottom.php'; ?>
