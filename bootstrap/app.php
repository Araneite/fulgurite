<?php

use App\Http\Middleware\IsUserAccountActive;
use App\Http\Middleware\SetUserLocale;
use App\Services\ActionLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
            return response()->json([
                'data' => [],
                'success' => false,
                'code' => 403,
                'message' => trans('internal.errors.unauthenticated.message'),
                'errors' => [
                    'authorization' => trans('internal.errors.unauthenticated.description'),
                ],
            ], 403);
        });
    })->create();
