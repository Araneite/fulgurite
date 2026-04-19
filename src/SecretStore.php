<?php

interface SecretProvider {
    public function put(string $ref, string $value, array $metadata = []): string;
    public function get(string $ref, string $purpose = 'runtime', array $context = []): ?string;
    public function delete(string $ref): void;
    public function exists(string $ref): bool;
    public function health(): array;
}

final class SecretStore {
    private static ?SecretProvider $agentProvider = null;
    private static ?SecretProvider $localProvider = null;

    public static function put(string $ref, string $value, array $metadata = []): string {
        return self::providerFor($ref)->put($ref, $value, $metadata);
    }

    public static function get(string $ref, string $purpose = 'runtime', array $context = []): ?string {
        return self::providerFor($ref)->get($ref, $purpose, $context);
    }

    public static function delete(string $ref): void {
        self::providerFor($ref)->delete($ref);
    }

    public static function exists(string $ref): bool {
        return self::providerFor($ref)->exists($ref);
    }

    public static function resolve(?string $ref, string $purpose = 'runtime', array $context = []): string {
        if (!self::isSecretRef($ref)) {
            return '';
        }
        return self::get((string) $ref, $purpose, $context) ?? '';
    }

    public static function writableRef(string $type, int $id, string $name, ?string $source = null): string {
        $source = self::normalizeWritableSource($source ?? self::defaultWritableSource());
        if ($source === 'agent') {
            return self::agentRef($type, $id, $name);
        }
        if ($source === 'local') {
            return self::localRef($type, $id, $name);
        }
        throw new InvalidArgumentException('Source de secret non supportee.');
    }

    public static function defaultWritableSource(): string {
        $configured = self::env('FULGURITE_SECRET_PROVIDER', '');
        if ($configured !== '') {
            return self::normalizeWritableSource($configured);
        }
        return 'agent';
    }

    public static function resolvedWritableSource(?string $source = null): string {
        $requested = self::normalizeWritableSource($source ?? self::defaultWritableSource());
        if ($requested === 'local') {
            return 'local';
        }

        $health = self::agent()->health();
        return !empty($health['ok']) ? 'agent' : 'local';
    }

    public static function normalizeWritableSource(string $source): string {
        $source = strtolower(trim($source));
        return match ($source) {
            'agent', 'broker', 'secret-agent' => 'agent',
            'local', 'encrypted', 'fallback' => 'local',
            default => throw new InvalidArgumentException('Source de secret inconnue: ' . $source),
        };
    }

    public static function agentRef(string $type, int $id, string $name): string {
        return self::entityRef('agent', $type, $id, $name);
    }

    public static function localRef(string $type, int $id, string $name): string {
        return self::entityRef('local', $type, $id, $name);
    }

    public static function infisicalRef(string $secretName): string {
        $secretName = trim($secretName);
        if ($secretName === '') {
            throw new InvalidArgumentException('Nom de secret Infisical requis.');
        }
        return 'secret://infisical/'. rawurlencode($secretName);
    }

    public static function isSecretRef(?string $value): bool {
        return is_string($value) && str_starts_with($value, 'secret://');
    }

    public static function providerNameForRef(string $ref): string {
        if (preg_match('#^secret://([^/]+)/#', $ref, $m) !== 1) {
            throw new InvalidArgumentException('Reference de secret invalide.');
        }
        return strtolower($m[1]);
    }

    public static function providerHealth(): array {
        return [
            'agent' => self::agent()->health(),
            'local' => self::local()->health(),
            'default' => self::defaultWritableSource(),
        ];
    }

    public static function brokerHealth(): array {
        return self::agent()->health();
    }

    public static function auditLogs(int $limit = 200): array {
        $provider = self::agent();
        if ($provider instanceof HaBrokerSecretProvider || $provider instanceof AgentSecretProvider) {
            try {
                return $provider->auditLogs($limit);
            } catch (Throwable $e) {
                return ['ok' => false, 'logs' => [], 'error' => $e->getMessage()];
            }
        }
        return ['ok' => false, 'logs' => [], 'error' => 'Provider agent indisponible.'];
    }

    public static function generateKey(): string {
        return base64_encode(random_bytes(LocalEncryptedSecretProvider::KEY_BYTES));
    }

    public static function masterKeyStatus(): array {
        return LocalEncryptedSecretProvider::masterKeyStatus();
    }

    public static function resetRuntimeState(): void {
        self::$agentProvider = null;
        self::$localProvider = null;
        HaBrokerSecretProvider::resetEndpointState();
    }

    public static function useProvidersForTests(?SecretProvider $agentProvider = null, ?SecretProvider $localProvider = null): void {
        self::$agentProvider = $agentProvider;
        self::$localProvider = $localProvider;
    }

