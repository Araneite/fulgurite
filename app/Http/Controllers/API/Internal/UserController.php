<?php

namespace App\Http\Controllers\API\Internal;

use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ModelNotFoundException;
use App\Exceptions\ModelNotTrashedException;
use App\Exceptions\RequestNotConfirmedException;
use App\Exceptions\UserResetPasswordException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserForgotPasswordRequest;
use App\Http\Requests\UserPasswordChangeRequest;
use App\Http\Requests\UserPasswordResetRequest;
use App\Http\Requests\UserStoreRequest;
use App\Http\Resources\API\Internal\BaseResource;
use App\Http\Resources\API\Internal\UserCollection;
use App\Http\Resources\API\Internal\UserResource;
use App\Mail\ForgotPasswordMail;
use App\Models\Contact;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\ActionLogger;
use App\Traits\AuthorizesApiRequests;
use App\Traits\HandlesIncludes;
use App\Traits\HasPermissions;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\Support\Str;
use App\Services\Users\UserRoleService;


class UserController extends Controller
{
    use HandlesIncludes, HasPermissions, softDeletes, AuthorizesApiRequests;
    
    /**
     * Display a listing of users.
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function index(Request $request, ActionLogger $logger)
    {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "view", "internal.actions.users.list", $user);
        
        $query = User::query();
        
        $this->applyIncludes($request, $query, ["role", "permission", "settings", "contact"]);
        
        $users = $query->apiList($request);
        
        $logger->info(
            action: "users.view",
            description: "logs.users.listed",
        );
        
        return (new UserCollection($users))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.index.detail', [
                "model"=> trans('internal/models.plural.user')
            ]));
    }
    
    /**
     * Store a newly created user in storage.
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function store(UserStoreRequest $request, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "create", "internal.actions.users.create", $user);
        
        
        $userData = $request->userData();
        $userContactData = $request->userContactData();
        $userSettingsData = $request->userSettingsData();
        
        if(!empty($userData)) {
            $model = User::create([
                "username"=> $userData["username"],
                "password"=> $userData["password"],
                "email"=> $userData["email"],
                "admin_notes"=> $userData["admin_notes"] ? $userData["admin_notes"] : null,
                "password_set_at"=> now(),
                "expire_at"=> $userData["expire_at"] ? $userData["expire_at"] : null,
                "active"=> $userData["active"] ? $userData["active"] : 1,
                "contact_id"=> null,
                "created_by"=> Auth::id(),
                "updated_by"=> Auth::id(),
                "created_at"=> now(),
                "updated_at"=> now(),
            ]);
        }
        if ($model && !empty($userContactData)) {
            $contact = Contact::create([
                "first_name"=> $userContactData["first_name"] ?? null,
                "last_name"=> $userContactData["last_name"] ?? null,
                "phone"=> $userContactData["phone"] ?? null,
                "phone_extension"=> $userContactData["phone_extension"] ?? 1,
                "job_title"=> $userContactData["job_title"] ?? null,
                "user_id"=> $model->id,
            ]);
            
            $model->update(["contact_id" => $contact->id]);
        }
        if ($model && $contact && !empty($userSettingsData)) {
            $userSettings = UserSetting::create([
                "preferred_locale"=> $userSettingsData["preferred_locale"] ?? app()->getLocale(),
                "preferred_timezone"=> $userSettingsData["preferred_timezone"] ?? app()->getTimezone(),
                "preferred_start_page"=> $userSettingsData["preferred_start_page"] ?? "dashboard",
                "force_actions_json"=> $userSettingsData["force_actions"] ?? null,
            ]);
            
            $model->update([
                "user_settings_id"=> $userSettings->id,
            ]);
        }
        
        $model->roles()->syncWithoutDetaching([$userData["role_id"]]);
        
        $model->refresh()->load([
            "contact",
            "roles",
        ]);
        
        $token = $model->createToken('internal')->plainTextToken;
        
        $logger->info(
            action: "user.create.",
            description: "logs.users.created",
            metadata: [
                "user_id"=> $user->id,
                "contact_id"=> $contact->id,
                "settings_id"=> $userSettings->id,
            ],
            target: $model,
        );
        
        return (new UserResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal/success.store.message"))
            ->setDetails([
                "token"=> $token,
                "create"=> trans("internal/success.store.details", [
                    "model"=> trans("internal/models.singular.user"),
                ]),
            ]);
    }
    
    /**
     * Display the specified user.
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function show(int|string $userReq, Request $request, ActionLogger $logger)
    {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "view", "internal.actions.users.show", $user);
        
        $query = User::query();
        
        $this->applyIncludes($request, $query, ["role", "permission", "settings", 'contact']);
        
        $model = is_numeric($userReq)
            ? $query->where('id', $userReq)->first()
            : $query->where('username', $userReq)->first();
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans("internal/models.user"),
                        "requested"=> $userReq,
                    ])
                ]
            );
        }
        
        $logger->info(
            action: "user.show",
            description: "logs.users.viewed",
            metadata: [
                "user_id"=> $model->id
            ],
            target: $model,
        );
        
        return (new UserResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal/success.show.message"))
            ->setDetails([
                "show"=> trans("internal/success.show.detail",[
                    "model"=> trans("internal/models.singular.user"),
                    "requested"=> $userReq,
                ]),
            ]);
    }
    
    /**
     * Update the specified user in storage.
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function update(UserUpdateRequest $request, int|string $userReq, ActionLogger $logger)
    {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "update", "internal.actions.users.update.message", $user);
        
        $model = $request->getTargetUser();
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"), 
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans("internal/models.singular.user"),
                        "requested"=> $userReq,
                    ])
                ]
            );
        }
        
        $userData = $request->userData();
        $userContactData = $request->userContactData();
        $userSettingData = $request->userSettingData();
        
        if (!empty($userData)) {
            $model->update($userData);
        }
        if (!empty($userContactData)) {
            $model->contact->update($userContactData);
        }
        if (!empty($userSettingData)) {
            $model->settings->update($userSettingData);
        }
        
        $model->refresh()->load(["contact", "role", "permission", "settings"]);
        
        $logger->info(
            action: "user.update",
            description: "logs.users.updated",
            metadata: [
                "user_id"=> $model->id,
                "data_changed"=> $model->getChanges(),
            ]
        );
        
        return (new UserResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal/success.update.message"))
            ->setDetails([
                "update"=> trans("internal/success.update.detail", [
                    "model"=> trans("internal/models.singular.user"),
                    "requested"=> $userReq,
                ]),
            ]);
    }
    
    /**
     * Remove the specified user from storage.
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function destroy(int|string $userReq, UserRoleService $userRoleService, ActionLogger $logger)
    {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "delete", "internal.actions.users.destroy.message", $user);
        
        $query = User::query();
        
        $model = is_numeric($userReq)
            ? $query->where('id', $userReq)->first()
            : $query->where('username', $userReq)->first();
        
        if (!$model) {
            throw new ModelNotTrashedException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans("internal/models.user"),
                        "requested"=> $userReq,
                    ])
                ]
            );
        }
        
        $model->contact->delete();
        $roles = $model->roles->all();
        foreach ($roles as $role) {
            $userRoleService->removeRole($model, $role);            
        }
        $model->settings->delete();
        $model->delete();
        
        $logger->warning(
            action: "user.destroy",
            description: "logs.users.deleted",
            metadata: [
                "user_id"=> $model->id
            ]
        );
        
        return (new UserResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal:success.destroy.message"))
            ->setDetails([
                "delete"=> trans("internal/success.destroy.detail", [
                    "model"=> trans("internal/models.singular.user"),
                    "requested"=> $userReq
                ]),
            ]);
    }
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function restore(int|string $userReq, UserRoleService $userRoleService, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();        
        $this->authorizeUserAction($user, "restore", "internal.actions.users.restore.message", $user);
        
        $query = User::query();
        $model = is_numeric($userReq)
            ? $query->withTrashed()
                ->where('id', $userReq)
                ->first()
            : $query->withTrashed()
                ->where('username', $userReq)
                ->first();
        
        if (!$model->trashed()) {
            throw new ModelNotTrashedException(
                trans("internal/errors.not_trashed.message"), 
                [
                    "not_found"=> trans("internal/errors.not_trashed.detail", [
                        "model"=> trans("internal/models.singular.user"),
                        "requested"=> $userReq
                    ])
                ]
            );
        }
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans("internal/models.singular.user"),
                        "requested"=> $userReq
                    ])
                ]
            );
        }
        
        $roles = $model->roles->all();
        foreach ($roles as $role) {
            $userRoleService->restoreRole($model, $role);        
        }
        
        $model->contact->restore();
        $model->settings->restore();
        $model->restore();
        
        $logger->info(
            action: "user.restore",
            description: "logs.users.restored",
            metadata: [
                "user_id"=> $model->id
            ]
        );
        
        return (new UserResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal/success.restore.message"))
            ->setDetails([
                "restore"=> trans("internal/success.restore.detail", [
                    "model"=> trans("internal/models.singular.user"),
                    "requested"=>$userReq
                ]),
            ]);
    }
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function changeStatus(int|string $userReq, UserRoleService $userRoleService, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "update", "internal.actions.users.change.status.message", $user);
        
        $query = User::query();
        
        $model = is_numeric($userReq)
            ? $query->where('id', $userReq)->first()
            : $query->where('username', $userReq)->first();
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans("internal/models.singular.user"),
                        "requested"=> $userReq
                    ])
                ]
            );
        }
        
        $model->update([
            "active"=> !$model->active
        ]);
        
        $logger->info(
            action: "user.status.update",
            description: "logs.users.status_updated",
            metadata: [
                "user_id"=> $model->id,
                "changed_to"=> $model->status
            ]
        );
        
        return (new UserResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal/success.change.message"))
            ->setDetails([
                "change"=> trans("internal/success.change.detail", [
                    "fields"=> join(", ", $model->getChanges()),
                    "model"=> trans("internal/models.singular.user"),
                    "requested"=>$userReq
                ]),
            ]);
        
    }
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function forceDeleteUser(Request $request, int|string $userReq, UserRoleService $userRoleService, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "forceDelete", "internal.actions.users.force_delete.message", $user);
        
        $query = User::query();
        
        $model = is_numeric($userReq)
            ? $query->withTrashed()
                ->where('id', $userReq)
                ->first()
            : $query->withTrashed()
                ->where('username', $userReq)
                ->first();
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans("internal/models.singular.user"),
                        "requested"=> $userReq
                    ])
                ]
            );
        }
        
        $confirmation = (bool) $request->confirmation;
        
        if (!$confirmation) {
            throw new RequestNotConfirmedException(
                trans("internal/errors.confirmation.message"),
                [
                    "confirmation_failed"=> trans("internal/errors.confirmation.detail")
                ]
            );
        }
        
        $roles = $model->roles->all();
        foreach ($roles as $role) {
            $userRoleService->hardRemoveRole($model, $role);
        }
        $model->contact->forceDelete();
        $model->settings->forceDelete();
        $model->forceDelete();
        
        $logger->warning(
            action: "user.delete.force",
            description: "logs.users.force_deleted",
            metadata: [
                "user_id"=> $model->id,
            ]
        );
        
        return (new UserResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal/success.force_delete.message"))
            ->setDetails([
                "force_delete"=> trans("internal/success.force_delete.detail", [
                    "model"=> trans("internal/models.singular.user"),
                    "requested"=> $userReq
                ]),
            ]);
    }
    
    
    //** Users actions */
    
