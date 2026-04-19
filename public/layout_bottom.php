</div><!-- .content -->

<footer class="page-footer">
    <div class="page-footer-inner">
        <div class="page-footer-left">
            <span class="page-footer-app"><?= h(AppConfig::appName()) ?></span>
            <span class="page-footer-version">v<?= h(APP_VERSION) ?></span>
        </div>
        <nav class="page-footer-links" aria-label="<?= h(t('layout.useful_links')) ?>">
            <?php if (Auth::isLoggedIn()): ?>
            <button type="button" class="page-footer-link" onclick="window.openOnboardingWizard(0)">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M6.5 6a1.5 1.5 0 0 1 3 0c0 .9-.8 1.4-1.2 1.8C7.9 8.2 8 8.6 8 9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                    <circle cx="8" cy="11.5" r=".75" fill="currentColor"/>
                </svg>
                <?= t('layout.start_guide') ?>
            </button>
            <span class="page-footer-sep" aria-hidden="true">·</span>
            <?php endif; ?>
            <a href="https://restic.net/docs/" target="_blank" rel="noopener" class="page-footer-link"><?= t('layout.restic_docs') ?></a>
            <span class="page-footer-sep" aria-hidden="true">·</span>
            <a href="https://github.com/restic/restic" target="_blank" rel="noopener" class="page-footer-link"><?= t('layout.github_restic') ?></a>
            <?php if (Auth::hasPermission('admin')): ?>
            <span class="page-footer-sep" aria-hidden="true">·</span>
            <a href="<?= routePath('/settings.php') ?>" class="page-footer-link"><?= t('layout.settings') ?></a>
            <?php endif; ?>
        </nav>
    </div>
</footer>

    </main>
</div><!-- .app -->

<?php ThemeRenderer::renderSlot('footer', $_ctx ?? ThemeRenderer::buildContext([])); ?>

<?php if (Auth::isLoggedIn()) include __DIR__ . '/partials/onboarding_wizard.php'; ?>

