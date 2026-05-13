<?php

use App\Http\Middleware\IsUserAccountActive;
use App\Http\Middleware\SetUserLocale;
use App\Services\ActionLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        
        $middleware->preventRequestForgery(except: [
            'profile/security-keys/options',
            'profile/security-keys',
            'a2f/passkey/options',
            'a2f/passkey/verify',
        ]);
        
        $middleware->append(SetUserLocale::class);

        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            app(ActionLogger::class)->failed(
                action: null,
                description: "logs.api.authentication.required",
            );
            if ($request->is("api/*")) {
                return response()->json([
                    'data' => [],
                    'success' => false,
                    'code' => 403,
                    'message' => trans('internal/errors.unauthenticated.message'),
                    'errors' => [
                        'authorization' => trans('internal/errors.unauthenticated.description'),
                    ],
                ], 403);
            }
            
            return redirect()->route("login");
        });
    })->create();
