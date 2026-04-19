<?php

declare(strict_types=1);

if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
    echo "Secret agent provider test skipped on Windows (Unix sockets required).\n";
    exit(0);
}

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-agent-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

$key = base64_encode(random_bytes(32));
$socket = $tmp . '/secrets.sock';
$dbPath = $tmp . '/secrets.db';

putenv('FULGURITE_SECRET_AGENT_SOCKET=' . $socket);
putenv('FULGURITE_SECRET_AGENT_DB=' . $dbPath);
putenv('FULGURITE_SECRET_AGENT_KEY=' . $key);
$_ENV['FULGURITE_SECRET_AGENT_SOCKET'] = $socket;
$_ENV['FULGURITE_SECRET_AGENT_DB'] = $dbPath;
$_ENV['FULGURITE_SECRET_AGENT_KEY'] = $key;
$_SERVER['FULGURITE_SECRET_AGENT_SOCKET'] = $socket;
$_SERVER['FULGURITE_SECRET_AGENT_DB'] = $dbPath;
$_SERVER['FULGURITE_SECRET_AGENT_KEY'] = $key;

require_once $root . '/src/SecretStore.php';
require_once $root . '/src/InfisicalClient.php';

$cmd = PHP_BINARY . ' ' . escapeshellarg($root . '/bin/fulgurite-secret-agent') . ' 2>/dev/null';
$proc = proc_open($cmd, [], $pipes);
if (!is_resource($proc)) {
    fwrite(STDERR, "Unable to start secret agent.\n");
    exit(1);
}

try {
    $deadline = microtime(true) + 3.0;
    while (!is_file($socket) && microtime(true) < $deadline) {
        usleep(50000);
    }
    $ref = 'secret://agent/repo/1/password';
    SecretStore::put($ref, 'agent-secret', ['test' => true]);
    if (SecretStore::get($ref, 'backup', ['repo_id' => 1]) !== 'agent-secret') {
        throw new RuntimeException('Agent provider did not return the stored secret.');
    }
    if (!SecretStore::exists($ref)) {
        throw new RuntimeException('Agent provider exists failed.');
    }
    SecretStore::delete($ref);
    if (SecretStore::exists($ref)) {
        throw new RuntimeException('Agent provider delete failed.');
    }
    echo "Secret agent provider tests OK.\n";
} finally {
    proc_terminate($proc);
    proc_close($proc);
    foreach (glob($tmp . '/*') ?: [] as $path) {
        @unlink($path);
    }
    @rmdir($tmp);
}
