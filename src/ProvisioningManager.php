<?php

class ProvisioningManager {
    public static function defaultRepoNotificationPolicy(): array {
        return Notifier::decodePolicy('', 'repo', ['notify_email' => 1]);
    }

    public static function defaultBackupNotificationPolicy(): array {
        return Notifier::decodePolicy('', 'backup_job', [
            'notify_on_failure' => AppConfig::getBool('backup_job_default_notify_on_failure', true) ? 1 : 0,
        ]);
    }

    public static function defaultBackupRetryPolicy(): array {
        return JobRetryPolicy::defaultEntityPolicy();
    }

    public static function generateSshKey(
        string $name,
        string $host,
        string $user = 'root',
        int $port = 22,
        string $description = '',
        ?callable $logger = null
    ): array {
        $result = SshKeyManager::generate($name, $host, $user, $port, $description);
        if (!empty($result['success'])) {
            Auth::log('ssh_key_generate', "Clé générée: $name pour $user@$host");
            self::emit($logger, 'Cle SSH generee (#' . (int) ($result['id'] ?? 0) . ').');
        }
        return $result;
    }

    public static function importSshKey(
        string $name,
        string $host,
        string $user = 'root',
        int $port = 22,
        string $privateKey = '',
        string $description = '',
        ?callable $logger = null
    ): array {
        $result = SshKeyManager::import($name, $host, $user, $port, $privateKey, $description);
        if (!empty($result['success'])) {
            Auth::log('ssh_key_import', "Clé importée: $name pour $user@$host");
            self::emit($logger, 'Cle SSH importee (#' . (int) ($result['id'] ?? 0) . ').');
        }
        return $result;
    }

    public static function createHost(array $options, ?callable $logger = null): array {
        $name = trim((string) ($options['name'] ?? ''));
        $hostname = trim((string) ($options['hostname'] ?? ''));
        $port = (int) ($options['port'] ?? 22);
        $user = trim((string) ($options['user'] ?? 'root'));
        $sshKeyId = ($options['ssh_key_id'] ?? '') !== '' ? (int) $options['ssh_key_id'] : null;
        $restoreOriginal = !empty($options['restore_original_enabled']);
        $sudoPassword = (string) ($options['sudo_password'] ?? '');
        $description = trim((string) ($options['description'] ?? ''));

        if ($name === '' || $hostname === '' || $sshKeyId === null || $sshKeyId <= 0) {
            throw new RuntimeException('Nom, adresse et cle SSH sont requis.');
        }

        $id = HostManager::add($name, $hostname, $port, $user, $sshKeyId, null, $restoreOriginal, $sudoPassword, $description);
        Auth::log('host_add', "Hôte créé: $name ($hostname)");
        self::emit($logger, 'Hote cree (#' . $id . ').');

        return ['id' => $id];
    }

