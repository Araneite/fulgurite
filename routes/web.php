<?php

use App\Http\Controllers\PluginProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::any('/plugins/{plugin}/{path?}', PluginProxyController::class)
    ->where('path', '.*')
    ->name('plugins.proxy');
