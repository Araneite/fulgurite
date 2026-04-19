<?php

class Notifier {
    private const OUTBOUND_HTTP_TIMEOUT = 5;

    private const CHANNELS = [
        'email' => ['label' => 'Email'],
        'discord' => ['label' => 'Discord'],
        'slack' => ['label' => 'Slack'],
        'telegram' => ['label' => 'Telegram'],
        'ntfy' => ['label' => 'ntfy'],
        'webhook' => ['label' => 'Webhook'],
        'teams' => ['label' => 'Teams'],
        'gotify' => ['label' => 'Gotify'],
        'in_app' => ['label' => 'In-App'],
        'web_push' => ['label' => 'Push Web'],
    ];

    private const PROFILES = [
        'repo' => [
            'label' => 'Depot',
            'events' => [
                'stale' => 'Backup ancien',
                'error' => 'Depot inaccessible',
                'no_snap' => 'Aucun snapshot',
            ],
        ],
        'backup_job' => [
            'label' => 'Backup job',
            'events' => [
                'failure' => 'Echec',
                'success' => 'Succes',
            ],
        ],
        'copy_job' => [
            'label' => 'Copy job',
            'events' => [
                'failure' => 'Echec',
                'success' => 'Succes',
            ],
        ],
        'weekly_report' => [
            'label' => 'Rapport hebdomadaire',
            'events' => [
                'report' => 'Rapport',
            ],
        ],
        'integrity_check' => [
            'label' => 'Verification d integrite',
            'events' => [
                'failure' => 'Probleme detecte',
                'success' => 'Tout est OK',
            ],
        ],
        'maintenance_vacuum' => [
            'label' => 'Maintenance SQLite',
            'events' => [
                'failure' => 'Echec',
                'success' => 'Succes',
            ],
        ],
        'login' => [
            'label' => 'Connexion',
            'events' => [
                'login' => 'Nouvelle connexion',
            ],
        ],
        'security' => [
            'label' => 'Securite',
            'events' => [
                'alert' => 'Alerte',
            ],
        ],
        'theme_request' => [
            'label' => 'Demande de theme',
            'events' => [
                'submitted' => 'Nouvelle demande',
            ],
        ],
        'disk_space' => [
            'label' => 'Espace disque',
            'events' => [
                'warning' => 'Seuil warning',
                'critical' => 'Seuil critique',
                'recovered' => 'Retour a la normale',
            ],
        ],
        'secret_broker' => [
            'label' => 'Broker secrets',
            'events' => [
                'degraded' => 'Cluster degrade',
                'node_failed' => 'Noeud defaillant',
                'failover' => 'Failover',
                'recovered' => 'Cluster recupere',
                'down' => 'Cluster indisponible',
            ],
        ],
    ];

    public static function getAvailableChannels(): array {
        return self::CHANNELS;
    }

    public static function getProfile(string $profile): array {
        return self::PROFILES[$profile] ?? ['label' => $profile, 'events' => []];
    }

    public static function encodePolicy(array $policy, string $profile): string {
        return json_encode(
            self::normalizePolicy($policy, $profile),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) ?: '{"inherit":true,"events":{}}';
    }

    public static function decodePolicy(?string $json, string $profile, array $legacy = []): array {
        $decoded = null;
        if (is_string($json) && trim($json) !== '') {
            $candidate = json_decode($json, true);
            if (is_array($candidate)) {
                $decoded = $candidate;
            }
        }

        if (!is_array($decoded)) {
            $decoded = self::legacyPolicy($profile, $legacy);
        }

        return self::normalizePolicy($decoded, $profile);
    }

    public static function parsePolicyPost(array $input, string $prefix, string $profile, ?array $fallback = null): array {
        $mode = (string) ($input[$prefix . '_notification_mode'] ?? (($fallback['inherit'] ?? true) ? 'inherit' : 'custom'));
        $events = self::getProfile($profile)['events'] ?? [];
        $policy = [
            'inherit' => $mode !== 'custom',
            'events' => [],
        ];

        foreach ($events as $eventKey => $_label) {
            $policy['events'][$eventKey] = [];
            foreach (array_keys(self::CHANNELS) as $channelKey) {
                $inputKey = $prefix . '_notify_' . $eventKey . '_' . $channelKey;
                if (isset($input[$inputKey])) {
                    $policy['events'][$eventKey][] = $channelKey;
                }
            }
        }

        return self::normalizePolicy($policy, $profile);
    }

    public static function summarizePolicy(array $policy, string $profile): array {
        $normalized = self::normalizePolicy($policy, $profile);
        if (!empty($normalized['inherit'])) {
            $channels = self::enabledChannels();
            if (empty($channels)) {
                return ['Global: aucun canal actif'];
            }
            return ['Global: ' . implode(' + ', self::channelLabels($channels))];
        }

        $events = self::getProfile($profile)['events'] ?? [];
        $lines = [];
        foreach ($events as $eventKey => $label) {
            $channels = $normalized['events'][$eventKey] ?? [];
            if (empty($channels)) {
                continue;
            }
            $lines[] = $label . ': ' . implode(' + ', self::channelLabels($channels));
        }

        return !empty($lines) ? $lines : ['Aucun canal'];
    }

    public static function policyHasChannels(array $policy, string $profile): bool {
        $normalized = self::normalizePolicy($policy, $profile);
        if (!empty($normalized['inherit'])) {
            return true;
        }

        foreach ($normalized['events'] as $channels) {
            if (!empty($channels)) {
                return true;
            }
        }

        return false;
    }

    public static function getEntityPolicy(string $profile, array $row): array {
        return self::decodePolicy((string) ($row['notification_policy'] ?? ''), $profile, $row);
    }

    public static function getSettingPolicy(string $settingKey, string $profile): array {
        return self::decodePolicy(Database::getSetting($settingKey, ''), $profile);
    }