    public static function createRepo(array $options, ?callable $logger = null): array {
        $name = trim((string) ($options['name'] ?? ''));
        $path = trim((string) ($options['path'] ?? ''));
        $passwordSource = (string) ($options['password_source'] ?? 'agent');
        $password = (string) ($options['password'] ?? '');
        $infisicalSecretName = trim((string) ($options['infisical_secret_name'] ?? ''));
        $description = trim((string) ($options['description'] ?? ''));
        $alertHours = (int) ($options['alert_hours'] ?? AppConfig::backupAlertHours());
        $initIfMissing = !empty($options['init_if_missing']);
        $sshKeyId = ($options['ssh_key_id'] ?? null) !== null && (int) $options['ssh_key_id'] > 0
            ? (int) $options['ssh_key_id']
            : null;
        $notificationPolicy = (string) ($options['notification_policy'] ?? '');
        if ($notificationPolicy === '') {
            $notificationPolicy = Notifier::encodePolicy(self::defaultRepoNotificationPolicy(), 'repo');
        }
        $notifyEmail = array_key_exists('notify_email', $options)
            ? (!empty($options['notify_email']) ? 1 : 0)
            : (Notifier::policyHasChannels(json_decode($notificationPolicy, true) ?: [], 'repo') ? 1 : 0);

        if ($name === '' || $path === '') {
            throw new RuntimeException('Nom et chemin du depot requis.');
        }

        if (preg_match('#^sftp:(?:[^@/]+@)?[^:/]+$#i', $path) ||
            preg_match('#^sftp://[^/]*/?$#i', $path)) {
            throw new RuntimeException(
                'Chemin SFTP incomplet : le chemin du depot sur le serveur est manquant. '
                . 'Format attendu : sftp:user@host:/chemin/depot ou sftp://user@host/path/depot'
            );
        }

        $passwordOk = ($passwordSource === 'infisical') ? ($infisicalSecretName !== '') : ($password !== '');
        if (!$passwordOk) {
            throw new RuntimeException($passwordSource === 'infisical'
                ? 'Nom du secret Infisical requis.'
                : 'Mot de passe du depot requis.');
        }

        if ($passwordSource === 'infisical') {
            $effectivePassword = InfisicalClient::getSecret($infisicalSecretName);
            if ($effectivePassword === null) {
                throw new RuntimeException("Impossible de recuperer le secret « $infisicalSecretName » depuis Infisical.");
            }
        } else {
            $effectivePassword = $password;
        }

        $sshPrivateKey = null;
        if ($sshKeyId !== null) {
            try {
                $sshKey = SshKeyManager::getById($sshKeyId);
                if ($sshKey) {
                    $ref = (string) ($sshKey['private_key_file'] ?? '');
                    if (str_starts_with($ref, 'secret://')) {
                        $sshPrivateKey = SecretStore::get($ref) ?: null;
                    } elseif ($ref !== '' && is_file($ref)) {
                        $sshPrivateKey = @file_get_contents($ref) ?: null;
                    }
                }
            } catch (Throwable) {
                // Non-blocking — the creation continues without the key
            }
        }

        $restic = new Restic($path, $effectivePassword, $sshPrivateKey);
        self::emit($logger, 'Vérification du dépôt restic cible...');
        if ($restic->ping()) {
            $id = RepoManager::add($name, $path, $password, $description, $alertHours, $notifyEmail, $passwordSource, $infisicalSecretName, $notificationPolicy);
            Auth::log('repo_add', "Dépôt ajouté: $name ($path)");
            self::emit($logger, 'Dépôt existant détecté et enregistré (#' . $id . ').');
            return ['id' => $id, 'initialized' => false];
        }

        if (!$initIfMissing) {
            throw new RuntimeException(
                'Le dépôt est inaccessible et l\'initialisation automatique est désactivée. '
                . 'Activez « Créer le dépôt s\'il est absent » ou vérifiez le chemin et la connectivité.'
            );
        }

        $configuredWebUser = getenv('FULGURITE_WEB_USER') ?: 'www-data';
        $configuredWebGroup = getenv('FULGURITE_WEB_GROUP') ?: $configuredWebUser;
        $isLocalPath = !preg_match('#^[a-z][a-z0-9+\-.]*://#i', $path) && !str_contains($path, ':');
        if ($isLocalPath) {
            if (is_dir($path)) {
                FilesystemScopeGuard::assertMutableTree($path, 'chmod');
            } else {
                FilesystemScopeGuard::assertPathCreatable($path, 'write');
                if (!@mkdir($path, 0755, true)) {
                    throw new RuntimeException(
                        'Impossible de creer le repertoire du depot ' . $path
                        . '. Creez-le manuellement avec les droits pour ' . $configuredWebUser . ':' . $configuredWebGroup . '.'
                    );
                }
            }
        }

        self::emit($logger, 'Initialisation du dépôt restic en cours...');
        $initResult = $restic->init();
        if (empty($initResult['success'])) {
            $rawError = trim((string) ($initResult['output'] ?? ''));
            throw new RuntimeException(self::humanizeSftpInitError($rawError));
        }

        if ($isLocalPath) {
            RepoManager::fixGroupPermissions($path);
        }

        $id = RepoManager::add($name, $path, $password, $description, $alertHours, $notifyEmail, $passwordSource, $infisicalSecretName, $notificationPolicy);
        Auth::log('repo_add', "Dépôt initialisé et ajouté: $name ($path)");
        self::emit($logger, 'Depot initialise puis enregistre (#' . $id . ').');

        return ['id' => $id, 'initialized' => true];
    }

