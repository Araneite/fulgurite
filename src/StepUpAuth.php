<?php

class StepUpAuth {
    public const FACTOR_NONE = 'none';
    public const FACTOR_CLASSIC = 'classic_2fa';
    public const FACTOR_WEBAUTHN = 'webauthn';

    private const SESSION_REAUTH = 'step_up_reauth';
    private const SESSION_PENDING = 'step_up_pending';

    private const POLICY_MAP = [
        'generic.sensitive' => [
            'sensitive' => true,
            'require_webauthn_uv' => true,
        ],
        'profile.sensitive' => [
            'sensitive' => true,
            'require_webauthn_uv' => true,
        ],
        'users.sensitive' => [
            'sensitive' => true,
            'require_webauthn_uv' => true,
        ],
        'settings.sensitive' => [
            'sensitive' => true,
            'require_webauthn_uv' => true,
        ],
        'webauthn.manage' => [
            'sensitive' => true,
            'require_webauthn_uv' => true,
        ],
        'resetup.step1' => [
            'sensitive' => true,
            'require_webauthn_uv' => true,
        ],
    ];

    public static function normalizeFactor(mixed $factor): string {
        $value = trim(strtolower((string) $factor));
        return match ($value) {
            self::FACTOR_CLASSIC => self::FACTOR_CLASSIC,
            self::FACTOR_WEBAUTHN => self::FACTOR_WEBAUTHN,
            self::FACTOR_NONE => self::FACTOR_NONE,
            default => '',
        };
    }

    public static function policyForOperation(string $operation): array {
        $normalized = trim(strtolower($operation));
        if ($normalized === '') {
            $normalized = 'generic.sensitive';
        }

        $policy = self::POLICY_MAP[$normalized] ?? self::POLICY_MAP['generic.sensitive'];
        $policy['operation'] = $normalized;
        $policy['max_age'] = AppConfig::reauthMaxAgeSeconds();
        return $policy;
    }

    public static function describeUser(array $user): array {
        $userId = (int) ($user['id'] ?? 0);
        $hasClassic = !empty($user['totp_enabled']) && !empty($user['has_totp_secret']);
        $hasWebAuthn = $userId > 0 && WebAuthn::userHasCredentials($userId);
        $storedPrimary = self::normalizeFactor($user['primary_second_factor'] ?? '');
        $availableFactors = self::buildAvailableFactors($hasClassic, $hasWebAuthn);
        $primaryFactor = self::resolvePrimaryFactor($storedPrimary, $availableFactors);

        return [
            'has_classic_2fa' => $hasClassic,
            'has_webauthn' => $hasWebAuthn,
            'available_factors' => $availableFactors,
            'stored_primary_factor' => $storedPrimary,
            'primary_factor' => $primaryFactor,
        ];
    }

    public static function syncPrimaryFactor(int $userId): string {
        $user = UserManager::getById($userId);
        if (!$user) {
            return self::FACTOR_NONE;
        }

        $state = self::describeUser($user);
        $storedPrimary = $state['stored_primary_factor'];
        $resolvedPrimary = $state['primary_factor'];
        if ($storedPrimary !== $resolvedPrimary) {
            UserManager::setPrimarySecondFactor($userId, $resolvedPrimary);
        }

        return $resolvedPrimary;
    }

    public static function currentUserConfig(): array {
        $user = Auth::currentUserRecord();
        if (!$user) {
            return [
                'primary_factor' => self::FACTOR_NONE,
                'available_factors' => [],
                'totp_enabled' => false,
                'webauthn_enabled' => false,
            ];
        }

        $state = self::describeUser($user);
        return [
            'primary_factor' => $state['primary_factor'],
            'available_factors' => $state['available_factors'],
            'totp_enabled' => $state['has_classic_2fa'],
            'webauthn_enabled' => $state['has_webauthn'],
        ];
    }

    public static function choosePrimaryFactor(int $userId, string $requestedFactor): string {
        $user = UserManager::getById($userId);
        if (!$user) {
            throw new RuntimeException('Utilisateur introuvable.');
        }

        $normalized = self::validateRequestedPrimaryFactor($user, $requestedFactor);
        UserManager::setPrimarySecondFactor($userId, $normalized);
        return $normalized;
    }