    public static function getRecentNotificationLogs(int $limit = 30): array {
        $stmt = Database::getInstance()->prepare("
            SELECT *
            FROM notification_log
            ORDER BY created_at DESC, id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function testPolicy(string $profile, array $policy, string $event, string $contextName = ''): array {
        $eventLabels = self::getProfile($profile)['events'] ?? [];
        $eventLabel = $eventLabels[$event] ?? $event;
        $suffix = $contextName !== '' ? ' - ' . $contextName : '';
        $title = '[Test] ' . (self::getProfile($profile)['label'] ?? $profile) . ' / ' . $eventLabel . $suffix;
        $body = "Ceci est un test de notification pour verifier la configuration de cet element.\n"
            . "Profil : " . (self::getProfile($profile)['label'] ?? $profile) . "\n"
            . "Evenement : " . $eventLabel . "\n"
            . "Date : " . formatCurrentDisplayDate();

        $result = self::sendProfileEvent($profile, $policy, $event, $title, $body, [
            'context_type' => 'test',
            'context_name' => $contextName !== '' ? $contextName : $profile,
            'ntfy_priority' => 'default',
        ]);

        if ($result['success']) {
            $sentChannels = array_keys(array_filter(
                $result['results'],
                static fn(array $item): bool => !empty($item['success'])
            ));
            return [
                'success' => true,
                'output' => 'Test envoye sur ' . implode(', ', self::channelLabels($sentChannels)),
            ];
        }

        return [
            'success' => false,
            'output' => $result['output'] ?: 'Aucun canal n a accepte le message de test.',
        ];
    }

    public static function dispatchPolicy(string $profile, array $policy, string $event, string $title, string $body, array $context = []): array {
        return self::sendProfileEvent($profile, $policy, $event, $title, $body, $context);
    }

    public static function sendBackupAlert(array $repo, float $hoursAgo, string $alertType = 'stale'): bool {
        $db = Database::getInstance();

        // Feature 1: Use new 24h throttling system instead of hardcoded 6h
        if (self::wasNotifiedToday('repo', $alertType, 'repo', (int) ($repo['id'] ?? 0))) {
            return false;
        }

        $hoursStr = round($hoursAgo, 1);
        $title = match ($alertType) {
            'stale' => "Backup ancien - {$repo['name']}",
            'error' => "Depot inaccessible - {$repo['name']}",
            'no_snap' => "Aucun snapshot - {$repo['name']}",
            default => "Alerte depot - {$repo['name']}",
        };

        $body = match ($alertType) {
            'stale' => "Le depot **{$repo['name']}** n a pas ete sauvegarde depuis **{$hoursStr}h** (seuil: {$repo['alert_hours']}h)\nChemin: `{$repo['path']}`",
            'error' => "Le depot **{$repo['name']}** est **inaccessible**\nChemin: `{$repo['path']}`",
            'no_snap' => "Le depot **{$repo['name']}** ne contient **aucun snapshot**\nChemin: `{$repo['path']}`",
            default => "Alerte sur le depot **{$repo['name']}**",
        };

        $policy = self::getEntityPolicy('repo', $repo);
        $result = self::sendProfileEvent('repo', $policy, $alertType, $title, $body, [
            'context_type' => 'repo',
            'context_id' => (int) ($repo['id'] ?? 0),
            'context_name' => (string) ($repo['name'] ?? ''),
            'ntfy_priority' => $alertType === 'error' ? 'urgent' : 'high',
        ]);

        $db->prepare("
            INSERT INTO alert_log (repo_id, repo_name, alert_type, message, notified)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $repo['id'],
            $repo['name'],
            $alertType,
            self::plainText($body),
            $result['success'] ? 1 : 0,
        ]);

        return $result['success'];
    }

    public static function sendWeeklyReport(array $repoStatuses): void {
        $ok = array_filter($repoStatuses, static fn(array $row): bool => ($row['status'] ?? '') === 'ok');
        $alerts = array_filter($repoStatuses, static fn(array $row): bool => ($row['status'] ?? '') !== 'ok');
        $title = 'Rapport hebdomadaire Fulgurite';
        $lines = [
            '**Resume de la semaine :**',
            '- ' . count($repoStatuses) . ' depots configures',
            '- ' . count($ok) . ' depots OK',
            '- ' . count($alerts) . ' depots en alerte',
            '',
        ];

        foreach ($repoStatuses as $statusRow) {
            $last = !empty($statusRow['last']) ? 'dernier backup il y a ' . ($statusRow['hours_ago'] ?? '?') . 'h' : 'aucun backup';
            $lines[] = '- **' . ($statusRow['repo']['name'] ?? 'Depot') . '** : ' . $last;
        }

        $body = implode("\n", $lines);
        $policy = self::getSettingPolicy('weekly_report_notification_policy', 'weekly_report');
        self::sendProfileEvent('weekly_report', $policy, 'report', $title, $body, [
            'context_type' => 'scheduler_task',
            'context_name' => 'weekly_report',
            'ntfy_priority' => 'default',
        ]);
    }

    public static function sendSecurityAlert(string $event, string $details, string $ip = ''): void {
        $title = 'Alerte securite - ' . $event;
        $body = $details
            . "\n**Heure** : " . formatCurrentDisplayDate()
            . "\n**Serveur** : " . ($_SERVER['SERVER_NAME'] ?? 'Fulgurite')
            . ($ip !== '' ? "\n**IP** : " . $ip : '');

        $policy = self::getSettingPolicy('security_alert_notification_policy', 'security');
        self::sendProfileEvent('security', $policy, 'alert', $title, $body, [
            'context_type' => 'security',
            'context_name' => $event,
            'ntfy_priority' => 'urgent',
        ]);
    }

    public static function sendLoginNotification(string $username, string $ip, string $ua): void {
        $browser = 'Inconnu';
        if (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Edge')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'curl')) {
            $browser = 'cURL';
        }

        $title = 'Nouvelle connexion - ' . $username;
        $body = "**Utilisateur** : $username\n**IP** : $ip\n**Navigateur** : $browser\n**Heure** : " . formatCurrentDisplayDate();

        $policy = self::getSettingPolicy('login_notification_policy', 'login');
        self::sendProfileEvent('login', $policy, 'login', $title, $body, [
            'context_type' => 'login',
            'context_name' => $username,
            'ntfy_priority' => 'default',
        ]);
    }

    public static function sendSecretBrokerEvent(string $event, string $details, array $meta = []): void {
        $title = 'Broker secrets - ' . ($event !== '' ? $event : 'evenement');
        $body = $details
            . "\n**Heure** : " . formatCurrentDisplayDate()
            . (!empty($meta['selected_endpoint']) ? "\n**Endpoint actif** : " . (string) $meta['selected_endpoint'] : '')
            . (!empty($meta['endpoint']) ? "\n**Endpoint** : " . (string) $meta['endpoint'] : '')
            . (!empty($meta['node_label']) ? "\n**Noeud** : " . (string) $meta['node_label'] : '');

        $policy = self::getSettingPolicy('secret_broker_notification_policy', 'secret_broker');
        self::sendProfileEvent('secret_broker', $policy, $event, $title, $body, [
            'context_type' => 'secret_broker',
            'context_name' => (string) ($meta['endpoint'] ?? $meta['selected_endpoint'] ?? $event),
            'link_url' => routePath('/performance.php'),
            'ntfy_priority' => in_array($event, ['down', 'node_failed'], true) ? 'high' : 'default',
        ]);
    }

    public static function sendThemeRequestSubmitted(array $request, string $requesterName): void {
        $reqId = (int) ($request['id'] ?? 0);
        $themeName = (string) ($request['theme_name'] ?? '');
        $sourceType = (string) ($request['source_type'] ?? '');
        $sourceLabel = $sourceType === 'url' ? 'URL : ' . (string) ($request['source_url'] ?? '') : 'Fichier uploade';
        $description = trim((string) ($request['theme_description'] ?? ''));

        $title = 'Nouvelle demande de theme #' . $reqId;
        $body = "Demandeur : **$requesterName**\n"
            . "Theme propose : **" . ($themeName !== '' ? $themeName : '(sans nom)') . "**\n"
            . "Source : $sourceLabel"
            . ($description !== '' ? "\n\n$description" : '');

        $policy = self::getSettingPolicy('theme_request_notification_policy', 'theme_request');
        self::sendProfileEvent('theme_request', $policy, 'submitted', $title, $body, [
            'context_type' => 'theme_request',
            'context_id' => $reqId,
            'context_name' => $themeName !== '' ? $themeName : ('Demande #' . $reqId),
            'link_url' => routePath('/themes.php', ['tab' => 'requests']),
            'recipient_permission' => 'themes.manage',
            'ntfy_priority' => 'default',
        ]);
    }

    public static function sendAll(string $title, string $body, string $ntfyPriority = 'default'): void {
        $policy = self::normalizePolicy(['inherit' => true, 'events' => ['alert' => []]], 'security');
        self::sendProfileEvent('security', $policy, 'alert', $title, $body, [
            'context_type' => 'system',
            'context_name' => $title,
            'ntfy_priority' => $ntfyPriority,
        ]);
    }

    public static function sendDiscord(string $title, string $body): bool {
        return self::sendDiscordMessage($title, $body)['success'];
    }

    public static function testDiscord(): array {
        $result = self::sendDiscordMessage('Test Fulgurite', 'La connexion Discord fonctionne correctement.');
        return self::formatTestDeliveryResult($result, 'Message envoye sur Discord');
    }

    public static function sendSlack(string $title, string $body): bool {
        return self::sendSlackMessage($title, $body)['success'];
    }

    public static function testSlack(): array {
        $result = self::sendSlackMessage('Test Fulgurite', 'La connexion Slack fonctionne correctement.');
        return self::formatTestDeliveryResult($result, 'Message envoye sur Slack');
    }

    public static function sendTelegram(string $html): bool {
        return self::sendTelegramHtml($html)['success'];
    }

    public static function testTelegram(): array {
        $result = self::sendTelegramHtml('<b>Test Fulgurite</b>' . "\n" . 'La connexion Telegram fonctionne correctement.');
        return self::formatTestDeliveryResult($result, 'Message envoye sur Telegram');
    }

    public static function sendNtfy(string $title, string $body, string $priority = 'default'): bool {
        return self::sendNtfyMessage($title, $body, $priority)['success'];
    }

    public static function testNtfy(): array {
        $result = self::sendNtfyMessage('Test Fulgurite', 'La connexion ntfy fonctionne correctement.', 'default');
        return self::formatTestDeliveryResult($result, 'Notification ntfy envoyee');
    }

    public static function sendEmail(string $subject, string $body): bool {
        return self::sendEmailMessage($subject, $body)['success'];
    }

    public static function testWebhook(): array {
        $result = self::sendWebhookMessage('Test Fulgurite', 'Le webhook generique fonctionne correctement.', [
            'plain_body' => 'Le webhook generique fonctionne correctement.',
            'profile' => 'test',
            'event' => 'webhook',
            'context_type' => 'test',
            'context_name' => 'webhook',
        ]);
        return self::formatTestDeliveryResult($result, 'Message envoye sur le webhook');
    }

    public static function testTeams(): array {
        $result = self::sendTeamsMessage('Test Fulgurite', 'La connexion Microsoft Teams fonctionne correctement.');
        return self::formatTestDeliveryResult($result, 'Message envoye sur Teams');
    }

    public static function testGotify(): array {
        $result = self::sendGotifyMessage('Test Fulgurite', 'La connexion Gotify fonctionne correctement.', 'default');
        return self::formatTestDeliveryResult($result, 'Notification Gotify envoyee');
    }

    public static function testInApp(): array {
        $result = AppNotificationManager::store('Test Fulgurite', 'La notification interne a bien ete enregistree.', [
            'profile_key' => 'test',
            'event_key' => 'in_app',
            'context_type' => 'test',
            'context_name' => 'in_app',
            'severity' => 'info',
            'link_url' => routePath('/settings.php', ['tab' => 'notifications']),
            'browser_delivery' => false,
        ]);
        return self::formatTestDeliveryResult($result, 'Notification in-app enregistree');
    }

    public static function testWebPush(): array {
        $result = AppNotificationManager::store('Test Fulgurite', 'Une notification navigateur est prete. Autorisez les notifications du navigateur et gardez une session ouverte.', [
            'profile_key' => 'test',
            'event_key' => 'web_push',
            'context_type' => 'test',
            'context_name' => 'web_push',
            'severity' => 'info',
            'link_url' => routePath('/settings.php', ['tab' => 'notifications']),
            'browser_delivery' => true,
        ]);
        return self::formatTestDeliveryResult($result, 'Notification navigateur enregistree');
    }

    public static function sendMail(string $to, string $subject, string $body): bool {
        return self::sendMailMessage($to, $subject, $body)['success'];
    }

    public static function sendTest(string $to): array {
        $result = self::sendMailMessage($to, '[Fulgurite] Test de notification email', "Ceci est un test.\n\nSi vous recevez cet email, la configuration est correcte.");
        return ['success' => $result['success'], 'output' => $result['success'] ? 'Email envoye' : $result['output']];
    }

    private static function normalizePolicy(array $policy, string $profile): array {
        $events = self::getProfile($profile)['events'] ?? [];
        $normalized = [
            'inherit' => !array_key_exists('inherit', $policy) || (bool) $policy['inherit'],
            'events' => [],
        ];

        foreach ($events as $eventKey => $_label) {
            $values = $policy['events'][$eventKey] ?? [];
            $values = is_array($values) ? $values : [];
            $filtered = [];
            foreach ($values as $channelKey) {
                $channel = (string) $channelKey;
                if (isset(self::CHANNELS[$channel])) {
                    $filtered[$channel] = $channel;
                }
            }
            $normalized['events'][$eventKey] = array_values($filtered);
        }

        return $normalized;
    }

    private static function legacyPolicy(string $profile, array $legacy): array {
        return match ($profile) {
            'repo' => !empty($legacy['notify_email'])
                ? ['inherit' => true, 'events' => []]
                : ['inherit' => false, 'events' => ['stale' => [], 'error' => [], 'no_snap' => []]],
            'backup_job' => [
                'inherit' => false,
                'events' => [
                    'failure' => !empty($legacy['notify_on_failure']) ? ['discord', 'slack', 'telegram', 'ntfy'] : [],
                    'success' => [],
                ],
            ],
            'copy_job' => [
                'inherit' => false,
                'events' => [
                    'failure' => ['discord', 'telegram', 'ntfy'],
                    'success' => [],
                ],
            ],
            'weekly_report' => ['inherit' => true, 'events' => ['report' => []]],
            'integrity_check' => [
                'inherit' => false,
                'events' => [
                    'failure' => ['discord', 'telegram', 'ntfy'],
                    'success' => [],
                ],
            ],
            'maintenance_vacuum' => [
                'inherit' => false,
                'events' => [
                    'failure' => [],
                    'success' => [],
                ],
            ],
            'login' => ['inherit' => true, 'events' => ['login' => []]],
            'security' => ['inherit' => true, 'events' => ['alert' => []]],
            'secret_broker' => [
                'inherit' => false,
                'events' => [
                    'degraded' => ['in_app'],
                    'node_failed' => ['in_app', 'discord', 'telegram', 'ntfy'],
                    'failover' => ['in_app'],
                    'recovered' => ['in_app'],
                    'down' => ['in_app', 'discord', 'telegram', 'ntfy'],
                ],
            ],
            'theme_request' => ['inherit' => false, 'events' => ['submitted' => ['in_app']]],
            'disk_space' => [
                'inherit' => false,
                'events' => [
                    'warning' => ['in_app'],
                    'critical' => ['in_app', 'discord', 'telegram', 'ntfy'],
                    'recovered' => ['in_app'],
                ],
            ],
            default => ['inherit' => true, 'events' => []],
        };
    }

    private static function sendProfileEvent(string $profile, array $policy, string $event, string $title, string $body, array $context = []): array {
        $normalized = self::normalizePolicy($policy, $profile);
        $channels = self::resolveChannels($normalized, $event);
        if (empty($channels)) {
            return [
                'success' => false,
                'results' => [],
                'output' => 'Aucun canal actif pour cet evenement.',
            ];
        }

        // Feature 1: Check throttling for this event
        $contextType = (string) ($context['context_type'] ?? $profile);
        $contextId = isset($context['context_id']) ? (int) $context['context_id'] : null;
        if (self::isThrottledEvent($profile, $event) && self::wasNotifiedToday($profile, $event, $contextType, $contextId)) {
            // Log the throttled attempt but return success to avoid repeated retries
            self::logNotificationAttempt(
                $contextType,
                $contextId,
                (string) ($context['context_name'] ?? $title),
                $profile,
                $event,
                'throttled',
                true,
                'Notification throttled (already sent within 24h)'
            );
            return [
                'success' => true,
                'results' => ['throttled' => ['success' => true, 'output' => 'Notification throttled - already sent within 24h']],
                'output' => 'Notification throttled - already sent within 24h',
            ];
        }

        $plainBody = (string) ($context['plain_body'] ?? self::plainText($body));
        $telegramHtml = (string) ($context['telegram_html'] ?? self::telegramHtml($title, $body));
        $results = [];
        $anySuccess = false;
        $appDelivery = null;
        $storeInApp = in_array('in_app', $channels, true) && self::isChannelEnabled('in_app');
        $storeWebPush = in_array('web_push', $channels, true) && self::isChannelEnabled('web_push');
        $appDeliveryNeeded = $storeInApp || $storeWebPush;

        if ($appDeliveryNeeded) {
            $appDelivery = AppNotificationManager::store($title, $plainBody, [
                'profile_key' => $profile,
                'event_key' => $event,
                'context_type' => (string) ($context['context_type'] ?? $profile),
                'context_id' => isset($context['context_id']) ? (int) $context['context_id'] : null,
                'context_name' => (string) ($context['context_name'] ?? $title),
                'severity' => (string) ($context['severity'] ?? self::severityForEvent($profile, $event)),
                'link_url' => (string) ($context['link_url'] ?? self::defaultLinkUrl($context)),
                'browser_delivery' => $storeWebPush,
                'recipient_permission' => (string) ($context['recipient_permission'] ?? ''),
            ]);
        }

        foreach ($channels as $channel) {
            $result = in_array($channel, ['in_app', 'web_push'], true)
                ? self::appDeliveryResult($channel, $appDelivery)
                : self::sendChannelMessage($channel, $title, $body, [
                    'plain_body' => $plainBody,
                    'telegram_html' => $telegramHtml,
                    'ntfy_priority' => (string) ($context['ntfy_priority'] ?? 'default'),
                    'profile' => $profile,
                    'event' => $event,
                    'context_type' => (string) ($context['context_type'] ?? $profile),
                    'context_id' => isset($context['context_id']) ? (int) $context['context_id'] : null,
                    'context_name' => (string) ($context['context_name'] ?? $title),
                    'severity' => (string) ($context['severity'] ?? self::severityForEvent($profile, $event)),
                    'link_url' => (string) ($context['link_url'] ?? self::defaultLinkUrl($context)),
                    'log_content' => (string) ($context['log_content'] ?? ''),
                ]);
            $results[$channel] = $result;
            $anySuccess = $anySuccess || !empty($result['success']);

            self::logNotificationAttempt(
                (string) ($context['context_type'] ?? $profile),
                isset($context['context_id']) ? (int) $context['context_id'] : null,
                (string) ($context['context_name'] ?? $title),
                $profile,
                $event,
                $channel,
                !empty($result['success']),
                (string) ($result['output'] ?? '')
            );
        }

        return [
            'success' => $anySuccess,
            'results' => $results,
            'output' => $anySuccess ? '' : self::firstFailureMessage($results),
        ];
    }

    private static function resolveChannels(array $policy, string $event): array {
        if (!empty($policy['inherit'])) {
            return self::enabledChannels();
        }

        return array_values(array_filter(
            $policy['events'][$event] ?? [],
            static fn(string $channel): bool => isset(self::CHANNELS[$channel])
        ));
    }

    private static function enabledChannels(): array {
        $enabled = [];
        foreach (array_keys(self::CHANNELS) as $channelKey) {
            if (self::isChannelEnabled($channelKey)) {
                $enabled[] = $channelKey;
            }
        }
        return $enabled;
    }

    private static function channelLabels(array $channels): array {
        return array_values(array_map(
            static fn(string $channel): string => self::CHANNELS[$channel]['label'] ?? $channel,
            array_values($channels)
        ));
    }

    private static function isChannelEnabled(string $channel): bool {
        return match ($channel) {
            'email' => Database::getSetting('mail_enabled') === '1',
            'discord' => Database::getSetting('discord_enabled') === '1',
            'slack' => Database::getSetting('slack_enabled') === '1',
            'telegram' => Database::getSetting('telegram_enabled') === '1',
            'ntfy' => Database::getSetting('ntfy_enabled') === '1',
            'webhook' => Database::getSetting('webhook_enabled') === '1',
            'teams' => Database::getSetting('teams_enabled') === '1',
            'gotify' => Database::getSetting('gotify_enabled') === '1',
            'in_app' => Database::getSetting('in_app_enabled') === '1',
            'web_push' => Database::getSetting('web_push_enabled') === '1',
            default => false,
        };
    }

    private static function sendChannelMessage(string $channel, string $title, string $body, array $options = []): array {
        // Feature 2: Prepare channel-specific body variants
        $plainBody = (string) ($options['plain_body'] ?? self::plainText($body));
        $logContent = (string) ($options['log_content'] ?? '');

        return match ($channel) {
            'email' => self::sendEmailMessage($title, self::buildEmailBody($title, $plainBody, $logContent !== '' ? $logContent : null)),
            'discord' => self::sendDiscordMessage($title, self::buildCompactBody($body, $logContent !== '' ? $logContent : null)),
            'slack' => self::sendSlackMessage($title, self::buildCompactBody($body, $logContent !== '' ? $logContent : null)),
            'telegram' => self::sendTelegramHtml((string) ($options['telegram_html'] ?? self::telegramHtml($title, $body))),
            'ntfy' => self::sendNtfyMessage($title, $plainBody, (string) ($options['ntfy_priority'] ?? 'default')),
            'webhook' => self::sendWebhookMessage($title, $body, $options),
            'teams' => self::sendTeamsMessage($title, self::buildCompactBody($body, $logContent !== '' ? $logContent : null)),
            'gotify' => self::sendGotifyMessage($title, $plainBody, (string) ($options['ntfy_priority'] ?? 'default')),
            default => ['success' => false, 'output' => 'Canal inconnu'],
        };
    }

    private static function sendDiscordMessage(string $title, string $body): array {
        if (Database::getSetting('discord_enabled') !== '1') {
            return ['success' => false, 'output' => 'Discord desactive'];
        }

        $url = trim(Database::getSetting('discord_webhook_url'));
        if ($url === '') {
            return ['success' => false, 'output' => 'Webhook Discord manquant'];
        }

        $payload = json_encode([
            'embeds' => [[
                'title' => $title,
                'description' => $body,
                'footer' => ['text' => 'Fulgurite | ' . date('d/m/Y H:i')],
            ]],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return self::postNotificationPayload($url, (string) $payload, ['Content-Type: application/json']);
    }

    private static function sendSlackMessage(string $title, string $body): array {
        if (Database::getSetting('slack_enabled') !== '1') {
            return ['success' => false, 'output' => 'Slack desactive'];
        }

        $url = trim(Database::getSetting('slack_webhook_url'));
        if ($url === '') {
            return ['success' => false, 'output' => 'Webhook Slack manquant'];
        }

        $mrkdwn = preg_replace('/\*\*(.*?)\*\*/s', '*$1*', $body);
        $payload = json_encode([
            'blocks' => [
                ['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => $title]],
                ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => (string) $mrkdwn]],
                ['type' => 'context', 'elements' => [['type' => 'mrkdwn', 'text' => 'Fulgurite | ' . date('d/m/Y H:i')]]],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return self::postNotificationPayload($url, (string) $payload, ['Content-Type: application/json']);
    }

    private static function sendTelegramHtml(string $html): array {
        if (Database::getSetting('telegram_enabled') !== '1') {
            return ['success' => false, 'output' => 'Telegram desactive'];
        }

        $token = trim(Database::getSetting('telegram_bot_token'));
        $chatId = trim(Database::getSetting('telegram_chat_id'));
        if ($token === '' || $chatId === '') {
            return ['success' => false, 'output' => 'Configuration Telegram incomplete'];
        }

        $url = 'https://api.telegram.org/bot'. $token. '/sendMessage';
        $payload = json_encode([
            'chat_id' => $chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return self::postNotificationPayload($url, (string) $payload, ['Content-Type: application/json']);
    }

    private static function sendNtfyMessage(string $title, string $body, string $priority = 'default'): array {
        if (Database::getSetting('ntfy_enabled') !== '1') {
            return ['success' => false, 'output' => 'ntfy desactive'];
        }

        $url = rtrim(Database::getSetting('ntfy_url'), '/');
        $topic = trim(Database::getSetting('ntfy_topic'));
        if ($url === '' || $topic === '') {
            return ['success' => false, 'output' => 'Configuration ntfy incomplete'];
        }

        $payload = json_encode([
            'topic' => $topic,
            'title' => $title,
            'message' => $body,
            'priority' => $priority,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return self::postNotificationPayload($url . '/' . rawurlencode($topic), (string) $payload, [
            'Content-Type: application/json',
            'Title: ' . $title,
            'Priority: ' . $priority,
        ]);
    }

    private static function sendWebhookMessage(string $title, string $body, array $options = []): array {
        if (Database::getSetting('webhook_enabled') !== '1') {
            return ['success' => false, 'output' => 'Webhook desactive'];
        }

        $url = trim(Database::getSetting('webhook_url'));
        if ($url === '') {
            return ['success' => false, 'output' => 'URL webhook manquante'];
        }

        $headers = ['Content-Type: application/json'];
        $token = trim(Database::getSetting('webhook_auth_token'));
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $payload = json_encode([
            'app' => AppConfig::appName(),
            'title' => $title,
            'body' => $body,
            'plain_body' => (string) ($options['plain_body'] ?? self::plainText($body)),
            'profile' => (string) ($options['profile'] ?? ''),
            'event' => (string) ($options['event'] ?? ''),
            'severity' => (string) ($options['severity'] ?? 'info'),
            'context_type' => (string) ($options['context_type'] ?? ''),
            'context_id' => $options['context_id'] ?? null,
            'context_name' => (string) ($options['context_name'] ?? ''),
            'link_url' => (string) ($options['link_url'] ?? ''),
            'log_content' => (string) ($options['log_content'] ?? ''),
            'created_at' => formatCurrentDisplayDate(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return self::postNotificationPayload($url, (string) $payload, $headers);
    }

    private static function sendTeamsMessage(string $title, string $body): array {
        if (Database::getSetting('teams_enabled') !== '1') {
            return ['success' => false, 'output' => 'Teams desactive'];
        }

        $url = trim(Database::getSetting('teams_webhook_url'));
        if ($url === '') {
            return ['success' => false, 'output' => 'Webhook Teams manquant'];
        }

        $payload = json_encode([
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'summary' => $title,
            'themeColor' => '2F80ED',
            'title' => $title,
            'text' => nl2br(h(self::plainText($body))),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return self::postNotificationPayload($url, (string) $payload, ['Content-Type: application/json']);
    }

    private static function sendGotifyMessage(string $title, string $body, string $priority = 'default'): array {
        if (Database::getSetting('gotify_enabled') !== '1') {
            return ['success' => false, 'output' => 'Gotify desactive'];
        }

        $url = rtrim(Database::getSetting('gotify_url'), '/');
        $token = trim(Database::getSetting('gotify_token'));
        if ($url === '' || $token === '') {
            return ['success' => false, 'output' => 'Configuration Gotify incomplete'];
        }

        $priorityMap = [
            'min' => 1,
            'low' => 3,
            'default' => 5,
            'high' => 7,
            'urgent' => 9,
        ];

        $payload = json_encode([
            'title' => $title,
            'message' => $body,
            'priority' => $priorityMap[$priority] ?? 5,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return self::postNotificationPayload($url . '/message?token=' . rawurlencode($token), (string) $payload, [
            'Content-Type: application/json',
        ]);
    }

    private static function sendEmailMessage(string $subject, string $body): array {
        if (Database::getSetting('mail_enabled') !== '1') {
            return ['success' => false, 'output' => 'Email desactive'];
        }

        $to = trim(Database::getSetting('mail_to'));
        if ($to === '') {
            return ['success' => false, 'output' => 'Destinataire email manquant'];
        }

        return self::sendMailMessage($to, $subject, $body);
    }

    private static function sendMailMessage(string $to, string $subject, string $body): array {
        $smtpHost = trim(Database::getSetting('smtp_host'));
        $from = Database::getSetting('mail_from', 'fulgurite@localhost');
        $fromName = AppConfig::mailFromName();

        $success = $smtpHost !== ''
            ? self::sendSmtp($to, $subject, $body, $from, $fromName)
            : mail($to, $subject, $body, "From: {$fromName} <{$from}>\r\nContent-Type: text/plain; charset=UTF-8\r\n");

        return [
            'success' => (bool) $success,
            'output' => $success ? 'Email envoye' : 'Echec de l envoi email',
        ];
    }

    private static function plainText(string $body): string {
        return trim(strip_tags(str_replace(['**', '`'], '', $body)));
    }

    private static function telegramHtml(string $title, string $body): string {
        $escaped = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        $escaped = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $escaped);
        $escaped = preg_replace('/`(.*?)`/s', '<code>$1</code>', (string) $escaped);
        return '<b>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</b>' . ($escaped !== '' ? "\n" . $escaped : '');
    }

    private static function firstFailureMessage(array $results): string {
        foreach ($results as $result) {
            if (!empty($result['output'])) {
                return (string) $result['output'];
            }
        }
        return 'Aucun canal n a accepte la notification.';
    }

    private static function severityForEvent(string $profile, string $event): string {
        return match ($profile . ':' . $event) {
            'repo:error',
            'backup_job:failure',
            'copy_job:failure',
            'integrity_check:failure',
            'security:alert',
            'secret_broker:down' => 'critical',
            'repo:stale',
            'repo:no_snap',
            'maintenance_vacuum:failure',
            'disk_space:warning',
            'secret_broker:degraded',
            'secret_broker:node_failed',
            'secret_broker:failover' => 'warning',
            'disk_space:critical' => 'critical',
            'backup_job:success',
            'copy_job:success',
            'integrity_check:success',
            'maintenance_vacuum:success',
            'disk_space:recovered',
            'secret_broker:recovered' => 'success',
            default => 'info',
        };
    }

    private static function defaultLinkUrl(array $context): string {
        return match ((string) ($context['context_type'] ?? '')) {
            'repo' => routePath('/repos.php'),
            'backup_job' => routePath('/backup_jobs.php'),
            'copy_job' => routePath('/copy_jobs.php'),
            'scheduler_task' => routePath('/scheduler.php'),
            'disk_space' => routePath('/stats.php'),
            'login', 'security' => routePath('/logs.php'),
            'secret_broker' => routePath('/performance.php'),
            default => routePath('/notifications.php'),
        };
    }

    private static function appDeliveryResult(string $channel, ?array $delivery): array {
        if (!self::isChannelEnabled($channel)) {
            return [
                'success' => false,
                'output' => $channel === 'web_push' ? 'Push Web desactive' : 'In-App desactive',
            ];
        }

        if (!is_array($delivery)) {
            return ['success' => false, 'output' => 'Aucune notification interne disponible'];
        }

        if (!empty($delivery['success'])) {
            return [
                'success' => true,
                'output' => $channel === 'web_push'
                    ? 'Notification navigateur prete pour les sessions ouvertes'
                    : 'Notification in-app enregistree',
            ];
        }

        return [
            'success' => false,
            'output' => (string) ($delivery['output'] ?? 'Echec de la notification interne'),
        ];
    }

    private static function logNotificationAttempt(
        string $contextType,
        ?int $contextId,
        string $contextName,
        string $profileKey,
        string $eventKey,
        string $channel,
        bool $success,
        string $output
    ): void {
        Database::getInstance()->prepare("
            INSERT INTO notification_log (
                context_type, context_id, context_name,
                profile_key, event_key, channel, success, output
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $contextType,
            $contextId,
            $contextName,
            $profileKey,
            $eventKey,
            $channel,
            $success ? 1 : 0,
            mb_substr($output, 0, 1000),
        ]);
    }

    private static function formatTestDeliveryResult(array $result, string $successMessage): array {
        $formatted = [
            'success' => !empty($result['success']),
            'output' => !empty($result['success'])
                ? $successMessage
                : (string) ($result['output'] ?? 'Echec de la notification'),
        ];

        if (isset($result['http_status']) && (int) $result['http_status'] > 0) {
            $formatted['http_status'] = (int) $result['http_status'];
        }

        return $formatted;
    }

    private static function postNotificationPayload(string $url, string $payload, array $headers = []): array {
        try {
            return self::httpPost($url, $payload, $headers);
        } catch (Throwable $e) {
            self::logHttpPostFailure($url, $e);

            return [
                'success' => false,
                'output' => $e->getMessage() !== '' ? $e->getMessage() : 'Outbound HTTPS request failed.',
                'http_status' => 0,
            ];
        }
    }

    private static function logHttpPostFailure(string $url, Throwable $error): void {
        $host = parse_url($url, PHP_URL_HOST);
        $hostLabel = is_string($host) && $host !== '' ? $host : 'unknown-host';
        error_log('[Fulgurite Notification HTTP] ' . $hostLabel . ' - ' . $error->getMessage());
    }

    private static function validateOutgoingUrl(string $url): void {
        (new PublicOutboundUrlValidator())->validate($url);
    }

    private static function httpPost(string $url, string $payload, array $headers = []): array {
        self::validateOutgoingUrl($url);

        $response = OutboundHttpClient::request('POST', $url, [
            'headers' => $headers,
            'body' => $payload,
            'timeout' => self::OUTBOUND_HTTP_TIMEOUT,
            'connect_timeout' => min(3, self::OUTBOUND_HTTP_TIMEOUT),
            'max_redirects' => 0,
            'user_agent' => 'Fulgurite-Notifier/1.0',
        ], new PublicOutboundUrlValidator());
        if (($response['error'] ?? null) !== null) {
            throw new RuntimeException((string) $response['error']);
        }

        $httpCode = (int) ($response['status'] ?? 0);
        $success = $httpCode >= 200 && $httpCode < 300;
        $details = $httpCode > 0
            ? 'HTTP ' . $httpCode
            : ($success ? 'Outbound request accepted.' : 'Remote endpoint rejected the request.');

        return [
            'success' => $success,
            'output' => $details,
            'http_status' => $httpCode,
        ];
    }

    private static function sendSmtp(string $to, string $subject, string $body, string $from, string $fromName): bool {
        $host = Database::getSetting('smtp_host');
        $port = (int) Database::getSetting('smtp_port', '587');
        $user = Database::getSetting('smtp_user');
        $pass = Database::getSetting('smtp_pass');
        $tls = Database::getSetting('smtp_tls', '1') === '1';

        $log = [];

        try {
            $ctx = stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                    'peer_name' => $host,
                ],
            ]);
            $prefix = $port === 465 ? 'ssl://' : '';
            $socket = stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
            if (!$socket) {
                throw new RuntimeException("Connexion echouee: $errstr ($errno)");
            }

            $log[] = 'Connecte a ' . $host . ':' . $port;
            $greeting = fgets($socket, 512);
            $log[] = 'S: ' . trim((string) $greeting);

            fwrite($socket, "EHLO fulgurite\r\n");
            while ($line = fgets($socket, 512)) {
                $log[] = 'S: ' . trim($line);
                if (($line[3] ?? ' ') === ' ') {
                    break;
                }
            }

            if ($tls && $port !== 465) {
                fwrite($socket, "STARTTLS\r\n");
                $reply = fgets($socket, 512);
                $log[] = 'STARTTLS: ' . trim((string) $reply);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fwrite($socket, "EHLO fulgurite\r\n");
                while ($line = fgets($socket, 512)) {
                    $log[] = 'S: ' . trim($line);
                    if (($line[3] ?? ' ') === ' ') {
                        break;
                    }
                }
            }

            if ($user !== '') {
                fwrite($socket, "AUTH LOGIN\r\n");
                $log[] = 'AUTH: ' . trim((string) fgets($socket, 512));
                fwrite($socket, base64_encode($user) . "\r\n");
                $log[] = 'USER: ' . trim((string) fgets($socket, 512));
                fwrite($socket, base64_encode($pass) . "\r\n");
                $auth = (string) fgets($socket, 512);
                $log[] = 'PASS: ' . trim($auth);
                if (!str_starts_with($auth, '2')) {
                    throw new RuntimeException('Auth echouee: ' . trim($auth));
                }
            }

            fwrite($socket, "MAIL FROM:<$from>\r\n");
            $log[] = 'MAIL FROM: ' . trim((string) fgets($socket, 512));
            fwrite($socket, "RCPT TO:<$to>\r\n");
            $log[] = 'RCPT TO: ' . trim((string) fgets($socket, 512));
            fwrite($socket, "DATA\r\n");
            $log[] = 'DATA: ' . trim((string) fgets($socket, 512));

            $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $message = "From: {$fromName} <{$from}>\r\nTo: {$to}\r\nSubject: {$subjectEncoded}\r\n"
                . 'Date: ' . date('r') . "\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n"
                . $body . "\r\n.\r\n";

            fwrite($socket, $message);
            $response = (string) fgets($socket, 512);
            $log[] = 'SEND: ' . trim($response);
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            error_log('[Fulgurite SMTP] ' . implode(' | ', $log));
            return str_starts_with($response, '2');
        } catch (Throwable $e) {
            error_log('[Fulgurite SMTP ERROR] ' . $e->getMessage() . ' | Log: ' . implode(' | ', $log));
            return false;
        }
    }

    private static function wasNotifiedToday(string $profileKey, string $eventKey, string $contextType, ?int $contextId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM notification_log
            WHERE profile_key = ?
              AND event_key = ?
              AND context_type = ?
              AND context_id IS ?
              AND success = 1
              AND datetime(created_at) > datetime('now', '-24 hours')
            LIMIT 1
        ");
        $stmt->execute([$profileKey, $eventKey, $contextType, $contextId]);
        $result = $stmt->fetch();
        return (int) ($result['count'] ?? 0) > 0;
    }

    private static function isThrottledEvent(string $profile, string $event): bool {
        // Events that should NEVER be throttled
        return !in_array("$profile:$event", [
            'security:alert',
            'login:login',
            'weekly_report:report',
            'backup_job:success',
            'copy_job:success',
        ], true);
    }

    private static function extractErrorLines(string $log): array {
        $lines = array_filter(
            preg_split('/\r?\n/', $log) ?: [],
            static function(string $line): bool {
                $lower = strtolower($line);
                return str_contains($lower, 'error')
                    || str_contains($lower, 'fail')
                    || str_contains($lower, 'exception')
                    || str_contains($lower, '!!')
                    || str_contains($lower, 'fatal');
            }
        );
        return array_slice($lines, 0, 5);
    }

    private static function buildEmailBody(string $title, string $plainBody, ?string $logContent = null): string {
        $body = $plainBody;
        if ($logContent !== null && $logContent !== '') {
            $body .= "\n\n--- Log complet ---\n";
            if (mb_strlen($logContent) <= 4000) {
                $body .= $logContent;
            } else {
                $body .= "[Log trop long pour inclure ici - consultez l'interface pour plus de details]\n"
                       . "[Premiers 4000 caractères:]\n"
                       . mb_substr($logContent, 0, 4000) . "\n[...]";
            }
        }
        return $body;
    }

    private static function buildCompactBody(string $plainBody, ?string $logContent = null): string {
        if ($logContent === null || $logContent === '') {
            return $plainBody;
        }
        // For compact channels, include brief error summary if present
        $errorLines = self::extractErrorLines($logContent);
        if (empty($errorLines)) {
            return $plainBody;
        }
        $body = $plainBody . "\n\n**Erreurs detectees :**\n";
        foreach ($errorLines as $line) {
            $body .= "- " . trim($line) . "\n";
        }
        return $body;
    }
}
