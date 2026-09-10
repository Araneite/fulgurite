<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Locale;
use App\Enums\Method2FA;
use App\Enums\StartPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\Services\Auth\TotpService;
use Illuminate\Validation\ValidationException;


class ProfileController extends Controller
{
    protected array $availableStartPages;
    protected array $availableLocales;
    protected array $available2FAMethods;
    
    public function __construct(Request $request) {
        $this->availableStartPages = StartPage::options();
        $this->availableLocales = Locale::options();
        $this->available2FAMethods = Method2FA::options();
    }
    
    public function show(Request $request): Response {
        $user = $request->user()->load(["contact", "settings", "role"]);
        
        
        return Inertia::render("Dashboard/Profile/Show", [
            "profile"=> [
                "username"=> $user->username,
                "email"=> $user->email,
                "active"=> $user->active,
                "last_login"=> $user->last_login?->format("d/m/Y H:i:s"),
                "role"=> $user->role?->name ?? trans("pages/profile.default_role"),
                "first_name"=> $user->contact?->first_name,
                "last_name"=> $user->contact?->last_name,
                "phone"=> $user->contact?->phone,
                "phone_extension"=> $user->contact?->phone_extension,
                "job_title"=> $user->contact?->job_title,
                "preferred_locale"=> $user->contact?->preferre_locale ?? app()->getLocale(),
                "preferred_timezone"=> $user->settings?->preferred_timezone ?? config('app.timezone'),
                "preferred_start_page"=> $user->settings?->preferred_start_page ?? "dashboard",
                "primary_second_factor"=> $user->settings?->primary_second_factor,
                "totp_enabled"=> (bool) $user->settings?->totp_enabled
            ],
            "methodOptions"=> $this->available2FAMethods,
            "localeOptions"=> $this->availableLocales,
            "startPageOptions"=> $this->availableStartPages
        ]);
    }
    
    public function update(Request $request): RedirectResponse {
        $user = Auth::user();
        
        $validated = $request->validate([
            "first_name"=> ["nullable", "string", "max:50"],
            "last_name"=> ["nullable", "string", "max:50"],
            "phone"=> ["nullable", "integer", "max_digits:11"],
            "phone_extension"=> ["nullable", "integer", "max_digits:3"],
            "job_title"=> ["nullable", "string", "max:50"],
            "preferred_locale"=> ["nullable", "string", Rule::in(Locale::values())],
            "preferred_timezone"=> ["nullable", "string", "max:80"],
            "preferred_start_page"=> ["nullable", "string", Rule::in(StartPage::values())]
        ]);
        
        $user->contact()->updateOrCreate(
            ["user_id"=> $user->id],
            collect($validated)->only([
                "first_name", "last_name", "phone", 
                "phone_extension", "job_title",
            ])->toArray()
        );
        
        $user->settings?->update(collect($validated)->only([
            "preferred_locale", "preferred_timezone", "preferred_start_page"
        ])->toArray());
        
        return back()->with("success", trans("pages/profile.success.updated"));
    }
    
    public function updatePassword(Request $request): RedirectResponse {
        $validated = $request->validated([
            "current_password"=> ["required", "current_password"],
            "password"=> ["required", "string", "min:8", "confirmed"]
        ]);
        
        $request->user()->update([
            "password"=> Hash::make($validated["password"]),
            "password_set_at"=> now()
        ]);
        
        return back()->with("success", trans("pages/profile.success.password_updated"));
    }
    
    public function updateTwoFactor(Request $request): Redirectresponse {
        $user = $request->user()->load("settings");
        
        $validated = $request->validate([
            "second_factor_methods"=> ["array"],
            "second_factor_methods.*"=> ["string", Rule::in(Method2FA::values())],
            "primary_second_factor"=> ["nullable", "string", Rule::in(Method2FA::values())],
        ]);
        
        $methods = collect($validated["second_factor_methods"] ?? [])
            ->unique()
            ->values()
            ->all();
        
        $primary = in_array($validated["primary_second_factor"] ?? null, $methods, true)
            ? $validated["primary_second_factor"]
            : ($methods[0] ?? "0");
        
        $user->settings?->update([
            "second_factor_methods"=> $methods,
            "primary_second_factor"=> $primary,
            "totp_enabled" => in_array("one_time_code", $methods, true)
        ]);
        
        return back()->with("success", trans("pages/profile.success.two_factor_updated"));
    }
    
    public function startTotpSetup(Request $request, TotpService $totpService): JsonResponse {
        $secret = $totpService->generateSecret();
        $uri = $totpService->provisioningUri($request->user(), $secret);
        
        $request->session()->put("totp_setup.secret", $secret);
        
        return response()->json([
            "success"=> true,
            "secret"=> $secret,
            "qr_code_svg"=> $totpService->qrCodeSvg($uri),
        ]);
    }
    
    public function confirmTotpSetup(Request $request, TotpService $totpService): RedirectResponse {
        $validated = $request->validate([
            "code"=> ["required", "string", "size:6"]
        ]);
        
        $secret = $request->session()->get("totp_setup.secret");
        
        if (!$secret || !$totpService->verify($secret, $validated["code"])) {
            throw ValidationException::withMessages([
                "code"=> trans("forms/confirmation-identity.errors.invalid_code")
            ]);
        }
        
        $settings = $request->user()->settings;
        
        $methods = collect($settings->second_factor_methods ?? [])
            ->push("one_time_code")
            ->unique()
            ->values()
            ->all();
        
        $settings->update([
            "totp_secret"=> $secret,
            "totp_enabled"=> true,
            "second_factor_methods"=> $methods,
            "primary_second_factor"=> in_array($settings->primary_second_factor, $methods, true)
                ? $settings->primary_second_factor
                : "one_time_code",
        ]);
        
        $request->session()->forget("totp_setup");
        
        return back()->with("success", trans("pages/profile.success.totp_enabled"));
    }
    
    public function disableTotp(Request $request): RedirectResponse {
        $settings = $request->user()->settings;
        
        $methods = collect($settings->second_factor_methods ?? [])
            ->reject(fn ($method)=> $method === "one_time_code")
            ->values()
            ->all();
        
        $settings->update([
            "totp_secret"=> null,
            "totp_enabled"=> false,
            "second_factor_methods"=> $methods,
            "primary_second_factor"=> $settings->primary_second_factor === "one_time_code"
                ? ($methods[0] ?? "0")
                : $settings->primary_second_factor
        ]);
        
        return back()->with("success", trans("pages/profile.success.totp_disabled"));
    }
}
