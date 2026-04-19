<?php
// =============================================================================
// ApiWebhookManager.php — management of webhooks sortants signes (HMAC SHA-256)
// =============================================================================

class ApiWebhookManager {
    private const STORE_RESPONSE_BODY_DEBUG_KEY = 'api_webhook_store_response_body_debug';

    public const EVENTS = [
        'backup_job.success',
        'backup_job.failure',
        'copy_job.success',
        'copy_job.failure',
        'restore.success',
        'restore.failure',
        'repo.created',
        'repo.deleted',
        'repo.check.failure',
        'snapshot.deleted',
        'api_token.created',
        'api_token.revoked',
    ];

    public static function getAll(): array {
        $rows = Database::getInstance()->query('SELECT * FROM api_webhooks ORDER BY created_at DESC')->fetchAll();
        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function getById(int $id): ?array {
        $stmt = Database::getInstance()->prepare('SELECT * FROM api_webhooks WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function create(array $data, int $createdBy): array {
        $name = trim((string) ($data['name'] ?? ''));
        $url = trim((string) ($data['url'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Nom requis.');
        }
        self::validateWebhookUrl($url);

        $events = self::normalizeEvents($data['events'] ?? []);
        $secret = (string) ($data['secret'] ?? '');
        if ($secret === '') {
            $secret = bin2hex(random_bytes(32));
        }
        $enabled = !empty($data['enabled']) ? 1 : 0;

        $db = Database::getInstance();
        $startedTransaction = !$db->inTransaction();
        if ($startedTransaction) {
            $db->beginTransaction();
        }
        try {
            $db->prepare("
                INSERT INTO api_webhooks (name, url, secret, secret_ref, events_json, enabled, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([$name, $url, '', null, json_encode($events), $enabled, $createdBy]);

            $id = (int) $db->lastInsertId();
            SensitiveEntitySecretManager::storeSecret(
                SensitiveEntitySecretManager::CONTEXT_API_WEBHOOK,
                $id,
                $secret,
                ['entity' => 'api_webhook', 'id' => $id, 'name' => $name]
            );

            if ($startedTransaction) {
                $db->commit();
            }

            $hook = self::getById($id);
            if ($hook === null) {
                throw new RuntimeException('Webhook introuvable apres creation.');
            }
            $hook['revealed_secret'] = $secret;
            return $hook;
        } catch (Throwable $e) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function update(int $id, array $data): ?array {
        $hook = self::getById($id);
        if (!$hook) return null;

        $fields = [];
        $values = [];
        $nextSecret = null;
        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ($name !== '') { $fields[] = 'name = ?'; $values[] = $name; }
        }
        if (array_key_exists('url', $data)) {
            $url = trim((string) $data['url']);
            if ($url === '') {
                throw new InvalidArgumentException('URL webhook requise.');
            }
            self::validateWebhookUrl($url);
            $fields[] = 'url = ?';
            $values[] = $url;
        }
        if (array_key_exists('events', $data)) {
            $fields[] = 'events_json = ?';
            $values[] = json_encode(self::normalizeEvents($data['events']));
        }
        if (array_key_exists('secret', $data) && trim((string) $data['secret']) !== '') {
            $nextSecret = trim((string) $data['secret']);
        }
        if (array_key_exists('enabled', $data)) {
            $fields[] = 'enabled = ?';
            $values[] = !empty($data['enabled']) ? 1 : 0;
        }
        if (empty($fields) && $nextSecret === null) return $hook;

        $db = Database::getInstance();
        $startedTransaction = !$db->inTransaction();
        if ($startedTransaction) {
            $db->beginTransaction();
        }
        try {
            if (!empty($fields)) {
                $values[] = $id;
                $db->prepare('UPDATE api_webhooks SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
            }
            if ($nextSecret !== null) {
                SensitiveEntitySecretManager::storeSecret(
                    SensitiveEntitySecretManager::CONTEXT_API_WEBHOOK,
                    $id,
                    $nextSecret,
                    ['entity' => 'api_webhook', 'id' => $id, 'name' => $data['name'] ?? $hook['name']],
                    $hook
                );
            }
            if ($startedTransaction) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        return self::getById($id);
    }

    public static function delete(int $id): bool {
        $hook = self::getById($id);
        if (!$hook) return false;
        SensitiveEntitySecretManager::clearSecret(SensitiveEntitySecretManager::CONTEXT_API_WEBHOOK, $id, $hook);
        Database::getInstance()->prepare('DELETE FROM api_webhooks WHERE id = ?')->execute([$id]);
        Database::getInstance()->prepare('DELETE FROM api_webhook_deliveries WHERE webhook_id = ?')->execute([$id]);
        return true;
    }

    /**
     * Broadcasts an event to all subscribed webhooks. Best effort, synchronous (short timeout).
     */
    public static function dispatch(string $event, array $payload): void {
        if (!AppConfig::isApiEnabled()) return;
        try {
            $rows = Database::getInstance()->query('SELECT * FROM api_webhooks WHERE enabled = 1')->fetchAll();
        } catch (Throwable $e) {
            return;
        }
        foreach ($rows as $row) {
            $hook = self::hydrate($row);
            if (!in_array($event, $hook['events'], true)) continue;
            self::send($hook, $event, $payload);
        }
    }

    public static function send(array $hook, string $event, array $payload): array {
        $body = json_encode([
            'event' => $event,
            'created_at' => gmdate('c'),
            'data' => $payload,
        ], JSON_UNESCAPED_SLASHES);

        try {
            $secret = SensitiveEntitySecretManager::getSecret(
                SensitiveEntitySecretManager::CONTEXT_API_WEBHOOK,
                $hook,
                'runtime',
                ['scope' => 'webhook_dispatch', 'webhook_id' => (int) ($hook['id'] ?? 0)]
            );
            if ($secret === null || $secret === '') {
                throw new RuntimeException('Secret webhook introuvable.');
            }
            $signature = hash_hmac('sha256', $body, $secret);
            $headers = [
                'Content-Type: application/json',
                'X-Fulgurite-Event: ' . $event,
                'X-Fulgurite-Signature: sha256=' . $signature,
                'X-Fulgurite-Delivery: ' . bin2hex(random_bytes(8)),
                'User-Agent: Fulgurite-Webhook/1.0',
            ];
            $response = OutboundHttpClient::request('POST', (string) $hook['url'], [
                'body' => $body,
                'headers' => $headers,
                'timeout' => 5,
                'connect_timeout' => 3,
                'max_redirects' => 0,
                'user_agent' => 'Fulgurite-Webhook/1.0',
            ], new PublicOutboundUrlValidator());
        } catch (Throwable $e) {
            $response = [
                'success' => false,
                'status' => 0,
                'body' => null,
                'error' => $e->getMessage(),
                'final_url' => (string) ($hook['url'] ?? ''),
            ];
        }

        $status = (int) ($response['status'] ?? 0);
        $error = (string) ($response['error'] ?? '');
        $responseBody = isset($response['body']) && is_string($response['body']) ? $response['body'] : null;
        $storeResponseBody = Database::getSetting(self::STORE_RESPONSE_BODY_DEBUG_KEY, '0') === '1'
            ? ($responseBody !== null ? substr($responseBody, 0, 4000) : null)
            : null;

        try {
            Database::getInstance()
                ->prepare("UPDATE api_webhooks SET last_status = ?, last_attempt_at = datetime('now'), last_error = ? WHERE id = ?")
                ->execute([$status, $error ?: null, (int) $hook['id']]);
            Database::getInstance()
                ->prepare('INSERT INTO api_webhook_deliveries (webhook_id, event, payload, status, response, error) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([(int) $hook['id'], $event, $body, $status, $storeResponseBody, $error ?: null]);
        } catch (Throwable $e) { /* ignore */ }

        return [
            'status' => $status,
            'error' => $error,
            'final_url' => (string) ($response['final_url'] ?? (string) ($hook['url'] ?? '')),
            'response_body_stored' => $storeResponseBody !== null,
        ];
    }

    public static function hydrate(array $row): array {
        $row['events'] = json_decode($row['events_json'] ?? '[]', true) ?: [];
        $row['enabled'] = (bool) ($row['enabled'] ?? 0);
        $row['has_secret'] = SensitiveEntitySecretManager::hasSecret(SensitiveEntitySecretManager::CONTEXT_API_WEBHOOK, $row);
        unset($row['secret']);
        return $row;
    }

    public static function publicView(array $hook): array {
        return [
            'id' => (int) $hook['id'],
            'name' => $hook['name'],
            'url' => $hook['url'],
            'events' => $hook['events'],
            'enabled' => $hook['enabled'],
            'last_status' => isset($hook['last_status']) ? (int) $hook['last_status'] : null,
            'last_attempt_at' => $hook['last_attempt_at'] ?? null,
            'last_error' => $hook['last_error'] ?? null,
            'created_at' => $hook['created_at'] ?? null,
        ];
    }

    private static function normalizeEvents(mixed $events): array {
        if (!is_array($events)) {
            $events = is_string($events) ? preg_split('/[\s,]+/', $events, -1, PREG_SPLIT_NO_EMPTY) ?: [] : [];
        }
        $valid = array_intersect($events, self::EVENTS);
        return array_values(array_unique($valid));
    }

    private static function validateWebhookUrl(string $url): void {
        (new PublicOutboundUrlValidator())->validate($url);
    }
}
