<?php

namespace App\Http\Middleware;

use App\Http\Resources\Dashboard\AuthUserResource;
use App\Services\Dashboard\DashboardPageRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'inertia';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $pageMeta = $request->route()?->defaults["dashboard_page"] ?? [];
        $user = $request->user();
        
        return [
            ...parent::share($request),
            'auth'=> [
                'user'=> fn () => $user
                    ? AuthUserResource::make($user)->resolve($request)
                    : null,
            ],
            "dashboard"=> [
                "pages"=> fn ()=> app(DashboardPageRegistry::class)->forUser($request->user())
            ],
            "page"=> [
                "title"=> $pageMeta["label"] ?? "Dashboard",
                "description"=> $pageMeta["description"] ?? "",
            ],
            "csrf_token"=> csrf_token(),
            'flash'=> [
                'success'=> fn () => $request->session()->get('success'),
                'error'=> fn () => $request->session()->get('error'),
                'info'=> fn () => $request->session()->get('info'),
                'warning'=> fn () => $request->session()->get('warning'),
            ],
            'app'=> [
                'config'=> [
                    'retention_days'=> fn () => config("app.retention_days"),
                ]
            ],
            "trans"=> [
                "layout"=> trans("dashboard/layouts/sidebar")
            ],
            "locale"=> app()->getLocale() ?? config("app.locale"),
        ];
    }
}
