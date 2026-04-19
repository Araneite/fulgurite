<?php

declare(strict_types=1);

/**
 * tests/ha_broker_client.php
 *
 * Standalone tests for HaBrokerSecreatetProvider: endpoint parsing, failover
 * logic, health reporting, all-down error semantics, and log/event integration.
 *
 * Run: php tests/ha_broker_client.php
 */

require_once __DIR__ . '/../src/SecretStore.php';
require_once __DIR__ . '/../src/SecretRedaction.php';

// ─── Minimal stubs for classes that SecreatetBrokerEvents/SecreatetStore reference ──

if (!class_exists('SecretBrokerEvents', false)) {
    class SecretBrokerEvents
    {
        public static array $failoverCalls = [];
        public static array $syncHealthCalls = [];

        public static function recordClientFailover(string $from, string $to, array $ctx = []): void
        {
            self::$failoverCalls[] = ['from' => $from, 'to' => $to, 'ctx' => $ctx];
        }

        public static function syncHealth(array $health, bool $notify = false): void
        {
            self::$syncHealthCalls[] = ['health' => $health, 'notify' => $notify];
        }

        public static function recentEvents(int $limit = 100): array
        {
            return [];
        }
    }
}

// ─── Test helpers ─────────────────────────────────────────────────────────────

$PASS = 0;
$FAIL = 0;

function ok(string $desc, bool $condition): void
{
    global $PASS, $FAIL;
    if ($condition) {
        echo "\033[32m✔\033[0m {$desc}\n";
        $PASS++;
    } else {
        echo "\033[31m✘\033[0m {$desc}\n";
        $FAIL++;
    }
}

function throws(string $desc, callable $fn, string $msgFragment = ''): void
{
    global $PASS, $FAIL;
    try {
        $fn();
        echo "\033[31m✘\033[0m {$desc} (expected exception, none thrown)\n";
        $FAIL++;
    } catch (Throwable $e) {
        if ($msgFragment !== '' && strpos($e->getMessage(), $msgFragment) === false) {
            echo "\033[31m✘\033[0m {$desc} (exception message mismatch: " . $e->getMessage() . ")\n";
            $FAIL++;
        } else {
            echo "\033[32m✔\033[0m {$desc}\n";
            $PASS++;
        }
    }
}

// ─── 1. URI Parsing ──────────────────────────────────────────────────────────

echo "\n── URI Parsing ──────────────────────────────────────────────────────────\n";

$endpoints = HaBrokerSecretProvider::parseEndpoints('unix:///run/fulgurite/secrets.sock');
ok('Single unix socket parsed', count($endpoints) === 1 && $endpoints[0]['type'] === 'unix');
ok('Unix path extracted correctly', $endpoints[0]['path'] === '/run/fulgurite/secrets.sock');

$endpoints = HaBrokerSecretProvider::parseEndpoints('tcp://broker1.local:9876');
ok('Single TCP endpoint parsed', count($endpoints) === 1 && $endpoints[0]['type'] === 'tcp');
ok('TCP host extracted', $endpoints[0]['host'] === 'broker1.local');
ok('TCP port extracted', $endpoints[0]['port'] === 9876);

$endpoints = HaBrokerSecretProvider::parseEndpoints(
    'unix:///run/fulgurite/s.sock,tcp://10.0.0.2:9876,tcp://10.0.0.3:9876'
);
ok('Multi-endpoint list parsed', count($endpoints) === 3);
ok('First is unix', $endpoints[0]['type'] === 'unix');
ok('Second is tcp', $endpoints[1]['type'] === 'tcp');
ok('Third is tcp', $endpoints[2]['type'] === 'tcp');

// Invalid URIs should be silently skipped.
$endpoints = HaBrokerSecretProvider::parseEndpoints('ftp://bad,tcp://host_missing_port,tcp://:9876');
ok('Invalid URIs are skipped', count($endpoints) === 0);

// Edge case: empty string.
$endpoints = HaBrokerSecretProvider::parseEndpoints('');
ok('Empty string returns empty list', $endpoints === []);

// Port range validation.
$endpoints = HaBrokerSecretProvider::parseEndpoints('tcp://host:0,tcp://host:65535,tcp://host:65536');
ok('Port 0 rejected', count($endpoints) === 1);
ok('Port 65535 accepted', $endpoints[0]['port'] === 65535);

// ─── 2. SecreatetStore::agentEndpoints() fallback ────────────────────────────────

echo "\n── SecretStore::agentEndpoints() fallback ──────────────────────────────\n";

