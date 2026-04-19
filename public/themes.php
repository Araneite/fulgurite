<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();

// Permission to manage themes (install, delete, approve). Users
// without this permission see installed themes as read-only and can submit
// a request, but do not see server-blocked install surfaces.
$canManage = Auth::hasPermission('themes.manage');
$isAdmin = $canManage; // backward-compatible alias with existing rendering
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

$flash = null;

// Download a request zip (admin only, GET).
if (($_GET['action'] ?? '') === 'download_request_file') {
    if (!$canManage) {
        http_response_code(403);
        exit('Acces refuse');
    }
    $reqId = (int) ($_GET['id'] ?? 0);
    $req = $reqId > 0 ? ThemeRequestManager::getById($reqId) : null;
    if ($req === null || (string) $req['source_type'] !== 'upload' || empty($req['source_file'])) {
        http_response_code(404);
        exit('Demande ou fichier introuvable');
    }
    $path = ThemePackage::pendingFilePath((string) $req['source_file']);
    if ($path === null) {
        http_response_code(404);
        exit('Fichier en attente introuvable (expire ou supprime ?)');
    }
    Auth::log('theme_request_download', "Telechargement du zip de la demande #$reqId");
    $downloadName = 'theme-request-' . $reqId . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

$tab = (string) ($_GET['tab'] ?? ($canManage ? 'installed' : 'my-requests'));
$allowedTabs = $canManage
    ? ['installed', 'store', 'requests', 'my-requests']
    : ['installed', 'my-requests'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = $canManage ? 'installed' : 'my-requests';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['install_json', 'install_package', 'install_url', 'install_store', 'delete', 'approve', 'reject'], true)) {
        if (!$isAdmin) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    switch ($action) {
        case 'install_json': {
            $overwrite = !empty($_POST['overwrite']);
            $jsonContent = '';
            if (!empty($_FILES['theme_file']['tmp_name']) && is_uploaded_file($_FILES['theme_file']['tmp_name'])) {
                if (($_FILES['theme_file']['size'] ?? 0) > 64 * 1024) {
                    $flash = ['type' => 'danger', 'msg' => t('flash.themes.file_too_large')];
                } else {
                    $jsonContent = (string) file_get_contents($_FILES['theme_file']['tmp_name']);
                }
            }
            if ($flash === null && $jsonContent === '' && isset($_POST['theme_json'])) {
                $raw = (string) $_POST['theme_json'];
                if (strlen($raw) > 64 * 1024) {
                    $flash = ['type' => 'danger', 'msg' => t('flash.themes.json_too_large')];
                } else {
                    $jsonContent = $raw;
                }
            }
            if ($flash === null) {
                if (trim($jsonContent) === '') {
                    $flash = ['type' => 'danger', 'msg' => t('flash.themes.no_content')];
                } else {
                    $result = ThemeManager::install($jsonContent, $overwrite);
                    if ($result['ok']) {
                        Auth::log('theme_install', 'Installation du theme ' . ($result['id'] ?? '?'));
                        $flash = ['type' => 'success', 'msg' => t('flash.themes.installed', ['id' => $result['id']])];
                    } else {
                        $flash = ['type' => 'danger', 'msg' => t('flash.themes.install_error', ['details' => implode(', ', $result['errors'] ?? ['erreur'])])];
                    }
                }
            }
            break;
        }

        case 'install_package': {
            $overwrite = !empty($_POST['overwrite']);
            if (empty($_FILES['package_file']['tmp_name']) || !is_uploaded_file($_FILES['package_file']['tmp_name'])) {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.no_zip')];
                break;
            }
            if (($_FILES['package_file']['size'] ?? 0) > ThemePackage::MAX_ZIP_BYTES) {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.archive_too_large')];
                break;
            }
            $extracted = ThemePackage::extract((string) $_FILES['package_file']['tmp_name']);
            if (!$extracted['ok']) {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.extract_error', ['details' => implode(', ', $extracted['errors'] ?? ['erreur'])])];
                break;
            }
            $result = ThemePackage::install($extracted['path'], $overwrite);
            ThemePackage::removeDirRecursive($extracted['path']);
            if ($result['ok']) {
                Auth::log('theme_install_package', 'Installation du paquet ' . ($result['id'] ?? '?'));
                $flash = ['type' => 'success', 'msg' => t('flash.themes.installed_from_archive', ['id' => $result['id']])];
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.install_error', ['details' => implode(', ', $result['errors'] ?? ['erreur'])])];
            }
            break;
        }

        case 'install_url': {
            $url = trim((string) ($_POST['source_url'] ?? ''));
            $overwrite = !empty($_POST['overwrite']);
            $fetched = ThemePackage::fetchUrl($url);
            if (!$fetched['ok']) {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.download_error', ['details' => implode(', ', $fetched['errors'] ?? ['erreur'])])];
                break;
            }
            $extracted = ThemePackage::extract($fetched['path']);
            @unlink($fetched['path']);
            if (!$extracted['ok']) {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.extract_error', ['details' => implode(', ', $extracted['errors'] ?? ['erreur'])])];
                break;
            }
            $result = ThemePackage::install($extracted['path'], $overwrite);
            ThemePackage::removeDirRecursive($extracted['path']);
            if ($result['ok']) {
                Auth::log('theme_install_url', 'Installation depuis URL : ' . $url);
                $flash = ['type' => 'success', 'msg' => t('flash.themes.installed_from_url', ['id' => $result['id']])];
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.install_error', ['details' => implode(', ', $result['errors'] ?? ['erreur'])])];
            }
            break;
        }

        case 'install_store': {
            $entryId = (string) ($_POST['store_id'] ?? '');
            $overwrite = !empty($_POST['overwrite']);
            $result = ThemeStore::install($entryId, $overwrite);
            if ($result['ok']) {
                Auth::log('theme_install_store', 'Installation depuis le store : ' . $entryId);
                $flash = ['type' => 'success', 'msg' => t('flash.themes.installed_from_store', ['id' => $result['id']])];
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.store_error', ['details' => implode(', ', $result['errors'] ?? ['erreur'])])];
            }
            $tab = 'store';
            break;
        }

        case 'delete': {
            $id = (string) ($_POST['id'] ?? '');
            $result = ThemeManager::delete($id);
            if ($result['ok']) {
                Auth::log('theme_delete', "Suppression du theme $id");
                $flash = ['type' => 'success', 'msg' => t('flash.themes.deleted', ['id' => $id])];
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.delete_error', ['details' => implode(', ', $result['errors'] ?? ['erreur'])])];
            }
            break;
        }

        case 'approve': {
            $reqId = (int) ($_POST['request_id'] ?? 0);
            $notes = (string) ($_POST['notes'] ?? '');
            $overwrite = !empty($_POST['overwrite']);
            $result = ThemeRequestManager::approve($reqId, $currentUserId, $notes, $overwrite);
            if ($result['ok']) {
                Auth::log('theme_request_approve', "Demande #$reqId approuvee (theme {$result['theme_id']})");
                $flash = ['type' => 'success', 'msg' => t('flash.themes.approved', ['id' => $result['theme_id']])];
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.approve_error', ['details' => implode(', ', $result['errors'] ?? ['erreur'])])];
            }
            $tab = 'requests';
            break;
        }

        case 'reject': {
            $reqId = (int) ($_POST['request_id'] ?? 0);
            $notes = (string) ($_POST['notes'] ?? '');
            $result = ThemeRequestManager::reject($reqId, $currentUserId, $notes);
            if ($result['ok']) {
                Auth::log('theme_request_reject', "Demande #$reqId rejetee");
                $flash = ['type' => 'success', 'msg' => t('flash.themes.rejected')];
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.reject_error', ['details' => implode(', ', $result['errors'] ?? ['erreur'])])];
            }
            $tab = 'requests';
            break;
        }

        case 'submit_request': {
            $sourceType = (string) ($_POST['source_type'] ?? 'upload');
            $themeName = (string) ($_POST['theme_name'] ?? '');
            $description = (string) ($_POST['description'] ?? '');
            $sourceUrl = (string) ($_POST['source_url'] ?? '');
            $tmpPath = null;

            if ($sourceType === 'upload') {
                if (empty($_FILES['package_file']['tmp_name']) || !is_uploaded_file($_FILES['package_file']['tmp_name'])) {
                    $flash = ['type' => 'danger', 'msg' => t('flash.themes.no_file')];
                    break;
                }
                if (($_FILES['package_file']['size'] ?? 0) > ThemePackage::MAX_ZIP_BYTES) {
                    $flash = ['type' => 'danger', 'msg' => t('flash.themes.archive_too_large')];
                    break;
                }
                $tmpPath = (string) $_FILES['package_file']['tmp_name'];
            }

            $result = ThemeRequestManager::submit(
                $currentUserId, $sourceType, $tmpPath, $sourceUrl, $themeName, $description
            );
            if ($result['ok']) {
                Auth::log('theme_request_submit', "Demande #{$result['id']} soumise ($sourceType)");
                $flash = ['type' => 'success', 'msg' => t('flash.themes.request_submitted')];
                $tab = 'my-requests';
            } else {
                $flash = ['type' => 'danger', 'msg' => t('flash.themes.request_error', ['details' => implode(', ', $result['errors'] ?? ['erreur'])])];
            }
            break;
        }
    }
}

