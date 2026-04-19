<?php

declare(strict_types=1);

class DatabaseConfigWriter
{
    public static function buildConfigPhp(array $values): string
    {
        $lines = [
            '<?php',
            "define('DB_DRIVER', " . var_export((string) ($values['DB_DRIVER'] ?? 'sqlite'), true) . ');',
            "define('DB_PATH', " . var_export((string) ($values['DB_PATH'] ?? dirname(__DIR__) . '/data/fulgurite.db'), true) . ');',
            "define('SEARCH_DB_PATH', " . var_export((string) ($values['SEARCH_DB_PATH'] ?? dirname(__DIR__) . '/data/fulgurite-search.db'), true) . ');',
            "define('DB_HOST', " . var_export((string) ($values['DB_HOST'] ?? 'localhost'), true) . ');',
            "define('DB_PORT', " . var_export((string) ($values['DB_PORT'] ?? ''), true) . ');',
            "define('DB_NAME', " . var_export((string) ($values['DB_NAME'] ?? 'fulgurite'), true) . ');',
            "define('DB_USER', " . var_export((string) ($values['DB_USER'] ?? ''), true) . ');',
            "define('DB_PASS', " . var_export((string) ($values['DB_PASS'] ?? ''), true) . ');',
            "define('DB_CHARSET', " . var_export((string) ($values['DB_CHARSET'] ?? 'utf8mb4'), true) . ');',
        ];

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    public static function writeConfigPhp(string $path, array $values): bool
    {
        return file_put_contents($path, self::buildConfigPhp($values)) !== false;
    }
}
