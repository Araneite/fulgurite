<?php

class RemoteBackupQuickFlow {
    public static function requireManagePermissions(): void {
        Auth::check();
        foreach (['backup_jobs.manage', 'hosts.manage', 'repos.manage', 'sshkeys.manage'] as $permission) {
            if (!Auth::hasPermission($permission)) {
                Auth::requirePermission($permission);
            }
        }
    }

    public static function wizardContext(): array {
        self::requireManagePermissions();

        return [
            'templates' => QuickBackupTemplateManager::getAll(),
            'default_template_ref' => QuickBackupTemplateManager::defaultReference(),
            'hosts' => Auth::filterAccessibleHosts(HostManager::getAll()),
            'repos' => Auth::filterAccessibleRepos(RepoManager::getAll()),
            'ssh_keys' => SshKeyManager::getAll(),
            'secret_storage_default' => SecretStore::defaultWritableSource(),
            'secret_storage_modes' => self::secretStorageModes(),
            'recent_history' => self::recentHistory(),
            'days_map' => [
                '1' => 'Lu',
                '2' => 'Ma',
                '3' => 'Me',
                '4' => 'Je',
                '5' => 'Ve',
                '6' => 'Sa',
                '7' => 'Di',
            ],
        ];
    }

    public static function preview(array $input): array {
        self::requireManagePermissions();
        $payload = self::normalizePayload($input);
        $checks = self::buildChecks($payload, false);

        return [
            'success' => !self::hasFatalCheck($checks),
            'payload' => self::exportPayload($payload),
            'summary' => self::buildHumanSummary($payload),
            'checks' => $checks,
            'can_create' => !self::hasFatalCheck($checks),
            'can_test' => !self::hasFatalCheck($checks) && !self::hasTestBlockingCheck($checks),
        ];
    }

    public static function create(array $input, ?callable $logger = null): array {
        self::requireManagePermissions();
        $payload = self::normalizePayload($input);
        self::emitLog($logger, 'Preparation de la creation rapide...');
        $checks = self::buildChecks($payload, true);
        self::emitLog($logger, 'Verification des preconditions terminee.');
        if (self::hasFatalCheck($checks)) {
            foreach ($checks as $check) {
                if (($check['status'] ?? '') !== 'error') {
                    continue;
                }
                self::emitLog($logger, '[BLOQUANT] ' . trim((string) ($check['title'] ?? 'Check')) . ' — ' . trim((string) ($check['message'] ?? '')));
            }
            return [
                'success' => false,
                'error' => 'Corrigez les points stucks avant de lancer la creation.',
                'checks' => $checks,
                'summary' => self::buildHumanSummary($payload),
            ];
        }

        $created = [
            'ssh_key_id' => null,
            'host_id' => null,
            'repo_id' => null,
            'job_id' => null,
        ];
        $nextSteps = [];
        $keyInfo = null;

        $db = Database::getInstance();
        $startedTransaction = !$db->inTransaction();
        if ($startedTransaction) {
            $db->beginTransaction();
        }

        try {
            self::emitLog($logger, 'Preparation de la cle SSH...');
            $keyInfo = self::resolveKeyForCreate($payload, $logger);
            $created['ssh_key_id'] = $keyInfo['id'] ?? null;
            self::emitLog($logger, 'Cle SSH prete (#' . (int) ($created['ssh_key_id'] ?? 0) . ').');

            if ($payload['host_mode'] === 'existing') {
                $created['host_id'] = $payload['existing_host_id'];
                self::emitLog($logger, 'Reutilisation de l hote existant #' . (int) $created['host_id'] . '.');
            } else {
                self::emitLog($logger, 'Creation de l hote ' . $payload['host_name'] . '...');
                $host = ProvisioningManager::createHost([
                    'name' => $payload['host_name'],
                    'hostname' => $payload['hostname'],
                    'port' => $payload['port'],
                    'user' => $payload['user'],
                    'ssh_key_id' => $keyInfo['id'] ?? null,
                    'restore_original_enabled' => false,
                    'sudo_password' => '',
                    'description' => '',
                ], $logger);
                $created['host_id'] = (int) ($host['id'] ?? 0);
            }

            if ($payload['repo_mode'] === 'existing') {
                $created['repo_id'] = $payload['existing_repo_id'];
                self::emitLog($logger, 'Reutilisation du depot existant #' . (int) $created['repo_id'] . '.');
            } else {
                self::emitLog($logger, 'Preparation du depot restic...');
                $repo = self::createRepo($payload, $created['ssh_key_id'], $logger);
                $created['repo_id'] = (int) ($repo['id'] ?? 0);
            }

            self::emitLog($logger, 'Creation du backup job ' . $payload['job_name'] . '...');
            $job = ProvisioningManager::createBackupJob([
                'name' => $payload['job_name'],
                'repo_id' => (int) $created['repo_id'],
                'source_paths' => $payload['source_paths'],
                'tags' => $payload['tags'],
                'excludes' => $payload['excludes'],
                'description' => $payload['job_description'],
                'schedule_enabled' => $payload['schedule_enabled'] ? 1 : 0,
                'schedule_hour' => $payload['schedule_hour'],
                'schedule_days' => implode(',', $payload['schedule_days']),
                'host_id' => (int) $created['host_id'],
                'remote_repo_path' => $payload['remote_repo_path'] !== '' ? $payload['remote_repo_path'] : null,
                'hostname_override' => $payload['hostname_override'] !== '' ? $payload['hostname_override'] : null,
                'retention_enabled' => $payload['retention_enabled'] ? 1 : 0,
                'retention_keep_last' => $payload['retention_keep_last'],
                'retention_keep_daily' => $payload['retention_keep_daily'],
                'retention_keep_weekly' => $payload['retention_keep_weekly'],
                'retention_keep_monthly' => $payload['retention_keep_monthly'],
                'retention_keep_yearly' => $payload['retention_keep_yearly'],
                'retention_prune' => $payload['retention_prune'] ? 1 : 0,
            ], $logger);
            $created['job_id'] = (int) ($job['id'] ?? 0);

            if ($startedTransaction) {
                $db->commit();
            }
            self::emitLog($logger, 'Transaction validee.');
        } catch (Throwable $e) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            self::emitLog($logger, 'ERREUR: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Creation interrompue : ' . $e->getMessage(),
                'checks' => $checks,
                'summary' => self::buildHumanSummary($payload),
            ];
        }

