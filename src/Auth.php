<?php
// =============================================================================
// Auth.php — Authentication, sessions, rate limiting
// =============================================================================

class Auth {
    private static ?array $currentUserRecordCache = null;
    private const TOTP_RATE_LIMIT_MAX_ATTEMPTS = 5;
    private const TOTP_RATE_LIMIT_WINDOW_MINUTES = 10;

    private static function secondFactorTtl(): int {
        return AppConfig::secondFactorPendingTtlSeconds();
    }

    private static function sessionDbTouchInterval(): int {
        return AppConfig::sessionDbTouchIntervalSeconds();
    }

    private static function roleRequiresAdminTwoFactor(string $role): bool {
        return AppConfig::getRoleLevel($role, 0) >= AppConfig::getRoleLevel(ROLE_ADMIN, PHP_INT_MAX);
    }

    private static function clearCurrentUserCache(): void {
        self::$currentUserRecordCache = null;
    }

    private static function forbiddenResponse(string $message): void {
        http_response_code(403);
        $isApi = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
              || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
              || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
            exit;
        }

        echo "<!DOCTYPE html><html><body style='font-family:sans-serif;padding:40px'>
            <h2>403 - Acces refuse</h2>
            <p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>
            <a href='/'>Retour</a></body></html>";
        exit;
    }

    private static function isUserSuspended(array $user): bool {
        $until = trim((string) ($user['suspended_until'] ?? ''));
        return $until !== '' && strtotime($until) > time();
    }

    private static function isUserExpired(array $user): bool {
        $expiresAt = trim((string) ($user['account_expires_at'] ?? ''));
        return $expiresAt !== '' && strtotime($expiresAt) < time();
    }

    private static function userHasTotp(array $user): bool {
        return !empty($user['totp_enabled']) && !empty($user['has_totp_secret']);
    }

    private static function userTotpSecret(array $user): string {
        return SensitiveEntitySecretManager::getSecret(
            SensitiveEntitySecretManager::CONTEXT_USER_TOTP,
            $user,
            'runtime',
            ['scope' => 'auth', 'user_id' => (int) ($user['id'] ?? 0)]
        ) ?? '';
    }

    private static function clientIp(): string {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    private static function currentPendingSecondFactorMethod(?array $pending): string {
        if (!$pending) {
            return '';
        }

        $methods = is_array($pending['methods'] ?? null) ? $pending['methods'] : [];
        $activeMethod = trim((string) ($pending['active_method'] ?? ''));
        if ($activeMethod !== '' && in_array($activeMethod, $methods, true)) {
            return $activeMethod;
        }

        $preferredMethod = trim((string) ($pending['preferred_method'] ?? ''));
        if ($preferredMethod !== '' && in_array($preferredMethod, $methods, true)) {
            return $preferredMethod;
        }

        return isset($methods[0]) ? (string) $methods[0] : '';
    }

    private static function loginSecondFactorMethodFromPrimaryFactor(string $primaryFactor): string {
        return match (StepUpAuth::normalizeFactor($primaryFactor)) {
            StepUpAuth::FACTOR_CLASSIC => 'totp',
            StepUpAuth::FACTOR_WEBAUTHN => 'webauthn',
            default => '',
        };
    }

    // ── Login step 1 ─────────────────────────────────────────────────────────
    public static function login(string $username, string $password): array {
        $ip = self::clientIp();
        $db = Database::getInstance();

        // ── Rate limiting ──────────────────────────────────────────────────
        $maxAttempts    = (int) Database::getSetting('login_max_attempts', '5');
        $lockoutMinutes = (int) Database::getSetting('login_lockout_minutes', '15');

        $recentFails = $db->prepare("
            SELECT COUNT(*) FROM login_attempts
            WHERE ip = ? AND success = 0
            AND attempted_at >= datetime('now', '-' || ? || ' minutes')
        ");
        $recentFails->execute([$ip, $lockoutMinutes]);
        $failCount = (int) $recentFails->fetchColumn();

        if ($failCount >= $maxAttempts) {
            self::logAccess('login_blocked', $username, $ip, false);
            return ['success' => false, 'totp_required' => false, 'blocked' => true,
                    'message' => "Trop de tentatives. Réessayez dans $lockoutMinutes minutes."];
        }

        // ── Credential verification ─────────────────────────────────────────
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $db->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, 0)")
               ->execute([$ip, $username]);
            self::logAccess('login_failed', $username, $ip, false);
            return ['success' => false, 'totp_required' => false, 'blocked' => false];
        }

        // Account disabled
        if (isset($user['enabled']) && (int)$user['enabled'] === 0) {
            self::logAccess('login_disabled', $username, $ip, false);
            return ['success' => false, 'totp_required' => false, 'blocked' => false, 'disabled' => true,
                    'message' => 'Ce compte a été désactivé.'];
        }

        // ── TOTP ───────────────────────────────────────────────────────────
        $user = UserManager::hydrateUser($user);
        if (self::isUserSuspended($user)) {
            self::logAccess('login_suspended', $username, $ip, false);
            return ['success' => false, 'totp_required' => false, 'blocked' => false,
                    'message' => 'Ce compte est temporairement suspendu.'];
        }
        if (self::isUserExpired($user)) {
            self::logAccess('login_expired', $username, $ip, false);
            return ['success' => false, 'totp_required' => false, 'blocked' => false,
                    'message' => 'Ce compte temporaire a expire.'];
        }
        $secondFactorState = StepUpAuth::describeUser($user);
        $hasTotp     = !empty($secondFactorState['has_classic_2fa']);
        $hasWebAuthn = !empty($secondFactorState['has_webauthn']);
        $pendingForceActions = UserManager::normalizeForceActions($user['force_actions'] ?? []);
        $allowFirstLoginFor2faSetup = in_array(UserManager::FORCE_ACTION_SETUP_2FA, $pendingForceActions, true);

        if (AppConfig::forceAdminTwoFactor()
            && self::roleRequiresAdminTwoFactor((string) ($user['role'] ?? ''))
            && !$hasTotp
            && !$hasWebAuthn
            && !$allowFirstLoginFor2faSetup
        ) {
            self::logAccess('login_blocked_2fa', $username, $ip, false);
            return [
                'success' => false,
                'totp_required' => false,
                'blocked' => false,
                'message' => 'Ce compte administrateur doit configurer un second facteur avant de pouvoir se reconnecter.',
                'requires_2fa_setup' => true,
            ];
        }

        if ($hasTotp || $hasWebAuthn) {
            $methods = [];
            foreach ($secondFactorState['available_factors'] ?? [] as $factor) {
                $method = self::loginSecondFactorMethodFromPrimaryFactor((string) $factor);
                if ($method !== '') {
                    $methods[] = $method;
                }
            }
            $preferred = self::loginSecondFactorMethodFromPrimaryFactor((string) ($secondFactorState['primary_factor'] ?? ''));
            if ($preferred === '' || !in_array($preferred, $methods, true)) {
                $preferred = $methods[0] ?? '';
            }
            self::beginSecondFactor($user, $methods, $preferred);
            return [
                'success'                => true,
                'totp_required'          => $preferred === 'totp',
                'second_factor_required' => true,
                'preferred_method'       => $preferred,
                'available_methods'      => $methods,
            ];
        }

        // Record success and clear failed attempts
        $db->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, 1)")
           ->execute([$ip, $username]);
        $db->prepare("DELETE FROM login_attempts WHERE ip = ? AND success = 0")
           ->execute([$ip]);

        self::completeLogin($user);
        return ['success' => true, 'totp_required' => false, 'second_factor_required' => false];
    }

    // ── Login step 2 (TOTP) ──────────────────────────────────────────────────
    public static function verifyTotp(string $code): bool {
        return !empty(self::verifyTotpResult($code)['success']);
    }

    public static function verifyTotpResult(string $code): array {
        $pending = self::getPendingSecondFactor();
        if (!$pending || !in_array('totp', $pending['methods'], true)) {
            return ['success' => false, 'message' => t('auth.session_invalid_login')];
        }
        if (self::currentPendingSecondFactorMethod($pending) !== 'totp') {
            return ['success' => false, 'message' => t('auth.session_invalid_login')];
        }

        $userId = (int) $pending['user_id'];
        $ip     = self::clientIp();
        $sessionKey = session_id();

        // ── Rate limiting TOTP (independant of rate limiting credentials) ──────
        $totpRateStatus = self::getTotpRateLimitStatus($userId, $ip, $sessionKey);
        if ($totpRateStatus['blocked']) {
            self::log('totp_rate_blocked', "TOTP stuck user #$userId IP $ip", 'warning');
            return ['success' => false, 'blocked' => true, 'message' => t('auth.blocked'), 'rate_limit' => $totpRateStatus];
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user) {
            $user = UserManager::hydrateUser($user);
        }

        if (!$user || self::isUserSuspended($user) || self::isUserExpired($user) || !Totp::verify(self::userTotpSecret($user), $code)) {
            self::recordTotpAttempt($userId, $ip, $sessionKey, false);
            $updatedRateStatus = self::getTotpRateLimitStatus($userId, $ip, $sessionKey);
            if (!empty($updatedRateStatus['delay'])) {
                usleep((int) $updatedRateStatus['delay'] * 1_000_000);
            }
            self::log('totp_failed', "Verification TOTP echouee pour user #$userId");
            return [
                'success' => false,
                'blocked' => !empty($updatedRateStatus['blocked']),
                'message' => !empty($updatedRateStatus['blocked']) ? t('auth.blocked') : t('auth.authentication_failed'),
                'rate_limit' => $updatedRateStatus,
            ];
        }

        self::recordTotpAttempt($userId, $ip, $sessionKey, true);
        $db->prepare("DELETE FROM login_attempts WHERE ip = ? AND success = 0")->execute([$ip]);

        self::completeLogin($user);
        self::clearPendingSecondFactor();
        return ['success' => true];
    }

    // ── TOTP rate limiting (independant of rate limiting credentials) ─────────

    /** Key of stockage login_attempts for the tentatives TOTP of a user. */
    private static function totpAttemptKey(int $userId): string {
        return 'totp:' . $userId;
    }

    private static function totpAttemptSessionKey(string $sessionKey): string {
        return 'totp-session:' . ($sessionKey !== '' ? $sessionKey : 'anonymous');
    }

    /**
     * Returns the status of rate limiting TOTP for a user + IP given.
     * Fenetre : 10 min / 5 echecs max.
     * Deux axes of comptage : by user and by IP (the plus eleve prevaut).
     */
    private static function getTotpRateLimitStatus(int $userId, string $ip, string $sessionKey = ''): array {
        $db         = Database::getInstance();
        $userKey    = self::totpAttemptKey($userId);
        $sessionRef = self::totpAttemptSessionKey($sessionKey);

        // Failures by user in the sliding window
        $userStmt = $db->prepare("
            SELECT COUNT(*) FROM login_attempts
            WHERE username = ? AND success = 0
              AND attempted_at >= datetime('now', '-' || ? || ' minutes')
        ");
        $userStmt->execute([$userKey, self::TOTP_RATE_LIMIT_WINDOW_MINUTES]);
        $userCount = (int) $userStmt->fetchColumn();

        // Failures by IP (across all TOTP targets) in the sliding window
        $ipStmt = $db->prepare("
            SELECT COUNT(*) FROM login_attempts
            WHERE ip = ? AND username LIKE 'totp:%' AND success = 0
              AND attempted_at >= datetime('now', '-' || ? || ' minutes')
        ");
        $ipStmt->execute([$ip, self::TOTP_RATE_LIMIT_WINDOW_MINUTES]);
        $ipCount = (int) $ipStmt->fetchColumn();

        $sessionStmt = $db->prepare("
            SELECT COUNT(*) FROM login_attempts
            WHERE username = ? AND success = 0
              AND attempted_at >= datetime('now', '-' || ? || ' minutes')
        ");
        $sessionStmt->execute([$sessionRef, self::TOTP_RATE_LIMIT_WINDOW_MINUTES]);
        $sessionCount = (int) $sessionStmt->fetchColumn();

        $count   = max($userCount, $ipCount, $sessionCount);
        $blocked = $count >= self::TOTP_RATE_LIMIT_MAX_ATTEMPTS;
        $remaining = max(0, self::TOTP_RATE_LIMIT_MAX_ATTEMPTS - $count);
        $retryAfter = 0;
        if ($blocked) {
            $oldestStmt = $db->prepare("
                SELECT MIN(attempted_at) FROM login_attempts
                WHERE success = 0
                  AND attempted_at >= datetime('now', '-' || ? || ' minutes')
                  AND (
                        username = ?
                        OR username = ?
                        OR (ip = ? AND username LIKE 'totp:%')
                  )
            ");
            $oldestStmt->execute([
                self::TOTP_RATE_LIMIT_WINDOW_MINUTES,
                $userKey,
                $sessionRef,
                $ip,
            ]);
            $oldestAttempt = (string) $oldestStmt->fetchColumn();
            $oldestAttemptTs = $oldestAttempt !== '' ? strtotime($oldestAttempt) : false;
            if ($oldestAttemptTs !== false) {
                $retryAfter = max(1, (self::TOTP_RATE_LIMIT_WINDOW_MINUTES * 60) - (time() - $oldestAttemptTs));
            }
        }

        return [
            'blocked' => $blocked,
            'count'   => $count,
            'remaining' => $remaining,
            'retry_after' => $retryAfter,
            'delay'   => $blocked ? 0 : self::totpProgressiveDelay($count),
        ];
    }

    /**
     * Delai progressif (backoff exponentiel) base on the nombre of echecs :
     * 0 → 0s | 1 → 1s | 2 → 2s | 3 → 4s | 4+ → 8s
     */
    private static function totpProgressiveDelay(int $failCount): int {
        if ($failCount <= 0) return 0;
        return min(8, 1 << max(0, $failCount - 1));
    }

    /**
     * Records a TOTP attempt (success or failure) in login_attempts.
     * En cas of success, purge the echecs TOTP for this user.     */
    private static function recordTotpAttempt(int $userId, string $ip, string $sessionKey, bool $success): void {
        $db      = Database::getInstance();
        $userKey = self::totpAttemptKey($userId);
        $sessionRef = self::totpAttemptSessionKey($sessionKey);

        $db->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, ?)")
           ->execute([$ip, $userKey, $success ? 1 : 0]);
        $db->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, ?)")
           ->execute([$ip, $sessionRef, $success ? 1 : 0]);

        if ($success) {
            // Purge failed TOTP attempts for this user after success
            $db->prepare("DELETE FROM login_attempts WHERE username = ? AND success = 0")
               ->execute([$userKey]);
            $db->prepare("DELETE FROM login_attempts WHERE username = ? AND success = 0")
               ->execute([$sessionRef]);
        }
    }

    public static function getPendingSecondFactor(): ?array {
        if (empty($_SESSION['login_pending_user_id'])) return null;
        $methods = $_SESSION['login_pending_methods'] ?? [];
        if (!is_array($methods) || empty($methods)) return null;
        $time = (int) ($_SESSION['login_pending_time'] ?? 0);
        if ($time <= 0 || (time() - $time) > self::secondFactorTtl()) {
            self::clearPendingSecondFactor();
            return null;
        }
        $activeMethod = (string) ($_SESSION['login_pending_active_method'] ?? '');
        if ($activeMethod === '' || !in_array($activeMethod, $methods, true)) {
            $activeMethod = (string) ($_SESSION['login_pending_preferred_method'] ?? $methods[0]);
            $_SESSION['login_pending_active_method'] = $activeMethod;
        }
        $switchRequestedAt = (int) ($_SESSION['login_pending_method_switch_requested_at'] ?? 0);
        $switchRequested = $switchRequestedAt > 0 && ($time > 0) && (($switchRequestedAt - $time) >= 0) && ((time() - $switchRequestedAt) <= self::secondFactorTtl());
        if (!$switchRequested && isset($_SESSION['login_pending_method_switch_requested_at'])) {
            unset($_SESSION['login_pending_method_switch_requested_at']);
        }
        return [
            'user_id'          => (int) $_SESSION['login_pending_user_id'],
            'username'         => (string) ($_SESSION['login_pending_username'] ?? ''),
            'role'             => (string) ($_SESSION['login_pending_role'] ?? ''),
            'time'             => $time,
            'methods'          => array_values($methods),
            'preferred_method' => (string) ($_SESSION['login_pending_preferred_method'] ?? $methods[0]),
            'active_method'    => $activeMethod,
            'switch_requested' => $switchRequested,
        ];
    }

    public static function requestSecondFactorMethodSwitch(): bool {
        $pending = self::getPendingSecondFactor();
        if (!$pending || count($pending['methods']) <= 1) return false;
        $_SESSION['login_pending_method_switch_requested_at'] = time();
        unset($_SESSION['webauthn_auth_request']);
        return true;
    }

    public static function selectSecondFactorMethod(string $method): bool {
        $pending = self::getPendingSecondFactor();
        if (!$pending || empty($pending['switch_requested']) || !in_array($method, $pending['methods'], true)) return false;
        if ($method === self::currentPendingSecondFactorMethod($pending)) return false;
        $_SESSION['login_pending_active_method'] = $method;
        unset($_SESSION['login_pending_method_switch_requested_at'], $_SESSION['webauthn_auth_request']);
        return true;
    }

    public static function completePendingWebAuthnLogin(int $userId): bool {
        $pending = self::getPendingSecondFactor();
        if (!$pending || !in_array('webauthn', $pending['methods'], true)) return false;
        if ((int) $pending['user_id'] !== $userId) return false;
        if (self::currentPendingSecondFactorMethod($pending) !== 'webauthn') {
            return false;
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND (enabled IS NULL OR enabled = 1)");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) return false;
        $user = UserManager::hydrateUser($user);
        if (self::isUserSuspended($user) || self::isUserExpired($user)) {
            return false;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db->prepare("DELETE FROM login_attempts WHERE ip = ? AND success = 0")->execute([$ip]);

        self::completeLogin($user);
        self::clearPendingSecondFactor();
        return true;
    }

    // ── Finaliser the connection ────────────────────────────────────────────────
    private static function completeLogin(array $user): void {
        $db = Database::getInstance();

        // Generate a unique session token
        $sessionToken = bin2hex(random_bytes(32));
        $ip           = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua           = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expires      = date('Y-m-d H:i:s', time() + AppConfig::sessionAbsoluteLifetimeSeconds());

        $db->prepare("
            INSERT INTO active_sessions (user_id, session_token, ip, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$user['id'], $sessionToken, $ip, $ua, $expires]);

        // Regenerate the session ID to prevent session fixation
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);

        // Limiter the nombre of sessions simultanees — supprimer the plus anciennes
        $maxSessions = (int) Database::getSetting('max_sessions_per_user', '5');
        if ($maxSessions > 0) {
            $countStmt = $db->prepare("SELECT COUNT(*) FROM active_sessions WHERE user_id = ?");
            $countStmt->execute([$user['id']]);
            $currentCount = (int) $countStmt->fetchColumn();
            if ($currentCount >= $maxSessions) {
                $excess = $currentCount - $maxSessions + 1;
                $oldSessions = $db->prepare("
                    SELECT id FROM active_sessions WHERE user_id = ?
                    ORDER BY last_activity ASC LIMIT ?
                ");
                $oldSessions->execute([$user['id'], $excess]);
                foreach ($oldSessions->fetchAll() as $old) {
                    $db->prepare("DELETE FROM active_sessions WHERE id = ?")->execute([$old['id']]);
                }
            }
        }

        $_SESSION['user_id']        = $user['id'];
        $_SESSION['username']       = $user['username'];
        $_SESSION['role']           = $user['role'];
        $_SESSION['display_name']   = UserManager::displayName($user);
        $_SESSION['first_name']     = $user['first_name'] ?? '';
        $_SESSION['last_name']      = $user['last_name'] ?? '';
        $_SESSION['email']          = $user['email'] ?? '';
        $_SESSION['preferred_locale'] = $user['preferred_locale'] ?? 'fr';
        $_SESSION['preferred_timezone'] = $user['preferred_timezone'] ?? '';
        $_SESSION['preferred_start_page'] = $user['preferred_start_page'] ?? 'dashboard';
        $_SESSION['force_actions']  = UserManager::normalizeForceActions($user['force_actions_json'] ?? $user['force_actions'] ?? []);
        $_SESSION['logged_in']      = true;
        $_SESSION['login_time']     = time();
        $_SESSION['last_activity']  = time();
        $_SESSION['last_db_activity_sync'] = time();
        $_SESSION['session_token']  = $sessionToken;
        $_SESSION['totp_enabled']   = self::userHasTotp($user);
        $_SESSION['primary_second_factor'] = StepUpAuth::syncPrimaryFactor((int) $user['id']);
        // Session fingerprint: detects cookie theft (IP + User-Agent)
        $_SESSION['session_fp']     = self::fingerprint();

        $db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?")
           ->execute([$user['id']]);
        self::clearCurrentUserCache();

        self::logAccess('login', $user['username'], $ip, true);

        // Notification of connection if activee
        if (Database::getSetting('login_notifications_enabled') === '1') {
            $newIpOnly = Database::getSetting('login_notifications_new_ip_only', '1') === '1';
            if (!$newIpOnly) {
                Notifier::sendLoginNotification($user['username'], $ip, $ua);
            } else {
                // Check if this IP has already been used by this user
                $known = $db->prepare("
                    SELECT COUNT(*) FROM active_sessions WHERE user_id = ? AND ip = ?
                ");
                $known->execute([$user['id'], $ip]);
                if ((int)$known->fetchColumn() === 0) {
                    Notifier::sendLoginNotification($user['username'], $ip, $ua);
                }
            }
        }
    }

    // ── Empreinte session ─────────────────────────────────────────────────────
    private static function beginSecondFactor(array $user, array $methods, string $preferred): void {
        $_SESSION['login_pending_user_id'] = $user['id'];
        $_SESSION['login_pending_username'] = $user['username'];
        $_SESSION['login_pending_role'] = $user['role'];
        $_SESSION['login_pending_methods'] = array_values($methods);
        $_SESSION['login_pending_preferred_method'] = $preferred;
        $_SESSION['login_pending_active_method'] = $preferred;
        $_SESSION['login_pending_time'] = time();
        unset($_SESSION['login_pending_method_switch_requested_at'], $_SESSION['webauthn_auth_request']);

        $_SESSION['totp_pending_user_id'] = $user['id'];
        $_SESSION['totp_pending_username'] = $user['username'];
        $_SESSION['totp_pending_role'] = $user['role'];
        $_SESSION['totp_pending_time'] = time();
    }

    public static function clearPendingSecondFactor(): void {
        unset(
            $_SESSION['login_pending_user_id'],
            $_SESSION['login_pending_username'],
            $_SESSION['login_pending_role'],
            $_SESSION['login_pending_methods'],
            $_SESSION['login_pending_preferred_method'],
            $_SESSION['login_pending_active_method'],
            $_SESSION['login_pending_method_switch_requested_at'],
            $_SESSION['login_pending_time'],
            $_SESSION['totp_pending_user_id'],
            $_SESSION['totp_pending_username'],
            $_SESSION['totp_pending_role'],
            $_SESSION['totp_pending_time'],
            $_SESSION['webauthn_verified_user_id'],
            $_SESSION['webauthn_auth_request']
        );
    }

    private static function fingerprint(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ip . '|' . $ua);
    }

    // ── logout ───────────────────────────────────────────────────────────
    public static function logout(): void {
        if (!empty($_SESSION['session_token'])) {
            Database::getInstance()
                ->prepare("DELETE FROM active_sessions WHERE session_token = ?")
                ->execute([$_SESSION['session_token']]);
        }
        self::log('logout', 'Déconnexion');
        self::clearCurrentUserCache();
        $_SESSION = [];
        session_destroy();
    }

    // ── Check session ──────────────────────────────────────────────────────
    public static function check(): void {
        if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
            redirectTo('/login.php');
        }

        // Expiration absolue
        if (time() - ($_SESSION['login_time'] ?? 0) > AppConfig::sessionAbsoluteLifetimeSeconds()) {
            self::logout();
            redirectTo('/login.php', ['expired' => 1]);
        }

        // Expiration by inactivite
        $inactivitySecs = (int) Database::getSetting('session_inactivity_minutes', '30') * 60;
        if (time() - ($_SESSION['last_activity'] ?? 0) > $inactivitySecs) {
            self::logout();
            redirectTo('/login.php', ['expired' => 1, 'reason' => 'inactivity']);
        }

        // Fingerprint verification: detects cookie theft from another browser/IP
        if (AppConfig::sessionStrictFingerprint() && !empty($_SESSION['session_fp']) && $_SESSION['session_fp'] !== self::fingerprint()) {
            self::logout();
            redirectTo('/login.php', ['expired' => 1, 'reason' => 'fingerprint']);
        }

        // Check if the session was revoked by an admin + IP binding + account still active
        if (!empty($_SESSION['session_token'])) {
            $db      = Database::getInstance();
            $current = $_SERVER['REMOTE_ADDR'] ?? '';
            // Token must exist and come from the same IP as the original login.
            $sess = $db->prepare("
                SELECT s.id FROM active_sessions s
                JOIN users u ON u.id = s.user_id
                WHERE s.session_token = ? AND s.ip = ? AND (u.enabled IS NULL OR u.enabled = 1)
            ");
            $sess->execute([$_SESSION['session_token'], $current]);
            if (!$sess->fetch()) {
                // Check the vraie reason for the log
                $exists = $db->prepare("SELECT id FROM active_sessions WHERE session_token = ?");
                $exists->execute([$_SESSION['session_token']]);
                if ($exists->fetch()) {
                    self::log('session_hijack', "IP différente ($current) — session invalidée", 'critical');
                }
                session_destroy();
                redirectTo('/login.php', ['expired' => 1, 'reason' => 'ip_mismatch']);
            }
            // Update last_activity in database
            $lastDbTouch = (int) ($_SESSION['last_db_activity_sync'] ?? 0);
            if ((time() - $lastDbTouch) >= self::sessionDbTouchInterval()) {
                $db->prepare("
                    UPDATE active_sessions
                    SET last_activity = datetime('now')
                    WHERE session_token = ?
                      AND (last_activity IS NULL OR last_activity <= datetime('now', '-' || ? || ' seconds'))
                ")->execute([
                    $_SESSION['session_token'],
                    AppConfig::sessionDbTouchCoalesceSeconds(),
                ]);
                $_SESSION['last_db_activity_sync'] = time();
            }
        }

        $user = self::currentUserRecord();
        if (!$user || (isset($user['enabled']) && (int) $user['enabled'] === 0) || self::isUserSuspended($user) || self::isUserExpired($user)) {
            self::logout();
            redirectTo('/login.php', ['expired' => 1, 'reason' => 'revoked']);
        }

        self::refreshSessionUser();
        $scriptName = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
        if (self::hasPendingForcedActions() && !in_array($scriptName, ['profile.php', 'logout.php'], true) && !str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), '/api/')) {
            redirectTo('/profile.php', ['forced' => 1]);
        }

        $_SESSION['last_activity'] = time();
    }

    // ── Revoke all sessions of a user (admin) ────────────────
    public static function revokeUserSessions(int $userId): int {
        $db   = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM active_sessions WHERE user_id = ? AND session_token != ?");
        $stmt->execute([$userId, $_SESSION['session_token'] ?? '']);
        $count = $stmt->rowCount();
        self::log('sessions_revoked', "Sessions révoquées pour user #$userId ($count sessions)");
        return $count;
    }

    // ── Revoke ALL sessions of a user (including their own) ──────
    public static function revokeAllUserSessions(int $userId): int {
        $db   = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM active_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    public static function revokeSessionById(int $sessionId, int $ownerUserId = 0): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            DELETE FROM active_sessions
            WHERE id = ?
              AND (? <= 0 OR user_id = ?)
        ");
        $stmt->execute([$sessionId, $ownerUserId, $ownerUserId]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            self::log('session_revoked', "Session #$sessionId revoquee", 'warning');
        }
        return $deleted;
    }

    // ── Lister the sessions actives ───────────────────────────────────────────
    public static function getActiveSessions(?int $userId = null): array {
        $db = Database::getInstance();
        if ($userId) {
            $stmt = $db->prepare("
                SELECT s.*, u.username FROM active_sessions s
                JOIN users u ON u.id = s.user_id
                WHERE s.user_id = ? AND s.expires_at > datetime('now')
                ORDER BY s.last_activity DESC
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $db->query("
                SELECT s.*, u.username FROM active_sessions s
                JOIN users u ON u.id = s.user_id
                WHERE s.expires_at > datetime('now')
                ORDER BY s.last_activity DESC
            ");
        }
        return $stmt->fetchAll();
    }

    // ── Nettoyer the sessions expirees ────────────────────────────────────────
    public static function cleanExpiredSessions(): void {
        Database::getInstance()->exec("DELETE FROM active_sessions WHERE expires_at < datetime('now')");
    }

    // ── Infos rate limiting ───────────────────────────────────────────────────
    public static function getRateLimitInfo(string $ip): array {
        $maxAttempts    = (int) Database::getSetting('login_max_attempts', '5');
        $lockoutMinutes = (int) Database::getSetting('login_lockout_minutes', '15');
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM login_attempts
            WHERE ip = ? AND success = 0
            AND attempted_at >= datetime('now', '-' || ? || ' minutes')
        ");
        $stmt->execute([$ip, $lockoutMinutes]);
        $failCount = (int) $stmt->fetchColumn();

        return [
            'attempts'    => $failCount,
            'max'         => $maxAttempts,
            'remaining'   => max(0, $maxAttempts - $failCount),
            'blocked'     => $failCount >= $maxAttempts,
            'lockout_min' => $lockoutMinutes,
        ];
    }

    public static function getTotpRateLimitInfo(): array {
        $pending = self::getPendingSecondFactor();
        if (!$pending || !in_array('totp', $pending['methods'], true)) {
            return [
                'blocked' => false,
                'count' => 0,
                'remaining' => self::TOTP_RATE_LIMIT_MAX_ATTEMPTS,
                'retry_after' => 0,
                'delay' => 0,
            ];
        }

        return self::getTotpRateLimitStatus(
            (int) ($pending['user_id'] ?? 0),
            self::clientIp(),
            session_id()
        );
    }

    // ── Admin : destuckr a IP ──────────────────────────────────────────────
    public static function unblockIp(string $ip): void {
        Database::getInstance()
            ->prepare("DELETE FROM login_attempts WHERE ip = ? AND success = 0")
            ->execute([$ip]);
        self::log('ip_unblocked', "IP débloquée: $ip");
    }

    // ── Hierarchy of roles ──────────────────────────────────────────────────
    /** checks if the role current is at least $minRole */
    public static function hasRole(string $minRole): bool {
        $current = $_SESSION['role'] ?? ROLE_VIEWER;
        $requiredLevel = AppConfig::getRoleLevel($minRole, PHP_INT_MAX);
        $currentLevel  = AppConfig::getRoleLevel((string) $current, 0);
        return $currentLevel >= $requiredLevel;
    }

    /** Stops the request with 403 if the role is insufficient */
    public static function requireRole(string $minRole): void {
        self::check();
        if (!self::hasRole($minRole)) {
            http_response_code(403);
            $isApi = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                  || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                  || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
            if ($isApi) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Accès refusé — privilèges insufficients']);
                exit;
            }
            $role = htmlspecialchars($_SESSION['role'] ?? '?');
            echo "<!DOCTYPE html><html><body style='font-family:sans-serif;padding:40px'>
                <h2>403 — Accès refusé</h2>
                <p>Votre rôle <strong>$role</strong> ne permet pas cette action.</p>
                <a href='/'>← Retour</a></body></html>";
            exit;
        }
    }

    /** Alias strict for the pages admin */
    public static function requireAdmin(): void {
        self::requireRole(ROLE_ADMIN);
    }

    // ── Re-authentication ───────────────────────────────────────────────────
    /** checks if a re-auth recente a eu lieu (for actions critiques) */
    public static function checkReauth(int $maxAgeSecs = 0): bool {
        return StepUpAuth::checkCurrentUserReauth('generic.sensitive', $maxAgeSecs);
    }

    /** Records a successful re-auth */
    public static function setReauth(): void {
        $user = self::currentUserRecord();
        if (!$user) {
            return;
        }
        $factor = StepUpAuth::describeUser($user)['primary_factor'];
        $_SESSION['step_up_reauth'] = [
            'user_id' => (int) $user['id'],
            'factor' => $factor,
            'operation' => 'generic.sensitive',
            'time' => time(),
        ];
    }

    // ── Helpers of capacites ─────────────────────────────────────────────────
    public static function clearReauth(): void {
        StepUpAuth::clearReauth();
    }

    public static function consumeReauth(int $maxAgeSecs = 0): bool {
        return StepUpAuth::consumeCurrentUserReauth('generic.sensitive', $maxAgeSecs);
    }

    public static function isAdmin(): bool       { return self::hasRole(ROLE_ADMIN); }
    public static function isLoggedIn(): bool    { return !empty($_SESSION['logged_in']); }

    public static function currentUserRecord(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }
        if (self::$currentUserRecordCache !== null) {
            return self::$currentUserRecordCache;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        self::$currentUserRecordCache = UserManager::getById($userId);
        return self::$currentUserRecordCache;
    }

    public static function refreshSessionUser(): void {
        self::clearCurrentUserCache();
        $user = self::currentUserRecord();
        if (!$user) {
            return;
        }

        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['first_name'] = $user['first_name'] ?? '';
        $_SESSION['last_name'] = $user['last_name'] ?? '';
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['preferred_locale'] = $user['preferred_locale'] ?? 'fr';
        $_SESSION['preferred_timezone'] = $user['preferred_timezone'] ?? '';
        $_SESSION['preferred_start_page'] = $user['preferred_start_page'] ?? 'dashboard';
        $_SESSION['force_actions'] = $user['force_actions'] ?? [];
        $_SESSION['totp_enabled'] = self::userHasTotp($user);
        $_SESSION['primary_second_factor'] = StepUpAuth::syncPrimaryFactor((int) $user['id']);
    }

    public static function preferredTimezone(): string {
        $timezone = trim((string) ($_SESSION['preferred_timezone'] ?? ''));
        return ($timezone !== '' && AppConfig::isValidTimezone($timezone)) ? $timezone : AppConfig::timezone();
    }

    public static function preferredLocale(): string {
        $locale = trim((string) ($_SESSION['preferred_locale'] ?? ''));
        $supported = array_keys(AppConfig::localeOptions());
        return ($locale !== '' && in_array($locale, $supported, true)) ? $locale : AppConfig::defaultLocale();
    }

    public static function currentUser(): array {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['role'] ?? '',
            'display_name' => $_SESSION['display_name'] ?? ($_SESSION['username'] ?? ''),
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'email' => $_SESSION['email'] ?? '',
        ];
    }

    public static function resolvedPermissionsForUser(array $user): array {
        $grants = [];
        foreach (AppConfig::rolePermissions((string) ($user['role'] ?? '')) as $permission) {
            $grants[$permission] = true;
        }

        foreach (UserManager::normalizePermissionMap($user['permissions'] ?? ($user['permissions_json'] ?? [])) as $permission => $allowed) {
            $grants[$permission] = (bool) $allowed;
        }

        ksort($grants);
        return $grants;
    }

    public static function hasPermission(string $permission): bool {
        $user = self::currentUserRecord();
        if (!$user) {
            return false;
        }

        $permissions = self::resolvedPermissionsForUser($user);
        return !empty($permissions[$permission]);
    }

    public static function requirePermission(string $permission): void {
        self::check();
        if (!self::hasPermission($permission)) {
            self::forbiddenResponse('Acces refuse - permission manquante : ' . $permission);
        }
    }

    public static function canRun(): bool {
        return self::hasPermission('backup_jobs.manage') || self::hasPermission('copy_jobs.manage');
    }

    public static function canRestore(): bool {
        return self::hasPermission('restore.run');
    }

    public static function canAccessRepoId(int $repoId): bool {
        if ($repoId <= 0) {
            return false;
        }
        if (self::hasPermission('repos.manage')) {
            return true;
        }
        if (!self::hasPermission('repos.view')) {
            return false;
        }

        $user = self::currentUserRecord();
        if (!$user) {
            return false;
        }

        $scopeMode = (string) ($user['repo_scope_mode'] ?? 'all');
        if ($scopeMode !== 'selected') {
            return true;
        }

        return in_array($repoId, UserManager::normalizeIdList($user['repo_scope'] ?? ($user['repo_scope_json'] ?? [])), true);
    }

    public static function canAccessHostId(int $hostId): bool {
        if ($hostId <= 0) {
            return false;
        }
        if (self::hasPermission('hosts.manage')) {
            return true;
        }

        $user = self::currentUserRecord();
        if (!$user) {
            return false;
        }

        $scopeMode = (string) ($user['host_scope_mode'] ?? 'all');
        if ($scopeMode !== 'selected') {
            return true;
        }

        return in_array($hostId, UserManager::normalizeIdList($user['host_scope'] ?? ($user['host_scope_json'] ?? [])), true);
    }

    public static function requireRepoAccess(int $repoId): void {
        self::check();
        if (!self::canAccessRepoId($repoId)) {
            self::forbiddenResponse('Acces refuse a ce depot.');
        }
    }

    public static function requireHostAccess(int $hostId): void {
        self::check();
        if (!self::canAccessHostId($hostId)) {
            self::forbiddenResponse('Acces refuse a cet hote.');
        }
    }

    public static function filterAccessibleRepos(array $repos): array {
        return array_values(array_filter($repos, static fn(array $repo): bool => self::canAccessRepoId((int) ($repo['id'] ?? 0))));
    }

    public static function filterAccessibleHosts(array $hosts): array {
        return array_values(array_filter($hosts, static fn(array $host): bool => self::canAccessHostId((int) ($host['id'] ?? 0))));
    }

    public static function pendingForcedActions(): array {
        return UserManager::normalizeForceActions($_SESSION['force_actions'] ?? []);
    }

    public static function hasPendingForcedActions(): bool {
        return !empty(self::pendingForcedActions());
    }

    public static function syncForcedActions(): void {
        $user = self::currentUserRecord();
        $_SESSION['force_actions'] = $user['force_actions'] ?? [];
    }

    public static function postLoginRedirect(): string {
        if (self::hasPendingForcedActions()) {
            return routePath('/profile.php', ['forced' => 1]);
        }

        $startPage = (string) ($_SESSION['preferred_start_page'] ?? 'dashboard');
        $options = AppConfig::startPageOptions();
        $target = $options[$startPage]['path'] ?? routePath('/index.php');

        if ($startPage === 'repos' && !self::hasPermission('repos.view')) {
            return routePath('/index.php');
        }
        if ($startPage === 'restores' && !self::hasPermission('restore.view')) {
            return routePath('/index.php');
        }
        if ($startPage === 'backup_jobs' && !self::hasPermission('backup_jobs.manage')) {
            return routePath('/index.php');
        }
        if ($startPage === 'copy_jobs' && !self::hasPermission('copy_jobs.manage')) {
            return routePath('/index.php');
        }
        if ($startPage === 'stats' && !self::hasPermission('stats.view')) {
            return routePath('/index.php');
        }
        if ($startPage === 'logs' && !self::hasPermission('logs.view')) {
            return routePath('/index.php');
        }

        return $target;
    }

    public static function sessionInfo(): array {
        $inactivityMinutes = (int) Database::getSetting('session_inactivity_minutes', '30');
        $warningMinutes    = (int) Database::getSetting('session_warning_minutes', '2');
        $lastActivity      = $_SESSION['last_activity'] ?? time();
        $secondsLeft       = ($inactivityMinutes * 60) - (time() - $lastActivity);
        return [
            'inactivity_seconds' => $inactivityMinutes * 60,
            'warning_seconds'    => $warningMinutes * 60,
            'seconds_left'       => max(0, $secondsLeft),
        ];
    }

    // ── TOTP ──────────────────────────────────────────────────────────────────
    public static function totpSetupStart(): array {
        $user   = self::currentUser();
        $secret = Totp::generateSecret();
        $_SESSION['totp_setup_secret'] = $secret;
        return Totp::getSetupPayload($secret, (string) $user['username']);
    }

    public static function totpSetupConfirm(string $code): bool {
        if (empty($_SESSION['totp_setup_secret'])) return false;
        $secret = $_SESSION['totp_setup_secret'];
        if (!Totp::verify($secret, $code)) return false;
        $user = self::currentUser();
        $db = Database::getInstance();
        $startedTransaction = !$db->inTransaction();
        if ($startedTransaction) {
            $db->beginTransaction();
        }
        try {
            $db->prepare("UPDATE users SET totp_enabled = 1 WHERE id = ?")->execute([$user['id']]);
            SensitiveEntitySecretManager::storeSecret(
                SensitiveEntitySecretManager::CONTEXT_USER_TOTP,
                (int) $user['id'],
                $secret,
                ['entity' => 'user', 'kind' => 'totp_setup']
            );
            if ($startedTransaction) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        unset($_SESSION['totp_setup_secret']);
        StepUpAuth::syncPrimaryFactor((int) $user['id']);
        UserManager::removeForceAction((int) $user['id'], UserManager::FORCE_ACTION_SETUP_2FA);
        self::refreshSessionUser();
        self::log('totp_enabled', '2FA activé');
        return true;
    }

    public static function setUserEnabled(int $userId, bool $enabled): void {
        Database::getInstance()
            ->prepare("UPDATE users SET enabled = ? WHERE id = ?")
            ->execute([$enabled ? 1 : 0, $userId]);
        if (!$enabled) {
            // Revoke all sessions of disabled account
            self::revokeAllUserSessions($userId);
        }
        self::clearCurrentUserCache();
        self::log('user_' . ($enabled ? 'enabled' : 'disabled'), "Compte user #$userId " . ($enabled ? 'activé' : 'désactivé'), 'warning');
    }

    public static function totpDisable(int $userId): void {
        Database::getInstance()->prepare("UPDATE users SET totp_enabled = 0 WHERE id = ?")->execute([$userId]);
        SensitiveEntitySecretManager::clearSecret(SensitiveEntitySecretManager::CONTEXT_USER_TOTP, $userId);
        StepUpAuth::syncPrimaryFactor($userId);
        self::clearCurrentUserCache();
        self::log('totp_disabled', "2FA désactivé pour user #$userId");
    }

    public static function keepAlive(): void {
        if (!empty($_SESSION['logged_in'])) {
            $_SESSION['last_activity'] = time();
        }
    }

    // ── Log of activite ────────────────────────────────────────────────────────
    // severity : 'info' | 'warning' | 'critical'
    public static function log(string $action, string $details = '', string $severity = 'info'): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            Database::getInstance()->prepare("
                INSERT INTO activity_logs (user_id, username, action, details, ip, user_agent, severity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? 'anonymous',
                $action, $details, $ip, $ua, $severity,
            ]);

            // Alert all enabled channels on critical event
            if ($severity === 'critical') {
                $user = $_SESSION['username'] ?? 'inconnu';
                Notifier::sendSecurityAlert($action, "**Utilisateur** : $user\n**Détails** : $details\n**IP** : $ip", $ip);
            }
        } catch (Exception $e) {}
    }

    // ── Log of access (with IP and UA) ───────────────────────────────────────────
    private static function logAccess(string $action, string $username, string $ip, bool $success): void {
        try {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            Database::getInstance()->prepare("
                INSERT INTO activity_logs (username, action, details, ip, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $username, $action,
                $success ? 'Connexion réussie' : 'Tentative échouée',
                $ip, $ua,
            ]);
        } catch (Exception $e) {}
    }
}
