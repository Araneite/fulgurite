<?php

use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\UserPageController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\PluginProxyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SecurityKeyController;

Route::get('/login', [UserPageController::class, 'showLogin'])->name('login');
Route::post('/login', [UserPageController::class, 'login']);
Route::get('reset-password', [UserPageController::class, 'showResetPassword'])->name('reset-password');

Route::get('/', function () {
    return view('welcome');
});

Route::get("/", [UserPageController::class, "index"])
    ->name("dashboard.home");

Route::any('/plugins/{plugin}/{path?}', PluginProxyController::class)
    ->where('path', '.*')
    ->name('plugins.proxy');
