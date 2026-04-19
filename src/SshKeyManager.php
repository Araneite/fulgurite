<?php
// =============================================================================
// SshKeyManager.php — management of keys SSH and of the trust host key
// =============================================================================

class SshKeyManager {
    public const HOST_KEY_VALID = 'HOST_KEY_VALID';
    public const HOST_KEY_UNKNOWN = 'HOST_KEY_UNKNOWN';
    public const HOST_KEY_CHANGED = 'HOST_KEY_CHANGED';
    public const HOST_KEY_PENDING_APPROVAL = 'HOST_KEY_PENDING_APPROVAL';

    private static string $keysDir = '';

    private static function keysDir(): string {
        if (self::$keysDir === '') {
            self::$keysDir = dirname(DB_PATH) . '/ssh_keys';
            if (!is_dir(self::$keysDir)) {
                mkdir(self::$keysDir, 0700, true);
            }
        }
        return self::$keysDir;
    }

    private static function sshSecretRef(string $type, int $id, string $name): string {
        return SecretStore::writableRef($type, $id, $name);
    }

    private static function sshKeyRef(int $id): string {
        return self::sshSecretRef('ssh_key', $id, 'private');
    }

    private static function hostKeyRef(int $id): string {
        return self::sshSecretRef('ssh_host_key', $id, 'approved');
    }

    private static function detectedHostKeyRef(int $id): string {
        return self::sshSecretRef('ssh_host_key', $id, 'detected');
    }

