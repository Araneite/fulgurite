<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('users.manage');

function renderRoleOptions(array $roles, string $selected = ''): string {
    $html = '';
    foreach ($roles as $role) {
        $label = h((string) ($role['label'] ?? $role['key']));
        $description = trim((string) ($role['description'] ?? ''));
        $optionLabel = $description !== '' ? $label . ' - ' . h($description) : $label;
        $isSelected = $selected === (string) $role['key'] ? 'selected' : '';
        $html .= '<option value="' . h((string) $role['key']) . '" ' . $isSelected . '>' . $optionLabel . '</option>';
    }

    return $html;
}

function renderPermissionMatrix(array $definitions, array $selected = [], string $prefix = 'permissions'): string {
    $groups = [];
    foreach ($definitions as $permission => $meta) {
        $groups[(string) ($meta['group'] ?? t('users.permission_group_other'))][$permission] = $meta;
    }

    $html = '<div style="display:flex;flex-direction:column;gap:12px">';
    foreach ($groups as $groupLabel => $permissions) {
        $html .= '<div style="border:1px solid var(--border);border-radius:10px;padding:12px">';
        $html .= '<div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:10px">' . h((string) $groupLabel) . '</div>';
        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px">';
        foreach ($permissions as $permission => $meta) {
            $checked = !empty($selected[$permission]) ? 'checked' : '';
            $html .= '<label style="display:flex;align-items:center;gap:8px;border:1px solid var(--border);border-radius:8px;padding:8px 10px;cursor:pointer">';
            $html .= '<input type="checkbox" name="' . h($prefix) . '[' . h($permission) . ']" value="1" ' . $checked . ' style="accent-color:var(--accent)">';
            $html .= '<span style="font-size:13px">' . h((string) ($meta['label'] ?? $permission)) . '</span>';
            $html .= '</label>';
        }
        $html .= '</div></div>';
    }
    $html .= '</div>';

    return $html;
}

function renderScopeOptions(array $items, array $selectedIds = []): string {
    $html = '';
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $label = trim((string) ($item['name'] ?? ''));
        if ($label === '') {
            $label = '#' . $id;
        }
        $selected = in_array($id, $selectedIds, true) ? 'selected' : '';
        $html .= '<option value="' . $id . '" ' . $selected . '>' . h($label) . '</option>';
    }

    return $html;
}

function renderForceActionOptions(array $definitions, array $selected = [], string $prefix = 'force_actions'): string {
    $html = '<div style="display:flex;flex-direction:column;gap:8px">';
    foreach ($definitions as $key => $meta) {
        $checked = in_array((string) $key, $selected, true) ? 'checked' : '';
        $html .= '<label style="display:flex;gap:10px;align-items:flex-start;border:1px solid var(--border);border-radius:8px;padding:8px 10px;cursor:pointer">';
        $html .= '<input type="checkbox" name="' . h($prefix) . '[]" value="' . h((string) $key) . '" ' . $checked . ' style="margin-top:2px;accent-color:var(--accent)">';
        $html .= '<span><span style="display:block;font-size:13px;font-weight:600">' . h((string) ($meta['label'] ?? $key)) . '</span>';
        $html .= '<span style="display:block;font-size:12px;color:var(--text2)">' . h((string) ($meta['description'] ?? '')) . '</span></span>';
        $html .= '</label>';
    }
    $html .= '</div>';

    return $html;
}

function browserLabel(string $userAgent): string {
    $ua = strtolower($userAgent);
    return match (true) {
        str_contains($ua, 'edg') => 'Edge',
        str_contains($ua, 'firefox') => 'Firefox',
        str_contains($ua, 'chrome') => 'Chrome',
        str_contains($ua, 'safari') => 'Safari',
        str_contains($ua, 'curl') => 'cURL',
        default => t('common.unknown'),
    };
}

function datetimeLocalValue(?string $value): string {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($raw))->format('Y-m-d\TH:i');
    } catch (Throwable $e) {
        return '';
    }
}

function inviteAbsoluteUrl(string $token): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . routePath('/invite.php', ['token' => $token]);
}

function permissionMapFromPost(array $definitions, array $posted): array {
    $map = [];
    foreach (array_keys($definitions) as $permission) {
        $map[$permission] = isset($posted[$permission]);
    }
    return $map;
}

