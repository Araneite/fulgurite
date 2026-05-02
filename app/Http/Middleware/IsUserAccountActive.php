<?php

namespace App\Http\Middleware;

use App\Exceptions\DisabledAccountException;
use App\Http\Resources\API\Internal\BaseResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsUserAccountActive
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     * @throws DisabledAccountException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user->active || $user->suspended_until >= now()) {
            $errors = ["account_disabled" => trans('internal.errors.users.account_disabled.description')];
            if ($user->suspension_reason) $errors["reason"] = $user->suspension_reason;
            throw new DisabledAccountException(trans('internal.errors.users.account_disabled.message'), $errors);
        }

        return $next($request);
    }
}
