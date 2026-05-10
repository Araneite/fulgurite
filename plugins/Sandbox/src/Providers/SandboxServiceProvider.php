<?php

namespace Plugins\Sandbox\Providers;

use Illuminate\Support\ServiceProvider;

class SandboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/plugins.php', 'plugins');
    }
}
