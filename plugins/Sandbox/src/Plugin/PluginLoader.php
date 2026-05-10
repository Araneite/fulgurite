<?php

namespace Plugins\Sandbox\Plugin;

use Illuminate\Contracts\Foundation\Application;

class PluginLoader
{
    public function __construct(
        private readonly Application $app,
        private readonly PluginRegistry $registry,
    ) {
    }
    
    public function registerEnabledPlugins(): void
    {
        foreach ($this->registry->enabled() as $plugin) {
            if (! is_string($plugin->provider) || ! class_exists($plugin->provider)) {
                continue;
            }
            
            $this->app->register($plugin->provider);
        }
    }
}