$flash = null;
$generatedInviteLink = null;
$db = Database::getInstance();
$roles = AppConfig::getRoles();
$validRoles = array_map(static fn(array $role): string => (string) $role['key'], $roles);
$permissionDefinitions = AppConfig::permissionDefinitions();
$forceActionDefinitions = UserManager::forceActionDefinitions();
$localeOptions = AppConfig::localeOptions();
$startPageOptions = AppConfig::startPageOptions();
$repos = RepoManager::getAll();
$hosts = HostManager::getAll();
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$inspectUserId = max(0, (int) ($_GET['inspect_user'] ?? $_POST['inspect_user'] ?? 0));
$sensitiveActions = [
    'add',
    'invite_user',
    'save_profile_admin',
    'save_access_policy',
    'change_password',
    'toggle_enabled',
    'disable_totp',
    'revoke_session',
    'revoke_sessions',
    'revoke_all_sessions',
    'delete',
    'unblock_ip',
    'revoke_invitation',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action !== '' && in_array($action, $sensitiveActions, true) && !StepUpAuth::consumeCurrentUserReauth('users.sensitive')) {
        $flash = ['type' => 'danger', 'msg' => t('flash.users.reauth_required')];
    } elseif ($action === 'add') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = in_array((string) ($_POST['role'] ?? ''), $validRoles, true) ? (string) $_POST['role'] : ROLE_VIEWER;

        if ($username === '' || $password === '') {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.username_password_required')];
        } else {
            $hibpCount = checkHibp($password);
            if ($hibpCount > 0) {
                $flash = ['type' => 'danger', 'msg' => t('flash.users.hibp', ['count' => $hibpCount])];
            } else {
                try {
                    $newUserId = UserManager::createUser([
                        'username' => $username,
                        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                        'role' => $role,
                        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
                        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
                        'email' => trim((string) ($_POST['email'] ?? '')),
                        'phone' => trim((string) ($_POST['phone'] ?? '')),
                        'job_title' => trim((string) ($_POST['job_title'] ?? '')),
                        'preferred_locale' => (string) ($_POST['preferred_locale'] ?? 'fr'),
                        'preferred_timezone' => trim((string) ($_POST['preferred_timezone'] ?? '')),
                        'preferred_start_page' => (string) ($_POST['preferred_start_page'] ?? 'dashboard'),
                        'force_actions' => array_values((array) ($_POST['force_actions'] ?? [])),
                        'created_by' => $currentUserId,
                    ]);
                    Auth::log('user_add', "Utilisateur cree: $username (#$newUserId)");
                    $flash = ['type' => 'success', 'msg' => t('flash.users.created')];
                    $inspectUserId = $newUserId;
                } catch (Throwable $e) {
                    $flash = ['type' => 'danger', 'msg' => $e->getMessage() ?: t('flash.users.create_error')];
                }
            }
        }
    } elseif ($action === 'invite_user') {
        $role = in_array((string) ($_POST['role'] ?? ''), $validRoles, true) ? (string) $_POST['role'] : ROLE_VIEWER;
        $invitePermissions = permissionMapFromPost($permissionDefinitions, (array) ($_POST['invite_permissions'] ?? []));

        try {
            $invite = UserManager::createInvitation([
                'username' => trim((string) ($_POST['username'] ?? '')),
                'role' => $role,
                'email' => trim((string) ($_POST['email'] ?? '')),
                'first_name' => trim((string) ($_POST['first_name'] ?? '')),
                'last_name' => trim((string) ($_POST['last_name'] ?? '')),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'job_title' => trim((string) ($_POST['job_title'] ?? '')),
                'preferred_locale' => (string) ($_POST['preferred_locale'] ?? 'fr'),
                'preferred_timezone' => trim((string) ($_POST['preferred_timezone'] ?? '')),
                'preferred_start_page' => (string) ($_POST['preferred_start_page'] ?? 'dashboard'),
                'permissions' => $invitePermissions,
                'repo_scope_mode' => (string) ($_POST['repo_scope_mode'] ?? 'all'),
                'repo_scope' => array_map('intval', (array) ($_POST['repo_scope'] ?? [])),
                'host_scope_mode' => (string) ($_POST['host_scope_mode'] ?? 'all'),
                'host_scope' => array_map('intval', (array) ($_POST['host_scope'] ?? [])),
                'force_actions' => array_values((array) ($_POST['force_actions'] ?? [])),
                'admin_notes' => trim((string) ($_POST['admin_notes'] ?? '')),
                'expires_at' => (string) ($_POST['expires_at'] ?? ''),
                'created_by' => $currentUserId,
            ]);
            $generatedInviteLink = inviteAbsoluteUrl((string) $invite['token']);
            Auth::log('user_invite', 'Invitation creee pour ' . trim((string) ($_POST['username'] ?? '')));
            $flash = ['type' => 'success', 'msg' => t('flash.users.invite_created')];
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'msg' => $e->getMessage() ?: t('flash.users.invite_error')];
        }
    } elseif ($action === 'save_profile_admin') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $targetUser = UserManager::getById($targetUserId);

        if (!$targetUser) {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.not_found')];
        } else {
            UserManager::updateProfile($targetUserId, [
                'first_name' => trim((string) ($_POST['first_name'] ?? '')),
                'last_name' => trim((string) ($_POST['last_name'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'job_title' => trim((string) ($_POST['job_title'] ?? '')),
                'preferred_locale' => (string) ($_POST['preferred_locale'] ?? 'fr'),
                'preferred_timezone' => trim((string) ($_POST['preferred_timezone'] ?? '')),
                'preferred_start_page' => (string) ($_POST['preferred_start_page'] ?? 'dashboard'),
                'admin_notes' => (string) ($targetUser['admin_notes'] ?? ''),
            ]);
            Auth::log('user_profile_update', 'Profil utilisateur mis a jour: ' . $targetUser['username']);
            $flash = ['type' => 'success', 'msg' => t('flash.users.profile_updated')];
            $inspectUserId = $targetUserId;
        }
    } elseif ($action === 'save_access_policy') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $targetUser = UserManager::getById($targetUserId);
        $newRole = in_array((string) ($_POST['role'] ?? ''), $validRoles, true) ? (string) $_POST['role'] : ROLE_VIEWER;

        if (!$targetUser) {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.not_found')];
        } elseif ($targetUserId === $currentUserId) {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.edit_own_profile_redirect')];
        } elseif (AppConfig::getRoleLevel($newRole, PHP_INT_MAX) > AppConfig::getRoleLevel((string) ($_SESSION['role'] ?? ROLE_VIEWER), 0)) {
            $flash = ['type' => 'danger', 'msg' => 'Impossible d\'attribuer un rôle supérieur au vôtre.'];
        } else {
            $permissions = permissionMapFromPost($permissionDefinitions, (array) ($_POST['permissions'] ?? []));
            $validatedPrimaryFactor = null;
            try {
                $validatedPrimaryFactor = StepUpAuth::validateRequestedPrimaryFactor($targetUser, (string) ($_POST['primary_second_factor'] ?? ''));
                UserManager::updateRole($targetUserId, $newRole);
                UserManager::updateAccessPolicy($targetUserId, [
                    'permissions' => $permissions,
                    'repo_scope_mode' => (string) ($_POST['repo_scope_mode'] ?? 'all'),
                    'repo_scope' => array_map('intval', (array) ($_POST['repo_scope'] ?? [])),
                    'host_scope_mode' => (string) ($_POST['host_scope_mode'] ?? 'all'),
                    'host_scope' => array_map('intval', (array) ($_POST['host_scope'] ?? [])),
                    'force_actions' => array_values((array) ($_POST['force_actions'] ?? [])),
                    'suspended_until' => (string) ($_POST['suspended_until'] ?? ''),
                    'suspension_reason' => trim((string) ($_POST['suspension_reason'] ?? '')),
                    'account_expires_at' => (string) ($_POST['account_expires_at'] ?? ''),
                ]);

                if ($validatedPrimaryFactor === StepUpAuth::FACTOR_NONE) {
                    StepUpAuth::syncPrimaryFactor($targetUserId);
                } else {
                    UserManager::setPrimarySecondFactor($targetUserId, (string) $validatedPrimaryFactor);
                }

                Auth::revokeAllUserSessions($targetUserId);
                Auth::log('user_access_policy_update', 'Politique d acces mise a jour pour ' . $targetUser['username'], 'warning');
                $flash = ['type' => 'success', 'msg' => t('flash.users.access_policy_updated')];
                $inspectUserId = $targetUserId;
            } catch (Throwable $e) {
                $flash = ['type' => 'danger', 'msg' => $e->getMessage() !== '' ? $e->getMessage() : t('flash.users.access_policy_error')];
            }
        }
    } elseif ($action === 'change_password') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $targetUser = UserManager::getById($targetUserId);
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if (!$targetUser) {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.not_found')];
        } elseif ($targetUserId === $currentUserId) {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.change_own_password_redirect')];
        } elseif (strlen($newPassword) < 6) {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.password_too_short')];
        } else {
            $hibpCount = checkHibp($newPassword);
            if ($hibpCount > 0) {
                $flash = ['type' => 'danger', 'msg' => t('flash.users.hibp', ['count' => $hibpCount])];
            } else {
                $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_BCRYPT), $targetUserId]);
                UserManager::markPasswordChanged($targetUserId);
                Auth::revokeAllUserSessions($targetUserId);
                Auth::log('password_change_admin', 'Mot de passe reinitialise pour ' . $targetUser['username'], 'warning');
                $flash = ['type' => 'success', 'msg' => t('flash.users.password_updated')];
                $inspectUserId = $targetUserId;
            }
        }
    } elseif ($action === 'toggle_enabled') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $enabled = (int) ($_POST['enabled'] ?? 0);
        if ($targetUserId === $currentUserId) {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.toggle_own_account')];
        } else {
            Auth::setUserEnabled($targetUserId, $enabled === 1);
            $flash = ['type' => 'success', 'msg' => $enabled === 1 ? t('flash.users.account_enabled') : t('flash.users.account_disabled')];
            $inspectUserId = $targetUserId;
        }
    } elseif ($action === 'disable_totp') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        if ($targetUserId > 0) {
            Auth::totpDisable($targetUserId);
            $flash = ['type' => 'success', 'msg' => t('flash.users.totp_disabled')];
            $inspectUserId = $targetUserId;
        }
    } elseif ($action === 'revoke_session') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $ownerUserId = (int) ($_POST['user_id'] ?? 0);
        if ($sessionId > 0 && Auth::revokeSessionById($sessionId, $ownerUserId > 0 ? $ownerUserId : 0)) {
            $flash = ['type' => 'success', 'msg' => t('flash.users.session_revoked')];
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.session_revoke_error')];
        }
        $inspectUserId = $ownerUserId;
    } elseif ($action === 'revoke_sessions') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $count = Auth::revokeUserSessions($targetUserId);
        $flash = ['type' => 'success', 'msg' => t('flash.users.sessions_revoked', ['count' => $count])];
        $inspectUserId = $targetUserId;
    } elseif ($action === 'revoke_all_sessions') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $count = Auth::revokeAllUserSessions($targetUserId);
        $flash = ['type' => 'success', 'msg' => t('flash.users.all_sessions_revoked', ['count' => $count])];
        $inspectUserId = $targetUserId;
    } elseif ($action === 'delete') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        if ($targetUserId === $currentUserId) {
            $flash = ['type' => 'danger', 'msg' => t('flash.users.cannot_delete_self')];
        } else {
            $targetUser = UserManager::getById($targetUserId);
            if (!$targetUser) {
                $flash = ['type' => 'danger', 'msg' => t('flash.users.not_found')];
            } else {
                Auth::revokeAllUserSessions($targetUserId);
                $db->prepare('DELETE FROM users WHERE id = ?')->execute([$targetUserId]);
                Auth::log('user_delete', 'Utilisateur supprime: ' . $targetUser['username'], 'warning');
                $flash = ['type' => 'success', 'msg' => t('flash.users.deleted')];
                if ($inspectUserId === $targetUserId) {
                    $inspectUserId = 0;
                }
            }
        }
    } elseif ($action === 'unblock_ip') {
        $ip = trim((string) ($_POST['ip'] ?? ''));
        if ($ip !== '') {
            Auth::unblockIp($ip);
            $flash = ['type' => 'success', 'msg' => t('flash.users.ip_unblocked', ['ip' => $ip])];
        }
    } elseif ($action === 'revoke_invitation') {
        $invitationId = (int) ($_POST['invitation_id'] ?? 0);
        if ($invitationId > 0) {
            UserManager::revokeInvitation($invitationId);
            Auth::log('user_invite_revoke', 'Invitation revoquee #' . $invitationId, 'warning');
            $flash = ['type' => 'success', 'msg' => t('flash.users.invitation_revoked')];
        }
    }
}

