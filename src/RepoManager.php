<?php
// =============================================================================
// RepoManager.php — management of restic repositories
// =============================================================================

class RepoManager {

    public static function getAll(): array {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM repos ORDER BY name")->fetchAll();
    }

    public static function getById(int $id): ?array {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM repos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getDueSnapshotRefreshRepos(int $limit = 25): array {
        $db = Database::getInstance();
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $limit = max(1, $limit);
        $uniqueKeyExpr = self::snapshotRefreshUniqueKeyExpr($driver, 'r.id');
        $dueCondition = self::snapshotRefreshDueConditionSql($driver);
        $sql = "
            SELECT r.id, r.name, r.snapshot_refresh_interval_minutes, r.last_snapshot_refreshed_at
            FROM repos r
            WHERE COALESCE(r.snapshot_refresh_enabled, 1) = 1
              AND COALESCE(r.snapshot_refresh_interval_minutes, 0) > 0
              AND {$dueCondition}
              AND NOT EXISTS (
                  SELECT 1
                  FROM job_queue jq
                  WHERE jq.type = 'repo_snapshot_refresh'
                    AND jq.unique_key = {$uniqueKeyExpr}
                    AND jq.status IN ('queued', 'running')
              )
            ORDER BY COALESCE(r.last_snapshot_refreshed_at, '1970-01-01 00:00:00') ASC, r.id ASC
            LIMIT ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function secondsUntilNextScheduledSnapshotRefresh(): ?int {
        $db = Database::getInstance();
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nextDueExpr = self::snapshotRefreshNextDueExprSql($driver);
        $stmt = $db->query("
            SELECT {$nextDueExpr} AS next_due_at
            FROM repos
            WHERE COALESCE(snapshot_refresh_enabled, 1) = 1
              AND COALESCE(snapshot_refresh_interval_minutes, 0) > 0
        ");
        $nextDueAt = $stmt ? $stmt->fetchColumn() : null;
        if ($nextDueAt === false || $nextDueAt === null || trim((string) $nextDueAt) === '') {
            return null;
        }

        $nextTs = strtotime((string) $nextDueAt);
        if ($nextTs === false) {
            return null;
        }

        $delta = $nextTs - time();
        return $delta > 0 ? $delta : 0;
    }

    private static function snapshotRefreshUniqueKeyExpr(string $driver, string $repoIdExpr): string {
        if ($driver === 'mysql') {
            return "CONCAT('repo_snapshot_refresh:', {$repoIdExpr})";
        }

        return "'repo_snapshot_refresh:' || {$repoIdExpr}";
    }

    private static function snapshotRefreshDueConditionSql(string $driver): string {
        return match ($driver) {
            'mysql' => "COALESCE(r.last_snapshot_refreshed_at, '1970-01-01 00:00:00') <= DATE_SUB(NOW(), INTERVAL r.snapshot_refresh_interval_minutes MINUTE)",
            'pgsql' => "COALESCE(r.last_snapshot_refreshed_at::timestamp, TIMESTAMP '1970-01-01 00:00:00') <= (NOW() - (r.snapshot_refresh_interval_minutes || ' minutes')::interval)",
            default => "COALESCE(r.last_snapshot_refreshed_at, '1970-01-01 00:00:00') <= datetime('now', '-' || r.snapshot_refresh_interval_minutes || ' minutes')",
        };
    }

    private static function snapshotRefreshNextDueExprSql(string $driver): string {
        return match ($driver) {
            'mysql' => "MIN(DATE_ADD(COALESCE(last_snapshot_refreshed_at, '1970-01-01 00:00:00'), INTERVAL snapshot_refresh_interval_minutes MINUTE))",
            'pgsql' => "MIN(COALESCE(last_snapshot_refreshed_at::timestamp, TIMESTAMP '1970-01-01 00:00:00') + (snapshot_refresh_interval_minutes || ' minutes')::interval)",
            default => "MIN(datetime(COALESCE(last_snapshot_refreshed_at, '1970-01-01 00:00:00'), '+' || snapshot_refresh_interval_minutes || ' minutes'))",
        };
    }

    public static function add(
        string $name,
        string $path,
        string $password,
        string $description            = '',
        int    $alertHours             = 25,
        int    $notifyEmail            = 1,
        string $passwordSource         = '',
        string $infisicalSecretName    = '',
        ?string $notificationPolicy    = null,
        int    $snapshotRefreshEnabled = 1
    ): int {
        self::validateLocalPathForRegistration($path);
        $passwordSource = self::normalizePasswordSource($passwordSource);
        $db = Database::getInstance();
        $startedTransaction = !$db->inTransaction();
        if ($startedTransaction) {
            $db->beginTransaction();
        }
        try {
            $db->prepare("
                INSERT INTO repos (name, path, password_file, password_ref, description, alert_hours, notify_email, password_source, infisical_secret_name, notification_policy, snapshot_refresh_enabled)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $name, $path, null, null, $description, $alertHours, $notifyEmail,
                $passwordSource,
                $infisicalSecretName ?: null,
                $notificationPolicy,
                $snapshotRefreshEnabled,
            ]);
            $id = (int) $db->lastInsertId();
            if (in_array($passwordSource, ['agent', 'local'], true)) {
                $ref = SecretStore::writableRef('repo', $id, 'password', $passwordSource);
                SecretStore::put($ref, $password, ['entity' => 'repo', 'id' => $id, 'name' => $name]);
                $db->prepare("UPDATE repos SET password_ref = ? WHERE id = ?")->execute([$ref, $id]);
            } elseif ($passwordSource === 'infisical' && $infisicalSecretName !== '') {
                $db->prepare("UPDATE repos SET password_ref = ? WHERE id = ?")->execute([SecretStore::infisicalRef($infisicalSecretName), $id]);
            }
            if ($startedTransaction) {
                $db->commit();
            }
            return $id;
        } catch (Throwable $e) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function delete(int $id, bool $deleteFiles = false): void {
        $db   = Database::getInstance();
        $repo = self::getById($id);
        if ($repo) {
            if (!empty($repo['password_ref']) && SecretStore::isSecretRef($repo['password_ref'])) {
                SecretStore::delete($repo['password_ref']);
            }
            if (!empty($repo['password_file']) && file_exists($repo['password_file'])) {
                FileSystem::deleteFile((string) $repo['password_file']);
            }
            // Delete repository files if requested and if this is a local path
            if ($deleteFiles && self::isLocalPath((string) ($repo['path'] ?? ''))) {
                self::deleteRepoDirectory((string) ($repo['path'] ?? ''));
            }
        }
        $indexDb = Database::getIndexInstance();
        $indexDb->prepare("DELETE FROM snapshot_navigation_index WHERE repo_id = ?")->execute([$id]);
        $indexDb->prepare("DELETE FROM snapshot_file_index WHERE repo_id = ?")->execute([$id]);
        $indexDb->prepare("DELETE FROM snapshot_search_index_status WHERE repo_id = ?")->execute([$id]);
        $indexDb->prepare("DELETE FROM repo_snapshot_catalog WHERE repo_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM repos WHERE id = ?")->execute([$id]);
    }

    private static function isLocalPath(string $path): bool {
        $path = trim($path);
        if ($path === '') {
            return false;
        }

        return !preg_match('~^[a-z][a-z0-9+.-]*://~i', $path)
            && !preg_match('~^[^/]+:.+~', $path);
    }

    private static function validateLocalPathForRegistration(string $path): void
    {
        if (!self::isLocalPath($path)) {
            return;
        }

        $path = trim($path);
        if ($path === '') {
            throw new RuntimeException('Chemin de depot local vide.');
        }

        if (is_dir($path)) {
            FilesystemScopeGuard::assertPathAllowed($path, 'read', true);
            return;
        }

        FilesystemScopeGuard::assertPathCreatable($path, 'write');
    }

    private static function deleteRepoDirectory(string $path): void {
        if (!is_dir($path)) {
            return;
        }

        FileSystem::removeDirectory($path);
    }

    public static function getPassword(array $repo): string {
        $source = self::normalizePasswordSource((string) ($repo['password_source'] ?? 'agent'));
        if ($source === 'infisical') {
            if (!empty($repo['password_ref']) && SecretStore::isSecretRef($repo['password_ref'])) {
                $pw = SecretStore::get($repo['password_ref']);
                if ($pw === null || $pw === '') {
                    return self::missingPassword($repo, 'secret Infisical introuvable ou vide');
                }
                return $pw;
            }
            $secret = InfisicalClient::getSecret($repo['infisical_secret_name'] ?? '');
            if ($secret === null || $secret === '') {
                return self::missingPassword($repo, 'secret Infisical introuvable ou vide');
            }
            return $secret;
        }
        if (!empty($repo['password_ref']) && SecretStore::isSecretRef($repo['password_ref'])) {
            $pw = SecretStore::get($repo['password_ref'], 'backup', ['repo_id' => (int) ($repo['id'] ?? 0)]);
            // Guard against empty string: SecretStore::get() may return '' when the agent
            // returns {'ok':true,'value':null} (null cast to string) or when the stored
            // secret was written as empty. The ?? operator only catches null, so we must
            // explicitly reject '' here too — otherwise restic receives RESTIC_PASSWORD=''
            // and falls back to stdin, causing "Fatal: an empty password is not a password".
            if ($pw === null || $pw === '') {
                return self::missingPassword($repo, 'reference de secret introuvable ou mot de passe vide');
            }
            return $pw;
        }
        if (!empty($repo['password_file']) && file_exists($repo['password_file'])) {
            return self::migrateLegacyPassword($repo);
        }
        return self::missingPassword($repo, 'aucune reference de secret configuree');
    }

    public static function getRestic(array $repo): Restic {
        return new Restic($repo['path'], self::getPassword($repo));
    }

    private static function chmodWithReport(string $path, int $mode, array &$errors): bool {
        $errorMessage = null;
        set_error_handler(static function (int $severity, string $message) use (&$errorMessage): bool {
            $errorMessage = $message;
            return true;
        });

        try {
            $success = chmod($path, $mode);
        } finally {
            restore_error_handler();
        }

        if (!$success) {
            $errors[] = [
                'path' => $path,
                'mode' => sprintf('%04o', $mode),
                'message' => $errorMessage ?: 'chmod a échoué sans message PHP',
            ];
        }

        return $success;
    }

    private static function findBinary(array $candidates): string {
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private static function getPermissionFixerScriptPath(): string {
        $candidate = dirname(__DIR__) . '/bin/fulgurite-fix-perms';
        return is_file($candidate) && is_executable($candidate) ? $candidate : '';
    }

    private static function runCommand(array $cmd): array {
        $result = ProcessRunner::run($cmd, ['capture_stderr' => true]);
        if (!$result['success'] && (int) ($result['code'] ?? 1) === 1 && trim((string) ($result['output'] ?? '')) === 'Impossible de lancer la commande') {
            return [
                'success' => false,
                'code' => 127,
                'output' => 'proc_open a échoué',
                'command' => ProcessRunner::renderCommand($cmd),
            ];
        }
        $code = (int) ($result['code'] ?? 1);

        return [
            'success' => $code === 0,
            'code' => $code,
            'output' => (string) ($result['output'] ?? ''),
            'command' => ProcessRunner::renderCommand($cmd),
        ];
    }

    private static function runSudoPermissionRepair(string $path): array {
        $path = FilesystemScopeGuard::assertMutableTree($path, 'chmod');
        $sudo = self::findBinary(['/usr/bin/sudo', '/bin/sudo']);
        $script = self::getPermissionFixerScriptPath();
        $find = self::findBinary(['/usr/bin/find', '/bin/find']);
        $chmod = self::findBinary(['/bin/chmod', '/usr/bin/chmod']);

        if ($sudo === '') {
            return [
                'attempted' => false,
                'success' => false,
                'code' => 127,
                'output' => 'sudo indisponible',
                'commands' => [],
            ];
        }

        $commands = [];
        if ($script !== '') {
            $commands[] = [$sudo, '-n', $script, $path];
        } elseif ($find !== '' && $chmod !== '') {
            $commands[] = [$sudo, '-n', $find, $path, '-type', 'd', '-exec', $chmod, '2770', '{}', '+'];
            $commands[] = [$sudo, '-n', $find, $path, '-type', 'f', '-exec', $chmod, '0660', '{}', '+'];
        } else {
            return [
                'attempted' => false,
                'success' => false,
                'code' => 127,
                'output' => 'script de réparation et fallback find/chmod indisponibles',
                'commands' => [],
            ];
        }

        $outputs = [];
        foreach ($commands as $command) {
            $result = self::runCommand($command);
            $outputs[] = trim(($result['command'] ?? '') . (($result['output'] ?? '') !== '' ? "\n" . $result['output'] : ''));
            if (empty($result['success'])) {
                return [
                    'attempted' => true,
                    'success' => false,
                    'code' => (int) ($result['code'] ?? 1),
                    'output' => trim(implode("\n", array_filter($outputs))),
                    'commands' => $commands,
                ];
            }
        }

        return [
            'attempted' => true,
            'success' => true,
            'code' => 0,
            'output' => trim(implode("\n", array_filter($outputs))),
            'commands' => $commands,
        ];
    }

    /**
     * Recursively applies access permissions on a local repository.
     * Dirs → 2770 (setgid), files → 0660.
     * must be appele after restic init and after each backup local.     */
    public static function fixGroupPermissions(string $path): array {
        $report = [
            'changed' => 0,
            'errors' => [],
            'sudo' => [
                'attempted' => false,
                'success' => false,
                'code' => 0,
                'output' => '',
            ],
        ];

        if (!is_dir($path)) {
            $report['errors'][] = [
                'path' => $path,
                'mode' => '2770',
                'message' => 'le répertoire du dépôt est introuvable',
            ];
            return $report;
        }

        $path = FilesystemScopeGuard::assertMutableTree($path, 'chmod');

        if (self::chmodWithReport($path, 02770, $report['errors'])) {
            $report['changed']++;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            if (self::chmodWithReport($item->getPathname(), $item->isDir() ? 02770 : 0660, $report['errors'])) {
                $report['changed']++;
            }
        }

        if ($report['errors'] !== []) {
            $report['sudo'] = self::runSudoPermissionRepair($path);
            if (!empty($report['sudo']['success'])) {
                $report['errors'] = [];
            }
        }

        return $report;
    }

    private static function migrateLegacyPassword(array $repo): string {
        $path = (string) ($repo['password_file'] ?? '');
        if ($path === '' || !is_file($path)) {
            // TOCTOU: file_exists() passed in the caller but the file is gone now.
            return self::missingPassword($repo, 'fichier de mot de passe introuvable');
        }
        $password = trim((string) file_get_contents($path));
        if ($password === '') {
            return self::missingPassword($repo, 'fichier de mot de passe vide');
        }
        $id = (int) ($repo['id'] ?? 0);
        if ($id > 0) {
            $ref = SecretStore::writableRef('repo', $id, 'password');
            SecretStore::put($ref, $password, ['entity' => 'repo', 'id' => $id, 'legacy_file' => $path]);
            Database::getInstance()->prepare("UPDATE repos SET password_ref = ?, password_source = ? WHERE id = ?")->execute([$ref, SecretStore::providerNameForRef($ref), $id]);
            FileSystem::deleteFile($path);
        }
        return $password;
    }

    private static function normalizePasswordSource(string $source): string {
        if (trim($source) === '') {
            return SecretStore::defaultWritableSource();
        }
        return match ($source) {
            'infisical' => 'infisical',
            'local' => 'local',
            'file' => 'local',
            default => 'agent',
        };
    }

    private static function missingPassword(array $repo, string $reason): string {
        $name = (string) ($repo['name'] ?? ('#' . (string) ($repo['id'] ?? '?')));
        throw new RuntimeException("Mot de passe du depot {$name} indisponible: {$reason}.");
    }
}
