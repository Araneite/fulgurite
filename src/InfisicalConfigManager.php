<?php

class InfisicalConfigManager {
    private const SETTINGS = [
        'infisical_enabled',
        'infisical_url',
        'infisical_token',
        'infisical_project_id',
        'infisical_environment',
        'infisical_secret_path',
        'infisical_allowed_hosts',
        'infisical_allowed_host_patterns',
        'infisical_allowed_cidrs',
        'infisical_allowed_port',
        'infisical_allow_http',
    ];

    private const ENDPOINT_SETTINGS = [
        'infisical_url',
        'infisical_token',
        'infisical_project_id',
        'infisical_environment',
        'infisical_secret_path',
        'infisical_allowed_hosts',
        'infisical_allowed_host_patterns',
        'infisical_allowed_cidrs',
        'infisical_allowed_port',
        'infisical_allow_http',
    ];

    /**
     * @return array<string,string>
     */
    public static function currentStoredSnapshot(): array {
        $snapshot = [];
        foreach (self::SETTINGS as $setting) {
            $default = $setting === 'infisical_environment' ? 'prod' : ($setting === 'infisical_secret_path' ? '/' : '0');
            if (!in_array($setting, ['infisical_enabled', 'infisical_allow_http'], true)) {
                $default = $setting === 'infisical_environment' ? 'prod' : ($setting === 'infisical_secret_path' ? '/' : '');
            }
            $snapshot[$setting] = Database::getStoredSetting($setting, $default);
        }

        return self::normalizeSnapshot($snapshot);
    }

    /**
     * @param array<string,string> $snapshot
     * @return array<string,string>
     */
    public static function snapshotToConfig(array $snapshot): array {
        $config = self::normalizeSnapshot($snapshot);
        $config['infisical_token_value'] = self::resolveStoredSecret((string) ($config['infisical_token'] ?? ''));
        return $config;
    }

