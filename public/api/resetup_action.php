<?php
/**
 * resetup_action.php — Backend API for system reconfiguration wizard.
 *
 * Security :
 *  - Restricted to administrators (Auth::requireAdmin).
 *  - Mandatory CSRF verification.
 *  - Rate limiting: 30 requests / 60 s.
 *  - Sudo password is NEVER logged or stored in session.
 *  - All critical actions are audited via Auth::log().
 *  - Step2+ actions require $_SESSION['resetup_authed'].
 *  - All actions except confirm_start require $_SESSION['resetup_confirmed'].
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Setup/ResetupWizard.php';
require_once __DIR__ . '/../../src/Setup/ResetupPermissions.php';
require_once __DIR__ . '/../../src/Setup/ResetupSudoRunner.php';

Auth::requireAdmin();
verifyCsrf();
rateLimitApi('resetup_action', 30, 60);

// ── Read JSON body ───────────────────────────────────────────────────────
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim((string) ($body['action'] ?? ''));

if ($action === '') {
    jsonResponse(['success' => false, 'error' => t('resetup.api.error.action_missing')], 400);
}

// ── Session check: all actions except confirm_start ────────────
if ($action !== 'confirm_start') {
    if (empty($_SESSION['resetup_confirmed'])) {
        Auth::log('resetup_unauthorized', 'Tentative d\'action resetup sans confirmation préalable : ' . $action, 'warning');
        jsonResponse(['success' => false, 'error' => t('resetup.api.error.session_not_initialized')], 403);
    }
}

// ── Enhanced authentication check: step2+ actions ───────────────────────────
$requiresAuth = !in_array($action, ['confirm_start', 'cancel', 'step1_verify_auth'], true);
if ($requiresAuth && empty($_SESSION['resetup_authed'])) {
    Auth::log('resetup_unauthorized', 'Tentative d\'action resetup sans reauth : ' . $action, 'warning');
    jsonResponse(['success' => false, 'error' => t('resetup.api.error.enhanced_auth_required')], 403);
}

// ═════════════════════════════════════════════════════════════════════════════
// Action dispatch
// ═════════════════════════════════════════════════════════════════════════════

switch ($action) {

    // ──────────────────────────────────────────────────────────────────────────
    // confirm_start — Initialize reconfiguration session
    // ──────────────────────────────────────────────────────────────────────────
    case 'confirm_start':
        $_SESSION['resetup_confirmed'] = true;
        $_SESSION['resetup_authed']    = false; // Must still pass Step 1
        Auth::log('resetup_start', 'Reconfiguration système démarrée par l\'administrateur', 'warning');
        jsonResponse(['success' => true]);


    // ──────────────────────────────────────────────────────────────────────────
    // cancel — Clear session and log cancellation
    // ──────────────────────────────────────────────────────────────────────────
    case 'cancel':
        // Clear all resetup_ session keys
        foreach (array_keys($_SESSION) as $key) {
            if (str_starts_with((string) $key, 'resetup_')) {
                unset($_SESSION[$key]);
            }
        }
        Auth::log('resetup_cancelled', 'Reconfiguration système annulée', 'info');
        jsonResponse(['success' => true]);


    // ──────────────────────────────────────────────────────────────────────────
    // step1_verify_auth — Check password + TOTP, set reauth session
    // ──────────────────────────────────────────────────────────────────────────
    case 'step1_verify_auth':
        $password = (string) ($body['password'] ?? '');
        $totpCode = trim((string) ($body['totp_code'] ?? ''));

        if ($password === '') {
            jsonResponse(['success' => false, 'error' => t('resetup.js.password_required')]);
        }

        $user = Auth::currentUserRecord();
        if (!$user) {
            jsonResponse(['success' => false, 'error' => 'Session invalide.'], 403);
        }

        $result = StepUpAuth::beginInteractiveReauth($user, $password, $totpCode, 'resetup.step1');
        if (!empty($result['success']) && !empty($result['completed'])) {
            Auth::log('resetup_auth_success', 'Authentification renforcée réussie pour le wizard resetup', 'info');
        }
        jsonResponse($result);


    // ──────────────────────────────────────────────────────────────────────────
    // step2_diagnostic — Analyze current system
    // ──────────────────────────────────────────────────────────────────────────
    case 'step2_diagnostic':
        try {
            $diagnostic = ResetupWizard::runDiagnostic();
            Auth::log('resetup_diagnostic', 'Diagnostic système exécuté', 'info');

            // Build {label, value, status} item list for JS table
            $items = [];
            $items[] = [
                'label'  => t('resetup.api.diagnostic.environment'),
                'value'  => $diagnostic['is_docker'] ? t('resetup.api.diagnostic.environment_docker') : t('resetup.api.diagnostic.environment_bare_metal'),
                'status' => 'ok',
            ];
            $items[] = [
                'label'  => t('resetup.api.diagnostic.php_version'),
                'value'  => $diagnostic['php_version'],
                'status' => 'ok',
            ];
            $items[] = [
                'label'  => t('resetup.api.diagnostic.os'),
                'value'  => $diagnostic['os'],
                'status' => 'ok',
            ];
            $items[] = [
                'label'  => t('resetup.api.diagnostic.fpm_user'),
                'value'  => $diagnostic['fpm_user'],
                'status' => 'ok',
            ];
            $items[] = [
                'label'  => t('resetup.api.diagnostic.fpm_group'),
                'value'  => $diagnostic['fpm_group'],
                'status' => 'ok',
            ];
            $items[] = [
                'label'  => t('resetup.api.diagnostic.worker_mode'),
                'value'  => $diagnostic['worker_mode'],
                'status' => $diagnostic['worker_running'] ? 'ok' : 'warn',
            ];
            $agentStatus = $diagnostic['secret_agent']['reachable'] ? 'ok'
                         : ($diagnostic['secret_agent']['configured'] ? 'warn' : 'ok');
            $items[] = [
                'label'  => t('resetup.api.diagnostic.secret_agent'),
                'value'  => $diagnostic['secret_agent']['configured']
                    ? ($diagnostic['secret_agent']['reachable'] ? t('resetup.api.diagnostic.secret_agent_reachable') : t('resetup.api.diagnostic.secret_agent_unreachable'))
                    : t('resetup.api.diagnostic.secret_agent_unconfigured'),
                'status' => $agentStatus,
            ];
            foreach ($diagnostic['app_directories'] as $dir) {
                $items[] = [
                    'label'  => t('resetup.api.diagnostic.directory_prefix') . basename((string)($dir['path'] ?? '')),
                    'value'  => ($dir['exists'] ? $dir['user'] . ':' . $dir['group'] . ' (' . $dir['mode'] . ')' : t('resetup.api.diagnostic.missing')),
                    'status' => $dir['exists'] ? 'ok' : 'warn',
                ];
            }

            jsonResponse([
                'success'        => true,
                'data'           => $items,
                'detected_user'  => $diagnostic['fpm_user']  ?? '',
                'detected_group' => $diagnostic['fpm_group'] ?? '',
            ]);
        } catch (Throwable $e) {
            Auth::log('resetup_diagnostic_error', 'Erreur diagnostic : ' . $e->getMessage(), 'error');
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.diagnostic_prefix') . $e->getMessage()]);
        }


    // ──────────────────────────────────────────────────────────────────────────
    // step3_save_selection — Validate and save in session
    // ──────────────────────────────────────────────────────────────────────────
    case 'step3_save_selection':
        $fpmUser    = trim((string) ($body['fpm_user']    ?? ''));
        $fpmGroup   = trim((string) ($body['fpm_group']   ?? ''));
        $workerMode = trim((string) ($body['worker_mode'] ?? 'systemd'));

        // Validation user/group
        if (!ResetupWizard::validateUser($fpmUser)) {
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.invalid_username')]);
        }
        if (!ResetupWizard::validateUser($fpmGroup)) {
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.invalid_group')]);
        }

        // Validation mode worker
        $allowedModes = ['systemd', 'cron', 'daemon'];
        if (!in_array($workerMode, $allowedModes, true)) {
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.invalid_worker_mode')]);
        }

        // backup en session
        $_SESSION['resetup_selection'] = [
            'fpm_user'    => $fpmUser,
            'fpm_group'   => $fpmGroup,
            'worker_mode' => $workerMode,
        ];

        Auth::log(
            'resetup_selection_saved',
            sprintf('Sélection enregistrée — user: %s, group: %s, mode: %s', $fpmUser, $fpmGroup, $workerMode),
            'info'
        );

        jsonResponse(['success' => true]);


    // ──────────────────────────────────────────────────────────────────────────
    // step4_test_sudo_nopass — Test sudo without password
    // ──────────────────────────────────────────────────────────────────────────
    case 'step4_test_sudo_nopass':
        $sudoOk = ResetupSudoRunner::testNoPassword();

        if ($sudoOk) {
            $_SESSION['resetup_sudo_ok'] = true;
            Auth::log('resetup_sudo_nopass_ok', 'sudo NOPASSWD disponible', 'info');
        } else {
            Auth::log('resetup_sudo_nopass_fail', 'sudo NOPASSWD non disponible', 'info');
        }

        jsonResponse(['success' => true, 'sudo_ok' => $sudoOk]);


    // ──────────────────────────────────────────────────────────────────────────
    // step4_test_sudo_withpass — Test sudo with password
    // Password is NEVER logged or stored in session.
    // ──────────────────────────────────────────────────────────────────────────
    case 'step4_test_sudo_withpass':
        $sudoPassword = (string) ($body['sudo_password'] ?? '');

        if ($sudoPassword === '') {
            jsonResponse(['success' => false, 'error' => t('resetup.js.sudo_pass_required')]);
        }

        try {
            // Minimal test: whoami with provided password
            $result = ResetupSudoRunner::runWithPassword($sudoPassword, ['/usr/bin/whoami']);
            unset($sudoPassword); // Immediate clearing — never keep in memory
            // Note: ResetupSudoRunner::runWithPassword() already unsets internally,
            //        but it is also cleared here for defense in depth.

            if ($result['success']) {
                $_SESSION['resetup_sudo_ok'] = true;
                // Log without password
                Auth::log('resetup_sudo_withpass_ok', 'sudo avec mot de passe : test réussi', 'warning');
                jsonResponse(['success' => true, 'sudo_ok' => true]);
            } else {
                Auth::log('resetup_sudo_withpass_fail', 'sudo avec mot de passe : test échoué', 'warning');
                jsonResponse(['success' => true, 'sudo_ok' => false, 'error' => t('resetup.api.error.sudo_invalid_or_unavailable')]);
            }
        } catch (Throwable $e) {
            unset($sudoPassword);
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.sudo_test_prefix') . $e->getMessage()]);
        }


    // ──────────────────────────────────────────────────────────────────────────
    // step5_apply_permissions — Apply chown/chmod permissions
    // ──────────────────────────────────────────────────────────────────────────
    case 'step5_apply_permissions':
        // Check sudo is OK (or manual mode valid)
        if (empty($_SESSION['resetup_sudo_ok']) && empty($_SESSION['resetup_manual_ok'])) {
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.step4_required')]);
        }

        $selection = $_SESSION['resetup_selection'] ?? [];
        if (empty($selection['fpm_user']) || empty($selection['fpm_group'])) {
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.step3_missing')]);
        }

        try {
            $paths  = ResetupPermissions::getAppDirectories();
            $result = ResetupPermissions::applyAll(
                $selection['fpm_user'],
                $selection['fpm_group'],
                $paths
            );
            FilesystemScopeGuard::writeCurrentPolicyFile();
            Auth::log(
                'resetup_permissions_applied',
                sprintf(
                    'Permissions appliquées — user: %s, group: %s — %s',
                    $selection['fpm_user'],
                    $selection['fpm_group'],
                    $result['success'] ? 'succès' : 'échec partiel'
                ),
                'warning'
            );
            // Convert to log line array for frontend
            $logLines = [];
            foreach ($result['results'] as $r) {
                $logLines[] = [
                    'type'    => $r['success'] ? 'ok' : 'err',
                    'message' => ($r['success'] ? '✓ ' : '✗ ') . $r['path'],
                ];
                if (!$r['success'] && !empty($r['chown_result']['output'])) {
                    $logLines[] = ['type' => 'warn', 'message' => '  ' . $r['chown_result']['output']];
                }
            }
            if (!empty($result['manual_commands'])) {
                $logLines[] = ['type' => 'warn', 'message' => t('resetup.api.message.manual_commands_available')];
            }
            jsonResponse(['success' => $result['success'], 'log' => $logLines, 'manual_commands' => $result['manual_commands'] ?? []]);
        } catch (Throwable $e) {
            Auth::log('resetup_permissions_error', 'Erreur permissions : ' . $e->getMessage(), 'error');
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.apply_permissions_prefix') . $e->getMessage()]);
        }


    // ──────────────────────────────────────────────────────────────────────────
    // step6_get_status — Current worker status (read-only)
    // ──────────────────────────────────────────────────────────────────────────
    case 'step6_get_status':
        $status = WorkerManager::getStatus(AppConfig::workerDefaultName());
        jsonResponse(['success' => true, 'status' => $status]);


    // ──────────────────────────────────────────────────────────────────────────
    // step6_apply_worker — Stop, reconfigure, restart worker
    // ──────────────────────────────────────────────────────────────────────────
    case 'step6_apply_worker':
        $selection = $_SESSION['resetup_selection'] ?? [];
        if (empty($selection['worker_mode'])) {
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.step3_worker_mode_missing')]);
        }

        try {
            $result = ResetupWizard::applyWorkerReconfig(
                $selection['fpm_user']  ?? '',
                $selection['fpm_group'] ?? ''
            );
            Auth::log(
                'resetup_worker_reconfigured',
                sprintf(
                    'Worker reconfiguré — user: %s group: %s mode: %s — %s',
                    $selection['fpm_user'] ?? '',
                    $selection['fpm_group'] ?? '',
                    $selection['worker_mode'],
                    $result['success'] ? 'succès' : 'échec'
                ),
                'warning'
            );
            // Convert steps to frontend log lines
            $logLines = [];
            foreach ($result['steps'] as $step) {
                $logLines[] = [
                    'type'    => $step['success'] ? 'ok' : 'err',
                    'message' => ($step['success'] ? '✓ ' : '✗ ') . $step['label'] . ($step['output'] !== '' ? ' — ' . $step['output'] : ''),
                ];
            }
            jsonResponse(['success' => $result['success'], 'log' => $logLines]);
        } catch (Throwable $e) {
            Auth::log('resetup_worker_error', 'Erreur reconfiguration worker : ' . $e->getMessage(), 'error');
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.worker_reconfigure_prefix') . $e->getMessage()]);
        }


    // ──────────────────────────────────────────────────────────────────────────
    // step7_apply_agent — Reconfigure secreatet agent socket
    // ──────────────────────────────────────────────────────────────────────────
    case 'step7_apply_agent':
        $selection = $_SESSION['resetup_selection'] ?? [];

        try {
            $socketPath = SecretStore::agentSocketPath();
            $result     = ResetupWizard::applyAgentReconfig($socketPath);

            Auth::log('resetup_agent_reconfigured', 'Secret agent socket reconfiguré — socket: ' . $socketPath, 'warning');

            // Partial resetup session cleanup (keep _confirmed for audit trail)
            unset(
                $_SESSION['resetup_authed'],
                $_SESSION['resetup_selection'],
                $_SESSION['resetup_sudo_ok'],
                $_SESSION['resetup_manual_ok']
            );

            // Build log lines for frontend
            $logLines = [[
                'type'    => $result['success'] ? 'ok' : 'warn',
                'message' => $result['message'],
            ]];

            jsonResponse([
                'success'     => $result['success'],
                'socket_ok'   => $result['reachable'],
                'socket_path' => $socketPath,
                'log'         => $logLines,
            ]);
        } catch (Throwable $e) {
            Auth::log('resetup_agent_error', 'Erreur reconfiguration agent : ' . $e->getMessage(), 'error');
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.agent_reconfigure_prefix') . $e->getMessage()]);
        }


    // ──────────────────────────────────────────────────────────────────────────
    // get_manual_commands — Return commands to run manually
    // ──────────────────────────────────────────────────────────────────────────
    case 'get_manual_commands':
        $selection = $_SESSION['resetup_selection'] ?? [];
        if (empty($selection['fpm_user']) || empty($selection['fpm_group'])) {
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.step3_missing')]);
        }

        try {
            $paths = ResetupPermissions::getAppDirectories();
            // generateManualCommands already returns ready shell lines (strings)
            $lines = ResetupPermissions::generateManualCommands(
                $selection['fpm_user'],
                $selection['fpm_group'],
                $paths
            );

            // Mark that user viewed manual commands
            $_SESSION['resetup_manual_ok'] = true;

            Auth::log('resetup_manual_commands_viewed', 'Commandes manuelles consultées', 'info');
            jsonResponse(['success' => true, 'commands' => $lines]);
        } catch (Throwable $e) {
            jsonResponse(['success' => false, 'error' => t('resetup.api.error.manual_commands_prefix') . $e->getMessage()]);
        }


    // ──────────────────────────────────────────────────────────────────────────
    // Action unknown
    // ──────────────────────────────────────────────────────────────────────────
    default:
        Auth::log('resetup_unknown_action', 'Action inconnue reçue : ' . $action, 'warning');
        jsonResponse(['success' => false, 'error' => t('backup_templates.api.error.action_unknown')], 400);
}