Auth::cleanExpiredSessions();
$users = UserManager::getAll();
$sessions = Auth::getActiveSessions();
$sessionsByUserId = [];
foreach ($sessions as $session) {
    $sessionsByUserId[(int) ($session['user_id'] ?? 0)][] = $session;
}

$webauthnCounts = [];
$webauthnCountRows = $db->query('SELECT user_id, COUNT(*) AS total FROM webauthn_credentials GROUP BY user_id')->fetchAll();
foreach ($webauthnCountRows as $row) {
    $webauthnCounts[(int) ($row['user_id'] ?? 0)] = (int) ($row['total'] ?? 0);
}

$blockedIps = $db->query("
    SELECT ip, COUNT(*) AS attempts, MAX(attempted_at) AS last_attempt
    FROM login_attempts
    WHERE success = 0
      AND attempted_at >= datetime('now', '-' || (
          SELECT value FROM settings WHERE key = 'login_lockout_minutes'
      ) || ' minutes')
    GROUP BY ip
    HAVING attempts >= (SELECT value FROM settings WHERE key = 'login_max_attempts')
")->fetchAll();

$pendingInvitations = UserManager::listInvitations();
$inspectedUser = null;
if ($inspectUserId > 0) {
    foreach ($users as $candidateUser) {
        if ((int) ($candidateUser['id'] ?? 0) === $inspectUserId) {
            $inspectedUser = $candidateUser;
            break;
        }
    }
}
if (!$inspectedUser && !empty($users)) {
    $inspectedUser = $users[0];
    $inspectUserId = (int) ($inspectedUser['id'] ?? 0);
}
$securityOverview = $inspectedUser ? UserManager::getUserSecurityOverview($inspectedUser) : null;

$title = t('users.title');
$active = 'users';
$actions = '<div class="flex gap-2">'
    . '<button class="btn btn-primary" onclick="document.getElementById(\'modal-add\').classList.add(\'show\')">+ ' . h(t('users.add_local_btn')) . '</button>'
    . '<button class="btn" onclick="document.getElementById(\'modal-invite\').classList.add(\'show\')">+ ' . h(t('users.invite_btn')) . '</button>'
    . '</div>';

include 'layout_top.php';
?>
<?php if ($generatedInviteLink !== null): ?>
<div class="card mb-4">
    <div class="card-header"><?= t('users.invite_link.title') ?></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        <div style="font-size:12px;color:var(--text2)"><?= t('users.invite_link.hint') ?></div>
        <div class="mono" id="generated-invite-link" style="font-size:12px;background:var(--bg3);padding:10px;border-radius:8px;word-break:break-all"><?= h($generatedInviteLink) ?></div>
        <div class="flex gap-2">
            <button class="btn btn-primary" onclick="copyText('generated-invite-link')"><?= t('users.invite_link.copy_btn') ?></button>
            <a href="<?= h($generatedInviteLink) ?>" class="btn" target="_blank" rel="noopener"><?= t('common.open') ?></a>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="modal-add" class="modal-overlay">
    <div class="modal" style="max-width:760px">
        <div class="modal-title"><?= t('users.modal_add.title') ?></div>
        <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('users.modal_add.reauth')) ?>" style="display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.username') ?></label><input type="text" name="username" class="form-control" required></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.password') ?></label><input type="password" name="password" class="form-control" minlength="6" required></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.role') ?></label><select name="role" class="form-control"><?= renderRoleOptions($roles, ROLE_VIEWER) ?></select></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.first_name') ?></label><input type="text" name="first_name" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.last_name') ?></label><input type="text" name="last_name" class="form-control"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.email') ?></label><input type="email" name="email" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.phone') ?></label><input type="text" name="phone" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.job_title') ?></label><input type="text" name="job_title" class="form-control"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.locale') ?></label>
                    <select name="preferred_locale" class="form-control">
                        <?php foreach ($localeOptions as $localeKey => $localeLabel): ?>
                        <option value="<?= h((string) $localeKey) ?>"><?= h((string) $localeLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label"><?= t('users.form.timezone') ?></label><input type="text" name="preferred_timezone" class="form-control" placeholder="Europe/Paris"></div>
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.start_page') ?></label>
                    <select name="preferred_start_page" class="form-control">
                        <?php foreach ($startPageOptions as $pageKey => $pageMeta): ?>
                        <option value="<?= h((string) $pageKey) ?>"><?= h((string) ($pageMeta['label'] ?? $pageKey)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('users.form.force_actions') ?></label>
                <?= renderForceActionOptions($forceActionDefinitions, [UserManager::FORCE_ACTION_REVIEW_PROFILE, UserManager::FORCE_ACTION_SETUP_2FA]) ?>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end">
                <button type="button" class="btn" onclick="document.getElementById('modal-add').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('users.modal_add.submit') ?></button>
            </div>
        </form>
    </div>
</div>

<div id="modal-invite" class="modal-overlay">
    <div class="modal" style="max-width:860px">
        <div class="modal-title"><?= t('users.modal_invite.title') ?></div>
        <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('users.modal_invite.reauth')) ?>" style="display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="action" value="invite_user">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.username') ?></label><input type="text" name="username" class="form-control" required></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.email') ?></label><input type="email" name="email" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.role') ?></label><select name="role" class="form-control"><?= renderRoleOptions($roles, ROLE_VIEWER) ?></select></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.first_name') ?></label><input type="text" name="first_name" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.last_name') ?></label><input type="text" name="last_name" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.phone') ?></label><input type="text" name="phone" class="form-control"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.job_title') ?></label><input type="text" name="job_title" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.timezone') ?></label><input type="text" name="preferred_timezone" class="form-control" placeholder="Europe/Paris"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.expires_at') ?></label><input type="datetime-local" name="expires_at" class="form-control" value="<?= h(date('Y-m-d\TH:i', time() + (7 * 86400))) ?>"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.locale') ?></label>
                    <select name="preferred_locale" class="form-control">
                        <?php foreach ($localeOptions as $localeKey => $localeLabel): ?>
                        <option value="<?= h((string) $localeKey) ?>"><?= h((string) $localeLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.start_page') ?></label>
                    <select name="preferred_start_page" class="form-control">
                        <?php foreach ($startPageOptions as $pageKey => $pageMeta): ?>
                        <option value="<?= h((string) $pageKey) ?>"><?= h((string) ($pageMeta['label'] ?? $pageKey)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label class="form-label"><?= t('users.form.force_actions') ?></label><?= renderForceActionOptions($forceActionDefinitions, [UserManager::FORCE_ACTION_REVIEW_PROFILE, UserManager::FORCE_ACTION_SETUP_2FA], 'force_actions') ?></div>
            <div class="form-group"><label class="form-label"><?= t('users.form.permissions') ?></label><?= renderPermissionMatrix($permissionDefinitions, [], 'invite_permissions') ?></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.repo_scope') ?></label>
                    <select name="repo_scope_mode" class="form-control" onchange="toggleScopeSelect(this,'invite-repo-scope-select')"><option value="all"><?= t('users.scope.all_repos') ?></option><option value="selected"><?= t('users.scope.selected_repos') ?></option></select>
                    <select name="repo_scope[]" id="invite-repo-scope-select" class="form-control" multiple size="6" style="margin-top:8px;display:none"><?= renderScopeOptions($repos) ?></select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.host_scope') ?></label>
                    <select name="host_scope_mode" class="form-control" onchange="toggleScopeSelect(this,'invite-host-scope-select')"><option value="all"><?= t('users.scope.all_hosts') ?></option><option value="selected"><?= t('users.scope.selected_hosts') ?></option></select>
                    <select name="host_scope[]" id="invite-host-scope-select" class="form-control" multiple size="6" style="margin-top:8px;display:none"><?= renderScopeOptions($hosts) ?></select>
                </div>
            </div>
            <div class="form-group"><label class="form-label"><?= t('users.form.admin_notes') ?></label><textarea name="admin_notes" class="form-control" rows="3" placeholder="<?= h(t('users.form.admin_notes_placeholder')) ?>"></textarea></div>
            <div class="flex gap-2" style="justify-content:flex-end">
                <button type="button" class="btn" onclick="document.getElementById('modal-invite').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('users.modal_invite.submit') ?></button>
            </div>
        </form>
    </div>
</div>

<div id="modal-profile" class="modal-overlay">
    <div class="modal" style="max-width:760px">
        <div class="modal-title"><?= t('users.modal_profile.title') ?></div>
        <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('users.modal_profile.reauth')) ?>" style="display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="action" value="save_profile_admin">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="user_id" id="profile-user-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.username_label') ?></label><input type="text" id="profile-username" class="form-control" disabled></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.job_title') ?></label><input type="text" name="job_title" id="profile-job-title" class="form-control"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.first_name') ?></label><input type="text" name="first_name" id="profile-first-name" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.last_name') ?></label><input type="text" name="last_name" id="profile-last-name" class="form-control"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.email') ?></label><input type="email" name="email" id="profile-email" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.phone') ?></label><input type="text" name="phone" id="profile-phone" class="form-control"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.locale') ?></label><select name="preferred_locale" id="profile-locale" class="form-control"><?php foreach ($localeOptions as $localeKey => $localeLabel): ?><option value="<?= h((string) $localeKey) ?>"><?= h((string) $localeLabel) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.timezone') ?></label><input type="text" name="preferred_timezone" id="profile-timezone" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.start_page') ?></label><select name="preferred_start_page" id="profile-start-page" class="form-control"><?php foreach ($startPageOptions as $pageKey => $pageMeta): ?><option value="<?= h((string) $pageKey) ?>"><?= h((string) ($pageMeta['label'] ?? $pageKey)) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end">
                <button type="button" class="btn" onclick="document.getElementById('modal-profile').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<div id="modal-access" class="modal-overlay">
    <div class="modal" style="max-width:940px">
        <div class="modal-title"><?= t('users.modal_access.title') ?></div>
        <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('users.modal_access.reauth')) ?>" style="display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="action" value="save_access_policy">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="user_id" id="access-user-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.username_label') ?></label><input type="text" id="access-username" class="form-control" disabled></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.role') ?></label><select name="role" id="access-role" class="form-control"><?= renderRoleOptions($roles) ?></select></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.primary_factor') ?></label>
                    <select name="primary_second_factor" id="access-primary-factor" class="form-control">
                        <option value="<?= h(StepUpAuth::FACTOR_CLASSIC) ?>"><?= t('users.factor.classic') ?></option>
                        <option value="<?= h(StepUpAuth::FACTOR_WEBAUTHN) ?>">WebAuthn</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.factor_availability') ?></label>
                    <div id="access-primary-factor-help" class="form-control" style="min-height:42px;display:flex;align-items:center;background:var(--bg3);color:var(--text2)"></div>
                </div>
            </div>
            <div class="form-group"><label class="form-label"><?= t('users.form.permissions') ?></label><?= renderPermissionMatrix($permissionDefinitions, [], 'permissions') ?></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.repo_scope') ?></label>
                    <select name="repo_scope_mode" id="access-repo-scope-mode" class="form-control" onchange="toggleScopeSelect(this,'access-repo-scope')"><option value="all"><?= t('users.scope.all_repos') ?></option><option value="selected"><?= t('users.scope.selected_repos') ?></option></select>
                    <select name="repo_scope[]" id="access-repo-scope" class="form-control" multiple size="6" style="margin-top:8px;display:none"><?= renderScopeOptions($repos) ?></select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= t('users.form.host_scope') ?></label>
                    <select name="host_scope_mode" id="access-host-scope-mode" class="form-control" onchange="toggleScopeSelect(this,'access-host-scope')"><option value="all"><?= t('users.scope.all_hosts') ?></option><option value="selected"><?= t('users.scope.selected_hosts') ?></option></select>
                    <select name="host_scope[]" id="access-host-scope" class="form-control" multiple size="6" style="margin-top:8px;display:none"><?= renderScopeOptions($hosts) ?></select>
                </div>
            </div>
            <div class="form-group"><label class="form-label"><?= t('users.form.force_actions') ?></label><?= renderForceActionOptions($forceActionDefinitions, [], 'force_actions') ?></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group"><label class="form-label"><?= t('users.form.suspended_until') ?></label><input type="datetime-local" name="suspended_until" id="access-suspended-until" class="form-control"></div>
                <div class="form-group"><label class="form-label"><?= t('users.form.account_expires') ?></label><input type="datetime-local" name="account_expires_at" id="access-expires-at" class="form-control"></div>
            </div>
            <div class="form-group"><label class="form-label"><?= t('users.form.suspension_reason') ?></label><textarea name="suspension_reason" id="access-suspension-reason" class="form-control" rows="3" placeholder="<?= h(t('common.optional')) ?>"></textarea></div>
            <div class="flex gap-2" style="justify-content:flex-end">
                <button type="button" class="btn" onclick="document.getElementById('modal-access').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<div id="modal-password" class="modal-overlay">
    <div class="modal">
        <div class="modal-title"><?= t('users.modal_password.title') ?></div>
        <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('users.modal_password.reauth')) ?>" style="display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="user_id" id="password-user-id">
            <div class="form-group"><label class="form-label"><?= t('users.form.username_label') ?></label><input type="text" id="password-username" class="form-control" disabled></div>
            <div class="form-group"><label class="form-label"><?= t('users.form.new_password') ?></label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
            <div class="flex gap-2" style="justify-content:flex-end">
                <button type="button" class="btn" onclick="document.getElementById('modal-password').classList.remove('show')"><?= t('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= t('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<form id="form-action" method="POST" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" id="form-action-name">
    <input type="hidden" name="user_id" id="form-action-user-id">
</form>

<form id="form-toggle-enabled" method="POST" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="toggle_enabled">
    <input type="hidden" name="user_id" id="toggle-user-id">
    <input type="hidden" name="enabled" id="toggle-enabled-val">
</form>

<script<?= cspNonceAttr() ?>>
function copyText(elementId) {
    const target = document.getElementById(elementId);
    if (!target) return;
    copyValue(target.textContent || '');
}
function copyValue(value) {
    if (!value) return;
    navigator.clipboard.writeText(value).then(() => window.toast('<?= h(t('users.js.link_copied')) ?>', 'success')).catch(() => window.toast('<?= h(t('users.js.copy_failed')) ?>', 'error'));
}
function submitForm(action, userId) {
    document.getElementById('form-action-name').value = action;
    document.getElementById('form-action-user-id').value = userId;
    document.getElementById('form-action').submit();
}
function submitProtectedAction(action, userId, message) {
    requireReauth(() => submitForm(action, userId), message || '<?= h(t('users.js.reauth_default')) ?>');
}
function submitProtectedForm(form) {
    if (!form) return false;
    if (form.dataset.reauthVerified === '1') {
        delete form.dataset.reauthVerified;
        return true;
    }
    const message = form.dataset.reauthMessage || '<?= h(t('users.js.reauth_default')) ?>';
    requireReauth(() => {
        form.dataset.reauthVerified = '1';
        form.submit();
    }, message);
    return false;
}
function submitToggleEnabled(userId, enabled, username) {
    const confirmMessage = enabled ? '<?= h(t('users.js.enable_confirm')) ?>' : '<?= h(t('users.js.disable_confirm')) ?>';
    const reauthMessage = enabled ? `<?= h(t('users.js.enable_reauth_prefix')) ?> ${username}.` : `<?= h(t('users.js.disable_reauth_prefix')) ?> ${username}.`;
    confirmAction(confirmMessage, () => {
        requireReauth(() => {
            document.getElementById('toggle-user-id').value = userId;
            document.getElementById('toggle-enabled-val').value = enabled;
            document.getElementById('form-toggle-enabled').submit();
        }, reauthMessage);
    });
}
function toggleScopeSelect(select, targetId) {
    const target = document.getElementById(targetId);
    if (!target) return;
    target.style.display = select.value === 'selected' ? 'block' : 'none';
}
function setSelectedValues(selectId, values) {
    const target = document.getElementById(selectId);
    if (!target) return;
    const normalized = new Set((values || []).map((value) => String(value)));
    Array.from(target.options).forEach((option) => {
        option.selected = normalized.has(option.value);
    });
}
function setCheckboxMap(prefix, values) {
    const normalized = values || {};
    document.querySelectorAll(`input[name^="${prefix}["]`).forEach((checkbox) => {
        const match = checkbox.name.match(/\[([^\]]+)\]/);
        const key = match ? match[1] : '';
        checkbox.checked = Boolean(normalized[key]);
    });
}
function setCheckboxList(prefix, values) {
    const normalized = new Set(values || []);
    document.querySelectorAll(`input[name="${prefix}[]"]`).forEach((checkbox) => {
        checkbox.checked = normalized.has(checkbox.value);
    });
}
function openProfileModal(user) {
    document.getElementById('profile-user-id').value = user.id || '';
    document.getElementById('profile-username').value = user.username || '';
    document.getElementById('profile-first-name').value = user.first_name || '';
    document.getElementById('profile-last-name').value = user.last_name || '';
    document.getElementById('profile-email').value = user.email || '';
    document.getElementById('profile-phone').value = user.phone || '';
    document.getElementById('profile-job-title').value = user.job_title || '';
    document.getElementById('profile-locale').value = user.preferred_locale || 'fr';
    document.getElementById('profile-timezone').value = user.preferred_timezone || '';
    document.getElementById('profile-start-page').value = user.preferred_start_page || 'dashboard';
    document.getElementById('modal-profile').classList.add('show');
}
function openAccessModal(user) {
    document.getElementById('access-user-id').value = user.id || '';
    document.getElementById('access-username').value = user.username || '';
    document.getElementById('access-role').value = user.role || 'viewer';
    const primaryFactorSelect = document.getElementById('access-primary-factor');
    const primaryFactorHelp = document.getElementById('access-primary-factor-help');
    const availablePrimaryFactors = new Set(user.available_primary_factors || []);
    if (primaryFactorSelect && primaryFactorHelp) {
        Array.from(primaryFactorSelect.options).forEach((option) => {
            option.disabled = !availablePrimaryFactors.has(option.value);
        });

        if (availablePrimaryFactors.size === 0) {
            primaryFactorSelect.disabled = true;
            primaryFactorHelp.textContent = 'Aucun facteur fort disponible sur ce compte.';
        } else {
            primaryFactorSelect.disabled = false;
            const preferred = user.primary_second_factor || '';
            if (preferred && availablePrimaryFactors.has(preferred)) {
                primaryFactorSelect.value = preferred;
            } else {
                primaryFactorSelect.value = availablePrimaryFactors.has('webauthn') ? 'webauthn' : 'classic_2fa';
            }
            primaryFactorHelp.textContent = Array.from(availablePrimaryFactors).map((factor) => factor === 'webauthn' ? 'WebAuthn' : 'A2F classique').join(' + ');
        }
    }
    document.getElementById('access-repo-scope-mode').value = user.repo_scope_mode || 'all';
    document.getElementById('access-host-scope-mode').value = user.host_scope_mode || 'all';
    document.getElementById('access-suspended-until').value = user.suspended_until || '';
    document.getElementById('access-expires-at').value = user.account_expires_at || '';
    document.getElementById('access-suspension-reason').value = user.suspension_reason || '';
    setCheckboxMap('permissions', user.permissions || {});
    setCheckboxList('force_actions', user.force_actions || []);
    setSelectedValues('access-repo-scope', user.repo_scope || []);
    setSelectedValues('access-host-scope', user.host_scope || []);
    toggleScopeSelect(document.getElementById('access-repo-scope-mode'), 'access-repo-scope');
    toggleScopeSelect(document.getElementById('access-host-scope-mode'), 'access-host-scope');
    document.getElementById('modal-access').classList.add('show');
}
function openPasswordModal(user) {
    document.getElementById('password-user-id').value = user.id || '';
    document.getElementById('password-username').value = user.username || '';
    document.getElementById('modal-password').classList.add('show');
}