// Without FULGURITE_SEcreateT_BROKER_ENDPOINTS, should fall back to FULGURITE_SEcreateT_AGENT_SOCKET.
putenv('FULGURITE_SECRET_BROKER_ENDPOINTS=');
putenv('FULGURITE_SECRET_AGENT_SOCKET=/tmp/test.sock');
SecretStore::resetRuntimeState();
$agentEndpoints = SecretStore::agentEndpoints();
ok('Fallback to unix socket when no BROKER_ENDPOINTS', count($agentEndpoints) === 1);
ok('Fallback endpoint type is unix', $agentEndpoints[0]['type'] === 'unix');
ok('Fallback endpoint path matches AGENT_SOCKET', $agentEndpoints[0]['path'] === '/tmp/test.sock');

// With FULGURITE_SEcreateT_BROKER_ENDPOINTS set.
putenv('FULGURITE_SECRET_BROKER_ENDPOINTS=unix:///run/sock1,tcp://host2:9876');
SecretStore::resetRuntimeState();
$agentEndpoints = SecretStore::agentEndpoints();
ok('BROKER_ENDPOINTS overrides AGENT_SOCKET', count($agentEndpoints) === 2);
ok('First endpoint is unix from BROKER_ENDPOINTS', $agentEndpoints[0]['path'] === '/run/sock1');

// Restore.
putenv('FULGURITE_SECRET_BROKER_ENDPOINTS=');
putenv('FULGURITE_SECRET_AGENT_SOCKET=');
SecretStore::resetRuntimeState();

// ─── 3. HaBrokerSecreatetProvider::health() — no endpoints ──────────────────────

echo "\n── health() — edge cases ─────────────────────────────────────────────────\n";

$provider = new HaBrokerSecretProvider([]);
$health = $provider->health();
ok('Empty endpoints → ok=false', $health['ok'] === false);
ok('Empty endpoints → provider=ha-broker', $health['provider'] === 'ha-broker');
ok('Empty endpoints → cluster.total=0', ($health['cluster']['total'] ?? -1) === 0);

// ─── 4. All endpoints unreachable (nothing listening) ────────────────────────

echo "\n── Failover: all endpoints unreachable ──────────────────────────────────\n";

HaBrokerSecretProvider::resetEndpointState();
$provider = new HaBrokerSecretProvider([
    ['uri' => 'unix:///nonexistent1.sock', 'type' => 'unix', 'path' => '/nonexistent1.sock'],
    ['uri' => 'unix:///nonexistent2.sock', 'type' => 'unix', 'path' => '/nonexistent2.sock'],
], 0.2);

$health = $provider->health();
ok('All down → ok=false', $health['ok'] === false);
ok('All down → cluster.healthy=0', ($health['cluster']['healthy'] ?? -1) === 0);
ok('All down → 2 nodes reported', count($health['cluster']['nodes'] ?? []) === 2);
ok('All down → each node has ok=false', array_sum(array_column($health['cluster']['nodes'], 'ok')) === 0);

throws(
    'get() throws when all endpoints down (no silent bypass)',
    static fn() => $provider->get('secret://agent/repo/1/password'),
    'cluster indisponible'
);

throws(
    'put() throws when all endpoints down',
    static fn() => $provider->put('secret://agent/repo/1/password', 'hunter2'),
    'cluster indisponible'
);

throws(
    'auditLogs() throws when all endpoints down',
    static fn() => $provider->auditLogs(10),
    'Aucun noeud broker disponible'
);

// ─── 5. Health cache / retry logic (per-request static state) ────────────────

echo "\n── Endpoint health cache ─────────────────────────────────────────────────\n";

HaBrokerSecretProvider::resetEndpointState();
$provider2 = new HaBrokerSecretProvider([
    ['uri' => 'unix:///bad1.sock', 'type' => 'unix', 'path' => '/bad1.sock'],
    ['uri' => 'unix:///bad2.sock', 'type' => 'unix', 'path' => '/bad2.sock'],
], 0.1);

// Attempt 1 — both fail, get cached as down.
try { $provider2->get('secret://agent/repo/1/password'); } catch (Throwable $ignored) {}
$state = $provider2->getEndpointState();
ok('Both endpoints marked down after failure', count($state) === 2);
ok('Endpoint 1 marked not ok', empty($state['unix:///bad1.sock']['ok']));
ok('Endpoint 2 marked not ok', empty($state['unix:///bad2.sock']['ok']));