$themes = ThemeManager::listThemes();
$storeEntries = ThemeStore::listEntries();
$myRequests = ThemeRequestManager::listForUser($currentUserId);
$allRequests = $isAdmin ? ThemeRequestManager::listAll() : [];
$pendingCount = $isAdmin ? ThemeRequestManager::countPending() : 0;

$title = t('themes.title');
$active = 'themes';
include 'layout_top.php';
?>

<div class="tabs" style="display:flex;gap:4px;border-bottom:1px solid var(--border);margin-bottom:16px">
    <a href="?tab=installed" class="tab-link <?= $tab === 'installed' ? 'active' : '' ?>"
       style="padding:10px 16px;text-decoration:none;border-bottom:2px solid <?= $tab === 'installed' ? 'var(--accent)' : 'transparent' ?>;color:<?= $tab === 'installed' ? 'var(--text)' : 'var(--text2)' ?>">
        <?= t('themes.tab.installed') ?> <span class="badge badge-gray"><?= count($themes) ?></span>
    </a>
    <?php if ($isAdmin): ?>
    <a href="?tab=store" class="tab-link <?= $tab === 'store' ? 'active' : '' ?>"
       style="padding:10px 16px;text-decoration:none;border-bottom:2px solid <?= $tab === 'store' ? 'var(--accent)' : 'transparent' ?>;color:<?= $tab === 'store' ? 'var(--text)' : 'var(--text2)' ?>">
        <?= t('themes.tab.store') ?> <span class="badge badge-blue"><?= count($storeEntries) ?></span>
    </a>
    <a href="?tab=requests" class="tab-link <?= $tab === 'requests' ? 'active' : '' ?>"
       style="padding:10px 16px;text-decoration:none;border-bottom:2px solid <?= $tab === 'requests' ? 'var(--accent)' : 'transparent' ?>;color:<?= $tab === 'requests' ? 'var(--text)' : 'var(--text2)' ?>">
        <?= t('themes.tab.requests') ?>
        <?php if ($pendingCount > 0): ?>
        <span class="badge badge-yellow"><?= $pendingCount ?></span>
        <?php endif; ?>
    </a>
    <?php endif; ?>
    <a href="?tab=my-requests" class="tab-link <?= $tab === 'my-requests' ? 'active' : '' ?>"
       style="padding:10px 16px;text-decoration:none;border-bottom:2px solid <?= $tab === 'my-requests' ? 'var(--accent)' : 'transparent' ?>;color:<?= $tab === 'my-requests' ? 'var(--text)' : 'var(--text2)' ?>">
        <?= t('themes.tab.my_requests') ?> <span class="badge badge-gray"><?= count($myRequests) ?></span>
    </a>
