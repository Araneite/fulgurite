<?php

use App\Http\Middleware\IsUserAccountActive;
use App\Http\Middleware\SetUserLocale;

return [
    'sandbox_url'=> env('PLUGINS_SANDBOX_URL', 'http://127.0.0.1:8081'),
    'sandbox_secret'=> env('PLUGINS_SANDBOX_SECRET'),
    'timeout'=> env('PLUGINS_SANDBOX_TIMEOUT', 10),
    
    'core_middleware_aliases'=> [
        "public"=> [],
        "auth"=> ['auth:sanctum', IsUserAccountActive::class],
        "setUserLocale"=> [SetUserLocale::class],
    ]
];
