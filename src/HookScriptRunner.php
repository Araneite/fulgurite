<?php

class HookScriptRunner {
    public static function runJobHook(array $job, string $phase): array {
        $field = $phase === 'post' ? 'post_hook_script_id' : 'pre_hook_script_id';
        $scriptId = (int) ($job[$field] ?? 0);
        if ($scriptId <= 0) {
            return ['success' => true, 'skipped' => true, 'output' => ''];
        }

        $script = HookScriptManager::getById($scriptId);
        if (!$script) {
            return ['success' => false, 'output' => "Script hook introuvable (#{$scriptId}).", 'code' => 1];
        }
        if (($script['status'] ?? 'active') !== 'active') {
            return ['success' => false, 'output' => "Le script '{$script['name']}' est desactive.", 'code' => 1];
        }

        $jobMode = !empty($job['host_id']) && !empty($job['private_key_file']) ? 'remote' : 'local';
        $scriptScope = (string) ($script['execution_scope'] ?? 'both');
        if ($scriptScope !== 'both' && $scriptScope !== $jobMode) {
            return ['success' => false, 'output' => "Le script '{$script['name']}' n'est pas compatible avec le mode {$jobMode}.", 'code' => 1];
        }

        try {
            $content = HookScriptManager::getContent($script);
        } catch (Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage(), 'code' => 1];
        }

        $runtimeVariables = self::buildRuntimeVariables($job, $script, $jobMode, $phase);
        $parsed = HookScriptSecurity::parseApprovedContent($content, $jobMode, $runtimeVariables);
        if (!$parsed['ok']) {
            return ['success' => false, 'output' => implode("\n", $parsed['errors'] ?? ['Validation du script impossible.']), 'code' => 1];
        }

        $log = [];
        $log[] = "Script: {$script['name']} (#{$script['id']})";
        $log[] = 'Portee du script: ' . HookScriptManager::scopeLabel($scriptScope);
        $log[] = 'Mode d execution: ' . HookScriptManager::scopeLabel($jobMode);

        foreach ($parsed['instructions'] as $instruction) {
            $displayArgs = $instruction['args_display'] ?? $instruction['args'] ?? [];
            $display = $instruction['command'] . (empty($displayArgs) ? '' : ' ' . implode(' ', array_map('strval', $displayArgs)));
            $log[] = '$ ' . $display;
            $result = $jobMode === 'remote'
                ? self::runRemoteInstruction($job, $instruction)
                : self::runLocalInstruction($instruction);

            $output = trim((string) ($result['output'] ?? ''));
            if ($output !== '') {
                foreach (preg_split('/\r?\n/', $output) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $log[] = '  ' . $line;
                    }
                }
            }