window.copyText = copyText;
window.copyValue = copyValue;
window.submitProtectedForm = submitProtectedForm;
window.toggleScopeSelect = toggleScopeSelect;
</script>

<div style="display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,.9fr);gap:16px;align-items:start" data-responsive-grid="stack">
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header">
                <?= t('users.table.title') ?>
                <span class="badge badge-blue"><?= count($users) ?></span>
            </div>
            <?php if (empty($users)): ?>
            <div class="empty-state" style="padding:32px"><?= t('users.table.empty') ?></div>
            <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead>
                    <tr>
                        <th><?= t('users.table.col_user') ?></th>
                        <th><?= t('users.table.col_contact') ?></th>
                        <th><?= t('users.table.col_role') ?></th>
                        <th><?= t('users.table.col_status') ?></th>
                        <th><?= t('users.table.col_security') ?></th>
                        <th><?= t('users.table.col_scope') ?></th>
                        <th><?= t('users.table.col_force_actions') ?></th>
                        <th><?= t('users.table.col_sessions') ?></th>
                        <th><?= t('users.table.col_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $managedUser): ?>
                    <?php
                    $managedUserId = (int) ($managedUser['id'] ?? 0);
                    $userSessions = $sessionsByUserId[$managedUserId] ?? [];
                    $statusMeta = UserManager::status($managedUser);
                    $resolvedPermissions = Auth::resolvedPermissionsForUser($managedUser);
                    $displayName = UserManager::displayName($managedUser);
                    $stepUpState = StepUpAuth::describeUser($managedUser);
                    $userData = [
                        'id' => $managedUserId,
                        'username' => (string) ($managedUser['username'] ?? ''),
                        'role' => (string) ($managedUser['role'] ?? ''),
                        'first_name' => (string) ($managedUser['first_name'] ?? ''),
                        'last_name' => (string) ($managedUser['last_name'] ?? ''),
                        'email' => (string) ($managedUser['email'] ?? ''),
                        'phone' => (string) ($managedUser['phone'] ?? ''),
                        'job_title' => (string) ($managedUser['job_title'] ?? ''),
                        'preferred_locale' => (string) ($managedUser['preferred_locale'] ?? 'fr'),
                        'preferred_timezone' => (string) ($managedUser['preferred_timezone'] ?? ''),
                        'preferred_start_page' => (string) ($managedUser['preferred_start_page'] ?? 'dashboard'),
                        'permissions' => $resolvedPermissions,
                        'repo_scope_mode' => (string) ($managedUser['repo_scope_mode'] ?? 'all'),
                        'repo_scope' => array_values((array) ($managedUser['repo_scope'] ?? [])),
                        'host_scope_mode' => (string) ($managedUser['host_scope_mode'] ?? 'all'),
                        'host_scope' => array_values((array) ($managedUser['host_scope'] ?? [])),
                        'force_actions' => array_values((array) ($managedUser['force_actions'] ?? [])),
                        'suspended_until' => datetimeLocalValue((string) ($managedUser['suspended_until'] ?? '')),
                        'suspension_reason' => (string) ($managedUser['suspension_reason'] ?? ''),
                        'account_expires_at' => datetimeLocalValue((string) ($managedUser['account_expires_at'] ?? '')),
                        'primary_second_factor' => (string) ($stepUpState['primary_factor'] ?? StepUpAuth::FACTOR_NONE),
                        'available_primary_factors' => array_values((array) ($stepUpState['available_factors'] ?? [])),
                    ];
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:600">
                                <a href="<?= routePath('/users.php', ['inspect_user' => $managedUserId]) ?>"><?= h((string) ($managedUser['username'] ?? '')) ?></a>
                                <?php if ($managedUserId === $currentUserId): ?>
                                <span class="badge badge-green" style="font-size:10px"><?= t('users.table.you') ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:12px;color:var(--text2)"><?= h($displayName !== '' ? $displayName : t('users.table.no_name')) ?></div>
                            <div style="font-size:12px;color:var(--text2)"><?= t('users.table.last_login_label') ?> <?= !empty($managedUser['last_login']) ? h(formatDate((string) $managedUser['last_login'])) : t('common.never') ?></div>
                        </td>
                        <td style="font-size:12px">
                            <div><?= !empty($managedUser['email']) ? h((string) $managedUser['email']) : t('users.table.no_email') ?></div>
                            <div style="color:var(--text2)"><?= !empty($managedUser['phone']) ? h((string) $managedUser['phone']) : t('users.table.no_phone') ?></div>
                            <div style="color:var(--text2)"><?= !empty($managedUser['job_title']) ? h((string) $managedUser['job_title']) : t('users.table.no_job_title') ?></div>
                        </td>
                        <td>
                            <span class="badge <?= AppConfig::getRoleBadgeClass((string) ($managedUser['role'] ?? '')) ?>"><?= h(AppConfig::getRoleLabel((string) ($managedUser['role'] ?? ''))) ?></span>
                            <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= h(UserManager::summarizePermissions($managedUser)) ?></div>
                        </td>
                        <td>
                            <span class="badge <?= h((string) ($statusMeta['badge'] ?? 'badge-gray')) ?>"><?= h((string) ($statusMeta['label'] ?? 'Inconnu')) ?></span>
                            <?php if (!empty($managedUser['suspended_until'])): ?>
                            <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= t('users.table.suspended_until') ?> <?= h(formatDate((string) $managedUser['suspended_until'])) ?></div>
                            <?php elseif (!empty($managedUser['account_expires_at'])): ?>
                            <div style="font-size:11px;color:var(--text2);margin-top:4px"><?= t('users.table.expires_on') ?> <?= h(formatDate((string) $managedUser['account_expires_at'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px">
                            <div><span class="badge <?= !empty($managedUser['totp_enabled']) ? 'badge-green' : 'badge-gray' ?>"><?= !empty($managedUser['totp_enabled']) ? t('users.table.totp_active') : t('users.table.totp_inactive') ?></span></div>
                            <div style="margin-top:4px"><span class="badge <?= !empty($webauthnCounts[$managedUserId]) ? 'badge-blue' : 'badge-gray' ?>"><?= (int) ($webauthnCounts[$managedUserId] ?? 0) ?> <?= t('users.table.keys_suffix') ?></span></div>
                            <div style="margin-top:4px;color:var(--text2)"><?= t('users.table.primary_factor_label') ?> <?= h($stepUpState['primary_factor'] === StepUpAuth::FACTOR_WEBAUTHN ? 'WebAuthn' : ($stepUpState['primary_factor'] === StepUpAuth::FACTOR_CLASSIC ? t('users.factor.classic') : t('common.none'))) ?></div>
                        </td>
                        <td style="font-size:12px">
                            <div><?= t('users.table.repos_label') ?> <?= h(UserManager::summarizeScope($managedUser, 'repo')) ?></div>
                            <div style="color:var(--text2)"><?= t('users.table.hosts_label') ?> <?= h(UserManager::summarizeScope($managedUser, 'host')) ?></div>
                        </td>
                        <td style="font-size:12px;color:var(--text2)">
                            <?php if (empty($managedUser['force_actions'])): ?>
                            <?= t('common.none_f') ?>
                            <?php else: ?>
                            <?= h(implode(', ', array_map(static fn(string $key): string => (string) ($forceActionDefinitions[$key]['label'] ?? $key), (array) $managedUser['force_actions']))) ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= count($userSessions) > 0 ? 'badge-green' : 'badge-gray' ?>"><?= count($userSessions) ?></span></td>
                        <td>
                            <div class="flex gap-2" style="flex-wrap:wrap">
                                <button class="btn btn-sm" onclick='openProfileModal(<?= h(json_encode($userData)) ?>)'><?= t('users.actions.profile') ?></button>
                                <button class="btn btn-sm" onclick='openAccessModal(<?= h(json_encode($userData)) ?>)'><?= t('users.actions.access') ?></button>
                                <button class="btn btn-sm" onclick='openPasswordModal(<?= h(json_encode(["id" => $managedUserId, "username" => (string) ($managedUser["username"] ?? "")])) ?>)'><?= t('users.actions.password') ?></button>
                                <a href="<?= routePath('/users.php', ['inspect_user' => $managedUserId]) ?>" class="btn btn-sm"><?= t('users.actions.security') ?></a>
                                <a href="<?= routePath('/logs.php', ['username' => (string) ($managedUser['username'] ?? '')]) ?>" class="btn btn-sm"><?= t('users.actions.audit') ?></a>
                                <?php if (!empty($managedUser['totp_enabled'])): ?>
                                <button class="btn btn-sm btn-warning" onclick='confirmAction(<?= json_encode(t('users.js.disable_totp_confirm', ['username' => (string) ($managedUser["username"] ?? "")]), JSON_HEX_APOS | JSON_HEX_QUOT) ?>, () => submitProtectedAction("disable_totp", <?= $managedUserId ?>, <?= json_encode(t('users.js.disable_totp_reauth'), JSON_HEX_APOS | JSON_HEX_QUOT) ?>))'><?= t('users.actions.disable_2fa') ?></button>
                                <?php endif; ?>
                                <?php if (count($userSessions) > 0): ?>
                                <button class="btn btn-sm btn-warning" onclick='confirmAction(<?= json_encode(t('users.js.revoke_sessions_confirm', ['username' => (string) ($managedUser["username"] ?? "")]), JSON_HEX_APOS | JSON_HEX_QUOT) ?>, () => submitProtectedAction("revoke_sessions", <?= $managedUserId ?>, <?= json_encode(t('users.js.revoke_sessions_reauth'), JSON_HEX_APOS | JSON_HEX_QUOT) ?>))'><?= t('users.actions.sessions') ?></button>
                                <?php endif; ?>
                                <?php if ($managedUserId !== $currentUserId): ?>
                                <?php if (((int) ($managedUser['enabled'] ?? 1)) === 1): ?>
                                <button class="btn btn-sm btn-warning" onclick='submitToggleEnabled(<?= $managedUserId ?>, 0, <?= json_encode((string) ($managedUser["username"] ?? ""), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><?= t('users.actions.disable') ?></button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick='submitToggleEnabled(<?= $managedUserId ?>, 1, <?= json_encode((string) ($managedUser["username"] ?? ""), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><?= t('users.actions.enable') ?></button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick='confirmAction(<?= json_encode(t('users.js.delete_confirm', ['username' => (string) ($managedUser["username"] ?? "")]), JSON_HEX_APOS | JSON_HEX_QUOT) ?>, () => submitProtectedAction("delete", <?= $managedUserId ?>, <?= json_encode(t('users.js.delete_reauth', ['username' => (string) ($managedUser["username"] ?? "")]), JSON_HEX_APOS | JSON_HEX_QUOT) ?>))'><?= t('common.delete') ?></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <?= t('users.invitations.title') ?>
                <span class="badge badge-blue"><?= count($pendingInvitations) ?></span>
            </div>
            <?php if (empty($pendingInvitations)): ?>
            <div class="empty-state" style="padding:24px"><?= t('users.invitations.empty') ?></div>
            <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th><?= t('users.table.col_user') ?></th><th><?= t('users.table.col_role') ?></th><th><?= t('users.invitations.col_expiry') ?></th><th><?= t('users.table.col_force_actions') ?></th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($pendingInvitations as $invitation): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600"><?= h((string) ($invitation['username'] ?? '')) ?></div>
                            <div style="font-size:12px;color:var(--text2)"><?= h(UserManager::displayName($invitation)) ?></div>
                            <div style="font-size:12px;color:var(--text2)"><?= !empty($invitation['email']) ? h((string) $invitation['email']) : t('users.table.no_email') ?></div>
                        </td>
                        <td><span class="badge <?= AppConfig::getRoleBadgeClass((string) ($invitation['role'] ?? '')) ?>"><?= h(AppConfig::getRoleLabel((string) ($invitation['role'] ?? ''))) ?></span></td>
                        <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) $invitation['expires_at'])) ?></td>
                        <td style="font-size:12px;color:var(--text2)">
                            <?php if (empty($invitation['force_actions'])): ?>
                            <?= t('common.none_f') ?>
                            <?php else: ?>
                            <?= h(implode(', ', array_map(static fn(string $key): string => (string) ($forceActionDefinitions[$key]['label'] ?? $key), (array) $invitation['force_actions']))) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-2" style="flex-wrap:wrap">
                                <?php if (!empty($invitation['token'])): ?>
                                <button class="btn btn-sm" onclick="copyValue(<?= h(json_encode(inviteAbsoluteUrl((string) $invitation['token']))) ?>)"><?= t('users.actions.copy_link') ?></button>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('users.invitations.revoke_reauth')) ?>" data-confirm-message="<?= h(t('users.invitations.revoke_confirm')) ?>" style="display:inline">
                                    <input type="hidden" name="action" value="revoke_invitation">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="invitation_id" value="<?= (int) ($invitation['id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><?= t('users.actions.revoke') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($blockedIps)): ?>
        <div class="card">
            <div class="card-header">
                <?= t('users.blocked_ips.title') ?>
                <span class="badge badge-red"><?= count($blockedIps) ?></span>
            </div>
            <table class="table">
                <thead><tr><th><?= t('users.blocked_ips.col_ip') ?></th><th><?= t('users.blocked_ips.col_attempts') ?></th><th><?= t('users.blocked_ips.col_last_attempt') ?></th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($blockedIps as $blocked): ?>
                    <tr>
                        <td class="mono"><?= h((string) ($blocked['ip'] ?? '')) ?></td>
                        <td><span class="badge badge-red"><?= (int) ($blocked['attempts'] ?? 0) ?></span></td>
                        <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) ($blocked['last_attempt'] ?? ''))) ?></td>
                        <td>
                            <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('users.blocked_ips.unblock_reauth')) ?>" style="display:inline">
                                <input type="hidden" name="action" value="unblock_ip">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="ip" value="<?= h((string) ($blocked['ip'] ?? '')) ?>">
                                <button type="submit" class="btn btn-sm btn-success"><?= t('users.actions.unblock') ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px">
