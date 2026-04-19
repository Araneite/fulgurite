<?php

class SshKnownHosts {
    private const PROBE_TIMEOUT_SECONDS = 8;

    public static function knownHostsFile(): string {
        $dir = dirname(DB_PATH) . '/ssh_known_hosts';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        @chmod($dir, 0700);

        $file = $dir . '/known_hosts';
        $entries = self::renderApprovedEntries();
        $payload = $entries === [] ? '' : implode("\n", $entries) . "\n";
        @file_put_contents($file, $payload, LOCK_EX);
        @chmod($file, 0600);

        return $file;
    }

    public static function sshOptions(string $host, int $port, int $connectTimeout = 10, bool $batchMode = true): array {
        $options = [
            '-o', 'StrictHostKeyChecking=yes',
            '-o', 'ConnectTimeout=' . max(1, $connectTimeout),
            '-o', 'UserKnownHostsFile=' . self::knownHostsFile(),
            '-o', 'GlobalKnownHostsFile=/dev/null',
        ];
        if ($batchMode) {
            array_splice($options, 2, 0, ['-o', 'BatchMode=yes']);
        }
        return $options;
    }

    public static function sshOptionsString(string $host, int $port, int $connectTimeout = 10, bool $batchMode = true): string {
        $parts = self::sshOptions($host, $port, $connectTimeout, $batchMode);
        $chunks = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            $chunks[] = $parts[$i] . ' ' . escapeshellarg($parts[$i + 1] ?? '');
        }
        return implode(' ', $chunks);
    }

    public static function isHostKeyKnown(string $host, int $port): bool {
        return SshKeyManager::isApprovedHostKey($host, $port);
    }

    public static function isHostKeyMatching(string $host, int $port, string $keyType, string $fingerprint): bool {
        $record = SshKeyManager::getHostTrustRecord($host, $port);
        if (!$record) {
            return false;
        }

        return hash_equals((string) ($record['approved_key_type'] ?? ''), trim($keyType))
            && hash_equals((string) ($record['approved_fingerprint'] ?? ''), trim($fingerprint));
    }

    public static function finalizeSshResult(array $result, string $host, int $port, string $context): array {
        $normalized = [
            'success' => !empty($result['success']),
            'output' => trim((string) ($result['output'] ?? '')),
            'code' => (int) ($result['code'] ?? 1),
        ];

        if ($normalized['success']) {
            SshKeyManager::markHostKeyValidated($host, $port, $context);
            return $normalized;
        }

        try {
            self::throwForCommandFailure($host, $port, $normalized['output'], $context);
        } catch (SshHostKeyException $e) {
            $payload = $e->toArray();
            $normalized['output'] = $e->getMessage();
            $normalized['host_key'] = $payload;
            $normalized['status'] = (string) ($payload['status'] ?? '');
        }

        return $normalized;
    }

    public static function throwForCommandFailure(string $host, int $port, string $output, string $context): void {
        $output = trim($output);
        if ($output === '') {
            return;
        }

        $record = SshKeyManager::getHostTrustRecord($host, $port);
        $knownFingerprint = trim((string) ($record['approved_fingerprint'] ?? ''));
        $detected = self::extractPresentedHostKey($output);
        if ($detected === null && self::mayContainHostKeyFailure($output)) {
            $detected = self::probePresentedHostKey($host, $port);
        }

        $changed = self::isChangedHostKeyFailure($output)
            || ($knownFingerprint !== '' && $detected !== null && $detected['fingerprint'] !== '' && !hash_equals($knownFingerprint, $detected['fingerprint']));
        if ($changed) {
            $keyType = (string) ($detected['key_type'] ?? ($record['approved_key_type'] ?? ''));
            $fingerprint = (string) ($detected['fingerprint'] ?? '');
            SshKeyManager::recordDetectedHostKey(
                $host,
                $port,
                SshKeyManager::HOST_KEY_CHANGED,
                $keyType,
                $fingerprint,
                $context,
                $knownFingerprint !== '' ? $knownFingerprint : null
            );

            throw new ChangedHostKeyException(
                self::changedMessage($host, $port, $fingerprint, $knownFingerprint),
                [
                    'status' => SshKeyManager::HOST_KEY_CHANGED,
                    'host' => trim($host),
                    'port' => $port > 0 ? $port : 22,
                    'key_type' => $keyType,
                    'fingerprint' => $fingerprint,
                    'previous_fingerprint' => $knownFingerprint,
                    'context' => $context,
                ]
            );
        }

        $unknown = self::isUnknownHostKeyFailure($output) || !$knownFingerprint;
        if (!$unknown) {
            return;
        }

        $keyType = (string) ($detected['key_type'] ?? '');
        $fingerprint = (string) ($detected['fingerprint'] ?? '');
        SshKeyManager::recordDetectedHostKey(
            $host,
            $port,
            SshKeyManager::HOST_KEY_UNKNOWN,
            $keyType,
            $fingerprint,
            $context
        );

        throw new UnknownHostKeyException(
            self::unknownMessage($host, $port, $fingerprint),
            [
                'status' => SshKeyManager::HOST_KEY_UNKNOWN,
                'host' => trim($host),
                'port' => $port > 0 ? $port : 22,
                'key_type' => $keyType,
                'fingerprint' => $fingerprint,
                'previous_fingerprint' => null,
                'context' => $context,
            ]
        );
    }

    private static function renderApprovedEntries(): array {
        $lines = [];
        foreach (SshKeyManager::allApprovedHostKeyMaterials() as $entry) {
            $publicKey = trim((string) ($entry['public_key'] ?? ''));
            if ($publicKey === '') {
                continue;
            }
            $lines[] = self::knownHostsPattern((string) ($entry['host'] ?? ''), (int) ($entry['port'] ?? 22)) . ' ' . $publicKey;
        }
        return array_values(array_unique($lines));
    }

    private static function knownHostsPattern(string $host, int $port): string {
        $host = trim($host, '[] ');
        $port = $port > 0 ? $port : 22;
        return $port === 22 ? $host : '[' . $host . ']:' . $port;
    }

    private static function unknownMessage(string $host, int $port, string $fingerprint): string {
        $message = 'Host key SSH inconnue pour ' . trim($host) . ':' . ($port > 0 ? $port : 22) . '. Connexion refusee en mode strict.';
        if ($fingerprint !== '') {
            $message .= ' Fingerprint detectee: ' . $fingerprint . '.';
        }
        $message .= ' Une approbation explicite est requise avant de relancer l action.';
        return $message;
    }

    private static function changedMessage(string $host, int $port, string $fingerprint, string $previousFingerprint): string {
        $message = 'La host key SSH de ' . trim($host) . ':' . ($port > 0 ? $port : 22) . ' a change. Connexion stucke.';
        if ($previousFingerprint !== '') {
            $message .= ' Ancienne fingerprint: ' . $previousFingerprint . '.';
        }
        if ($fingerprint !== '') {
            $message .= ' Nouvelle fingerprint: ' . $fingerprint . '.';
        }
        return $message;
    }

    private static function mayContainHostKeyFailure(string $output): bool {
        $output = strtolower($output);
        foreach ([
            'host key verification failed',
            'remote host identification has changed',
            'no host key is known for',
            'offending ',
        ] as $needle) {
            if (str_contains($output, $needle)) {
                return true;
            }
        }
        return false;
    }

    private static function isUnknownHostKeyFailure(string $output): bool {
        $output = strtolower($output);
        return str_contains($output, 'no host key is known for')
            || str_contains($output, 'host key verification failed')
            || str_contains($output, 'host key is not cached');
    }

    private static function isChangedHostKeyFailure(string $output): bool {
        $output = strtolower($output);
        return str_contains($output, 'remote host identification has changed')
            || str_contains($output, 'offending ')
            || str_contains($output, 'host key for ');
    }

    private static function extractPresentedHostKey(string $output): ?array {
        if (preg_match('/Server host key:\s+([^\s]+)\s+(SHA256:[A-Za-z0-9+\/=]+)/i', $output, $matches) === 1) {
            return [
                'key_type' => trim((string) $matches[1]),
                'fingerprint' => trim((string) $matches[2]),
            ];
        }

        if (preg_match('/(ssh-ed25519|ssh-rsa|ecdsa-sha2-nistp(?:256|384|521)).*?(SHA256:[A-Za-z0-9+\/=]+)/i', $output, $matches) === 1) {
            return [
                'key_type' => trim((string) $matches[1]),
                'fingerprint' => trim((string) $matches[2]),
            ];
        }

        if (preg_match('/\b(SHA256:[A-Za-z0-9+\/=]+)\b/', $output, $matches) === 1) {
            return [
                'key_type' => '',
                'fingerprint' => trim((string) $matches[1]),
            ];
        }

        return null;
    }

    public static function fetchHostPublicKeyExplicitly(string $host, int $port, ?string $expectedFingerprint = null): string {
        $binary = ProcessRunner::locateBinary('ssh-keyscan', ['/usr/bin/ssh-keyscan', '/usr/local/bin/ssh-keyscan']);
        if ($binary === '') {
            throw new RuntimeException('ssh-keyscan est indisponible. Fournissez la host key du serveur manuellement.');
        }

        $cmd = [
            $binary,
            '-T', (string) self::PROBE_TIMEOUT_SECONDS,
            '-p', (string) ($port > 0 ? $port : 22),
            trim($host),
        ];
        $result = ProcessRunner::run($cmd, [
            'env' => [
                'PATH' => (string) (getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'),
            ],
            'timeout' => self::PROBE_TIMEOUT_SECONDS,
        ]);

        $lines = preg_split('/\r?\n/', (string) ($result['stdout'] ?? $result['output'] ?? '')) ?: [];
        $candidates = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            try {
                $analysis = SshKeyManager::analyzeHostPublicKey($line);
            } catch (Throwable $e) {
                continue;
            }

            $candidates[] = [
                'public_key' => $analysis['normalized'],
                'key_type' => $analysis['type'],
                'fingerprint' => $analysis['fingerprint'],
            ];
        }

        if ($candidates === []) {
            throw new RuntimeException('Aucune host key exploitable n a pu etre lue pour ce serveur.');
        }

        if ($expectedFingerprint !== null && trim($expectedFingerprint) !== '') {
            foreach ($candidates as $candidate) {
                if (hash_equals(trim($expectedFingerprint), (string) $candidate['fingerprint'])) {
                    return (string) $candidate['public_key'];
                }
            }

            throw new RuntimeException('La host key lue explicitement ne correspond pas a la fingerprint detectee precedemment.');
        }

        return (string) $candidates[0]['public_key'];
    }

    private static function probePresentedHostKey(string $host, int $port): ?array {
        $tmpHome = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'fulgurite-hostkey-' . bin2hex(random_bytes(6));
        @mkdir($tmpHome . DIRECTORY_SEPARATOR . '.ssh', 0700, true);
        @chmod($tmpHome, 0700);
        @chmod($tmpHome . DIRECTORY_SEPARATOR . '.ssh', 0700);

        try {
            $cmd = array_merge([
                SSH_BIN,
                '-vv',
                '-o', 'PreferredAuthentications=none',
                '-o', 'PubkeyAuthentication=no',
                '-o', 'PasswordAuthentication=no',
                '-o', 'KbdInteractiveAuthentication=no',
                '-o', 'NumberOfPasswordPrompts=0',
                '-p', (string) ($port > 0 ? $port : 22),
            ], self::sshOptions($host, $port, self::PROBE_TIMEOUT_SECONDS), [
                'probe@' . trim($host),
                'exit',
            ]);

            $result = ProcessRunner::run($cmd, [
                'env' => [
                    'HOME' => $tmpHome,
                    'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
                ],
                'timeout' => self::PROBE_TIMEOUT_SECONDS,
            ]);
        } finally {
            FileSystem::removeDirectory($tmpHome);
        }

        return self::extractPresentedHostKey((string) ($result['output'] ?? ''));
    }
}
