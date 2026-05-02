<?php

use App\Http\Middleware\IsUserAccountActive;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Internal\UserController as InternalUserController;
use App\Http\Controllers\API\Internal\ActionLogController as InternalActionLogController;

Route::prefix('internal')->group(function () {
    // Routes users actions
    Route::post('/login', [InternalUserController::class, 'login'])->name('users.login');
    Route::post('/forgot-password', [InternalUserController::class, 'forgotPassword'])->name('users.forgotPassword');
    Route::post('/reset-password', [InternalUserController::class, 'resetPassword'])->name('users.resetPassword');
    
    Route::middleware(['auth:sanctum', IsUserAccountActive::class])->group(function () {
        // === CRUD ===
        //--- Users --- 
        Route::get('/users', [InternalUserController::class, 'index'])->name('users.list');
        Route::get('/users/{user}', [InternalUserController::class, 'show'])->name('users.view');
        Route::post('/users', [InternalUserController::class, 'store'])->name('users.create');
        Route::patch('/users/{user}', [InternalUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [InternalUserController::class, 'destroy'])->name('users.destroy');
        Route::post("/users/{user}/restore", [InternalUserController::class, 'restore'])->name('users.restore');
        
        // === Administration ===
        // --- Users ---
        Route::delete("/users/{user}/force", [InternalUserController::class, 'forceDeleteUser'])->name('users.forceDelete');
        Route::patch("/users/{user}/status", [InternalUserController::class, 'changeStatus'])->name('users.updateStatus');
        
        // --- Logs --- 
        Route::get("/logs", [InternalActionLogController::class, 'index'])->name('logs.list');
        Route::get("/logs/{log}", [InternalActionLogController::class, 'show'])->name('logs.view');
        Route::post("/logs/archive", [InternalActionLogController::class, 'archive'])->name('logs.archive');
        
        //  === Personal ===
        Route::get("/me", [InternalUserController::class, 'getAuthenticatedUser'])->name('users.me.view');
        Route::patch("/me", [InternalUserController::class, 'patchAuthenticatedUser'])->name('users.me.update');
        Route::delete('/me', [InternalUserController::class, 'destroyAuthenticatedUser'])->name('users.me.destroy');
        
        // Sensitive actions
        // --- Users ---
        Route::post('/change-password/{user?}', [InternalUserController::class, 'changePassword'])->name('users.changePassword');
        
        // Logout
        Route::post('/logout', [InternalUserController::class, 'logout'])->name('users.logout');
    });
//    Route::get('/users', [InternalUserController::class, 'index']);
    
});