        if (!empty($keyInfo['manual_command'])) {
            $nextStepMessage = !empty($keyInfo['deploy_warning'])
                ? 'Le deploiement automatique a echoue. Ajoutez la cle publique sur la machine cible avant le premier test.'
                : 'La cle a ete creee mais n a pas ete deployee automatiquement. Ajoutez la cle publique sur la machine cible avant le premier test.';
            $nextSteps[] = [
                'tone' => 'warning',
                'title' => 'Deploiement manuel de la cle requis',
                'message' => $nextStepMessage,
                'action' => $keyInfo['manual_command'],
            ];
            if (!empty($keyInfo['deploy_warning'])) {
                $nextSteps[] = [
                    'tone' => 'warning',
                    'title' => 'Detail du deploiement automatique',
                    'message' => trim((string) $keyInfo['deploy_warning']),
                    'action' => '',
                ];
            }
            self::emitLog($logger, 'Deploiement manuel de la cle requis.');
        }

        $testInfo = null;
        if ($payload['run_after_create']) {
            $canRunImmediateTest = empty($keyInfo['manual_command']) && self::canRunImmediateTest($payload);
            if (!$canRunImmediateTest) {
                $nextSteps[] = [
                    'tone' => 'warning',
                    'title' => 'Test differe',
                    'message' => 'Le job a ete cree, mais le test immediat demande encore une action sur l acces SSH.',
                    'action' => 'Ajoutez la cle publique sur l hote puis lancez le job depuis Backup jobs.',
                ];
                self::emitLog($logger, 'Test immediat differe : acces SSH encore incomplet.');
            } else {
                self::emitLog($logger, 'Lancement du premier test de sauvegarde en arriere-plan...');
                $testInfo = self::launchImmediateTest((int) $created['job_id']);
                self::emitLog($logger, 'Test immediat lance (run: ' . (string) ($testInfo['run_id'] ?? '') . ').');
            }
        }

        self::emitLog($logger, 'Creation rapide terminee avec succes.');

