<?php

use Illuminate\Support\Facades\Route;
use Plugins\SystemInfo\Http\Controllers\SystemInfoController;

Route::prefix('api/internal/plugins/system-info')
    ->name('plugins.system-info.')
    ->middleware(['api'])
    ->group(function () {
        Route::get('/status', [SystemInfoController::class, 'status'])
            ->name('status');
    });
