<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
require_once $root . '/src/bootstrap.php';

function fail(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertThrows(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }

    fail($message . "\nExpected exception: InvalidArgumentException or RuntimeException");
}

function assertDoesNotThrow(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable $e) {
        fail($message . "\nUnexpected exception: " . $e::class . "\nMessage: " . $e->getMessage());
    }
}

$validate = new ReflectionMethod(Notifier::class, 'validateOutgoingUrl');
$validate->setAccessible(true);

$blockedUrls = [
    'file:///etc/passwd',
    'http://example.com',
    'https://localhost',
    'https://127.0.0.1',
    'https://192.168.1.10',
    'https://10.0.0.5',
    'https://169.254.169.254/latest/meta-data',
];

foreach ($blockedUrls as $url) {
    assertThrows(
        static fn() => $validate->invoke(null, $url),
        'Blocked SSRF candidate should be rejected: ' . $url
    );
}

assertDoesNotThrow(
    static fn() => $validate->invoke(null, 'https://1.1.1.1/webhook'),
    'Public HTTPS IP should remain allowed.'
);

echo "notification_ssrf_guard tests OK.\n";
