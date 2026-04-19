<?php

declare(strict_types=1);

function fail(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fail(
            $message
            . "\nExpected: " . var_export($expected, true)
            . "\nActual: " . var_export($actual, true)
        );
    }
}

function assertNullValue(mixed $actual, string $message): void {
    if ($actual !== null) {
        fail($message . "\nActual: " . var_export($actual, true));
    }
}

function headerValue(array $headers, string $name): ?string {
    $needle = strtolower($name) . ':';
    foreach ($headers as $header) {
        if (str_starts_with(strtolower($header), $needle)) {
            return trim(substr($header, strlen($needle)));
        }
    }

    return null;
}

function removeDirectory(string $path): void {
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($path);
}

function resetDatabaseConnections(): void {
    $reflection = new ReflectionClass(Database::class);
    foreach (['instance', 'indexInstance'] as $propertyName) {
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}

function writePrependFile(string $path): void {
    file_put_contents($path, "<?php\ndefine('FULGURITE_CLI', true);\n");
}

function parseCgiResponse(string $stdout): array {
    $parts = preg_split("/\r\n\r\n|\n\n/", $stdout, 2);
    if (!is_array($parts) || count($parts) !== 2) {
        fail("Unable to parse CGI response.\nSTDOUT:\n" . trim($stdout));
    }

    $headerLines = preg_split("/\r\n|\n|\r/", trim($parts[0])) ?: [];
    $statusHeader = headerValue($headerLines, 'Status');
    if ($statusHeader === null || preg_match('/^(\d{3})\b/', $statusHeader, $matches) !== 1) {
        fail("CGI response is missing a valid Status header.\nHeaders:\n" . implode("\n", $headerLines));
    }

    return [
        'status' => (int) $matches[1],
        'headers' => $headerLines,
        'body' => $parts[1],
    ];
}

function executeCgiRequest(string $root, string $tmp, string $method, string $uri, array $extraEnv = []): array {
    $prependPath = $tmp . DIRECTORY_SEPARATOR . 'prepend.php';
    writePrependFile($prependPath);

    $phpCgi = getenv('PHP_CGI_BINARY') ?: (dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'php-cgi.exe');
    if (!is_file($phpCgi)) {
        fail('php-cgi binary not found.');
    }
    $command = '"'
        . str_replace('"', '\"', $phpCgi)
        . '" -d auto_prepend_file="'
        . str_replace('"', '\"', $prependPath)
        . '"';

    $env = array_merge($_ENV, [
        'REDIRECT_STATUS' => '1',
        'GATEWAY_INTERFACE' => 'CGI/1.1',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'REQUEST_METHOD' => $method,
        'REQUEST_URI' => $uri,
        'SCRIPT_NAME' => '/api/v1/index.php',
        'SCRIPT_FILENAME' => $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'v1' . DIRECTORY_SEPARATOR . 'index.php',
        'DOCUMENT_ROOT' => $root . DIRECTORY_SEPARATOR . 'public',
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_HOST' => 'localhost',
        'CONTENT_LENGTH' => '0',
        'DB_DRIVER' => 'sqlite',
        'DB_PATH' => $tmp . DIRECTORY_SEPARATOR . 'fulgurite.db',
        'SEARCH_DB_PATH' => $tmp . DIRECTORY_SEPARATOR . 'fulgurite-search.db',
    ], $extraEnv);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, $root, $env);
    if (!is_resource($process)) {
        fail('Unable to start php-cgi.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        fail("php-cgi request failed.\nSTDERR:\n" . trim((string) $stderr));
    }

    return parseCgiResponse((string) $stdout);
}

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fulgurite-api-cors-test-' . bin2hex(random_bytes(4));
if (!mkdir($tmp, 0700, true) && !is_dir($tmp)) {
    fail('Unable to create test temp directory.');
}

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';

require_once $root . '/src/bootstrap.php';

try {
    Database::setSetting('api_enabled', '1');
    Database::setSetting('api_cors_allowed_origins', 'https://b.test');

    $userId = UserManager::createUser([
        'username' => 'cors-user-' . bin2hex(random_bytes(4)),
        'password_hash' => password_hash('test-password', PASSWORD_DEFAULT),
        'role' => ROLE_ADMIN,
    ]);

    $created = ApiTokenManager::create($userId, [
        'name' => 'cors-token',
        'scopes' => ['me:read'],
        'allowed_origins' => ['https://a.test'],
    ], $userId);
    $secret = (string) $created['secret'];

    $preflight = executeCgiRequest($root, $tmp, 'OPTIONS', '/api/v1/me', [
        'HTTP_ORIGIN' => 'https://b.test',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);
    assertSameValue(204, $preflight['status'], 'Global preflight should be accepted.');
    assertSameValue(
        'https://b.test',
        headerValue($preflight['headers'], 'Access-Control-Allow-Origin'),
        'Global preflight should expose Access-Control-Allow-Origin.'
    );

    $mismatch = executeCgiRequest($root, $tmp, 'GET', '/api/v1/me', [
        'HTTP_ORIGIN' => 'https://b.test',
        'HTTP_AUTHORIZATION' => 'Bearer ' . $secret,
    ]);
    assertSameValue(403, $mismatch['status'], 'Request with Origin outside token.allowed_origins must be rejected.');
    assertNullValue(
        headerValue($mismatch['headers'], 'Access-Control-Allow-Origin'),
        'Rejected request must not expose Access-Control-Allow-Origin.'
    );
} finally {
    resetDatabaseConnections();
    removeDirectory($tmp);
}

echo "API v1 CORS tests OK.\n";
