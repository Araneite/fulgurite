<?php

class JobRetryPolicy {
    private const RETRYABLE_ERRORS = [
        'lock' => 'Verrou depot',
        'network' => 'Reseau / backend',
        'timeout' => 'Timeout',
        'busy' => 'Ressource occupee',
        'unknown' => 'Erreur inconnue',
    ];

    public static function defaultGlobalPolicy(): array {
        return self::normalizePolicy([
            'inherit' => false,
            'enabled' => true,
            'max_retries' => 1,
            'delay_seconds' => 20,
            'retry_on' => ['lock', 'network', 'timeout'],
        ], false);
    }

    public static function defaultEntityPolicy(): array {
        return self::normalizePolicy([
            'inherit' => true,
            'enabled' => true,
            'max_retries' => 1,
            'delay_seconds' => 20,
            'retry_on' => ['lock', 'network', 'timeout'],
        ], true);
    }

    public static function getRetryableOptions(): array {
        return self::RETRYABLE_ERRORS;
    }

    public static function encodePolicy(array $policy, bool $allowInherit = true): string {
        return json_encode(
            self::normalizePolicy($policy, $allowInherit),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) ?: '{"inherit":true,"enabled":true,"max_retries":1,"delay_seconds":20,"retry_on":["lock","network","timeout"]}';
    }

    public static function decodePolicy(?string $json, ?array $fallback = null, bool $allowInherit = true): array {
        $decoded = null;
        if (is_string($json) && trim($json) !== '') {
            $candidate = json_decode($json, true);
            if (is_array($candidate)) {
                $decoded = $candidate;
            }
        }

        if (!is_array($decoded)) {
            $decoded = $fallback ?? ($allowInherit ? self::defaultEntityPolicy() : self::defaultGlobalPolicy());
        }

        return self::normalizePolicy($decoded, $allowInherit);
    }

    public static function parsePolicyPost(array $input, string $prefix, ?array $fallback = null, bool $allowInherit = true): array {
        $basePolicy = self::normalizePolicy(
            $fallback ?? ($allowInherit ? self::defaultEntityPolicy() : self::defaultGlobalPolicy()),
            $allowInherit
        );
        $mode = (string) ($input[$prefix . '_retry_mode'] ?? ($basePolicy['inherit'] ? 'inherit' : 'custom'));
        $policy = [
            'inherit' => $allowInherit ? ($mode !== 'custom') : false,
            'enabled' => isset($input[$prefix . '_retry_enabled']),
            'max_retries' => (int) ($input[$prefix . '_retry_max_retries'] ?? $basePolicy['max_retries']),
            'delay_seconds' => (int) ($input[$prefix . '_retry_delay_seconds'] ?? $basePolicy['delay_seconds']),
            'retry_on' => [],
        ];

        foreach (array_keys(self::RETRYABLE_ERRORS) as $key) {
            if (isset($input[$prefix . '_retry_on_' . $key])) {
                $policy['retry_on'][] = $key;
            }
        }

        return self::normalizePolicy($policy, $allowInherit);
    }

    public static function getGlobalPolicy(): array {
        return self::decodePolicy(Database::getSetting('job_retry_policy', ''), self::defaultGlobalPolicy(), false);
    }

    public static function getEntityPolicy(array $row): array {
        return self::decodePolicy((string) ($row['retry_policy'] ?? ''), self::defaultEntityPolicy(), true);
    }

    public static function resolvePolicy(array $policy): array {
        $normalized = self::normalizePolicy($policy, true);
        if (!empty($normalized['inherit'])) {
            $resolved = self::getGlobalPolicy();
            $resolved['source'] = 'global';
            return $resolved;
        }

        $normalized['inherit'] = false;
        $normalized['source'] = 'custom';
        return $normalized;
    }

    public static function summarizePolicy(array $policy): array {
        $normalized = self::normalizePolicy($policy, true);
        $resolved = self::resolvePolicy($normalized);
        $items = [];

        if (!empty($normalized['inherit'])) {
            $items[] = ['text' => 'Global', 'tone' => 'blue'];
        } else {
            $items[] = ['text' => 'Personnalise', 'tone' => 'purple'];
        }

        if (empty($resolved['enabled']) || (int) $resolved['max_retries'] <= 0) {
            $items[] = ['text' => 'Retry desactive', 'tone' => 'gray'];
            return $items;
        }

        $retryLabel = (int) $resolved['max_retries'] === 1
            ? '1 retry'
            : ((int) $resolved['max_retries'] . ' retries');

        $items[] = ['text' => $retryLabel, 'tone' => 'green'];
        $items[] = ['text' => 'Attente ' . (int) $resolved['delay_seconds'] . 's', 'tone' => 'yellow'];

        $retryLabels = [];
        foreach ($resolved['retry_on'] as $key) {
            if (isset(self::RETRYABLE_ERRORS[$key])) {
                $retryLabels[] = self::RETRYABLE_ERRORS[$key];
            }
        }

        if (!empty($retryLabels)) {
            $items[] = ['text' => implode(' + ', $retryLabels), 'tone' => 'gray'];
        }

        return $items;
    }

