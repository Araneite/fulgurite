<?php

use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\RoleController;
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
Route::get('/2fa', [TwoFactorController::class, 'show'])->name('two-factor.show');
Route::post('/2fa', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
Route::post('/a2f/method', [TwoFactorController::class, 'changeMethod'])->name('two-factor.method');
Route::post('/a2f/email-code', [TwoFactorController::class, 'sendEmailCode'])->name('two-factor.email-code');
Route::post('/a2f/passkey/options', [TwoFactorController::class, 'passkeyOptions'])->name('two-factor.passkey.options');
Route::post('/a2f/passkey/verify', [TwoFactorController::class, 'verifyPasskey'])->name('two-factor.passkey.verify');

Route::get('/2fa/passkey/options', [TwoFactorController::class, 'passkeyOptions'])
    ->name('two-factor.passkey.options');
Route::post('/2fa/passkey/verify', [TwoFactorController::class, 'verifyPasskey'])
    ->name('two-factor.passkey.verify');

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

    // --- Dashboard users ---
    Route::get("/users", [UserPageController::class, "index"])
        ->name("dashboard.users")
        ->defaults("dashboard_page", [
            "label"=> trans("pages/list.pages.users.title"),
            "description"=> trans("pages/list.pages.users.description"),
            "icon"=> "pi-users",
            "section"=> trans("pages/list.sections.admin"),
            "order"=> 10,
            "permissions"=> ["users:view"],
            "in_nav"=> true,
        ]);
    Route::get('/users/{user}/details', [UserPageController::class, 'details'])
        ->withTrashed()
        ->name('dashboard.users.details');
    Route::get('/users/{user}', [UserPageController::class, 'show'])
        ->withTrashed()
        ->name('dashboard.users.show');
    
    // Create a new user
    Route::post("/users", [UserPageController::class, "store"])
        ->name("dashboard.users.store");
    Route::patch("users/{user}/quick-edit", [UserPageController::class, "quickEdit"])
        ->name("dashboard.users.quick-edit");
    // Update data of user profile
    Route::patch('/users/{user}/data', [UserPageController::class, 'updateData'])
        ->name('dashboard.users.data.update');
    // Update security data of user profile
    Route::patch('/users/{user}/security', [UserPageController::class, 'updateSecurity'])
        ->name('dashboard.users.security.update');
    // Update administration related data of user profile
    Route::patch('/users/{user}/admin', [UserPageController::class, 'updateAdmin'])
        ->name('dashboard.users.admin.update');
    Route::delete('/users/{user}/delete', [UserPageController::class, 'destroy'])
        ->name('dashboard.users.delete');
    
    // --- Dashboard roles ---
    // Get roles for select menus
    Route::post('/roles/select-menu', [RoleController::class, 'selectMenu'])
        ->name('dashboard.roles.select-menu');
    
    Route::post('/security-keys/options', [SecurityKeyController::class, 'options'])->name('security-keys.options');
    Route::post('/security-keys', [SecurityKeyController::class, 'store'])->name('security-keys.store');
    Route::delete('/security-keys/{credential}', [SecurityKeyController::class, 'destroy'])->name('security-keys.destroy');
    // === 2FA Confirmation === 
    Route::post('/user/confirm-identity', [TwoFactorController::class, 'confirmIdentity'])
        ->name('user.confirm-identity');
    Route::post('/user/confirm-identity/method', [TwoFactorController::class, 'changeIdentityMethod'])
        ->name('user.confirm-identity.method');
    Route::post('/user/confirm-identity/email-code', [TwoFactorController::class, 'sendIdentityEmailCode'])
        ->middleware('throttle:6,1')
        ->name('user.confirm-identity.email-code');
    Route::get('/user/confirm-identity/passkey/options', [TwoFactorController::class, 'identityPasskeyOptions'])
        ->middleware('throttle:10,1')
        ->name('user.confirm-identity.passkey.options');
    Route::post('/user/confirm-identity/passkey/verify', [TwoFactorController::class, 'verifyIdentityPasskey'])
        ->middleware('throttle:10,1')
        ->name('user.confirm-identity.passkey.verify');
    
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
