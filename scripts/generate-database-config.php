<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../src/DatabaseConfigWriter.php';

$outputPath = $argv[1] ?? '';
if (!is_string($outputPath) || $outputPath === '') {
    fwrite(STDERR, "Usage: php scripts/generate-database-config.php <output-path>\n");
    exit(1);
}

$values = [
    'DB_DRIVER' => getenv('DB_DRIVER') !== false ? (string) getenv('DB_DRIVER') : 'sqlite',
    'DB_PATH' => getenv('DB_PATH') !== false ? (string) getenv('DB_PATH') : dirname(__DIR__) . '/data/fulgurite.db',
    'SEARCH_DB_PATH' => getenv('SEARCH_DB_PATH') !== false ? (string) getenv('SEARCH_DB_PATH') : dirname(__DIR__) . '/data/fulgurite-search.db',
    'DB_HOST' => getenv('DB_HOST') !== false ? (string) getenv('DB_HOST') : 'localhost',
    'DB_PORT' => getenv('DB_PORT') !== false ? (string) getenv('DB_PORT') : '',
    'DB_NAME' => getenv('DB_NAME') !== false ? (string) getenv('DB_NAME') : 'fulgurite',
    'DB_USER' => getenv('DB_USER') !== false ? (string) getenv('DB_USER') : '',
    'DB_PASS' => getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '',
    'DB_CHARSET' => getenv('DB_CHARSET') !== false ? (string) getenv('DB_CHARSET') : 'utf8mb4',
];

if (!DatabaseConfigWriter::writeConfigPhp($outputPath, $values)) {
    fwrite(STDERR, "Unable to write database config.\n");
    exit(1);
}
