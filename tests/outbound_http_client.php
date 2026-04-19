<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-outbound-http-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

putenv('DB_PATH=' . $tmp . '/app.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/search.db');
$_ENV['DB_PATH'] = $tmp . '/app.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/search.db';

require_once $root . '/src/bootstrap.php';

function failHttpTest(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertTrueHttpTest(bool $condition, string $message): void {
    if (!$condition) {
        failHttpTest($message);
    }
}

function assertSameHttpTest(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        failHttpTest($message . ' Expected: ' . var_export($expected, true) . ' Got: ' . var_export($actual, true));
    }
}

function assertThrowsHttpTest(callable $callable, string $message): void {
    try {
        $callable();
    } catch (Throwable) {
        return;
    }

    failHttpTest($message);
}

function cleanupHttpTestDir(string $path): void {
    if (!is_dir($path)) {
        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($itemPath)) {
            cleanupHttpTestDir($itemPath);
            @rmdir($itemPath);
            continue;
        }

        @unlink($itemPath);
    }
}

function reserveHttpTestPort(): int {
    $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!is_resource($server)) {
        failHttpTest('Unable to reserve a local TCP port for outbound HTTP tests: ' . $error);
    }

    $address = (string) stream_socket_get_name($server, false);
    fclose($server);

    $port = (int) substr(strrchr($address, ':'), 1);
    if ($port <= 0) {
        failHttpTest('Unable to determine a local TCP port for outbound HTTP tests.');
    }

    return $port;
}

function startHttpTestServer(string $tmp, int $port): array {
    $routerPath = $tmp . DIRECTORY_SEPARATOR . 'router.php';
    $stdoutPath = $tmp . DIRECTORY_SEPARATOR . 'server.out.log';
    $stderrPath = $tmp . DIRECTORY_SEPARATOR . 'server.err.log';
    $redirectHost = 'redirect-target.test';
    $blockedHost = 'blocked-target.test';

    $router = <<<'PHP'
<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

if ($uri === '/pinned' || $uri === '/final') {
    header('Content-Type: application/json');
    echo json_encode([
        'host' => $host,
        'path' => $uri,
        'remote_addr' => $remote,
    ], JSON_UNESCAPED_SLASHES);
    return true;
}

if ($uri === '/redirect-safe') {
    header('Location: http://__REDIRECT_HOST__:__PORT__/final', true, 302);
    echo 'redirecting';
    return true;
}

if ($uri === '/redirect-blocked') {
    header('Location: http://__BLOCKED_HOST__:__PORT__/final', true, 302);
    echo 'redirecting';
    return true;
}

http_response_code(404);
echo 'not-found';
PHP;
    $router = str_replace(
        ['__REDIRECT_HOST__', '__BLOCKED_HOST__', '__PORT__'],
        [$redirectHost, $blockedHost, (string) $port],
        $router
    );
    file_put_contents($routerPath, $router);

    $command = '"' . PHP_BINARY . '" -S 127.0.0.1:' . $port . ' "' . $routerPath . '"';
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['file', $stdoutPath, 'a'],
        2 => ['file', $stderrPath, 'a'],
    ], $pipes, $root = dirname(__DIR__));
    if (!is_resource($process)) {
        failHttpTest('Unable to start the local HTTP server used by outbound tests.');
    }
    if (isset($pipes[0]) && is_resource($pipes[0])) {
        fclose($pipes[0]);
    }

    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $errno, $error, 0.2);
        if (is_resource($socket)) {
            fclose($socket);
            $ready = true;
            break;
        }
        usleep(100000);
    }

    if (!$ready) {
        proc_terminate($process);
        proc_close($process);
        $stderr = is_file($stderrPath) ? (string) file_get_contents($stderrPath) : '';
        failHttpTest('Local HTTP server did not become ready for outbound tests. ' . trim($stderr));
    }

    return [
        'process' => $process,
        'router_path' => $routerPath,
    ];
}

final class TestPinnedOutboundValidator implements OutboundUrlValidator {
    /** @var array<string,string[]> */
    private array $hostMap;
    private int $expectedPort;
    /** @var string[] */
    public array $validatedUrls = [];

    /**
     * @param array<string,string[]> $hostMap
     */
    public function __construct(array $hostMap, int $expectedPort) {
        $this->hostMap = $hostMap;
        $this->expectedPort = $expectedPort;
    }

