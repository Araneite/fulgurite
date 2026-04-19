<?php

declare(strict_types=1);

$configPath = $argv[1] ?? '';
if (!is_string($configPath) || $configPath === '') {
    fwrite(STDERR, "Missing config path.\n");
    exit(1);
}

require $configPath;

echo json_encode([
    'DB_DRIVER' => DB_DRIVER,
    'DB_PATH' => DB_PATH,
    'SEARCH_DB_PATH' => SEARCH_DB_PATH,
    'DB_HOST' => DB_HOST,
    'DB_PORT' => DB_PORT,
    'DB_NAME' => DB_NAME,
    'DB_USER' => DB_USER,
    'DB_PASS' => DB_PASS,
    'DB_CHARSET' => DB_CHARSET,
], JSON_THROW_ON_ERROR);
