<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        foreach ($this->enabledPlugin() as $plugin) {
            if (!class_exists($plugin->provider)) {
                continue;
            }
            
            $this->app->register($plugin->provider);
        }
    }
    
    private function enabledPlugin(): Collection
    {
        try {
            if (!Schema::hasTable('fg_plugins')) return collect();
            
            return Db::table('fg_plugins')->where('enabled', true)->get(['slug', 'provider']);
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