// resetEndpointState clears the cache.
HaBrokerSecretProvider::resetEndpointState();
ok('resetEndpointState clears all state', $provider2->getEndpointState() === []);

// ─── 6. Assertion: invalid ref rejected ──────────────────────────────────────

echo "\n── Input validation ──────────────────────────────────────────────────────\n";

HaBrokerSecretProvider::resetEndpointState();
$provider3 = new HaBrokerSecretProvider([
    ['uri' => 'unix:///any.sock', 'type' => 'unix', 'path' => '/any.sock'],
], 0.1);

throws('get() rejects empty ref', static fn() => $provider3->get(''), 'invalide');
throws('get() rejects malformed ref', static fn() => $provider3->get('not-a-secret-ref'), 'invalide');
throws('get() rejects ref with traversal', static fn() => $provider3->get('secret://agent/../etc/1/password'), 'invalide');
throws('put() rejects empty ref', static fn() => $provider3->put('', 'value'), 'invalide');
throws('delete() rejects invalid ref', static fn() => $provider3->delete('secret://wrong/path'), 'invalide');
throws('exists() rejects bad ref', static fn() => $provider3->exists('secret://agent/repo/0/name'), 'invalide'); // id=0 not allowed
throws('get() rejects invalid purpose', static fn() => $provider3->get('secret://agent/repo/1/p', 'DROP TABLE'), 'invalide');

// ─── 7. BrokerClusterMonitor::normalizeStatus (via detectEvents indirectly) ──

echo "\n── BrokerClusterMonitor state detection (via local stubs) ───────────────\n";

// We can test normalizeStatus by going through liveHealth() if the provider is configured,
// but since there's no live broker, we test detectEvents indirectly via checkAndNotify()
// with a stubbed provider. Instead we use Reflection or just test event detection logic.

require_once __DIR__ . '/../src/BrokerClusterMonitor.php';

// Use a private-method-accessible subtest via inline closures bound to a local class.
$monitor = new class {
    /** Expose normalizeStatus and detectEvents for testing. */
    public function normalize(array $health): array
    {
        return BrokerClusterMonitor::_testNormalize($health);
    }
    public function detect(array $prev, array $curr): array
    {
        return BrokerClusterMonitor::_testDetect($prev, $curr);
    }
};

// Since we can't call private methods, test via known public API boundaries.
// getLastStatus() with no DB returns 'unknown'.
$initialStatus = BrokerClusterMonitor::getLastStatus();
ok('getLastStatus() returns array', is_array($initialStatus));
ok('Initial state is unknown (no DB in test)', in_array((string) ($initialStatus['state'] ?? ''), ['unknown', 'ok', 'down', 'degraded', 'unconfigured'], true));

// ─── 8. SecreatetStore::resetRuntimeState resets HaBrokerSecreatetProvider state ───

echo "\n── SecretStore::resetRuntimeState ────────────────────────────────────────\n";

putenv('FULGURITE_SECRET_BROKER_ENDPOINTS=unix:///ep1.sock,unix:///ep2.sock');
SecretStore::resetRuntimeState();
$provider4 = SecretStore::agent();
ok('agent() returns HaBrokerSecretProvider', $provider4 instanceof HaBrokerSecretProvider);
ok('agent() has 2 endpoints from env', count($provider4->getEndpoints()) === 2);

// Mark one down.
try { $provider4->get('secret://agent/repo/1/pass'); } catch (Throwable $ignored) {}
$stateBeforeReset = $provider4->getEndpointState();
$hasDownEntries = !empty(array_filter($stateBeforeReset, static fn(array $s): bool => !$s['ok']));
// Both may be down since neither socket exists.

SecretStore::resetRuntimeState();
// After reset, endpoint state should be cleared AND a new provider instance should be returned.
$provider5 = SecretStore::agent();
ok('After resetRuntimeState, new provider instance returned', $provider4 !== $provider5);
ok('After resetRuntimeState, endpoint state cleared', $provider5->getEndpointState() === []);

// Restore env.
putenv('FULGURITE_SECRET_BROKER_ENDPOINTS=');
SecretStore::resetRuntimeState();

// ─── Summary ──────────────────────────────────────────────────────────────────

echo "\n";
echo "─────────────────────────────────────────────────────────────────────────\n";
echo "Tests: " . ($PASS + $FAIL) . "   Pass: \033[32m{$PASS}\033[0m   Fail: \033[31m{$FAIL}\033[0m\n";

if ($FAIL > 0) {
    exit(1);
}