        return [
            'success' => true,
            'created' => $created,
            'summary' => self::buildHumanSummary($payload),
            'checks' => $checks,
            'next_steps' => $nextSteps,
            'test' => $testInfo,
            'manual_key' => [
                'public_key' => (string) ($keyInfo['public_key'] ?? ''),
                'command' => (string) ($keyInfo['manual_command'] ?? ''),
            ],
        ];
    }

    public static function persistCreationHistory(?int $jobId, bool $success, string $output): void {
        Database::getInstance()->prepare("
            INSERT INTO cron_log (job_type, job_id, status, output)
            VALUES ('quick_backup', ?, ?, ?)
        ")->execute([
            $jobId ?: null,
            $success ? 'success' : 'failed',
            $output,
        ]);
    }

    public static function recentHistory(int $limit = 8): array {
        $stmt = Database::getInstance()->prepare("
            SELECT id, job_id, status, output, ran_at
            FROM cron_log
            WHERE job_type = 'quick_backup'
            ORDER BY ran_at DESC, id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function normalizePayload(array $input): array {
        $templateRef = trim((string) ($input['template_ref'] ?? QuickBackupTemplateManager::defaultReference()));
        $template = QuickBackupTemplateManager::getByReference($templateRef)
            ?? QuickBackupTemplateManager::getByReference(QuickBackupTemplateManager::defaultReference());
        $defaults = (array) ($template['defaults'] ?? []);

        $hostMode = ((string) ($input['host_mode'] ?? 'create')) === 'existing' ? 'existing' : 'create';
        $repoMode = ((string) ($input['repo_mode'] ?? 'create')) === 'existing' ? 'existing' : 'create';
        $keyMode = (string) ($input['key_mode'] ?? 'generate');
        if (!in_array($keyMode, ['existing', 'generate', 'import'], true)) {
            $keyMode = 'generate';
        }

        $hostName = trim((string) ($input['host_name'] ?? ''));
        $hostname = trim((string) ($input['hostname'] ?? ''));
        if ($hostName === '' && $hostname !== '') {
            $hostName = $hostname;
        }

        $user = trim((string) ($input['user'] ?? ($defaults['host_user'] ?? 'root')));
        if ($user === '') {
            $user = 'root';
        }

        $port = max(1, min(65535, (int) ($input['port'] ?? ($defaults['host_port'] ?? 22))));
        $sourcePaths = self::splitLines((string) ($input['source_paths'] ?? ''));
        if ($sourcePaths === [] && !empty($defaults['source_paths'])) {
            $sourcePaths = array_values((array) $defaults['source_paths']);
        }

        $excludes = self::splitLines((string) ($input['excludes'] ?? ''));
        if ($excludes === [] && !empty($defaults['excludes'])) {
            $excludes = array_values((array) $defaults['excludes']);
        }

        $tags = self::splitCsv((string) ($input['tags'] ?? ''));
        if ($tags === [] && !empty($defaults['tags'])) {
            $tags = array_values((array) $defaults['tags']);
        }

        $repoName = trim((string) ($input['repo_name'] ?? ''));
        $repoName = self::resolvePatternField($repoName, (string) ($defaults['repo_name_pattern'] ?? ''), $hostName, $hostname);

        $repoPath = trim((string) ($input['repo_path'] ?? ''));
        $repoPath = self::resolvePatternField($repoPath, (string) ($defaults['repo_path_pattern'] ?? ''), $hostName, $hostname);

        $remoteRepoPath = trim((string) ($input['remote_repo_path'] ?? ''));
        $remoteRepoPath = self::resolvePatternField($remoteRepoPath, (string) ($defaults['remote_repo_path_pattern'] ?? ''), $hostName, $hostname);
        if ($remoteRepoPath === '') {
            $remoteRepoPath = $repoPath;
        }

        $jobName = trim((string) ($input['job_name'] ?? ''));
        $jobName = self::resolvePatternField($jobName, (string) ($defaults['job_name_pattern'] ?? ''), $hostName, $hostname);
        if ($jobName === '' && $hostName !== '') {
            $jobName = 'Sauvegarde ' . $hostName;
        }

        $repoPasswordSource = self::normalizeRepoPasswordSource(
            (string) ($input['repo_password_source'] ?? ($defaults['repo_password_source'] ?? SecretStore::defaultWritableSource()))
        );
        $repoInfisicalSecretName = trim((string) ($input['repo_infisical_secret_name'] ?? ($input['infisical_secret_name'] ?? '')));

        $days = QuickBackupTemplateManager::normalizeDays($input['schedule_days'] ?? ($defaults['schedule_days'] ?? ['1']));
        $scheduleEnabled = array_key_exists('schedule_enabled', $input)
            ? !empty($input['schedule_enabled'])
            : !empty($defaults['schedule_enabled']);
        $retentionEnabled = array_key_exists('retention_enabled', $input)
            ? !empty($input['retention_enabled'])
            : !empty($defaults['retention_enabled']);

        return [
            'template_ref' => $templateRef,
            'template' => $template,
            'host_mode' => $hostMode,
            'existing_host_id' => ($input['existing_host_id'] ?? '') !== '' ? (int) $input['existing_host_id'] : null,
            'host_name' => $hostName,
            'hostname' => $hostname,
            'port' => $port,
            'user' => $user,
            'key_mode' => $keyMode,
            'existing_key_id' => ($input['existing_key_id'] ?? '') !== '' ? (int) $input['existing_key_id'] : null,
            'key_name' => trim((string) ($input['key_name'] ?? ($hostName !== '' ? $hostName . '-quick' : 'backup-quick'))),
            'private_key' => (string) ($input['private_key'] ?? ''),
            'deploy_password' => (string) ($input['deploy_password'] ?? ''),
            'repo_mode' => $repoMode,
            'existing_repo_id' => ($input['existing_repo_id'] ?? '') !== '' ? (int) $input['existing_repo_id'] : null,
            'repo_name' => $repoName,
            'repo_path' => $repoPath,
            'remote_repo_path' => $remoteRepoPath,
            'repo_password_source' => $repoPasswordSource,
            'repo_infisical_secret_name' => $repoInfisicalSecretName,
            'repo_password' => (string) ($input['repo_password'] ?? ''),
            'repo_description' => trim((string) ($input['repo_description'] ?? '')),
            'init_repo_if_missing' => array_key_exists('init_repo_if_missing', $input) ? !empty($input['init_repo_if_missing']) : true,
            'job_name' => $jobName,
            'job_description' => trim((string) ($input['job_description'] ?? '')),
            'source_paths' => array_values(array_filter(array_map('trim', $sourcePaths))),
            'excludes' => array_values(array_filter(array_map('trim', $excludes))),
            'tags' => array_values(array_filter(array_map('trim', $tags))),
            'schedule_enabled' => $scheduleEnabled,
            'schedule_hour' => max(0, min(23, (int) ($input['schedule_hour'] ?? ($defaults['schedule_hour'] ?? 2)))),
            'schedule_days' => $days,
            'retention_enabled' => $retentionEnabled,
            'retention_keep_last' => max(0, (int) ($input['retention_keep_last'] ?? ($defaults['retention_keep_last'] ?? 0))),
            'retention_keep_daily' => max(0, (int) ($input['retention_keep_daily'] ?? ($defaults['retention_keep_daily'] ?? 0))),
            'retention_keep_weekly' => max(0, (int) ($input['retention_keep_weekly'] ?? ($defaults['retention_keep_weekly'] ?? 0))),
            'retention_keep_monthly' => max(0, (int) ($input['retention_keep_monthly'] ?? ($defaults['retention_keep_monthly'] ?? 0))),
            'retention_keep_yearly' => max(0, (int) ($input['retention_keep_yearly'] ?? ($defaults['retention_keep_yearly'] ?? 0))),
            'retention_prune' => array_key_exists('retention_prune', $input)
                ? !empty($input['retention_prune'])
                : !empty($defaults['retention_prune']),
            'run_after_create' => !empty($input['run_after_create']),
            'hostname_override' => trim((string) ($input['hostname_override'] ?? '')),
        ];
    }

    public static function buildHumanSummary(array $payload): array {
        $sourceLabel = $payload['host_mode'] === 'existing'
            ? self::existingHostLabel((int) ($payload['existing_host_id'] ?? 0))
            : (($payload['host_name'] !== '' ? $payload['host_name'] : 'Nouvel hote') . ' (' . $payload['user'] . '@' . $payload['hostname'] . ':' . $payload['port'] . ')');

        $repoLabel = $payload['repo_mode'] === 'existing'
            ? self::existingRepoLabel((int) ($payload['existing_repo_id'] ?? 0))
            : ($payload['repo_name'] . ' -> ' . $payload['repo_path']);

        $schedule = $payload['schedule_enabled']
            ? 'Actif a ' . sprintf('%02d:00', $payload['schedule_hour']) . ' les ' . implode(', ', array_map([self::class, 'dayLabel'], $payload['schedule_days']))
            : 'Non planifie';

        $retention = $payload['retention_enabled']
            ? self::retentionLabel($payload)
            : 'Aucune retention automatique';

        return [
            ['label' => 'Template', 'value' => (string) (($payload['template']['name'] ?? 'Template rapide'))],
            ['label' => 'Machine source', 'value' => $sourceLabel],
            ['label' => 'Acces SSH', 'value' => self::sshModeLabel($payload)],
            ['label' => 'Depot cible', 'value' => $repoLabel],
            ['label' => 'Depot vu depuis la machine source', 'value' => $payload['remote_repo_path'] !== '' ? $payload['remote_repo_path'] : 'Meme chemin que le depot principal'],
            ['label' => 'Stockage du secret depot', 'value' => self::repoPasswordSourceLabel($payload)],
            ['label' => 'Contenu sauvegarde', 'value' => implode(', ', $payload['source_paths'])],
            ['label' => 'Exclusions', 'value' => $payload['excludes'] !== [] ? implode(', ', $payload['excludes']) : 'Aucune'],
            ['label' => 'Tags', 'value' => $payload['tags'] !== [] ? implode(', ', $payload['tags']) : 'Aucun'],
            ['label' => 'Planification', 'value' => $schedule],
            ['label' => 'Retention', 'value' => $retention],
            ['label' => 'Test apres creation', 'value' => $payload['run_after_create'] ? 'Oui, lancer le premier backup maintenant' : 'Non, creation seule'],
        ];
    }

    public static function exportPayload(array $payload): array {
        $export = $payload;
        unset($export['private_key'], $export['deploy_password'], $export['repo_password']);
        $export['template'] = [
            'reference' => (string) (($payload['template']['reference'] ?? '')),
            'name' => (string) (($payload['template']['name'] ?? '')),
        ];
        return $export;
    }

    public static function renderPattern(string $pattern, array $tokens): string {
        $hostName = trim((string) ($tokens['host_name'] ?? 'host'));
        $hostname = trim((string) ($tokens['hostname'] ?? $hostName));
        $slugSource = $hostName !== '' ? $hostName : $hostname;
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $slugSource) ?? 'host');
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'host';
        }

        return strtr($pattern, [
            '{{host_name}}' => $hostName !== '' ? $hostName : $hostname,
            '{{hostname}}' => $hostname !== '' ? $hostname : $hostName,
            '{{host_slug}}' => $slug,
            '{{app_name}}' => AppConfig::appName(),
        ]);
    }

    private static function resolvePatternField(string $value, string $fallbackPattern, string $hostName, string $hostname): string {
        $candidate = $value !== '' ? $value : $fallbackPattern;
        if ($candidate === '') {
            return '';
        }

        if (!str_contains($candidate, '{{')) {
            return $candidate;
        }

        return self::renderPattern($candidate, [
            'host_name' => $hostName,
            'hostname' => $hostname,
        ]);
    }

    private static function buildChecks(array $payload, bool $allowSideEffects): array {
        $checks = [];

        $checks[] = [
            'status' => 'success',
            'title' => 'Permissions',
            'message' => 'Vous avez les droits nécessaires pour créer une clé SSH, un hôte, un dépôt et un job de sauvegarde.',
            'action' => '',
            'blocks_create' => false,
            'blocks_test' => false,
        ];

        if ($payload['host_mode'] === 'existing') {
            $host = HostManager::getById((int) ($payload['existing_host_id'] ?? 0));
            if (!$host) {
                $checks[] = self::issue('error', 'Machine source introuvable', 'L\'hôte sélectionné n\'existe plus. Choisissez un hôte valide dans la liste.', 'Sélectionnez un hôte existant.', true, true);
            } else {
                $result = HostManager::testConnection($host);
                $checks[] = [
                    'status' => !empty($result['success']) ? 'success' : 'warning',
                    'title' => 'Accès SSH (hôte existant)',
                    'message' => !empty($result['success'])
                        ? 'La connexion SSH vers cet hôte est opérationnelle avec la clé associée.'
                        : 'La connexion SSH ne répond pas avec la clé actuellement associée à cet hôte.',
                    'action' => !empty($result['success']) ? '' : 'Allez dans l\'écran Hôtes → Tester/Setup pour diagnostiquer et corriger l\'accès.',
                    'details' => (string) ($result['output'] ?? ''),
                    'blocks_create' => false,
                    'blocks_test' => !empty($result['success']) ? false : true,
                ];
            }
        } else {
            if ($payload['host_name'] === '' || $payload['hostname'] === '') {
                $checks[] = self::issue('error', 'Machine source incomplète', 'Le nom affiché et l\'adresse SSH (IP ou FQDN) de la machine source sont obligatoires.', 'Renseignez le nom et l\'adresse SSH de la machine à sauvegarder.', true, true);
            } else {
                $checks[] = [
                    'status' => 'success',
                    'title' => 'Machine source',
                    'message' => 'La machine source est correctement renseignée — l\'hôte sera créé pendant la procédure.',
                    'action' => '',
                    'blocks_create' => false,
                    'blocks_test' => false,
                ];
            }

            $checks[] = self::sshCheckForNewHost($payload, $allowSideEffects);
        }

        if ($payload['repo_mode'] === 'existing') {
            $repo = RepoManager::getById((int) ($payload['existing_repo_id'] ?? 0));
            if (!$repo) {
                $checks[] = self::issue('error', 'Dépôt introuvable', 'Le dépôt sélectionné n\'existe plus ou n\'est plus accessible. Choisissez un dépôt valide.', 'Sélectionnez un dépôt existant.', true, true);
            } else {
                $restic = RepoManager::getRestic($repo);
                $result = $restic->ping();
                $checks[] = [
                    'status' => $result ? 'success' : 'warning',
                    'title' => 'Dépôt existant',
                    'message' => $result
                        ? 'Le dépôt sélectionné est accessible et répond correctement.'
                        : 'Le dépôt existe en base mais ne répond pas avec sa configuration actuelle.',
                    'action' => $result ? '' : 'Vérifiez le mot de passe et la connectivité depuis l\'écran Dépôts.',
                    'blocks_create' => false,
                    'blocks_test' => $result ? false : true,
                ];
            }
        } else {
            if ($payload['repo_name'] === '' || $payload['repo_path'] === '') {
                $checks[] = self::issue('error', 'Dépôt incomplet', 'Le nom et le chemin du dépôt sont obligatoires.', 'Renseignez le nom et le chemin du dépôt.', true, true);
            } elseif (($pathError = self::validateRepoPath($payload['repo_path'])) !== null) {
                $checks[] = self::issue('error', 'Chemin du dépôt invalide', $pathError, 'Corrigez le chemin du dépôt.', true, true);
            } else {
                $passwordError = self::validateRepoSecretSelection($payload);
                if ($passwordError !== null) {
                    $checks[] = self::issue('error', 'Mot de passe du dépôt manquant', $passwordError, 'Choisissez un stockage valide pour le mot de passe du dépôt.', true, true);
                } else {
                    $preflightKey = self::resolveSshPrivateKeyForPayload($payload);
                    $restic = new Restic($payload['repo_path'], self::resolveRepoEffectivePassword($payload), $preflightKey);
                    $result = $restic->ping();
                    if ($result) {
                        $checks[] = [
                            'status' => 'success',
                            'title' => 'Dépôt de destination',
                            'message' => 'Le dépôt est accessible et répond correctement — il sera rattaché tel quel.',
                            'action' => '',
                            'blocks_create' => false,
                            'blocks_test' => false,
                        ];
                    } else {
                        $isSftp = self::isSftpPath($payload['repo_path']);
                        $keyUnavailable = $isSftp && $preflightKey === null && $payload['key_mode'] === 'generate';
                        if ($keyUnavailable) {
                            $checks[] = [
                                'status' => 'info',
                                'title' => 'Dépôt de destination',
                                'message' => 'La vérification anticipée du dépôt n\'est pas possible car la clé SSH sera générée pendant la création.',
                                'action' => $payload['init_repo_if_missing']
                                    ? 'Le dépôt sera initialisé automatiquement pendant la création.'
                                    : 'Activez « Créer le dépôt s\'il est absent » pour qu\'il soit initialisé automatiquement.',
                                'blocks_create' => !$payload['init_repo_if_missing'],
                                'blocks_test' => true,
                            ];
                        } else {
                            $checks[] = [
                                'status' => $payload['init_repo_if_missing'] ? 'warning' : 'error',
                                'title' => 'Dépôt de destination',
                                'message' => $payload['init_repo_if_missing']
                                    ? 'Le dépôt n\'est pas encore accessible à cette adresse — il sera initialisé automatiquement lors de la création.'
                                    : 'Le dépôt est inaccessible et l\'initialisation automatique est désactivée.',
                                'action' => $payload['init_repo_if_missing']
                                    ? 'Vérifiez quand même le chemin si vous attendiez un dépôt déjà existant.'
                                    : 'Activez « Créer le dépôt s\'il est absent » ou corrigez le chemin du dépôt.',
                                'blocks_create' => !$payload['init_repo_if_missing'],
                                'blocks_test' => true,
                            ];
                        }
                    }
                }
            }
        }

        if ($payload['source_paths'] === []) {
            $checks[] = self::issue('error', 'Contenu à sauvegarder manquant', 'Ajoutez at least un chemin absolu à sauvegarder (ex : /etc, /home).', 'Renseignez un chemin par ligne dans l\'étape Contenu.', true, true);
        } else {
            $invalidPath = self::firstInvalidSourcePath($payload['source_paths']);
            if ($invalidPath !== null) {
                $checks[] = self::issue('error', 'Chemin source invalide', 'Tous les chemins doivent être absolus (commencer par / ou X:\\).', 'Corrigez le chemin : ' . $invalidPath, true, true);
            } else {
                $n = count($payload['source_paths']);
                $checks[] = [
                    'status' => 'success',
                    'title' => 'Contenu à sauvegarder',
                    'message' => $n . ' ' . ($n > 1 ? 'chemins sources définis' : 'chemin source défini') . '.',
                    'action' => '',
                    'blocks_create' => false,
                    'blocks_test' => false,
                ];
            }
        }

        if ($payload['run_after_create']) {
            $canTest = self::canRunImmediateTest($payload);
            $checks[] = [
                'status' => $canTest ? 'success' : 'warning',
                'title' => 'Premier test immédiat',
                'message' => $canTest
                    ? 'La première sauvegarde de test sera lancée automatiquement juste après la création.'
                    : 'La création peut démarrer, mais le test immédiat ne sera pas possible tant que l\'accès SSH n\'est pas opérationnel.',
                'action' => $canTest ? '' : 'Fournissez un mot de passe de déploiement pour que la clé soit copiée automatiquement, ou déployez-la manuellement avant le test.',
                'blocks_create' => false,
                'blocks_test' => !$canTest,
            ];
        }

        return $checks;
    }

    private static function sshCheckForNewHost(array $payload, bool $allowSideEffects): array {
        $hostLabel = $payload['user'] . '@' . $payload['hostname'] . ':' . $payload['port'];
        if ($payload['key_mode'] === 'existing') {
            $key = SshKeyManager::getById((int) ($payload['existing_key_id'] ?? 0));
            if (!$key) {
                return self::issue('error', 'Clé SSH introuvable', 'La clé SSH sélectionnée n\'existe plus. Choisissez une clé valide dans la liste.', 'Sélectionnez une clé SSH existante.', true, true);
            }

            $result = self::testSshWithStoredKey((int) $key['id'], $payload['hostname'], $payload['port'], $payload['user']);
            return [
                'status' => !empty($result['success']) ? 'success' : 'warning',
                'title' => 'Accès SSH (clé existante)',
                'message' => !empty($result['success'])
                    ? 'La clé sélectionnée est bien acceptée par ' . $hostLabel . '.'
                    : 'La clé sélectionnée n\'est pas encore acceptée par ' . $hostLabel . '.',
                'action' => !empty($result['success'])
                    ? ''
                    : (!empty($payload['deploy_password'])
                        ? 'Un déploiement automatique de la clé sera tenté pendant la création grâce au mot de passe fourni.'
                        : 'Fournissez un mot de passe de déploiement pour copier la clé automatiquement, ou déployez-la manuellement sur la machine cible.'),
                'details' => (string) ($result['output'] ?? ''),
                'blocks_create' => false,
                'blocks_test' => empty($result['success']) && empty($payload['deploy_password']),
            ];
        }

        if ($payload['key_mode'] === 'import') {
            if (trim($payload['private_key']) === '') {
                return self::issue('error', 'Clé privée manquante', 'Collez le contenu d\'une clé privée valide (format PEM) pour continuer.', 'Ajoutez la clé privée dans le champ prévu.', true, true);
            }

            $result = self::testSshWithPrivateKey($payload['private_key'], $payload['hostname'], $payload['port'], $payload['user']);
            if (!empty($result['success'])) {
                return [
                    'status' => 'success',
                    'title' => 'Accès SSH (clé importée)',
                    'message' => 'La clé importée est acceptée par ' . $hostLabel . ' — connexion opérationnelle.',
                    'action' => '',
                    'details' => (string) ($result['output'] ?? ''),
                    'blocks_create' => false,
                    'blocks_test' => false,
                ];
            }

            return [
                'status' => 'warning',
                'title' => 'Clé importée à déployer',
                'message' => 'La clé importée n\'est pas encore autorisée sur ' . $hostLabel . '.',
                'action' => !empty($payload['deploy_password'])
                    ? 'Un déploiement automatique sera tenté pendant la création grâce au mot de passe fourni.'
                    : 'Fournissez un mot de passe de déploiement pour copier la clé automatiquement sur la machine cible.',
                'details' => (string) ($result['output'] ?? ''),
                'blocks_create' => false,
                'blocks_test' => empty($payload['deploy_password']),
            ];
        }

        return [
            'status' => 'warning',
            'title' => 'Nouvelle clé SSH à générer',
            'message' => 'Une nouvelle paire de clés SSH sera générée et associée à cet hôte pendant la création.',
            'action' => !empty($payload['deploy_password'])
                ? 'Le déploiement automatique sera tenté grâce au mot de passe fourni.'
                : 'Fournissez un mot de passe de déploiement pour copier la clé automatiquement, ou une commande manuelle vous sera fournie à la fin.',
            'blocks_create' => false,
            'blocks_test' => empty($payload['deploy_password']),
        ];
    }

    private static function resolveKeyForCreate(array $payload, ?callable $logger = null): array {
        if ($payload['host_mode'] === 'existing') {
            $host = HostManager::getById((int) $payload['existing_host_id']);
            if (!$host || empty($host['ssh_key_id'])) {
                throw new RuntimeException('L hote selectionne ne possede pas de cle SSH exploitable.');
            }
            self::emitLog($logger, 'Utilisation de la cle SSH deja associee a l hote existant.');
            return [
                'id' => (int) $host['ssh_key_id'],
                'public_key' => '',
                'manual_command' => '',
                'deploy_warning' => '',
            ];
        }

        if ($payload['key_mode'] === 'existing') {
            $key = SshKeyManager::getById((int) ($payload['existing_key_id'] ?? 0));
            if (!$key) {
                throw new RuntimeException('La cle SSH selectionnee est introuvable.');
            }
            self::emitLog($logger, 'Reutilisation de la cle SSH existante #' . (int) $key['id'] . '.');
            $manualCommand = '';
            $deployWarning = '';
            if (empty(self::testSshWithStoredKey((int) $key['id'], $payload['hostname'], $payload['port'], $payload['user'])['success']) && $payload['deploy_password'] === '') {
                $manualCommand = 'echo "' . str_replace('"', '\"', (string) ($key['public_key'] ?? '')) . '" >> ~/.ssh/authorized_keys';
                self::emitLog($logger, 'La cle existe deja mais devra etre deployee manuellement sur l hote.');
            } elseif ($payload['deploy_password'] !== '') {
                self::emitLog($logger, 'Deploiement automatique de la cle SSH existante...');
                $deploy = SshKeyManager::deployKey(
                    (int) $key['id'],
                    $payload['deploy_password'],
                    $payload['hostname'],
                    $payload['user'],
                    (int) $payload['port']
                );
                if (empty($deploy['success'])) {
                    Auth::log('ssh_key_deploy', "Déploiement clé vers {$payload['user']}@{$payload['hostname']} — ECHEC");
                    $manualCommand = 'echo "' . str_replace('"', '\"', (string) ($key['public_key'] ?? '')) . '" >> ~/.ssh/authorized_keys';
                    $deployWarning = 'Impossible de deployer la cle SSH existante automatiquement : ' . trim((string) ($deploy['output'] ?? ''));
                    self::emitLog($logger, 'Deploiement automatique impossible, bascule vers une installation manuelle de la cle.');
                } else {
                    Auth::log('ssh_key_deploy', "Déploiement clé vers {$payload['user']}@{$payload['hostname']} — OK");
                    self::emitLog($logger, 'Cle SSH existante deployee avec succes.');
                }
            }

            return [
                'id' => (int) $key['id'],
                'public_key' => (string) ($key['public_key'] ?? ''),
                'manual_command' => $manualCommand,
                'deploy_warning' => $deployWarning,
            ];
        }

        if ($payload['key_mode'] === 'import') {
            self::emitLog($logger, 'Import de la cle SSH fournie...');
            $key = ProvisioningManager::importSshKey(
                $payload['key_name'],
                $payload['hostname'],
                $payload['user'],
                $payload['port'],
                $payload['private_key'],
                'Flux rapide',
                $logger
            );
        } else {
            self::emitLog($logger, 'Generation d une nouvelle cle SSH...');
            $key = ProvisioningManager::generateSshKey(
                $payload['key_name'],
                $payload['hostname'],
                $payload['user'],
                $payload['port'],
                'Flux rapide',
                $logger
            );
        }

        if (empty($key['success'])) {
            throw new RuntimeException('Impossible de preparer la cle SSH : ' . trim((string) ($key['error'] ?? '')));
        }

        $manualCommand = '';
        $deployWarning = '';
        if ($payload['deploy_password'] !== '') {
            self::emitLog($logger, 'Deploiement automatique de la nouvelle cle SSH...');
            $deploy = SshKeyManager::deployKey(
                (int) $key['id'],
                $payload['deploy_password'],
                $payload['hostname'],
                $payload['user'],
                (int) $payload['port']
            );
            if (empty($deploy['success'])) {
                Auth::log('ssh_key_deploy', "Déploiement clé vers {$payload['user']}@{$payload['hostname']} — ECHEC");
                $manualCommand = 'echo "' . str_replace('"', '\"', (string) ($key['public_key'] ?? '')) . '" >> ~/.ssh/authorized_keys';
                $deployWarning = 'La cle a ete creee mais son deploiement automatique a echoue : ' . trim((string) ($deploy['output'] ?? ''));
                self::emitLog($logger, 'Deploiement automatique impossible, la creation continue avec une etape manuelle pour installer la cle.');
            } else {
                Auth::log('ssh_key_deploy', "Déploiement clé vers {$payload['user']}@{$payload['hostname']} — OK");
                self::emitLog($logger, 'Nouvelle cle deployee avec succes.');
            }
        } else {
            $manualCommand = 'echo "' . str_replace('"', '\"', (string) ($key['public_key'] ?? '')) . '" >> ~/.ssh/authorized_keys';
            self::emitLog($logger, 'Aucun mot de passe de deploiement fourni : deploiement manuel requis.');
        }

        return [
            'id' => (int) $key['id'],
            'public_key' => (string) ($key['public_key'] ?? ''),
            'manual_command' => $manualCommand,
            'deploy_warning' => $deployWarning,
        ];
    }

    private static function createRepo(array $payload, ?int $sshKeyId, ?callable $logger = null): array {
        return ProvisioningManager::createRepo([
            'name' => trim($payload['repo_name']),
            'path' => trim($payload['repo_path']),
            'password_source' => self::normalizeRepoPasswordSource((string) ($payload['repo_password_source'] ?? '')),
            'password' => (string) ($payload['repo_password'] ?? ''),
            'infisical_secret_name' => trim((string) ($payload['repo_infisical_secret_name'] ?? '')),
            'description' => trim($payload['repo_description']),
            'alert_hours' => AppConfig::backupAlertHours(),
            'init_if_missing' => !empty($payload['init_repo_if_missing']),
            'ssh_key_id' => $sshKeyId,
        ], $logger);
    }

    private static function secretStorageModes(): array {
        return [
            [
                'value' => 'agent',
                'label' => 'Broker local (recommande)',
                'available' => true,
            ],
            [
                'value' => 'local',
                'label' => 'Local chiffre fallback',
                'available' => true,
            ],
            [
                'value' => 'infisical',
                'label' => 'Secret Infisical',
                'available' => InfisicalClient::isConfigured(),
            ],
        ];
    }

    private static function normalizeRepoPasswordSource(string $source): string {
        $source = trim($source);
        if ($source === 'infisical') {
            return 'infisical';
        }
        if ($source === '') {
            return SecretStore::defaultWritableSource();
        }
        try {
            return SecretStore::normalizeWritableSource($source);
        } catch (Throwable $e) {
            return SecretStore::defaultWritableSource();
        }
    }

    private static function repoPasswordSourceLabel(array $payload): string {
        $source = self::normalizeRepoPasswordSource((string) ($payload['repo_password_source'] ?? ''));
        return match ($source) {
            'infisical' => 'Secret Infisical' . (!empty($payload['repo_infisical_secret_name']) ? ' — ' . (string) $payload['repo_infisical_secret_name'] : ''),
            'local' => 'Local chiffre fallback',
            default => 'Broker local (recommande)',
        };
    }

    private static function validateRepoPath(string $path): ?string {
        if ($path === '') {
            return null;
        }
        // sftp: SCP-like without path component, e.g. "sftp:user@host" or "sftp:host"
        if (preg_match('#^sftp:(?:[^@/]+@)?[^:/]+$#i', $path)) {
            return 'Chemin SFTP incomplet : le chemin du depot sur le serveur est manquant. Format attendu : sftp:user@host:/chemin/depot';
        }
        // sftp:// URL without path, e.g. "sftp://user@host" or "sftp://user@host:"
        if (preg_match('#^sftp://[^/]*/?$#i', $path)) {
            return 'Chemin SFTP incomplet : le chemin du depot sur le serveur est manquant. Format attendu : sftp://user@host/path/depot';
        }
        return null;
    }

    private static function validateRepoSecretSelection(array $payload): ?string {
        $source = self::normalizeRepoPasswordSource((string) ($payload['repo_password_source'] ?? ''));
        if ($source === 'infisical') {
            if (!InfisicalClient::isConfigured()) {
                return 'Infisical n est pas configure sur cette instance.';
            }
            if (trim((string) ($payload['repo_infisical_secret_name'] ?? '')) === '') {
                return 'Le nom du secret Infisical est requis pour le depot.';
            }
            try {
                self::resolveRepoEffectivePassword($payload);
            } catch (Throwable $e) {
                return $e->getMessage();
            }
            return null;
        }
        return trim((string) ($payload['repo_password'] ?? '')) === ''
            ? 'Le mot de passe du depot est requis.'
            : null;
    }

    private static function resolveRepoEffectivePassword(array $payload): string {
        $source = self::normalizeRepoPasswordSource((string) ($payload['repo_password_source'] ?? ''));
        if ($source !== 'infisical') {
            $password = (string) ($payload['repo_password'] ?? '');
            if ($password === '') {
                throw new RuntimeException('Le mot de passe du depot est requis.');
            }
            return $password;
        }

        if (!InfisicalClient::isConfigured()) {
            throw new RuntimeException('Infisical n est pas configure sur cette instance.');
        }
        $secretName = trim((string) ($payload['repo_infisical_secret_name'] ?? ''));
        if ($secretName === '') {
            throw new RuntimeException('Le nom du secret Infisical est requis pour le depot.');
        }
        $secret = InfisicalClient::getSecret($secretName);
        if ($secret === null || $secret === '') {
            throw new RuntimeException('Impossible de recuperer le secret « ' . $secretName . ' » depuis Infisical.');
        }
        return $secret;
    }

    private static function isSftpPath(string $path): bool {
        return str_starts_with(strtolower(trim($path)), 'sftp:');
    }

    private static function resolveSshPrivateKeyForPayload(array $payload): ?string {
        $keyMode = (string) ($payload['key_mode'] ?? '');

        if ($keyMode === 'import') {
            $key = trim((string) ($payload['private_key'] ?? ''));
            return $key !== '' ? $key : null;
        }

        if ($keyMode === 'existing') {
            $keyId = ($payload['existing_key_id'] ?? '') !== '' ? (int) $payload['existing_key_id'] : null;
            if ($keyId !== null && $keyId > 0) {
                try {
                    $sshKey = SshKeyManager::getById($keyId);
                    if ($sshKey) {
                        $ref = (string) ($sshKey['private_key_file'] ?? '');
                        if (str_starts_with($ref, 'secret://')) {
                            return SecretStore::get($ref) ?: null;
                        } elseif ($ref !== '' && is_file($ref)) {
                            return @file_get_contents($ref) ?: null;
                        }
                    }
                } catch (Throwable) {}
            }
        }

        return null;
    }

    private static function canRunImmediateTest(array $payload): bool {
        if (!$payload['run_after_create']) {
            return false;
        }

        if ($payload['host_mode'] === 'existing') {
            return true;
        }

        if ($payload['key_mode'] === 'existing') {
            if (!empty(self::testSshWithStoredKey((int) ($payload['existing_key_id'] ?? 0), $payload['hostname'], $payload['port'], $payload['user'])['success'])) {
                return true;
            }
            return $payload['deploy_password'] !== '';
        }

        if ($payload['key_mode'] === 'import') {
            if (!empty(self::testSshWithPrivateKey($payload['private_key'], $payload['hostname'], $payload['port'], $payload['user'])['success'])) {
                return true;
            }
            return $payload['deploy_password'] !== '';
        }

        return $payload['deploy_password'] !== '';
    }

    private static function launchImmediateTest(int $jobId): array {
        $run = RunLogManager::createRun('backup');
        $runId = $run['run_id'];
        $logFile = $run['log_file'];
        $pidFile = $run['pid_file'];
        $scriptPath = dirname(__DIR__) . '/public/api/run_backup_background.php';
        if (!is_file($scriptPath)) {
            throw new RuntimeException('Script de test background introuvable.');
        }

        $launch = ProcessRunner::startBackgroundPhp($scriptPath, [$jobId, $logFile], $logFile, $pidFile);
        if (empty($launch['success'])) {
            throw new RuntimeException('Impossible de demarrer le test de sauvegarde.');
        }

        return [
            'started' => true,
            'run_id' => $runId,
            'pid' => isset($launch['pid']) ? (string) $launch['pid'] : null,
        ];
    }

    private static function emitLog(?callable $logger, string $message): void {
        if ($logger !== null) {
            $logger($message);
        }
    }

    private static function testSshWithStoredKey(int $keyId, string $hostname, int $port, string $user): array {
        if ($keyId <= 0 || $hostname === '') {
            return ['success' => false, 'output' => 'Parametres SSH incomplets'];
        }

        try {
            $keyFile = SshKeyManager::getTemporaryKeyFile($keyId);
        } catch (Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }

        return self::testSshWithKeyFile($keyFile, $hostname, $port, $user, true);
    }

    private static function testSshWithPrivateKey(string $privateKey, string $hostname, int $port, string $user): array {
        if (trim($privateKey) === '' || $hostname === '') {
            return ['success' => false, 'output' => 'Parametres SSH incomplets'];
        }

        $keyFile = Restic::writeTempSecretFile($privateKey, 'quick_flow_key_');
        return self::testSshWithKeyFile($keyFile, $hostname, $port, $user, true);
    }

    private static function testSshWithKeyFile(string $keyFile, string $hostname, int $port, string $user, bool $unlinkKey): array {
        $tmpHome = self::createTempHome('quick-flow-ssh-');
        $cmd = array_merge([
            SSH_BIN,
            '-i', $keyFile,
            '-p', (string) $port,
        ], SshKnownHosts::sshOptions($hostname, $port, 8), [
            $user . '@' . $hostname,
            'echo QUICK_FLOW_OK',
        ]);

        $result = ProcessRunner::run($cmd, [
            'env' => [
                'HOME' => $tmpHome,
                'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            ],
        ]);

        FileSystem::removeDirectory($tmpHome);
        if ($unlinkKey) {
            @unlink($keyFile);
        }

        $final = SshKnownHosts::finalizeSshResult([
            'success' => (int) ($result['code'] ?? 1) === 0 && str_contains(trim((string) ($result['output'] ?? '')), 'QUICK_FLOW_OK'),
            'output' => trim((string) ($result['output'] ?? '')),
            'code' => (int) ($result['code'] ?? 1),
        ], $hostname, $port, 'quick_flow_ssh_test');
        return [
            'success' => !empty($final['success']),
            'output' => (string) ($final['output'] ?? ''),
            'host_key' => $final['host_key'] ?? null,
        ];
    }

    private static function createTempHome(string $prefix): string {
        $tmpHome = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(6));
        @mkdir($tmpHome . DIRECTORY_SEPARATOR . '.ssh', 0700, true);
        return $tmpHome;
    }

    private static function existingHostLabel(int $hostId): string {
        $host = HostManager::getById($hostId);
        if (!$host) {
            return 'Hote existant a selectionner';
        }

        return (string) ($host['name'] ?? 'Hote') . ' (' . (string) ($host['user'] ?? 'root') . '@' . (string) ($host['hostname'] ?? '') . ':' . (int) ($host['port'] ?? 22) . ')';
    }

    private static function existingRepoLabel(int $repoId): string {
        $repo = RepoManager::getById($repoId);
        if (!$repo) {
            return 'Depot existant a selectionner';
        }

        return (string) ($repo['name'] ?? 'Depot') . ' -> ' . (string) ($repo['path'] ?? '');
    }

    private static function sshModeLabel(array $payload): string {
        if ($payload['host_mode'] === 'existing') {
            return 'Reutiliser la cle deja associee a l hote selectionne';
        }

        return match ($payload['key_mode']) {
            'existing' => 'Utiliser une cle SSH existante',
            'import' => 'Importer une cle privee existante',
            default => 'Generer une nouvelle cle SSH',
        };
    }

    private static function retentionLabel(array $payload): string {
        $parts = [];
        if ($payload['retention_keep_last'] > 0) {
            $parts[] = $payload['retention_keep_last'] . ' derniers';
        }
        if ($payload['retention_keep_daily'] > 0) {
            $parts[] = $payload['retention_keep_daily'] . ' quotidiens';
        }
        if ($payload['retention_keep_weekly'] > 0) {
            $parts[] = $payload['retention_keep_weekly'] . ' hebdos';
        }
        if ($payload['retention_keep_monthly'] > 0) {
            $parts[] = $payload['retention_keep_monthly'] . ' mensuels';
        }
        if ($payload['retention_keep_yearly'] > 0) {
            $parts[] = $payload['retention_keep_yearly'] . ' annuels';
        }
        if ($parts === []) {
            $parts[] = 'configuration manuelle a completer';
        }

        return implode(', ', $parts) . ($payload['retention_prune'] ? ' + prune' : '');
    }

    private static function dayLabel(string $day): string {
        return [
            '1' => 'lun.',
            '2' => 'mar.',
            '3' => 'mer.',
            '4' => 'jeu.',
            '5' => 'ven.',
            '6' => 'sam.',
            '7' => 'dim.',
        ][$day] ?? $day;
    }

    private static function splitLines(string $value): array {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $value))));
    }

    private static function splitCsv(string $value): array {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private static function firstInvalidSourcePath(array $paths): ?string {
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            try {
                // Remote quick-flow: paths live on the remote host, so skip local virtual FS checks.
                FilesystemScopeGuard::assertValidBackupSourcePath($path, false);
            } catch (Throwable) {
                return $path;
            }
        }

        return null;
    }

    private static function issue(
        string $status,
        string $title,
        string $message,
        string $action,
        bool $blocksCreate,
        bool $blocksTest
    ): array {
        return [
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'action' => $action,
            'blocks_create' => $blocksCreate,
            'blocks_test' => $blocksTest,
        ];
    }

    private static function hasFatalCheck(array $checks): bool {
        foreach ($checks as $check) {
            if (!empty($check['blocks_create'])) {
                return true;
            }
        }
        return false;
    }

    private static function hasTestBlockingCheck(array $checks): bool {
        foreach ($checks as $check) {
            if (!empty($check['blocks_test'])) {
                return true;
            }
        }
        return false;
    }
}
