<?php

use Pdo\Mysql;

$pluginDriver = env('PLUGINS_DB_CONNECTION') ?: env('DB_CONNECTION', 'sqlite');

return [
    "default"=> env('PLUGINS_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
    
    "connections"=>[
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('PLUGINS_DB_URL') ?: env('DB_URL'),
            'host' => env('PLUGINS_DB_HOST') ?: env('DB_HOST', '127.0.0.1'),
            'port' => env('PLUGINS_DB_PORT') ?: env('DB_PORT', '5432'),
            'database' => env('PLUGINS_DB_DATABASE') ?: env('DB_DATABASE', 'laravel'),
            'username' => env('PLUGINS_DB_USERNAME'),
            'password' => env('PLUGINS_DB_PASSWORD'),
            'charset' => env('PLUGINS_DB_CHARSET') ?: env('DB_CHARSET', 'utf8'),
            'prefix' => env('PLUGINS_DB_PREFIX', ''),
            'prefix_indexes' => true,
            'search_path' => env('PLUGINS_DB_SCHEMA') ?: env('DB_SCHEMA', 'public'),
            'sslmode' => env('PLUGINS_DB_SSLMODE') ?: env('DB_SSLMODE', 'prefer'),
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('PLUGINS_DB_URL') ?: env('DB_URL'),
            'database' => env('PLUGINS_DB_DATABASE') ?: database_path('plugins.sqlite'),
            'prefix' => env('PLUGINS_DB_PREFIX', ''),
            'foreign_key_constraints' => env('PLUGINS_DB_FOREIGN_KEYS', true),
        ],
        
        'mysql' => [
            'driver' => $pluginDriver,
            'url' => env('PLUGINS_DB_URL') ?: env('DB_URL'),
            'host' => env('PLUGINS_DB_HOST') ?: env('DB_HOST', '127.0.0.1'),
            'port' => env('PLUGINS_DB_PORT') ?: env('DB_PORT', '3306'),
            'database' => env('PLUGINS_DB_DATABASE') ?: env('DB_DATABASE', 'laravel'),
            'username' => env('PLUGINS_DB_USERNAME'),
            'password' => env('PLUGINS_DB_PASSWORD'),
            'unix_socket' => env('PLUGINS_DB_SOCKET') ?: env('DB_SOCKET', ''),
            'charset' => env('PLUGINS_DB_CHARSET') ?: env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('PLUGINS_DB_COLLATION') ?: env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('PLUGINS_DB_PREFIX', ''),
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
        
        'mariadb'=> [
            'driver' => $pluginDriver,
            'url' => env('PLUGINS_DB_URL') ?: env('DB_URL'),
            'host' => env('PLUGINS_DB_HOST') ?: env('DB_HOST', '127.0.0.1'),
            'port' => env('PLUGINS_DB_PORT') ?: env('DB_PORT', '3306'),
            'database' => env('PLUGINS_DB_DATABASE') ?: env('DB_DATABASE', 'laravel'),
            'username' => env('PLUGINS_DB_USERNAME'),
            'password' => env('PLUGINS_DB_PASSWORD'),
            'unix_socket' => env('PLUGINS_DB_SOCKET') ?: env('DB_SOCKET', ''),
            'charset' => env('PLUGINS_DB_CHARSET') ?: env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('PLUGINS_DB_COLLATION') ?: env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('PLUGINS_DB_PREFIX', ''),
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
        
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('PLUGINS_DB_URL') ?: env('DB_URL'),
            'host' => env('PLUGINS_DB_HOST') ?: env('DB_HOST', 'localhost'),
            'port' => env('PLUGINS_DB_PORT') ?: env('DB_PORT', '1433'),
            'database' => env('PLUGINS_DB_DATABASE') ?: env('DB_DATABASE', 'laravel'),
            'username' => env('PLUGINS_DB_USERNAME'),
            'password' => env('PLUGINS_DB_PASSWORD'),
            'charset' => env('PLUGINS_DB_CHARSET') ?: env('DB_CHARSET', 'utf8'),
            'prefix' => env('PLUGINS_DB_PREFIX', ''),
            'prefix_indexes' => true,
        ],
    ],
    
    "migrations"=> [
        "table"=> "plugin_migrations",
    ]
];
