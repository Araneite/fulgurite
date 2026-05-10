<?php

use Illuminate\Support\Facades\Route;
use Plugins\Sandbox\Http\Controllers\HealthController;

Route::get('/sandbox/health', [HealthController::class, 'show'])
    ->name('sandbox.health');
