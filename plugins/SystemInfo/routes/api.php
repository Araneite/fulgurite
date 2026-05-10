<?php

use Illuminate\Support\Facades\Route;
use Plugins\SystemInfo\Http\Controllers\SystemInfoController;

Route::prefix('plugins/system-info')
    ->name('plugins.system-info.')
    ->group(function () {
        Route::get('/status', [SystemInfoController::class, 'status'])
            ->name('status');
        
        Route::get('/private-status', [SystemInfoController::class, 'privateStatus'])
            ->name('private-status');
    });