    public static function classifyFailure(string $output, int $code = 0): array {
        $normalized = strtolower(trim($output));

        if ($normalized !== '') {
            if (preg_match('/(?:already locked|repository is locked|unable to create lock|stale lock|lock.*exists)/', $normalized) === 1) {
                return self::classification('lock', 'Depot verrouille', true);
            }

            if (preg_match('/(?:timed out|timeout|deadline exceeded|i\/o timeout|context deadline exceeded|operation timed out)/', $normalized) === 1) {
                return self::classification('timeout', 'Timeout detecte', true);
            }

            if (preg_match('/(?:connection refused|connection reset|broken pipe|no route to host|network is unreachable|temporary failure|tls handshake timeout|unexpected eof|dial tcp|lookup .* no such host|service unavailable|gateway timeout|server misbehaving|connection closed by remote host|ssh: handshake failed)/', $normalized) === 1) {
                return self::classification('network', 'Erreur reseau ou backend', true);
            }

            if (preg_match('/(?:resource temporarily unavailable|device or resource busy|text file busy|too many open files)/', $normalized) === 1) {
                return self::classification('busy', 'Ressource temporairement indisponible', true);
            }

            if (preg_match('/(?:wrong password|password is incorrect|authentication failed|permission denied \(publickey\)|invalid api token|access denied|unauthorized|forbidden)/', $normalized) === 1) {
                return self::classification('auth', 'Erreur d authentification', false);
            }

            if (preg_match('/(?:permission denied|operation not permitted|read-only file system)/', $normalized) === 1) {
                return self::classification('permission', 'Probleme de permissions', false);
            }

            if (preg_match('/(?:repository does not exist|no such file or directory|invalid argument|unknown flag|config file .* not found|unable to open config file|unsupported backend|fatal:)/', $normalized) === 1) {
                return self::classification('config', 'Erreur de configuration', false);
            }
        }

        if ($code === 124) {
            return self::classification('timeout', 'Timeout detecte', true);
        }

        return self::classification('unknown', 'Erreur non classee', true);
    }

    public static function shouldRetry(array $policy, string $output, int $code, int $retryCount): array {
        $resolved = self::normalizePolicy($policy, false);
        $classification = self::classifyFailure($output, $code);

        if (empty($resolved['enabled']) || (int) $resolved['max_retries'] <= 0) {
            return [
                'retry' => false,
                'classification' => $classification,
                'reason' => 'Retry desactive',
                'delay_seconds' => (int) $resolved['delay_seconds'],
            ];
        }

        if ($retryCount >= (int) $resolved['max_retries']) {
            return [
                'retry' => false,
                'classification' => $classification,
                'reason' => 'Nombre maximal de retries atteint',
                'delay_seconds' => (int) $resolved['delay_seconds'],
            ];
        }

        if (empty($classification['retryable'])) {
            return [
                'retry' => false,
                'classification' => $classification,
                'reason' => 'Erreur non relancable automatiquement',
                'delay_seconds' => (int) $resolved['delay_seconds'],
            ];
        }

        if (!in_array($classification['category'], $resolved['retry_on'], true)) {
            return [
                'retry' => false,
                'classification' => $classification,
                'reason' => 'Categorie non cochee dans la politique',
                'delay_seconds' => (int) $resolved['delay_seconds'],
            ];
        }

        return [
            'retry' => true,
            'classification' => $classification,
            'reason' => 'Erreur transitoire eligible au retry',
            'delay_seconds' => (int) $resolved['delay_seconds'],
        ];
    }

    private static function normalizePolicy(array $policy, bool $allowInherit): array {
        $retryOn = [];
        foreach ((array) ($policy['retry_on'] ?? []) as $value) {
            $key = (string) $value;
            if (isset(self::RETRYABLE_ERRORS[$key])) {
                $retryOn[$key] = $key;
            }
        }

        if (empty($retryOn)) {
            foreach (['lock', 'network', 'timeout'] as $defaultKey) {
                $retryOn[$defaultKey] = $defaultKey;
            }
        }

        return [
            'inherit' => $allowInherit ? (!array_key_exists('inherit', $policy) || (bool) $policy['inherit']) : false,
            'enabled' => !array_key_exists('enabled', $policy) || (bool) $policy['enabled'],
            'max_retries' => max(0, min(10, (int) ($policy['max_retries'] ?? 1))),
            'delay_seconds' => max(1, min(600, (int) ($policy['delay_seconds'] ?? 20))),
            'retry_on' => array_values($retryOn),
        ];
    }

    private static function classification(string $category, string $label, bool $retryable): array {
        return [
            'category' => $category,
            'label' => $label,
            'retryable' => $retryable,
        ];
    }
}
