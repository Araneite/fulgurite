<?php

namespace Plugins\Sandbox\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCoreProxy
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('plugins.shared_secret');
        $received = (string) $request->header('X-Fulgurite-Sandbox-Secret');
        
        if ($expected === '' || ! hash_equals($expected, $received)) {
            abort(403);
        }
        
        return $next($request);
    }
}