    public static function agent(): SecretProvider {
        if (self::$agentProvider === null) {
            $endpoints = self::agentEndpoints();
            $timeout = (float) self::env('FULGURITE_SECRET_BROKER_TIMEOUT', self::env('FULGURITE_SECRET_AGENT_TIMEOUT', '2.0'));
            self::$agentProvider = new HaBrokerSecretProvider($endpoints, $timeout);
        }
        return self::$agentProvider;
    }

    public static function local(): SecretProvider {
        if (self::$localProvider === null) {
            self::$localProvider = new LocalEncryptedSecretProvider(Database::getInstance(), 'local');
        }
        return self::$localProvider;
    }

    /**
     * @return list<array{uri:string,type:string,path?:string,host?:string,port?:int}>
     */
    public static function agentEndpoints(): array {
        $raw = self::env('FULGURITE_SECRET_BROKER_ENDPOINTS', '');
        if ($raw !== '') {
            $parsed = HaBrokerSecretProvider::parseEndpoints($raw);
            if (!empty($parsed)) {
                return $parsed;
            }
        }
        $socketPath = self::agentSocketPath();
        $legacyUri = str_starts_with($socketPath, 'unix://') ? $socketPath : ('unix://'. $socketPath);
        return [['uri' => $legacyUri, 'type' => 'unix', 'path' => str_replace('unix://', '', $legacyUri)]];
    }

    public static function agentSocketPath(): string {
        return self::env('FULGURITE_SECRET_AGENT_SOCKET', '/run/fulgurite/secrets.sock');
    }

    public static function env(string $key, string $default = ''): string {
        if (function_exists('fulguriteEnv')) {
            $value = fulguriteEnv($key, '');
            if ($value !== '') {
                return $value;
            }
        }
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function providerFor(string $ref): SecretProvider {
        return match (self::providerNameForRef($ref)) {
            'agent' => self::agent(),
            'local' => self::local(),
            'infisical' => new InfisicalSecretProvider(),
            default => throw new InvalidArgumentException('Provider de secret non supporte.'),
        };
    }

    private static function entityRef(string $provider, string $type, int $id, string $name): string {
        $type = trim($type, '/');
        $name = trim($name, '/');
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $provider) || $type === '' || $id < 1 || $name === '') {
            throw new InvalidArgumentException('Reference de secret invalide.');
        }
        if (!preg_match('/^[a-z0-9_-]+$/', $type) || !preg_match('/^[a-z0-9_-]+$/', $name)) {
            throw new InvalidArgumentException('Reference de secret invalide.');
        }
        return "secret://{$provider}/{$type}/{$id}/{$name}";
    }
}

final class AgentSecretProvider implements SecretProvider {
    private array $allowedPurposes = ['runtime', 'backup', 'copy', 'restore', 'sudo', 'migration', 'setup', 'health', 'audit'];

    public function __construct(private string $socketPath, private float $timeoutSeconds = 2.0) {}

    public function put(string $ref, string $value, array $metadata = []): string {
        $this->assertAgentRef($ref);
        $this->request(['action' => 'put', 'secret_ref' => $ref, 'value' => $value, 'metadata' => $metadata, 'purpose' => 'migration']);
        return $ref;
    }

    public function get(string $ref, string $purpose = 'runtime', array $context = []): ?string {
        $this->assertAgentRef($ref);
        $payload = $this->request(['action' => 'get', 'secret_ref' => $ref, 'purpose' => $this->purpose($purpose), 'context' => $context]);
        return array_key_exists('value', $payload) ? (string) $payload['value'] : null;
    }

    public function delete(string $ref): void {
        $this->assertAgentRef($ref);
        $this->request(['action' => 'delete', 'secret_ref' => $ref, 'purpose' => 'migration']);
    }

    public function exists(string $ref): bool {
        $this->assertAgentRef($ref);
        $payload = $this->request(['action' => 'exists', 'secret_ref' => $ref, 'purpose' => 'runtime']);
        return !empty($payload['exists']);
    }

    public function health(): array {
        try {
            return $this->request(['action' => 'health', 'purpose' => 'health']);
        } catch (Throwable $e) {
            return ['ok' => false, 'provider' => 'agent', 'socket' => $this->socketPath, 'error' => $e->getMessage()];
        }
    }

    public function auditLogs(int $limit = 200): array {
        $limit = max(1, min(500, $limit));
        return $this->request(['action' => 'audit_tail', 'purpose' => 'audit', 'limit' => $limit]);
    }