</div>

<?php if ($flash !== null): ?>
<div class="alert alert-<?= h((string) $flash['type']) ?>" style="margin-bottom:16px">
    <?= h((string) $flash['msg']) ?>
</div>
<?php endif; ?>

<?php if ($tab === 'installed'): ?>
<div class="card mb-4">
    <div class="card-header">
        <?= t('themes.installed.header') ?>
        <span class="badge badge-blue"><?= count($themes) ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($themes)): ?>
        <div class="empty-state"><div><?= t('themes.installed.empty') ?></div></div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('themes.table.id') ?></th>
                    <th><?= t('common.name') ?></th>
                    <th><?= t('themes.table.type') ?></th>
                    <th><?= t('themes.table.author') ?></th>
                    <th><?= t('themes.table.version') ?></th>
                    <th><?= t('themes.table.preview') ?></th>
                    <?php if ($isAdmin): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($themes as $theme): ?>
                <tr>
                    <td style="font-family:var(--font-mono);font-size:12px">
                        <?= h((string) $theme['id']) ?>
                        <?php if ($theme['builtin']): ?><span class="badge badge-gray" style="margin-left:6px"><?= t('themes.badge.builtin') ?></span><?php endif; ?>
                        <?php if (($theme['installation_mode'] ?? '') === 'trusted_local'): ?><span class="badge badge-red" style="margin-left:6px">trusted local</span><?php endif; ?>
                        <?php if (($theme['installation_mode'] ?? '') === 'shipped'): ?><span class="badge badge-purple" style="margin-left:6px"><?= t('themes.badge.shipped') ?></span><?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:500"><?= h((string) $theme['name']) ?></div>
                        <?php if (!empty($theme['description'])): ?>
                        <div style="font-size:12px;color:var(--text2)"><?= h((string) $theme['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px">
                        <?php if (($theme['type'] ?? 'variables') === 'advanced'): ?>
                            <span class="badge badge-purple">advanced</span>
                            <?php if (!empty($theme['has_css'])): ?><span class="badge badge-blue" style="margin-left:4px">CSS</span><?php endif; ?>
                            <?php if (!empty($theme['executes_php'])): ?><span class="badge badge-red" style="margin-left:4px">code PHP trusted</span><?php endif; ?>
                            <?php if (!empty($theme['slots'])): ?><span class="badge badge-green" style="margin-left:4px">slots : <?= count($theme['slots']) ?></span><?php endif; ?>
                            <?php if (!empty($theme['parts'])): ?><span class="badge badge-green" style="margin-left:4px">parts : <?= count($theme['parts']) ?></span><?php endif; ?>
                            <?php if (!empty($theme['pages'])): ?><span class="badge badge-yellow" style="margin-left:4px">pages : <?= count($theme['pages']) ?></span><?php endif; ?>
                            <?php if (empty($theme['executes_php'])): ?><span class="badge badge-gray" style="margin-left:4px">safe package</span><?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-gray">variables</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--text2)"><?= h((string) ($theme['author'] ?? '')) ?></td>
                    <td style="font-size:12px;color:var(--text2)"><?= h((string) ($theme['version'] ?? '')) ?></td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <?php foreach (['--bg', '--bg2', '--accent', '--green', '--red', '--yellow', '--purple'] as $varName): ?>
                                <?php if (isset($theme['variables'][$varName])): ?>
                                <span title="<?= h($varName . ': ' . $theme['variables'][$varName]) ?>"
                                      style="display:inline-block;width:18px;height:18px;border-radius:4px;border:1px solid var(--border);background:<?= h((string) $theme['variables'][$varName]) ?>"></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <?php if ($isAdmin): ?>
                    <td style="text-align:right">
                        <?php if (!$theme['builtin'] && ($theme['installation_mode'] ?? '') !== 'trusted_local'): ?>
                        <form method="POST" style="display:inline" data-confirm-message="<?= h(t('themes.delete_confirm', ['id' => (string) $theme['id']])) ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= h((string) $theme['id']) ?>">
                            <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:12px"><?= t('common.delete') ?></button>
                        </form>
                        <?php elseif (($theme['installation_mode'] ?? '') === 'trusted_local'): ?>
                        <span style="font-size:11px;color:var(--text2)"><?= t('themes.delete_trusted_hint') ?></span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="card mb-4">
    <div class="card-header"><?= t('themes.install_json.header') ?></div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:12px">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="install_json">
            <div class="form-group">
                <label class="form-label"><?= t('themes.install_json.file_label') ?></label>
                <input type="file" name="theme_file" accept="application/json,.json" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('themes.install_json.paste_label') ?></label>
                <textarea name="theme_json" class="form-control" rows="6" style="font-family:var(--font-mono);font-size:12px"></textarea>
            </div>
            <label class="settings-toggle">
                <input type="checkbox" name="overwrite" value="1">
                <span><?= t('themes.overwrite_label') ?></span>
            </label>
            <div style="text-align:right">
                <button type="submit" class="btn btn-primary"><?= t('themes.install_json.submit_btn') ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('themes.install_package.header') ?></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text2);margin:0 0 12px">
            <?= t('themes.install_package.desc', ['dir' => h(ThemeManager::trustedThemesDir())]) ?>
        </p>
        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:12px">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="install_package">
            <div class="form-group">
                <label class="form-label"><?= t('themes.install_package.file_label', ['size' => (int) (ThemePackage::MAX_ZIP_BYTES / 1024)]) ?></label>
                <input type="file" name="package_file" accept=".zip,application/zip" class="form-control" required>
            </div>
            <label class="settings-toggle">
                <input type="checkbox" name="overwrite" value="1">
                <span><?= t('themes.overwrite_label') ?></span>
            </label>
            <div style="text-align:right">
                <button type="submit" class="btn btn-primary"><?= t('themes.install_package.submit_btn') ?></button>
            </div>
        </form>

        <hr style="border:none;border-top:1px solid var(--border);margin:20px 0">

        <form method="POST" style="display:flex;flex-direction:column;gap:12px">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="install_url">
            <div class="form-group">
                <label class="form-label"><?= t('themes.install_url.url_label') ?></label>
                <input type="url" name="source_url" class="form-control" placeholder="https://github.com/user/mon-theme">
                <div style="font-size:12px;color:var(--text2);margin-top:4px">
                    <?= t('themes.install_url.url_hint') ?>
                </div>
            </div>
            <label class="settings-toggle">
                <input type="checkbox" name="overwrite" value="1">
                <span><?= t('themes.overwrite_label') ?></span>
            </label>
            <div style="text-align:right">
                <button type="submit" class="btn btn-primary"><?= t('themes.install_url.submit_btn') ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('themes.trusted_local.header') ?></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text2);margin:0 0 12px">
            <?= t('themes.trusted_local.desc', ['dir' => h(ThemeManager::trustedThemesDir())]) ?>
        </p>
        <div style="display:grid;gap:12px">
            <div style="padding:12px;border:1px solid var(--border);border-radius:8px;background:var(--bg2)">
                <div style="font-weight:600;margin-bottom:8px"><?= t('themes.trusted_local.workflow_title') ?></div>
                <ol style="margin:0;padding-left:18px;font-size:13px;color:var(--text2)">
                    <li><?= t('themes.trusted_local.step1') ?></li>
                    <li><?= t('themes.trusted_local.step2') ?></li>
                    <li><?= t('themes.trusted_local.step3') ?></li>
                    <li><?= t('themes.trusted_local.step4') ?></li>
                </ol>
            </div>
            <div style="padding:12px;border:1px solid var(--border);border-radius:8px;background:var(--bg2)">
                <div style="font-weight:600;margin-bottom:8px"><?= t('themes.trusted_local.example_title') ?></div>
                <code style="display:block;font-family:var(--font-mono);font-size:12px;white-space:pre-wrap">cp -R src/themes_builtin/horizon data/themes_trusted/mon-theme</code>
            </div>
            <div style="font-size:12px;color:var(--text2)">
                <?= t('themes.trusted_local.footer') ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if ($tab === 'store' && $isAdmin): ?>
