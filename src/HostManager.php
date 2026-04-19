<?php
// =============================================================================
// HostManager.php — management of hotes distants
// =============================================================================

class HostManager {

    // ── Lire ──────────────────────────────────────────────────────────────────

    public static function getAll(): array {
        $db = Database::getInstance();
        return $db->query("
            SELECT h.*, k.name AS ssh_key_name, k.user AS key_user, k.private_key_file
            FROM hosts h
            LEFT JOIN ssh_keys k ON k.id = h.ssh_key_id
            ORDER BY h.name
        ")->fetchAll();
    }

    public static function getById(int $id): ?array {
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT h.*, k.name AS ssh_key_name, k.user AS key_user, k.private_key_file
            FROM hosts h
            LEFT JOIN ssh_keys k ON k.id = h.ssh_key_id
            WHERE h.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ── Write helpers ─────────────────────────────────────────────────────────

    public static function add(
        string $name,
        string $hostname,
        int    $port         = 22,
        string $user         = 'root',
        ?int   $sshKeyId     = null,
        ?string $restoreManagedRoot = null,
        bool   $restoreOriginalEnabled = false,
        string $sudoPassword = '',
        string $description  = ''
    ): int {
        $restoreManagedRoot = self::normalizeRestoreManagedRoot($restoreManagedRoot);

        $db = Database::getInstance();
        $db->prepare("
            INSERT INTO hosts (name, hostname, port, user, ssh_key_id, restore_managed_root, restore_original_enabled, sudo_password_file, sudo_password_ref, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$name, $hostname, $port, $user, $sshKeyId, $restoreManagedRoot, $restoreOriginalEnabled ? 1 : 0, null, null, $description]);
        $id = (int) $db->lastInsertId();
        if ($sudoPassword !== '') {
            $ref = SecretStore::writableRef('host', $id, 'sudo');
            SecretStore::put($ref, $sudoPassword, ['entity' => 'host', 'id' => $id, 'name' => $name, 'kind' => 'sudo']);
            $db->prepare("UPDATE hosts SET sudo_password_ref = ? WHERE id = ?")->execute([$ref, $id]);
        }
        return $id;
    }

    public static function update(int $id, array $data): void {
        $db   = Database::getInstance();
        $host = self::getById($id);
        if (!$host) return;

        $fields = [];
        $values = [];

        foreach (['name', 'hostname', 'port', 'user', 'ssh_key_id', 'description'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }

        if (array_key_exists('restore_managed_root', $data)) {
            $fields[] = 'restore_managed_root = ?';
            $values[] = self::normalizeRestoreManagedRoot($data['restore_managed_root']);
        }

        if (array_key_exists('restore_original_enabled', $data)) {
            $fields[] = 'restore_original_enabled = ?';
            $values[] = $data['restore_original_enabled'] ? 1 : 0;
        }

        if (array_key_exists('sudo_password', $data)) {
            if (!empty($data['sudo_password'])) {
                // Nouveau mot of passe : ecraser the ancien file
                if (!empty($host['sudo_password_ref']) && SecretStore::isSecretRef($host['sudo_password_ref'])) {
                    SecretStore::delete($host['sudo_password_ref']);
                }
                if (!empty($host['sudo_password_file']) && file_exists($host['sudo_password_file'])) {
                    FileSystem::deleteFile((string) $host['sudo_password_file']);
                }
                $ref = SecretStore::writableRef('host', $id, 'sudo');
                SecretStore::put($ref, (string) $data['sudo_password'], ['entity' => 'host', 'id' => $id, 'name' => $data['name'] ?? $host['name'], 'kind' => 'sudo']);
                $fields[] = "sudo_password_file = ?";
                $values[] = null;
                $fields[] = "sudo_password_ref = ?";
                $values[] = $ref;
            } elseif ($data['sudo_password'] === '' && isset($data['clear_sudo']) && $data['clear_sudo']) {
                // Effacer the mot of passe sudo
                if (!empty($host['sudo_password_ref']) && SecretStore::isSecretRef($host['sudo_password_ref'])) {
                    SecretStore::delete($host['sudo_password_ref']);
                }
                if (!empty($host['sudo_password_file']) && file_exists($host['sudo_password_file'])) {
                    FileSystem::deleteFile((string) $host['sudo_password_file']);
                }
                $fields[] = "sudo_password_file = ?";
                $values[] = null;
                $fields[] = "sudo_password_ref = ?";
                $values[] = null;
            }
        }

        if (empty($fields)) return;
        $values[] = $id;
        $db->prepare("UPDATE hosts SET " . implode(', ', $fields) . " WHERE id = ?")
           ->execute($values);
    }

    public static function delete(int $id): void {
        $db   = Database::getInstance();
        $host = self::getById($id);
        if ($host) {
            if (!empty($host['sudo_password_ref']) && SecretStore::isSecretRef($host['sudo_password_ref'])) {
                SecretStore::delete($host['sudo_password_ref']);
            }
            if (!empty($host['sudo_password_file']) && file_exists($host['sudo_password_file'])) {
                FileSystem::deleteFile((string) $host['sudo_password_file']);
            }
        }
        $db->prepare("DELETE FROM hosts WHERE id = ?")->execute([$id]);
    }

    // ── Mot of passe sudo ─────────────────────────────────────────────────────

    public static function getSudoPassword(array $host): string {
        if (!empty($host['sudo_password_ref']) && SecretStore::isSecretRef($host['sudo_password_ref'])) {
            return SecretStore::get($host['sudo_password_ref'], 'sudo', ['host_id' => (int) ($host['id'] ?? 0)]) ?? '';
        }
        if (!empty($host['sudo_password_file']) && file_exists($host['sudo_password_file'])) {
            return self::migrateLegacySudoPassword($host);
        }
        return '';
    }

    public static function hasSudoPassword(array $host): bool {
        return (!empty($host['sudo_password_ref']) && SecretStore::isSecretRef($host['sudo_password_ref']) && SecretStore::exists($host['sudo_password_ref']))
            || (!empty($host['sudo_password_file']) && file_exists($host['sudo_password_file']));
    }

    private static function migrateLegacySudoPassword(array $host): string {
        $path = (string) ($host['sudo_password_file'] ?? '');
        if ($path === '' || !is_file($path)) {
            return '';
        }
        $password = trim((string) file_get_contents($path));
        $id = (int) ($host['id'] ?? 0);
        if ($id > 0 && $password !== '') {
            $ref = SecretStore::writableRef('host', $id, 'sudo');
            SecretStore::put($ref, $password, ['entity' => 'host', 'id' => $id, 'legacy_file' => $path, 'kind' => 'sudo']);
            Database::getInstance()->prepare("UPDATE hosts SET sudo_password_ref = ? WHERE id = ?")->execute([$ref, $id]);
            FileSystem::deleteFile($path);
        }
        return $password;
    }

    private static function normalizeRestoreManagedRoot(mixed $value): ?string {
        $path = trim((string) $value);
        return $path === '' ? null : $path;
    }

    // ── Test of connection ─────────────────────────────────────────────────────

	public static function testConnection(array $host): array {
		if (empty($host['ssh_key_id'])) {
			return ['success' => false, 'output' => 'Clé SSH non associée'];
		}

		$sshKeyFile = SshKeyManager::getTemporaryKeyFile((int)$host['ssh_key_id']);

		$tmpHome = '/tmp/fulgurite-host-test-' . uniqid();
		mkdir($tmpHome . '/.ssh', 0700, true);

		$result = Restic::runShell(array_merge([
			'ssh',
			'-i', $sshKeyFile,
			'-p', (string) $host['port'],
		], SshKnownHosts::sshOptions((string) $host['hostname'], (int) $host['port'], 8), [
			$host['user'] . '@' . $host['hostname'],
			'echo OK',
		]), ['HOME' => $tmpHome]);

		FileSystem::removeDirectory($tmpHome);
		Restic::deleteTempSecretFile($sshKeyFile);
		return SshKnownHosts::finalizeSshResult($result, (string) $host['hostname'], (int) $host['port'], 'host_test');
	}

    public static function runRemoteCommand(array $host, string $command): array {
        if (empty($host['ssh_key_id'])) {
            return ['success' => false, 'output' => 'Clé SSH non associée', 'code' => 1];
        }

        $sshKeyFile = SshKeyManager::getTemporaryKeyFile((int) $host['ssh_key_id']);
        $tmpHome = '/tmp/fulgurite-host-run-' . uniqid();
        @mkdir($tmpHome . '/.ssh', 0700, true);

        try {
            $result = Restic::runShell(array_merge([
                SSH_BIN,
                '-i', $sshKeyFile,
                '-p', (string) ((int) ($host['port'] ?? 22)),
            ], SshKnownHosts::sshOptions((string) ($host['hostname'] ?? ''), (int) ($host['port'] ?? 22), 10), [
                (string) ($host['user'] ?? 'root') . '@' . (string) ($host['hostname'] ?? ''),
                $command,
            ]), ['HOME' => $tmpHome]);
            return SshKnownHosts::finalizeSshResult($result, (string) ($host['hostname'] ?? ''), (int) ($host['port'] ?? 22), 'host_command');
        } finally {
            FileSystem::removeDirectory($tmpHome);
            Restic::deleteTempSecretFile($sshKeyFile);
        }
    }

    public static function probeFilesystem(array $host, string $path): array {
        $path = trim($path);
        if ($path === '') {
            return ['success' => false, 'output' => 'Chemin distant vide'];
        }

        $command = 'p=' . escapeshellarg($path)
            . '; while [ ! -e "$p" ] && [ "$p" != "/" ]; do p=$(dirname "$p"); done'
            . '; if [ ! -e "$p" ]; then p="/"; fi'
            . '; df -Pk "$p" | tail -1'
            . '; printf "\n__FULGURITE_PROBE_PATH__=%s\n" "$p"';
        $result = self::runRemoteCommand($host, $command);
        if (empty($result['success'])) {
            return $result;
        }

        $output = trim((string) ($result['output'] ?? ''));
        if (!preg_match('/^\S+\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%/m', $output, $matches)) {
            return ['success' => false, 'output' => 'Sortie df distante inattendue: ' . $output];
        }

        $totalBytes = ((int) $matches[1]) * 1024;
        $usedBytes = ((int) $matches[2]) * 1024;
        $freeBytes = ((int) $matches[3]) * 1024;
        $probePath = $path;
        if (preg_match('/__FULGURITE_PROBE_PATH__=(.+)$/m', $output, $probeMatches)) {
            $probePath = trim((string) $probeMatches[1]);
        }

        return [
            'success' => true,
            'probe_path' => $probePath,
            'total_bytes' => $totalBytes,
            'free_bytes' => $freeBytes,
            'used_bytes' => $usedBytes,
            'used_percent' => $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0.0,
            'output' => $output,
        ];
    }
}