    private function request(array $payload): array {
        $address = 'unix://'. $this->socketPath;
        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client($address, $errno, $errstr, $this->timeoutSeconds, STREAM_CLIENT_CONNECT);
        if (!is_resource($stream)) {
            throw new RuntimeException('Secret broker indisponible. Configurez fulgurite-secret-agent ou FULGURITE_SECRET_PROVIDER=local pour le fallback explicite.');
        }
        stream_set_timeout($stream, (int) max(1, ceil($this->timeoutSeconds)));
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || @fwrite($stream, $json . "\n") === false) {
            fclose($stream);
            throw new RuntimeException('Impossible d envoyer la requete au secret broker.');
        }
        $line = fgets($stream);
        fclose($stream);
        if (!is_string($line) || trim($line) === '') {
            throw new RuntimeException('Reponse vide du secret broker.');
        }
        $response = json_decode($line, true);
        if (!is_array($response)) {
            throw new RuntimeException('Reponse invalide du secret broker.');
        }
        if (empty($response['ok'])) {
            throw new RuntimeException((string) ($response['error'] ?? 'Erreur secret broker.'));
        }
        return $response;
    }

    private function purpose(string $purpose): string {
        $purpose = strtolower(trim($purpose));
        if (!in_array($purpose, $this->allowedPurposes, true)) {
            throw new InvalidArgumentException('Purpose de secret invalide.');
        }
        return $purpose;
    }

    private function assertAgentRef(string $ref): void {
        if (preg_match('#^secret://agent/[a-z0-9_-]+/[1-9][0-9]*/[a-z0-9_-]+$#', $ref) !== 1) {
            throw new InvalidArgumentException('Reference de secret agent invalide.');
        }
    }
}

/**
 * HA-capable broker client that supports multiple endpoints (unix:// or tcp://) with
 * automatic failover. Within a single PHP request, failed endpoints are remembered and
 * skipped for ENDPOINT_DOWN_TTL seconds before being retried. If all endpoints fail,
 * a RuntimeException is thrown — there is no silent insecure fallback at this layer;
 * the SensitiveEntitySecretManager layer handles the explicit local fallback.
 */
final class HaBrokerSecretProvider implements SecretProvider {
    /** Seconds a failed endpoint is skipped before being retried within the same request. */
    private const ENDPOINT_DOWN_TTL = 10.0;

    private const ALLOWED_PURPOSES = ['runtime', 'backup', 'copy', 'restore', 'sudo', 'migration', 'setup', 'health', 'audit'];

    /** @var list<array{uri:string,type:string,path?:string,host?:string,port?:int}> */
    private array $endpoints;
    private float $timeoutSeconds;

    /** Per-request static health cache: uri => {ok, failed_at, error} */
    private static array $endpointState = [];
    private ?string $lastSuccessfulEndpoint = null;

    /**
     * @param list<array{uri:string,type:string,...}> $endpoints Parsed endpoint descriptors.
     */
    public function __construct(array $endpoints, float $timeoutSeconds = 2.0) {
        $this->endpoints = $endpoints;
        $this->timeoutSeconds = max(0.1, $timeoutSeconds);
    }

    /**
     * Parse a comma-separated string of endpoint URIs into descriptor arrays.
     * Accepted schemes: unix:///path/to/socket tcp://host:port
     *
     * @return list<array{uri:string,type:string,path?:string,host?:string,port?:int}>
     */
    public static function parseEndpoints(string $raw): array {
        $endpoints = [];
        foreach (array_filter(array_map('trim', explode(',', $raw))) as $uri) {
            $parsed = self::parseUri($uri);
            if ($parsed !== null) {
                $endpoints[] = $parsed;
            }
        }
        return $endpoints;
    }

    private static function parseUri(string $uri): ?array {
        if (str_starts_with($uri, 'unix://')) {
            $path = substr($uri, 7);
            return $path !== '' ? ['uri' => $uri, 'type' => 'unix', 'path' => $path] : null;
        }
        if (str_starts_with($uri, 'tcp://')) {
            $rest = substr($uri, 6);
            $lastColon = strrpos($rest, ':');
            if ($lastColon === false) {
                return null;
            }
            $host = substr($rest, 0, $lastColon);
            $port = (int) substr($rest, $lastColon + 1);
            if ($host === '' || $port < 1 || $port > 65535) {
                return null;
            }
            return ['uri' => $uri, 'type' => 'tcp', 'host' => $host, 'port' => $port];
        }
        return null;
    }

    /** Reset per-request endpoint health cache (used by tests and SecretStore::resetRuntimeState). */
    public static function resetEndpointState(): void {
        self::$endpointState = [];
    }

    /** Return the parsed endpoint descriptors (useful for diagnostics). */
    public function getEndpoints(): array {
        return $this->endpoints;
    }

    /** Return the current per-request endpoint health state (useful for diagnostics). */
    public function getEndpointState(): array {
        return self::$endpointState;
    }

    // ── SecretProvider interface ────────────────────────────────────────────────

    public function put(string $ref, string $value, array $metadata = []): string {
        $this->assertAgentRef($ref);
        $this->request(['action' => 'put', 'secret_ref' => $ref, 'value' => $value, 'metadata' => $metadata, 'purpose' => 'migration']);
        return $ref;
    }

