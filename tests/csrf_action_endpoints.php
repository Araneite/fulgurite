<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$apiDir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'api';

$missing = [];
$checked = 0;

foreach (glob($apiDir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $path) {
    $source = (string) file_get_contents($path);
    $isSessionBacked = preg_match('/\bAuth::(?:check|require[A-Za-z]*)\s*\(/', $source) === 1;
    $readsRequestBody = str_contains($source, 'php://input');

    if (!$isSessionBacked || !$readsRequestBody) {
        continue;
    }

    $checked++;

    if (preg_match('/\bverifyCsrf\s*\(/', $source) === 1) {
        continue;
    }

    $missing[] = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
}

if ($missing !== []) {
    fwrite(
        STDERR,
        "Session-backed non-GET endpoints missing verifyCsrf():\n - " . implode("\n - ", $missing) . "\n"
    );
    exit(1);
}

echo "CSRF guard OK for $checked session-backed request-body API endpoints.\n";