<?php if ($inspectedUser && $securityOverview): ?>
        <?php $inspectedStepUpState = StepUpAuth::describeUser($inspectedUser); ?>
        <div class="card">
            <div class="card-header">
                <?= t('users.security.title_prefix') ?> <?= h((string) ($inspectedUser['username'] ?? '')) ?>
                <span class="badge <?= AppConfig::getRoleBadgeClass((string) ($inspectedUser['role'] ?? '')) ?>"><?= h(AppConfig::getRoleLabel((string) ($inspectedUser['role'] ?? ''))) ?></span>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div style="border:1px solid var(--border);border-radius:10px;padding:12px"><div style="font-size:11px;color:var(--text2)"><?= t('users.security.display_name') ?></div><div style="font-weight:600"><?= h(UserManager::displayName($inspectedUser)) ?></div></div>
                    <div style="border:1px solid var(--border);border-radius:10px;padding:12px"><div style="font-size:11px;color:var(--text2)"><?= t('users.security.last_login') ?></div><div style="font-weight:600"><?= !empty($inspectedUser['last_login']) ? h(formatDate((string) $inspectedUser['last_login'])) : t('common.never') ?></div></div>
                    <div style="border:1px solid var(--border);border-radius:10px;padding:12px"><div style="font-size:11px;color:var(--text2)">TOTP</div><div style="font-weight:600"><?= !empty($inspectedUser['totp_enabled']) ? t('common.active') : t('common.inactive') ?></div></div>
                    <div style="border:1px solid var(--border);border-radius:10px;padding:12px"><div style="font-size:11px;color:var(--text2)"><?= t('users.security.webauthn_keys') ?></div><div style="font-weight:600"><?= count((array) ($securityOverview['webauthn_keys'] ?? [])) ?></div></div>
                    <div style="border:1px solid var(--border);border-radius:10px;padding:12px"><div style="font-size:11px;color:var(--text2)"><?= t('users.security.primary_factor') ?></div><div style="font-weight:600"><?= h($inspectedStepUpState['primary_factor'] === StepUpAuth::FACTOR_WEBAUTHN ? 'WebAuthn' : ($inspectedStepUpState['primary_factor'] === StepUpAuth::FACTOR_CLASSIC ? t('users.factor.classic') : t('common.none'))) ?></div></div>
                </div>
                <div>
                    <div style="font-size:12px;font-weight:600;margin-bottom:8px"><?= t('users.security.active_sessions') ?></div>
                    <?php if (empty($securityOverview['sessions'])): ?>
                    <div class="empty-state" style="padding:18px"><?= t('users.security.no_sessions') ?></div>
                    <?php else: ?>
                    <table class="table">
                        <thead><tr><th><?= t('users.blocked_ips.col_ip') ?></th><th><?= t('users.security.col_browser') ?></th><th><?= t('users.security.col_activity') ?></th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ((array) $securityOverview['sessions'] as $securitySession): ?>
                            <tr>
                                <td class="mono" style="font-size:12px"><?= h((string) ($securitySession['ip'] ?? '')) ?></td>
                                <td style="font-size:12px"><?= h(browserLabel((string) ($securitySession['user_agent'] ?? ''))) ?></td>
                                <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) ($securitySession['last_activity'] ?? ''))) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('users.security.revoke_session_reauth')) ?>" data-confirm-message="<?= h(t('users.security.revoke_session_confirm')) ?>" style="display:inline">
                                        <input type="hidden" name="action" value="revoke_session">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) ($inspectedUser['id'] ?? 0) ?>">
                                        <input type="hidden" name="inspect_user" value="<?= (int) ($inspectedUser['id'] ?? 0) ?>">
                                        <input type="hidden" name="session_id" value="<?= (int) ($securitySession['id'] ?? 0) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><?= t('users.actions.revoke') ?></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-size:12px;font-weight:600;margin-bottom:8px"><?= t('users.security.recent_attempts') ?></div>
                    <?php if (empty($securityOverview['recent_login_attempts'])): ?>
                    <div class="empty-state" style="padding:18px"><?= t('users.security.no_attempts') ?></div>
                    <?php else: ?>
                    <table class="table">
                        <thead><tr><th><?= t('common.date') ?></th><th><?= t('users.blocked_ips.col_ip') ?></th><th><?= t('common.status') ?></th></tr></thead>
                        <tbody>
                            <?php foreach ((array) $securityOverview['recent_login_attempts'] as $loginAttempt): ?>
                            <tr>
                                <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) ($loginAttempt['attempted_at'] ?? ''))) ?></td>
                                <td class="mono" style="font-size:12px"><?= h((string) ($loginAttempt['ip'] ?? '')) ?></td>
                                <td><span class="badge <?= !empty($loginAttempt['success']) ? 'badge-green' : 'badge-red' ?>"><?= !empty($loginAttempt['success']) ? t('common.success_label') : t('common.failure_label') ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-size:12px;font-weight:600;margin-bottom:8px"><?= t('users.security.recent_activity') ?></div>
                    <?php if (empty($securityOverview['recent_activity'])): ?>
                    <div class="empty-state" style="padding:18px"><?= t('users.security.no_activity') ?></div>
                    <?php else: ?>
                    <table class="table">
                        <thead><tr><th><?= t('common.date') ?></th><th><?= t('common.action') ?></th><th><?= t('common.details') ?></th></tr></thead>
                        <tbody>
                            <?php foreach ((array) $securityOverview['recent_activity'] as $activityRow): ?>
                            <tr>
                                <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) ($activityRow['created_at'] ?? ''))) ?></td>
                                <td><span class="badge badge-gray"><?= h((string) ($activityRow['action'] ?? '')) ?></span></td>
                                <td style="font-size:12px;color:var(--text2)"><?= h((string) ($activityRow['details'] ?? '')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state" style="padding:32px"><?= t('users.security.select_user') ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'layout_bottom.php'; ?>