<div class="card mb-4">
    <div class="card-header">
        <?= t('themes.store.header') ?>
        <span class="badge badge-blue"><?= count($storeEntries) ?></span>
    </div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text2);margin-bottom:16px">
            <?= t('themes.store.desc', ['dir' => h(ThemeManager::trustedThemesDir())]) ?>
        </p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
            <?php foreach ($storeEntries as $entry): ?>
            <div style="border:1px solid var(--border);border-radius:8px;padding:16px;background:var(--bg2)">
                <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:8px">
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:14px"><?= h((string) $entry['name']) ?></div>
                        <div style="font-size:11px;color:var(--text2);font-family:var(--font-mono)"><?= h((string) $entry['id']) ?></div>
                    </div>
                    <?php if ($entry['type'] === 'advanced'): ?>
                        <span class="badge badge-purple">advanced safe</span>
                    <?php else: ?>
                        <span class="badge badge-gray">variables</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($entry['description'])): ?>
                <div style="font-size:12px;color:var(--text2);margin-bottom:10px;min-height:32px"><?= h((string) $entry['description']) ?></div>
                <?php endif; ?>
                <?php if (!empty($entry['preview_variables'])): ?>
                <div style="display:flex;gap:4px;margin-bottom:10px">
                    <?php foreach ($entry['preview_variables'] as $varName => $value): ?>
                    <span title="<?= h($varName) ?>" style="display:inline-block;width:20px;height:20px;border-radius:4px;border:1px solid var(--border);background:<?= h($value) ?>"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div style="font-size:11px;color:var(--text2);margin-bottom:10px">
                    <?= h((string) $entry['author']) ?> - v<?= h((string) $entry['version']) ?>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="install_store">
                    <input type="hidden" name="store_id" value="<?= h((string) $entry['id']) ?>">
                    <label style="font-size:11px;display:flex;align-items:center;gap:4px;margin-bottom:6px">
                        <input type="checkbox" name="overwrite" value="1">
                        <span><?= t('themes.overwrite_short') ?></span>
                    </label>
                    <button type="submit" class="btn btn-primary" style="width:100%;padding:6px;font-size:12px"><?= t('themes.store.install_btn') ?></button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'requests' && $isAdmin): ?>