    public function get(string $ref, string $purpose = 'runtime', array $context = []): ?string {
        $this->assertAgentRef($ref);
        $payload = $this->request(['action' => 'get', 'secret_ref' => $ref, 'purpose' => $this->sanitizePurpose($purpose), 'context' => $context]);
        return array_key_exists('value', $payload) ? (string) $payload['value'] : null;
    }

    public function delete(string $ref): void {
        $this->assertAgentRef($ref);
        $this->request(['action' => 'delete', 'secret_ref' => $ref, 'purpose' => 'migration']);
    }

    public function exists(string $ref): bool {
        $this->assertAgentRef($ref);
        $payload = $this->request(['action' => 'exists', 'secret_ref' => $ref, 'purpose' => 'runtime']);
        return !empty($payload['exists']);
    }

    /**
     * Returns cluster-level health: probes every configured endpoint and reports each node's
     * status, the count of healthy nodes, whether the cluster is degraded, and the active
     * endpoint URI.
     */
    public function health(): array {
        if (empty($this->endpoints)) {
            return [
                'ok' => false, 'provider' => 'ha-broker',
                'error' => 'Aucun endpoint broker configure.',
                'cluster' => ['total' => 0, 'healthy' => 0, 'degraded' => false, 'selected_endpoint' => null, 'nodes' => []],
            ];
        }
        foreach ($this->endpoints as $endpoint) {
            try {
                $result = $this->requestEndpoint($endpoint, ['action' => 'cluster_health', 'purpose' => 'health']);
                $cluster = is_array($result['cluster'] ?? null) ? $result['cluster'] : [];
                $cluster['selected_endpoint'] = (string) ($cluster['selected_endpoint'] ?? $endpoint['uri']);
                $cluster['nodes'] = array_values(array_map(static function (array $node): array {
                    return [
                        'endpoint' => (string) ($node['endpoint'] ?? ''),
                        'ok' => !empty($node['ok']),
                        'node_id' => (string) ($node['node_id'] ?? ''),
                        'node_label' => (string) ($node['node_label'] ?? ''),
                        'cluster_name' => (string) ($node['cluster_name'] ?? ''),
                        'backend' => (string) ($node['backend'] ?? ''),
                        'error' => (string) ($node['error'] ?? ''),
                    ];
                }, is_array($cluster['nodes'] ?? null) ? $cluster['nodes'] : []));
                return [
                    'ok' => !empty($result['ok']),
                    'provider' => 'ha-broker',
                    'cluster' => $cluster,
                ];
            } catch (Throwable $ignored) {
            }
        }
        $nodes = [];
        $healthyCount = 0;
        $selectedEndpoint = null;
        foreach ($this->endpoints as $endpoint) {
            try {
                $result = $this->requestEndpoint($endpoint, ['action' => 'health', 'purpose' => 'health']);
                $node = [
                    'endpoint' => $endpoint['uri'],
                    'ok' => true,
                    'type' => $endpoint['type'],
                    'node_id' => (string) ($result['node_id'] ?? ''),
                    'node_label' => (string) ($result['node_label'] ?? ''),
                    'cluster_name' => (string) ($result['cluster_name'] ?? ''),
                    'bind' => (string) ($result['bind'] ?? $endpoint['uri']),
                    'backend' => (string) ($result['backend'] ?? ''),
                ];
                $this->markEndpointUp($endpoint['uri']);
                $healthyCount++;
                if ($selectedEndpoint === null) {
                    $selectedEndpoint = $endpoint['uri'];
                }
            } catch (Throwable $e) {
                $node = ['endpoint' => $endpoint['uri'], 'ok' => false, 'type' => $endpoint['type'], 'error' => $e->getMessage()];
                $this->markEndpointDown($endpoint['uri'], $e->getMessage());
            }
            $nodes[] = $node;
        }
        $total = count($this->endpoints);
        return [
            'ok' => $healthyCount > 0,
            'provider' => 'ha-broker',
            'cluster' => [
                'total' => $total,
                'healthy' => $healthyCount,
                'degraded' => $healthyCount > 0 && $healthyCount < $total,
                'selected_endpoint' => $selectedEndpoint,
                'nodes' => $nodes,
            ],
        ];
    }

    public function auditLogs(int $limit = 200): array {
        $limit = max(1, min(500, $limit));
        foreach ($this->endpoints as $endpoint) {
            if ($this->isEndpointDown($endpoint['uri'])) {
                continue;
            }
            try {
                return $this->requestEndpoint($endpoint, ['action' => 'audit_tail', 'purpose' => 'audit', 'limit' => $limit]);
            } catch (Throwable $e) {
                $this->markEndpointDown($endpoint['uri'], $e->getMessage());
            }
        }
        // Last resort: try all regardless of cached down state.
        foreach ($this->endpoints as $endpoint) {
            try {
                return $this->requestEndpoint($endpoint, ['action' => 'audit_tail', 'purpose' => 'audit', 'limit' => $limit]);
            } catch (Throwable $ignored) {
            }
        }
        throw new RuntimeException('Aucun noeud broker disponible pour la lecture des logs d audit.');
    }