    public static function createBackupJob(array $options, ?callable $logger = null): array {
        $name = trim((string) ($options['name'] ?? ''));
        $repoId = (int) ($options['repo_id'] ?? 0);
        $sourcePaths = array_values((array) ($options['source_paths'] ?? []));
        $tags = array_values((array) ($options['tags'] ?? []));
        $excludes = array_values((array) ($options['excludes'] ?? []));
        $description = trim((string) ($options['description'] ?? ''));
        $scheduleEnabled = !empty($options['schedule_enabled']) ? 1 : 0;
        $scheduleHour = (int) ($options['schedule_hour'] ?? AppConfig::getInt('backup_job_default_schedule_hour', 2, 0, 23));
        $scheduleDays = (string) ($options['schedule_days'] ?? implode(',', AppConfig::getCsvValues('backup_job_default_schedule_days', '1')));
        $hostId = ($options['host_id'] ?? '') !== '' && $options['host_id'] !== null ? (int) $options['host_id'] : null;
        $remoteRepoPath = trim((string) ($options['remote_repo_path'] ?? ''));
        $hostnameOverride = trim((string) ($options['hostname_override'] ?? ''));
        $retentionEnabled = !empty($options['retention_enabled']) ? 1 : 0;
        $retentionKeepLast = max(0, (int) ($options['retention_keep_last'] ?? AppConfig::getInt('backup_job_default_retention_keep_last', 0, 0, 1000)));
        $retentionKeepDaily = max(0, (int) ($options['retention_keep_daily'] ?? AppConfig::getInt('backup_job_default_retention_keep_daily', 0, 0, 1000)));
        $retentionKeepWeekly = max(0, (int) ($options['retention_keep_weekly'] ?? AppConfig::getInt('backup_job_default_retention_keep_weekly', 0, 0, 1000)));
        $retentionKeepMonthly = max(0, (int) ($options['retention_keep_monthly'] ?? AppConfig::getInt('backup_job_default_retention_keep_monthly', 0, 0, 1000)));
        $retentionKeepYearly = max(0, (int) ($options['retention_keep_yearly'] ?? AppConfig::getInt('backup_job_default_retention_keep_yearly', 0, 0, 1000)));
        $retentionPrune = array_key_exists('retention_prune', $options)
            ? (!empty($options['retention_prune']) ? 1 : 0)
            : (AppConfig::getBool('backup_job_default_retention_prune', true) ? 1 : 0);
        $preHookScriptId = max(0, (int) ($options['pre_hook_script_id'] ?? 0)) ?: null;
        $postHookScriptId = max(0, (int) ($options['post_hook_script_id'] ?? 0)) ?: null;
        $notificationPolicy = (string) ($options['notification_policy'] ?? '');
        if ($notificationPolicy === '') {
            $notificationPolicy = Notifier::encodePolicy(self::defaultBackupNotificationPolicy(), 'backup_job');
        }
        $retryPolicy = (string) ($options['retry_policy'] ?? '');
        if ($retryPolicy === '') {
            $retryPolicy = JobRetryPolicy::encodePolicy(self::defaultBackupRetryPolicy(), true);
        }
        $notifyOnFailure = array_key_exists('notify_on_failure', $options)
            ? (!empty($options['notify_on_failure']) ? 1 : 0)
            : (Notifier::policyHasChannels(json_decode($notificationPolicy, true) ?: [], 'backup_job') ? 1 : 0);

        if ($name === '' || $repoId <= 0 || $sourcePaths === []) {
            throw new RuntimeException('Nom, depot et at least un chemin source sont requis.');
        }

        $id = BackupJobManager::add(
            $name,
            $repoId,
            $sourcePaths,
            $tags,
            $excludes,
            $description,
            $scheduleEnabled,
            $scheduleHour,
            $scheduleDays,
            $notifyOnFailure,
            $hostId,
            $remoteRepoPath !== '' ? $remoteRepoPath : null,
            $hostnameOverride !== '' ? $hostnameOverride : null,
            $retentionEnabled,
            $retentionKeepLast,
            $retentionKeepDaily,
            $retentionKeepWeekly,
            $retentionKeepMonthly,
            $retentionKeepYearly,
            $retentionPrune,
            $preHookScriptId,
            $postHookScriptId,
            $notificationPolicy,
            $retryPolicy
        );

        Auth::log('backup_job_add', "Job backup créé: $name");
        self::emit($logger, 'Backup job cree (#' . $id . ').');

        return ['id' => $id];
    }

    private static function humanizeSftpInitError(string $rawError): string {
        if ($rawError === '') {
            return 'Impossible d\'initialiser le dépôt (erreur inconnue).';
        }

        $lower = strtolower($rawError);

        if (str_contains($lower, 'host key verification failed')) {
            return 'Impossible d\'initialiser le dépôt : la vérification de la clé SSH du serveur a échoué. '
                . 'Vérifiez que l\'adresse du serveur est correcte et qu\'aucune clé SSH en cache n\'est périmée. '
                . 'Détail : ' . $rawError;
        }
        if (str_contains($lower, 'permission denied')) {
            return 'Impossible d\'initialiser le dépôt : accès SSH refusé (Permission denied). '
                . 'Vérifiez que la clé SSH est bien déployée sur la machine de destination et que l\'utilisateur est correct. '
                . 'Détail : ' . $rawError;
        }
        if (str_contains($lower, 'no route to host') || str_contains($lower, 'network is unreachable')) {
            return 'Impossible d\'initialiser le dépôt : le serveur de destination est injoignable. '
                . 'Vérifiez l\'adresse IP/FQDN et la connectivité réseau. '
                . 'Détail : ' . $rawError;
        }
        if (str_contains($lower, 'connection refused')) {
            return 'Impossible d\'initialiser le dépôt : connexion SSH refusée par le serveur. '
                . 'Vérifiez que SSH tourne sur le bon port. '
                . 'Détail : ' . $rawError;
        }
        if (str_contains($lower, 'connection timed out') || str_contains($lower, 'timed out')) {
            return 'Impossible d\'initialiser le dépôt : délai de connexion SSH dépassé. '
                . 'Vérifiez l\'adresse et que le pare-feu autorise la connexion. '
                . 'Détail : ' . $rawError;
        }
        if (str_contains($lower, 'no such file or directory') || str_contains($lower, 'failure')) {
            return 'Impossible d\'initialiser le dépôt : le chemin de destination n\'existe pas ou n\'est pas accessible. '
                . 'Créez le répertoire sur le serveur distant et vérifiez les permissions. '
                . 'Détail : ' . $rawError;
        }

        return 'Impossible d\'initialiser le dépôt : ' . $rawError;
    }

    private static function emit(?callable $logger, string $message): void {
        if ($logger !== null) {
            $logger($message);
        }
    }
}