            if (empty($result['success'])) {
                $log[] = "Commande echouee (code " . (int) ($result['code'] ?? 1) . ').';
                return [
                    'success' => false,
                    'output' => implode("\n", $log),
                    'code' => (int) ($result['code'] ?? 1),
                    'script' => $script,
                ];
            }
        }

        return [
            'success' => true,
            'output' => implode("\n", $log),
            'code' => 0,
            'script' => $script,
        ];
    }

    private static function runLocalInstruction(array $instruction): array {
        return Restic::runShell(array_merge([$instruction['binary']], $instruction['args']));
    }

    private static function runRemoteInstruction(array $job, array $instruction): array {
        $tmpHome = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'fulgurite-hook-' . uniqid('', true);
        mkdir($tmpHome . DIRECTORY_SEPARATOR . '.ssh', 0700, true);

        $remoteParts = array_merge([$instruction['binary']], $instruction['args']);
        $remoteCommand = implode(' ', array_map('escapeshellarg', $remoteParts));
        $cmd = array_merge([
            SSH_BIN,
            '-i', $job['private_key_file'],
            '-p', (string) $job['host_port'],
        ], SshKnownHosts::sshOptions((string) $job['host_hostname'], (int) $job['host_port'], 10), [
            $job['host_user'] . '@' . $job['host_hostname'],
            $remoteCommand,
        ]);
        $result = Restic::runShell($cmd, [
            'HOME' => $tmpHome,
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ]);
        self::removeDirectory($tmpHome);
        return SshKnownHosts::finalizeSshResult($result, (string) $job['host_hostname'], (int) $job['host_port'], 'hook_remote');
    }

    private static function removeDirectory(string $path): void {
        FileSystem::removeDirectory($path);
    }

    private static function buildRuntimeVariables(array $job, array $script, string $jobMode, string $phase): array {
        $sourcePaths = json_decode((string) ($job['source_paths'] ?? '[]'), true);
        if (!is_array($sourcePaths)) {
            $sourcePaths = [];
        }
        $tags = json_decode((string) ($job['tags'] ?? '[]'), true);
        if (!is_array($tags)) {
            $tags = [];
        }
        $excludes = json_decode((string) ($job['excludes'] ?? '[]'), true);
        if (!is_array($excludes)) {
            $excludes = [];
        }

        $remoteRepoPath = trim((string) ($job['remote_repo_path'] ?? ''));
        $repoPath = trim((string) ($job['repo_path'] ?? ''));
        $resolvedRepoPath = $remoteRepoPath !== '' ? $remoteRepoPath : $repoPath;
        $repoPathBasename = $repoPath !== '' ? basename(str_replace('\\', '/', $repoPath)) : '';
        $repo = !empty($job['repo_id']) ? RepoManager::getById((int) $job['repo_id']) : null;
        $cronUrl = trim(AppConfig::get('cron_entry_base_url', ''));
        $baseUrl = preg_replace('#/cron\.php$#', '', $cronUrl);
        $sourcePathBasenames = [];
        foreach ($sourcePaths as $sourcePath) {
            $sourcePathBasenames[] = basename(str_replace('\\', '/', (string) $sourcePath));
        }

        $variables = [
            'APP_NAME' => 'fulgurite',
            'APP_BASE_URL' => (string) ($baseUrl ?? ''),
            'SCRIPT_NAME' => (string) ($script['name'] ?? ''),
            'SCRIPT_SCOPE' => (string) ($script['execution_scope'] ?? 'both'),
            'JOB_ID' => (string) (int) ($job['id'] ?? 0),
            'JOB_NAME' => (string) ($job['name'] ?? ''),
            'JOB_DESCRIPTION' => (string) ($job['description'] ?? ''),
            'EXECUTION_SCOPE' => $jobMode,
            'EXECUTION_PHASE' => $phase === 'post' ? 'post' : 'pre',
            'CURRENT_DATE' => date('Y-m-d'),
            'CURRENT_TIME' => date('H:i:s'),
            'CURRENT_DATETIME' => date(DATE_ATOM),
            'EXECUTION_UNIX_TS' => (string) time(),
            'EXECUTION_DATE_COMPACT' => date('Ymd'),
            'EXECUTION_TIME_COMPACT' => date('His'),
            'REPO_ID' => (string) (int) ($job['repo_id'] ?? 0),
            'REPO_NAME' => (string) ($job['repo_name'] ?? ''),
            'REPO_PATH' => $repoPath,
            'REMOTE_REPO_PATH' => $resolvedRepoPath,
            'REPO_PATH_BASENAME' => $repoPathBasename,
            'REPO_PASSWORD_SOURCE' => (string) ($repo['password_source'] ?? ''),
            'REPO_INFISICAL_SECRET_NAME' => (string) ($repo['infisical_secret_name'] ?? ''),
            'REPO_ALERT_HOURS' => (string) ($repo['alert_hours'] ?? ''),
            'REPO_NOTIFY_EMAIL' => (string) ($repo['notify_email'] ?? ''),
            'HOST_ID' => (string) (int) ($job['host_id'] ?? 0),
            'HOST_NAME' => (string) ($job['host_name'] ?? ''),
            'HOST_HOSTNAME' => (string) ($job['host_hostname'] ?? ''),
            'HOST_USER' => (string) ($job['host_user'] ?? ''),
            'HOST_PORT' => (string) ($job['host_port'] ?? ''),
            'HOST_IS_REMOTE' => $jobMode === 'remote' ? '1' : '0',
            'HOST_SSH_TARGET' => trim((string) ($job['host_user'] ?? '')) !== '' && trim((string) ($job['host_hostname'] ?? '')) !== ''
                ? trim((string) $job['host_user']) . '@' . trim((string) $job['host_hostname']) . ':' . trim((string) ($job['host_port'] ?? '22'))
                : '',
            'HOSTNAME_OVERRIDE' => (string) ($job['hostname_override'] ?? ''),
            'JOB_LAST_RUN_AT' => (string) ($job['last_run'] ?? ''),
            'JOB_LAST_STATUS' => (string) ($job['last_status'] ?? ''),
            'JOB_SCHEDULE_ENABLED' => !empty($job['schedule_enabled']) ? '1' : '0',
            'JOB_SCHEDULE_HOUR' => (string) ($job['schedule_hour'] ?? ''),
            'JOB_SCHEDULE_DAYS' => (string) ($job['schedule_days'] ?? ''),
            'JOB_NOTIFY_ON_FAILURE' => !empty($job['notify_on_failure']) ? '1' : '0',
            'JOB_NOTIFICATION_POLICY_JSON' => (string) ($job['notification_policy'] ?? ''),
            'JOB_RETRY_POLICY_JSON' => (string) ($job['retry_policy'] ?? ''),
            'JOB_RETENTION_ENABLED' => !empty($job['retention_enabled']) ? '1' : '0',
            'JOB_RETENTION_KEEP_LAST' => (string) ($job['retention_keep_last'] ?? ''),
            'JOB_RETENTION_KEEP_DAILY' => (string) ($job['retention_keep_daily'] ?? ''),
            'JOB_RETENTION_KEEP_WEEKLY' => (string) ($job['retention_keep_weekly'] ?? ''),
            'JOB_RETENTION_KEEP_MONTHLY' => (string) ($job['retention_keep_monthly'] ?? ''),
            'JOB_RETENTION_KEEP_YEARLY' => (string) ($job['retention_keep_yearly'] ?? ''),
            'JOB_RETENTION_PRUNE' => !empty($job['retention_prune']) ? '1' : '0',
            'SOURCE_PATH_COUNT' => (string) count($sourcePaths),
            'SOURCE_PATHS_CSV' => implode(',', array_map('strval', $sourcePaths)),
            'SOURCE_PATHS_JSON' => json_encode(array_values($sourcePaths), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]',
            'TAG_COUNT' => (string) count($tags),
            'TAGS_CSV' => implode(',', array_map('strval', $tags)),
            'TAGS_JSON' => json_encode(array_values($tags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]',
            'EXCLUDE_COUNT' => (string) count($excludes),
            'EXCLUDES_CSV' => implode(',', array_map('strval', $excludes)),
            'EXCLUDES_JSON' => json_encode(array_values($excludes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]',
        ];

        foreach (range(1, 5) as $index) {
            $variables['SOURCE_PATH_' . $index] = isset($sourcePaths[$index - 1]) ? (string) $sourcePaths[$index - 1] : '';
            $variables['SOURCE_PATH_BASENAME_' . $index] = isset($sourcePathBasenames[$index - 1]) ? (string) $sourcePathBasenames[$index - 1] : '';
            $variables['TAG_' . $index] = isset($tags[$index - 1]) ? (string) $tags[$index - 1] : '';
            $variables['EXCLUDE_' . $index] = isset($excludes[$index - 1]) ? (string) $excludes[$index - 1] : '';
        }

        if (Database::getSetting('hook_expose_secrets', '0') === '1') {
            if ($repo) {
                $repoPassword = trim((string) RepoManager::getPassword($repo));
                if ($repoPassword !== '') {
                    $variables['SECRET_JOB_REPO_PASSWORD'] = $repoPassword;
                }
            }

            $secretSettings = [
                'SECRET_INSTANCE_SMTP_PASS' => Database::getSetting('smtp_pass'),
                'SECRET_INSTANCE_WEBHOOK_AUTH_TOKEN' => Database::getSetting('webhook_auth_token'),
                'SECRET_INSTANCE_INFISICAL_TOKEN' => Database::getSetting('infisical_token'),
                'SECRET_INSTANCE_GOTIFY_TOKEN' => Database::getSetting('gotify_token'),
                'SECRET_INSTANCE_TELEGRAM_BOT_TOKEN' => Database::getSetting('telegram_bot_token'),
                'SECRET_INSTANCE_DISCORD_WEBHOOK_URL' => Database::getSetting('discord_webhook_url'),
                'SECRET_INSTANCE_SLACK_WEBHOOK_URL' => Database::getSetting('slack_webhook_url'),
                'SECRET_INSTANCE_TEAMS_WEBHOOK_URL' => Database::getSetting('teams_webhook_url'),
            ];
            foreach ($secretSettings as $key => $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $variables[$key] = $value;
                }
            }
        }

        return $variables;
    }
}
