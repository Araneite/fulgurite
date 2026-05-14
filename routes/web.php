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

// 2FA
Route::get('/a2f', [TwoFactorController::class, 'show'])->name('two-factor.show');
Route::post('/a2f', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
Route::post('/a2f/method', [TwoFactorController::class, 'changeMethod'])->name('two-factor.method');
Route::post('/a2f/email-code', [TwoFactorController::class, 'sendEmailCode'])->name('two-factor.email-code');
Route::post('/a2f/passkey/options', [TwoFactorController::class, 'passkeyOptions'])->name('two-factor.passkey.options');
Route::post('/a2f/passkey/verify', [TwoFactorController::class, 'verifyPasskey'])->name('two-factor.passkey.verify');

Route::middleware('auth')->group(function () {
    Route::get('/security-keys', [SecurityKeyController::class, 'index'])->name('security-keys.index');

    /**
     * === Get Methods ===
     */
    /* --- Global --- */
    Route::get("/", [UserPageController::class, "index"])
        ->name("dashboard.home")
        ->defaults("dashboard_page", [
            "label"=> trans("pages/list.pages.dashboard.title"),
            "description"=> trans("pages/list.pages.dashboard.description"),
            "icon"=> "pi-objects-column",
            "section"=> trans("pages/list.sections.global"),
            "order"=> 10,
            "permissions"=> [],
            "in_nav"=> true,
        ]);
    
    /* --- Profile --- */
    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('dashboard.profile')
        ->defaults("dashboard_page", [
            "label"=> trans("pages/list.pages.profile.title"),
            "description"=> trans("pages/list.pages.profile.description"),
        ]);
    Route::get('/security-keys', [SecurityKeyController::class, 'index'])
        ->name('security-keys.index');
    
    Route::post('/security-keys/options', [SecurityKeyController::class, 'options'])->name('security-keys.options');
    Route::post('/security-keys', [SecurityKeyController::class, 'store'])->name('security-keys.store');
    Route::delete('/security-keys/{credential}', [SecurityKeyController::class, 'destroy'])->name('security-keys.destroy');
    
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::put('/profile/two-factor', [ProfileController::class, 'updateTwoFactor'])->name('profile.two-factor.update');
    
    Route::post('/profile/security-keys/options', [ProfileController::class, 'securityKeyOptions'])->name('profile.security-keys.options');
    Route::post('/profile/security-keys', [ProfileController::class, 'storeSecurityKey'])->name('profile.security-keys.store');
    Route::delete('/profile/security-keys/{credential}', [ProfileController::class, 'destroySecurityKey'])->name('profile.security-keys.destroy');
    
    Route::post('/profile/totp/setup', [ProfileController::class, 'startTotpSetup'])->name('profile.totp.setup');
    Route::post('/profile/totp/confirm', [ProfileController::class, 'confirmTotpSetup'])->name('profile.totp.confirm');
    Route::delete('/profile/totp', [ProfileController::class, 'disableTotp'])->name('profile.totp.disable');
});

Route::any('/plugins/{plugin}/{path?}', PluginProxyController::class)
    ->where('path', '.*')
    ->name('plugins.proxy');
