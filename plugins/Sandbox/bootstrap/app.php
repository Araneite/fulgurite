<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Plugins\Sandbox\Http\Middleware\VerifyCoreProxy;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(VerifyCoreProxy::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
