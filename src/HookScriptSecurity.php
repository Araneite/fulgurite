<?php

class HookScriptSecurity {
    private const VARIABLE_PATTERN = '/\{\{([A-Z0-9_]+)\}\}/';

    private static ?array $configCache = null;

    public static function config(): array {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        $path = dirname(__DIR__) . '/config/script_security.php';
        $config = is_file($path) ? require $path : [];
        self::$configCache = is_array($config) ? $config : [];
        return self::$configCache;
    }

    public static function bannedCommands(): array {
        return array_values(self::config()['banned_commands'] ?? []);
    }

    public static function bannedPatterns(): array {
        return array_values(self::config()['banned_patterns'] ?? []);
    }

    public static function allowedCommands(): array {
        $commands = self::config()['allowed_commands'] ?? [];
        return is_array($commands) ? $commands : [];
    }

    public static function autocompleteValues(): array {
        $values = self::config()['autocomplete_values'] ?? [];
        return is_array($values) ? $values : [];
    }

    public static function variableDefinitions(): array {
        $definitions = self::normalizeVariableDefinitions(self::config()['variables'] ?? []);
        foreach (self::dynamicVariableDefinitions() as $name => $definition) {
            $definitions[$name] = $definition;
        }
        ksort($definitions);
        return $definitions;
    }

    public static function allowedScopes(): array {
        $scopes = self::config()['allowed_scopes'] ?? ['local', 'remote', 'both'];
        return is_array($scopes) ? $scopes : ['local', 'remote', 'both'];
    }

    public static function maxBytes(): int {
        return max(1, (int) (self::config()['max_bytes'] ?? 16384));
    }

    public static function maxLines(): int {
        return max(1, (int) (self::config()['max_lines'] ?? 64));
    }

    public static function maxNameLength(): int {
        return max(8, (int) (self::config()['max_name_length'] ?? 120));
    }

    public static function normalizeName(string $name): string {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if (function_exists('mb_substr')) {
            return mb_substr($name, 0, self::maxNameLength());
        }
        return substr($name, 0, self::maxNameLength());
    }

    public static function normalizeContent(string $content): string {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        return trim($content);
    }

    public static function validate(string $name, string $content, string $scope = 'both', ?array $runtimeVariables = null): array {
        $errors = [];
        $name = self::normalizeName($name);
        $content = self::normalizeContent($content);
        $scope = trim(strtolower($scope));

        if ($name === '') {
            $errors[] = 'Le nom du script est requis.';
        }
        if (!in_array($scope, self::allowedScopes(), true)) {
            $errors[] = 'Portee de script invalide.';
        }
        if ($content === '') {
            $errors[] = 'Le contenu du script est vide.';
        }
        if (strlen($content) > self::maxBytes()) {
            $errors[] = 'Le script depasse la taille maximale autorisee.';
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content)) {
            $errors[] = 'Le script contient des caracteres de controle interdits.';
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $lines = preg_split('/\n/', $content) ?: [];
        if (count($lines) > self::maxLines()) {
            $errors[] = 'Le script depasse le nombre maximal de lignes autorise.';
        }

        $parsedLines = [];
        foreach ($lines as $lineNumber => $line) {
            $parsed = self::parseLine($line, $lineNumber + 1, $scope, $runtimeVariables);
            if (!$parsed['ok']) {
                $errors = array_merge($errors, $parsed['errors']);
                continue;
            }
            if ($parsed['instruction'] !== null) {
                $parsedLines[] = $parsed['instruction'];
            }
        }

        if ($parsedLines === []) {
            $errors[] = 'Le script ne contient aucune instruction executable.';
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => array_values(array_unique($errors))];
        }