    public static function validateRequestedPrimaryFactor(array $user, string $requestedFactor): string {
        $normalized = self::normalizeFactor($requestedFactor);
        $state = self::describeUser($user);
        if (empty($state['available_factors'])) {
            if ($normalized !== '' && $normalized !== self::FACTOR_NONE) {
                throw new RuntimeException('Le facteur principal choisi n est pas disponible sur ce compte.');
            }
            return self::FACTOR_NONE;
        }
        if ($normalized === self::FACTOR_NONE) {
            throw new RuntimeException('Ce compte dispose deja de facteurs forts. Choisissez-en un comme facteur principal.');
        }
        if (!in_array($normalized, $state['available_factors'], true)) {
            throw new RuntimeException('Le facteur principal choisi n est pas disponible sur ce compte.');
        }

        return $normalized;
    }

    public static function beginInteractiveReauth(array $user, string $password, string $totpCode, string $operation = 'generic.sensitive'): array {
        $policy = self::policyForOperation($operation);
        if (trim($password) === '') {
            return ['success' => false, 'error' => 'Mot de passe requis.'];
        }

        if (!password_verify($password, (string) ($user['password'] ?? ''))) {
            Auth::log('reauth_failed', 'Re-authentification echouee', 'warning');
            self::clearPending();
            return ['success' => false, 'error' => 'Mot de passe incorrect.'];
        }

        $state = self::describeUser($user);
        $requiredFactor = $state['primary_factor'];

        if ($requiredFactor === self::FACTOR_CLASSIC) {
            $totpSecret = SensitiveEntitySecretManager::getSecret(
                SensitiveEntitySecretManager::CONTEXT_USER_TOTP,
                $user,
                'runtime',
                ['scope' => 'step_up', 'user_id' => (int) ($user['id'] ?? 0)]
            ) ?? '';
            if ($totpCode === '' || !Totp::verify($totpSecret, $totpCode)) {
                Auth::log('reauth_failed', 'Re-authentification echouee — code 2FA invalide', 'warning');
                self::clearPending();
                return ['success' => false, 'error' => 'Code 2FA invalide.'];
            }

            self::recordSuccess((int) $user['id'], self::FACTOR_CLASSIC, $policy['operation']);
            return ['success' => true, 'completed' => true, 'factor' => self::FACTOR_CLASSIC];
        }

        if ($requiredFactor === self::FACTOR_WEBAUTHN) {
            $_SESSION[self::SESSION_PENDING] = [
                'user_id' => (int) $user['id'],
                'operation' => $policy['operation'],
                'required_factor' => self::FACTOR_WEBAUTHN,
                'require_webauthn_uv' => !empty($policy['require_webauthn_uv']),
                'time' => time(),
            ];

            return ['success' => true, 'completed' => false, 'next_factor' => self::FACTOR_WEBAUTHN];
        }

        self::recordSuccess((int) $user['id'], self::FACTOR_NONE, $policy['operation']);
        return ['success' => true, 'completed' => true, 'factor' => self::FACTOR_NONE];
    }

    public static function pendingWebAuthnRequest(int $userId, string $operation): ?array {
        $pending = $_SESSION[self::SESSION_PENDING] ?? null;
        if (!is_array($pending)) {
            return null;
        }

        if ((int) ($pending['user_id'] ?? 0) !== $userId) {
            self::clearPending();
            return null;
        }

        if ((string) ($pending['required_factor'] ?? '') !== self::FACTOR_WEBAUTHN) {
            self::clearPending();
            return null;
        }

        $requestedOperation = self::policyForOperation($operation)['operation'];
        $pendingOperation = self::storedOperation($pending['operation'] ?? null);
        if ($pendingOperation === '' || $pendingOperation !== $requestedOperation) {
            self::clearPending();
            return null;
        }

        if ((time() - (int) ($pending['time'] ?? 0)) > AppConfig::secondFactorPendingTtlSeconds()) {
            self::clearPending();
            return null;
        }

        return $pending;
    }

    public static function completePendingWebAuthn(int $userId, string $operation): bool {
        $pending = self::pendingWebAuthnRequest($userId, $operation);
        if ($pending === null) {
            return false;
        }

        self::recordSuccess($userId, self::FACTOR_WEBAUTHN, (string) ($pending['operation'] ?? ''));
        self::clearPending();
        return true;
    }