<div class="card mb-4">
    <div class="card-header">
        <?= t('themes.requests.header') ?>
        <?php if ($pendingCount > 0): ?><span class="badge badge-yellow"><?= t('themes.requests.pending_badge', ['count' => $pendingCount]) ?></span><?php endif; ?>
    </div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text2);margin:0 0 16px">
            <?= t('themes.requests.desc') ?>
        </p>
        <?php if (empty($allRequests)): ?>
        <div class="empty-state"><div><?= t('themes.requests.empty') ?></div></div>
        <?php else: ?>
        <?php foreach ($allRequests as $req): ?>
        <div style="border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px;background:var(--bg2)">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px">
                <div style="flex:1">
                    <div style="font-weight:600;font-size:14px"><?= h((string) $req['theme_name']) ?></div>
                    <div style="font-size:12px;color:var(--text2)">
                        #<?= (int) $req['id'] ?>
                        <?php $reqName = trim(((string) ($req['requester_first_name'] ?? '')) . ' ' . ((string) ($req['requester_last_name'] ?? ''))); ?>
                        - <?= t('themes.requests.by') ?> <?= h($reqName !== '' ? $reqName : (string) ($req['requester_username'] ?? '?')) ?>
                        - <?= h(formatDate((string) $req['created_at'])) ?>
                    </div>
                </div>
                <div>
                    <?php $status = (string) $req['status']; ?>
                    <?php if ($status === 'pending'): ?>
                        <span class="badge badge-yellow"><?= t('themes.requests.status.pending') ?></span>
                    <?php elseif ($status === 'approved'): ?>
                        <span class="badge badge-green"><?= t('themes.requests.status.approved') ?></span>
                    <?php else: ?>
                        <span class="badge badge-red"><?= t('themes.requests.status.rejected') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($req['theme_description'])): ?>
            <div style="font-size:13px;color:var(--text2);margin-bottom:8px"><?= h((string) $req['theme_description']) ?></div>
            <?php endif; ?>

            <div style="font-size:12px;color:var(--text2);margin-bottom:12px">
                <?= t('themes.requests.source_label') ?>
                <?php if ($req['source_type'] === 'upload'): ?>
                    <span class="badge badge-gray">upload</span>
                    <?php if (!empty($req['source_file'])): ?>
                        <code style="font-family:var(--font-mono);font-size:11px">data/themes_pending/<?= h((string) $req['source_file']) ?></code>
                        <?php if ($status === 'pending'): ?>
                            <a href="?action=download_request_file&amp;id=<?= (int) $req['id'] ?>"
                               class="btn btn-secondary"
                               style="padding:2px 10px;font-size:11px;margin-left:6px"
                               title="<?= h(t('themes.requests.download_zip_title')) ?>"><?= t('themes.requests.download_zip') ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge badge-blue">URL</span>
                    <a href="<?= h((string) $req['source_url']) ?>" target="_blank" rel="noopener" style="font-size:12px"><?= h((string) $req['source_url']) ?></a>
                <?php endif; ?>
            </div>

            <?php if ($status === 'pending'): ?>
            <form method="POST" style="display:flex;gap:8px;align-items:flex-start">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                <input type="text" name="notes" class="form-control" placeholder="<?= h(t('themes.requests.notes_placeholder')) ?>" style="flex:1">
                <label style="font-size:11px;display:flex;align-items:center;gap:4px;white-space:nowrap;padding:8px">
                    <input type="checkbox" name="overwrite" value="1"><span><?= t('themes.overwrite_short') ?></span>
                </label>
                <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding:6px 14px"><?= t('themes.requests.approve_btn') ?></button>
                <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding:6px 14px"
                        data-confirm-message="<?= h(t('themes.requests.reject_confirm')) ?>"><?= t('themes.requests.reject_btn') ?></button>
            </form>
            <?php else: ?>
                <?php if (!empty($req['review_notes'])): ?>
                <div style="font-size:12px;color:var(--text2);padding:8px;background:var(--bg3);border-radius:4px">
                    <strong><?= t('themes.requests.notes_label') ?></strong> <?= h((string) $req['review_notes']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($req['installed_theme_id'])): ?>
                <div style="font-size:12px;margin-top:6px">
                    <?= t('themes.requests.installed_label') ?> <code style="font-family:var(--font-mono)"><?= h((string) $req['installed_theme_id']) ?></code>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'my-requests'): ?>