        return [
            'ok' => true,
            'name' => $name,
            'content' => implode("\n", array_map(static fn(string $line): string => rtrim($line), $lines)),
            'scope' => $scope,
            'instructions' => $parsedLines,
            'line_count' => count($parsedLines),
            'checksum' => hash('sha256', $content),
        ];
    }

    public static function parseApprovedContent(string $content, string $scope = 'both', ?array $runtimeVariables = null): array {
        $result = self::validate('runtime', $content, $scope, $runtimeVariables);
        if (!$result['ok']) {
            return ['ok' => false, 'errors' => $result['errors']];
        }
        return ['ok' => true, 'instructions' => $result['instructions']];
    }

    private static function parseLine(string $line, int $lineNumber, string $scope, ?array $runtimeVariables): array {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            return ['ok' => true, 'instruction' => null];
        }

        foreach (self::bannedPatterns() as $pattern) {
            if (@preg_match($pattern, $trimmed) && preg_match($pattern, $trimmed) === 1) {
                return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : syntaxe shell interdite."]];
            }
        }

        $tokens = self::tokenize($trimmed);
        if (!$tokens['ok']) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : {$tokens['error']}"]];
        }

        $parts = $tokens['tokens'];
        if ($parts === []) {
            return ['ok' => true, 'instruction' => null];
        }

        $commandKey = $parts[0];
        $allowedCommands = self::allowedCommands();
        if (!isset($allowedCommands[$commandKey])) {
            if (in_array($commandKey, self::bannedCommands(), true)) {
                return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : la commande '{$commandKey}' est explicitement interdite."]];
            }
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : la commande '{$commandKey}' n'est pas autorisee."]];
        }

        $definition = $allowedCommands[$commandKey];
        $commandScopes = $definition['allowed_scopes'] ?? ['local', 'remote'];
        if ($scope !== 'both' && !in_array($scope, $commandScopes, true)) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : la commande '{$commandKey}' n'est pas autorisee pour cette portee."]];
        }

        $resolvedArgs = [];
        $displayArgs = [];
        foreach (array_slice($parts, 1) as $arg) {
            $resolved = self::resolveVariablesInToken($arg, $lineNumber, $runtimeVariables);
            if (!$resolved['ok']) {
                return ['ok' => false, 'errors' => $resolved['errors']];
            }
            $resolvedArgs[] = $resolved['value'];
            $displayArgs[] = $resolved['display'] ?? $resolved['value'];
        }

        $validator = $definition['validator'] ?? null;
        if (!is_string($validator) || !method_exists(self::class, $validator)) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : validateur manquant pour '{$commandKey}'."]];
        }

        $validation = self::{$validator}($resolvedArgs, $lineNumber);
        if (!$validation['ok']) {
            return ['ok' => false, 'errors' => $validation['errors']];
        }

        return [
            'ok' => true,
            'instruction' => [
                'line' => $lineNumber,
                'command' => $commandKey,
                'binary' => (string) ($definition['binary'] ?? $commandKey),
                'args' => $validation['args'] ?? $resolvedArgs,
                'args_display' => $displayArgs,
                'raw' => $trimmed,
            ],
        ];
    }

    private static function tokenize(string $line): array {
        $tokens = [];
        $rest = $line;

        while ($rest !== '') {
            $rest = ltrim($rest);
            if ($rest === '') {
                break;
            }

            $first = $rest[0];
            if ($first === "'") {
                if (!preg_match("/^'([^']*)'/", $rest, $matches)) {
                    return ['ok' => false, 'error' => 'chaine simple quotee invalide'];
                }
                $tokens[] = $matches[1];
                $rest = substr($rest, strlen($matches[0]));
                continue;
            }

            if ($first === '"') {
                if (!preg_match('/^"((?:[^"\\\\]|\\\\.)*)"/', $rest, $matches)) {
                    return ['ok' => false, 'error' => 'chaine double quotee invalide'];
                }
                $tokens[] = stripcslashes($matches[1]);
                $rest = substr($rest, strlen($matches[0]));
                continue;
            }

            if (!preg_match('/^[^\s]+/', $rest, $matches)) {
                return ['ok' => false, 'error' => 'token invalide'];
            }

            $token = $matches[0];
            if (preg_match('/[`$;&|<>]/', $token)) {
                return ['ok' => false, 'error' => 'token interdit'];
            }
            $tokens[] = $token;
            $rest = substr($rest, strlen($token));
        }

        return ['ok' => true, 'tokens' => $tokens];
    }

    private static function resolveVariablesInToken(string $token, int $lineNumber, ?array $runtimeVariables): array {
        if (!preg_match_all(self::VARIABLE_PATTERN, $token, $matches)) {
            return ['ok' => true, 'value' => $token, 'display' => $token];
        }

        $definitions = self::variableDefinitions();
        $replacements = [];
        $displayReplacements = [];
        foreach (array_values(array_unique($matches[1])) as $variableName) {
            if (!isset($definitions[$variableName])) {
                return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : la variable '{{{$variableName}}}' n'est pas autorisee."]];
            }

            $definition = $definitions[$variableName];
            if ($runtimeVariables !== null) {
                if (!array_key_exists($variableName, $runtimeVariables)) {
                    return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : la variable '{{{$variableName}}}' n'est pas disponible pour ce job."]];
                }
                $replacement = self::sanitizeResolvedVariable((string) $runtimeVariables[$variableName]);
            } else {
                $replacement = self::sampleValueForVariable($definition);
            }

            $tokenName = '{{' . $variableName . '}}';
            $replacements[$tokenName] = $replacement;
            $displayReplacements[$tokenName] = !empty($definition['sensitive']) ? $tokenName : $replacement;
        }

        return [
            'ok' => true,
            'value' => strtr($token, $replacements),
            'display' => strtr($token, $displayReplacements),
        ];
    }

    private static function sanitizeResolvedVariable(string $value): string {
        $value = str_replace(["\r", "\n", "\0"], ' ', $value);
        return trim($value);
    }

    private static function sampleValueForVariable(array $definition): string {
        $sample = trim((string) ($definition['sample'] ?? ''));
        if ($sample !== '') {
            return self::sanitizeResolvedVariable($sample);
        }
        if (!empty($definition['sensitive'])) {
            return 'secret-value';
        }

        return match ((string) ($definition['type'] ?? 'string')) {
            'path' => '/sample/path',
            'integer' => '1',
            'json' => '[]',
            default => 'sample',
        };
    }

    private static function normalizeVariableDefinitions($definitions): array {
        if (!is_array($definitions)) {
            return [];
        }

        $normalized = [];
        foreach ($definitions as $name => $meta) {
            $name = strtoupper(trim((string) $name));
            if ($name === '' || !preg_match('/^[A-Z0-9_]+$/', $name)) {
                continue;
            }

            $meta = is_array($meta) ? $meta : [];
            $normalized[$name] = [
                'name' => $name,
                'token' => '{{' . $name . '}}',
                'label' => trim((string) ($meta['label'] ?? str_replace('_', ' ', $name))),
                'category' => trim((string) ($meta['category'] ?? 'Systeme')) ?: 'Systeme',
                'description' => trim((string) ($meta['description'] ?? '')),
                'type' => trim((string) ($meta['type'] ?? 'string')) ?: 'string',
                'sample' => (string) ($meta['sample'] ?? ''),
                'sensitive' => !empty($meta['sensitive']),
                'available' => array_key_exists('available', $meta) ? (bool) $meta['available'] : true,
                'provider' => trim((string) ($meta['provider'] ?? '')),
            ];
        }

        return $normalized;
    }

    private static function dynamicVariableDefinitions(): array {
        $cronUrl = trim(AppConfig::get('cron_entry_base_url', ''));
        $baseUrl = self::deriveBaseUrl($cronUrl);

        $definitions = self::normalizeVariableDefinitions([
            'APP_BASE_URL' => [
                'label' => 'Base URL',
                'category' => 'Application',
                'description' => 'URL de base estimee de l application.',
                'type' => 'string',
                'sample' => $baseUrl !== '' ? $baseUrl : 'http://127.0.0.1',
            ],
            'EXECUTION_PHASE' => [
                'label' => 'Phase',
                'category' => 'Execution',
                'description' => 'Phase d execution du hook: pre ou post.',
                'type' => 'string',
                'sample' => 'pre',
            ],
            'EXECUTION_UNIX_TS' => [
                'label' => 'Timestamp Unix',
                'category' => 'Execution',
                'description' => 'Timestamp Unix de l execution.',
                'type' => 'integer',
                'sample' => '1712750400',
            ],
            'EXECUTION_DATE_COMPACT' => [
                'label' => 'Date compacte',
                'category' => 'Execution',
                'description' => 'Date compacte au format YYYYMMDD.',
                'type' => 'string',
                'sample' => '20260410',
            ],
            'EXECUTION_TIME_COMPACT' => [
                'label' => 'Heure compacte',
                'category' => 'Execution',
                'description' => 'Heure compacte au format HHMMSS.',
                'type' => 'string',
                'sample' => '143000',
            ],
            'SCRIPT_SCOPE' => [
                'label' => 'Portee du script',
                'category' => 'Script',
                'description' => 'Portee declaree pour le script approuve.',
                'type' => 'string',
                'sample' => 'both',
            ],
            'JOB_LAST_RUN_AT' => [
                'label' => 'Dernier lancement',
                'category' => 'Job',
                'description' => 'Derniere date d execution connue du job.',
                'type' => 'string',
                'sample' => '2026-04-10 02:00:00',
            ],
            'JOB_LAST_STATUS' => [
                'label' => 'Dernier status',
                'category' => 'Job',
                'description' => 'Dernier status enregistre pour le job.',
                'type' => 'string',
                'sample' => 'success',
            ],
            'JOB_SCHEDULE_ENABLED' => [
                'label' => 'Planification active',
                'category' => 'Job',
                'description' => 'Indique si la planification du job est active.',
                'type' => 'integer',
                'sample' => '1',
            ],
            'JOB_SCHEDULE_HOUR' => [
                'label' => 'Heure planifiee',
                'category' => 'Job',
                'description' => 'Heure de planification du job.',
                'type' => 'integer',
                'sample' => '2',
            ],
            'JOB_SCHEDULE_DAYS' => [
                'label' => 'Jours planifies',
                'category' => 'Job',
                'description' => 'Jours de planification du job au format CSV.',
                'type' => 'string',
                'sample' => '1,2,3,4,5',
            ],
            'JOB_NOTIFY_ON_FAILURE' => [
                'label' => 'Notif sur echec',
                'category' => 'Job',
                'description' => 'Indique si le job notifie sur echec.',
                'type' => 'integer',
                'sample' => '1',
            ],
            'JOB_NOTIFICATION_POLICY_JSON' => [
                'label' => 'Politique notification',
                'category' => 'Job',
                'description' => 'Politique de notification du job en JSON.',
                'type' => 'json',
                'sample' => '{"inherit":true}',
            ],
            'JOB_RETRY_POLICY_JSON' => [
                'label' => 'Politique retry',
                'category' => 'Job',
                'description' => 'Politique de retry du job en JSON.',
                'type' => 'json',
                'sample' => '{"enabled":true}',
            ],
            'JOB_RETENTION_ENABLED' => [
                'label' => 'Retention active',
                'category' => 'Job',
                'description' => 'Indique si la retention est active.',
                'type' => 'integer',
                'sample' => '1',
            ],
            'JOB_RETENTION_KEEP_LAST' => [
                'label' => 'Retention keep-last',
                'category' => 'Job',
                'description' => 'Valeur keep-last du job.',
                'type' => 'integer',
                'sample' => '7',
            ],
            'JOB_RETENTION_KEEP_DAILY' => [
                'label' => 'Retention keep-daily',
                'category' => 'Job',
                'description' => 'Valeur keep-daily du job.',
                'type' => 'integer',
                'sample' => '14',
            ],
            'JOB_RETENTION_KEEP_WEEKLY' => [
                'label' => 'Retention keep-weekly',
                'category' => 'Job',
                'description' => 'Valeur keep-weekly du job.',
                'type' => 'integer',
                'sample' => '4',
            ],
            'JOB_RETENTION_KEEP_MONTHLY' => [
                'label' => 'Retention keep-monthly',
                'category' => 'Job',
                'description' => 'Valeur keep-monthly du job.',
                'type' => 'integer',
                'sample' => '6',
            ],
            'JOB_RETENTION_KEEP_YEARLY' => [
                'label' => 'Retention keep-yearly',
                'category' => 'Job',
                'description' => 'Valeur keep-yearly du job.',
                'type' => 'integer',
                'sample' => '2',
            ],
            'JOB_RETENTION_PRUNE' => [
                'label' => 'Retention prune',
                'category' => 'Job',
                'description' => 'Indique si prune est active.',
                'type' => 'integer',
                'sample' => '1',
            ],
            'REPO_PATH_BASENAME' => [
                'label' => 'Nom du chemin depot',
                'category' => 'Depot',
                'description' => 'Basename du chemin du depot.',
                'type' => 'string',
                'sample' => 'repo',
            ],
            'REPO_PASSWORD_SOURCE' => [
                'label' => 'Source mot de passe depot',
                'category' => 'Depot',
                'description' => 'Source du mot de passe du depot: file ou infisical.',
                'type' => 'string',
                'sample' => 'infisical',
            ],
            'REPO_INFISICAL_SECRET_NAME' => [
                'label' => 'Nom secret Infisical depot',
                'category' => 'Depot',
                'description' => 'Nom du secret Infisical associe au depot.',
                'type' => 'string',
                'sample' => 'RESTIC_REPO_PASSWORD',
            ],
            'REPO_ALERT_HOURS' => [
                'label' => 'Alerte depot',
                'category' => 'Depot',
                'description' => 'Seuil d alerte en heures configure sur le depot.',
                'type' => 'integer',
                'sample' => '24',
            ],
            'REPO_NOTIFY_EMAIL' => [
                'label' => 'Email alerte depot',
                'category' => 'Depot',
                'description' => 'Email d alerte configure sur le depot.',
                'type' => 'string',
                'sample' => 'ops@example.com',
            ],
            'HOST_IS_REMOTE' => [
                'label' => 'Job distant',
                'category' => 'Hote',
                'description' => 'Indique si le job s execute via SSH.',
                'type' => 'integer',
                'sample' => '1',
            ],
            'HOST_SSH_TARGET' => [
                'label' => 'Cible SSH',
                'category' => 'Hote',
                'description' => 'Cible SSH sous la forme user@host:port.',
                'type' => 'string',
                'sample' => 'backup@prod.internal:22',
            ],
            'SOURCE_PATHS_CSV' => [
                'label' => 'Sources CSV',
                'category' => 'Sources',
                'description' => 'Liste des sources du job au format CSV.',
                'type' => 'string',
                'sample' => '/srv/data,/srv/www',
            ],
            'SOURCE_PATHS_JSON' => [
                'label' => 'Sources JSON',
                'category' => 'Sources',
                'description' => 'Liste des sources du job en JSON.',
                'type' => 'json',
                'sample' => '["/srv/data","/srv/www"]',
            ],
            'TAG_COUNT' => [
                'label' => 'Nombre de tags',
                'category' => 'Tags',
                'description' => 'Nombre de tags attaches au job.',
                'type' => 'integer',
                'sample' => '2',
            ],
            'TAGS_CSV' => [
                'label' => 'Tags CSV',
                'category' => 'Tags',
                'description' => 'Liste des tags au format CSV.',
                'type' => 'string',
                'sample' => 'prod,db',
            ],
            'TAGS_JSON' => [
                'label' => 'Tags JSON',
                'category' => 'Tags',
                'description' => 'Liste des tags en JSON.',
                'type' => 'json',
                'sample' => '["prod","db"]',
            ],
            'EXCLUDE_COUNT' => [
                'label' => 'Nombre d exclusions',
                'category' => 'Exclusions',
                'description' => 'Nombre d exclusions attachees au job.',
                'type' => 'integer',
                'sample' => '2',
            ],
            'EXCLUDES_CSV' => [
                'label' => 'Exclusions CSV',
                'category' => 'Exclusions',
                'description' => 'Liste des exclusions au format CSV.',
                'type' => 'string',
                'sample' => '*.tmp,node_modules',
            ],
            'EXCLUDES_JSON' => [
                'label' => 'Exclusions JSON',
                'category' => 'Exclusions',
                'description' => 'Liste des exclusions en JSON.',
                'type' => 'json',
                'sample' => '["*.tmp","node_modules"]',
            ],
        ]);

        foreach (range(1, 5) as $index) {
            $definitions += self::normalizeVariableDefinitions([
                'SOURCE_PATH_BASENAME_' . $index => [
                    'label' => 'Basename source ' . $index,
                    'category' => 'Sources',
                    'description' => 'Basename du chemin source ' . $index . '.',
                    'type' => 'string',
                    'sample' => 'data',
                ],
                'TAG_' . $index => [
                    'label' => 'Tag ' . $index,
                    'category' => 'Tags',
                    'description' => 'Tag numero ' . $index . ' du job.',
                    'type' => 'string',
                    'sample' => 'prod',
                ],
                'EXCLUDE_' . $index => [
                    'label' => 'Exclusion ' . $index,
                    'category' => 'Exclusions',
                    'description' => 'Exclusion numero ' . $index . ' du job.',
                    'type' => 'string',
                    'sample' => '*.tmp',
                ],
            ]);
        }

        $secretsExposed = Database::getSetting('hook_expose_secrets', '0') === '1';
        $secretSettings = [
            'SECRET_JOB_REPO_PASSWORD' => [
                'label' => 'Mot de passe depot du job',
                'category' => 'Secrets',
                'description' => 'Mot de passe du depot du job courant. Jamais affiche en clair dans l UI.',
                'provider' => 'Context',
                'available' => $secretsExposed,
            ],
            'SECRET_INSTANCE_SMTP_PASS' => [
                'label' => 'Mot de passe SMTP',
                'category' => 'Secrets',
                'description' => 'Mot de passe SMTP configure pour l instance. Jamais expose dans l UI.',
                'provider' => 'Settings',
                'available' => $secretsExposed && Database::getSetting('smtp_pass') !== '',
            ],
            'SECRET_INSTANCE_WEBHOOK_AUTH_TOKEN' => [
                'label' => 'Token webhook generique',
                'category' => 'Secrets',
                'description' => 'Token d authentification du webhook generique. Jamais expose dans l UI.',
                'provider' => 'Settings',
                'available' => $secretsExposed && Database::getSetting('webhook_auth_token') !== '',
            ],
            'SECRET_INSTANCE_INFISICAL_TOKEN' => [
                'label' => 'Token Infisical',
                'category' => 'Secrets',
                'description' => 'Token d acces Infisical configure sur l instance. Jamais expose dans l UI.',
                'provider' => 'Infisical',
                'available' => $secretsExposed && Database::getSetting('infisical_token') !== '',
            ],
            'SECRET_INSTANCE_GOTIFY_TOKEN' => [
                'label' => 'Token Gotify',
                'category' => 'Secrets',
                'description' => 'Token Gotify configure sur l instance. Jamais expose dans l UI.',
                'provider' => 'Settings',
                'available' => $secretsExposed && Database::getSetting('gotify_token') !== '',
            ],
            'SECRET_INSTANCE_TELEGRAM_BOT_TOKEN' => [
                'label' => 'Token Telegram',
                'category' => 'Secrets',
                'description' => 'Token bot Telegram configure sur l instance. Jamais expose dans l UI.',
                'provider' => 'Settings',
                'available' => $secretsExposed && Database::getSetting('telegram_bot_token') !== '',
            ],
            'SECRET_INSTANCE_DISCORD_WEBHOOK_URL' => [
                'label' => 'Webhook Discord',
                'category' => 'Secrets',
                'description' => 'URL du webhook Discord configuree sur l instance. Jamais exposee dans l UI.',
                'provider' => 'Settings',
                'available' => $secretsExposed && Database::getSetting('discord_webhook_url') !== '',
            ],
            'SECRET_INSTANCE_SLACK_WEBHOOK_URL' => [
                'label' => 'Webhook Slack',
                'category' => 'Secrets',
                'description' => 'URL du webhook Slack configuree sur l instance. Jamais exposee dans l UI.',
                'provider' => 'Settings',
                'available' => $secretsExposed && Database::getSetting('slack_webhook_url') !== '',
            ],
            'SECRET_INSTANCE_TEAMS_WEBHOOK_URL' => [
                'label' => 'Webhook Teams',
                'category' => 'Secrets',
                'description' => 'URL du webhook Teams configuree sur l instance. Jamais exposee dans l UI.',
                'provider' => 'Settings',
                'available' => $secretsExposed && Database::getSetting('teams_webhook_url') !== '',
            ],
        ];

        foreach ($secretSettings as $name => $meta) {
            $definitions[$name] = self::normalizeVariableDefinitions([
                $name => array_merge($meta, [
                    'type' => 'secret',
                    'sample' => 'Valeur masquee',
                    'sensitive' => true,
                ]),
            ])[$name];
        }

        return $definitions;
    }

    private static function deriveBaseUrl(string $cronUrl): string {
        if ($cronUrl === '') {
            return '';
        }

        $parts = parse_url($cronUrl);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $base = $parts['scheme'] . '://'. $parts['host'];
        if (!empty($parts['port'])) {
            $base .= ':' . $parts['port'];
        }
        if (!empty($parts['path'])) {
            $path = preg_replace('#/cron\.php$#', '', (string) $parts['path']);
            $base .= rtrim($path, '/');
        }
        return rtrim($base, '/');
    }

    private static function validateSystemctl(array $args, int $lineNumber): array {
        if (count($args) !== 2) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : systemctl attend exactement 2 arguments."]];
        }
        [$action, $unit] = $args;
        if (!in_array($action, ['reload', 'restart', 'start', 'stop', 'status'], true)) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : action systemctl interdite."]];
        }
        if (!preg_match('/^[a-zA-Z0-9_.@-]+(?:\.service)?$/', $unit)) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : unite systemd invalide."]];
        }
        return ['ok' => true, 'args' => [$action, $unit]];
    }

    private static function validateFsfreeze(array $args, int $lineNumber): array {
        if (count($args) !== 2) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : fsfreeze attend exactement 2 arguments."]];
        }
        [$action, $mountPoint] = $args;
        if (!in_array($action, ['--freeze', '--unfreeze'], true)) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : option fsfreeze interdite."]];
        }
        if (!preg_match('#^/[a-zA-Z0-9._/\-]+$#', $mountPoint)) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : point de montage invalide."]];
        }
        return ['ok' => true, 'args' => [$action, $mountPoint]];
    }

    private static function validateSleep(array $args, int $lineNumber): array {
        if (count($args) !== 1 || !preg_match('/^(?:[1-9]|[1-5][0-9]|60)$/', $args[0])) {
            return ['ok' => false, 'errors' => ["Ligne {$lineNumber} : sleep autorise uniquement une duree entiere de 1 a 60 secondes."]];
        }
        return ['ok' => true, 'args' => [$args[0]]];
    }
}
