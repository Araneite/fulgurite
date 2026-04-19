<?php

final class SecretBrokerEvents
{
    public static function syncHealth(array $health, bool $notify = false): void
    {
        $cluster = is_array($health['cluster'] ?? null) ? $health['cluster'] : [];
        $nodes = is_array($cluster['nodes'] ?? null) ? $cluster['nodes'] : [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            self::syncNode($node, $notify);
        }

        if ($notify) {
            self::syncClusterSummary($health);
        }
    }

    public static function recordClientFailover(string $fromEndpoint, string $toEndpoint, array $context = []): void
    {
        if (trim($fromEndpoint) === '' || trim($toEndpoint) === '' || $fromEndpoint === $toEndpoint) {
            return;
        }

        self::insertEvent(
            'failover',
            'warning',
            $toEndpoint,
            [
                'message' => 'Basculé du broker ' . $fromEndpoint . ' vers ' . $toEndpoint,
                'details_json' => $context + ['from' => $fromEndpoint, 'to' => $toEndpoint],
            ]
        );
    }

    public static function recentEvents(int $limit = 100): array
    {
        $stmt = Database::getInstance()->prepare("
            SELECT *
            FROM secret_broker_events
            ORDER BY created_at DESC, id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, max(1, min(500, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function syncNode(array $node, bool $notify): void
    {
        $endpoint = trim((string) ($node['endpoint'] ?? ''));
        if ($endpoint === '') {
            return;
        }

        $status = !empty($node['ok']) ? 'ok' : 'error';
        $error = trim((string) ($node['error'] ?? ''));
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT status FROM secret_broker_status WHERE endpoint = ?');
        $stmt->execute([$endpoint]);
        $previous = $stmt->fetchColumn();

        $db->prepare("
            INSERT INTO secret_broker_status (endpoint, node_id, node_label, cluster_name, status, error_message, last_seen_at, last_change_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'), datetime('now'))
            ON CONFLICT(endpoint) DO UPDATE SET
                node_id = excluded.node_id,
                node_label = excluded.node_label,
                cluster_name = excluded.cluster_name,
                status = excluded.status,
                error_message = excluded.error_message,
                last_seen_at = excluded.last_seen_at,
                last_change_at = CASE WHEN secret_broker_status.status <> excluded.status THEN excluded.last_change_at ELSE secret_broker_status.last_change_at END,
                updated_at = excluded.updated_at
        ")->execute([
            $endpoint,
            (string) ($node['node_id'] ?? ''),
            (string) ($node['node_label'] ?? ''),
            (string) ($node['cluster_name'] ?? ''),
            $status,
            $error !== '' ? $error : null,
        ]);

        if ($previous === false) {
            if ($status !== 'ok') {
                self::emitNodeEvent('node_failed', 'warning', $endpoint, $node, $notify);
            }
            return;
        }

        if ($previous !== $status) {
            if ($status === 'ok') {
                self::emitNodeEvent('node_recovered', 'success', $endpoint, $node, $notify);
            } else {
                self::emitNodeEvent('node_failed', 'warning', $endpoint, $node, $notify);
            }
        }
    }

    private static function syncClusterSummary(array $health): void
    {
        $cluster = is_array($health['cluster'] ?? null) ? $health['cluster'] : [];
        $nodes = is_array($cluster['nodes'] ?? null) ? $cluster['nodes'] : [];
        $total = count($nodes);
        $healthy = count(array_filter($nodes, static fn(array $node): bool => !empty($node['ok'])));
        $selected = (string) ($cluster['selected_endpoint'] ?? '');
        $state = ($total > 0 && $healthy === $total) ? 'ok' : (($healthy > 0) ? 'degraded' : 'down');
        $previous = Database::getSetting('secret_broker_cluster_state', '');
        $previousSelected = Database::getSetting('secret_broker_cluster_selected_endpoint', '');

        Database::setSetting('secret_broker_cluster_state', $state);
        Database::setSetting('secret_broker_cluster_selected_endpoint', $selected);

        if ($previous !== '' && $previous !== $state) {
            if ($state === 'ok') {
                self::emitClusterNotification('recovered', 'success', $cluster, 'Cluster broker revenu a la normale');
            } elseif ($state === 'degraded') {
                self::emitClusterNotification('degraded', 'warning', $cluster, 'Cluster broker degrade : ' . $healthy . '/' . $total . ' noeuds sains');
            } elseif ($state === 'down') {
                self::emitClusterNotification('down', 'critical', $cluster, 'Cluster broker indisponible');
            }
        }

        if ($previousSelected !== '' && $selected !== '' && $previousSelected !== $selected) {
            self::emitClusterNotification('failover', 'warning', $cluster, 'Failover broker actif : ' . $selected);
        }
    }

    private static function emitNodeEvent(string $eventType, string $severity, string $endpoint, array $node, bool $notify): void
    {
        $message = $eventType === 'node_recovered'
            ? 'Noeud broker recupere : ' . $endpoint
            : 'Noeud broker defaillant : ' . $endpoint;

        self::insertEvent($eventType, $severity, $endpoint, [
            'node_id' => (string) ($node['node_id'] ?? ''),
            'node_label' => (string) ($node['node_label'] ?? ''),
            'message' => $message,
            'details_json' => SecretRedaction::redactValue($node),
        ]);

        if ($notify) {
            Notifier::sendSecretBrokerEvent(
                $eventType === 'node_recovered' ? 'recovered' : 'node_failed',
                $message,
                $node
            );
        }
    }

    private static function emitClusterNotification(string $eventType, string $severity, array $cluster, string $message): void
    {
        self::insertEvent($eventType, $severity, (string) ($cluster['selected_endpoint'] ?? ''), [
            'message' => $message,
            'details_json' => SecretRedaction::redactValue($cluster),
        ]);
        Notifier::sendSecretBrokerEvent($eventType, $message, $cluster);
    }

    private static function insertEvent(string $eventType, string $severity, string $endpoint, array $payload): void
    {
        Database::getInstance()->prepare("
            INSERT INTO secret_broker_events (endpoint, event_type, severity, node_id, node_label, message, details_json, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ")->execute([
            $endpoint,
            $eventType,
            $severity,
            (string) ($payload['node_id'] ?? ''),
            (string) ($payload['node_label'] ?? ''),
            (string) ($payload['message'] ?? $eventType),
            json_encode($payload['details_json'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
        ]);
    }
}