<div class="card mb-4">
    <div class="card-header"><?= t('themes.my_requests.header') ?></div>
    <div class="card-body">
        <?php if (empty($myRequests)): ?>
        <div class="empty-state" style="padding:12px"><div><?= t('themes.my_requests.empty') ?></div></div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>#</th><th><?= t('common.name') ?></th><th><?= t('themes.table.source') ?></th><th><?= t('common.status') ?></th><th><?= t('common.date') ?></th><th><?= t('themes.requests.notes_label') ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($myRequests as $req): ?>
                <tr>
                    <td><?= (int) $req['id'] ?></td>
                    <td><?= h((string) $req['theme_name']) ?></td>
                    <td>
                        <?php if ($req['source_type'] === 'upload'): ?>
                            <span class="badge badge-gray">upload</span>
                        <?php else: ?>
                            <span class="badge badge-blue">URL</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $status = (string) $req['status']; ?>
                        <?php if ($status === 'pending'): ?>
                            <span class="badge badge-yellow"><?= t('themes.requests.status.pending') ?></span>
                        <?php elseif ($status === 'approved'): ?>
                            <span class="badge badge-green"><?= t('themes.requests.status.approved') ?></span>
                        <?php else: ?>
                            <span class="badge badge-red"><?= t('themes.requests.status.rejected') ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) $req['created_at'])) ?></td>
                    <td style="font-size:12px;color:var(--text2)"><?= h((string) ($req['review_notes'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('themes.submit.header') ?></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text2);margin:0 0 16px">
            <?php if ($isAdmin): ?>
                <?= t('themes.submit.desc_admin') ?>
            <?php else: ?>
                <?= t('themes.submit.desc_user') ?>
            <?php endif; ?>
        </p>

        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:12px">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="submit_request">

            <div class="form-group">
                <label class="form-label"><?= t('themes.submit.name_label') ?></label>
                <input type="text" name="theme_name" class="form-control" required maxlength="100" placeholder="<?= h(t('themes.submit.name_placeholder')) ?>">
            </div>

            <div class="form-group">
                <label class="form-label"><?= t('themes.submit.desc_label') ?></label>
                <textarea name="description" class="form-control" rows="3" maxlength="1000" placeholder="<?= h(t('themes.submit.desc_placeholder')) ?>"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label"><?= t('themes.submit.source_type_label') ?></label>
                <label style="display:block;margin-bottom:6px">
                    <input type="radio" name="source_type" value="upload" checked onchange="document.getElementById('req-upload').style.display='block';document.getElementById('req-url').style.display='none'">
                    <?= t('themes.submit.source_upload') ?>
                </label>
                <label style="display:block">
                    <input type="radio" name="source_type" value="url" onchange="document.getElementById('req-upload').style.display='none';document.getElementById('req-url').style.display='block'">
                    <?= t('themes.submit.source_url') ?>
                </label>
            </div>

            <div id="req-upload" class="form-group">
                <label class="form-label"><?= t('themes.submit.file_label', ['size' => (int) (ThemePackage::MAX_ZIP_BYTES / 1024)]) ?></label>
                <input type="file" name="package_file" accept=".zip,application/zip" class="form-control">
            </div>

            <div id="req-url" class="form-group" style="display:none">
                <label class="form-label"><?= t('themes.submit.url_label') ?></label>
                <input type="url" name="source_url" class="form-control" placeholder="https://github.com/user/mon-theme">
            </div>

            <div style="text-align:right">
                <button type="submit" class="btn btn-primary"><?= t('themes.submit.submit_btn') ?></button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'layout_bottom.php'; ?>
