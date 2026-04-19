<?php

declare(strict_types=1);

/**
 * BrokerClusterMonitor — monitors the HA secret broker cluster health,
 * persists state transitions to the application database, and dispatches
 * Notifier events when the cluster degrades, a node fails, or the cluster
 * recovers.
 *
 * Intended to be called from cron (once per run) and from performance.php
 * (on manual refresh). Safe to call multiple times; state is deduplicated
 * against the last persisted record.
 *
 * @security Never persists secret values. Only stores aggregate health
 * metrics (node counts, state label, timestamp).
 */
final class BrokerClusterMonitor
{
    private const SETTING_KEY = 'broker_cluster_last_status';
    private const HEALTH_ATTEMPTS = 2;
    private const HEALTH_RETRY_DELAY_US = 200000;
    private const DOWN_GRACE_SECONDS = 30;

    // ── Public API ────────────────────────────────────────────────────────────────

    /**
     * Run a cluster health check, sync node status via SecretBrokerEvents,
     * detect state transitions, dispatch notifications, and persist the new state.
     *
     * Returns an array with keys: health, status, previous, events.
     */
    public static function checkAndNotify(): array
    {
        $previous = self::loadLastStatus();
        $health   = self::stabilizedHealth($previous);
        $current  = self::normalizeStatus($health);

        $events = self::detectEvents($previous, $current);

        // Sync per-node status and fire notifications via existing SecretBrokerEvents.
        if (class_exists('SecretBrokerEvents', false)) {
            try {
                SecretBrokerEvents::syncHealth($health, true);
            } catch (Throwable $e) {
                SecretRedaction::errorLog(
                    'Fulgurite broker monitor: SecretBrokerEvents::syncHealth failed: '
                    . SecretRedaction::safeThrowableMessage($e)
                );
            }
        }

        self::saveLastStatus($current);

        return [
            'health'   => $health,
            'status'   => $current,
            'previous' => $previous,
            'events'   => $events,
        ];
    }

    /**
     * Return the last persisted cluster status without running a live health check.
     */
    public static function getLastStatus(): array
    {
        return self::loadLastStatus();
    }

    /**
     * Run a live health check and return the result without persisting state
     * or dispatching notifications. Suitable for read-only display in the UI.
     */
    public static function liveHealth(): array
    {
        $previous = self::loadLastStatus();
        $health = self::stabilizedHealth($previous);
        $status = self::normalizeStatus($health);
        if (($status['state'] ?? 'unknown') === 'ok') {
            self::saveLastStatus($status);
        }
        return $health;
    }

    // ── Internal ──────────────────────────────────────────────────────────────────

    private static function stabilizedHealth(array $previousStatus): array
    {
        $health = self::stableHealth();
        if (!empty($health['ok'])) {
            return $health;
        }

        if (!self::isRecentHealthyStatus($previousStatus)) {
            return $health;
        }

        return self::healthFromStatus($previousStatus);
    }

    private static function stableHealth(): array
    {
        $attempt = 1;
        $lastHealth = SecretStore::brokerHealth();
        while (!empty($lastHealth['ok']) || $attempt >= self::HEALTH_ATTEMPTS) {
            return $lastHealth;
        }

        while ($attempt < self::HEALTH_ATTEMPTS) {
            $attempt++;
            SecretStore::resetRuntimeState();
            usleep(self::HEALTH_RETRY_DELAY_US);
            $lastHealth = SecretStore::brokerHealth();
            if (!empty($lastHealth['ok'])) {
                return $lastHealth;
            }
        }

        return $lastHealth;
    }

    private static function isRecentHealthyStatus(array $status): bool
    {
        $state = (string) ($status['state'] ?? '');
        if ($state !== 'ok') {
            return false;
        }

        $checkedAt = (string) ($status['checked_at'] ?? '');
        if ($checkedAt === '') {
            return false;
        }

        $checkedTs = strtotime($checkedAt);
        if ($checkedTs === false) {
            return false;
        }

        return (time() - $checkedTs) <= self::DOWN_GRACE_SECONDS;
    }