    /**
     * @return array<string,string>
     */
    public static function currentConfig(): array {
        return self::snapshotToConfig(self::currentStoredSnapshot());
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,string>|null $baseConfig
     * @return array<string,string>
     */
    public static function candidateFromInput(array $input, ?array $baseConfig = null): array {
        $config = $baseConfig ?? self::currentConfig();
        $config = self::normalizeSnapshot($config);
        $config['infisical_token_value'] = $config['infisical_token_value'] ?? self::resolveStoredSecret((string) ($config['infisical_token'] ?? ''));

        if (array_key_exists('infisical_enabled_present', $input) || array_key_exists('infisical_enabled', $input)) {
            $config['infisical_enabled'] = !empty($input['infisical_enabled']) ? '1' : '0';
        }
        if (array_key_exists('infisical_allow_http_present', $input) || array_key_exists('infisical_allow_http', $input)) {
            $config['infisical_allow_http'] = !empty($input['infisical_allow_http']) ? '1' : '0';
        }

        foreach ([
            'infisical_url',
            'infisical_project_id',
            'infisical_environment',
            'infisical_secret_path',
            'infisical_allowed_hosts',
            'infisical_allowed_host_patterns',
            'infisical_allowed_cidrs',
            'infisical_allowed_port',
        ] as $field) {
            if (array_key_exists($field, $input)) {
                $config[$field] = trim((string) $input[$field]);
            }
        }

        if (array_key_exists('infisical_token', $input) && trim((string) $input['infisical_token']) !== '') {
            $config['infisical_token'] = trim((string) $input['infisical_token']);
            $config['infisical_token_value'] = trim((string) $input['infisical_token']);
        }

        return self::normalizeSnapshot($config);
    }

    /**
     * @param array<string,string> $current
     * @param array<string,string> $candidate
     */
    public static function hasProtectedChanges(array $current, array $candidate): bool {
        foreach (self::SETTINGS as $setting) {
            if ($setting === 'infisical_token') {
                if ((string) ($current['infisical_token'] ?? '') !== (string) ($candidate['infisical_token'] ?? '')) {
                    return true;
                }
                continue;
            }

            if ((string) ($current[$setting] ?? '') !== (string) ($candidate[$setting] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,string> $current
     * @param array<string,string> $candidate
     */
    public static function requiresConnectivityValidation(array $current, array $candidate): bool {
        if ($candidate['infisical_enabled'] === '1') {
            return true;
        }

        foreach (self::ENDPOINT_SETTINGS as $setting) {
            if ((string) ($current[$setting] ?? '') !== (string) ($candidate[$setting] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,string> $config
     * @return array<string,string>
     */
    public static function materializeTrustedPolicyConfig(array $config): array {
        $config = self::normalizeSnapshot($config);
        $parts = OutboundUrlTools::parseUrl((string) $config['infisical_url']);

        $hosts = self::csvToList((string) $config['infisical_allowed_hosts']);
        $patterns = self::csvToList((string) $config['infisical_allowed_host_patterns']);
        $cidrs = self::csvToList((string) $config['infisical_allowed_cidrs']);
        $expectedPort = (int) ($config['infisical_allowed_port'] !== '' ? $config['infisical_allowed_port'] : 0);

        if ($hosts === [] && $patterns === []) {
            $hosts = [$parts['host']];
        }
        if ($expectedPort <= 0) {
            $expectedPort = $parts['port'];
        }
        if ($cidrs === []) {
            $resolvedIps = OutboundUrlTools::resolveHostIps($parts['host']);
            if ($resolvedIps === []) {
                throw new InvalidArgumentException('Impossible de resoudre le DNS de la cible Infisical.');
            }
            $cidrs = array_map([OutboundUrlTools::class, 'ipToHostCidr'], $resolvedIps);
        }

        $config['infisical_allowed_hosts'] = implode(', ', array_values(array_unique($hosts)));
        $config['infisical_allowed_host_patterns'] = implode(', ', array_values(array_unique($patterns)));
        $config['infisical_allowed_cidrs'] = implode(', ', array_values(array_unique($cidrs)));
        $config['infisical_allowed_port'] = (string) $expectedPort;

        return $config;
    }

    /**
     * @param array<string,string> $config
     * @return array{
     * allowed_hosts:string[],
     * allowed_patterns:string[],
     * allowed_cidrs:string[],
     * allow_http:bool,
     * expected_port:int
     * }
     */
    public static function buildTrustedPolicy(array $config): array {
        $config = self::materializeTrustedPolicyConfig($config);

        return [
            'allowed_hosts' => self::csvToList((string) $config['infisical_allowed_hosts']),
            'allowed_patterns' => self::csvToList((string) $config['infisical_allowed_host_patterns']),
            'allowed_cidrs' => self::csvToList((string) $config['infisical_allowed_cidrs']),
            'allow_http' => $config['infisical_allow_http'] === '1',
            'expected_port' => (int) $config['infisical_allowed_port'],
        ];
    }

    /**
     * @param array<string,string> $config
     * @return array<string,mixed>
     */
    public static function testConfiguration(array $config): array {
        $config = self::normalizeSnapshot($config);
        $config['infisical_token_value'] = $config['infisical_token_value'] ?? self::resolveStoredSecret((string) ($config['infisical_token'] ?? ''));

        if ($config['infisical_url'] === '' || $config['infisical_token_value'] === '') {
            return ['success' => false, 'output' => 'Infisical non configure (URL ou token manquant).'];
        }

        try {
            $config = self::materializeTrustedPolicyConfig($config);
            $policy = self::buildTrustedPolicy($config);
            $validator = new TrustedServiceEndpointValidator($policy);
            $statusUrl = rtrim($config['infisical_url'], '/') . '/api/status';
            $response = OutboundHttpClient::request('GET', $statusUrl, [
                'headers' => [
                    'Accept: application/json',
                    'Authorization: Bearer ' . $config['infisical_token_value'],
                ],
                'timeout' => 5,
                'connect_timeout' => 3,
                'max_redirects' => 2,
                'user_agent' => 'Fulgurite-Infisical/1.0',
            ], $validator);
        } catch (Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }

        if ($response['error'] !== null) {
            return ['success' => false, 'output' => $response['error'], 'http_status' => $response['status'] ?? 0];
        }

        if ((int) ($response['status'] ?? 0) !== 200) {
            return [
                'success' => false,
                'output' => 'Le serveur Infisical ne repond pas avec le code HTTP attendu (200).',
                'http_status' => (int) ($response['status'] ?? 0),
            ];
        }

        $decoded = json_decode((string) ($response['body'] ?? ''), true);
        if (!is_array($decoded) || (!isset($decoded['message']) && !isset($decoded['date']) && !isset($decoded['status']))) {
            return [
                'success' => false,
                'output' => 'La reponse /api/status ne correspond pas a une signature Infisical attendue.',
                'http_status' => (int) ($response['status'] ?? 0),
            ];
        }

        return [
            'success' => true,
            'output' => 'Connexion Infisical OK (' . $config['infisical_url'] . ')',
            'http_status' => (int) ($response['status'] ?? 0),
            'config' => $config,
            'validation' => [
                'resolved_ips' => (array) (($response['validated']['resolved_ips'] ?? [])),
                'effective_policy' => $policy,
                'final_url' => (string) ($response['final_url'] ?? $statusUrl),
            ],
        ];
    }

    /**
     * @param array<string,string> $config
     * @param array<string,mixed> $validationResult
     * @return array<string,string>
     */
    public static function persistConfiguration(
        array $config,
        int $userId,
        array $validationResult,
        string $action = 'update',
        ?int $restoredFromId = null
    ): array {
        $db = Database::getInstance();
        $before = self::currentStoredSnapshot();
        $config = self::materializeTrustedPolicyConfig($config);

        try {
            $db->beginTransaction();
            foreach (self::SETTINGS as $setting) {
                Database::setSetting($setting, (string) ($config[$setting] ?? ''));
            }
            $after = self::currentStoredSnapshot();
            $historySql = Database::adaptDdlPublic("
                INSERT INTO infisical_config_history (
                    action, changed_by, previous_url, new_url, source_ip, validation_success,
                    validation_result_json, config_json, restored_from_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $db->prepare($historySql)->execute([
                $action,
                $userId,
                $before['infisical_url'] ?? '',
                $after['infisical_url'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? '',
                !empty($validationResult['success']) ? 1 : 0,
                json_encode([
                    'output' => $validationResult['output'] ?? '',
                    'http_status' => (int) ($validationResult['http_status'] ?? 0),
                    'validation' => $validationResult['validation'] ?? [],
                ], JSON_UNESCAPED_SLASHES),
                json_encode($after, JSON_UNESCAPED_SLASHES),
                $restoredFromId,
            ]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        return self::currentConfig();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function history(int $limit = 10): array {
        $stmt = Database::getInstance()->prepare(Database::adaptDdlPublic("
            SELECT h.*, u.username
            FROM infisical_config_history h
            LEFT JOIN users u ON u.id = h.changed_by
            ORDER BY h.id DESC
            LIMIT ?
        "));
        $stmt->bindValue(1, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        return array_map(static function (array $row): array {
            $row['validation_result'] = json_decode((string) ($row['validation_result_json'] ?? '{}'), true) ?: [];
            return $row;
        }, $rows);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function historyEntry(int $id): ?array {
        $stmt = Database::getInstance()->prepare('SELECT * FROM infisical_config_history WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['validation_result'] = json_decode((string) ($row['validation_result_json'] ?? '{}'), true) ?: [];
        $row['config'] = json_decode((string) ($row['config_json'] ?? '{}'), true) ?: [];
        return $row;
    }

    /**
     * @param array<string,string> $snapshot
     * @return array<string,string>
     */
    private static function normalizeSnapshot(array $snapshot): array {
        $defaults = [
            'infisical_enabled' => '0',
            'infisical_url' => '',
            'infisical_token' => '',
            'infisical_project_id' => '',
            'infisical_environment' => 'prod',
            'infisical_secret_path' => '/',
            'infisical_allowed_hosts' => '',
            'infisical_allowed_host_patterns' => '',
            'infisical_allowed_cidrs' => '',
            'infisical_allowed_port' => '',
            'infisical_allow_http' => '0',
            'infisical_token_value' => '',
        ];

        $config = array_merge($defaults, $snapshot);
        $config['infisical_enabled'] = !empty($config['infisical_enabled']) ? '1' : '0';
        $config['infisical_allow_http'] = !empty($config['infisical_allow_http']) ? '1' : '0';
        $config['infisical_url'] = rtrim(trim((string) $config['infisical_url']), '/');
        $config['infisical_project_id'] = trim((string) $config['infisical_project_id']);
        $config['infisical_environment'] = trim((string) $config['infisical_environment']) !== '' ? trim((string) $config['infisical_environment']) : 'prod';
        $secretPath = trim((string) $config['infisical_secret_path']);
        $config['infisical_secret_path'] = $secretPath !== '' ? $secretPath : '/';
        $config['infisical_allowed_hosts'] = self::listToCsv(self::csvToList((string) $config['infisical_allowed_hosts']));
        $config['infisical_allowed_host_patterns'] = self::listToCsv(self::csvToList((string) $config['infisical_allowed_host_patterns']));
        $config['infisical_allowed_cidrs'] = self::listToCsv(self::csvToList((string) $config['infisical_allowed_cidrs']));
        $config['infisical_allowed_port'] = trim((string) $config['infisical_allowed_port']);
        $config['infisical_token'] = (string) $config['infisical_token'];
        $config['infisical_token_value'] = (string) ($config['infisical_token_value'] ?? '');

        return $config;
    }

    private static function resolveStoredSecret(string $value): string {
        if ($value === '') {
            return '';
        }
        if (class_exists('SecretStore', false) && SecretStore::isSecretRef($value)) {
            return SecretStore::get($value) ?? '';
        }

        return $value;
    }

    /**
     * @return string[]
     */
    private static function csvToList(string $value): array {
        $items = preg_split('/[\s,;]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_unique($items));
    }

    /**
     * @param string[] $values
     */
    private static function listToCsv(array $values): string {
        return implode(', ', array_values(array_unique(array_filter(array_map('trim', $values)))));
    }
}
