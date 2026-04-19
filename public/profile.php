<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::check();

$flash = null;
$db = Database::getInstance();
$user = Auth::currentUser();
$userId = (int) ($user['id'] ?? 0);
$userDb = UserManager::getById($userId);
if (!$userDb) {
    Auth::logout();
    redirectTo('/login.php', ['expired' => 1, 'reason' => 'revoked']);
}

$forceActionDefinitions = UserManager::forceActionDefinitions();
$pendingForcedActions = Auth::pendingForcedActions();
$localeOptions = AppConfig::localeOptions();
$startPageOptions = AppConfig::startPageOptions();
$securityOverview = UserManager::getUserSecurityOverview($userDb);
$currentSessionToken = (string) ($_SESSION['session_token'] ?? '');
$totpSetup = null;
$sensitiveActions = ['totp_disable', 'revoke_session', 'primary_factor_update'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action !== '' && in_array($action, $sensitiveActions, true) && !StepUpAuth::consumeCurrentUserReauth('profile.sensitive')) {
        $flash = ['type' => 'danger', 'msg' => t('flash.profile.reauth_required')];
    } elseif ($action === 'profile_update') {
        UserManager::updateProfile($userId, [
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'job_title' => trim((string) ($_POST['job_title'] ?? '')),
            'preferred_locale' => (string) ($_POST['preferred_locale'] ?? 'fr'),
            'preferred_timezone' => trim((string) ($_POST['preferred_timezone'] ?? '')),
            'preferred_start_page' => (string) ($_POST['preferred_start_page'] ?? 'dashboard'),
            'preferred_theme' => (string) ($_POST['preferred_theme'] ?? 'dark'),
            'admin_notes' => (string) ($userDb['admin_notes'] ?? ''),
        ]);
        UserManager::removeForceAction($userId, UserManager::FORCE_ACTION_REVIEW_PROFILE);
        Auth::refreshSessionUser();
        Auth::syncForcedActions();
        $userDb = UserManager::getById($userId) ?? $userDb;
        $securityOverview = UserManager::getUserSecurityOverview($userDb);
        $pendingForcedActions = Auth::pendingForcedActions();
        $flash = ['type' => 'success', 'msg' => t('flash.profile.updated')];
    } elseif ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!password_verify($current, (string) ($userDb['password'] ?? ''))) {
            $flash = ['type' => 'danger', 'msg' => t('flash.profile.password_wrong')];
        } elseif (strlen($new) < 6) {
            $flash = ['type' => 'danger', 'msg' => t('flash.profile.password_too_short')];
        } elseif ($new !== $confirm) {
            $flash = ['type' => 'danger', 'msg' => t('flash.profile.password_mismatch')];
        } else {
            $hibpCount = checkHibp($new);
            if ($hibpCount > 0) {
                $flash = ['type' => 'danger', 'msg' => t('flash.profile.password_hibp', ['count' => $hibpCount])];
            } else {
                $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_BCRYPT), $userId]);
                UserManager::markPasswordChanged($userId);
                Auth::revokeUserSessions($userId);
                Auth::refreshSessionUser();
                Auth::syncForcedActions();
                $userDb = UserManager::getById($userId) ?? $userDb;
                $securityOverview = UserManager::getUserSecurityOverview($userDb);
                $pendingForcedActions = Auth::pendingForcedActions();
                Auth::log('password_change', 'Changement de mot de passe depuis le profil', 'warning');
                $flash = ['type' => 'success', 'msg' => t('flash.profile.password_updated')];
            }
        }
    } elseif ($action === 'totp_setup_start') {
        $totpSetup = Auth::totpSetupStart();
    } elseif ($action === 'totp_setup_confirm') {
        $code = preg_replace('/\s/', '', (string) ($_POST['totp_code'] ?? ''));
        if (Auth::totpSetupConfirm($code)) {
            $userDb = UserManager::getById($userId) ?? $userDb;
            $securityOverview = UserManager::getUserSecurityOverview($userDb);
            $pendingForcedActions = Auth::pendingForcedActions();
            $flash = ['type' => 'success', 'msg' => t('flash.profile.totp_enabled')];
        } else {
            $totpSetup = Auth::totpSetupStart();
            $flash = ['type' => 'danger', 'msg' => t('flash.profile.totp_invalid')];
        }
    } elseif ($action === 'totp_disable') {
        Auth::totpDisable($userId);
        $userDb = UserManager::getById($userId) ?? $userDb;
        $securityOverview = UserManager::getUserSecurityOverview($userDb);
        $pendingForcedActions = Auth::pendingForcedActions();
        $flash = ['type' => 'success', 'msg' => t('flash.profile.totp_disabled')];
    } elseif ($action === 'primary_factor_update') {
        try {
            StepUpAuth::choosePrimaryFactor($userId, (string) ($_POST['primary_second_factor'] ?? ''));
            Auth::refreshSessionUser();
            $userDb = UserManager::getById($userId) ?? $userDb;
            $securityOverview = UserManager::getUserSecurityOverview($userDb);
            $pendingForcedActions = Auth::pendingForcedActions();
            $flash = ['type' => 'success', 'msg' => t('flash.profile.primary_factor_updated')];
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'msg' => $e->getMessage() !== '' ? $e->getMessage() : t('flash.profile.primary_factor_invalid')];
        }
    } elseif ($action === 'revoke_session') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        if ($sessionId > 0 && Auth::revokeSessionById($sessionId, $userId)) {
            $securityOverview = UserManager::getUserSecurityOverview($userDb);
            $flash = ['type' => 'success', 'msg' => t('flash.profile.session_revoked')];
        } else {
            $flash = ['type' => 'danger', 'msg' => t('flash.profile.session_revoke_err')];
        }
    }
}