<?php
$sessionInfo    = Auth::sessionInfo();
$inactivitySecs = $sessionInfo['inactivity_seconds'];
$warningSecs    = $sessionInfo['warning_seconds'];
$stepUpConfig   = StepUpAuth::currentUserConfig();
$activePage = (string) ($active ?? '');
$reauthScriptPages = ['profile', 'repos', 'settings', 'users'];
$loadReauthUi = in_array($activePage, $reauthScriptPages, true);
?>
<script<?= cspNonceAttr() ?>>
window.FULGURITE_CONFIG = <?= json_encode([
    'csrfToken' => csrfToken(),
    'locale' => Translator::locale(),
    'timezone' => [
        'name' => AppConfig::timezone(),
        'label' => AppConfig::timezoneLabel(),
        'server' => AppConfig::serverTimezone(),
        'usesServerDefault' => AppConfig::timezoneUsesServerDefault(),
    ],
    'session' => [
        'inactivityMs' => $inactivitySecs * 1000,
        'warningMs' => $warningSecs * 1000,
    ],
    'reauth' => [
        'totpEnabled' => !empty($stepUpConfig['totp_enabled']),
        'webauthnEnabled' => !empty($stepUpConfig['webauthn_enabled']),
        'primaryFactor' => (string) ($stepUpConfig['primary_factor'] ?? StepUpAuth::FACTOR_NONE),
        'availableFactors' => array_values((array) ($stepUpConfig['available_factors'] ?? [])),
    ],
    'notifications' => [
        'enabled' => Auth::isLoggedIn(),
        'userId' => (int) ($_SESSION['user_id'] ?? 0),
        'browserChannelEnabled' => AppConfig::getBool('web_push_enabled'),
        'feedUrl' => routePath('/api/notifications_feed.php'),
        'centerUrl' => routePath('/notifications.php'),
        'pollIntervalMs' => 20000,
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

window.FULGURITE_STRINGS = <?= json_encode([
    'confirm_identity'            => t('js.confirm_identity'),
    'webauthn_waiting'            => t('js.webauthn_waiting'),
    'webauthn_registered'         => t('js.webauthn_registered'),
    'webauthn_unavailable_https'  => t('js.webauthn_unavailable_https'),
    'webauthn_use_https'          => t('js.webauthn_use_https'),
    'webauthn_unavailable_browser'=> t('js.webauthn_unavailable_browser'),
    'webauthn_try_https'          => t('js.webauthn_try_https'),
    'webauthn_requires_https'     => t('js.webauthn_requires_https'),
    'webauthn_unsupported'        => t('js.webauthn_unsupported'),
    'snapshot_refresh_queued'     => t('js.snapshot_refresh_queued'),
    'confirm_add_hardware_key'    => t('js.confirm_add_hardware_key'),
    'confirm_delete_hardware_key' => t('js.confirm_delete_hardware_key'),
    'delete_hardware_key_confirm' => t('js.delete_hardware_key_confirm'),
    'error_prefix'                => t('js.error_prefix'),
    'probe_done'                  => t('js.probe_done'),
    'network_error'               => t('js.network_error'),
    'probe_network_error'         => t('js.probe_network_error'),
    'probed_time_prefix'          => t('js.probed_time_prefix'),
    'repo_prefix'                 => t('js.repo_prefix'),
    'operation_cancelled'         => t('js.operation_cancelled'),
    'loading'                     => t('js.loading'),
    'webauthn_session_invalid'    => t('js.webauthn_session_invalid'),
    'webauthn_prepare_error'      => t('js.webauthn_prepare_error'),
    'webauthn_verify_error'       => t('js.webauthn_verify_error'),
    'notification_test_sent'      => t('js.notification_test_sent'),
    'notification_test_failed'    => t('js.notification_test_failed'),
    'notification_test_error'     => t('js.notification_test_error'),
    'browser_notifications_unavailable' => t('js.browser_notifications_unavailable'),
    'browser_notifications_enabled' => t('js.browser_notifications_enabled'),
    'browser_notifications_denied' => t('js.browser_notifications_denied'),
    'confirmation_required'       => t('js.confirmation_required'),
    'confirm_action'              => t('js.confirm_action'),
    'cancel'                      => t('js.cancel'),
    'confirm'                     => t('js.confirm'),
    'unsaved_changes_message'     => t('js.unsaved_changes_message'),
    'unsaved_changes_title'       => t('js.unsaved_changes_title'),
    'leave_page'                  => t('js.leave_page'),
    'stay'                        => t('js.stay'),
    'session_extended'            => t('js.session_extended'),
    'reauth_webauthn_help'        => t('js.reauth.webauthn_help'),
    'reauth_webauthn_confirm'     => t('js.reauth.webauthn_confirm'),
    'reauth_password_required'    => t('js.reauth.password_required'),
    'reauth_webauthn_unavailable' => t('js.reauth.webauthn_unavailable'),
    'reauth_webauthn_failed'      => t('js.reauth.webauthn_failed'),
    'reauth_failed'               => t('js.reauth.failed'),
    'explore_no_snapshot_available' => t('js.explore.no_snapshot_available'),
    'explore_no_tag'              => t('js.explore.no_tag'),
    'explore_click_to_remove'     => t('js.explore.click_to_remove'),
    'explore_delete_snapshot_confirm' => t('js.explore.delete_snapshot_confirm'),
    'explore_deleting'            => t('js.explore.deleting'),
    'explore_snapshot_deleted'    => t('js.explore.snapshot_deleted'),
    'explore_unknown_error'       => t('js.explore.unknown_error'),
    'explore_confirmation_required' => t('js.explore.confirmation_required'),
    'explore_in_progress'         => t('js.explore.in_progress'),
    'explore_no_ssh_key'          => t('js.explore.no_ssh_key'),
    'explore_restore_done'        => t('js.explore.restore_done'),
    'explore_error'               => t('js.explore.error'),
    'explore_select_snapshot'     => t('js.explore.select_snapshot'),
    'explore_comparison_loading'  => t('js.explore.comparison_loading'),
    'explore_no_difference'       => t('js.explore.no_difference'),
    'explore_min_2_chars'         => t('js.explore.min_2_chars'),
    'explore_search_loading'      => t('js.explore.search_loading'),
    'explore_index_preparing'     => t('js.explore.index_preparing'),
    'explore_no_file_found'       => t('js.explore.no_file_found'),
    'explore_search_results'      => t('js.explore.search_results'),
    'explore_checking'            => t('js.explore.checking'),
    'explore_check_in_progress'   => t('js.explore.check_in_progress'),
    'explore_check_integrity'     => t('js.explore.check_integrity'),
    'explore_integrity_ok'        => t('js.explore.integrity_ok'),
    'explore_issue_detected'      => t('js.explore.issue_detected'),
    'explore_init_repo_confirm'   => t('js.explore.init_repo_confirm'),
    'explore_initializing'        => t('js.explore.initializing'),
    'explore_init_in_progress'    => t('js.explore.init_in_progress'),
    'explore_init_repo'           => t('js.explore.init_repo'),
    'explore_repo_initialized'    => t('js.explore.repo_initialized'),
    'explore_files_selected'      => t('js.explore.files_selected'),
    'explore_no_file_selected'    => t('js.explore.no_file_selected'),
    'explore_restore_in_progress' => t('js.explore.restore_in_progress'),
    'explore_enter_file_path'     => t('js.explore.enter_file_path'),
    'explore_select_two_snapshots' => t('js.explore.select_two_snapshots'),
    'explore_identical_lines'     => t('js.explore.identical_lines'),
    'explore_saving'              => t('js.explore.saving'),
    'explore_add_error'           => t('js.explore.add_error'),
    'explore_remove_error'        => t('js.explore.remove_error'),
    'explore_tags_updated'        => t('js.explore.tags_updated'),
    'explore_simulation_in_progress' => t('js.explore.simulation_in_progress'),
    'explore_apply_in_progress'   => t('js.explore.apply_in_progress'),
    'explore_no_output'           => t('js.explore.no_output'),
    'explore_simulation_done'     => t('js.explore.simulation_done'),
    'explore_retention_applied'   => t('js.explore.retention_applied'),
    'explore_load_error'          => t('js.explore.load_error'),
    'explore_destination_exists_error' => t('js.explore.destination_exists_error'),
    'explore_destination_exists_summary' => t('js.explore.destination_exists_summary'),
    'explore_other_paths'         => t('js.explore.other_paths'),
    'explore_original_disabled'   => t('js.explore.original_disabled'),
    'explore_local_original_forbidden' => t('js.explore.local_original_forbidden'),
    'explore_local_original_blocked_warning' => t('js.explore.local_original_blocked_warning'),
    'explore_origin_host_unknown' => t('js.explore.origin_host_unknown'),
    'explore_remote_original_blocked_warning' => t('js.explore.remote_original_blocked_warning'),
    'explore_origin_host_required' => t('js.explore.origin_host_required'),
    'explore_select_origin_host'  => t('js.explore.select_origin_host'),
    'explore_retype_confirmation' => t('js.explore.retype_confirmation'),
    'explore_admin_confirmation_required' => t('js.explore.admin_confirmation_required'),
    'explore_remote_admin_mode'   => t('js.explore.remote_admin_mode'),
    'explore_local_exact_blocked' => t('js.explore.local_exact_blocked'),
    'explore_remote_recommended'  => t('js.explore.remote_recommended'),
    'explore_local_recommended'   => t('js.explore.local_recommended'),
    'explore_restore_btn'         => t('explore.restore_btn'),
    'explore_diff_added'          => t('explore.diff_added'),
    'explore_diff_removed'        => t('explore.diff_removed'),
    'explore_diff_changed'        => t('explore.diff_changed'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script<?= cspNonceAttr() ?> src="/assets/app.js?v=<?= urlencode(APP_VERSION) ?>" defer></script>
<?php if ($loadReauthUi): ?>
<script<?= cspNonceAttr() ?> src="/assets/app-reauth.js?v=<?= urlencode(APP_VERSION) ?>" defer></script>
<?php endif; ?>

<?php if ($loadReauthUi): ?>
<!-- Modal re-authentification -->
<div id="modal-reauth" class="modal-overlay">
    <div class="modal" style="max-width:400px">
        <div class="modal-title" style="color:var(--red)"><?= t('modal.reauth.title') ?></div>
        <p id="reauth-message" style="font-size:13px;color:var(--text2);margin:0 0 16px"></p>
        <div class="form-group">
            <label class="form-label"><?= t('modal.reauth.password_label') ?></label>
            <input type="password" id="reauth-password" class="form-control" placeholder="<?= h(t('auth.password')) ?>">
        </div>
        <div id="reauth-webauthn-help" style="display:none;font-size:12px;color:var(--text2);margin:-6px 0 10px"></div>
        <div id="reauth-totp-group" class="form-group" style="display:none">
            <label class="form-label"><?= t('modal.reauth.totp_label') ?></label>
            <input type="text" id="reauth-totp" class="form-control" placeholder="123456" maxlength="6" autocomplete="one-time-code">
        </div>
        <div id="reauth-error" style="color:var(--red);font-size:12px;min-height:16px;margin-bottom:8px"></div>
        <div class="flex gap-2" style="justify-content:flex-end">
            <button type="button" class="btn" onclick="closeReauth()"><?= t('modal.reauth.cancel') ?></button>
            <button type="button" class="btn" id="reauth-webauthn-button" onclick="submitReauth()" style="display:none"><?= t('modal.reauth.confirm') ?> (WebAuthn)</button>
            <button type="button" class="btn btn-danger" onclick="submitReauth()"><?= t('modal.reauth.confirm') ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="modal-confirm" class="modal-overlay">
    <div class="modal" style="max-width:420px">
        <div class="modal-title" id="modal-confirm-title"><?= t('modal.confirm.default_title') ?></div>
        <p id="modal-confirm-message" style="font-size:13px;color:var(--text2);margin:0 0 16px;white-space:pre-line"></p>
        <div class="flex gap-2" style="justify-content:flex-end">
            <button type="button" class="btn" id="modal-confirm-cancel" data-confirm-dismiss><?= t('modal.confirm.cancel') ?></button>
            <button type="button" class="btn btn-danger" id="modal-confirm-submit" data-confirm-accept><?= t('modal.confirm.confirm') ?></button>
        </div>
    </div>
</div>
</body>
</html>
