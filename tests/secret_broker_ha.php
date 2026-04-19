<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-secret-broker-ha-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

$key = base64_encode(random_bytes(32));
putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
putenv('FULGURITE_SECRET_KEY=' . $key);
putenv('FULGURITE_SECRET_PROVIDER=agent');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_ENV['FULGURITE_SECRET_KEY'] = $key;
$_ENV['FULGURITE_SECRET_PROVIDER'] = 'agent';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['FULGURITE_SECRET_KEY'] = $key;
$_SERVER['FULGURITE_SECRET_PROVIDER'] = 'agent';

require_once $root . '/src/bootstrap.php';

final class StaticLocalSecretProvider implements SecretProvider
{
    public function put(string $ref, string $value, array $metadata = []): string { return $ref; }
    public function get(string $ref, string $purpose = 'runtime', array $context = []): ?string { return null; }
    public function delete(string $ref): void {}
    public function exists(string $ref): bool { return false; }
    public function health(): array { return ['ok' => true, 'provider' => 'local']; }
}

final class SequencedBrokerHealthProvider implements SecretProvider
{
    /** @param list<array<string,mixed>> $states */
    public function __construct(private array $states) {}

    public function put(string $ref, string $value, array $metadata = []): string { return $ref; }
    public function get(string $ref, string $purpose = 'runtime', array $context = []): ?string { return null; }
    public function delete(string $ref): void {}
    public function exists(string $ref): bool { return false; }

    public function health(): array
    {
        if (count($this->states) > 1) {
            return array_shift($this->states);
        }
        return $this->states[0] ?? ['ok' => false, 'provider' => 'ha-broker', 'cluster' => ['total' => 0, 'healthy' => 0, 'nodes' => []]];
    }
}

function failTest(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertTrueValue(bool $value, string $message): void
{
    if (!$value) {
        failTest($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        failTest($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

function removeTree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($path);
}

try {
    Database::setSetting('secret_broker_cluster_state', '');
    Database::setSetting('secret_broker_cluster_selected_endpoint', '');

    $agentProvider = new SequencedBrokerHealthProvider([
        [
            'ok' => true,
            'provider' => 'ha-broker',
            'cluster' => [
                'total' => 2,
                'healthy' => 2,
                'degraded' => false,
                'selected_endpoint' => 'tcp://broker-a:9876',
                'nodes' => [
                    ['endpoint' => 'tcp://broker-a:9876', 'ok' => true, 'node_id' => 'a', 'node_label' => 'broker-a', 'backend' => 'shared-db'],
                    ['endpoint' => 'tcp://broker-b:9876', 'ok' => true, 'node_id' => 'b', 'node_label' => 'broker-b', 'backend' => 'shared-db'],
                ],
            ],
        ],
        [
            'ok' => true,
            'provider' => 'ha-broker',
            'cluster' => [
                'total' => 2,
                'healthy' => 1,
                'degraded' => true,
                'selected_endpoint' => 'tcp://broker-a:9876',
                'nodes' => [
                    ['endpoint' => 'tcp://broker-a:9876', 'ok' => true, 'node_id' => 'a', 'node_label' => 'broker-a', 'backend' => 'shared-db'],
                    ['endpoint' => 'tcp://broker-b:9876', 'ok' => false, 'node_id' => 'b', 'node_label' => 'broker-b', 'backend' => 'shared-db', 'error' => 'timeout'],
                ],
            ],
        ],
        [
            'ok' => true,
            'provider' => 'ha-broker',
            'cluster' => [
                'total' => 2,
                'healthy' => 1,
                'degraded' => true,
                'selected_endpoint' => 'tcp://broker-b:9876',
                'nodes' => [
                    ['endpoint' => 'tcp://broker-a:9876', 'ok' => false, 'node_id' => 'a', 'node_label' => 'broker-a', 'backend' => 'shared-db', 'error' => 'timeout'],
                    ['endpoint' => 'tcp://broker-b:9876', 'ok' => true, 'node_id' => 'b', 'node_label' => 'broker-b', 'backend' => 'shared-db'],
                ],
            ],
        ],
        [
            'ok' => true,
            'provider' => 'ha-broker',
            'cluster' => [
                'total' => 2,
                'healthy' => 2,
                'degraded' => false,
                'selected_endpoint' => 'tcp://broker-b:9876',
                'nodes' => [
                    ['endpoint' => 'tcp://broker-a:9876', 'ok' => true, 'node_id' => 'a', 'node_label' => 'broker-a', 'backend' => 'shared-db'],
                    ['endpoint' => 'tcp://broker-b:9876', 'ok' => true, 'node_id' => 'b', 'node_label' => 'broker-b', 'backend' => 'shared-db'],
                ],
            ],
        ],
    ]);

    SecretStore::useProvidersForTests($agentProvider, new StaticLocalSecretProvider());

    $first = BrokerClusterMonitor::checkAndNotify();
    assertSameValue('ok', $first['status']['state'] ?? null, 'Initial HA broker state should be healthy.');

    $second = BrokerClusterMonitor::checkAndNotify();
    assertSameValue('degraded', $second['status']['state'] ?? null, 'Degraded cluster state should be detected.');
    $events = SecretBrokerEvents::recentEvents(20);
    $eventTypes = array_column($events, 'event_type');
    assertTrueValue(in_array('node_failed', $eventTypes, true), 'A failing broker node should create a node_failed event.');
    assertSameValue('error', Database::getInstance()->query("SELECT status FROM secret_broker_status WHERE endpoint = 'tcp://broker-b:9876'")->fetchColumn(), 'Failed node status should persist in secret_broker_status.');

    $third = BrokerClusterMonitor::checkAndNotify();
    assertTrueValue(in_array('failover', $third['events'] ?? [], true), 'Endpoint switch should be reported as a failover.');
    $events = SecretBrokerEvents::recentEvents(20);
    $eventTypes = array_column($events, 'event_type');
    assertTrueValue(in_array('failover', $eventTypes, true), 'Failover should be persisted as a broker event.');

    $fourth = BrokerClusterMonitor::checkAndNotify();
    assertSameValue('ok', $fourth['status']['state'] ?? null, 'Recovered cluster should return to healthy state.');
    $events = SecretBrokerEvents::recentEvents(20);
    $eventTypes = array_column($events, 'event_type');
    assertTrueValue(in_array('recovered', $eventTypes, true), 'Cluster recovery should create a recovered event.');
    assertSameValue('ok', Database::getSetting('secret_broker_cluster_state', ''), 'Cluster state setting should be updated after recovery.');

    echo "Secret broker HA tests OK.\n";
} finally {
    SecretStore::resetRuntimeState();
    removeTree($tmp);
}