    // ── Internal request dispatch with failover ──────────────────────────────────

    private function request(array $payload): array {
        if (empty($this->endpoints)) {
            throw new RuntimeException('Aucun endpoint broker configure. Definissez FULGURITE_SECRET_BROKER_ENDPOINTS ou FULGURITE_SECRET_PROVIDER=local pour le fallback explicite.');
        }
        $lastError = null;
        $anyActive = false;
        $firstTriedEndpoint = null;
        foreach ($this->endpoints as $endpoint) {
            if ($this->isEndpointDown($endpoint['uri'])) {
                continue;
            }
            $anyActive = true;
            if ($firstTriedEndpoint === null) {
                $firstTriedEndpoint = $endpoint['uri'];
            }
            try {
                $result = $this->requestEndpoint($endpoint, $payload);
                $this->markEndpointUp($endpoint['uri']);
                $this->recordSelection($firstTriedEndpoint, $endpoint['uri'], $payload);
                return $result;
            } catch (RuntimeException $e) {
                $this->markEndpointDown($endpoint['uri'], $e->getMessage());
                $lastError = $e;
            }
        }
        if (!$anyActive) {
            // All endpoints are cached as down — force a retry round.
            foreach ($this->endpoints as $endpoint) {
                try {
                    $result = $this->requestEndpoint($endpoint, $payload);
                    $this->markEndpointUp($endpoint['uri']);
                    $this->recordSelection($firstTriedEndpoint ?? $endpoint['uri'], $endpoint['uri'], $payload);
                    return $result;
                } catch (RuntimeException $e) {
                    $this->markEndpointDown($endpoint['uri'], $e->getMessage());
                    $lastError = $e;
                }
            }
        }
        $nodeCount = count($this->endpoints);
        throw new RuntimeException(
            'Secret broker cluster indisponible (' . $nodeCount . ' noeud(s) en echec). '
            . 'Configurez FULGURITE_SECRET_PROVIDER=local pour le fallback explicite. '
            . 'Derniere erreur: ' . ($lastError?->getMessage() ?? 'inconnue')
        );
    }

    private function requestEndpoint(array $endpoint, array $payload): array {
        $address = $endpoint['type'] === 'unix'
            ? 'unix://'. $endpoint['path']
            : 'tcp://'. $endpoint['host']. ':'. $endpoint['port'];
        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client($address, $errno, $errstr, $this->timeoutSeconds, STREAM_CLIENT_CONNECT);
        if (!is_resource($stream)) {
            throw new RuntimeException('Noeud broker indisponible (' . $address . '): ' . $errstr);
        }
        stream_set_timeout($stream, (int) max(1, ceil($this->timeoutSeconds)));
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || @fwrite($stream, $json . "\n") === false) {
            @fclose($stream);
            throw new RuntimeException('Impossible d envoyer la requete au noeud broker.');
        }
        $line = fgets($stream);
        @fclose($stream);
        if (!is_string($line) || trim($line) === '') {
            throw new RuntimeException('Reponse vide du noeud broker.');
        }
        $response = json_decode($line, true);
        if (!is_array($response)) {
            throw new RuntimeException('Reponse invalide du noeud broker.');
        }
        if (empty($response['ok'])) {
            throw new RuntimeException((string) ($response['error'] ?? 'Erreur noeud broker.'));
        }
        return $response;
    }

    // ── Endpoint health tracking ─────────────────────────────────────────────────

    private function isEndpointDown(string $uri): bool {
        $state = self::$endpointState[$uri] ?? null;
        if ($state === null || $state['ok']) {
            return false;
        }
        return (microtime(true) - ($state['failed_at'] ?? 0.0)) < self::ENDPOINT_DOWN_TTL;
    }

    private function markEndpointDown(string $uri, string $error): void {
        self::$endpointState[$uri] = ['ok' => false, 'failed_at' => microtime(true), 'error' => $error];
    }

    private function markEndpointUp(string $uri): void {
        self::$endpointState[$uri] = ['ok' => true, 'failed_at' => null, 'error' => ''];
    }

    private function recordSelection(string $fromUri, string $selectedUri, array $payload): void {
        if ($this->lastSuccessfulEndpoint !== null && $this->lastSuccessfulEndpoint !== $selectedUri) {
            SecretBrokerEvents::recordClientFailover($this->lastSuccessfulEndpoint, $selectedUri, [
                'action' => (string) ($payload['action'] ?? ''),
            ]);
        } elseif ($fromUri !== '' && $fromUri !== $selectedUri) {
            SecretBrokerEvents::recordClientFailover($fromUri, $selectedUri, [
                'action' => (string) ($payload['action'] ?? ''),
            ]);
        }
        $this->lastSuccessfulEndpoint = $selectedUri;
    }

    // ── Validation helpers ────────────────────────────────────────────────────────

    private function assertAgentRef(string $ref): void {
        if (preg_match('#^secret://agent/[a-z0-9_-]+/[1-9][0-9]*/[a-z0-9_-]+$#', $ref) !== 1) {
            throw new InvalidArgumentException('Reference de secret agent invalide.');
        }
    }

    private function sanitizePurpose(string $purpose): string {
        $purpose = strtolower(trim($purpose));
        if (!in_array($purpose, self::ALLOWED_PURPOSES, true)) {
            throw new InvalidArgumentException('Purpose de secret invalide.');
        }
        return $purpose;
    }
}

