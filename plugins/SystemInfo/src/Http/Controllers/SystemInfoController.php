<?php

namespace Plugins\SystemInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\Internal\BaseResource;

class SystemInfoController extends Controller
{
    public function status(): BaseResource
    {
        return BaseResource::make([
            'plugin' => 'system-info',
            'status' => 'enabled',
            'app' => [
                'name' => config('app.name'),
                'env' => app()->environment(),
                'debug' => (bool) config('app.debug'),
                'timezone' => config('app.timezone'),
                'locale' => app()->getLocale(),
            ],
            'runtime' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
            ],
            'checked_at' => now()->toIso8601String(),
        ])
            ->success()
            ->setCode(200)
            ->setMessage('Plugin System Info actif.');
    }
}
