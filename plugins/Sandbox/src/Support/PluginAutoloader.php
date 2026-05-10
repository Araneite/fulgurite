<?php

namespace Plugins\Sandbox\Support;

class PluginAutoloader
{
    private static bool $registered = false;
    
    public static function register(string $pluginsPath): void
    {
        if (self::$registered) {
            return;
        }
        
        spl_autoload_register(function (string $class) use ($pluginsPath): void {
            $prefix = 'Plugins\\';
            
            if (! str_starts_with($class, $prefix)) {
                return;
            }
            
            if (
                str_starts_with($class, 'Plugins\\Sandbox\\') ||
                str_starts_with($class, 'Plugins\\CoreReadModels\\')
            ) {
                return;
            }
            
            $relative = substr($class, strlen($prefix));
            $parts = explode('\\', $relative);
            $pluginName = array_shift($parts);
            
            if (! $pluginName || empty($parts)) {
                return;
            }
            
            $file = $pluginsPath
                .DIRECTORY_SEPARATOR.$pluginName
                .DIRECTORY_SEPARATOR.'src'
                .DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts)
                .'.php';
            
            if (is_file($file)) {
                require $file;
            }
        });
        
        self::$registered = true;
    }
}