final class LocalEncryptedSecretProvider implements SecretProvider {
    private const ALGORITHM_SODIUM = 'xchacha20poly1305-ietf';
    private const ALGORITHM_OPENSSL = 'aes-256-gcm';
    private const FALLBACK_KEY_FILE = 'secrets/local_master.key';
    public const KEY_BYTES = 32;
    private const SODIUM_NONCE_BYTES = 24;
    private const GCM_NONCE_BYTES = 12;

    public function __construct(private PDO $db, private string $provider = 'local') {}

    public static function forAgent(?string $dbPath = null): self {
        $dsn = SecretStore::env('FULGURITE_SECRET_AGENT_DB_DSN', '');
        if ($dsn !== '') {
            $db = new PDO(
                $dsn,
                SecretStore::env('FULGURITE_SECRET_AGENT_DB_USER', ''),
                SecretStore::env('FULGURITE_SECRET_AGENT_DB_PASS', '')
            );
        } else {
            $path = $dbPath ?: SecretStore::env('FULGURITE_SECRET_AGENT_DB', '/var/lib/fulgurite-secrets/secrets.db');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0700, true);
            }
            $db = new PDO('sqlite:' . $path);
        }
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $db->exec('PRAGMA busy_timeout = 5000');
        }
        self::initializeAgentSchema($db);
        return new self($db, 'agent');
    }

    private static function nowExpressionForDriver(string $driver): string {
        return match ($driver) {
            'mysql', 'pgsql' => 'NOW()',
            default => "datetime('now')",
        };
    }

    private static function adaptDdlForDriver(string $sql, string $driver): string {
        if ($driver === 'sqlite') {
            return $sql;
        }

        if ($driver === 'mysql') {
            $sql = preg_replace(
                '/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i',
                'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                $sql
            );
            $sql = preg_replace('/(?<=\s)TEXT\s+PRIMARY\s+KEY\b/i', 'VARCHAR(255) PRIMARY KEY', $sql);
            $sql = str_replace("DEFAULT (datetime('now'))", 'DEFAULT CURRENT_TIMESTAMP', $sql);
            $sql = preg_replace('/\bINTEGER\s+PRIMARY\s+KEY\b(?!\s+AUTOINCREMENT)/i', 'INT UNSIGNED NOT NULL PRIMARY KEY', $sql);
            return $sql;
        }

        if ($driver === 'pgsql') {
            $sql = preg_replace(
                '/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i',
                'SERIAL PRIMARY KEY',
                $sql
            );
            $sql = preg_replace('/\bINTEGER\s+PRIMARY\s+KEY\b(?!\s+AUTOINCREMENT)/i', 'INTEGER PRIMARY KEY', $sql);
            $sql = str_replace("DEFAULT (datetime('now'))", 'DEFAULT NOW()', $sql);
            return $sql;
        }

        return $sql;
    }

    private static function initializeAgentSchema(PDO $db): void {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = self::nowExpressionForDriver($driver);

        $db->exec(self::adaptDdlForDriver("CREATE TABLE IF NOT EXISTS app_secrets (
            ref TEXT PRIMARY KEY,
            provider TEXT NOT NULL,
            algorithm TEXT NOT NULL,
            nonce TEXT NOT NULL,
            ciphertext TEXT NOT NULL,
            metadata_json TEXT NOT NULL DEFAULT '{}',
            created_at TEXT DEFAULT ({$nowExpr}),
            updated_at TEXT DEFAULT ({$nowExpr})
        )", $driver));
        $db->exec('CREATE INDEX IF NOT EXISTS idx_app_secrets_provider ON app_secrets (provider, updated_at)');
        $db->exec(self::adaptDdlForDriver("CREATE TABLE IF NOT EXISTS app_secret_audit (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ts TEXT DEFAULT ({$nowExpr}),
            node_id TEXT,
            node_label TEXT,
            cluster_name TEXT,
            action TEXT NOT NULL,
            secret_ref TEXT,
            purpose TEXT,
            ok INTEGER NOT NULL DEFAULT 0,
            details_json TEXT NOT NULL DEFAULT '{}'
        )", $driver));
        $db->exec('CREATE INDEX IF NOT EXISTS idx_app_secret_audit_ts ON app_secret_audit (ts DESC, id DESC)');
    }

    public function put(string $ref, string $value, array $metadata = []): string {
        $this->assertRef($ref);
        $key = self::masterKey($this->provider);
        $algorithm = self::preferredAlgorithm();
        $nonce = random_bytes($algorithm === self::ALGORITHM_SODIUM ? self::SODIUM_NONCE_BYTES : self::GCM_NONCE_BYTES);
        $ciphertext = self::encrypt($value, $ref, $algorithm, $nonce, $key);
        $metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = "INSERT INTO app_secrets (ref, provider, algorithm, nonce, ciphertext, metadata_json, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
                    ON CONFLICT(ref) DO UPDATE SET algorithm = excluded.algorithm, nonce = excluded.nonce,
                        ciphertext = excluded.ciphertext, metadata_json = excluded.metadata_json, updated_at = excluded.updated_at";
            $this->db->prepare($sql)->execute([$ref, $this->provider, $algorithm, base64_encode($nonce), base64_encode($ciphertext), $metadataJson]);
        } else {
            $this->delete($ref);
            $this->db->prepare("INSERT INTO app_secrets (ref, provider, algorithm, nonce, ciphertext, metadata_json, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, " . self::nowExpressionForDriver((string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME)) . ", " . self::nowExpressionForDriver((string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME)) . ")")
                ->execute([$ref, $this->provider, $algorithm, base64_encode($nonce), base64_encode($ciphertext), $metadataJson]);
        }
        return $ref;
    }

    public function get(string $ref, string $purpose = 'runtime', array $context = []): ?string {
        $this->assertRef($ref);
        $stmt = $this->db->prepare('SELECT algorithm, nonce, ciphertext FROM app_secrets WHERE ref = ? AND provider = ?');
        $stmt->execute([$ref, $this->provider]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $algorithm = (string) ($row['algorithm'] ?? '');
        $nonce = base64_decode((string) $row['nonce'], true);
        $ciphertext = base64_decode((string) $row['ciphertext'], true);
        if (!in_array($algorithm, [self::ALGORITHM_SODIUM, self::ALGORITHM_OPENSSL], true) || $nonce === false || $ciphertext === false) {
            throw new RuntimeException('Secret local corrompu.');
        }
        $plaintext = self::decrypt($ciphertext, $ref, $algorithm, $nonce, self::masterKey($this->provider));
        if ($plaintext === false) {
            throw new RuntimeException('Impossible de dechiffrer le secret.');
        }
        return $plaintext;
    }

    public function delete(string $ref): void {
        $this->assertRef($ref);
        $this->db->prepare('DELETE FROM app_secrets WHERE ref = ? AND provider = ?')->execute([$ref, $this->provider]);
    }

    public function exists(string $ref): bool {
        $this->assertRef($ref);
        $stmt = $this->db->prepare('SELECT 1 FROM app_secrets WHERE ref = ? AND provider = ? LIMIT 1');
        $stmt->execute([$ref, $this->provider]);
        return (bool) $stmt->fetchColumn();
    }

    public function health(): array {
        try {
            $this->db->query('SELECT 1')->fetchColumn();
            return ['ok' => true, 'provider' => $this->provider, 'key' => self::masterKeyStatus($this->provider)];
        } catch (Throwable $e) {
            return ['ok' => false, 'provider' => $this->provider, 'error' => $e->getMessage()];
        }
    }

    public static function masterKeyStatus(string $provider = 'local'): array {
        $source = self::findMasterKeySource($provider);
        return [
            'configured' => $source['value'] !== '',
            'source' => $source['label'],
            'fallback' => $source['fallback'],
            'warning' => $source['fallback'] ? 'Cle fallback sous data/secrets: dev seulement, deconseille en production.' : '',
        ];
    }

    private static function masterKey(string $provider = 'local'): string {
        if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt') && !function_exists('openssl_encrypt')) {
            throw new RuntimeException('Extension PHP sodium ou openssl requise pour les secrets chiffres.');
        }
        $source = self::findMasterKeySource($provider);
        if ($source['value'] === '') {
            if ($provider === 'agent') {
                throw new RuntimeException('Cle maitre du secret broker absente.');
            }
            $source = self::createFallbackMasterKey();
        }
        return self::decodeMasterKey($source['value'], $source['label']);
    }

    private static function findMasterKeySource(string $provider): array {
        if ($provider === 'agent') {
            $env = SecretStore::env('FULGURITE_SECRET_AGENT_KEY', '');
            if ($env !== '') {
                return ['value' => trim($env), 'label' => 'FULGURITE_SECRET_AGENT_KEY', 'fallback' => false];
            }
            $path = SecretStore::env('FULGURITE_SECRET_AGENT_KEY_FILE', '/etc/fulgurite/secret-agent.key');
            if (is_file($path) && is_readable($path)) {
                return ['value' => trim((string) file_get_contents($path)), 'label' => $path, 'fallback' => false];
            }
            return ['value' => '', 'label' => '', 'fallback' => false];
        }
        $env = SecretStore::env('FULGURITE_SECRET_KEY', '');
        if ($env !== '') {
            return ['value' => trim($env), 'label' => 'FULGURITE_SECRET_KEY', 'fallback' => false];
        }
        foreach (['/run/secrets/fulgurite_secret_key', '/etc/fulgurite/secret.key'] as $path) {
            if (is_file($path) && is_readable($path)) {
                return ['value' => trim((string) file_get_contents($path)), 'label' => $path, 'fallback' => false];
            }
        }
        $fallback = dirname(Database::dbPath()) . '/' . self::FALLBACK_KEY_FILE;
        if (is_file($fallback) && is_readable($fallback)) {
            return ['value' => trim((string) file_get_contents($fallback)), 'label' => $fallback, 'fallback' => true];
        }
        return ['value' => '', 'label' => '', 'fallback' => false];
    }

    private static function createFallbackMasterKey(): array {
        $path = dirname(Database::dbPath()) . '/' . self::FALLBACK_KEY_FILE;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        $value = SecretStore::generateKey();
        if (@file_put_contents($path, $value . "\n", LOCK_EX) === false) {
            throw new RuntimeException('Impossible de creer la cle fallback du SecretStore.');
        }
        @chmod($path, 0600);
        error_log('Fulgurite security warning: fallback local encrypted SecretStore is enabled. Use fulgurite-secret-agent in production.');
        return ['value' => $value, 'label' => $path, 'fallback' => true];
    }

    private static function decodeMasterKey(string $value, string $source): string {
        $value = trim($value);
        $key = preg_match('/^[a-f0-9]{64}$/i', $value) === 1 ? hex2bin($value) : base64_decode($value, true);
        if ($key === false && strlen($value) === self::KEY_BYTES) {
            $key = $value;
        }
        if (!is_string($key) || strlen($key) !== self::KEY_BYTES) {
            throw new RuntimeException("Cle maitre invalide ({$source}). Utilisez 32 octets base64 ou 64 caracteres hex.");
        }
        return $key;
    }

    private static function preferredAlgorithm(): string {
        if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
            return self::ALGORITHM_SODIUM;
        }
        if (function_exists('openssl_encrypt')) {
            return self::ALGORITHM_OPENSSL;
        }
        throw new RuntimeException('Aucun moteur de chiffrement disponible.');
    }

    private static function encrypt(string $plaintext, string $ref, string $algorithm, string $nonce, string $key): string {
        $aad = $ref . "\n" . $algorithm;
        if ($algorithm === self::ALGORITHM_SODIUM) {
            return sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $key);
        }
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
        if ($ciphertext === false || $tag === '') {
            throw new RuntimeException('Echec du chiffrement.');
        }
        return $ciphertext . $tag;
    }

    private static function decrypt(string $payload, string $ref, string $algorithm, string $nonce, string $key): string|false {
        $aad = $ref . "\n" . $algorithm;
        if ($algorithm === self::ALGORITHM_SODIUM) {
            return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($payload, $aad, $nonce, $key);
        }
        if (strlen($payload) < 16) {
            return false;
        }
        return openssl_decrypt(substr($payload, 0, -16), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, substr($payload, -16), $aad);
    }

    private function assertRef(string $ref): void {
        $provider = preg_quote($this->provider, '#');
        if (preg_match('#^secret://'. $provider. '/[a-z0-9_-]+/[1-9][0-9]*/[a-z0-9_-]+$#', $ref) !== 1) {
            throw new InvalidArgumentException('Reference de secret invalide.');
        }
    }
}

final class InfisicalSecretProvider implements SecretProvider {
    public function put(string $ref, string $value, array $metadata = []): string {
        throw new RuntimeException('Ecriture Infisical non supportee par Fulgurite.');
    }

    public function get(string $ref, string $purpose = 'runtime', array $context = []): ?string {
        $name = rawurldecode(substr($ref, strlen('secret://infisical/')));
        return InfisicalClient::getSecret($name);
    }

    public function delete(string $ref): void {}

    public function exists(string $ref): bool {
        return $this->get($ref) !== null;
    }

    public function health(): array {
        return ['ok' => InfisicalClient::isConfigured(), 'provider' => 'infisical'];
    }
}