    private static function healthFromStatus(array $status): array
    {
        $nodes = self::normalizeNodes((array) ($status['nodes'] ?? []));
        $healthy = (int) ($status['healthy'] ?? count(array_filter($nodes, static fn(array $node): bool => !empty($node['ok']))));
        $total = (int) ($status['total'] ?? count($nodes));
        if ($total <= 0 && $nodes !== []) {
            $total = count($nodes);
        }
        if ($healthy < 0) {
            $healthy = 0;
        }
        if ($healthy > $total) {
            $healthy = $total;
        }

        return [
            'ok' => ((string) ($status['state'] ?? '')) === 'ok',
            'provider' => 'ha-broker',
            'stabilized' => true,
            'cluster' => [
                'total' => $total,
                'healthy' => $healthy,
                'degraded' => $healthy > 0 && $healthy < $total,
                'selected_endpoint' => (string) ($status['selected_endpoint'] ?? ''),
                'nodes' => $nodes,
            ],
        ];
    }

    private static function normalizeStatus(array $health): array
    {
        $cluster = $health['cluster'] ?? [];
        $total   = (int) ($cluster['total']   ?? 0);
        $healthy = (int) ($cluster['healthy'] ?? 0);
        $ok      = !empty($health['ok']);

        if ($total === 0) {
            $state = 'unconfigured';
        } elseif (!$ok) {
            $state = 'down';
        } elseif ($healthy < $total) {
            $state = 'degraded';
        } else {
            $state = 'ok';
        }

        return [
            'state'       => $state,
            'total'       => $total,
            'healthy'     => $healthy,
            'selected_endpoint' => (string) ($cluster['selected_endpoint'] ?? $cluster['active_endpoint'] ?? ''),
            'nodes'       => self::normalizeNodes((array) ($cluster['nodes'] ?? [])),
            'checked_at'  => gmdate('c'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function normalizeNodes(array $nodes): array
    {
        $normalized = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $normalized[] = [
                'endpoint' => (string) ($node['endpoint'] ?? ''),
                'ok' => !empty($node['ok']),
                'node_id' => (string) ($node['node_id'] ?? ''),
                'node_label' => (string) ($node['node_label'] ?? ''),
                'cluster_name' => (string) ($node['cluster_name'] ?? ''),
                'backend' => (string) ($node['backend'] ?? ''),
                'error' => (string) ($node['error'] ?? ''),
            ];
        }
        return $normalized;
    }

    /**
     * Determine which notification events to fire based on the transition
     * from $previous state to $current state.
     *
     * @return list<string>
     */
    private static function detectEvents(array $previous, array $current): array
    {
        $prevState = (string) ($previous['state'] ?? 'unknown');
        $currState = (string) ($current['state'] ?? 'unknown');
        $prevSelected = (string) ($previous['selected_endpoint'] ?? '');
        $currSelected = (string) ($current['selected_endpoint'] ?? '');

        if ($prevState === $currState) {
            return ($prevSelected !== '' && $currSelected !== '' && $prevSelected !== $currSelected)
                ? ['failover']
                : [];
        }

        $events = [];

        // Cluster just went down or newly degraded.
        if (in_array($currState, ['down', 'degraded'], true)
            && !in_array($prevState, ['down', 'degraded'], true)) {
            $events[] = ($currState === 'down') ? 'down' : 'degraded';
        }

        // Already degraded, one more node went down (total → down).
        if ($currState === 'down' && $prevState === 'degraded') {
            $events[] = 'down';
        }

        // Recovery from any degraded state.
        if ($currState === 'ok' && in_array($prevState, ['down', 'degraded'], true)) {
            $events[] = 'recovered';
        }

        return array_values(array_unique($events));
    }

    // ── Persistence ───────────────────────────────────────────────────────────────

    private static function loadLastStatus(): array
    {
        try {
            $raw = Database::getSetting(self::SETTING_KEY);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        } catch (Throwable $ignored) {
            // DB may not be available during early bootstrap or CLI tests.
        }
        return ['state' => 'unknown', 'total' => 0, 'healthy' => 0, 'checked_at' => null];
    }

    private static function saveLastStatus(array $status): void
    {
        try {
            Database::setSetting(self::SETTING_KEY, (string) json_encode($status, JSON_UNESCAPED_SLASHES));
        } catch (Throwable $ignored) {
        }
    }
}