    /**
     * @throws AuthenticationException
     */
    public function login(Request $request, ActionLogger $logger) {
        $credentials = $request->only('email', 'password');
        $credentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];
        
        $attempt = LoginAttempt::create([
            "scope"=> "Internal API",
            "ip_address" => $request->ip(),
            "user_agent" => $request->userAgent(),
            "success"=> Auth::attempt($credentials),
            "username"=> $credentials['email'],
            "created_at"=> now(),
        ]);
        
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            $token = $user->createToken('internal')->plainTextToken;
            
            $logger->info(
                action: "user.login",
                description: "logs.users.login.success",
                metadata: [
                    "user_id"=> $user->id
                ]
            );
            
            return (new UserResource($user))
                ->success()
                ->setCode(200)
                ->setMessage(trans('internal/success.login.message'))
                ->setDetails([
                    "token"=> $token,
                    "login_success"=> trans('internal/success.login.detail', [
                        "user"=> $user->username
                    ]),
                ]);
        }
        
        $logger->failed(
            action: "user.login",
            description: "logs.users.login.failed",
            metadata: [
                "login"=> $credentials["email"],
            ]
        );
        
        throw new AuthenticationException(
            trans('internal/errors.login.message'),
            [
                "authentication"=> trans('internal/errors.login.detail')
            ]);
    }
    
    /**
     * @throws AuthenticationException
     */
    public function logout(Request $request, ActionLogger $logger) {
        $user = Auth::user();
            
        if (!$user) throw new AuthenticationException(
            trans('internal/errors.unauthenticated.message'), 
            [
                "authorization"=> trans('internal/errors.unauthenticated.detail')
            ]);
        
        $user->currentAccessToken()->delete();
        
        $logger->info(
            action: "user.logout",
            description: "logs.users.logout",
            metadata: [
                "user_id"=> $user->id
            ]
        );
        
        return (new BaseResource([]))
            ->setCode(200)
            ->setMessage(trans('internal/success.logout.message'))
            ->setDetails(["logout"=>trans('internal/success.logout.detail', ["user"=> $user->username])]);
    }
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function changePassword(UserPasswordChangeRequest $request, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        
        $model = $request->getTargetUser();
        if (!$model) {
            $model = $user;
        }
        
        if ($model->id !== $user->id) $this->authorizeUserAction($user, "update", "internal.actions.users.update.message", $user);
        
        $data = $request->userResetPasswordData();
        
        $model->update($data);
        $model->tokens()->delete();
        
        $logger->warning(
            action: "user.password.change",
            description: "logs.users.password_updated",
            metadata: [
                "user_id"=> $model->id,
            ]
        );
        
        return (new UserResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal/success.reset_password.message"))
            ->setDetails([
                "reset_password"=> trans("internal/success.reset_password.detail", [
                    "user" => $model->username
                ]),
            ]);
    }
    
    public function forgotPassword(UserForgotPasswordRequest $request, ActionLogger $logger) {
        $email = $request->validated("email");
        
        $query = User::query();
        $user = $query->where("email", $email)->first();
        
        $logger->warning(
            action: "user.password.forgot",
            description: "logs.users.forgot_password",
            metadata: [
                "user_id"=> $user->id
            ]
        );
        
        if (!$user) {
            return BaseResource::success()
                ->setCode(200)
                ->setMessage(trans("internal/infos.forgot_password.message"))
                ->setDetails([
                    "forgot_password"=> trans("internal/info.forgot_password.detail", ["email"=>$email]),
                ]);
        }
        
        $token = Password::broker()->createToken($user);
        
        $resetUrl = rtrim(config('app.frontend_url'), '/')
            . '/reset-password?'
            . http_build_query([
                'token' => $token,
                'email' => $user->email,
            ]);
        $locale = $user?->settings?->preferred_locale ?? config('app.locale');
        
        Mail::to($user->email)->queue(
            new ForgotPasswordMail(
                resetUrl: $resetUrl,
                username: $user->username,
                locale: $locale,
            )
        );
        
        return BaseResource::success()
            ->setCode(200)
            ->setMessage(trans("internal/infos.forgot_password.message"))
            ->setDetails([
                "forgot_password"=> trans("internal/infos.forgot_password.detail", ["email"=>$email]),
            ]);
    }
    
    public function resetPassword(UserPasswordResetRequest $request, ActionLogger $logger) {
        $user = User::query()->where("email", $request->validated("email"))->first();
        $status = Password::broker()->reset(
            $request->resetPasswordData(),
            function (User $user,string $password) use ($logger) {
                $user->forceFill([
                    'password' => $password,
                    'password_set_at'=> now(),
                    'remember_token'=> Str::random(60)
                ])->save();
                
                $user->tokens()->delete();
                
                event(new PasswordReset($user));
            }
        );
        
        if ($status == Password::PASSWORD_RESET) {
            $logger->failed(
                action: "user.password.reset",
                description: "logs.users.reset_password.failed",
                metadata: [
                    "user_id"=> $user->id,
                ]
            );
            
            throw new UserResetPasswordException(
                trans("internal/errors.reset_password.message"),
                [
                    "reset_password"=> trans("internal/errors.reset_password.description")
                ]
            );
        }
        
        $logger->warning(
            action: "user.password.reset",
            description: "logs.users.password_reset.success",
            metadata: [
                "user_id"=> $user->id,
            ]
        );
        
        return (new BaseResource([]))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.reset_password.message'))
            ->setDetails([
                'reset_password' => trans('internal/success.reset_password.detail', [
                    "user" => $user->username
                ]),
            ]);
    }
    
    /**
     * @throws AuthenticationException
     */
    public function getAuthenticatedUser(Request $request, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        
        $query = User::query();
        
        
        $this->applyIncludes($request, $query, ["role", "settings", "contact"]);
        
        $user = $query->where('id', $user->id)->first();
        
        $logger->info(
            action: "user.viewed",
            description: "logs.users.profile_viewed",
            metadata: [
                "user_id"=> $user->id
            ]
        );
        
        return (new UserResource($user))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.show.message'))
            ->setDetails([
                "show"=> trans("internal/success.show.detail", [
                    "model"=> trans("internal/models.singular.user"),
                    "requested"=> $user->username,
                ]),
            ]);
    }
    
    /**
     * @throws AuthenticationException
     */
    public function patchAuthenticatedUser(UserUpdateRequest $request, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        
        $userData = $request->userData();
        $userContactData = $request->userContactData();
        $userSettingData = $request->userSettingData();
        
        if (!empty($userData)) $user->update($userData);
        if (!empty($userContactData)) $user->contact->update($userContactData);
        if (!empty($userSettingData)) $user->settings->update($userSettingData);
        
        $user->refresh()->load(["role", "settings", "contact"]);
        
        $logger->info(
            action: "user.updated",
            description: "logs.users.profile_updated",
            metadata: [
                "user_id"=> $user->id
            ]
        );
        
        return (new UserResource($user))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.update.message'))
            ->setDetails([
                "update"=> trans("internal/success.update.detail", [
                    "model"=> trans("internal/models.singular.user"),
                    'requested'=>$user->username
                ]),
            ]);
    }
    
    /**
     * @throws AuthenticationException
     */
    public function destroyAuthenticatedUser(UserRoleService $userRoleService, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        
        $roles = $user->roles->all();
        foreach ($roles as $role) {
            $userRoleService->removeRole($user, $role);
        }
        
        $user->settings->delete();
        $user->contact->delete();
        $user->delete();
        $user->tokens()->delete();
        
        $logger->info(
            action: "user.destroyed",
            description: "logs.users.profile_deleted",
            metadata: [
                "user_id"=> $user->id
            ]
        );
        
        return (new UserResource($user))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.destroy.message'))
            ->setDetails([
                "delete"=> trans("internal/success.destroy.message", [
                    "model"=> trans("internal/models.singular.user"),
                    "requested"=> $user->username,
                ]),
            ]);
    }
    
}