$webauthnKeys = $securityOverview['webauthn_keys'] ?? [];
$activeSessions = $securityOverview['sessions'] ?? [];
$recentActivity = $securityOverview['recent_activity'] ?? [];
$recentAttempts = $securityOverview['recent_login_attempts'] ?? [];
$statusMeta = UserManager::status($userDb);
$stepUpState = StepUpAuth::describeUser($userDb);
$primaryFactor = (string) ($stepUpState['primary_factor'] ?? StepUpAuth::FACTOR_NONE);
$availablePrimaryFactors = array_values((array) ($stepUpState['available_factors'] ?? []));

$title = t('profile.title');
$active = 'profile';
$subtitle = !empty($_GET['forced']) && !empty($pendingForcedActions)
    ? t('profile.subtitle_forced')
    : t('profile.subtitle');

include 'layout_top.php';
?>

<?php if (!empty($pendingForcedActions)): ?>
<div class="alert alert-warning" style="margin-bottom:16px">
    <strong><?= t('profile.required_actions') ?></strong><br>
    <?php foreach ($pendingForcedActions as $forcedAction): ?>
        <?= h($forceActionDefinitions[$forcedAction]['label'] ?? $forcedAction) ?><br>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid-2-sidebar" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,.7fr);gap:16px;align-items:start">
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><?= t('profile.section.profile') ?></div>
            <div class="card-body">
                <form method="POST" style="display:flex;flex-direction:column;gap:16px">
                    <input type="hidden" name="action" value="profile_update">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.first_name') ?></label>
                            <input type="text" name="first_name" class="form-control" value="<?= h((string) ($userDb['first_name'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.last_name') ?></label>
                            <input type="text" name="last_name" class="form-control" value="<?= h((string) ($userDb['last_name'] ?? '')) ?>">
                        </div>
                    </div>
                    <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.email') ?></label>
                            <input type="email" name="email" class="form-control" value="<?= h((string) ($userDb['email'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.phone') ?></label>
                            <input type="text" name="phone" class="form-control" value="<?= h((string) ($userDb['phone'] ?? '')) ?>" placeholder="+33...">
                        </div>
                    </div>
                    <div class="grid-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:12px">
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.job_title') ?></label>
                            <input type="text" name="job_title" class="form-control" value="<?= h((string) ($userDb['job_title'] ?? '')) ?>" placeholder="Ex. SRE">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.language') ?></label>
                            <select name="preferred_locale" class="form-control">
                                <?php foreach ($localeOptions as $localeKey => $localeLabel): ?>
                                <option value="<?= h((string) $localeKey) ?>" <?= (string) ($userDb['preferred_locale'] ?? 'fr') === (string) $localeKey ? 'selected' : '' ?>><?= h((string) $localeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.start_page') ?></label>
                            <select name="preferred_start_page" class="form-control">
                                <?php foreach ($startPageOptions as $pageKey => $pageMeta): ?>
                                <option value="<?= h((string) $pageKey) ?>" <?= (string) ($userDb['preferred_start_page'] ?? 'dashboard') === (string) $pageKey ? 'selected' : '' ?>><?= h((string) ($pageMeta['label'] ?? $pageKey)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.theme') ?></label>
                            <select name="preferred_theme" class="form-control">
                                <?php
                                $currentTheme = (string) ($userDb['preferred_theme'] ?? ThemeManager::DEFAULT_THEME_ID);
                                foreach (ThemeManager::listThemes() as $themeOption):
                                ?>
                                <option value="<?= h((string) $themeOption['id']) ?>" <?= $currentTheme === (string) $themeOption['id'] ? 'selected' : '' ?>><?= h((string) $themeOption['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('profile.timezone') ?></label>
                        <input type="text" name="preferred_timezone" class="form-control" value="<?= h((string) ($userDb['preferred_timezone'] ?? '')) ?>" placeholder="Ex. Europe/Paris">
                        <div style="font-size:12px;color:var(--text2);margin-top:4px"><?= t('profile.timezone_hint') ?></div>
                    </div>
                    <div class="flex gap-2" style="justify-content:flex-end">
                        <button type="submit" class="btn btn-primary"><?= t('profile.save') ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><?= t('profile.section.password') ?></div>
            <div class="card-body">
                <form method="POST" style="display:flex;flex-direction:column;gap:14px">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="form-group">
                        <label class="form-label"><?= t('profile.current_password') ?></label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.new_password') ?></label>
                            <input type="password" name="new_password" class="form-control" minlength="6" autocomplete="new-password" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.confirm_password') ?></label>
                            <input type="password" name="confirm_password" class="form-control" minlength="6" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="flex gap-2" style="justify-content:flex-end">
                        <button type="submit" class="btn btn-primary"><?= t('profile.update_password') ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><?= t('profile.section.totp') ?></div>
            <div class="card-body">
                <?php if (!empty($userDb['totp_enabled']) && !$totpSetup): ?>
                <div class="alert alert-success" style="margin-bottom:16px"><?= t('profile.totp_active') ?></div>
                <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('profile.totp_disable_reauth')) ?>" data-confirm-message="<?= h(t('profile.totp_disable_confirm')) ?>">
                    <input type="hidden" name="action" value="totp_disable">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-danger"><?= t('profile.totp_disable') ?></button>
                </form>
                <?php elseif ($totpSetup): ?>
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div style="font-size:13px"><?= t('profile.totp_scan') ?></div>
                    <div style="text-align:center">
                        <div style="display:inline-block;border-radius:10px;border:4px solid #fff;background:#fff;line-height:0">
                            <?= (string) $totpSetup['qr_svg'] ?>
                        </div>
                    </div>
                    <div style="font-size:12px;color:var(--text2)"><?= t('profile.totp_manual_key') ?></div>
                    <div style="font-family:var(--font-mono);font-size:14px;letter-spacing:2px;background:var(--bg3);padding:10px 12px;border-radius:8px;text-align:center;color:var(--accent)">
                        <?= h((string) $totpSetup['secret']) ?>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="totp_setup_confirm">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <div class="form-group">
                            <label class="form-label"><?= t('profile.totp_code_label') ?></label>
                            <input type="text" name="totp_code" class="form-control" style="font-family:var(--font-mono);font-size:20px;letter-spacing:6px;text-align:center" placeholder="000000" maxlength="6" pattern="\d{6}" autocomplete="one-time-code" required autofocus>
                        </div>
                        <div class="flex gap-2" style="justify-content:flex-end">
                            <a href="<?= routePath('/profile.php') ?>" class="btn"><?= t('common.cancel') ?></a>
                            <button type="submit" class="btn btn-primary"><?= t('profile.totp_confirm_btn') ?></button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <p style="font-size:13px;color:var(--text2);margin-bottom:16px"><?= t('profile.totp_helper') ?></p>
                <form method="POST">
                    <input type="hidden" name="action" value="totp_setup_start">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-primary"><?= t('profile.totp_enable') ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <?= t('profile.section.webauthn') ?>
                <span class="badge badge-blue"><?= count($webauthnKeys) ?></span>
            </div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--text2);margin-bottom:16px"><?= t('profile.webauthn_helper') ?></p>
                <?php if (!empty($webauthnKeys)): ?>
                <div class="table-wrap">
                <table class="table" style="margin-bottom:16px">
                    <thead><tr><th><?= t('profile.webauthn_table.name') ?></th><th><?= t('profile.webauthn_table.added') ?></th><th><?= t('profile.webauthn_table.uses') ?></th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($webauthnKeys as $key): ?>
                        <tr>
                            <td style="font-weight:500"><?= h((string) ($key['name'] ?? t('profile.webauthn_key_default'))) ?></td>
                            <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) $key['created_at'])) ?></td>
                            <td><span class="badge badge-gray"><?= (int) ($key['use_count'] ?? 0) ?></span></td>
                            <td><button class="btn btn-sm btn-danger" onclick="deleteWebAuthnKey(<?= (int) $key['id'] ?>)"><?= t('profile.webauthn_delete') ?></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
                <div id="webauthn-status" style="margin-bottom:12px;font-size:13px"></div>
                <div id="webauthn-help" style="margin-bottom:12px;font-size:12px;color:var(--text2)"><?= t('profile.webauthn_help') ?></div>
                <div class="flex gap-2" style="align-items:center">
                    <input type="text" id="webauthn-key-name" class="form-control" style="max-width:220px" placeholder="<?= h(t('profile.webauthn_add_name_placeholder')) ?>">
                    <button class="btn btn-primary" onclick="registerWebAuthnKey()" id="btn-webauthn-reg"><?= t('profile.webauthn_add') ?></button>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><?= t('profile.section.account') ?></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                <div>
                    <div style="font-size:11px;color:var(--text2)"><?= t('profile.account.username') ?></div>
                    <div style="font-size:15px;font-weight:600"><?= h((string) ($userDb['username'] ?? '')) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2)"><?= t('profile.account.display_name') ?></div>
                    <div><?= h(UserManager::displayName($userDb)) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2)"><?= t('profile.account.role') ?></div>
                    <span class="badge <?= AppConfig::getRoleBadgeClass((string) ($userDb['role'] ?? '')) ?>"><?= h(AppConfig::getRoleLabel((string) ($userDb['role'] ?? ''))) ?></span>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2)"><?= t('profile.account.status') ?></div>
                    <span class="badge <?= h((string) ($statusMeta['badge'] ?? 'badge-gray')) ?>"><?= h((string) ($statusMeta['label'] ?? t('common.unknown'))) ?></span>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2)"><?= t('profile.account.last_login') ?></div>
                    <div><?= !empty($userDb['last_login']) ? h(formatDate((string) $userDb['last_login'])) : t('common.never') ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2)"><?= t('profile.account.primary_factor') ?></div>
                    <div><?= h(t('profile.primary_factor.option.' . $primaryFactor)) ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><?= t('profile.section.primary_factor') ?></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                <div style="font-size:13px;color:var(--text2)"><?= t('profile.primary_factor.helper') ?></div>
                <?php if (empty($availablePrimaryFactors)): ?>
                <div class="alert alert-warning" style="margin:0"><?= t('profile.primary_factor.none_available') ?></div>
                <?php else: ?>
                <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('profile.primary_factor_reauth')) ?>" style="display:flex;flex-direction:column;gap:12px">
                    <input type="hidden" name="action" value="primary_factor_update">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="form-group">
                        <label class="form-label"><?= t('profile.primary_factor.label') ?></label>
                        <select name="primary_second_factor" class="form-control">
                            <?php foreach ($availablePrimaryFactors as $factor): ?>
                            <option value="<?= h((string) $factor) ?>" <?= $primaryFactor === (string) $factor ? 'selected' : '' ?>><?= h(t('profile.primary_factor.option.' . (string) $factor)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="font-size:12px;color:var(--text2)">
                        <?php if (in_array(StepUpAuth::FACTOR_CLASSIC, $availablePrimaryFactors, true) && in_array(StepUpAuth::FACTOR_WEBAUTHN, $availablePrimaryFactors, true)): ?>
                        <?= t('profile.primary_factor.both_available') ?>
                        <?php elseif (in_array(StepUpAuth::FACTOR_WEBAUTHN, $availablePrimaryFactors, true)): ?>
                        <?= t('profile.primary_factor.webauthn_only') ?>
                        <?php else: ?>
                        <?= t('profile.primary_factor.classic_only') ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-2" style="justify-content:flex-end">
                        <button type="submit" class="btn btn-primary"><?= t('profile.primary_factor.save') ?></button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <?= t('profile.section.sessions') ?>
                <span class="badge badge-blue"><?= count($activeSessions) ?></span>
            </div>
            <?php if (empty($activeSessions)): ?>
            <div class="empty-state" style="padding:24px"><?= t('profile.empty_sessions') ?></div>
            <?php else: ?>
            <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= t('profile.sessions_table.ip') ?></th><th><?= t('profile.sessions_table.login') ?></th><th><?= t('profile.sessions_table.activity') ?></th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($activeSessions as $session): ?>
                    <?php $isCurrent = (string) ($session['session_token'] ?? '') === $currentSessionToken; ?>
                    <tr style="<?= $isCurrent ? 'background:var(--bg3)' : '' ?>">
                        <td class="mono" style="font-size:12px">
                            <?= h((string) ($session['ip'] ?? '')) ?>
                            <?php if ($isCurrent): ?>
                            <span class="badge badge-green" style="font-size:10px"><?= t('profile.session_current') ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) $session['created_at'])) ?></td>
                        <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) $session['last_activity'])) ?></td>
                        <td>
                            <?php if (!$isCurrent): ?>
                            <form method="POST" onsubmit="return submitProtectedForm(this)" data-reauth-message="<?= h(t('profile.session_revoke_reauth')) ?>" data-confirm-message="<?= h(t('profile.session_revoke_confirm')) ?>" style="display:inline">
                                <input type="hidden" name="action" value="revoke_session">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><?= t('profile.session_revoke') ?></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><?= t('profile.section.recent_logins') ?></div>
            <?php if (empty($recentAttempts)): ?>
            <div class="empty-state" style="padding:24px"><?= t('profile.empty_attempts') ?></div>
            <?php else: ?>
            <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= t('profile.logins_table.date') ?></th><th><?= t('profile.logins_table.ip') ?></th><th><?= t('profile.logins_table.status') ?></th></tr></thead>
                <tbody>
                    <?php foreach ($recentAttempts as $attempt): ?>
                    <tr>
                        <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) $attempt['attempted_at'])) ?></td>
                        <td class="mono" style="font-size:12px"><?= h((string) ($attempt['ip'] ?? '')) ?></td>
                        <td><span class="badge <?= !empty($attempt['success']) ? 'badge-green' : 'badge-red' ?>"><?= !empty($attempt['success']) ? t('profile.login_success') : t('profile.login_fail') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><?= t('profile.section.recent_activity') ?></div>
            <?php if (empty($recentActivity)): ?>
            <div class="empty-state" style="padding:24px"><?= t('profile.empty_activity') ?></div>
            <?php else: ?>
            <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= t('profile.activity_table.date') ?></th><th><?= t('profile.activity_table.action') ?></th><th><?= t('profile.activity_table.details') ?></th></tr></thead>
                <tbody>
                    <?php foreach ($recentActivity as $activityRow): ?>
                    <tr>
                        <td style="font-size:12px;color:var(--text2)"><?= h(formatDate((string) $activityRow['created_at'])) ?></td>
                        <td><span class="badge badge-gray"><?= h((string) ($activityRow['action'] ?? '')) ?></span></td>
                        <td style="font-size:12px;color:var(--text2)"><?= h((string) ($activityRow['details'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script<?= cspNonceAttr() ?>>
const CSRF = '<?= csrfToken() ?>';
const webauthnStatusEl = document.getElementById('webauthn-status');
const webauthnHelpEl = document.getElementById('webauthn-help');
const webauthnButtonEl = document.getElementById('btn-webauthn-reg');

function submitProtectedForm(form) {
    if (!form) {
        return false;
    }
    if (form.dataset.reauthVerified === '1') {
        delete form.dataset.reauthVerified;
        return true;
    }
    const message = form.dataset.reauthMessage || (window.FULGURITE_STRINGS?.confirm_identity || 'Confirm your identity');
    requireReauth(() => {
        form.dataset.reauthVerified = '1';
        form.submit();
    }, message);
    return false;
}

function arrayBufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach((byte) => {
        binary += String.fromCharCode(byte);
    });
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

function updateWebAuthnAvailabilityNotice() {
    if (!window.isSecureContext) {
        webauthnStatusEl.innerHTML = '<span style="color:var(--yellow)">' + (window.FULGURITE_STRINGS?.webauthn_unavailable_https || 'WebAuthn unavailable') + '</span>';
        webauthnHelpEl.innerHTML = (window.FULGURITE_STRINGS?.webauthn_use_https || 'Use HTTPS') + ' <code>https://</code> <code>localhost</code>';
        webauthnButtonEl.disabled = true;
        return;
    }
    if (!window.PublicKeyCredential || !navigator.credentials?.create) {
        webauthnStatusEl.innerHTML = '<span style="color:var(--red)">' + (window.FULGURITE_STRINGS?.webauthn_unavailable_browser || 'WebAuthn unavailable') + '</span>';
        webauthnHelpEl.innerHTML = window.FULGURITE_STRINGS?.webauthn_try_https || 'Try HTTPS';
        webauthnButtonEl.disabled = true;
        return;
    }
    webauthnStatusEl.innerHTML = '';
    webauthnHelpEl.innerHTML = (window.FULGURITE_STRINGS?.webauthn_requires_https || 'WebAuthn requires HTTPS or localhost');
    webauthnButtonEl.disabled = false;
}

async function performWebAuthnRegistration() {
    if (!window.isSecureContext) {
        webauthnStatusEl.innerHTML = '<span style="color:var(--yellow)">' + (window.FULGURITE_STRINGS?.webauthn_requires_https || 'WebAuthn requires HTTPS') + '</span>';
        return;
    }
    if (!window.PublicKeyCredential) {
        webauthnStatusEl.innerHTML = '<span style="color:var(--red)">' + (window.FULGURITE_STRINGS?.webauthn_unsupported || 'WebAuthn unsupported') + '</span>';
        return;
    }

    const _locale = (window.FULGURITE_CONFIG && window.FULGURITE_CONFIG.locale) ? window.FULGURITE_CONFIG.locale + '-' + window.FULGURITE_CONFIG.locale.toUpperCase() : 'fr-FR';
    const name = document.getElementById('webauthn-key-name').value.trim() || new Date().toLocaleDateString(_locale);
    const button = document.getElementById('btn-webauthn-reg');
    button.disabled = true;
    webauthnStatusEl.innerHTML = '<span style="color:var(--text2)">' + (window.FULGURITE_STRINGS?.webauthn_waiting || '...') + '</span>';

    try {
        const optionsResponse = await fetch('/api/webauthn_register_options.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({ csrf_token: CSRF }),
        });
        const optionsData = await optionsResponse.json();
        if (!optionsData.success) {
            throw new Error(optionsData.error || (window.FULGURITE_STRINGS?.webauthn_prepare_error || 'Unable to prepare WebAuthn'));
        }

        const { options } = optionsData;
        options.challenge = Uint8Array.from(atob(options.challenge), (char) => char.charCodeAt(0));
        options.user.id = Uint8Array.from(atob(options.user.id), (char) => char.charCodeAt(0));

        const credential = await navigator.credentials.create({ publicKey: options });
        const credentialData = {
            id: credential.id,
            rawId: arrayBufferToBase64Url(credential.rawId),
            type: credential.type,
            clientDataJSON: arrayBufferToBase64Url(credential.response.clientDataJSON),
            attestationObject: arrayBufferToBase64Url(credential.response.attestationObject),
            transports: typeof credential.response.getTransports === 'function' ? credential.response.getTransports() : [],
        };

        const verifyResponse = await fetch('/api/webauthn_register_verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({ credential: credentialData, name, csrf_token: CSRF }),
        });
        const result = await verifyResponse.json();
        if (result.success) {
            webauthnStatusEl.innerHTML = '<span style="color:var(--green)">' + (window.FULGURITE_STRINGS?.webauthn_registered || 'Key registered') + '</span>';
            setTimeout(() => window.location.reload(), 1200);
        } else {
            webauthnStatusEl.innerHTML = `<span style="color:var(--red)">${result.error || '<?= h(t('common.unknown_error')) ?>'}</span>`;
        }
    } catch (error) {
        webauthnStatusEl.innerHTML = `<span style="color:var(--red)">${error.message || '<?= h(t('js.operation_cancelled')) ?>'}</span>`;
    } finally {
        button.disabled = false;
    }
}

function registerWebAuthnKey() {
    if (typeof window.requireReauth !== 'function') {
        void performWebAuthnRegistration();
        return;
    }
    window.requireReauth(() => { void performWebAuthnRegistration(); }, window.FULGURITE_STRINGS?.confirm_add_hardware_key || 'Confirm your identity');
}

async function performWebAuthnDeletion(credentialId) {
    const response = await fetch('/api/webauthn_delete_key.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ credential_id: credentialId, csrf_token: CSRF }),
    });
    const data = await response.json();
    if (data.success) {
        window.location.reload();
        return;
    }
    window.toast((window.FULGURITE_STRINGS?.error_prefix || 'Error: ') + (data.error || ''), 'error');
}

async function deleteWebAuthnKey(credentialId) {
    const confirmed = await window.confirmActionAsync(window.FULGURITE_STRINGS?.delete_hardware_key_confirm || 'Delete this hardware key?');
    if (!confirmed) {
        return;
    }
    if (typeof window.requireReauth !== 'function') {
        void performWebAuthnDeletion(credentialId);
        return;
    }
    window.requireReauth(() => { void performWebAuthnDeletion(credentialId); }, window.FULGURITE_STRINGS?.confirm_delete_hardware_key || 'Confirm your identity');
}

updateWebAuthnAvailabilityNotice();

window.submitProtectedForm = submitProtectedForm;
window.registerWebAuthnKey = registerWebAuthnKey;
window.deleteWebAuthnKey = deleteWebAuthnKey;
</script>

<?php include 'layout_bottom.php'; ?>
