<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService
    ) {}
    
    public function show(Request $request): RedirectResponse | Response {
        $user = $this->pendingUser($request);
        
        if (!$user) {
            return redirect()->route("login");
        }
        
        $methods = $this->twoFactorService->availableMethods($user);
        $availableValues = collect($methods)->pluck('value');
        
        $preferredMethod = $user->settings?->primary_second_factor;
        
        $selectedMethod = $request->session()->get('two_factor.method');
        
        if (!$selectedMethod || !$availableValues->contains($selectedMethod)) {
            $selectedMethod = $availableValues->contains($preferredMethod)
                ? $preferredMethod
                : $availableValues->first();
            
            $request->session()->put('two_factor.method', $selectedMethod);
        }
        
        return Inertia::render("Auth/TwoFactorChallenge", [
            "methods"=> $methods,
            "selectedMethod"=> $selectedMethod,
            "maskedEmail"=> $this->twoFactorService->maskedEmail($user->email),
            "trans"=> trans("auth/two-factor")
        ]);
        
    }
    
    public function changeMethod(Request $request): RedirectResponse {
        $user = $this->pendingUser($request);
        
        if (!$user) {
            return redirect()->route("login");
        }
        
        $methods = collect($this->twoFactorService->availableMethods($user))->pluck("value")->all();
        
        $validated = $request->validate([
            "method"=> ["required", "string", Rule::in($methods)]
        ]);
        
        $request->session()->put("two_factor.method", $validated["method"]);
        
        if ($validated["method"] === "email") {
            $this->twoFactorService->sendEmailCode($user);
        }
        
        return back();
    }
    
    public function sendEmailCode(Request $request): RedirectResponse {
        $user = $this->pendingUser($request);
        
        if (!$user) {
            return redirect()->route("login");
        }
        
        $this->twoFactorService->sendEmailCode($user);
        
        return back()->with("success", trans("auth/two-factor.email_code_sent"));
    }
    
    public function verify(Request $request): RedirectResponse {
        $user = $this->pendingUser($request);
        
        if (!$user) {
            return redirect()->route("login");
        }
        
        $method = $request->session()->get("two_factor.method");
        
        $validated = $request->validate([
            "code"=> ["required_unless:method,passkey", "nullable", "string", "size:6"],
            "method"=> ["required", "string", Rule::in(["email", "one_time_code", "passkey"])]
        ]);
        
        if (!$this->twoFactorService->verify($user, $method, $validated["code"] ?? null)) {
            throw ValidationException::withMessages([
                "code"=> trans("auth/two-factor.errors.invalid_code")
            ]);
        }
        
        Auth::login($user, $request->session()->pull("two_factor.remember", false));
        
        $request->session()->forget("two_factor");
        $request->session()->regenerate();
        
        return redirect()->intended("/");
    }
    
    public function passkeyOptions(AssertionRequest  $request) {
        $user = $this->pendingUser($request);
        
        if (!$user) {
            abort(403);
        }
        
        return $request->secureLogin()->toVerify([
            'email' => $user->email,
        ]);
    }
    
    public function verifyPasskey(AssertedRequest $request): JsonResponse {
        $pendingUser = $this->pendingUser($request);
        
        if (!$pendingUser) {
            return response()->json([
                "success" => false,
                "message" => trans("auth/two-factor.errors.invalid_passkey"),
            ], 403);
        }
        
        $assertedUser = $request->login(
            remember: $request->session()->get("two_factor.remember", false),
            destroySession: false,
            callbacks: [
                fn ($user) => $user->is($pendingUser),
            ],
        );
        
        if (!$assertedUser || !$assertedUser->is($pendingUser)) {
            return response()->json([
                "success" => false,
                "message" => trans("auth/two-factor.errors.invalid_passkey"),
                "errors" => [
                    "passkey" => [trans("auth/two-factor.errors.invalid_passkey")],
                ],
            ], 422);
        }
        
        $request->session()->forget("two_factor");
        $request->session()->regenerate();
        
        return response()->json([
            "success" => true,
            "redirect" => url()->previous() !== route("two-factor.show")
                ? url()->previous()
                : url("/"),
            "message" => trans("auth/two-factor.success.authenticated"),
        ]);
    }
    
    private function pendingUser(Request $request): ?User {
        $userId = $request->session()->get("two_factor.user_id");
        
        if (!$userId) {
            return null;
        }
        
        return User::query()->with("settings")->find($userId);
    }
}
