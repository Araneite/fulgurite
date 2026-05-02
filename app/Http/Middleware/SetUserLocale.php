<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedLocales = config('app.available_locales', ['en_US', 'fr_FR']);
        $defaultLocale = config('app.locale');
        
        $locale = $request->get('locale') 
            ?? auth()->user()?->settings->preferred_locale
            ?? session('locale')
            ?? $defaultLocale;
        
        if (!is_string($locale) || !in_array($locale, $allowedLocales, true)) {
            $locale = $defaultLocale;
        }
        
        App::setLocale($locale);
        
        return $next($request);
    }
}
