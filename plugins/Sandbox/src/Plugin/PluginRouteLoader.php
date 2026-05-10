<?php

namespace Plugins\Sandbox\Plugin;

class PluginRouteLoader
{
	public function load(object $plugin): void {
        $pluginName = str($plugin->provider)
            ->after("Plugins\\")
            ->before("\\")
            ->toString();
        
        $routeFile = base_path("plugins/{$pluginName}/routes/api.php");
        
        if (is_file($routeFile)) {
            require $routeFile;
        }
    }
}
