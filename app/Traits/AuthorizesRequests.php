<?php

namespace App\Traits;

use App\Models\User;
use App\Services\ActionLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait AuthorizesRequests
{
    public function logger(): ActionLogger {
        return app(ActionLogger::class);
    }

    /**
     * Check if connected user has permission
     * On success return authenticated user
     * 
     * @param Request $request
     * @param string $ability
     * @param string $actionTranslationKey
     * @param mixed|null $arguments
     * @return User
     */
    protected function authorizeUserAction(
        Request $request,
        string $ability,
        string $actionTranslationKey,
        mixed $arguments = null
    ): User {
        $user = $request->user();
        $model = "";
        if (!is_array($arguments)) {
            if (class_exists($arguments)) {
                $class = new $arguments();
                $model = get_class($class);
            } else {
                $model = get_class($arguments);
            }

            $model = strtolower(preg_replace('/([A-z]+)?\\\(Models)(\\\)?([A-z]+)?\\\/i', "", $model));
        }

        if (!$user->can($ability, $arguments)) {
            $message = method_exists($user, 'permissionDenialMessage')
                ? $user->permissionDenialMessage($actionTranslationKey, $arguments)
                : trans('global/errors.unauthorized', [
                    'action'=> strtolower(trans($actionTranslationKey)),
                    'model'=> $model
                ]);
            
            throw ValidationException::withMessages([
                'action'=> $message
            ]);
        }
        
        return $user;
    }
}
