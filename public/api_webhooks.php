<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('settings.manage');

$flash = null;
$revealedSecret = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $userId = (int) Auth::currentUser()['id'];

    if ($action === 'create') {
        try {
            $hook = ApiWebhookManager::create([
                'name' => $_POST['name'] ?? '',
                'url' => $_POST['url'] ?? '',
                'events' => (array) ($_POST['events'] ?? []),
                'enabled' => !empty($_POST['enabled']),
            ], $userId);
            $revealedSecret = $hook['revealed_secret'] ?? null;
            $flash = ['type' => 'success', 'msg' => t('api_webhooks.created')];
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'msg' => t('api_webhooks.error_prefix') . $e->getMessage()];
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['hook_id'] ?? 0);
        try {
            ApiWebhookManager::update($id, [
                'name' => $_POST['name'] ?? '',
                'url' => $_POST['url'] ?? '',
                'events' => (array) ($_POST['events'] ?? []),
                'enabled' => !empty($_POST['enabled']),
            ]);
            $flash = ['type' => 'success', 'msg' => t('api_webhooks.updated')];
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'msg' => t('api_webhooks.error_prefix') . $e->getMessage()];
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['hook_id'] ?? 0);
        ApiWebhookManager::delete($id);
        $flash = ['type' => 'success', 'msg' => t('api_webhooks.deleted')];
    }

    if ($action === 'test') {
        $id = (int) ($_POST['hook_id'] ?? 0);
        $hook = ApiWebhookManager::getById($id);
        if ($hook) {
            $result = ApiWebhookManager::send($hook, 'webhook.test', ['message' => t('api_webhooks.test_payload_message'), 'at' => gmdate('c')]);
            $ok = $result['status'] >= 200 && $result['status'] < 300;
            $flash = [
                'type' => $ok ? 'success' : 'danger',
                'msg' => t('api_webhooks.test_sent', [
                    'status' => (int) $result['status'],
                    'detail' => $result['error'] ? ' / ' . $result['error'] : '',
                ]),
            ];
        }
    }
}

$hooks = ApiWebhookManager::getAll();
$events = ApiWebhookManager::EVENTS;

$title = t('api_webhooks.title');
$active = 'api_webhooks';
$actions = '<button class="btn btn-primary" onclick="document.getElementById(\'modal-hook\').classList.add(\'show\')">' . h(t('api_webhooks.new')) . '</button>';
include 'layout_top.php';
?>

<?php if ($revealedSecret): ?>
<div class="alert alert-success mb-4">
    <div style="font-weight:600;margin-bottom:8px"><?= t('api_webhooks.secret_title') ?></div>
    <div class="pubkey-box mono" style="word-break:break-all"><?= h($revealedSecret) ?></div>
    <div style="margin-top:10px">
        <button class="btn btn-sm btn-success"
            onclick="navigator.clipboard.writeText(<?= json_encode($revealedSecret) ?>).then(()=>{this.textContent=<?= json_encode(t('api_webhooks.copied')) ?>})">
            <?= t('api_webhooks.copy') ?>
        </button>
    </div>
    <div style="font-size:12px;color:var(--text2);margin-top:8px">
        <?= t('api_webhooks.signature_help') ?>
    </div>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <?= t('api_webhooks.list_title') ?>
        <span class="badge badge-blue"><?= count($hooks) ?></span>
    </div>
    <?php if (empty($hooks)): ?>
    <div class="empty-state">
        <div style="font-size:14px;margin:24px 0"><?= t('api_webhooks.empty') ?></div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-hook').classList.add('show')">
            <?= t('api_webhooks.new') ?>
        </button>
    </div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr>
            <th><?= t('api_webhooks.col_name') ?></th><th><?= t('api_webhooks.col_url') ?></th><th><?= t('api_webhooks.col_events') ?></th><th><?= t('api_webhooks.col_status') ?></th><th><?= t('api_webhooks.col_last_http') ?></th><th><?= t('api_webhooks.col_actions') ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($hooks as $h): ?>
        <tr>
            <td style="font-weight:500"><?= h($h['name']) ?></td>
            <td class="mono" style="font-size:11px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($h['url']) ?></td>
            <td style="font-size:11px;color:var(--text2)"><?= h(implode(', ', $h['events'])) ?></td>
            <td><span class="badge badge-<?= $h['enabled'] ? 'green' : 'gray' ?>"><?= $h['enabled'] ? t('api_webhooks.status_active') : t('api_webhooks.status_disabled') ?></span></td>
            <td style="font-size:12px"><?= isset($h['last_status']) ? (int) $h['last_status'] : '—' ?></td>
            <td>
                <div class="flex gap-2">
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="test">
                        <input type="hidden" name="hook_id" value="<?= (int) $h['id'] ?>">
                        <button class="btn btn-sm"><?= t('api_webhooks.action_test') ?></button>
                    </form>
                    <form method="post" style="display:inline" data-confirm-message="<?= h(t('api_webhooks.confirm_delete')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="hook_id" value="<?= (int) $h['id'] ?>">
                        <button class="btn btn-sm btn-danger"><?= t('api_webhooks.action_delete') ?></button>
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
    <?= t('api_webhooks.delivery_help') ?>
    <?= t('api_webhooks.available_events', ['events' => h(implode(', ', $events))]) ?>
</div>

<!-- Modal createation -->
<div id="modal-hook" class="modal-overlay">
    <div class="modal" style="max-width:640px">
        <div class="modal-title"><?= t('api_webhooks.modal_new_title') ?></div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label"><?= t('api_webhooks.field_name') ?></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('api_webhooks.field_url') ?></label>
                <input type="url" name="url" class="form-control" required placeholder="https://example.com/webhooks/fulgurite">
            </div>
            <div class="form-group">
                <label class="settings-toggle">
                    <input type="checkbox" name="enabled" value="1" checked>
                    <span><?= t('api_webhooks.enable_now') ?></span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('api_webhooks.field_events') ?></label>
                <div style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;padding:10px">
                    <?php foreach ($events as $event): ?>
                        <label class="policy-channel-toggle">
                            <input type="checkbox" name="events[]" value="<?= h($event) ?>">
                            <span class="mono" style="font-size:12px"><?= h($event) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn" onclick="document.getElementById('modal-hook').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('api_webhooks.create') ?></button>
            </div>
        </form>
    </div>
</div>

<?php include 'layout_bottom.php'; ?>