    private static function normalizeHost(string $host): string {
        $host = trim($host);
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }
        return trim($host);
    }

    private static function normalizePort(int $port): int {
        return $port > 0 ? $port : 22;
    }

    private static function normalizePrivateKey(string $content): string {
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        return rtrim($content, "\n") . "\n";
    }

    private static function normalizePublicKey(string $publicKey): string {
        $publicKey = trim(str_replace(["\r\n", "\r"], "\n", $publicKey));
        $publicKey = strtok($publicKey, "\n") ?: '';
        if ($publicKey === '') {
            throw new InvalidArgumentException('Cle publique SSH vide.');
        }

        $parts = preg_split('/\s+/', $publicKey, 4);
        if (!$parts || count($parts) < 2) {
            throw new InvalidArgumentException('Format de cle publique SSH invalide.');
        }

        if (!self::isSupportedHostKeyType((string) $parts[0]) && isset($parts[1]) && self::isSupportedHostKeyType((string) $parts[1])) {
            array_shift($parts);
        }

        $type = (string) ($parts[0] ?? '');
        $material = (string) ($parts[1] ?? '');
        $comment = trim(implode(' ', array_slice($parts, 2)));
        if (!self::isSupportedHostKeyType($type) || $material === '') {
            throw new InvalidArgumentException('Type de cle publique SSH non supporte.');
        }

        return $type . ' ' . $material . ($comment !== '' ? ' ' . $comment : '');
    }

    private static function sshKeygenBinary(): string {
        $binary = ProcessRunner::locateBinary('ssh-keygen', ['/usr/bin/ssh-keygen', '/usr/local/bin/ssh-keygen']);
        return $binary !== '' ? $binary : 'ssh-keygen';
    }

    private static function writeTempPrivateKeyFile(string $content): string {
        return Restic::writeTempSecretFile(self::normalizePrivateKey($content), 'ssh_key_');
    }

    private static function writeTempPublicKeyFile(string $publicKey): string {
        $tempFile = Restic::writeTempSecretFile($publicKey, 'ssh_pub_');
        $pubFile = $tempFile . '.pub';

        if (@rename($tempFile, $pubFile)) {
            return $pubFile;
        }

        if (@copy($tempFile, $pubFile)) {
            @unlink($tempFile);
            @chmod($pubFile, 0600);
            return $pubFile;
        }

        @unlink($tempFile);
        throw new RuntimeException('Impossible de preparer le fichier temporaire de cle publique.');
    }

    private static function createTempHome(): ?string {
        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $tmpHome = $base . DIRECTORY_SEPARATOR . 'fulgurite-ssh-' . bin2hex(random_bytes(8));

        if (!mkdir($tmpHome . DIRECTORY_SEPARATOR . '.ssh', 0700, true)) {
            return null;
        }

        chmod($tmpHome, 0700);
        chmod($tmpHome . DIRECTORY_SEPARATOR . '.ssh', 0700);
        return $tmpHome;
    }

    private static function secretValue(?string $ref, string $purpose = 'runtime', array $context = []): ?string {
        $ref = trim((string) $ref);
        if ($ref === '' || !str_starts_with($ref, 'secret://')) {
            return null;
        }

        $value = SecretStore::get($ref, $purpose, $context);
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    private static function deleteSecretValue(?string $ref): void {
        $ref = trim((string) $ref);
        if ($ref === '' || !str_starts_with($ref, 'secret://')) {
            return;
        }

        SecretStore::delete($ref);
    }

    private static function removeTempHome(string $tmpHome): void {
        $base = realpath(sys_get_temp_dir());
        $target = realpath($tmpHome);
        if ($base === false || $target === false) {
            return;
        }

        $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'fulgurite-ssh-';
        if (!str_starts_with($target, $prefix) || !is_dir($target)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isLink() || $item->isFile()) {
                @unlink($item->getPathname());
            } elseif ($item->isDir()) {
                @rmdir($item->getPathname());
            }
        }

        @rmdir($target);
    }

    private static function hostKeyStatusSort(string $status): int {
        return match ($status) {
            self::HOST_KEY_CHANGED => 0,
            self::HOST_KEY_PENDING_APPROVAL => 1,
            self::HOST_KEY_UNKNOWN => 2,
            default => 3,
        };
    }

    private static function isSupportedHostKeyType(string $type): bool {
        return (bool) preg_match('/^(ssh-ed25519|ssh-rsa|ecdsa-sha2-nistp(?:256|384|521))$/', trim($type));
    }

    private static function now(): string {
        return gmdate('Y-m-d H:i:s');
    }

    private static function encodeHostKeyLogDetails(array $details): string {
        $json = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json === false ? implode(' | ', array_map(static fn($k, $v): string => $k . '=' . (string) $v, array_keys($details), $details)) : $json;
    }

    private static function logHostKeyEvent(string $action, array $details, string $severity = 'info'): void {
        Auth::log($action, self::encodeHostKeyLogDetails($details), $severity);
    }

    private static function fingerprintMismatchMessage(string $expectedFingerprint, string $actualFingerprint): string {
        $message = 'La cle fournie ne correspond pas a la fingerprint detectee.';
        if ($expectedFingerprint !== '') {
            $message .= ' Fingerprint attendue: ' . $expectedFingerprint . '.';
        }
        if ($actualFingerprint !== '') {
            $message .= ' Fingerprint fournie: ' . $actualFingerprint . '.';
        }
        $message .= ' N utilisez pas la cle publique client de la section "Cles configurees" : ici il faut la host key du serveur distant.';
        return $message;
    }

    private static function persistDetectedHostKey(string $host, int $port, string $publicKey, string $context): array {
        $analysis = self::analyzeHostPublicKey($publicKey);
        $record = self::ensureHostTrustRecord($host, $port);
        $ref = !empty($record['detected_key_ref']) && str_starts_with((string) $record['detected_key_ref'], 'secret://')
            ? (string) $record['detected_key_ref']
            : self::detectedHostKeyRef((int) $record['id']);

        SecretStore::put($ref, $analysis['normalized'], [
            'entity_type' => 'ssh_host_key',
            'entity_id' => (int) $record['id'],
            'host' => $host,
            'port' => $port,
            'fingerprint' => $analysis['fingerprint'],
            'key_type' => $analysis['type'],
            'action' => 'detect',
            'context' => $context,
        ]);

        self::updateHostTrustRecord((int) $record['id'], [
            'detected_key_ref' => $ref,
            'detected_key_type' => $analysis['type'],
            'detected_fingerprint' => $analysis['fingerprint'],
            'last_context' => trim($context),
            'last_seen_at' => self::now(),
        ]);

        $updated = self::getHostTrustRecord($host, $port) ?: $record;
        $updated['detected_public_key'] = $analysis['normalized'];
        return $updated;
    }

    private static function resolvedHostKeySubmission(array $record, string $publicKey): array {
        $publicKey = trim($publicKey);
        $stored = self::secretValue((string) ($record['detected_key_ref'] ?? ''), 'runtime', [
            'scope' => 'ssh_host_key',
            'host' => (string) ($record['host'] ?? ''),
            'port' => (int) ($record['port'] ?? 22),
            'kind' => 'detected',
        ]);
        $expectedFingerprint = trim((string) ($record['detected_fingerprint'] ?? ''));

        if ($publicKey !== '') {
            $submitted = self::analyzeHostPublicKey($publicKey);
            if ($expectedFingerprint === '' || hash_equals($expectedFingerprint, $submitted['fingerprint']) || $stored === null) {
                return $submitted;
            }

            $detected = self::analyzeHostPublicKey($stored);
            if (hash_equals($expectedFingerprint, $detected['fingerprint'])) {
                return $detected;
            }

            return $submitted;
        }

        if ($stored === null) {
            throw new RuntimeException('Aucune host key detectee n est disponible. Chargez d abord la host key du serveur depuis l interface ou collez la host key verifiee manuellement.');
        }

        return self::analyzeHostPublicKey($stored);
    }

    private static function notifyHostKeyReview(array $record, string $event, string $severity): void {
        $title = $event === self::HOST_KEY_CHANGED
            ? 'Host key SSH modifiee - ' . $record['host'] . ':' . $record['port']
            : 'Host key SSH a approuver - ' . $record['host'] . ':' . $record['port'];

        $body = "**Host** : {$record['host']}\n"
            . "**Port** : {$record['port']}\n"
            . "**Statut** : {$event}\n"
            . (!empty($record['detected_key_type']) ? "**Type** : {$record['detected_key_type']}\n" : '')
            . (!empty($record['detected_fingerprint']) ? "**Fingerprint** : {$record['detected_fingerprint']}\n" : '')
            . (!empty($record['previous_fingerprint']) ? "**Ancienne fingerprint** : {$record['previous_fingerprint']}\n" : '')
            . (!empty($record['last_context']) ? "**Contexte** : {$record['last_context']}\n" : '');

        AppNotificationManager::store($title, trim($body), [
            'profile_key' => 'security',
            'event_key' => strtolower($event),
            'context_type' => 'ssh_host_key',
            'context_id' => (int) ($record['id'] ?? 0),
            'context_name' => $record['host'] . ':' . $record['port'],
            'severity' => $severity,
            'recipient_permission' => 'ssh_host_key.approve',
            'link_url' => routePath('/sshkeys.php'),
        ]);
    }

    private static function hostTrustRecordRaw(string $host, int $port): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM ssh_host_trust WHERE host = ? AND port = ?');
        $stmt->execute([self::normalizeHost($host), self::normalizePort($port)]);
        return $stmt->fetch() ?: null;
    }

    private static function ensureHostTrustRecord(string $host, int $port): array {
        $host = self::normalizeHost($host);
        $port = self::normalizePort($port);
        $record = self::hostTrustRecordRaw($host, $port);
        if ($record) {
            return $record;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO ssh_host_trust (host, port, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $now = self::now();
        $stmt->execute([$host, $port, self::HOST_KEY_UNKNOWN, $now, $now]);
        return self::hostTrustRecordRaw($host, $port) ?: [
            'id' => (int) $db->lastInsertId(),
            'host' => $host,
            'port' => $port,
            'status' => self::HOST_KEY_UNKNOWN,
        ];
    }

    private static function updateHostTrustRecord(int $id, array $fields): void {
        if ($fields === []) {
            return;
        }

        $assignments = [];
        $params = [];
        foreach ($fields as $column => $value) {
            $assignments[] = $column . ' = ?';
            $params[] = $value;
        }
        $assignments[] = 'updated_at = ?';
        $params[] = self::now();
        $params[] = $id;

        $sql = 'UPDATE ssh_host_trust SET ' . implode(', ', $assignments) . ' WHERE id = ?';
        Database::getInstance()->prepare($sql)->execute($params);
    }

    public static function getTemporaryKeyFile(int $id): string {
        $key = self::getById($id);
        if (!$key) {
            throw new RuntimeException("Cle SSH #$id introuvable.");
        }

        $ref = (string) ($key['private_key_file'] ?? '');
        if (!str_starts_with($ref, 'secret://')) {
            if (is_file($ref)) {
                return $ref;
            }
            throw new RuntimeException("Cle SSH #$id non disponible.");
        }

        $content = SecretStore::get($ref, 'runtime', ['scope' => 'ssh_key', 'ssh_key_id' => $id]);
        if (!$content) {
            throw new RuntimeException("Contenu de la cle SSH #$id introuvable dans le SecretStore.");
        }

        return self::writeTempPrivateKeyFile($content);
    }

    public static function getAll(): array {
        return Database::getInstance()->query('SELECT * FROM ssh_keys ORDER BY name')->fetchAll();
    }

    public static function getById(int $id): ?array {
        $stmt = Database::getInstance()->prepare('SELECT * FROM ssh_keys WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function generate(
        string $name,
        string $host,
        string $user,
        int $port,
        string $description = ''
    ): array {
        $slug = preg_replace('/[^a-z0-9_-]/', '_', strtolower($name));
        $keyFile = self::keysDir() . '/' . $slug . '_' . substr(md5($name . time()), 0, 8);

        $result = ProcessRunner::run([
            self::sshKeygenBinary(),
            '-t', 'ed25519',
            '-f', $keyFile,
            '-N', '',
            '-C', "fulgurite-$slug@$host",
        ], ['env' => ['HOME' => '/tmp']]);

        if (!$result['success'] && trim((string) ($result['output'] ?? '')) === 'Impossible de lancer la commande') {
            return ['success' => false, 'error' => 'Impossible de lancer ssh-keygen'];
        }

        $stdout = (string) ($result['stdout'] ?? '');
        $stderr = (string) ($result['stderr'] ?? '');
        $code = (int) ($result['code'] ?? 1);
        if ($code !== 0) {
            return ['success' => false, 'error' => trim($stderr ?: $stdout)];
        }

        $privateKey = file_get_contents($keyFile);
        if ($privateKey === false) {
            @unlink($keyFile);
            @unlink($keyFile . '.pub');
            return ['success' => false, 'error' => 'Impossible de lire la cle privee generee.'];
        }
        $privateKey = self::normalizePrivateKey($privateKey);
        $pubKey = trim((string) file_get_contents($keyFile . '.pub'));

        $db = Database::getInstance();
        $db->prepare("
            INSERT INTO ssh_keys (name, host, user, port, private_key_file, public_key, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$name, $host, $user, $port, 'placeholder', $pubKey, $description]);

        $id = (int) $db->lastInsertId();
        $ref = self::sshKeyRef($id);
        SecretStore::put($ref, $privateKey, [
            'entity_type' => 'ssh_key',
            'entity_id' => $id,
            'name' => $name,
            'host' => $host,
            'port' => self::normalizePort($port),
        ]);
        $db->prepare('UPDATE ssh_keys SET private_key_file = ? WHERE id = ?')->execute([$ref, $id]);

        @unlink($keyFile);
        @unlink($keyFile . '.pub');

        return [
            'success' => true,
            'id' => $id,
            'public_key' => $pubKey,
            'key_file' => $ref,
        ];
    }

    public static function import(
        string $name,
        string $host,
        string $user,
        int $port,
        string $privateKeyContent,
        string $description = ''
    ): array {
        $slug = preg_replace('/[^a-z0-9_-]/', '_', strtolower($name));
        $keyFile = self::keysDir() . '/' . $slug . '_' . substr(md5($name . time()), 0, 8);

        $privateKeyContent = self::normalizePrivateKey($privateKeyContent);
        file_put_contents($keyFile, $privateKeyContent);
        chmod($keyFile, 0600);

        $result = ProcessRunner::run([self::sshKeygenBinary(), '-y', '-f', $keyFile], ['env' => ['HOME' => '/tmp']]);
        $pubKey = trim((string) ($result['stdout'] ?? ''));
        if (empty($result['success']) || $pubKey === '') {
            @unlink($keyFile);
            return ['success' => false, 'error' => trim((string) ($result['output'] ?? 'Import de cle invalide'))];
        }

        $db = Database::getInstance();
        $db->prepare("
            INSERT INTO ssh_keys (name, host, user, port, private_key_file, public_key, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$name, $host, $user, $port, 'placeholder', $pubKey, $description]);

        $id = (int) $db->lastInsertId();
        $ref = self::sshKeyRef($id);
        SecretStore::put($ref, $privateKeyContent, [
            'entity_type' => 'ssh_key',
            'entity_id' => $id,
            'name' => $name,
            'host' => $host,
            'port' => self::normalizePort($port),
        ]);
        $db->prepare('UPDATE ssh_keys SET private_key_file = ? WHERE id = ?')->execute([$ref, $id]);
        @unlink($keyFile);

        return [
            'success' => true,
            'id' => $id,
            'public_key' => $pubKey,
        ];
    }

    public static function delete(int $id): void {
        $db = Database::getInstance();
        $key = self::getById($id);
        if ($key) {
            $ref = (string) ($key['private_key_file'] ?? '');
            if (str_starts_with($ref, 'secret://')) {
                SecretStore::delete($ref);
            } elseif (is_file($ref)) {
                @unlink($ref);
            }
            if (is_file($ref . '.pub')) {
                @unlink($ref . '.pub');
            }
        }
        $db->prepare('DELETE FROM ssh_keys WHERE id = ?')->execute([$id]);
    }

    public static function analyzeHostPublicKey(string $publicKey): array {
        $normalized = self::normalizePublicKey($publicKey);
        $parts = preg_split('/\s+/', $normalized, 3);
        $type = (string) ($parts[0] ?? '');
        $tempPath = self::writeTempPublicKeyFile($normalized);
        try {
            $result = ProcessRunner::run([self::sshKeygenBinary(), '-lf', $tempPath, '-E', 'sha256'], ['env' => ['HOME' => '/tmp']]);
        } finally {
            @unlink($tempPath);
        }

        $output = trim((string) ($result['stdout'] ?? ($result['output'] ?? '')));
        if (empty($result['success']) || !preg_match('/\b(SHA256:[A-Za-z0-9+\/=]+)\b/', $output, $matches)) {
            throw new InvalidArgumentException('Impossible de calculer la fingerprint de la cle publique SSH.');
        }

        return [
            'normalized' => $normalized,
            'type' => $type,
            'fingerprint' => (string) $matches[1],
        ];
    }

    public static function getHostTrustRecord(string $host, int $port): ?array {
        $record = self::hostTrustRecordRaw($host, $port);
        if (!$record) {
            return null;
        }
        $record['host'] = self::normalizeHost((string) ($record['host'] ?? ''));
        $record['port'] = self::normalizePort((int) ($record['port'] ?? 22));
        $record['approved_public_key'] = self::secretValue((string) ($record['approved_key_ref'] ?? ''), 'runtime', [
            'scope' => 'ssh_host_key',
            'host' => (string) $record['host'],
            'port' => (int) $record['port'],
            'kind' => 'approved',
        ]);
        $record['detected_public_key'] = self::secretValue((string) ($record['detected_key_ref'] ?? ''), 'runtime', [
            'scope' => 'ssh_host_key',
            'host' => (string) $record['host'],
            'port' => (int) $record['port'],
            'kind' => 'detected',
        ]);
        return $record;
    }

    public static function getAllHostTrust(): array {
        $rows = Database::getInstance()
            ->query('SELECT * FROM ssh_host_trust ORDER BY updated_at DESC, host ASC, port ASC')
            ->fetchAll();

        usort($rows, static function (array $left, array $right): int {
            $sort = self::hostKeyStatusSort((string) ($left['status'] ?? ''))
                <=> self::hostKeyStatusSort((string) ($right['status'] ?? ''));
            if ($sort !== 0) {
                return $sort;
            }

            $timeSort = strcmp((string) ($right['updated_at'] ?? ''), (string) ($left['updated_at'] ?? ''));
            if ($timeSort !== 0) {
                return $timeSort;
            }

            return strcmp((string) ($left['host'] ?? ''), (string) ($right['host'] ?? ''));
        });

        return $rows;
    }

    public static function isApprovedHostKey(string $host, int $port): bool {
        $record = self::getHostTrustRecord($host, $port);
        return !empty($record['approved_key_ref']) && !empty($record['approved_fingerprint']);
    }

    public static function approvedHostKeyMaterial(string $host, int $port): ?array {
        $record = self::getHostTrustRecord($host, $port);
        if (!$record || empty($record['approved_key_ref'])) {
            return null;
        }

        $publicKey = SecretStore::get((string) $record['approved_key_ref'], 'runtime', [
            'scope' => 'ssh_host_key',
            'host' => (string) $record['host'],
            'port' => (int) $record['port'],
        ]);
        if (!$publicKey) {
            return null;
        }

        return [
            'id' => (int) $record['id'],
            'host' => (string) $record['host'],
            'port' => (int) $record['port'],
            'public_key' => trim($publicKey),
            'type' => (string) ($record['approved_key_type'] ?? ''),
            'fingerprint' => (string) ($record['approved_fingerprint'] ?? ''),
            'status' => (string) ($record['status'] ?? self::HOST_KEY_VALID),
        ];
    }

    public static function allApprovedHostKeyMaterials(): array {
        $stmt = Database::getInstance()->query("
            SELECT *
            FROM ssh_host_trust
            WHERE approved_key_ref IS NOT NULL
              AND approved_key_ref != ''
              AND approved_fingerprint IS NOT NULL
              AND approved_fingerprint != ''
            ORDER BY host ASC, port ASC
        ");

        $entries = [];
        foreach ($stmt->fetchAll() as $row) {
            $material = self::approvedHostKeyMaterial((string) $row['host'], (int) $row['port']);
            if ($material) {
                $entries[] = $material;
            }
        }
        return $entries;
    }

    public static function recordDetectedHostKey(
        string $host,
        int $port,
        string $status,
        string $keyType,
        string $fingerprint,
        string $context,
        ?string $previousFingerprint = null
    ): array {
        $host = self::normalizeHost($host);
        $port = self::normalizePort($port);
        $record = self::ensureHostTrustRecord($host, $port);

        $storedStatus = $status === self::HOST_KEY_UNKNOWN
            ? self::HOST_KEY_PENDING_APPROVAL
            : $status;
        $previousFingerprint = $previousFingerprint !== null && trim($previousFingerprint) !== ''
            ? trim($previousFingerprint)
            : (string) ($record['approved_fingerprint'] ?? '');
        $existingDetectedRef = (string) ($record['detected_key_ref'] ?? '');
        $existingDetectedFingerprint = trim((string) ($record['detected_fingerprint'] ?? ''));
        $keepDetectedRef = $existingDetectedRef !== ''
            && $existingDetectedFingerprint !== ''
            && hash_equals($existingDetectedFingerprint, trim($fingerprint));
        $newDetails = [
            'status' => $storedStatus,
            'detected_key_ref' => $keepDetectedRef ? $existingDetectedRef : null,
            'detected_key_type' => trim($keyType),
            'detected_fingerprint' => trim($fingerprint),
            'previous_fingerprint' => $previousFingerprint !== '' ? $previousFingerprint : null,
            'last_context' => trim($context),
            'last_seen_at' => self::now(),
        ];

        self::updateHostTrustRecord((int) $record['id'], $newDetails);
        if (!$keepDetectedRef) {
            self::deleteSecretValue($existingDetectedRef);
        }
        $updated = self::getHostTrustRecord($host, $port) ?: array_merge($record, $newDetails);

        $logDetails = [
            'host' => $host,
            'port' => $port,
            'fingerprint' => trim($fingerprint),
            'old_fingerprint' => $previousFingerprint,
            'key_type' => trim($keyType),
            'status' => $status,
            'context' => trim($context),
            'user_id' => $_SESSION['user_id'] ?? null,
        ];

        if ($status === self::HOST_KEY_CHANGED) {
            self::logHostKeyEvent('HOST_KEY_CHANGED', $logDetails, 'critical');
            self::notifyHostKeyReview($updated, self::HOST_KEY_CHANGED, 'warning');
        } else {
            self::logHostKeyEvent('HOST_KEY_UNKNOWN', $logDetails, 'warning');
            self::notifyHostKeyReview($updated, self::HOST_KEY_UNKNOWN, 'warning');
        }

        return $updated;
    }

    public static function markHostKeyValidated(string $host, int $port, string $context = ''): void {
        $record = self::getHostTrustRecord($host, $port);
        if (!$record || empty($record['approved_key_ref'])) {
            return;
        }

        self::updateHostTrustRecord((int) $record['id'], [
            'status' => self::HOST_KEY_VALID,
            'detected_key_ref' => null,
            'detected_key_type' => null,
            'detected_fingerprint' => null,
            'previous_fingerprint' => null,
            'last_context' => trim($context),
            'last_seen_at' => self::now(),
        ]);
        self::deleteSecretValue((string) ($record['detected_key_ref'] ?? ''));
    }

    public static function approveHostKey(string $host, int $port, string $publicKey): void {
        $host = self::normalizeHost($host);
        $port = self::normalizePort($port);
        $record = self::ensureHostTrustRecord($host, $port);
        if (!empty($record['approved_key_ref']) && !empty($record['approved_fingerprint'])) {
            throw new RuntimeException('Une host key est deja approuvee pour cet hote. Utilisez le remplacement explicite.');
        }

        $analysis = self::resolvedHostKeySubmission($record, $publicKey);
        $expectedFingerprint = trim((string) ($record['detected_fingerprint'] ?? ''));
        if ($expectedFingerprint !== '' && !hash_equals($expectedFingerprint, $analysis['fingerprint'])) {
            throw new RuntimeException(self::fingerprintMismatchMessage($expectedFingerprint, $analysis['fingerprint']));
        }

        $ref = !empty($record['approved_key_ref']) && str_starts_with((string) $record['approved_key_ref'], 'secret://')
            ? (string) $record['approved_key_ref']
            : self::hostKeyRef((int) $record['id']);

        SecretStore::put($ref, $analysis['normalized'], [
            'entity_type' => 'ssh_host_key',
            'entity_id' => (int) $record['id'],
            'host' => $host,
            'port' => $port,
            'fingerprint' => $analysis['fingerprint'],
            'key_type' => $analysis['type'],
            'action' => 'approve',
        ]);

        self::updateHostTrustRecord((int) $record['id'], [
            'approved_key_ref' => $ref,
            'approved_key_type' => $analysis['type'],
            'approved_fingerprint' => $analysis['fingerprint'],
            'detected_key_ref' => null,
            'detected_key_type' => null,
            'detected_fingerprint' => null,
            'previous_fingerprint' => null,
            'status' => self::HOST_KEY_VALID,
            'last_context' => 'approval',
            'last_seen_at' => self::now(),
            'approved_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
            'approved_at' => self::now(),
        ]);
        self::deleteSecretValue((string) ($record['detected_key_ref'] ?? ''));

        self::logHostKeyEvent('HOST_KEY_APPROVED', [
            'host' => $host,
            'port' => $port,
            'fingerprint' => $analysis['fingerprint'],
            'key_type' => $analysis['type'],
            'context' => 'approval',
            'user_id' => $_SESSION['user_id'] ?? null,
        ]);
    }

    public static function replaceHostKey(string $host, int $port, string $newKey): void {
        $host = self::normalizeHost($host);
        $port = self::normalizePort($port);
        $record = self::ensureHostTrustRecord($host, $port);
        if (empty($record['approved_key_ref']) || empty($record['approved_fingerprint'])) {
            throw new RuntimeException('Aucune host key approuvee n existe encore pour cet hote.');
        }

        $analysis = self::resolvedHostKeySubmission($record, $newKey);
        $expectedFingerprint = trim((string) ($record['detected_fingerprint'] ?? ''));
        if ($expectedFingerprint !== '' && !hash_equals($expectedFingerprint, $analysis['fingerprint'])) {
            throw new RuntimeException(self::fingerprintMismatchMessage($expectedFingerprint, $analysis['fingerprint']));
        }

        $ref = (string) $record['approved_key_ref'];
        SecretStore::put($ref, $analysis['normalized'], [
            'entity_type' => 'ssh_host_key',
            'entity_id' => (int) $record['id'],
            'host' => $host,
            'port' => $port,
            'fingerprint' => $analysis['fingerprint'],
            'key_type' => $analysis['type'],
            'action' => 'replace',
        ]);

        $oldFingerprint = (string) ($record['approved_fingerprint'] ?? '');
        self::updateHostTrustRecord((int) $record['id'], [
            'approved_key_type' => $analysis['type'],
            'approved_fingerprint' => $analysis['fingerprint'],
            'detected_key_ref' => null,
            'detected_key_type' => null,
            'detected_fingerprint' => null,
            'previous_fingerprint' => null,
            'status' => self::HOST_KEY_VALID,
            'last_context' => 'replacement',
            'last_seen_at' => self::now(),
            'approved_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
            'approved_at' => self::now(),
        ]);
        self::deleteSecretValue((string) ($record['detected_key_ref'] ?? ''));

        self::logHostKeyEvent('HOST_KEY_REPLACED', [
            'host' => $host,
            'port' => $port,
            'fingerprint' => $analysis['fingerprint'],
            'old_fingerprint' => $oldFingerprint,
            'key_type' => $analysis['type'],
            'context' => 'replacement',
            'user_id' => $_SESSION['user_id'] ?? null,
        ], 'warning');
    }

    public static function rejectHostKey(string $host, int $port): void {
        $record = self::getHostTrustRecord($host, $port);
        if (!$record) {
            return;
        }

        $status = !empty($record['approved_key_ref']) ? self::HOST_KEY_CHANGED : self::HOST_KEY_UNKNOWN;
        self::updateHostTrustRecord((int) $record['id'], [
            'status' => $status,
            'last_context' => 'rejected',
            'last_seen_at' => self::now(),
        ]);

        self::logHostKeyEvent('HOST_KEY_REJECTED', [
            'host' => (string) $record['host'],
            'port' => (int) $record['port'],
            'fingerprint' => (string) ($record['detected_fingerprint'] ?? ''),
            'old_fingerprint' => (string) ($record['previous_fingerprint'] ?? ''),
            'key_type' => (string) ($record['detected_key_type'] ?? ''),
            'context' => 'rejected',
            'user_id' => $_SESSION['user_id'] ?? null,
        ], 'warning');
    }

    public static function fetchDetectedHostKey(string $host, int $port): array {
        $host = self::normalizeHost($host);
        $port = self::normalizePort($port);
        $record = self::ensureHostTrustRecord($host, $port);
        $expectedFingerprint = trim((string) ($record['detected_fingerprint'] ?? ''));
        $publicKey = SshKnownHosts::fetchHostPublicKeyExplicitly($host, $port, $expectedFingerprint !== '' ? $expectedFingerprint : null);
        $updated = self::persistDetectedHostKey($host, $port, $publicKey, 'explicit_fetch');

        self::logHostKeyEvent('HOST_KEY_DETECTED', [
            'host' => $host,
            'port' => $port,
            'fingerprint' => (string) ($updated['detected_fingerprint'] ?? ''),
            'old_fingerprint' => (string) ($updated['previous_fingerprint'] ?? ''),
            'key_type' => (string) ($updated['detected_key_type'] ?? ''),
            'context' => 'explicit_fetch',
            'user_id' => $_SESSION['user_id'] ?? null,
        ]);

        return $updated;
    }

    public static function test(int $id): array {
        $key = self::getById($id);
        if (!$key) {
            return ['success' => false, 'output' => 'Cle introuvable'];
        }

        $tmpKeyFile = self::getTemporaryKeyFile($id);
        try {
            $result = Restic::testSshConnection(
                (string) $key['user'],
                (string) $key['host'],
                (int) $key['port'],
                $tmpKeyFile
            );
        } finally {
            @unlink($tmpKeyFile);
        }

        return $result;
    }

    public static function deployKey(int $id, string $password, ?string $hostOverride = null, ?string $userOverride = null, ?int $portOverride = null): array {
        $key = self::getById($id);
        if (!$key) {
            return ['success' => false, 'output' => 'Cle introuvable'];
        }

        $host = trim($hostOverride ?? (string) ($key['host'] ?? ''));
        $user = trim($userOverride ?? (string) ($key['user'] ?? ''));
        $port = $portOverride ?? (int) ($key['port'] ?? 22);
        if ($host === '' || $user === '' || $port <= 0) {
            return ['success' => false, 'output' => 'Cible SSH invalide'];
        }

        try {
            $pubKeyFile = self::writeTempPublicKeyFile((string) ($key['public_key'] ?? ''));
        } catch (Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }

        $tmpHome = self::createTempHome();
        if ($tmpHome === null) {
            @unlink($pubKeyFile);
            return ['success' => false, 'output' => 'Impossible de creer le HOME temporaire SSH'];
        }

        $cmd = array_merge([
            'sshpass', '-d', '0',
            'ssh-copy-id',
            '-f',
            '-i', $pubKeyFile,
            '-p', (string) $port,
        ], SshKnownHosts::sshOptions($host, $port, 10, false), [
            "{$user}@{$host}",
        ]);
        $env = [
            'HOME' => $tmpHome,
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ];

        $raw = ProcessRunner::run($cmd, ['env' => $env, 'stdin' => $password . "\n"]);
        self::removeTempHome($tmpHome);
        @unlink($pubKeyFile);

        if (!$raw['success'] && trim((string) ($raw['output'] ?? '')) === 'Impossible de lancer la commande') {
            return ['success' => false, 'output' => 'Impossible de lancer sshpass'];
        }

        $result = [
            'success' => (int) ($raw['code'] ?? 1) === 0,
            'output' => trim((string) ($raw['stdout'] ?? '') . "\n" . (string) ($raw['stderr'] ?? '')),
            'code' => (int) ($raw['code'] ?? 1),
        ];

        return SshKnownHosts::finalizeSshResult($result, $host, $port, 'ssh_key_deploy');
    }
}
