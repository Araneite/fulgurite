<?php

namespace Plugins\SystemInfo;

use Illuminate\Support\ServiceProvider;

class SystemInfoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
