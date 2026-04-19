<?php
declare(strict_types=1);

define('FULGURITE_CLI', true);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script doit etre execute en CLI.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/src/Profiler.php';
RequestProfiler::bootstrap();
require_once dirname(__DIR__) . '/src/AppConfig.php';
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/DatabaseMigrations.php';

try {
    DatabaseMigrations::migrateConfiguredDatabases();
    fwrite(STDOUT, "Migrations appliquees avec succes.\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Echec migration: " . $e->getMessage() . "\n");
    exit(1);
}