    /**
     * @return array<string,mixed>
     */
    public function validate(string $url): array {
        $this->validatedUrls[] = $url;

        $parts = OutboundUrlTools::parseUrl($url);
        if ($parts['scheme'] !== 'http') {
            throw new InvalidArgumentException('Test validator only allows HTTP.');
        }
        if ($parts['port'] !== $this->expectedPort) {
            throw new InvalidArgumentException('Unexpected outbound port for test transport.');
        }
        if (!isset($this->hostMap[$parts['host']])) {
            throw new InvalidArgumentException('Host not allowed by test validator: ' . $parts['host']);
        }

        $parts['resolved_ips'] = $this->hostMap[$parts['host']];
        return $parts;
    }
}

$port = reserveHttpTestPort();
$server = startHttpTestServer($tmp, $port);

try {
    $pinnedValidator = new TestPinnedOutboundValidator([
        'pinned-target.test' => ['127.0.0.1'],
    ], $port);
    $pinnedResponse = OutboundHttpClient::request('GET', 'http://pinned-target.test:' . $port . '/pinned', [
        'timeout' => 5,
        'connect_timeout' => 2,
        'max_redirects' => 0,
        'user_agent' => 'Fulgurite-Test/1.0',
    ], $pinnedValidator);

    assertTrueHttpTest($pinnedResponse['success'] === true, 'Pinned outbound request should succeed through the validated IP.');
    assertSameHttpTest(200, $pinnedResponse['status'], 'Pinned outbound request should return HTTP 200.');
    assertSameHttpTest('127.0.0.1', $pinnedResponse['primary_ip'] ?? null, 'Pinned outbound request must connect to the validated IP.');
    $pinnedBody = json_decode((string) ($pinnedResponse['body'] ?? ''), true);
    assertSameHttpTest('pinned-target.test:' . $port, $pinnedBody['host'] ?? null, 'Pinned outbound request must preserve the original Host header.');
    assertSameHttpTest(['/pinned'], [$pinnedBody['path'] ?? null], 'Pinned outbound request should reach the expected path.');

    $redirectValidator = new TestPinnedOutboundValidator([
        'origin-target.test' => ['127.0.0.1'],
        'redirect-target.test' => ['127.0.0.1'],
    ], $port);
    $redirectResponse = OutboundHttpClient::request('GET', 'http://origin-target.test:' . $port . '/redirect-safe', [
        'timeout' => 5,
        'connect_timeout' => 2,
        'max_redirects' => 1,
        'user_agent' => 'Fulgurite-Test/1.0',
    ], $redirectValidator);

    assertTrueHttpTest($redirectResponse['success'] === true, 'Validated redirect chain should succeed.');
    assertSameHttpTest(1, $redirectResponse['redirect_count'], 'Validated redirect chain should record one redirect.');
    assertSameHttpTest(
        'http://redirect-target.test:' . $port . '/final',
        $redirectResponse['final_url'],
        'Validated redirect chain should expose the final URL.'
    );
    assertSameHttpTest(
        [
            'http://origin-target.test:' . $port . '/redirect-safe',
            'http://redirect-target.test:' . $port . '/final',
        ],
        $redirectValidator->validatedUrls,
        'Each redirect hop must be revalidated before the next connection.'
    );
    $redirectBody = json_decode((string) ($redirectResponse['body'] ?? ''), true);
    assertSameHttpTest(
        'redirect-target.test:' . $port,
        $redirectBody['host'] ?? null,
        'Redirected outbound request must preserve the redirected Host header.'
    );
    assertSameHttpTest('127.0.0.1', $redirectResponse['primary_ip'] ?? null, 'Redirected outbound request must still use the validated IP.');

    $blockedRedirectValidator = new TestPinnedOutboundValidator([
        'origin-target.test' => ['127.0.0.1'],
    ], $port);
    assertThrowsHttpTest(
        static function () use ($blockedRedirectValidator, $port): void {
            OutboundHttpClient::request('GET', 'http://origin-target.test:' . $port . '/redirect-blocked', [
                'timeout' => 5,
                'connect_timeout' => 2,
                'max_redirects' => 1,
                'user_agent' => 'Fulgurite-Test/1.0',
            ], $blockedRedirectValidator);
        },
        'Redirects to an unvalidated target must be blocked.'
    );
    assertSameHttpTest(
        [
            'http://origin-target.test:' . $port . '/redirect-blocked',
            'http://blocked-target.test:' . $port . '/final',
        ],
        $blockedRedirectValidator->validatedUrls,
        'Blocked redirects must still be validated before they are rejected.'
    );
} finally {
    if (isset($server['process']) && is_resource($server['process'])) {
        proc_terminate($server['process']);
        proc_close($server['process']);
    }
    cleanupHttpTestDir($tmp);
    @rmdir($tmp);
}

echo "outbound_http_client tests OK.\n";
