<?php

namespace App\Traits;

use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Services\ActionLogger;
use Illuminate\Contracts\Auth\Authenticatable;

trait AuthorizesApiRequests
{
    public function logger(): ActionLogger {
        return app(ActionLogger::class);
    }
    
    /**
     * @throws AuthenticationException
     */
    protected function requireAuthenticatedUser(): Authenticatable {
        $user = auth()->user();
        
        if (!$user) {
            $this->logger()->failed(
                action: "api.authentication.require",
                description: trans("logs.api.authentication.required"),
                metadata: [
                    "route"=> request()->path()
                ]
            );
            throw new AuthenticationException(trans('internal.errors.unauthenticated.message'), 
                [
                    "authentication"=> trans('internal.errors.unauthenticated.description')
                ]
            );
        }
        
        return $user;
    }
    
    /**
     * @throws AuthorizationException
     */
    protected function authorizeUserAction(
        Authenticatable $user,
        string $ability,
        string $actionTranslationKey,
        mixed $arguments = null
    ):void {
        if (!$user->can($ability, $arguments)) {
            $this->logger()->failed(
                action: "api.check_permission",
                description: trans("logs.api.permissions.failed")
            );
            throw new AuthorizationException(trans('internal.errors.unauthorized.message'),
             [
                 "authorization"=> trans('internal.errors.unauthorized.description',[
                     "end_sentence"=> trans($actionTranslationKey),
                 ])
             ]);
        }
    }
}