    public static function checkCurrentUserReauth(string $operation = 'generic.sensitive', int $maxAgeSecs = 0): bool {
        $user = Auth::currentUserRecord();
        if (!$user) {
            return false;
        }

        return self::checkReauthForUser($user, $operation, $maxAgeSecs);
    }

    public static function checkReauthForUser(array $user, string $operation = 'generic.sensitive', int $maxAgeSecs = 0): bool {
        $proof = $_SESSION[self::SESSION_REAUTH] ?? null;
        if (!is_array($proof)) {
            return false;
        }

        $policy = self::policyForOperation($operation);
        if (empty($policy['sensitive'])) {
            return true;
        }

        if ($maxAgeSecs <= 0) {
            $maxAgeSecs = (int) $policy['max_age'];
        }

        if ((int) ($proof['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            return false;
        }

        $requestedOperation = (string) $policy['operation'];
        $proofOperation = self::storedOperation($proof['operation'] ?? null);
        if ($proofOperation === '' || $proofOperation !== $requestedOperation) {
            return false;
        }

        if ((time() - (int) ($proof['time'] ?? 0)) > $maxAgeSecs) {
            return false;
        }

        $requiredFactor = self::describeUser($user)['primary_factor'];
        $proofFactor = self::normalizeFactor($proof['factor'] ?? '');
        return self::proofSatisfies($proofFactor, $requiredFactor);
    }

    public static function consumeCurrentUserReauth(string $operation = 'generic.sensitive', int $maxAgeSecs = 0): bool {
        $isValid = self::checkCurrentUserReauth($operation, $maxAgeSecs);
        self::clearReauth();
        return $isValid;
    }

    public static function clearReauth(): void {
        unset($_SESSION[self::SESSION_REAUTH]);
    }

    public static function clearPending(): void {
        unset($_SESSION[self::SESSION_PENDING]);
    }

    private static function recordSuccess(int $userId, string $factor, string $operation): void {
        $normalizedOperation = self::policyForOperation($operation)['operation'];
        $_SESSION[self::SESSION_REAUTH] = [
            'user_id' => $userId,
            'factor' => $factor,
            'operation' => $normalizedOperation,
            'time' => time(),
        ];

        if ($normalizedOperation === 'resetup.step1') {
            $_SESSION['resetup_authed'] = true;
        }

        Auth::log('reauth_success', 'Re-authentification reussie');
    }

    private static function resolvePrimaryFactor(string $storedFactor, array $availableFactors): string {
        if ($storedFactor !== '' && in_array($storedFactor, $availableFactors, true)) {
            return $storedFactor;
        }
        if ($storedFactor === self::FACTOR_NONE && empty($availableFactors)) {
            return self::FACTOR_NONE;
        }

        // Compatibility fallback for accounts created before explicit selection.
        if (in_array(self::FACTOR_WEBAUTHN, $availableFactors, true)) {
            return self::FACTOR_WEBAUTHN;
        }
        if (in_array(self::FACTOR_CLASSIC, $availableFactors, true)) {
            return self::FACTOR_CLASSIC;
        }

        return self::FACTOR_NONE;
    }

    private static function proofSatisfies(string $proofFactor, string $requiredFactor): bool {
        if ($requiredFactor === self::FACTOR_NONE) {
            return in_array($proofFactor, [self::FACTOR_NONE, self::FACTOR_CLASSIC, self::FACTOR_WEBAUTHN], true);
        }

        return $proofFactor === $requiredFactor;
    }

    private static function storedOperation(mixed $operation): string {
        $normalized = trim(strtolower((string) $operation));
        if ($normalized === '') {
            return '';
        }

        return array_key_exists($normalized, self::POLICY_MAP) ? $normalized : '';
    }

    private static function buildAvailableFactors(bool $hasClassic, bool $hasWebAuthn): array {
        $availableFactors = [];
        if ($hasClassic) {
            $availableFactors[] = self::FACTOR_CLASSIC;
        }
        if ($hasWebAuthn) {
            $availableFactors[] = self::FACTOR_WEBAUTHN;
        }
        return $availableFactors;
    }
}
