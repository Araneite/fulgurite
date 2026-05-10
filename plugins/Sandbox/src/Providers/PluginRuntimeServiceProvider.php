<?php

namespace Plugins\Sandbox\Providers;

use Illuminate\Support\ServiceProvider;
use Plugins\Sandbox\Support\PluginAutoloader;
use Plugins\Sandbox\Plugin\PluginLoader;
use Plugins\Sandbox\Plugin\PluginRegistry;

class PluginRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        PluginAutoloader::register(dirname(__DIR__, 3));
        
        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(PluginLoader::class);
        
        $this->app->make(PluginLoader::class)->registerEnabledPlugins();
    }
}
