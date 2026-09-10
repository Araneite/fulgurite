<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ForcedActions;
use App\Enums\Locale;
use App\Enums\StartPage;
use App\Enums\Timezone;
use App\Enums\View;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\User\UserQuickEditRequest;
use App\Http\Requests\User\UserLoginRequest;
use App\Http\Resources\Dashboard\InvitationResource;
use App\Http\Resources\Dashboard\UserDetailsResource;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use App\Traits\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

// TODO: remove comment below
// Regex extract phone ext and phone number from string
// /^(\+?\d{2,})(0\d{9})$|^(\+?\d+)([1-9]\d{8})$/gm
class UserPageController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display the users page in dashboard
     * 
     * @param Request $request
     * @return Response
     */
	public function index(Request $request): Response {
        // === Users ===
        $canViewSensitive = $request->user()->hasPermission('users:view-sensitive');
        $perPage = (int) $request->integer("per_page", 20);

        $sortField = $request->string("sort_field", "id")->toString();
        $sortDirection = $request->string("sort_direction", "asc")->toString();
        
        $search = trim($request->string('search')->toString());
        $view = trim($request->string("view")->toString());

        $allowedSortFields = [
            "id", "username", "email",
            "active", "updated_at"
        ];

        if (!in_array($sortField, $allowedSortFields, true)) $sortField = "id";
        if (!in_array($sortDirection, ["asc", "desc"], true)) $sortDirection = "asc";
        
        $query = User::query()
            ->select(["id", "username", "email", "active", "updated_at", "expire_at", "user_settings_id", "deleted_at"])
            ->when($canViewSensitive, fn ($query) => $query->addSelect('admin_notes'));
        
        if ($search !== '') {
            $query->where(function ($query) use ($search, $canViewSensitive) {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('created_at', 'like', "%{$search}%")
                    ->orWhere('updated_at', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('active', 'like', "%{$search}%");

                if ($canViewSensitive) {
                    $query->orWhere('admin_notes', 'like', "%{$search}%");
                }
            });
        }
        
        if (!in_array($view, View::values(), true)) {
            $view = 'active';
        }
        
        if ($view === 'trash') $query->onlyTrashed();
        if ($view === 'all') $query->withTrashed();
        
        // === Invitations ===
        $invitationsPerPage = (int) $request->integer("invitations_per_page", 20);
        $invitationsPage = (int) $request->integer("invitations_page", 1);
        $invitationsSearch = trim($request->string("invitations_search")->toString());
        $invitationsView = trim($request->string("invitations_view", "active")->toString());

        if (!in_array($invitationsView, View::values(), true)) {
            $invitationsView = 'active';
        }
        
        $statusTranslations = [
            'pending'=> trans("resources/invitations.status.pending"),
            'accepted'=> trans("resources/invitations.status.accepted"),
            'revoked'=> trans("resources/invitations.status.revoked"),
            'expired'=> trans("resources/invitations.status.expired"),
        ];
        
        $matchingStatuses = collect($statusTranslations)
            ->filter(fn($label, $status) => str_contains(
                mb_strtolower($label), 
                mb_strtolower($invitationsSearch)
            ))
            ->keys()
            ->values()
            ->all();
        
        $invitationsQuery = Invitation::query()
            ->select([
                'id', 'email', 'username',
                'status', 'expires_at', 'accepted_at',
                'revoked_at', 'created_at', 'invited_by', 
                'deleted_at'
            ])
            ->orderByDesc("created_at");
        
        if ($invitationsView === 'trash') $invitationsQuery->onlyTrashed();
        if ($invitationsView === 'all') $invitationsQuery->withTrashed();
        
        if ($invitationsSearch !== '') {
            $invitationsQuery->where(function ($query) use ($invitationsSearch, $matchingStatuses) {
                $query->where('email', 'like', "%{$invitationsSearch}%")
                    ->orWhere('username', 'like', "%{$invitationsSearch}%")
                    ->orWhere('status', 'like', "%{$invitationsSearch}%")
                    ->orWhere('id', 'like', "%{$invitationsSearch}%");
                
                if (!empty($matchingStatuses)) {
                    $query->orWhereIn('status', $matchingStatuses);
                }
            });
        }
        
        // === Render ===
        return Inertia::render("Dashboard/Users/Index", [
            "users"=> UserResource::collection(
                $query
                ->with(['contact', 'roles:id,name,level,project_id', 'settings'])
                ->orderBy($sortField, $sortDirection)
                ->paginate($perPage)
                ->withQueryString(),
            ),
            "invitations"=> InvitationResource::collection(
                $invitationsQuery
                ->with(['inviter'])
                ->paginate($invitationsPerPage, ['*'], 'invitations_page', $invitationsPage)
                ->withQueryString()
            ),
            "permissions"=> [
                "create"=> $request->user()->can('create', User::class),
                "view_sensitive"=> $canViewSensitive,
            ],
            "filters"=> [
                "view"=> $view,
                "per_page"=> $perPage,
                "sort_field"=> $sortField,
                "sort_direction"=> $sortDirection,
                "search"=> $search,
                
                "invitations_page"=> $invitationsPage,
                "invitations_per_page"=> $invitationsPerPage,
                "invitations_search"=> $invitationsSearch,
                "invitations_view" => $invitationsView,
            ],
            "fields"=> [
                "id"=> trans("resources/users.fields.id"),
                "username"=> trans("resources/users.fields.username"),
                "email"=> trans("resources/users.fields.email"),
                "active"=> trans("resources/users.fields.active"),
                "admin_notes"=> trans("resources/users.fields.admin_notes"),
                "updated_at"=> trans("resources/users.fields.updated_at")
            ],
            "tableName"=> trans("pages/users.table.name"),
            "__"=> [
                'actions'=> [...trans("actions")],
                'messages'=> [...trans("messages")],
                'resources'=> [
                    'users'=> [...trans("resources/users")],
                    'invitations'=> [...trans("resources/invitations")],
                ],
                'forms'=> [
                    'users' => [...trans("forms/users")],
                    'invitations'=> [...trans("forms/invitations")],
                ],
                'views'=> [...trans("global/views")],
                'errors'=> [...trans("global/errors")],
                'global'=> [
                    'views'=> [...trans("global/views")],
                    'errors'=> [...trans("global/errors")],
                    'forms'=> [...trans("global/forms")],
                    'confirm_identity'=> [...trans("global/confirmation-identity")],
                    'modals'=> [...trans("global/modals")],
                ],
                ...trans("pages/users")
            ],
            "availableForcedActions"=> ForcedActions::options(),
            'availableLocales'=> Locale::options(),
            'availableTimezones'=> Timezone::options(),
            'availableStartPages'=> StartPage::options(),
            'phoneCountries'=> fn () => collect(config('phone_countries'))
                ->map(fn (array $country) => [
                    'iso2'=> $country['iso2'],
                    'extension'=> $country['extension'],
                    'name'=> trans("global/countries.{$country['iso2']}"),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all()
        ]);
    }
    
    /**
     * Return the information required by the user details drawer.
     *
     * Sensitive relationships are loaded only when the authenticated user
     * is allowed to view sensitive information about the requested user.
     *
     * @param Request $request
     * @param User $user
     * @return UserDetailsResource
     */
    public function details(Request $request, User $user): UserDetailsResource
    {
        Gate::authorize('view', $user);

        $user->loadMissing('contact');

        if ($request->user()->can('viewSensitive', $user)) {
            $user->loadMissing('roles:id,name,level,project_id');
        }

        return UserDetailsResource::make($user);
    }
    
    public function show(Request $request, User $user): Response {
        
    }

    /**
     * Display the login view
     * 
     * @return RedirectResponse|Response
     */
    public function showLogin(): RedirectResponse|Response
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }
        
        return Inertia::render('Auth/Login', [
            'resetPasswordUrl' => route('reset-password'),
            'trans'=> __("forms/login")
        ]);
    }

    /**
     * Handle login request
     * 
     * @param UserLoginRequest $request
     * @return RedirectResponse
     */
    public function login(UserLoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();
        
        $methods = app(TwoFactorService::class)->availableMethods($user);
        
        if ($methods !== []) {
            Auth::logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put("two_factor.user_id", $user->id);
            $request->session()->put("two_factor.remember", $request->boolean("remember"));
            $request->session()->put("two_factor.method", $methods[0]['value']);
            
            return redirect()->route("two-factor.show");
        }
        
        $request->session()->regenerate();
        
        return redirect()->intended('/');
    }
    
    public function store(UserStoreRequest $request): RedirectResponse {
        
    }
    
    public function quickEdit(UserQuickEditRequest $request, User $user): RedirectResponse {
        $authUser = $this->authorizeUserAction($request, 'update', 'actions.edit', $user);
        
        DB::transaction(function () use ($request, $user) {
            $userData = $request->userData();
            $contactData = $request->contactData();
            
            if ($userData !== []) {
                $user->update($userData);
            }
            
            if ($contactData !== []) {
                $user->contact()->updateOrCreate(
                    ['user_id'=> $user->id],
                    $contactData
                );
            }
            
            $user->refresh()->load(['contact', 'roles', 'settings']);
        });
        
        return back()->with('success', trans('resources/users.messages.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        throw ValidationException::withMessages([
            'toast'=> json_encode([
                'title' => 'Toast de test',
                'description' => 'Je suis un test',
            ]),
        ]);
        return back()->with('success', trans('resources/users.messages.destroyed'));
    }

}
