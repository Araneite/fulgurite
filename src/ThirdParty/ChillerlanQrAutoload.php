<?php

if (defined('FULGURITE_CHILLERLAN_QR_AUTOLOAD_REGISTERED')) {
    return;
}

define('FULGURITE_CHILLERLAN_QR_AUTOLOAD_REGISTERED', true);

spl_autoload_register(static function (string $class): void {
    static $prefixes = [
        'chillerlan\\QRCode\\' => __DIR__ . '/../../lib/php-qrcode/src/',
        'chillerlan\\Settings\\' => __DIR__ . '/../../lib/php-settings-container/src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        if ($relativeClass === false || $relativeClass === '') {
            return;
        }

        $path = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (is_file($path)) {
            require_once $path;
        }

        return;
    }
});
