<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'Plugins\\';
    
    if (! str_starts_with($class, $prefix)) {
        return;
    }
    
    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);
    
    $pluginName = array_shift($parts);
    
    if (! $pluginName || empty($parts)) {
        return;
    }
    
    $classPath = implode(DIRECTORY_SEPARATOR, $parts) . '.php';
    
    $file = __DIR__
        . DIRECTORY_SEPARATOR
        . $pluginName
        . DIRECTORY_SEPARATOR
        . 'src'
        . DIRECTORY_SEPARATOR
        . $classPath;
    
    if (is_file($file)) {
        require $file;
    }
});
